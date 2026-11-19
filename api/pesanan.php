<?php
// POST /api/pesanan.php          -> membuat pesanan baru (body JSON)
// GET  /api/pesanan.php?kode=INV... -> melihat pesanan beserta item-nya
require __DIR__ . '/lib/helper.php';

hanya_metode(['GET', 'POST']);
$conn = koneksi_db();

function format_pesanan(array $p, array $items)
{
    return [
        'kode_pesanan'  => $p['kode_pesanan'],
        'id_pesanan'    => (int)$p['id_pesanan'],
        'id_user'       => (int)$p['id_user'],
        'nama_penerima' => $p['nama_penerima'],
        'alamat_kirim'  => $p['alamat_kirim'],
        'no_hp'         => $p['no_hp'],
        'catatan'       => $p['catatan'],
        'subtotal'      => (float)$p['subtotal'],
        'diskon_persen' => (int)$p['diskon_persen'],
        'diskon_nominal'=> (float)$p['diskon_nominal'],
        'total'         => (float)$p['total'],
        'status'        => $p['status'],
        'tanggal'       => $p['tanggal'],
        'items'         => $items,
    ];
}

// ---------- GET: baca pesanan berdasarkan kode ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $kode = trim($_GET['kode'] ?? '');
    if ($kode === '' || !preg_match('/^[A-Za-z0-9]{5,20}$/', $kode)) {
        gagal(400, 'Parameter kode pesanan wajib diisi (contoh: ?kode=INV261119ABC123)');
    }

    $stmt = $conn->prepare("SELECT id_pesanan, kode_pesanan, id_user, subtotal, diskon_persen, diskon_nominal,
                                   total, status, nama_penerima, alamat_kirim, no_hp, catatan, tanggal
                            FROM pesanan WHERE kode_pesanan = ?");
    $stmt->bind_param('s', $kode);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    if (!$p) {
        gagal(404, "Pesanan dengan kode $kode tidak ditemukan");
    }

    $stmt = $conn->prepare("SELECT id_produk, nama_produk, harga, jumlah, subtotal
                            FROM detail_pesanan WHERE id_pesanan = ? ORDER BY id_detail");
    $stmt->bind_param('i', $p['id_pesanan']);
    $stmt->execute();
    $items = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $items[] = [
            'id_produk'    => $r['id_produk'] === null ? null : (int)$r['id_produk'],
            'nama_produk'  => $r['nama_produk'],
            'harga_satuan' => (float)$r['harga'],
            'jumlah'       => (int)$r['jumlah'],
            'subtotal'     => (float)$r['subtotal'],
        ];
    }
    sukses(format_pesanan($p, $items), 'Detail pesanan ditemukan');
}

// ---------- POST: buat pesanan ----------
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;           // dukung juga form-data / x-www-form-urlencoded
}
if (!$input) {
    gagal(400, 'Body request kosong atau bukan JSON yang valid');
}

$errors  = [];
$idUser  = $input['id_user'] ?? null;
$nama    = trim($input['nama_penerima'] ?? '');
$alamat  = trim($input['alamat_kirim'] ?? '');
$noHp    = trim($input['no_hp'] ?? '');
$catatan = trim($input['catatan'] ?? '');
$items   = $input['items'] ?? null;

if (!is_numeric($idUser) || (int)$idUser < 1) $errors['id_user'] = 'Wajib diisi (id pembeli, angka positif)';
if ($nama === '' || mb_strlen($nama) > 100)   $errors['nama_penerima'] = 'Wajib diisi, maksimal 100 karakter';
if ($alamat === '')                           $errors['alamat_kirim'] = 'Wajib diisi';
if (!preg_match('/^[0-9+\-\s]{8,20}$/', $noHp)) $errors['no_hp'] = 'Tidak valid (8-20 digit)';
if (mb_strlen($catatan) > 255)                $errors['catatan'] = 'Maksimal 255 karakter';
if (!is_array($items) || count($items) === 0) {
    $errors['items'] = 'Minimal satu item: [{"id_produk":1,"jumlah":2}]';
} else {
    foreach ($items as $i => $it) {
        $idp = $it['id_produk'] ?? null;
        $jml = $it['jumlah'] ?? null;
        if (!is_numeric($idp) || (int)$idp < 1 || !is_numeric($jml) || (int)$jml < 1) {
            $errors["items[$i]"] = 'id_produk dan jumlah harus angka positif';
        }
    }
}
if ($errors) {
    gagal(422, 'Data pesanan tidak valid', $errors);
}
$idUser = (int)$idUser;

$conn->begin_transaction();
try {
    // Pembeli harus ada dan ber-role 'pembeli'
    $stmt = $conn->prepare("SELECT id_user FROM users WHERE id_user = ? AND role = 'pembeli'");
    $stmt->bind_param('i', $idUser);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new RuntimeException("404|Pembeli dengan id_user $idUser tidak ditemukan");
    }

    // Gabungkan item dengan id_produk yang sama
    $gabung = [];
    foreach ($items as $it) {
        $idp = (int)$it['id_produk'];
        $gabung[$idp] = ($gabung[$idp] ?? 0) + (int)$it['jumlah'];
    }

    $subtotal = 0;
    $jmlBarang = 0;
    $baris = [];
    $cek = $conn->prepare("SELECT nama_produk, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE");
    foreach ($gabung as $idp => $jml) {
        $cek->bind_param('i', $idp);
        $cek->execute();
        $pr = $cek->get_result()->fetch_assoc();
        if (!$pr) {
            throw new RuntimeException("404|Produk dengan id $idp tidak ditemukan");
        }
        if ((int)$pr['stok'] < $jml) {
            throw new RuntimeException("409|Stok produk '{$pr['nama_produk']}' tidak cukup (tersisa {$pr['stok']})");
        }
        $sub = $pr['harga'] * $jml;
        $subtotal += $sub;
        $jmlBarang += $jml;
        $baris[] = [$idp, $pr['nama_produk'], (float)$pr['harga'], $jml, $sub];
    }

    $h = hitung_diskon($subtotal, $jmlBarang);
    $kode = 'INV' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));

    $stmt = $conn->prepare("INSERT INTO pesanan
        (kode_pesanan, id_user, subtotal, diskon_persen, diskon_nominal, total, status, nama_penerima, alamat_kirim, no_hp, catatan)
        VALUES (?, ?, ?, ?, ?, ?, 'menunggu', ?, ?, ?, ?)");
    $stmt->bind_param('sididdssss', $kode, $idUser, $h['subtotal'], $h['persen'], $h['nominal'], $h['total'],
                      $nama, $alamat, $noHp, $catatan);
    $stmt->execute();
    $idPesanan = $conn->insert_id;

    $ins = $conn->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga, jumlah, subtotal) VALUES (?,?,?,?,?,?)");
    $upd = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
    foreach ($baris as [$idp, $namaProduk, $harga, $jml, $sub]) {
        $ins->bind_param('iisdid', $idPesanan, $idp, $namaProduk, $harga, $jml, $sub);
        $ins->execute();
        $upd->bind_param('ii', $jml, $idp);
        $upd->execute();
    }
    $conn->commit();

    $stmt = $conn->prepare("SELECT id_pesanan, kode_pesanan, id_user, subtotal, diskon_persen, diskon_nominal,
                                   total, status, nama_penerima, alamat_kirim, no_hp, catatan, tanggal
                            FROM pesanan WHERE id_pesanan = ?");
    $stmt->bind_param('i', $idPesanan);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $out = [];
    foreach ($baris as [$idp, $namaProduk, $harga, $jml, $sub]) {
        $out[] = ['id_produk' => $idp, 'nama_produk' => $namaProduk, 'harga_satuan' => $harga,
                  'jumlah' => $jml, 'subtotal' => (float)$sub];
    }
    sukses(format_pesanan($p, $out), 'Pesanan berhasil dibuat', 201);
} catch (RuntimeException $e) {
    $conn->rollback();
    [$kodeHttp, $pesan] = explode('|', $e->getMessage(), 2);
    gagal((int)$kodeHttp, $pesan);
} catch (Throwable $e) {
    $conn->rollback();
    error_log('API pesanan gagal: ' . $e->getMessage());
    gagal(500, 'Terjadi kesalahan pada server saat menyimpan pesanan');
}
