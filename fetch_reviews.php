<?php
require 'db_connect.php';

$stmt = $conn->query("SELECT r.comment, r.venue_name, u.username, r.response FROM reviews r 
                      JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($reviews) > 0) {
    foreach ($reviews as $review) {
        echo "<p><strong>" . htmlspecialchars($review['username']) . "</strong> reviewed <strong>" . 
             htmlspecialchars($review['venue_name']) . "</strong>: <br> " . 
             htmlspecialchars($review['comment']) . "</p>";

        if (!empty($review['response'])) {
            echo "<p style='color: green;'><strong>Admin Response:</strong> " . 
                 htmlspecialchars($review['response']) . "</p>";
        }
        echo "<hr>";
    }
} else {
    echo "<p>No reviews yet.</p>";
}
?>



