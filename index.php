<?php
// Halaman depan: pintu masuk ke dua website
require __DIR__ . '/config/config.php';
require __DIR__ . '/includes/helpers.php';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(NAMA_ORGANISASI) ?></title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container narrow landing">
  <span class="brand-mark big" style="margin: 0 auto 12px; display: block;"></span>
  <h1><?= e(NAMA_ORGANISASI) ?></h1>
  <p class="muted">Sistem pembayaran uang kas. Pilih website sesuai peranmu.</p>

  <div class="portal-grid">
    <a class="portal role-anggota" href="anggota/login.php">
      <h2>Website Anggota</h2>
      <div class="muted">Untuk anggota yang membayar kas</div>
      <ul>
        <li>Lihat tagihan</li>
        <li>Upload bukti pembayaran</li>
        <li>Riwayat & status pembayaran</li>
      </ul>
    </a>
    <a class="portal role-bendahara" href="bendahara/login.php">
      <h2>Website Bendahara</h2>
      <div class="muted">Untuk bendahara yang mengelola kas</div>
      <ul>
        <li>Buat tagihan kas</li>
        <li>Verifikasi pembayaran</li>
        <li>Catat kas masuk/keluar & laporan</li>
      </ul>
    </a>
  </div>
</main>
</body>
</html>
