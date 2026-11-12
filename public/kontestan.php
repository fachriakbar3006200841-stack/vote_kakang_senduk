<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/public_data.php';

$category = $_GET['kategori'] ?? 'Semua';
if (!is_string($category) || !in_array($category, ['Semua', 'Kakang', 'Senduk'], true)) {
    http_response_code(400);
    exit('Filter kategori tidak valid.');
}

try {
    $pdo = public_database();
    $period = current_public_period($pdo);
    $contestants = $period === null
        ? []
        : active_contestants($pdo, (int) $period['id'], $category === 'Semua' ? null : $category);
} catch (Throwable $exception) {
    fail_public_page($exception);
}

$pageTitle = 'Daftar Kontestan';
require __DIR__ . '/../includes/public_header.php';
?>
<main>
    <section class="page-banner">
        <div class="container">
            <p class="eyebrow">Kenali para finalis</p>
            <h1>Kontestan Kakang Senduk</h1>
            <p>Temukan generasi muda yang membawa potensi, karakter, dan kebanggaan Ponorogo.</p>
        </div>
    </section>
    <section class="section-space">
        <div class="container">
            <?php if ($period !== null): ?>
                <div class="text-center mb-4">
                    <span class="period-state">Periode aktif</span>
                    <h2 class="period-title"><?= e((string) $period['nama_periode']) ?></h2>
                </div>
                <nav class="filter-bar" aria-label="Filter kategori kontestan">
                    <?php foreach (['Semua', 'Kakang', 'Senduk'] as $filter): ?>
                        <a class="filter-link<?= $category === $filter ? ' active' : '' ?>"
                           href="<?= e(app_base_url()) ?>/public/kontestan.php?kategori=<?= rawurlencode($filter) ?>"
                           <?= $category === $filter ? 'aria-current="page"' : '' ?>><?= e($filter) ?></a>
                    <?php endforeach; ?>
                </nav>

                <?php if ($contestants === []): ?>
                    <div class="empty-state">
                        <strong>Belum ada kontestan untuk ditampilkan</strong>
                        Belum tersedia kontestan aktif<?= $category !== 'Semua' ? ' dalam kategori ' . e($category) : '' ?> pada periode ini.
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($contestants as $contestant): ?>
                            <div class="col-12 col-sm-6 col-lg-4">
                                <article class="contestant-card">
                                    <img class="contestant-photo"
                                         src="<?= e(public_photo_url($contestant['foto'])) ?>"
                                         alt="Foto <?= e($contestant['nama']) ?>"
                                         loading="lazy">
                                    <div class="contestant-body">
                                        <span class="contestant-number">Nomor <?= e($contestant['nomor_peserta']) ?></span>
                                        <h3><?= e($contestant['nama']) ?></h3>
                                        <span class="category-pill"><?= e($contestant['kategori']) ?></span>
                                        <?php if (trim((string) $contestant['deskripsi']) !== ''): ?>
                                            <p class="profile-copy mt-3 mb-3"><?= e($contestant['deskripsi']) ?></p>
                                        <?php endif; ?>
                                        <a class="btn btn-navy btn-sm mt-3"
                                           href="<?= e(app_base_url()) ?>/public/detail-kontestan.php?id=<?= (int) $contestant['id'] ?>">
                                            Lihat profil
                                        </a>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <strong>Belum ada periode pemilihan yang berlangsung</strong>
                    Daftar kontestan akan tersedia saat periode voting aktif.
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
