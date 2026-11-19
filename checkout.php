<?php
require __DIR__ . '/includes/init.php';
$u = require_login();

$items = rows(
    $db,
    'SELECT k.id_keranjang, k.jumlah, p.id_produk, p.nama_produk, p.harga, p.stok, p.gambar
       FROM keranjang k JOIN produk p ON p.id_produk = k.id_produk
      WHERE k.id_user = ? ORDER BY k.id_keranjang',
    'i',
    [$u['id_user']]
);
if (!$items) {
    flash('warning', 'Keranjang kosong, tidak ada yang dapat di-checkout.');
    redirect('keranjang.php');
}

$profil = row($db, 'SELECT nama, alamat, no_hp FROM users WHERE id_user = ?', 'i', [$u['id_user']]);
$in = [
    'nama'    => $profil['nama'] ?? '',
    'alamat'  => $profil['alamat'] ?? '',
    'no_hp'   => $profil['no_hp'] ?? '',
    'catatan' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach (array_keys($in) as $k) {
        $in[$k] = trim($_POST[$k] ?? '');
    }
    if ($in['nama'] === '' || mb_strlen($in['nama']) > 100) {
        $errors[] = 'Nama penerima wajib diisi (maks. 100 karakter).';
    }
    if ($in['alamat'] === '') {
        $errors[] = 'Alamat pengiriman wajib diisi.';
    }
    if (!preg_match('/^[0-9+\-\s]{8,20}$/', $in['no_hp'])) {
        $errors[] = 'Nomor HP tidak valid (8–20 digit).';
    }
    if (mb_strlen($in['catatan']) > 255) {
        $errors[] = 'Catatan maksimal 255 karakter.';
    }

    if (!$errors) {
        // ---- Transaksi database: semua langkah berhasil, atau tidak sama sekali ----
        $db->begin_transaction();
        try {
            $subtotal = 0;
            $jmlBarang = 0;
            $baris = [];
            foreach ($items as $it) {
                // Kunci baris produk agar stok tidak diperebutkan pembeli lain
                $p = row($db, 'SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE', 'i', [$it['id_produk']]);
                if (!$p || (int) $p['stok'] < (int) $it['jumlah']) {
                    throw new RuntimeException('Stok "' . $it['nama_produk'] . '" tidak mencukupi. Silakan sesuaikan jumlah di keranjang.');
                }
                $sub = $p['harga'] * $it['jumlah'];
                $subtotal += $sub;
                $jmlBarang += (int) $it['jumlah'];
                $baris[] = [$p, (int) $it['jumlah'], $sub];
            }

            // Diskon dihitung ulang di server dari data database, bukan dari input pengguna
            $h = hitung_diskon((float) $subtotal, $jmlBarang);

            $kode = 'INV' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            query(
                $db,
                'INSERT INTO pesanan (kode_pesanan, id_user, subtotal, diskon_persen, diskon_nominal, total, status, nama_penerima, alamat_kirim, no_hp, catatan)
                 VALUES (?, ?, ?, ?, ?, ?, \'menunggu\', ?, ?, ?, ?)',
                'sididdssss',
                [$kode, $u['id_user'], $h['subtotal'], $h['persen'], $h['nominal'], $h['total'], $in['nama'], $in['alamat'], $in['no_hp'], $in['catatan']]
            )->close();
            $idPesanan = $db->insert_id;

            foreach ($baris as [$p, $jml, $sub]) {
                query(
                    $db,
                    'INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga, jumlah, subtotal) VALUES (?, ?, ?, ?, ?, ?)',
                    'iisdid',
                    [$idPesanan, $p['id_produk'], $p['nama_produk'], $p['harga'], $jml, $sub]
                )->close();
                query($db, 'UPDATE produk SET stok = stok - ? WHERE id_produk = ?', 'ii', [$jml, $p['id_produk']])->close();
            }

            query($db, 'DELETE FROM keranjang WHERE id_user = ?', 'i', [$u['id_user']])->close();
            $db->commit();

            flash('success', 'Pesanan berhasil dibuat. Terima kasih telah berbelanja!');
            redirect('pesanan_detail.php?kode=' . urlencode($kode));
        } catch (RuntimeException $ex) {
            $db->rollback();
            $errors[] = $ex->getMessage();
        } catch (Throwable $ex) {
            $db->rollback();
            error_log('Checkout gagal: ' . $ex->getMessage());
            $errors[] = 'Terjadi kesalahan saat menyimpan pesanan. Silakan coba lagi.';
        }
    }
}

$subtotalTampil = 0;
$jmlTampil = 0;
foreach ($items as $it) {
    $subtotalTampil += $it['harga'] * $it['jumlah'];
    $jmlTampil += (int) $it['jumlah'];
}
$hTampil = hitung_diskon($subtotalTampil, $jmlTampil);
$judul = 'Checkout';
include __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Checkout</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <form method="post" class="card shadow-sm border-0 card-body">
            <?= csrf_field() ?>
            <h2 class="h5 mb-3">Data Penerima</h2>
            <div class="mb-3">
                <label class="form-label" for="nama">Nama penerima</label>
                <input class="form-control" id="nama" name="nama" value="<?= e($in['nama']) ?>" required maxlength="100">
            </div>
            <div class="mb-3">
                <label class="form-label" for="no_hp">Nomor HP</label>
                <input class="form-control" id="no_hp" name="no_hp" value="<?= e($in['no_hp']) ?>" required maxlength="20">
            </div>
            <div class="mb-3">
                <label class="form-label" for="alamat">Alamat pengiriman</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= e($in['alamat']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="catatan">Catatan (opsional)</label>
                <input class="form-control" id="catatan" name="catatan" value="<?= e($in['catatan']) ?>" maxlength="255">
            </div>
            <button class="btn btn-brand btn-lg">Buat Pesanan</button>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h2 class="h5">Ringkasan Pesanan</h2>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($items as $it): ?>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><?= e($it['nama_produk']) ?> <small class="text-muted">× <?= (int) $it['jumlah'] ?></small></span>
                            <span><?= rupiah($it['harga'] * $it['jumlah']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?= ringkasan_harga_html($hTampil) ?>
                <p class="small text-muted mt-2 mb-0">Pembayaran dilakukan setelah pesanan dikonfirmasi penjual.</p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
