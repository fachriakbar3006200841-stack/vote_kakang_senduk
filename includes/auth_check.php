<?php
declare(strict_types=1);

require_once __DIR__ . '/csrf.php';

function app_base_url(): string
{
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(dirname(__DIR__));

    if ($documentRoot === false || $projectRoot === false) {
        return '';
    }

    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $projectRoot = str_replace('\\', '/', $projectRoot);

    if (strncasecmp($projectRoot, $documentRoot . '/', strlen($documentRoot) + 1) !== 0) {
        return '';
    }

    return '/' . trim(substr($projectRoot, strlen($documentRoot)), '/');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function redirect_to_role_home(string $role): never
{
    if ($role === 'admin') {
        header('Location: ' . app_base_url() . '/admin/index.php');
        exit;
    }

    header('Location: ' . app_base_url() . '/pemilih/index.php');
    exit;
}

function clear_authenticated_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function require_login(array $allowedRoles = []): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: ' . app_base_url() . '/auth/login.php');
        exit;
    }

    try {
        $pdo = require __DIR__ . '/../config/database.php';
        $statement = $pdo->prepare(
            'SELECT id, nama, username, email, role, status
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $user['id'] ?? 0]);
        $freshUser = $statement->fetch();
    } catch (Throwable $exception) {
        error_log('Authentication session validation failed: ' . $exception->getMessage());
        http_response_code(503);
        exit('Layanan autentikasi sedang tidak tersedia. Silakan coba kembali.');
    }

    if (!$freshUser || $freshUser['status'] !== 'aktif') {
        clear_authenticated_session();
        header('Location: ' . app_base_url() . '/auth/login.php?status=inactive');
        exit;
    }

    $_SESSION['user'] = [
        'id' => (int) $freshUser['id'],
        'nama' => $freshUser['nama'],
        'username' => $freshUser['username'],
        'email' => $freshUser['email'],
        'role' => $freshUser['role'],
    ];

    if ($allowedRoles !== [] && !in_array($freshUser['role'], $allowedRoles, true)) {
        http_response_code(403);
        exit('Anda tidak memiliki izin untuk mengakses halaman ini.');
    }

    return $_SESSION['user'];
}
