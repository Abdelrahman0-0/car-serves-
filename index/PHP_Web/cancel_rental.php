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

    // التحقق من وجود الحجز
    $check_query = "SELECT CarID FROM Reservation WHERE ReservationID = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $car_id = $row['CarID'];

        if ($car_id) {
            // إلغاء الحجز
            $cancel_query = "DELETE FROM Reservation WHERE ReservationID = ?";
            $cancel_stmt = $conn->prepare($cancel_query);
            $cancel_stmt->bind_param("i", $reservation_id);

            if ($cancel_stmt->execute()) {
                // تحديث حالة السيارة إلى "Active"
                $update_query = "UPDATE Car SET Status = 'Active' WHERE CarID = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("i", $car_id);

                if ($update_stmt->execute()) {
                    echo "<p>Reservation ID $reservation_id has been canceled successfully, and car status updated to Active for CarID $car_id.</p>";
                } else {
                    echo "<p>Error updating car status: " . $update_stmt->error . "</p>";
                }

                $update_stmt->close();
            } else {
                echo "<p>Error cancelling the reservation: " . $cancel_stmt->error . "</p>";
            }

            $cancel_stmt->close();
        } else {
            echo "<p>Error fetching CarID.</p>";
        }
    } else {
        echo "<p>Reservation ID not found.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
