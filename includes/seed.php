<?php
// Membuat tabel + akun awal. Dipanggil otomatis oleh db() saat database masih kosong.
// Tagihan, pembayaran, dan kas sengaja dimulai dari nol.

function pasang_database(PDO $pdo): void
{
    $pdo->beginTransaction();
    try {
        $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/../database/kas.sql'));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $pdo->exec($statement);
        }
        isi_akun_awal($pdo);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function isi_akun_awal(PDO $pdo): void
{
    $tambah_user = $pdo->prepare('INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)');
    $tambah_user->execute(['Siti Bendahara', 'bendahara', password_hash('bendahara123', PASSWORD_DEFAULT), 'bendahara']);

    foreach (['andi' => 'Andi Pratama', 'budi' => 'Budi Santoso', 'citra' => 'Citra Lestari', 'dewi' => 'Dewi Anggraini'] as $username => $nama) {
        $tambah_user->execute([$nama, $username, password_hash('anggota123', PASSWORD_DEFAULT), 'anggota']);
    }
}
