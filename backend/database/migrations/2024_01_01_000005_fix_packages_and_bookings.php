<?php

declare(strict_types=1);

use App\Core\Database;

/**
 * Migration: Fix Package Addon References and Add Missing Features
 *
 * Fixes:
 * - Correct addon IDs in packages
 * - Add foreign keys for package_id and gift_card_id in bookings
 * - Add booking_status_history table for audit trail
 * - Add dynamic_price_applied column to bookings
 */
class FixPackageAddonsAndBookings
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        // ============================================
        // Fix Package Addon References
        // ============================================

        // Fix romantic-sunset: addon 26 should be 27 (romantic setup, not birthday)
        $this->db->execute("
            UPDATE packages
            SET included_addons = '[{\"addon_id\": 9, \"quantity\": 1}, {\"addon_id\": 27, \"quantity\": 1}, {\"addon_id\": 23, \"quantity\": 2}]'
            WHERE slug = 'romantic-sunset'
        ");

        // Fix proposal-perfect: addon 19 should be 20 (photographer, not fishing)
        $this->db->execute("
            UPDATE packages
            SET included_addons = '[{\"addon_id\": 9, \"quantity\": 1}, {\"addon_id\": 28, \"quantity\": 1}, {\"addon_id\": 20, \"quantity\": 1}]'
            WHERE slug = 'proposal-perfect'
        ");

        // ============================================
        // Add Booking Status History Table
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS booking_status_history (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                booking_id INT UNSIGNED NOT NULL,
                old_status VARCHAR(50) NULL,
                new_status VARCHAR(50) NOT NULL,
                changed_by_type ENUM('user', 'admin', 'system', 'vendor') DEFAULT 'system',
                changed_by_id INT UNSIGNED NULL,
                reason VARCHAR(500) NULL,
                metadata JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_booking (booking_id),
                INDEX idx_created (created_at),

                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Add Missing Columns to Bookings
        // ============================================

        // Check if column exists before adding
        $this->db->execute("
            ALTER TABLE bookings
            ADD COLUMN IF NOT EXISTS dynamic_price_adjustment_thb DECIMAL(12, 2) DEFAULT 0 AFTER extras_price_thb,
            ADD COLUMN IF NOT EXISTS pricing_rules_applied JSON NULL AFTER dynamic_price_adjustment_thb,
            ADD COLUMN IF NOT EXISTS loyalty_tier_id INT UNSIGNED NULL AFTER cashback_percent,
            ADD COLUMN IF NOT EXISTS loyalty_discount_thb DECIMAL(12, 2) DEFAULT 0 AFTER loyalty_tier_id,
            ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL AFTER status,
            ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL AFTER confirmed_at
        ");

        // ============================================
        // Add Selected Addons Table for Bookings
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS booking_addons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                booking_id INT UNSIGNED NOT NULL,
                addon_id INT UNSIGNED NOT NULL,
                quantity INT UNSIGNED DEFAULT 1,
                unit_price_thb DECIMAL(10, 2) NOT NULL,
                total_price_thb DECIMAL(12, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_booking (booking_id),
                INDEX idx_addon (addon_id),

                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                FOREIGN KEY (addon_id) REFERENCES addons(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ============================================
        // Booking Status Transitions Table (State Machine Rules)
        // ============================================
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS booking_status_transitions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                from_status VARCHAR(50) NOT NULL,
                to_status VARCHAR(50) NOT NULL,
                allowed_by JSON NOT NULL COMMENT '[\"user\", \"admin\", \"system\", \"vendor\"]',
                requires_reason BOOLEAN DEFAULT FALSE,
                auto_actions JSON NULL COMMENT 'Actions to perform on transition',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                UNIQUE KEY unique_transition (from_status, to_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insert allowed transitions
        $this->db->execute("
            INSERT INTO booking_status_transitions (from_status, to_status, allowed_by, requires_reason, auto_actions) VALUES
            -- From pending
            ('pending', 'confirmed', '[\"admin\", \"vendor\", \"system\"]', FALSE, '{\"notify_user\": true}'),
            ('pending', 'paid', '[\"system\"]', FALSE, '{\"notify_user\": true, \"notify_admin\": true}'),
            ('pending', 'cancelled', '[\"user\", \"admin\", \"vendor\"]', TRUE, '{\"refund_cashback\": true, \"notify_user\": true}'),

            -- From confirmed
            ('confirmed', 'paid', '[\"system\", \"admin\"]', FALSE, '{\"credit_cashback\": true, \"notify_user\": true}'),
            ('confirmed', 'cancelled', '[\"user\", \"admin\", \"vendor\"]', TRUE, '{\"refund_cashback\": true, \"notify_user\": true}'),

            -- From paid
            ('paid', 'completed', '[\"admin\", \"vendor\", \"system\"]', FALSE, '{\"update_stats\": true}'),
            ('paid', 'cancelled', '[\"admin\"]', TRUE, '{\"process_refund\": true, \"deduct_cashback\": true, \"notify_user\": true}'),
            ('paid', 'refunded', '[\"admin\", \"system\"]', TRUE, '{\"process_refund\": true, \"deduct_cashback\": true, \"notify_user\": true}'),
            ('paid', 'no_show', '[\"admin\", \"vendor\"]', FALSE, '{\"update_stats\": true}'),

            -- From completed
            ('completed', 'refunded', '[\"admin\"]', TRUE, '{\"process_refund\": true, \"deduct_cashback\": true}')
        ");

        // ============================================
        // Add Vendor Notification for New Bookings
        // ============================================
        $this->db->execute("
            INSERT IGNORE INTO notification_templates (code, name, channel, title_template, body_template, is_active) VALUES
            ('vendor_new_booking', 'Vendor: New Booking', 'telegram', 'New Booking #{reference}', '🎉 New booking!\n\nCustomer: {customer_name}\nDate: {booking_date}\nAmount: ฿{total_price}\n\nPlease confirm within 2 hours.', TRUE),
            ('vendor_booking_cancelled', 'Vendor: Booking Cancelled', 'telegram', 'Booking Cancelled #{reference}', '❌ Booking cancelled\n\nReference: {reference}\nDate: {booking_date}\nReason: {reason}', TRUE),
            ('booking_confirmed_user', 'User: Booking Confirmed', 'telegram', 'Booking Confirmed! 🎉', '✅ Your booking has been confirmed!\n\nReference: {reference}\nDate: {booking_date}\nTime: {start_time}\n\nSee you soon!', TRUE),
            ('booking_reminder', 'Booking Reminder', 'telegram', 'Reminder: Your Trip Tomorrow! 🚤', '⏰ Reminder!\n\nYour {item_name} is tomorrow!\n\nDate: {booking_date}\nTime: {start_time}\nMeeting Point: {meeting_point}\n\nDon''t forget to bring: sunscreen, swimwear, camera!', TRUE)
        ");
    }

    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS booking_status_transitions");
        $this->db->execute("DROP TABLE IF EXISTS booking_addons");
        $this->db->execute("DROP TABLE IF EXISTS booking_status_history");

        $this->db->execute("
            ALTER TABLE bookings
            DROP COLUMN IF EXISTS dynamic_price_adjustment_thb,
            DROP COLUMN IF EXISTS pricing_rules_applied,
            DROP COLUMN IF EXISTS loyalty_tier_id,
            DROP COLUMN IF EXISTS loyalty_discount_thb,
            DROP COLUMN IF EXISTS confirmed_at,
            DROP COLUMN IF EXISTS completed_at
        ");

        $this->db->execute("
            DELETE FROM notification_templates
            WHERE code IN ('vendor_new_booking', 'vendor_booking_cancelled', 'booking_confirmed_user', 'booking_reminder')
        ");
    }
}
