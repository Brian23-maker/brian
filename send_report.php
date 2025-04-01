<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the report data and email from the POST request
    $report_data = json_decode($_POST['report_data'], true);
    $email = $_POST['email'];

    // Validate inputs
    if (!$report_data || !is_array($report_data)) {
        die("❌ Error: Invalid or missing report data.");
    }

    if (empty($email)) {
        die("❌ Error: No email provided.");
    }

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // SMTP Debugging (Set to 0 in Production)
        $mail->SMTPDebug = 0; 

        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dayolizermaroa@gmail.com'; // Use environment variable
        $mail->Password = 'tnjs vwzg eist zuia';  // Use environment variable
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email Headers
        $mail->setFrom('dayolizermaroa@gmail.com', 'cultural_events');
        $mail->addAddress($email);

        // Email Content
        $mail->isHTML(false);
        $mail->Subject = 'User Comment Analysis Report';

        // Prepare the email body
        $body = "User Comment Analysis Report:\n\n";
        foreach ($report_data as $challenge => $count) {
            $body .= ucfirst($challenge) . ": " . $count . "\n";
        }

        $mail->Body = $body;

        // Send email
        if ($mail->send()) {
            echo "✅ Report sent successfully! Check your email.";
        } else {
            echo "❌ Failed to send report.";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $mail->ErrorInfo;
    }
}
?>

