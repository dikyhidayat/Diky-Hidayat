<?php
require_once __DIR__ . '/config.php';

// Jika sudah login, langsung ke dashboard
if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Percabangan validasi
    if (!csrf_valid()) {
        $error = t('err_csrf');
    } elseif ($username === '' || $password === '') {
        $error = t('err_login_required');
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);          // cegah session fixation
            $_SESSION['user']    = $user['username'];
            $_SESSION['user_id'] = (int) $user['id'];
            redirect('index.php');
        } else {
            $error = t('err_login_invalid');
        }
    }
}

$pageTitle = t('login_title');
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center mt-4">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-person-lock text-primary" style="font-size:3rem"></i>
                    <h1 class="h4 mt-2"><?= e(t('login_title')) ?></h1>
                    <p class="text-muted small mb-0"><?= e(t('login_subtitle')) ?></p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="username" class="form-label"><?= e(t('username')) ?></label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= e($username) ?>" autofocus autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><?= e(t('password')) ?></label>
                        <input type="password" class="form-control" id="password" name="password"
                               autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> <?= e(t('btn_login')) ?>
                    </button>
                </form>
                <p class="text-center text-muted small mt-3 mb-0"><?= e(t('demo_hint')) ?></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
