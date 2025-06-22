<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Ambil data chat dengan emosi untuk grafik
$sql = "SELECT emotion, timestamp FROM chat_logs WHERE user_id = ? AND emotion IS NOT NULL ORDER BY timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$emotionData = [];
while ($row = $result->fetch_assoc()) {
  $emotion = $row['emotion'];
  $timestamp = $row['timestamp'];
  if (!isset($emotionData[$emotion])) {
    $emotionData[$emotion] = ["count" => 0, "timestamps" => []];
  }
  $emotionData[$emotion]['count']++;
  $emotionData[$emotion]['timestamps'][] = $timestamp;
}
$stmt->close();

// Hitung emosi dominan
$dominantEmotion = "-";
$max = 0;
foreach ($emotionData as $emotion => $data) {
  if ($data['count'] > $max) {
    $max = $data['count'];
    $dominantEmotion = $emotion;
  }
}

// Ambil semua pesan user
$sqlChat = "SELECT message FROM chat_logs WHERE user_id = ? AND sender = 'user' ORDER BY timestamp ASC";
$stmt = $conn->prepare($sqlChat);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$allMessages = [];
while ($row = $result->fetch_assoc()) {
  $allMessages[] = $row['message'];
}
$stmt->close();
$conn->close();

// Siapkan prompt untuk Gemini
$chatText = implode("\n", $allMessages);
$prompt = <<<PROMPT
Berikut ini adalah isi percakapan seorang pengguna dalam sesi curhat:

$chatText

Buatlah ringkasan singkat dari perasaan pengguna selama sesi ini. Lalu berikan saran yang empatik dan membantu agar ia bisa merasa lebih baik.

Formatkan hasilmu dalam JSON valid:
{
  "summary": "Rangkuman singkat perasaan pengguna.",
  "suggestion": "Saran empatik yang bisa diberikan kepadanya."
}
PROMPT;

// Fungsi untuk kirim ke Gemini API
function kirimKeGemini($prompt) {
  $apiKey = 'AIzaSyAma0SiTKATeePBrOesjfhgY7xD_6JEa1s'; // Ganti jika perlu
  $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

  $body = json_encode([
    'contents' => [[
      'parts' => [[ 'text' => $prompt ]]
    ]]
  ]);

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $response = curl_exec($ch);
  curl_close($ch);

  return $response;
}

// Ambil JSON dari teks Gemini
function ambilJsonDariTeks($text) {
  preg_match('/\{.*\}/s', $text, $match);
  return $match[0] ?? '{}';
}

// Kirim ke Gemini
$response = kirimKeGemini($prompt);
$result = json_decode($response, true);
$raw = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
$parsed = json_decode(ambilJsonDariTeks($raw), true);

$labels = array_keys($emotionData);
$counts = array_map(fn($e) => $e['count'], $emotionData);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ringkasan Emosi - ChitChat</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: sans-serif; margin: 20px; background: #f4f4f4; }
    h1 { color: #333; }
    .summary-box {
      background: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 700px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    canvas { margin-top: 20px; }
    .btn-back, .btn-logout {
      margin-top: 30px;
      display: inline-block;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      margin-right: 10px;
    }
    .btn-back { background: #2196f3; color: white; }
    .btn-logout { background: #e53935; color: white; }
    .summary-section {
      margin-top: 20px;
      background: #f9f9f9;
      padding: 15px;
      border-left: 4px solid #7e57c2;
      border-radius: 6px;
    }
  </style>
</head>
<body>
  <div class="summary-box">
    <h1>Ringkasan Emosi Anda</h1>
    <p><strong>Emosi Dominan:</strong> <?= ucfirst($dominantEmotion) ?></p>

    <canvas id="emotionChart" width="400" height="300"></canvas>

    <?php if ($parsed): ?>
      <div class="summary-section">
        <p><strong>Ringkasan Perasaan:</strong><br><?= htmlspecialchars($parsed['summary'] ?? '-') ?></p>
        <p><strong>Saran:</strong><br><?= htmlspecialchars($parsed['suggestion'] ?? '-') ?></p>
      </div>
    <?php endif; ?>

    <a href="index.php" class="btn-back">⬅️ Kembali ke Chat</a>
    <a href="logout.php" class="btn-logout">🚪 Keluar Aplikasi</a>
  </div>

  <script>
    const ctx = document.getElementById('emotionChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
          label: 'Frekuensi Emosi',
          data: <?= json_encode($counts) ?>,
          backgroundColor: '#7e57c2'
        }]
      },
      options: {
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } }
      }
    });
  </script>
</body>
</html>
