<?php
require __DIR__ . '/../includes/init.php';
$u = require_role(['admin']);

$editId = (int) ($_GET['edit'] ?? 0);
$edit = $editId > 0 ? row($db, 'SELECT * FROM kategori WHERE id_kategori = ?', 'i', [$editId]) : null;
$nama = $edit['nama_kategori'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $aksi = (string) ($_POST['aksi'] ?? '');
    $id = (int) ($_POST['id_kategori'] ?? 0);
    $namaInput = trim((string) ($_POST['nama_kategori'] ?? ''));

    if ($aksi === 'simpan') {
        if ($namaInput === '' || mb_strlen($namaInput) > 80) {
            flash('danger', 'Nama kategori wajib diisi dan maksimal 80 karakter.');
        } elseif (row($db, 'SELECT id_kategori FROM kategori WHERE LOWER(nama_kategori) = LOWER(?) AND id_kategori <> ?', 'si', [$namaInput, $id])) {
            flash('danger', 'Nama kategori sudah digunakan.');
        } elseif ($id > 0) {
            if (!row($db, 'SELECT id_kategori FROM kategori WHERE id_kategori = ?', 'i', [$id])) {
                flash('danger', 'Kategori tidak ditemukan.');
            } else {
                query($db, 'UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?', 'si', [$namaInput, $id])->close();
                flash('success', 'Kategori berhasil diperbarui.');
            }
        } else {
            query($db, 'INSERT INTO kategori (nama_kategori) VALUES (?)', 's', [$namaInput])->close();
            flash('success', 'Kategori berhasil ditambahkan.');
        }
        redirect('admin/kategori.php');
    }

    if ($aksi === 'hapus') {
        $kategori = row($db, 'SELECT id_kategori, nama_kategori FROM kategori WHERE id_kategori = ?', 'i', [$id]);
        if (!$kategori) {
            flash('danger', 'Kategori tidak ditemukan.');
        } else {
            query($db, 'DELETE FROM kategori WHERE id_kategori = ?', 'i', [$id])->close();
            flash('success', 'Kategori "' . $kategori['nama_kategori'] . '" berhasil dihapus. Produk terkait tetap ada dan kategorinya menjadi kosong.');
        }
        redirect('admin/kategori.php');
    }

    flash('warning', 'Aksi kategori tidak dikenali.');
    redirect('admin/kategori.php');
}

$daftar = rows($db, 'SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk FROM kategori k LEFT JOIN produk p ON p.id_kategori = k.id_kategori GROUP BY k.id_kategori, k.nama_kategori ORDER BY k.nama_kategori');
$judul = 'Kelola Kategori';
include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3"><div><h1 class="h3 mb-1">Kelola Kategori</h1><p class="text-muted mb-0">Admin mengatur kategori yang digunakan penjual saat membuat produk.</p></div></div>
<div class="row g-4">
<div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body p-4"><h2 class="h5 mb-3"><?= $edit ? 'Edit Kategori' : 'Tambah Kategori' ?></h2><form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id_kategori" value="<?= (int)$editId ?>"><label class="form-label" for="nama_kategori">Nama Kategori</label><input class="form-control mb-3" id="nama_kategori" name="nama_kategori" maxlength="80" value="<?= e($nama) ?>" required><button class="btn btn-brand">Simpan</button><?php if ($edit): ?><a href="<?= BASE_URL ?>/admin/kategori.php" class="btn btn-outline-secondary">Batal</a><?php endif; ?></form></div></div></div>
<div class="col-lg-7"><div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="p-3 border-bottom"><h2 class="h5 mb-0">Daftar Kategori</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Kategori</th><th class="text-center">Jumlah Produk</th><th class="text-end">Aksi</th></tr></thead><tbody><?php foreach ($daftar as $k): ?><tr><td class="fw-semibold"><?= e($k['nama_kategori']) ?></td><td class="text-center"><?= (int)$k['jumlah_produk'] ?></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/admin/kategori.php?edit=<?= (int)$k['id_kategori'] ?>">Edit</a><form method="post" class="d-inline" onsubmit="return confirm('Hapus kategori ini? Produk tidak akan dihapus.');"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_kategori" value="<?= (int)$k['id_kategori'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach; ?><?php if (!$daftar): ?><tr><td colspan="3" class="p-4 text-muted">Belum ada kategori.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
