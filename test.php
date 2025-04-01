<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
require 'vendor/autoload.php'; // Ensure Composer autoload is included

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch admin email (modify based on your DB structure)
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_email = $admin ? $admin['email'] : "admin@example.com";

// Handle Approve, Disapprove, and Delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['event_id'])) {
        $event_id = $_POST['event_id'];
        $action = $_POST['action'];

        // Fetch event details for email notification
        $stmt = $conn->prepare("SELECT user_id, event_name, event_date, description FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            $user_id = $event['user_id'];
            $event_name = $event['event_name'];
            $event_date = $event['event_date'];
            $description = $event['description'];

            // Fetch user email
            $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_email = $user['email'];

            // Update event status
            if ($action === 'approve') {
                $status = 'approved';
                $popup_message = "Event has been approved and confirmation email sent to the user.";
            } elseif ($action === 'disapprove') {
                $status = 'disapproved';
                $popup_message = "Event has been disapproved and confirmation email sent to the user.";
            } elseif ($action === 'delete') {
                $status = 'deleted';
            }

            if ($action !== 'delete') {
                $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
                $stmt->execute([$status, $event_id]);
            } else {
                $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$event_id]);
            }

            // Send email notification using PHPMailer
            if ($action !== 'delete') {
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = 'dayolizermaroa@gmail.com'; // Your Gmail address
                    $mail->Password = 'tnjs vwzg eist zuia'; // Your Gmail app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom($admin_email, 'Admin');
                    $mail->addAddress($user_email); // Add a recipient

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = "Your Event Has Been $status";
                    $mail->Body = "
                        <h2>Event Status Update</h2>
                        <p>Dear User,</p>
                        <p>Your event <strong>$event_name</strong> scheduled on <strong>$event_date</strong> has been <strong>$status</strong> by the admin.</p>
                        <p><strong>Description:</strong> $description</p>
                        <p>If you have any questions, please contact us at +254791338643.</p>
                        <p>Thank you.</p>
                    ";

                    if ($mail->send()) {
                        // Set popup message in session
                        $_SESSION['popup_message'] = $popup_message;
                    } else {
                        $_SESSION['popup_message'] = "Failed to send email.";
                    }
                } catch (Exception $e) {
                    $_SESSION['popup_message'] = "Failed to send email: " . $mail->ErrorInfo;
                }
            }
        }
    }
}

// Fetch all events from the database
$events = [];
try {
    $stmt = $conn->query("SELECT events.id, events.event_name, events.event_date, events.description, events.status, users.name AS user_name, users.email AS user_email FROM events JOIN users ON events.user_id = users.id");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch events: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            text-align: left;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .container h3 {
            margin-bottom: 20px;
            color: #444;
            font-size: 24px;
            font-weight: 600;
            text-align: left;
        }
        .back-button {
            margin-top: 20px;
            text-align: center;
        }
        .back-button a {
            text-decoration: none;
            padding: 10px 20px;
            background-color: #2c3e50;
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-button a:hover {
            background-color: #34495e;
        }
        .event-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
            overflow-x: auto;
        }
        .event-table th,
        .event-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .event-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: 600;
        }
        .event-table tr:hover {
            background-color: #f1f1f1;
        }
        .event-table .actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap; /* Allow buttons to wrap to the next line if needed */
        }
        .event-table .actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            white-space: nowrap; /* Prevent button text from wrapping */
        }
        .event-table .actions button.approve {
            background-color: #28a745;
            color: white;
        }
        .event-table .actions button.approve:hover {
            background-color: #218838;
        }
        .event-table .actions button.disapprove {
            background-color: #dc3545;
            color: white;
        }
        .event-table .actions button.disapprove:hover {
            background-color: #c82333;
        }
        .event-table .actions button.delete {
            background-color: #ffc107;
            color: black;
        }
        .event-table .actions button.delete:hover {
            background-color: #e0a800;
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
    <div class="container">
        <h3>Registered Events</h3>

        <!-- Display popup message -->
        <?php if (isset($_SESSION['popup_message'])): ?>
            <script>
                alert("<?php echo $_SESSION['popup_message']; ?>");
            </script>
            <?php unset($_SESSION['popup_message']); ?>
        <?php endif; ?>

        <table class="event-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Event</th>
                    <th style="width: 20%;">User</th>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 20%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($event['event_name']); ?></strong><br>
                            <span><?php echo htmlspecialchars($event['event_date']); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($event['user_name']); ?><br>
                            <span><?php echo htmlspecialchars($event['user_email']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($event['description']); ?></td>
                        <td><?php echo htmlspecialchars($event['status']); ?></td>
                        <td class="actions">
                            <form method="post" style="display:inline;" onsubmit="showLoadingSpinner(this)">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="approve">Approve</button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="showLoadingSpinner(this)">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <input type="hidden" name="action" value="disapprove">
                                <button type="submit" class="disapprove">Disapprove</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Loading Spinner -->
        <div id="loading-spinner">
            Sending email... <i class="fas fa-spinner fa-spin"></i>
        </div>

        <!-- Back to Admin Dashboard Button -->
        <div class="back-button">
            <a href="admin_dashboard.php">← Back to Admin Dashboard</a>
        </div>
    </div>

    <script>
        function showLoadingSpinner(form) {
            document.getElementById('loading-spinner').style.display = 'block';
            form.submit();
        }
    </script>
</body>
</html>