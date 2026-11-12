<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['pemilih']);

$pdo = require __DIR__ . '/../config/database.php';

$period = $pdo->query(
    "SELECT id, nama_periode, tanggal_mulai, tanggal_selesai
     FROM periode_voting
     WHERE status = 'aktif'
       AND NOW() BETWEEN tanggal_mulai AND tanggal_selesai
     ORDER BY tanggal_mulai DESC, id DESC
     LIMIT 1"
)->fetch();

if ($period === false) {
    http_response_code(403);
    $pageTitle = 'Voting Tidak Tersedia';
    require __DIR__ . '/../includes/public_header.php';
    ?>
    <main class="section-space">
        <div class="container">
            <div class="empty-state">
                <strong>Voting belum dibuka</strong>
                Saat ini periode pemilihan belum aktif. Silakan tunggu informasi resmi panitia.
                <div><a class="btn btn-navy btn-sm mt-3" href="<?= e(app_base_url()) ?>/pemilih/index.php">Kembali ke dashboard</a></div>
            </div>
        </div>
    </main>
    <?php
    require __DIR__ . '/../includes/public_footer.php';
    exit;
}

$periodId = (int) $period['id'];
$categories = ['Kakang', 'Senduk'];
$contestantsByCategory = [];
foreach ($categories as $category) {
    $statement = $pdo->prepare(
        'SELECT id, nomor_peserta, nama, kategori, foto, deskripsi
         FROM kontestan
         WHERE periode_id = :period_id
           AND kategori = :kategori
           AND status = :status
         ORDER BY CAST(nomor_peserta AS UNSIGNED), nomor_peserta ASC'
    );
    $statement->execute([
        'period_id' => $periodId,
        'kategori' => $category,
        'status' => 'aktif',
    ]);
    $contestantsByCategory[$category] = $statement->fetchAll();
}

$voteStatus = [];
$voteStatement = $pdo->prepare(
    'SELECT kategori, kontestan_id FROM voting WHERE user_id = :user_id AND periode_id = :period_id'
);
$voteStatement->execute([
    'user_id' => (int) current_user()['id'],
    'period_id' => $periodId,
]);
foreach ($voteStatement->fetchAll() as $vote) {
    $voteStatus[(string) $vote['kategori']] = (int) $vote['kontestan_id'];
}

$errors = [];
$successMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi formulir tidak valid atau sudah kedaluwarsa.';
    } else {
        $selectedVotes = [];
        foreach ($categories as $category) {
            $selectedId = isset($_POST['vote_' . strtolower($category)]) ? (int) $_POST['vote_' . strtolower($category)] : 0;
            if ($selectedId <= 0) {
                $errors[] = 'Pilih kontestan untuk kategori ' . $category . '.';
                continue;
            }

            $check = $pdo->prepare(
                'SELECT id, kategori, periode_id, status
                 FROM kontestan
                 WHERE id = :id AND periode_id = :period_id AND kategori = :kategori AND status = :status
                 LIMIT 1'
            );
            $check->execute([
                'id' => $selectedId,
                'period_id' => $periodId,
                'kategori' => $category,
                'status' => 'aktif',
            ]);

            $contestant = $check->fetch();
            if ($contestant === false) {
                $errors[] = 'Pilihan kontestan untuk kategori ' . $category . ' tidak valid.';
                continue;
            }

            $selectedVotes[$category] = $selectedId;
        }

        if ($errors === []) {
            try {
                $pdo->beginTransaction();
                foreach ($selectedVotes as $category => $contestantId) {
                    $alreadyVoted = $pdo->prepare(
                        'SELECT id FROM voting WHERE user_id = :user_id AND periode_id = :period_id AND kategori = :kategori LIMIT 1'
                    );
                    $alreadyVoted->execute([
                        'user_id' => (int) current_user()['id'],
                        'period_id' => $periodId,
                        'kategori' => $category,
                    ]);

                    if ($alreadyVoted->fetch() !== false) {
                        throw new RuntimeException('Anda sudah memilih kategori ' . $category . ' pada periode ini.');
                    }

                    $insert = $pdo->prepare(
                        'INSERT INTO voting (user_id, periode_id, kontestan_id, kategori)
                         VALUES (:user_id, :period_id, :kontestan_id, :kategori)'
                    );
                    $insert->execute([
                        'user_id' => (int) current_user()['id'],
                        'period_id' => $periodId,
                        'kontestan_id' => $contestantId,
                        'kategori' => $category,
                    ]);
                }

                $pdo->commit();
                set_flash('success', 'Suara Anda berhasil disimpan.');
                header('Location: ' . app_base_url() . '/pemilih/index.php');
                exit;
            } catch (Throwable $exception) {
                $pdo->rollBack();
                $errors[] = $exception->getMessage();
            }
        }
    }
}

$pageTitle = 'Voting Pemilih';
require __DIR__ . '/../includes/public_header.php';
?>
<main class="section-space">
    <div class="container">
        <a class="back-to-list" href="<?= e(app_base_url()) ?>/pemilih/index.php">← Kembali ke dashboard</a>
        <div class="mt-3">
            <p class="eyebrow">Pemilihan</p>
            <h1>Form voting</h1>
        </div>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger mt-3" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= e((string) $error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="panel-box mt-4">
            <p class="mb-3"><strong>Periode:</strong> <?= e((string) $period['nama_periode']) ?></p>
            <form method="post" action="">
                <?= csrf_field() ?>
                <?php foreach ($categories as $category): ?>
                    <div class="mb-4 border rounded p-3">
                        <h3 class="h5 mb-3"><?= e($category) ?></h3>
                        <?php if (isset($voteStatus[$category])): ?>
                            <div class="alert alert-success mb-3" role="status">Anda sudah memilih kategori <?= e($category) ?> pada periode ini.</div>
                        <?php elseif ($contestantsByCategory[$category] === []): ?>
                            <div class="alert alert-secondary mb-0" role="alert">Belum ada kontestan aktif untuk kategori <?= e($category) ?>.</div>
                        <?php else: ?>
                            <select class="form-select" name="vote_<?= strtolower($category) ?>" aria-label="Pilih <?= e($category) ?>">
                                <option value="0">-- Pilih kontestan <?= e($category) ?> --</option>
                                <?php foreach ($contestantsByCategory[$category] as $contestant): ?>
                                    <option value="<?= (int) $contestant['id'] ?>"><?= e((string) $contestant['nomor_peserta']) ?> - <?= e((string) $contestant['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php $remainingCategories = array_filter($categories, static fn (string $category): bool => !isset($voteStatus[$category])); ?>
                <?php if ($remainingCategories === []): ?>
                    <button type="submit" class="btn btn-gold" disabled>Suara sudah diproses</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-gold">Simpan suara</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
