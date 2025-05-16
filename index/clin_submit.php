<?php

session_start();

// Database connection settings
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'car_services';

// Connect to MySQL
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Function to clean input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type'])) {
    $formType = clean_input($_POST['form_type']);

    if ($formType === 'feedback') {
        // Process feedback form
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $serviceType = clean_input($_POST['service-type']);
        $rating = (int)clean_input($_POST['rating']);
        $feedback = clean_input($_POST['feedback']);
        
        // Validate inputs
        if (empty($name) || empty($email) || empty($serviceType) || empty($feedback) || $rating < 1 || $rating > 5) {
            header("Location: feedBack.html?status=error");
            exit();
        }
        
        // Check if customer exists
        $customerId = null;
        $checkCustomer = $conn->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
        if ($checkCustomer) {
            $checkCustomer->bind_param("s", $email);
            $checkCustomer->execute();
            $result = $checkCustomer->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $customerId = $row['CustomerID'];
            }
            $checkCustomer->close();
        }
    
        // Insert feedback - make sure column names match your database exactly
        $stmt = $conn->prepare("INSERT INTO Feedback 
                              (CustomerID, customer_name, customer_email, service_type, Rating, Comments, feedback_details, SubmissionDate) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        
        $bindResult = $stmt->bind_param("isssiss", $customerId, $name, $email, $serviceType, $rating, $feedback, $feedback);
        
        if (!$bindResult) {
            die("Bind failed: " . $stmt->error);
        }
        
        if ($stmt->execute()) {
            header("Location: feedBack.html?status=success");
        } else {
            header("Location: feedBack.html?status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
        
    } elseif ($formType === 'contact') {
        // Process contact form
        $name = clean_input($_POST['name']);
        $email = clean_input($_POST['email']);
        $message = clean_input($_POST['message']);
        $phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : null;
        $subject = "Contact Form Submission";
        
        // Validate inputs
        if (empty($name) || empty($email) || empty($message)) {
            header("Location: About.html?status=error");
            exit();
        }
        
        // Insert contact
        $stmt = $conn->prepare("INSERT INTO Contact 
                              (Name, Email, Phone, Subject, Message, SubmissionDate, Status) 
                              VALUES (?, ?, ?, ?, ?, NOW(), 'new')");
        
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        
        $bindResult = $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        
        if (!$bindResult) {
            die("Bind failed: " . $stmt->error);
        }
        
        if ($stmt->execute()) {
            header("Location: About.html?status=success");
        } else {
            header("Location: About.html?status=error&message=" . urlencode($stmt->error));
        }
        $stmt->close();
        
    } else {
        die("Unknown form type");
    }
} else {
    die("Invalid request");
}

$conn->close();
?>