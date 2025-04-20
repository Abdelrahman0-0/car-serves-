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

// التحقق من أن الطلب يحتوي على التاريخ
if (isset($_GET['date'])) {
    $date = $_GET['date'];

    // التحقق من صحة التاريخ
    if (empty($date)) {
        die("Date is required.");
    }

    // استعلام لاستخراج حالة السيارات في التاريخ المحدد
    $query = "
        SELECT 
            Car.CarID,
            Car.Model,
            Car.Year,
            Car.PlateID,
            CASE
                WHEN Reservation.StartDate <= ? AND Reservation.EndDate >= ? THEN 'Reserved'
                ELSE 'Available'
            END AS CarStatus
        FROM Car
        LEFT JOIN Reservation ON Car.CarID = Reservation.CarID 
            AND (
                (Reservation.StartDate <= ? AND Reservation.EndDate >= ?)  -- حاله السيارة في اليوم المطلوب
                OR Reservation.StartDate IS NULL  -- إذا لم يكن هناك حجز للسيارة في هذا اليوم
            )
        ORDER BY Car.Model;
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }

    // ربط المعاملات
    $stmt->bind_param("ssss", $date, $date, $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    // عرض النتائج في جدول HTML
    echo "<h2>Car Status for $date</h2>";
    echo "<table border='1'>
            <tr>
                <th>Car ID</th>
                <th>Car Model</th>
                <th>Car Year</th>
                <th>Car Plate ID</th>
                <th>Car Status</th>
            </tr>";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['CarID']}</td>
                    <td>{$row['Model']}</td>
                    <td>{$row['Year']}</td>
                    <td>{$row['PlateID']}</td>
                    <td>{$row['CarStatus']}</td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No car status available for the specified date.</p>";
    }

    // إغلاق الاتصال
    $stmt->close();
    $conn->close();
}
?>
