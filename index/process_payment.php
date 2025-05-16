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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // التحقق من البيانات المطلوبة
    $required_fields = ['RentalID', 'Amount', 'paymentDate', 'paymentMethod'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            die("Error: Missing required field - $field");
        }
    }

    // تنظيف البيانات
    $rental_id = intval($_POST['RentalID']);
    $amount = floatval($_POST['Amount']);
    $payment_date = $conn->real_escape_string($_POST['paymentDate']);
    $payment_method = $conn->real_escape_string($_POST['paymentMethod']);

    if ($rental_id <= 0) {
        die("Error: Invalid Rental ID");
    }

    if ($amount <= 0) {
        die("Error: Payment amount must be positive");
    }

    // التحقق من وجود الحجز
    $check_rental = $conn->prepare("SELECT RentalID, CarID FROM Rental WHERE RentalID = ? AND Status = 'reserved'");
    $check_rental->bind_param("i", $rental_id);
    $check_rental->execute();
    $check_result = $check_rental->get_result();

    if ($check_result->num_rows == 0) {
        die("Error: Rental not found or already processed");
    }
    $rental_data = $check_result->fetch_assoc();
    $car_id = $rental_data['CarID'];
    $check_rental->close();
    
    // بدء المعاملة
    $conn->begin_transaction();
    $transaction_id = 'TXN' . time() . rand(1000, 9999);

    try {
        // 1. تسجيل الدفع
        $payment_query = "INSERT INTO Payment (RentalID, Amount, PaymentDate, TransactionID, PaymentMethod, Status) 
                          VALUES (?, ?, ?, ?, ?, 'completed')";
        $stmt = $conn->prepare($payment_query);
        $stmt->bind_param("idsss", $rental_id, $amount, $payment_date, $transaction_id, $payment_method);
        if (!$stmt->execute()) {
            throw new Exception("Payment failed: " . $stmt->error);
        }
        $stmt->close();
        
        // 2. تحديث حالة الحجز
        $update_rental = $conn->prepare("UPDATE Rental SET Status = 'completed' WHERE RentalID = ?");
        $update_rental->bind_param("i", $rental_id);
        if (!$update_rental->execute()) {
            throw new Exception("Rental update failed: " . $update_rental->error);
        }
        $update_rental->close();
        
        // 3. تحديث حالة السيارة
        $update_car = $conn->prepare("UPDATE Car SET Status = 'rented' WHERE CarID = ?");
        $update_car->bind_param("i", $car_id);
        if (!$update_car->execute()) {
            throw new Exception("Car update failed: " . $update_car->error);
        }
        $update_car->close();
        
        // 4. تحديث حالة الطلب
        $update_order = $conn->prepare("UPDATE orders SET status = 'completed' 
                                       WHERE type = 'rental' AND car_info LIKE 
                                       (SELECT CONCAT(CarName, ' ', Model, ' (', PlateNumber, ')') 
                                        FROM Car WHERE CarID = ?)");
        $update_order->bind_param("i", $car_id);
        if (!$update_order->execute()) {
            throw new Exception("Order update failed: " . $update_order->error);
        }
        $update_order->close();
        
        $conn->commit();
        
        // رسالة النجاح
        echo "<div style='text-align: center; padding: 20px;'>";
        echo "<h2 style='color: #2ecc71;'>Payment Processed Successfully!</h2>";
        echo "<p><strong>Transaction ID:</strong> $transaction_id</p>";
        echo "<p><strong>Amount Paid:</strong> " . number_format($amount, 2) . " SAR</p>";
        echo "<form action='index.html' method='GET' style='margin-top: 20px;'>";
        echo "<button type='submit' style='padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;'>Go To Home</button>";
        echo "</form>";
        echo "</div>";     
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
    }
} else {
    die("Error: Invalid request method");
}

$conn->close();
?>