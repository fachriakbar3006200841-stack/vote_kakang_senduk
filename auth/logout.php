<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode permintaan tidak diizinkan.');
}

if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Permintaan tidak valid. Muat ulang halaman lalu coba kembali.');
}

clear_authenticated_session();
header('Location: ' . app_base_url() . '/auth/login.php?status=logged_out');
exit;
