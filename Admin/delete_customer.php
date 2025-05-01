<?php
$servername = "localhost";
$username = "root"; // عدل بناءً على إعداداتك
$password = ""; // عدل بناءً على إعداداتك
$dbname = "rental3";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql = "DELETE FROM Customer WHERE CustomerID = $delete_id";
    if ($conn->query($sql) === TRUE) {
        echo "تم حذف العميل بنجاح!";
    } else {
        echo "فشل في حذف العميل: " . $conn->error;
    }
} else {
    echo "رقم العميل غير محدد.";
}

$conn->close();
?>
