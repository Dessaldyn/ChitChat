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

// === Label emosi & skoring intensitas ===
$emotionLabels = [
    'neutral' => 0,
    'happy' => 1,
    'confused' => 2,
    'sad' => 3,
    'lonely' => 4,
    'anxious' => 5,
    'angry' => 6
];

// === Prompt untuk Gemini (lebih natural dan adaptif) ===
$prompt = <<<PROMPT
Kamu adalah Gemini, AI teman curhat yang empatik dan suportif. 
Gunakan konteks di bawah ini untuk memahami situasi pengguna:

Konteks:
"$context"

Pesan terbaru pengguna:
"$message"

Ekspresi wajah pengguna (hanya pendukung): "$faceEmotion"

Tugasmu:
1. Pahami emosi utama dari pesan pengguna.
2. Tentukan apakah kamu harus mendengarkan (jika cerita masih awal atau belum cukup jelas) atau memberikan saran (jika pengguna tampak butuh solusi).
3. Balas dengan nada empatik, alami, dan sesuai konteks.

Jawaban kamu harus dalam format JSON seperti ini:
{
  "emotion": "sad | happy | angry | confused | neutral | lonely | anxious",
  "action": "saran | mendengarkan",
  "reason": "respon empatik dan personal kamu"
}
PROMPT;

// === Kirim ke Gemini API ===
$apiKey = 'AIzaSyAma0SiTKATeePBrOesjfhgY7xD_6JEa1s';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

$body = json_encode([
    'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]]
]);
$headers = ['Content-Type: application/json'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

// === Ambil hasil respon dari Gemini ===
$result = json_decode($response, true);
$rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

preg_match('/\{.*\}/s', $rawText, $match);
if (!$match) {
    echo json_encode(['error' => 'Respons AI tidak valid', 'raw' => $rawText]);
    exit;
}

$parsed = json_decode($match[0], true);
$textEmotion = strtolower($parsed['emotion'] ?? 'neutral');
$action = strtolower($parsed['action'] ?? 'mendengarkan');
$reason = $parsed['reason'] ?? 'Aku mendengarkan, silakan lanjutkan ceritamu.';

// === Skor untuk teks dan face
$textLabel = $emotionLabels[$textEmotion] ?? 0;
$faceLabel = $emotionLabels[$faceEmotion] ?? 0;

// === Validasi threshold & bobot adaptif
if (abs($textLabel - $faceLabel) > 2 || $faceEmotion === 'neutral') {
    $finalLabel = $textLabel;
    $finalEmotion = $textEmotion;
} else {
    $finalScore = ($textLabel * 0.7) + ($faceLabel * 0.3);
    $finalEmotion = array_reduce(array_keys($emotionLabels), function ($carry, $key) use ($emotionLabels, $finalScore) {
        return (abs($emotionLabels[$key] - $finalScore) < abs($emotionLabels[$carry] - $finalScore)) ? $key : $carry;
    }, 'neutral');
}

// === Validasi aksi (jika belum "saran", tapi pengguna tampak butuh)
$triggerEmotions = ['sad', 'angry', 'confused', 'anxious', 'lonely'];
$wordCount = str_word_count($message);
$containsQuestion = strpos($message, '?') !== false;

if ($action !== 'saran') {
    if (in_array($finalEmotion, $triggerEmotions) || $wordCount > 10 || $containsQuestion) {
        $action = 'saran';
    }
}

// === Kirim ke frontend
echo json_encode([
    'emotion' => $finalEmotion,
    'action' => $action,
    'reason' => $reason
]);
