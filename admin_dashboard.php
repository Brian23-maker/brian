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
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #ecf0f1;
            --dark: #2a2a40;
            --darker: #1e1e2f;
            --success: #2ecc71;
            --error: #e74c3c;
            --text: #333;
            --text-light: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text);
            min-height: 100vh;
            position: relative;
        }

        .background-pattern {
            position: fixed;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(52, 152, 219, 0.1) 2px, transparent 2px);
            background-size: 30px 30px;
            z-index: 0;
            pointer-events: none;
        }

        .navbar {
            width: 100%;
            background: var(--secondary);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            text-decoration: none;
        }

        .navbar-nav {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-nav a:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .navbar-nav a.logout {
            color: var(--error);
        }

        .navbar-nav a.logout:hover {
            background: var(--error);
            color: white;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1000px;
            margin: 5rem auto 2rem;
            position: relative;
            z-index: 1;
        }

        h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
        }

        .send-email {
            margin-top: 2rem;
        }

        h3 {
            color: var(--secondary);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .user-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            background: #f8f9fa;
        }

        .user-list label {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            margin: 0.5rem 0;
            background: white;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .user-list label:hover {
            background: #e8f4fc;
            transform: translateX(5px);
        }

        .user-list input[type="checkbox"] {
            margin-right: 1rem;
            accent-color: var(--primary);
        }

        textarea {
            width: 100%;
            min-height: 150px;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            resize: vertical;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        button[type="submit"] {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        button[type="submit"]:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        #loading-spinner {
            display: none;
            margin-top: 1rem;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 0.5rem;
                padding: 1rem 0.5rem;
            }
            
            .navbar-nav {
                width: 100%;
                justify-content: center;
            }
            
            .container {
                margin-top: 7rem;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <nav class="navbar">
        <a href="#" class="navbar-brand">Admin Dashboard</a>
        <div class="navbar-nav">
            <a href="send_sms.php"><i class="fas fa-sms"></i> Send SMS</a>
            <a href="admin_events.php"><i class="fas fa-calendar-alt"></i> Events</a>
            <a href="admin_comments.php"><i class="fas fa-comments"></i> Comments</a>
            <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="admin_venues.php"><i class="fas fa-map-marker-alt"></i> Venues</a>
            <a href="admin_transactions.php"><i class="fas fa-credit-card"></i> Transactions</a>
            <a href="general_report.php"><i class="fas fa-file-alt"></i> General Reports</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h2>

        <form method="post" class="send-email">
            <h3><i class="fas fa-envelope"></i> Send Email Notification to Users</h3>
            <div class="user-list">
                <?php foreach ($users as $user): ?>
                    <label>
                        <input type="checkbox" name="users[]" value="<?php echo $user['email']; ?>">
                        <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                    </label>
                <?php endforeach; ?>
            </div>
            <textarea name="message" placeholder="Enter your message here..." required></textarea>
            <button type="submit" name="send_email">
                <i class="fas fa-paper-plane"></i> Send Email Notification
            </button>
            <div id="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i> Sending emails...
            </div>
        </form>
    </div>

    <script>
        document.querySelector('form.send-email').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const spinner = document.getElementById('loading-spinner');
            
            // Disable button and show spinner
            submitButton.disabled = true;
            spinner.style.display = 'block';
            
            // Collect form data
            const formData = new FormData(form);
            
            // Simulate AJAX request (replace with actual fetch)
            setTimeout(() => {
                // In a real implementation, you would use fetch() to send the data
                console.log('Form data:', Object.fromEntries(formData));
                
                // Re-enable button and hide spinner
                submitButton.disabled = false;
                spinner.style.display = 'none';
                
                // Show success message
                alert('Emails sent successfully!');
                
                // Optional: Reset form
                form.reset();
            }, 2000);
        });
    </script>
</body>
</html>







