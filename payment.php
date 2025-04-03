<?php
// Start session at the very beginning
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require 'db_connect.php';

// Initialize variables
$message = '';
$venues = [];
$events = [];

try {
    // Fetch venues from database
    $stmt = $conn->query("SELECT id, venue_name FROM venues");
    if ($stmt) {
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch events from database
    $stmt = $conn->query("SELECT id, event_name FROM events");
    if ($stmt) {
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
}

// Function to Generate Access Token
function generateAccessToken() {
    $consumerKey = "SlyLXhpfQtAGOHnx143luy1KEUzLcpWA8us43DAQMBcUghjy"; 
    $consumerSecret = "Cz11r7FypthZr5Gz14HVtNVLAOz7KWMb0XHS81QQUbT7KIvyI2AAN2aoyK1XyzpU"; 
    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";

    $credentials = base64_encode("$consumerKey:$consumerSecret");

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($curl);
    if ($response === false) {
        return false;
    }
    curl_close($curl);

    $json = json_decode($response);
    return $json->access_token ?? false;
}

// Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate inputs
        $phone = $_POST['phone'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $venue_id = $_POST['venue'] ?? 0;
        $event_id = $_POST['event'] ?? 0;

        if (empty($phone) || empty($amount) || empty($venue_id) || empty($event_id)) {
            throw new Exception("Please fill in all fields.");
        }

        if ($amount < 1500) {
            throw new Exception("The minimum amount to pay is 1500.");
        }

        // Get venue and event names
        $stmt = $conn->prepare("SELECT venue_name FROM venues WHERE id = ?");
        $stmt->execute([$venue_id]);
        $venue = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("SELECT event_name FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetchColumn();
        
        // Process payment
        $accessToken = generateAccessToken();
        if (!$accessToken) {
            throw new Exception("Failed to generate access token.");
        }

        $businessShortCode = "174379";
        $passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
        $timestamp = date("YmdHis");
        $password = base64_encode($businessShortCode . $passkey . $timestamp);

        $stkURL = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";
        $callbackURL = "https://yourdomain.com/callback.php";

        // Format phone number - updated to handle 01 format
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Validate phone number length (should be 9 digits after 0/01 is removed)
        if (strlen($phone) < 9) {
            throw new Exception("Invalid phone number. Please use format 07xxxxxxxx or 01xxxxxxxx");
        }
        
        // Convert to 254 format
        if (preg_match('/^(0|01)/', $phone)) {
            $phone = preg_replace('/^(0|01)/', '254', $phone);
        } elseif (!preg_match('/^254/', $phone)) {
            $phone = '254' . $phone;
        }
        
        // Ensure the final number is 12 digits (254 + 9 digits)
        if (strlen($phone) != 12) {
            throw new Exception("Invalid phone number length after conversion");
        }

        // Create clear payment description
        $event_short = substr($event, 0, 12); // Limit to 12 chars
        $venue_short = substr($venue, 0, 12); // Limit to 12 chars
        $amount_formatted = number_format($amount);

        $payload = [
            "BusinessShortCode" => $businessShortCode,
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $businessShortCode,
            "PhoneNumber" => $phone,
            "CallBackURL" => $callbackURL,
            "AccountReference" => "EVENT: $event_short",
            "TransactionDesc" => "VENUE: $venue_short | Ksh $amount_formatted"
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $stkURL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response, true);
        
        if (isset($result["ResponseCode"]) && $result["ResponseCode"] == "0") {
            $message = "STK Push Sent. Check your phone to approve payment for $event at $venue";
        } else {
            $error_msg = $result["errorMessage"] ?? "Unknown error";
            throw new Exception("Payment failed: $error_msg");
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Payment Portal</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #00a86b, #008080);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 30px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h2 {
            color: #00a86b;
            margin-bottom: 20px;
            font-size: 24px;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
            color: #555;
            text-align: left;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        input:focus, select:focus {
            border-color: #00a86b;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #00a86b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #008080;
        }
        .back-btn {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease;
            text-align: center;
            text-decoration: none;
            margin-top: 10px;
        }
        .back-btn:hover {
            background: #c0392b;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
            margin-top: 10px;
        }
        .success {
            color: #00a86b;
            font-weight: bold;
            margin-top: 10px;
        }
        .logo {
            width: 120px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://th.bing.com/th/id/OIP.yi2pvKxD_TGay1eEwE-GVQHaFa?w=292&h=213&c=8&rs=1&qlt=90&o=6&pid=3.1&rm=2" alt="Event Logo" class="logo">
        <h2>Event Payment Portal</h2>
        
        <?php if (!empty($message)): ?>
            <p class="<?php echo strpos($message, 'failed') !== false ? 'error' : 'success' ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>
        
        <form method="POST">
            <label for="venue">Select Venue:</label>
            <select id="venue" name="venue" required>
                <option value="">-- Select a Venue --</option>
                <?php foreach ($venues as $venue): ?>
                    <option value="<?php echo htmlspecialchars($venue['id']); ?>"
                        <?php if (isset($_POST['venue']) && $_POST['venue'] == $venue['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($venue['venue_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="event">Select Event:</label>
            <select id="event" name="event" required>
                <option value="">-- Select an Event --</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?php echo htmlspecialchars($event['id']); ?>"
                        <?php if (isset($_POST['event']) && $_POST['event'] == $event['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($event['event_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" placeholder="07XXXXXXXX or 01XXXXXXXX" 
                   pattern="(0|01)[0-9]{8,9}" 
                   title="Please enter a valid phone number starting with 0 or 01"
                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
            
            <label for="amount">Amount (Ksh):</label>
            <input type="number" id="amount" name="amount" placeholder="Minimum 1500" min="1500" required
                   value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>">
            
            <button type="submit" class="btn">Make Payment</button>
        </form>
        <a href="user_dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>
</body>
</html>









