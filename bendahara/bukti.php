<?php
require __DIR__ . '/../includes/bootstrap.php';
start_session('bendahara');
require_login('bendahara');

$file = db_value('SELECT file_bukti FROM pembayaran WHERE id = ?', [(int)($_GET['id'] ?? 0)]);
if (!$file) {
    http_response_code(404);
    exit('Bukti tidak ditemukan.');
}
kirim_bukti($file);
