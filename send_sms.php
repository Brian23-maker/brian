<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = "";
$error = "";

// Function to send SMS using Africa's Talking
function sendAfricasTalkingSMS($phone, $message) {
    $username = "sandbox";  // Change this if using a live account
    $apiKey = "atsk_d2fbc847286eaecac52f3f7065c809539cd584f4e339825799e7ed1b69877a05889b0893"; // Replace with your actual API key

    $url = "https://api.sandbox.africastalking.com/version1/messaging"; // Change for live

    // Ensure the phone number is in the correct format
    $phone = preg_replace('/^0/', '+254', $phone); // Convert 07XXXXX to +2547XXXXX

    $data = [
        "username" => $username,
        "to"       => $phone,
        "message"  => $message,
        "from"     => "AFRICASTKNG"
    ];

    $headers = [
        "Content-Type: application/x-www-form-urlencoded",
        "apiKey: $apiKey"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Debugging output
    file_put_contents("sms_debug.log", "HTTP Code: $httpCode - Response: $response\n", FILE_APPEND);
    
    $decodedResponse = json_decode($response, true);

    if ($httpCode == 201 && isset($decodedResponse["SMSMessageData"]["Recipients"][0]["status"])) {
        if ($decodedResponse["SMSMessageData"]["Recipients"][0]["status"] == "Success") {
            return true;
        } else {
            return $decodedResponse["SMSMessageData"]["Recipients"][0]["status"];
        }
    } else {
        return $decodedResponse;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST['message']);

    // Fetch user phone numbers
    $stmt = $conn->prepare("SELECT phone FROM users WHERE phone IS NOT NULL");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($users) {
        $allSuccessful = true;

        foreach ($users as $user) {
            $phone = $user['phone'];
            $response = sendAfricasTalkingSMS($phone, $message);

            if ($response !== true) {
                $error .= "Failed to send SMS to " . htmlspecialchars($phone) . ": " . json_encode($response) . "<br>";
                $allSuccessful = false;
            }
        }

        if ($allSuccessful) {
            $success = "SMS sent successfully!";
            header("refresh:5;url=admin_dashboard.php");  
        }
    } else {
        $error = "No users with phone numbers found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send SMS Notification</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('https://th.bing.com/th?id=OIP.NgOQpDk_zH2xDPfHfRv7jAHaE8&w=306&h=204&c=8&rs=1&qlt=90&o=6&pid=3.1&rm=2');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 50%;
            min-width: 400px;
        }
        textarea, button {
            width: 100%;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .success { color: green; }
        .error { color: red; }
        a {
            text-decoration: none;
            color: blue;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Send SMS Notification</h2>
        <?php if ($success) echo "<p class='success'>$success Redirecting...</p>"; ?>
        <?php if ($error) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <textarea name="message" placeholder="Enter SMS message" required></textarea><br>
            <button type="submit">Send SMS</button>
        </form>
        <p><a href="admin_dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>










