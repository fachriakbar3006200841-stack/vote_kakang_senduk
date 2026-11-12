<?php
declare(strict_types=1);

try {
    $pdo = require __DIR__ . '/config/database.php';
    $databaseName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $expectedTables = ['users', 'periode_voting', 'kontestan', 'voting'];

    if ($databaseName !== 'db_voting_kakang_senduk') {
        throw new RuntimeException('Unexpected database selected.');
    }

    $placeholders = implode(',', array_fill(0, count($expectedTables), '?'));
    $statement = $pdo->prepare(
        'SELECT TABLE_NAME
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME IN (' . $placeholders . ')'
    );
    $statement->execute($expectedTables);
    $foundTables = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (count($foundTables) !== count($expectedTables)) {
        throw new RuntimeException('One or more required tables are missing.');
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo "KONEKSI DATABASE BERHASIL\n";
    echo 'Database dan seluruh tabel inti ditemukan.';
} catch (Throwable $exception) {
    error_log('Database test failed: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'KONEKSI DATABASE GAGAL. Periksa konfigurasi dan log server.';
}
