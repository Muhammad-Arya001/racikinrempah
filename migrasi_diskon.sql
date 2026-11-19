-- Migrasi: tambah kolom diskon ke tabel pesanan TANPA menghapus data.
-- Jalankan sekali di phpMyAdmin (tab SQL) bila database sudah terisi.
-- Jika Anda mengimpor ulang database.sql terbaru, file ini TIDAK perlu dijalankan.
ALTER TABLE pesanan
  ADD COLUMN subtotal       DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER id_user,
  ADD COLUMN diskon_persen  TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER subtotal,
  ADD COLUMN diskon_nominal DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER diskon_persen;
