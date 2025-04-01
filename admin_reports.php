<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include Composer's autoloader
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db_connect.php'; // Ensure this file correctly connects to the database

// Fetch all comments from the database
$comments = [];
try {
    $stmt = $conn->query("SELECT comment FROM comments");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Check if comments were fetched
    if (empty($comments)) {
        die("No comments found in the database.");
    }
} catch (PDOException $e) {
    die("Failed to fetch comments: " . $e->getMessage());
}

// Categorize comments into themes
$categories = [
    'Venue Quality' => 0,
    'Event Organization' => 0,
    'Staff Behavior' => 0,
    'Other' => 0
];

foreach ($comments as $comment) {
    $text = strtolower($comment['comment']); // Use the correct column name

    if (strpos($text, 'venue') !== false) {
        $categories['Venue Quality']++;
    } elseif (strpos($text, 'event') !== false || strpos($text, 'organized') !== false) {
        $categories['Event Organization']++;
    } elseif (strpos($text, 'staff') !== false || strpos($text, 'helpful') !== false) {
        $categories['Staff Behavior']++;
    } else {
        $categories['Other']++;
    }
}

// Fetch registered users' emails
$users = [];
try {
    $stmt = $conn->query("SELECT email FROM users WHERE role = 'user'");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch users: " . $e->getMessage());
}

// Handle form submission to send emails
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $selectedEmails = $_POST['emails'] ?? [];
    $reportContent = generateReportContent($categories);

    if (!empty($selectedEmails)) {
        foreach ($selectedEmails as $email) {
            if (sendEmail($email, "User Comment Report", $reportContent)) {
                echo "<script>alert('Report sent successfully to $email!');</script>";
            } else {
                echo "<script>alert('Failed to send report to $email.');</script>";
            }
        }
    } else {
        echo "<script>alert('No emails selected.');</script>";
    }
}

// Function to generate the report content
function generateReportContent($categories) {
    // Report content
    $html = "<h1>User Comment Report</h1>";
    $html .= "<table border='1' cellpadding='10'>";
    $html .= "<tr><th>Category</th><th>Count</th></tr>";
    foreach ($categories as $category => $count) {
        $html .= "<tr><td>$category</td><td>$count</td></tr>";
    }
    $html .= "</table>";

    // Add guidelines and measures
    $html .= "<h2>Guidelines and Measures</h2>";
    $html .= "<ul>";
    $html .= "<li><strong>Venue Quality:</strong> We are working with venue management to improve lighting, cleanliness, and overall facilities.</li>";
    $html .= "<li><strong>Event Organization:</strong> We are streamlining event schedules and ensuring better coordination with organizers.</li>";
    $html .= "<li><strong>Staff Behavior:</strong> Staff training programs are being implemented to improve customer service.</li>";
    $html .= "<li><strong>Other Issues:</strong> We are reviewing all feedback to address any additional concerns.</li>";
    $html .= "</ul>";

    return $html;
}

// Function to generate a PDF from HTML content
function generatePdf($html) {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    return $dompdf->output();
}

// Function to send email using PHPMailer
function sendEmail($to, $subject, $content) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'dayolizermaroa@gmail.com'; // Your Gmail address
        $mail->Password = 'tnjs vwzg eist zuia'; // Your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'Admin'); // Sender email and name
        $mail->addAddress($to); // Recipient email

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $content;

        // Attach PDF
        $pdfContent = generatePdf($content);
        $mail->addStringAttachment($pdfContent, 'User_Comment_Report.pdf');

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
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
            justify-content: center;
        }
        .navbar {
            width: 100%;
            background: #2c3e50;
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
            color: #ffffff;
        }
        .navbar-nav {
            display: flex;
            gap: 20px;
        }
        .navbar-nav a {
            text-decoration: none;
            color: #ffffff;
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .navbar-nav a:hover {
            background-color: #34495e;
        }
        .navbar-nav a.logout {
            color: #e74c3c;
        }
        .navbar-nav a.logout:hover {
            background-color: #c0392b;
        }
        .container {
            background: #ffffff;
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
        .chart-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .user-list {
            margin: 20px 0;
            text-align: left;
        }
        .user-list label {
            display: block;
            margin: 10px 0;
        }
        .print-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .print-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">Admin Reports</div>
        <div class="navbar-nav">
            <a href="admin_dashboard.php">🏠 Dashboard</a>
            <a href="admin_comments.php">💬 Comments</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h2>User Comment Reports</h2>
        <div class="chart-container">
            <canvas id="commentChart"></canvas> <!-- Pie Chart Canvas -->
        </div>

        <!-- Printable Report -->
        <div id="printableReport" style="display: none;">
            <h1>User Comment Report</h1>
            <table border="1" cellpadding="10">
                <tr><th>Category</th><th>Count</th></tr>
                <?php foreach ($categories as $category => $count): ?>
                    <tr><td><?php echo $category; ?></td><td><?php echo $count; ?></td></tr>
                <?php endforeach; ?>
            </table>
            <h2>Guidelines and Measures</h2>
            <ul>
                <li><strong>Venue Quality:</strong> We are working with venue management to improve lighting, cleanliness, and overall facilities.</li>
                <li><strong>Event Organization:</strong> We are streamlining event schedules and ensuring better coordination with organizers.</li>
                <li><strong>Staff Behavior:</strong> Staff training programs are being implemented to improve customer service.</li>
                <li><strong>Other Issues:</strong> We are reviewing all feedback to address any additional concerns.</li>
            </ul>
        </div>

        <!-- Print Button -->
        <button class="print-button" onclick="printReport()">🖨️ Print Report</button>

        <!-- Send Email Form -->
        <form method="post" class="user-list">
            <h3>Send Report to Users</h3>
            <?php foreach ($users as $user): ?>
                <label>
                    <input type="checkbox" name="emails[]" value="<?php echo $user['email']; ?>">
                    <?php echo $user['email']; ?>
                </label>
            <?php endforeach; ?>
            <button type="submit" name="send_email">📧 Send Report</button>
        </form>
    </div>

    <script>
        // Prepare data for the pie chart
        const categories = <?php echo json_encode($categories); ?>;

        const labels = Object.keys(categories);
        const data = Object.values(categories);

        // Create the pie chart
        const ctx = document.getElementById('commentChart').getContext('2d');
        const commentChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Comments',
                    data: data,
                    backgroundColor: [
                        '#FF6384', // Red
                        '#36A2EB', // Blue
                        '#FFCE56', // Yellow
                        '#4BC0C0'  // Teal
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'User Comments Distribution'
                    }
                }
            }
        });

        // Function to print the report
        function printReport() {
            const printContent = document.getElementById('printableReport').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload(); // Reload to restore the original content
        }
    </script>
</body>
</html>