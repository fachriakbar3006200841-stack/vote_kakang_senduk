<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/public_data.php';

$rawId = $_GET['id'] ?? null;
if (!is_string($rawId) || !preg_match('/\A[1-9][0-9]*\z/', $rawId)) {
    http_response_code(404);
    $contestant = null;
} else {
    try {
        $pdo = public_database();
        $statement = $pdo->prepare(
            "SELECT k.id, k.periode_id, k.nomor_peserta, k.nama, k.kategori, k.foto, k.deskripsi,
                    p.nama_periode, p.tanggal_mulai, p.tanggal_selesai
             FROM kontestan AS k
             INNER JOIN periode_voting AS p ON p.id = k.periode_id
             WHERE k.id = :id
               AND k.status = 'aktif'
               AND p.status = 'aktif'
               AND NOW() BETWEEN p.tanggal_mulai AND p.tanggal_selesai
             LIMIT 1"
        );
        $statement->execute(['id' => $rawId]);
        $contestant = $statement->fetch() ?: null;
        if ($contestant === null) {
            http_response_code(404);
        }
    } catch (Throwable $exception) {
        fail_public_page($exception);
    }
}

if ($contestant === null) {
    $pageTitle = 'Kontestan Tidak Ditemukan';
    require __DIR__ . '/../includes/public_header.php';
    ?>
    <main class="section-space">
        <div class="container">
            <div class="empty-state">
                <strong>Profil kontestan tidak ditemukan</strong>
                Kontestan mungkin tidak aktif, belum tersedia, atau alamat profil tidak valid.
                <div><a class="btn btn-navy btn-sm mt-3" href="<?= e(app_base_url()) ?>/public/kontestan.php">Kembali ke daftar kontestan</a></div>
            </div>
        </div>
    </main>
    <?php
    require __DIR__ . '/../includes/public_footer.php';
    exit;
}

$pageTitle = 'Profil ' . $contestant['nama'];
require __DIR__ . '/../includes/public_header.php';
?>
<main class="section-space">
    <div class="container">
        <a class="back-to-list" href="<?= e(app_base_url()) ?>/public/kontestan.php">← Kembali ke daftar kontestan</a>
        <article class="detail-panel mt-3">
            <div class="row g-0">
                <div class="col-12 col-lg-5">
                    <img class="detail-photo h-100"
                         src="<?= e(public_photo_url($contestant['foto'])) ?>"
                         alt="Foto <?= e($contestant['nama']) ?>">
                </div>
                <div class="col-12 col-lg-7">
                    <div class="detail-content">
                        <span class="detail-number">Nomor <?= e($contestant['nomor_peserta']) ?></span>
                        <h1><?= e($contestant['nama']) ?></h1>
                        <span class="category-pill"><?= e($contestant['kategori']) ?></span>
                        <div class="detail-period">
                            <span class="eyebrow">Periode pemilihan</span>
                            <p><?= e($contestant['nama_periode']) ?></p>
                            <small><?= e(format_public_date($contestant['tanggal_mulai'])) ?> – <?= e(format_public_date($contestant['tanggal_selesai'])) ?></small>
                        </div>
                        <div class="detail-description">
                            <h2>Profil kontestan</h2>
                            <?php if (trim((string) $contestant['deskripsi']) !== ''): ?>
                                <p><?= e($contestant['deskripsi']) ?></p>
                            <?php else: ?>
                                <p>Informasi profil belum tersedia.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </div>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
