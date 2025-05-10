<?php
// Database connection settings
$host = 'localhost';
$user = 'your_db_user';
$password = 'your_db_password';
$database = 'your_database_name';

// Connect to MySQL
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Determine which form is being submitted
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type'])) {
    $formType = $_POST['form_type'];

    if ($formType === 'feedback'){
        $name = $_POST['name'];
        $email = $_POST['email'];
        $serviceType = $_POST['service-type'];
        $rating = $_POST['rating'];
        $feedback = $_POST['feedback'];
    
        $stmt = $conn->prepare("INSERT INTO feedback (name, email, service_type, rating, feedback_text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $name, $email, $serviceType, $rating, $feedback);
        $stmt->execute();
    
        echo "Feedback submitted successfully!";
        
    } elseif ($formType === 'contact') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $message = $_POST['message'];
        $phone = $_POST['phone'];

        $stmt = $conn->prepare("INSERT INTO contact (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);
        $stmt->execute();
        echo "Your contact message has been sent!";

    } elseif ($formType === 'sales') {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $car_model = $_POST['car_model'];
        $year = $_POST['year'];
        $condition = $_POST['condition'];
        $price = $_POST['price'];
    
        // Handle image upload
        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === 0) {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir);
    
            $fileName = basename($_FILES['car_image']['name']);
            $targetPath = $uploadDir . uniqid() . "_" . $fileName;
    
            if (move_uploaded_file($_FILES['car_image']['tmp_name'], $targetPath)) {
                // Save data including image path
                $stmt = $conn->prepare("INSERT INTO car_sales (email, phone, name, address, car_model, year, car_condition, price, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssis", $email, $phone, $name, $address, $car_model, $year, $condition, $price, $targetPath);
                $stmt->execute();
                echo "Car sale request submitted!";
            } else {
                echo "Failed to upload image.";
            }
        } else {
            echo "No image uploaded or upload error.";
        }
    } else {
        echo "Unknown form type.";
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();
?>
