<?php
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

// التحقق من إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // الحصول على البيانات من النموذج
    $reservation_id = intval($_POST['reservation_id']);
    $amount = floatval($_POST['Amount']);
    $payment_date = $_POST['paymentDate'];
    $payment_method = $_POST['paymentMethod'];
    $card_number = isset($_POST['CardNumber']) ? $_POST['CardNumber'] : null;
    $paypal_account = isset($_POST['PayPalAccount']) ? $_POST['PayPalAccount'] : null;
    $bank_account = isset($_POST['BankAccount']) ? $_POST['BankAccount'] : null;

    // التحقق من طريقة الدفع والحقول المطلوبة
    if ($payment_method == "Credit Card" && empty($card_number)) {
        echo "Error: Card number is required for credit card payments.";
        exit;
    } elseif ($payment_method == "PayPal" && empty($paypal_account)) {
        echo "Error: PayPal account is required for PayPal payments.";
        exit;
    } elseif ($payment_method == "Bank Transfer" && empty($bank_account)) {
        echo "Error: Bank account number is required for bank transfer payments.";
        exit;
    }

    // إدخال البيانات في جدول المدفوعات
    $query = "INSERT INTO payment (ReservationID, Amount, paymentDate, paymentMethod, CardNumber, PayPalAccount, BankAccount) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo "Failed to prepare statement: " . $conn->error;
        exit;
    }

    $stmt->bind_param(
        "idsssss", 
        $reservation_id, 
        $amount, 
        $payment_date, 
        $payment_method, 
        $card_number, 
        $paypal_account, 
        $bank_account
    );

    if ($stmt->execute()) {
        echo "Payment recorded successfully.";
        echo "<form action='index.html' method='POST'>
                        <button type='submit'>Go To Home</button>
                      </form>";
    } else {
        echo "Error: " . $stmt->error;
    }

    // إغلاق الاستعلام
    $stmt->close();
}

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>
