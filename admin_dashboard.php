<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch admin name (modify based on your DB structure)
require 'db_connect.php';
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin ? $admin['name'] : "Admin";
$admin_email = $admin ? $admin['email'] : "admin@example.com";

// Fetch all users from the database
$users = [];
try {
    $stmt = $conn->query("SELECT id, name, email FROM users WHERE role = 'user'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: #f8f9fa; /* Light neutral gray background */
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .navbar {
            width: 100%;
            background: #2c3e50; /* Dark blue-gray navbar */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .navbar-brand {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff; /* White text for contrast */
        }
        .navbar-nav {
            display: flex;
            gap: 20px;
        }
        .navbar-nav a {
            text-decoration: none;
            color: #ffffff; /* White text for links */
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .navbar-nav a:hover {
            background-color: #34495e; /* Slightly lighter blue-gray on hover */
        }
        .navbar-nav a.logout {
            color: #e74c3c; /* Red color for logout */
        }
        .navbar-nav a.logout:hover {
            background-color: #c0392b; /* Darker red on hover */
        }
        .container {
            background: #ffffff; /* White container */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 90%;
            max-width: 800px;
            margin-top: 80px;
        }
        .container h2 {
            margin-bottom: 20px;
            color: #444;
            font-size: 24px;
            font-weight: 600;
        }
        .send-email {
            margin-top: 30px;
            text-align: left;
        }
        .send-email h3 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #444;
        }
        .user-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .user-list label {
            display: flex;
            align-items: center;
            margin: 8px 0;
            padding: 8px;
            background: white;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .user-list label:hover {
            background-color: #f1f1f1;
        }
        .user-list input[type="checkbox"] {
            margin-right: 10px;
        }
        textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            resize: vertical;
            margin-bottom: 20px;
        }
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        button[type="submit"] {
            padding: 12px 20px;
            background-color: #28a745; /* Green submit button */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #218838; /* Darker green on hover */
        }
        #loading-spinner {
            display: none;
            margin-top: 15px;
            color: #007bff;
            font-size: 14px;
        }
        #loading-spinner i {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">Admin Dashboard</div>
        <div class="navbar-nav">
            <a href="send_sms.php">📩 Send SMS</a>
            <a href="admin_events.php">📅 Events</a>
            <a href="admin_comments.php">💬 Comments</a>
            <a href="admin_reports.php">📊 Reports</a> <!-- New link to reports page -->
            <a href="admin_venues.php">🏟️ Venues</a> <!-- New link -->
            <a href="admin_transactions.php">💳 Transactions</a>
            <a href="general_report.php"><i class="fas fa-chart-bar"></i> General Reports</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h2>

        <!-- Send Email Notification Form -->
        <form method="post" class="send-email">
            <h3>Send Email Notification to Users</h3>
            <div class="user-list">
                <?php foreach ($users as $user): ?>
                    <label>
                        <input type="checkbox" name="users[]" value="<?php echo $user['email']; ?>">
                        <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                    </label>
                <?php endforeach; ?>
            </div>
            <textarea name="message" placeholder="Enter your message here..." required></textarea>
            <button type="submit" name="send_email">📧 Send Email Notification</button>
        </form>

        <!-- Loading Spinner -->
        <div id="loading-spinner">
            Sending emails... <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>

    <script>
        // Handle the "Send Email Notification" button click
        document.querySelector('form.send-email').addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent the form from submitting normally

            // Show the loading spinner
            document.getElementById('loading-spinner').style.display = 'block';

            // Get selected users and message
            const formData = new FormData(this);

            // Send the data to send_email.php
            fetch('send_email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Hide the loading spinner
                document.getElementById('loading-spinner').style.display = 'none';

                // Display the success or error message
                alert(data);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send emails. Please try again.');
            });
        });
    </script>
</body>
</html>







