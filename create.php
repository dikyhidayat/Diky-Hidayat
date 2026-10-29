<?php
require_once __DIR__ . '/config.php';
require_login();

$data   = ['nim' => '', 'nama' => '', 'jurusan' => '', 'email' => '', 'no_hp' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['global'] = t('err_csrf');
    } else {
        // Validasi & sanitasi
        [$data, $errors] = validate_mahasiswa($_POST);

        // Cek duplikasi NIM hanya jika field lain sudah valid
        if (empty($errors['nim']) && nim_exists($pdo, $data['nim'])) {
            $errors['nim'] = t('err_nim_duplicate');
        }

        // Simpan jika tidak ada error
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO mahasiswa (nim, nama, jurusan, email, no_hp)
                     VALUES (:nim, :nama, :jurusan, :email, :no_hp)'
                );
                $stmt->execute([
                    ':nim'     => $data['nim'],
                    ':nama'    => $data['nama'],
                    ':jurusan' => $data['jurusan'],
                    ':email'   => $data['email'],
                    ':no_hp'   => $data['no_hp'] !== '' ? $data['no_hp'] : null,
                ]);
                set_flash('success', 'msg_created');
                redirect('index.php');
            } catch (PDOException $ex) {
                $errors['global'] = t('msg_db_error');
            }
        }
    }
}

$pageTitle   = t('form_add_title');
$formAction  = 'create.php';
$submitLabel = t('btn_save');
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h1 class="h4 mb-0"><i class="bi bi-person-plus-fill text-primary"></i> <?= e(t('form_add_title')) ?></h1>
            </div>
            <div class="card-body p-4">
                <?php require __DIR__ . '/includes/form_mahasiswa.php'; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
