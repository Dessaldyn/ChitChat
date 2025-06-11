<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once 'db.php'; // koneksi database

$userId = $_SESSION['user_id'];

// === JIKA POST, SIMPAN CHAT KE DATABASE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $data = json_decode(file_get_contents('php://input'), true);

  $message = $data['message'] ?? '';
  $sender = $data['sender'] ?? 'user';
  $emotion = $data['emotion'] ?? null;

  if (trim($message) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Pesan kosong.']);
    exit;
  }

  $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, message, sender, emotion) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $userId, $message, $sender, $emotion);

  if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
  } else {
    http_response_code(500);
    echo json_encode(['error' => $stmt->error]);
  }

  $stmt->close();
  $conn->close();
  exit;
}

// === JIKA GET, TAMPILKAN RIWAYAT CHAT ===
$stmt = $conn->prepare("SELECT sender, message, emotion, timestamp FROM chat_logs WHERE user_id = ? ORDER BY timestamp DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$chatLog = [];
while ($row = $result->fetch_assoc()) {
  $chatLog[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Riwayat Curhat</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="chat-fullscreen">
    <div class="navbar">
      <div class="navbar-title">📖 Riwayat Curhat</div>
      <button class="chat-history-button" onclick="window.location.href='index.php'">⬅️ Kembali</button>
    </div>

    <div id="chat-log" class="chat-messages">
      <?php if (empty($chatLog)): ?>
        <p>Tidak ada riwayat curhat.</p>
      <?php else: ?>
        <?php foreach ($chatLog as $msg): ?>
          <div class="chat-message <?= $msg['sender'] === 'user' ? 'user-message' : 'system-message' ?>">
            <small>
              [<?= date("H:i:s", strtotime($msg['timestamp'])) ?>]
              <?= $msg['emotion'] ? strtoupper($msg['emotion']) . ' -' : '' ?>
            </small><br>
            <?= htmlspecialchars($msg['message']) ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
