<?php
// payment.php
// بدء الجلسة لتخزين البيانات
session_start();

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

// التحقق من أن جميع الحقول ممتلئة
if (empty($_POST['Amount']) || empty($_POST['PaymentDate']) || empty($_POST['PaymentMethod']) || empty($_POST['ReservationID'])) {
    die("Please fill in all the fields.");
}

// استلام البيانات من النموذج
$Amount = $_POST['Amount'];
$PaymentDate = $_POST['PaymentDate'];
$PaymentMethod = $_POST['PaymentMethod'];
$ReservationID = $_POST['ReservationID'];

// تحقق من وجود رقم الحجز في قاعدة البيانات
$sql_check_reservation = "SELECT * FROM Reservation WHERE ReservationID = '$ReservationID'";
$result_reservation = $conn->query($sql_check_reservation);

if ($result_reservation->num_rows > 0) {
    // إدخال بيانات الدفع إلى جدول الدفع
    $sql_insert_payment = "INSERT INTO Payment (ReservationID, Amount, PaymentDate, PaymentMethod) 
                           VALUES ('$ReservationID', '$Amount', '$PaymentDate', '$PaymentMethod')";

    if ($conn->query($sql_insert_payment) === TRUE) {
        // تحديث حالة الحجز إلى "Paid"
        $sql_update_reservation = "UPDATE Reservation SET Status = 'Paid' WHERE ReservationID = '$ReservationID'";
        if ($conn->query($sql_update_reservation) === TRUE) {
            echo "Payment successful and reservation status updated.";
        } else {
            echo "Error updating reservation status: " . $conn->error;
        }
    } else {
        echo "Error inserting payment: " . $conn->error;
    }
} else {
    echo "Invalid Reservation ID.";
}

// إغلاق الاتصال
$conn->close();
?>
