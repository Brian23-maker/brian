<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Unauthorized access!";
    exit();
}

$review_id = $_POST['review_id'];
$admin_response = $_POST['admin_response'];

$sql = "UPDATE venue_reviews SET admin_response = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$admin_response, $review_id]);

header("Location: admin_reviews.php");
exit();
?>
