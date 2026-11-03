# Sistem Kas — Website Anggota & Website Bendahara

Dua website (PHP + SQLite) yang memakai **satu database yang sama**:

| Website | Folder | Fitur |
|---|---|---|
| Anggota | `anggota/` | Login, lihat tagihan, upload bukti bayar, riwayat & status pembayaran |
| Bendahara | `bendahara/` | Login, buat tagihan, verifikasi pembayaran, catat kas masuk/keluar, laporan kas, kelola anggota |

Database-nya SQLite: satu file `database/kas.db` yang **dibuat otomatis beserta akun awal** saat website pertama kali dibuka. Tidak perlu server database, user, atau password.

## Cara menjalankan

### Ubuntu

Sekali saja:

```bash
sudo apt install -y php-cli php-sqlite3
```

Setiap mau menjalankan (dari dalam folder project):

```bash
php -S localhost:8000 router.php
```

Lalu buka `http://localhost:8000`. Tekan `Ctrl+C` di terminal untuk mematikan.

### Windows (XAMPP)

Salin folder `ProjekNaya` ke `C:\xampp\htdocs\`, nyalakan **Apache** (MySQL tidak perlu), lalu buka `http://localhost/ProjekNaya/`.

### Akun awal

| Website | Username | Password |
|---|---|---|
| Bendahara | `bendahara` | `bendahara123` |
| Anggota | `andi`, `budi`, `citra`, `dewi` | `anggota123` |

Selain akun, semua data dimulai dari nol: belum ada tagihan, pembayaran, atau transaksi kas (saldo Rp 0).

**Mengulang dari awal:** matikan server, hapus file `database/kas.db` dan isi folder `uploads/bukti/`, lalu jalankan lagi.

### Urutan demo

1. **Bendahara** membuat tagihan di menu *Tagihan*.
2. **Anggota** login (pakai jendela incognito supaya bisa bersamaan), melihat tagihan, lalu upload bukti.
3. **Bendahara** membuka *Verifikasi* lalu klik *Terima*. Status anggota menjadi lunas dan saldo bertambah.
4. **Bendahara** mencatat pengeluaran di *Catat Kas*, lalu menunjukkan hasilnya di *Laporan*.

## Alur yang menghubungkan kedua website

```
Bendahara buat tagihan ──► baris tagihan_anggota dibuat untuk setiap anggota aktif (status: belum_bayar)
                                   │
Anggota upload bukti   ──► pembayaran baru (menunggu), status tagihan → menunggu
                                   │
Bendahara verifikasi   ──► Terima: status → lunas  +  otomatis tercatat di transaksi_kas (masuk)
                       └─► Tolak : status → ditolak + catatan alasan → anggota bisa upload ulang
```

Laporan kas dihitung dari tabel `transaksi_kas`: **saldo = total masuk − total keluar**.

## Struktur folder

```
ProjekNaya/
├── index.php            Halaman depan (pilih website)
├── router.php           Dipakai saat menjalankan dengan php -S
├── config/config.php    Pengaturan: lokasi database, nama organisasi, info rekening
├── includes/            Kode bersama: koneksi DB, login, upload, tampilan, akun awal
├── anggota/             WEBSITE 1 — sudut pandang anggota
├── bendahara/           WEBSITE 2 — sudut pandang bendahara
├── assets/style.css     Tampilan (biru = anggota, hijau = bendahara)
├── database/kas.sql     Struktur tabel
├── database/kas.db      File database (muncul otomatis)
└── uploads/bukti/       File bukti pembayaran (tidak bisa diakses langsung)
```

## Tabel database

- `users`: akun anggota & bendahara (kolom `role`)
- `tagihan`: tagihan yang dibuat bendahara
- `tagihan_anggota`: status bayar tiap anggota untuk tiap tagihan
- `pembayaran`: setiap bukti yang diupload, termasuk yang ditolak
- `transaksi_kas`: buku kas masuk/keluar

Isi database bisa dilihat dengan aplikasi **DB Browser for SQLite** (`sudo apt install sqlitebrowser`), buka file `database/kas.db`.

## Keamanan yang sudah diterapkan

- Password disimpan sebagai hash (`password_hash`).
- Role dicek di server pada setiap halaman, jadi anggota tidak bisa membuka halaman bendahara walau tahu URL-nya.
- Anggota hanya bisa melihat tagihan dan bukti miliknya sendiri.
- Semua query memakai prepared statement (aman dari SQL injection), dan output di-escape (aman dari XSS).
- Setiap form memakai token CSRF.
- Upload dicek dari isi file (hanya JPG/PNG/PDF, maksimal 2 MB) dan disimpan dengan nama acak.
- Folder `database/`, `includes/`, `config/`, dan `uploads/` diblokir dari akses langsung (lewat `.htaccess` di Apache, lewat `router.php` di `php -S`).
- Verifikasi berjalan di dalam transaksi database yang mengunci tulis, jadi satu pembayaran tidak bisa tercatat dua kali di kas.
- Session anggota dan bendahara terpisah, jadi keduanya bisa login bersamaan di satu browser untuk demo.
