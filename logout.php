<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
    }
    session_destroy();
    session_start();
    flash('success', 'Anda telah keluar.');
}
redirect('index.php');
