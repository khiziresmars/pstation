<?php

declare(strict_types=1);

use App\Core\Database;

class AddNotificationSettings
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    public function up(): void
    {
        // Create notification_templates table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS notification_templates (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(100) NOT NULL UNIQUE,
                name VARCHAR(200) NOT NULL,
                description TEXT,
                type ENUM('admin', 'user', 'both') DEFAULT 'both',
                channel ENUM('telegram', 'email', 'both') DEFAULT 'telegram',
                template_text TEXT NOT NULL,
                template_html TEXT,
                variables JSON COMMENT 'Available template variables',
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create notification_log table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS notification_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                template_code VARCHAR(100),
                recipient_type ENUM('admin', 'user') NOT NULL,
                recipient_id INT UNSIGNED NOT NULL,
                channel ENUM('telegram', 'email') NOT NULL,
                message TEXT NOT NULL,
                status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
                error_message TEXT,
                metadata JSON,
                sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notification_recipient (recipient_type, recipient_id),
                INDEX idx_notification_status (status),
                INDEX idx_notification_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insert default notification templates
        $this->db->execute("
            INSERT INTO notification_templates (code, name, description, type, channel, template_text, variables) VALUES
            ('new_booking', 'New Booking Alert', 'Sent when a new booking is created', 'admin', 'telegram',
             '🆕 *New Booking!*\n\nReference: `{reference}`\nType: {type}\nItem: {item_name}\nDate: {date}\nGuests: {guests}\nTotal: ฿{total}\n\nCustomer: {customer_name}\nPhone: {phone}',
             JSON_ARRAY('reference', 'type', 'item_name', 'date', 'guests', 'total', 'customer_name', 'phone')),

            ('booking_paid', 'Booking Paid', 'Sent when payment is confirmed', 'admin', 'telegram',
             '💰 *Payment Received!*\n\nReference: `{reference}`\nAmount: ฿{amount}\nMethod: {payment_method}\n\nItem: {item_name}\nDate: {date}',
             JSON_ARRAY('reference', 'amount', 'payment_method', 'item_name', 'date')),

            ('booking_cancelled', 'Booking Cancelled', 'Sent when booking is cancelled', 'admin', 'telegram',
             '❌ *Booking Cancelled*\n\nReference: `{reference}`\nReason: {reason}\n\nItem: {item_name}\nDate: {date}\nCustomer: {customer_name}',
             JSON_ARRAY('reference', 'reason', 'item_name', 'date', 'customer_name')),

            ('low_availability', 'Low Availability Alert', 'Sent when vessel/tour has few slots left', 'admin', 'telegram',
             '⚠️ *Low Availability*\n\n{type}: {item_name}\nDate: {date}\nRemaining slots: {remaining}',
             JSON_ARRAY('type', 'item_name', 'date', 'remaining')),

            ('new_review', 'New Review', 'Sent when new review is submitted', 'admin', 'telegram',
             '⭐ *New Review*\n\nItem: {item_name}\nRating: {rating}/5\nCustomer: {customer_name}\n\n\"{review_text}\"',
             JSON_ARRAY('item_name', 'rating', 'customer_name', 'review_text')),

            ('daily_summary', 'Daily Summary', 'Daily booking summary report', 'admin', 'telegram',
             '📊 *Daily Summary - {date}*\n\n📌 New bookings: {new_bookings}\n💰 Revenue: ฿{revenue}\n👥 Total guests: {guests}\n⭐ New reviews: {reviews}\n\nTop items:\n{top_items}',
             JSON_ARRAY('date', 'new_bookings', 'revenue', 'guests', 'reviews', 'top_items')),

            ('user_booking_confirmed', 'Booking Confirmation', 'Sent to user when booking is confirmed', 'user', 'telegram',
             '✅ *Booking Confirmed!*\n\nReference: `{reference}`\n\n{item_name}\n📅 {date} at {time}\n👥 {guests} guests\n\n💰 Total: ฿{total}\n\nMeeting point:\n{meeting_point}\n\nContact: {contact_phone}',
             JSON_ARRAY('reference', 'item_name', 'date', 'time', 'guests', 'total', 'meeting_point', 'contact_phone')),

            ('user_booking_reminder', 'Booking Reminder', 'Sent 24h before booking', 'user', 'telegram',
             '🔔 *Reminder: Tomorrow!*\n\nYour booking `{reference}` is tomorrow!\n\n{item_name}\n📅 {date} at {time}\n\n📍 Meeting point:\n{meeting_point}\n\nHave a great trip! 🌊',
             JSON_ARRAY('reference', 'item_name', 'date', 'time', 'meeting_point')),

            ('referral_bonus', 'Referral Bonus', 'Sent when referral bonus is credited', 'user', 'telegram',
             '🎁 *Referral Bonus!*\n\nYour friend {friend_name} made their first booking!\n\n+฿{bonus} added to your cashback balance.\n\nCurrent balance: ฿{balance}',
             JSON_ARRAY('friend_name', 'bonus', 'balance'))
        ");

        // Add notification preferences to settings
        $this->db->execute("
            INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
            ('notifications_enabled', 'true', 'boolean', 'Enable/disable all notifications'),
            ('admin_group_chat_id', '', 'string', 'Telegram group chat ID for admin notifications'),
            ('notify_on_new_booking', 'true', 'boolean', 'Send notification on new booking'),
            ('notify_on_payment', 'true', 'boolean', 'Send notification on payment'),
            ('notify_on_cancellation', 'true', 'boolean', 'Send notification on cancellation'),
            ('notify_on_new_review', 'true', 'boolean', 'Send notification on new review'),
            ('daily_summary_enabled', 'true', 'boolean', 'Send daily summary report'),
            ('daily_summary_time', '20:00', 'string', 'Time to send daily summary (Asia/Bangkok)')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS notification_log");
        $this->db->execute("DROP TABLE IF EXISTS notification_templates");

        $this->db->execute("
            DELETE FROM settings WHERE setting_key IN (
                'notifications_enabled', 'admin_group_chat_id', 'notify_on_new_booking',
                'notify_on_payment', 'notify_on_cancellation', 'notify_on_new_review',
                'daily_summary_enabled', 'daily_summary_time'
            )
        ");
    }
}
