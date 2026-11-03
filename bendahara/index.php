<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
$user = require_login('bendahara');

$awal_bulan = date('Y-m-01');
$bulan_ini = db_row(
    "SELECT COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN nominal END), 0) AS masuk,
            COALESCE(SUM(CASE WHEN jenis = 'keluar' THEN nominal END), 0) AS keluar
       FROM transaksi_kas WHERE tanggal >= ?",
    [$awal_bulan]
);
$tunggakan = db_row(
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(t.nominal), 0) AS total
       FROM tagihan_anggota ta JOIN tagihan t ON t.id = ta.tagihan_id
      WHERE ta.status IN ('belum_bayar', 'ditolak')"
);
$menunggu = jumlah_menunggu();

$transaksi = db_all('SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT 6');
$tagihan = db_all(
    "SELECT t.id, t.judul, t.nominal, t.jatuh_tempo,
            (SELECT COUNT(*) FROM tagihan_anggota ta WHERE ta.tagihan_id = t.id) AS total,
            (SELECT COUNT(*) FROM tagihan_anggota ta WHERE ta.tagihan_id = t.id AND ta.status = 'lunas') AS lunas
       FROM tagihan t ORDER BY t.jatuh_tempo DESC, t.id DESC LIMIT 4"
);

page_header('Dashboard', 'bendahara', $user);
?>
<div class="page-head">
  <div>
    <h1>Dashboard</h1>
    <p class="muted">Ringkasan kas per <?= tanggal(date('Y-m-d')) ?></p>
  </div>
</div>

<?php if ($menunggu > 0): ?>
  <div class="alert alert-info">
    Ada <strong><?= $menunggu ?></strong> pembayaran yang menunggu verifikasi.
    <a href="verifikasi.php"><strong>Verifikasi sekarang →</strong></a>
  </div>
<?php endif; ?>

<div class="stats">
  <div class="stat highlight">
    <span>Saldo kas</span>
    <strong><?= rupiah(saldo_kas()) ?></strong>
    <small>total saat ini</small>
  </div>
  <div class="stat">
    <span>Masuk bulan ini</span>
    <strong class="text-success"><?= rupiah($bulan_ini['masuk']) ?></strong>
    <small><?= nama_bulan($awal_bulan) ?></small>
  </div>
  <div class="stat">
    <span>Keluar bulan ini</span>
    <strong class="text-danger"><?= rupiah($bulan_ini['keluar']) ?></strong>
    <small><?= nama_bulan($awal_bulan) ?></small>
  </div>
  <div class="stat">
    <span>Belum dibayar anggota</span>
    <strong><?= rupiah($tunggakan['total']) ?></strong>
    <small><?= (int)$tunggakan['jumlah'] ?> tagihan</small>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><h2>Tagihan terbaru</h2><a href="tagihan.php" class="small">Semua →</a></div>
    <?php if (!$tagihan): ?>
      <div class="empty">Belum ada tagihan. <a href="tagihan.php">Buat tagihan</a></div>
    <?php endif; ?>
    <?php foreach ($tagihan as $t): $persen = $t['total'] ? round($t['lunas'] / $t['total'] * 100) : 0; ?>
      <p style="margin: 0 0 14px;">
        <a href="tagihan_detail.php?id=<?= (int)$t['id'] ?>"><strong><?= e($t['judul']) ?></strong></a>
        <span class="muted small" style="float: right;"><?= (int)$t['lunas'] ?>/<?= (int)$t['total'] ?> lunas</span>
        <span class="progress" style="display: block; margin-top: 6px;"><span style="width: <?= $persen ?>%"></span></span>
      </p>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-head"><h2>Transaksi terakhir</h2><a href="laporan.php" class="small">Laporan →</a></div>
    <?php if (!$transaksi): ?>
      <div class="empty">Belum ada transaksi.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <tbody>
        <?php foreach ($transaksi as $tr): ?>
          <tr>
            <td class="nowrap muted small"><?= tanggal($tr['tanggal']) ?></td>
            <td><?= e($tr['keterangan']) ?></td>
            <td class="num <?= $tr['jenis'] === 'masuk' ? 'text-success' : 'text-danger' ?>">
              <?= $tr['jenis'] === 'masuk' ? '+' : '−' ?><?= rupiah($tr['nominal']) ?>
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
