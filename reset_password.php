<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['reset_error'] = "Passwords do not match";
        header("Location: reset_password.php?token=$token");
        exit();
    }
    
    // Check token validity
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Update password and clear token
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        
        $_SESSION['reset_success'] = "Password has been reset successfully. You can now login with your new password.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['reset_error'] = "Invalid or expired token";
        header("Location: login.php");
        exit();
    }
}

// Check if token is provided in URL
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header("Location: login.php");
    exit();
}

// Verify token
$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['reset_error'] = "Invalid or expired token";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .container { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 400px; }
        h2 { margin-bottom: 1.5rem; color: #2c3e50; font-size: 1.8rem; }
        input { width: 100%; padding: 0.8rem; margin: 0.8rem 0; border-radius: 8px; border: 1px solid #dfe6e9; font-size: 1rem; }
        button { width: 100%; padding: 0.8rem; border: none; border-radius: 8px; background-color: #3498db; color: white; font-size: 1rem; cursor: pointer; margin-top: 0.5rem; }
        .error-message { color: #e74c3c; margin: 0.5rem 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Your Password</h2>
        
        <?php if (isset($_SESSION['reset_error'])): ?>
            <div class="error-message"><?= $_SESSION['reset_error'] ?></div>
            <?php unset($_SESSION['reset_error']); ?>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" name="password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" name="reset_password">Reset Password</button>
        </form>
        
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>