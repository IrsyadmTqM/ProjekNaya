<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
$user = require_login('bendahara');

if (is_post()) {
    check_csrf();
    $judul = trim($_POST['judul'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $nominal = parse_nominal($_POST['nominal'] ?? '');
    $jatuh_tempo = $_POST['jatuh_tempo'] ?? '';

    if ($judul === '' || $nominal <= 0 || !tanggal_valid($jatuh_tempo)) {
        flash('error', 'Judul, nominal, dan jatuh tempo wajib diisi dengan benar.');
        redirect('tagihan.php');
    }

    $pdo = db();
    $pdo->beginTransaction();
    db_query(
        'INSERT INTO tagihan (judul, keterangan, nominal, jatuh_tempo, dibuat_oleh) VALUES (?, ?, ?, ?, ?)',
        [potong($judul, 100), $keterangan === '' ? null : potong($keterangan, 255), $nominal, $jatuh_tempo, $user['id']]
    );
    $tagihan_id = (int)$pdo->lastInsertId();

    // Tagihan langsung dibagikan ke semua anggota yang aktif
    $jumlah = db_query(
        "INSERT INTO tagihan_anggota (tagihan_id, anggota_id)
         SELECT ?, id FROM users WHERE role = 'anggota' AND aktif = 1",
        [$tagihan_id]
    )->rowCount();
    $pdo->commit();

    flash('success', 'Tagihan "' . $judul . '" dibuat untuk ' . $jumlah . ' anggota.');
    redirect('tagihan_detail.php?id=' . $tagihan_id);
}

$tagihan = db_all(
    "SELECT t.*,
            (SELECT COUNT(*) FROM tagihan_anggota ta WHERE ta.tagihan_id = t.id) AS total,
            (SELECT COUNT(*) FROM tagihan_anggota ta WHERE ta.tagihan_id = t.id AND ta.status = 'lunas') AS lunas,
            (SELECT COUNT(*) FROM tagihan_anggota ta WHERE ta.tagihan_id = t.id AND ta.status = 'menunggu') AS menunggu
       FROM tagihan t
      ORDER BY t.jatuh_tempo DESC, t.id DESC"
);
$jumlah_anggota = (int)db_value("SELECT COUNT(*) FROM users WHERE role = 'anggota' AND aktif = 1");

page_header('Tagihan', 'bendahara', $user);
?>
<div class="page-head">
  <div>
    <h1>Tagihan kas</h1>
    <p class="muted">Tagihan baru otomatis muncul di website anggota.</p>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Buat tagihan baru</h2>
    <form method="post">
      <?= csrf_field() ?>
      <label>Judul
        <input name="judul" required maxlength="100" value="Kas <?= e(nama_bulan(date('Y-m-d'))) ?>">
      </label>
      <label>Nominal per anggota (Rp)
        <input name="nominal" required inputmode="numeric" placeholder="10000">
      </label>
      <label>Jatuh tempo
        <input type="date" name="jatuh_tempo" required value="<?= date('Y-m-t') ?>">
      </label>
      <label>Keterangan <span class="hint">(opsional)</span>
        <input name="keterangan" maxlength="255" placeholder="Iuran kas bulanan">
      </label>
      <button class="btn btn-primary btn-block">Buat tagihan</button>
      <p class="muted small center">Akan dikirim ke <?= $jumlah_anggota ?> anggota aktif.</p>
    </form>
  </div>

  <div class="card">
    <h2>Daftar tagihan</h2>
    <?php if (!$tagihan): ?>
      <div class="empty">Belum ada tagihan.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tagihan</th><th class="num">Nominal</th><th>Jatuh tempo</th><th>Progres</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tagihan as $t): $persen = $t['total'] ? round($t['lunas'] / $t['total'] * 100) : 0; ?>
          <tr>
            <td>
              <strong><?= e($t['judul']) ?></strong>
              <?php if ($t['keterangan']): ?><div class="muted small"><?= e($t['keterangan']) ?></div><?php endif; ?>
            </td>
            <td class="num"><?= rupiah($t['nominal']) ?></td>
            <td class="nowrap"><?= tanggal($t['jatuh_tempo']) ?></td>
            <td>
              <div class="progress"><span style="width: <?= $persen ?>%"></span></div>
              <div class="small muted"><?= (int)$t['lunas'] ?>/<?= (int)$t['total'] ?> lunas
                <?php if ($t['menunggu']): ?>· <span style="color: var(--warning)"><?= (int)$t['menunggu'] ?> menunggu</span><?php endif; ?>
              </div>
            </td>
            <td class="right"><a class="btn btn-ghost btn-sm" href="tagihan_detail.php?id=<?= (int)$t['id'] ?>">Detail</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php page_footer();
