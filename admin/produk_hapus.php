<?php
require __DIR__ . '/../includes/init.php';

$u = require_role(['penjual']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/produk.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

$p = row(
    $db,
    'SELECT id_produk, id_penjual, nama_produk, gambar FROM produk WHERE id_produk = ?',
    'i',
    [$id]
);

if (!$p || (int) $p['id_penjual'] !== (int) $u['id_user']) {
    flash('danger', 'Produk tidak ditemukan atau bukan milik Anda.');
    redirect('admin/produk.php');
}

$pernahDipesan = row(
    $db,
    'SELECT 1 AS x FROM detail_pesanan WHERE id_produk = ? LIMIT 1',
    'i',
    [$id]
);

if ($pernahDipesan) {
    flash('warning', 'Produk tidak dapat dihapus karena sudah pernah masuk ke pesanan. Silakan ubah stok menjadi 0 jika produk tidak dijual lagi.');
    redirect('admin/produk.php');
}

query($db, 'DELETE FROM produk WHERE id_produk = ? AND id_penjual = ?', 'ii', [$id, $u['id_user']])->close();
hapus_gambar($p['gambar']);

flash('success', 'Produk "' . $p['nama_produk'] . '" berhasil dihapus.');
redirect('admin/produk.php');
