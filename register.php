<?php
require __DIR__ . '/includes/init.php';
if (current_user()) {
    redirect('index.php');
}

$in = ['nama' => '', 'email' => '', 'role' => 'pembeli', 'no_hp' => '', 'alamat' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach (array_keys($in) as $k) {
        $in[$k] = trim($_POST[$k] ?? '');
    }
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    if ($in['nama'] === '' || mb_strlen($in['nama']) > 100) {
        $errors[] = 'Nama wajib diisi (maks. 100 karakter).';
    }
    if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($in['email']) > 120) {
        $errors[] = 'Format email tidak valid.';
    }
    if (!in_array($in['role'], ['pembeli', 'penjual'], true)) {
        $in['role'] = 'pembeli'; // admin tidak bisa mendaftar lewat form
    }
    if (strlen($pass) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if ($pass !== $pass2) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }
    if ($in['no_hp'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $in['no_hp'])) {
        $errors[] = 'Nomor HP tidak valid.';
    }
    if (!$errors && row($db, 'SELECT 1 AS x FROM users WHERE email = ?', 's', [$in['email']])) {
        $errors[] = 'Email sudah terdaftar.';
    }

    if (!$errors) {
        query(
            $db,
            'INSERT INTO users (nama, email, password, role, alamat, no_hp) VALUES (?, ?, ?, ?, ?, ?)',
            'ssssss',
            [$in['nama'], $in['email'], password_hash($pass, PASSWORD_DEFAULT), $in['role'], $in['alamat'], $in['no_hp']]
        )->close();
        flash('success', 'Pendaftaran berhasil. Silakan masuk.');
        redirect('login.php');
    }
}

$judul = 'Daftar';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0"><div class="card-body p-4">
            <h1 class="h4 mb-3">Daftar Akun</h1>
            <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="nama">Nama lengkap</label>
                    <input class="form-control" id="nama" name="nama" value="<?= e($in['nama']) ?>" required maxlength="100"></div>
                <div class="mb-3"><label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($in['email']) ?>" required maxlength="120"></div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6"></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="password2">Ulangi password</label>
                        <input type="password" class="form-control" id="password2" name="password2" required minlength="6"></div>
                </div>
                <div class="mb-3"><label class="form-label" for="role">Daftar sebagai</label>
                    <select class="form-select" id="role" name="role">
                        <option value="pembeli" <?= $in['role'] === 'pembeli' ? 'selected' : '' ?>>Pembeli</option>
                        <option value="penjual" <?= $in['role'] === 'penjual' ? 'selected' : '' ?>>Penjual</option>
                    </select></div>
                <div class="mb-3"><label class="form-label" for="no_hp">No. HP (opsional)</label>
                    <input class="form-control" id="no_hp" name="no_hp" value="<?= e($in['no_hp']) ?>" maxlength="20"></div>
                <div class="mb-3"><label class="form-label" for="alamat">Alamat (opsional)</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="2"><?= e($in['alamat']) ?></textarea></div>
                <button class="btn btn-brand w-100">Daftar</button>
            </form>
        </div></div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
