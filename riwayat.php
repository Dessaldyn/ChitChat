<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once 'db.php';
$userId = $_SESSION['user_id'];

// === SIMPAN CHAT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['action'])) {
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
  $stmt->execute();
  $stmt->close();
  $conn->close();
  exit;
}

// === HAPUS SEMUA PESAN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus_semua') {
  $stmt = $conn->prepare("DELETE FROM chat_logs WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->close();
  header("Location: riwayat.php");
  exit;
}

// === HAPUS PESAN TERTENTU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'hapus' && isset($_POST['timestamp'])) {
  $timestamp = $_POST['timestamp'];
  $stmt = $conn->prepare("DELETE FROM chat_logs WHERE user_id = ? AND timestamp = ?");
  $stmt->bind_param("is", $userId, $timestamp);
  $stmt->execute();
  $stmt->close();
  header("Location: riwayat.php");
  exit;
}

// === TAMPILKAN RIWAYAT ===
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
    * {
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      margin: 0;
      font-family: 'Inter', sans-serif;
      background: #1e1e2f;
      color: #f0f0f0;
    }

    .container {
      display: flex;
      flex-direction: column;
      height: 100vh;
      padding: 20px;
    }

    .title {
      font-size: 24px;
      font-weight: 600;
      text-align: center;
      margin-bottom: 10px;
    }

    .button-row {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-bottom: 15px;
    }

    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
    }

    .btn-back {
      background-color: #6c63ff;
      color: white;
    }

    .btn-back:hover {
      background-color: #5a54d6;
    }

    .btn-delete-all {
      background-color: #e53935;
      color: white;
    }

    .btn-delete-all:hover {
      background-color: #c62828;
    }

    .chat-log {
      flex: 1;
      overflow-y: auto;
      padding-right: 5px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      background: #2a2a3f;
      border-radius: 10px;
      padding: 20px;
    }

    .chat-message-wrapper {
      position: relative;
    }

    .chat-message {
      padding: 12px 16px;
      border-radius: 10px;
      max-width: 70%;
      white-space: pre-wrap;
    }

    .user-message {
      background: #4a4aff;
      align-self: flex-end;
      color: white;
    }

    .system-message {
      background: #444;
      align-self: flex-start;
      color: #eee;
    }

    small {
      display: block;
      font-size: 0.75rem;
      margin-bottom: 5px;
      opacity: 0.8;
    }

    .delete-btn {
      position: absolute;
      top: 8px;
      right: -28px;
      background: transparent;
      border: none;
      color: #ff6666;
      font-size: 16px;
      cursor: pointer;
    }

    .empty-log {
      text-align: center;
      color: #ccc;
      font-style: italic;
      padding-top: 30px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="title">📖 Riwayat Curhat</div>

    <div class="button-row">
      <button class="btn btn-back" onclick="window.location.href='index.php'">⬅️ Kembali</button>
      <form method="POST" onsubmit="return confirm('Yakin ingin menghapus semua pesan?')">
        <input type="hidden" name="action" value="hapus_semua">
        <button type="submit" class="btn btn-delete-all">🗑️ Hapus Semua</button>
      </form>
    </div>

    <div class="chat-log">
      <?php if (empty($chatLog)): ?>
        <p class="empty-log">Tidak ada riwayat curhat.</p>
      <?php else: ?>
        <?php foreach ($chatLog as $msg): ?>
          <div class="chat-message-wrapper">
            <div class="chat-message <?= $msg['sender'] === 'user' ? 'user-message' : 'system-message' ?>">
              <small>[<?= date("H:i:s", strtotime($msg['timestamp'])) ?>] <?= $msg['emotion'] ? strtoupper($msg['emotion']) . ' -' : '' ?></small>
              <?= htmlspecialchars($msg['message']) ?>
            </div>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="hapus">
              <input type="hidden" name="timestamp" value="<?= htmlspecialchars($msg['timestamp']) ?>">
              <button type="submit" class="delete-btn" title="Hapus pesan">🗑️</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
