<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$username = $_SESSION['username'] ?? 'Pengguna';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ChitChat - Sesi Curhat</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to bottom, #1e1e2f, #2e2e4d);
      color: white;
      display: flex;
      flex-direction: column;
      height: 100vh;
    }

    nav.navbar {
      background: #28224f;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: bold;
      font-size: 1.1rem;
    }

    .chat-history-button,
    .navbar button {
      background: #7e57c2;
      border: none;
      color: white;
      padding: 6px 12px;
      margin-left: 10px;
      border-radius: 6px;
      cursor: pointer;
    }

    .chat-history-button:hover {
      background: #6a46b1;
    }

    .chat-fullscreen {
      flex: 1;
      display: flex;
      flex-direction: column;
      padding: 15px;
      max-width: 800px;
      margin: auto;
      width: 100%;
    }

    .chat-messages {
      flex: 1;
      overflow-y: auto;
      margin-bottom: 15px;
      background: #1a1a2e;
      padding: 15px;
      border-radius: 10px;
      box-shadow: inset 0 0 10px rgba(0,0,0,0.3);
    }

    .chat-form {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    #chat-input {
      flex: 1;
      padding: 12px;
      border-radius: 8px;
      border: none;
      font-size: 1rem;
      background-color: #2a2a40;
      color: white;
    }

    #chat-form button {
      padding: 10px 16px;
      font-size: 1rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    #chat-form button[type="submit"] {
      background-color: #6a1b9a;
      color: white;
    }

    #chat-form button#end-session {
      background-color: #ff595e;
      color: white;
    }

    .video-popup {
      position: fixed;
      bottom: 20px;
      right: 20px;
      border: 2px solid #ccc;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.4);
      overflow: hidden;
      width: 200px;
      background: #111;
    }

    #video {
      width: 100%;
      display: block;
      border-bottom: 1px solid #444;
    }

    #emotion-label {
      padding: 8px;
      text-align: center;
      font-size: 0.9rem;
      background: #2b2b40;
      color: #ccc;
    }

    .chat-message-wrapper {
      margin-bottom: 10px;
    }

    .chat-message {
      padding: 10px 14px;
      border-radius: 10px;
      margin-bottom: 4px;
      max-width: 70%;
      line-height: 1.4;
      word-wrap: break-word;
    }

    .user-message {
      background: #3a3a65;
      align-self: flex-end;
      margin-left: auto;
    }

    .system-message {
      background: #593f90;
      align-self: flex-start;
    }

    .timestamp {
      font-size: 0.7rem;
      color: #aaa;
      margin-top: 2px;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="navbar-brand" style="font-size:1.5rem;">
      <img src="Logo_ChitChat-removebg-preview.png" alt="ChitChat Logo" height="60" class="me-2 align-middle" style="border-radius:50%;object-fit:cover;">
      <strong>ChitChat</strong> - Selamat datang, <?= htmlspecialchars($username) ?>
    </div>
    <div>
      <button class="chat-history-button" onclick="window.location.href='riwayat.php'">📖 Riwayat</button>
      <button onclick="window.location.href='logout.php'">🚪 Logout</button>
    </div>
</nav>

  <!-- Chat Area -->
  <div class="chat-fullscreen" data-aos="fade-up">
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
      <button type="button" id="end-session" onclick="window.location.href='summary.php'">Akhiri</button>
    </form>
  </div>

  <!-- Webcam Display -->
  <div class="video-popup" id="videoPopup">
    <video id="video" autoplay muted playsinline></video>
    <div id="emotion-label">Mendeteksi emosi...</div>
  </div>

  <!-- Script -->
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>AOS.init();</script>
  <script src="face-api.min.js"></script>
  <script src="script.js"></script>
</body>
</html>
