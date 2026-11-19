<?php
require __DIR__ . '/../includes/init.php';
$u = require_role(['admin']);

$totalPengguna = (int) (row($db, 'SELECT COUNT(*) AS n FROM users')['n'] ?? 0);
$totalPembeli = (int) (row($db, "SELECT COUNT(*) AS n FROM users WHERE role = 'pembeli'")['n'] ?? 0);
$totalPenjual = (int) (row($db, "SELECT COUNT(*) AS n FROM users WHERE role = 'penjual'")['n'] ?? 0);
$totalProduk = (int) (row($db, 'SELECT COUNT(*) AS n FROM produk')['n'] ?? 0);
$stokMenipis = (int) (row($db, 'SELECT COUNT(*) AS n FROM produk WHERE stok <= 5')['n'] ?? 0);
$stokHabis = (int) (row($db, 'SELECT COUNT(*) AS n FROM produk WHERE stok = 0')['n'] ?? 0);
$totalPesanan = (int) (row($db, 'SELECT COUNT(*) AS n FROM pesanan')['n'] ?? 0);
$pesananAktif = (int) (row($db, "SELECT COUNT(*) AS n FROM pesanan WHERE status NOT IN ('selesai','batal')")['n'] ?? 0);
$pesananSelesai = (int) (row($db, "SELECT COUNT(*) AS n FROM pesanan WHERE status = 'selesai'")['n'] ?? 0);
$totalPenjualan = (float) (row($db, "SELECT COALESCE(SUM(total),0) AS n FROM pesanan WHERE status <> 'batal'")['n'] ?? 0);

$laporanBaru = (int) (row($db, "SELECT COUNT(*) AS n FROM laporan_stok WHERE status = 'baru'")['n'] ?? 0);
$pesananTerbaru = rows($db, 'SELECT ps.id_pesanan, ps.kode_pesanan, ps.total, ps.status, ps.tanggal, u.nama AS pembeli FROM pesanan ps JOIN users u ON u.id_user = ps.id_user ORDER BY ps.id_pesanan DESC LIMIT 8');
$stokTerendah = rows($db, 'SELECT p.id_produk, p.nama_produk, p.stok, u.nama AS nama_penjual FROM produk p JOIN users u ON u.id_user = p.id_penjual WHERE p.stok <= 5 ORDER BY p.stok ASC, p.nama_produk ASC LIMIT 8');
$penjualTeratas = rows($db, "SELECT u.id_user, u.nama, COUNT(DISTINCT p.id_produk) AS produk, COALESCE(SUM(d.subtotal),0) AS nilai_penjualan FROM users u LEFT JOIN produk p ON p.id_penjual = u.id_user LEFT JOIN detail_pesanan d ON d.id_produk = p.id_produk LEFT JOIN pesanan ps ON ps.id_pesanan = d.id_pesanan AND ps.status <> 'batal' WHERE u.role = 'penjual' GROUP BY u.id_user, u.nama ORDER BY nilai_penjualan DESC, produk DESC LIMIT 5");

$judul = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div><div class="eyebrow">Pusat Pengelolaan</div><h1 class="h3 mb-1">Dashboard Admin</h1><p class="text-muted mb-0">Kelola data marketplace, pantau aktivitas, dan tindak lanjuti kondisi sistem.</p></div>
    <div class="d-flex gap-2"><a href="<?= BASE_URL ?>/admin/laporan.php" class="btn btn-outline-primary">📊 Lihat Laporan</a><a href="<?= BASE_URL ?>/admin/kategori.php" class="btn btn-brand">🏷️ Kelola Kategori</a></div>
</div>

<div class="row g-3 mb-4">
<?php $cards = [
 ['Total Pengguna',$totalPengguna,'👥','Kelola Pengguna','admin/pengguna.php','primary'],
 ['Penjual',$totalPenjual,'🏪','Data Pengguna','admin/pengguna.php','success'],
 ['Produk',$totalProduk,'📦','Data Produk','admin/produk.php','info'],
 ['Stok Menipis',$stokMenipis,'⚠️','Laporan Stok','admin/laporan_stok.php','warning'],
 ['Total Pesanan',$totalPesanan,'🛒','Monitoring Pesanan','admin/pesanan.php','primary'],
 ['Pesanan Aktif',$pesananAktif,'🔄','Monitoring Pesanan','admin/pesanan.php','info'],
 ['Pesanan Selesai',$pesananSelesai,'✅','Monitoring Pesanan','admin/pesanan.php','success'],
 ['Nilai Penjualan',$totalPenjualan,'💰','Laporan Penjualan','admin/laporan.php?jenis=penjualan','warning'],
]; foreach ($cards as [$label,$value,$icon,$linkText,$link,$tone]): ?>
<div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><div><div class="small text-muted"><?= e($label) ?></div><div class="fs-4 fw-bold mt-1"><?= $label === 'Nilai Penjualan' ? rupiah($value) : number_format((int)$value,0,',','.') ?></div></div><span class="fs-3"><?= $icon ?></span></div><a class="small text-decoration-none d-inline-block mt-3" href="<?= BASE_URL ?>/<?= e($link) ?>"><?= e($linkText) ?> →</a></div></div></div>
<?php endforeach; ?>
</div>

<?php if ($laporanBaru > 0): ?><div class="alert alert-warning d-flex justify-content-between align-items-center gap-3"><div><strong>🔔 <?= $laporanBaru ?> laporan stok baru</strong><div class="small">Ada laporan stok yang perlu dipantau.</div></div><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>/admin/laporan_stok.php">Buka Laporan Stok</a></div><?php endif; ?>

<div class="row g-4">
<div class="col-lg-7"><div class="card border-0 shadow-sm h-100"><div class="card-body p-0"><div class="p-3 border-bottom d-flex justify-content-between"><div><h2 class="h5 mb-1">Pesanan Terbaru</h2><div class="small text-muted">Aktivitas transaksi terbaru di marketplace.</div></div><a class="small" href="<?= BASE_URL ?>/admin/pesanan.php">Lihat semua</a></div><?php if (!$pesananTerbaru): ?><div class="p-4 text-muted">Belum ada pesanan.</div><?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Pembeli</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach ($pesananTerbaru as $p): ?><tr><td class="fw-semibold"><?= e($p['kode_pesanan']) ?></td><td><?= e($p['pembeli']) ?></td><td class="small text-nowrap"><?= e(date('d M Y H:i', strtotime($p['tanggal']))) ?></td><td class="text-nowrap"><?= rupiah($p['total']) ?></td><td><?= status_badge($p['status']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div></div>
<div class="col-lg-5"><div class="card border-0 shadow-sm h-100"><div class="card-body p-0"><div class="p-3 border-bottom d-flex justify-content-between"><div><h2 class="h5 mb-1">Stok Menipis</h2><div class="small text-muted">Produk dengan stok 5 unit atau kurang.</div></div><a class="small" href="<?= BASE_URL ?>/admin/laporan_stok.php">Kelola</a></div><?php if (!$stokTerendah): ?><div class="p-4 text-muted">Semua stok masih aman.</div><?php else: ?><div class="list-group list-group-flush"><?php foreach ($stokTerendah as $p): ?><a href="<?= BASE_URL ?>/admin/laporan_stok.php?id=<?= (int)$p['id_produk'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"><div><div class="fw-semibold"><?= e($p['nama_produk']) ?></div><div class="small text-muted"><?= e($p['nama_penjual']) ?></div></div><span class="badge text-bg-warning"><?= (int)$p['stok'] ?> unit</span></a><?php endforeach; ?></div><?php endif; ?></div></div></div>
<div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h2 class="h5">Ringkasan Stok</h2><div class="row g-3 mt-1"><div class="col-6"><div class="p-3 rounded border"><div class="small text-muted">Produk stok menipis</div><div class="fs-3 fw-bold text-warning"><?= $stokMenipis ?></div></div></div><div class="col-6"><div class="p-3 rounded border"><div class="small text-muted">Produk stok habis</div><div class="fs-3 fw-bold text-danger"><?= $stokHabis ?></div></div></div></div><a class="btn btn-outline-primary mt-3" href="<?= BASE_URL ?>/admin/produk.php">Buka Data Produk</a></div></div></div>
<div class="col-lg-6"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h2 class="h5">Penjual Teratas</h2><?php if (!$penjualTeratas): ?><div class="text-muted">Belum ada data penjual.</div><?php else: ?><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Penjual</th><th>Produk</th><th class="text-end">Penjualan</th></tr></thead><tbody><?php foreach ($penjualTeratas as $p): ?><tr><td><?= e($p['nama']) ?></td><td><?= (int)$p['produk'] ?></td><td class="text-end"><?= rupiah($p['nilai_penjualan']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?><a class="btn btn-outline-primary mt-2" href="<?= BASE_URL ?>/admin/laporan.php?jenis=penjual">Lihat Laporan Penjual</a></div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
