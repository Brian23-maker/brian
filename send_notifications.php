<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch users
$users = $conn->query("SELECT id, username FROM users WHERE role = 'user'")->fetchAll(PDO::FETCH_ASSOC);

// Handle notification sending
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST['message']);
    $user_id = $_POST['user_id'] ?? null;

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $message])) {
            echo "<p style='color:green;'>Notification sent successfully!</p>";
        } else {
            echo "<p style='color:red;'>Failed to send notification.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notifications</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 50%;
            min-width: 400px;
        }
        textarea, select, button {
            width: 100%;
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Send Notifications</h2>
        <form method="post">
            <textarea name="message" placeholder="Enter notification message..." required></textarea>
            <select name="user_id">
                <option value="">Send to all users</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Send Notification</button>
        </form>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>




