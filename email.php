<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

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

    // Sender
    $mail->setFrom('your_email@gmail.com', 'Your Name');

    // Recipient
    $mail->addAddress('recipient@example.com');

    // Content
    $mail->isHTML(false);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email.';

    // Send the email
    if ($mail->send()) {
        echo "Notification successfully sent!"; // Updated success message
    } else {
        echo "Failed to send notification."; // Updated failure message
    }
} catch (Exception $e) {
    echo "Error: " . $mail->ErrorInfo; // Error message
}
?>