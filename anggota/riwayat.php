<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('anggota');
$user = require_login('anggota');

$tagihan = db_all(
    "SELECT ta.id, ta.status, t.judul, t.nominal, t.jatuh_tempo
       FROM tagihan_anggota ta
       JOIN tagihan t ON t.id = ta.tagihan_id
      WHERE ta.anggota_id = ?
      ORDER BY t.jatuh_tempo DESC, t.id DESC",
    [$user['id']]
);

$pembayaran = db_all(
    "SELECT p.id, p.status, p.created_at, p.catatan_anggota, p.catatan_bendahara, p.diverifikasi_at,
            t.judul, t.nominal
       FROM pembayaran p
       JOIN tagihan_anggota ta ON ta.id = p.tagihan_anggota_id
       JOIN tagihan t ON t.id = ta.tagihan_id
      WHERE ta.anggota_id = ?
      ORDER BY p.id DESC",
    [$user['id']]
);

page_header('Riwayat', 'anggota', $user);
?>
<div class="page-head">
  <div>
    <h1>Riwayat pembayaran</h1>
    <p class="muted">Semua bukti yang pernah kamu kirim beserta status verifikasinya.</p>
  </div>
</div>

<div class="card">
  <h2>Bukti yang dikirim</h2>
  <?php if (!$pembayaran): ?>
    <div class="empty">Belum ada pembayaran.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Dikirim</th><th>Tagihan</th><th class="num">Nominal</th><th>Status</th><th>Catatan bendahara</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($pembayaran as $p): ?>
        <tr>
          <td class="nowrap"><?= tanggal($p['created_at'], true) ?></td>
          <td>
            <?= e($p['judul']) ?>
            <?php if ($p['catatan_anggota']): ?><div class="muted small"><?= e($p['catatan_anggota']) ?></div><?php endif; ?>
          </td>
          <td class="num"><?= rupiah($p['nominal']) ?></td>
          <td>
            <?= status_badge($p['status']) ?>
            <?php if ($p['diverifikasi_at']): ?><div class="muted small"><?= tanggal($p['diverifikasi_at'], true) ?></div><?php endif; ?>
          </td>
          <td class="<?= $p['status'] === 'ditolak' ? 'text-danger' : '' ?>"><?= e($p['catatan_bendahara'] ?: '-') ?></td>
          <td class="right"><a class="btn btn-ghost btn-sm" href="bukti.php?id=<?= (int)$p['id'] ?>" target="_blank">Lihat bukti</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Status semua tagihan</h2>
  <?php if (!$tagihan): ?>
    <div class="empty">Belum ada tagihan.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Tagihan</th><th class="num">Nominal</th><th>Jatuh tempo</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($tagihan as $t): ?>
        <tr>
          <td><?= e($t['judul']) ?></td>
          <td class="num"><?= rupiah($t['nominal']) ?></td>
          <td class="nowrap"><?= tanggal($t['jatuh_tempo']) ?></td>
          <td><?= status_badge($t['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php page_footer();
