<?php
// Upload & tampilkan file bukti pembayaran.
// File disimpan dengan nama acak di uploads/bukti/ (folder ini diblokir dari akses langsung),
// lalu ditampilkan lewat bukti.php setelah cek login.

const TIPE_BUKTI = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];

// Tipe file dicek dari isinya, bukan dari nama file
function deteksi_tipe_bukti(string $path): ?string
{
    $info = @getimagesize($path);
    if ($info) {
        if ($info[2] === IMAGETYPE_JPEG) {
            return 'jpg';
        }
        return $info[2] === IMAGETYPE_PNG ? 'png' : null;
    }

    $fh = fopen($path, 'rb');
    $awal = fread($fh, 5);
    fclose($fh);
    return $awal === '%PDF-' ? 'pdf' : null;
}

// Mengembalikan nama file yang disimpan, atau null (pesan error diisi ke $error)
function simpan_bukti(?array $file, ?string &$error): ?string
{
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Pilih file bukti pembayaran dulu.';
        return null;
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > MAX_UPLOAD_BYTES) {
        $error = 'Ukuran file maksimal ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Upload gagal, coba lagi.';
        return null;
    }

    $ext = deteksi_tipe_bukti($file['tmp_name']);
    if ($ext === null) {
        $error = 'File harus berupa gambar JPG/PNG atau PDF.';
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $nama = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $nama)) {
        $error = 'Gagal menyimpan file. Pastikan folder uploads/bukti bisa ditulis.';
        return null;
    }
    return $nama;
}

function kirim_bukti(string $nama): void
{
    $nama = basename($nama);
    $path = UPLOAD_DIR . $nama;
    if (!is_file($path)) {
        http_response_code(404);
        exit('File bukti tidak ditemukan.');
    }

    $ext = pathinfo($nama, PATHINFO_EXTENSION);
    header('Content-Type: ' . (TIPE_BUKTI[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="bukti-' . $nama . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function bukti_gambar(string $nama): bool
{
    return in_array(pathinfo($nama, PATHINFO_EXTENSION), ['jpg', 'png'], true);
}
