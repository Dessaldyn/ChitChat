<?php
// api_gemini.php
header('Content-Type: application/json');

// Ganti dengan API Key milikmu
$apiKey = 'AIzaSyAma0SiTKATeePBrOesjfhgY7xD_6JEa1s'; 

// Ambil input dari frontend
$input = json_decode(file_get_contents('php://input'), true);
$chatText = $input['message'] ?? '';

// Validasi
if (!$chatText) {
    echo json_encode(['error' => 'Pesan kosong']);
    exit;
}

// Buat prompt untuk Gemini
$prompt = "
Tolong analisis teks curhat berikut ini dan berikan hasil dalam format JSON.
Teks: \"$chatText\"

Jawaban harus seperti ini:
{
  \"emotion\": \"happy/sad/angry/neutral\",
  \"action\": \"saran/mendengarkan\",
  \"reason\": \"(penjelasan ringkas mengapa memilih emosi dan aksi tersebut)\"
}
";

// Siapkan payload
$payload = [
    'contents' => [[ 'parts' => [[ 'text' => $prompt ]]]]
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($curl);
if (curl_errno($curl)) {
    echo json_encode(['error' => curl_error($curl)]);
    exit;
}
curl_close($curl);

// Ambil dan parsing hasil
$data = json_decode($response, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

$jsonStart = strpos($text, '{');
$jsonEnd = strrpos($text, '}');
$jsonString = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);

$result = json_decode($jsonString, true);

// Validasi output Gemini
if (!$result || !isset($result['emotion'], $result['action'])) {
    echo json_encode(['error' => 'Respons AI tidak valid', 'raw' => $text]);
    exit;
}

// Kirim balik ke frontend
echo json_encode($result);
