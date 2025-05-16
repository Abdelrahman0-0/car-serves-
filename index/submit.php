<?php
session_start();

// Database connection settings
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'car_services';

// Create connection
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['form_type'])) {
        $formType = $_POST['form_type'];
        
        if ($formType === 'sales') {
            handleSalesSubmission($conn);
        } elseif ($formType === 'purchase_form') {
            handlePurchaseSubmission($conn);
        }
    } elseif (isset($_POST['search_type'])) {
        handleSearchRequest($conn);
        exit();
    }
}

function handleSalesSubmission($conn) {
    $conn->begin_transaction();
    try {
        // Validate and collect form data
        if (empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['name']) || 
            empty($_POST['address']) || empty($_POST['car_name']) || empty($_POST['car_model']) || 
            empty($_POST['plate_number']) || empty($_POST['year']) || empty($_POST['price'])) {
            throw new Exception("All required fields must be filled");
        }

        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $name = $conn->real_escape_string($_POST['name']);
        $address = $conn->real_escape_string($_POST['address']);
        $carName = $conn->real_escape_string($_POST['car_name']);
        $carModel = $conn->real_escape_string($_POST['car_model']);
        $plateNumber = $conn->real_escape_string($_POST['plate_number']);
        $year = (int)$_POST['year'];
        $price = (float)$_POST['price'];
        $condition = isset($_POST['condition']) ? $conn->real_escape_string($_POST['condition']) : 'good';

        // Process image upload
        $targetPath = null;
        if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Failed to create upload directory");
                }
            }

            $fileName = uniqid() . '_' . basename($_FILES['car_image']['name']);
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['car_image']['tmp_name'], $targetPath)) {
                throw new Exception("Failed to upload image");
            }
        } else {
            throw new Exception("Car image is required");
        }

        // 1. Insert car with 'maintenance' status initially
        $stmt = $conn->prepare("INSERT INTO Car 
            (CarName, Model, Year, Color, Status, SalePrice, Image, PlateNumber) 
            VALUES (?, ?, ?, ?, 'maintenance', ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $color = ''; // Default empty color
        $stmt->bind_param("ssissss", $carName, $carModel, $year, $color, $price, $targetPath, $plateNumber);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $carID = $conn->insert_id;
        $stmt->close();

        // 2. Insert or update customer
        $stmt = $conn->prepare("INSERT INTO Customer 
            (Name, Email, Phone, Address, Password, Status) 
            VALUES (?, ?, ?, ?, 'default_password', 'active') 
            ON DUPLICATE KEY UPDATE Name=VALUES(Name), Phone=VALUES(Phone), Address=VALUES(Address)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssss", $name, $email, $phone, $address);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $customerID = $conn->insert_id ?: $conn->query("SELECT CustomerID FROM Customer WHERE Email = '$email'")->fetch_assoc()['CustomerID'];
        $stmt->close();

        // 3. Create sale record with pending status
        $stmt = $conn->prepare("INSERT INTO Sale 
            (CarID, CustomerID, SalePrice, PaymentMethod, Status) 
            VALUES (?, ?, ?, 'cash', 'pending')");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iid", $carID, $customerID, $price);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // 4. Create order record with pending status
        $carInfo = "$carName $carModel";
        $stmt = $conn->prepare("INSERT INTO orders 
            ( client_name, car_info, type, date, status, price) 
            VALUES (?, ?, 'sale', CURDATE(), 'pending', ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssd", $name, $carInfo, $price);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        redirect('servic.html', 'Your sales request has been submitted successfully and is pending approval', true);

    } catch (Exception $e) {
        $conn->rollback();
        // Delete uploaded file if transaction failed
        if (isset($targetPath)) {
            @unlink($targetPath);
        }
        redirect('servic.html', 'Request submission failed: ' . $e->getMessage(), false);
    }
}

function handlePurchaseSubmission($conn) {
    $conn->begin_transaction();
    try {
        // Validate required fields
        if (empty($_POST['car_id']) || empty($_POST['full_name']) || empty($_POST['phone']) || 
            empty($_POST['address']) || empty($_POST['total_price'])) {
            throw new Exception("All required fields must be filled");
        }

        $carID = (int)$_POST['car_id'];
        $fullName = $conn->real_escape_string($_POST['full_name']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
        $address = $conn->real_escape_string($_POST['address']);
        $totalPrice = (float)$_POST['total_price'];
        $carName = $conn->real_escape_string($_POST['car_name']);
        $carModel = $conn->real_escape_string($_POST['car_model']);

        // 1. Verify car exists and is available
        $stmt = $conn->prepare("SELECT * FROM Car WHERE CarID = ? AND Status = 'available'");
        $stmt->bind_param("i", $carID);
        $stmt->execute();
        $car = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$car) {
            throw new Exception("Car is not available for purchase");
        }

        // 2. Insert or update customer
        $stmt = $conn->prepare("INSERT INTO Customer 
            (Name, Email, Phone, Address, Password, Status) 
            VALUES (?, ?, ?, ?, 'default_password', 'active') 
            ON DUPLICATE KEY UPDATE Name=VALUES(Name), Phone=VALUES(Phone), Address=VALUES(Address)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssss", $fullName, $email, $phone, $address);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $customerID = $conn->insert_id ?: $conn->query("SELECT CustomerID FROM Customer WHERE Email = '$email' OR Phone = '$phone'")->fetch_assoc()['CustomerID'];
        $stmt->close();

        // 3. Create purchase record
        $stmt = $conn->prepare("INSERT INTO CustomerPurchase 
            (CustomerID, CarID, FinalPrice, PaymentMethod, PaymentStatus, DeliveryAddress) 
            VALUES (?, ?, ?, 'cash', 'completed', ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iids", $customerID, $carID, $totalPrice, $address);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // 4. Create sale record
        $stmt = $conn->prepare("INSERT INTO Sale 
            (CarID, CustomerID, SalePrice, PaymentMethod, Status) 
            VALUES (?, ?, ?, 'cash', 'completed')");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iid", $carID, $customerID, $totalPrice);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // 5. Update car status to 'sold'
        $stmt = $conn->prepare("UPDATE Car SET Status='sold' WHERE CarID=?");
        $stmt->bind_param("i", $carID);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        // 6. Create order record
        $carInfo = "$carName $carModel";
        $stmt = $conn->prepare("INSERT INTO orders 
            (CarID, client_name, car_info, type, date, status, price) 
            VALUES (?, ?, ?, 'buy', CURDATE(), 'completed', ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("issd", $carID, $fullName, $carInfo, $totalPrice);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        redirect('servic.html', 'Your purchase request has been submitted successfully', true);

    } catch (Exception $e) {
        $conn->rollback();
        redirect('servic.html', 'Purchase failed: ' . $e->getMessage(), false);
    }
}

function handleSearchRequest($conn) {
    $searchType = $_POST['search_type'];
    $carName = $conn->real_escape_string($_POST['car_name']);
    $carModel = $conn->real_escape_string($_POST['car_model']);

    if ($searchType === 'rental') {
        $sql = "SELECT c.CarID, c.CarName, c.Model, c.Year, c.PricePerDay, c.Status, 
                       c.PlateNumber, c.Image, o.Location as OfficeLocation
                FROM Car c
                LEFT JOIN Office o ON c.OfficeID = o.OfficeID
                WHERE c.CarName LIKE '%$carName%' 
                AND c.Model LIKE '%$carModel%'
                AND c.Status = 'available'
                AND c.PricePerDay IS NOT NULL";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo '<h3>Search Results:</h3>';
            while($row = $result->fetch_assoc()) {
                echo '
                <div class="car-item">
                    <div class="car-header">
                        <div class="car-info">
                            <h4>'.$row['CarName'].' '.$row['Model'].' ('.$row['Year'].')</h4>
                        </div>
                        <div class="car-status available">
                            Available
                        </div>
                    </div>
                    <div class="car-details">
                        <p><strong>Price:</strong> '.$row['PricePerDay'].' SAR/day</p>
                        <p><strong>Location:</strong> '.($row['OfficeLocation'] ?? 'Not specified').'</p>
                        <a href="Car Reservation.html?carId='.$row['CarID'].'" class="book-btn">Book Now</a>
                    </div>
                </div>';
            }
        } else {
            echo '<p>No rental cars found matching your criteria.</p>';
        }
    } 
    elseif ($searchType === 'purchase') {
        $sql = "SELECT c.CarID, c.CarName, c.Model, c.Year, c.SalePrice, c.Status, 
                       c.PlateNumber, c.Image, o.Location as OfficeLocation
                FROM Car c
                LEFT JOIN Office o ON c.OfficeID = o.OfficeID
                LEFT JOIN orders ord ON c.CarID = ord.CarID AND ord.type = 'sale'
                WHERE c.CarName LIKE '%$carName%' 
                AND c.Model LIKE '%$carModel%'
                AND c.Status = 'available'
                AND c.SalePrice IS NOT NULL
                AND (ord.CarID IS NULL OR ord.status = 'completed')";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo '<h3>Cars Available for Purchase:</h3>';
            while($row = $result->fetch_assoc()) {
                echo '
                <div class="car-item">
                    <div class="car-header">
                        <div class="car-info">
                            <h4>'.$row['CarName'].' '.$row['Model'].' ('.$row['Year'].')</h4>
                        </div>
                        <div class="car-status available">
                            Available
                        </div>
                    </div>
                    <div class="car-details">
                        <p><strong>Price:</strong> '.number_format($row['SalePrice'], 2).' SAR</p>
                        <p><strong>Location:</strong> '.($row['OfficeLocation'] ?? 'Not specified').'</p>
                        <button class="book-btn purchase-btn" 
                            data-car-id="'.$row['CarID'].'"
                            data-car-name="'.$row['CarName'].'"
                            data-car-model="'.$row['Model'].'"
                            data-car-year="'.$row['Year'].'"
                            data-car-price="'.$row['SalePrice'].'">
                            Purchase This Car
                        </button>
                    </div>
                </div>';
            }
        } else {
            echo '<p>No cars for purchase found matching your criteria.</p>';
        }
    }
}

function redirect($url, $message, $success = true) {
    header("Location: $url?" . ($success ? 'success=' : 'error=') . urlencode($message));
    exit();
}
?>