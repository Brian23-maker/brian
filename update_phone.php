<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch current user data
$stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone']);

    // Validate phone number format
    if (!preg_match('/^\+254[7-9][0-9]{8}$/', $phone)) {
        $error = "Invalid phone number format! Use +2547XXXXXXXX.";
    } else {
        // Update phone number
        $updateStmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
        if ($updateStmt->execute([$phone, $user_id])) {
            $success = "Phone number updated successfully!";
        } else {
            $error = "Failed to update phone number!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Phone Number</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General Styles */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('https://th.bing.com/th?id=OIP.NgOQpDk_zH2xDPfHfRv7jAHaE8&w=306&h=204&c=8&rs=1&qlt=90&o=6&pid=3.1&rm=2');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: 'Arial', sans-serif;
        }

        /* Container Styles */
        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form Styles */
        h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }

        input, button {
            width: 100%;
            margin: 10px 0;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus, button:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
            outline: none;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        /* Link Styles */
        a {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #0056b3;
        }

        /* Message Styles */
        .success-message {
            color: #28a745;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            animation: slideIn 0.5s ease-in-out;
        }

        .error-message {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            animation: slideIn 0.5s ease-in-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Phone Number</h2>
        <?php if ($success): ?>
            <p class="success-message"><?= $success ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-message"><?= $error ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="phone" placeholder="Enter Phone Number (e.g., +2547XXXXXXXX)" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            <button type="submit">Update</button>
        </form>
        <a href="user_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
