<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "car_services";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب البيانات من قاعدة البيانات
$sql = "SELECT * FROM orders";
$result = $conn->query($sql);

// تحويل النتائج إلى مصفوفة لتسهيل التعامل معها
$orders = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
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

        .table-container {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .table {
            background-color: #333;
            color: #fff;
        }

        .table thead {
            background-color: #444;
        }

        .table th, .table td {
            border-color: #555;
            padding: 12px 15px;
            text-align: left;
        }

        .btn-primary {
            background-color: #00bcd4;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #008ba3;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #bb2d3b;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
        }

        .text-dark {
            color: #212529 !important;
        }

        h1, h2 {
            color: #fff;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
            margin-right: 5px;
        }

        .section {
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            color: #b0bec5;
            font-size: 14px;
        }

        select, input {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #444;
            background-color: #2d2d2d;
            color: white;
            width: 200px;
        }

        /* Modal styles */
        .modal-content {
            background-color: #1e1e1e;
            color: white;
        }
        
        .modal-header {
            border-bottom: 1px solid #444;
        }
        
        .modal-footer {
            border-top: 1px solid #444;
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
            <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
        </div>

        <!-- Orders Filtering -->
        <div class="section">
            <div class="filter-section">
                <div class="filter-group">
                    <label for="orderType">Order Type:</label>
                    <select id="orderType">
                        <option value="all">All</option>
                        <option value="rental">Rental</option>
                        <option value="sale">Sale</option>
                        <option value="buy">Purchase</option>
                        <option value="repair">Repair</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="orderStatus">Order Status:</label>
                    <select id="orderStatus">
                        <option value="all">All</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="inprogress">In Progress</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="filterOrders()"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Recent Orders</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrderModal"><i class="fas fa-plus"></i> Add New Order</button>
            </div>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order Type</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTable">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['type']) ?></td>
                            <td><?= htmlspecialchars($order['client_name']) ?></td>
                            <td><?= htmlspecialchars($order['car_info']) ?></td>
                            <td><?= htmlspecialchars($order['date']) ?></td>
                            <td><span class="badge <?= getStatusColor($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
                            <td><?= number_format($order['price'], 2) ?> EGP</td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="viewOrder(<?= $order['id'] ?>)"><i class="fas fa-eye"></i> View</button>
                                <button class="btn btn-danger btn-sm" onclick="cancelOrder(<?= $order['id'] ?>)" <?= $order['status'] === 'Canceled' || $order['status'] === 'Completed' ? 'disabled' : '' ?>><i class="fas fa-trash-alt"></i> Cancel</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item">
                        <a class="page-link" href="#">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrderModalLabel">Order Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Order details will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Order Modal -->
    <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOrderModalLabel">Add New Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addOrderForm" action="add_order.php" method="POST">
                        <div class="mb-3">
                            <label for="newOrderType" class="form-label">Order Type</label>
                            <select class="form-select" id="newOrderType" name="type" required>
                                <option value="rental">Rental</option>
                                <option value="sale">Sale</option>
                                <option value="buy">Purchase</option>
                                <option value="repair">Repair</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newCustomer" class="form-label">Customer</label>
                            <input type="text" class="form-control" id="newCustomer" name="client_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="newVehicle" class="form-label">Vehicle</label>
                            <input type="text" class="form-control" id="newVehicle" name="car_info" required>
                        </div>
                        <div class="mb-3">
                            <label for="newDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="newDate" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">Status</label>
                            <select class="form-select" id="newStatus" name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Canceled">Canceled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newPrice" class="form-label">Price (EGP)</label>
                            <input type="number" class="form-control" id="newPrice" name="price" step="0.01" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addOrderForm" class="btn btn-primary">Add Order</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modals
        const viewOrderModal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
        const addOrderModal = new bootstrap.Modal(document.getElementById('addOrderModal'));

        // Sample data for JavaScript functions (you might want to fetch this from PHP)
        const ordersData = <?php echo json_encode($orders); ?>;

        function filterOrders() {
            const type = document.getElementById("orderType").value;
            const status = document.getElementById("orderStatus").value;
            const rows = document.querySelectorAll("#ordersTable tr");

            rows.forEach(row => {
                const rowType = row.cells[1].textContent.toLowerCase();
                const rowStatus = row.cells[5].textContent.toLowerCase().replace(" ", "");
                
                const typeMatch = type === "all" || rowType === type;
                const statusMatch = status === "all" || rowStatus === status;
                
                row.style.display = typeMatch && statusMatch ? "" : "none";
            });
        }

        function viewOrder(orderId) {
            const order = ordersData.find(o => o.id == orderId);
            if (order) {
                const orderDetails = document.getElementById("orderDetails");
                orderDetails.innerHTML = `
                    <p><strong>Order ID:</strong> ${order.id}</p>
                    <p><strong>Type:</strong> ${order.type}</p>
                    <p><strong>Customer:</strong> ${order.client_name}</p>
                    <p><strong>Vehicle:</strong> ${order.car_info}</p>
                    <p><strong>Date:</strong> ${order.date}</p>
                    <p><strong>Status:</strong> <span class="badge ${getStatusColor(order.status)}">${order.status}</span></p>
                    <p><strong>Price:</strong> ${order.price} EGP</p>
                `;
                viewOrderModal.show();
            }
        }

        function cancelOrder(orderId) {
            if (confirm("Are you sure you want to cancel this order?")) {
                // Here you would typically send an AJAX request to update the database
                // For now, we'll just update the UI
                const row = document.querySelector(`#ordersTable tr[data-id="${orderId}"]`);
                if (row) {
                    row.cells[5].innerHTML = '<span class="badge bg-danger">Canceled</span>';
                    const cancelBtn = row.querySelector('.btn-danger');
                    if (cancelBtn) cancelBtn.disabled = true;
                }
                alert("Order has been canceled successfully!");
            }
        }

        function getStatusColor(status) {
            if (status === "Completed") return "bg-success";
            if (status === "Canceled") return "bg-danger";
            if (status === "In Progress") return "bg-primary";
            if (status === "Pending") return "bg-warning text-dark";
            return "bg-secondary";
        }
    </script>
</body>
</html>

<?php
function getStatusColor($status) {
    if ($status === "Completed") return "bg-success";
    if ($status === "Canceled") return "bg-danger";
    if ($status === "In Progress") return "bg-primary";
    if ($status === "Pending") return "bg-warning text-dark";
    return "bg-secondary";
}
?>