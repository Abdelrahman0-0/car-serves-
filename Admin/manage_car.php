<?php
// الاتصال بقاعدة البيانات
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "car_services";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// معالجة عمليات الإضافة والتحديث والحذف
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_car':
                $result = addCar($conn);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
            case 'update_car':
                $result = updateCar($conn);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
            case 'delete_car':
                $result = deleteCar($conn);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
            case 'change_status':
                $result = changeCarStatus($conn);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// دالة إضافة سيارة جديدة
function addCar($conn) {
    $name = $_POST['name'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = $_POST['year'] ?? '';
    $color = $_POST['color'] ?? '';
    $plate = $_POST['plate'] ?? '';
    $status = $_POST['status'] ?? '';
    $pricePerDay = ($status === 'available' || $status === 'rented') ? ($_POST['dailyPrice'] ?? 0) : null;
    $salePrice = ($status === 'sold') ? ($_POST['salePrice'] ?? 0) : null;
    $officeID = $_POST['officeID'] ?? null;

    if (empty($name) || empty($model) || empty($year) || empty($color) || empty($plate) || empty($status)) {
        return ['success' => false, 'message' => 'Please fill all required fields'];
    }

    if (($status === 'available' || $status === 'rented') && empty($pricePerDay)) {
        return ['success' => false, 'message' => 'Please enter daily price'];
    }

    if ($status === 'sold' && empty($salePrice)) {
        return ['success' => false, 'message' => 'Please enter sale price'];
    }

    try {
        $stmt = $conn->prepare("INSERT INTO Car (CarName, Model, Year, Color, PlateNumber, Status, PricePerDay, SalePrice, OfficeID) 
                               VALUES (:name, :model, :year, :color, :plate, :status, :pricePerDay, :salePrice, :officeID)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':model', $model);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':color', $color);
        $stmt->bindParam(':plate', $plate);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':pricePerDay', $pricePerDay);
        $stmt->bindParam(':salePrice', $salePrice);
        $stmt->bindParam(':officeID', $officeID);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Car added successfully'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// دالة تحديث بيانات السيارة
function updateCar($conn) {
    $carID = $_POST['carID'] ?? '';
    $name = $_POST['name'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = $_POST['year'] ?? '';
    $color = $_POST['color'] ?? '';
    $plate = $_POST['plate'] ?? '';
    $status = $_POST['status'] ?? '';
    $pricePerDay = ($status === 'available' || $status === 'rented') ? ($_POST['dailyPrice'] ?? 0) : null;
    $salePrice = ($status === 'sold') ? ($_POST['salePrice'] ?? 0) : null;

    if (empty($carID) || empty($name) || empty($model) || empty($year) || empty($color) || empty($plate) || empty($status)) {
        return ['success' => false, 'message' => 'Please fill all required fields'];
    }

    if (($status === 'available' || $status === 'rented') && empty($pricePerDay)) {
        return ['success' => false, 'message' => 'Please enter daily price'];
    }

    if ($status === 'sold' && empty($salePrice)) {
        return ['success' => false, 'message' => 'Please enter sale price'];
    }

    try {
        $stmt = $conn->prepare("UPDATE Car SET 
                               CarName = :name, 
                               Model = :model, 
                               Year = :year, 
                               Color = :color, 
                               PlateNumber = :plate, 
                               Status = :status, 
                               PricePerDay = :pricePerDay, 
                               SalePrice = :salePrice 
                               WHERE CarID = :carID");
        $stmt->bindParam(':carID', $carID);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':model', $model);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':color', $color);
        $stmt->bindParam(':plate', $plate);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':pricePerDay', $pricePerDay);
        $stmt->bindParam(':salePrice', $salePrice);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Car updated successfully'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// دالة حذف السيارة
function deleteCar($conn) {
    $carID = $_POST['carID'] ?? '';

    if (empty($carID)) {
        return ['success' => false, 'message' => 'Please enter car ID'];
    }

    try {
        $stmt = $conn->prepare("DELETE FROM Car WHERE CarID = :carID");
        $stmt->bindParam(':carID', $carID);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Car deleted successfully'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// دالة تغيير حالة السيارة
function changeCarStatus($conn) {
    $carID = $_POST['carID'] ?? '';
    $newStatus = $_POST['newStatus'] ?? '';
    $pricePerDay = ($newStatus === 'available' || $newStatus === 'rented') ? ($_POST['dailyPrice'] ?? 0) : null;
    $salePrice = ($newStatus === 'sold') ? ($_POST['salePrice'] ?? 0) : null;

    if (empty($carID) || empty($newStatus)) {
        return ['success' => false, 'message' => 'Please fill all required fields'];
    }

    if (($newStatus === 'available' || $newStatus === 'rented') && empty($pricePerDay)) {
        return ['success' => false, 'message' => 'Please enter daily price'];
    }

    if ($newStatus === 'sold' && empty($salePrice)) {
        return ['success' => false, 'message' => 'Please enter sale price'];
    }

    try {
        $stmt = $conn->prepare("UPDATE Car SET 
                               Status = :status, 
                               PricePerDay = :pricePerDay, 
                               SalePrice = :salePrice 
                               WHERE CarID = :carID");
        $stmt->bindParam(':carID', $carID);
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':pricePerDay', $pricePerDay);
        $stmt->bindParam(':salePrice', $salePrice);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'Car status changed successfully'];
    } catch(PDOException $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// جلب بيانات السيارات لعرضها في الجدول
function getCars($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM Car");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error fetching cars: " . $e->getMessage());
    }
}

// جلب بيانات سيارة محددة
function getCarById($conn, $carID) {
    try {
        $stmt = $conn->prepare("SELECT c.*, o.Location as OfficeLocation FROM Car c LEFT JOIN Office o ON c.OfficeID = o.OfficeID WHERE c.CarID = :carID");
        $stmt->bindParam(':carID', $carID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return null;
    }
}

// جلب بيانات المكاتب
function getOffices($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM Office");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

$cars = getCars($conn);
$offices = getOffices($conn);

// متغيرات لعرض النماذج
$show_add_modal = isset($_GET['show_add_modal']);
$show_update_modal = isset($_GET['show_update_modal']);
$show_delete_modal = isset($_GET['show_delete_modal']);
$show_status_modal = isset($_GET['show_status_modal']);
$show_details_modal = isset($_GET['show_details_modal']);

// بيانات السيارة للعرض في النماذج
$car_to_edit = null;
$car_to_delete = null;
$car_to_change_status = null;
$car_details = null;

if ($show_update_modal && isset($_GET['car_id'])) {
    $car_to_edit = getCarById($conn, $_GET['car_id']);
    if (!$car_to_edit) {
        $message = 'Car not found';
        $message_type = 'error';
        $show_update_modal = false;
    }
}

if ($show_delete_modal && isset($_GET['car_id'])) {
    $car_to_delete = getCarById($conn, $_GET['car_id']);
    if (!$car_to_delete) {
        $message = 'Car not found';
        $message_type = 'error';
        $show_delete_modal = false;
    }
}

if ($show_status_modal && isset($_GET['car_id'])) {
    $car_to_change_status = getCarById($conn, $_GET['car_id']);
    if (!$car_to_change_status) {
        $message = 'Car not found';
        $message_type = 'error';
        $show_status_modal = false;
    }
}

if ($show_details_modal && isset($_GET['car_id'])) {
    $car_details = getCarById($conn, $_GET['car_id']);
    if (!$car_details) {
        $message = 'Car not found';
        $message_type = 'error';
        $show_details_modal = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="mangcar.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header text-center mb-4">
            <h4><i class="fas fa-car"></i> Car Services</h4>
        </div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="data_Admin.php"><i class="fas fa-users"></i> Users</a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="Analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="clints.php"><i class="fas fa-user-tie"></i> Clients</a>
        <a href="reports.php"><i class="fas fa-clipboard-list"></i> Reports</a>
        <a href="Statistics.php"><i class="fas fa-chart-bar me-2"></i> Statistics</a>
        <a href="mange.php"><i class="fas fa-cogs"></i> Manage</a>
        <a href="manage_car.php" class="active"><i class="fas fa-car"></i> Cars</a>
        <a href="#logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    
    <div class="main-content">
        <!-- رسائل التنبيه -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="top-bar">
            <h1>Car Management</h1>
            <div>
                <div class="dropdown d-inline-block me-2">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="managementDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i> Car Management
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                        <li><a class="dropdown-item" href="manage_car.php?show_add_modal=1"><i class="fas fa-plus me-2"></i>Add New Car</a></li>
                        <li><a class="dropdown-item" href="manage_car.php?show_delete_modal=1"><i class="fas fa-trash me-2"></i>Delete Car</a></li>
                        <li><a class="dropdown-item" href="manage_car.php?show_update_modal=1"><i class="fas fa-edit me-2"></i>Update Car Info</a></li>
                        <li><a class="dropdown-item" href="manage_car.php?show_status_modal=1"><i class="fas fa-sync-alt me-2"></i>Change Car Status</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Car List</h2>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter"></i> Filter by Status
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                            <li><a class="dropdown-item" href="manage_car.php">All</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="manage_car.php?status=available">Available</a></li>
                            <li><a class="dropdown-item" href="manage_car.php?status=rented">Reserved</a></li>
                            <li><a class="dropdown-item" href="manage_car.php?status=sold">Sold</a></li>
                            <li><a class="dropdown-item" href="manage_car.php?status=maintenance">Out of Service</a></li>
                        </ul>
                    </div>
                    <div class="search-box">
                        <form method="GET" action="manage_car.php">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo $_GET['search'] ?? ''; ?>">
                                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <table id="carsTable" class="display">
                <thead>
                    <tr>
                        <th>Car ID</th>
                        <th>Name</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Color</th>
                        <th>License Plate</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // فلترة السيارات حسب الحالة إذا تم تحديدها
                    $filtered_cars = $cars;
                    if (isset($_GET['status'])) {
                        $filtered_cars = array_filter($cars, function($car) {
                            return $car['Status'] === $_GET['status'];
                        });
                    }
                    
                    // البحث عن السيارات إذا تم إدخال نص بحث
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $search_term = strtolower($_GET['search']);
                        $filtered_cars = array_filter($filtered_cars, function($car) use ($search_term) {
                            return (strpos(strtolower($car['CarName']), $search_term) !== false ||
                                   strpos(strtolower($car['Model']), $search_term) !== false ||
                                   strpos(strtolower($car['PlateNumber']), $search_term) !== false ||
                                   strpos(strtolower($car['Color']), $search_term) !== false);
                        });
                    }
                    
                    foreach ($filtered_cars as $car): 
                        $status_text = ucfirst(str_replace('_', ' ', $car['Status']));
                        $status_class = $car['Status'];
                    ?>
                        <tr>
                            <td><?php echo $car['CarID']; ?></td>
                            <td><?php echo htmlspecialchars($car['CarName']); ?></td>
                            <td><?php echo htmlspecialchars($car['Model']); ?></td>
                            <td><?php echo $car['Year']; ?></td>
                            <td><?php echo htmlspecialchars($car['Color']); ?></td>
                            <td><?php echo htmlspecialchars($car['PlateNumber']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    if ($car['Status'] === 'sold') {
                                        echo '$' . number_format($car['SalePrice'], 2);
                                    } elseif ($car['Status'] === 'available' || $car['Status'] === 'rented') {
                                        echo '$' . number_format($car['PricePerDay'], 2) . '/day';
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td>
                                <a href="manage_car.php?show_details_modal=1&car_id=<?php echo $car['CarID']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal إضافة سيارة جديدة -->
    <?php if ($show_add_modal): ?>
    <div class="modal fade show" id="addCarModal" tabindex="-1" aria-labelledby="addCarModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #1a1a1a; color: #fff;">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCarModalLabel">Add New Car</h5>
                    <a href="manage_car.php" class="btn-close" aria-label="Close" style="filter: invert(1);"></a>
                </div>
                <form method="POST" action="manage_car.php">
                    <input type="hidden" name="action" value="add_car">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="carName" class="form-label">Car Name</label>
                            <input type="text" class="form-control" id="carName" name="name" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="carModel" class="form-label">Model</label>
                            <input type="text" class="form-control" id="carModel" name="model" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="carYear" class="form-label">Year</label>
                            <input type="number" class="form-control" id="carYear" name="year" min="2000" max="<?php echo date('Y'); ?>" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="carColor" class="form-label">Color</label>
                            <input type="text" class="form-control" id="carColor" name="color" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="carPlate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="carPlate" name="plate" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="carStatus" class="form-label">Status</label>
                            <select class="form-select" id="carStatus" name="status" onchange="updatePriceField()" required style="background-color: #333; color: #fff; border-color: #444;">
                                <option value="available" selected>Available</option>
                                <option value="rented">Reserved</option>
                                <option value="sold">Sold</option>
                                <option value="maintenance">Out of Service</option>
                            </select>
                        </div>
                        <div id="dailyPriceContainer" class="mb-3">
                            <label for="carDailyPrice" class="form-label">Daily Price ($)</label>
                            <input type="number" class="form-control" id="carDailyPrice" name="dailyPrice" min="1" step="0.01" style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div id="salePriceContainer" class="mb-3" style="display:none;">
                            <label for="carSalePrice" class="form-label">Sale Price ($)</label>
                            <input type="number" class="form-control" id="carSalePrice" name="salePrice" min="1" step="0.01" style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="carOffice" class="form-label">Office Location</label>
                            <select class="form-select" id="carOffice" name="officeID" style="background-color: #333; color: #fff; border-color: #444;">
                                <option value="">Select Office</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['OfficeID']; ?>"><?php echo htmlspecialchars($office['Location']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="carImage" class="form-label">Car Image</label>
                            <input type="file" class="form-control" id="carImage" name="image" accept="image/*" style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_car.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal تحديث بيانات السيارة -->
    <?php if ($show_update_modal && $car_to_edit): ?>
    <div class="modal fade show" id="updateCarModal" tabindex="-1" aria-labelledby="updateCarModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #1a1a1a; color: #fff;">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateCarModalLabel">Update Car Information</h5>
                    <a href="manage_car.php" class="btn-close" aria-label="Close" style="filter: invert(1);"></a>
                </div>
                <form method="POST" action="manage_car.php">
                    <input type="hidden" name="action" value="update_car">
                    <input type="hidden" name="carID" value="<?php echo $car_to_edit['CarID']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="updateCarName" class="form-label">Car Name</label>
                            <input type="text" class="form-control" id="updateCarName" name="name" value="<?php echo htmlspecialchars($car_to_edit['CarName']); ?>" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="updateCarModel" class="form-label">Model</label>
                            <input type="text" class="form-control" id="updateCarModel" name="model" value="<?php echo htmlspecialchars($car_to_edit['Model']); ?>" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="updateCarYear" class="form-label">Year</label>
                            <input type="number" class="form-control" id="updateCarYear" name="year" min="2000" max="<?php echo date('Y'); ?>" value="<?php echo $car_to_edit['Year']; ?>" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="updateCarColor" class="form-label">Color</label>
                            <input type="text" class="form-control" id="updateCarColor" name="color" value="<?php echo htmlspecialchars($car_to_edit['Color']); ?>" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="updateCarPlate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="updateCarPlate" name="plate" value="<?php echo htmlspecialchars($car_to_edit['PlateNumber']); ?>" required style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="updateCarStatus" class="form-label">Status</label>
                            <select class="form-select" id="updateCarStatus" name="status" onchange="updatePriceFieldInUpdate()" required style="background-color: #333; color: #fff; border-color: #444;">
                                <option value="available" <?php echo $car_to_edit['Status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="rented" <?php echo $car_to_edit['Status'] === 'rented' ? 'selected' : ''; ?>>Reserved</option>
                                <option value="sold" <?php echo $car_to_edit['Status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                <option value="maintenance" <?php echo $car_to_edit['Status'] === 'maintenance' ? 'selected' : ''; ?>>Out of Service</option>
                            </select>
                        </div>
                        <div id="updateDailyPriceContainer" class="mb-3" <?php echo ($car_to_edit['Status'] === 'available' || $car_to_edit['Status'] === 'rented') ? '' : 'style="display:none;"'; ?>>
                            <label for="updateCarDailyPrice" class="form-label">Daily Price ($)</label>
                            <input type="number" class="form-control" id="updateCarDailyPrice" name="dailyPrice" min="1" step="0.01" value="<?php echo $car_to_edit['PricePerDay'] ?? ''; ?>" style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div id="updateSalePriceContainer" class="mb-3" <?php echo $car_to_edit['Status'] === 'sold' ? '' : 'style="display:none;"'; ?>>
                            <label for="updateCarSalePrice" class="form-label">Sale Price ($)</label>
                            <input type="number" class="form-control" id="updateCarSalePrice" name="salePrice" min="1" step="0.01" value="<?php echo $car_to_edit['SalePrice'] ?? ''; ?>" style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                        <div class="mb-3">
                            <label for="updateCarOffice" class="form-label">Office Location</label>
                            <select class="form-select" id="updateCarOffice" name="officeID" style="background-color: #333; color: #fff; border-color: #444;">
                                <option value="">Select Office</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo $office['OfficeID']; ?>" <?php echo $car_to_edit['OfficeID'] == $office['OfficeID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['Location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="updateCarImage" class="form-label">Change Car Image</label>
                            <input type="file" class="form-control" id="updateCarImage" name="image" accept="image/*" style="background-color: #333; color: #fff; border-color: #444;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_car.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal حذف السيارة -->
    <?php if ($show_delete_modal && $car_to_delete): ?>
    <div class="modal fade show" id="deleteCarModal" tabindex="-1" aria-labelledby="deleteCarModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: #1a1a1a; color: #fff;">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCarModalLabel">Delete Car</h5>
                    <a href="manage_car.php" class="btn-close" aria-label="Close" style="filter: invert(1);"></a>
                </div>
                <form method="POST" action="manage_car.php">
                    <input type="hidden" name="action" value="delete_car">
                    <input type="hidden" name="carID" value="<?php echo $car_to_delete['CarID']; ?>">
                    <div class="modal-body">
                        <div id="carToDeleteInfo">
                            <h5>Car Information</h5>
                            <p><strong>ID:</strong> <?php echo $car_to_delete['CarID']; ?></p>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($car_to_delete['CarName']); ?></p>
                            <p><strong>Model:</strong> <?php echo htmlspecialchars($car_to_delete['Model']); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($car_to_delete['Color']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge <?php echo $car_to_delete['Status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $car_to_delete['Status'])); ?>
                                </span>
                            </p>
                            <div class="alert alert-danger mt-3" style="background-color: #dc3545; color: white; border-color: #dc3545;">
                                <strong>Warning!</strong> Are you sure you want to delete this car? This action cannot be undone.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_car.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal تغيير حالة السيارة -->
    <?php if ($show_status_modal && $car_to_change_status): ?>
    <div class="modal fade show" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: #1a1a1a; color: #fff;">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">Change Car Status</h5>
                    <a href="manage_car.php" class="btn-close" aria-label="Close" style="filter: invert(1);"></a>
                </div>
                <form method="POST" action="manage_car.php">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="carID" value="<?php echo $car_to_change_status['CarID']; ?>">
                    <div class="modal-body">
                        <div id="carToChangeStatusInfo">
                            <h5>Car Information</h5>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($car_to_change_status['CarName']); ?></p>
                            <p><strong>Model:</strong> <?php echo htmlspecialchars($car_to_change_status['Model']); ?></p>
                            <p><strong>Current Status:</strong> 
                                <span class="status-badge <?php echo $car_to_change_status['Status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $car_to_change_status['Status'])); ?>
                                </span>
                            </p>
                            <div class="mb-3 mt-3">
                                <label for="newCarStatus" class="form-label">New Status</label>
                                <select class="form-select" id="newCarStatus" name="newStatus" onchange="updatePriceFieldInStatusChange()" required style="background-color: #333; color: #fff; border-color: #444;">
                                    <option value="available" <?php echo $car_to_change_status['Status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="rented" <?php echo $car_to_change_status['Status'] === 'rented' ? 'selected' : ''; ?>>Reserved</option>
                                    <option value="sold" <?php echo $car_to_change_status['Status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                    <option value="maintenance" <?php echo $car_to_change_status['Status'] === 'maintenance' ? 'selected' : ''; ?>>Out of Service</option>
                                </select>
                            </div>
                            <div id="statusDailyPriceContainer" class="mb-3" <?php echo ($car_to_change_status['Status'] === 'available' || $car_to_change_status['Status'] === 'rented') ? '' : 'style="display:none;"'; ?>>
                                <label for="statusCarDailyPrice" class="form-label">Daily Price ($)</label>
                                <input type="number" class="form-control" id="statusCarDailyPrice" name="dailyPrice" min="1" step="0.01" value="<?php echo $car_to_change_status['PricePerDay'] ?? ''; ?>" style="background-color: #333; color: #fff; border-color: #444;">
                            </div>
                            <div id="statusSalePriceContainer" class="mb-3" <?php echo $car_to_change_status['Status'] === 'sold' ? '' : 'style="display:none;"'; ?>>
                                <label for="statusCarSalePrice" class="form-label">Sale Price ($)</label>
                                <input type="number" class="form-control" id="statusCarSalePrice" name="salePrice" min="1" step="0.01" value="<?php echo $car_to_change_status['SalePrice'] ?? ''; ?>" style="background-color: #333; color: #fff; border-color: #444;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_car.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Change Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal عرض تفاصيل السيارة -->
    <?php if ($show_details_modal && $car_details): ?>
    <div class="modal fade show" id="carDetailsModal" tabindex="-1" aria-labelledby="carDetailsModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #1a1a1a; color: #fff;">
                <div class="modal-header">
                    <h5 class="modal-title" id="carDetailsModalLabel">Car Details</h5>
                    <a href="manage_car.php" class="btn-close" aria-label="Close" style="filter: invert(1);"></a>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img src="<?php echo $car_details['Image'] ? htmlspecialchars($car_details['Image']) : 'https://via.placeholder.com/300'; ?>" class="img-fluid mb-3" alt="Car Image">
                        </div>
                        <div class="col-md-6">
                            <h4><?php echo htmlspecialchars($car_details['CarName']); ?> <?php echo $car_details['Year']; ?></h4>
                            <p><strong>Car ID:</strong> <?php echo $car_details['CarID']; ?></p>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($car_details['CarName']); ?></p>
                            <p><strong>Model:</strong> <?php echo htmlspecialchars($car_details['Model']); ?></p>
                            <p><strong>Year:</strong> <?php echo $car_details['Year']; ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($car_details['Color']); ?></p>
                            <p><strong>License Plate:</strong> <?php echo htmlspecialchars($car_details['PlateNumber']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge <?php echo $car_details['Status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $car_details['Status'])); ?>
                                </span>
                            </p>
                            <p><strong>Price:</strong> 
                                <?php 
                                    if ($car_details['Status'] === 'sold') {
                                        echo '$' . number_format($car_details['SalePrice'], 2);
                                    } elseif ($car_details['Status'] === 'available' || $car_details['Status'] === 'rented') {
                                        echo '$' . number_format($car_details['PricePerDay'], 2) . '/day';
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </p>
                            <?php if (!empty($car_details['OfficeLocation'])): ?>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($car_details['OfficeLocation']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="manage_car.php" class="btn btn-secondary">Close</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#carsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/en-GB.json'
            },
            dom: 'lrtip',
            initComplete: function() {
                $('.dataTables_filter').hide();
            }
        });
    });

    function updatePriceField() {
        const status = document.getElementById('carStatus').value;
        const dailyPriceContainer = document.getElementById('dailyPriceContainer');
        const salePriceContainer = document.getElementById('salePriceContainer');

        dailyPriceContainer.style.display = 'none';
        salePriceContainer.style.display = 'none';

        if (status === 'available' || status === 'rented') {
            dailyPriceContainer.style.display = 'block';
        } else if (status === 'sold') {
            salePriceContainer.style.display = 'block';
        }
    }

    function updatePriceFieldInUpdate() {
        const status = document.getElementById('updateCarStatus').value;
        const dailyPriceContainer = document.getElementById('updateDailyPriceContainer');
        const salePriceContainer = document.getElementById('updateSalePriceContainer');
        
        dailyPriceContainer.style.display = 'none';
        salePriceContainer.style.display = 'none';
        
        if (status === 'available' || status === 'rented') {
            dailyPriceContainer.style.display = 'block';
        } else if (status === 'sold') {
            salePriceContainer.style.display = 'block';
        }
    }

    function updatePriceFieldInStatusChange() {
        const status = document.getElementById('newCarStatus').value;
        const dailyPriceContainer = document.getElementById('statusDailyPriceContainer');
        const salePriceContainer = document.getElementById('statusSalePriceContainer');
        
        dailyPriceContainer.style.display = 'none';
        salePriceContainer.style.display = 'none';
        
        if (status === 'available' || status === 'rented') {
            dailyPriceContainer.style.display = 'block';
        } else if (status === 'sold') {
            salePriceContainer.style.display = 'block';
        }
    }
    </script>
</body>
</html>