<?php
/**
 * config.php
 * - Memulai session
 * - Koneksi database (PDO)
 * - Mengatur bahasa (i18n via $_SESSION['lang'])
 * - Fungsi bantu: e(), t(), CSRF, flash message, proteksi login, validasi
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Konfigurasi Database (sesuaikan dengan server Anda) ---------- */
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_mahasiswa');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $ex) {
    http_response_code(500);
    exit('Database connection failed. Pastikan database.sql sudah diimpor dan kredensial di config.php benar.');
}

/* ---------- Pengaturan Bahasa ---------- */
const SUPPORTED_LANGS = ['id', 'en'];

// Jika ada ?lang=xx, simpan ke session lalu redirect agar URL bersih
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['lang'])) {
    if (in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    $params = $_GET;
    unset($params['lang']);
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

// Bahasa default: Indonesia
if (empty($_SESSION['lang']) || !in_array($_SESSION['lang'], SUPPORTED_LANGS, true)) {
    $_SESSION['lang'] = 'id';
}
$GLOBALS['lang'] = require __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';

/* ---------- Fungsi Bantu ---------- */

/** Escape output (pencegahan XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Ambil teks terjemahan; mendukung placeholder sprintf. */
function t(string $key, ...$args): string
{
    $text = $GLOBALS['lang'][$key] ?? $key;
    return $args ? vsprintf($text, $args) : $text;
}

/** URL untuk mengganti bahasa dengan mempertahankan parameter GET lain. */
function lang_url(string $code): string
{
    return '?' . http_build_query(array_merge($_GET, ['lang' => $code]));
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['csrf_token']);
}

/* ---------- Flash message ---------- */
function set_flash(string $type, string $key, ...$args): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => t($key, ...$args)];
}

function render_flash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    switch ($flash['type']) {
        case 'success':
            $class = 'alert-success';
            break;
        case 'warning':
            $class = 'alert-warning';
            break;
        case 'danger':
            $class = 'alert-danger';
            break;
        default:
            $class = 'alert-info';
    }
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">'
        . e($flash['msg'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

/* ---------- Proteksi halaman ---------- */
function require_login(): void
{
    if (empty($_SESSION['user'])) {
        set_flash('warning', 'login_required');
        redirect('login.php');
    }
}

/* ---------- Validasi & Sanitasi data mahasiswa ---------- */

/**
 * Membersihkan dan memvalidasi input form mahasiswa.
 * @return array [data_bersih, daftar_error]
 */
function validate_mahasiswa(array $post): array
{
    $data = [
        'nim'     => trim((string) ($post['nim'] ?? '')),
        'nama'    => trim((string) ($post['nama'] ?? '')),
        'jurusan' => trim((string) ($post['jurusan'] ?? '')),
        'email'   => trim((string) ($post['email'] ?? '')),
        'no_hp'   => trim((string) ($post['no_hp'] ?? '')),
    ];
    $errors = [];

    // NIM: wajib, hanya angka 5-20 digit
    if ($data['nim'] === '') {
        $errors['nim'] = t('err_nim_required');
    } elseif (!preg_match('/^[0-9]{5,20}$/', $data['nim'])) {
        $errors['nim'] = t('err_nim_format');
    }

    // Nama: wajib, 3-100 karakter
    if ($data['nama'] === '') {
        $errors['nama'] = t('err_name_required');
    } elseif (mb_strlen($data['nama']) < 3 || mb_strlen($data['nama']) > 100) {
        $errors['nama'] = t('err_name_length');
    }

    // Jurusan: wajib
    if ($data['jurusan'] === '') {
        $errors['jurusan'] = t('err_major_required');
    } elseif (mb_strlen($data['jurusan']) > 100) {
        $errors['jurusan'] = t('err_major_length');
    }

    // Email: wajib + format valid (filter_var)
    if ($data['email'] === '') {
        $errors['email'] = t('err_email_required');
    } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = t('err_email_invalid');
    }

    // No HP: opsional, bila diisi harus berformat benar
    if ($data['no_hp'] !== '' && !preg_match('/^\+?[0-9]{8,15}$/', $data['no_hp'])) {
        $errors['no_hp'] = t('err_phone_format');
    }

    return [$data, $errors];
}

/** Cek apakah NIM sudah dipakai (abaikan id tertentu saat edit). */
function nim_exists(PDO $pdo, string $nim, int $exceptId = 0): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim AND id <> :id');
    $stmt->execute([':nim' => $nim, ':id' => $exceptId]);
    return (int) $stmt->fetchColumn() > 0;
}
