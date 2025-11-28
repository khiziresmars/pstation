-- ============================================
-- Phuket Yacht & Tours Database Schema
-- Version: 1.0.0
-- MySQL 8.0+
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS phuket_yachts
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE phuket_yachts;

-- ============================================
-- Users Table
-- Stores Telegram users and their profile data
-- ============================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    telegram_id BIGINT UNSIGNED NOT NULL UNIQUE,
    username VARCHAR(255) NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    language_code VARCHAR(5) DEFAULT 'en',
    photo_url VARCHAR(500) NULL,
    referral_code VARCHAR(20) NOT NULL UNIQUE,
    referred_by INT UNSIGNED NULL,
    cashback_balance DECIMAL(12, 2) DEFAULT 0.00,
    preferred_currency VARCHAR(3) DEFAULT 'THB',
    is_active BOOLEAN DEFAULT TRUE,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_telegram_id (telegram_id),
    INDEX idx_referral_code (referral_code),
    INDEX idx_referred_by (referred_by),

    CONSTRAINT fk_users_referred_by
        FOREIGN KEY (referred_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Vessels Table
-- Yachts, speedboats, catamarans, sailboats
-- ============================================
CREATE TABLE vessels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('yacht', 'speedboat', 'catamaran', 'sailboat') NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description_en TEXT NOT NULL,
    description_ru TEXT NULL,
    description_th TEXT NULL,
    short_description_en VARCHAR(500) NULL,
    short_description_ru VARCHAR(500) NULL,
    short_description_th VARCHAR(500) NULL,
    capacity INT UNSIGNED NOT NULL,
    cabins INT UNSIGNED DEFAULT 0,
    length_meters DECIMAL(5, 2) NOT NULL,
    year_built YEAR NULL,
    manufacturer VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    features JSON NOT NULL DEFAULT '[]',
    amenities JSON NOT NULL DEFAULT '[]',
    crew_info JSON NULL,
    price_per_hour_thb DECIMAL(12, 2) NOT NULL,
    price_per_day_thb DECIMAL(12, 2) NOT NULL,
    price_half_day_thb DECIMAL(12, 2) NULL,
    min_rental_hours INT UNSIGNED DEFAULT 4,
    max_rental_hours INT UNSIGNED DEFAULT 24,
    location VARCHAR(255) DEFAULT 'Phuket Marina',
    home_port VARCHAR(255) NULL,
    captain_included BOOLEAN DEFAULT TRUE,
    fuel_included BOOLEAN DEFAULT FALSE,
    fuel_policy VARCHAR(255) DEFAULT 'Fuel at charterer expense',
    insurance_included BOOLEAN DEFAULT TRUE,
    images JSON NOT NULL DEFAULT '[]',
    thumbnail VARCHAR(500) NULL,
    video_url VARCHAR(500) NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    rating DECIMAL(2, 1) DEFAULT 0.0,
    reviews_count INT UNSIGNED DEFAULT 0,
    bookings_count INT UNSIGNED DEFAULT 0,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (type),
    INDEX idx_capacity (capacity),
    INDEX idx_price_day (price_per_day_thb),
    INDEX idx_is_active (is_active),
    INDEX idx_is_featured (is_featured),
    INDEX idx_rating (rating),
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- ============================================
-- Tours Table
-- Island tours, snorkeling, fishing, sunset cruises
-- ============================================
CREATE TABLE tours (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category ENUM('islands', 'snorkeling', 'fishing', 'sunset', 'adventure', 'private') NOT NULL DEFAULT 'islands',
    name_en VARCHAR(255) NOT NULL,
    name_ru VARCHAR(255) NULL,
    name_th VARCHAR(255) NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description_en TEXT NOT NULL,
    description_ru TEXT NULL,
    description_th TEXT NULL,
    short_description_en VARCHAR(500) NULL,
    short_description_ru VARCHAR(500) NULL,
    short_description_th VARCHAR(500) NULL,
    duration_hours DECIMAL(4, 1) NOT NULL,
    departure_time TIME NOT NULL,
    return_time TIME NULL,
    includes JSON NOT NULL DEFAULT '[]',
    excludes JSON NOT NULL DEFAULT '[]',
    itinerary JSON NOT NULL DEFAULT '[]',
    highlights JSON NOT NULL DEFAULT '[]',
    meeting_point VARCHAR(500) NOT NULL,
    meeting_point_coordinates JSON NULL,
    pickup_available BOOLEAN DEFAULT TRUE,
    pickup_fee_thb DECIMAL(10, 2) DEFAULT 0.00,
    min_participants INT UNSIGNED DEFAULT 1,
    max_participants INT UNSIGNED NOT NULL,
    min_age INT UNSIGNED DEFAULT 0,
    difficulty_level ENUM('easy', 'moderate', 'challenging') DEFAULT 'easy',
    price_adult_thb DECIMAL(12, 2) NOT NULL,
    price_child_thb DECIMAL(12, 2) NOT NULL,
    child_age_from INT UNSIGNED DEFAULT 4,
    child_age_to INT UNSIGNED DEFAULT 11,
    infant_free BOOLEAN DEFAULT TRUE,
    private_charter_price_thb DECIMAL(12, 2) NULL,
    images JSON NOT NULL DEFAULT '[]',
    thumbnail VARCHAR(500) NULL,
    video_url VARCHAR(500) NULL,
    schedule JSON NULL COMMENT 'Available days of week: ["monday", "tuesday", ...]',
    blackout_dates JSON NULL COMMENT 'Dates when tour is not available',
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    rating DECIMAL(2, 1) DEFAULT 0.0,
    reviews_count INT UNSIGNED DEFAULT 0,
    bookings_count INT UNSIGNED DEFAULT 0,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category),
    INDEX idx_price_adult (price_adult_thb),
    INDEX idx_is_active (is_active),
    INDEX idx_is_featured (is_featured),
    INDEX idx_rating (rating),
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- ============================================
-- Promo Codes Table
-- Discount codes and special offers
-- ============================================
CREATE TABLE promo_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NULL,
    description VARCHAR(500) NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    min_order_amount DECIMAL(12, 2) DEFAULT 0.00,
    max_discount_amount DECIMAL(12, 2) NULL,
    applies_to ENUM('all', 'vessels', 'tours') DEFAULT 'all',
    vessel_ids JSON NULL COMMENT 'Specific vessel IDs if limited',
    tour_ids JSON NULL COMMENT 'Specific tour IDs if limited',
    max_uses INT UNSIGNED NULL,
    max_uses_per_user INT UNSIGNED DEFAULT 1,
    used_count INT UNSIGNED DEFAULT 0,
    valid_from TIMESTAMP NOT NULL,
    valid_until TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_public BOOLEAN DEFAULT FALSE,
    created_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_valid_dates (valid_from, valid_until),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- Bookings Table
-- All vessel and tour reservations
-- ============================================
CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    bookable_type ENUM('vessel', 'tour') NOT NULL,
    bookable_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    duration_hours DECIMAL(4, 1) NULL,
    adults_count INT UNSIGNED NOT NULL DEFAULT 1,
    children_count INT UNSIGNED DEFAULT 0,
    infants_count INT UNSIGNED DEFAULT 0,

    -- Pricing breakdown
    base_price_thb DECIMAL(12, 2) NOT NULL,
    extras_price_thb DECIMAL(12, 2) DEFAULT 0.00,
    extras_details JSON NULL,
    pickup_fee_thb DECIMAL(10, 2) DEFAULT 0.00,
    pickup_address VARCHAR(500) NULL,
    subtotal_thb DECIMAL(12, 2) NOT NULL,

    -- Discounts
    promo_code_id INT UNSIGNED NULL,
    promo_discount_thb DECIMAL(12, 2) DEFAULT 0.00,
    cashback_used_thb DECIMAL(12, 2) DEFAULT 0.00,
    total_discount_thb DECIMAL(12, 2) DEFAULT 0.00,

    -- Final amount
    total_price_thb DECIMAL(12, 2) NOT NULL,

    -- Payment info
    currency_paid VARCHAR(3) DEFAULT 'THB',
    exchange_rate DECIMAL(10, 4) DEFAULT 1.0000,
    amount_paid DECIMAL(12, 2) NULL,
    amount_paid_original DECIMAL(12, 2) NULL COMMENT 'Amount in original currency',

    -- Cashback
    cashback_percent DECIMAL(4, 2) DEFAULT 5.00,
    cashback_earned_thb DECIMAL(12, 2) DEFAULT 0.00,
    cashback_status ENUM('pending', 'credited', 'cancelled') DEFAULT 'pending',

    -- Booking status
    status ENUM('pending', 'confirmed', 'paid', 'completed', 'cancelled', 'refunded', 'no_show') NOT NULL DEFAULT 'pending',
    cancellation_reason VARCHAR(500) NULL,
    cancelled_at TIMESTAMP NULL,

    -- Payment details
    payment_method ENUM('telegram_stars', 'bank_transfer', 'cash', 'credit_card', 'crypto') NULL,
    telegram_payment_charge_id VARCHAR(255) NULL,
    payment_provider_charge_id VARCHAR(255) NULL,
    paid_at TIMESTAMP NULL,

    -- Additional info
    special_requests TEXT NULL,
    internal_notes TEXT NULL,
    contact_phone VARCHAR(20) NULL,
    contact_email VARCHAR(255) NULL,

    -- Metadata
    source ENUM('telegram', 'web', 'admin') DEFAULT 'telegram',
    user_agent VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_bookable (bookable_type, bookable_id),
    INDEX idx_booking_date (booking_date),
    INDEX idx_status (status),
    INDEX idx_reference (booking_reference),
    INDEX idx_created_at (created_at),

    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bookings_promo
        FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Reviews Table
-- User reviews for vessels and tours
-- ============================================
CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    bookable_type ENUM('vessel', 'tour') NOT NULL,
    bookable_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NULL,
    comment TEXT NULL,
    pros TEXT NULL,
    cons TEXT NULL,
    images JSON DEFAULT '[]',
    is_verified BOOLEAN DEFAULT FALSE COMMENT 'Verified purchase',
    is_featured BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT TRUE,
    admin_response TEXT NULL,
    admin_response_at TIMESTAMP NULL,
    helpful_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_bookable (bookable_type, bookable_id),
    INDEX idx_rating (rating),
    INDEX idx_is_published (is_published),

    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Favorites Table
-- User favorites/wishlist
-- ============================================
CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    favoritable_type ENUM('vessel', 'tour') NOT NULL,
    favoritable_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_favorite (user_id, favoritable_type, favoritable_id),
    INDEX idx_user_id (user_id),

    CONSTRAINT fk_favorites_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Exchange Rates Table
-- Currency exchange rates to THB
-- ============================================
CREATE TABLE exchange_rates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(3) NOT NULL UNIQUE,
    currency_name VARCHAR(100) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    rate_to_thb DECIMAL(12, 6) NOT NULL,
    rate_from_thb DECIMAL(12, 6) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_currency_code (currency_code)
) ENGINE=InnoDB;

-- ============================================
-- Availability Table
-- Vessel and tour availability/blackout dates
-- ============================================
CREATE TABLE availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    available_type ENUM('vessel', 'tour') NOT NULL,
    available_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    available_slots INT UNSIGNED NULL COMMENT 'For tours with limited slots',
    booked_slots INT UNSIGNED DEFAULT 0,
    special_price_thb DECIMAL(12, 2) NULL COMMENT 'Override price for this date',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_availability (available_type, available_id, date),
    INDEX idx_date (date),
    INDEX idx_available (available_type, available_id)
) ENGINE=InnoDB;

-- ============================================
-- Referral Transactions Table
-- Track referral bonuses
-- ============================================
CREATE TABLE referral_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT UNSIGNED NOT NULL,
    referred_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    bonus_amount_thb DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'credited', 'cancelled') DEFAULT 'pending',
    credited_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_referrer (referrer_id),
    INDEX idx_referred (referred_id),

    CONSTRAINT fk_referral_referrer
        FOREIGN KEY (referrer_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_referral_referred
        FOREIGN KEY (referred_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_referral_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Cashback Transactions Table
-- Track cashback credits and usage
-- ============================================
CREATE TABLE cashback_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    type ENUM('earned', 'used', 'expired', 'adjusted') NOT NULL,
    amount_thb DECIMAL(12, 2) NOT NULL,
    balance_after_thb DECIMAL(12, 2) NOT NULL,
    description VARCHAR(255) NULL,
    expires_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_booking (booking_id),
    INDEX idx_type (type),

    CONSTRAINT fk_cashback_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cashback_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Promo Code Usage Table
-- Track individual promo code usage
-- ============================================
CREATE TABLE promo_code_usage (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promo_code_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    discount_applied_thb DECIMAL(12, 2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_promo (promo_code_id),
    INDEX idx_user (user_id),

    CONSTRAINT fk_promo_usage_promo
        FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_promo_usage_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_promo_usage_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Settings Table
-- Application settings and configuration
-- ============================================
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NULL,
    type ENUM('string', 'integer', 'float', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',
    description VARCHAR(255) NULL,
    is_public BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key (`key`),
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- ============================================
-- Activity Log Table
-- Track user and admin actions
-- ============================================
CREATE TABLE activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at),

    CONSTRAINT fk_activity_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Notifications Table
-- User notifications
-- ============================================
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at),

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Vessel Extras Table
-- Additional services for vessels
-- ============================================
CREATE TABLE vessel_extras (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vessel_id INT UNSIGNED NULL COMMENT 'NULL means available for all vessels',
    name_en VARCHAR(255) NOT NULL,
    name_ru VARCHAR(255) NULL,
    name_th VARCHAR(255) NULL,
    description_en VARCHAR(500) NULL,
    description_ru VARCHAR(500) NULL,
    description_th VARCHAR(500) NULL,
    price_thb DECIMAL(10, 2) NOT NULL,
    price_type ENUM('per_booking', 'per_hour', 'per_person') DEFAULT 'per_booking',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_vessel (vessel_id),

    CONSTRAINT fk_extras_vessel
        FOREIGN KEY (vessel_id) REFERENCES vessels(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Insert default exchange rates
-- ============================================
INSERT INTO exchange_rates (currency_code, currency_name, currency_symbol, rate_to_thb, rate_from_thb) VALUES
('THB', 'Thai Baht', '฿', 1.000000, 1.000000),
('USD', 'US Dollar', '$', 35.500000, 0.028169),
('EUR', 'Euro', '€', 38.500000, 0.025974),
('RUB', 'Russian Ruble', '₽', 0.380000, 2.631579),
('GBP', 'British Pound', '£', 44.500000, 0.022472),
('CNY', 'Chinese Yuan', '¥', 4.900000, 0.204082),
('AUD', 'Australian Dollar', 'A$', 23.200000, 0.043103);

-- ============================================
-- Insert default settings
-- ============================================
INSERT INTO settings (`key`, value, type, category, description, is_public) VALUES
('cashback_percent', '5', 'float', 'loyalty', 'Default cashback percentage', TRUE),
('referral_bonus_thb', '200', 'float', 'loyalty', 'Referral bonus in THB', TRUE),
('min_booking_hours', '4', 'integer', 'booking', 'Minimum rental hours', TRUE),
('max_booking_days_ahead', '90', 'integer', 'booking', 'Maximum days in advance for booking', TRUE),
('cancellation_hours', '48', 'integer', 'booking', 'Hours before booking for free cancellation', TRUE),
('contact_phone', '+66 76 123 456', 'string', 'contact', 'Main contact phone', TRUE),
('contact_email', 'info@phuket-yachts.com', 'string', 'contact', 'Main contact email', TRUE),
('contact_whatsapp', '+66812345678', 'string', 'contact', 'WhatsApp number', TRUE),
('telegram_channel', '@phuketyachts', 'string', 'contact', 'Telegram channel', TRUE),
('office_address', 'Royal Phuket Marina, 63/100 Moo 2, Thepkasattri Rd', 'string', 'contact', 'Office address', TRUE),
('telegram_stars_rate', '0.013', 'float', 'payment', 'THB per Telegram Star', FALSE),
('default_language', 'en', 'string', 'general', 'Default language', TRUE),
('maintenance_mode', '0', 'boolean', 'general', 'Maintenance mode enabled', FALSE),
('booking_confirmation_required', '1', 'boolean', 'booking', 'Manual confirmation required', FALSE);
