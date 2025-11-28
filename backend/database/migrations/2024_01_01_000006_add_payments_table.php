<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Core\Database;

/**
 * Migration: Add payments table for multi-provider payment tracking
 */
class Migration_2024_01_01_000006
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function up(): void
    {
        // Create payments table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                provider VARCHAR(50) NOT NULL COMMENT 'stripe, nowpayments, telegram_stars, bank_transfer',
                provider_payment_id VARCHAR(255) NOT NULL,
                amount_thb DECIMAL(12, 2) NOT NULL,
                amount_provider DECIMAL(20, 8) NOT NULL COMMENT 'Amount in provider currency',
                currency VARCHAR(10) NOT NULL DEFAULT 'THB',
                status VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, completed, failed, refunded',
                metadata JSON DEFAULT NULL,
                error_message TEXT DEFAULT NULL,
                refund_amount DECIMAL(12, 2) DEFAULT NULL,
                refund_reason TEXT DEFAULT NULL,
                refunded_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_booking (booking_id),
                INDEX idx_provider (provider),
                INDEX idx_status (status),
                INDEX idx_provider_payment (provider_payment_id),
                INDEX idx_created (created_at),

                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Add payment fields to bookings table if not exist
        $columns = $this->db->fetchAll("SHOW COLUMNS FROM bookings");
        $columnNames = array_column($columns, 'Field');

        if (!in_array('payment_transaction_id', $columnNames)) {
            $this->db->execute("
                ALTER TABLE bookings
                ADD COLUMN payment_transaction_id VARCHAR(255) DEFAULT NULL AFTER payment_method
            ");
        }

        if (!in_array('paid_at', $columnNames)) {
            $this->db->execute("
                ALTER TABLE bookings
                ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER payment_transaction_id
            ");
        }

        // Create payment webhooks log table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS payment_webhook_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(50) NOT NULL,
                event_type VARCHAR(100) DEFAULT NULL,
                payload JSON NOT NULL,
                signature VARCHAR(255) DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'received' COMMENT 'received, processed, failed',
                error_message TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                processed_at DATETIME DEFAULT NULL,

                INDEX idx_provider (provider),
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        echo "Migration 000006: Payments table created successfully.\n";
    }

    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS payment_webhook_logs");
        $this->db->execute("DROP TABLE IF EXISTS payments");

        echo "Migration 000006: Payments table dropped.\n";
    }
}
