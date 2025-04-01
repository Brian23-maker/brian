<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_message = strtolower(trim($_POST['message']));
    $response = "";

    // Expanded chatbot logic
    $responses = [
        "hello" => "Hello! How can I assist you today?",
        "hi" => "Hi there! How can I help you?",
        "how are you" => "I'm just a bot, but I'm here to help!",
        "event schedule" => "You can view the event schedule <a href='event_schedule.php'>here</a>.",
        "venue" => "Find available venues and directions <a href='view_venues.php'>here</a>.",
        "payment" => "To make a payment, go to the M-Pesa payment section.",
        "mpesa" => "For M-Pesa payments, ensure your phone number is registered and check your balance.",
        "ticket price" => "Ticket prices depend on the event. Check the event details for more information.",
        "help" => "You can ask me about events, venues, or payments. How can I assist?",
        "weather" => "I'm sorry, I can't check the weather right now. Try a weather app!",
        "directions" => "You can find directions to the venue on the <a href='view_venues.php'>venues page</a>.",
        "contact" => "For further assistance, please contact support at support@example.com.",
        "thank you" => "You're welcome! Let me know if you need anything else.",
        "bye" => "Goodbye! Have a great day!",
        "cancel" => "To cancel your ticket, please visit the <a href='cancel_ticket.php'>cancel ticket page</a>.",
        "refund" => "Refunds are processed within 7-10 business days. Contact support for more details.",
        "feedback" => "We appreciate your feedback! Please share it <a href='feedback.php'>here</a>."
    ];

    // Check if the message matches a predefined response
    foreach ($responses as $key => $value) {
        if (strpos($user_message, $key) !== false) {
            $response = $value;
            break;
        }
    }

    // Default response if no match is found
    if (!$response) {
        $response = "I'm not sure about that. Try asking about events, venues, or payments.";
    }

    echo json_encode(["response" => $response]);
}
?>

