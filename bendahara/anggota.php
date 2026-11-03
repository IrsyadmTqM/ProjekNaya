<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
$user = require_login('bendahara');

if (is_post()) {
    check_csrf();

    if (($_POST['aksi'] ?? '') === 'toggle') {
        db_query("UPDATE users SET aktif = 1 - aktif WHERE id = ? AND role = 'anggota'", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Status anggota diperbarui.');
        redirect('anggota.php');
    }

    $nama = trim($_POST['nama'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($nama === '' || !preg_match('/^[a-z0-9._]{3,30}$/', $username) || strlen($password) < 6) {
        flash('error', 'Nama wajib diisi, username 3–30 karakter (huruf kecil, angka, titik, garis bawah), password minimal 6 karakter.');
        redirect('anggota.php');
    }
    if (db_value('SELECT COUNT(*) FROM users WHERE username = ?', [$username])) {
        flash('error', 'Username "' . $username . '" sudah dipakai.');
        redirect('anggota.php');
    }

    db_query(
        "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'anggota')",
        [potong($nama, 100), $username, password_hash($password, PASSWORD_DEFAULT)]
    );
    flash('success', $nama . ' ditambahkan. Tagihan berikutnya akan otomatis dikirim ke anggota ini.');
    redirect('anggota.php');
}

$anggota = db_all(
    "SELECT u.id, u.nama, u.username, u.aktif,
            (SELECT COUNT(*) FROM tagihan_anggota ta WHERE ta.anggota_id = u.id AND ta.status IN ('belum_bayar', 'ditolak')) AS belum,
            (SELECT COUNT(*) FROM tagihan_anggota ta WHERE ta.anggota_id = u.id AND ta.status = 'lunas') AS lunas
       FROM users u
      WHERE u.role = 'anggota'
      ORDER BY u.aktif DESC, u.nama"
);

page_header('Anggota', 'bendahara', $user);
?>
<div class="page-head">
  <div>
    <h1>Anggota</h1>
    <p class="muted">Akun untuk login di website anggota.</p>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Tambah anggota</h2>
    <form method="post">
      <?= csrf_field() ?>
      <label>Nama lengkap <input name="nama" required maxlength="100"></label>
      <label>Username <input name="username" required pattern="[a-z0-9._]{3,30}" placeholder="misal: andi"></label>
      <label>Password awal <input name="password" required minlength="6"></label>
      <button class="btn btn-primary btn-block">Tambah</button>
    </form>
  </div>

  <div class="card">
    <h2>Daftar anggota (<?= count($anggota) ?>)</h2>
    <?php if (!$anggota): ?>
      <div class="empty">Belum ada anggota.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Nama</th><th>Lunas</th><th>Belum bayar</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($anggota as $a): ?>
          <tr class="<?= $a['aktif'] ? '' : 'muted' ?>">
            <td><strong><?= e($a['nama']) ?></strong> <div class="muted small">@<?= e($a['username']) ?></div></td>
            <td><?= (int)$a['lunas'] ?></td>
            <td class="<?= $a['belum'] ? 'text-danger' : '' ?>"><?= (int)$a['belum'] ?></td>
            <td><?= $a['aktif'] ? 'Aktif' : 'Nonaktif' ?></td>
            <td class="right">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="btn btn-ghost btn-sm"><?= $a['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php page_footer();
