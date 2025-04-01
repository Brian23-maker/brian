<?php
session_start();
require 'db_connect.php';

// Redirect if user is not logged in or is not a 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Ensure username is set in the session

// Fetch venues and events for commenting
try {
    $venues = $conn->query("SELECT * FROM venues")->fetchAll(PDO::FETCH_ASSOC);
    $events = $conn->query("SELECT * FROM events")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Failed to fetch data: " . $e->getMessage();
}

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $venue_id = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
    $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;

    if (empty($comment)) {
        $error_message = "Comment cannot be empty!";
    } else {
        try {
            // Insert comment with username
            $stmt = $conn->prepare("INSERT INTO comments (user_id, username, venue_id, event_id, comment, created_at) 
                                    VALUES (:user_id, :username, :venue_id, :event_id, :comment, NOW())");
            $stmt->execute([
                ':user_id' => $user_id,
                ':username' => $username, // Include username
                ':venue_id' => $venue_id,
                ':event_id' => $event_id,
                ':comment' => $comment
            ]);
            $success_message = "Comment submitted successfully!";
        } catch (PDOException $e) {
            $error_message = "Failed to submit comment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('https://th.bing.com/th?id=OIP.M-kldc_BwRpMsWgM53dhUwHaDt&w=350&h=175&c=8&rs=1&qlt=90&o=6&pid=3.1&rm=2');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
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
        textarea, select, button, input {
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
        <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>
        <a href="event_schedule.php">Create Event Schedule</a> |
        <a href="event_register.php">Register an Event</a> |
        <a href="view_venues.php">View Available Venues</a> |
        <a href="update_phone.php">Update Phone Number</a> |
        <a href="logout.php">Logout</a>

        <h3>Leave a Comment</h3>
        <?php if (isset($success_message)): ?>
            <p style="color: green;"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <form method="post" action="user_dashboard.php">
            <textarea name="comment" placeholder="Write your comment about a venue or event..." required></textarea>
            <select name="venue_id">
                <option value="">Select Venue (optional)</option>
                <?php foreach ($venues as $venue): ?>
                    <option value="<?= htmlspecialchars($venue['id']) ?>"><?= htmlspecialchars($venue['venue_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="event_id">
                <option value="">Select Event (optional)</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?= htmlspecialchars($event['id']) ?>"><?= htmlspecialchars($event['event_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Submit Comment</button>
        </form>
    </div>
</body>
</html>

