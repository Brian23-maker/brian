<?php
session_start();
require 'db_connect.php';

// Redirect if user is not logged in or is not a 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

// Fetch venues and events
$venues = [];
$events = [];
$error_message = '';
$success_message = '';

try {
    $venues = $conn->query("SELECT * FROM venues")->fetchAll(PDO::FETCH_ASSOC);
    $events = $conn->query("SELECT * FROM events")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Failed to fetch data: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $venue_id = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
    $event_id = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;

    if (empty($comment)) {
        $error_message = "Comment cannot be empty!";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO comments (user_id, username, venue_id, event_id, comment, created_at) 
                                    VALUES (:user_id, :username, :venue_id, :event_id, :comment, NOW())");
            $stmt->execute([
                ':user_id' => $user_id,
                ':username' => $username,
                ':venue_id' => $venue_id,
                ':event_id' => $event_id,
                ':comment' => htmlspecialchars($comment, ENT_QUOTES, 'UTF-8')
            ]);
            $success_message = "Comment submitted successfully!";
        } catch (PDOException $e) {
            $error_message = "Failed to submit comment: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

// Fetch user's comments with admin responses
$user_comments = [];
try {
    $stmt = $conn->prepare("
        SELECT c.id, c.comment, c.created_at, c.response, v.venue_name, e.event_name 
        FROM comments c
        LEFT JOIN venues v ON c.venue_id = v.id
        LEFT JOIN events e ON c.event_id = e.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Failed to fetch comments: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #ecf0f1;
            --dark: #2a2a40;
            --darker: #1e1e2f;
            --success: #2ecc71;
            --error: #e74c3c;
            --text: #333;
            --text-light: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .background-pattern {
            position: fixed;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(52, 152, 219, 0.1) 2px, transparent 2px);
            background-size: 30px 30px;
            z-index: 0;
            pointer-events: none;
        }

        .navbar {
            width: 100%;
            background: var(--secondary);
            padding: 1rem;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .navbar a:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .main-container {
            display: flex;
            flex-direction: column;
            padding: 1rem;
            margin-top: 4rem;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        @media (min-width: 1024px) {
            .main-container {
                flex-direction: row;
                align-items: flex-start;
            }
        }

        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        @media (min-width: 1024px) {
            .section {
                width: calc(33.333% - 1rem);
                margin-bottom: 0;
            }
        }

        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
        }

        textarea, select, input {
            width: 100%;
            padding: 0.8rem;
            margin: 0.5rem 0;
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        textarea:focus, select:focus, input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.8rem center;
            background-size: 1rem;
        }

        button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 0.5rem;
        }

        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }

        .comment-box {
            max-height: 400px;
            overflow-y: auto;
            padding: 0.5rem;
        }

        .comment {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 3px solid var(--primary);
        }

        .comment p {
            margin-bottom: 0.5rem;
        }

        .comment .response {
            background: #e8f4fc;
            padding: 0.8rem;
            border-radius: 6px;
            margin-top: 0.8rem;
            border-left: 3px solid var(--secondary);
        }

        .chat-box {
            height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
        }

        .message {
            max-width: 80%;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.8rem;
            position: relative;
        }

        .message.user {
            background: var(--primary);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .message.bot {
            background: #e8f4fc;
            color: var(--text);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 0.7rem;
            color: var(--text-light);
            margin-top: 0.3rem;
            text-align: right;
        }

        .typing-indicator {
            display: none;
            align-self: flex-start;
            background: #e8f4fc;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.8rem;
            border-bottom-left-radius: 4px;
        }

        .typing-indicator span {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: var(--primary);
            border-radius: 50%;
            margin: 0 2px;
            animation: typing 1s infinite ease-in-out;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .chat-controls {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .chat-controls input {
            flex: 1;
            margin: 0;
        }

        .chat-controls button {
            width: auto;
            margin: 0;
            padding: 0.8rem;
        }

        .suggested-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .suggested-question {
            background: #e8f4fc;
            color: var(--primary);
            border: 1px solid var(--primary);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .suggested-question:hover {
            background: var(--primary);
            color: white;
        }

        .success-message {
            color: var(--success);
            margin: 1rem 0;
            padding: 0.8rem;
            background: #e8f8f0;
            border-radius: 6px;
            border-left: 3px solid var(--success);
        }

        .error-message {
            color: var(--error);
            margin: 1rem 0;
            padding: 0.8rem;
            background: #fdedec;
            border-radius: 6px;
            border-left: 3px solid var(--error);
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <div class="navbar">
        <a href="event_schedule.php"><i class="fas fa-calendar-alt"></i> Create Schedule</a>
        <a href="event_register.php"><i class="fas fa-user-plus"></i> Register Event</a>
        <a href="view_venues.php"><i class="fas fa-map-marker-alt"></i> View Venues</a>
        <a href="update_phone.php"><i class="fas fa-phone"></i> Update Phone</a>
        <a href="payment.php"><i class="fas fa-credit-card"></i> Make Payment</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-container">
        <!-- Leave a Comment Section -->
        <div class="section">
            <h2><i class="fas fa-comment"></i> Leave a Comment</h2>
            <?php if ($success_message): ?>
                <div class="success-message"><?= $success_message ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="error-message"><?= $error_message ?></div>
            <?php endif; ?>
            <form method="post">
                <textarea name="comment" placeholder="Write your comment..." rows="4" required></textarea>
                <select name="venue_id">
                    <option value="">Select Venue (optional)</option>
                    <?php foreach ($venues as $venue): ?>
                        <option value="<?= $venue['id'] ?>"><?= $venue['venue_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="event_id">
                    <option value="">Select Event (optional)</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= $event['id'] ?>"><?= $event['event_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"><i class="fas fa-paper-plane"></i> Submit Comment</button>
            </form>
        </div>

        <!-- Your Comments Section -->
        <div class="section">
            <h2><i class="fas fa-comments"></i> Your Comments</h2>
            <div class="comment-box">
                <?php if (empty($user_comments)): ?>
                    <p>No comments found.</p>
                <?php else: ?>
                    <?php foreach ($user_comments as $comment): ?>
                        <div class="comment">
                            <p><strong>Comment:</strong> <?= htmlspecialchars($comment['comment']) ?></p>
                            <p><small>Posted on: <?= htmlspecialchars($comment['created_at']) ?></small></p>
                            <?php if (!empty($comment['venue_name'])): ?>
                                <p><small>Venue: <?= htmlspecialchars($comment['venue_name']) ?></small></p>
                            <?php endif; ?>
                            <?php if (!empty($comment['event_name'])): ?>
                                <p><small>Event: <?= htmlspecialchars($comment['event_name']) ?></small></p>
                            <?php endif; ?>
                            <?php if (!empty($comment['response'])): ?>
                                <div class="response">
                                    <strong>Admin Response:</strong>
                                    <p><?= htmlspecialchars($comment['response']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat with Assistant Section -->
        <div class="section">
            <h2><i class="fas fa-robot"></i> Chat with Assistant</h2>
            
            <div class="suggested-questions">
                <div class="suggested-question" onclick="insertQuestion('How do I register for an event?')">
                    <i class="fas fa-user-plus"></i> Register event
                </div>
                <div class="suggested-question" onclick="insertQuestion('How to make a payment?')">
                    <i class="fas fa-credit-card"></i> Make payment
                </div>
                <div class="suggested-question" onclick="insertQuestion('View available venues')">
                    <i class="fas fa-map-marker-alt"></i> View venues
                </div>
                <div class="suggested-question" onclick="insertQuestion('Help with comments')">
                    <i class="fas fa-comment"></i> Comment help
                </div>
            </div>
            
            <div class="chat-box" id="chat-box">
                <div class="message bot">
                    Hello! I'm your event assistant. How can I help you today?
                    <div class="message-time"><?= date('h:i A') ?></div>
                </div>
                <div class="typing-indicator" id="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            
            <div class="chat-controls">
                <input type="text" id="user-message" placeholder="Type your message..." onkeypress="handleKeyPress(event)">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
            
            <div class="chat-controls">
                <button onclick="startVoiceRecognition()"><i class="fas fa-microphone"></i> Voice</button>
                <button onclick="clearChat()"><i class="fas fa-trash"></i> Clear</button>
                <button onclick="showHelp()"><i class="fas fa-question-circle"></i> Help</button>
            </div>
        </div>
    </div>

    <script>
        // Enhanced Chatbot functionality
        const chatBox = document.getElementById('chat-box');
        const userMessageInput = document.getElementById('user-message');
        const typingIndicator = document.getElementById('typing-indicator');
        
        // Chatbot memory to maintain context
        let chatHistory = [
            { sender: 'bot', message: "Hello! I'm your event assistant. How can I help you today?", time: getCurrentTime() }
        ];

        // Enhanced chatbot responses with context awareness
        const chatbotResponses = {
            "hello": "Hello again! How can I assist you today?",
            "hi": "Hi there! What can I do for you?",
            "hey": "Hey! How can I help with your event needs?",
            "how to navigate": "You can navigate using the links in the navbar. For example: \n- 'Create Event Schedule' to plan events\n- 'Register an Event' to sign up\n- 'View Venues' to see available spaces\n- 'Make Payment' for transactions",
            "how to comment": "To leave a comment:\n1. Go to the 'Leave a Comment' section\n2. Write your comment in the text box\n3. Optionally select a venue or event\n4. Click 'Submit Comment'",
            "how to view comments": "Your submitted comments appear in the 'Your Comments' section. Any admin responses will be shown there too.",
            "how to make payment": "To make a payment:\n1. Click 'Make Payment' in the navbar\n2. Follow the payment process\n3. You'll receive a confirmation when complete",
            "register event": "To register for an event:\n1. Click 'Register an Event' in the navbar\n2. Select the event you want to join\n3. Complete the registration form\n4. Submit your details",
            "view venues": "Available venues can be viewed by:\n1. Clicking 'View Available Venues' in the navbar\n2. Browse the list of venues\n3. Click on a venue for more details",
            "help": "I can help with:\n- Event registration\n- Venue information\n- Payment processes\n- Comment submission\n- General navigation\n\nTry asking about any of these!",
            "thank": "You're welcome! Is there anything else I can help with?",
            "thanks": "You're welcome! Is there anything else I can help with?",
            "default": "I'm not sure I understand. Could you try asking differently? Here are some things I can help with:\n- Event registration\n- Venue information\n- Payment processes\n- Comment submission"
        };

        // Function to get current time in HH:MM AM/PM format
        function getCurrentTime() {
            const now = new Date();
            let hours = now.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            const minutes = now.getMinutes().toString().padStart(2, '0');
            return `${hours}:${minutes} ${ampm}`;
        }

        // Function to send a message
        function sendMessage() {
            const userMessage = userMessageInput.value.trim();
            if (userMessage === "") return;

            // Display user message
            appendMessage(userMessage, 'user');
            chatHistory.push({ sender: 'user', message: userMessage, time: getCurrentTime() });

            // Show typing indicator
            typingIndicator.style.display = 'flex';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Simulate bot thinking before responding
            setTimeout(() => {
                typingIndicator.style.display = 'none';
                
                // Get chatbot response
                const response = getChatbotResponse(userMessage.toLowerCase());
                appendMessage(response, 'bot');
                chatHistory.push({ sender: 'bot', message: response, time: getCurrentTime() });

                // Clear input
                userMessageInput.value = "";
                userMessageInput.focus();
            }, 1000 + Math.random() * 2000); // Random delay between 1-3 seconds
        }

        // Function to append a message to the chat box
        function appendMessage(message, sender) {
            const messageElement = document.createElement('div');
            messageElement.className = `message ${sender}`;
            messageElement.innerHTML = message.replace(/\n/g, '<br>');
            
            const timeElement = document.createElement('div');
            timeElement.className = 'message-time';
            timeElement.textContent = getCurrentTime();
            
            messageElement.appendChild(timeElement);
            chatBox.insertBefore(messageElement, typingIndicator);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Enhanced function to get chatbot response
        function getChatbotResponse(userMessage) {
            // Check for greetings
            if (userMessage.includes('hello') || userMessage.includes('hi') || userMessage.includes('hey')) {
                return chatbotResponses["hello"];
            }
            
            // Check for thanks
            if (userMessage.includes('thank') || userMessage.includes('thanks')) {
                return chatbotResponses["thank"];
            }
            
            // Check for help request
            if (userMessage.includes('help')) {
                return chatbotResponses["help"];
            }
            
            // Check other keywords
            const keywords = [
                'navigate', 'comment', 'view comment', 'payment', 
                'register', 'venue', 'event', 'schedule'
            ];
            
            for (const keyword of keywords) {
                if (userMessage.includes(keyword)) {
                    const responseKey = Object.keys(chatbotResponses).find(k => 
                        k.includes(keyword) || keyword.includes(k)
                    );
                    if (responseKey) {
                        return chatbotResponses[responseKey];
                    }
                }
            }
            
            // If no match found, return default response
            return chatbotResponses['default'];
        }

        // Function to clear chat
        function clearChat() {
            chatBox.innerHTML = `
                <div class="message bot">
                    Hello! I'm your event assistant. How can I help you today?
                    <div class="message-time">${getCurrentTime()}</div>
                </div>
                <div class="typing-indicator" id="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;
            chatHistory = [
                { sender: 'bot', message: "Hello! I'm your event assistant. How can I help you today?", time: getCurrentTime() }
            ];
        }

        // Function to handle Enter key press
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Voice recognition functionality
        function startVoiceRecognition() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert("Voice recognition not supported in this browser.");
                return;
            }

            const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.lang = 'en-US';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            recognition.start();

            recognition.onstart = () => {
                appendMessage("Listening... Speak now.", 'bot');
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                userMessageInput.value = transcript;
                sendMessage();
            };

            recognition.onerror = (event) => {
                appendMessage("Error occurred in voice recognition: " + event.error, 'bot');
            };
        }

        // Function to insert suggested question
        function insertQuestion(question) {
            userMessageInput.value = question;
            userMessageInput.focus();
        }

        // Function to show help
        function showHelp() {
            appendMessage(chatbotResponses["help"], 'bot');
            chatHistory.push({ sender: 'bot', message: chatbotResponses["help"], time: getCurrentTime() });
        }

        // Focus the input field on page load
        window.onload = function() {
            userMessageInput.focus();
        };
    </script>
</body>
</html>