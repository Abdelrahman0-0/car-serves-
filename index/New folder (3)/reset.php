<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST["identifier"]);

    if (empty($identifier)) {
        echo "Please enter your email, phone, or username.";
    } else {

        echo "If this account exists, a reset link has been sent to: " . htmlspecialchars($identifier);
    }
}
?>
