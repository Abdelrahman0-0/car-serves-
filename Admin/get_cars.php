<?php
// لتفعيل إظهار الأخطاء في PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

// إعدادات الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root"; // اسم المستخدم الخاص بقاعدة البيانات
$password = ""; // كلمة المرور الخاصة بقاعدة البيانات
$dbname = "rental3"; // اسم قاعدة البيانات

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// التأكد من أن قاعدة البيانات والجدول موجودين
$sql_check_db = "SHOW TABLES LIKE 'car'";
$result_check_db = $conn->query($sql_check_db);

if ($result_check_db->num_rows == 0) {
    die("Table 'car' does not exist in the database.");
}

// استعلام لجلب بيانات السيارات
$sql = "SELECT * FROM car";
$result = $conn->query($sql);

// التحقق من وجود بيانات
$car = [];
if ($result && $result->num_rows > 0) {
    // إذا كان الاستعلام ناجحًا ويوجد نتائج
    while($row = $result->fetch_assoc()) {
        $car[] = $row;
    }
} else {
    // في حال لم يتم العثور على بيانات
    $car = ["message" => "No data found"];
}

// إرجاع البيانات بصيغة JSON
echo json_encode($car);

// غلق الاتصال
$conn->close();
?>
