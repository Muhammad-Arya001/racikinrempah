<?php

require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/kartu_produk.php';

$judul = 'Beranda';


/**
 * Pilih gaya kartu kategori
 * berdasarkan nama kategori.
 */
function tema_kategori(string $nama): array
{
    $n = mb_strtolower($nama);

    if (strpos($n, 'minuman') !== false) {
        return ['minuman', ''];
    }

    if (strpos($n, 'makanan') !== false) {
        return ['makanan', ''];
    }

    if (strpos($n, 'bumbu') !== false) {
        return ['bumbu', ''];
    }

    return ['rempah', ''];
}


/**
 * Ambil kategori
 */
$kategori = rows(
    $db,
    'SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY id_kategori'
);


/**
 * Produk unggulan
 */
$unggulan = rows(
    $db,
    'SELECT
        p.*,
        k.nama_kategori,
        u.nama AS nama_penjual,
        COALESCE(t.terjual, 0) AS terjual
     FROM produk p
     LEFT JOIN kategori k
        ON k.id_kategori = p.id_kategori
     JOIN users u
        ON u.id_user = p.id_penjual
     LEFT JOIN (
        SELECT
            id_produk,
            SUM(jumlah) AS terjual
        FROM detail_pesanan
        GROUP BY id_produk
     ) t
        ON t.id_produk = p.id_produk
     WHERE p.stok > 0
     ORDER BY terjual DESC, p.id_produk ASC
     LIMIT 8'
);


/**
 * =====================================================
 * FOTO KATEGORI
 * =====================================================
 */
$foto_kategori = [
    'minuman' => 'minumanrempah.jpeg',
    'makanan' => 'makanankayarempah.jpeg',
    'bumbu'   => 'bumburacik.jpeg',
    'rempah'  => 'rempahutuhdanbubuk.jpeg',
];


include __DIR__ . '/includes/header.php';

?>


<!-- =====================================================
     HERO
     ===================================================== -->

<section
    class="hero-foto mb-5"
    style="--hero-foto:url('<?= BASE_URL ?>/assets/img/kitorempah.jpeg')"
>

    <div class="hero-isi">

        <span class="hero-eyebrow">
            RacikInRempah
        </span>

        <h1 class="hero-judul">
            Racik Rasa,<br>
            Hangatkan Hidup.
        </h1>

        <p class="hero-sub">
            Minuman rempah, makanan kaya rempah, dan bumbu pilihan
            langsung dari kebun dan dapur Nusantara.
        </p>

        <a
            class="btn btn-kunyit btn-lg"
            href="<?= BASE_URL ?>/katalog.php"
        >
            Jelajahi Produk
            <span aria-hidden="true">→</span>
        </a>

    </div>

</section>


<!-- =====================================================
     KATEGORI PRODUK
     ===================================================== -->

<section class="mb-5">

    <div class="d-flex justify-content-between align-items-end mb-3">

        <div>

            <div class="eyebrow">
                Pilih sesuai selera
            </div>

            <h2 class="judul-seksi h3 mb-0">
                Kategori Produk
            </h2>

        </div>


        <a
            class="fw-semibold text-decoration-none"
            href="<?= BASE_URL ?>/katalog.php"
        >
            Lihat produk →
        </a>

    </div>


    <!-- KARTU KATEGORI -->

    <div class="row g-3">

        <?php foreach ($kategori as $k): ?>

            <?php

            [$slug, $ikon] = tema_kategori($k['nama_kategori']);

            // Ambil nama file foto sesuai kategori
            $foto = $foto_kategori[$slug] ?? 'rempahutuhdanbubuk.jpeg';

            ?>

            <div class="col-6 col-lg-3">

                <!--
                    PENTING:
                    class kat-card, kat-ikon, kat-nama
                    dikembalikan ke class CSS asli project
                -->

                <a
                    class="kat-card kat-<?= $slug ?>"
                    href="<?= BASE_URL ?>/katalog.php?kategori=<?= (int) $k['id_kategori'] ?>"
                    style="--foto:url('<?= BASE_URL ?>/assets/img/<?= e($foto) ?>')"
                >

                    <!-- ICON -->
                    <span
                        class="kat-ikon"
                        aria-hidden="true"
                    >
                        <?= $ikon ?>
                    </span>


                    <!-- NAMA KATEGORI -->
                    <span class="kat-nama">
                        <?= e($k['nama_kategori']) ?>
                    </span>

                </a>

            </div>

        <?php endforeach; ?>

    </div>

</section>


<!-- =====================================================
     PRODUK UNGGULAN
     ===================================================== -->

<section class="mb-5">

    <div class="d-flex justify-content-between align-items-end mb-3">

        <div>

            <div class="eyebrow">
                Temukan racikan favoritmu
            </div>

            <h2 class="judul-seksi h3 mb-0">
                Produk Unggulan
            </h2>

        </div>


        <a
            class="fw-semibold text-decoration-none"
            href="<?= BASE_URL ?>/katalog.php"
        >
            Semua produk →
        </a>

    </div>


    <?php if (!$unggulan): ?>

        <div class="alert alert-secondary">
            Belum ada produk tersedia.
        </div>

    <?php else: ?>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">

            <?php foreach ($unggulan as $p): ?>

                <?= kartu_produk($p) ?>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<!-- =====================================================
     DISKON
     ===================================================== -->

<section
    class="promo-panel mb-5"
    aria-label="Diskon belanja banyak"
>

    <div class="row align-items-center g-3">

        <div class="col-lg-4">

            <div class="eyebrow eyebrow-terang">
                Makin banyak, makin hemat
            </div>

            <h2 class="h3 fw-bold mb-1">
                Diskon Belanja Banyak
            </h2>

            <p class="mb-0 opacity-75">
                Otomatis terpotong saat checkout, tanpa kode.
            </p>

        </div>


        <div class="col-lg-8">

            <div class="row g-2 text-center">


                <div class="col-4">

                    <div class="promo-tier">

                        <span class="persen">
                            10%
                        </span>

                        <small>
                            lebih dari 5 barang
                        </small>

                    </div>

                </div>


                <div class="col-4">

                    <div class="promo-tier">

                        <span class="persen">
                            25%
                        </span>

                        <small>
                            lebih dari 15 barang
                        </small>

                    </div>

                </div>


                <div class="col-4">

                    <div class="promo-tier">

                        <span class="persen">
                            30%
                        </span>

                        <small>
                            lebih dari 25 barang
                        </small>

                    </div>

                </div>


            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     NILAI / KEUNGGULAN
     ===================================================== -->

<section class="mb-2">

    <div class="row g-3 text-center">


        <div class="col-md-4">

            <div class="nilai-card">

                <div class="nilai-ikon">
                    
                </div>

                <h3 class="h6 fw-bold">
                    Alami dari Nusantara
                </h3>

                <p class="mb-0 small text-muted">
                    Rempah dan bahan pilihan,
                    tanpa pengawet berlebih.
                </p>

            </div>

        </div>


        <div class="col-md-4">

            <div class="nilai-card">

                <div class="nilai-ikon">
                    
                </div>

                <h3 class="h6 fw-bold">
                    Langsung dari Penjual Lokal
                </h3>

                <p class="mb-0 small text-muted">
                    Mendukung petani dan pelaku usaha
                    rempah sekitar.
                </p>

            </div>

        </div>


        <div class="col-md-4">

            <div class="nilai-card">

                <div class="nilai-ikon">
                    
                </div>

                <h3 class="h6 fw-bold">
                    Siap Diracik
                </h3>

                <p class="mb-0 small text-muted">
                    Dari wedang hangat sampai bumbu
                    masakan, tinggal seduh dan masak.
                </p>

            </div>

        </div>


    </div>

</section>


<?php include __DIR__ . '/includes/footer.php'; ?>