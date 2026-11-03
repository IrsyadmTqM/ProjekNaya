<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
$user = require_login('bendahara');

if (is_post()) {
    check_csrf();

    if (($_POST['aksi'] ?? '') === 'hapus') {
        // Hanya catatan manual yang boleh dihapus; kas dari pembayaran anggota terkunci
        $hapus = db_query('DELETE FROM transaksi_kas WHERE id = ? AND pembayaran_id IS NULL', [(int)($_POST['id'] ?? 0)]);
        flash($hapus->rowCount() ? 'success' : 'error', $hapus->rowCount() ? 'Transaksi dihapus.' : 'Transaksi ini tidak bisa dihapus.');
        redirect('kas.php');
    }

    $jenis = $_POST['jenis'] ?? '';
    $nominal = parse_nominal($_POST['nominal'] ?? '');
    $tanggal = $_POST['tanggal'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (!in_array($jenis, ['masuk', 'keluar'], true) || $nominal <= 0 || !tanggal_valid($tanggal) || $keterangan === '') {
        flash('error', 'Semua kolom wajib diisi dengan benar.');
        redirect('kas.php');
    }
    if ($jenis === 'keluar' && $nominal > saldo_kas()) {
        flash('error', 'Saldo kas tidak cukup. Saldo saat ini ' . rupiah(saldo_kas()) . '.');
        redirect('kas.php');
    }

    db_query(
        'INSERT INTO transaksi_kas (jenis, nominal, keterangan, tanggal, dicatat_oleh) VALUES (?, ?, ?, ?, ?)',
        [$jenis, $nominal, potong($keterangan, 255), $tanggal, $user['id']]
    );
    flash('success', 'Kas ' . $jenis . ' ' . rupiah($nominal) . ' dicatat.');
    redirect('kas.php');
}

$transaksi = db_all(
    'SELECT k.*, u.nama AS pencatat
       FROM transaksi_kas k JOIN users u ON u.id = k.dicatat_oleh
      ORDER BY k.tanggal DESC, k.id DESC
      LIMIT 50'
);

page_header('Catat Kas', 'bendahara', $user);
?>
<div class="page-head">
  <div>
    <h1>Catat kas masuk & keluar</h1>
    <p class="muted">Pembayaran anggota yang diterima tercatat otomatis. Di sini untuk pemasukan/pengeluaran lain.</p>
  </div>
  <div class="stat" style="min-width: 200px;"><span>Saldo saat ini</span><strong><?= rupiah(saldo_kas()) ?></strong></div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Transaksi baru</h2>
    <form method="post">
      <?= csrf_field() ?>
      <label>Jenis
        <select name="jenis" required>
          <option value="keluar">Kas keluar (pengeluaran)</option>
          <option value="masuk">Kas masuk (pemasukan lain)</option>
        </select>
      </label>
      <label>Nominal (Rp)
        <input name="nominal" required inputmode="numeric" placeholder="25000">
      </label>
      <label>Tanggal
        <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>">
      </label>
      <label>Keterangan
        <input name="keterangan" required maxlength="255" placeholder="Misal: beli spidol, donasi alumni">
      </label>
      <button class="btn btn-primary btn-block">Simpan</button>
    </form>
  </div>

  <div class="card">
    <div class="card-head"><h2>50 transaksi terakhir</h2><a href="laporan.php" class="small">Laporan lengkap →</a></div>
    <?php if (!$transaksi): ?>
      <div class="empty">Belum ada transaksi.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Jenis</th><th class="num">Nominal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($transaksi as $k): ?>
          <tr>
            <td class="nowrap"><?= tanggal($k['tanggal']) ?></td>
            <td>
              <?= e($k['keterangan']) ?>
              <div class="muted small"><?= $k['pembayaran_id'] ? 'Otomatis dari pembayaran anggota' : 'Dicatat ' . e($k['pencatat']) ?></div>
            </td>
            <td><?= status_badge($k['jenis']) ?></td>
            <td class="num"><?= rupiah($k['nominal']) ?></td>
            <td class="right">
              <?php if (!$k['pembayaran_id']): ?>
                <form method="post" onsubmit="return confirm('Hapus transaksi ini?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                  <button class="btn-link">Hapus</button>
                </form>
              <?php endif; ?>
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
