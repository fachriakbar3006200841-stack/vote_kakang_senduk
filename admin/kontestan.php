<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['admin']);

$pdo = require __DIR__ . '/../config/database.php';

$search = trim((string) ($_GET['search'] ?? ''));
$category = $_GET['kategori'] ?? 'Semua';
$periodFilter = isset($_GET['periode']) ? (int) $_GET['periode'] : 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

if (!is_string($category) || !in_array($category, ['Semua', 'Kakang', 'Senduk'], true)) {
    http_response_code(400);
    exit('Filter kategori tidak valid.');
}

$adminError = get_flash('admin_error');
$adminSuccess = get_flash('admin_success');
$periods = $pdo->query('SELECT id, nama_periode FROM periode_voting ORDER BY tanggal_mulai DESC')->fetchAll();

$countSql = "SELECT COUNT(*)
             FROM kontestan k
             INNER JOIN periode_voting p ON p.id = k.periode_id
             WHERE 1 = 1";
$countParams = [];

if ($search !== '') {
    $countSql .= ' AND (k.nama LIKE :search OR k.nomor_peserta LIKE :search)';
    $countParams['search'] = '%' . $search . '%';
}

if ($category !== 'Semua') {
    $countSql .= ' AND k.kategori = :category';
    $countParams['category'] = $category;
}

if ($periodFilter > 0) {
    $countSql .= ' AND k.periode_id = :periode_id';
    $countParams['periode_id'] = $periodFilter;
}

$countStatement = $pdo->prepare($countSql);
$countStatement->execute($countParams);
$totalRows = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT k.id, k.nomor_peserta, k.nama, k.kategori, k.status, k.foto, p.nama_periode
        FROM kontestan k
        INNER JOIN periode_voting p ON p.id = k.periode_id
        WHERE 1 = 1";
$params = [];

if ($search !== '') {
    $sql .= ' AND (k.nama LIKE :search OR k.nomor_peserta LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($category !== 'Semua') {
    $sql .= ' AND k.kategori = :category';
    $params['category'] = $category;
}

if ($periodFilter > 0) {
    $sql .= ' AND k.periode_id = :periode_id';
    $params['periode_id'] = $periodFilter;
}

$sql .= ' ORDER BY k.created_at DESC LIMIT :limit OFFSET :offset';
$params['limit'] = $perPage;
$params['offset'] = $offset;

$statement = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$statement->execute();
$contestants = $statement->fetchAll();

$pageTitle = 'Kelola Kontestan';
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
                <a href="<?= e(app_base_url()) ?>/admin/index.php">Dashboard</a>
                <a class="active" href="<?= e(app_base_url()) ?>/admin/kontestan.php">Kontestan</a>
                <a href="<?= e(app_base_url()) ?>/admin/pemilih.php">Pemilih</a>
                <a href="<?= e(app_base_url()) ?>/auth/logout.php">Logout</a>
            </nav>
        </aside>
        <section class="admin-main">
            <div class="admin-topbar">
                <div>
                    <p class="eyebrow">Kelola kompetisi</p>
                    <h1>Kontestan</h1>
                </div>
                <a class="btn btn-gold" href="<?= e(app_base_url()) ?>/admin/kontestan-form.php">Tambah kontestan</a>
            </div>

            <?php if ($adminError !== null): ?><div class="alert alert-danger mt-3"><?= e($adminError) ?></div><?php endif; ?>
            <?php if ($adminSuccess !== null): ?><div class="alert alert-success mt-3"><?= e($adminSuccess) ?></div><?php endif; ?>

            <div class="panel-box mt-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="search">Pencarian nama / nomor</label>
                        <input class="form-control" id="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama atau nomor peserta">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="kategori">Filter kategori</label>
                        <select class="form-select" id="kategori" name="kategori">
                            <option value="Semua" <?= $category === 'Semua' ? 'selected' : '' ?>>Semua</option>
                            <option value="Kakang" <?= $category === 'Kakang' ? 'selected' : '' ?>>Kakang</option>
                            <option value="Senduk" <?= $category === 'Senduk' ? 'selected' : '' ?>>Senduk</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="periode">Filter periode</label>
                        <select class="form-select" id="periode" name="periode">
                            <option value="0">Semua periode</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= (int) $period['id'] ?>" <?= $periodFilter === (int) $period['id'] ? 'selected' : '' ?>><?= e((string) $period['nama_periode']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn btn-navy w-100" type="submit">Cari</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive panel-box mt-4">
                <table class="table align-middle table-admin">
                    <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nomor</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($contestants === []): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada data kontestan pada filter ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contestants as $contestant): ?>
                            <tr>
                                <td>
                                    <img class="table-thumb" src="<?= e(public_photo_url($contestant['foto'])) ?>" alt="Foto <?= e($contestant['nama']) ?>">
                                </td>
                                <td><?= e((string) $contestant['nomor_peserta']) ?></td>
                                <td><?= e((string) $contestant['nama']) ?></td>
                                <td><?= e((string) $contestant['kategori']) ?></td>
                                <td><?= e((string) $contestant['nama_periode']) ?></td>
                                <td>
                                    <span class="status-badge <?= $contestant['status'] === 'aktif' ? 'active' : 'inactive' ?>"><?= e((string) $contestant['status']) ?></span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(app_base_url()) ?>/admin/kontestan-form.php?id=<?= (int) $contestant['id'] ?>">Edit</a>
                                        <form method="post" action="<?= e(app_base_url()) ?>/admin/kontestan-toggle.php" onsubmit="return confirm('Ubah status kontestan ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $contestant['id'] ?>">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $contestant['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalRows > $perPage): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(app_base_url()) ?>/admin/kontestan.php?search=<?= rawurlencode($search) ?>&kategori=<?= rawurlencode($category) ?>&periode=<?= (int) $periodFilter ?>&page=<?= max(1, $page - 1) ?>">Sebelumnya</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(app_base_url()) ?>/admin/kontestan.php?search=<?= rawurlencode($search) ?>&kategori=<?= rawurlencode($category) ?>&periode=<?= (int) $periodFilter ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(app_base_url()) ?>/admin/kontestan.php?search=<?= rawurlencode($search) ?>&kategori=<?= rawurlencode($category) ?>&periode=<?= (int) $periodFilter ?>&page=<?= min($totalPages, $page + 1) ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
