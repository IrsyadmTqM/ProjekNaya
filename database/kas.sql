-- Struktur database Sistem Kas (SQLite)
-- Dijalankan otomatis saat website pertama kali dibuka, hasilnya file database/kas.db

CREATE TABLE IF NOT EXISTS users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nama        TEXT    NOT NULL,
    username    TEXT    NOT NULL UNIQUE,
    password    TEXT    NOT NULL,
    role        TEXT    NOT NULL CHECK (role IN ('anggota', 'bendahara')),
    aktif       INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- Tagihan yang dibuat bendahara, misal "Kas November 2026"
CREATE TABLE IF NOT EXISTS tagihan (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    judul        TEXT    NOT NULL,
    keterangan   TEXT    NULL,
    nominal      INTEGER NOT NULL CHECK (nominal > 0),
    jatuh_tempo  TEXT    NOT NULL,
    dibuat_oleh  INTEGER NOT NULL REFERENCES users(id),
    created_at   TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- Satu baris per anggota per tagihan; di sinilah status bayar tiap anggota disimpan
CREATE TABLE IF NOT EXISTS tagihan_anggota (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    tagihan_id  INTEGER NOT NULL REFERENCES tagihan(id),
    anggota_id  INTEGER NOT NULL REFERENCES users(id),
    status      TEXT    NOT NULL DEFAULT 'belum_bayar'
                CHECK (status IN ('belum_bayar', 'menunggu', 'lunas', 'ditolak')),
    UNIQUE (tagihan_id, anggota_id)
);

-- Setiap upload bukti dari anggota (termasuk yang ditolak, untuk riwayat)
CREATE TABLE IF NOT EXISTS pembayaran (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    tagihan_anggota_id  INTEGER NOT NULL REFERENCES tagihan_anggota(id),
    file_bukti          TEXT    NOT NULL,
    catatan_anggota     TEXT    NULL,
    status              TEXT    NOT NULL DEFAULT 'menunggu'
                        CHECK (status IN ('menunggu', 'diterima', 'ditolak')),
    catatan_bendahara   TEXT    NULL,
    diverifikasi_oleh   INTEGER NULL REFERENCES users(id),
    diverifikasi_at     TEXT    NULL,
    created_at          TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- Buku kas. Pembayaran yang diterima otomatis masuk ke sini (pembayaran_id terisi),
-- pemasukan/pengeluaran lain dicatat manual oleh bendahara (pembayaran_id kosong).
-- UNIQUE pada pembayaran_id mencegah satu pembayaran tercatat dua kali.
CREATE TABLE IF NOT EXISTS transaksi_kas (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    jenis          TEXT    NOT NULL CHECK (jenis IN ('masuk', 'keluar')),
    nominal        INTEGER NOT NULL CHECK (nominal > 0),
    keterangan     TEXT    NOT NULL,
    tanggal        TEXT    NOT NULL,
    pembayaran_id  INTEGER NULL UNIQUE REFERENCES pembayaran(id),
    dicatat_oleh   INTEGER NOT NULL REFERENCES users(id),
    created_at     TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);
