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
  <link rel="stylesheet" href="style.css" />
  <style>
    .chat-message-wrapper { position: relative; padding-right: 50px; }
    .delete-btn {
      position: absolute;
      top: 0;
      right: 0;
      background: transparent;
      border: none;
      color: red;
      font-size: 16px;
      cursor: pointer;
    }
    .button-row {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-bottom: 10px;
      padding: 0 10px;
    }
    .btn-back, .btn-delete-all {
      background-color: #2196f3;
      color: white;
      padding: 8px 14px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .btn-delete-all {
      background-color: #e53935;
    }
    .btn-delete-all:hover {
      background-color: #c62828;
    }
  </style>
</head>
<body>
  <div class="chat-fullscreen">
    <div class="navbar">
      <div class="navbar-title">📖 Riwayat Curhat</div>
      <div class="button-row">
        <button class="btn-back" onclick="window.location.href='index.php'">⬅️ Kembali</button>
        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus semua pesan?')">
          <input type="hidden" name="action" value="hapus_semua">
          <button type="submit" class="btn-delete-all">🗑️ Hapus Semua</button>
        </form>
      </div>
    </div>

    <div id="chat-log" class="chat-messages">
      <?php if (empty($chatLog)): ?>
        <p>Tidak ada riwayat curhat.</p>
      <?php else: ?>
        <?php foreach ($chatLog as $msg): ?>
          <div class="chat-message-wrapper">
            <div class="chat-message <?= $msg['sender'] === 'user' ? 'user-message' : 'system-message' ?>">
              <small>
                [<?= date("H:i:s", strtotime($msg['timestamp'])) ?>]
                <?= $msg['emotion'] ? strtoupper($msg['emotion']) . ' -' : '' ?>
              </small><br>
              <?= htmlspecialchars($msg['message']) ?>
            </div>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="hapus">
              <input type="hidden" name="timestamp" value="<?= htmlspecialchars($msg['timestamp']) ?>">
              <button type="submit" class="delete-btn">🗑️</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
