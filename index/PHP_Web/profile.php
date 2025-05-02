<?php
// بدء الجلسة للتحقق من تسجيل دخول العميل
session_start();

// التحقق من أن العميل قد سجل الدخول (افترض أن "customer_id" تم تخزينه في الجلسة بعد تسجيل الدخول)
if (!isset($_SESSION['customer_id'])) {
    // إذا لم يكن العميل مسجلاً الدخول، إعادة توجيه إلى صفحة الدخول
    header("Location: login.php");
    exit();
}

// الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Rental3";

$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على بيانات العميل من الجلسة
$customer_id = $_SESSION['customer_id'];

// استعلام للحصول على بيانات العميل من قاعدة البيانات
$sql = "SELECT * FROM Customer WHERE CustomerID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

// التحقق من أن العميل موجود
if ($result->num_rows > 0) {
    // استرجاع بيانات العميل
    $customer = $result->fetch_assoc();
} else {
    echo "Customer not found!";
    exit();
}

// استعلام للحصول على بيانات الحجز الخاص بالعميل
$sql_reservation = "SELECT * FROM Reservation WHERE CustomerID = ? ORDER BY StartDate DESC LIMIT 1"; // جلب آخر حجز فقط
$stmt_reservation = $conn->prepare($sql_reservation);
$stmt_reservation->bind_param("i", $customer_id);
$stmt_reservation->execute();
$reservation_result = $stmt_reservation->get_result();

// التحقق من وجود حجز
if ($reservation_result->num_rows > 0) {
    // استرجاع بيانات الحجز
    $reservation = $reservation_result->fetch_assoc();
} else {
    $reservation = null; // إذا لم يكن هناك حجز، ترك القيمة فارغة
}

$stmt->close();
$stmt_reservation->close();
$conn->close();
?>
