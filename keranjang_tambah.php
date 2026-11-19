<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_verify();
$u = require_login();

$idProduk = (int) ($_POST['id_produk'] ?? 0);
$jumlah   = max(1, (int) ($_POST['jumlah'] ?? 1));

$p = row($db, 'SELECT id_produk, nama_produk, stok, id_penjual FROM produk WHERE id_produk = ?', 'i', [$idProduk]);

if (!$p) {
    flash('danger', 'Produk tidak ditemukan.');
} elseif ((int) $p['id_penjual'] === (int) $u['id_user']) {
    flash('warning', 'Anda tidak dapat membeli produk milik sendiri.');
} elseif ((int) $p['stok'] < 1) {
    flash('warning', 'Maaf, stok "' . $p['nama_produk'] . '" habis.');
} else {
    $ada  = row($db, 'SELECT id_keranjang, jumlah FROM keranjang WHERE id_user = ? AND id_produk = ?', 'ii', [$u['id_user'], $idProduk]);
    $baru = min(($ada['jumlah'] ?? 0) + $jumlah, (int) $p['stok']);

    if ($ada) {
        query($db, 'UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ?', 'ii', [$baru, $ada['id_keranjang']])->close();
    } else {
        query($db, 'INSERT INTO keranjang (id_user, id_produk, jumlah) VALUES (?, ?, ?)', 'iii', [$u['id_user'], $idProduk, $baru])->close();
    }
    $pesan = '"' . $p['nama_produk'] . '" ditambahkan ke keranjang.';
    if ($baru < ($ada['jumlah'] ?? 0) + $jumlah) {
        $pesan .= ' Jumlah dibatasi sesuai stok (' . $p['stok'] . ').';
    }
    flash('success', $pesan);
}

header('Location: ' . safe_back('index.php'));
exit;
