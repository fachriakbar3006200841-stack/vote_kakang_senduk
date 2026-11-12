<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/public_data.php';

try {
    $pdo = public_database();
    $period = current_public_period($pdo);
    $featuredContestants = $period === null ? [] : active_contestants($pdo, (int) $period['id'], null, 3);
} catch (Throwable $exception) {
    fail_public_page($exception);
}

$pageTitle = 'Beranda';
require __DIR__ . '/includes/public_header.php';
?>
<main>
    <section class="hero">
        <div class="hero-mark" aria-hidden="true">P</div>
        <div class="container">
            <div class="hero-content">
                <p class="eyebrow">Duta wisata Kabupaten Ponorogo</p>
                <h1>Pesona Ponorogo,<br>generasi penuh makna.</h1>
                <p class="hero-copy">Mengenal Kakang dan Senduk—generasi muda yang membawa semangat budaya, keramahan, dan potensi wisata Ponorogo.</p>
                <div class="hero-actions">
                    <a class="btn btn-gold btn-lg" href="<?= e(app_base_url()) ?>/public/kontestan.php">Lihat kontestan</a>
                    <?php if (current_user() === null): ?>
                        <a class="btn btn-hero-outline btn-lg" href="<?= e(app_base_url()) ?>/auth/login.php">Masuk untuk memilih</a>
                    <?php else: ?>
                        <span class="btn btn-hero-outline btn-lg disabled">Halo, <?= e((string) current_user()['nama']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space" id="periode">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow">Informasi pemilihan</p>
                <h2>Periode voting</h2>
                <p>Informasi periode ditampilkan dari database dan hanya ketika periode sedang aktif.</p>
            </div>
            <?php if ($period !== null): ?>
                <div class="period-card">
                    <div>
                        <span class="period-state">Sedang berlangsung</span>
                        <h3><?= e($period['nama_periode']) ?></h3>
                        <div class="period-dates"><?= e(format_public_date($period['tanggal_mulai'])) ?> – <?= e(format_public_date($period['tanggal_selesai'])) ?></div>
                    </div>
                    <a class="btn btn-navy" href="<?= e(app_base_url()) ?>/public/kontestan.php">Jelajahi kontestan</a>
                </div>
            <?php else: ?>
                <div class="empty-period">Belum ada periode voting yang sedang berlangsung. Informasi akan diperbarui saat periode resmi dibuka.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="intro-section section-space">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow">Budaya dan potensi daerah</p>
                <h2>Mengenal Kakang Senduk</h2>
            </div>
            <p class="intro-copy">Kakang Senduk merupakan duta muda yang turut memperkenalkan kekayaan budaya dan pariwisata Kabupaten Ponorogo. Berlandaskan semangat Reog, keramahan, dan kreativitas, para finalis menjadi wajah generasi yang bangga pada daerahnya.</p>
        </div>
    </section>

    <section class="section-space">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow">Representasi generasi Ponorogo</p>
                <h2>Kontestan unggulan</h2>
                <p>Kontestan yang ditampilkan berasal dari data aktif pada periode yang sedang berlangsung.</p>
            </div>
            <?php if ($featuredContestants === []): ?>
                <div class="empty-state">
                    <strong>Kontestan segera hadir</strong>
                    Belum ada kontestan aktif pada periode yang sedang berlangsung.
                    <div><a class="btn btn-navy btn-sm mt-3" href="<?= e(app_base_url()) ?>/public/kontestan.php">Lihat daftar kontestan</a></div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($featuredContestants as $contestant): ?>
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
                                        <p class="profile-copy mt-3"><?= e($contestant['deskripsi']) ?></p>
                                    <?php endif; ?>
                                    <a class="btn btn-navy btn-sm mt-2" href="<?= e(app_base_url()) ?>/public/detail-kontestan.php?id=<?= (int) $contestant['id'] ?>">Lihat profil</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-4"><a class="btn btn-navy" href="<?= e(app_base_url()) ?>/public/kontestan.php">Lihat semua kontestan</a></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="intro-section section-space">
        <div class="container">
            <div class="section-heading mb-0">
                <p class="eyebrow">Informasi penting</p>
                <h2>Ikuti informasi resmi</h2>
                <p>Jadwal dan daftar peserta pada halaman ini mengikuti data periode serta kontestan yang aktif. Pastikan kembali informasi kegiatan melalui kanal resmi penyelenggara.</p>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/public_footer.php'; ?>
