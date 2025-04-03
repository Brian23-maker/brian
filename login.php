<?php
session_start();
require 'db_connect.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Forgot Password Handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    $email = $_POST['email'];
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiration
        
        // Store token in database
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        $stmt->execute([$token, $expires, $email]);
        
        // Send email with reset link using PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'dayolizermaroa@gmail.com'; // Your Gmail
            $mail->Password   = 'tnjs vwzg eist zuia';     // Your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            
            // Recipients
            $mail->setFrom('dayolizermaroa@gmail.com', 'Cultural Events');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=$token";
            $mail->Body    = "
                <h2>Password Reset Request</h2>
                <p>You requested to reset your password. Click the link below to proceed:</p>
                <p><a href='$resetLink'>Reset Password</a></p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>This link will expire in 1 hour.</p>
            ";
            
            $mail->AltBody = "Password Reset Link: $resetLink";
            
            $mail->send();
            $_SESSION['reset_message'] = "Password reset link has been sent to your email address.";
        } catch (Exception $e) {
            $_SESSION['reset_error'] = "Message could not be sent. Please try again later.";
            error_log("Mailer Error: " . $e->getMessage());
        }
    } else {
        $_SESSION['reset_error'] = "No account found with that email address";
    }
    
    header("Location: login.php");
    exit();
}

// Regular Login Handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "<p style='color:red; text-align:center;'>Email and password are required!</p>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'user':
                    header("Location: user_dashboard.php");
                    break;
                default:
                    echo "<p style='color:red; text-align:center;'>Unknown user role!</p>";
                    break;
            }
            exit();
        } else {
            echo "<p style='color:red; text-align:center;'>Invalid email or password!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .container { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; width: 100%; max-width: 400px; }
        h2 { margin-bottom: 1.5rem; color: #2c3e50; font-size: 1.8rem; }
        input { width: 100%; padding: 0.8rem; margin: 0.8rem 0; border-radius: 8px; border: 1px solid #dfe6e9; font-size: 1rem; }
        button { width: 100%; padding: 0.8rem; border: none; border-radius: 8px; background-color: #3498db; color: white; font-size: 1rem; cursor: pointer; margin-top: 0.5rem; }
        .forgot-password-form { display: none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee; }
        .forgot-password-form.show { display: block; }
        .forgot-password-btn { background: none; border: none; color: #3498db; cursor: pointer; margin-top: 0.5rem; }
        .success-message { color: #27ae60; margin: 0.5rem 0; }
        .error-message { color: #e74c3c; margin: 0.5rem 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome Back</h2>
        
        <?php if (isset($_SESSION['reset_message'])): ?>
            <div class="success-message"><?= $_SESSION['reset_message'] ?></div>
            <?php unset($_SESSION['reset_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['reset_error'])): ?>
            <div class="error-message"><?= $_SESSION['reset_error'] ?></div>
            <?php unset($_SESSION['reset_error']); ?>
        <?php endif; ?>
        
        <form method="post" id="loginForm">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Sign In</button>
        </form>
        
        <button class="forgot-password-btn" onclick="toggleForgotPassword()">Forgot Password?</button>
        
        <div id="forgotPasswordForm" class="forgot-password-form">
            <h3>Reset Password</h3>
            <form method="post">
                <input type="email" name="email" placeholder="Your Email Address" required>
                <button type="submit" name="forgot_password">Send Reset Link</button>
            </form>
            <button class="forgot-password-btn" onclick="toggleForgotPassword()">Back to Login</button>
        </div>
        
        <p>New here? <a href="register.php">Create an account</a></p>
    </div>

    <script>
        function toggleForgotPassword() {
            const loginForm = document.getElementById('loginForm');
            const forgotForm = document.getElementById('forgotPasswordForm');
            
            loginForm.style.display = loginForm.style.display === 'none' ? 'block' : 'none';
            forgotForm.classList.toggle('show');
        }
    </script>
</body>
</html>