<?php
// Router untuk server bawaan PHP: php -S localhost:8000 router.php
// Server bawaan tidak membaca .htaccess, jadi folder rahasia diblokir di sini
// (file database, kode bersama, dan bukti pembayaran tidak boleh diunduh langsung).
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Dicek di segmen mana pun, supaya trik seperti /anggota/../database/kas.db juga tertolak
if (preg_match('#(^|/)(database|includes|config|uploads)(/|$)#i', $path)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

return false; // file lain dilayani seperti biasa
