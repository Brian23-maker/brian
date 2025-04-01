<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $venue_id = $_POST['venue_id'];

    $stmt = $conn->prepare("INSERT INTO events (user_id, event_name, description, event_date, venue_id) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $event_name, $description, $event_date, $venue_id])) {
        echo "<p class='success-message'>Event registered successfully!</p>";
    } else {
        echo "<p class='error-message'>Failed to register event.</p>";
    }
}

// Fetch available venues
$venues = $conn->query("SELECT * FROM venues")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General Styles */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('https://th.bing.com/th?id=OIP.gwAojbNZ67N0oaVAsqWeQwHaLH&w=204&h=306&c=8&rs=1&qlt=90&o=6&pid=3.1&rm=2');
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

        input, select, textarea, button {
            width: 100%;
            margin: 10px 0;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus, button:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
            outline: none;
        }

        textarea {
            resize: vertical;
            height: 100px;
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
        <h2>Register Event</h2>
        <form method="post">
            <input type="text" name="event_name" placeholder="Event Name" required>
            <textarea name="description" placeholder="Event Description" required></textarea>
            <input type="date" name="event_date" required>
            <select name="venue_id" required>
                <option value="">Select Venue</option>
                <?php foreach ($venues as $venue): ?>
                    <option value="<?= $venue['id'] ?>"><?= $venue['venue_name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Register Event</button>
        </form>
        <a href="user_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
