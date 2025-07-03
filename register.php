<?php
session_start();
require_once 'db.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($email) || empty($password)) {
    $error = "Semua field wajib diisi.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email tidak valid.";
  } else {
    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      $error = "Email sudah terdaftar.";
    } else {
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      $insert = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
      $insert->bind_param("sss", $username, $email, $passwordHash);
      if ($insert->execute()) {
        $success = "Pendaftaran berhasil. Silakan login.";
      } else {
        $error = "Terjadi kesalahan saat menyimpan data.";
      }
      $insert->close();
    }

    $check->close();
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar - ChitChat</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to bottom, #0f0f0f, #1c1c1c);
      color: white;
      font-family: sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .register-box {
      background: #1f1f1f;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(255, 255, 255, 0.08);
      max-width: 450px;
      width: 100%;
    }
    .register-box h2 {
      text-align: center;
      margin-bottom: 25px;
    }
    .register-box input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: none;
      border-radius: 5px;
    }
    .register-box button {
      width: 100%;
      padding: 10px;
      background-color: #7e57c2;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .register-box button:hover {
      background-color: #6a46b0;
    }
    .register-box a {
      color: #aaa;
      text-decoration: none;
      font-size: 14px;
      display: block;
      margin-top: 15px;
      text-align: center;
    }
    .msg-success {
      color: lightgreen;
      text-align: center;
      margin-bottom: 10px;
    }
    .msg-error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="register-box" data-aos="zoom-in">
    <h2>📝 Daftar Akun ChitChat</h2>

    <?php if ($error): ?>
      <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="text" name="username" placeholder="Nama Pengguna" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Kata Sandi" required />
      <button type="submit">Daftar</button>
    </form>

    <a href="login.php">Sudah punya akun? Masuk di sini</a>
    <a href="landing.php">⬅️ Kembali ke Beranda</a>
  </div>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>AOS.init();</script>
</body>
</html>
