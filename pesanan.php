<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

$daftar = rows(
    $db,
    'SELECT kode_pesanan, tanggal, total, status FROM pesanan WHERE id_user = ? ORDER BY id_pesanan DESC',
    'i',
    [$u['id_user']]
);
$judul = 'Pesanan Saya';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Pesanan Saya</h1>
<?php if (!$daftar): ?>
    <div class="alert alert-secondary">Belum ada pesanan. <a href="<?= BASE_URL ?>/katalog.php">Belanja sekarang</a></div>
<?php else: ?>
    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Kode</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $d): ?>
                <tr>
                    <td class="fw-semibold"><?= e($d['kode_pesanan']) ?></td>
                    <td><?= e(date('d M Y H:i', strtotime($d['tanggal']))) ?></td>
                    <td class="text-end"><?= rupiah($d['total']) ?></td>
                    <td><?= status_badge($d['status']) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/pesanan_detail.php?kode=<?= urlencode($d['kode_pesanan']) ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
