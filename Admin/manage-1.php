<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "rental13";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['delete_type'], $_POST['id'])) {
        $type = $_POST['delete_type'];
        $id = intval($_POST['id']);

        $table = '';
        if ($type === 'contact') $table = 'contact';
        elseif ($type === 'feedback') $table = 'feedback';
        elseif ($type === 'orders') $table = 'orders';
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid type']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    if (isset($_POST['reply_type'], $_POST['id'], $_POST['reply'])) {
        $type = $_POST['reply_type'];
        $id = intval($_POST['id']);
        $reply = $_POST['reply'];

        $table = '';
        if ($type === 'contact') $table = 'contact';
        elseif ($type === 'feedback') $table = 'feedback';
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid reply type']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE $table SET reply = ? WHERE id = ?");
        $stmt->bind_param("si", $reply, $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }

    if (isset($_POST['order_id'], $_POST['status'])) {
        $id = intval($_POST['order_id']);
        $status = $_POST['status'];

        $allowed_status = ['Pending', 'In Progress', 'Completed', 'Canceled'];
        if (!in_array($status, $allowed_status)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
        exit;
    }
}

// Fetch data
$contacts = $conn->query("SELECT * FROM contact ORDER BY Response DESC");
$feedbacks = $conn->query("SELECT * FROM feedback ORDER BY Response DESC");
$canceled_orders = $conn->query("SELECT * FROM orders WHERE status = 'Canceled' ORDER BY type  DESC");
$orders = $conn->query("SELECT * FROM orders ORDER BY status DESC");
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin panal- manage</title>
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
            padding: 0;}
        .sidebar {
            background-color: #1a1a1a;
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            display: flex;
            flex-direction: column;}
        .sidebar a {
            color: #aaa;
            padding: 15px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;}
        .sidebar a:hover {
            background-color: #333;
            color: #00bcd4;}
        .sidebar i {margin-right: 10px;}
        .main-content {
            margin-left: 250px;
            padding: 20px;}
        .top-bar {
            background-color: #1a1a1a;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;}
        .section {
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);}
        .section h2 {
            margin-bottom: 20px;
            color: #00bcd4;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;}
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #333;}
        .item:last-child {border-bottom: none;}
        .btn {
            padding: 6px 12px;
            margin-left: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;}
        .btn-delete {background-color: #e74c3c;}
        .btn-delete:hover {background-color: #c0392b;}
        .btn-reply {background-color: #2ecc71;}
        .btn-reply:hover {background-color: #27ae60;}
        .btn-remove {background-color: #e67e22;}
        .btn-remove:hover {background-color: #d35400;}
        .btn-edit {background-color: #3498db;}
        .btn-edit:hover {background-color: #2980b9;}
        .reply-box {
            margin-top: 10px;
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #333;
            background-color: #121212;
            color: white;
            display: none;}
        .reply-box textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #333;
            background-color: #121212;
            color: white;
            resize: vertical;}
        .action-buttons {
            display: flex;
            align-items: center;}
        .status-pending {color: orange;}
        .status-in-progress {color: dodgerblue;}
        .status-completed {color: greenyellow;}
        .status-canceled {color: red;}
    </style></head>
<body>
    <div class="sidebar">
        <div class="sidebar-header text-center mb-4">
            <h4><i class="fas fa-car"></i> Car Services</h4>
        </div>
        <a href="Admin_panal.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="data_Admin.html"><i class="fas fa-users"></i> Users</a>
        <a href="orders.html"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="Analytics.html"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="clints.html"><i class="fas fa-user-tie"></i> Clients</a>
        <a href="reports.html"><i class="fas fa-clipboard-list"></i> Reports</a>
        <a href="Statistics.html"><i class="fas fa-chart-bar me-2"></i> Statistics</a>
        <a href="mange.html"><i class="fas fa-cogs"></i> Manage</a>
        <a href="manage_car.html" class="active"><i class="fas fa-car"></i> Cars</a>
        <a href="#logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>

<div class="main-content">

  <!-- Messages Section -->
  <section class="section" id="messages">
    <h2>Messages</h2>
    <?php if ($contacts->num_rows > 0): ?>
      <?php while ($msg = $contacts->fetch_assoc()): ?>
      <div class="item" data-id="<?= $msg['id'] ?>" data-type="contact">
        <span><strong><?= htmlspecialchars($msg['name']) ?>:</strong> <?= htmlspecialchars($msg['message']) ?></span>
        <div class="action-buttons">
          <button class="btn btn-reply" onclick="toggleReplyBox(this)">Reply</button>
          <button class="btn btn-delete" onclick="deleteItem(this)">Delete</button>
        </div>
      </div>
      <div class="reply-box" style="margin-bottom:20px;">
        <textarea placeholder="Write your reply here..."><?= htmlspecialchars($msg['reply']) ?></textarea>
        <button class="btn btn-primary" onclick="sendReply(this, 'contact', <?= $msg['id'] ?>)">Send Reply</button>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No messages found.</p>
    <?php endif; ?>
  </section>

  <!-- Feedback Section -->
  <section class="section" id="feedbacks">
    <h2>Feedback</h2>
    <?php if ($feedbacks->num_rows > 0): ?>
      <?php while ($fb = $feedbacks->fetch_assoc()): ?>
      <div class="item" data-id="<?= $fb['id'] ?>" data-type="feedback">
        <span><strong><?= htmlspecialchars($fb['name']) ?>:</strong> <?= htmlspecialchars($fb['comment']) ?></span>
        <div class="action-buttons">
          <button class="btn btn-reply" onclick="toggleReplyBox(this)">Reply</button>
          <button class="btn btn-delete" onclick="deleteItem(this)">Delete</button>
        </div>
      </div>
      <div class="reply-box" style="margin-bottom:20px;">
        <textarea placeholder="Write your reply here..."><?= htmlspecialchars($fb['reply']) ?></textarea>
        <button class="btn btn-primary" onclick="sendReply(this, 'feedback', <?= $fb['id'] ?>)">Send Reply</button>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No feedback found.</p>
    <?php endif; ?>
  </section>

  <!-- Canceled Orders Section -->
  <section class="section" id="canceled-orders">
    <h2>Canceled Orders</h2>
    <?php if ($canceled_orders->num_rows > 0): ?>
      <?php while ($order = $canceled_orders->fetch_assoc()): ?>
      <div class="item" data-id="<?= $order['id'] ?>" data-type="orders">
        <span><strong>Order #<?= $order['id'] ?>:</strong> <?= htmlspecialchars($order['details']) ?> - <span class="status-Canceled"><?= $order['status'] ?></span></span>
        <div class="action-buttons">
          <button class="btn btn-delete" onclick="deleteItem(this)">Delete</button>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No canceled orders.</p>
    <?php endif; ?>
  </section>

  <!-- Manage Orders Section -->
  <section class="section" id="orders">
    <h2>Manage Orders</h2>
    <?php if ($orders->num_rows > 0): ?>
      <?php while ($order = $orders->fetch_assoc()): ?>
      <div class="item" data-id="<?= $order['id'] ?>" data-type="orders">
        <span><strong>Order #<?= $order['id'] ?>:</strong> <?= htmlspecialchars($order['details']) ?> - 
          <select class="form-select" onchange="updateOrderStatus(<?= $order['id'] ?>, this.value)">
            <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $order['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Canceled" <?= $order['status'] === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
          </select>
        </span>
        <div class="action-buttons">
          <button class="btn btn-delete" onclick="deleteItem(this)">Delete</button>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No orders found.</p>
    <?php endif; ?>
  </section>

</div>

<script>
function toggleReplyBox(button) {
  const item = button.closest('.item');
  const replyBox = item.nextElementSibling;
  if (replyBox.style.display === 'flex') {
    replyBox.style.display = 'none';
  } else {
    replyBox.style.display = 'flex';
  }
}

function deleteItem(button) {
  if (!confirm('Are you sure you want to delete this item?')) return;
  const item = button.closest('.item');
  const id = item.getAttribute('data-id');
  const type = item.getAttribute('data-type');

  fetch('', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({delete_type: type, id: id})
  }).then(res => res.json())
    .then(data => {
      if (data.success) {
        const replyBox = item.nextElementSibling;
        if (replyBox && replyBox.classList.contains('reply-box')) {
          replyBox.remove();
        }
        item.remove();
      } else {
        alert('Failed to delete: ' + (data.message || 'Unknown error'));
      }
    }).catch(() => alert('Network error'));
}

function sendReply(button, type, id) {
  const replyBox = button.parentElement;
  const textarea = replyBox.querySelector('textarea');
  const reply = textarea.value.trim();
  if (reply === '') {
    alert('Reply cannot be empty.');
    return;
  }
  fetch('', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({reply_type: type, id: id, reply: reply})
  }).then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Reply sent successfully.');
      } else {
        alert('Failed to send reply: ' + (data.message || 'Unknown error'));
      }
    }).catch(() => alert('Network error'));
}

function updateOrderStatus(id, status) {
  fetch('', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({order_id: id, status: status})
  }).then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Order status updated to "' + status + '".');
        if (status === 'Canceled') {
          location.reload(); // refresh to update canceled orders section
        }
      } else {
        alert('Failed to update order status: ' + (data.message || 'Unknown error'));
      }
    }).catch(() => alert('Network error'));
}
</script>

</body>
</html>