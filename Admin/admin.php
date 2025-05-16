<?php
// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "car_services");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// إضافة مستخدم جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $registered_at = date("Y-m-d H:i:s");

    $hashed_password=password_hash($password,PASSWORD_DEFAULT);

    $sql = "INSERT INTO Admin (Name, Email, Password, RegisteredAt, Role, Status) VALUES ('$name', '$email', '$hashed_password', '$registered_at', 'admin', 'active')";
            if ($conn->query($sql)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
    }


// تعديل مستخدم (بدون تعديل كلمة المرور إلا إذا أدخلت كلمة مرور جديدة)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo "<script>alert('password must be at least 6 characters');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE Admin SET Name='$name', Email='$email', Password='$hashed_password' WHERE AdminID=$id";
                  }
    } else {
      $sql = "UPDATE Admin SET Name='$name', Email='$email', Password='$hashed_password' WHERE AdminID=$id";
        }

    if (isset($sql) && $conn->query($sql)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif (isset($sql)) {
        echo "Error: " . $conn->error;
    }
}

// حذف مستخدم
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM Admin WHERE AdminID=$id";
        if ($conn->query($sql)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

// جلب المستخدمين (لا تعرض كلمة المرور)
$result = $conn->query("SELECT AdminID as id, Name as name, Email as email, RegisteredAt as registered_at FROM Admin ORDER BY AdminID DESC");

?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>إدارة المستخدمين</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: 'Poppins', Arial, sans-serif;
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
      border-radius: 8px;
    }

    h1 {
      color: #00bcd4;
      margin: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #1e1e1e;
      margin-top: 20px;
    }

    th, td {
      border: 1px solid #444;
      padding: 12px;
      text-align: left;
    }

    th {
      background-color: #333;
      color: #00bcd4;
    }

    .btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-edit {
      background-color: #2196F3;
      color: white;
    }

    .btn-delete {
      background-color: #f44336;
      color: white;
    }

    .btn-add {
      background-color: #00BCD4;
      color: white;
      padding: 8px 16px;
      font-weight: bold;
      border-radius: 5px;
    }

    .actions {
      display: flex;
      gap: 5px;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.7);
      justify-content: center;
      align-items: center;
      z-index: 100;
    }

    .modal-content {
      background-color: #1e1e1e;
      padding: 25px;
      border-radius: 10px;
      width: 450px;
      max-width: 90%;
      color: #fff;
      box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #444;
    }

    .modal-title {
      color: #00bcd4;
      margin: 0;
      font-size: 1.5rem;
    }

    .close-btn {
      background-color: transparent;
      color: #aaa;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close-btn:hover {
      color: #f44336;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: #aaa;
    }

    .form-control {
      width: 100%;
      padding: 10px;
      background-color: #333;
      border: 1px solid #444;
      border-radius: 4px;
      color: #fff;
      font-size: 14px;
    }

    .form-control:focus {
      outline: none;
      border-color: #00bcd4;
    }

    .modal-footer {
      margin-top: 20px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn-submit {
      background-color: #00BCD4;
      color: white;
      padding: 10px 20px;
      font-weight: bold;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn-submit:hover {
      background-color: #008ba3;
    }

    .btn-cancel {
      background-color: #666;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn-cancel:hover {
      background-color: #555;
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
      <h1>Users Management</h1>
      <button class="btn btn-add" onclick="openAddModal()">
        <i class="fas fa-plus"></i> Add New User
      </button>
    </div>

    <table id="userTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Registered At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()) : ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= $row['registered_at'] ?></td>
            <td class="actions">
              <button class="btn btn-edit" onclick='openEditModal(<?= json_encode($row) ?>)'>Edit</button>
              <a href="?delete=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('هل أنت متأكد من حذف المستخدم؟');">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Add/Edit Modal -->
  <div class="modal" id="userModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="modalTitle">Add New User</h3>
        <button class="close-btn" onclick="closeModal('userModal')">&times;</button>
      </div>
      <form id="userForm" method="POST" onsubmit="return validateForm()">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="userId" value="">
        <div class="form-group">
          <label for="name">Name:</label>
          <input class="form-control" type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
          <label for="email">Email:</label>
          <input class="form-control" type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
  <label for="password">Password:</label>
  <input class="form-control" type="password" id="password" name="password" >
</div>
        <div class="modal-footer">
          <button type="submit" class="btn-submit" id="submitBtn">Save</button>
          <button type="button" class="btn-cancel" onclick="closeModal('userModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

<script>
  function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userId').value = '';
    document.getElementById('name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('userModal').style.display = 'flex';
  }

  function openEditModal(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('userId').value = user.id;
    document.getElementById('name').value = user.name;
    document.getElementById('email').value = user.email;
    document.getElementById('userModal').style.display = 'flex';
  }

  function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }

  // التحقق البسيط من الفورم قبل الإرسال
  function validateForm() {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();

    if (name === '' || email === '') {
      alert('يرجى تعبئة كل الحقول المطلوبة');
      return false;
    }
    return true;
  }

  // غلق المودال عند الضغط في أي مكان خارج محتوى المودال
  window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
      modal.style.display = "none";
    }
  }


</script>

<!-- لا تنسى ربط FontAwesome إذا ماكانش مربوط -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</body>
</html>

    
   