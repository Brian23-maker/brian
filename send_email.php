<?php
// send_email.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected users and message from the POST request
    $users = $_POST['users'] ?? [];
    $message = $_POST['message'] ?? '';

    // Validate the message
    if (empty($message)) {
        die("Message cannot be empty.");
    }

    // Validate the selected users
    if (empty($users)) {
        die("No users selected.");
    }

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP(); // Use SMTP
        $mail->Host = 'smtp.gmail.com'; // Gmail's SMTP server
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'dayolizermaroa@gmail.com'; // Your Gmail address
        $mail->Password = 'tnjs vwzg eist zuia'; // Your Google App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to
        $mail->SMTPDebug = 2; // Enable verbose debug output (set to 0 in production)

        // Sender
        $mail->setFrom('your-email@gmail.com', 'Admin');

        // Recipients
        foreach ($users as $userEmail) {
            if (filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($userEmail); // Add a recipient
            }
        }

        // Content
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = 'Notification from Admin';
        $mail->Body = $message;

        // Send the email
        $mail->send();
        echo "Emails sent successfully.";
    } catch (Exception $e) {
        echo "Failed to send emails. Error: " . $mail->ErrorInfo;
    }
} else {
    die("Invalid request method.");
}
?>>