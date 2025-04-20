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

// التحقق من إرسال النموذج
if (isset($_POST['search'])) {
    $CarName = trim($_POST['CarName']); // إزالة المسافات الزائدة
    $CarModel = trim($_POST['CarModel']); // إزالة المسافات الزائدة

    // طباعة القيم المدخلة للتأكد من صحتها
    echo "Searching for Car Name: " . $CarName . "<br>";
    echo "Searching for Car Model: " . $CarModel . "<br>";

    // استعلام SQL لتجاهل حالة الأحرف لكل من CarName وModel
    $sql = "SELECT * FROM Car 
            WHERE LOWER(CarName) = LOWER('$CarName') 
            AND LOWER(Model) = LOWER('$CarModel')";
    $result = $conn->query($sql);

    // التحقق من نجاح الاستعلام
    if (!$result) {
        die("Error in query: " . $conn->error);
    }

    // التحقق إذا كانت هناك سيارات متاحة للحجز
    if ($result->num_rows > 0) {
        echo "<h3>Search Results:</h3>";
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <th>Car Name</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Plate ID</th>
                    <th>Price</th>
                    <th>Status</th>
                    
                   
                </tr>";

        // عرض جميع السيارات التي تم العثور عليها
        while ($car = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $car['CarName'] . "</td>
                    <td>" . $car['Model'] . "</td>
                    <td>" . $car['Year'] . "</td>
                    <td>" . $car['PlateID'] . "</td>
                    <td>" . $car['Price'] . "</td>
                    <td>";

            // حالة السيارة
            if ($car['Status'] == 'active') {
                echo "<p style='color: green;'>The car is available for booking.</p>";
                echo "<form action='Car Reservation.html' method='POST'>
                        <input type='hidden' name='CarName' value='" . $car['CarName'] . "'>
                        <input type='hidden' name='CarModel' value='" . $car['Model'] . "'>
                        <input type='hidden' name='CarPlate' value='" . $car['PlateID'] . "'>
                        <input type='hidden' name='Price' value='" . $car['Price'] . "'>
                        <button type='submit'>Reserve Now</button>
                      </form>";
            } elseif ($car['Status'] == 'rented') {
                echo "<p style='color: red;'>This car is currently rented.</p>";
            } else {
                echo "<p style='color: orange;'>This car is out of service.</p>";
            }

            echo "</td></tr>";
        }

        echo "</table>";
        echo "<form action='index.html' method='POST'>
        <button type='submit'>Go To Home</button>
      </form>";
    } else {
        // إذا لم يتم العثور على السيارة
        echo "<p style='color: red;'>No car found with the specified name and model.</p>";
    }
}

$conn->close();
?>
