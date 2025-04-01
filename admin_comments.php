<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all comments with responses
$comments = $conn->query("
    SELECT c.id, c.username, v.venue_name, e.event_name, c.comment, c.created_at, c.response 
    FROM comments c
    LEFT JOIN venues v ON c.venue_id = v.id
    LEFT JOIN events e ON c.event_id = e.id
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id']) && isset($_POST['response'])) {
    $comment_id = $_POST['comment_id'];
    $response = $_POST['response'];

    // Update the comment with the admin's response
    $update_stmt = $conn->prepare("UPDATE comments SET response = ? WHERE id = ?");
    $update_stmt->execute([$response, $comment_id]);

    // Redirect to refresh the page and avoid form resubmission
    header("Location: admin_comments.php");
    exit();
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = $_POST['delete_comment'];

    // Delete the comment from the database
    $delete_stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $delete_stmt->execute([$comment_id]);

    // Redirect to refresh the page and avoid form resubmission
    header("Location: admin_comments.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Comments</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('https://th.bing.com/th?id=OIP.zE9gYwGuWS6zL7Us8P6PqQHaE7&w=306&h=204&c=8&rs=1&qlt=90&o=6&pid=3.1&rm=2') no-repeat center center fixed; 
            background-size: cover;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: rgba(255, 255, 255, 0.9); /* Slight transparency */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 80%;
            min-width: 600px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f8f8f8;
        }
        .response-form {
            margin-top: 10px;
        }
        .response-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }
        .response-form button {
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .response-form button:hover {
            background-color: #0056b3;
        }
        .response {
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 3px solid #007bff;
        }
        .delete-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-button:hover {
            background-color: #c82333;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Comments</h2>
        <table>
            <tr>
                <th>Username</th>
                <th>Venue</th>
                <th>Event</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Response</th>
                <th>Action</th>
            </tr>
            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?= htmlspecialchars($comment['username']) ?></td>
                    <td><?= $comment['venue_name'] ? htmlspecialchars($comment['venue_name']) : 'N/A' ?></td>
                    <td><?= $comment['event_name'] ? htmlspecialchars($comment['event_name']) : 'N/A' ?></td>
                    <td><?= htmlspecialchars($comment['comment']) ?></td>
                    <td><?= htmlspecialchars($comment['created_at']) ?></td>
                    <td>
                        <?php if (!empty($comment['response'])): ?>
                            <div class="response">
                                <strong>Admin Response:</strong> <?= htmlspecialchars($comment['response']) ?>
                            </div>
                        <?php else: ?>
                            <form class="response-form" method="POST">
                                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                <textarea name="response" placeholder="Write your response here..." required></textarea>
                                <button type="submit">Submit Response</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                            <input type="hidden" name="delete_comment" value="<?= $comment['id'] ?>">
                            <button type="submit" class="delete-button">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="admin_dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>
