<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('anggota');
$user = require_login('anggota');

// Anggota hanya boleh membuka bukti miliknya sendiri
$file = db_value(
    'SELECT p.file_bukti FROM pembayaran p
       JOIN tagihan_anggota ta ON ta.id = p.tagihan_anggota_id
      WHERE p.id = ? AND ta.anggota_id = ?',
    [(int)($_GET['id'] ?? 0), $user['id']]
);

if (!$file) {
    http_response_code(404);
    exit('Bukti tidak ditemukan.');
}
kirim_bukti($file);
