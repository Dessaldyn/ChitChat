// Ambil elemen HTML
const video = document.getElementById('video');
const emotionLabel = document.getElementById('emotion-label');
const chatMessages = document.getElementById('chat-messages');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');

// Database pertanyaan berdasarkan emosi
const emotionPrompts = {
    happy: "Apa yang terjadi hari ini? Kamu terlihat sangat senang sekali! Kalau boleh, apakah kamu mau menceritakannya?",
    sad: "Aku melihat raut kesedihan di wajahmu. Tidak apa-apa untuk merasa sedih. Kalau kamu mau berbagi, aku di sini untuk mendengarkan.",
    angry: "Sepertinya ada sesuatu yang membuatmu marah. Terkadang, menceritakannya bisa membantu melepaskan beban. Ada apa?",
    surprised: "Wow, kamu terlihat terkejut! Apakah ada sesuatu yang tak terduga terjadi?",
    neutral: "Bagaimana kabarmu hari ini? Ceritakan apa saja yang sedang kamu pikirkan."
};

let lastAskedEmotion = null;

// === Chat Log untuk Riwayat ===
let chatLog = [];

function saveChatLog() {
    localStorage.setItem("chitchat_log", JSON.stringify(chatLog));
}

// Kirim chat ke backend PHP (riwayat.php) untuk disimpan ke MySQL
function saveToDatabase(message, sender, emotion = null) {
    fetch('riwayat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, sender, emotion })
    }).catch(error => console.error("Gagal menyimpan ke database:", error));
}

// Tambahan: Kirim ke API Gemini
async function analyzeWithGemini(message) {
    try {
        const res = await fetch('api_gemini.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message })
        });
        return await res.json();
    } catch (err) {
        console.error('Gagal menghubungi Gemini:', err);
        return null;
    }
}

// Fungsi menampilkan pesan di chat
function addMessage(text, sender, emotion = null) {
    const entry = {
        text,
        sender,
        timestamp: new Date().toISOString()
    };
    chatLog.push(entry);
    saveChatLog();

    const messageElement = document.createElement('div');
    messageElement.classList.add('chat-message', sender === 'user' ? 'user-message' : 'system-message');
    messageElement.innerText = text;
    chatMessages.appendChild(messageElement);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    saveToDatabase(text, sender, emotion);
}

// Form Chat
chatForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const userText = chatInput.value.trim();
    if (userText === '') return;

    addMessage(userText, 'user');
    chatInput.value = '';

    // Kirim ke Gemini
    const result = await analyzeWithGemini(userText);

    if (result && result.emotion) {
        emotionLabel.innerText = `Emosi: ${result.emotion}`;

        if (result.action === 'saran') {
            addMessage(result.reason, 'system', result.emotion);
        } else {
            console.log("Gemini hanya mendengarkan, tidak memberi saran.");
        }
    } else {
        addMessage("Maaf, saya tidak bisa memahami perasaanmu barusan.", 'system');
    }
});

// Load model face-api.js
async function loadModels() {
    const url = 'models';
    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(url),
        faceapi.nets.faceExpressionNet.loadFromUri(url),
        faceapi.nets.faceLandmark68TinyNet.loadFromUri(url)
    ]);
    console.log('Model berhasil dimuat');
}

// Nyalakan kamera
async function startVideo() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
        console.log("Kamera aktif");
    } catch (err) {
        alert("Tidak bisa mengakses kamera.");
        console.error(err);
    }
}

// Deteksi ekspresi wajah & tanya sesuai emosi
function startDetection() {
    video.addEventListener('play', () => {
        const displaySize = { width: video.width, height: video.height };

        setInterval(async () => {
            const detections = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks(true)
                .withFaceExpressions(true);

            if (detections.length > 0) {
                const expressions = detections[0].expressions;
                const maxEmotion = Object.entries(expressions).reduce((a, b) => a[1] > b[1] ? a : b)[0];

                emotionLabel.innerText = `Ekspresi Wajah: ${maxEmotion}`;

                if (emotionPrompts[maxEmotion] && maxEmotion !== lastAskedEmotion) {
                    addMessage(emotionPrompts[maxEmotion], 'system');
                    lastAskedEmotion = maxEmotion;
                }
            } else {
                emotionLabel.innerText = 'Wajah tidak terdeteksi';
                lastAskedEmotion = null;
            }
        }, 1000);
    });
}

// Bersihkan log saat reload
window.addEventListener('beforeunload', () => {
    localStorage.removeItem("chitchat_log");
});

// Mulai semua proses
document.addEventListener('DOMContentLoaded', async () => {
    await loadModels();
    await startVideo();
    startDetection();
});

// Drag kamera
const videoPopup = document.querySelector('.video-popup');

if (videoPopup) {
    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    videoPopup.addEventListener('mousedown', function (e) {
        if (e.target.tagName.toLowerCase() === 'video') return;

        isDragging = true;
        offsetX = e.clientX - videoPopup.offsetLeft;
        offsetY = e.clientY - videoPopup.offsetTop;
        videoPopup.style.transition = "none";
    });

    document.addEventListener('mousemove', function (e) {
        if (isDragging) {
            videoPopup.style.left = `${e.clientX - offsetX}px`;
            videoPopup.style.top = `${e.clientY - offsetY}px`;
        }
    });

    document.addEventListener('mouseup', function () {
        isDragging = false;
    });
}
