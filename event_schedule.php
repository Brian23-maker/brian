<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = $_POST['event_id'] ?? null;
    $activity = trim($_POST['activity']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if (!empty($event_id) && !empty($activity) && !empty($start_time) && !empty($end_time)) {
        $stmt = $conn->prepare("INSERT INTO event_schedule (event_id, activity, start_time, end_time) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$event_id, $activity, $start_time, $end_time])) {
            echo "<p class='success-message'>Schedule added successfully!</p>";
        } else {
            echo "<p class='error-message'>Failed to add schedule.</p>";
        }
    } else {
        echo "<p class='error-message'>Please fill in all fields.</p>";
    }
}

// Fetch all events (remove user_id filter)
$stmt = $conn->prepare("SELECT id, event_name FROM events");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General Styles */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('https://th.bing.com/th?id=OIP.Bbz6tv3dTfSHFE-M9r0y9QAAAA&w=204&h=306&c=8&rs=1&qlt=90&o=6&pid=3.1&rm=2');
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

        input, select, button {
            width: 100%;
            margin: 10px 0;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, button:focus {
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
        <h2>Add Event Schedule</h2>
        <form method="post">
            <select name="event_id" required>
                <?php if (!empty($events)): ?>
                    <option value="">-- Select an Event --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= htmlspecialchars($event['id']) ?>"><?= htmlspecialchars($event['event_name']) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>No events available</option>
                <?php endif; ?>
            </select>
            <input type="text" name="activity" placeholder="Activity Name" required>
            <input type="time" name="start_time" required>
            <input type="time" name="end_time" required>
            <button type="submit">Add Schedule</button>
        </form>
        <a href="user_dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>










