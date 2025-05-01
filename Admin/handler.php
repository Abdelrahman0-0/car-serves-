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

// التحقق من أن الطلب يحتوي على الإجراء المناسب
if (isset($_GET['action']) && $_GET['action'] === 'fetch_reservations') {
    // الحصول على التواريخ من النموذج
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];

    // التحقق من صحة التواريخ
    if (empty($start_date) || empty($end_date)) {
        die("Both start date and end date are required.");
    }

    // استعلام لاسترداد الحجوزات في الفترة الزمنية المحددة
    $query = "
        SELECT 
            Reservation.ReservationID,
            Reservation.StartDate,
            Reservation.EndDate,
            Reservation.TotalCost,
            Reservation.Status,
            Customer.Name AS CustomerName,
            Customer.Email AS CustomerEmail,
            Customer.Phone AS CustomerPhone,
            Car.Model AS CarModel,
            Car.Year AS CarYear,
            Car.PlateID AS CarPlateID
        FROM Reservation
        INNER JOIN Customer ON Reservation.CustomerID = Customer.CustomerID
        INNER JOIN Car ON Reservation.CarID = Car.CarID
        WHERE Reservation.StartDate >= ? AND Reservation.EndDate <= ?
        ORDER BY Reservation.StartDate ASC;
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // عرض النتائج في جدول HTML
    if ($result->num_rows > 0) {
        echo "<h2>Reservations from $start_date to $end_date</h2>";
        echo "<table border='1'>
                <tr>
                    <th>Reservation ID</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Cost</th>
                    <th>Status</th>
                    <th>Customer Name</th>
                    <th>Customer Email</th>
                    <th>Customer Phone</th>
                    <th>Car Model</th>
                    <th>Car Year</th>
                    <th>Car Plate ID</th>
                </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['ReservationID']}</td>
                    <td>{$row['StartDate']}</td>
                    <td>{$row['EndDate']}</td>
                    <td>{$row['TotalCost']}</td>
                    <td>{$row['Status']}</td>
                    <td>{$row['CustomerName']}</td>
                    <td>{$row['CustomerEmail']}</td>
                    <td>{$row['CustomerPhone']}</td>
                    <td>{$row['CarModel']}</td>
                    <td>{$row['CarYear']}</td>
                    <td>{$row['CarPlateID']}</td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No reservations found for the specified period.</p>";
    }

    // إغلاق الاتصال والاستعلام
    $stmt->close();
    $conn->close();
}
?>
