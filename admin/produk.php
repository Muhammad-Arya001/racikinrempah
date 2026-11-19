<?php

require __DIR__ . '/../includes/init.php';

$u = require_role(['admin', 'penjual']);


/*
|--------------------------------------------------------------------------
| DATA PRODUK
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        p.*,
        k.nama_kategori,
        us.nama AS nama_penjual

    FROM produk p

    LEFT JOIN kategori k
      ON k.id_kategori = p.id_kategori

    JOIN users us
      ON us.id_user = p.id_penjual
';


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
| Admin hanya melihat data produk.
*/

if ($u['role'] === 'admin') {

    $daftar = rows(
        $db,

        $sql . '
        ORDER BY p.id_produk DESC'
    );


    /*
    | Produk dengan stok <= 5
    */

    $stokMenipis = rows(
        $db,

        $sql . '
        WHERE p.stok <= 5

        ORDER BY
            p.stok ASC,
            p.nama_produk ASC'
    );


    $judul = 'Data Produk';

}


/*
|--------------------------------------------------------------------------
| PENJUAL
|--------------------------------------------------------------------------
| Penjual hanya melihat produk miliknya.
*/

else {

    $daftar = rows(
        $db,

        $sql . '
        WHERE p.id_penjual = ?

        ORDER BY p.id_produk DESC',

        'i',

        [
            $u['id_user']
        ]
    );


    $judul = 'Produk Saya';
}


include __DIR__ . '/../includes/header.php';

?>


<div class="d-flex justify-content-between align-items-center mb-3">

    <h1 class="h3 mb-0">
        <?= e($judul) ?>
    </h1>


    <?php if ($u['role'] === 'penjual'): ?>

        <a
            href="<?= BASE_URL ?>/admin/produk_form.php"
            class="btn btn-brand"
        >
            + Tambah Produk
        </a>

    <?php endif; ?>

</div>



<?php if ($u['role'] === 'admin' && $stokMenipis): ?>

<div class="alert alert-warning">

    <div class="fw-semibold mb-1">
        ⚠️ Stok Produk Menipis
    </div>

    <div class="small">

        Ada
        <?= count($stokMenipis) ?>
        produk dengan stok 5 atau kurang.

        Admin dapat membuka menu <strong>Laporan Stok</strong> untuk mengirim pemberitahuan langsung kepada penjual.

    </div>

</div>

<?php endif; ?>



<?php if (!$daftar): ?>

<div class="alert alert-secondary">
    Belum ada produk.
</div>


<?php else: ?>


<div class="table-responsive bg-white rounded shadow-sm">

<table class="table align-middle mb-0">

<thead class="table-light">

<tr>

    <th>
        Produk
    </th>

    <th>
        Kategori
    </th>


    <?php if ($u['role'] === 'admin'): ?>

        <th>
            Penjual
        </th>

    <?php endif; ?>


    <th class="text-end">
        Harga
    </th>

    <th class="text-center">
        Stok
    </th>

    <th class="text-end">
        Aksi
    </th>

</tr>

</thead>


<tbody>


<?php foreach ($daftar as $p): ?>

<tr>


<td>

<div class="d-flex align-items-center gap-2">

    <?= gambar_html($p, 'img-thumb') ?>

    <span>
        <?= e($p['nama_produk']) ?>
    </span>

</div>

</td>


<td>

<?= e(
    $p['nama_kategori'] ?? '-'
) ?>

</td>



<?php if ($u['role'] === 'admin'): ?>

<td>

<?= e(
    $p['nama_penjual']
) ?>

</td>

<?php endif; ?>



<td class="text-end">

<?= rupiah(
    $p['harga']
) ?>

</td>



<td class="text-center">

<?php if ((int) $p['stok'] <= 5): ?>

<span class="badge text-bg-warning">

    <?= (int) $p['stok'] ?>

    · Menipis

</span>

<?php else: ?>

<?= (int) $p['stok'] ?>

<?php endif; ?>

</td>



<td class="text-end text-nowrap">


<a
    class="btn btn-sm btn-outline-secondary"
    href="<?= BASE_URL ?>/produk.php?id=<?= (int) $p['id_produk'] ?>"
>
    Lihat
</a>



<?php if ($u['role'] === 'admin' && (int) $p['stok'] <= 5): ?>

<a
    class="btn btn-sm btn-warning"
    href="<?= BASE_URL ?>/admin/laporan_stok.php?id=<?= (int) $p['id_produk'] ?>"
>
    Laporkan Stok
</a>

<?php endif; ?>


<?php if ($u['role'] === 'penjual'): ?>


<a
    class="btn btn-sm btn-outline-primary"
    href="<?= BASE_URL ?>/admin/produk_form.php?id=<?= (int) $p['id_produk'] ?>"
>
    Edit
</a>


<form
    method="post"
    action="<?= BASE_URL ?>/admin/produk_hapus.php"
    class="d-inline"
    onsubmit="return confirm('Hapus produk ini?');"
>

    <?= csrf_field() ?>

    <input
        type="hidden"
        name="id"
        value="<?= (int) $p['id_produk'] ?>"
    >

    <button
        type="submit"
        class="btn btn-sm btn-outline-danger"
    >
        Hapus
    </button>

</form>


<?php endif; ?>


</td>

</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>


<?php endif; ?>


<?php include __DIR__ . '/../includes/footer.php'; ?>