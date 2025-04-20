<?php
// إعداد الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rental3";

$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // التحقق من تاريخ البداية والنهاية
    if (strtotime($start_date) >= strtotime($end_date)) {
        echo "<p>End date must be after start date.</p>";
    } else {
        // التحقق من وجود الحجز
        $check_query = "SELECT * FROM Reservation WHERE ReservationID = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // تحديث مدة الحجز
            $update_query = "UPDATE Reservation SET StartDate = ?, EndDate = ? WHERE ReservationID = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $start_date, $end_date, $reservation_id);

            if ($update_stmt->execute()) {
                echo "<p>Reservation ID $reservation_id has been updated successfully.</p>";
            } else {
                echo "<p>Error updating reservation: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>Reservation ID not found.</p>";
        }
    }

    $stmt->close();
    $conn->close();
}
?>
