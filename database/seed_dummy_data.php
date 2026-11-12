<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';

try {
    $pdo = require __DIR__ . '/../config/database.php';

    $period = $pdo->query(
        "SELECT id, nama_periode, status
         FROM periode_voting
         WHERE status = 'aktif'
         ORDER BY tanggal_mulai DESC, id DESC
         LIMIT 1"
    )->fetch();

    if ($period === false) {
        echo "BLOCKED: Tidak ada periode voting aktif. Dummy data tidak dapat dibuat tanpa periode aktif.\n";
        exit(1);
    }

    $periodId = (int) $period['id'];

    $contestantMap = [];
    $contestantSeed = [
        'Kakang' => [
            ['nomor_peserta' => '01', 'nama' => 'Rizky Adi Pratama', 'deskripsi' => 'Mahasiswa Ponorogo yang aktif dalam komunitas budaya dan pariwisata lokal.'],
            ['nomor_peserta' => '02', 'nama' => 'Dimas Bayu Nugroho', 'deskripsi' => 'Pemuda yang dikenal ramah, kompeten, dan membawa semangat inovasi untuk daerah.'],
            ['nomor_peserta' => '03', 'nama' => 'Fajar Rizqullah', 'deskripsi' => 'Muda yang konsisten mempromosikan potensi wisata serta seni tradisi Ponorogo.'],
            ['nomor_peserta' => '04', 'nama' => 'Aditya Putra Setiawan', 'deskripsi' => 'Inspirator muda dengan semangat kepemimpinan dan jiwa gotong royong.'],
            ['nomor_peserta' => '05', 'nama' => 'Muhammad Arif Rahmadi', 'deskripsi' => 'Penyuka budaya lokal yang membawa energi positif dan kecintaan pada tanah kelahirannya.'],
        ],
        'Senduk' => [
            ['nomor_peserta' => '01', 'nama' => 'Ayu Lestari', 'deskripsi' => 'Perempuan muda yang membawa pesona, kecerdasan, dan semangat budaya Ponorogo.'],
            ['nomor_peserta' => '02', 'nama' => 'Nabila Putri Sari', 'deskripsi' => 'Komunikatif dan penuh semangat dalam memperkenalkan produk budaya lokal.'],
            ['nomor_peserta' => '03', 'nama' => 'Shinta Dewi Kusuma', 'deskripsi' => 'Mempunyai jiwa kepemimpinan dan perhatian besar pada pengembangan komunitas.'],
            ['nomor_peserta' => '04', 'nama' => 'Rina Kartika Maharani', 'deskripsi' => 'Menggabungkan kecantikan, kehangatan, serta semangat membangun daerah.'],
            ['nomor_peserta' => '05', 'nama' => 'Zahra Nur Aisyah', 'deskripsi' => 'Dikenal sebagai sosok yang aktif, inspiratif, dan peduli pada potensi lokal.'],
        ],
    ];

    $voteCounts = [
        'Kakang' => [18, 15, 12, 9, 8],
        'Senduk' => [16, 14, 11, 9, 7],
    ];

    $pdo->beginTransaction();

    foreach ($contestantSeed as $category => $entries) {
        $contestantMap[$category] = [];

        foreach ($entries as $entry) {
            $check = $pdo->prepare(
                'SELECT id FROM kontestan
                 WHERE periode_id = :period_id
                   AND nomor_peserta = :nomor_peserta
                   AND kategori = :kategori
                 LIMIT 1'
            );
            $check->execute([
                'period_id' => $periodId,
                'nomor_peserta' => $entry['nomor_peserta'],
                'kategori' => $category,
            ]);

            $existing = $check->fetch();
            if ($existing !== false) {
                $contestantMap[$category][] = (int) $existing['id'];
                continue;
            }

            $insert = $pdo->prepare(
                'INSERT INTO kontestan (periode_id, nomor_peserta, nama, kategori, foto, deskripsi, status)
                 VALUES (:period_id, :nomor_peserta, :nama, :kategori, NULL, :deskripsi, :status)'
            );
            $insert->execute([
                'period_id' => $periodId,
                'nomor_peserta' => $entry['nomor_peserta'],
                'nama' => $entry['nama'],
                'kategori' => $category,
                'deskripsi' => $entry['deskripsi'],
                'status' => 'aktif',
            ]);

            $contestantMap[$category][] = (int) $pdo->lastInsertId();
        }
    }

    $createdUserIds = [];
    $voteUserCounter = 1;

    foreach ($voteCounts as $category => $counts) {
        foreach ($counts as $index => $count) {
            $contestId = $contestantMap[$category][$index];

            for ($i = 0; $i < $count; $i++) {
                $username = sprintf('vote_%s_%d_%d', strtolower($category), $contestId, $voteUserCounter);
                $email = sprintf('vote_%s_%d_%d@dummy.local', strtolower($category), $contestId, $voteUserCounter);
                $name = sprintf('Pemilih %s %d', $category, $voteUserCounter);

                $userCheck = $pdo->prepare(
                    'SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1'
                );
                $userCheck->execute([
                    'username' => $username,
                    'email' => $email,
                ]);
                $user = $userCheck->fetch();

                if ($user === false) {
                    $userInsert = $pdo->prepare(
                        'INSERT INTO users (nama, username, email, password, role, status)
                         VALUES (:nama, :username, :email, :password, :role, :status)'
                    );
                    $userInsert->execute([
                        'nama' => $name,
                        'username' => $username,
                        'email' => $email,
                        'password' => password_hash('DummyVoter123!', PASSWORD_DEFAULT),
                        'role' => 'pemilih',
                        'status' => 'aktif',
                    ]);
                    $userId = (int) $pdo->lastInsertId();
                } else {
                    $userId = (int) $user['id'];
                }

                $existingVote = $pdo->prepare(
                    'SELECT id FROM voting
                     WHERE user_id = :user_id
                       AND periode_id = :period_id
                       AND kategori = :kategori
                     LIMIT 1'
                );
                $existingVote->execute([
                    'user_id' => $userId,
                    'period_id' => $periodId,
                    'kategori' => $category,
                ]);

                if ($existingVote->fetch() === false) {
                    $voteInsert = $pdo->prepare(
                        'INSERT INTO voting (user_id, periode_id, kontestan_id, kategori)
                         VALUES (:user_id, :period_id, :kontestan_id, :kategori)'
                    );
                    $voteInsert->execute([
                        'user_id' => $userId,
                        'period_id' => $periodId,
                        'kontestan_id' => $contestId,
                        'kategori' => $category,
                    ]);
                }

                $voteUserCounter++;
            }
        }
    }

    $pdo->commit();

    $totalContestantsByCategory = [];
    foreach ($contestantMap as $category => $ids) {
        $totalContestantsByCategory[$category] = count($ids);
    }

    $totalVoteRows = (int) $pdo->query('SELECT COUNT(*) FROM voting WHERE periode_id = ' . (int) $periodId)->fetchColumn();

    echo "SEED_OK\n";
    echo "Periode: " . $period['nama_periode'] . " (ID: " . $periodId . ")\n";
    foreach ($contestantMap as $category => $ids) {
        $count = count($ids);
        echo "Kontestan $category: $count\n";
    }
    echo "Total voting rows: $totalVoteRows\n";
    echo "Data dummy dibuat untuk pengujian. Bukan hasil pemilihan resmi.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Dummy data seeding failed: ' . $exception->getMessage());
    echo "SEED_FAILED\n";
    echo $exception->getMessage() . "\n";
    exit(1);
}
