<?php
require 'db.php'; // atau config koneksi database kamu

$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

$sql = "SELECT message FROM chat_logs 
        WHERE user_id = ? AND sender = 'user'
        ORDER BY timestamp DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row['message'];
}

echo json_encode(['context' => implode("\n", array_reverse($messages))]);
