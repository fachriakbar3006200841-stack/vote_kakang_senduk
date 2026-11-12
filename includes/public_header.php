<?php
$currentUser = current_user();
$siteTitle = $pageTitle ?? 'Kakang Senduk Ponorogo';
$baseUrl = app_base_url();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102b4e">
    <title><?= e($siteTitle) ?> | Kakang Senduk Ponorogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e($baseUrl) ?>/assets/css/public.css" rel="stylesheet">
    <link href="<?= e($baseUrl) ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg site-nav">
    <div class="container">
        <a class="navbar-brand brand-lockup" href="<?= e($baseUrl) ?>/index.php">
            <span class="brand-seal" aria-hidden="true">KS</span>
            <span>Kakang <b>Senduk</b><small>PONOROGO</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                <a class="nav-link" href="<?= e($baseUrl) ?>/index.php">Beranda</a>
                <a class="nav-link" href="<?= e($baseUrl) ?>/public/kontestan.php">Kontestan</a>
                <?php if ($currentUser !== null): ?>
                    <span class="nav-user">Halo, <?= e((string) $currentUser['nama']) ?></span>
                    <form method="post" action="<?= e($baseUrl) ?>/auth/logout.php" class="m-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-nav-outline" type="submit">Keluar</button>
                    </form>
                <?php else: ?>
                    <a class="nav-link" href="<?= e($baseUrl) ?>/auth/login.php">Masuk</a>
                    <a class="btn btn-nav-gold" href="<?= e($baseUrl) ?>/auth/register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
