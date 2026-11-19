<?php
require __DIR__ . '/../includes/init.php';

$u = require_role(['admin', 'penjual']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string) ($_POST['aksi'] ?? '');
    $idLaporan = (int) ($_POST['id_laporan'] ?? 0);

    $laporan = row(
        $db,
        'SELECT l.*, p.nama_produk, p.stok AS stok_sekarang
         FROM laporan_stok l
         LEFT JOIN produk p ON p.id_produk = l.id_produk
         WHERE l.id_laporan = ?',
        'i',
        [$idLaporan]
    );

    if (!$laporan) {
        flash('danger', 'Laporan stok tidak ditemukan.');
        redirect('admin/laporan_stok.php');
    }

    if ($u['role'] === 'admin' && $aksi === 'kirim') {
        $idProduk = (int) ($_POST['id_produk'] ?? 0);
        $pesan = trim((string) ($_POST['pesan'] ?? ''));
        $produk = row(
            $db,
            'SELECT p.id_produk, p.nama_produk, p.stok, p.id_penjual, u.nama AS nama_penjual
             FROM produk p
             JOIN users u ON u.id_user = p.id_penjual
             WHERE p.id_produk = ? AND u.role = \'penjual\'',
            'i',
            [$idProduk]
        );

        if (!$produk) {
            flash('danger', 'Produk atau penjual tidak ditemukan.');
        } elseif ((int) $produk['stok'] > 5) {
            flash('warning', 'Produk tersebut tidak lagi memiliki stok yang menipis.');
        } elseif ($pesan === '') {
            flash('danger', 'Pesan laporan wajib diisi.');
        } elseif (mb_strlen($pesan) > 500) {
            flash('danger', 'Pesan laporan maksimal 500 karakter.');
        } else {
            query(
                $db,
                'INSERT INTO laporan_stok (id_produk, id_admin, id_penjual, stok_saat_laporan, pesan, status)
                 VALUES (?, ?, ?, ?, ?, \'baru\')',
                'iiiis',
                [$idProduk, $u['id_user'], $produk['id_penjual'], $produk['stok'], $pesan]
            )->close();
            flash('success', 'Laporan stok berhasil dikirim kepada ' . $produk['nama_penjual'] . '.');
        }
        redirect('admin/laporan_stok.php?id=' . $idProduk);
    }

    if ($u['role'] === 'penjual' && $aksi === 'dibaca') {
        if ((int) $laporan['id_penjual'] !== (int) $u['id_user']) {
            http_response_code(403);
            exit('Laporan ini bukan untuk toko Anda.');
        }
        query(
            $db,
            "UPDATE laporan_stok SET status = CASE WHEN status = 'baru' THEN 'dibaca' ELSE status END, dibaca_at = COALESCE(dibaca_at, NOW()) WHERE id_laporan = ? AND id_penjual = ?",
            'ii',
            [$idLaporan, $u['id_user']]
        )->close();
        flash('success', 'Laporan ditandai sudah dibaca.');
        redirect('admin/laporan_stok.php');
    }

    if ($u['role'] === 'penjual' && $aksi === 'tindaklanjuti') {
        if ((int) $laporan['id_penjual'] !== (int) $u['id_user']) {
            http_response_code(403);
            exit('Laporan ini bukan untuk toko Anda.');
        }
        query(
            $db,
            "UPDATE laporan_stok SET status = 'ditindaklanjuti', dibaca_at = COALESCE(dibaca_at, NOW()), ditindaklanjuti_at = NOW() WHERE id_laporan = ? AND id_penjual = ?",
            'ii',
            [$idLaporan, $u['id_user']]
        )->close();
        flash('success', 'Laporan stok ditandai sudah ditindaklanjuti.');
        redirect('admin/laporan_stok.php');
    }

    flash('warning', 'Aksi laporan tidak dikenali.');
    redirect('admin/laporan_stok.php');
}

$judul = 'Laporan Stok';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($u['role'] === 'admin'): ?>
    <?php
    $idProduk = (int) ($_GET['id'] ?? 0);
    $produkTerpilih = null;
    if ($idProduk > 0) {
        $produkTerpilih = row(
            $db,
            'SELECT p.id_produk, p.nama_produk, p.stok, u.nama AS nama_penjual
             FROM produk p JOIN users u ON u.id_user = p.id_penjual
             WHERE p.id_produk = ? AND u.role = \'penjual\'',
            'i',
            [$idProduk]
        );
    }

    $stokMenipis = rows(
        $db,
        'SELECT p.id_produk, p.nama_produk, p.stok, u.nama AS nama_penjual
         FROM produk p
         JOIN users u ON u.id_user = p.id_penjual
         WHERE p.stok <= 5 AND u.role = \'penjual\'
         ORDER BY p.stok ASC, p.nama_produk ASC'
    );

    $riwayat = rows(
        $db,
        'SELECT l.*, p.nama_produk, u.nama AS nama_penjual
         FROM laporan_stok l
         LEFT JOIN produk p ON p.id_produk = l.id_produk
         LEFT JOIN users u ON u.id_user = l.id_penjual
         ORDER BY l.id_laporan DESC
         LIMIT 50'
    );
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Laporan Stok</h1>
            <p class="text-muted mb-0">Pantau stok rendah dan kirim pemberitahuan langsung kepada penjual.</p>
        </div>
    </div>

    <?php if ($produkTerpilih): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Kirim Laporan Stok</h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><div class="small text-muted">Produk</div><div class="fw-semibold"><?= e($produkTerpilih['nama_produk']) ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Penjual</div><div class="fw-semibold"><?= e($produkTerpilih['nama_penjual']) ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Stok saat ini</div><div class="fw-semibold text-danger"><?= (int) $produkTerpilih['stok'] ?> unit</div></div>
                </div>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="kirim">
                    <input type="hidden" name="id_produk" value="<?= (int) $produkTerpilih['id_produk'] ?>">
                    <label class="form-label" for="pesan">Pesan untuk Penjual</label>
                    <textarea class="form-control mb-3" id="pesan" name="pesan" rows="3" maxlength="500" required>Stok produk <?= e($produkTerpilih['nama_produk']) ?> tersisa <?= (int) $produkTerpilih['stok'] ?> unit. Mohon segera melakukan penambahan stok.</textarea>
                    <button class="btn btn-brand">📣 Kirim Laporan</button>
                    <a href="<?= BASE_URL ?>/admin/laporan_stok.php" class="btn btn-outline-secondary">Batal</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="p-3 border-bottom"><h2 class="h5 mb-0">Produk dengan Stok Menipis</h2></div>
            <?php if (!$stokMenipis): ?>
                <div class="p-4 text-muted">Tidak ada produk dengan stok 5 atau kurang.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Produk</th><th>Penjual</th><th class="text-center">Stok</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($stokMenipis as $p): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($p['nama_produk']) ?></td>
                                <td><?= e($p['nama_penjual']) ?></td>
                                <td class="text-center"><span class="badge text-bg-warning"><?= (int) $p['stok'] ?> · Menipis</span></td>
                                <td class="text-end"><a class="btn btn-sm btn-warning" href="<?= BASE_URL ?>/admin/laporan_stok.php?id=<?= (int) $p['id_produk'] ?>">Laporkan Stok</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="p-3 border-bottom"><h2 class="h5 mb-0">Riwayat Laporan</h2></div>
            <?php if (!$riwayat): ?>
                <div class="p-4 text-muted">Belum ada laporan stok.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Produk</th><th>Penjual</th><th>Stok saat laporan</th><th>Pesan</th><th>Status</th><th>Tanggal</th></tr></thead>
                        <tbody>
                        <?php foreach ($riwayat as $r): ?>
                            <?php $badge = ['baru' => 'warning', 'dibaca' => 'info', 'ditindaklanjuti' => 'success'][$r['status']] ?? 'secondary'; ?>
                            <tr>
                                <td><?= e($r['nama_produk'] ?? 'Produk dihapus') ?></td>
                                <td><?= e($r['nama_penjual'] ?? '-') ?></td>
                                <td><?= (int) $r['stok_saat_laporan'] ?> unit</td>
                                <td class="small" style="min-width:260px"><?= e($r['pesan']) ?></td>
                                <td><span class="badge text-bg-<?= $badge ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                                <td class="small text-nowrap"><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <?php
    $laporanSaya = rows(
        $db,
        'SELECT l.*, p.nama_produk, p.stok AS stok_sekarang, u.nama AS nama_admin
         FROM laporan_stok l
         LEFT JOIN produk p ON p.id_produk = l.id_produk
         LEFT JOIN users u ON u.id_user = l.id_admin
         WHERE l.id_penjual = ?
         ORDER BY CASE l.status WHEN \'baru\' THEN 0 WHEN \'dibaca\' THEN 1 ELSE 2 END, l.id_laporan DESC',
        'i',
        [$u['id_user']]
    );
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1">Laporan Stok</h1>
            <p class="text-muted mb-0">Pemberitahuan dari admin mengenai produk yang perlu segera di-restock.</p>
        </div>
    </div>

    <?php if (!$laporanSaya): ?>
        <div class="alert alert-success">✓ Belum ada laporan stok dari admin.</div>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($laporanSaya as $r): ?>
            <?php $badge = ['baru' => 'warning', 'dibaca' => 'info', 'ditindaklanjuti' => 'success'][$r['status']] ?? 'secondary'; ?>
            <div class="col-12">
                <div class="card shadow-sm border-0 <?= $r['status'] === 'baru' ? 'border-start border-warning border-4' : '' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3 flex-wrap">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h2 class="h5 mb-0"><?= e($r['nama_produk'] ?? 'Produk dihapus') ?></h2>
                                    <span class="badge text-bg-<?= $badge ?>"><?= e(ucfirst($r['status'])) ?></span>
                                </div>
                                <div class="small text-muted">Dari <?= e($r['nama_admin'] ?? 'Admin') ?> · <?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></div>
                            </div>
                            <div class="text-end"><div class="small text-muted">Stok saat dilaporkan</div><div class="fs-5 fw-bold text-danger"><?= (int) $r['stok_saat_laporan'] ?> unit</div></div>
                        </div>
                        <div class="alert alert-warning mt-3 mb-3">⚠️ <?= e($r['pesan']) ?></div>
                        <?php if ($r['nama_produk']): ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-sm btn-brand" href="<?= BASE_URL ?>/admin/produk_form.php?id=<?= (int) $r['id_produk'] ?>">Perbarui Stok</a>
                                <?php if ($r['status'] === 'baru'): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?><input type="hidden" name="aksi" value="dibaca"><input type="hidden" name="id_laporan" value="<?= (int) $r['id_laporan'] ?>">
                                        <button class="btn btn-sm btn-outline-secondary">Tandai Dibaca</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($r['status'] !== 'ditindaklanjuti'): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrf_field() ?><input type="hidden" name="aksi" value="tindaklanjuti"><input type="hidden" name="id_laporan" value="<?= (int) $r['id_laporan'] ?>">
                                        <button class="btn btn-sm btn-outline-success">✓ Tandai Ditindaklanjuti</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php if ((int) $r['stok_sekarang'] > 5): ?><div class="small text-success mt-2">Stok sekarang: <?= (int) $r['stok_sekarang'] ?> unit — stok sudah tidak menipis.</div><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
