<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';

if (current_user() !== null) {
    redirect_to_role_home((string) current_user()['role']);
}

$identity = '';
$errorMessage = null;
$successMessage = get_flash('success');
$loggedOut = ($_GET['status'] ?? '') === 'logged_out';
$inactive = ($_GET['status'] ?? '') === 'inactive';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Sesi formulir tidak valid atau telah kedaluwarsa. Muat ulang halaman lalu coba kembali.';
    } else {
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($identity === '' || strlen($identity) > 191 || $password === '' || strlen($password) > 72) {
            $errorMessage = 'Username/email atau password tidak valid.';
        } else {
            try {
                $pdo = require __DIR__ . '/../config/database.php';
                $statement = $pdo->prepare(
                    'SELECT id, nama, username, email, password, role, status
                     FROM users
                     WHERE username = :username OR email = :email
                     LIMIT 1'
                );
                $statement->execute([
                    'username' => $identity,
                    'email' => $identity,
                ]);
                $user = $statement->fetch();

                if (!$user || $user['status'] !== 'aktif' || !password_verify($password, $user['password'])) {
                    $errorMessage = 'Username/email atau password tidak sesuai.';
                } else {
                    session_regenerate_id(true);
                    csrf_token(true);
                    $_SESSION['user'] = [
                        'id' => (int) $user['id'],
                        'nama' => $user['nama'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                    ];

                    redirect_to_role_home((string) $user['role']);
                }
            } catch (Throwable $exception) {
                error_log('Login failed: ' . $exception->getMessage());
                http_response_code(500);
                $errorMessage = 'Login belum dapat diproses. Silakan coba kembali.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | Kakang Senduk Ponorogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_base_url()) ?>/assets/css/auth.css" rel="stylesheet">
</head>
<body>
<main class="auth-shell">
    <section class="auth-brand" aria-label="Kakang Senduk Ponorogo">
        <div class="brand-mark">KS</div>
        <p class="brand-kicker">Duta Wisata Kabupaten Ponorogo</p>
        <h1>Kakang<br>Senduk</h1>
        <p class="brand-copy">Kenali pesona daerah, dukung generasi muda, dan ikut merayakan budaya Ponorogo.</p>
        <span class="brand-ornament" aria-hidden="true">✦</span>
    </section>
    <section class="auth-panel">
        <div class="auth-form-wrap login-form">
            <a class="back-link" href="<?= e(app_base_url()) ?>/index.php">← Kembali ke beranda</a>
            <p class="form-kicker">Selamat datang kembali</p>
            <h2>Masuk ke akun</h2>
            <p class="form-intro">Gunakan username atau email yang terdaftar.</p>

            <?php if ($successMessage !== null): ?><div class="alert alert-success" role="status"><?= e($successMessage) ?></div><?php endif; ?>
            <?php if ($loggedOut): ?><div class="alert alert-success" role="status">Anda telah keluar dengan aman.</div><?php endif; ?>
            <?php if ($inactive): ?><div class="alert alert-warning" role="status">Akun tidak aktif atau sesi Anda telah berakhir. Silakan masuk kembali.</div><?php endif; ?>
            <?php if ($errorMessage !== null): ?><div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>

            <form method="post" action="" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="identity">Username atau email</label>
                    <input class="form-control" id="identity" name="identity" maxlength="191" autocomplete="username" required autofocus value="<?= e($identity) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" maxlength="72" autocomplete="current-password" required>
                </div>
                <button class="btn btn-auth w-100" type="submit">Masuk</button>
            </form>
            <p class="auth-switch">Belum memiliki akun? <a href="<?= e(app_base_url()) ?>/auth/register.php">Daftar sebagai pemilih</a></p>
        </div>
    </section>
</main>
</body>
</html>
