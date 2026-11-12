CREATE DATABASE IF NOT EXISTS `db_voting_kakang_senduk`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `db_voting_kakang_senduk`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(150) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'pemilih') NOT NULL DEFAULT 'pemilih',
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `periode_voting` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama_periode` VARCHAR(150) NOT NULL,
    `tanggal_mulai` DATETIME NOT NULL,
    `tanggal_selesai` DATETIME NOT NULL,
    `status` ENUM('draft', 'aktif', 'selesai') NOT NULL DEFAULT 'draft',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_periode_status_waktu` (`status`, `tanggal_mulai`, `tanggal_selesai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kontestan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `periode_id` BIGINT UNSIGNED NOT NULL,
    `nomor_peserta` VARCHAR(20) NOT NULL,
    `nama` VARCHAR(150) NOT NULL,
    `kategori` ENUM('Kakang', 'Senduk') NOT NULL,
    `foto` VARCHAR(255) DEFAULT NULL,
    `deskripsi` TEXT DEFAULT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_kontestan_periode_nomor_kategori` (`periode_id`, `nomor_peserta`, `kategori`),
    UNIQUE KEY `uq_kontestan_id_periode_kategori` (`id`, `periode_id`, `kategori`),
    KEY `idx_kontestan_periode_kategori_status` (`periode_id`, `kategori`, `status`),
    CONSTRAINT `fk_kontestan_periode`
        FOREIGN KEY (`periode_id`) REFERENCES `periode_voting` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `voting` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `periode_id` BIGINT UNSIGNED NOT NULL,
    `kontestan_id` BIGINT UNSIGNED NOT NULL,
    `kategori` ENUM('Kakang', 'Senduk') NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_voting_user_periode_kategori` (`user_id`, `periode_id`, `kategori`),
    KEY `idx_voting_periode_kategori` (`periode_id`, `kategori`),
    KEY `idx_voting_kontestan` (`kontestan_id`),
    CONSTRAINT `fk_voting_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `fk_voting_periode`
        FOREIGN KEY (`periode_id`) REFERENCES `periode_voting` (`id`)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `fk_voting_kontestan_periode_kategori`
        FOREIGN KEY (`kontestan_id`, `periode_id`, `kategori`)
        REFERENCES `kontestan` (`id`, `periode_id`, `kategori`)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
