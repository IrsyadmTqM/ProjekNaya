<?php

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function nama_bulan(string $tanggal): string
{
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($tanggal);
    return $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function tanggal(?string $tanggal, bool $dengan_jam = false): string
{
    if (!$tanggal) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tanggal);
    $hasil = date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $dengan_jam ? $hasil . ', ' . date('H:i', $t) : $hasil;
}

function tanggal_valid(string $tanggal): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $d && $d->format('Y-m-d') === $tanggal;
}

function potong(string $teks, int $maks): string
{
    return function_exists('mb_substr') ? mb_substr($teks, 0, $maks) : substr($teks, 0, $maks);
}

// "10.000" / "Rp 10,000" -> 10000
function parse_nominal($input): int
{
    return (int)preg_replace('/\D/', '', (string)$input);
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = [$tipe, $pesan];
}

function take_flash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function check_csrf(): void
{
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Sesi formulir sudah kedaluwarsa. Kembali, muat ulang halaman, lalu coba lagi.');
    }
}

function status_badge(string $status): string
{
    $label = [
        'belum_bayar' => 'Belum bayar',
        'menunggu' => 'Menunggu verifikasi',
        'lunas' => 'Lunas',
        'ditolak' => 'Ditolak',
        'diterima' => 'Diterima',
        'masuk' => 'Masuk',
        'keluar' => 'Keluar',
    ];
    return '<span class="badge badge-' . e($status) . '">' . e($label[$status] ?? $status) . '</span>';
}

function saldo_kas(?string $sebelum_tanggal = null): int
{
    $sql = "SELECT COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN nominal ELSE -nominal END), 0) FROM transaksi_kas";
    if ($sebelum_tanggal === null) {
        return (int)db_value($sql);
    }
    return (int)db_value($sql . ' WHERE tanggal < ?', [$sebelum_tanggal]);
}

function jumlah_menunggu(): int
{
    return (int)db_value("SELECT COUNT(*) FROM pembayaran WHERE status = 'menunggu'");
}
