<?php
// GET /api/produk.php                -> semua produk (opsional ?kategori=ID, ?q=kata)
// GET /api/produk.php?id=1           -> detail produk
require __DIR__ . '/lib/helper.php';

hanya_metode(['GET']);
$conn = koneksi_db();

$sqlDasar = "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar,
                    k.id_kategori, k.nama_kategori,
                    u.id_user AS id_penjual, u.nama AS nama_penjual
             FROM produk p
             LEFT JOIN kategori k ON k.id_kategori = p.id_kategori
             JOIN users u ON u.id_user = p.id_penjual";

// URL gambar mengarah ke folder uploads/ pada project web (satu tingkat di atas api/)
function url_gambar($nama)
{
    if ($nama === null || $nama === '') {
        return null;
    }
    $skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $basis = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    return $skema . '://' . $_SERVER['HTTP_HOST'] . $basis . '/uploads/' . rawurlencode($nama);
}

function format_produk(array $r)
{
    return [
        'id'        => (int)$r['id_produk'],
        'nama'      => $r['nama_produk'],
        'deskripsi' => $r['deskripsi'],
        'harga'     => (float)$r['harga'],
        'stok'      => (int)$r['stok'],
        'gambar'    => $r['gambar'],
        'gambar_url'=> url_gambar($r['gambar']),
        'kategori'  => $r['id_kategori'] === null ? null
                       : ['id' => (int)$r['id_kategori'], 'nama' => $r['nama_kategori']],
        'penjual'   => ['id' => (int)$r['id_penjual'], 'nama' => $r['nama_penjual']],
    ];
}

// ---- Detail produk ----
if (isset($_GET['id'])) {
    if (!ctype_digit((string)$_GET['id']) || (int)$_GET['id'] < 1) {
        gagal(400, 'Parameter id harus berupa angka positif');
    }
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare($sqlDasar . " WHERE p.id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        gagal(404, "Produk dengan id $id tidak ditemukan");
    }
    sukses(format_produk($row), 'Detail produk ditemukan');
}

// ---- Daftar produk (dengan filter opsional) ----
$where = [];
$tipe = '';
$params = [];

if (isset($_GET['kategori'])) {
    if (!ctype_digit((string)$_GET['kategori'])) {
        gagal(400, 'Parameter kategori harus berupa angka');
    }
    $where[] = 'p.id_kategori = ?';
    $tipe .= 'i';
    $params[] = (int)$_GET['kategori'];
}
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $where[] = 'p.nama_produk LIKE ?';
    $tipe .= 's';
    $params[] = '%' . trim($_GET['q']) . '%';
}

$sql = $sqlDasar . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY p.id_produk';
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($tipe, ...$params);
}
$stmt->execute();
$hasil = $stmt->get_result();

$data = [];
while ($row = $hasil->fetch_assoc()) {
    $data[] = format_produk($row);
}

if (count($data) === 0) {
    gagal(404, 'Data produk tidak ditemukan');
}
sukses(['total' => count($data), 'produk' => $data], 'Daftar produk berhasil diambil');
