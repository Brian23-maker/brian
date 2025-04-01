<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include PHPMailer

class Mailer {
    public static function sendEmail($to, $subject, $body) {
        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Change if using a different email provider
            $mail->SMTPAuth = true;
            $mail->Username = 'dayolizermaroa@gmail.com'; // Your email
            $mail->Password = 'tnjs vwzg eist zuia'; // Use App Password if using Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email Content
            $mail->setFrom('your-email@gmail.com', 'cultural_events');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(true);

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
