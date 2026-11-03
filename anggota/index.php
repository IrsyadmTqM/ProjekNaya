<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('anggota');
$user = require_login('anggota');

// Hanya tagihan milik anggota yang sedang login
$tagihan = db_all(
    "SELECT ta.id, ta.status, t.judul, t.keterangan, t.nominal, t.jatuh_tempo,
            (SELECT p.catatan_bendahara FROM pembayaran p
              WHERE p.tagihan_anggota_id = ta.id ORDER BY p.id DESC LIMIT 1) AS catatan
       FROM tagihan_anggota ta
       JOIN tagihan t ON t.id = ta.tagihan_id
      WHERE ta.anggota_id = ? AND ta.status <> 'lunas'
      ORDER BY t.jatuh_tempo, t.id",
    [$user['id']]
);

$harus_bayar = array_filter($tagihan, function ($t) {
    return $t['status'] !== 'menunggu';
});
$jumlah_menunggu = count($tagihan) - count($harus_bayar);
$total_lunas = db_value(
    "SELECT COALESCE(SUM(t.nominal), 0) FROM tagihan_anggota ta JOIN tagihan t ON t.id = ta.tagihan_id
      WHERE ta.anggota_id = ? AND ta.status = 'lunas'",
    [$user['id']]
);
$hari_ini = date('Y-m-d');

page_header('Tagihan Saya', 'anggota', $user);
?>
<div class="page-head">
  <div>
    <h1>Halo, <?= e($user['nama']) ?></h1>
    <p class="muted">Berikut tagihan kas yang belum lunas.</p>
  </div>
</div>

<div class="stats">
  <div class="stat highlight">
    <span>Harus dibayar</span>
    <strong><?= rupiah(array_sum(array_column($harus_bayar, 'nominal'))) ?></strong>
    <small><?= count($harus_bayar) ?> tagihan</small>
  </div>
  <div class="stat">
    <span>Menunggu verifikasi</span>
    <strong><?= $jumlah_menunggu ?></strong>
    <small>sedang dicek bendahara</small>
  </div>
  <div class="stat">
    <span>Sudah dibayar</span>
    <strong><?= rupiah($total_lunas) ?></strong>
    <small>total semua tagihan lunas</small>
  </div>
</div>

<div class="card">
  <h2>Tagihan aktif</h2>
  <?php if (!$tagihan): ?>
    <div class="empty">Semua tagihan sudah lunas. Terima kasih!</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Tagihan</th><th class="num">Nominal</th><th>Jatuh tempo</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($tagihan as $t): $telat = $t['status'] !== 'menunggu' && $t['jatuh_tempo'] < $hari_ini; ?>
        <tr>
          <td>
            <strong><?= e($t['judul']) ?></strong>
            <?php if ($t['keterangan']): ?><div class="muted small"><?= e($t['keterangan']) ?></div><?php endif; ?>
            <?php if ($t['status'] === 'ditolak' && $t['catatan']): ?>
              <div class="note-error small">Alasan ditolak: <?= e($t['catatan']) ?></div>
            <?php endif; ?>
          </td>
          <td class="num"><?= rupiah($t['nominal']) ?></td>
          <td class="nowrap <?= $telat ? 'text-danger' : '' ?>">
            <?= tanggal($t['jatuh_tempo']) ?><?= $telat ? '<div class="small">Lewat jatuh tempo</div>' : '' ?>
          </td>
          <td><?= status_badge($t['status']) ?></td>
          <td class="right">
            <?php if ($t['status'] === 'menunggu'): ?>
              <span class="muted small">Sedang dicek</span>
            <?php else: ?>
              <a class="btn btn-primary btn-sm" href="bayar.php?id=<?= (int)$t['id'] ?>">
                <?= $t['status'] === 'ditolak' ? 'Upload ulang' : 'Bayar' ?>
              </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php page_footer();
