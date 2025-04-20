<?php
// تأكد من الاتصال بقاعدة البيانات
$servername = "localhost";  // اسم الخادم
$username = "root";         // اسم المستخدم
$password = "";             // كلمة المرور
$dbname = "Rental3";         // اسم قاعدة البيانات

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// تحقق من إذا كانت البيانات أُرسلت
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $model = $_POST['model'];
    $plateID = $_POST['plateID'];
    $year = $_POST['year'];
    $status = $_POST['status'];

    // استعلام للتحقق من وجود السيارة
    $check_sql = "SELECT * FROM Car WHERE Model = '$model' AND PlateID = '$plateID' AND Year = '$year'";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        // استعلام لتحديث حالة السيارة
        $update_sql = "UPDATE Car SET Status = '$status' WHERE Model = '$model' AND PlateID = '$plateID' AND Year = '$year'";

        if ($conn->query($update_sql) === TRUE) {
            // إذا كانت الحالة تم تحديثها إلى "متاحة" أو "خارج الخدمة"، احذف السيارة من جدول الحجوزات
            if ($status == 'active' || $status == 'out of service') {
                $delete_sql = "DELETE FROM Reservation WHERE CarID = (SELECT CarID FROM Car WHERE Model = '$model' AND PlateID = '$plateID' AND Year = '$year')";
                if ($conn->query($delete_sql) === TRUE) {
                    echo "Car status updated and car removed from reservations successfully!";
                } else {
                    echo "Error removing car from reservations: " . $conn->error;
                }
            } else {
                echo "Car status updated successfully!";
            }
        } else {
            echo "Error updating car status: " . $conn->error;
        }
    } else {
        echo "No car found with the provided details.";
    }
}
?>
