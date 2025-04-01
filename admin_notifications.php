<?php
require 'Mailer.php';
require 'db.php'; // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Get all users
    $sql = "SELECT email FROM users";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        Mailer::sendEmail($row['email'], $subject, $message);
    }

    echo "Notification sent successfully!";
}
?>

<form method="POST">
    <input type="text" name="subject" placeholder="Email Subject" required><br>
    <textarea name="message" placeholder="Email Message" required></textarea><br>
    <button type="submit">Send Notification</button>
</form>
