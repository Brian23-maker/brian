<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comments'])) {
    $comment_ids = $_POST['comments'];

    // Fetch selected comments
    $placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
    $stmt = $conn->prepare("SELECT comments.*, users.name AS user_name, users.email AS user_email 
                            FROM comments 
                            JOIN users ON comments.user_id = users.id 
                            WHERE comments.id IN ($placeholders)");
    $stmt->execute($comment_ids);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate the report
    $report = "<h1>User Comments Report</h1>";
    foreach ($comments as $comment) {
        $report .= "<p><strong>{$comment['user_name']}</strong>: {$comment['comment']}</p>";
        if (!empty($comment['admin_response'])) {
            $report .= "<p><em>Admin Response:</em> {$comment['admin_response']}</p>";
        }
        $report .= "<hr>";
    }

    // Send the report via email
    require 'vendor/autoload.php'; // Use PHPMailer or any other email library
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';
        $mail->Password = 'your_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('your_email@example.com', 'Admin');
        foreach ($comments as $comment) {
            $mail->addAddress($comment['user_email'], $comment['user_name']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'User Comments Report';
        $mail->Body = $report;

        $mail->send();
        echo 'Reports have been sent successfully!';
    } catch (Exception $e) {
        echo "Failed to send reports: {$mail->ErrorInfo}";
    }
} else {
    echo "No comments selected.";
}