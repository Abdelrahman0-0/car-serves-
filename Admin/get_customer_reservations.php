<?php
// Database connection
$host = 'localhost';
$db = 'rental3';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if customer_id is submitted
if (isset($_POST['customer_id'])) {
    $customer_id = $_POST['customer_id'];

    // Query with JOIN to fetch details
    $query = "
        SELECT 
            customer.Name AS customer_name,
            customer.Phone,
            customer.Email,
            car.Model AS car_model,
            car.CarName AS car_name,
            car.PlateID AS plate_id,
            reservation.StartDate,
            reservation.EndDate,
            reservation.TotalCost,
            reservation.Status
        FROM 
            reservation
        JOIN 
            customer ON customer.CustomerID = reservation.CustomerID
        JOIN 
            car ON car.CarID = reservation.CarID
        WHERE 
            customer.CustomerID = :customer_id
    ";

    // Prepare and execute query
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "<table border='1'>
                <tr>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Car Model</th>
                    <th>Car Name</th>
                    <th>Plate ID</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Cost</th>
                    <th>Status</th>
                </tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                    <td>{$row['customer_name']}</td>
                    <td>{$row['Phone']}</td>
                    <td>{$row['Email']}</td>
                    <td>{$row['car_model']}</td>
                    <td>{$row['car_name']}</td>
                    <td>{$row['plate_id']}</td>
                    <td>{$row['StartDate']}</td>
                    <td>{$row['EndDate']}</td>
                    <td>{$row['TotalCost']}</td>
                    <td>{$row['Status']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No reservations found for this customer.</p>";
    }
} else {
    echo "<p>Customer ID is required.</p>";
}
?>
