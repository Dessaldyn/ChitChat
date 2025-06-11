<?php
session_start();
require_once 'db.php'; // koneksi ke database

// Cek jika user sudah login, redirect ke index
if (isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  // Validasi input sederhana
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email tidak valid.";
  } else {
    $sql = "SELECT user_id, username, password_hash FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $username, $hashedPassword);

    if ($stmt->num_rows > 0) {
      $stmt->fetch();
      if (password_verify($password, $hashedPassword)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
      } else {
        $error = "Kata sandi salah.";
      }
    } else {
      $error = "Email tidak ditemukan.";
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login - ChitChat</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="chat-fullscreen">
    <div class="chat-header">🔐 Login ke ChitChat</div>

    <form method="post" class="chat-form" style="flex-direction: column; gap: 15px;">
      <?php if ($error): ?>
        <div style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Kata Sandi" required />
      <button type="submit">Masuk</button>
    </form>

    <p style="text-align:center; margin-top:20px;">
      Belum punya akun? <a href="register.php">Daftar di sini</a>
    </p>
  </div>
</body>
</html>
