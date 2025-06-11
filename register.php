<?php
session_start();
require_once 'db.php'; // koneksi ke database

$error = "";
$success = "";

// Proses jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  // Validasi sederhana
  if (empty($username) || empty($email) || empty($password)) {
    $error = "Semua field wajib diisi.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email tidak valid.";
  } else {
    // Cek apakah email sudah terdaftar
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
</head>
<body>
  <div class="chat-fullscreen">
    <div class="chat-header">📝 Daftar Akun ChitChat</div>

    <form method="post" class="chat-form" style="flex-direction: column; gap: 15px;">
      <?php if ($error): ?>
        <div style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($success): ?>
        <div style="color: green; text-align: center;"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <input type="text" name="username" placeholder="Nama Pengguna" required />
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Kata Sandi" required />
      <button type="submit">Daftar</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
      Sudah punya akun? <a href="login.php">Masuk di sini</a>
    </p>
  </div>
</body>
</html>
