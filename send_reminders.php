<?php
require 'Mailer.php';
require 'db.php';

$today = date("Y-m-d");
$reminderDate = date('Y-m-d', strtotime($today . ' +1 day')); // Send reminder 1 day before

$sql = "SELECT email, event_name, event_date FROM event_registrations WHERE event_date = '$reminderDate'";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $subject = "Event Reminder: " . $row['event_name'];
    $message = "Reminder: You have an upcoming event (" . $row['event_name'] . ") on " . $row['event_date'] . ".";
    Mailer::sendEmail($row['email'], $subject, $message);
}
?>
