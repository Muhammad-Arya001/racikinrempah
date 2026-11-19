<?php
declare(strict_types=1);

/*
 * Bootstrap aplikasi:
 * - koneksi MySQLi
 * - session
 * - helper query
 * - autentikasi/otorisasi
 * - CSRF
 * - flash message
 * - helper tampilan gambar, rupiah, redirect
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('RACIKINREMPAH');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config.php';

if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = preg_replace('#/(?:admin|includes)(?:/.*)?$#', '', $scriptName);
    $basePath = preg_replace('#/[^/]+\.php$#', '', $basePath);
    $basePath = rtrim((string) $basePath, '/');
    define('BASE_URL', $basePath === '/' ? '' : $basePath);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->set_charset('utf8mb4');

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function query(mysqli $db, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        if (strlen($types) !== count($params)) {
            throw new InvalidArgumentException('Jumlah tipe parameter tidak sesuai dengan jumlah parameter.');
        }
        $stmt->bind_param($types, ...$params);
    } elseif ($params) {
        throw new InvalidArgumentException('Parameter diberikan tanpa tipe bind.');
    }
    $stmt->execute();
    return $stmt;
}

function rows(mysqli $db, string $sql, string $types = '', array $params = []): array
{
    $stmt = query($db, $sql, $types, $params);
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    $stmt->close();
    return $data;
}

function row(mysqli $db, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = query($db, $sql, $types, $params);
    $result = $stmt->get_result();
    $data = $result->fetch_assoc() ?: null;
    $result->free();
    $stmt->close();
    return $data;
}

function rupiah(mixed $nominal): string
{
    return 'Rp ' . number_format((float) $nominal, 0, ',', '.');
}

function redirect(string $path): never
{
    if (preg_match('#^https?://#i', $path)) {
        throw new InvalidArgumentException('Redirect eksternal tidak diizinkan.');
    }

    $path = '/' . ltrim($path, '/');
    header('Location: ' . BASE_URL . $path);
    exit;
}

function safe_back(string $fallback = 'index.php'): string
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref === '') {
        return BASE_URL . '/' . ltrim($fallback, '/');
    }

    $parts = parse_url($ref);
    $host = $parts['host'] ?? '';
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';

    if ($host !== '' && $currentHost !== '' && strcasecmp($host, preg_replace('/:\d+$/', '', $currentHost)) !== 0) {
        return BASE_URL . '/' . ltrim($fallback, '/');
    }

    $path = $parts['path'] ?? '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    return $path . $query;
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['next'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/index.php');
        flash('warning', 'Silakan masuk terlebih dahulu.');
        redirect('login.php');
    }
    return $u;
}

function require_role(array $roles): array
{
    $u = require_login();
    if (!in_array($u['role'] ?? '', $roles, true)) {
        http_response_code(403);
        $judul = 'Akses Ditolak';
        include __DIR__ . '/header.php';
        echo '<div class="alert alert-danger"><strong>Akses ditolak.</strong> Anda tidak memiliki izin untuk membuka halaman ini.</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
    return $u;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    $valid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    if (!$valid) {
        http_response_code(419);
        exit('Token keamanan tidak valid. Silakan kembali dan coba lagi.');
    }
}

function status_badge(string $s): string
{
    $map = [
        'menunggu' => ['warning', 'Menunggu'],
        'dibayar' => ['info', 'Dibayar'],
        'diproses' => ['info', 'Diproses'],
        'disiapkan' => ['secondary', 'Sedang Disiapkan'],
        'dikirim' => ['primary', 'Dikirim'],
        'dalam_pengantaran' => ['primary', 'Dalam Pengantaran'],
        'selesai' => ['success', 'Selesai'],
        'batal' => ['danger', 'Batal'],
    ];

    [$warna, $label] = $map[$s] ?? ['secondary', ucfirst(str_replace('_', ' ', $s))];
    return '<span class="badge text-bg-' . e($warna) . '">' . e($label) . '</span>';
}

function gambar_url(?string $gambar): string
{
    $gambar = trim((string) $gambar);
    if ($gambar === '') {
        return BASE_URL . '/assets/img/rempahkito.jpeg';
    }

    if (preg_match('#^(https?:)?//#i', $gambar)) {
        return $gambar;
    }

    if (str_starts_with($gambar, 'uploads/')) {
        return BASE_URL . '/' . ltrim($gambar, '/');
    }

    return BASE_URL . '/uploads/' . ltrim($gambar, '/');
}

function gambar_html(array $p, string $class = ''): string
{
    $src = gambar_url($p['gambar'] ?? null);
    $alt = e($p['nama_produk'] ?? 'Produk rempah');
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<img src="' . e($src) . '" alt="' . $alt . '"' . $classAttr . ' loading="lazy">';
}

function hapus_gambar(?string $gambar): void
{
    $gambar = trim((string) $gambar);
    if ($gambar === '' || str_contains($gambar, '://')) {
        return;
    }

    $relative = str_starts_with($gambar, 'uploads/') ? substr($gambar, strlen('uploads/')) : basename($gambar);
    $relative = basename($relative);
    if ($relative === '' || $relative === '.' || $relative === '..') {
        return;
    }

    $path = __DIR__ . '/../uploads/' . $relative;
    if (is_file($path)) {
        @unlink($path);
    }
}

function simpan_gambar(array $file, ?string $gambarLama = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $gambarLama;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gambar gagal.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar maksimal 5 MB.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WEBP.');
    }

    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder uploads tidak dapat dibuat.');
    }

    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        throw new RuntimeException('Gambar tidak dapat disimpan.');
    }

    if ($gambarLama) {
        hapus_gambar($gambarLama);
    }

    return 'uploads/' . $name;
}
