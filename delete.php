<?php
require_once __DIR__ . '/config.php';
require_login();

// Hanya menerima POST (konfirmasi dilakukan lewat modal di index.php)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!csrf_valid()) {
    set_flash('danger', 'err_csrf');
    redirect('index.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    set_flash('danger', 'msg_invalid_request');
    redirect('index.php');
}

try {
    $stmt = $pdo->prepare('DELETE FROM mahasiswa WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'msg_deleted');
    } else {
        set_flash('danger', 'msg_not_found');
    }
} catch (PDOException $ex) {
    set_flash('danger', 'msg_db_error');
}

redirect('index.php');
