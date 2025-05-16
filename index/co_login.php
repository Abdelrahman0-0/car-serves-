<?php
session_start();

// الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_services";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT CustomerID, Name, Email, Password, Phone, Address, DrivingLicense FROM Customer WHERE LOWER(Email) = LOWER(?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['Password'])) {
            // تخزين جميع بيانات المستخدم في الجلسة
            $_SESSION['user_data'] = $user;
            header("Location: profile.php");
            exit();
        } else {
            header("Location: login.html?error=invalid_password");
            exit();
        }
    } else {
        header("Location: login.html?error=user_not_found");
        exit();
    }
    
    $stmt->close();
} else {
    header("Location: login.html?error=invalid_request");
    exit();
}

$conn->close();
?>