<?php
// Database Configuration
$db_host = 'localhost';
$db_name = 'car_services';
$db_user = 'root';
$db_pass = '';

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add_car':
                $stmt = $pdo->prepare("INSERT INTO Car (CarName, Model, Year, Color, PlateNumber, Status, PricePerDay, SalePrice) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $success = $stmt->execute([
                    $_POST['name'],
                    $_POST['model'],
                    $_POST['year'],
                    $_POST['color'],
                    $_POST['plate'],
                    $_POST['status'],
                    $_POST['status'] === 'available' ? $_POST['price'] : null,
                    $_POST['status'] === 'sold' ? $_POST['price'] : null
                ]);
                echo json_encode(['success' => $success, 'carId' => $pdo->lastInsertId()]);
                exit;
                
            case 'update_car':
                $stmt = $pdo->prepare("UPDATE Car SET 
                                      CarName = ?, Model = ?, Year = ?, Color = ?, 
                                      PlateNumber = ?, Status = ?, 
                                      PricePerDay = ?, SalePrice = ? 
                                      WHERE CarID = ?");
                $success = $stmt->execute([
                    $_POST['name'],
                    $_POST['model'],
                    $_POST['year'],
                    $_POST['color'],
                    $_POST['plate'],
                    $_POST['status'],
                    $_POST['status'] === 'available' ? $_POST['price'] : null,
                    $_POST['status'] === 'sold' ? $_POST['price'] : null,
                    $_POST['carId']
                ]);
                echo json_encode(['success' => $success]);
                exit;
                
            case 'delete_car':
                $stmt = $pdo->prepare("DELETE FROM Car WHERE CarID = ?");
                $success = $stmt->execute([$_POST['carId']]);
                echo json_encode(['success' => $success]);
                exit;
                
            case 'change_status':
                $stmt = $pdo->prepare("UPDATE Car SET Status = ?, 
                                      PricePerDay = ?, SalePrice = ? 
                                      WHERE CarID = ?");
                $success = $stmt->execute([
                    $_POST['status'],
                    $_POST['status'] === 'available' ? $_POST['price'] : null,
                    $_POST['status'] === 'sold' ? $_POST['price'] : null,
                    $_POST['carId']
                ]);
                echo json_encode(['success' => $success]);
                exit;
                
            case 'get_car':
                $stmt = $pdo->prepare("SELECT * FROM Car WHERE CarID = ?");
                $stmt->execute([$_POST['carId']]);
                $car = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($car);
                exit;
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Get all cars for the table
$cars = $pdo->query("SELECT * FROM Car")->fetchAll(PDO::FETCH_ASSOC);
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
 
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header text-center mb-4">
            <h4><i class="fas fa-car"></i> Car Services</h4>
        </div>
        <a href="#"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="#"><i class="fas fa-users"></i> Users</a>
        <a href="#"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="#"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="#"><i class="fas fa-user-tie"></i> Clients</a>
        <a href="#"><i class="fas fa-clipboard-list"></i> Reports</a>
        <a href="#"><i class="fas fa-chart-bar me-2"></i> Statistics</a>
        <a href="#"><i class="fas fa-cogs"></i> Manage</a>
        <a href="#" class="active"><i class="fas fa-car"></i> Cars</a>
        <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                            <li><a class="dropdown-item filter-option" href="#" data-filter="rented">Rented</a></li>
                            <li><a class="dropdown-item filter-option" href="#" data-filter="sold">Sold</a></li>
                            <li><a class="dropdown-item filter-option" href="#" data-filter="maintenance">Maintenance</a></li>
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
                        <td><?= htmlspecialchars($car['CarID']) ?></td>
                        <td><?= htmlspecialchars($car['CarName']) ?></td>
                        <td><?= htmlspecialchars($car['Model']) ?></td>
                        <td><?= htmlspecialchars($car['Year']) ?></td>
                        <td><?= htmlspecialchars($car['Color']) ?></td>
                        <td><?= htmlspecialchars($car['PlateNumber']) ?></td>
                        <td>
                            <span class="status-badge <?= strtolower($car['Status']) ?>">
                                <?= ucfirst($car['Status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($car['Status'] === 'sold'): ?>
                                $<?= number_format($car['SalePrice'], 2) ?>
                            <?php elseif ($car['Status'] === 'available' || $car['Status'] === 'rented'): ?>
                                $<?= number_format($car['PricePerDay'], 2) ?>/day
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="viewCarDetails(<?= $car['CarID'] ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

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
            
            $(document).on('click', '.filter-option', function(e) {
                e.preventDefault();
                const filterValue = $(this).data('filter');
                if (filterValue === 'all') {
                    $('#carsTable').DataTable().column(6).search('').draw();
                } else {
                    $('#carsTable').DataTable().column(6).search(filterValue).draw();
                }
                $('#filterDropdown').html(`<i class="fas fa-filter"></i> ${filterValue === 'all' ? 'Filter by Status' : 'Status: ' + filterValue}`);
            });
        });
        
        function getStatusClass(status) {
            switch(status.toLowerCase()) {
                case 'available': return 'available';
                case 'rented': return 'rented';
                case 'sold': return 'sold';
                case 'maintenance': return 'maintenance';
                default: return '';
            }
        }
        
        function updatePriceField(selectId, dailyContainerId, saleContainerId) {
            const status = document.getElementById(selectId).value;
            const dailyPriceContainer = document.getElementById(dailyContainerId);
            const salePriceContainer = document.getElementById(saleContainerId);
            
            dailyPriceContainer.style.display = 'none';
            salePriceContainer.style.display = 'none';
            
            if (status === 'available' || status === 'rented') {
                dailyPriceContainer.style.display = 'block';
            } else if (status === 'sold') {
                salePriceContainer.style.display = 'block';
            }
        }
        
        function showAddCarModal() {
            Swal.fire({
                title: 'Add New Car',
                html: `
                    <form id="addCarForm">
                        <div class="mb-3">
                            <label for="carName" class="form-label">Car Name</label>
                            <input type="text" class="form-control" id="carName" required>
                        </div>
                        <div class="mb-3">
                            <label for="carModel" class="form-label">Model</label>
                            <input type="text" class="form-control" id="carModel" required>
                        </div>
                        <div class="mb-3">
                            <label for="carYear" class="form-label">Year</label>
                            <input type="number" class="form-control" id="carYear" min="2000" max="2023" required>
                        </div>
                        <div class="mb-3">
                            <label for="carColor" class="form-label">Color</label>
                            <input type="text" class="form-control" id="carColor" required>
                        </div>
                        <div class="mb-3">
                            <label for="carPlate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="carPlate" required>
                        </div>
                        <div class="mb-3">
                            <label for="carStatus" class="form-label">Status</label>
                            <select class="form-select" id="carStatus" onchange="updatePriceField('carStatus', 'dailyPriceContainer', 'salePriceContainer')" required>
                                <option value="available" selected>Available</option>
                                <option value="rented">Rented</option>
                                <option value="sold">Sold</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div id="dailyPriceContainer" class="mb-3">
                            <label for="carDailyPrice" class="form-label">Daily Price ($)</label>
                            <input type="number" class="form-control" id="carDailyPrice" min="1">
                        </div>
                        <div id="salePriceContainer" class="mb-3" style="display:none;">
                            <label for="carSalePrice" class="form-label">Sale Price ($)</label>
                            <input type="number" class="form-control" id="carSalePrice" min="1">
                        </div>
                    </form>`,
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const name = document.getElementById('carName').value;
                    const model = document.getElementById('carModel').value;
                    const year = document.getElementById('carYear').value;
                    const color = document.getElementById('carColor').value;
                    const plate = document.getElementById('carPlate').value;
                    const status = document.getElementById('carStatus').value;
                    const dailyPrice = document.getElementById('carDailyPrice').value;
                    const salePrice = document.getElementById('carSalePrice').value;
                    
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
                    
                    return { 
                        name, model, year, color, plate, status, 
                        price: status === 'sold' ? salePrice : dailyPrice 
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', {
                        action: 'add_car',
                        ...result.value
                    }, function(response) {
                        if (response.success) {
                            Swal.fire('Success!', 'Car added successfully', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', 'Failed to add car', 'error');
                        }
                    }, 'json');
                }
            });
        }
        
        function showDeleteCarModal() {
            Swal.fire({
                title: 'Delete Car',
                html: `
                    <form id="deleteCarForm">
                        <div class="mb-3">
                            <label for="deleteCarId" class="form-label">Car ID</label>
                            <input type="text" class="form-control" id="deleteCarId" placeholder="Enter car ID" required>
                        </div>
                    </form>`,
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                preConfirm: () => {
                    const carId = document.getElementById('deleteCarId').value;
                    if (!carId) {
                        Swal.showValidationMessage('Please enter car ID');
                        return false;
                    }
                    return carId;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', {
                        action: 'delete_car',
                        carId: result.value
                    }, function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', 'Car deleted successfully', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', 'Failed to delete car', 'error');
                        }
                    }, 'json');
                }
            });
        }
        
        function showUpdateCarModal() {
            Swal.fire({
                title: 'Update Car',
                html: `
                    <form id="updateCarForm">
                        <div class="mb-3">
                            <label for="updateCarId" class="form-label">Car ID</label>
                            <input type="text" class="form-control" id="updateCarId" placeholder="Enter car ID" required>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-secondary" onclick="loadCarForUpdate()">
                                <i class="fas fa-search"></i> Load Car
                            </button>
                        </div>
                        <div id="updateCarFields" style="display:none;">
                            <div class="mb-3">
                                <label for="updateCarName" class="form-label">Car Name</label>
                                <input type="text" class="form-control" id="updateCarName" required>
                            </div>
                            <div class="mb-3">
                                <label for="updateCarModel" class="form-label">Model</label>
                                <input type="text" class="form-control" id="updateCarModel" required>
                            </div>
                            <div class="mb-3">
                                <label for="updateCarYear" class="form-label">Year</label>
                                <input type="number" class="form-control" id="updateCarYear" min="2000" max="2023" required>
                            </div>
                            <div class="mb-3">
                                <label for="updateCarColor" class="form-label">Color</label>
                                <input type="text" class="form-control" id="updateCarColor" required>
                            </div>
                            <div class="mb-3">
                                <label for="updateCarPlate" class="form-label">License Plate</label>
                                <input type="text" class="form-control" id="updateCarPlate" required>
                            </div>
                            <div class="mb-3">
                                <label for="updateCarStatus" class="form-label">Status</label>
                                <select class="form-select" id="updateCarStatus" onchange="updatePriceField('updateCarStatus', 'updateDailyPriceContainer', 'updateSalePriceContainer')" required>
                                    <option value="available">Available</option>
                                    <option value="rented">Rented</option>
                                    <option value="sold">Sold</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div id="updateDailyPriceContainer" class="mb-3">
                                <label for="updateCarDailyPrice" class="form-label">Daily Price ($)</label>
                                <input type="number" class="form-control" id="updateCarDailyPrice" min="1">
                            </div>
                            <div id="updateSalePriceContainer" class="mb-3" style="display:none;">
                                <label for="updateCarSalePrice" class="form-label">Sale Price ($)</label>
                                <input type="number" class="form-control" id="updateCarSalePrice" min="1">
                            </div>
                        </div>
                    </form>`,
                showCancelButton: true,
                confirmButtonText: 'Update',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const carId = document.getElementById('updateCarId').value;
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
                    
                    return { 
                        carId, name, model, year, color, plate, status, 
                        price: status === 'sold' ? salePrice : dailyPrice 
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', {
                        action: 'update_car',
                        ...result.value
                    }, function(response) {
                        if (response.success) {
                            Swal.fire('Updated!', 'Car updated successfully', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', 'Failed to update car', 'error');
                        }
                    }, 'json');
                }
            });
        }
        
        function loadCarForUpdate() {
            const carId = document.getElementById('updateCarId').value;
            if (!carId) {
                Swal.showValidationMessage('Please enter car ID');
                return;
            }
            
            $.post('', {
                action: 'get_car',
                carId: carId
            }, function(response) {
                if (response) {
                    document.getElementById('updateCarName').value = response.CarName;
                    document.getElementById('updateCarModel').value = response.Model;
                    document.getElementById('updateCarYear').value = response.Year;
                    document.getElementById('updateCarColor').value = response.Color;
                    document.getElementById('updateCarPlate').value = response.PlateNumber;
                    document.getElementById('updateCarStatus').value = response.Status;
                    
                    if (response.Status === 'available' || response.Status === 'rented') {
                        document.getElementById('updateCarDailyPrice').value = response.PricePerDay;
                    } else if (response.Status === 'sold') {
                        document.getElementById('updateCarSalePrice').value = response.SalePrice;
                    }
                    
                    updatePriceField('updateCarStatus', 'updateDailyPriceContainer', 'updateSalePriceContainer');
                    document.getElementById('updateCarFields').style.display = 'block';
                } else {
                    Swal.showValidationMessage('Car not found');
                }
            }, 'json');
        }
        
        function showChangeStatusModal() {
            Swal.fire({
                title: 'Change Car Status',
                html: `
                    <form id="changeStatusForm">
                        <div class="mb-3">
                            <label for="statusCarId" class="form-label">Car ID</label>
                            <input type="text" class="form-control" id="statusCarId" placeholder="Enter car ID" required>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-secondary" onclick="loadCarForStatusChange()">
                                <i class="fas fa-search"></i> Load Car
                            </button>
                        </div>
                        <div id="statusChangeFields" style="display:none;">
                            <div class="mb-3">
                                <label for="statusCarName" class="form-label">Car Name</label>
                                <input type="text" class="form-control" id="statusCarName" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="currentCarStatus" class="form-label">Current Status</label>
                                <input type="text" class="form-control" id="currentCarStatus" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="newCarStatus" class="form-label">New Status</label>
                                <select class="form-select" id="newCarStatus" onchange="updatePriceField('newCarStatus', 'statusDailyPriceContainer', 'statusSalePriceContainer')" required>
                                    <option value="available">Available</option>
                                    <option value="rented">Rented</option>
                                    <option value="sold">Sold</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div id="statusDailyPriceContainer" class="mb-3">
                                <label for="statusCarDailyPrice" class="form-label">Daily Price ($)</label>
                                <input type="number" class="form-control" id="statusCarDailyPrice" min="1">
                            </div>
                            <div id="statusSalePriceContainer" class="mb-3" style="display:none;">
                                <label for="statusCarSalePrice" class="form-label">Sale Price ($)</label>
                                <input type="number" class="form-control" id="statusCarSalePrice" min="1">
                            </div>
                        </div>
                    </form>`,
                showCancelButton: true,
                confirmButtonText: 'Change Status',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const carId = document.getElementById('statusCarId').value;
                    const newStatus = document.getElementById('newCarStatus').value;
                    const dailyPrice = document.getElementById('statusCarDailyPrice').value;
                    const salePrice = document.getElementById('statusCarSalePrice').value;
                    
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
                        carId, 
                        status: newStatus, 
                        price: newStatus === 'sold' ? salePrice : dailyPrice 
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', {
                        action: 'change_status',
                        ...result.value
                    }, function(response) {
                        if (response.success) {
                            Swal.fire('Changed!', 'Car status changed successfully', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', 'Failed to change car status', 'error');
                        }
                    }, 'json');
                }
            });
        }
        
        function loadCarForStatusChange() {
            const carId = document.getElementById('statusCarId').value;
            if (!carId) {
                Swal.showValidationMessage('Please enter car ID');
                return;
            }
            
            $.post('', {
                action: 'get_car',
                carId: carId
            }, function(response) {
                if (response) {
                    document.getElementById('statusCarName').value = response.CarName;
                    document.getElementById('currentCarStatus').value = response.Status;
                    document.getElementById('newCarStatus').value = response.Status;
                    
                    if (response.Status === 'available' || response.Status === 'rented') {
                        document.getElementById('statusCarDailyPrice').value = response.PricePerDay;
                    } else if (response.Status === 'sold') {
                        document.getElementById('statusCarSalePrice').value = response.SalePrice;
                    }
                    
                    updatePriceField('newCarStatus', 'statusDailyPriceContainer', 'statusSalePriceContainer');
                    document.getElementById('statusChangeFields').style.display = 'block';
                } else {
                    Swal.showValidationMessage('Car not found');
                }
            }, 'json');
        }
        
        function viewCarDetails(carId) {
            $.post('', {
                action: 'get_car',
                carId: carId
            }, function(response) {
                if (response) {
                    const priceDisplay = response.Status === 'sold' ? 
                        `$${response.SalePrice}` : 
                        (response.Status === 'available' || response.Status === 'rented') ? 
                        `$${response.PricePerDay}/day` : '-';
                    
                    Swal.fire({
                        title: 'Car Details',
                        html: `
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="https://via.placeholder.com/300" class="img-fluid mb-3" alt="Car Image">
                                </div>
                                <div class="col-md-6">
                                    <h4>${response.CarName} ${response.Year}</h4>
                                    <p><strong>Car ID:</strong> ${response.CarID}</p>
                                    <p><strong>Name:</strong> ${response.CarName}</p>
                                    <p><strong>Model:</strong> ${response.Model}</p>
                                    <p><strong>Year:</strong> ${response.Year}</p>
                                    <p><strong>Color:</strong> ${response.Color}</p>
                                    <p><strong>License Plate:</strong> ${response.PlateNumber}</p>
                                    <p><strong>Status:</strong> <span class="status-badge ${getStatusClass(response.Status)}">${response.Status}</span></p>
                                    <p><strong>Price:</strong> ${priceDisplay}</p>
                                </div>
                            </div>`,
                        width: '800px',
                        showConfirmButton: false,
                        showCloseButton: true
                    });
                } else {
                    Swal.fire('Error', 'Car not found', 'error');
                }
            }, 'json');
        }
    </script>
</body>
</html>