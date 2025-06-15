<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$faceEmotion = $data['face_emotion'] ?? '';

if (empty($message)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// === Gemini Prompt ===
$prompt = "Berikan tanggapan empatik terhadap pesan berikut: '$message'. Ekspresi wajah pengguna saat ini: '$faceEmotion'. 
Gunakan isi pesan sebagai dasar utama dalam memahami emosi, dan gunakan ekspresi wajah hanya sebagai pendukung. 
Jangan hanya mendengarkan — berikan respon yang menunjukkan bahwa kamu benar-benar memperhatikan dan ingin memahami lebih dalam.";

// === Kirim ke Gemini (gunakan Gemini 2.0 endpoint terbaru) ===
$apiKey = 'AIzaSyAma0SiTKATeePBrOesjfhgY7xD_6JEa1s'; // Ganti dengan API key milikmu
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

$body = json_encode([
    'contents' => [[
        'parts' => [[ 'text' => $prompt ]]
    ]]
]);

$headers = [
    'Content-Type: application/json'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

$reason = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, saya tidak bisa memberikan respon saat ini.';

// === Analisis Emosi Berdasarkan Isi Balasan ===
$lowerText = strtolower($reason);
if (str_contains($lowerText, 'senang') || str_contains($lowerText, 'bahagia')) {
    $emotion = 'positif';
    $action = 'respon';
} elseif (str_contains($lowerText, 'sedih') || str_contains($lowerText, 'kecewa') || str_contains($lowerText, 'terpuruk')) {
    $emotion = 'sedih';
    $action = 'respon';
} elseif (str_contains($lowerText, 'marah') || str_contains($lowerText, 'kesal') || str_contains($lowerText, 'benci')) {
    $emotion = 'marah';
    $action = 'respon';
} elseif (str_contains($lowerText, 'baik') || str_contains($lowerText, 'netral')) {
    $emotion = 'netral';
    $action = 'respon';
} else {
    $emotion = 'netral';
    $action = 'dengar';
}

// === Output ===
echo json_encode([
    'emotion' => $emotion,
    'action' => $action,
    'reason' => $reason
]);
