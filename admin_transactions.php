<?php
session_start();
require 'vendor/autoload.php';
require 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch admin details
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin['name'] ?? 'Admin';

// Handle transaction approval
if (isset($_POST['approve_transaction'])) {
    $transaction_id = $_POST['transaction_id'];
    
    try {
        // Fetch transaction details
        $stmt = $conn->prepare("
            SELECT t.*, u.name as user_name, u.email as user_email 
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            throw new Exception("Transaction not found");
        }

        // Update transaction status
        $update_stmt = $conn->prepare("
            UPDATE transactions 
            SET status = 'approved', approved_by = ?, approved_at = NOW() 
            WHERE id = ?
        ");
        $update_stmt->execute([$admin_id, $transaction_id]);

        // Send approval email using PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dayolizermaroa@gmail.com'; // Your Gmail
            $mail->Password = 'tnjs vwzg eist zuia'; // Your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('dayolizermaroa@gmail.com', 'Payment System');
            $mail->addAddress($transaction['user_email'], $transaction['user_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Payment Approved - Transaction #' . $transaction['transaction_ref'];
            
            $mail->Body = "
                <h2>Payment Approved</h2>
                <p>Dear {$transaction['user_name']},</p>
                <p>We're pleased to inform you that your payment has been approved.</p>
                
                <h3>Transaction Details</h3>
                <table border='0' cellpadding='5'>
                    <tr><td><strong>Reference:</strong></td><td>#{$transaction['transaction_ref']}</td></tr>
                    <tr><td><strong>Amount:</strong></td><td>$" . number_format($transaction['amount'], 2) . "</td></tr>
                    <tr><td><strong>Method:</strong></td><td>" . ucfirst($transaction['payment_method']) . "</td></tr>
                    <tr><td><strong>Status:</strong></td><td>Approved</td></tr>
                    <tr><td><strong>Approved On:</strong></td><td>" . date('F j, Y \a\t g:i A') . "</td></tr>
                </table>
                
                <p>If you have any questions, please contact our support team.</p>
                <p>Best regards,<br>Admin Team</p>
            ";
            
            $mail->AltBody = "Dear {$transaction['user_name']},\n\nYour payment of $" . number_format($transaction['amount'], 2) . " (Reference: #{$transaction['transaction_ref']}) has been approved.\n\nThank you,\nAdmin Team";
            
            $mail->send();
            
            $_SESSION['success_message'] = "Transaction #{$transaction['transaction_ref']} approved successfully! Notification email sent to {$transaction['user_email']}.";
        } catch (Exception $e) {
            throw new Exception("Failed to send email notification: " . $e->getMessage());
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    header("Location: admin_transactions.php");
    exit();
}

// Fetch all transactions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$transactions = [];
$total_transactions = 0;

try {
    // Get total count
    $stmt = $conn->query("SELECT COUNT(*) FROM transactions");
    $total_transactions = $stmt->fetchColumn();
    
    // Get paginated transactions
    $stmt = $conn->prepare("
        SELECT t.*, u.name as user_name, u.email as user_email 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT :offset, :per_page
    ");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch transactions: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4fc3f7;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gray-color: #6c757d;
            --white-color: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .header h2 {
            color: var(--secondary-color);
            font-size: 28px;
        }
        
        .search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-filter input, .search-filter select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-filter button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-filter button:hover {
            background-color: var(--secondary-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: var(--white-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
        
        .status-pending {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .status-approved {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-declined {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #218838;
        }
        
        .btn-view {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #039be5;
        }
        
        .transaction-details {
            display: none;
        }
        
        .transaction-details td {
            padding: 20px;
            background-color: #f8fafc;
        }
        
        .details-content {
            padding: 15px;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .details-content h4 {
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        
        .details-content p {
            margin-bottom: 8px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }
        
        .pagination a {
            padding: 8px 15px;
            border: 1px solid #ddd;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background-color: #f1f1f1;
        }
        
        .pagination a.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .navbar {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .navbar-brand:hover {
            opacity: 0.8;
        }
        
        .navbar-nav {
            display: flex;
            gap: 20px;
        }
        
        .navbar-nav a {
            color: white;
            text-decoration: none;
            font-size: 15px;
            transition: opacity 0.3s;
        }
        
        .navbar-nav a:hover {
            opacity: 0.8;
        }
        
        .navbar-nav a.active {
            font-weight: 600;
            border-bottom: 2px solid white;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand" onclick="window.location.href='admin_dashboard.php'">
            <i class="fas fa-arrow-left"></i> Transaction Management System
        </div>
        <div class="navbar-nav">
            <a href="admin_dashboard.php">🏠 Dashboard</a>
            <a href="admin_transactions.php" class="active">💳 Transactions</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h2>Transaction Management</h2>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="search-filter">
            <input type="text" id="search" placeholder="Search transactions...">
            <select id="status-filter">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="declined">Declined</option>
            </select>
            <button onclick="filterTransactions()">Filter</button>
        </div>
        
        <table id="transactions-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($transaction['transaction_ref']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($transaction['user_name']); ?><br>
                            <small><?php echo htmlspecialchars($transaction['user_email']); ?></small>
                        </td>
                        <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                        <td><?php echo ucfirst($transaction['payment_method']); ?></td>
                        <td class="status-<?php echo $transaction['status']; ?>">
                            <?php echo ucfirst($transaction['status']); ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></td>
                        <td>
                            <?php if ($transaction['status'] == 'pending'): ?>
                                <form method="post" class="approve-form">
                                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                    <button type="submit" name="approve_transaction" class="btn btn-approve">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button class="btn btn-view" onclick="toggleDetails(<?php echo $transaction['id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <tr id="details-<?php echo $transaction['id']; ?>" class="transaction-details">
                        <td colspan="7">
                            <div class="details-content">
                                <h4>Transaction Details</h4>
                                <p><strong>Reference:</strong> #<?php echo htmlspecialchars($transaction['transaction_ref']); ?></p>
                                <p><strong>User:</strong> <?php echo htmlspecialchars($transaction['user_name']); ?> (<?php echo htmlspecialchars($transaction['user_email']); ?>)</p>
                                <p><strong>Amount:</strong> $<?php echo number_format($transaction['amount'], 2); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo ucfirst($transaction['payment_method']); ?></p>
                                <p><strong>Status:</strong> <span class="status-<?php echo $transaction['status']; ?>"><?php echo ucfirst($transaction['status']); ?></span></p>
                                <p><strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($transaction['created_at'])); ?></p>
                                <?php if ($transaction['status'] == 'approved'): ?>
                                    <p><strong>Approved By:</strong> <?php echo htmlspecialchars($admin_name); ?></p>
                                    <p><strong>Approved On:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($transaction['approved_at'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($transaction['details'])): ?>
                                    <p><strong>Additional Details:</strong></p>
                                    <pre><?php echo htmlspecialchars($transaction['details']); ?></pre>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php
            $total_pages = ceil($total_transactions / $per_page);
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a href="?page=<?php echo $i; ?>" <?php if ($i == $page) echo 'class="active"'; ?>>
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDetails(transactionId) {
            const detailsRow = document.getElementById(`details-${transactionId}`);
            detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
        }

        function filterTransactions() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value.toLowerCase();
            const rows = document.querySelectorAll('#transactions-table tbody tr');
            
            rows.forEach(row => {
                if (row.classList.contains('transaction-details')) {
                    // Hide all details rows initially
                    row.style.display = 'none';
                    return;
                }
                
                const cells = row.querySelectorAll('td');
                let showRow = true;
                
                // Search filter
                if (searchTerm) {
                    let rowText = '';
                    cells.forEach(cell => {
                        rowText += cell.textContent.toLowerCase() + ' ';
                    });
                    
                    if (!rowText.includes(searchTerm)) {
                        showRow = false;
                    }
                }
                
                // Status filter
                if (statusFilter && showRow) {
                    const statusCell = row.querySelector('.status-pending, .status-approved, .status-declined');
                    if (!statusCell || !statusCell.classList.contains(`status-${statusFilter}`)) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Add event listeners for Enter key in search
        document.getElementById('search').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                filterTransactions();
            }
        });

        // Confirm before approving
        document.querySelectorAll('.approve-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to approve this transaction? An email will be sent to the user.')) {
                    e.preventDefault();
                }
            });
        });

        // Add keyboard shortcut (Alt + H) to go back to dashboard
        document.addEventListener('keydown', function(e) {
            if (e.altKey && e.key.toLowerCase() === 'h') {
                window.location.href = 'admin_dashboard.php';
            }
        });
    </script>
</body>
</html>