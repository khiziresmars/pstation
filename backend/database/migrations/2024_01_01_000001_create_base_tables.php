<?php

/**
 * Migration: Create Base Tables
 * Creates all core database tables
 */

return new class {
    public function up(PDO $pdo): void
    {
        // ===========================================
        // USERS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                telegram_id BIGINT UNIQUE NULL,
                email VARCHAR(255) UNIQUE NULL,
                password_hash VARCHAR(255) NULL,
                google_id VARCHAR(100) UNIQUE NULL,
                google_access_token TEXT NULL,
                google_refresh_token TEXT NULL,
                auth_method ENUM('telegram', 'email', 'google') DEFAULT 'telegram',
                email_verified TINYINT(1) DEFAULT 0,
                username VARCHAR(100) NULL,
                first_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NULL,
                photo_url VARCHAR(500) NULL,
                phone VARCHAR(50) NULL,
                whatsapp VARCHAR(50) NULL,
                preferred_contact ENUM('telegram', 'whatsapp', 'phone', 'email') DEFAULT 'telegram',
                language_code VARCHAR(10) DEFAULT 'en',
                preferred_currency VARCHAR(3) DEFAULT 'THB',
                cashback_balance_thb DECIMAL(10,2) DEFAULT 0.00,
                referral_code VARCHAR(20) UNIQUE NULL,
                referred_by INT NULL,
                is_blocked TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login_at TIMESTAMP NULL,
                INDEX idx_telegram_id (telegram_id),
                INDEX idx_email (email),
                INDEX idx_referral_code (referral_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // ADMINS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admins (
                id INT PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(100) NOT NULL,
                role ENUM('super_admin', 'admin', 'manager', 'support') DEFAULT 'admin',
                permissions JSON NULL,
                is_active TINYINT(1) DEFAULT 1,
                last_login_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // VESSELS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vessels (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name_en VARCHAR(255) NOT NULL,
                name_ru VARCHAR(255) NULL,
                name_th VARCHAR(255) NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                type ENUM('yacht', 'speedboat', 'catamaran', 'sailboat') NOT NULL,
                description_en TEXT NULL,
                description_ru TEXT NULL,
                description_th TEXT NULL,
                capacity INT NOT NULL,
                length_meters DECIMAL(5,2) NULL,
                year_built INT NULL,
                price_per_hour_thb DECIMAL(10,2) NOT NULL,
                price_per_day_thb DECIMAL(10,2) NULL,
                min_hours INT DEFAULT 4,
                captain_included TINYINT(1) DEFAULT 1,
                fuel_included TINYINT(1) DEFAULT 0,
                amenities JSON NULL,
                images JSON NULL,
                thumbnail VARCHAR(500) NULL,
                location VARCHAR(255) NULL,
                rating DECIMAL(2,1) DEFAULT 0.0,
                reviews_count INT DEFAULT 0,
                bookings_count INT DEFAULT 0,
                is_featured TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                vendor_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_type (type),
                INDEX idx_featured (is_featured),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // TOURS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tours (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name_en VARCHAR(255) NOT NULL,
                name_ru VARCHAR(255) NULL,
                name_th VARCHAR(255) NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                category VARCHAR(100) NOT NULL,
                description_en TEXT NULL,
                description_ru TEXT NULL,
                description_th TEXT NULL,
                duration_hours INT NOT NULL,
                departure_time TIME NULL,
                meeting_point VARCHAR(500) NULL,
                adult_price_thb DECIMAL(10,2) NOT NULL,
                child_price_thb DECIMAL(10,2) NULL,
                max_capacity INT NOT NULL,
                min_participants INT DEFAULT 1,
                pickup_available TINYINT(1) DEFAULT 1,
                pickup_fee_thb DECIMAL(10,2) DEFAULT 0,
                includes JSON NULL,
                excludes JSON NULL,
                itinerary JSON NULL,
                images JSON NULL,
                thumbnail VARCHAR(500) NULL,
                rating DECIMAL(2,1) DEFAULT 0.0,
                reviews_count INT DEFAULT 0,
                bookings_count INT DEFAULT 0,
                is_featured TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                vendor_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_category (category),
                INDEX idx_featured (is_featured),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // BOOKINGS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bookings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                booking_reference VARCHAR(20) NOT NULL UNIQUE,
                user_id INT NOT NULL,
                bookable_type ENUM('vessel', 'tour') NOT NULL,
                bookable_id INT NOT NULL,
                booking_date DATE NOT NULL,
                start_time TIME NULL,
                hours INT NULL,
                adults INT DEFAULT 1,
                children INT DEFAULT 0,
                pickup TINYINT(1) DEFAULT 0,
                pickup_address VARCHAR(500) NULL,

                -- Pricing
                base_price_thb DECIMAL(10,2) NOT NULL,
                extras_price_thb DECIMAL(10,2) DEFAULT 0,
                pickup_fee_thb DECIMAL(10,2) DEFAULT 0,
                subtotal_thb DECIMAL(10,2) NOT NULL,
                promo_discount_thb DECIMAL(10,2) DEFAULT 0,
                cashback_used_thb DECIMAL(10,2) DEFAULT 0,
                gift_card_used_thb DECIMAL(10,2) DEFAULT 0,
                total_thb DECIMAL(10,2) NOT NULL,
                cashback_earned_thb DECIMAL(10,2) DEFAULT 0,

                -- Promo & Discounts
                promo_code VARCHAR(50) NULL,
                package_id INT NULL,
                gift_card_id INT NULL,

                -- Contact info
                special_requests TEXT NULL,
                contact_phone VARCHAR(50) NULL,
                contact_whatsapp VARCHAR(50) NULL,
                contact_email VARCHAR(255) NULL,
                preferred_contact_method ENUM('telegram', 'whatsapp', 'phone', 'email') DEFAULT 'telegram',

                -- Status
                status ENUM('pending', 'confirmed', 'paid', 'in_progress', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
                payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
                payment_method VARCHAR(50) NULL,

                -- Metadata
                admin_notes TEXT NULL,
                vendor_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                confirmed_at TIMESTAMP NULL,
                paid_at TIMESTAMP NULL,
                cancelled_at TIMESTAMP NULL,
                cancellation_reason TEXT NULL,
                reminder_sent TINYINT(1) DEFAULT 0,

                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_reference (booking_reference),
                INDEX idx_user (user_id),
                INDEX idx_date (booking_date),
                INDEX idx_status (status),
                INDEX idx_bookable (bookable_type, bookable_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // BOOKING ADDONS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS booking_addons (
                id INT PRIMARY KEY AUTO_INCREMENT,
                booking_id INT NOT NULL,
                addon_id INT NOT NULL,
                quantity INT DEFAULT 1,
                price_thb DECIMAL(10,2) NOT NULL,
                total_thb DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                INDEX idx_booking (booking_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // ADDONS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS addons (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name_en VARCHAR(255) NOT NULL,
                name_ru VARCHAR(255) NULL,
                name_th VARCHAR(255) NULL,
                description_en TEXT NULL,
                description_ru TEXT NULL,
                description_th TEXT NULL,
                category VARCHAR(100) NOT NULL,
                price_thb DECIMAL(10,2) NOT NULL,
                pricing_type ENUM('fixed', 'per_person', 'per_hour', 'per_item') DEFAULT 'fixed',
                applies_to ENUM('all', 'vessel', 'tour') DEFAULT 'all',
                applies_to_types JSON NULL,
                image VARCHAR(500) NULL,
                sort_order INT DEFAULT 0,
                is_popular TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_applies (applies_to)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // PACKAGES
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS packages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name_en VARCHAR(255) NOT NULL,
                name_ru VARCHAR(255) NULL,
                name_th VARCHAR(255) NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description_en TEXT NULL,
                description_ru TEXT NULL,
                description_th TEXT NULL,
                type VARCHAR(100) NOT NULL,
                discount_percent DECIMAL(5,2) DEFAULT 0,
                included_addons JSON NULL,
                image VARCHAR(500) NULL,
                sort_order INT DEFAULT 0,
                is_featured TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // REVIEWS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                booking_id INT NULL,
                bookable_type ENUM('vessel', 'tour') NOT NULL,
                bookable_id INT NOT NULL,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                title VARCHAR(255) NULL,
                comment TEXT NULL,
                images JSON NULL,
                reply TEXT NULL,
                replied_at TIMESTAMP NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                is_verified TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_bookable (bookable_type, bookable_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // PROMO CODES
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS promo_codes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                code VARCHAR(50) NOT NULL UNIQUE,
                type ENUM('percentage', 'fixed', 'free_addon') NOT NULL,
                value DECIMAL(10,2) NOT NULL,
                min_order_thb DECIMAL(10,2) DEFAULT 0,
                max_discount_thb DECIMAL(10,2) NULL,
                applies_to ENUM('all', 'vessel', 'tour') DEFAULT 'all',
                usage_limit INT NULL,
                usage_count INT DEFAULT 0,
                user_limit INT DEFAULT 1,
                valid_from TIMESTAMP NULL,
                valid_until TIMESTAMP NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_code (code),
                INDEX idx_valid (valid_from, valid_until)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // GIFT CARDS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gift_cards (
                id INT PRIMARY KEY AUTO_INCREMENT,
                code VARCHAR(50) NOT NULL UNIQUE,
                initial_amount_thb DECIMAL(10,2) NOT NULL,
                balance_thb DECIMAL(10,2) NOT NULL,
                design_template VARCHAR(50) DEFAULT 'default',
                purchaser_id INT NULL,
                purchaser_name VARCHAR(100) NULL,
                recipient_name VARCHAR(100) NULL,
                recipient_email VARCHAR(255) NULL,
                personal_message TEXT NULL,
                applies_to ENUM('all', 'vessel', 'tour') DEFAULT 'all',
                valid_until DATE NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                purchased_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_code (code),
                INDEX idx_purchaser (purchaser_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // PAYMENTS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                booking_id INT NOT NULL,
                user_id INT NOT NULL,
                amount_thb DECIMAL(10,2) NOT NULL,
                amount_currency DECIMAL(10,2) NULL,
                currency VARCHAR(10) DEFAULT 'THB',
                method ENUM('stripe', 'crypto', 'telegram_stars', 'bank_transfer', 'cash') NOT NULL,
                provider_payment_id VARCHAR(255) NULL,
                status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                metadata JSON NULL,
                paid_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_booking (booking_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // FAVORITES
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS favorites (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                favoritable_type ENUM('vessel', 'tour') NOT NULL,
                favoritable_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_favorite (user_id, favoritable_type, favoritable_id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // NOTIFICATIONS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                data JSON NULL,
                is_read TINYINT(1) DEFAULT 0,
                read_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_read (user_id, is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // CASHBACK TRANSACTIONS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS cashback_transactions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                booking_id INT NULL,
                type ENUM('earned', 'spent', 'expired', 'adjustment', 'referral') NOT NULL,
                amount_thb DECIMAL(10,2) NOT NULL,
                balance_after_thb DECIMAL(10,2) NOT NULL,
                description VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // EXCHANGE RATES
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS exchange_rates (
                id INT PRIMARY KEY AUTO_INCREMENT,
                currency_code VARCHAR(3) NOT NULL UNIQUE,
                currency_name VARCHAR(50) NOT NULL,
                currency_symbol VARCHAR(10) NOT NULL,
                rate_to_thb DECIMAL(12,6) NOT NULL,
                rate_from_thb DECIMAL(12,6) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_currency (currency_code),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // SETTINGS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                `key` VARCHAR(100) NOT NULL UNIQUE,
                `value` TEXT NULL,
                `type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
                description VARCHAR(500) NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // AVAILABILITY (Blackout dates)
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS availability (
                id INT PRIMARY KEY AUTO_INCREMENT,
                bookable_type ENUM('vessel', 'tour') NOT NULL,
                bookable_id INT NOT NULL,
                date DATE NOT NULL,
                status ENUM('available', 'unavailable', 'limited') DEFAULT 'available',
                slots_remaining INT NULL,
                special_price_thb DECIMAL(10,2) NULL,
                reason VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_availability (bookable_type, bookable_id, date),
                INDEX idx_date (date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // ACTIVITY LOGS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NULL,
                admin_id INT NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NULL,
                entity_id INT NULL,
                old_values JSON NULL,
                new_values JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_admin (admin_id),
                INDEX idx_action (action),
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // DAILY STATS (for analytics)
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS daily_stats (
                id INT PRIMARY KEY AUTO_INCREMENT,
                date DATE NOT NULL,
                metric VARCHAR(100) NOT NULL,
                value INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_stat (date, metric),
                INDEX idx_date (date),
                INDEX idx_metric (metric)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // EMAIL VERIFICATIONS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_verifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                token VARCHAR(100) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ===========================================
        // PASSWORD RESETS
        // ===========================================
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL UNIQUE,
                token VARCHAR(100) NOT NULL UNIQUE,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        echo "Base tables created successfully.\n";
    }

    public function down(PDO $pdo): void
    {
        $tables = [
            'password_resets',
            'email_verifications',
            'daily_stats',
            'activity_logs',
            'availability',
            'settings',
            'exchange_rates',
            'cashback_transactions',
            'notifications',
            'favorites',
            'payments',
            'gift_cards',
            'promo_codes',
            'reviews',
            'packages',
            'addons',
            'booking_addons',
            'bookings',
            'tours',
            'vessels',
            'admins',
            'users',
        ];

        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$table}");
        }

        echo "All tables dropped.\n";
    }
};
