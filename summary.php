<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once 'db.php';
$user_id = $_SESSION['user_id'];

// Ambil data emosi
$emotionData = [];
$stmt = $conn->prepare("SELECT emotion, timestamp FROM chat_logs WHERE user_id = ? AND emotion IS NOT NULL ORDER BY timestamp ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
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

// Ambil seluruh pesan user
$allMessages = [];
$stmt = $conn->prepare("SELECT message FROM chat_logs WHERE user_id = ? AND sender = 'user' ORDER BY timestamp ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $allMessages[] = $row['message'];
}
$stmt->close();
$conn->close();

// Siapkan prompt
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

// Kirim ke Gemini
function kirimKeGemini($prompt) {
  $apiKey = 'AIzaSyAma0SiTKATeePBrOesjfhgY7xD_6JEa1s'; // Ganti dengan API key milikmu
  $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
  $body = json_encode(['contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]]]);
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
  ]);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

// Ambil dan parsing JSON dari response Gemini
function ambilJsonDariTeks($text) {
  preg_match('/\{.*\}/s', $text, $match);
  return $match[0] ?? '{}';
}

// Eksekusi Gemini
$parsed = [];
$response = kirimKeGemini($prompt);
if ($response) {
  $result = json_decode($response, true);
  $raw = '';
  if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $raw = $result['candidates'][0]['content']['parts'][0]['text'];
  }
  $parsed = json_decode(ambilJsonDariTeks($raw), true);
}

// Fallback jika kosong
if (!$parsed || !isset($parsed['summary'])) {
  $parsed['summary'] = 'Tidak ada ringkasan tersedia.';
}
if (!isset($parsed['suggestion'])) {
  $parsed['suggestion'] = 'Tidak ada saran tersedia.';
}

$labels = array_keys($emotionData);
$counts = array_map(fn($e) => $e['count'], $emotionData);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Ringkasan Emosi - ChitChat</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #1e1e2f;
      color: #f0f0f0;
      margin: 0;
      padding: 30px 15px;
    }
    .summary-box {
      background: #2a2a3f;
      padding: 30px;
      border-radius: 12px;
      max-width: 750px;
      margin: auto;
      box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }
    h1 {
      font-size: 24px;
      margin-bottom: 10px;
      text-align: center;
    }
    .highlight {
      color: #ffb74d;
      font-weight: bold;
    }
    canvas {
      margin-top: 20px;
      background: #fff;
      border-radius: 10px;
    }
    .summary-section {
      background: #35354f;
      padding: 20px;
      border-radius: 8px;
      margin-top: 25px;
    }
    .summary-section strong {
      color: #d0aaff;
    }
    .btn-group {
      text-align: center;
      margin-top: 30px;
    }
    .btn-group a {
      text-decoration: none;
      display: inline-block;
      padding: 10px 18px;
      margin: 5px;
      border-radius: 6px;
      font-weight: 600;
    }
    .btn-back {
      background: #6c63ff;
      color: white;
    }
    .btn-logout {
      background: #e53935;
      color: white;
    }
  </style>
</head>
<body>
  <div class="summary-box">
    <h1>📊 Ringkasan Emosi Anda</h1>
    <p>Emosi yang paling sering Anda tunjukkan adalah: <span class="highlight"><?= ucfirst($dominantEmotion) ?></span></p>

    <canvas id="emotionChart" width="400" height="250"></canvas>

    <div class="summary-section">
      <p><strong>Ringkasan Perasaan:</strong><br><?= htmlspecialchars($parsed['summary']) ?></p>
      <p><strong>Saran untuk Anda:</strong><br><?= htmlspecialchars($parsed['suggestion']) ?></p>
    </div>

    <div class="btn-group">
      <a href="index.php" class="btn-back">⬅️ Kembali ke Chat</a>
      <a href="logout.php" class="btn-logout">🚪 Keluar Aplikasi</a>
    </div>
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
        responsive: true,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: false } }
      }
    });
  </script>
</body>
</html>
