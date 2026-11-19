<?php
/** Header utama. Membutuhkan includes/init.php. */
$u = current_user();
$jumlahKeranjang = 0;
$jumlahLaporanStok = 0;

if ($u && $u['role'] === 'penjual') {
    $r = row($db, "SELECT COUNT(*) AS n FROM laporan_stok WHERE id_penjual = ? AND status = 'baru'", 'i', [$u['id_user']]);
    $jumlahLaporanStok = (int) ($r['n'] ?? 0);
}

if ($u && $u['role'] === 'pembeli') {
    $r = row($db, 'SELECT COALESCE(SUM(jumlah),0) AS n FROM keranjang WHERE id_user = ?', 'i', [$u['id_user']]);
    $jumlahKeranjang = (int) ($r['n'] ?? 0);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul ?? 'Beranda') ?> · RacikInRempah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/logo.png">
    <link href="<?= BASE_URL ?>/assets/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-brand sticky-top">
    <div class="container">
        <a class="navbar-brand brand-wrap" href="<?= BASE_URL ?>/index.php">
            <img class="brand-logo" src="<?= BASE_URL ?>/assets/logo.png" alt="" width="44" height="44">
            <span class="brand-teks"><span class="brand-nama">Racik<span class="brand-in">In</span>Rempah</span><small class="brand-tag">Ramuan &amp; Rempah Nusantara</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php">Beranda</a></li>

                <?php if (!$u || $u['role'] === 'pembeli'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/katalog.php">Produk</a></li>
                <?php endif; ?>

                <?php if ($u && $u['role'] === 'penjual'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/katalog.php">Lihat Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/produk.php">Produk Saya</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/pesanan.php">Pesanan Masuk</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/laporan_stok.php">Laporan Stok <?php if ($jumlahLaporanStok > 0): ?><span class="badge rounded-pill bg-warning text-dark"><?= $jumlahLaporanStok ?></span><?php endif; ?></a></li>
                <?php endif; ?>

                <?php if ($u && $u['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/produk.php">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/pesanan.php">Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/pengguna.php">Pengguna</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/laporan.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/laporan_stok.php">Stok</a></li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <?php if ($u && $u['role'] === 'pembeli'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/keranjang.php">
                            🛒 Keranjang
                            <?php if ($jumlahKeranjang > 0): ?><span class="badge rounded-pill bg-warning text-dark"><?= $jumlahKeranjang ?></span><?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/pesanan.php">Pesanan Saya</a></li>
                <?php endif; ?>

                <?php if ($u): ?>
                    <li class="nav-item"><span class="navbar-text small text-white-50"><?= e($u['nama']) ?> (<?= e(ucfirst($u['role'])) ?>)</span></li>
                    <li class="nav-item">
                        <form method="post" action="<?= BASE_URL ?>/logout.php" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-light">Keluar</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login.php">Masuk</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-gold" href="<?= BASE_URL ?>/register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 flex-grow-1">
<?php foreach ($_SESSION['flash'] ?? [] as [$tipe, $pesan]): ?>
    <div class="alert alert-<?= e($tipe) ?> alert-dismissible fade show" role="alert">
        <?= e($pesan) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endforeach; unset($_SESSION['flash']); ?>
