<?php

require __DIR__ . '/../includes/init.php';

$u = require_role(['admin', 'penjual']);


/*
|--------------------------------------------------------------------------
| STATUS PESANAN
|--------------------------------------------------------------------------
| Status ini digunakan oleh PENJUAL.
|
| Admin hanya melihat perkembangan pesanan.
*/

$statusValid = [
    'menunggu',
    'dibayar',
    'diproses',
    'disiapkan',
    'dikirim',
    'dalam_pengantaran',
    'selesai',
    'batal'
];


/*
|--------------------------------------------------------------------------
| PENJUAL MENGUBAH STATUS PESANAN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_verify();


    /*
    | Admin tidak boleh mengubah status.
    */

    if ($u['role'] !== 'penjual') {

        http_response_code(403);

        exit(
            'Admin hanya dapat memantau pesanan. ' .
            'Status pesanan diubah oleh penjual.'
        );
    }


    $idPesanan = (int) ($_POST['id_pesanan'] ?? 0);

    $status = $_POST['status'] ?? '';


    /*
    | Pastikan pesanan memiliki produk
    | milik penjual yang sedang login.
    */

    $punyaPesanan = row(
        $db,

        'SELECT 1 AS x
         FROM detail_pesanan d

         JOIN produk p
           ON p.id_produk = d.id_produk

         WHERE d.id_pesanan = ?
           AND p.id_penjual = ?

         LIMIT 1',

        'ii',

        [
            $idPesanan,
            $u['id_user']
        ]
    );


    if (!$punyaPesanan) {

        flash(
            'danger',
            'Pesanan tidak ditemukan atau bukan pesanan produk Anda.'
        );

    } elseif (!in_array($status, $statusValid, true)) {

        flash(
            'danger',
            'Status pesanan tidak valid.'
        );

    } else {

        query(
            $db,

            'UPDATE pesanan
             SET status = ?
             WHERE id_pesanan = ?',

            'si',

            [
                $status,
                $idPesanan
            ]

        )->close();


        flash(
            'success',
            'Status pesanan berhasil diperbarui.'
        );
    }


    redirect('admin/pesanan.php');
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PESANAN
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT
        ps.id_pesanan,
        ps.kode_pesanan,
        ps.tanggal,
        ps.total,
        ps.status,
        us.nama AS pembeli

    FROM pesanan ps

    JOIN users us
      ON us.id_user = ps.id_user
';


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
| Admin melihat semua pesanan.
*/

if ($u['role'] === 'admin') {

    $daftar = rows(
        $db,

        $sql . '
        ORDER BY ps.id_pesanan DESC'
    );


    $judul = 'Monitoring Pesanan';

}


/*
|--------------------------------------------------------------------------
| PENJUAL
|--------------------------------------------------------------------------
| Penjual hanya melihat pesanan yang
| mempunyai produk miliknya.
*/

else {

    $daftar = rows(
        $db,

        $sql . '
        WHERE EXISTS (

            SELECT 1

            FROM detail_pesanan d

            JOIN produk p
              ON p.id_produk = d.id_produk

            WHERE d.id_pesanan = ps.id_pesanan

              AND p.id_penjual = ?

        )

        ORDER BY ps.id_pesanan DESC',

        'i',

        [
            $u['id_user']
        ]
    );


    $judul = 'Pesanan Masuk';
}


include __DIR__ . '/../includes/header.php';

?>


<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h1 class="h3 mb-1">
            <?= e($judul) ?>
        </h1>


        <?php if ($u['role'] === 'penjual'): ?>

            <p class="text-muted mb-0">
                Kelola pesanan, siapkan barang,
                lalu perbarui status pengiriman.
            </p>

        <?php else: ?>

            <p class="text-muted mb-0">
                Admin hanya memantau seluruh perkembangan pesanan.
            </p>

        <?php endif; ?>

    </div>

</div>


<?php if (!$daftar): ?>

    <div class="alert alert-secondary">
        Belum ada pesanan.
    </div>

<?php else: ?>


<div class="table-responsive bg-white rounded shadow-sm">

<table class="table align-middle mb-0">

<thead class="table-light">

<tr>

    <th>Kode</th>

    <th>Pembeli</th>

    <th>Tanggal</th>

    <th class="text-end">
        Total
    </th>

    <th>
        Status
    </th>

    <th class="text-end">
        Aksi
    </th>

</tr>

</thead>


<tbody>


<?php foreach ($daftar as $d): ?>

<tr>

    <td class="fw-semibold">
        <?= e($d['kode_pesanan']) ?>
    </td>


    <td>
        <?= e($d['pembeli']) ?>
    </td>


    <td>
        <?= e(
            date(
                'd M Y H:i',
                strtotime($d['tanggal'])
            )
        ) ?>
    </td>


    <td class="text-end">
        <?= rupiah($d['total']) ?>
    </td>


    <td>


        <?php if ($u['role'] === 'penjual'): ?>


            <form
                method="post"
                class="d-flex gap-1"
            >

                <?= csrf_field() ?>


                <input
                    type="hidden"
                    name="id_pesanan"
                    value="<?= (int) $d['id_pesanan'] ?>"
                >


                <select
                    name="status"
                    class="form-select form-select-sm"
                >

                    <?php foreach ($statusValid as $s): ?>

                        <option
                            value="<?= e($s) ?>"
                            <?= $d['status'] === $s ? 'selected' : '' ?>
                        >

                            <?= e(
                                ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $s
                                    )
                                )
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="btn btn-sm btn-outline-primary"
                >
                    Simpan
                </button>

            </form>


        <?php else: ?>


            <?= status_badge($d['status']) ?>


        <?php endif; ?>


    </td>


    <td class="text-end">

        <a
            class="btn btn-sm btn-outline-secondary"
            href="<?= BASE_URL ?>/pesanan_detail.php?kode=<?= urlencode($d['kode_pesanan']) ?>"
        >
            Detail
        </a>

    </td>

</tr>

<?php endforeach; ?>


</tbody>

</table>

</div>


<?php endif; ?>


<?php include __DIR__ . '/../includes/footer.php'; ?>