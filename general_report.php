 <?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch all data for the general report
$reportData = [];
$userEmails = []; // Array to store user emails
try {
    // Users statistics
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
    $usersCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // New users this month
    $stmt = $conn->query("SELECT COUNT(*) as new_users FROM users WHERE role = 'user' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $newUsers = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Events statistics
    $stmt = $conn->query("SELECT COUNT(*) as total_events FROM events");
    $eventsCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Upcoming events
    $stmt = $conn->query("SELECT COUNT(*) as upcoming_events FROM events WHERE event_date >= NOW()");
    $upcomingEvents = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Transactions summary
    $stmt = $conn->query("SELECT COUNT(*) as total_transactions, SUM(amount) as total_revenue FROM transactions");
    $transactionsData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Revenue by month
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as transactions,
            SUM(amount) as revenue
        FROM transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $revenueByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch all user emails
    $stmt = $conn->query("SELECT id, email FROM users WHERE role = 'user'");
    $userEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $reportData = [
        'users' => array_merge($usersCount, $newUsers),
        'events' => array_merge($eventsCount, $upcomingEvents),
        'transactions' => array_merge($transactionsData, ['revenue_by_month' => $revenueByMonth]),
        'generated_at' => date('Y-m-d H:i:s')
    ];
    
} catch (PDOException $e) {
    die("Failed to fetch report data: " . $e->getMessage());
}

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'] ?? 'pdf'; // Default to PDF now
    
    switch ($report_type) {
        case 'pdf':
            generatePDFReport($reportData);
            break;
            
        case 'csv':
            generateCSVReport($reportData);
            break;
            
        case 'email':
            if (!empty($_POST['email_recipients'])) {
                $recipients = is_array($_POST['email_recipients']) ? $_POST['email_recipients'] : [$_POST['email_recipients']];
                $validRecipients = [];
                
                foreach ($recipients as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $validRecipients[] = $email;
                    }
                }
                
                if (!empty($validRecipients)) {
                    sendEmailReport($reportData, $validRecipients);
                    $_SESSION['report_message'] = "Report sent successfully to " . count($validRecipients) . " recipients";
                } else {
                    $_SESSION['report_error'] = "No valid email addresses selected";
                }
            } else {
                $_SESSION['report_error'] = "Please select at least one recipient";
            }
            header("Location: general_report.php");
            exit();
    }
}

function generateReportHTML($data) {
    ob_start(); ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Cultural Events Report - <?= date('Y-m-d') ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; text-align: center; }
            h2 { color: #444; border-bottom: 1px solid #eee; padding-bottom: 5px; }
            .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
            .summary-card { background: #f8f9fa; border-radius: 8px; padding: 20px; text-align: center; }
            .summary-card h3 { margin-top: 0; color: #444; }
            .summary-card .value { font-size: 24px; font-weight: bold; margin: 10px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            th { background-color: #f2f2f2; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .footer { margin-top: 30px; font-size: 0.8em; text-align: center; color: #666; }
            .logo { text-align: center; margin-bottom: 20px; }
            .section { margin-bottom: 40px; }
        </style>
    </head>
    <body>
        <div class="logo">
            <h1>Cultural Events System Report</h1>
        </div>
        
        <div class="header">
            <p>Generated on: <?= $data['generated_at'] ?></p>
        </div>
        
        <div class="section">
            <h2>User Statistics</h2>
            <div class="summary">
                <div class="summary-card">
                    <h3>Total Users</h3>
                    <div class="value"><?= $data['users']['total_users'] ?></div>
                </div>
                <div class="summary-card">
                    <h3>New Users (30 days)</h3>
                    <div class="value"><?= $data['users']['new_users'] ?></div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Event Statistics</h2>
            <div class="summary">
                <div class="summary-card">
                    <h3>Total Events</h3>
                    <div class="value"><?= $data['events']['total_events'] ?></div>
                </div>
                <div class="summary-card">
                    <h3>Upcoming Events</h3>
                    <div class="value"><?= $data['events']['upcoming_events'] ?></div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Financial Statistics</h2>
            <div class="summary">
                <div class="summary-card">
                    <h3>Total Transactions</h3>
                    <div class="value"><?= $data['transactions']['total_transactions'] ?></div>
                </div>
                <div class="summary-card">
                    <h3>Total Revenue</h3>
                    <div class="value">$<?= number_format($data['transactions']['total_revenue'] ?? 0, 2) ?></div>
                </div>
            </div>
            
            <h3>Revenue by Month (Last 6 Months)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Transactions</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['transactions']['revenue_by_month'] as $month): ?>
                    <tr>
                        <td><?= $month['month'] ?></td>
                        <td><?= $month['transactions'] ?></td>
                        <td>$<?= number_format($month['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>© <?= date('Y') ?> Cultural Events System. All rights reserved.</p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function generatePDFReport($data) {
    $dompdf = new Dompdf();
    $dompdf->loadHtml(generateReportHTML($data));
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("cultural_events_report_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
    exit();
}

function generateCSVReport($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cultural_events_report_' . date('Ymd') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, ['Cultural Events Report - Generated at: ' . $data['generated_at']]);
    fputcsv($output, []);
    
    // User statistics
    fputcsv($output, ['USER STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Users', $data['users']['total_users']]);
    fputcsv($output, ['New Users (30 days)', $data['users']['new_users']]);
    fputcsv($output, []);
    
    // Event statistics
    fputcsv($output, ['EVENT STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Events', $data['events']['total_events']]);
    fputcsv($output, ['Upcoming Events', $data['events']['upcoming_events']]);
    fputcsv($output, []);
    
    // Financial statistics
    fputcsv($output, ['FINANCIAL STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Transactions', $data['transactions']['total_transactions']]);
    fputcsv($output, ['Total Revenue', '$' . number_format($data['transactions']['total_revenue'], 2)]);
    fputcsv($output, []);
    fputcsv($output, ['Revenue by Month', 'Transactions', 'Revenue']);
    foreach ($data['transactions']['revenue_by_month'] as $month) {
        fputcsv($output, [$month['month'], $month['transactions'], '$' . number_format($month['revenue'], 2)]);
    }
    
    fclose($output);
    exit();
}

function sendEmailReport($data, $emails) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dayolizermaroa@gmail.com';
        $mail->Password   = 'tnjs vwzg eist zuia';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        
        $mail->setFrom('dayolizermaroa@gmail.com', 'Cultural Events System');
        
        // Add all recipients
        foreach ($emails as $email) {
            $mail->addAddress($email);
        }
        
        // Attach PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml(generateReportHTML($data));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfString = $dompdf->output();
        $mail->addStringAttachment($pdfString, 'cultural_events_report.pdf');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Cultural Events Report - ' . date('Y-m-d');
        $mail->Body    = '
            <h1>Cultural Events System Report</h1>
            <p>Please find attached the latest system report with key metrics about users, events, and transactions.</p>
            
            <h2>Quick Summary</h2>
            <ul>
                <li><strong>Total Users:</strong> ' . $data['users']['total_users'] . '</li>
                <li><strong>New Users (30 days):</strong> ' . $data['users']['new_users'] . '</li>
                <li><strong>Total Events:</strong> ' . $data['events']['total_events'] . '</li>
                <li><strong>Upcoming Events:</strong> ' . $data['events']['upcoming_events'] . '</li>
                <li><strong>Total Revenue:</strong> $' . number_format($data['transactions']['total_revenue'] ?? 0, 2) . '</li>
            </ul>
            
            <p>This is an automated message. Please do not reply.</p>
        ';
        
        $mail->send();
    } catch (Exception $e) {
        $_SESSION['report_error'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cultural Events Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .admin-nav {
            background: #4e73df;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .nav-brand {
            color: white;
            font-weight: bold;
            font-size: 1.2em;
            text-decoration: none;
        }
        .nav-brand i {
            margin-right: 10px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .nav-links a.active {
            background: rgba(255,255,255,0.3);
        }
        .nav-links i {
            margin-right: 5px;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .report-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-top: 20px;
        }
        .report-type-selector {
            display: flex;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .report-type-selector label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .report-type-selector label:hover {
            background: #e9ecef;
        }
        .report-type-selector input[type="radio"] {
            margin: 0;
        }
        .email-field {
            margin-top: 15px;
            display: none;
            width: 100%;
        }
        .email-field.visible {
            display: block;
        }
        .email-field .select2-container {
            width: 100% !important;
            max-width: 600px;
        }
        .select2-selection {
            min-height: 42px;
            padding: 8px;
            border: 1px solid #ddd !important;
        }
        .btn-generate {
            background: #4e73df;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        .btn-generate:hover {
            background: #3a5bc7;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .report-type-selector {
                flex-direction: column;
                gap: 10px;
            }
            .nav-container {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-links {
                margin-top: 15px;
                width: 100%;
            }
            .nav-links a {
                display: block;
                margin: 5px 0;
                padding: 8px 0;
            }
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="nav-brand">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
            <div class="nav-links">
                <a href="admin_dashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="general_report.php" class="active"><i class="fas fa-chart-pie"></i> Reports</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1><i class="fas fa-chart-pie"></i> Cultural Events Report</h1>
        
        <?php if (isset($_SESSION['report_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['report_message'] ?>
            </div>
            <?php unset($_SESSION['report_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['report_error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['report_error'] ?>
            </div>
            <?php unset($_SESSION['report_error']); ?>
        <?php endif; ?>
        
        <div class="report-container">
            <form method="post">
                <h2><i class="fas fa-cog"></i> Generate Report</h2>
                
                <div class="report-type-selector">
                    <label>
                        <input type="radio" name="report_type" value="pdf" checked> 
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </label>
                    <label>
                        <input type="radio" name="report_type" value="csv"> 
                        <i class="fas fa-file-csv"></i> Download CSV
                    </label>
                    <label>
                        <input type="radio" name="report_type" value="email"> 
                        <i class="fas fa-envelope"></i> Email Report
                    </label>
                </div>
                
                <div id="emailField" class="email-field">
                    <label for="email_recipients">Select Recipients:</label>
                    <select name="email_recipients[]" id="email_recipients" class="form-control" multiple="multiple" style="width: 100%">
                        <?php foreach ($userEmails as $user): ?>
                            <option value="<?= htmlspecialchars($user['email']) ?>">
                                <?= htmlspecialchars($user['email']) ?> (User ID: <?= $user['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="generate_report" class="btn-generate">
                    <i class="fas fa-sync-alt"></i> Generate Report
                </button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#email_recipients').select2({
                placeholder: "Select email recipients",
                allowClear: true
            });
            
            // Toggle email field visibility
            $('input[name="report_type"]').change(function() {
                $('#emailField').toggleClass('visible', this.value === 'email');
            });
            
            // Set initial state
            if ($('input[name="report_type"]:checked').val() === 'email') {
                $('#emailField').addClass('visible');
            }
        });
    </script>
</body>
</html>