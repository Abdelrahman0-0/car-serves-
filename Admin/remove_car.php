<?php
// تأكد من الاتصال بقاعدة البيانات
$servername = "localhost";  // اسم الخادم
$username = "root";         // اسم المستخدم
$password = "";             // كلمة المرور
$dbname = "rental3";        // اسم قاعدة البيانات

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على البيانات من النموذج
$model = $_POST['model'];
$plateID = $_POST['plateID'];
$year = $_POST['year'];

// التحقق من وجود السيارة في قاعدة البيانات
$sql_check_car = "SELECT * FROM Car WHERE Model = ? AND PlateID = ? AND Year = ?";
$stmt_check = $conn->prepare($sql_check_car);
$stmt_check->bind_param("ssi", $model, $plateID, $year);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // السيارة موجودة، قم بالحذف
    $sql = "DELETE FROM Car WHERE Model = ? AND PlateID = ? AND Year = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $model, $plateID, $year);

    if ($stmt->execute()) {
        echo "Car removed successfully";
    } else {
        echo "Error removing car: " . $stmt->error;
    }
} else {
    echo "No car found with the given details.";
}

// إغلاق الاتصال
$stmt->close();
$conn->close();
?>
