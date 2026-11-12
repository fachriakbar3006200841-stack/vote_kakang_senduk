<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    set_flash('admin_error', 'Sesi formulir tidak valid.');
    header('Location: ' . app_base_url() . '/admin/pemilih.php');
    exit;
}

$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$nextStatus = $_POST['next_status'] ?? '';
if ($userId <= 0 || !in_array($nextStatus, ['aktif', 'nonaktif'], true)) {
    set_flash('admin_error', 'Data pemilih tidak valid.');
    header('Location: ' . app_base_url() . '/admin/pemilih.php');
    exit;
}

try {
    $pdo = require __DIR__ . '/../config/database.php';
    $statement = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id AND role = :role LIMIT 1');
    $statement->execute([
        'status' => $nextStatus,
        'id' => $userId,
        'role' => 'pemilih',
    ]);

    if ($statement->rowCount() === 0) {
        set_flash('admin_error', 'Pemilih yang dipilih tidak ditemukan.');
    } else {
        set_flash('admin_success', 'Status pemilih berhasil diperbarui.');
    }
} catch (Throwable $exception) {
    error_log('User status update failed: ' . $exception->getMessage());
    set_flash('admin_error', 'Status pemilih tidak dapat diubah.');
}

header('Location: ' . app_base_url() . '/admin/pemilih.php');
exit;
