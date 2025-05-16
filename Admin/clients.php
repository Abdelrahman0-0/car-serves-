<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "car_services";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$sql = "
    SELECT 
        c.CustomerID, 
        c.Name, 
        c.Email, 
        c.Phone, 
        c.Address, 
        COUNT(o.id) AS order_count
    FROM Customer c
    LEFT JOIN orders o ON c.Name = o.client_name
    GROUP BY c.CustomerID
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients Management</title>
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
            vertical-align: middle;
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
            background-color: #e53935;
            border: none;
        }

        .btn-danger:hover {
            background-color: #b71c1c;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-warning {
            background-color: #ffc107;
            border: none;
            color: #000;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            background-color: #333;
            border: 1px solid #555;
            color: #fff;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #00bcd4;
        }

        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .modal-content {
            background-color: #333;
            color: #fff;
            border: none;
        }

        .modal-header, .modal-footer {
            border-color: #555;
        }

        .form-control {
            background-color: #444;
            border: 1px solid #555;
            color: #fff;
        }

        .form-control:focus {
            background-color: #444;
            color: #fff;
            border-color: #00bcd4;
            box-shadow: none;
        }

        .badge {
            font-weight: 500;
            padding: 5px 10px;
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
            <h1>Clients Management</h1>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Client List</h2>
                <div class="controls">
                    <button class="btn btn-primary" id="addClientBtn">
                        <i class="fas fa-plus"></i> Add New Client
                    </button>
                    <button class="btn btn-success" id="exportDataBtn">
                        <i class="fas fa-file-export"></i> Export Data
                    </button>
                </div>
            </div>

            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search by name, email, or phone...">
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="clientsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Orders</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientsBody">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['CustomerID']; ?></td>
                                    <td><?php echo $row['Name']; ?></td>
                                    <td><?php echo $row['Email']; ?></td>
                                    <td><?php echo $row['Phone']; ?></td>
                                    <td><?php echo $row['Address']; ?></td>
                                    <td><?php echo $row['order_count']; ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm me-2" onclick="viewClient('<?php echo $row['CustomerID']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm me-2" onclick="editClient('<?php echo $row['CustomerID']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="showDeleteModal('<?php echo $row['CustomerID']; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">لا توجد بيانات لعرضها.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Client Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Client Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="clientDetailsContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit/Add Client Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalTitle">Edit Client</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="clientForm">
                        <input type="hidden" id="clientId">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="editPhone" required>
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Address</label>
                            <input type="text" class="form-control" id="editAddress" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveClientBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this client? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DOM elements
        const clientsBody = document.getElementById("clientsBody");
        const searchInput = document.getElementById("searchInput");
        const addClientBtn = document.getElementById("addClientBtn");
        const exportDataBtn = document.getElementById("exportDataBtn");
        const saveClientBtn = document.getElementById("saveClientBtn");
        const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

        // Modal instances
        const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        // Current selected client ID
        let selectedClientId = null;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
        });

        // View client details
        function viewClient(id) {
            // In a real application, you would fetch this data from the server
            // For now, we'll just show the ID
            document.getElementById("clientDetailsContent").innerHTML = `
                <div class="mb-3">
                    <h6>Client ID</h6>
                    <p>${id}</p>
                </div>
                <p>Details would be fetched from the server in a real application.</p>
            `;
            viewModal.show();
        }

        // Edit client
        function editClient(id) {
            selectedClientId = id;
            document.getElementById("editModalTitle").textContent = "Edit Client";
            document.getElementById("clientId").value = id;
            
            // In a real application, you would fetch the client data from the server
            // and populate the form fields here
            
            editModal.show();
        }

        // Show delete confirmation modal
        function showDeleteModal(id) {
            selectedClientId = id;
            deleteModal.show();
        }

        // Setup event listeners
        function setupEventListeners() {
            // Search functionality
            searchInput.addEventListener("input", function() {
                const query = this.value.toLowerCase();
                const rows = clientsBody.getElementsByTagName("tr");
                
                for (let row of rows) {
                    const cells = row.getElementsByTagName("td");
                    let shouldShow = false;
                    
                    for (let i = 0; i < cells.length - 1; i++) { // Skip actions column
                        if (cells[i].textContent.toLowerCase().includes(query)) {
                            shouldShow = true;
                            break;
                        }
                    }
                    
                    row.style.display = shouldShow ? "" : "none";
                }
            });

            // Add new client
            addClientBtn.addEventListener("click", function() {
                selectedClientId = null;
                document.getElementById("editModalTitle").textContent = "Add New Client";
                document.getElementById("clientForm").reset();
                editModal.show();
            });

            // Save client (both add and edit)
            saveClientBtn.addEventListener("click", function() {
                // In a real application, you would send this data to the server
                // using AJAX or form submission
                alert("In a real application, this would save the client data to the server.");
                editModal.hide();
            });

            // Delete client
            confirmDeleteBtn.addEventListener("click", function() {
                // In a real application, you would send a delete request to the server
                alert("In a real application, this would delete the client with ID: " + selectedClientId);
                deleteModal.hide();
            });

            // Export data
            exportDataBtn.addEventListener("click", function() {
                // In a real application, you might generate a CSV from the server data
                alert("In a real application, this would export the client data.");
            });
        }

        // Make functions available globally
        window.viewClient = viewClient;
        window.editClient = editClient;
        window.showDeleteModal = showDeleteModal;
    </script>
</body>
</html>

<?php $conn->close(); ?>