<?php
// الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root"; 
$password = "";
$dbname = "rental3"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على بيانات المستخدم من الجلسة
session_start();
$user_id = $_SESSION['user_id']; // معرف المستخدم من الجلسة

// استعلام لجلب بيانات العميل من قاعدة البيانات
$query = "SELECT * FROM customer WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// إذا تم العثور على العميل
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found!";
    exit();
}

// إغلاق الاتصال
$stmt->close();
$conn->close();
?>
