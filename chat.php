<?php
session_start();
include 'db.php';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_message = trim($_POST['message']);
    if (empty($user_message)) {
        echo json_encode(['error' => 'Empty message']);
        exit;
    }
 
    $session_id = session_id();
 
    $query = "INSERT INTO chat_messages (session_id, message, is_user) VALUES ($1, $2, $3)";
    $result = pg_query_params($conn, $query, [$session_id, $user_message, true]);
    if (!$result) {
        echo json_encode(['error' => 'Database error: ' . pg_last_error()]);
        exit;
    }
 
    $query = "SELECT message, is_user FROM chat_messages WHERE session_id = $1 ORDER BY timestamp ASC";
    $result = pg_query_params($conn, $query, [$session_id]);
    if (!$result) {
        echo json_encode(['error' => 'Database error: ' . pg_last_error()]);
        exit;
    }
 
    $history = [];
    $history[] = ['role' => 'system', 'content' => 'You are a helpful assistant.'];
 
    while ($row = pg_fetch_assoc($result)) {
        $role = $row['is_user'] ? 'user' : 'assistant';
        $history[] = ['role' => $role, 'content' => $row['message']];
    }
 
    $api_key = 'your_openai_api_key_here';
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => $history,
        'temperature' => 0.7,
    ];
 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ]);
 
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
 
    if ($http_code !== 200) {
        echo json_encode(['error' => 'API error: ' . $response]);
        exit;
    }
 
    $response_data = json_decode($response, true);
    $bot_message = $response_data['choices'][0]['message']['content'] ?? 'Sorry, no response.';
 
    $query = "INSERT INTO chat_messages (session_id, message, is_user) VALUES ($1, $2, $3)";
    $result = pg_query_params($conn, $query, [$session_id, $bot_message, false]);
    if (!$result) {
        echo json_encode(['error' => 'Database error: ' . pg_last_error()]);
        exit;
    }
 
    echo json_encode(['response' => $bot_message]);
    exit;
}
?>
