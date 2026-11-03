<?php
// Kerangka HTML (header, menu, footer) untuk kedua website

function menu_untuk(string $role): array
{
    if ($role === 'bendahara') {
        return [
            'index.php' => 'Dashboard',
            'tagihan.php' => 'Tagihan',
            'verifikasi.php' => 'Verifikasi',
            'kas.php' => 'Catat Kas',
            'laporan.php' => 'Laporan',
            'anggota.php' => 'Anggota',
        ];
    }
    return [
        'index.php' => 'Tagihan Saya',
        'riwayat.php' => 'Riwayat',
    ];
}

function nama_situs(string $role): string
{
    return $role === 'bendahara' ? 'Panel Bendahara' : 'Portal Anggota';
}

function page_header(string $title, string $role, ?array $user = null): void
{
    $halaman = basename($_SERVER['SCRIPT_NAME']);
    $induk = ['tagihan_detail.php' => 'tagihan.php', 'bayar.php' => 'index.php'];
    $aktif = $induk[$halaman] ?? $halaman;
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · <?= e(nama_situs($role)) ?></title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="role-<?= e($role) ?>">
<?php if ($user): ?>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="index.php">
      <span class="brand-mark"></span>
      <span><?= e(NAMA_ORGANISASI) ?> <small><?= e(nama_situs($role)) ?></small></span>
    </a>
    <nav class="nav">
      <?php foreach (menu_untuk($role) as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $file === $aktif ? 'active' : '' ?>">
          <?= e($label) ?>
          <?php if ($file === 'verifikasi.php' && ($n = jumlah_menunggu()) > 0): ?><span class="count"><?= $n ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="user">
      <span class="user-name"><?= e($user['nama']) ?></span>
      <a href="logout.php" class="btn btn-sm btn-on-accent">Logout</a>
    </div>
  </div>
</header>
<?php endif; ?>
<main class="container">
<?php foreach (take_flash() as [$tipe, $pesan]): ?>
  <div class="alert alert-<?= e($tipe) ?>"><?= e($pesan) ?></div>
<?php endforeach; ?>
<?php
}

function page_footer(): void
{
    echo "</main>\n</body>\n</html>\n";
}

function render_login(string $role, ?string $error): void
{
    page_header('Login', $role);
    ?>
  <div class="login-wrap">
    <form method="post" class="card login-card">
      <div class="login-head">
        <span class="brand-mark big"></span>
        <h1>Login <?= $role === 'bendahara' ? 'Bendahara' : 'Anggota' ?></h1>
        <p class="muted"><?= e(NAMA_ORGANISASI) ?> · <?= e(nama_situs($role)) ?></p>
      </div>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <label>Username
        <input name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
      </label>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button class="btn btn-primary btn-block">Masuk</button>
      <p class="muted small center"><a href="../index.php">← Halaman utama</a></p>
    </form>
  </div>
<?php
    page_footer();
}
