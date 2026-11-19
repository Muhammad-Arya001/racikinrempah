<?php
require __DIR__ . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$p = row(
    $db,
    'SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
       FROM produk p
       LEFT JOIN kategori k ON k.id_kategori = p.id_kategori
       JOIN users u ON u.id_user = p.id_penjual
      WHERE p.id_produk = ?',
    'i',
    [$id]
);
if (!$p) {
    http_response_code(404);
    $judul = 'Produk tidak ditemukan';
    include __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">Produk tidak ditemukan. <a href="' . BASE_URL . '/index.php">Kembali ke daftar produk</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
$judul = $p['nama_produk'];
include __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/katalog.php">Produk</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/katalog.php?kategori=<?= (int) $p['id_kategori'] ?>"><?= e($p['nama_kategori'] ?? 'Umum') ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($p['nama_produk']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-6"><?= gambar_html($p, 'img-detail') ?></div>
    <div class="col-md-6">
        <h1 class="h3"><?= e($p['nama_produk']) ?></h1>
        <div class="display-6 text-success fw-bold mb-3"><?= rupiah($p['harga']) ?></div>
        <dl class="row mb-3">
            <dt class="col-4">Kategori</dt><dd class="col-8"><?= e($p['nama_kategori'] ?? 'Umum') ?></dd>
            <dt class="col-4">Penjual</dt><dd class="col-8"><?= e($p['nama_penjual']) ?></dd>
            <dt class="col-4">Stok</dt>
            <dd class="col-8">
                <?= $p['stok'] > 0 ? '<span class="text-success">' . (int) $p['stok'] . ' tersedia</span>' : '<span class="text-danger">Habis</span>' ?>
            </dd>
        </dl>
        <p class="text-secondary"><?= nl2br(e($p['deskripsi'] ?? '')) ?></p>

        <form method="post" action="<?= BASE_URL ?>/keranjang_tambah.php" class="d-flex gap-2 align-items-center mt-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
            <input type="number" name="jumlah" value="1" min="1" max="<?= max(1, (int) $p['stok']) ?>" class="form-control qty-input" aria-label="Jumlah" <?= $p['stok'] < 1 ? 'disabled' : '' ?>>
            <button class="btn btn-brand btn-lg" <?= $p['stok'] < 1 ? 'disabled' : '' ?>>Tambah ke Keranjang</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
