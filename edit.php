<?php
require_once __DIR__ . '/config.php';
require_login();

// Ambil id unik dari URL dan validasi sebagai integer
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    set_flash('danger', 'msg_invalid_request');
    redirect('index.php');
}

// Ambil data lama
$stmt = $pdo->prepare('SELECT * FROM mahasiswa WHERE id = :id');
$stmt->execute([':id' => $id]);
$existing = $stmt->fetch();

if (!$existing) {
    set_flash('danger', 'msg_not_found');
    redirect('index.php');
}

$data   = [
    'nim'     => $existing['nim'],
    'nama'    => $existing['nama'],
    'jurusan' => $existing['jurusan'],
    'email'   => $existing['email'],
    'no_hp'   => (string) $existing['no_hp'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['global'] = t('err_csrf');
    } else {
        [$data, $errors] = validate_mahasiswa($_POST);

        if (empty($errors['nim']) && nim_exists($pdo, $data['nim'], $id)) {
            $errors['nim'] = t('err_nim_duplicate');
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    'UPDATE mahasiswa
                     SET nim = :nim, nama = :nama, jurusan = :jurusan, email = :email, no_hp = :no_hp
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':nim'     => $data['nim'],
                    ':nama'    => $data['nama'],
                    ':jurusan' => $data['jurusan'],
                    ':email'   => $data['email'],
                    ':no_hp'   => $data['no_hp'] !== '' ? $data['no_hp'] : null,
                    ':id'      => $id,
                ]);
                set_flash('success', 'msg_updated');
                redirect('index.php');
            } catch (PDOException $ex) {
                $errors['global'] = t('msg_db_error');
            }
        }
    }
}

$pageTitle   = t('form_edit_title');
$formAction  = 'edit.php?id=' . $id;
$submitLabel = t('btn_update');
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h1 class="h4 mb-0"><i class="bi bi-pencil-square text-warning"></i> <?= e(t('form_edit_title')) ?></h1>
            </div>
            <div class="card-body p-4">
                <?php require __DIR__ . '/includes/form_mahasiswa.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
