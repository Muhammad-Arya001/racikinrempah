<?php
require __DIR__ . '/../includes/init.php';

$u = require_role(['penjual']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$produk = null;

if ($id > 0) {
    $produk = row(
        $db,
        'SELECT * FROM produk WHERE id_produk = ? AND id_penjual = ?',
        'ii',
        [$id, $u['id_user']]
    );

    if (!$produk) {
        http_response_code(404);
        exit('Produk tidak ditemukan atau bukan milik Anda.');
    }
}

$kategori = rows($db, 'SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori');
$errors = [];

$in = [
    'nama_produk' => $produk['nama_produk'] ?? '',
    'id_kategori' => (int) ($produk['id_kategori'] ?? 0),
    'deskripsi' => $produk['deskripsi'] ?? '',
    'harga' => $produk['harga'] ?? '',
    'stok' => $produk['stok'] ?? 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $in['nama_produk'] = trim((string) ($_POST['nama_produk'] ?? ''));
    $in['id_kategori'] = (int) ($_POST['id_kategori'] ?? 0);
    $in['deskripsi'] = trim((string) ($_POST['deskripsi'] ?? ''));
    $in['harga'] = trim((string) ($_POST['harga'] ?? ''));
    $in['stok'] = (int) ($_POST['stok'] ?? 0);

    if ($in['nama_produk'] === '' || mb_strlen($in['nama_produk']) > 150) {
        $errors[] = 'Nama produk wajib diisi dan maksimal 150 karakter.';
    }
    if ($in['id_kategori'] < 1 || !row($db, 'SELECT id_kategori FROM kategori WHERE id_kategori = ?', 'i', [$in['id_kategori']])) {
        $errors[] = 'Kategori produk tidak valid.';
    }
    if (!is_numeric($in['harga']) || (float) $in['harga'] < 0) {
        $errors[] = 'Harga produk tidak valid.';
    }
    if ($in['stok'] < 0) {
        $errors[] = 'Stok tidak boleh kurang dari 0.';
    }
    if (mb_strlen($in['deskripsi']) > 5000) {
        $errors[] = 'Deskripsi terlalu panjang.';
    }

    $gambarBaru = $produk['gambar'] ?? null;
    if (!$errors && isset($_FILES['gambar']) && ($_FILES['gambar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $gambarBaru = simpan_gambar($_FILES['gambar'], $gambarBaru);
        } catch (Throwable $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        if ($id > 0) {
            query(
                $db,
                'UPDATE produk SET id_kategori = ?, nama_produk = ?, deskripsi = ?, harga = ?, stok = ?, gambar = ? WHERE id_produk = ? AND id_penjual = ?',
                'issdisii',
                [$in['id_kategori'], $in['nama_produk'], $in['deskripsi'], (float) $in['harga'], $in['stok'], $gambarBaru, $id, $u['id_user']]
            )->close();
            flash('success', 'Produk berhasil diperbarui.');
        } else {
            query(
                $db,
                'INSERT INTO produk (id_kategori, id_penjual, nama_produk, deskripsi, harga, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)',
                'iissdis',
                [$in['id_kategori'], $u['id_user'], $in['nama_produk'], $in['deskripsi'], (float) $in['harga'], $in['stok'], $gambarBaru]
            )->close();
            flash('success', 'Produk berhasil ditambahkan.');
        }
        redirect('admin/produk.php');
    }
}

$judul = $id > 0 ? 'Edit Produk' : 'Tambah Produk';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= e($judul) ?></h1>
    <a href="<?= BASE_URL ?>/admin/produk.php" class="btn btn-outline-secondary">← Kembali</a>
</div>

<?php foreach ($errors as $er): ?>
    <div class="alert alert-danger"><?= e($er) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="card shadow-sm border-0">
    <div class="card-body p-4">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="nama_produk">Nama Produk</label>
                <input class="form-control" id="nama_produk" name="nama_produk" value="<?= e($in['nama_produk']) ?>" maxlength="150" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="id_kategori">Kategori</label>
                <select class="form-select" id="id_kategori" name="id_kategori" required>
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= (int) $k['id_kategori'] ?>" <?= (int) $in['id_kategori'] === (int) $k['id_kategori'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="harga">Harga</label>
                <input type="number" class="form-control" id="harga" name="harga" value="<?= e($in['harga']) ?>" min="0" step="0.01" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="stok">Stok</label>
                <input type="number" class="form-control" id="stok" name="stok" value="<?= (int) $in['stok'] ?>" min="0" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="deskripsi">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" maxlength="5000"><?= e($in['deskripsi']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="gambar">Gambar Produk</label>
                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG, PNG, atau WEBP. Maksimal 5 MB.</div>
                <?php if (!empty($produk['gambar'])): ?>
                    <div class="mt-3" style="max-width:180px"><?= gambar_html($produk, 'img-thumb') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white p-3">
        <button class="btn btn-brand">Simpan Produk</button>
        <a href="<?= BASE_URL ?>/admin/produk.php" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
