<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

$kode = (string) ($_GET['kode'] ?? '');
$ps = row(
    $db,
    'SELECT ps.*, us.nama AS nama_pembeli FROM pesanan ps JOIN users us ON us.id_user = ps.id_user WHERE ps.kode_pesanan = ?',
    's',
    [$kode]
);

// Hak akses: pemilik pesanan, admin, atau penjual yang produknya ada di pesanan ini
$boleh = false;
if ($ps) {
    if ((int) $ps['id_user'] === (int) $u['id_user'] || $u['role'] === 'admin') {
        $boleh = true;
    } elseif ($u['role'] === 'penjual') {
        $boleh = (bool) row(
            $db,
            'SELECT 1 AS x FROM detail_pesanan d JOIN produk p ON p.id_produk = d.id_produk
              WHERE d.id_pesanan = ? AND p.id_penjual = ? LIMIT 1',
            'ii',
            [$ps['id_pesanan'], $u['id_user']]
        );
    }
}
if (!$ps || !$boleh) {
    http_response_code(404);
    $judul = 'Pesanan tidak ditemukan';
    include __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">Pesanan tidak ditemukan.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$detail = rows($db, 'SELECT * FROM detail_pesanan WHERE id_pesanan = ? ORDER BY id_detail', 'i', [$ps['id_pesanan']]);
$judul = 'Pesanan ' . $ps['kode_pesanan'];
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Pesanan <?= e($ps['kode_pesanan']) ?></h1>
        <div class="text-muted small"><?= e(date('d M Y H:i', strtotime($ps['tanggal']))) ?> · oleh <?= e($ps['nama_pembeli']) ?></div>
    </div>
    <div class="fs-5"><?= status_badge($ps['status']) ?></div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table mb-0">
                <thead class="table-light"><tr><th>Produk</th><th class="text-end">Harga</th><th class="text-center">Jml</th><th class="text-end">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($detail as $d): ?>
                    <tr>
                        <td><?= e($d['nama_produk']) ?></td>
                        <td class="text-end"><?= rupiah($d['harga']) ?></td>
                        <td class="text-center"><?= (int) $d['jumlah'] ?></td>
                        <td class="text-end"><?= rupiah($d['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <?php if ((float) $ps['subtotal'] > 0 && (int) $ps['diskon_persen'] > 0): ?>
                        <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= rupiah($ps['subtotal']) ?></td></tr>
                        <tr class="diskon-baris"><td colspan="3" class="text-end">Diskon <?= (int) $ps['diskon_persen'] ?>%</td><td class="text-end">− <?= rupiah($ps['diskon_nominal']) ?></td></tr>
                    <?php endif; ?>
                    <tr class="table-light"><th colspan="3" class="text-end">Total</th><th class="text-end text-success"><?= rupiah($ps['total']) ?></th></tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6">Dikirim ke</h2>
            <p class="mb-1 fw-semibold"><?= e($ps['nama_penerima']) ?></p>
            <p class="mb-1"><?= nl2br(e($ps['alamat_kirim'])) ?></p>
            <p class="mb-1 text-muted"><?= e($ps['no_hp']) ?></p>
            <?php if ($ps['catatan']): ?><p class="mb-0 small fst-italic">Catatan: <?= e($ps['catatan']) ?></p><?php endif; ?>
        </div></div>
    </div>
</div>
<a href="<?= BASE_URL ?>/pesanan.php" class="btn btn-link mt-3">← Semua pesanan</a>
<?php include __DIR__ . '/includes/footer.php'; ?>
