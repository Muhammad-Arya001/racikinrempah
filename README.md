# RacikInRempah — Marketplace Rempah Nusantara

Aplikasi web marketplace sederhana: PHP Native, MySQLi, Bootstrap 5.

**Demo:** _isi URL InfinityFree_ · **Repo:** _isi URL GitHub_

## Fitur
- Daftar produk (cari, filter kategori, paginasi) dan detail produk
- CRUD produk dengan upload gambar (penjual/admin)
- Keranjang belanja dan checkout (transaksi database, kunci stok)
- Diskon otomatis: >5 barang 10%, >15 barang 25%, >25 barang 30% (aturan di `includes/diskon.php`)
- Riwayat pesanan, kelola pesanan (admin)
- Login/registrasi dengan peran admin, penjual, pembeli

## Instalasi
1. Impor `database.sql` ke database MySQL (phpMyAdmin → Import).
2. Salin `config.sample.php` → `config.php`. File ini otomatis memakai pengaturan **XAMPP** (root, tanpa password, database `db_marketplace`) saat dibuka lewat `localhost`, dan pengaturan **InfinityFree** saat online — isi bagian `INFINITYFREE` dengan kredensial hosting. `BASE_URL` terdeteksi otomatis. **Jangan commit `config.php`.**
3. Letakkan proyek di `htdocs` (atau folder web server) dan buka di browser.

Akun demo (ganti/hapus untuk penggunaan nyata):

| Peran | Email | Password |
|---|---|---|
| Admin | admin@pasar.test | admin123 |
| Penjual | penjual@pasar.test | penjual123 |
| Pembeli | pembeli@pasar.test | pembeli123 |

Perancangan lengkap (ERD, alur, mockup, deployment, pengujian) ada di `docs/perancangan.docx` / `docs/perancangan.md`.

## Fitur Admin
- Dashboard ringkasan marketplace
- Data produk dan monitoring stok
- Laporan stok kepada penjual
- Monitoring pesanan
- Kelola pengguna dan role
- Kelola kategori produk
- Laporan penjualan, produk terlaris, status pesanan, dan performa penjual
