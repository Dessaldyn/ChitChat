// Ambil elemen HTML
const video = document.getElementById('video');
const emotionLabel = document.getElementById('emotion-label');
const chatMessages = document.getElementById('chat-messages');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');

const typingIndicator = document.createElement('div');
typingIndicator.className = 'typing-indicator';
typingIndicator.innerText = 'Gemini sedang mengetik...';

const emotionPrompts = {
    happy: "Apa yang terjadi hari ini? Kamu terlihat sangat senang sekali! Kalau boleh, apakah kamu mau menceritakannya?",
    sad: "Aku melihat raut kesedihan di wajahmu. Tidak apa-apa untuk merasa sedih. Kalau kamu mau berbagi, aku di sini untuk mendengarkan.",
    angry: "Sepertinya ada sesuatu yang membuatmu marah. Terkadang, menceritakannya bisa membantu melepaskan beban. Ada apa?",
    surprised: "Wow, kamu terlihat terkejut! Apakah ada sesuatu yang tak terduga terjadi?",
    neutral: "Bagaimana kabarmu hari ini? Ceritakan apa saja yang sedang kamu pikirkan."
};

let lastAskedEmotion = null;
let firstFacePromptSent = false;
let currentFaceEmotion = 'neutral';
let chatLog = [];

// Simpan riwayat lokal
function saveChatLog() {
    localStorage.setItem("chitchat_log", JSON.stringify(chatLog));
}

// Ambil 3 pesan terakhir user
function getRecentContext(limit = 3) {
    return chatLog
        .filter(e => e.sender === 'user')
        .slice(-limit)
        .map(e => e.text)
        .join('\n');
}

// Format waktu
function formatTime() {
    const now = new Date();
    return `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')} | ${now.toLocaleDateString('id-ID')}`;
}

// Simpan ke database
function saveToDatabase(message, sender, emotion = null) {
    fetch('riwayat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message, sender, emotion })
    }).catch(console.error);
}

// Analisis ke Gemini
async function analyzeWithGemini(message, faceEmotion) {
    const context = getRecentContext();
    try {
        const res = await fetch('api_gemini.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, face_emotion: faceEmotion, context })
        });
        return await res.json();
    } catch (err) {
        console.error('Gagal menghubungi Gemini:', err);
        return null;
    }
}

// Tambah pesan ke UI
function addMessage(text, sender, emotion = null) {
    const entry = { text, sender, timestamp: new Date().toISOString() };
    chatLog.push(entry);
    saveChatLog();

    const wrapper = document.createElement('div');
    wrapper.classList.add('chat-message-wrapper');

    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    avatar.innerText = sender === 'user' ? '👤' : '🤖';

    const messageElement = document.createElement('div');
    messageElement.classList.add('chat-message', sender === 'user' ? 'user-message' : 'system-message');
    messageElement.innerText = text;

    const timeLabel = document.createElement('div');
    timeLabel.className = 'timestamp';
    timeLabel.innerText = formatTime();

    wrapper.appendChild(avatar);
    wrapper.appendChild(messageElement);
    wrapper.appendChild(timeLabel);
    chatMessages.appendChild(wrapper);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    saveToDatabase(text, sender, emotion);
}

// Form kirim chat
chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const userText = chatInput.value.trim();
    if (!userText) return;

    addMessage(userText, 'user');
    chatInput.value = '';

    chatMessages.appendChild(typingIndicator);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    const result = await analyzeWithGemini(userText, currentFaceEmotion);
    typingIndicator.remove();

    if (result && result.emotion) {
        emotionLabel.innerText = `Ekspresi Wajah: ${currentFaceEmotion} | Emosi Teks: ${result.emotion}`;

        if (result.action === 'saran') {
            addMessage(result.reason, 'system', result.emotion);
        } else {
            addMessage("Aku mendengarkan, silakan lanjutkan ceritamu.", 'system', result.emotion);
        }
    } else {
        addMessage("Maaf, saya tidak bisa memahami perasaanmu barusan.", 'system');
    }
});

// Load model face-api
async function loadModels() {
    const url = 'models';
    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(url),
        faceapi.nets.faceExpressionNet.loadFromUri(url),
        faceapi.nets.faceLandmark68TinyNet.loadFromUri(url)
    ]);
    console.log('Model berhasil dimuat');
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

// Deteksi wajah
function startDetection() {
    video.addEventListener('play', () => {
        setInterval(async () => {
            const detections = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks(true)
                .withFaceExpressions(true);

            if (detections.length > 0) {
                const expressions = detections[0].expressions;
                const maxEmotion = Object.entries(expressions).reduce((a, b) => a[1] > b[1] ? a : b)[0];
                currentFaceEmotion = maxEmotion;
                emotionLabel.innerText = `Ekspresi Wajah: ${maxEmotion}`;

                if (!firstFacePromptSent && emotionPrompts[maxEmotion]) {
                    addMessage(emotionPrompts[maxEmotion], 'system');
                    lastAskedEmotion = maxEmotion;
                    firstFacePromptSent = true;
                }
            } else {
                emotionLabel.innerText = 'Wajah tidak terdeteksi';
                currentFaceEmotion = 'neutral';
            }
        }, 1000);
    });
}

// Inisialisasi
document.addEventListener('DOMContentLoaded', async () => {
    await loadModels();
    await startVideo();
    startDetection();
});

// Reset chat saat keluar
window.addEventListener('beforeunload', () => {
    localStorage.removeItem("chitchat_log");
});

// Drag video popup
const videoPopup = document.querySelector('.video-popup');
if (videoPopup) {
    let isDragging = false;
    let offsetX = 0, offsetY = 0;

    videoPopup.addEventListener('mousedown', (e) => {
        if (e.target.tagName.toLowerCase() === 'video') return;
        isDragging = true;
        offsetX = e.clientX - videoPopup.offsetLeft;
        offsetY = e.clientY - videoPopup.offsetTop;
        videoPopup.style.transition = "none";
    });

    document.addEventListener('mousemove', (e) => {
        if (isDragging) {
            videoPopup.style.left = `${e.clientX - offsetX}px`;
            videoPopup.style.top = `${e.clientY - offsetY}px`;
        }
    });

    document.addEventListener('mouseup', () => isDragging = false);
}
