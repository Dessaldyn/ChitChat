<?php
session_start();
require_once 'db.php';

// Jika sudah login, langsung ke index
if (isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email tidak valid.";
  } else {
    $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE email = ?");
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
    .login-box {
      background: #1f1f1f;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 0 12px rgba(255, 255, 255, 0.1);
      max-width: 400px;
      width: 100%;
    }
    .login-box h2 {
      text-align: center;
      margin-bottom: 25px;
    }
    .login-box input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: none;
      border-radius: 5px;
    }
    .login-box button {
      width: 100%;
      padding: 10px;
      background-color: #7e57c2;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .login-box button:hover {
      background-color: #6a46b0;
    }
    .login-box a {
      color: #aaa;
      text-decoration: none;
      font-size: 14px;
      display: block;
      margin-top: 15px;
      text-align: center;
    }
    .error-msg {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="login-box" data-aos="fade-up">
    <h2>🔐 Login ke ChitChat</h2>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Kata Sandi" required />
      <button type="submit">Masuk</button>
    </form>

    <a href="register.php">Belum punya akun? Daftar di sini</a>
    <a href="landing.php">⬅️ Kembali ke Beranda</a>
  </div>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>AOS.init();</script>
</body>
</html>
