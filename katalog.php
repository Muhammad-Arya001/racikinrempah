<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/kartu_produk.php';
$judul = 'Semua Produk';

$q    = trim($_GET['q'] ?? '');
$kat  = (int) ($_GET['kategori'] ?? 0);
$hal  = max(1, (int) ($_GET['hal'] ?? 1));
$per  = 8;

$where = ['1=1'];
$types = '';
$params = [];
if ($q !== '') {
    $where[] = '(p.nama_produk LIKE ? OR p.deskripsi LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'ss';
    array_push($params, $like, $like);
}
if ($kat > 0) {
    $where[] = 'p.id_kategori = ?';
    $types .= 'i';
    $params[] = $kat;
}
$w = implode(' AND ', $where);

$total = (int) row($db, "SELECT COUNT(*) AS n FROM produk p WHERE $w", $types, $params)['n'];
$halaman = max(1, (int) ceil($total / $per));
$hal = min($hal, $halaman);
$off = ($hal - 1) * $per;

$produk = rows(
    $db,
    "SELECT p.*, k.nama_kategori, u.nama AS nama_penjual
       FROM produk p
       LEFT JOIN kategori k ON k.id_kategori = p.id_kategori
       JOIN users u ON u.id_user = p.id_penjual
      WHERE $w
      ORDER BY p.id_produk DESC
      LIMIT $per OFFSET $off",
    $types,
    $params
);
$kategoriList = rows($db, 'SELECT * FROM kategori ORDER BY nama_kategori');

include __DIR__ . '/includes/header.php';
?>
<div class="mb-3">
    <div class="eyebrow">Katalog</div>
    <h1 class="judul-seksi h2 mb-0">Semua Produk</h1>
</div>

<form class="row g-2 mb-4" method="get">
    <div class="col-12 col-md-6">
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari produk…" aria-label="Cari produk">
    </div>
    <div class="col-8 col-md-4">
        <select name="kategori" class="form-select" aria-label="Kategori">
            <option value="0">Semua kategori</option>
            <?php foreach ($kategoriList as $k): ?>
                <option value="<?= $k['id_kategori'] ?>" <?= $kat === (int) $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-4 col-md-2 d-grid"><button class="btn btn-brand">Cari</button></div>
</form>

<?php if (!$produk): ?>
    <div class="alert alert-secondary">Tidak ada produk yang cocok dengan pencarian Anda.</div>
<?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
        <?php foreach ($produk as $p) { echo kartu_produk($p); } ?>
    </div>

    <?php if ($halaman > 1): ?>
        <nav class="mt-4" aria-label="Halaman">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $halaman; $i++): ?>
                    <li class="page-item <?= $i === $hal ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= e(http_build_query(['q' => $q, 'kategori' => $kat, 'hal' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
