<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';

if (current_user() !== null) {
    redirect_to_role_home((string) current_user()['role']);
}

$errors = [];
$old = ['nama' => '', 'username' => '', 'email' => ''];
$generalError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $generalError = 'Sesi formulir tidak valid atau telah kedaluwarsa. Muat ulang halaman lalu coba kembali.';
    } else {
        $old['nama'] = trim((string) ($_POST['nama'] ?? ''));
        $old['username'] = trim((string) ($_POST['username'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($old['nama'] === '' || strlen($old['nama']) > 150) {
            $errors['nama'] = 'Nama wajib diisi dan maksimal 150 karakter.';
        }
        if (!preg_match('/\A[a-zA-Z0-9._-]{3,50}\z/', $old['username'])) {
            $errors['username'] = 'Username harus 3–50 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 191) {
            $errors['email'] = 'Masukkan alamat email yang valid.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'Password harus terdiri dari 8–72 byte.';
        }
        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak cocok.';
        }

        if ($errors === []) {
            try {
                $pdo = require __DIR__ . '/../config/database.php';
                $check = $pdo->prepare(
                    'SELECT id FROM users
                     WHERE username = :username OR email = :email
                     LIMIT 1'
                );
                $check->execute([
                    'username' => $old['username'],
                    'email' => $old['email'],
                ]);

                if ($check->fetch()) {
                    $generalError = 'Username atau email sudah digunakan.';
                } else {
                    $insert = $pdo->prepare(
                        'INSERT INTO users (nama, username, email, password, role, status)
                         VALUES (:nama, :username, :email, :password, :role, :status)'
                    );
                    $insert->execute([
                        'nama' => $old['nama'],
                        'username' => $old['username'],
                        'email' => $old['email'],
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => 'pemilih',
                        'status' => 'aktif',
                    ]);

                    set_flash('success', 'Pendaftaran berhasil. Silakan masuk menggunakan akun Anda.');
                    header('Location: ' . app_base_url() . '/auth/login.php');
                    exit;
                }
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $generalError = 'Username atau email sudah digunakan.';
                } else {
                    error_log('Registration failed: ' . $exception->getMessage());
                    http_response_code(500);
                    $generalError = 'Pendaftaran belum dapat diproses. Silakan coba kembali.';
                }
            } catch (Throwable $exception) {
                error_log('Registration failed: ' . $exception->getMessage());
                http_response_code(500);
                $generalError = 'Pendaftaran belum dapat diproses. Silakan coba kembali.';
            }
        }
    }
}

$successMessage = get_flash('success');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Akun | Kakang Senduk Ponorogo</title>
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
        <div class="auth-form-wrap">
            <a class="back-link" href="<?= e(app_base_url()) ?>/index.php">← Kembali ke beranda</a>
            <p class="form-kicker">Bergabung bersama kami</p>
            <h2>Buat akun pemilih</h2>
            <p class="form-intro">Daftarkan akun untuk berpartisipasi dalam pemilihan.</p>

            <?php if ($successMessage !== null): ?>
                <div class="alert alert-success" role="status"><?= e($successMessage) ?></div>
            <?php endif; ?>
            <?php if ($generalError !== null): ?>
                <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
            <?php endif; ?>

            <form method="post" action="" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="nama">Nama lengkap</label>
                    <input class="form-control<?= isset($errors['nama']) ? ' is-invalid' : '' ?>" id="nama" name="nama" maxlength="150" autocomplete="name" required value="<?= e($old['nama']) ?>">
                    <?php if (isset($errors['nama'])): ?><div class="invalid-feedback"><?= e($errors['nama']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control<?= isset($errors['username']) ? ' is-invalid' : '' ?>" id="username" name="username" maxlength="50" autocomplete="username" required value="<?= e($old['username']) ?>">
                    <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" type="email" id="email" name="email" maxlength="191" autocomplete="email" required value="<?= e($old['email']) ?>">
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Konfirmasi password</label>
                    <input class="form-control<?= isset($errors['password_confirmation']) ? ' is-invalid' : '' ?>" type="password" id="password_confirmation" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
                    <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                </div>
                <button class="btn btn-auth w-100" type="submit">Daftar sebagai pemilih</button>
            </form>
            <p class="auth-switch">Sudah memiliki akun? <a href="<?= e(app_base_url()) ?>/auth/login.php">Masuk</a></p>
        </div>
    </section>
</main>
</body>
</html>
