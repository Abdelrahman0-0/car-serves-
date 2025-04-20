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

// التحقق من أن الطلب هو للمدفوعات اليومية
if (isset($_GET['action']) && $_GET['action'] === 'fetch_daily_payments') {
    // جلب التاريخ من الطلب
    $date = $_GET['date'];

    // استعلام SQL لجلب المدفوعات اليومية
    $sql = "SELECT PaymentDate AS payment_date, SUM(Amount) AS total_amount 
            FROM Payment 
            WHERE PaymentDate = ? 
            GROUP BY PaymentDate 
            ORDER BY PaymentDate";

    // تجهيز الاستعلام
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date); // تمرير التاريخ الواحد فقط
    $stmt->execute();
    $result = $stmt->get_result();

    // عرض النتائج
    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th>Date</th>
                    <th>Total Payments</th>
                </tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['payment_date']) . "</td>
                    <td>" . htmlspecialchars($row['total_amount']) . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No payments found for the selected date.</p>";
    }

    // إغلاق الموارد
    $stmt->close();
    $conn->close();
} else {
    echo "<p>Invalid action specified.</p>";
}
?>
