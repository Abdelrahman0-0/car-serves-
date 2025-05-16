<?php
session_start();

// التحقق من تسجيل دخول المستخدم
if (!isset($_SESSION['user_data'])) {
    header("Location: login.html");
    exit();
}

// اتصال قاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_services";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['user_data'];
$customer_id = $user['CustomerID'];

// معالجة طلبات النماذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحديث الملف الشخصي
    if (isset($_POST['name'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');
        $license = $conn->real_escape_string($_POST['license'] ?? '');
        
        $update_query = "UPDATE Customer SET 
                        Name = '$name',
                        Email = '$email',
                        Phone = '$phone',
                        Address = '$address',
                        DrivingLicense = '$license'
                        WHERE CustomerID = $customer_id";
        
        if ($conn->query($update_query)) {
            // تحديث بيانات المستخدم في الجلسة
            $user_query = "SELECT * FROM Customer WHERE CustomerID = $customer_id";
            $result = $conn->query($user_query);
            $_SESSION['user_data'] = $result->fetch_assoc();
            
            header("Location: profile.php?success=profile_updated");
            exit();
        } else {
            $error = "فشل في تحديث الملف الشخصي: " . $conn->error;
        }
    }
    
    // إلغاء الحجز
    if (isset($_POST['cancel_booking'])) {
        $rental_id = intval($_POST['rental_id']);
        
        $cancel_query = "UPDATE Rental SET Status = 'cancelled' 
                        WHERE RentalID = $rental_id AND CustomerID = $customer_id";
        
        if ($conn->query($cancel_query)) {
            $car_id_query = "SELECT CarID FROM Rental WHERE RentalID = $rental_id";
            $car_id_result = $conn->query($car_id_query);
            if ($car_id_result->num_rows > 0) {
                $car_id = $car_id_result->fetch_assoc()['CarID'];
                $conn->query("UPDATE Car SET Status = 'available' WHERE CarID = $car_id");
            }
            
            header("Location: profile.php?success=booking_cancelled");
            exit();
        } else {
            $error = "فشل في إلغاء الحجز: " . $conn->error;
        }
    }
    
    // تعديل الحجز
    if (isset($_POST['start_date'])) {
        $rental_id = intval($_POST['rental_id']);
        $start_date = $conn->real_escape_string($_POST['start_date']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        
        $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
        $car_query = "SELECT c.PricePerDay FROM Rental r JOIN Car c ON r.CarID = c.CarID WHERE r.RentalID = $rental_id";
        $car_result = $conn->query($car_query);
        $car_data = $car_result->fetch_assoc();
        $total_cost = $days * $car_data['PricePerDay'];
        
        $update_query = "UPDATE Rental SET 
                        StartDate = '$start_date',
                        EndDate = '$end_date',
                        TotalCost = $total_cost
                        WHERE RentalID = $rental_id AND CustomerID = $customer_id";
        
        if ($conn->query($update_query)) {
            header("Location: profile.php?success=booking_updated");
            exit();
        } else {
            $error = "فشل في تحديث الحجز: " . $conn->error;
        }
    }
}

// تهيئة المتغيرات
$current_rental = [];
$rental_history = [];
$maintenance_records = [];
$feedback_records = [];
$contact_records = [];

// الحصول على الحجز الحالي
$rental_query = "SELECT r.*, c.CarName, c.Model, c.Year, c.PlateNumber, c.PricePerDay 
                 FROM Rental r JOIN Car c ON r.CarID = c.CarID 
                 WHERE r.CustomerID = ? AND r.Status IN ('reserved', 'active') 
                 ORDER BY r.StartDate DESC LIMIT 1";
$stmt = $conn->prepare($rental_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$rental_result = $stmt->get_result();
if ($rental_result && $rental_result->num_rows > 0) {
    $current_rental = $rental_result->fetch_assoc();
}

// الحصول على سجل الحجوزات
$history_query = "SELECT r.*, c.CarName, c.Model, c.Year 
                  FROM Rental r JOIN Car c ON r.CarID = c.CarID 
                  WHERE r.CustomerID = ? AND r.Status = 'completed' 
                  ORDER BY r.StartDate DESC LIMIT 5";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$history_result = $stmt->get_result();
if ($history_result && $history_result->num_rows > 0) {
    $rental_history = $history_result->fetch_all(MYSQLI_ASSOC);
}

// الحصول على سجلات الصيانة
$maintenance_query = "SELECT m.*, c.CarName, c.Model 
                      FROM Maintenance m JOIN Car c ON m.CarID = c.CarID 
                      WHERE c.CarID IN (SELECT CarID FROM Rental WHERE CustomerID = ?) 
                      ORDER BY m.MaintenanceDate DESC LIMIT 5";
$stmt = $conn->prepare($maintenance_query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$maintenance_result = $stmt->get_result();
if ($maintenance_result && $maintenance_result->num_rows > 0) {
    $maintenance_records = $maintenance_result->fetch_all(MYSQLI_ASSOC);
}

// استبدل جزء استعلام Feedback بالكود التالي:

// الحصول على سجلات الـ Feedback (استعلام مبسط بدون JOINs معقدة)
$feedback_query = "SELECT FeedbackID, Rating, Comments, SubmissionDate, Response 
                  FROM Feedback 
                  WHERE CustomerID = ?
                  ORDER BY SubmissionDate DESC LIMIT 5";

$stmt = $conn->prepare($feedback_query);
if ($stmt === false) {
    die("Error preparing feedback query: " . $conn->error);
}

$stmt->bind_param("i", $customer_id);
if (!$stmt->execute()) {
    die("Error executing feedback query: " . $stmt->error);
}

$feedback_result = $stmt->get_result();
$feedback_records = [];
if ($feedback_result && $feedback_result->num_rows > 0) {
    while ($row = $feedback_result->fetch_assoc()) {
        $feedback_records[] = [
            'subject' => 'تقييم خدمة - تقييم عام',
            'message' => $row['Comments'],
            'rating' => $row['Rating'],
            'response' => $row['Response']
        ];
    }
}
// الحصول على سجلات الـ Contact
$contact_query = "SELECT ContactID, Subject, Message, Response, SubmissionDate 
                 FROM Contact 
                 WHERE Email = ? 
                 ORDER BY SubmissionDate DESC LIMIT 5";

$stmt = $conn->prepare($contact_query);
if ($stmt === false) {
    die("Error preparing contact query: " . $conn->error);
}

$stmt->bind_param("s", $user['Email']);
if (!$stmt->execute()) {
    die("Error executing contact query: " . $stmt->error);
}

$contact_result = $stmt->get_result();
$contact_records = [];
if ($contact_result && $contact_result->num_rows > 0) {
    while ($row = $contact_result->fetch_assoc()) {
        $contact_records[] = [
            'subject' => $row['Subject'],
            'message' => $row['Message'],
            'response' => $row['Response']
        ];
    }
}

// تخزين البيانات في الجلسة
$_SESSION['profile_data'] = [
    'user' => $user,
    'current_rental' => $current_rental,
    'rental_history' => $rental_history,
    'maintenance_records' => $maintenance_records,
    'join_date' => date("F Y", strtotime($user['RegistrationDate'] ?? 'now')),
    'feedback' => $feedback_records,
    'contact' => $contact_records
];

// عرض رسائل النجاح أو الخطأ
$success_message = '';
if (isset($_GET['success'])) {
    $messages = [
        'profile_updated' => 'تم تحديث الملف الشخصي بنجاح',
        'booking_cancelled' => 'تم إلغاء الحجز بنجاح',
        'booking_updated' => 'تم تحديث الحجز بنجاح'
    ];
    
    if (isset($messages[$_GET['success']])) {
        $success_message = $messages[$_GET['success']];
    }
}
error_log(print_r($_SESSION['profile_data'], true)); // لتصحيح الأخطاء

include 'profile.html';
?>