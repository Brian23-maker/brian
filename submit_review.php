<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    exit("Unauthorized access");
}

$user_id = $_SESSION['user_id'];
$venue_name = trim($_POST['venue_name']);
$comment = trim($_POST['comment']);

if (!empty($venue_name) && !empty($comment)) {
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, venue_name, comment) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $venue_name, $comment]);
    echo "Review submitted successfully!";
} else {
    echo "Please fill in all fields!";
}
?>


