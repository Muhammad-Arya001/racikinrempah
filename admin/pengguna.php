<?php
require __DIR__ . '/../includes/init.php';
$u = require_role(['admin']);

$roleValid = ['pembeli', 'penjual', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $idTarget = (int) ($_POST['id_user'] ?? 0);
    $roleBaru = $_POST['role'] ?? '';

    $target = row($db, 'SELECT id_user, nama, role FROM users WHERE id_user = ?', 'i', [$idTarget]);

    if (!$target) {
        flash('danger', 'Pengguna tidak ditemukan.');
    } elseif ($idTarget === (int) $u['id_user']) {
        flash('warning', 'Anda tidak dapat mengubah peran akun Anda sendiri.');
    } elseif (!in_array($roleBaru, $roleValid, true)) {
        flash('danger', 'Peran tidak valid.');
    } elseif ($target['role'] === $roleBaru) {
        flash('info', 'Peran ' . $target['nama'] . ' tidak berubah.');
    } else {
        query($db, 'UPDATE users SET role = ? WHERE id_user = ?', 'si', [$roleBaru, $idTarget])->close();
        flash('success', 'Peran ' . $target['nama'] . ' diubah menjadi ' . $roleBaru . '.');
    }
    redirect('admin/pengguna.php');
}

$daftar = rows(
    $db,
    'SELECT us.id_user, us.nama, us.email, us.role, us.created_at,
            (SELECT COUNT(*) FROM produk p WHERE p.id_penjual = us.id_user) AS jml_produk,
            (SELECT COUNT(*) FROM pesanan ps WHERE ps.id_user = us.id_user) AS jml_pesanan
       FROM users us ORDER BY us.id_user'
);

$judul = 'Kelola Pengguna';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-1">Kelola Pengguna</h1>
<p class="text-muted">Ubah peran pengguna (misalnya pembeli menjadi penjual). Perubahan berlaku saat pengguna tersebut login kembali.</p>

<div class="table-responsive bg-white rounded shadow-sm">
    <table class="table align-middle mb-0">
        <thead class="table-light">
            <tr><th>Nama</th><th>Email</th><th>Terdaftar</th><th class="text-center">Produk</th><th class="text-center">Pesanan</th><th style="min-width:230px">Peran</th></tr>
        </thead>
        <tbody>
        <?php foreach ($daftar as $d): $diriSendiri = (int) $d['id_user'] === (int) $u['id_user']; ?>
            <tr>
                <td class="fw-semibold"><?= e($d['nama']) ?><?= $diriSendiri ? ' <span class="badge text-bg-light">Anda</span>' : '' ?></td>
                <td><?= e($d['email']) ?></td>
                <td><?= e(date('d M Y', strtotime($d['created_at']))) ?></td>
                <td class="text-center"><?= (int) $d['jml_produk'] ?></td>
                <td class="text-center"><?= (int) $d['jml_pesanan'] ?></td>
                <td>
                    <?php if ($diriSendiri): ?>
                        <span class="badge text-bg-secondary"><?= e(ucfirst($d['role'])) ?></span>
                    <?php else: ?>
                        <form method="post" class="d-flex gap-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_user" value="<?= $d['id_user'] ?>">
                            <select name="role" class="form-select form-select-sm" aria-label="Peran <?= e($d['nama']) ?>">
                                <?php foreach ($roleValid as $r): ?>
                                    <option value="<?= $r ?>" <?= $d['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-primary">Simpan</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
