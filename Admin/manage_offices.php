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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'add') {
        // إضافة مكتب
        $location = $_POST['location'];
        $phone = $_POST['phone'];

        $sql = "INSERT INTO Office (Location, Phone) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error in prepare statement: " . $conn->error);
        }

        $stmt->bind_param('ss', $location, $phone);

        if ($stmt->execute()) {
            echo "<p class='message'>Office added successfully!</p>";
        } else {
            echo "<p class='message' style='color: red;'>Error: " . $stmt->error . "</p>";
        }

        $stmt->close();
    } elseif ($action === 'delete') {
        // حذف مكتب
        $office_id = $_POST['office_id'];

        $sql = "DELETE FROM Office WHERE OfficeID = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error in prepare statement: " . $conn->error);
        }

        $stmt->bind_param('i', $office_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<p class='message'>Office deleted successfully!</p>";
            } else {
                echo "<p class='message' style='color: red;'>No office found with the provided ID.</p>";
            }
        } else {
            echo "<p class='message' style='color: red;'>Error: " . $stmt->error . "</p>";
        }

        $stmt->close();
    }
}

// إغلاق الاتصال
$conn->close();
?>
