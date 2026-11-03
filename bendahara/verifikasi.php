<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
$user = require_login('bendahara');

if (is_post()) {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    $catatan = potong(trim($_POST['catatan'] ?? ''), 255);

    if (!in_array($aksi, ['terima', 'tolak'], true)) {
        redirect('verifikasi.php');
    }
    if ($aksi === 'tolak' && $catatan === '') {
        flash('error', 'Tulis alasan penolakan supaya anggota tahu apa yang harus diperbaiki.');
        redirect('verifikasi.php#p' . $id);
    }

    // Status dicek di dalam transaksi: kalau tombol diklik dua kali, permintaan kedua
    // menunggu lalu melihat status yang sudah berubah, jadi kas tidak tercatat dobel
    mulai_transaksi();
    $p = db_row(
        "SELECT p.id, p.status, p.tagihan_anggota_id, t.judul, t.nominal, u.nama
           FROM pembayaran p
           JOIN tagihan_anggota ta ON ta.id = p.tagihan_anggota_id
           JOIN tagihan t ON t.id = ta.tagihan_id
           JOIN users u ON u.id = ta.anggota_id
          WHERE p.id = ?",
        [$id]
    );
    if (!$p || $p['status'] !== 'menunggu') {
        batal_transaksi();
        flash('info', 'Pembayaran ini sudah diproses sebelumnya.');
        redirect('verifikasi.php');
    }

    $sekarang = date('Y-m-d H:i:s');
    $status_bayar = $aksi === 'terima' ? 'diterima' : 'ditolak';
    db_query(
        'UPDATE pembayaran SET status = ?, catatan_bendahara = ?, diverifikasi_oleh = ?, diverifikasi_at = ? WHERE id = ?',
        [$status_bayar, $catatan === '' ? null : $catatan, $user['id'], $sekarang, $id]
    );
    db_query(
        'UPDATE tagihan_anggota SET status = ? WHERE id = ?',
        [$aksi === 'terima' ? 'lunas' : 'ditolak', $p['tagihan_anggota_id']]
    );

    // Pembayaran yang diterima otomatis tercatat sebagai kas masuk
    if ($aksi === 'terima') {
        db_query(
            "INSERT INTO transaksi_kas (jenis, nominal, keterangan, tanggal, pembayaran_id, dicatat_oleh)
             VALUES ('masuk', ?, ?, ?, ?, ?)",
            [$p['nominal'], potong($p['judul'] . ' - ' . $p['nama'], 255), date('Y-m-d'), $id, $user['id']]
        );
    }
    selesai_transaksi();

    flash('success', $aksi === 'terima'
        ? 'Pembayaran ' . $p['nama'] . ' diterima dan ' . rupiah($p['nominal']) . ' masuk ke kas.'
        : 'Pembayaran ' . $p['nama'] . ' ditolak. Anggota bisa upload ulang.');
    redirect('verifikasi.php');
}

$antrian = db_all(
    "SELECT p.id, p.file_bukti, p.catatan_anggota, p.created_at, t.judul, t.nominal, u.nama, u.username
       FROM pembayaran p
       JOIN tagihan_anggota ta ON ta.id = p.tagihan_anggota_id
       JOIN tagihan t ON t.id = ta.tagihan_id
       JOIN users u ON u.id = ta.anggota_id
      WHERE p.status = 'menunggu'
      ORDER BY p.created_at, p.id"
);

$selesai = db_all(
    "SELECT p.id, p.status, p.catatan_bendahara, p.diverifikasi_at, t.judul, t.nominal, u.nama
       FROM pembayaran p
       JOIN tagihan_anggota ta ON ta.id = p.tagihan_anggota_id
       JOIN tagihan t ON t.id = ta.tagihan_id
       JOIN users u ON u.id = ta.anggota_id
      WHERE p.status <> 'menunggu'
      ORDER BY p.diverifikasi_at DESC, p.id DESC
      LIMIT 15"
);

page_header('Verifikasi', 'bendahara', $user);
?>
<div class="page-head">
  <div>
    <h1>Verifikasi pembayaran</h1>
    <p class="muted">Cek bukti dari anggota. Pembayaran yang diterima otomatis masuk ke buku kas.</p>
  </div>
</div>

<?php if (!$antrian): ?>
  <div class="card"><div class="empty">Tidak ada pembayaran yang menunggu verifikasi.</div></div>
<?php else: ?>
<div class="verif-list">
  <?php foreach ($antrian as $p): ?>
  <div class="card verif-item" id="p<?= (int)$p['id'] ?>">
    <a class="bukti-thumb" href="bukti.php?id=<?= (int)$p['id'] ?>" target="_blank" title="Buka ukuran penuh">
      <?php if (bukti_gambar($p['file_bukti'])): ?>
        <img src="bukti.php?id=<?= (int)$p['id'] ?>" alt="Bukti pembayaran <?= e($p['nama']) ?>" loading="lazy">
      <?php else: ?>
        <span class="bukti-pdf">PDF · buka</span>
      <?php endif; ?>
    </a>
    <div>
      <h2><?= e($p['nama']) ?> <span class="muted small">@<?= e($p['username']) ?></span></h2>
      <dl class="dl">
        <dt>Tagihan</dt><dd><?= e($p['judul']) ?></dd>
        <dt>Nominal</dt><dd><?= rupiah($p['nominal']) ?></dd>
        <dt>Dikirim</dt><dd><?= tanggal($p['created_at'], true) ?></dd>
        <?php if ($p['catatan_anggota']): ?><dt>Catatan</dt><dd><?= e($p['catatan_anggota']) ?></dd><?php endif; ?>
      </dl>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <label>Catatan untuk anggota <span class="hint">(wajib jika ditolak)</span>
          <input name="catatan" maxlength="255" placeholder="Misal: nominal transfer kurang Rp 2.000">
        </label>
        <div class="actions">
          <button class="btn btn-success" name="aksi" value="terima">Terima pembayaran</button>
          <button class="btn btn-danger" name="aksi" value="tolak">Tolak</button>
        </div>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="margin-top: 20px;">
  <h2>Baru saja diverifikasi</h2>
  <?php if (!$selesai): ?>
    <div class="empty">Belum ada.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Waktu</th><th>Anggota</th><th>Tagihan</th><th class="num">Nominal</th><th>Hasil</th><th>Catatan</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($selesai as $s): ?>
        <tr>
          <td class="nowrap muted"><?= tanggal($s['diverifikasi_at'], true) ?></td>
          <td><?= e($s['nama']) ?></td>
          <td><?= e($s['judul']) ?></td>
          <td class="num"><?= rupiah($s['nominal']) ?></td>
          <td><?= status_badge($s['status']) ?></td>
          <td class="small"><?= e($s['catatan_bendahara'] ?: '-') ?></td>
          <td class="right"><a class="small" href="bukti.php?id=<?= (int)$s['id'] ?>" target="_blank">Bukti</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php page_footer();
