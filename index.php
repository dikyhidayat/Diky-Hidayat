<?php
require_once __DIR__ . '/config.php';
require_login();

// ---------- READ: ambil data (dengan pencarian opsional) ----------
$q = trim((string) ($_GET['q'] ?? ''));

try {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare(
            'SELECT * FROM mahasiswa
             WHERE nim LIKE :q1 OR nama LIKE :q2 OR jurusan LIKE :q3
             ORDER BY id DESC'
        );
        $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
    } else {
        $stmt = $pdo->query('SELECT * FROM mahasiswa ORDER BY id DESC');
    }
    $rows = $stmt->fetchAll();

    $totalStudents = (int) $pdo->query('SELECT COUNT(*) FROM mahasiswa')->fetchColumn();
    $totalMajors   = (int) $pdo->query('SELECT COUNT(DISTINCT jurusan) FROM mahasiswa')->fetchColumn();
} catch (PDOException $ex) {
    $rows = [];
    $totalStudents = $totalMajors = 0;
    set_flash('danger', 'msg_db_error');
}

$pageTitle = t('dashboard_title');
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4">
    <h1 class="h3 mb-1"><?= e(t('dashboard_title')) ?></h1>
    <p class="text-muted mb-0"><?= e(t('welcome')) ?>, <strong><?= e($_SESSION['user']) ?></strong> 👋</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-people-fill text-primary fs-1"></i>
                <div>
                    <div class="text-muted small"><?= e(t('total_students')) ?></div>
                    <div class="fs-3 fw-bold"><?= $totalStudents ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card stat-card shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-journal-bookmark-fill text-success fs-1"></i>
                <div>
                    <div class="text-muted small"><?= e(t('total_majors')) ?></div>
                    <div class="fs-3 fw-bold"><?= $totalMajors ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
            <form method="GET" action="index.php" class="d-flex gap-2 flex-grow-1" style="max-width:520px">
                <input type="text" name="q" class="form-control" value="<?= e($q) ?>"
                       placeholder="<?= e(t('search_placeholder')) ?>">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> <?= e(t('btn_search')) ?></button>
                <?php if ($q !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary"><?= e(t('btn_reset')) ?></a>
                <?php endif; ?>
            </form>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?= e(t('btn_add')) ?></a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-primary">
                <tr>
                    <th><?= e(t('col_no')) ?></th>
                    <th><?= e(t('col_nim')) ?></th>
                    <th><?= e(t('col_name')) ?></th>
                    <th><?= e(t('col_major')) ?></th>
                    <th><?= e(t('col_email')) ?></th>
                    <th><?= e(t('col_phone')) ?></th>
                    <th class="text-center"><?= e(t('col_action')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><?= e(t('no_data')) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($row['nim']) ?></td>
                            <td><?= e($row['nama']) ?></td>
                            <td><?= e($row['jurusan']) ?></td>
                            <td><?= e($row['email']) ?></td>
                            <td><?= e($row['no_hp'] ?: '-') ?></td>
                            <td class="text-center text-nowrap">
                                <a href="edit.php?id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil-square"></i> <?= e(t('btn_edit')) ?>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                        data-id="<?= (int) $row['id'] ?>"
                                        data-nama="<?= e($row['nama']) ?> (<?= e($row['nim']) ?>)">
                                    <i class="bi bi-trash"></i> <?= e(t('btn_delete')) ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="delete.php" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="delete-id" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    <?= e(t('modal_delete_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1"><?= e(t('modal_delete_body')) ?></p>
                <p class="fw-bold mb-0" id="delete-nama"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= e(t('modal_cancel')) ?></button>
                <button type="submit" class="btn btn-danger"><?= e(t('modal_confirm')) ?></button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (ev) {
    var btn = ev.relatedTarget;
    document.getElementById('delete-id').value = btn.getAttribute('data-id');
    document.getElementById('delete-nama').textContent = btn.getAttribute('data-nama');
});
</script>
JS;
require __DIR__ . '/includes/footer.php';
?>
