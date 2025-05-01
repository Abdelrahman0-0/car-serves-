<?php
// الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root"; // اسم المستخدم في قاعدة البيانات
$password = ""; // كلمة المرور في قاعدة البيانات
$dbname = "rental3"; // اسم قاعدة البيانات

$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Example for email login
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // استعلام SQL مباشر
    $sql = "SELECT * FROM Customer WHERE Email = '$email'";  // ملاحظة: هذا غير آمن ويعرض التطبيق لمخاطر SQL Injection

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Login successful
            header("Location: index.html");
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found with this email.";
    }
}

// غلق الاتصال بقاعدة البيانات
$conn->close();
?>
