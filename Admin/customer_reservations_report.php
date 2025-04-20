<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rental3";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if customer ID is provided
if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    echo "Customer ID: " . $customer_id . "<br>";  // Debugging
} else {
    die("Customer ID is required.");
}

// Query to fetch all reservations for the specified customer along with car details
$query = "
    SELECT 
        Reservation.ReservationID,
        Reservation.StartDate,
        Reservation.EndDate,
        Customer.CustomerID,
        Customer.name,
        Customer.Email,
        Car.CarID,
        Car.Model AS CarModel,
        Car.PlateID AS CarPlateID
    FROM Reservation
    INNER JOIN Customer ON Reservation.CustomerID = Customer.CustomerID
    INNER JOIN Car ON Reservation.CarID = Car.CarID
    WHERE Customer.CustomerID = ?
    ORDER BY Reservation.StartDate;
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

// Bind parameters
$stmt->bind_param("i", $customer_id);
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

// Display the results in an HTML table
echo "<h2>Reservation Report for Customer ID: $customer_id</h2>";
echo "<table border='1'>
        <tr>
            <th>Reservation ID</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Customer Name</th>
            <th>Customer Email</th>
            <th>Car Model</th>
            <th>Car Plate ID</th>
        </tr>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format StartDate and EndDate
        $startDate = new DateTime($row['StartDate']);
        $endDate = new DateTime($row['EndDate']);
        $formattedStartDate = $startDate->format('d-m-Y');
        $formattedEndDate = $endDate->format('d-m-Y');

        echo "<tr>
                <td>{$row['ReservationID']}</td>
                <td>{$formattedStartDate}</td>
                <td>{$formattedEndDate}</td>
                <td>{$row['name']}</td>
                <td>{$row['Email']}</td>
                <td>{$row['CarModel']}</td>
                <td>{$row['CarPlateID']}</td>
            </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No reservations found for this customer.</p>";
}

// Close the statement and the connection
$stmt->close();
$conn->close();
?>
