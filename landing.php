<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ChitChat - Landing Page</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
  <style>
    body {
      background: linear-gradient(120deg, #141e30, #243b55);
      color: white;
      font-family: 'Segoe UI', sans-serif;
    }
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      text-align: center;
    }
    .btn-utama {
      background-color: #00bcd4;
      border: none;
      padding: 10px 20px;
      color: white;
      font-weight: bold;
      margin: 10px;
      border-radius: 25px;
    }
    .fitur-icon {
      font-size: 2rem;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand d-flex align-items-center">
      <img src="Logo_ChitChat-removebg-preview.png" alt="Logo" width="48" height="48" class="me-3 align-middle" style="border-radius:50%;object-fit:cover;">
      <strong>ChitChat</strong>
    </span>
    <div>
      <a href="login.php" class="btn btn-outline-light">Masuk</a>
    </div>
</nav>

  <!-- Hero Section -->
  <section class="hero container" data-aos="fade-up">
    <div class="mx-auto">
      <h1 class="display-4 fw-bold">Ruang Curhat Digital yang Memahami Kamu</h1>
      <p class="lead">Deteksi emosi wajah & teks, balasan empatik dari Gemini, dan ringkasan emosimu dalam satu tempat.</p>
      <a href="login.php" class="btn btn-utama">💬 Mulai Curhat</a>
      <a href="#fitur" class="btn btn-outline-light">🔍 Lihat Fitur</a>
    </div>
  </section>

  <!-- Fitur Section -->
  <section id="fitur" class="container py-5">
    <div class="row text-center">
      <div class="col-md-3" data-aos="fade-up">
        <div class="fitur-icon">🎭</div>
        <h5>Deteksi Emosi</h5>
        <p>Wajah dan teks kamu diproses secara real-time untuk mengenali suasana hatimu.</p>
      </div>
      <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
        <div class="fitur-icon">🤖</div>
        <h5>AI Empatik</h5>
        <p>Gemini memberikan respon yang nyambung dan penuh perhatian sesuai emosimu.</p>
      </div>
      <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
        <div class="fitur-icon">📊</div>
        <h5>Ringkasan Emosi</h5>
        <p>Di akhir sesi, kamu akan mendapat grafik dan ringkasan emosi yang kamu alami.</p>
      </div>
      <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
        <div class="fitur-icon">🔒</div>
        <h5>Privasi Terjaga</h5>
        <p>Semua curhatanmu aman tersimpan dan tidak dibagikan ke siapapun.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="text-center py-4">
    <p class="text-white-50">&copy; <?= date('Y') ?> ChitChat. All rights reserved.</p>
  </footer>

  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script>
    AOS.init();
  </script>
</body>
</html>
