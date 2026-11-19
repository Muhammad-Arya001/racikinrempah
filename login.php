<?php
require __DIR__ . '/includes/init.php';
if (current_user()) {
    redirect('index.php');
}

$email = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = (string) ($_POST['password'] ?? '');

    $user = row($db, 'SELECT id_user, nama, email, password, role FROM users WHERE email = ?', 's', [$email]);
    if ($user && password_verify($pass, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id_user' => (int) $user['id_user'], 'nama' => $user['nama'], 'role' => $user['role']];
        flash('success', 'Selamat datang, ' . $user['nama'] . '!');

        $next = $_SESSION['next'] ?? '';
        unset($_SESSION['next']);
        // Hanya izinkan redirect ke path lokal
        if (is_string($next) && preg_match('#^/(?!/)#', $next)) {
            header('Location: ' . $next);
            exit;
        }
        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php');
        }
        if ($user['role'] === 'penjual') {
            redirect('admin/produk.php');
        }
        redirect('index.php');
    }
    $error = 'Email atau password salah.';
}

$judul = 'Masuk';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0"><div class="card-body p-4">
            <h1 class="h4 mb-3">Masuk</h1>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button class="btn btn-brand w-100">Masuk</button>
            </form>
            <p class="small text-center mt-3 mb-0">Belum punya akun? <a href="<?= BASE_URL ?>/register.php">Daftar</a></p>
        </div></div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
