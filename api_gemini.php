<?php
header('Content-Type: application/json');

// Ambil data dari JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');
$faceEmotion = strtolower(trim($data['face_emotion'] ?? ''));
$context = trim($data['context'] ?? '');

if (empty($message)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// === Prompt dengan konteks dan ekspresi ===
$prompt = <<<PROMPT
Kamu adalah AI pendengar yang empatik dan hangat bernama Gemini.
Tugasmu adalah menanggapi curhatan pengguna dengan perhatian dan empati.

Gunakan konteks berikut ini untuk memahami topik pembicaraan yang sedang berlangsung:
$context

Dan ini adalah pesan terbaru dari pengguna:
"$message"

Ekspresi wajah pengguna (tidak wajib, hanya pendukung): "$faceEmotion"

Berdasarkan kombinasi konteks dan pesan terbaru, tentukan:
- Emosi utama pengguna
- Aksi yang harus kamu ambil: apakah kamu perlu "mendengarkan" atau sudah saatnya memberikan "saran"
- Balasan empatik yang nyambung dengan konteks

Kembalikan dalam format JSON **VALID** seperti ini (jangan ada tambahan lain):
{
  "emotion": "sad | happy | angry | confused | neutral | lonely | anxious",
  "action": "saran | mendengarkan",
  "reason": "balasan empatik dan manusiawi yang kamu berikan"
}
PROMPT;

// === Kirim ke Gemini API (model 2.0 Flash) ===
$apiKey = 'AIzaSyAma0SiTKATeePBrOesjfhgY7xD_6JEa1s'; // Ganti dengan milikmu
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

$body = json_encode([
    'contents' => [[
        'parts' => [[ 'text' => $prompt ]]
    ]]
]);

$headers = ['Content-Type: application/json'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

// === Ambil hasil JSON dari teks Gemini
$result = json_decode($response, true);
$rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

preg_match('/\{.*\}/s', $rawText, $match);
if (!$match) {
    echo json_encode([
        'error' => 'Respons AI tidak valid',
        'raw' => $rawText
    ]);
    exit;
}

$parsed = json_decode($match[0], true);
$emotion = strtolower($parsed['emotion'] ?? 'neutral');
$action  = strtolower($parsed['action'] ?? 'mendengarkan');
$reason  = $parsed['reason'] ?? 'Aku mendengarkan, silakan lanjutkan ceritamu.';

// === Tambahan validasi dinamis
$triggerEmotions = ['sad', 'angry', 'confused', 'anxious', 'lonely'];
$wordCount = str_word_count($message);
$containsQuestion = strpos($message, '?') !== false;

if ($action !== 'saran') {
    if (in_array($emotion, $triggerEmotions) || $wordCount > 10 || $containsQuestion) {
        $action = 'saran';
    }
}

// === Kirim ke frontend
echo json_encode([
    'emotion' => $emotion,
    'action' => $action,
    'reason' => $reason
]);
