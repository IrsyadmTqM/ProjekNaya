<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
$user = require_login('bendahara');

// ?bulan=2026-11 untuk satu bulan, ?bulan=semua untuk seluruh riwayat
$bulan = $_GET['bulan'] ?? date('Y-m');
if ($bulan !== 'semua' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) {
    $bulan = date('Y-m');
}

if ($bulan === 'semua') {
    $judul_periode = 'Semua periode';
    $saldo_awal = 0;
    $transaksi = db_all('SELECT * FROM transaksi_kas ORDER BY tanggal, id');
} else {
    $mulai = $bulan . '-01';
    $akhir = date('Y-m-t', strtotime($mulai));
    $judul_periode = nama_bulan($mulai);
    $saldo_awal = saldo_kas($mulai);
    $transaksi = db_all('SELECT * FROM transaksi_kas WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal, id', [$mulai, $akhir]);
}

$total_masuk = 0;
$total_keluar = 0;
foreach ($transaksi as $k) {
    if ($k['jenis'] === 'masuk') {
        $total_masuk += $k['nominal'];
    } else {
        $total_keluar += $k['nominal'];
    }
}
$saldo_akhir = $saldo_awal + $total_masuk - $total_keluar;

page_header('Laporan Kas', 'bendahara', $user);
?>
<div class="page-head">
  <div>
    <h1>Laporan kas</h1>
    <p class="muted"><?= e(NAMA_ORGANISASI) ?> · <?= e($judul_periode) ?></p>
  </div>
  <form class="inline-form no-print" method="get">
    <label>Periode
      <input type="month" name="bulan" value="<?= $bulan === 'semua' ? '' : e($bulan) ?>">
    </label>
    <button class="btn btn-ghost">Tampilkan</button>
    <a class="btn btn-ghost" href="?bulan=semua">Semua</a>
    <button type="button" class="btn btn-primary" onclick="window.print()">Cetak</button>
  </form>
</div>

<div class="stats">
  <div class="stat"><span>Saldo awal</span><strong><?= rupiah($saldo_awal) ?></strong></div>
  <div class="stat"><span>Total masuk</span><strong class="text-success"><?= rupiah($total_masuk) ?></strong></div>
  <div class="stat"><span>Total keluar</span><strong class="text-danger"><?= rupiah($total_keluar) ?></strong></div>
  <div class="stat highlight"><span>Saldo akhir</span><strong><?= rupiah($saldo_akhir) ?></strong></div>
</div>

<div class="card">
  <h2>Buku kas · <?= e($judul_periode) ?></h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Tanggal</th><th>Keterangan</th><th class="num">Masuk</th><th class="num">Keluar</th><th class="num">Saldo</th></tr>
      </thead>
      <tbody>
        <tr class="muted">
          <td></td><td><em>Saldo awal</em></td><td></td><td></td><td class="num"><?= rupiah($saldo_awal) ?></td>
        </tr>
        <?php $saldo = $saldo_awal; foreach ($transaksi as $k):
            $saldo += $k['jenis'] === 'masuk' ? $k['nominal'] : -$k['nominal']; ?>
        <tr>
          <td class="nowrap"><?= tanggal($k['tanggal']) ?></td>
          <td><?= e($k['keterangan']) ?></td>
          <td class="num text-success"><?= $k['jenis'] === 'masuk' ? rupiah($k['nominal']) : '' ?></td>
          <td class="num text-danger"><?= $k['jenis'] === 'keluar' ? rupiah($k['nominal']) : '' ?></td>
          <td class="num"><?= rupiah($saldo) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$transaksi): ?>
          <tr><td colspan="5" class="empty">Tidak ada transaksi di periode ini.</td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td></td><td>Total</td>
          <td class="num text-success"><?= rupiah($total_masuk) ?></td>
          <td class="num text-danger"><?= rupiah($total_keluar) ?></td>
          <td class="num"><?= rupiah($saldo_akhir) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php page_footer();
