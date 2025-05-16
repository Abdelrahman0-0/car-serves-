<?php
// Database connection
$host = 'localhost';
$dbname = 'car_services';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch statistics from database
function getStatistic($pdo, $query) {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchColumn();
}

// Get statistics for the current month
$currentMonth = date('Y-m');
$rentalOrders = getStatistic($pdo, "SELECT COUNT(*) FROM Rental WHERE StartDate LIKE '$currentMonth%'");
$carSales = getStatistic($pdo, "SELECT COUNT(*) FROM Sale WHERE SaleDate LIKE '$currentMonth%' AND Status = 'completed'");
$maintenance = getStatistic($pdo, "SELECT COUNT(*) FROM Maintenance WHERE MaintenanceDate LIKE '$currentMonth%'");
$newCustomers = getStatistic($pdo, "SELECT COUNT(*) FROM Customer WHERE RegistrationDate LIKE '$currentMonth%'");

// Get rental data for the last 5 months
$rentalData = [];
$salesData = [];
$maintenanceData = [];
$carTypesData = [];

for ($i = 4; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $rentalData[$month] = getStatistic($pdo, "SELECT COUNT(*) FROM Rental WHERE StartDate LIKE '$month%'");
    $salesData[$month] = getStatistic($pdo, "SELECT COUNT(*) FROM Sale WHERE SaleDate LIKE '$month%' AND Status = 'completed'");
}

// Get car sales by type
$carTypes = ['Sedan', 'SUV', 'Sports', 'Van', 'Truck'];
foreach ($carTypes as $type) {
    $carTypesData[$type] = getStatistic($pdo, "SELECT COUNT(*) FROM Sale s JOIN Car c ON s.CarID = c.CarID WHERE c.Model LIKE '%$type%'");
}

// Get maintenance types data
$maintenanceTypes = [
    'Regular' => getStatistic($pdo, "SELECT COUNT(*) FROM Maintenance WHERE MaintenanceType LIKE '%Regular%'"),
    'Repairs' => getStatistic($pdo, "SELECT COUNT(*) FROM Maintenance WHERE MaintenanceType LIKE '%Repair%'"),
    'Accidents' => getStatistic($pdo, "SELECT COUNT(*) FROM Maintenance WHERE MaintenanceType LIKE '%Accident%'"),
    'Modifications' => getStatistic($pdo, "SELECT COUNT(*) FROM Maintenance WHERE MaintenanceType LIKE '%Modification%'")
];

// Get popular car brands
$popularBrands = ['Toyota', 'Nissan', 'Hyundai', 'Mercedes', 'BMW'];
foreach ($popularBrands as $brand) {
    $brandData[$brand] = getStatistic($pdo, "SELECT COUNT(*) FROM Rental r JOIN Car c ON r.CarID = c.CarID WHERE c.CarName LIKE '%$brand%'");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Services Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            background-color: #1a1a1a;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar a {
            color: #aaa;
            padding: 15px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .sidebar a:hover {
            background-color: #333;
            color: #00bcd4;
        }

        .sidebar i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .top-bar {
            background-color: #1a1a1a;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
        }

        .chart-container, .stat-card {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .btn-primary {
            background-color: #00bcd4;
            border: none;
            color: #fff;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #008ba3;
        }

        .stat-card {
            color: white;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .bg-primary { background-color: #00bcd4; }
        .bg-success { background-color: #4CAF50; }
        .bg-warning { background-color: #FFC107; color: #333; }
        .bg-info { background-color: #17a2b8; }

        .row {
            margin-right: -15px;
            margin-left: -15px;
        }

        .col-md-3, .col-md-6 {
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header text-center mb-4">
            <h4><i class="fas fa-car"></i> Car Services</h4>
        </div>
        <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="admin.php"><i class="fas fa-users"></i> Users</a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="clients.php"><i class="fas fa-user-tie"></i> Clients</a>
        <a href="report.php"><i class="fas fa-clipboard-list"></i> Reports</a>
        <a href="Statistics1.php"><i class="fas fa-chart-bar me-2"></i> Statistics</a>
        <a href="manage.php"><i class="fas fa-cogs"></i> Manage</a>
        <a href="mangeCar.php"><i class="fas fa-car"></i> Cars</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1>Car Services Analytics</h1>
            <div>
                <button class="btn btn-primary"><i class="fas fa-bell"></i></button>
                <button class="btn btn-primary"><i class="fas fa-envelope"></i></button>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <h3>Rental Orders</h3>
                    <h2><?php echo $rentalOrders; ?></h2>
                    <p>This Month</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <h3>Car Sales</h3>
                    <h2><?php echo $carSales; ?></h2>
                    <p>This Month</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <h3>Maintenance</h3>
                    <h2><?php echo $maintenance; ?></h2>
                    <p>This Month</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <h3>New Customers</h3>
                    <h2><?php echo $newCustomers; ?></h2>
                    <p>This Month</p>
                </div>
            </div>
        </div>

        <!-- Main Charts Row 1 -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h2><i class="fas fa-chart-line"></i> Rental Analytics</h2>
                    <canvas id="rentalChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h2><i class="fas fa-chart-bar"></i> Car Sales</h2>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Main Charts Row 2 -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h2><i class="fas fa-tools"></i> Maintenance Requests</h2>
                    <canvas id="maintenanceChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h2><i class="fas fa-car"></i> Most Popular Car Types</h2>
                    <canvas id="carTypesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Rental Chart
        const rentalCtx = document.getElementById('rentalChart').getContext('2d');
        new Chart(rentalCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($rentalData)); ?>,
                datasets: [{
                    label: 'Monthly Rentals',
                    data: <?php echo json_encode(array_values($rentalData)); ?>,
                    borderColor: '#00bcd4',
                    fill: false
                }]
            }
        });

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($carTypesData)); ?>,
                datasets: [{
                    label: 'Number of Sales',
                    data: <?php echo json_encode(array_values($carTypesData)); ?>,
                    backgroundColor: ['#00bcd4', '#4CAF50', '#FFC107', '#FF5722', '#9C27B0']
                }]
            }
        });

        // Maintenance Chart
        const maintenanceCtx = document.getElementById('maintenanceChart').getContext('2d');
        new Chart(maintenanceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($maintenanceTypes)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($maintenanceTypes)); ?>,
                    backgroundColor: ['#00bcd4', '#4CAF50', '#FFC107', '#FF5722']
                }]
            }
        });

        // Car Types Chart
        const carTypesCtx = document.getElementById('carTypesChart').getContext('2d');
        new Chart(carTypesCtx, {
            type: 'polarArea',
            data: {
                labels: <?php echo json_encode(array_keys($brandData)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($brandData)); ?>,
                    backgroundColor: ['#00bcd4', '#4CAF50', '#FFC107', '#FF5722', '#9C27B0']
                }]
            }
        });
    </script>
</body>
</html>