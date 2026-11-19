-- ============================================================
-- Database: RacikInRempah (marketplace rempah)
-- Engine  : InnoDB, charset utf8mb4
-- Cara pakai (InfinityFree): buat database di Control Panel ->
-- MySQL Databases, buka phpMyAdmin, pilih database, tab Import,
-- unggah file ini. (Jangan jalankan CREATE DATABASE di hosting.)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS laporan_stok;
DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS keranjang;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. users --------------------------------------------------
CREATE TABLE users (
  id_user    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama       VARCHAR(100) NOT NULL,
  email      VARCHAR(120) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
  alamat     TEXT NULL,
  no_hp      VARCHAR(20) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_user),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. kategori -----------------------------------------------
CREATE TABLE kategori (
  id_kategori   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_kategori VARCHAR(80) NOT NULL,
  PRIMARY KEY (id_kategori),
  UNIQUE KEY uq_kategori_nama (nama_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. produk -------------------------------------------------
CREATE TABLE produk (
  id_produk   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_kategori INT UNSIGNED NULL,
  id_penjual  INT UNSIGNED NOT NULL,
  nama_produk VARCHAR(150) NOT NULL,
  deskripsi   TEXT NULL,
  harga       DECIMAL(12,2) NOT NULL,
  stok        INT UNSIGNED NOT NULL DEFAULT 0,
  gambar      VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_produk),
  KEY idx_produk_kategori (id_kategori),
  KEY idx_produk_penjual (id_penjual),
  CONSTRAINT fk_produk_kategori FOREIGN KEY (id_kategori)
    REFERENCES kategori (id_kategori) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_produk_penjual FOREIGN KEY (id_penjual)
    REFERENCES users (id_user) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. keranjang ----------------------------------------------
CREATE TABLE keranjang (
  id_keranjang INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_user      INT UNSIGNED NOT NULL,
  id_produk    INT UNSIGNED NOT NULL,
  jumlah       INT UNSIGNED NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_keranjang),
  UNIQUE KEY uq_keranjang_user_produk (id_user, id_produk),
  KEY idx_keranjang_produk (id_produk),
  CONSTRAINT fk_keranjang_user FOREIGN KEY (id_user)
    REFERENCES users (id_user) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_keranjang_produk FOREIGN KEY (id_produk)
    REFERENCES produk (id_produk) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. pesanan ------------------------------------------------
CREATE TABLE pesanan (
  id_pesanan    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_pesanan  VARCHAR(20) NOT NULL,
  id_user       INT UNSIGNED NOT NULL,
  subtotal      DECIMAL(14,2) NOT NULL DEFAULT 0,
  diskon_persen TINYINT UNSIGNED NOT NULL DEFAULT 0,
  diskon_nominal DECIMAL(14,2) NOT NULL DEFAULT 0,
  total         DECIMAL(14,2) NOT NULL,
  status        ENUM('menunggu','dibayar','diproses','disiapkan','dikirim','dalam_pengantaran','selesai','batal') NOT NULL DEFAULT 'menunggu',
  nama_penerima VARCHAR(100) NOT NULL,
  alamat_kirim  TEXT NOT NULL,
  no_hp         VARCHAR(20) NOT NULL,
  catatan       VARCHAR(255) NULL,
  tanggal       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pesanan),
  UNIQUE KEY uq_pesanan_kode (kode_pesanan),
  KEY idx_pesanan_user (id_user),
  CONSTRAINT fk_pesanan_user FOREIGN KEY (id_user)
    REFERENCES users (id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. detail_pesanan -----------------------------------------
-- nama_produk & harga disimpan sebagai snapshot saat transaksi,
-- sehingga riwayat tetap utuh walau produk diubah/dihapus.
CREATE TABLE detail_pesanan (
  id_detail   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pesanan  INT UNSIGNED NOT NULL,
  id_produk   INT UNSIGNED NULL,
  nama_produk VARCHAR(150) NOT NULL,
  harga       DECIMAL(12,2) NOT NULL,
  jumlah      INT UNSIGNED NOT NULL,
  subtotal    DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (id_detail),
  KEY idx_detail_pesanan (id_pesanan),
  KEY idx_detail_produk (id_produk),
  CONSTRAINT fk_detail_pesanan FOREIGN KEY (id_pesanan)
    REFERENCES pesanan (id_pesanan) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detail_produk FOREIGN KEY (id_produk)
    REFERENCES produk (id_produk) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. laporan_stok ------------------------------------------
-- Catatan stok rendah yang dikirim Admin kepada Penjual.
CREATE TABLE laporan_stok (
  id_laporan INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_produk INT UNSIGNED NULL,
  id_admin INT UNSIGNED NOT NULL,
  id_penjual INT UNSIGNED NOT NULL,
  stok_saat_laporan INT UNSIGNED NOT NULL DEFAULT 0,
  pesan VARCHAR(500) NOT NULL,
  status ENUM('baru','dibaca','ditindaklanjuti') NOT NULL DEFAULT 'baru',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dibaca_at TIMESTAMP NULL DEFAULT NULL,
  ditindaklanjuti_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id_laporan),
  KEY idx_laporan_produk (id_produk),
  KEY idx_laporan_admin (id_admin),
  KEY idx_laporan_penjual_status (id_penjual, status),
  CONSTRAINT fk_laporan_produk FOREIGN KEY (id_produk) REFERENCES produk (id_produk) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_laporan_admin FOREIGN KEY (id_admin) REFERENCES users (id_user) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_laporan_penjual FOREIGN KEY (id_penjual) REFERENCES users (id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATA CONTOH
-- Akun demo (password di-hash dengan password_hash / bcrypt):
--   admin@pasar.test   / admin123
--   penjual@pasar.test / penjual123
--   pembeli@pasar.test / pembeli123
-- GANTI/HAPUS akun ini jika aplikasi dipakai di luar keperluan tugas.
-- ============================================================
INSERT INTO users (nama, email, password, role, alamat, no_hp) VALUES
('Administrator', 'admin@pasar.test',   '$2y$10$/6bRZgPBYb5v9NkXtFN4vOKtk.wB0BYbshAfScOHXEDOjYqIHroE.', 'admin',   'Bengkulu', '081200000001'),
('Dapur Rempah Kito', 'penjual@pasar.test', '$2y$10$o70y9.T1034KgKMz6i9MpOMoNZUVXjqUO/1kuV1HaVFz6zwudlvA6', 'penjual', 'Jl. Rempah No. 5, Bengkulu', '081200000002'),
('Budi Pembeli',  'pembeli@pasar.test', '$2y$10$y7fiUh9p5WPyki6xY2LmGuvfEi6G2EobcdCOmVBIjbVLXJrOGJNRS', 'pembeli', 'Jl. Melati No. 12, Bengkulu', '081200000003');

INSERT INTO kategori (nama_kategori) VALUES
('Minuman Rempah'),
('Makanan Kaya Rempah'),
('Bumbu Racik'),
('Rempah Utuh & Bubuk');

INSERT INTO produk (id_kategori, id_penjual, nama_produk, deskripsi, harga, stok) VALUES
(1, 2, 'Wedang Jahe Instan 10 Sachet',   'Wedang jahe merah instan, hangat dan pedas manis. Seduh dengan air panas, cocok diminum saat cuaca dingin.', 28000, 60),
(1, 2, 'Bandrek Khas Sunda 200 gr',       'Racikan bandrek dari jahe, kayu manis, cengkeh, dan gula aren. Seduh panas, nikmat bersama kelapa muda.', 30000, 45),
(1, 2, 'Wedang Uwuh Daun Rempah 150 gr',  'Campuran secang, jahe, kayu manis, cengkeh, dan daun pala. Seduhan berwarna merah dengan aroma hangat.', 26000, 40),
(1, 2, 'Beras Kencur Siap Seduh 200 gr',  'Serbuk beras kencur tradisional, segar dan menenangkan. Larutkan dalam air hangat atau dingin.', 24000, 50),
(2, 2, 'Rendang Daging Kering 250 gr',    'Rendang kering dimasak lama dengan santan dan rempah lengkap. Tahan lama, siap santap.', 95000, 20),
(2, 2, 'Sambal Rica-Rica Rempah 200 gr',  'Sambal rica-rica pedas gurih dengan serai, jahe, dan daun jeruk. Cocok untuk ayam atau ikan.', 35000, 35),
(2, 2, 'Kerupuk Bumbu Rempah 200 gr',     'Kerupuk renyah dengan taburan bumbu rempah, gurih dan harum. Teman makan yang pas.', 20000, 60),
(3, 2, 'Bumbu Rendang Instan 150 gr',     'Bumbu lengkap rendang: cabai, lengkuas, kunyit, serai, dan rempah pilihan. Tinggal tumis dan masak.', 22000, 50),
(3, 2, 'Bumbu Soto Kuning 100 gr',        'Bumbu dasar soto kuning dengan kunyit, jahe, dan kemiri. Aroma khas, praktis dipakai.', 15000, 70),
(3, 2, 'Bumbu Opor & Kari 100 gr',        'Bumbu racik untuk opor dan kari, gurih dengan ketumbar, jintan, dan kapulaga.', 16000, 65),
(4, 2, 'Kayu Manis Batang 100 gr',        'Kayu manis batang pilihan, aromanya manis dan hangat. Untuk minuman, kue, dan masakan.', 18000, 80),
(4, 2, 'Cengkeh Kering 100 gr',           'Cengkeh kering kualitas baik, harum tajam. Cocok untuk bumbu masakan dan wedang.', 30000, 55),
(4, 2, 'Lada Hitam Butir 100 gr',         'Lada hitam butir berkualitas, aroma tajam, dipanen dan dijemur alami.', 15000, 70),
(4, 2, 'Kunyit Bubuk Murni 100 gr',       'Kunyit bubuk tanpa campuran, warna kuning pekat. Untuk bumbu, jamu, dan pewarna alami.', 12000, 90);
