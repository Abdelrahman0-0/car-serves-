# -- coding: utf-8 -*-
"""
Spam Classification: Compare RandomForest, LogisticRegression, SVM, XGBoost, and LightGBM
with TF-IDF, SMOTE, and hyperparameter tuning. Evaluates accuracy, F1, precision, recall, and time.
Saves models, prediction results, and reports.
"""
import pandas as pd
import numpy as np
import re
import time
import warnings
import os
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

# Initialize lemmatizer and stopwords
lemmatizer = WordNetLemmatizer()
stop_words = set(stopwords.words('english'))

def clean_text(text):
    """
    Clean the input text by lowering case, removing non-alphabetic characters,
    removing stopwords, and applying lemmatization.
    """
    text = text.lower()
    text = re.sub(r'[^a-zA-Z]', ' ', text)  # remove non-letters
    tokens = nltk.word_tokenize(text)
    tokens = [
        lemmatizer.lemmatize(token) 
        for token in tokens 
        if token not in stop_words and token.isalpha()
    ]
    return ' '.join(tokens)

def save_artifacts(model, vectorizer, model_name, save_dir='saved_models'):
    """
    Save model and vectorizer to files
    """
    if not os.path.exists(save_dir):
        os.makedirs(save_dir)
    
    # Save model
    model_path = os.path.join(save_dir, f"{model_name}_model.pkl")
    dump(model, model_path)
    
    # Save vectorizer
    vectorizer_path = os.path.join(save_dir, f"{model_name}_vectorizer.pkl")
    dump(vectorizer, vectorizer_path)
    
    print(f"Saved {model_name} artifacts to {save_dir}")

def load_artifacts(model_name, save_dir='saved_models'):
    """
    Load model and vectorizer from files
    Returns: (model, vectorizer) or (None, None) if files not found
    """
    try:
        model_path = os.path.join(save_dir, f"{model_name}_model.pkl")
        vectorizer_path = os.path.join(save_dir, f"{model_name}_vectorizer.pkl")
        
        model = load(model_path)
        vectorizer = load(vectorizer_path)
        
        print(f"Loaded {model_name} artifacts from {save_dir}")
        return model, vectorizer
    except FileNotFoundError as e:
        print(f"Error loading artifacts: {e}")
        return None, None

def evaluate_model(model, X_test, y_test):
    """
    Evaluate model performance and return metrics
    """
    start_pred = time.time()
    y_pred = model.predict(X_test)
    pred_time = time.time() - start_pred
    
    acc = accuracy_score(y_test, y_pred)
    prec = precision_score(y_test, y_pred, zero_division=0)
    rec = recall_score(y_test, y_pred, zero_division=0)
    f1 = f1_score(y_test, y_pred, zero_division=0)
    
    return {
        'Accuracy': acc,
        'Precision': prec,
        'Recall': rec,
        'F1-score': f1,
        'Pred Time (s)': pred_time
    }

def main():
    # 1. Load data
    df = pd.read_csv('spam.csv')
    
    # Data preparation
    if 'v1' in df.columns and 'v2' in df.columns:
        df = df.rename(columns={'v1': 'label', 'v2': 'text'})
    
    if df['label'].dtype == object:
        unique_vals = set(df['label'].unique())
        if unique_vals <= {'ham', 'spam'}:
            df['label'] = df['label'].map({'ham': 0, 'spam': 1})
        else:
            df['label'] = df['label'].astype(int)
    
    df = df.dropna(subset=['text', 'label'])
    
    # 2. Text preprocessing
    print("Cleaning text data...")
    df['cleaned'] = df['text'].apply(clean_text)
    
    X = df['cleaned']
    y = df['label']
    X_raw = df['text']
    
    # Train-test split
    X_train_clean, X_test_clean, X_train_raw, X_test_raw, y_train, y_test = train_test_split(
        X, X_raw, y, test_size=0.2, random_state=42, stratify=y
    )
    
    # 3. TF-IDF Vectorization
    print("Applying TF-IDF vectorization...")
    vectorizer = TfidfVectorizer()
    X_train_tfidf = vectorizer.fit_transform(X_train_clean)
    X_test_tfidf = vectorizer.transform(X_test_clean)
    
    # 4. Handle class imbalance with SMOTE
    print("Applying SMOTE to balance classes...")
    smote = SMOTE(random_state=42)
    X_train_res, y_train_res = smote.fit_resample(X_train_tfidf.toarray(), y_train)
    
    # 5. Define models and hyperparameters
    models = {
        'LogisticRegression': LogisticRegression(random_state=42, max_iter=1000),
        'RandomForest': RandomForestClassifier(random_state=42),
        'SVM': SVC(random_state=42, probability=True),
        'XGBoost': XGBClassifier(random_state=42, use_label_encoder=False, eval_metric='logloss'),
        'LightGBM': LGBMClassifier(random_state=42)
    }
    
    params = {
        'LogisticRegression': {'C': [0.01, 0.1, 1, 10], 'solver': ['liblinear']},
        'RandomForest': {'n_estimators': [100, 200], 'max_depth': [None, 10, 20]},
        'SVM': {'C': [0.1, 1], 'kernel': ['linear', 'rbf']},
        'XGBoost': {'learning_rate': [0.01, 0.1], 'n_estimators': [100, 200], 'max_depth': [3, 5]},
        'LightGBM': {'learning_rate': [0.01, 0.1], 'n_estimators': [100, 200], 'num_leaves': [31, 50]}
    }
    
    results = []
    
    # 6. Train, tune, and evaluate each model
    for name, model in models.items():
        print(f"\nTraining {name}...")
        grid = GridSearchCV(model, params[name], cv=5, scoring='accuracy', n_jobs=-1)
        
        start_time = time.time()
        grid.fit(X_train_res, y_train_res)
        train_time = time.time() - start_time
        
        best_model = grid.best_estimator_
        print(f"{name} best params: {grid.best_params_}")
        print(f"{name} training time: {train_time:.2f} seconds")
        
        # Save model and vectorizer
        save_artifacts(best_model, vectorizer, name)
        
        # Evaluate model
        metrics = evaluate_model(best_model, X_test_tfidf.toarray(), y_test)
        
        print(f"{name} Accuracy: {metrics['Accuracy']:.4f}, Precision: {metrics['Precision']:.4f}, "
              f"Recall: {metrics['Recall']:.4f}, F1-score: {metrics['F1-score']:.4f}")
        print(f"{name} prediction time: {metrics['Pred Time (s)']:.4f} seconds")
        
        # Save results
        results.append({
            'Model': name,
            **metrics,
            'Train Time (s)': train_time
        })
        
        # Save predictions and report
        df_pred = pd.DataFrame({
            'Text': X_test_raw.tolist(),
            'Actual': y_test.tolist(),
            'Predicted': best_model.predict(X_test_tfidf.toarray()).tolist()
        })
        df_pred.to_csv(f"predictions_{name}.csv", index=False)
        
        report = classification_report(y_test, best_model.predict(X_test_tfidf.toarray()), zero_division=0)
        with open(f"classification_report_{name}.txt", "w") as f:
            f.write(f"Classification Report for {name}\n")
            f.write(f"Accuracy: {metrics['Accuracy']:.4f}\n\n")
            f.write(report)
    
    # 7. Compare results
    results_df = pd.DataFrame(results)
    results_df = results_df.sort_values(by='Accuracy', ascending=False)
    print("\nModel comparison:")
    print(results_df)
    results_df.to_csv('model_comparison_results.csv', index=False)

if __name__ == "__main__":
    main()