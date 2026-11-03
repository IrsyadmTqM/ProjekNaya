<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
$user = require_login('bendahara');

$tagihan = db_row('SELECT * FROM tagihan WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
if (!$tagihan) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('tagihan.php');
}

$anggota = db_all(
    "SELECT u.nama, u.username, ta.status,
            (SELECT MAX(p.created_at) FROM pembayaran p WHERE p.tagihan_anggota_id = ta.id) AS terakhir_upload
       FROM tagihan_anggota ta
       JOIN users u ON u.id = ta.anggota_id
      WHERE ta.tagihan_id = ?
      ORDER BY CASE ta.status WHEN 'menunggu' THEN 1 WHEN 'ditolak' THEN 2 WHEN 'belum_bayar' THEN 3 ELSE 4 END, u.nama",
    [$tagihan['id']]
);

$rekap = array_count_values(array_column($anggota, 'status'));
$lunas = $rekap['lunas'] ?? 0;

page_header($tagihan['judul'], 'bendahara', $user);
?>
<p><a href="tagihan.php">← Semua tagihan</a></p>
<div class="page-head">
  <div>
    <h1><?= e($tagihan['judul']) ?></h1>
    <p class="muted"><?= rupiah($tagihan['nominal']) ?> per anggota · jatuh tempo <?= tanggal($tagihan['jatuh_tempo']) ?>
      <?= $tagihan['keterangan'] ? '· ' . e($tagihan['keterangan']) : '' ?></p>
  </div>
</div>

<div class="stats">
  <div class="stat highlight">
    <span>Terkumpul</span>
    <strong><?= rupiah($lunas * $tagihan['nominal']) ?></strong>
    <small>dari <?= rupiah(count($anggota) * $tagihan['nominal']) ?></small>
  </div>
  <div class="stat"><span>Lunas</span><strong><?= $lunas ?></strong><small>anggota</small></div>
  <div class="stat"><span>Menunggu verifikasi</span><strong><?= $rekap['menunggu'] ?? 0 ?></strong><small>anggota</small></div>
  <div class="stat"><span>Belum bayar / ditolak</span><strong><?= ($rekap['belum_bayar'] ?? 0) + ($rekap['ditolak'] ?? 0) ?></strong><small>anggota</small></div>
</div>

<div class="card">
  <h2>Status per anggota</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Anggota</th><th>Status</th><th>Upload terakhir</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($anggota as $a): ?>
        <tr>
          <td><strong><?= e($a['nama']) ?></strong> <span class="muted small">@<?= e($a['username']) ?></span></td>
          <td><?= status_badge($a['status']) ?></td>
          <td class="muted"><?= tanggal($a['terakhir_upload'], true) ?></td>
          <td class="right">
            <?php if ($a['status'] === 'menunggu'): ?><a class="btn btn-primary btn-sm" href="verifikasi.php">Verifikasi</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php page_footer();
