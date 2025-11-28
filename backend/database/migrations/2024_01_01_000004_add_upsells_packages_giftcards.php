<?php

declare(strict_types=1);

use App\Core\Database;

/**
 * Migration: Add Upsells, Packages, Gift Cards, Dynamic Pricing, and Vendor Foundation
 *
 * This migration adds essential features for increasing average order value:
 * - Extended add-ons/extras system with categories
 * - Package bundles (Romantic, Family, Corporate, etc.)
 * - Gift cards/vouchers
 * - Dynamic pricing rules (seasons, weekends, early bird)
 * - Vendor foundation for future marketplace
 */
class AddUpsellsPackagesGiftcards
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        // ============================================
        // Add-on Categories Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS addon_categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) NOT NULL UNIQUE,
                name_en VARCHAR(100) NOT NULL,
                name_ru VARCHAR(100) NULL,
                name_th VARCHAR(100) NULL,
                description_en VARCHAR(500) NULL,
                description_ru VARCHAR(500) NULL,
                description_th VARCHAR(500) NULL,
                icon VARCHAR(50) NULL COMMENT 'Icon class or emoji',
                applies_to ENUM('all', 'vessels', 'tours') DEFAULT 'all',
                sort_order INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Extended Add-ons Table (replaces vessel_extras)
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS addons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_id INT UNSIGNED NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                name_en VARCHAR(255) NOT NULL,
                name_ru VARCHAR(255) NULL,
                name_th VARCHAR(255) NULL,
                description_en TEXT NULL,
                description_ru TEXT NULL,
                description_th TEXT NULL,

                -- Pricing
                price_thb DECIMAL(10, 2) NOT NULL,
                price_type ENUM('fixed', 'per_hour', 'per_person', 'per_item') DEFAULT 'fixed',
                min_quantity INT UNSIGNED DEFAULT 1,
                max_quantity INT UNSIGNED NULL,

                -- Availability
                applies_to ENUM('all', 'vessels', 'tours') DEFAULT 'all',
                vessel_types JSON NULL COMMENT 'Specific vessel types: [\"yacht\", \"catamaran\"]',
                tour_categories JSON NULL COMMENT 'Specific tour categories',
                vessel_ids JSON NULL COMMENT 'Specific vessel IDs',
                tour_ids JSON NULL COMMENT 'Specific tour IDs',

                -- Display
                image VARCHAR(500) NULL,
                icon VARCHAR(50) NULL,
                is_popular BOOLEAN DEFAULT FALSE,
                is_recommended BOOLEAN DEFAULT FALSE,
                sort_order INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_category (category_id),
                INDEX idx_applies_to (applies_to),
                INDEX idx_active (is_active),

                FOREIGN KEY (category_id) REFERENCES addon_categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Package Bundles Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS packages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(100) NOT NULL UNIQUE,
                name_en VARCHAR(255) NOT NULL,
                name_ru VARCHAR(255) NULL,
                name_th VARCHAR(255) NULL,
                tagline_en VARCHAR(255) NULL COMMENT 'Short marketing text',
                tagline_ru VARCHAR(255) NULL,
                tagline_th VARCHAR(255) NULL,
                description_en TEXT NULL,
                description_ru TEXT NULL,
                description_th TEXT NULL,

                -- Package Type
                type ENUM('romantic', 'family', 'corporate', 'adventure', 'party', 'wedding', 'custom') NOT NULL,

                -- What's included
                base_type ENUM('vessel', 'tour') NOT NULL,
                base_id INT UNSIGNED NULL COMMENT 'Specific vessel/tour or NULL for any',
                vessel_types JSON NULL COMMENT 'Allowed vessel types',
                tour_categories JSON NULL,
                included_addons JSON NOT NULL COMMENT '[{\"addon_id\": 1, \"quantity\": 1}, ...]',
                included_features JSON NULL COMMENT 'Text features not linked to addons',

                -- Pricing
                base_price_thb DECIMAL(12, 2) NOT NULL COMMENT 'Starting price',
                discount_percent DECIMAL(5, 2) DEFAULT 0 COMMENT 'Bundle discount',
                min_duration_hours INT UNSIGNED DEFAULT 4,
                min_guests INT UNSIGNED DEFAULT 2,
                max_guests INT UNSIGNED NULL,

                -- Display
                images JSON DEFAULT '[]',
                thumbnail VARCHAR(500) NULL,
                badge VARCHAR(50) NULL COMMENT 'e.g., BESTSELLER, NEW, HOT',
                is_featured BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                sort_order INT DEFAULT 0,

                -- Stats
                bookings_count INT UNSIGNED DEFAULT 0,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_type (type),
                INDEX idx_featured (is_featured),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Gift Cards Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS gift_cards (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(20) NOT NULL UNIQUE,

                -- Value
                initial_amount_thb DECIMAL(12, 2) NOT NULL,
                balance_thb DECIMAL(12, 2) NOT NULL,
                currency_purchased VARCHAR(3) DEFAULT 'THB',
                amount_paid DECIMAL(12, 2) NOT NULL,

                -- Purchaser
                purchaser_user_id INT UNSIGNED NULL,
                purchaser_name VARCHAR(255) NULL,
                purchaser_email VARCHAR(255) NULL,
                purchaser_phone VARCHAR(20) NULL,

                -- Recipient
                recipient_name VARCHAR(255) NULL,
                recipient_email VARCHAR(255) NULL,
                recipient_phone VARCHAR(20) NULL,
                personal_message TEXT NULL,

                -- Delivery
                delivery_method ENUM('email', 'sms', 'print', 'none') DEFAULT 'email',
                delivered_at TIMESTAMP NULL,

                -- Design
                design_template VARCHAR(50) DEFAULT 'classic',

                -- Status
                status ENUM('pending', 'active', 'used', 'expired', 'cancelled') DEFAULT 'pending',
                activated_at TIMESTAMP NULL,
                redeemed_by_user_id INT UNSIGNED NULL,

                -- Validity
                valid_from DATE NOT NULL,
                valid_until DATE NOT NULL,

                -- Restrictions
                applies_to ENUM('all', 'vessels', 'tours', 'packages') DEFAULT 'all',
                min_order_amount DECIMAL(12, 2) DEFAULT 0,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_code (code),
                INDEX idx_status (status),
                INDEX idx_valid (valid_from, valid_until),
                INDEX idx_purchaser (purchaser_user_id),
                INDEX idx_redeemed (redeemed_by_user_id),

                FOREIGN KEY (purchaser_user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (redeemed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Gift Card Transactions Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS gift_card_transactions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                gift_card_id INT UNSIGNED NOT NULL,
                booking_id INT UNSIGNED NULL,
                type ENUM('purchase', 'redeem', 'refund', 'expire', 'adjust') NOT NULL,
                amount_thb DECIMAL(12, 2) NOT NULL,
                balance_after_thb DECIMAL(12, 2) NOT NULL,
                note VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_gift_card (gift_card_id),
                INDEX idx_booking (booking_id),

                FOREIGN KEY (gift_card_id) REFERENCES gift_cards(id) ON DELETE CASCADE,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Dynamic Pricing Rules Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS pricing_rules (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(500) NULL,

                -- Rule Type
                type ENUM('season', 'day_of_week', 'early_bird', 'last_minute', 'group_size', 'duration', 'special_date') NOT NULL,

                -- Applicability
                applies_to ENUM('all', 'vessels', 'tours') DEFAULT 'all',
                vessel_types JSON NULL,
                tour_categories JSON NULL,
                vessel_ids JSON NULL,
                tour_ids JSON NULL,

                -- Conditions
                start_date DATE NULL COMMENT 'For seasonal rules',
                end_date DATE NULL,
                days_of_week JSON NULL COMMENT '[\"saturday\", \"sunday\"]',
                days_before_booking INT NULL COMMENT 'For early bird (min days ahead)',
                days_before_max INT NULL COMMENT 'For last minute (max days ahead)',
                min_guests INT NULL,
                max_guests INT NULL,
                min_duration_hours INT NULL,

                -- Adjustment
                adjustment_type ENUM('percentage', 'fixed') NOT NULL,
                adjustment_value DECIMAL(10, 2) NOT NULL COMMENT 'Positive = increase, Negative = discount',

                -- Priority & Status
                priority INT DEFAULT 0 COMMENT 'Higher = applied first',
                is_stackable BOOLEAN DEFAULT FALSE COMMENT 'Can combine with other rules',
                is_active BOOLEAN DEFAULT TRUE,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_type (type),
                INDEX idx_dates (start_date, end_date),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Vendors Table (Foundation for Marketplace)
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS vendors (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                -- Basic Info
                company_name VARCHAR(255) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                contact_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                phone VARCHAR(20) NOT NULL,
                whatsapp VARCHAR(20) NULL,
                telegram_id BIGINT UNSIGNED NULL,

                -- Business Details
                business_type ENUM('individual', 'company') DEFAULT 'company',
                tax_id VARCHAR(50) NULL,
                license_number VARCHAR(100) NULL,

                -- Address
                address TEXT NULL,
                city VARCHAR(100) DEFAULT 'Phuket',
                province VARCHAR(100) DEFAULT 'Phuket',
                postal_code VARCHAR(10) NULL,
                country VARCHAR(50) DEFAULT 'Thailand',

                -- Bank Details
                bank_name VARCHAR(100) NULL,
                bank_account_name VARCHAR(255) NULL,
                bank_account_number VARCHAR(50) NULL,

                -- Commission
                commission_rate DECIMAL(5, 2) DEFAULT 15.00 COMMENT 'Platform commission %',

                -- Documents (JSON array of file paths)
                documents JSON NULL COMMENT 'Business license, insurance, etc.',

                -- Status
                status ENUM('pending', 'approved', 'suspended', 'rejected') DEFAULT 'pending',
                verified_at TIMESTAMP NULL,
                verified_by INT UNSIGNED NULL,
                rejection_reason VARCHAR(500) NULL,

                -- Profile
                logo VARCHAR(500) NULL,
                cover_image VARCHAR(500) NULL,
                description_en TEXT NULL,
                description_ru TEXT NULL,
                description_th TEXT NULL,

                -- Stats
                rating DECIMAL(2, 1) DEFAULT 0.0,
                reviews_count INT UNSIGNED DEFAULT 0,
                total_bookings INT UNSIGNED DEFAULT 0,
                total_revenue_thb DECIMAL(14, 2) DEFAULT 0,

                -- Settings
                notification_settings JSON NULL,
                auto_confirm_bookings BOOLEAN DEFAULT FALSE,

                is_active BOOLEAN DEFAULT TRUE,
                last_login_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_email (email),
                INDEX idx_status (status),
                INDEX idx_active (is_active),
                INDEX idx_telegram (telegram_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Vendor Payouts Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS vendor_payouts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                vendor_id INT UNSIGNED NOT NULL,

                -- Period
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,

                -- Amounts
                gross_amount_thb DECIMAL(14, 2) NOT NULL,
                commission_thb DECIMAL(12, 2) NOT NULL,
                adjustments_thb DECIMAL(12, 2) DEFAULT 0,
                net_amount_thb DECIMAL(14, 2) NOT NULL,

                -- Bookings included
                bookings_count INT UNSIGNED NOT NULL,
                booking_ids JSON NOT NULL,

                -- Status
                status ENUM('pending', 'processing', 'paid', 'failed') DEFAULT 'pending',
                payment_method VARCHAR(50) NULL,
                payment_reference VARCHAR(255) NULL,
                paid_at TIMESTAMP NULL,

                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_vendor (vendor_id),
                INDEX idx_status (status),
                INDEX idx_period (period_start, period_end),

                FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Add vendor_id to vessels and tours tables
        // ============================================
        $this->db->execute("
            ALTER TABLE vessels
            ADD COLUMN vendor_id INT UNSIGNED NULL AFTER id,
            ADD INDEX idx_vendor (vendor_id),
            ADD CONSTRAINT fk_vessels_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
        ");

        $this->db->execute("
            ALTER TABLE tours
            ADD COLUMN vendor_id INT UNSIGNED NULL AFTER id,
            ADD INDEX idx_vendor (vendor_id),
            ADD CONSTRAINT fk_tours_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
        ");

        // ============================================
        // Update bookings table for packages and gift cards
        // ============================================
        $this->db->execute("
            ALTER TABLE bookings
            ADD COLUMN package_id INT UNSIGNED NULL AFTER bookable_id,
            ADD COLUMN gift_card_id INT UNSIGNED NULL AFTER promo_code_id,
            ADD COLUMN gift_card_amount_thb DECIMAL(12, 2) DEFAULT 0 AFTER promo_discount_thb,
            ADD COLUMN vendor_id INT UNSIGNED NULL AFTER source,
            ADD COLUMN vendor_commission_thb DECIMAL(12, 2) DEFAULT 0 AFTER vendor_id,
            ADD INDEX idx_package (package_id),
            ADD INDEX idx_gift_card (gift_card_id),
            ADD INDEX idx_vendor (vendor_id)
        ");

        // ============================================
        // Loyalty Tiers Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS loyalty_tiers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) NOT NULL UNIQUE,
                name_en VARCHAR(100) NOT NULL,
                name_ru VARCHAR(100) NULL,
                name_th VARCHAR(100) NULL,

                -- Requirements
                min_bookings INT UNSIGNED DEFAULT 0,
                min_spent_thb DECIMAL(14, 2) DEFAULT 0,

                -- Benefits
                cashback_percent DECIMAL(4, 2) NOT NULL,
                extra_discount_percent DECIMAL(4, 2) DEFAULT 0,
                priority_support BOOLEAN DEFAULT FALSE,
                free_cancellation_hours INT UNSIGNED DEFAULT 48,
                exclusive_offers BOOLEAN DEFAULT FALSE,

                -- Display
                icon VARCHAR(50) NULL,
                color VARCHAR(20) NULL,
                badge_image VARCHAR(500) NULL,
                sort_order INT DEFAULT 0,

                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Add loyalty tier to users
        // ============================================
        $this->db->execute("
            ALTER TABLE users
            ADD COLUMN loyalty_tier_id INT UNSIGNED NULL AFTER cashback_balance,
            ADD COLUMN total_spent_thb DECIMAL(14, 2) DEFAULT 0 AFTER loyalty_tier_id,
            ADD COLUMN total_bookings INT UNSIGNED DEFAULT 0 AFTER total_spent_thb,
            ADD INDEX idx_loyalty_tier (loyalty_tier_id)
        ");

        // ============================================
        // Insert Default Data
        // ============================================

        // Addon Categories
        $this->db->execute("
            INSERT INTO addon_categories (slug, name_en, name_ru, name_th, icon, applies_to, sort_order) VALUES
            ('catering', 'Catering & Food', 'Кейтеринг и еда', 'อาหารและเครื่องดื่ม', '🍽️', 'all', 1),
            ('beverages', 'Beverages', 'Напитки', 'เครื่องดื่ม', '🍾', 'all', 2),
            ('water-sports', 'Water Sports', 'Водные виды спорта', 'กีฬาทางน้ำ', '🏄', 'vessels', 3),
            ('entertainment', 'Entertainment', 'Развлечения', 'ความบันเทิง', '🎉', 'vessels', 4),
            ('photography', 'Photo & Video', 'Фото и видео', 'ถ่ายภาพและวิดีโอ', '📸', 'all', 5),
            ('spa-wellness', 'Spa & Wellness', 'Спа и велнес', 'สปาและสุขภาพ', '💆', 'vessels', 6),
            ('decoration', 'Decoration', 'Декорации', 'การตกแต่ง', '🎈', 'vessels', 7),
            ('equipment', 'Equipment Rental', 'Аренда оборудования', 'เช่าอุปกรณ์', '🎣', 'all', 8),
            ('transfers', 'Transfers', 'Трансферы', 'บริการรับส่ง', '🚐', 'all', 9)
        ");

        // Sample Addons
        $this->db->execute("
            INSERT INTO addons (category_id, slug, name_en, name_ru, name_th, description_en, price_thb, price_type, is_popular, sort_order) VALUES
            -- Catering
            (1, 'thai-lunch', 'Thai Lunch Set', 'Тайский обед', 'ชุดอาหารกลางวันไทย', 'Traditional Thai lunch with 3 dishes, rice, and dessert', 450, 'per_person', TRUE, 1),
            (1, 'bbq-seafood', 'BBQ Seafood', 'BBQ морепродукты', 'บาร์บีคิวซีฟู้ด', 'Fresh grilled seafood: prawns, squid, fish, and shellfish', 1200, 'per_person', TRUE, 2),
            (1, 'premium-dinner', 'Premium Dinner', 'Премиум ужин', 'อาหารเย็นพรีเมี่ยม', '5-course gourmet dinner with wine pairing', 2500, 'per_person', FALSE, 3),
            (1, 'breakfast', 'Continental Breakfast', 'Континентальный завтрак', 'อาหารเช้าคอนติเนนทัล', 'Fresh fruits, pastries, eggs, and coffee', 350, 'per_person', FALSE, 4),
            (1, 'snack-platter', 'Snack Platter', 'Закуски', 'ขนมขบเคี้ยว', 'Assorted snacks, chips, and finger food', 800, 'fixed', FALSE, 5),

            -- Beverages
            (2, 'soft-drinks', 'Soft Drinks Package', 'Безалкогольные напитки', 'แพ็คเกจเครื่องดื่ม', 'Unlimited soft drinks, water, and juices', 300, 'per_person', TRUE, 1),
            (2, 'beer-package', 'Beer Package', 'Пакет пиво', 'แพ็คเกจเบียร์', 'Selection of local and imported beers', 500, 'per_person', TRUE, 2),
            (2, 'premium-bar', 'Premium Open Bar', 'Премиум бар', 'บาร์พรีเมี่ยม', 'Unlimited premium spirits, cocktails, and champagne', 2000, 'per_person', FALSE, 3),
            (2, 'champagne', 'Champagne Bottle', 'Бутылка шампанского', 'แชมเปญ', 'Moët & Chandon Brut Imperial', 4500, 'per_item', TRUE, 4),

            -- Water Sports
            (3, 'jet-ski', 'Jet Ski', 'Гидроцикл', 'เจ็ทสกี', 'Yamaha jet ski rental with safety briefing', 2500, 'per_hour', TRUE, 1),
            (3, 'kayak', 'Kayak', 'Каяк', 'เรือคายัค', 'Double kayak for exploring caves and coastline', 500, 'per_hour', TRUE, 2),
            (3, 'paddleboard', 'Stand-up Paddleboard', 'SUP доска', 'ซับบอร์ด', 'SUP board with paddle', 400, 'per_hour', FALSE, 3),
            (3, 'snorkel-premium', 'Premium Snorkel Set', 'Премиум снорклинг', 'ชุดดำน้ำพรีเมี่ยม', 'High-quality mask, snorkel, and fins', 300, 'per_person', FALSE, 4),
            (3, 'diving-intro', 'Intro Dive', 'Пробное погружение', 'ดำน้ำเบื้องต้น', 'Introductory dive with instructor (no certification required)', 3500, 'per_person', FALSE, 5),
            (3, 'wakeboard', 'Wakeboarding', 'Вейкборд', 'เวคบอร์ด', 'Wakeboarding session with equipment and instructor', 2000, 'per_hour', FALSE, 6),

            -- Entertainment
            (4, 'dj', 'DJ Service', 'DJ', 'ดีเจ', 'Professional DJ with sound system', 8000, 'fixed', FALSE, 1),
            (4, 'karaoke', 'Karaoke System', 'Караоке', 'คาราโอเกะ', 'Karaoke with Thai, English, and Russian songs', 2500, 'fixed', TRUE, 2),
            (4, 'live-music', 'Live Musician', 'Живая музыка', 'นักดนตรีสด', 'Acoustic guitar or saxophone player', 6000, 'fixed', FALSE, 3),
            (4, 'fishing-equipment', 'Fishing Equipment', 'Рыболовное снаряжение', 'อุปกรณ์ตกปลา', 'Professional fishing rods, reels, and tackle', 1500, 'fixed', TRUE, 4),

            -- Photography
            (5, 'photographer', 'Professional Photographer', 'Профессиональный фотограф', 'ช่างภาพมืออาชีพ', '4 hours, 100+ edited photos delivered digitally', 8000, 'fixed', TRUE, 1),
            (5, 'drone-video', 'Drone Video', 'Съемка с дрона', 'วิดีโอโดรน', 'Aerial footage of your experience, 3-5 min edited video', 5000, 'fixed', TRUE, 2),
            (5, 'full-coverage', 'Full Photo & Video Package', 'Полный пакет фото и видео', 'แพ็คเกจถ่ายภาพและวิดีโอ', 'Photographer + drone + edited video', 15000, 'fixed', FALSE, 3),

            -- Spa & Wellness
            (6, 'thai-massage', 'Thai Massage', 'Тайский массаж', 'นวดแผนไทย', 'Traditional Thai massage onboard', 1500, 'per_person', TRUE, 1),
            (6, 'yoga-session', 'Yoga Session', 'Занятие йогой', 'คลาสโยคะ', 'Private yoga session with instructor', 3000, 'fixed', FALSE, 2),
            (6, 'aromatherapy', 'Aromatherapy Massage', 'Аромамассаж', 'นวดอโรมา', 'Relaxing aromatherapy massage', 2000, 'per_person', FALSE, 3),

            -- Decoration
            (7, 'birthday', 'Birthday Decoration', 'Украшение на день рождения', 'ตกแต่งวันเกิด', 'Balloons, banner, and cake', 3500, 'fixed', TRUE, 1),
            (7, 'romantic', 'Romantic Setup', 'Романтическое оформление', 'ตกแต่งโรแมนติก', 'Rose petals, candles, and champagne setup', 5000, 'fixed', TRUE, 2),
            (7, 'proposal', 'Proposal Package', 'Пакет предложения руки', 'แพ็คเกจขอแต่งงาน', 'Ring presentation setup, flowers, photographer', 15000, 'fixed', FALSE, 3),
            (7, 'wedding', 'Wedding Decoration', 'Свадебное оформление', 'ตกแต่งงานแต่ง', 'Full wedding decoration package', 35000, 'fixed', FALSE, 4),

            -- Transfers
            (9, 'airport-transfer', 'Airport Transfer', 'Трансфер из аэропорта', 'รับส่งสนามบิน', 'Private car transfer from/to Phuket Airport', 1200, 'fixed', TRUE, 1),
            (9, 'hotel-pickup', 'Hotel Pickup', 'Трансфер из отеля', 'รับจากโรงแรม', 'Round-trip transfer from your hotel to marina', 800, 'fixed', TRUE, 2)
        ");

        // Sample Packages
        $this->db->execute("
            INSERT INTO packages (slug, name_en, name_ru, name_th, tagline_en, type, base_type, vessel_types, included_addons, included_features, base_price_thb, discount_percent, min_guests, badge, is_featured, sort_order) VALUES
            ('romantic-sunset', 'Romantic Sunset Cruise', 'Романтический круиз на закате', 'ล่องเรือชมพระอาทิตย์ตก', 'Unforgettable sunset experience for couples', 'romantic', 'vessel', '[\"yacht\", \"catamaran\"]', '[{\"addon_id\": 9, \"quantity\": 1}, {\"addon_id\": 26, \"quantity\": 2}, {\"addon_id\": 25, \"quantity\": 1}]', '[\"4-hour private cruise\", \"Sunset viewing at best spots\", \"Romantic music playlist\"]', 25000, 15, 2, 'BESTSELLER', TRUE, 1),

            ('family-adventure', 'Family Fun Day', 'Семейный день приключений', 'วันสนุกของครอบครัว', 'Perfect day out for the whole family', 'family', 'vessel', '[\"catamaran\", \"speedboat\"]', '[{\"addon_id\": 1, \"quantity\": 1}, {\"addon_id\": 6, \"quantity\": 1}, {\"addon_id\": 12, \"quantity\": 2}]', '[\"6-hour island hopping\", \"Kids activities\", \"Snorkeling at 2 locations\", \"Beach time\"]', 35000, 10, 4, NULL, TRUE, 2),

            ('corporate-retreat', 'Corporate Team Building', 'Корпоративный тимбилдинг', 'สร้างทีมองค์กร', 'Build stronger teams on the water', 'corporate', 'vessel', '[\"catamaran\", \"yacht\"]', '[{\"addon_id\": 2, \"quantity\": 1}, {\"addon_id\": 7, \"quantity\": 1}, {\"addon_id\": 17, \"quantity\": 1}]', '[\"Full-day charter\", \"Team games & activities\", \"Dedicated event coordinator\", \"Meeting space onboard\"]', 85000, 20, 10, 'POPULAR', TRUE, 3),

            ('party-cruise', 'Ultimate Party Cruise', 'Вечеринка на яхте', 'ปาร์ตี้ล่องเรือ', 'The best floating party in Phuket', 'party', 'vessel', '[\"yacht\", \"catamaran\"]', '[{\"addon_id\": 8, \"quantity\": 1}, {\"addon_id\": 15, \"quantity\": 1}, {\"addon_id\": 16, \"quantity\": 1}]', '[\"8-hour cruise\", \"Professional DJ\", \"LED lighting setup\", \"Late night swimming\"]', 55000, 15, 8, 'HOT', TRUE, 4),

            ('proposal-perfect', 'Perfect Proposal', 'Идеальное предложение', 'ขอแต่งงานสมบูรณ์แบบ', 'Make the moment unforgettable', 'wedding', 'vessel', '[\"yacht\"]', '[{\"addon_id\": 9, \"quantity\": 1}, {\"addon_id\": 27, \"quantity\": 1}, {\"addon_id\": 19, \"quantity\": 1}]', '[\"Private yacht for 2\", \"Proposal setup with flowers\", \"Professional photographer\", \"Champagne & dinner\"]', 45000, 10, 2, NULL, FALSE, 5)
        ");

        // Loyalty Tiers
        $this->db->execute("
            INSERT INTO loyalty_tiers (slug, name_en, name_ru, name_th, min_bookings, min_spent_thb, cashback_percent, extra_discount_percent, priority_support, free_cancellation_hours, exclusive_offers, icon, color, sort_order) VALUES
            ('bronze', 'Bronze', 'Бронза', 'บรอนซ์', 0, 0, 5.00, 0, FALSE, 48, FALSE, '🥉', '#CD7F32', 1),
            ('silver', 'Silver', 'Серебро', 'เงิน', 3, 50000, 7.00, 2, FALSE, 72, FALSE, '🥈', '#C0C0C0', 2),
            ('gold', 'Gold', 'Золото', 'ทอง', 7, 150000, 10.00, 5, TRUE, 96, TRUE, '🥇', '#FFD700', 3),
            ('platinum', 'Platinum', 'Платина', 'แพลทินัม', 15, 500000, 15.00, 10, TRUE, 168, TRUE, '💎', '#E5E4E2', 4)
        ");

        // Default Pricing Rules
        $this->db->execute("
            INSERT INTO pricing_rules (name, description, type, applies_to, start_date, end_date, adjustment_type, adjustment_value, priority, is_active) VALUES
            ('High Season', 'Peak tourist season pricing', 'season', 'all', '2024-11-01', '2025-04-30', 'percentage', 15, 10, TRUE),
            ('Low Season', 'Green season discount', 'season', 'all', '2025-05-01', '2025-10-31', 'percentage', -10, 10, TRUE),
            ('Weekend Premium', 'Saturday and Sunday premium', 'day_of_week', 'all', NULL, NULL, 'percentage', 10, 5, TRUE),
            ('Early Bird 30', 'Book 30+ days ahead for discount', 'early_bird', 'all', NULL, NULL, 'percentage', -10, 3, TRUE),
            ('Last Minute Deal', 'Book within 48 hours', 'last_minute', 'all', NULL, NULL, 'percentage', -15, 3, TRUE),
            ('New Year', 'New Year period premium', 'special_date', 'all', '2024-12-28', '2025-01-03', 'percentage', 50, 15, TRUE),
            ('Songkran', 'Thai New Year premium', 'special_date', 'all', '2025-04-12', '2025-04-16', 'percentage', 30, 15, TRUE),
            ('Group Discount', '10+ guests discount', 'group_size', 'tours', NULL, NULL, 'percentage', -10, 2, TRUE)
        ");

        // Update pricing rules with day_of_week condition
        $this->db->execute("
            UPDATE pricing_rules SET days_of_week = '[\"saturday\", \"sunday\"]' WHERE name = 'Weekend Premium'
        ");

        // Update early bird and last minute rules
        $this->db->execute("
            UPDATE pricing_rules SET days_before_booking = 30 WHERE name = 'Early Bird 30'
        ");

        $this->db->execute("
            UPDATE pricing_rules SET days_before_max = 2 WHERE name = 'Last Minute Deal'
        ");

        $this->db->execute("
            UPDATE pricing_rules SET min_guests = 10 WHERE name = 'Group Discount'
        ");
    }

    public function down(): void
    {
        // Remove columns from bookings
        $this->db->execute("ALTER TABLE bookings DROP FOREIGN KEY IF EXISTS fk_bookings_package");
        $this->db->execute("ALTER TABLE bookings DROP FOREIGN KEY IF EXISTS fk_bookings_gift_card");
        $this->db->execute("ALTER TABLE bookings DROP COLUMN IF EXISTS package_id");
        $this->db->execute("ALTER TABLE bookings DROP COLUMN IF EXISTS gift_card_id");
        $this->db->execute("ALTER TABLE bookings DROP COLUMN IF EXISTS gift_card_amount_thb");
        $this->db->execute("ALTER TABLE bookings DROP COLUMN IF EXISTS vendor_id");
        $this->db->execute("ALTER TABLE bookings DROP COLUMN IF EXISTS vendor_commission_thb");

        // Remove columns from users
        $this->db->execute("ALTER TABLE users DROP COLUMN IF EXISTS loyalty_tier_id");
        $this->db->execute("ALTER TABLE users DROP COLUMN IF EXISTS total_spent_thb");
        $this->db->execute("ALTER TABLE users DROP COLUMN IF EXISTS total_bookings");

        // Remove vendor from vessels and tours
        $this->db->execute("ALTER TABLE vessels DROP FOREIGN KEY IF EXISTS fk_vessels_vendor");
        $this->db->execute("ALTER TABLE vessels DROP COLUMN IF EXISTS vendor_id");
        $this->db->execute("ALTER TABLE tours DROP FOREIGN KEY IF EXISTS fk_tours_vendor");
        $this->db->execute("ALTER TABLE tours DROP COLUMN IF EXISTS vendor_id");

        // Drop tables
        $this->db->execute("DROP TABLE IF EXISTS vendor_payouts");
        $this->db->execute("DROP TABLE IF EXISTS vendors");
        $this->db->execute("DROP TABLE IF EXISTS pricing_rules");
        $this->db->execute("DROP TABLE IF EXISTS gift_card_transactions");
        $this->db->execute("DROP TABLE IF EXISTS gift_cards");
        $this->db->execute("DROP TABLE IF EXISTS packages");
        $this->db->execute("DROP TABLE IF EXISTS addons");
        $this->db->execute("DROP TABLE IF EXISTS addon_categories");
        $this->db->execute("DROP TABLE IF EXISTS loyalty_tiers");
    }
}
