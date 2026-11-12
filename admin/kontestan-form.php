<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_login(['admin']);

$pdo = require __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$mode = $id > 0 ? 'edit' : 'create';

$periods = $pdo->query('SELECT id, nama_periode FROM periode_voting ORDER BY tanggal_mulai DESC')->fetchAll();
$record = null;
if ($mode === 'edit') {
    $statement = $pdo->prepare('SELECT * FROM kontestan WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $record = $statement->fetch();
    if ($record === false) {
        http_response_code(404);
        exit('Kontestan tidak ditemukan.');
    }
}

$errors = [];
$old = [
    'periode_id' => (string) (($record['periode_id'] ?? '') ?: ''),
    'nomor_peserta' => (string) (($record['nomor_peserta'] ?? '') ?: ''),
    'nama' => (string) (($record['nama'] ?? '') ?: ''),
    'kategori' => (string) (($record['kategori'] ?? 'Kakang') ?: 'Kakang'),
    'status' => (string) (($record['status'] ?? 'aktif') ?: 'aktif'),
    'foto' => (string) (($record['foto'] ?? '') ?: ''),
    'deskripsi' => (string) (($record['deskripsi'] ?? '') ?: ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errors['csrf'] = 'Sesi formulir tidak valid.';
    } else {
        $old['periode_id'] = trim((string) ($_POST['periode_id'] ?? ''));
        $old['nomor_peserta'] = trim((string) ($_POST['nomor_peserta'] ?? ''));
        $old['nama'] = trim((string) ($_POST['nama'] ?? ''));
        $old['kategori'] = trim((string) ($_POST['kategori'] ?? 'Kakang'));
        $old['status'] = trim((string) ($_POST['status'] ?? 'aktif'));
        $old['deskripsi'] = trim((string) ($_POST['deskripsi'] ?? ''));

        if (!preg_match('/\A[1-9][0-9]*\z/', $old['periode_id'])) {
            $errors['periode_id'] = 'Periode voting wajib dipilih.';
        }
        if ($old['nomor_peserta'] === '' || strlen($old['nomor_peserta']) > 20 || !preg_match('/\A[0-9A-Za-z.-]+\z/', $old['nomor_peserta'])) {
            $errors['nomor_peserta'] = 'Nomor peserta tidak valid. Gunakan kombinasi angka/huruf, titik, atau tanda hubung.';
        }
        if ($old['nama'] === '' || strlen($old['nama']) > 150) {
            $errors['nama'] = 'Nama kontestan wajib diisi dan maksimal 150 karakter.';
        }
        if (!in_array($old['kategori'], ['Kakang', 'Senduk'], true)) {
            $errors['kategori'] = 'Kategori tidak valid.';
        }
        if (!in_array($old['status'], ['aktif', 'nonaktif'], true)) {
            $errors['status'] = 'Status tidak valid.';
        }

        $periodExists = $pdo->prepare('SELECT id FROM periode_voting WHERE id = :id LIMIT 1');
        $periodExists->execute(['id' => (int) $old['periode_id']]);
        if ($periodExists->fetch() === false) {
            $errors['periode_id'] = 'Periode voting tidak ditemukan.';
        }

        $duplicateCheck = $pdo->prepare(
            'SELECT id FROM kontestan WHERE periode_id = :periode_id AND nomer_peserta = :nomor_peserta AND kategori = :kategori AND id <> :id LIMIT 1'
        );
        $duplicateCheck->execute([
            'periode_id' => (int) $old['periode_id'],
            'nomor_peserta' => $old['nomor_peserta'],
            'kategori' => $old['kategori'],
            'id' => $id,
        ]);
        if ($duplicateCheck->fetch() !== false) {
            $errors['nomor_peserta'] = 'Nomor peserta sudah dipakai pada periode dan kategori yang sama.';
        }

        $newPhotoPath = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['foto'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['foto'] = 'Upload foto gagal. Silakan coba lagi.';
            } else {
                $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $extension = $allowedMime[$mime] ?? null;
                if ($extension === null) {
                    $errors['foto'] = 'Format foto tidak valid. Gunakan JPG, PNG, atau WEBP.';
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $errors['foto'] = 'Ukuran foto maksimal 2 MB.';
                } else {
                    $tmpName = $file['tmp_name'];
                    $imageData = @getimagesize($tmpName);
                    if ($imageData === false) {
                        $errors['foto'] = 'File yang diunggah bukan gambar valid.';
                    } else {
                        $newName = uniqid('contestant_', true) . '.' . $extension;
                        $target = __DIR__ . '/../uploads/' . $newName;
                        if (!move_uploaded_file($tmpName, $target)) {
                            $errors['foto'] = 'Gagal menyimpan foto di direktori upload.';
                        } else {
                            $newPhotoPath = $newName;
                        }
                    }
                }
            }
        }

        if ($errors === []) {
            try {
                $pdo->beginTransaction();
                if ($mode === 'edit') {
                    $sql = 'UPDATE kontestan SET periode_id = :periode_id, nomor_peserta = :nomor_peserta, nama = :nama, kategori = :kategori, deskripsi = :deskripsi, status = :status';
                    $params = [
                        'periode_id' => (int) $old['periode_id'],
                        'nomor_peserta' => $old['nomor_peserta'],
                        'nama' => $old['nama'],
                        'kategori' => $old['kategori'],
                        'deskripsi' => $old['deskripsi'],
                        'status' => $old['status'],
                        'id' => $id,
                    ];
                    if ($newPhotoPath !== null) {
                        $sql .= ', foto = :foto';
                        $params['foto'] = $newPhotoPath;
                    }
                    $sql .= ' WHERE id = :id';
                    $statement = $pdo->prepare($sql);
                    $statement->execute($params);

                    if ($newPhotoPath !== null && !empty($record['foto'])) {
                        $oldPhoto = __DIR__ . '/../uploads/' . $record['foto'];
                        if (is_file($oldPhoto)) {
                            @unlink($oldPhoto);
                        }
                    }
                } else {
                    $sql = 'INSERT INTO kontestan (periode_id, nomor_peserta, nama, kategori, foto, deskripsi, status)
                            VALUES (:periode_id, :nomor_peserta, :nama, :kategori, :foto, :deskripsi, :status)';
                    $statement = $pdo->prepare($sql);
                    $statement->execute([
                        'periode_id' => (int) $old['periode_id'],
                        'nomor_peserta' => $old['nomor_peserta'],
                        'nama' => $old['nama'],
                        'kategori' => $old['kategori'],
                        'foto' => $newPhotoPath ?? null,
                        'deskripsi' => $old['deskripsi'],
                        'status' => $old['status'],
                    ]);
                }

                $pdo->commit();
                set_flash('admin_success', $mode === 'edit' ? 'Kontestan berhasil diperbarui.' : 'Kontestan berhasil ditambahkan.');
                header('Location: ' . app_base_url() . '/admin/kontestan.php');
                exit;
            } catch (Throwable $exception) {
                $pdo->rollBack();
                error_log('Contest save failed: ' . $exception->getMessage());
                $errors['database'] = 'Data tidak dapat disimpan. Silakan coba lagi.';
            }
        }
    }
}

$pageTitle = $mode === 'edit' ? 'Edit Kontestan' : 'Tambah Kontestan';
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
                    <h1><?= $mode === 'edit' ? 'Edit Kontestan' : 'Tambah Kontestan' ?></h1>
                </div>
                <a class="btn btn-nav-outline" href="<?= e(app_base_url()) ?>/admin/kontestan.php">Kembali</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mt-4">
                    <?php foreach ($errors as $errorMessage): ?>
                        <div><?= e((string) $errorMessage) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="panel-box mt-4">
                <form method="post" enctype="multipart/form-data" class="row g-3">
                    <?= csrf_field() ?>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="periode_id">Periode voting</label>
                        <select class="form-select" id="periode_id" name="periode_id">
                            <option value="">Pilih periode</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= (int) $period['id'] ?>" <?= (string) $period['id'] === $old['periode_id'] ? 'selected' : '' ?>><?= e((string) $period['nama_periode']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['periode_id'])): ?><div class="invalid-text"><?= e($errors['periode_id']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="nomor_peserta">Nomor peserta</label>
                        <input class="form-control" id="nomor_peserta" name="nomor_peserta" value="<?= e($old['nomor_peserta']) ?>">
                        <?php if (isset($errors['nomor_peserta'])): ?><div class="invalid-text"><?= e($errors['nomor_peserta']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="nama">Nama lengkap</label>
                        <input class="form-control" id="nama" name="nama" value="<?= e($old['nama']) ?>">
                        <?php if (isset($errors['nama'])): ?><div class="invalid-text"><?= e($errors['nama']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="kategori">Kategori</label>
                        <select class="form-select" id="kategori" name="kategori">
                            <option value="Kakang" <?= $old['kategori'] === 'Kakang' ? 'selected' : '' ?>>Kakang</option>
                            <option value="Senduk" <?= $old['kategori'] === 'Senduk' ? 'selected' : '' ?>>Senduk</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="aktif" <?= $old['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $old['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="deskripsi">Deskripsi / profil</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5"><?= e($old['deskripsi']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="foto">Foto kontestan</label>
                        <input class="form-control" type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <?php if (isset($errors['foto'])): ?><div class="invalid-text"><?= e($errors['foto']) ?></div><?php endif; ?>
                        <?php if ($mode === 'edit' && $old['foto'] !== ''): ?>
                            <div class="mt-2">
                                <img class="table-thumb" src="<?= e(public_photo_url($old['foto'])) ?>" alt="Foto saat ini">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-navy"><?= $mode === 'edit' ? 'Simpan perubahan' : 'Tambah kontestan' ?></button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
