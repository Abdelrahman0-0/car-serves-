<?php

session_start();
if (!isset($_SESSION['user_data'])) {
    header("Location: Car Reservation.html");
    exit();
}

// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "car_services"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// التحقق من وجود جميع الحقول المطلوبة
$required_fields = ['customer_name', 'phone', 'email', 'car_model', 'car_name', 'plate_id', 'start_date', 'end_date'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        die("Error: Missing required field - $field");
    }
}

// تنظيف البيانات
$customer_name = $conn->real_escape_string($_POST['customer_name']);
$phone = $conn->real_escape_string($_POST['phone']);
$email = $conn->real_escape_string($_POST['email']);
$address = isset($_POST['address']) ? $conn->real_escape_string($_POST['address']) : '';
$car_model = $conn->real_escape_string($_POST['car_model']);
$car_name = $conn->real_escape_string($_POST['car_name']);
$plate_id = $conn->real_escape_string($_POST['plate_id']);
$start_date = $conn->real_escape_string($_POST['start_date']);
$end_date = $conn->real_escape_string($_POST['end_date']);

// 1. معالجة العميل
$customer_id = 0;
$sql = "SELECT CustomerID FROM Customer WHERE Email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $customer_id = $row['CustomerID'];
} else {
    $sql = "INSERT INTO Customer (Name, Email, Phone, Address, Password, Status) 
            VALUES (?, ?, ?, ?, 'defaultpassword', 'active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $customer_name, $email, $phone, $address);
    if (!$stmt->execute()) {
        die("Error creating customer: " . $stmt->error);
    }
    $customer_id = $stmt->insert_id;
}

// 2. التحقق من السيارة
$sql = "SELECT CarID, PricePerDay FROM Car 
        WHERE Model = ? AND CarName = ? AND PlateNumber = ? AND Status = 'available'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $car_model, $car_name, $plate_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: The selected car ($car_name $car_model - $plate_id) is not available or details are incorrect");
}

$row = $result->fetch_assoc();
$car_id = $row['CarID'];
$daily_cost = $row['PricePerDay'];

// 3. حساب التكلفة والمدة
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$interval = $date1->diff($date2);
$num_days = $interval->days;
if ($num_days < 1) {
    die("Error: Rental period must be at least 1 day");
}
$total_cost = $daily_cost * $num_days;

// 4. تسجيل الحجز
$conn->begin_transaction();
try {
    // أ) تسجيل الحجز
    $sql = "INSERT INTO Rental (CustomerID, CarID, StartDate, EndDate, TotalCost, Status, PickupLocation) 
            VALUES (?, ?, ?, ?, ?, 'reserved', 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissd", $customer_id, $car_id, $start_date, $end_date, $total_cost);
    if (!$stmt->execute()) {
        throw new Exception("Error creating rental: " . $stmt->error);
    }
    $rental_id = $stmt->insert_id;

    // ب) تحديث حالة السيارة
    $sql = "UPDATE Car SET Status = 'rented' WHERE CarID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $car_id);
    if (!$stmt->execute()) {
        throw new Exception("Error updating car status: " . $stmt->error);
    }

    // ج) تسجيل في جدول الطلبات
    $car_info = "$car_name $car_model ($plate_id)";
    $sql = "INSERT INTO orders (client_name, car_info, type, date, status, price) 
            VALUES (?, ?, 'rental', ?, 'pending', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssd", $customer_name, $car_info, $start_date, $total_cost);
    if (!$stmt->execute()) {
        throw new Exception("Error creating order record: " . $stmt->error);
    }

    $conn->commit();

    // رسالة النجاح
    echo "<div style='text-align: center; padding: 20px; border: 1px solid green; border-radius: 5px; margin: 20px;'>";
    echo "<h2 style='color: green;'>Reservation Successful!</h2>";
    echo "<p><strong>Reservation ID:</strong> $rental_id</p>";
    echo "<p><strong>Total Cost:</strong> $total_cost SAR</p>";
    echo "<a href='pp.html?rental_id=$rental_id&total_cost=$total_cost' style='display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 15px;'>Proceed to Payment</a>";
    echo "</div>";

} catch (Exception $e) {
    $conn->rollback();
    die("Error processing reservation: " . $e->getMessage());
}

$conn->close();
?>