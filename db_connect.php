<?php
$host = "localhost";
$dbname = "cultural_events"; // Your database name
$username = "root"; // Default MAMP username
$password = "root"; // Default MAMP password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
