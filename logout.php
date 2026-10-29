<?php
require_once __DIR__ . '/config.php';

// Simpan pilihan bahasa agar tidak hilang setelah logout
$currentLang = $_SESSION['lang'] ?? 'id';

// Hancurkan session sepenuhnya
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Mulai session baru hanya untuk pesan + bahasa
session_start();
session_regenerate_id(true);
$_SESSION['lang'] = $currentLang;
$GLOBALS['lang']  = require __DIR__ . '/lang/' . $currentLang . '.php';
set_flash('success', 'logout_success');

redirect('login.php');
