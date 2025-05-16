<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "car_services";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$totalCars = $conn->query("SELECT COUNT(*) FROM car")->fetch_row()[0];
$totalClients = $conn->query("SELECT COUNT(*) FROM customer")->fetch_row()[0];
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$rentalOrders = $conn->query("SELECT COUNT(*) FROM rental")->fetch_row()[0];
$salesOrders = $conn->query("SELECT COUNT(*) FROM sale")->fetch_row()[0];
$repairOrders = $conn->query("SELECT COUNT(*) FROM maintenance")->fetch_row()[0];
$messages = $conn->query("SELECT COUNT(*) FROM contact")->fetch_row()[0];
$feedback = $conn->query("SELECT COUNT(*) FROM feedback")->fetch_row()[0];
$availableCars = $conn->query("SELECT COUNT(*) FROM car WHERE status = 'available'")->fetch_row()[0];
$rentedCars = $conn->query("SELECT COUNT(*) FROM car WHERE status = 'rented'")->fetch_row()[0];
$soldCars = $conn->query("SELECT COUNT(*) FROM car WHERE status = 'sold'")->fetch_row()[0];


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Car Services Admin Dashboard</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">

  <!-- Custom Styles -->
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

    h1 {
      margin: 0;
      font-size: 24px;
      color: #e3d4d4;
    }

    .dashboard {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .card {
      background-color: #1a1a1a;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
      text-align: center;
      transition: transform 0.3s ease;
      border: 1px solid #333;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .card i {
      font-size: 30px;
      color: #00bcd4;
      margin-bottom: 10px;
    }

    .number {
      font-size: 24px;
      font-weight: bold;
      color: #fff;
    }



    .details {
      margin-top: 10px;
      font-size: 13px;
      color: #ccc;
      text-align: left;
    }

    .details div {
      margin: 2px 0;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
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

<!-- Main Content -->
<div class="main-content">
  <div class="top-bar">
    <h1><i class="fas fa-chart-line me-2"></i> Statistics Dashboard</h1>

  </div>

  <!-- Statistics Cards -->
  <div class="dashboard">
    <div class="card">
  <i class="fas fa-car"></i>
  <div class="number"><?= $totalCars ?></div>
  <div class="label">Total Cars</div>
  <div class="details">
    <div>Available: <?= $availableCars ?></div>
    <div>Rented: <?= $rentedCars ?></div>
    <div>Sold: <?= $soldCars ?></div>
  </div>
</div>
    <div class="card">
      <i class="fas fa-users"></i>
      <div class="number"><?= $totalClients ?></div>
      <div class="label">Total Clients</div>
    </div>
    <div class="card">
      <i class="fas fa-clipboard-list"></i>
      <div class="number"><?= $totalOrders ?></div>
      <div class="label">Total Orders</div>
    </div>
    <div class="card">
      <i class="fas fa-car-side"></i>
      <div class="number"><?= $rentalOrders ?></div>
      <div class="label">Rental Orders</div>
    </div>
    <div class="card">
      <i class="fas fa-shopping-cart"></i>
      <div class="number"><?= $salesOrders ?></div>
      <div class="label">Sales Orders</div>
    </div>
    <div class="card">
      <i class="fas fa-tools"></i>
      <div class="number"><?= $repairOrders ?></div>
      <div class="label">Repair Orders</div>
    </div>
    <div class="card">
      <i class="fas fa-envelope"></i>
      <div class="number"><?= $messages ?></div>
      <div class="label">Messages</div>
    </div>
    <div class="card">
      <i class="fas fa-comment-alt"></i>
      <div class="number"><?= $feedback ?></div>
      <div class="label">Feedback</div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>