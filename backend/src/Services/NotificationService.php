<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;
use App\Core\Logger;

/**
 * Notification Service
 * Handles sending notifications via Telegram and email
 */
class NotificationService
{
    private Database $db;
    private Logger $logger;
    private string $botToken;
    private ?string $adminGroupChatId;
    private bool $enabled;

    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = $app->getDatabase();
        $this->logger = new Logger('notifications');
        $this->botToken = $app->getConfig('telegram.bot_token');

        // Get settings
        $settings = $this->getSettings();
        $this->enabled = ($settings['notifications_enabled'] ?? 'true') === 'true';
        $this->adminGroupChatId = $settings['admin_group_chat_id'] ?? null;
    }

    /**
     * Send notification using template
     */
    public function sendFromTemplate(string $templateCode, array $variables, ?array $recipients = null): array
    {
        $template = $this->getTemplate($templateCode);

        if (!$template || !$template['is_active']) {
            $this->logger->warning("Template not found or inactive: {$templateCode}");
            return ['success' => false, 'error' => 'Template not found'];
        }

        $message = $this->interpolate($template['template_text'], $variables);
        $results = [];

        // Determine recipients
        if ($recipients === null) {
            $recipients = $this->getRecipientsByTemplate($template);
        }

        foreach ($recipients as $recipient) {
            $result = $this->send(
                $recipient['telegram_id'],
                $message,
                $recipient['type'],
                $recipient['id'],
                $templateCode
            );
            $results[] = $result;
        }

        return [
            'success' => true,
            'sent' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success'])),
        ];
    }

    /**
     * Send notification to specific Telegram chat
     */
    public function send(
        int|string $chatId,
        string $message,
        string $recipientType = 'admin',
        ?int $recipientId = null,
        ?string $templateCode = null
    ): array {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'Notifications disabled'];
        }

        // Log the notification
        $logId = $this->db->insert('notification_log', [
            'template_code' => $templateCode,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId ?? 0,
            'channel' => 'telegram',
            'message' => $message,
            'status' => 'pending',
        ]);

        // Send via Telegram
        $result = $this->sendTelegramMessage($chatId, $message);

        // Update log
        $this->db->update('notification_log', [
            'status' => $result['success'] ? 'sent' : 'failed',
            'error_message' => $result['error'] ?? null,
            'sent_at' => $result['success'] ? date('Y-m-d H:i:s') : null,
        ], 'id = ?', [$logId]);

        return $result;
    }

    /**
     * Send to admin group
     */
    public function sendToAdminGroup(string $message, ?string $templateCode = null): array
    {
        if (!$this->adminGroupChatId) {
            $this->logger->warning('Admin group chat ID not configured');
            return ['success' => false, 'error' => 'Admin group not configured'];
        }

        return $this->send($this->adminGroupChatId, $message, 'admin', null, $templateCode);
    }

    /**
     * Send to admins by role
     */
    public function sendToAdminsByRole(string $roleName, string $message, ?string $templateCode = null): array
    {
        $admins = $this->db->query("
            SELECT a.id, a.telegram_id, a.notification_settings
            FROM admins a
            JOIN admin_roles r ON a.role_id = r.id
            WHERE r.name = ? AND a.is_active = 1
        ", [$roleName]);

        $results = [];
        foreach ($admins as $admin) {
            $settings = json_decode($admin['notification_settings'] ?? '{}', true);

            // Check if admin wants this type of notification
            if ($templateCode && isset($settings['disabled_templates'])) {
                if (in_array($templateCode, $settings['disabled_templates'])) {
                    continue;
                }
            }

            $results[] = $this->send(
                $admin['telegram_id'],
                $message,
                'admin',
                $admin['id'],
                $templateCode
            );
        }

        return [
            'success' => true,
            'sent' => count(array_filter($results, fn($r) => $r['success'])),
        ];
    }

    /**
     * Send to all active admins
     */
    public function sendToAllAdmins(string $message, ?string $templateCode = null): array
    {
        $admins = $this->db->query("
            SELECT id, telegram_id, notification_settings
            FROM admins
            WHERE is_active = 1
        ");

        $results = [];
        foreach ($admins as $admin) {
            $results[] = $this->send(
                $admin['telegram_id'],
                $message,
                'admin',
                $admin['id'],
                $templateCode
            );
        }

        return [
            'success' => true,
            'sent' => count(array_filter($results, fn($r) => $r['success'])),
        ];
    }

    /**
     * Send to user
     */
    public function sendToUser(int $userId, string $message, ?string $templateCode = null): array
    {
        $user = $this->db->queryOne("SELECT id, telegram_id FROM users WHERE id = ?", [$userId]);

        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }

        return $this->send($user['telegram_id'], $message, 'user', $user['id'], $templateCode);
    }

    // ==================== Booking Notifications ====================

    /**
     * Notify about new booking
     */
    public function notifyNewBooking(array $booking): void
    {
        $settings = $this->getSettings();
        if (($settings['notify_on_new_booking'] ?? 'true') !== 'true') {
            return;
        }

        $variables = $this->prepareBookingVariables($booking);
        $this->sendFromTemplate('new_booking', $variables);
    }

    /**
     * Notify about payment received
     */
    public function notifyPaymentReceived(array $booking): void
    {
        $settings = $this->getSettings();
        if (($settings['notify_on_payment'] ?? 'true') !== 'true') {
            return;
        }

        $variables = $this->prepareBookingVariables($booking);
        $variables['amount'] = number_format($booking['total_price_thb'], 0);
        $variables['payment_method'] = $this->formatPaymentMethod($booking['payment_method'] ?? 'unknown');

        $this->sendFromTemplate('booking_paid', $variables);
    }

    /**
     * Notify about booking cancellation
     */
    public function notifyBookingCancelled(array $booking, ?string $reason = null): void
    {
        $settings = $this->getSettings();
        if (($settings['notify_on_cancellation'] ?? 'true') !== 'true') {
            return;
        }

        $variables = $this->prepareBookingVariables($booking);
        $variables['reason'] = $reason ?? 'Not specified';

        $this->sendFromTemplate('booking_cancelled', $variables);
    }

    /**
     * Notify about new review
     */
    public function notifyNewReview(array $review): void
    {
        $settings = $this->getSettings();
        if (($settings['notify_on_new_review'] ?? 'true') !== 'true') {
            return;
        }

        $variables = [
            'item_name' => $review['item_name'] ?? 'Unknown',
            'rating' => $review['rating'],
            'customer_name' => $review['user_name'] ?? 'Anonymous',
            'review_text' => mb_substr($review['comment'] ?? '', 0, 200),
        ];

        $this->sendFromTemplate('new_review', $variables);
    }

    /**
     * Send user booking confirmation
     */
    public function sendUserBookingConfirmation(array $booking): void
    {
        $variables = $this->prepareBookingVariables($booking);

        // Get meeting point info
        $item = $this->getBookingItem($booking);
        $variables['meeting_point'] = $item['meeting_point'] ?? 'To be confirmed';
        $variables['contact_phone'] = $this->getSettings()['contact_phone'] ?? '+66 XX XXX XXXX';

        $this->sendFromTemplate('user_booking_confirmed', $variables, [
            ['type' => 'user', 'id' => $booking['user_id'], 'telegram_id' => $booking['user_telegram_id']],
        ]);
    }

    /**
     * Send daily summary report
     */
    public function sendDailySummary(?string $date = null): void
    {
        $settings = $this->getSettings();
        if (($settings['daily_summary_enabled'] ?? 'true') !== 'true') {
            return;
        }

        $date = $date ?? date('Y-m-d');
        $stats = $this->getDailyStats($date);

        $topItems = array_map(
            fn($item) => "• {$item['name']}: {$item['count']} bookings",
            array_slice($stats['top_items'], 0, 5)
        );

        $variables = [
            'date' => date('M d, Y', strtotime($date)),
            'new_bookings' => $stats['new_bookings'],
            'revenue' => number_format($stats['revenue'], 0),
            'guests' => $stats['total_guests'],
            'reviews' => $stats['new_reviews'],
            'top_items' => implode("\n", $topItems) ?: 'No bookings today',
        ];

        $this->sendFromTemplate('daily_summary', $variables);
    }

    // ==================== Internal Methods ====================

    /**
     * Send Telegram message
     */
    private function sendTelegramMessage(int|string $chatId, string $message): array
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error("Telegram API error: {$error}");
            return ['success' => false, 'error' => $error];
        }

        $result = json_decode($response, true);

        if (!($result['ok'] ?? false)) {
            $errorMsg = $result['description'] ?? 'Unknown error';
            $this->logger->error("Telegram send failed: {$errorMsg}");
            return ['success' => false, 'error' => $errorMsg];
        }

        return ['success' => true, 'message_id' => $result['result']['message_id'] ?? null];
    }

    /**
     * Get notification template
     */
    private function getTemplate(string $code): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM notification_templates WHERE code = ?",
            [$code]
        );
    }

    /**
     * Get recipients for template
     */
    private function getRecipientsByTemplate(array $template): array
    {
        $recipients = [];

        if (in_array($template['type'], ['admin', 'both'])) {
            $admins = $this->db->query("
                SELECT id, telegram_id FROM admins WHERE is_active = 1
            ");

            foreach ($admins as $admin) {
                $recipients[] = [
                    'type' => 'admin',
                    'id' => $admin['id'],
                    'telegram_id' => $admin['telegram_id'],
                ];
            }
        }

        return $recipients;
    }

    /**
     * Get settings from database
     */
    private function getSettings(): array
    {
        $settings = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $result = [];
        foreach ($settings as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    /**
     * Interpolate variables into template
     */
    private function interpolate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{$key}}", (string) $value, $template);
        }
        return $template;
    }

    /**
     * Prepare booking variables for templates
     */
    private function prepareBookingVariables(array $booking): array
    {
        return [
            'reference' => $booking['booking_reference'],
            'type' => ucfirst($booking['bookable_type'] ?? 'item'),
            'item_name' => $booking['item_name'] ?? 'Unknown',
            'date' => date('M d, Y', strtotime($booking['booking_date'])),
            'time' => $booking['start_time'] ?? '09:00',
            'guests' => ($booking['adults_count'] ?? 0) + ($booking['children_count'] ?? 0),
            'total' => number_format($booking['total_price_thb'], 0),
            'customer_name' => trim(($booking['user_first_name'] ?? '') . ' ' . ($booking['user_last_name'] ?? '')) ?: 'Guest',
            'phone' => $booking['contact_phone'] ?? 'Not provided',
        ];
    }

    /**
     * Get booking item details
     */
    private function getBookingItem(array $booking): ?array
    {
        if ($booking['bookable_type'] === 'vessel') {
            return $this->db->queryOne(
                "SELECT * FROM vessels WHERE id = ?",
                [$booking['bookable_id']]
            );
        } elseif ($booking['bookable_type'] === 'tour') {
            return $this->db->queryOne(
                "SELECT * FROM tours WHERE id = ?",
                [$booking['bookable_id']]
            );
        }
        return null;
    }

    /**
     * Format payment method for display
     */
    private function formatPaymentMethod(string $method): string
    {
        return match ($method) {
            'telegram_stars' => '⭐ Telegram Stars',
            'bank_transfer' => '🏦 Bank Transfer',
            'credit_card' => '💳 Credit Card',
            'cash' => '💵 Cash',
            'crypto' => '₿ Crypto',
            default => ucfirst($method),
        };
    }

    /**
     * Get daily statistics
     */
    private function getDailyStats(string $date): array
    {
        $stats = $this->db->queryOne("
            SELECT
                COUNT(*) as new_bookings,
                COALESCE(SUM(total_price_thb), 0) as revenue,
                COALESCE(SUM(adults_count + children_count), 0) as total_guests
            FROM bookings
            WHERE DATE(created_at) = ?
        ", [$date]);

        $reviews = $this->db->queryOne("
            SELECT COUNT(*) as count FROM reviews WHERE DATE(created_at) = ?
        ", [$date]);

        $topItems = $this->db->query("
            SELECT
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as name,
                COUNT(*) as count
            FROM bookings b
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            WHERE DATE(b.created_at) = ?
            GROUP BY b.bookable_type, b.bookable_id, name
            ORDER BY count DESC
            LIMIT 5
        ", [$date]);

        return [
            'new_bookings' => (int) ($stats['new_bookings'] ?? 0),
            'revenue' => (float) ($stats['revenue'] ?? 0),
            'total_guests' => (int) ($stats['total_guests'] ?? 0),
            'new_reviews' => (int) ($reviews['count'] ?? 0),
            'top_items' => $topItems,
        ];
    }
}
