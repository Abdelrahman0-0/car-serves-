<?php
// إعدادات قاعدة البيانات
$servername = "localhost";
$username = "root"; // اسم المستخدم لقاعدة البيانات
$password = ""; // كلمة المرور لقاعدة البيانات
$dbname = "Rental3"; // اسم قاعدة البيانات

// إنشاء اتصال بقاعدة البيانات
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// اسم المستخدم وكلمة المرور المدخلة
$input_username = $_POST['username']; // اسم المستخدم المدخل
$input_password = $_POST['password']; // كلمة المرور المدخلة

// استعلام SQL للتحقق من اسم المستخدم أو البريد الإلكتروني
$sql = "SELECT * FROM admin WHERE name = ? OR email = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Error preparing the SQL query: ' . $conn->error);
}

// ربط المعاملات
$stmt->bind_param("ss", $input_username, $input_username);

// تنفيذ الاستعلام
$stmt->execute();
$result = $stmt->get_result();

// التحقق من وجود اسم المستخدم أو البريد الإلكتروني
if ($result->num_rows > 0) {
    // استرجاع البيانات من قاعدة البيانات
    $row = $result->fetch_assoc();

    // التحقق من كلمة المرور باستخدام password_verify()
    if (password_verify($input_password, $row['password'])) {
        // إذا كانت كلمة المرور صحيحة
        header("Location: Admin _Dashboard.html"); // تصحيح اسم الرابط
        exit();
    } else {
        // كلمة المرور غير صحيحة
        echo "Incorrect password. Please try again.";
    }
} else {
    // اسم المستخدم أو البريد الإلكتروني غير موجود
    echo "Username or email not found. Please try again.";
}

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>
