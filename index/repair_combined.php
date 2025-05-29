<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'car_services';

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $service_type = $conn->real_escape_string($_POST['service-type'] ?? 'Unknown');
    $service_price = floatval($_POST['service-price'] ?? 0);
    $full_name = $conn->real_escape_string($_POST['full-name'] ?? 'Unknown');
    $phone = $conn->real_escape_string($_POST['phone'] ?? 'Unknown');
    $address = $conn->real_escape_string($_POST['address'] ?? 'Unknown');
    $car_model = $conn->real_escape_string($_POST['car-model'] ?? 'Unknown');
    $plate_number = $conn->real_escape_string($_POST['plate-number'] ?? 'Unknown');
    $problem_description = $conn->real_escape_string($_POST['problem'] ?? 'No description provided');

    // Insert into Maintenance table
    $maintenance_sql = "INSERT INTO Maintenance (CarID, MaintenanceType, Description, Cost, Status) 
                       VALUES (?, ?, ?, ?, 'pending')";
    
    // Find or create car record
    $car_id = null;
    $find_car_sql = "SELECT CarID FROM Car WHERE PlateNumber = ?";
    $stmt = $conn->prepare($find_car_sql);
    $stmt->bind_param("s", $plate_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $car_id = $row['CarID'];
    } else {
        // Create new car record with default values
        $insert_car_sql = "INSERT INTO Car (CarName, Model, Year, Color, Mileage, PlateNumber, Status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'maintenance')";
        $stmt = $conn->prepare($insert_car_sql);
        $car_name = explode(' ', $car_model)[0] ?? 'Unknown';
        $year = date('Y');
        $color = 'Unknown';
        $mileage = 0;
        $stmt->bind_param("ssisis", $car_name, $car_model, $year, $color, $mileage, $plate_number);
        $stmt->execute();
        $car_id = $stmt->insert_id;
    }
    
    // Insert maintenance record
    $stmt = $conn->prepare($maintenance_sql);
    $stmt->bind_param("issd", $car_id, $service_type, $problem_description, $service_price);
    $maintenance_success = $stmt->execute();
    
    // Insert into orders table
    $order_sql = "INSERT INTO orders (client_name, car_info, type, date, status, price) 
                 VALUES (?, ?, 'repair', CURDATE(), 'pending', ?)";
    $stmt = $conn->prepare($order_sql);
    $car_info = "$car_model ($plate_number)";
    $stmt->bind_param("ssd", $full_name, $car_info, $service_price);
    $order_success = $stmt->execute();
    
    // Prepare response
    header('Content-Type: application/json');
    
    if ($maintenance_success && $order_success) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Repair service submitted successfully!',
            'service' => $service_type,
            'price' => $service_price
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error submitting repair service: ' . $conn->error
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>