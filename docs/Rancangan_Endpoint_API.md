# Dokumen Rancangan Endpoint REST API — RacikInRempah (Marketplace Rempah)

## A. Analisis dan Perancangan

### a. Fungsi Web Service/API dan alasan API diperlukan

Web Service/API adalah antarmuka yang memungkinkan satu aplikasi meminta dan mengirim data ke aplikasi lain melalui protokol HTTP dengan format data standar (JSON). Pada marketplace RacikInRempah, API menjadi pintu akses terkontrol ke database (produk, kategori, pesanan) tanpa membuka database secara langsung.

API diperlukan karena:
1. **Interoperabilitas** – aplikasi mobile (Android/iOS), aplikasi pihak ketiga, atau sistem lain yang berbeda bahasa/platform dapat memakai data yang sama lewat JSON.
2. **Pemisahan lapisan** – tampilan (web/mobile) terpisah dari logika bisnis dan database, sehingga perubahan di satu sisi tidak merusak sisi lain.
3. **Keamanan** – aplikasi lain tidak mengakses MySQL langsung; input divalidasi di server dan kredensial database tidak terekspos.
4. **Satu sumber data** – web, mobile, dan mitra membaca data yang konsisten dari satu layanan (misalnya stok dan harga yang sama).
5. **Skalabilitas** – layanan baru (aplikasi kurir, marketplace mitra) cukup memakai API yang sama.

### b. Rancangan Endpoint

| No | Method & Endpoint | Fungsi | Parameter | Kode Respons |
|----|-------------------|--------|-----------|--------------|
| 1 | `GET /api/produk.php` | Menampilkan seluruh produk | opsional `kategori`, `q` | 200, 400, 404 |
| 2 | `GET /api/produk.php?id=1` | Menampilkan detail produk | `id` (wajib, angka) | 200, 400, 404 |
| 3 | `GET /api/kategori.php` | Menampilkan daftar kategori | – | 200, 404 |
| 4 | `POST /api/pesanan.php` | Membuat pesanan baru | body JSON (lihat bawah) | 201, 400, 404, 409, 422 |
| 5 | `GET /api/pesanan.php?kode=INV...` | Melihat pesanan & itemnya | `kode` (wajib) | 200, 400, 404 |

Kode lain: `405` metode tidak diizinkan, `500` kesalahan server/database.

**Hubungan dengan database Soal 11:** `produk` (+ `kategori`, `users` sebagai penjual) untuk endpoint 1–3; `pesanan`, `detail_pesanan`, `users` (pembeli) untuk endpoint 4–5. Aturan diskon pesanan sama dengan aplikasi web (>5 barang 10%, >15 barang 25%, >25 barang 30%), dihitung di server.

**Body POST /api/pesanan.php**
```json
{
  "id_user": 3,
  "nama_penerima": "Budi Pembeli",
  "alamat_kirim": "Jl. Melati No. 12, Bengkulu",
  "no_hp": "081200000003",
  "catatan": "Tolong dikemas rapi",
  "items": [
    { "id_produk": 1, "jumlah": 2 },
    { "id_produk": 5, "jumlah": 1 }
  ]
}
```
(`id_user` = id akun ber-role *pembeli*; pada data contoh, Budi Pembeli = 3. `catatan` opsional.)

### c. Struktur JSON Response

**Daftar produk** – `GET /api/produk.php`
```json
{
  "status": "success",
  "message": "Daftar produk berhasil diambil",
  "data": {
    "total": 14,
    "produk": [
      {
        "id": 1,
        "nama": "Wedang Jahe Instan 10 Sachet",
        "deskripsi": "Wedang jahe merah instan, hangat dan pedas manis. ...",
        "harga": 28000,
        "stok": 60,
        "gambar": null,
        "kategori": { "id": 1, "nama": "Minuman Rempah" },
        "penjual": { "id": 2, "nama": "Dapur Rempah Kito" }
      }
    ]
  }
}
```

**Detail produk** – `GET /api/produk.php?id=1`
```json
{
  "status": "success",
  "message": "Detail produk ditemukan",
  "data": {
    "id": 1,
    "nama": "Wedang Jahe Instan 10 Sachet",
    "deskripsi": "Wedang jahe merah instan, hangat dan pedas manis. ...",
    "harga": 28000,
    "stok": 60,
    "gambar": null,
    "kategori": { "id": 1, "nama": "Minuman Rempah" },
    "penjual": { "id": 2, "nama": "Dapur Rempah Kito" }
  }
}
```

**Error – data tidak ditemukan (404)**
```json
{ "status": "error", "message": "Produk dengan id 999 tidak ditemukan" }
```

**Error validasi (422)**
```json
{
  "status": "error",
  "message": "Data pesanan tidak valid",
  "errors": { "nama_penerima": "Wajib diisi, maksimal 100 karakter", "items": "Minimal satu item: [{\"id_produk\":1,\"jumlah\":2}]" }
}
```

> Contoh di atas hanya ilustrasi rancangan. Untuk laporan, pakai response **asli** dari XAMPP Anda (lihat `Laporan_Pengujian.md`).
