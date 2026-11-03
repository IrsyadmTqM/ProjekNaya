<?php
// Database SQLite: satu file, dibuat otomatis (beserta akun awal) saat pertama dibuka.
// Hapus file ini untuk mengulang dari awal.
define('DB_PATH', __DIR__ . '/../database/kas.db');

define('NAMA_ORGANISASI', 'Kas Kelas');

// Ditampilkan ke anggota di halaman pembayaran
define('INFO_PEMBAYARAN', 'Transfer ke BRI 1234-5678-90 a.n. Bendahara Kelas, atau bayar tunai ke bendahara lalu foto kuitansinya.');

define('UPLOAD_DIR', __DIR__ . '/../uploads/bukti/');
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB

date_default_timezone_set('Asia/Jakarta');
