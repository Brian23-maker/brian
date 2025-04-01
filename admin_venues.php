<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'db_connect.php';

// Fetch admin info
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_name = $admin ? $admin['name'] : "Admin";

// Fetch all approved events with venue and user details
$approved_events = [];
try {
    $stmt = $conn->query("
        SELECT 
            e.id AS event_id,
            e.event_name,
            e.event_date,
            e.description,
            v.id AS venue_id,
            v.name AS venue_name,
            v.location,
            v.capacity,
            u.name AS organizer_name,
            u.email AS organizer_email
        FROM events e
        JOIN venues v ON e.venue_id = v.id
        JOIN users u ON e.user_id = u.id
        WHERE e.status = 'approved'
        ORDER BY e.event_date DESC
    ");
    $approved_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch approved events: " . $e->getMessage());
}

// Fetch all venues (for reference)
$all_venues = [];
try {
    $stmt = $conn->query("SELECT id, name FROM venues ORDER BY name");
    $all_venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch venues: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .navbar {
            width: 100%;
            background: #2c3e50;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .navbar-brand {
            font-size: 24px;
            font-weight: 600;
            color: #ffffff;
        }
        .navbar-nav {
            display: flex;
            gap: 20px;
        }
        .navbar-nav a {
            text-decoration: none;
            color: #ffffff;
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .navbar-nav a:hover {
            background-color: #34495e;
        }
        .navbar-nav a.logout {
            color: #e74c3c;
        }
        .navbar-nav a.logout:hover {
            background-color: #c0392b;
        }
        .container {
            background: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1200px;
            margin: 100px auto 50px;
        }
        .container h2 {
            margin-bottom: 20px;
            color: #444;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }
        .section {
            margin-top: 30px;
        }
        .section h3 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #444;
            border-bottom: 2px solid #eee;
            padding-bottom: 8px;
        }
        .event-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .event-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .event-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }
        .event-detail {
            flex: 1;
            min-width: 200px;
        }
        .event-detail strong {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .venue-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .venue-info h5 {
            margin-bottom: 10px;
            color: #34495e;
        }
        .organizer-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .no-data {
            color: #666;
            font-style: italic;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 14px;
            background-color: #d4edda;
            color: #155724;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">Admin Dashboard</div>
        <div class="navbar-nav">
            <a href="admin_dashboard.php">🏠 Dashboard</a>
            <a href="admin_events.php">📅 Events</a>
            <a href="admin_venues.php">🏟️ Venues</a>
            <a href="admin_comments.php">💬 Comments</a>
            <a href="admin_reports.php">📊 Reports</a>
            <a href="logout.php" class="logout">🚪 Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2>Approved Events and Their Venues</h2>

        <!-- Approved Events Section -->
        <div class="section">
            <h3>Approved Events <span class="status-badge">Approved</span></h3>
            
            <?php if (empty($approved_events)): ?>
                <div class="no-data">No approved events found.</div>
            <?php else: ?>
                <?php foreach ($approved_events as $event): ?>
                    <div class="event-card">
                        <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                        
                        <div class="event-details">
                            <div class="event-detail">
                                <strong>Event Date:</strong>
                                <?php echo htmlspecialchars(date('F j, Y', strtotime($event['event_date']))); ?>
                            </div>
                            <div class="event-detail">
                                <strong>Event ID:</strong>
                                <?php echo htmlspecialchars($event['event_id']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($event['description'])): ?>
                            <div class="event-detail">
                                <strong>Description:</strong>
                                <?php echo htmlspecialchars($event['description']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="venue-info">
                            <h5>Venue Details</h5>
                            <div class="event-details">
                                <div class="event-detail">
                                    <strong>Venue Name:</strong>
                                    <?php echo htmlspecialchars($event['venue_name']); ?>
                                </div>
                                <div class="event-detail">
                                    <strong>Location:</strong>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                                <div class="event-detail">
                                    <strong>Capacity:</strong>
                                    <?php echo htmlspecialchars($event['capacity']); ?>
                                </div>
                                <div class="event-detail">
                                    <strong>Venue ID:</strong>
                                    <?php echo htmlspecialchars($event['venue_id']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="organizer-info">
                            <strong>Organized By:</strong>
                            <?php echo htmlspecialchars($event['organizer_name']); ?> 
                            (<?php echo htmlspecialchars($event['organizer_email']); ?>)
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- All Venues Reference Section -->
        <div class="section">
            <h3>All Venues in System</h3>
            <?php if (empty($all_venues)): ?>
                <div class="no-data">No venues found in the system.</div>
            <?php else: ?>
                <ul style="column-count: 2; list-style-type: none;">
                    <?php foreach ($all_venues as $venue): ?>
                        <li style="margin-bottom: 8px;">
                            <?php echo htmlspecialchars($venue['name']); ?>
                            (ID: <?php echo htmlspecialchars($venue['id']); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>