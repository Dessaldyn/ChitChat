<?php
session_start();

// Redirect ke login jika belum login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

// Ambil nama pengguna jika tersedia
$username = $_SESSION['username'] ?? 'Pengguna';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ChitChat</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar">
  <div class="navbar-brand">
    <img src="logo.svg" alt="ChitChat Logo" height="24" />
    💬 <span class="brand-text">ChitChat</span>
    <button class="chat-history-button" onclick="window.location.href='riwayat.php'">
      📖 Riwayat Chat
    </button>
    <button onclick="window.location.href='logout.php'">🚪 Logout</button>
  </div>
  </nav>


  <!-- Area Chat Fullscreen -->
  <div class="chat-fullscreen">
    <div id="chat-messages" class="chat-messages"></div>

    <form id="chat-form" class="chat-form">
      <input 
        type="text" 
        id="chat-input" 
        placeholder="Tulis ceritamu di sini..." 
        autocomplete="off" 
        required
      />
      <button type="submit">Kirim</button>
    </form>
  </div>

  <!-- Webcam sebagai popup draggable + resizable -->
  <div class="video-popup" id="videoPopup" style="resize: both; overflow: hidden;">
    <video id="video" autoplay muted playsinline></video>
    <div id="emotion-label">Mendeteksi emosi...</div>
  </div>

  <!-- Script Deteksi Emosi -->
  <script src="face-api.min.js"></script>
  <script src="script.js"></script>
</body>
</html>
