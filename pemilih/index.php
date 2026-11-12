<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['pemilih']);

$pdo = require __DIR__ . '/../config/database.php';
$successMessage = get_flash('success');
$period = $pdo->query(
    "SELECT id, nama_periode, tanggal_mulai, tanggal_selesai
     FROM periode_voting
     WHERE status = 'aktif'
       AND NOW() BETWEEN tanggal_mulai AND tanggal_selesai
     ORDER BY tanggal_mulai DESC, id DESC
     LIMIT 1"
)->fetch();

$userVotes = [];
if ($period !== false) {
    $voteStatement = $pdo->prepare(
        'SELECT kategori FROM voting WHERE user_id = :user_id AND periode_id = :period_id'
    );
    $voteStatement->execute([
        'user_id' => (int) current_user()['id'],
        'period_id' => (int) $period['id'],
    ]);
    $userVotes = $voteStatement->fetchAll(PDO::FETCH_COLUMN, 0);
}

$pageTitle = 'Dashboard Pemilih';
require __DIR__ . '/../includes/public_header.php';
?>
<main class="section-space">
    <div class="container">
        <?php if ($successMessage !== null): ?><div class="alert alert-success" role="status"><?= e($successMessage) ?></div><?php endif; ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <p class="eyebrow">Panel pemilih</p>
                <h1>Halo, <?= e((string) current_user()['nama']) ?></h1>
            </div>
            <a class="btn btn-gold" href="<?= e(app_base_url()) ?>/pemilih/vote.php">Lanjutkan voting</a>
        </div>

        <?php if ($period === false): ?>
            <div class="empty-state">
                <strong>Periode voting belum aktif</strong>
                Saat ini belum ada periode pemilihan yang berlangsung. Tunggu informasi resmi dari panitia.
            </div>
        <?php else: ?>
            <div class="period-card mb-4">
                <div>
                    <span class="period-state">Periode aktif</span>
                    <h3><?= e((string) $period['nama_periode']) ?></h3>
                    <div class="period-dates"><?= e(format_public_date((string) $period['tanggal_mulai'])) ?> – <?= e(format_public_date((string) $period['tanggal_selesai'])) ?></div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="panel-box h-100">
                        <h3>Status voting Anda</h3>
                        <?php if ($userVotes === []): ?>
                            <p class="mb-0 text-muted">Anda belum memberikan suara pada periode ini.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($userVotes as $voteCategory): ?>
                                    <li class="mb-2"><span class="status-badge active"><?= e((string) $voteCategory) ?></span> sudah dipilih.</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="panel-box h-100">
                        <h3>Langkah berikutnya</h3>
                        <p>Masuk ke halaman voting untuk memilih kontestan terbaik di kategori Kakang dan Senduk.</p>
                        <a class="btn btn-navy" href="<?= e(app_base_url()) ?>/pemilih/vote.php">Ke halaman voting</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
