<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_services";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getOrCreateCustomer($conn, $name, $email, $phone, $address) {
    $email_check = $conn->real_escape_string($email);
    $result = $conn->query("SELECT CustomerID FROM Customer WHERE Email = '$email_check' LIMIT 1");

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['CustomerID'];
    } else {
        $stmt = $conn->prepare("INSERT INTO Customer (Name, Email, Phone, Address, Password) VALUES (?, ?, ?, ?, ?)");
        $defaultPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt->bind_param("sssss", $name, $email, $phone, $address, $defaultPassword);
        $stmt->execute();
        return $stmt->insert_id;
    }
}

function getOrCreateCar($conn, $car_model, $year) {
    $model = $conn->real_escape_string($car_model);
    $result = $conn->query("SELECT CarID FROM Car WHERE Model = '$model' AND Year = $year LIMIT 1");

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['CarID'];
    } else {
        $defaultName = $model;
        $defaultPlate = uniqid("PLATE");
        $status = "available";
        $stmt = $conn->prepare("INSERT INTO Car (CarName, Model, Year, PlateNumber, Status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $defaultName, $model, $year, $defaultPlate, $status);
        $stmt->execute();
        return $stmt->insert_id;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? uniqid("noemail") . "@example.com";
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $car_model = $_POST['car_model'] ?? '';
    $car_name = $_POST['car_name'] ?? '';
    $year = intval($_POST['year']) ?: 2020;
    $final_price = floatval($_POST['total_price']) ?: 50000.00;
    $payment_method = $_POST['payment_method'] ?? 'cash';

    $customer_id = getOrCreateCustomer($conn, $name, $email, $phone, $address);
    $car_id = getOrCreateCar($conn, $car_model, $year);

    $stmt = $conn->prepare("INSERT INTO CustomerPurchase (CustomerID, CarID, FinalPrice, PaymentMethod, DeliveryAddress) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iidss", $customer_id, $car_id, $final_price, $payment_method, $address);
    
    if ($stmt->execute()) {
        echo "<p style='color: green; text-align: center;'>✅ Purchase request submitted successfully!</p>";
        // بعد نجاح الشراء (بعد $stmt->execute())
require_once 'order_logger.php';
$car_info = $car_model . ' ' . $year;
logOrder($conn, $name, $car_info, 'buy', $final_price, $conn->insert_id, 'purchase');
// بعد نجاح الشراء
$stmt = $conn->prepare("UPDATE Car SET Status = 'sold' WHERE CarID = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
    } 

    
    else {
        echo "<p style='color: red; text-align: center;'>❌ Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
$conn->close();
?>

