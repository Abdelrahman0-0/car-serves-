<?php
// الاتصال بقاعدة البيانات
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "rental3"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على بيانات النموذج
$customer_name = $_POST['customer_name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$address = $_POST['address'];
$car_model = $_POST['car_model'];
$car_name = $_POST['car_name'];
$plate_id = $_POST['plate_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

// التحقق من وجود العميل
$sql = "SELECT CustomerID FROM Customer WHERE Name = ? AND Phone = ? AND Email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $customer_name, $phone, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $customer_id = $row['CustomerID'];
} else {
    // إذا لم يكن العميل موجودًا، إضافته
    $sql = "INSERT INTO Customer (Name, Email, Phone, Address, Password) VALUES (?, ?, ?, ?, 'defaultpassword')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $customer_name, $email, $phone, $address);
    $stmt->execute();
    $customer_id = $stmt->insert_id;
}

// التحقق من توفر السيارة واستخراج السعر
$sql = "SELECT CarID, Price FROM Car WHERE Model = ? AND CarName = ? AND PlateID = ? AND Status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $car_model, $car_name, $plate_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $car_id = $row['CarID'];
    $daily_cost = $row['Price']; // استخراج السعر اليومي من قاعدة البيانات
} else {
    die("Car is not available or Plate ID is incorrect.");
}

// حساب عدد الأيام بين تاريخ البداية والنهاية
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$interval = $date1->diff($date2);
$num_days = $interval->days;

// حساب التكلفة الإجمالية
$total_cost = $daily_cost * $num_days;

// إنشاء الحجز
$sql = "INSERT INTO reservation (CustomerID, CarID, StartDate, EndDate, TotalCost, Status) VALUES (?, ?, ?, ?, ?, 'Reserved')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissd", $customer_id, $car_id, $start_date, $end_date, $total_cost);
$stmt->execute();

// الحصول على رقم الحجز الذي تم إنشاؤه
$reservation_id = $stmt->insert_id;

// تحديث حالة السيارة
$sql = "UPDATE Car SET Status = 'rented' WHERE CarID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $car_id);
$stmt->execute();

// عرض رسالة نجاح مع رقم الحجز
echo "<p style='color: green;'>Reservation successful! Total cost: $total_cost</p>";
echo "<p style='color: blue;'>Your Reservation ID: $reservation_id</p>";

echo "<form action='pa.html' method='POST'>
<button type='submit'>Payment Now!</button>
</form>";

// إغلاق الاتصال
$conn->close();
?>
