<?php
session_start();

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'car_services');
define('DB_USER', 'root'); // Change these values according to your database settings
define('DB_PASS', '');

// Establish database connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
$step = 1; // 1 = email verification, 2 = password reset
$error = '';
$email = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email'])) {
        // Step 1: Email verification
        $email = trim($_POST['email']);
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM Customer WHERE Email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $step = 2; // Move to password reset step
        } else {
            $error = "No account found with that email address.";
        }
    } 
    elseif (isset($_POST['new_password'])) {
        // Step 2: Password reset
        $email = $_POST['email_hidden'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate passwords
        if ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
            $step = 2;
        } elseif (strlen($newPassword) < 8) {
            $error = "Password must be at least 8 characters long.";
            $step = 2;
        } else {
            // Update password in database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updateStmt = $conn->prepare("UPDATE Customer SET Password = :password WHERE Email = :email");
            $updateStmt->bindParam(':password', $hashedPassword);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->execute();
            
            $success = "Your password has been reset successfully!";
            $step = 1; // Return to initial step
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CarZone - Forgot Password</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #0f0f0f, #2c3e50);
            color: #ecf0f1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            line-height: 1.6;
        }
        
        .container {
            background-color: #34495e;
            padding: 40px;
            width: 380px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 15px rgba(26, 188, 156, 0.5);
            margin-bottom: 20px;
        }
        
        h2 {
            color: #ecdbba;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        label {
            display: block;
            text-align: left;
            color: #bdc3c7;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #2c3e50;
            background-color: #2c3e50;
            color: #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s ease;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #1abc9c;
            outline: none;
            background: #34495e;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #1abc9c;
            color: white;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background-color: #16a085;
        }
        
        .error {
            color: #ff8080;
            font-size: 13px;
            margin-bottom: 10px;
            display: block;
        }
        
        .success {
            color: #80ff80;
            font-size: 13px;
            margin-bottom: 10px;
            display: block;
        }
        
        #resetForm {
            display: <?php echo ($step == 1) ? 'block' : 'none'; ?>;
        }
        
        #passwordForm {
            display: <?php echo ($step == 2) ? 'block' : 'none'; ?>;
        }
    </style>    
</head>

<body>
    <div class="container">
        <img src="img/logo.jpg" alt="CarZone Logo" class="logo">
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <a href="login.html" style="color: #1abc9c; display: block; margin-top: 15px;">Return to login</a>
        <?php else: ?>
            <!-- Email Verification Form -->
            <form id="resetForm" method="post">
                <h2>Forgot Your Password?</h2>
                
                <?php if ($error && $step == 1): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address" required value="<?php echo htmlspecialchars($email); ?>">
                
                <button type="submit">Continue</button>
            </form>
            
            <!-- Password Reset Form -->
            <form id="passwordForm" method="post">
                <h2>Reset Your Password</h2>
                
                <?php if ($error && $step == 2): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <input type="hidden" name="email_hidden" value="<?php echo htmlspecialchars($email); ?>">
                
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>