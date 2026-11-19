<?php
// Fungsi bantu bersama untuk semua endpoint.
// koneksi.php (lokal, tidak di-commit) diutamakan; jika belum ada, pakai bawaan XAMPP.
require file_exists(__DIR__ . '/koneksi.php') ? __DIR__ . '/koneksi.php' : __DIR__ . '/koneksi.example.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function kirim_json($kode, $data)
{
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function sukses($data, $pesan = 'Berhasil', $kode = 200)
{
    kirim_json($kode, ['status' => 'success', 'message' => $pesan, 'data' => $data]);
}

function gagal($kode, $pesan, $errors = null)
{
    $out = ['status' => 'error', 'message' => $pesan];
    if ($errors !== null) {
        $out['errors'] = $errors;
    }
    kirim_json($kode, $out);
}

function hanya_metode(array $diizinkan)
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $diizinkan, true)) {
        header('Allow: ' . implode(', ', $diizinkan));
        gagal(405, 'Metode ' . $_SERVER['REQUEST_METHOD'] . ' tidak diizinkan pada endpoint ini');
    }
}

// Aturan diskon sama dengan aplikasi Soal 11 (includes/diskon.php):
// >5 barang 10%, >15 barang 25%, >25 barang 30%.
function hitung_diskon($subtotal, $jumlahBarang)
{
    $persen = 0;
    if ($jumlahBarang > 25)      $persen = 30;
    elseif ($jumlahBarang > 15)  $persen = 25;
    elseif ($jumlahBarang > 5)   $persen = 10;
    $nominal = round($subtotal * $persen / 100, 2);
    return ['subtotal' => $subtotal, 'persen' => $persen, 'nominal' => $nominal, 'total' => $subtotal - $nominal];
}

// Error tak terduga selalu dijawab JSON 500, bukan halaman error PHP.
set_exception_handler(function ($e) {
    error_log('API error: ' . $e->getMessage());
    gagal(500, 'Terjadi kesalahan pada server');
});

function koneksi_db()
{
    global $db_host, $db_user, $db_pass, $db_name;
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_errno) {
        gagal(500, 'Koneksi database gagal');
    }
    $conn->set_charset('utf8mb4');
    // Setelah tersambung, error SQL dilempar sebagai exception (agar transaksi pesanan ter-rollback, bukan gagal diam-diam).
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    return $conn;
}