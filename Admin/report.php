<?php
// Database connection
$host = 'localhost';
$dbname = 'car_services';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Report queries based on report type
$reportType = $_GET['type'] ?? 'rent';
$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';
$carId = $_GET['car_id'] ?? '';

function getReportData($type, $from, $to, $carId = null) {
    global $pdo;

    $query = "";
    $params = [':from' => $from, ':to' => $to];
    
    switch ($type) {
        case 'rent':
            $query = "SELECT r.*, c.CarName, c.Model, cu.Name as customer_name 
                      FROM Rental r
                      JOIN Car c ON r.CarID = c.CarID
                      JOIN Customer cu ON r.CustomerID = cu.CustomerID
                      WHERE r.StartDate BETWEEN :from AND :to";
            if ($carId) {
                $query .= " AND r.CarID = :car_id";
                $params[':car_id'] = $carId;
            }
            break;
            
        case 'sales':
            $query = "SELECT s.*, c.CarName, c.Model, cu.Name as customer_name 
                      FROM Sale s
                      JOIN Car c ON s.CarID = c.CarID
                      JOIN Customer cu ON s.CustomerID = cu.CustomerID
                      WHERE s.SaleDate BETWEEN :from AND :to";
            if ($carId) {
                $query .= " AND s.CarID = :car_id";
                $params[':car_id'] = $carId;
            }
            break;
            
        case 'repair':
            $query = "SELECT m.*, c.CarName, c.Model 
                      FROM Maintenance m
                      JOIN Car c ON m.CarID = c.CarID
                      WHERE m.MaintenanceDate BETWEEN :from AND :to";
            if ($carId) {
                $query .= " AND m.CarID = :car_id";
                $params[':car_id'] = $carId;
            }
            break;
            
        case 'revenue':
            $query = "SELECT SUM(TotalCost) as total FROM Rental WHERE StartDate BETWEEN :from AND :to";
            if ($carId) {
                $query .= " AND CarID = :car_id";
                $params[':car_id'] = $carId;
            }
            break;
            
        case 'service':
            $query = "SELECT m.*, c.CarName, c.Model, c.PlateNumber
                      FROM Maintenance m
                      JOIN Car c ON m.CarID = c.CarID
                      WHERE m.MaintenanceDate BETWEEN :from AND :to";
            if ($carId) {
                $query .= " AND m.CarID = :car_id";
                $params[':car_id'] = $carId;
            }
            $query .= " ORDER BY m.MaintenanceDate DESC";
            break;
            
        default:
            return false;
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fromDate = $_POST['from'];
    $toDate = $_POST['to'];
    $reportType = $_POST['reportType'];
    $carId = $_POST['car_id'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
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
            margin-bottom: 20px;
        }

        .section {
            background-color: #1e1e1e;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .report-title {
            margin-bottom: 20px;
            color: #00bcd4;
        }
        
        .table-dark {
            background-color: #1e1e1e;
        }
        
        .form-label {
            color: #aaa;
        }
        
        .alert {
            border-radius: 8px;
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
            <h1><i class="fas fa-chart-pie"></i> Reports - Car System</h1>
        </div>

        <div class="section">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="reportType" class="form-label">Report Type:</label>
                        <select id="reportType" name="reportType" class="form-select" required>
                            <option value="rent" <?= $reportType == 'rent' ? 'selected' : '' ?>>Rental Reports</option>
                            <option value="sales" <?= $reportType == 'sales' ? 'selected' : '' ?>>Sales Reports</option>
                            <option value="repair" <?= $reportType == 'repair' ? 'selected' : '' ?>>Repair Reports</option>
                            <option value="service" <?= $reportType == 'service' ? 'selected' : '' ?>>Service Reports</option>
                            <option value="revenue" <?= $reportType == 'revenue' ? 'selected' : '' ?>>Revenue Reports</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="car_id" class="form-label">Car ID:</label>
                        <input type="text" id="car_id" name="car_id" class="form-control" value="<?= htmlspecialchars($carId) ?>" placeholder="Optional">
                    </div>
                    <div class="col-md-2">
                        <label for="from" class="form-label">From Date:</label>
                        <input type="date" id="from" name="from" class="form-control" value="<?= htmlspecialchars($fromDate) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label for="to" class="form-label">To Date:</label>
                        <input type="date" id="to" name="to" class="form-control" value="<?= htmlspecialchars($toDate) ?>" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="section" id="output">
            <?php if ($fromDate && $toDate): ?>
                <?php $result = getReportData($reportType, $fromDate, $toDate, $carId); ?>
                
                <?php if ($result === false): ?>
                    <div class="alert alert-danger">Error generating report. Please try again.</div>
                <?php elseif ($result->rowCount() > 0): ?>
                    <h2 class="report-title">
                        <?= ucfirst($reportType) ?> Report 
                        <?= $carId ? "for Car ID: $carId" : '' ?>
                        from <?= htmlspecialchars($fromDate) ?> to <?= htmlspecialchars($toDate) ?>
                    </h2>
                    
                    <?php if ($reportType == 'rent'): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped">
                                <thead>
                                    <tr>
                                        <th>Car</th>
                                        <th>Customer</th>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['CarName']) ?> <?= htmlspecialchars($row['Model']) ?></td>
                                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($row['StartDate']) ?> to <?= htmlspecialchars($row['EndDate']) ?></td>
                                            <td><?= number_format($row['TotalCost'], 2) ?> EGP</td>
                                            <td><?= htmlspecialchars($row['Status']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    
                    <?php elseif ($reportType == 'sales'): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped">
                                <thead>
                                    <tr>
                                        <th>Car</th>
                                        <th>Customer</th>
                                        <th>Sale Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['CarName']) ?> <?= htmlspecialchars($row['Model']) ?></td>
                                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($row['SaleDate']) ?></td>
                                            <td><?= number_format($row['SalePrice'], 2) ?> EGP</td>
                                            <td><?= htmlspecialchars($row['Status']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    
                    <?php elseif ($reportType == 'repair' || $reportType == 'service'): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-striped">
                                <thead>
                                    <tr>
                                        <th>Car</th>
                                        <th>Maintenance Date</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Cost</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['CarName']) ?> <?= htmlspecialchars($row['Model']) ?></td>
                                            <td><?= htmlspecialchars($row['MaintenanceDate']) ?></td>
                                            <td><?= htmlspecialchars($row['MaintenanceType']) ?></td>
                                            <td><?= htmlspecialchars($row['Description']) ?></td>
                                            <td><?= number_format($row['Cost'], 2) ?> EGP</td>
                                            <td><?= htmlspecialchars($row['Status']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    
                    <?php elseif ($reportType == 'revenue'): ?>
                        <?php $row = $result->fetch(); ?>
                        <div class="alert alert-info">
                            <h4>Total Revenue: <?= number_format($row['total'], 2) ?> EGP</h4>
                            <?php if ($carId): ?>
                                <p>For Car ID: <?= htmlspecialchars($carId) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                
                <?php else: ?>
                    <div class="alert alert-warning">
                        No data found for <?= $reportType ?> report 
                        <?= $carId ? "for Car ID: $carId" : '' ?>
                        in the selected date range.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>