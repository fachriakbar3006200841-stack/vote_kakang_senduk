<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode request tidak diizinkan.');
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    $_SESSION['flash']['admin_error'] = 'Sesi formulir tidak valid. Silakan ulangi.';
    header('Location: ' . app_base_url() . '/admin/kontestan.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    $_SESSION['flash']['admin_error'] = 'ID kontestan tidak valid.';
    header('Location: ' . app_base_url() . '/admin/kontestan.php');
    exit;
}

$pdo = require __DIR__ . '/../config/database.php';
$statement = $pdo->prepare('SELECT id, status FROM kontestan WHERE id = :id LIMIT 1');
$statement->execute(['id' => $id]);
$contestant = $statement->fetch();

if ($contestant === false) {
    http_response_code(404);
    $_SESSION['flash']['admin_error'] = 'Kontestan tidak ditemukan.';
    header('Location: ' . app_base_url() . '/admin/kontestan.php');
    exit;
}

$newStatus = $contestant['status'] === 'aktif' ? 'nonaktif' : 'aktif';
$update = $pdo->prepare('UPDATE kontestan SET status = :status WHERE id = :id');
$update->execute(['status' => $newStatus, 'id' => $id]);

$_SESSION['flash']['admin_success'] = 'Status kontestan berhasil diubah menjadi ' . $newStatus . '.';
header('Location: ' . app_base_url() . '/admin/kontestan.php');
exit;
