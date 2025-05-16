<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "car_services";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function updateRelatedStatus($conn, $order_id, $new_status) {
    // Get order information
    $order_query = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order = $order_query->get_result()->fetch_assoc();
    $order_query->close();
    
    if (!$order) return false;
    
    // Map order status to related table status
    $table_status = match($new_status) {
        'pending' => 'pending',
        'inprogress' => 'in_progress',
        'completed' => 'completed',
        'canceled' => 'cancelled',
        default => 'pending'
    };
    
    // Find related record based on order type and update it
    switch ($order['type']) {
        case 'rental':
            // Update Rental table
            $stmt = $conn->prepare("
                UPDATE Rental r
                JOIN Customer c ON r.CustomerID = c.CustomerID
                JOIN Car ON r.CarID = Car.CarID
                SET r.Status = ?
                WHERE c.Name LIKE ? 
                AND CONCAT(Car.CarName, ' ', Car.Model) LIKE ?
                AND r.StartDate <= DATE_ADD(?, INTERVAL 3 DAY)
                AND r.StartDate >= DATE_SUB(?, INTERVAL 3 DAY)
            ");
            $client_search = "%" . $order['client_name'] . "%";
            $car_search = "%" . $order['car_info'] . "%";
            $stmt->bind_param("sssss", $table_status, $client_search, $car_search, $order['date'], $order['date']);
            $stmt->execute();
            
            // Update Car status if rental is completed or canceled
            if ($new_status === 'completed' || $new_status === 'canceled') {
                $conn->query("
                    UPDATE Car 
                    JOIN Rental ON Car.CarID = Rental.CarID
                    JOIN Customer ON Rental.CustomerID = Customer.CustomerID
                    SET Car.Status = 'available'
                    WHERE Customer.Name LIKE '$client_search'
                    AND Rental.StartDate <= DATE_ADD('{$order['date']}', INTERVAL 3 DAY)
                    AND Rental.StartDate >= DATE_SUB('{$order['date']}', INTERVAL 3 DAY)
                ");
            }
            break;
            
        case 'sale':
            // Update Sale table
            $stmt = $conn->prepare("
                UPDATE Sale s
                JOIN Customer c ON s.CustomerID = c.CustomerID
                JOIN Car ON s.CarID = Car.CarID
                SET s.Status = ?
                WHERE c.Name LIKE ? 
                AND CONCAT(Car.CarName, ' ', Car.Model) LIKE ?
                AND s.SaleDate <= DATE_ADD(?, INTERVAL 3 DAY)
                AND s.SaleDate >= DATE_SUB(?, INTERVAL 3 DAY)
            ");
            $client_search = "%" . $order['client_name'] . "%";
            $car_search = "%" . $order['car_info'] . "%";
            $stmt->bind_param("sssss", $table_status, $client_search, $car_search, $order['date'], $order['date']);
            $stmt->execute();
            
            // Update Car status
            if ($new_status === 'completed') {
                $conn->query("
                    UPDATE Car 
                    JOIN Sale ON Car.CarID = Sale.CarID
                    JOIN Customer ON Sale.CustomerID = Customer.CustomerID
                    SET Car.Status = 'available'
                    WHERE Customer.Name LIKE '$client_search'
                    AND Sale.SaleDate <= DATE_ADD('{$order['date']}', INTERVAL 3 DAY)
                    AND Sale.SaleDate >= DATE_SUB('{$order['date']}', INTERVAL 3 DAY)
                ");
            } elseif ($new_status === 'canceled') {
                $conn->query("
                    UPDATE Car 
                    JOIN Sale ON Car.CarID = Sale.CarID
                    JOIN Customer ON Sale.CustomerID = Customer.CustomerID
                    SET Car.Status = 'maintenance'
                    WHERE Customer.Name LIKE '$client_search'
                    AND Sale.SaleDate <= DATE_ADD('{$order['date']}', INTERVAL 3 DAY)
                    AND Sale.SaleDate >= DATE_SUB('{$order['date']}', INTERVAL 3 DAY)
                ");
            }
            break;            
        case 'buy':
            // Update CustomerPurchase table
            $stmt = $conn->prepare("
                UPDATE CustomerPurchase p
                JOIN Customer c ON p.CustomerID = c.CustomerID
                JOIN Car ON p.CarID = Car.CarID
                SET p.PaymentStatus = ?
                WHERE c.Name LIKE ? 
                AND CONCAT(Car.CarName, ' ', Car.Model) LIKE ?
                AND p.PurchaseDate <= DATE_ADD(?, INTERVAL 3 DAY)
                AND p.PurchaseDate >= DATE_SUB(?, INTERVAL 3 DAY)
            ");
            $client_search = "%" . $order['client_name'] . "%";
            $car_search = "%" . $order['car_info'] . "%";
            $stmt->bind_param("sssss", $table_status, $client_search, $car_search, $order['date'], $order['date']);
            $stmt->execute();
            
            // Update Car status
            if ($new_status === 'completed') {
                $conn->query("
                    UPDATE Car 
                    JOIN CustomerPurchase ON Car.CarID = CustomerPurchase.CarID
                    JOIN Customer ON CustomerPurchase.CustomerID = Customer.CustomerID
                    SET Car.Status = 'sold'
                    WHERE Customer.Name LIKE '$client_search'
                    AND CustomerPurchase.PurchaseDate <= DATE_ADD('{$order['date']}', INTERVAL 3 DAY)
                    AND CustomerPurchase.PurchaseDate >= DATE_SUB('{$order['date']}', INTERVAL 3 DAY)
                ");
            } elseif ($new_status === 'canceled') {
                $conn->query("
                    UPDATE Car 
                    JOIN CustomerPurchase ON Car.CarID = CustomerPurchase.CarID
                    JOIN Customer ON CustomerPurchase.CustomerID = Customer.CustomerID
                    SET Car.Status = 'available'
                    WHERE Customer.Name LIKE '$client_search'
                    AND CustomerPurchase.PurchaseDate <= DATE_ADD('{$order['date']}', INTERVAL 3 DAY)
                    AND CustomerPurchase.PurchaseDate >= DATE_SUB('{$order['date']}', INTERVAL 3 DAY)
                ");
            }
            break;
            
        case 'repair':
            // Update Maintenance table
            $stmt = $conn->prepare("
                UPDATE Maintenance m
                JOIN Car ON m.CarID = Car.CarID
                SET m.Status = ?
                WHERE CONCAT(Car.CarName, ' ', Car.Model) LIKE ?
                AND m.MaintenanceDate <= DATE_ADD(?, INTERVAL 3 DAY)
                AND m.MaintenanceDate >= DATE_SUB(?, INTERVAL 3 DAY)
            ");
            $car_search = "%" . $order['car_info'] . "%";
            $stmt->bind_param("ssss", $table_status, $car_search, $order['date'], $order['date']);
            $stmt->execute();
            
            // Update Car status if maintenance is completed
            if ($new_status === 'completed') {
                $conn->query("
                    UPDATE Car 
                    JOIN Maintenance ON Car.CarID = Maintenance.CarID
                    SET Car.Status = 'available'
                    WHERE Maintenance.MaintenanceDate <= DATE_ADD('{$order['date']}', INTERVAL 3 DAY)
                    AND Maintenance.MaintenanceDate >= DATE_SUB('{$order['date']}', INTERVAL 3 DAY)
                ");
            }
            break;
    }
    return true;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    if ($_POST['form_action'] === 'update_order_status') {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['new_status'];
        
        $allowed_status = ['pending', 'inprogress', 'completed', 'canceled'];
        if (in_array($new_status, $allowed_status)) {
            $conn->begin_transaction();
            try {
                // Update order status
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $order_id);
                $stmt->execute();
                $stmt->close();
                
                // Update related tables and car status
                updateRelatedStatus($conn, $order_id, $new_status);
                
                $conn->commit();
                header("Location: manage.php?success=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                header("Location: manage.php?error=" . urlencode($e->getMessage()));
                exit();
            }
        }
    }
    elseif ($_POST['form_action'] === 'delete_order') {
        $order_id = intval($_POST['order_id']);
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage.php?success=2");
        exit();
    }
} 
// Handle message and feedback replies
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    $type = $_POST['type'];
    $id = intval($_POST['id']);
    $reply = $_POST['reply'];
    
    try {
        if ($type === 'contact') {
            $stmt = $conn->prepare("UPDATE Contact SET Response = ?, Status = 'resolved' WHERE ContactID = ?");
            $stmt->bind_param("si", $reply, $id);
        } elseif ($type === 'feedback') {
            $stmt = $conn->prepare("UPDATE Feedback SET Response = ? WHERE FeedbackID = ?");
            $stmt->bind_param("si", $reply, $id);
        }
        
        $stmt->execute();
        $stmt->close();
        header("Location: manage.php?success=3");
        exit();
    } catch (Exception $e) {
        header("Location: manage.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Fetch data
$pending_orders = $conn->query("SELECT * FROM orders WHERE status = 'pending' ORDER BY date DESC");
$inprogress_orders = $conn->query("SELECT * FROM orders WHERE status = 'inprogress' ORDER BY date DESC");
$completed_orders = $conn->query("SELECT * FROM orders WHERE status = 'completed' ORDER BY date DESC");
$canceled_orders = $conn->query("SELECT * FROM orders WHERE status = 'canceled' ORDER BY date DESC");
$contacts = $conn->query("SELECT ContactID as id, Name as name, Email as email, Message as message, Response as reply, SubmissionDate as created_at FROM Contact ORDER BY SubmissionDate DESC");
$feedbacks = $conn->query("SELECT FeedbackID as id, (SELECT Name FROM Customer WHERE CustomerID=Feedback.CustomerID) as name, Comments as comment, Response as reply, SubmissionDate as created_at FROM Feedback ORDER BY SubmissionDate DESC");
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin panal- manage</title>
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
        background-color: #1a1a1a;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    .section h2 {
        margin-bottom: 20px;
        color: #00bcd4;
        border-bottom: 1px solid #333;
        padding-bottom: 10px;
    }
    .item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #333;
    }
    .item:last-child {
        border-bottom: none;
    }
    .btn {
        padding: 6px 12px;
        margin-left: 8px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        color: white;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    .btn-delete {
        background-color: #e74c3c;
    }
    .btn-delete:hover {
        background-color: #c0392b;
    }
    .btn-reply {
        background-color: #2ecc71;
    }
    .btn-reply:hover {
        background-color: #27ae60;
    }
    .btn-remove {
        background-color: #e67e22;
    }
    .btn-remove:hover {
        background-color: #d35400;
    }
    .btn-edit {
        background-color: #3498db;
    }
    .btn-edit:hover {
        background-color: #2980b9;
    }
    .reply-box {
        margin-top: 10px;
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #333;
        background-color: #121212;
        color: white;
        display: none;
    }
    .reply-box textarea {
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #333;
        background-color: #121212;
        color: white;
        resize: vertical;
    }
    .action-buttons {
        display: flex;
        align-items: center;
    }
    .status-pending {
        color: orange;
    }
    .status-inprogress {
        color: dodgerblue;
    }
    .status-completed {
        color: greenyellow;
    }
    .status-canceled {
        color: red;
    }
    .alert-success {
        background-color: #2ecc71;
        color: white;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    select.form-select {
        background-color: #121212;
        color: white;
        border: 1px solid #333;
        padding: 5px;
        border-radius: 5px;
    }
    /* New styles for messages and feedback */
    .message-card {
        background-color: #252525;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border-left: 4px solid #00bcd4;
    }
    .message-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .message-sender {
        font-weight: bold;
        color: #00bcd4;
    }
    .message-date {
        color: #aaa;
        font-size: 0.9em;
    }
    .message-content {
        margin-bottom: 10px;
        line-height: 1.5;
    }
    .message-reply {
        background-color: #333;
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
        border-left: 3px solid #2ecc71;
    }
    .reply-label {
        font-weight: bold;
        color: #2ecc71;
        margin-bottom: 5px;
        display: block;
    }
    .no-reply {
        color: #aaa;
        font-style: italic;
    }
    .message-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo match($_GET['success']) {
                    1 => "Order status updated successfully!",
                    2 => "Order deleted successfully!",
                    3 => "Reply sent successfully!",
                    default => "Action completed successfully!"
                };
                ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                Error: <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <!-- Messages Section -->
        <section class="section" id="messages">
            <h2><i class="fas fa-envelope"></i> Messages</h2>
            <?php if ($contacts->num_rows > 0): ?>
                <?php while ($msg = $contacts->fetch_assoc()): ?>
                <div class="message-card">
                    <div class="message-header">
                        <div class="message-sender"><?= htmlspecialchars($msg['name']) ?> (<?= htmlspecialchars($msg['email']) ?>)</div>
                        <div class="message-date"><?= date('M d, Y H:i', strtotime($msg['created_at'])) ?></div>
                    </div>
                    <div class="message-content">
                        <?= htmlspecialchars($msg['message']) ?>
                    </div>
                    
                    <?php if (!empty($msg['reply'])): ?>
                        <div class="message-reply">
                            <span class="reply-label">Your Reply:</span>
                            <?= htmlspecialchars($msg['reply']) ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reply">No reply yet</div>
                    <?php endif; ?>
                    
                    <div class="message-actions">
                        <button class="btn btn-reply" onclick="toggleReplyBox(this)">Reply</button>
                        <button class="btn btn-delete" onclick="confirmDelete('contact', <?= htmlspecialchars($msg['id']) ?>)">Delete</button>
                    </div>
                    
                    <div class="reply-box">
                        <form method="post" action="manage.php">
                            <input type="hidden" name="type" value="contact">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($msg['id']) ?>">
                            <textarea name="reply" rows="3" placeholder="Write your reply here..."><?= htmlspecialchars($msg['reply'] ?? '') ?></textarea>
                            <button type="submit" class="btn btn-primary mt-2">Send Reply</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No messages found.</p>
            <?php endif; ?>
        </section>

        <!-- Feedback Section -->
        <section class="section" id="feedbacks">
            <h2><i class="fas fa-comment-alt"></i> Feedback</h2>
            <?php if ($feedbacks->num_rows > 0): ?>
                <?php while ($fb = $feedbacks->fetch_assoc()): ?>
                <div class="message-card">
                    <div class="message-header">
                        <div class="message-sender"><?= htmlspecialchars($fb['name']) ?></div>
                        <div class="message-date"><?= date('M d, Y H:i', strtotime($fb['created_at'])) ?></div>
                    </div>
                    <div class="message-content">
                        <?= htmlspecialchars($fb['comment']) ?>
                    </div>
                    
                    <?php if (!empty($fb['reply'])): ?>
                        <div class="message-reply">
                            <span class="reply-label">Your Response:</span>
                            <?= htmlspecialchars($fb['reply']) ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reply">No response yet</div>
                    <?php endif; ?>
                    
                    <div class="message-actions">
                        <button class="btn btn-reply" onclick="toggleReplyBox(this)">Respond</button>
                        <button class="btn btn-delete" onclick="confirmDelete('feedback', <?= htmlspecialchars($fb['id']) ?>)">Delete</button>
                    </div>
                    
                    <div class="reply-box">
                        <form method="post" action="manage.php">
                            <input type="hidden" name="type" value="feedback">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($fb['id']) ?>">
                            <textarea name="reply" rows="3" placeholder="Write your response here..."><?= htmlspecialchars($fb['reply'] ?? '') ?></textarea>
                            <button type="submit" class="btn btn-primary mt-2">Send Response</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No feedback found.</p>
            <?php endif; ?>
        </section>

        <!-- Manage Orders Section -->
        <section class="section" id="orders">
            <h2><i class="fas fa-tasks"></i> Manage Orders</h2>

            <h3>Pending Orders</h3>
            <?php if ($pending_orders->num_rows > 0): ?>
                <?php while ($order = $pending_orders->fetch_assoc()): ?>
                <div class="item">
                    <div>
                        <strong>Order #<?= htmlspecialchars($order['id']) ?>:</strong> 
                        <?= htmlspecialchars($order['client_name']) ?> - 
                        <?= htmlspecialchars($order['car_info']) ?> - 
                        <span class="status-pending"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="form_action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <select name="new_status" onchange="this.form.submit()" class="form-select">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="inprogress" <?= $order['status'] === 'inprogress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="canceled" <?= $order['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        </select>
                    </form>
                    <form method="post" style="display: inline; margin-left: 10px;">
                        <input type="hidden" name="form_action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <button type="submit" class="btn btn-delete">Delete</button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No pending orders.</p>
            <?php endif; ?>

            <h3>In Progress Orders</h3>
            <?php if ($inprogress_orders->num_rows > 0): ?>
                <?php while ($order = $inprogress_orders->fetch_assoc()): ?>
                <div class="item">
                    <div>
                        <strong>Order #<?= htmlspecialchars($order['id']) ?>:</strong> 
                        <?= htmlspecialchars($order['client_name']) ?> - 
                        <?= htmlspecialchars($order['car_info']) ?> - 
                        <span class="status-inprogress"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="form_action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <select name="new_status" onchange="this.form.submit()" class="form-select">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="inprogress" <?= $order['status'] === 'inprogress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="canceled" <?= $order['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        </select>
                    </form>
                    <form method="post" style="display: inline; margin-left: 10px;">
                        <input type="hidden" name="form_action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <button type="submit" class="btn btn-delete">Delete</button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No orders in progress.</p>
            <?php endif; ?>

            <h3>Completed Orders</h3>
            <?php if ($completed_orders->num_rows > 0): ?>
                <?php while ($order = $completed_orders->fetch_assoc()): ?>
                <div class="item">
                    <div>
                        <strong>Order #<?= htmlspecialchars($order['id']) ?>:</strong> 
                        <?= htmlspecialchars($order['client_name']) ?> - 
                        <?= htmlspecialchars($order['car_info']) ?> - 
                        <span class="status-completed"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="form_action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <select name="new_status" onchange="this.form.submit()" class="form-select">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="inprogress" <?= $order['status'] === 'inprogress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="canceled" <?= $order['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        </select>
                    </form>
                    <form method="post" style="display: inline; margin-left: 10px;">
                        <input type="hidden" name="form_action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <button type="submit" class="btn btn-delete">Delete</button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No completed orders.</p>
            <?php endif; ?>

            <h3>Canceled Orders</h3>
            <?php if ($canceled_orders->num_rows > 0): ?>
                <?php while ($order = $canceled_orders->fetch_assoc()): ?>
                <div class="item">
                    <div>
                        <strong>Order #<?= htmlspecialchars($order['id']) ?>:</strong> 
                        <?= htmlspecialchars($order['client_name']) ?> - 
                        <?= htmlspecialchars($order['car_info']) ?> - 
                        <span class="status-canceled"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="form_action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <select name="new_status" onchange="this.form.submit()" class="form-select">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="inprogress" <?= $order['status'] === 'inprogress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="canceled" <?= $order['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        </select>
                    </form>
                    <form method="post" style="display: inline; margin-left: 10px;">
                        <input type="hidden" name="form_action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                        <button type="submit" class="btn btn-delete">Delete</button>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No canceled orders.</p>
            <?php endif; ?>
        </section>
    </div>

    <script>
    function toggleReplyBox(button) {
        const messageCard = button.closest('.message-card');
        const replyBox = messageCard.querySelector('.reply-box');
        replyBox.style.display = replyBox.style.display === 'block' ? 'none' : 'block';
    }

    function confirmDelete(type, id) {
        if (confirm('Are you sure you want to delete this item?')) {
            window.location.href = `delete_handler.php?type=${type}&id=${id}`;
        }
    }
    </script>
</body>
</html>