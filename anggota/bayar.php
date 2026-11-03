<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('anggota');
$user = require_login('anggota');

$id = (int)($_GET['id'] ?? 0);
$sql_tagihan = "SELECT ta.id, ta.status, t.judul, t.keterangan, t.nominal, t.jatuh_tempo
                  FROM tagihan_anggota ta
                  JOIN tagihan t ON t.id = ta.tagihan_id
                 WHERE ta.id = ? AND ta.anggota_id = ?";
$tagihan = db_row($sql_tagihan, [$id, $user['id']]);

if (!$tagihan) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('index.php');
}
if (!in_array($tagihan['status'], ['belum_bayar', 'ditolak'], true)) {
    flash('info', 'Tagihan "' . $tagihan['judul'] . '" sudah dibayar atau sedang diverifikasi.');
    redirect('index.php');
}

if (is_post()) {
    check_csrf();
    $error = null;
    $file = simpan_bukti($_FILES['bukti'] ?? null, $error);
    if ($file === null) {
        flash('error', $error);
        redirect('bayar.php?id=' . $id);
    }

    // Cek ulang status di dalam transaksi supaya klik dua kali tidak membuat dua pembayaran
    mulai_transaksi();
    $status = db_value('SELECT status FROM tagihan_anggota WHERE id = ? AND anggota_id = ?', [$id, $user['id']]);
    if (!in_array($status, ['belum_bayar', 'ditolak'], true)) {
        batal_transaksi();
        unlink(UPLOAD_DIR . $file);
        flash('info', 'Tagihan ini sudah diproses.');
        redirect('index.php');
    }

    $catatan = trim($_POST['catatan'] ?? '');
    db_query(
        'INSERT INTO pembayaran (tagihan_anggota_id, file_bukti, catatan_anggota, created_at) VALUES (?, ?, ?, ?)',
        [$id, $file, $catatan === '' ? null : potong($catatan, 255), date('Y-m-d H:i:s')]
    );
    db_query("UPDATE tagihan_anggota SET status = 'menunggu' WHERE id = ?", [$id]);
    selesai_transaksi();

    flash('success', 'Bukti pembayaran terkirim. Bendahara akan segera memverifikasi.');
    redirect('riwayat.php');
}

page_header('Bayar ' . $tagihan['judul'], 'anggota', $user);
?>
<p><a href="index.php">← Kembali ke tagihan</a></p>

<div class="grid-2">
  <div class="card">
    <h2><?= e($tagihan['judul']) ?></h2>
    <div class="amount"><?= rupiah($tagihan['nominal']) ?></div>
    <p class="muted">Jatuh tempo <?= tanggal($tagihan['jatuh_tempo']) ?></p>
    <?php if ($tagihan['keterangan']): ?><p><?= e($tagihan['keterangan']) ?></p><?php endif; ?>
    <?php if ($tagihan['status'] === 'ditolak'): ?>
      <div class="alert alert-error small">Bukti sebelumnya ditolak. Silakan upload bukti yang benar.</div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Upload bukti pembayaran</h2>
    <div class="pay-info small"><strong>Cara bayar:</strong> <?= e(INFO_PEMBAYARAN) ?></div>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label>File bukti
        <span class="hint">Foto/screenshot JPG atau PNG, atau PDF. Maksimal <?= MAX_UPLOAD_BYTES / 1024 / 1024 ?> MB.</span>
        <input type="file" name="bukti" id="bukti" accept="image/jpeg,image/png,application/pdf" required>
      </label>
      <img id="preview" class="preview" alt="Pratinjau bukti">
      <label>Catatan <span class="hint">(opsional)</span>
        <textarea name="catatan" maxlength="255" placeholder="Misal: transfer dari rekening ibu, a.n. Sri"></textarea>
      </label>
      <button class="btn btn-primary">Kirim bukti pembayaran</button>
    </form>
  </div>
</div>

<script>
document.getElementById('bukti').addEventListener('change', function () {
  var img = document.getElementById('preview');
  var file = this.files[0];
  if (file && file.type.indexOf('image/') === 0) {
    img.src = URL.createObjectURL(file);
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }
});
</script>
<?php page_footer();
