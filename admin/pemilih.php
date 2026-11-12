<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['admin']);

$pdo = require __DIR__ . '/../config/database.php';

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = $_GET['status'] ?? 'Semua';
if (!is_string($statusFilter) || !in_array($statusFilter, ['Semua', 'aktif', 'nonaktif'], true)) {
    http_response_code(400);
    exit('Filter status tidak valid.');
}

$adminError = get_flash('admin_error');
$adminSuccess = get_flash('admin_success');

$sql = 'SELECT id, nama, username, email, role, status FROM users WHERE role = :role';
$params = ['role' => 'pemilih'];

if ($search !== '') {
    $sql .= ' AND (nama LIKE :search OR username LIKE :search OR email LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($statusFilter !== 'Semua') {
    $sql .= ' AND status = :status';
    $params['status'] = $statusFilter;
}

$sql .= ' ORDER BY created_at DESC';

$statement = $pdo->prepare($sql);
$statement->execute($params);
$users = $statement->fetchAll();

$pageTitle = 'Kelola Pemilih';
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
                <a href="<?= e(app_base_url()) ?>/admin/kontestan.php">Kontestan</a>
                <a class="active" href="<?= e(app_base_url()) ?>/admin/pemilih.php">Pemilih</a>
                <a href="<?= e(app_base_url()) ?>/auth/logout.php">Logout</a>
            </nav>
        </aside>
        <section class="admin-main">
            <div class="admin-topbar">
                <div>
                    <p class="eyebrow">Akun pemilih</p>
                    <h1>Pemilih</h1>
                </div>
            </div>

            <?php if ($adminError !== null): ?><div class="alert alert-danger mt-3"><?= e($adminError) ?></div><?php endif; ?>
            <?php if ($adminSuccess !== null): ?><div class="alert alert-success mt-3"><?= e($adminSuccess) ?></div><?php endif; ?>

            <div class="panel-box mt-4">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="search">Pencarian</label>
                        <input class="form-control" id="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama / username / email">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Semua" <?= $statusFilter === 'Semua' ? 'selected' : '' ?>>Semua</option>
                            <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $statusFilter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
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
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($users === []): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada pemilih pada filter ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= e((string) $user['nama']) ?></td>
                                <td><?= e((string) $user['username']) ?></td>
                                <td><?= e((string) $user['email']) ?></td>
                                <td>
                                    <span class="status-badge <?= $user['status'] === 'aktif' ? 'active' : 'inactive' ?>"><?= e((string) $user['status']) ?></span>
                                </td>
                                <td>
                                    <form method="post" action="<?= e(app_base_url()) ?>/admin/pemilih-toggle.php" onsubmit="return confirm('Ubah status pemilih ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <input type="hidden" name="next_status" value="<?= $user['status'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                                        <button type="submit" class="btn btn-sm <?= $user['status'] === 'aktif' ? 'btn-outline-warning' : 'btn-outline-success' ?>"><?= $user['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
