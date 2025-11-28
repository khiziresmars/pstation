<?php

/**
 * Migration: Authentication and Contact Fields
 * Adds support for multi-auth (Email, Google) and contact details
 */

return new class {
    public function up(PDO $pdo): void
    {
        // Add auth fields to users table
        $pdo->exec("
            ALTER TABLE users
            ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE NULL AFTER telegram_id,
            ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL AFTER email,
            ADD COLUMN IF NOT EXISTS google_id VARCHAR(100) UNIQUE NULL AFTER password_hash,
            ADD COLUMN IF NOT EXISTS google_access_token TEXT NULL AFTER google_id,
            ADD COLUMN IF NOT EXISTS google_refresh_token TEXT NULL AFTER google_access_token,
            ADD COLUMN IF NOT EXISTS auth_method ENUM('telegram', 'email', 'google') DEFAULT 'telegram' AFTER google_refresh_token,
            ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0 AFTER auth_method,
            ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL AFTER email_verified,
            ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(50) NULL AFTER phone,
            ADD COLUMN IF NOT EXISTS preferred_contact ENUM('telegram', 'whatsapp', 'phone', 'email') DEFAULT 'telegram' AFTER whatsapp,
            ADD COLUMN IF NOT EXISTS calendar_link VARCHAR(500) NULL AFTER preferred_contact,
            ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL AFTER updated_at,
            ADD INDEX idx_users_email (email),
            ADD INDEX idx_users_google_id (google_id),
            ADD INDEX idx_users_auth_method (auth_method)
        ");

        // Email verification tokens
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_verifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                token VARCHAR(100) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Password reset tokens
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL UNIQUE,
                token VARCHAR(100) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Admin users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                role ENUM('super_admin', 'admin', 'manager', 'support') DEFAULT 'admin',
                is_active TINYINT(1) DEFAULT 1,
                last_login_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_role (role),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Contact preferences for bookings
        $pdo->exec("
            ALTER TABLE bookings
            ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(50) NULL AFTER special_requests,
            ADD COLUMN IF NOT EXISTS contact_whatsapp VARCHAR(50) NULL AFTER contact_phone,
            ADD COLUMN IF NOT EXISTS contact_email VARCHAR(255) NULL AFTER contact_whatsapp,
            ADD COLUMN IF NOT EXISTS preferred_contact_method ENUM('telegram', 'whatsapp', 'phone', 'email') DEFAULT 'telegram' AFTER contact_email,
            ADD COLUMN IF NOT EXISTS calendar_event_id VARCHAR(255) NULL AFTER preferred_contact_method,
            ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) DEFAULT 0 AFTER calendar_event_id
        ");

        // Insert default admin
        $adminEmail = 'admin@admin.com';
        $adminPassword = password_hash('admin', PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO admins (email, password_hash, name, role)
            VALUES (?, ?, 'Administrator', 'super_admin')
        ");
        $stmt->execute([$adminEmail, $adminPassword]);

        echo "Auth and contacts migration completed.\n";
    }

    public function down(PDO $pdo): void
    {
        // Drop tables
        $pdo->exec("DROP TABLE IF EXISTS password_resets");
        $pdo->exec("DROP TABLE IF EXISTS email_verifications");
        $pdo->exec("DROP TABLE IF EXISTS admins");

        // Remove columns from users (be careful in production)
        // $pdo->exec("ALTER TABLE users DROP COLUMN email, DROP COLUMN password_hash...");

        echo "Auth and contacts migration rolled back.\n";
    }
};
