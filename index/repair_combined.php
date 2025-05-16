<?php
session_start();

// اتصال بقاعدة البيانات
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'car_services';

$conn = new mysqli($host, $user, $password, $database);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// معالجة البيانات المرسلة
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // إعداد صفحة HTML للعرض
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Repair Submission Result</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            h1 { color: #2c3e50; }
            .success { color: #27ae60; }
            .error { color: #e74c3c; }
            .service-item { margin-bottom: 15px; padding: 10px; background: #ecf0f1; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Repair Services Submission</h1>';
    
    // معالجة البيانات من النموذج
    $service_type = $_POST['service-type'] ?? 'Unknown';
    $service_price = $_POST['service-price'] ?? 0;
    $full_name = $_POST['full-name'] ?? 'Unknown';
    $phone = $_POST['phone'] ?? 'Unknown';
    $address = $_POST['address'] ?? 'Unknown';
    $car_model = $_POST['car-model'] ?? 'Unknown';
    $plate_number = $_POST['plate-number'] ?? 'Unknown';
    $problem_description = $_POST['problem'] ?? 'No description provided';
    
    // البحث عن CarID أو إضافة سيارة جديدة
    $car_id = null;
    $find_car_sql = "SELECT CarID FROM Car WHERE PlateNumber = ?";
    $stmt = $conn->prepare($find_car_sql);
    $stmt->bind_param("s", $plate_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $car_id = $row['CarID'];
    } else {
        // إضافة سيارة جديدة مع قيم افتراضية للحقول المطلوبة
        $insert_car_sql = "INSERT INTO Car (CarName, Model, Year, Color, Mileage, PlateNumber, Status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'maintenance')";
        $stmt = $conn->prepare($insert_car_sql);
        $car_name = explode(' ', $car_model)[0] ?? 'Unknown';
        $year = date('Y'); // سنة افتراضية
        $color = 'Unknown';
        $mileage = 0;
        $stmt->bind_param("ssisis", $car_name, $car_model, $year, $color, $mileage, $plate_number);
        $stmt->execute();
        $car_id = $stmt->insert_id;
    }
    
    // إدراج سجل الصيانة
    $insert_sql = "INSERT INTO Maintenance (CarID, MaintenanceType, Description, Cost, Status) 
                  VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("issd", $car_id, $service_type, $problem_description, $service_price);
    
    if ($stmt->execute()) {
        echo '<div class="service-item success">
            <h3>'.htmlspecialchars($service_type).'</h3>
            <p>Car: '.htmlspecialchars($car_model).' ('.htmlspecialchars($plate_number).')</p>
            <p>Customer: '.htmlspecialchars($full_name).'</p>
            <p>Cost: $'.htmlspecialchars($service_price).'</p>
            <p><strong>Successfully submitted!</strong></p>
        </div>';
        // بعد نجاح إدخال الصيانة (بعد $stmt->execute())
require_once 'order_logger.php';
logOrder($conn, $full_name, $car_model . ' (' . $plate_number . ')', 'repair', $service_price);
    } else {
        echo '<div class="service-item error">
            <h3>'.htmlspecialchars($service_type).'</h3>
            <p>Error: '.htmlspecialchars($stmt->error).'</p>
        </div>';
    }
    
    echo '</div></body></html>';
} else {
    echo "<p style='color: red;'>No data received.</p>";
}

$conn->close();
?>