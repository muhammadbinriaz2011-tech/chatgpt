<?php
session_start();
include 'db.php';
 
$session_id = session_id();
 
$query = "SELECT message, is_user, timestamp FROM chat_messages WHERE session_id = $1 ORDER BY timestamp ASC";
$result = pg_query_params($conn, $query, [$session_id]);
$history = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $history[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - ChatGPT Clone</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        .chat-container {
            width: 100%;
            max-width: 800px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 80vh;
            transition: all 0.3s ease;
        }
        .chat-header {
            background: linear-gradient(135deg, #4a90e2, #50c878);
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .chat-window {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f9fbfc;
            display: flex;
            flex-direction: column;
        }
        .message {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 12px 18px;
            border-radius: 20px;
            line-height: 1.4;
            position: relative;
            animation: fadeIn 0.3s ease;
        }
        .user-message {
            background: #4a90e2;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        .bot-message {
            background: #e9ecef;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        .message::after {
            content: attr(data-time);
            font-size: 0.7em;
            color: #999;
            position: absolute;
            bottom: -15px;
            right: 10px;
        }
        .user-message::after {
            right: auto;
            left: 10px;
        }
        .input-area {
            display: flex;
            padding: 15px;
            background: #fff;
            border-top: 1px solid #eee;
        }
        #user-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 1em;
            outline: none;
            transition: border 0.3s;
        }
        #user-input:focus {
            border-color: #4a90e2;
        }
        #send-btn {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 12px 20px;
            margin-left: 10px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s, transform 0.2s;
        }
        #send-btn:hover {
            background: #357abd;
            transform: scale(1.05);
        }
        #loading {
            display: none;
            align-self: flex-start;
            margin: 10px 20px;
            color: #999;
            font-style: italic;
            animation: pulse 1s infinite;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        @media (max-width: 600px) {
            .chat-container {
                height: 100vh;
                border-radius: 0;
            }
            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">AI Chatbot</div>
        <div class="chat-window" id="chat-window">
            <?php foreach ($history as $msg): ?>
                <div class="message <?php echo $msg['is_user'] ? 'user-message' : 'bot-message'; ?>" data-time="<?php echo date('H:i', strtotime($msg['timestamp'])); ?>">
                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                </div>
            <?php endforeach; ?>
            <div id="loading">Thinking...</div>
        </div>
        <div class="input-area">
            <input type="text" id="user-input" placeholder="Type your message...">
            <button id="send-btn">Send</button>
        </div>
    </div>
    <script>
        const chatWindow = document.getElementById('chat-window');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const loading = document.getElementById('loading');
 
        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
 
        function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;
 
            const userMsg = document.createElement('div');
            userMsg.classList.add('message', 'user-message');
            userMsg.innerHTML = message.replace(/\n/g, '<br>');
            userMsg.dataset.time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            chatWindow.appendChild(userMsg);
 
            userInput.value = '';
            chatWindow.scrollTop = chatWindow.scrollHeight;
 
            loading.style.display = 'block';
            chatWindow.scrollTop = chatWindow.scrollHeight;
 
            fetch('chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.error) {
                    alert(data.error);
                    return;
                }
 
                const botMsg = document.createElement('div');
                botMsg.classList.add('message', 'bot-message');
                botMsg.innerHTML = data.response.replace(/\n/g, '<br>');
                botMsg.dataset.time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                chatWindow.appendChild(botMsg);
                chatWindow.scrollTop = chatWindow.scrollHeight;
            })
            .catch(error => {
                loading.style.display = 'none';
                alert('Error: ' + error);
            });
        }
    </script>
</body>
</html>
