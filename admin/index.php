<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['admin']);

$pdo = require __DIR__ . '/../config/database.php';

$contestantCount = (int) $pdo->query("SELECT COUNT(*) FROM kontestan")->fetchColumn();
$activeContestantCount = (int) $pdo->query("SELECT COUNT(*) FROM kontestan WHERE status = 'aktif'")->fetchColumn();
$periodCount = (int) $pdo->query("SELECT COUNT(*) FROM periode_voting")->fetchColumn();
$userCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pemilih'")->fetchColumn();

$pageTitle = 'Dashboard Admin';
require __DIR__ . '/../includes/public_header.php';
?>
<main class="admin-shell">
    <div class="container-fluid admin-wrap">
        <aside class="admin-sidebar">
            <div class="brand-box">
                <span class="brand-seal">KS</span>
                <div>
                    <strong>Kakang Senduk</strong>
                    <small>Admin Panel</small>
                </div>
            </div>
            <nav class="admin-nav">
                <a class="active" href="<?= e(app_base_url()) ?>/admin/index.php">Dashboard</a>
                <a href="<?= e(app_base_url()) ?>/admin/kontestan.php">Kontestan</a>
                <a href="<?= e(app_base_url()) ?>/admin/pemilih.php">Pemilih</a>
                <a href="<?= e(app_base_url()) ?>/auth/logout.php">Logout</a>
            </nav>
        </aside>
        <section class="admin-main">
            <div class="admin-topbar">
                <div>
                    <p class="eyebrow">Panel administrator</p>
                    <h1>Dashboard</h1>
                </div>
                <div class="user-badge"><?= e((string) current_user()['nama']) ?></div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card navy">
                        <span>Total Kontestan</span>
                        <strong><?= $contestantCount ?></strong>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card gold">
                        <span>Aktif</span>
                        <strong><?= $activeContestantCount ?></strong>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card gray">
                        <span>Periode</span>
                        <strong><?= $periodCount ?></strong>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="stat-card navy">
                        <span>Pemilih</span>
                        <strong><?= $userCount ?></strong>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
