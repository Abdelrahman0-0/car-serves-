<?php
// الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rental3";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // الحقول المطلوبة
    $requiredFields = ['CarName', 'Model', 'Year', 'PlateID', 'Status', 'location', 'price'];

    // التحقق من وجود جميع الحقول
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }

    // إذا كان هناك حقول مفقودة، أظهر رسالة خطأ
    if (!empty($missingFields)) {
        echo "Error: Missing required fields: " . implode(', ', $missingFields);
        exit;
    }

    // استلام القيم من الطلب
    $carName = trim($_POST['CarName']);
    $model = trim($_POST['Model']);
    $year = intval($_POST['Year']);
    $plateID = trim($_POST['PlateID']);
    $status = trim($_POST['Status']);
    $officeLoc = trim($_POST['location']);
    $price = intval($_POST['price']);

    // إعداد استعلام الإدراج
    $sql = "INSERT INTO car (CarName, Model, Year, PlateID, Status, Location, price) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error in prepare statement: " . $conn->error);
    }

    // ربط القيم بالاستعلام
    $stmt->bind_param('ssisssi', $carName, $model, $year, $plateID, $status, $officeLoc, $price);

    // تنفيذ الاستعلام
    if ($stmt->execute()) {
        echo "Car added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // إغلاق الجملة
    $stmt->close();
}

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>
