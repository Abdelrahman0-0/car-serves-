<?php
// إعدادات قاعدة البيانات
$servername = "localhost";
$username = "root"; // اسم المستخدم لقاعدة البيانات
$password = ""; // كلمة المرور لقاعدة البيانات
$dbname = "rental3"; // اسم قاعدة البيانات

// إنشاء اتصال بقاعدة البيانات
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// التحقق من إرسال البيانات عبر POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // استلام البيانات المدخلة من النموذج
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // تشفير كلمة المرور قبل تخزينها
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // التحقق من عدم وجود نفس البريد الإلكتروني مسبقًا
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Email already exists. Please use a different email.";
    } else {
        // استعلام لإضافة بيانات المدير الجديد إلى قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO admin (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            echo "New admin added successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    // إغلاق الاتصال مع قاعدة البيانات
    $stmt->close();
    $conn->close();
}
?>
