<?php
/** Kartu produk dipakai di beranda (produk unggulan) dan katalog. Dibungkus <div class="col"> untuk grid Bootstrap. */
function kartu_produk(array $p): string
{
    ob_start(); ?>
    <div class="col">
        <div class="card h-100 produk-card">
            <a href="<?= BASE_URL ?>/produk.php?id=<?= (int) $p['id_produk'] ?>"><?= gambar_html($p, 'card-img-top') ?></a>
            <div class="card-body d-flex flex-column">
                <span class="badge text-bg-light align-self-start mb-1"><?= e($p['nama_kategori'] ?? 'Umum') ?></span>
                <h2 class="h6 card-title"><a href="<?= BASE_URL ?>/produk.php?id=<?= (int) $p['id_produk'] ?>"><?= e($p['nama_produk']) ?></a></h2>
                <div class="fw-bold text-success"><?= rupiah($p['harga']) ?></div>
                <small class="text-muted mb-2"><?= e($p['nama_penjual']) ?> · Stok <?= (int) $p['stok'] ?></small>
                <form method="post" action="<?= BASE_URL ?>/keranjang_tambah.php" class="mt-auto">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_produk" value="<?= (int) $p['id_produk'] ?>">
                    <input type="hidden" name="jumlah" value="1">
                    <button class="btn btn-sm btn-brand w-100" <?= $p['stok'] < 1 ? 'disabled' : '' ?>>
                        <?= $p['stok'] < 1 ? 'Stok habis' : '+ Keranjang' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
