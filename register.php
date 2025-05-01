<?php
// الاتصال بقاعدة البيانات
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "rental3"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// التحقق من إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // استلام البيانات من النموذج
    $name = $_POST['customer-name'];
    $email = $_POST['customer-email'];
    $phone = $_POST['customer-phone'];
    $password = $_POST['customer-password'];
    $address = $_POST['customer-address'];

    // تشفير كلمة المرور باستخدام password_hash
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // تحضير استعلام SQL لإدخال البيانات في جدول العملاء
    $sql = "INSERT INTO Customer (Name, Email, Phone, Password, Address) 
            VALUES ('$name', '$email', '$phone', '$hashedPassword', '$address')";

    // تنفيذ الاستعلام والتحقق من النتيجة
    if ($conn->query($sql) === TRUE) {
        header("Location: login.html");
    } else {
        echo "Somthin Erorr!!: " . $conn->error;
    }
}


// إغلاق الاتصال
$conn->close();
?>
