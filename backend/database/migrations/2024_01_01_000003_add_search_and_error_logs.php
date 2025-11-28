<?php

/**
 * Migration: Add search logs and client error tracking tables
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // Search logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS search_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                query VARCHAR(255) NOT NULL,
                results_count INT UNSIGNED NOT NULL DEFAULT 0,
                user_id INT UNSIGNED NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_query (query(100)),
                INDEX idx_user (user_id),
                INDEX idx_created (created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Client errors table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS client_errors (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                message TEXT NOT NULL,
                stack TEXT NULL,
                url VARCHAR(500) NULL,
                user_agent VARCHAR(500) NULL,
                ip_address VARCHAR(45) NULL,
                user_id INT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created (created_at),
                INDEX idx_user (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Contact messages table (for website contact form)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contact_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NULL,
                message TEXT NOT NULL,
                ip_address VARCHAR(45) NULL,
                is_read BOOLEAN DEFAULT FALSE,
                replied_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_created (created_at),
                INDEX idx_read (is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Newsletter subscriptions
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                is_active BOOLEAN DEFAULT TRUE,
                subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                unsubscribed_at TIMESTAMP NULL,
                INDEX idx_email (email),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS newsletter_subscriptions");
        $pdo->exec("DROP TABLE IF EXISTS contact_messages");
        $pdo->exec("DROP TABLE IF EXISTS client_errors");
        $pdo->exec("DROP TABLE IF EXISTS search_logs");
    }
};
