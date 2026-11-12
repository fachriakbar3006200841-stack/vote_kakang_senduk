<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';

function public_database(): PDO
{
    try {
        return require __DIR__ . '/../config/database.php';
    } catch (Throwable $exception) {
        error_log('Public page database connection failed: ' . $exception->getMessage());
        http_response_code(503);
        exit('Informasi saat ini belum dapat dimuat. Silakan coba kembali.');
    }
}

function current_public_period(PDO $pdo): ?array
{
    $statement = $pdo->query(
        "SELECT id, nama_periode, tanggal_mulai, tanggal_selesai
         FROM periode_voting
         WHERE status = 'aktif'
           AND NOW() BETWEEN tanggal_mulai AND tanggal_selesai
         ORDER BY tanggal_mulai DESC, id DESC
         LIMIT 1"
    );
    $period = $statement->fetch();

    return $period ?: null;
}

function active_contestants(PDO $pdo, int $periodId, ?string $category = null, int $limit = 0): array
{
    $sql = "SELECT k.id, k.periode_id, k.nomor_peserta, k.nama, k.kategori, k.foto, k.deskripsi,
                   p.nama_periode
            FROM kontestan AS k
            INNER JOIN periode_voting AS p ON p.id = k.periode_id
            WHERE k.periode_id = :period_id
              AND k.status = 'aktif'
              AND p.status = 'aktif'
              AND NOW() BETWEEN p.tanggal_mulai AND p.tanggal_selesai";
    $params = ['period_id' => $periodId];

    if ($category !== null) {
        $sql .= ' AND k.kategori = :category';
        $params['category'] = $category;
    }

    $sql .= ' ORDER BY k.kategori ASC, k.nomor_peserta ASC, k.nama ASC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }

    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function fail_public_page(Throwable $exception): never
{
    error_log('Public page query failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Informasi saat ini belum dapat dimuat. Silakan coba kembali.');
}

function public_photo_url(?string $photo): string
{
    $placeholder = app_base_url() . '/assets/img/contestant-placeholder.svg';
    if ($photo === null || trim($photo) === '') {
        return $placeholder;
    }

    $normalized = str_replace('\\', '/', trim($photo));
    if (str_contains($normalized, '..') || str_starts_with($normalized, '/')) {
        return $placeholder;
    }

    $filename = basename($normalized);
    if (!preg_match('/\A[a-zA-Z0-9._-]+\.(?:jpe?g|png|webp)\z/i', $filename)) {
        return $placeholder;
    }

    $filePath = dirname(__DIR__) . '/uploads/' . $filename;
    if (!is_file($filePath)) {
        return $placeholder;
    }

    return app_base_url() . '/uploads/' . rawurlencode($filename);
}

function format_public_date(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp === false ? '' : date('d M Y, H:i', $timestamp);
}
