-- Tambahan fitur laporan stok Admin -> Penjual
CREATE TABLE IF NOT EXISTS laporan_stok (
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
