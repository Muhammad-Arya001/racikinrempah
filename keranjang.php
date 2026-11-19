<?php
require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/diskon.php';

$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'update') {
        foreach (($_POST['jumlah'] ?? []) as $idKeranjang => $jml) {
            $idKeranjang = (int) $idKeranjang;
            $jml = (int) $jml;

            $item = row(
                $db,
                'SELECT 
                    k.id_keranjang,
                    p.stok
                 FROM keranjang k
                 JOIN produk p ON p.id_produk = k.id_produk
                 WHERE k.id_keranjang = ?
                   AND k.id_user = ?',
                'ii',
                [$idKeranjang, $u['id_user']]
            );

            if (!$item) {
                continue;
            }

            // Jika jumlah 0, hapus dari keranjang
            if ($jml < 1) {
                query(
                    $db,
                    'DELETE FROM keranjang
                     WHERE id_keranjang = ?
                       AND id_user = ?',
                    'ii',
                    [$idKeranjang, $u['id_user']]
                )->close();

                continue;
            }

            // Jumlah tidak boleh melebihi stok
            $stok = (int) $item['stok'];
            $jml = min($jml, $stok);

            query(
                $db,
                'UPDATE keranjang
                 SET jumlah = ?
                 WHERE id_keranjang = ?
                   AND id_user = ?',
                'iii',
                [$jml, $idKeranjang, $u['id_user']]
            )->close();
        }

        flash('success', 'Keranjang berhasil diperbarui.');

    } elseif ($aksi === 'hapus') {

        $idKeranjang = (int) ($_POST['id_keranjang'] ?? 0);

        query(
            $db,
            'DELETE FROM keranjang
             WHERE id_keranjang = ?
               AND id_user = ?',
            'ii',
            [$idKeranjang, $u['id_user']]
        )->close();

        flash('success', 'Item berhasil dihapus dari keranjang.');
    }

    redirect('keranjang.php');
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA KERANJANG
|--------------------------------------------------------------------------
*/

$items = rows(
    $db,
    'SELECT
        k.id_keranjang,
        k.jumlah,
        p.id_produk,
        p.nama_produk,
        p.harga,
        p.stok,
        p.gambar
     FROM keranjang k
     JOIN produk p ON p.id_produk = k.id_produk
     WHERE k.id_user = ?
     ORDER BY k.id_keranjang',
    'i',
    [$u['id_user']]
);


/*
|--------------------------------------------------------------------------
| HITUNG SUBTOTAL DAN JUMLAH BARANG
|--------------------------------------------------------------------------
*/

$subtotal = 0;
$jmlBarang = 0;

foreach ($items as $it) {
    $harga = (float) $it['harga'];
    $jumlah = (int) $it['jumlah'];

    $subtotal += $harga * $jumlah;
    $jmlBarang += $jumlah;
}


/*
|--------------------------------------------------------------------------
| HITUNG DISKON
|--------------------------------------------------------------------------
|
| Fungsi hitung_diskon() berasal dari:
| includes/diskon.php
|
*/

$h = hitung_diskon(
    (float) $subtotal,
    (int) $jmlBarang
);


$judul = 'Keranjang Belanja';

include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-3">Keranjang Belanja</h1>

<?php if (!$items): ?>

    <div class="alert alert-secondary">
        Keranjang Anda masih kosong.
        <a href="<?= BASE_URL ?>/katalog.php">
            Mulai belanja
        </a>
    </div>

<?php else: ?>

    <div class="row g-4">

        <!-- =========================================================
             DAFTAR PRODUK
        ========================================================== -->
        <div class="col-lg-8">

            <form method="post" id="form-update">

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="aksi"
                    value="update"
                >

                <div class="table-responsive bg-white rounded shadow-sm">

                    <table class="table align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Harga</th>
                                <th style="width: 110px;">
                                    Jumlah
                                </th>
                                <th class="text-end">
                                    Subtotal
                                </th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($items as $it): ?>

                            <?php
                            $idKeranjang = (int) $it['id_keranjang'];
                            $idProduk = (int) $it['id_produk'];
                            $jumlah = (int) $it['jumlah'];
                            $stok = (int) $it['stok'];
                            $harga = (float) $it['harga'];

                            $subtotalProduk = $harga * $jumlah;
                            ?>

                            <tr>

                                <!-- PRODUK -->
                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <?= gambar_html($it, 'img-thumb') ?>

                                        <a
                                            class="text-decoration-none"
                                            href="<?= BASE_URL ?>/produk.php?id=<?= $idProduk ?>"
                                        >
                                            <?= e($it['nama_produk']) ?>
                                        </a>

                                    </div>

                                </td>


                                <!-- HARGA -->
                                <td class="text-end">
                                    <?= rupiah($harga) ?>
                                </td>


                                <!-- JUMLAH -->
                                <td>

                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
                                        name="jumlah[<?= $idKeranjang ?>]"
                                        value="<?= $jumlah ?>"
                                        min="0"
                                        max="<?= $stok ?>"
                                        aria-label="Jumlah <?= e($it['nama_produk']) ?>"
                                    >

                                    <small class="text-muted">
                                        Stok: <?= $stok ?>
                                    </small>

                                </td>


                                <!-- SUBTOTAL -->
                                <td class="text-end">

                                    <?= rupiah($subtotalProduk) ?>

                                </td>


                                <!-- HAPUS -->
                                <td class="text-end">

                                    <button
                                        type="submit"
                                        form="hapus-<?= $idKeranjang ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        aria-label="Hapus <?= e($it['nama_produk']) ?>"
                                        title="Hapus produk"
                                    >
                                        ✕
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- TOMBOL UPDATE -->

                <button
                    type="submit"
                    class="btn btn-outline-secondary mt-3"
                >
                    Perbarui Jumlah
                </button>


                <!-- LANJUT BELANJA -->

                <a
                    href="<?= BASE_URL ?>/katalog.php"
                    class="btn btn-link mt-3"
                >
                    ← Lanjut belanja
                </a>

            </form>


            <!-- =====================================================
                 FORM HAPUS PRODUK
            ====================================================== -->

            <?php foreach ($items as $it): ?>

                <?php
                $idKeranjang = (int) $it['id_keranjang'];
                ?>

                <form
                    method="post"
                    id="hapus-<?= $idKeranjang ?>"
                    class="d-none"
                >

                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="aksi"
                        value="hapus"
                    >

                    <input
                        type="hidden"
                        name="id_keranjang"
                        value="<?= $idKeranjang ?>"
                    >

                </form>

            <?php endforeach; ?>

        </div>


        <!-- =========================================================
             RINGKASAN PESANAN
        ========================================================== -->

        <div class="col-lg-4">

            <div class="card shadow-sm border-0">

                <div class="card-body">

                    <h2 class="h5">
                        Ringkasan
                    </h2>


                    <div class="my-3">

                        <?= ringkasan_harga_html($h) ?>

                    </div>


                    <!-- CHECKOUT -->

                    <a
                        href="<?= BASE_URL ?>/checkout.php"
                        class="btn btn-brand w-100 btn-lg"
                    >
                        Checkout
                    </a>

                </div>

            </div>

        </div>

    </div>

<?php endif; ?>


<?php
include __DIR__ . '/includes/footer.php';
?>