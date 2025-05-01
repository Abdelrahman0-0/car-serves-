# -*- coding: utf-8 -*-
"""
Spam Classification with API Endpoint
"""
import pandas as pd
import numpy as np
import re
import time
import warnings
import os
from flask import Flask, request, jsonify
from nltk.corpus import stopwords
from nltk.stem import WordNetLemmatizer
import nltk

from sklearn.model_selection import train_test_split, GridSearchCV
from sklearn.feature_extraction.text import TfidfVectorizer
from imblearn.over_sampling import SMOTE
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier
from sklearn.svm import SVC
from xgboost import XGBClassifier
from lightgbm import LGBMClassifier
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, classification_report
from joblib import dump, load

warnings.filterwarnings('ignore')

# Download NLTK data
nltk.download('stopwords')
nltk.download('wordnet')
nltk.download('omw-1.4')
nltk.download('punkt')

# Initialize Flask app
app = Flask(__name__)

# Initialize lemmatizer and stopwords
lemmatizer = WordNetLemmatizer()
stop_words = set(stopwords.words('english'))

def clean_text(text):
    """Clean text data"""
    text = text.lower()
    text = re.sub(r'[^a-zA-Z]', ' ', text)
    tokens = nltk.word_tokenize(text)
    tokens = [
        lemmatizer.lemmatize(token) 
        for token in tokens 
        if token not in stop_words and token.isalpha()
    ]
    return ' '.join(tokens)

def save_artifacts(model, vectorizer, model_name, save_dir='saved_models'):
    """Save model and vectorizer"""
    if not os.path.exists(save_dir):
        os.makedirs(save_dir)
    dump(model, os.path.join(save_dir, f"{model_name}_model.pkl"))
    dump(vectorizer, os.path.join(save_dir, f"{model_name}_vectorizer.pkl"))
    print(f"Saved {model_name} artifacts")

def load_artifacts(model_name, save_dir='saved_models'):
    """Load model and vectorizer"""
    try:
        model = load(os.path.join(save_dir, f"{model_name}_model.pkl"))
        vectorizer = load(os.path.join(save_dir, f"{model_name}_vectorizer.pkl"))
        return model, vectorizer
    except FileNotFoundError as e:
        print(f"Error loading artifacts: {e}")
        return None, None

# API Endpoint for Prediction
@app.route('/predict', methods=['POST'])
def predict():
    """API endpoint for spam classification"""
    data = request.get_json()
    if 'message' not in data:
        return jsonify({'error': 'No message provided'}), 400
    
    model_name = data.get('model', 'RandomForest')  # Default to RandomForest
    model, vectorizer = load_artifacts(model_name)
    
    if not model or not vectorizer:
        return jsonify({'error': 'Model not found'}), 404
    
    # Preprocess and predict
    cleaned_text = clean_text(data['message'])
    text_vector = vectorizer.transform([cleaned_text])
    prediction = model.predict(text_vector)[0]
    proba = model.predict_proba(text_vector)[0]
    
    return jsonify({
        'model': model_name,
        'prediction': int(prediction),
        'label': 'spam' if prediction == 1 else 'ham',
        'probability': {
            'spam': float(proba[1]),
            'ham': float(proba[0])
        }
    })

def train_and_save_models():
    """Train models and save artifacts"""
    df = pd.read_csv('spam_data.csv')
    
    # Data preparation
    if 'v1' in df.columns and 'v2' in df.columns:
        df = df.rename(columns={'v1': 'label', 'v2': 'text'})
    df['label'] = df['label'].map({'ham': 0, 'spam': 1}) if df['label'].dtype == object else df['label']
    
    # Text preprocessing
    df['cleaned'] = df['text'].apply(clean_text)
    X_train, X_test, y_train, y_test = train_test_split(
        df['cleaned'], df['label'], test_size=0.2, random_state=42, stratify=df['label']
    )
    
    # Vectorization
    vectorizer = TfidfVectorizer()
    X_train_tfidf = vectorizer.fit_transform(X_train)
    X_test_tfidf = vectorizer.transform(X_test)
    
    # Handle imbalance
    smote = SMOTE(random_state=42)
    X_res, y_res = smote.fit_resample(X_train_tfidf, y_train)
    
    # Model training
    models = {
        'RandomForest': RandomForestClassifier(random_state=42),
        'LogisticRegression': LogisticRegression(random_state=42, max_iter=1000),
        'SVM': SVC(random_state=42, probability=True),
        'XGBoost': XGBClassifier(random_state=42, use_label_encoder=False, eval_metric='logloss'),
        'LightGBM': LGBMClassifier(random_state=42)
    }
    
    for name, model in models.items():
        print(f"Training {name}...")
        model.fit(X_res, y_res)
        save_artifacts(model, vectorizer, name)
        print(f"Accuracy for {name}: {model.score(X_test_tfidf, y_test):.4f}")

if __name__ == "__main__":
    # First train and save models (comment out after first run)
    # train_and_save_models()
    
    # Then start the API server
    app.run(host='0.0.0.0', port=5000, debug=True)