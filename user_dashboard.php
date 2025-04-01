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
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #1e1e2f; /* Dark background */
            color: #ffffff; /* White text */
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* Navbar Styles */
        .navbar {
            width: 100%;
            background: #2c3e50; /* Dark blue */
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 18px;
            transition: 0.3s;
        }

        .navbar a:hover {
            background: #3498db; /* Light blue */
            border-radius: 5px;
        }

        /* Main Container Styles */
        .main-container {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
            height: calc(100vh - 60px); /* Subtract navbar height */
            padding: 20px;
            margin-top: 60px; /* Offset for navbar */
            overflow-x: auto;
            overflow-y: hidden;
        }

        /* Section Styles */
        .section {
            background: #2a2a40; /* Darker background */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 30%;
            min-width: 300px;
            margin: 0 10px;
            height: 100%;
            overflow-y: auto;
        }

        .section h2 {
            margin-bottom: 15px;
            color: #3498db; /* Light blue */
            font-size: 20px;
        }

        /* Form Styles */
        textarea, select, button, input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #444;
            border-radius: 5px;
            font-size: 14px;
            background: #3a3a4f; /* Dark input background */
            color: #ffffff; /* White text */
        }

        button {
            background-color: #3498db; /* Light blue */
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9; /* Darker blue */
        }

        /* Comment Box Styles */
        .comment-box {
            border: 1px solid #444;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            background: #3a3a4f; /* Dark background */
        }

        .comment {
            margin: 10px 0;
            padding: 10px;
            border-bottom: 1px solid #444;
        }

        .comment:last-child {
            border-bottom: none;
        }

        .comment .response {
            margin-top: 10px;
            padding: 10px;
            background: #2a2a40; /* Darker background */
            border-left: 3px solid #3498db; /* Light blue */
            border-radius: 5px;
        }

        /* Chat Box Styles */
        .chat-box {
            border: 1px solid #444;
            padding: 10px;
            height: 300px;
            overflow-y: auto;
            background: #3a3a4f; /* Dark background */
            margin-top: 10px;
            border-radius: 5px;
            display: flex;
            flex-direction: column;
        }

        .message {
            margin: 5px 0;
            padding: 8px 12px;
            border-radius: 15px;
            max-width: 80%;
            word-wrap: break-word;
        }

        .message.user {
            background: #3498db; /* Light blue for user messages */
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .message.bot {
            background: #444; /* Darker for bot messages */
            color: white;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .message-time {
            font-size: 0.7em;
            color: #aaa;
            margin-top: 3px;
            text-align: right;
        }

        .typing-indicator {
            display: none;
            padding: 8px 12px;
            background: #444;
            color: white;
            border-radius: 15px;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
            margin: 5px 0;
            max-width: 60%;
        }

        .typing-indicator span {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #ccc;
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
            gap: 10px;
            margin-top: 10px;
        }

        .chat-controls button {
            flex: 1;
        }

        #voice-status {
            margin-top: 5px;
            font-size: 0.8em;
            color: #aaa;
        }

        .suggested-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 10px;
        }

        .suggested-question {
            background: #3a3a4f;
            border: 1px solid #444;
            border-radius: 15px;
            padding: 5px 10px;
            font-size: 0.8em;
            cursor: pointer;
            transition: background 0.2s;
        }

        .suggested-question:hover {
            background: #3498db;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="event_schedule.php">Create Event Schedule</a>
        <a href="event_register.php">Register an Event</a>
        <a href="view_venues.php">View Available Venues</a>
        <a href="update_phone.php">Update Phone Number</a>
        <a href="payment.php">Make payment</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="main-container">
        <!-- Leave a Comment Section -->
        <div class="section">
            <h2>Leave a Comment</h2>
            <?php if ($success_message): ?>
                <p style="color: #4caf50;"><?= $success_message ?></p>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <p style="color: #ff4444;"><?= $error_message ?></p>
            <?php endif; ?>
            <form method="post">
                <textarea name="comment" placeholder="Write your comment..." required></textarea>
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
                <button type="submit">Submit Comment</button>
            </form>
        </div>

        <!-- Your Comments Section -->
        <div class="section">
            <h2>Your Comments</h2>
            <div class="comment-box">
                <?php if (empty($user_comments)): ?>
                    <p>No comments found.</p>
                <?php else: ?>
                    <?php foreach ($user_comments as $comment): ?>
                        <div class="comment">
                            <p><strong>Comment:</strong> <?= htmlspecialchars($comment['comment']) ?></p>
                            <p><strong>Date:</strong> <?= htmlspecialchars($comment['created_at']) ?></p>
                            <?php if (!empty($comment['response'])): ?>
                                <div class="response">
                                    <strong>Admin Response:</strong> <?= htmlspecialchars($comment['response']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat with Assistant Section -->
        <div class="section">
            <h2>Chat with Assistant</h2>
            
            <!-- Suggested Questions -->
            <div class="suggested-questions">
                <div class="suggested-question" onclick="insertQuestion('How do I register for an event?')">Register event</div>
                <div class="suggested-question" onclick="insertQuestion('How to make a payment?')">Make payment</div>
                <div class="suggested-question" onclick="insertQuestion('View available venues')">View venues</div>
                <div class="suggested-question" onclick="insertQuestion('Help with comments')">Comment help</div>
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
            
            <p id="voice-status">Voice recognition is ready.</p>
        </div>
    </div>

    <script>
        // Enhanced Chatbot functionality
        const chatBox = document.getElementById('chat-box');
        const userMessageInput = document.getElementById('user-message');
        const voiceStatus = document.getElementById('voice-status');
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
                voiceStatus.textContent = "Voice recognition not supported in this browser.";
                return;
            }

            const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.lang = 'en-US';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            recognition.start();

            recognition.onstart = () => {
                voiceStatus.textContent = "Listening... Speak now.";
            };

            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                userMessageInput.value = transcript;
                sendMessage();
            };

            recognition.onend = () => {
                voiceStatus.textContent = "Voice recognition is ready.";
            };

            recognition.onerror = (event) => {
                voiceStatus.textContent = "Error occurred: " + event.error;
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