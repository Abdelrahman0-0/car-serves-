<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_services";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");




// Get statistics data
$stats = [
    'available_cars' => 0,
    'today_sales' => 0,
    'active_rentals' => 0,
    'maintenance_jobs' => 0
];

// Available cars
$query = "SELECT COUNT(*) as count FROM Car WHERE Status = 'available'";
$result = $conn->query($query);
if ($result) {
    $stats['available_cars'] = $result->fetch_assoc()['count'];
}

// Today's sales
$query = "SELECT SUM(SalePrice) as total FROM Sale 
          WHERE DATE(SaleDate) = CURDATE() AND Status = 'completed'";
$result = $conn->query($query);
if ($result) {
    $stats['today_sales'] = $result->fetch_assoc()['total'] ?? 0;
}

// Active rentals
$query = "SELECT COUNT(*) as count FROM Rental 
          WHERE Status = 'active' AND CURDATE() BETWEEN StartDate AND EndDate";
$result = $conn->query($query);
if ($result) {
    $stats['active_rentals'] = $result->fetch_assoc()['count'];
}

// Maintenance jobs
$query = "SELECT COUNT(*) as count FROM Maintenance 
          WHERE Status IN ('pending', 'in_progress')";
$result = $conn->query($query);
if ($result) {
    $stats['maintenance_jobs'] = $result->fetch_assoc()['count'];
}

// Recent car listings
$cars = [];
$query = "SELECT c.*, o.Location as OfficeLocation 
          FROM Car c LEFT JOIN Office o ON c.OfficeID = o.OfficeID 
          ORDER BY c.CarID DESC LIMIT 5";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cars[] = $row;
    }
}

// Upcoming maintenance
$maintenance = [];
$query = "SELECT m.*, c.CarName, c.Model, cu.Name as CustomerName 
          FROM Maintenance m
          JOIN Car c ON m.CarID = c.CarID
          LEFT JOIN Rental r ON r.CarID = c.CarID AND r.Status = 'active'
          LEFT JOIN Customer cu ON r.CustomerID = cu.CustomerID
          WHERE m.Status IN ('pending', 'in_progress')
          ORDER BY m.MaintenanceDate ASC LIMIT 3";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $maintenance[] = $row;
    }
}

// Monthly sales data for chart
$monthly_data = [
    'sales' => [],
    'rentals' => []
];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    
    // Sales count
    $query = "SELECT COUNT(*) as count FROM Sale 
              WHERE DATE_FORMAT(SaleDate, '%Y-%m') = '$month' AND Status = 'completed'";
    $result = $conn->query($query);
    $monthly_data['sales'][] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Rentals count
    $query = "SELECT COUNT(*) as count FROM Rental 
              WHERE DATE_FORMAT(StartDate, '%Y-%m') = '$month' AND Status = 'completed'";
    $result = $conn->query($query);
    $monthly_data['rentals'][] = $result ? $result->fetch_assoc()['count'] : 0;
}

// Inventory by type
$inventory = [];
$query = "SELECT 
            SUM(CASE WHEN Model LIKE '%SUV%' THEN 1 ELSE 0 END) as suv,
            SUM(CASE WHEN Model LIKE '%Truck%' OR Model LIKE '%Pickup%' THEN 1 ELSE 0 END) as truck,
            SUM(CASE WHEN Model LIKE '%Sedan%' THEN 1 ELSE 0 END) as sedan,
            SUM(CASE WHEN Model LIKE '%Sports%' THEN 1 ELSE 0 END) as sports,
            SUM(CASE WHEN Model LIKE '%Electric%' OR Model LIKE '%EV%' THEN 1 ELSE 0 END) as electric
          FROM Car WHERE Status != 'sold'";
$result = $conn->query($query);
if ($result) {
    $inventory = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Services Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
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

        .stats-card {
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card h3 {
            margin: 0;
            font-size: 24px;
        }

        .stats-card p {
            margin: 5px 0 0;
            font-size: 16px;
            color: #aaa;
        }

        .chart-container {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .table-container {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            background-color: #1a1a1a;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #333;
        }

        table th {
            background-color: #333;
            color: #00bcd4;
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

        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-info {
            background-color: #17a2b8;
        }

        /* DataTables Custom Styling for Dark Theme */
        .dataTables_wrapper {
            color: #fff;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #fff;
        }

        .dataTables_wrapper .dataTables_filter input {
            background-color: #333;
            color: #fff;
            border: 1px solid #444;
        }

        .dataTables_wrapper .dataTables_length select {
            background-color: #333;
            color: #fff;
            border: 1px solid #444;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #fff !important;
            background: #333;
            border: 1px solid #444;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #444 !important;
            color: white !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #00bcd4 !important;
            color: white !important;
            border: 1px solid #00bcd4;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #008ba3 !important;
            color: white !important;
        }

        table.dataTable {
            color: #fff;
            background-color: #1a1a1a;
            border: 1px solid #333;
        }

        table.dataTable thead th, 
        table.dataTable thead td {
            border-bottom: 1px solid #333;
            background-color: #333;
            color: #00bcd4;
        }

        table.dataTable tbody th, 
        table.dataTable tbody td {
            border-bottom: 1px solid #333;
            background-color: #1a1a1a;
            color: #fff;
        }

        table.dataTable tbody tr:hover {
            background-color: #333 !important;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #1f1f1f;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #1a1a1a;
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
            <h2><i class="fas fa-car me-2"></i> Car Services Dashboard</h2>
            <div>
                <button class="btn btn-primary me-2" onclick="showNotifications()">
                    <i class="fas fa-bell me-1"></i> Notifications
                    <span class="badge bg-danger ms-1">3</span>
                </button>
                <button class="btn btn-primary" onclick="showMessages()">
                    <i class="fas fa-envelope me-1"></i> Messages
                    <span class="badge bg-warning text-dark ms-1">5</span>
                </button>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><?php echo $stats['available_cars']; ?></h3>
                            <p>Available Cars</p>
                        </div>
                        <i class="fas fa-car fa-2x text-info"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>$<?php echo number_format($stats['today_sales'], 2); ?></h3>
                            <p>Today's Sales</p>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><?php echo $stats['active_rentals']; ?></h3>
                            <p>Active Rentals</p>
                        </div>
                        <i class="fas fa-calendar-alt fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><?php echo $stats['maintenance_jobs']; ?></h3>
                            <p>Maintenance Jobs</p>
                        </div>
                        <i class="fas fa-tools fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h4><i class="fas fa-chart-line me-2"></i>Monthly Sales & Rentals</h4>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h4><i class="fas fa-chart-pie me-2"></i>Inventory by Type</h4>
                    <canvas id="inventoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-car me-2"></i>Recent Car Listings</h4>
                <a href="manage_car.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add New Car</a>
            </div>
            <table id="carsTable" class="display">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Make/Model</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $car): ?>
                    <tr>
                        <td>CAR-<?php echo $car['CarID']; ?></td>
                        <td><?php echo htmlspecialchars($car['CarName'] . ' ' . $car['Model']); ?></td>
                        <td><?php echo $car['Year']; ?></td>
                        <td>
                            <?php 
                            $badge_class = '';
                            switch ($car['Status']) {
                                case 'available': $badge_class = 'badge-success'; break;
                                case 'rented': $badge_class = 'badge-info'; break;
                                case 'maintenance': $badge_class = 'badge-warning'; break;
                                case 'sold': $badge_class = 'badge-danger'; break;
                                default: $badge_class = 'badge-secondary';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst($car['Status']); ?>
                            </span>
                        </td>
                        <td>$<?php echo number_format($car['PricePerDay'], 2); ?>/day</td>
                        <td><?php echo htmlspecialchars($car['OfficeLocation'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="manage_car.php?action=view&id=<?php echo $car['CarID']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>
                            <a href="manage_car.php?action=edit&id=<?php echo $car['CarID']; ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-calendar-alt me-2"></i>Upcoming Maintenance</h4>
                <a href="manage_maintenance.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Schedule Service</a>
            </div>
            <table id="maintenanceTable" class="display">
                <thead>
                    <tr>
                        <th>Service ID</th>
                        <th>Car</th>
                        <th>Customer</th>
                        <th>Service Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenance as $job): ?>
                    <tr>
                        <td>SRV-<?php echo $job['MaintenanceID']; ?></td>
                        <td><?php echo htmlspecialchars($job['CarName'] . ' ' . $job['Model']); ?></td>
                        <td><?php echo htmlspecialchars($job['CustomerName'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($job['MaintenanceType']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($job['MaintenanceDate'])); ?></td>
                        <td>
                            <?php 
                            $badge_class = '';
                            switch ($job['Status']) {
                                case 'pending': $badge_class = 'badge-warning'; break;
                                case 'in_progress': $badge_class = 'badge-info'; break;
                                case 'completed': $badge_class = 'badge-success'; break;
                                default: $badge_class = 'badge-secondary';
                            }
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $job['Status'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="manage_maintenance.php?action=view&id=<?php echo $job['MaintenanceID']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>
                            <a href="manage_maintenance.php?action=edit&id=<?php echo $job['MaintenanceID']; ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $months = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $months[] = date('M', strtotime("-$i months"));
                    }
                    echo json_encode($months);
                ?>,
                datasets: [
                    {
                        label: 'Car Sales',
                        data: <?php echo json_encode($monthly_data['sales']); ?>,
                        backgroundColor: 'rgba(0, 188, 212, 0.7)',
                        borderColor: 'rgba(0, 188, 212, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Rentals',
                        data: <?php echo json_encode($monthly_data['rentals']); ?>,
                        backgroundColor: 'rgba(255, 193, 7, 0.7)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Inventory Chart
        const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
        const inventoryChart = new Chart(inventoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sedan', 'SUV', 'Truck', 'Sports', 'Electric'],
                datasets: [{
                    data: [
                        <?php echo $inventory['sedan'] ?? 0; ?>,
                        <?php echo $inventory['suv'] ?? 0; ?>,
                        <?php echo $inventory['truck'] ?? 0; ?>,
                        <?php echo $inventory['sports'] ?? 0; ?>,
                        <?php echo $inventory['electric'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        'rgba(0, 188, 212, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(23, 162, 184, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Initialize DataTables
        $(document).ready(function() {
            $('#carsTable, #maintenanceTable').DataTable();
        });

        function showNotifications() {
            Swal.fire({
                title: 'Notifications',
                html: `
                    <div class="notification-tabs">
                        <button class="btn btn-primary btn-sm" onclick="showTab('unread')">Unread</button>
                        <button class="btn btn-primary btn-sm" onclick="showTab('read')">Read</button>
                        <button class="btn btn-primary btn-sm" onclick="showTab('all')">All</button>
                    </div>
                    <div id="notification-content" class="mt-3">
                        <!-- Notification content will be loaded here -->
                    </div>
                `,
                width: '600px',
                showConfirmButton: false,
                didOpen: () => {
                    showTab('unread');
                }
            });
        }

        function showTab(tab) {
            const content = document.getElementById('notification-content');
            let notifications = [];

            if (tab === 'unread') {
                notifications = [
                    { id: 1, message: 'New car listing added: Tesla Model 3', date: '2023-06-10' },
                    { id: 2, message: 'Test drive scheduled for BMW X5', date: '2023-06-11' },
                    { id: 3, message: 'Maintenance completed for Ford F-150', date: '2023-06-12' }
                ];
            } else if (tab === 'read') {
                notifications = [
                    { id: 4, message: 'Payment received for Toyota Camry', date: '2023-06-08' },
                    { id: 5, message: 'New rental agreement signed', date: '2023-06-07' }
                ];
            } else if (tab === 'all') {
                notifications = [
                    { id: 1, message: 'New car listing added: Tesla Model 3', date: '2023-06-10' },
                    { id: 2, message: 'Test drive scheduled for BMW X5', date: '2023-06-11' },
                    { id: 3, message: 'Maintenance completed for Ford F-150', date: '2023-06-12' },
                    { id: 4, message: 'Payment received for Toyota Camry', date: '2023-06-08' },
                    { id: 5, message: 'New rental agreement signed', date: '2023-06-07' }
                ];
            }

            content.innerHTML = notifications.map(notification => `
                <div class="notification-item p-2 mb-2" style="background-color: #1a1a1a; border-radius: 4px;">
                    <p><strong>${notification.message}</strong> <small class="text-muted">${notification.date}</small></p>
                </div>
            `).join('');
        }

        function showMessages() {
            Swal.fire({
                title: 'Messages',
                html: `
                    <div class="message-tabs">
                        <button class="btn btn-primary btn-sm" onclick="showMessageTab('inbox')">Inbox</button>
                        <button class="btn btn-primary btn-sm" onclick="showMessageTab('sent')">Sent</button>
                        <button class="btn btn-primary btn-sm" onclick="showMessageTab('important')">Important</button>
                    </div>
                    <div id="message-content" class="mt-3">
                        <!-- Message content will be loaded here -->
                    </div>
                `,
                width: '600px',
                showConfirmButton: false,
                didOpen: () => {
                    showMessageTab('inbox');
                }
            });
        }

        function showMessageTab(tab) {
            const content = document.getElementById('message-content');
            let messages = [];

            if (tab === 'inbox') {
                messages = [
                    { id: 1, from: 'John Smith', subject: 'Inquiry about Toyota Camry', date: '2023-06-10' },
                    { id: 2, from: 'Car Dealer Network', subject: 'New inventory available', date: '2023-06-09' }
                ];
            } else if (tab === 'sent') {
                messages = [
                    { id: 3, to: 'Service Department', subject: 'Urgent: Brake parts needed', date: '2023-06-08' },
                    { id: 4, to: 'Sales Team', subject: 'Monthly targets update', date: '2023-06-07' }
                ];
            } else if (tab === 'important') {
                messages = [
                    { id: 5, from: 'Finance Department', subject: 'Quarterly report review', date: '2023-06-05' },
                    { id: 6, from: 'CEO', subject: 'Strategy meeting next week', date: '2023-06-04' }
                ];
            }

            content.innerHTML = messages.map(message => `
                <div class="message-item p-2 mb-2" style="background-color: #1a1a1a; border-radius: 4px;">
                    <p><strong>${tab === 'sent' ? 'To' : 'From'}: ${tab === 'sent' ? message.to : message.from}</strong></p>
                    <p>${message.subject} <small class="text-muted">${message.date}</small></p>
                </div>
            `).join('');
        }
    </script>
</body>
</html>