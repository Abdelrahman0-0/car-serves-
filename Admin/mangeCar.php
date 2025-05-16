<?php
// Database connection
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

// Process forms
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_car':
                addCar($conn);
                break;
            case 'update_car':
                updateCar($conn);
                break;
            case 'delete_car':
                deleteCar($conn);
                break;
            case 'change_status':
                changeStatus($conn);
                break;
        }
    }
}

// Function to add a car
function addCar($conn) {
    $name = $_POST['name'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $color = $_POST['color'];
    $plate = $_POST['plate'];
    $status = $_POST['status'];
    $pricePerDay = ($status == 'available' || $status == 'rented') ? $_POST['dailyPrice'] : null;
    $salePrice = ($status == 'sold') ? $_POST['salePrice'] : null;
    $officeID = $_POST['officeID'] ?? null;

    // Validate required fields
    if (empty($name) || empty($model) || empty($year) || empty($color) || empty($plate) || empty($status)) {
        header("Location: mangeCar.php?error=Please fill all required fields");
        exit();
    }

    // Validate prices based on status
    if (($status == 'available' || $status == 'rented') && empty($pricePerDay)) {
        header("Location: mangeCar.php?error=Please enter daily price");
        exit();
    }
    if ($status == 'sold' && empty($salePrice)) {
        header("Location: mangeCar.php?error=Please enter sale price");
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO Car (CarName, Model, Year, Color, PlateNumber, Status, PricePerDay, SalePrice, OfficeID) 
                               VALUES (:name, :model, :year, :color, :plate, :status, :pricePerDay, :salePrice, :officeID)");
        $stmt->execute([
            ':name' => $name,
            ':model' => $model,
            ':year' => $year,
            ':color' => $color,
            ':plate' => $plate,
            ':status' => $status,
            ':pricePerDay' => $pricePerDay,
            ':salePrice' => $salePrice,
            ':officeID' => $officeID
        ]);

        header("Location: mangeCar.php?success=Car added successfully");
        exit();
    } catch(PDOException $e) {
        header("Location: mangeCar.php?error=Database error: " . $e->getMessage());
        exit();
    }
}

// Function to update a car
function updateCar($conn) {
    $carID = $_POST['carID'];
    $name = $_POST['name'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $color = $_POST['color'];
    $plate = $_POST['plate'];
    $status = $_POST['status'];
    $pricePerDay = ($status == 'available' || $status == 'rented') ? $_POST['dailyPrice'] : null;
    $salePrice = ($status == 'sold') ? $_POST['salePrice'] : null;
    $officeID = $_POST['officeID'] ?? null;

    // Validate required fields
    if (empty($carID) || empty($name) || empty($model) || empty($year) || empty($color) || empty($plate) || empty($status)) {
        header("Location: mangeCar.php?error=Please fill all required fields");
        exit();
    }

    // Validate prices based on status
    if (($status == 'available' || $status == 'rented') && empty($pricePerDay)) {
        header("Location: mangeCar.php?error=Please enter daily price");
        exit();
    }
    if ($status == 'sold' && empty($salePrice)) {
        header("Location: mangeCar.php?error=Please enter sale price");
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE Car SET CarName = :name, Model = :model, Year = :year, Color = :color, 
                               PlateNumber = :plate, Status = :status, PricePerDay = :pricePerDay, 
                               SalePrice = :salePrice, OfficeID = :officeID WHERE CarID = :carID");
        $stmt->execute([
            ':carID' => $carID,
            ':name' => $name,
            ':model' => $model,
            ':year' => $year,
            ':color' => $color,
            ':plate' => $plate,
            ':status' => $status,
            ':pricePerDay' => $pricePerDay,
            ':salePrice' => $salePrice,
            ':officeID' => $officeID
        ]);

        header("Location: mangeCar.php?success=Car updated successfully");
        exit();
    } catch(PDOException $e) {
        header("Location: mangeCar.php?error=Database error: " . $e->getMessage());
        exit();
    }
}

// Function to delete a car
function deleteCar($conn) {
    $carID = $_POST['carID'];
    
    if (empty($carID)) {
        header("Location: mangeCar.php?error=Car ID is required");
        exit();
    }

    try {
        // First delete related records
        $conn->beginTransaction();
        
        // Delete from Maintenance
        $stmt = $conn->prepare("DELETE FROM Maintenance WHERE CarID = :carID");
        $stmt->execute([':carID' => $carID]);
        
        // Delete from Rental
        $stmt = $conn->prepare("DELETE FROM Rental WHERE CarID = :carID");
        $stmt->execute([':carID' => $carID]);
        
        // Delete from Sale
        $stmt = $conn->prepare("DELETE FROM Sale WHERE CarID = :carID");
        $stmt->execute([':carID' => $carID]);
        
        // Delete from CustomerPurchase
        $stmt = $conn->prepare("DELETE FROM CustomerPurchase WHERE CarID = :carID");
        $stmt->execute([':carID' => $carID]);
        
        // Finally delete the car
        $stmt = $conn->prepare("DELETE FROM Car WHERE CarID = :carID");
        $stmt->execute([':carID' => $carID]);
        
        $conn->commit();

        header("Location: mangeCar.php?success=Car deleted successfully");
        exit();
    } catch(PDOException $e) {
        $conn->rollBack();
        header("Location: mangeCar.php?error=Database error: " . $e->getMessage());
        exit();
    }
}

// Function to change car status
function changeStatus($conn) {
    $carID = $_POST['carID'];
    $newStatus = $_POST['newStatus'];
    $pricePerDay = ($newStatus == 'available' || $newStatus == 'rented') ? $_POST['dailyPrice'] : null;
    $salePrice = ($newStatus == 'sold') ? $_POST['salePrice'] : null;

    if (empty($carID) || empty($newStatus)) {
        header("Location: mangeCar.php?error=Car ID and status are required");
        exit();
    }

    // Validate prices based on status
    if (($newStatus == 'available' || $newStatus == 'rented') && empty($pricePerDay)) {
        header("Location: mangeCar.php?error=Please enter daily price");
        exit();
    }
    if ($newStatus == 'sold' && empty($salePrice)) {
        header("Location: mangeCar.php?error=Please enter sale price");
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE Car SET Status = :status, PricePerDay = :pricePerDay, SalePrice = :salePrice WHERE CarID = :carID");
        $stmt->execute([
            ':carID' => $carID,
            ':status' => $newStatus,
            ':pricePerDay' => $pricePerDay,
            ':salePrice' => $salePrice
        ]);

        header("Location: mangeCar.php?success=Car status changed successfully");
        exit();
    } catch(PDOException $e) {
        header("Location: mangeCar.php?error=Database error: " . $e->getMessage());
        exit();
    }
}

// Get cars data from database
$cars = [];
$stmt = $conn->prepare("SELECT * FROM Car");
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get offices data
$offices = [];
$stmt = $conn->prepare("SELECT * FROM Office");
$stmt->execute();
$offices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get status display text
function getStatusDisplay($status) {
    switch ($status) {
        case 'available': return 'Available';
        case 'rented': return 'Reserved';
        case 'sold': return 'Sold';
        case 'maintenance': return 'Out of Service';
        default: return ucfirst($status);
    }
}

// Function to get status CSS class
function getStatusClass($status) {
    switch ($status) {
        case 'available': return 'available';
        case 'rented': return 'reserved';
        case 'sold': return 'sold';
        case 'maintenance': return 'out-of-service';
        default: return '';
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
    <link rel="stylesheet" href="mangcar1.css">
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
            <h1>Car Management</h1>
            <div>
                <div class="dropdown d-inline-block me-2">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="managementDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i> Car Management
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                        <li><a class="dropdown-item" href="#" onclick="showAddCarModal()"><i class="fas fa-plus me-2"></i>Add New Car</a></li>
                        <li><a class="dropdown-item" href="#" onclick="showDeleteCarModal()"><i class="fas fa-trash me-2"></i>Delete Car</a></li>
                        <li><a class="dropdown-item" href="#" onclick="showUpdateCarModal()"><i class="fas fa-edit me-2"></i>Update Car Info</a></li>
                        <li><a class="dropdown-item" href="#" onclick="showChangeStatusModal()"><i class="fas fa-sync-alt me-2"></i>Change Car Status</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Car List</h2>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter"></i> Filter by Status
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                            <li><a class="dropdown-item filter-option" href="#" data-filter="all">All</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item filter-option" href="#" data-filter="available">Available</a></li>
                            <li><a class="dropdown-item filter-option" href="#" data-filter="rented">Reserved</a></li>
                            <li><a class="dropdown-item filter-option" href="#" data-filter="sold">Sold</a></li>
                            <li><a class="dropdown-item filter-option" href="#" data-filter="maintenance">Out of Service</a></li>
                        </ul>
                    </div>
                    <div class="search-box">
                        <input type="text" id="carSearch" class="form-control" placeholder="Search...">
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
                    <?php foreach ($cars as $car): ?>
                    <tr>
                        <td>CAR<?= str_pad($car['CarID'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($car['CarName']) ?></td>
                        <td><?= htmlspecialchars($car['Model']) ?></td>
                        <td><?= htmlspecialchars($car['Year']) ?></td>
                        <td><?= htmlspecialchars($car['Color']) ?></td>
                        <td><?= htmlspecialchars($car['PlateNumber']) ?></td>
                        <td><span class="status-badge <?= getStatusClass($car['Status']) ?>"><?= getStatusDisplay($car['Status']) ?></span></td>
                        <td>
                            <?php if ($car['Status'] == 'available' || $car['Status'] == 'rented'): ?>
                                $<?= number_format($car['PricePerDay'], 2) ?>/day
                            <?php elseif ($car['Status'] == 'sold'): ?>
                                $<?= number_format($car['SalePrice'], 2) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="viewCarDetails('CAR<?= str_pad($car['CarID'], 3, '0', STR_PAD_LEFT) ?>')">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hidden forms for operations -->
    <form id="addCarForm" method="post" style="display:none;">
        <input type="hidden" name="action" value="add_car">
        <div class="mb-3">
            <label for="carName" class="form-label">Car Name</label>
            <input type="text" class="form-control" id="carName" name="name" required>
        </div>
        <div class="mb-3">
            <label for="carModel" class="form-label">Model</label>
            <input type="text" class="form-control" id="carModel" name="model" required>
        </div>
        <div class="mb-3">
            <label for="carYear" class="form-label">Year</label>
            <input type="number" class="form-control" id="carYear" name="year" min="2000" max="<?= date('Y') ?>" required>
        </div>
        <div class="mb-3">
            <label for="carColor" class="form-label">Color</label>
            <input type="text" class="form-control" id="carColor" name="color" required>
        </div>
        <div class="mb-3">
            <label for="carPlate" class="form-label">License Plate</label>
            <input type="text" class="form-control" id="carPlate" name="plate" required>
        </div>
        <div class="mb-3">
            <label for="carStatus" class="form-label">Status</label>
            <select class="form-select" id="carStatus" name="status" onchange="updatePriceField()" required>
                <option value="available" selected>Available</option>
                <option value="rented">Reserved</option>
                <option value="sold">Sold</option>
                <option value="maintenance">Out of Service</option>
            </select>
        </div>
        <div id="dailyPriceContainer" class="mb-3">
            <label for="carDailyPrice" class="form-label">Daily Price ($)</label>
            <input type="number" class="form-control" id="carDailyPrice" name="dailyPrice" min="1" step="0.01">
        </div>
        <div id="salePriceContainer" class="mb-3" style="display:none;">
            <label for="carSalePrice" class="form-label">Sale Price ($)</label>
            <input type="number" class="form-control" id="carSalePrice" name="salePrice" min="1" step="0.01">
        </div>
        <div class="mb-3">
            <label for="carOffice" class="form-label">Office</label>
            <select class="form-select" id="carOffice" name="officeID">
                <option value="">Select Office</option>
                <?php foreach ($offices as $office): ?>
                    <option value="<?= $office['OfficeID'] ?>"><?= htmlspecialchars($office['Location']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form id="updateCarForm" method="post" style="display:none;">
        <input type="hidden" name="action" value="update_car">
        <input type="hidden" id="updateCarId" name="carID">
        <div class="mb-3">
            <label for="updateCarName" class="form-label">Car Name</label>
            <input type="text" class="form-control" id="updateCarName" name="name" required>
        </div>
        <div class="mb-3">
            <label for="updateCarModel" class="form-label">Model</label>
            <input type="text" class="form-control" id="updateCarModel" name="model" required>
        </div>
        <div class="mb-3">
            <label for="updateCarYear" class="form-label">Year</label>
            <input type="number" class="form-control" id="updateCarYear" name="year" min="2000" max="<?= date('Y') ?>" required>
        </div>
        <div class="mb-3">
            <label for="updateCarColor" class="form-label">Color</label>
            <input type="text" class="form-control" id="updateCarColor" name="color" required>
        </div>
        <div class="mb-3">
            <label for="updateCarPlate" class="form-label">License Plate</label>
            <input type="text" class="form-control" id="updateCarPlate" name="plate" required>
        </div>
        <div class="mb-3">
            <label for="updateCarStatus" class="form-label">Status</label>
            <select class="form-select" id="updateCarStatus" name="status" onchange="updatePriceFieldInUpdate()" required>
                <option value="available">Available</option>
                <option value="rented">Reserved</option>
                <option value="sold">Sold</option>
                <option value="maintenance">Out of Service</option>
            </select>
        </div>
        <div id="updateDailyPriceContainer" class="mb-3">
            <label for="updateCarDailyPrice" class="form-label">Daily Price ($)</label>
            <input type="number" class="form-control" id="updateCarDailyPrice" name="dailyPrice" min="1" step="0.01">
        </div>
        <div id="updateSalePriceContainer" class="mb-3" style="display:none;">
            <label for="updateCarSalePrice" class="form-label">Sale Price ($)</label>
            <input type="number" class="form-control" id="updateCarSalePrice" name="salePrice" min="1" step="0.01">
        </div>
        <div class="mb-3">
            <label for="updateCarOffice" class="form-label">Office</label>
            <select class="form-select" id="updateCarOffice" name="officeID">
                <option value="">Select Office</option>
                <?php foreach ($offices as $office): ?>
                    <option value="<?= $office['OfficeID'] ?>"><?= htmlspecialchars($office['Location']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <form id="deleteCarForm" method="post" style="display:none;">
        <input type="hidden" name="action" value="delete_car">
        <input type="hidden" id="deleteCarId" name="carID">
    </form>

    <form id="changeStatusForm" method="post" style="display:none;">
        <input type="hidden" name="action" value="change_status">
        <input type="hidden" id="statusCarId" name="carID">
        <input type="hidden" id="newCarStatus" name="newStatus">
        <input type="hidden" id="statusCarDailyPrice" name="dailyPrice">
        <input type="hidden" id="statusCarSalePrice" name="salePrice">
    </form>

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
    
    $('#carSearch').on('keyup', function() {
        $('#carsTable').DataTable().search($(this).val()).draw();
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

function showAddCarModal() {
    // إنشاء محتوى النموذج مباشرة
    const formContent = `
        <div class="mb-3">
            <label for="carName" class="form-label">Car Name</label>
            <input type="text" class="form-control" id="carName" name="name" required>
        </div>
        <div class="mb-3">
            <label for="carModel" class="form-label">Model</label>
            <input type="text" class="form-control" id="carModel" name="model" required>
        </div>
        <div class="mb-3">
            <label for="carYear" class="form-label">Year</label>
            <input type="number" class="form-control" id="carYear" name="year" min="2000" max="<?= date('Y') ?>" required>
        </div>
        <div class="mb-3">
            <label for="carColor" class="form-label">Color</label>
            <input type="text" class="form-control" id="carColor" name="color" required>
        </div>
        <div class="mb-3">
            <label for="carPlate" class="form-label">License Plate</label>
            <input type="text" class="form-control" id="carPlate" name="plate" required>
        </div>
        <div class="mb-3">
            <label for="carStatus" class="form-label">Status</label>
            <select class="form-select" id="carStatus" name="status" onchange="updatePriceFieldInModal()" required>
                <option value="available" selected>Available</option>
                <option value="rented">Reserved</option>
                <option value="sold">Sold</option>
                <option value="maintenance">Out of Service</option>
            </select>
        </div>
        <div id="dailyPriceContainer" class="mb-3">
            <label for="carDailyPrice" class="form-label">Daily Price ($)</label>
            <input type="number" class="form-control" id="carDailyPrice" name="dailyPrice" min="1" step="0.01">
        </div>
        <div id="salePriceContainer" class="mb-3" style="display:none;">
            <label for="carSalePrice" class="form-label">Sale Price ($)</label>
            <input type="number" class="form-control" id="carSalePrice" name="salePrice" min="1" step="0.01">
        </div>
        <div class="mb-3">
            <label for="carOffice" class="form-label">Office</label>
            <select class="form-select" id="carOffice" name="officeID">
                <option value="">Select Office</option>
                <?php foreach ($offices as $office): ?>
                    <option value="<?= $office['OfficeID'] ?>"><?= htmlspecialchars($office['Location']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    `;

    Swal.fire({
        title: 'Add New Car',
        html: formContent,
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        preConfirm: () => {
            // الحصول على القيم مباشرة من عناصر النموذج في SweetAlert
            const name = Swal.getPopup().querySelector('#carName').value;
            const model = Swal.getPopup().querySelector('#carModel').value;
            const year = Swal.getPopup().querySelector('#carYear').value;
            const color = Swal.getPopup().querySelector('#carColor').value;
            const plate = Swal.getPopup().querySelector('#carPlate').value;
            const status = Swal.getPopup().querySelector('#carStatus').value;
            const dailyPrice = Swal.getPopup().querySelector('#carDailyPrice').value;
            const salePrice = Swal.getPopup().querySelector('#carSalePrice').value;
            
            if (!name || !model || !year || !color || !plate || !status) {
                Swal.showValidationMessage('Please fill all required fields');
                return false;
            }
            if ((status === 'available' || status === 'rented') && !dailyPrice) {
                Swal.showValidationMessage('Please enter daily price');
                return false;
            }
            if (status === 'sold' && !salePrice) {
                Swal.showValidationMessage('Please enter sale price');
                return false;
            }
            
            // إذا كانت جميع الحقول صالحة، إرجاع القيم
            return {
                name: name,
                model: model,
                year: year,
                color: color,
                plate: plate,
                status: status,
                dailyPrice: dailyPrice,
                salePrice: salePrice,
                officeID: Swal.getPopup().querySelector('#carOffice').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // إنشاء نموذج مخفي وإرساله
            const form = document.createElement('form');
            form.method = 'post';
            form.style.display = 'none';
            
            // إضافة حقول الإدخال
            const addInput = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            };
            
            addInput('action', 'add_car');
            addInput('name', result.value.name);
            addInput('model', result.value.model);
            addInput('year', result.value.year);
            addInput('color', result.value.color);
            addInput('plate', result.value.plate);
            addInput('status', result.value.status);
            addInput('dailyPrice', result.value.dailyPrice || '');
            addInput('salePrice', result.value.salePrice || '');
            addInput('officeID', result.value.officeID || '');
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function updatePriceFieldInModal() {
    const popup = Swal.getPopup();
    if (!popup) return;
    
    const status = popup.querySelector('#carStatus').value;
    const dailyPriceContainer = popup.querySelector('#dailyPriceContainer');
    const salePriceContainer = popup.querySelector('#salePriceContainer');

    dailyPriceContainer.style.display = 'none';
    salePriceContainer.style.display = 'none';

    if (status === 'available' || status === 'rented') {
        dailyPriceContainer.style.display = 'block';
    } else if (status === 'sold') {
        salePriceContainer.style.display = 'block';
    }
}

function showDeleteCarModal() {
    Swal.fire({
        title: 'Delete Car',
        html: `
            <div class="mb-3">
                <label for="deleteCarIdInput" class="form-label">Car ID</label>
                <input type="text" class="form-control" id="deleteCarIdInput" placeholder="Enter car ID (e.g., CAR001)" required>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-secondary" onclick="searchCarToDelete()">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            <div id="carToDeleteInfo" style="display:none;">
                <hr>
                <h5>Car Information</h5>
                <p><strong>Name:</strong> <span id="deleteCarName"></span></p>
                <p><strong>Model:</strong> <span id="deleteCarModel"></span></p>
                <p><strong>Color:</strong> <span id="deleteCarColor"></span></p>
                <p><strong>Status:</strong> <span id="deleteCarStatus"></span></p>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Confirm Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        preConfirm: () => {
            const carId = document.getElementById('deleteCarIdInput').value;
            if (!carId) {
                Swal.showValidationMessage('Please enter car ID');
                return false;
            }
            return carId;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const carId = result.value.replace('CAR', '');
            document.getElementById('deleteCarId').value = carId;
            document.getElementById('deleteCarForm').submit();
        }
    });
}

function searchCarToDelete() {
    const carIdInput = document.getElementById('deleteCarIdInput').value;
    if (!carIdInput) {
        Swal.showValidationMessage('Please enter car ID');
        return;
    }
    
    const table = $('#carsTable').DataTable();
    const row = table.rows().nodes().to$().find(`td:first-child:contains('${carIdInput}')`).closest('tr');
    
    if (row.length) {
        document.getElementById('deleteCarName').textContent = row.find('td:nth-child(2)').text();
        document.getElementById('deleteCarModel').textContent = row.find('td:nth-child(3)').text();
        document.getElementById('deleteCarColor').textContent = row.find('td:nth-child(5)').text();
        document.getElementById('deleteCarStatus').textContent = row.find('td:nth-child(7) span').text();
        document.getElementById('carToDeleteInfo').style.display = 'block';
    } else {
        Swal.showValidationMessage('Car not found');
    }
}

function showUpdateCarModal() {
    Swal.fire({
        title: 'Update Car Information',
        html: `
            <div class="mb-3">
                <label for="updateCarIdInput" class="form-label">Car ID</label>
                <input type="text" class="form-control" id="updateCarIdInput" placeholder="Enter car ID (e.g., CAR001)" required>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-secondary" onclick="searchCarToUpdate()">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            <div id="carToUpdateInfo" style="display:none;">
                <hr>
                ${document.getElementById('updateCarForm').innerHTML.replace('id="updateCarId"', 'id="updateCarIdHidden"')}
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const carId = document.getElementById('updateCarIdHidden').value;
            const name = document.getElementById('updateCarName').value;
            const model = document.getElementById('updateCarModel').value;
            const year = document.getElementById('updateCarYear').value;
            const color = document.getElementById('updateCarColor').value;
            const plate = document.getElementById('updateCarPlate').value;
            const status = document.getElementById('updateCarStatus').value;
            const dailyPrice = document.getElementById('updateCarDailyPrice').value;
            const salePrice = document.getElementById('updateCarSalePrice').value;
            
            if (!carId || !name || !model || !year || !color || !plate || !status) {
                Swal.showValidationMessage('Please fill all required fields');
                return false;
            }
            if ((status === 'available' || status === 'rented') && !dailyPrice) {
                Swal.showValidationMessage('Please enter daily price');
                return false;
            }
            if (status === 'sold' && !salePrice) {
                Swal.showValidationMessage('Please enter sale price');
                return false;
            }
            
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('updateCarForm').submit();
        }
    });
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

function searchCarToUpdate() {
    const carIdInput = document.getElementById('updateCarIdInput').value;
    if (!carIdInput) {
        Swal.showValidationMessage('Please enter car ID');
        return;
    }
    
    const table = $('#carsTable').DataTable();
    const row = table.rows().nodes().to$().find(`td:first-child:contains('${carIdInput}')`).closest('tr');
    
    if (row.length) {
        const carId = carIdInput.replace('CAR', '');
        document.getElementById('updateCarIdHidden').value = carId;
        document.getElementById('updateCarName').value = row.find('td:nth-child(2)').text();
        document.getElementById('updateCarModel').value = row.find('td:nth-child(3)').text();
        document.getElementById('updateCarYear').value = row.find('td:nth-child(4)').text();
        document.getElementById('updateCarColor').value = row.find('td:nth-child(5)').text();
        document.getElementById('updateCarPlate').value = row.find('td:nth-child(6)').text();
        
        const statusText = row.find('td:nth-child(7) span').text().toLowerCase();
        let statusValue;
        switch(statusText) {
            case 'available': statusValue = 'available'; break;
            case 'reserved': statusValue = 'rented'; break;
            case 'sold': statusValue = 'sold'; break;
            case 'out of service': statusValue = 'maintenance'; break;
            default: statusValue = 'available';
        }
        document.getElementById('updateCarStatus').value = statusValue;
        
        const priceText = row.find('td:nth-child(8)').text();
        if (statusValue === 'available' || statusValue === 'rented') {
            const dailyPrice = priceText.replace('$', '').replace('/day', '').trim();
            document.getElementById('updateCarDailyPrice').value = dailyPrice;
        } else if (statusValue === 'sold') {
            document.getElementById('updateCarSalePrice').value = priceText.replace('$', '').trim();
        }
        
        updatePriceFieldInUpdate();
        document.getElementById('carToUpdateInfo').style.display = 'block';
    } else {
        Swal.showValidationMessage('Car not found');
    }
}

function showChangeStatusModal() {
    Swal.fire({
        title: 'Change Car Status',
        html: `
            <div class="mb-3">
                <label for="statusCarIdInput" class="form-label">Car ID</label>
                <input type="text" class="form-control" id="statusCarIdInput" placeholder="Enter car ID (e.g., CAR001)" required>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-secondary" onclick="searchCarToChangeStatus()">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
            <div id="carToChangeStatusInfo" style="display:none;">
                <hr>
                <h5>Car Information</h5>
                <p><strong>Name:</strong> <span id="statusCarName"></span></p>
                <p><strong>Model:</strong> <span id="statusCarModel"></span></p>
                <p><strong>Current Status:</strong> <span id="currentCarStatus"></span></p>
                <div class="mb-3 mt-3">
                    <label for="newCarStatusSelect" class="form-label">New Status</label>
                    <select class="form-select" id="newCarStatusSelect" onchange="updatePriceFieldInStatusChange()" required>
                        <option value="available">Available</option>
                        <option value="rented">Reserved</option>
                        <option value="sold">Sold</option>
                        <option value="maintenance">Out of Service</option>
                    </select>
                </div>
                <div id="statusDailyPriceContainer" class="mb-3">
                    <label for="statusCarDailyPriceInput" class="form-label">Daily Price ($)</label>
                    <input type="number" class="form-control" id="statusCarDailyPriceInput" min="1" step="0.01">
                </div>
                <div id="statusSalePriceContainer" class="mb-3" style="display:none;">
                    <label for="statusCarSalePriceInput" class="form-label">Sale Price ($)</label>
                    <input type="number" class="form-control" id="statusCarSalePriceInput" min="1" step="0.01">
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Change Status',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const carId = document.getElementById('statusCarIdInput').value;
            const newStatus = document.getElementById('newCarStatusSelect').value;
            const dailyPrice = document.getElementById('statusCarDailyPriceInput').value;
            const salePrice = document.getElementById('statusCarSalePriceInput').value;
            
            if (!carId || !newStatus) {
                Swal.showValidationMessage('Please fill all required fields');
                return false;
            }
            if ((newStatus === 'available' || newStatus === 'rented') && !dailyPrice) {
                Swal.showValidationMessage('Please enter daily price');
                return false;
            }
            if (newStatus === 'sold' && !salePrice) {
                Swal.showValidationMessage('Please enter sale price');
                return false;
            }
            
            return {
                carId: carId.replace('CAR', ''),
                newStatus,
                dailyPrice,
                salePrice
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            document.getElementById('statusCarId').value = data.carId;
            document.getElementById('newCarStatus').value = data.newStatus;
            document.getElementById('statusCarDailyPrice').value = data.dailyPrice;
            document.getElementById('statusCarSalePrice').value = data.salePrice;
            document.getElementById('changeStatusForm').submit();
        }
    });
}

function updatePriceFieldInStatusChange() {
    const status = document.getElementById('newCarStatusSelect').value;
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

function searchCarToChangeStatus() {
    const carIdInput = document.getElementById('statusCarIdInput').value;
    if (!carIdInput) {
        Swal.showValidationMessage('Please enter car ID');
        return;
    }
    
    const table = $('#carsTable').DataTable();
    const row = table.rows().nodes().to$().find(`td:first-child:contains('${carIdInput}')`).closest('tr');
    
    if (row.length) {
        document.getElementById('statusCarName').textContent = row.find('td:nth-child(2)').text();
        document.getElementById('statusCarModel').textContent = row.find('td:nth-child(3)').text();
        document.getElementById('currentCarStatus').textContent = row.find('td:nth-child(7) span').text();
        
        const statusText = row.find('td:nth-child(7) span').text().toLowerCase();
        let statusValue;
        switch(statusText) {
            case 'available': statusValue = 'available'; break;
            case 'reserved': statusValue = 'rented'; break;
            case 'sold': statusValue = 'sold'; break;
            case 'out of service': statusValue = 'maintenance'; break;
            default: statusValue = 'available';
        }
        document.getElementById('newCarStatusSelect').value = statusValue;
        
        const priceText = row.find('td:nth-child(8)').text();
        if (statusValue === 'available' || statusValue === 'rented') {
            const dailyPrice = priceText.replace('$', '').replace('/day', '').trim();
            document.getElementById('statusCarDailyPriceInput').value = dailyPrice;
        } else if (statusValue === 'sold') {
            document.getElementById('statusCarSalePriceInput').value = priceText.replace('$', '').trim();
        }
        
        updatePriceFieldInStatusChange();
        document.getElementById('carToChangeStatusInfo').style.display = 'block';
    } else {
        Swal.showValidationMessage('Car not found');
    }
}

function viewCarDetails(carId) {
    const table = $('#carsTable').DataTable();
    const row = table.rows().nodes().to$().find(`td:first-child:contains('${carId}')`).closest('tr');
    
    if (row.length) {
        const carInfo = {
            id: carId,
            name: row.find('td:nth-child(2)').text(),
            model: row.find('td:nth-child(3)').text(),
            year: row.find('td:nth-child(4)').text(),
            color: row.find('td:nth-child(5)').text(),
            plate: row.find('td:nth-child(6)').text(),
            status: row.find('td:nth-child(7) span').text(),
            price: row.find('td:nth-child(8)').text()
        };

        Swal.fire({
            title: 'Car Details',
            html: `
                <div class="row">
                    <div class="col-md-6">
                        <img src="https://via.placeholder.com/300" class="img-fluid mb-3" alt="Car Image">
                    </div>
                    <div class="col-md-6">
                        <h4>${carInfo.name} ${carInfo.year}</h4>
                        <p><strong>Car ID:</strong> ${carInfo.id}</p>
                        <p><strong>Name:</strong> ${carInfo.name}</p>
                        <p><strong>Model:</strong> ${carInfo.model}</p>
                        <p><strong>Year:</strong> ${carInfo.year}</p>
                        <p><strong>Color:</strong> ${carInfo.color}</p>
                        <p><strong>License Plate:</strong> ${carInfo.plate}</p>
                        <p><strong>Status:</strong> <span class="status-badge ${getStatusClass(carInfo.status)}">${carInfo.status}</span></p>
                        <p><strong>Price:</strong> ${carInfo.price}</p>
                    </div>
                </div>`,
            width: '800px',
            showConfirmButton: false,
            showCloseButton: true
        });
    }
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'available': return 'available';
        case 'reserved': return 'reserved';
        case 'sold': return 'sold';
        case 'out of service': return 'out-of-service';
        case 'purchased': return 'purchased';
        default: return '';
    }
}

// Filter table by car status
$(document).on('click', '.filter-option', function(e) {
    e.preventDefault();
    const filterValue = $(this).data('filter');

    if (filterValue === 'all') {
        $('#carsTable').DataTable().column(6).search('').draw();
    } else {
        $('#carsTable').DataTable().column(6).search(filterValue).draw();
    }

    // Update button text to reflect current filter
    $('#filterDropdown').html(`<i class="fas fa-filter"></i> ${filterValue === 'all' ? 'Filter by Status' : 'Status: ' + filterValue}`);
});
</script>
</body>
</html>