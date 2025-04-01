<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch user reviews
$sql = "SELECT v.id, u.username, v.venue_name, v.comment, v.admin_response 
        FROM venue_reviews v 
        JOIN users u ON v.user_id = u.id
        ORDER BY v.created_at DESC";
$stmt = $conn->query($sql);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Review Responses</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f4f4f4;
        }
        .container {
            width: 60%;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .review {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        textarea {
            width: 100%;
            height: 80px;
            margin-top: 10px;
        }
        button {
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin - Review Responses</h2>
        <?php foreach ($reviews as $review): ?>
            <div class="review">
                <p><strong>User:</strong> <?= htmlspecialchars($review['username']) ?></p>
                <p><strong>Venue:</strong> <?= htmlspecialchars($review['venue_name']) ?></p>
                <p><strong>Review:</strong> <?= htmlspecialchars($review['comment']) ?></p>
                <p><strong>Admin Response:</strong> 
                    <?= $review['admin_response'] ? htmlspecialchars($review['admin_response']) : "<i>No response yet</i>" ?>
                </p>
                <form action="respond_review.php" method="POST">
                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                    <textarea name="admin_response" required placeholder="Enter your response here..."></textarea>
                    <button type="submit">Send Response</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>


