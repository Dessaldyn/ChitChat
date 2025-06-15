// Ambil elemen HTML
const video = document.getElementById('video');
const emotionLabel = document.getElementById('emotion-label');
const chatMessages = document.getElementById('chat-messages');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');

// === Variabel untuk face expression ===
let faceEmotionSupport = null;
let hasAskedFromFace = false;

// Pertanyaan pembuka berdasarkan emosi wajah (sekali saja)
const emotionPrompts = {
    happy: "Apa yang terjadi hari ini? Kamu terlihat sangat senang sekali! Kalau boleh, apakah kamu mau menceritakannya?",
    sad: "Aku melihat raut kesedihan di wajahmu. Tidak apa-apa untuk merasa sedih. Kalau kamu mau berbagi, aku di sini untuk mendengarkan.",
    angry: "Sepertinya ada sesuatu yang membuatmu marah. Terkadang, menceritakannya bisa membantu melepaskan beban. Ada apa?",
    surprised: "Wow, kamu terlihat terkejut! Apakah ada sesuatu yang tak terduga terjadi?",
    neutral: "Bagaimana kabarmu hari ini? Ceritakan apa saja yang sedang kamu pikirkan."
};

let chatLog = [];

// Simpan log ke localStorage
function saveChatLog() {
    localStorage.setItem("chitchat_log", JSON.stringify(chatLog));
}

// Simpan ke database
function saveToDatabase(message, sender, emotion = null) {
    fetch('riwayat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, sender, emotion })
    }).catch(error => console.error("Gagal menyimpan ke database:", error));
}

// Kirim ke Gemini (dengan faceEmotion support)
async function analyzeWithGemini(message, faceEmotion = null) {
    try {
        const res = await fetch('api_gemini.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, faceEmotion })
        });
        return await res.json();
    } catch (err) {
        console.error('Gagal menghubungi Gemini:', err);
        return null;
    }
}

// Tampilkan pesan
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

// Event saat user submit chat
chatForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const userText = chatInput.value.trim();
    if (userText === '') return;

    addMessage(userText, 'user');
    chatInput.value = '';

    const result = await analyzeWithGemini(userText, faceEmotionSupport);

    if (result && result.emotion) {
        emotionLabel.innerText = `Emosi: ${result.emotion}`;

        if (result.action === 'saran' || result.action === 'respon') {
            addMessage(result.reason, 'system', result.emotion);
        } else if (result.action === 'dengar') {
            addMessage("Aku mendengarkan, lanjutkan ceritamu ya.", 'system', result.emotion);
        } else {
            addMessage("Baik, aku mencatat perasaanmu.", 'system', result.emotion);
        }
    } else {
        addMessage("Maaf, saya belum bisa memahami perasaanmu barusan.", 'system');
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
    console.log('Model face-api.js dimuat');
}

// Aktifkan kamera
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

// Deteksi ekspresi wajah (sekali tanya, lalu hanya support)
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

                faceEmotionSupport = maxEmotion;
                emotionLabel.innerText = `Ekspresi Wajah: ${maxEmotion}`;

                if (emotionPrompts[maxEmotion] && !hasAskedFromFace) {
                    addMessage(emotionPrompts[maxEmotion], 'system');
                    hasAskedFromFace = true;
                }
            } else {
                emotionLabel.innerText = 'Wajah tidak terdeteksi';
                faceEmotionSupport = null;
            }
        }, 1000);
    });
}

// Bersihkan riwayat saat keluar
window.addEventListener('beforeunload', () => {
    localStorage.removeItem("chitchat_log");
});

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', async () => {
    await loadModels();
    await startVideo();
    startDetection();
});

// === Drag Kamera (video-popup) ===
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
