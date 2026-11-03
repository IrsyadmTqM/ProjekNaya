<?php
// Koneksi database yang dipakai bersama oleh kedua website

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        if (!extension_loaded('pdo_sqlite')) {
            http_response_code(500);
            exit('Ekstensi SQLite untuk PHP belum aktif. Di Ubuntu: sudo apt install php-sqlite3');
        }
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');

        // Pertama kali dibuka: buat tabel dan akun awal
        if (!$pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'users'")->fetchColumn()) {
            require_once __DIR__ . '/seed.php';
            pasang_database($pdo);
        }
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_row(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = [])
{
    return db_query($sql, $params)->fetchColumn();
}

// BEGIN IMMEDIATE langsung mengunci database untuk menulis, jadi kalau tombol diklik
// dua kali, permintaan kedua menunggu dan baru membaca status setelah yang pertama selesai.
function mulai_transaksi(): void
{
    db()->exec('BEGIN IMMEDIATE');
}

function selesai_transaksi(): void
{
    db()->exec('COMMIT');
}

function batal_transaksi(): void
{
    db()->exec('ROLLBACK');
}
