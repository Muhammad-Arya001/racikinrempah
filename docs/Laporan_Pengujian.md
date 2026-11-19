# Laporan Hasil Pengujian REST API Marketplace

**Nama / NIM :** ______________________
**Mata Kuliah :** ______________________
**Tanggal Uji :** ______________________
**URL GitHub :** https://github.com/<username>/RacikInRempah

## 1. Lingkungan Pengujian
- XAMPP versi: ____ (Apache & MySQL dalam status *Running*) — *screenshot XAMPP Control Panel*
- PHP versi: ____ | MySQL/MariaDB versi: ____
- Database: `rempahkito` (file `database_xampp.sql`, dari Soal 11) — *screenshot phpMyAdmin*
- Folder proyek: `C:\xampp\htdocs\rempahkitofinal`
- Base URL: `http://localhost/rempahkitofinal/api/`

## 2. Ringkasan Hasil Uji

| No | Skenario | Request | Status yang diharapkan | Status didapat | Hasil |
|----|----------|---------|------------------------|----------------|-------|
| 1 | Semua produk | `GET /api/produk.php` | 200 | | |
| 2 | Detail produk | `GET /api/produk.php?id=1` | 200 | | |
| 3 | Produk tidak ada | `GET /api/produk.php?id=999` | 404 | | |
| 4 | ID tidak valid | `GET /api/produk.php?id=abc` | 400 | | |
| 5 | Daftar kategori | `GET /api/kategori.php` | 200 | | |
| 6 | Buat pesanan | `POST /api/pesanan.php` | 201 | | |
| 7 | Pesanan data kosong | `POST /api/pesanan.php` (body tidak lengkap) | 422 | | |
| 8 | Stok tidak cukup | `POST /api/pesanan.php` (jumlah besar) | 409 | | |
| 9 | Baca pesanan | `GET /api/pesanan.php?kode=<kode dari hasil POST>` | 200 | | |
| 10 | Metode salah | `DELETE /api/produk.php` | 405 | | |

## 3. Detail Pengujian
Untuk **setiap** skenario di atas, tempelkan:
1. Screenshot **browser** (URL `localhost` terlihat + JSON response), untuk request GET.
2. Screenshot **Postman** (method, URL, body, status code, response JSON).
3. Analisis singkat (1–2 kalimat): apakah sesuai harapan?

### 3.1 GET semua produk
- URL: `http://localhost/rempahkitofinal/api/produk.php`
- Screenshot browser: _(tempel)_
- Screenshot Postman: _(tempel)_
- Analisis: ...

### 3.2 GET detail produk
- URL: `http://localhost/rempahkitofinal/api/produk.php?id=1`
- Screenshot: _(tempel)_

### 3.3 GET daftar kategori
- Screenshot: _(tempel)_

### 3.4 POST pesanan
- Postman: Method `POST`, URL `http://localhost/rempahkitofinal/api/pesanan.php`, Body → raw → JSON:
```json
{
  "id_user": 3,
  "nama_penerima": "Budi Pembeli",
  "alamat_kirim": "Jl. Melati No. 12, Bengkulu",
  "no_hp": "081200000003",
  "items": [ { "id_produk": 1, "jumlah": 2 } ]
}
```
- Screenshot request + response 201: _(tempel)_

### 3.5 Pengujian error
- 404, 400, 422, 409, 405: _(tempel screenshot masing-masing)_

## 4. Bukti Data di Database MySQL
Jalankan di phpMyAdmin (tab SQL) setelah POST pesanan, lalu screenshot hasilnya:
```sql
SELECT * FROM pesanan ORDER BY id_pesanan DESC;
SELECT * FROM detail_pesanan ORDER BY id_detail DESC;
SELECT id_produk, nama_produk, stok FROM produk;  -- stok harus berkurang
```
- Screenshot: _(tempel)_

## 5. Kesimpulan
(Tulis 3–5 kalimat: API berjalan di XAMPP, mengembalikan JSON, menangani error, dan data pesanan tersimpan ke MySQL.)
