<?php
/**
 * Aturan diskon berdasarkan TOTAL JUMLAH BARANG dalam satu pesanan.
 * Satu-satunya tempat aturan ini ditulis; keranjang, checkout, dan detail pesanan memakainya.
 *
 *   lebih dari 25 barang -> 30%
 *   lebih dari 15 barang -> 25%
 *   lebih dari  5 barang -> 10%
 *   selainnya            -> 0%
 *
 * Untuk mengubah aturan, cukup ubah daftar TIER_DISKON di bawah (batas => persen).
 */
const TIER_DISKON = [25 => 30, 15 => 25, 5 => 10]; // urut dari batas terbesar

function tarif_diskon(int $jumlahBarang): int
{
    foreach (TIER_DISKON as $batas => $persen) {
        if ($jumlahBarang > $batas) {
            return $persen;
        }
    }
    return 0;
}

/** @return array{subtotal:float, jumlah:int, persen:int, nominal:float, total:float} */
function hitung_diskon(float $subtotal, int $jumlahBarang): array
{
    $persen  = tarif_diskon($jumlahBarang);
    $nominal = round($subtotal * $persen / 100);
    return [
        'subtotal' => $subtotal,
        'jumlah'   => $jumlahBarang,
        'persen'   => $persen,
        'nominal'  => $nominal,
        'total'    => $subtotal - $nominal,
    ];
}

/** Tingkat diskon berikutnya: berapa barang lagi dan diskon yang didapat, atau null bila sudah maksimal. */
function diskon_berikutnya(int $jumlahBarang): ?array
{
    $batas = array_keys(TIER_DISKON);
    sort($batas); // 5, 15, 25
    foreach ($batas as $b) {
        if ($jumlahBarang <= $b) {
            return ['kurang' => $b + 1 - $jumlahBarang, 'persen' => TIER_DISKON[$b]];
        }
    }
    return null;
}

/** HTML ringkasan harga (subtotal, diskon, total) + petunjuk diskon berikutnya. */
function ringkasan_harga_html(array $h): string
{
    $o  = '<div class="d-flex justify-content-between"><span>Subtotal (' . (int) $h['jumlah'] . ' barang)</span><span>' . rupiah($h['subtotal']) . '</span></div>';
    if ($h['persen'] > 0) {
        $o .= '<div class="d-flex justify-content-between diskon-baris"><span>Diskon ' . (int) $h['persen'] . '%</span><span>− ' . rupiah($h['nominal']) . '</span></div>';
    }
    $o .= '<hr class="my-2"><div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span class="text-success">' . rupiah($h['total']) . '</span></div>';

    $next = diskon_berikutnya((int) $h['jumlah']);
    if ($next) {
        $o .= '<div class="promo-hint mt-3">Tambah <strong>' . $next['kurang'] . ' barang lagi</strong> untuk diskon <strong>' . $next['persen'] . '%</strong>.</div>';
    } else {
        $o .= '<div class="promo-hint mt-3">Diskon maksimal tercapai. 🎉</div>';
    }
    return $o;
}
