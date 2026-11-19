<?php
// GET /api/kategori.php -> daftar kategori beserta jumlah produk
require __DIR__ . '/lib/helper.php';

hanya_metode(['GET']);
$conn = koneksi_db();

$sql = "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
        FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori
        GROUP BY k.id_kategori, k.nama_kategori ORDER BY k.id_kategori";
$hasil = $conn->query($sql);
if (!$hasil) {
    gagal(500, 'Terjadi kesalahan saat membaca data kategori');
}

$data = [];
while ($r = $hasil->fetch_assoc()) {
    $data[] = [
        'id'            => (int)$r['id_kategori'],
        'nama'          => $r['nama_kategori'],
        'jumlah_produk' => (int)$r['jumlah_produk'],
    ];
}

if (count($data) === 0) {
    gagal(404, 'Data kategori tidak ditemukan');
}
sukses($data, 'Daftar kategori berhasil diambil');
