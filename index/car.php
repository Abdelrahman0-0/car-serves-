<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'car_services';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize variables
$message = '';
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'home';

// Process purchase form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'purchase') {
        $carId = $_POST['car_id'] ?? null;
        $email = $_POST['email'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        try {
            // 1. Verify car exists and is available
            $stmt = $pdo->prepare("SELECT * FROM Car WHERE CarID = ? AND Status = 'available'");
            $stmt->execute([$carId]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$car) {
                throw new Exception("Car is not available for purchase");
            }

            // 2. Verify customer exists
            $stmt = $pdo->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
            $stmt->execute([$email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                throw new Exception("You must be registered to make a purchase. Please sign up first.");
            }

            $customerId = $customer['CustomerID'];

            // 3. Process the purchase in CustomerPurchase table
            $stmt = $pdo->prepare("INSERT INTO CustomerPurchase 
                                (CustomerID, CarID, FinalPrice, PaymentMethod, PaymentStatus, DeliveryAddress) 
                                VALUES (?, ?, ?, ?, 'completed', ?)");
            $stmt->execute([
                $customerId,
                $carId,
                $car['SalePrice'],
                $_POST['paymentMethod'],
                $address
            ]);

            // Update car status
            $stmt = $pdo->prepare("UPDATE Car SET Status = 'sold' WHERE CarID = ?");
            $stmt->execute([$carId]);

            $message = "Purchase successful!";
            $currentPage = 'purchase_success';
            $purchaseDetails = [
                'car' => $car,
                'customer_id' => $customerId,
                'full_name' => $fullName,
                'phone' => $phone,
                'address' => $address
            ];

        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
    }
}
// Get cars data
try {
    // New cars for sale - only cars manufactured in last 2 years
    $stmt = $pdo->query("SELECT c.* FROM Car c 
                        WHERE c.Status = 'available' 
                        AND c.SalePrice IS NOT NULL 
                        AND c.Year >= YEAR(CURDATE()) - 2");
    $carsForSale = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cars for rent
    $stmt = $pdo->query("SELECT * FROM Car WHERE Status = 'available' AND PricePerDay IS NOT NULL");
    $carsForRent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Used cars for sale - only cars manufactured more than 2 years ago
    $stmt = $pdo->query("SELECT c.* FROM Car c 
                        WHERE c.Status = 'available' 
                        AND c.SalePrice IS NOT NULL 
                        AND c.Year < YEAR(CURDATE()) - 2");
    $usedCars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

// Get car details if viewing purchase page
$carDetails = null;
if (isset($_GET['car_id'])) {
    $carId = $_GET['car_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM Car WHERE CarID = ?");
        $stmt->execute([$carId]);
        $carDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Hub</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #2c3e50;
            color: #ecf0f1;
            line-height: 1.6;
        }

        header {
            background-color: #34495e;
            color: #ecf0f1;
            text-align: center;
            padding: 40px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .search-bar {
            width: 80%;
            max-width: 600px;
            margin: 30px auto;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px;
            border-radius: 30px;
            border: none;
            background-color: #2c3e50;
            color: #ecf0f1;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            box-shadow: 0 2px 10px rgba(26, 188, 156, 0.3);
        }

        header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            color: #1abc9c;
        }

        header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .section {
            padding: 30px 5%;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            color: #1abc9c;
            position: relative;
        }

        .section h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 3px;
            background: #1abc9c;
            margin: 10px auto;
            border-radius: 3px;
        }

        /* Cars Section Styling */
        .car-container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            justify-content: center;
            margin-top: 30px;
        }

        .car-card {
            background-color: #34495e;
            padding: 20px;
            border-radius: 10px;
            width: 280px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        .car-card img {
            width: 100%;
            height: 180px;
            border-radius: 8px;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .car-card h3 {
            color: #ecf0f1;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .car-card p {
            color: #bdc3c7;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .car-card button {
            background-color: #1abc9c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s;
        }

        .car-card button:hover {
            background-color: #16a085;
            transform: scale(1.02);
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #34495e;
            margin-top: 50px;
            font-size: 14px;
            color: #bdc3c7;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 200;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #34495e;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #bdc3c7;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #ecf0f1;
        }

        .modal-header {
            border-bottom: 1px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #1abc9c;
            font-size: 24px;
        }

        .modal-body {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .car-detail {
            flex: 1;
            min-width: 250px;
        }

        .car-images {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .car-images img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .car-images img:hover {
            transform: scale(1.05);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            min-width: 120px;
            text-align: center;
        }

        .btn-buy {
            background-color: #e74c3c;
            color: white;
        }

        .btn-buy:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-rent {
            background-color: #3498db;
            color: white;
        }

        .btn-rent:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        /* Form Styles */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #34495e;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1abc9c;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #2c3e50;
            background-color: #2c3e50;
            color: #ecf0f1;
            font-size: 16px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1abc9c;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .form-buttons button {
            flex: 1;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .form-submit {
            background-color: #2ecc71;
            color: white;
            border: none;
        }

        .form-submit:hover {
            background-color: #27ae60;
        }

        .form-cancel {
            background-color: #e74c3c;
            color: white;
            border: none;
        }

        .form-cancel:hover {
            background-color: #c0392b;
        }

        .car-info-display {
            background-color: #2c3e50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .car-info-display p {
            margin: 8px 0;
            color: #bdc3c7;
        }

        .car-info-display strong {
            color: #1abc9c;
        }

        /* Success Message */
        .success-message {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #34495e;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .success-message h2 {
            color: #2ecc71;
            margin-bottom: 20px;
        }

        .success-message p {
            margin: 15px 0;
            color: #bdc3c7;
        }

        .success-message .btn-back {
            display: inline-block;
            background-color: #1abc9c;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .success-message .btn-back:hover {
            background-color: #16a085;
            transform: translateY(-2px);
        }

        /* Navigation */
        .logo1 {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            display: flex;
            align-items: center;
        }
        .logo1 img {
            width: 70px;
            height: auto;
            border-radius: 100px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.5);
        }

        .dropdown-menu {
            list-style: none;
            padding: 10px;
            margin: 0;
            display: none;
            position: absolute;
            font-weight: bold;
            top: 100%;
            left: 0;
            background-color: rgba(0, 0, 0, 0.8);
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            z-index: 1000;
        }
        .dropdown-menu li {
            padding: 10px 20px;
        }

        .dropdown-menu li a {
            color: #bdc3c7;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .dropdown-menu li a:hover {
            background-color: #557770;
        }

        .logo1:hover .dropdown-menu {
            display: block;
        }

        /* Purchase Form Styles */
        .purchase-form {
            max-width: 800px;
            margin: 20px auto;
            background-color: #34495e;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .purchase-form h2 {
            color: #1abc9c;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .form-table td {
            padding: 10px;
            border: 1px solid #2c3e50;
        }
        
        .form-table td:first-child {
            width: 30%;
            background-color: #2c3e50;
            color: #1abc9c;
            font-weight: bold;
        }
        
        .form-table input {
            width: 100%;
            padding: 8px;
            background-color: #2c3e50;
            border: 1px solid #2c3e50;
            color: #ecf0f1;
        }
        
        .form-buttons-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .form-buttons-container button {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .submit-btn {
            background-color: #1abc9c;
            color: white;
        }
        
        .cancel-btn {
            background-color: #e74c3c;
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header h1 {
                font-size: 28px;
            }
            
            .car-card {
                width: 100%;
                max-width: 350px;
            }
            
            .modal-content, .form-container, .success-message {
                width: 95%;
                margin: 10% auto;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        @media (max-width: 480px) {
            header {
                padding: 25px 0;
            }
            
            header h1 {
                font-size: 24px;
            }
            
            header p {
                font-size: 16px;
            }
            
            .section h2 {
                font-size: 22px;
            }
            
            .modal-body {
                flex-direction: column;
            }
            
            .action-buttons, .form-buttons {
                flex-direction: column;
            }
            
            .btn, .form-buttons button {
                width: 100%;
            }
        }

        /* Message Styles */
        .message {
            background-color: #1abc9c;
            color: white;
            padding: 15px;
            text-align: center;
            margin: 20px auto;
            max-width: 600px;
            border-radius: 5px;
            animation: fadeIn 0.5s;
        }

        .message.error {
            background-color: #e74c3c;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo1">
            <img src="img/logo.png" alt="Car Rental Logo">
            <ul class="dropdown-menu">
                <li><a href="index.html">Home</a></li>
                <li><a href="feedback.html">Feedback</a></li>
                <li><a href="servic.html">Services</a></li>
                <li><a href="about.html">Contact</a></li>
                <li><a href="login.html" id="auth-link">Login</a></li>
            </ul>
        </div>
    
        <h1>Welcome to Car Hub</h1>
        <p>Find your dream car at the best prices</p>
    </header>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($currentPage === 'home'): ?>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search for a car..." onkeyup="searchCars()">
        </div>

        <!-- New Cars for Sale -->
        <section class="section">
            <h2>New Cars for Sale</h2>
            <div class="car-container">
                <?php foreach ($carsForSale as $car): ?>
                    <div class="car-card" data-name="<?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model']); ?>">
                        <img src="<?php echo htmlspecialchars($car['Image'] ?: 'https://via.placeholder.com/400x250?text=' . urlencode($car['CarName'] . ' ' . $car['Model'])); ?>" 
                             alt="<?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model']); ?>">
                        <h3><?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model'] . ' ' . $car['Year']); ?></h3>
                        <p>Price: $<?php echo number_format($car['SalePrice'], 2); ?></p>
                        <button onclick="showCarDetails(<?php echo $car['CarID']; ?>, 'buy')">View Details</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Cars for Rent -->
        <section class="section">
            <h2>Cars for Rent</h2>
            <div class="car-container">
                <?php foreach ($carsForRent as $car): ?>
                    <div class="car-card" data-name="<?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model']); ?>">
                        <img src="<?php echo htmlspecialchars($car['Image'] ?: 'https://via.placeholder.com/400x250?text=' . urlencode($car['CarName'] . ' ' . $car['Model'])); ?>" 
                             alt="<?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model']); ?>">
                        <h3><?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model'] . ' ' . $car['Year']); ?></h3>
                        <p>Rent: $<?php echo number_format($car['PricePerDay'], 2); ?> / day</p>
                        <button onclick="showCarDetails(<?php echo $car['CarID']; ?>, 'rent')">View Details</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Used Cars for Sale -->
        <section class="section">
            <h2>Used Cars for Sale</h2>
            <div class="car-container">
                <?php foreach ($usedCars as $car): ?>
                    <div class="car-card" data-name="<?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model']); ?>">
                        <img src="<?php echo htmlspecialchars($car['Image'] ?: 'https://via.placeholder.com/400x250?text=' . urlencode($car['CarName'] . ' ' . $car['Model'])); ?>" 
                             alt="<?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model']); ?>">
                        <h3><?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model'] . ' ' . $car['Year']); ?></h3>
                        <p>Price: $<?php echo number_format($car['SalePrice'], 2); ?></p>
                        <button onclick="showCarDetails(<?php echo $car['CarID']; ?>, 'buy')">View Details</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Modal for Car Details -->
        <div id="carModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <div class="modal-header">
                    <h3 id="modalCarName"></h3>
                    <p id="modalCarPrice"></p>
                </div>
                <div class="modal-body">
                    <div class="car-detail">
                        <h4>Details</h4>
                        <p id="modalCarType"></p>
                        <p id="modalCarYear"></p>
                        <p id="modalCarMileage"></p>
                        <p id="modalCarColor"></p>
                        <p id="modalCarPlate"></p>
                        <p id="modalCarStatus"></p>
                    </div>
                    <div class="car-detail">
                        <h4>Images</h4>
                        <div class="car-images" id="modalCarImages"></div>
                    </div>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-buy" id="buyBtn" onclick="showPurchaseForm()">Buy Now</button>
                    <button class="btn btn-rent" id="rentBtn" onclick="redirectToRental()">Rent Now</button>
                </div>
            </div>
        </div>

    <?php elseif ($currentPage === 'purchase' && $carDetails): ?>
        <section class="section">
            <div class="purchase-form">
                <h2>Car Purchase Request</h2>
                
                <form method="post">
                    <input type="hidden" name="action" value="purchase">
                    <input type="hidden" name="car_id" value="<?php echo $carDetails['CarID']; ?>">
                    
                    <table class="form-table">
                        <tr>
                            <td>Full Name</td>
                            <td><input type="text" name="full_name" required placeholder="Your Full Name"></td>
                            <td>Car Name</td>
                            <td><input type="text" value="<?php echo htmlspecialchars($carDetails['CarName']); ?>" readonly></td>
                        </tr>
                        <tr>
                            <td>Phone Number</td>
                            <td><input type="tel" name="phone" required placeholder="011 2345 6225"></td>
                            <td>Car Model</td>
                            <td><input type="text" value="<?php echo htmlspecialchars($carDetails['Model']); ?>" readonly></td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><input type="email" name="email" required placeholder="example@gmail.com"></td>
                            <td>Total Price (SAR)</td>
                            <td><input type="text" value="<?php echo number_format($carDetails['SalePrice'], 2); ?>" readonly></td>
                        </tr>
                        <tr>
                            <td>Address</td>
                            <td><input type="text" name="address" required placeholder="City, Street, Building"></td>
                            <td>Manufacturing Year</td>
                            <td><input type="text" value="<?php echo htmlspecialchars($carDetails['Year']); ?>" readonly></td>
                        </tr>
                    </table>
                    
                    <div class="form-group">
                        <label for="paymentMethod">Payment Method</label>
                        <select id="paymentMethod" name="paymentMethod" required>
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash</option>
                            <option value="credit">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="form-buttons-container">
                        <button type="submit" class="submit-btn">Submit Request</button>
                        <button type="button" class="cancel-btn" onclick="window.location.href='?'">Cancel</button>
                    </div>
                </form>
            </div>
        </section>

    <?php elseif ($currentPage === 'purchase_success' && isset($purchaseDetails)): ?>
        <section class="section">
            <div class="success-message">
                <h2>Thank You for Your Purchase!</h2>
                <p>Your purchase has been successfully processed.</p>
                
                <div class="car-info-display">
                    <p><strong>Car:</strong> <?php echo htmlspecialchars($purchaseDetails['car']['CarName'] . ' ' . $purchaseDetails['car']['Model'] . ' ' . $purchaseDetails['car']['Year']); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($purchaseDetails['car']['SalePrice'], 2); ?></p>
                    <p><strong>Plate Number:</strong> <?php echo htmlspecialchars($purchaseDetails['car']['PlateNumber'] ?? 'N/A'); ?></p>
                </div>
                
                <p>Our sales representative will contact you shortly to arrange the delivery.</p>
                
                <a href="?" class="btn-back">Back to Car List</a>
            </div>
        </section>

    <?php else: ?>
        <section class="section">
            <div style="background-color: #e74c3c; color: white; padding: 20px; border-radius: 5px; text-align: center;">
                <p>Page not found or invalid request.</p>
                <a href="?" style="color: #1abc9c; text-decoration: none; font-weight: bold;">Back to Home</a>
            </div>
        </section>
    <?php endif; ?>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Car Hub. All rights reserved.</p>
    </footer>
    <script>
        // Search Functionality
        function searchCars() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const carCards = document.querySelectorAll('.car-card');
            
            carCards.forEach(card => {
                const carName = card.getAttribute('data-name').toUpperCase();
                if (carName.includes(filter)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        }
        
        // Current car details
        let currentCar = {};
        
        // Show car details modal
        function showCarDetails(carId, type) {
            // Get all cars from the page
            const carCards = document.querySelectorAll('.car-card');
            let carData = null;
            
            // Find the clicked car
            carCards.forEach(card => {
                if (card.querySelector('button').getAttribute('onclick').includes(carId)) {
                    const img = card.querySelector('img');
                    const title = card.querySelector('h3');
                    const price = card.querySelector('p');
                    
                    carData = {
                        CarID: carId,
                        CarName: title.textContent.split(' ')[0],
                        Model: title.textContent.split(' ')[1],
                        Year: title.textContent.split(' ')[2],
                        Image: img.src,
                        SalePrice: type === 'buy' ? parseFloat(price.textContent.replace('Price: $', '').replace(',', '')) : null,
                        PricePerDay: type === 'rent' ? parseFloat(price.textContent.replace('Rent: $', '').replace(' / day', '').replace(',', '')) : null,
                        Color: 'N/A',
                        PlateNumber: 'N/A',
                        Status: 'available'
                    };
                }
            });
            
            if (carData) {
                currentCar = carData;
                
                const modal = document.getElementById('carModal');
                document.getElementById('modalCarName').textContent = `${carData.CarName} ${carData.Model} ${carData.Year}`;
                
                if (type === 'rent') {
                    document.getElementById('modalCarPrice').textContent = `Rent Price: $${carData.PricePerDay.toLocaleString()} / day`;
                } else {
                    document.getElementById('modalCarPrice').textContent = `Sale Price: $${carData.SalePrice.toLocaleString()}`;
                }
                
                document.getElementById('modalCarType').textContent = `Type: ${type === 'rent' ? 'For Rent' : 'For Sale'}`;
                document.getElementById('modalCarYear').textContent = `Year: ${carData.Year}`;
                document.getElementById('modalCarMileage').textContent = `Mileage: N/A`;
                document.getElementById('modalCarColor').textContent = `Color: ${carData.Color}`;
                document.getElementById('modalCarPlate').textContent = `Plate Number: ${carData.PlateNumber}`;
                document.getElementById('modalCarStatus').textContent = `Status: ${carData.Status}`;
                
                // Update button visibility based on car type
                const buyBtn = document.getElementById('buyBtn');
                const rentBtn = document.getElementById('rentBtn');
                
                if (type === 'rent') {
                    buyBtn.style.display = 'none';
                    rentBtn.style.display = 'block';
                } else {
                    buyBtn.style.display = 'block';
                    rentBtn.style.display = 'none';
                }
                
                // Create image placeholders
                const imagesContainer = document.getElementById('modalCarImages');
                imagesContainer.innerHTML = '';
                
                const mainImg = document.createElement('img');
                mainImg.src = carData.Image;
                mainImg.alt = `${carData.CarName} ${carData.Model}`;
                imagesContainer.appendChild(mainImg);
                
                // Add additional placeholder images
                for (let i = 0; i < 3; i++) {
                    const img = document.createElement('img');
                    img.src = `https://via.placeholder.com/120x80?text=${carData.CarName}+${i+1}`;
                    img.alt = `${carData.CarName} ${carData.Model} ${i+1}`;
                    imagesContainer.appendChild(img);
                }
                
                modal.style.display = "block";
            }
        }
        
        function closeModal() {
            document.getElementById('carModal').style.display = "none";
        }
        
        // Show purchase form
        function showPurchaseForm() {
            window.location.href = `?page=purchase&car_id=${currentCar.CarID}`;
        }
        
        // Redirect to rental page
        function redirectToRental() {
    window.location.href = `Car Reservation.html?car_id=${currentCar.CarID}&car_name=${encodeURIComponent(currentCar.CarName)}&car_model=${encodeURIComponent(currentCar.Model)}&plate_id=${encodeURIComponent(currentCar.PlateNumber)}`;
}
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('carModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>