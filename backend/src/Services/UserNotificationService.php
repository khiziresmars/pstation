<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * User Notification Service
 * Handles in-app notifications for users (not admin notifications)
 */
class UserNotificationService
{
    private Database $db;

    // Notification types
    public const TYPE_BOOKING_CONFIRMED = 'booking_confirmed';
    public const TYPE_BOOKING_CANCELLED = 'booking_cancelled';
    public const TYPE_BOOKING_REMINDER = 'booking_reminder';
    public const TYPE_PAYMENT_RECEIVED = 'payment_received';
    public const TYPE_PAYMENT_REFUNDED = 'payment_refunded';
    public const TYPE_REVIEW_RESPONSE = 'review_response';
    public const TYPE_PROMO_AVAILABLE = 'promo_available';
    public const TYPE_CASHBACK_EARNED = 'cashback_earned';
    public const TYPE_SYSTEM = 'system';

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Create notification for user
     */
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): int {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ? json_encode($data) : null,
            'action_url' => $actionUrl,
            'is_read' => false,
        ]);
    }

    /**
     * Get user notifications
     */
    public function getForUser(int $userId, int $limit = 20, int $offset = 0, bool $unreadOnly = false): array
    {
        $sql = "
            SELECT * FROM notifications
            WHERE user_id = ?
        ";

        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

        $notifications = $this->db->query($sql, [$userId, $limit, $offset]);

        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'] ?? '{}', true);
        }

        return $notifications;
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $affected = $this->db->update(
            'notifications',
            ['is_read' => true, 'read_at' => date('Y-m-d H:i:s')],
            'id = ? AND user_id = ?',
            [$notificationId, $userId]
        );

        return $affected > 0;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->db->update(
            'notifications',
            ['is_read' => true, 'read_at' => date('Y-m-d H:i:s')],
            'user_id = ? AND is_read = 0',
            [$userId]
        );
    }

    /**
     * Delete notification
     */
    public function delete(int $notificationId, int $userId): bool
    {
        $affected = $this->db->delete(
            'notifications',
            'id = ? AND user_id = ?',
            [$notificationId, $userId]
        );

        return $affected > 0;
    }

    /**
     * Delete old read notifications
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return $this->db->delete(
            'notifications',
            'is_read = 1 AND created_at < ?',
            [$cutoff]
        );
    }

    // ====================
    // Notification Creators
    // ====================

    /**
     * Notify user about booking confirmation
     */
    public function notifyBookingConfirmed(int $userId, array $booking): int
    {
        $itemName = $booking['vessel_name'] ?? $booking['tour_name'] ?? 'Booking';

        return $this->create(
            $userId,
            self::TYPE_BOOKING_CONFIRMED,
            'Booking Confirmed',
            "Your booking for {$itemName} on {$booking['booking_date']} has been confirmed!",
            [
                'booking_reference' => $booking['reference'],
                'booking_date' => $booking['booking_date'],
            ],
            '/bookings/' . $booking['reference']
        );
    }

    /**
     * Notify user about booking cancellation
     */
    public function notifyBookingCancelled(int $userId, array $booking, ?string $reason = null): int
    {
        $itemName = $booking['vessel_name'] ?? $booking['tour_name'] ?? 'Booking';
        $message = "Your booking for {$itemName} has been cancelled.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        return $this->create(
            $userId,
            self::TYPE_BOOKING_CANCELLED,
            'Booking Cancelled',
            $message,
            [
                'booking_reference' => $booking['reference'],
                'reason' => $reason,
            ]
        );
    }

    /**
     * Notify user about upcoming booking (reminder)
     */
    public function notifyBookingReminder(int $userId, array $booking): int
    {
        $itemName = $booking['vessel_name'] ?? $booking['tour_name'] ?? 'Your booking';
        $date = date('M j', strtotime($booking['booking_date']));

        return $this->create(
            $userId,
            self::TYPE_BOOKING_REMINDER,
            'Booking Reminder',
            "{$itemName} is coming up on {$date}! Don't forget to prepare.",
            [
                'booking_reference' => $booking['reference'],
                'booking_date' => $booking['booking_date'],
            ],
            '/bookings/' . $booking['reference']
        );
    }

    /**
     * Notify user about payment received
     */
    public function notifyPaymentReceived(int $userId, array $payment): int
    {
        $amount = number_format($payment['amount'], 0);

        return $this->create(
            $userId,
            self::TYPE_PAYMENT_RECEIVED,
            'Payment Received',
            "We've received your payment of ฿{$amount}. Thank you!",
            [
                'amount' => $payment['amount'],
                'booking_reference' => $payment['booking_reference'] ?? null,
            ]
        );
    }

    /**
     * Notify user about refund
     */
    public function notifyPaymentRefunded(int $userId, array $refund): int
    {
        $amount = number_format($refund['amount'], 0);

        return $this->create(
            $userId,
            self::TYPE_PAYMENT_REFUNDED,
            'Refund Processed',
            "Your refund of ฿{$amount} has been processed.",
            [
                'amount' => $refund['amount'],
                'booking_reference' => $refund['booking_reference'] ?? null,
            ]
        );
    }

    /**
     * Notify user about admin response to their review
     */
    public function notifyReviewResponse(int $userId, array $review): int
    {
        return $this->create(
            $userId,
            self::TYPE_REVIEW_RESPONSE,
            'Response to Your Review',
            "The team has responded to your review. Thank you for your feedback!",
            [
                'review_id' => $review['id'],
            ]
        );
    }

    /**
     * Notify user about new promo code
     */
    public function notifyPromoAvailable(int $userId, array $promo): int
    {
        $discount = $promo['discount_type'] === 'percentage'
            ? "{$promo['discount_value']}% off"
            : "฿{$promo['discount_value']} off";

        return $this->create(
            $userId,
            self::TYPE_PROMO_AVAILABLE,
            'Special Offer for You!',
            "Use code {$promo['code']} for {$discount} your next booking!",
            [
                'promo_code' => $promo['code'],
                'discount' => $discount,
                'expires_at' => $promo['expires_at'] ?? null,
            ]
        );
    }

    /**
     * Notify user about cashback earned
     */
    public function notifyCashbackEarned(int $userId, float $amount, string $source): int
    {
        $formatted = number_format($amount, 0);

        return $this->create(
            $userId,
            self::TYPE_CASHBACK_EARNED,
            'Cashback Earned!',
            "You've earned ฿{$formatted} cashback from {$source}!",
            [
                'amount' => $amount,
                'source' => $source,
            ],
            '/profile#cashback'
        );
    }

    /**
     * Send system notification to user
     */
    public function notifySystem(int $userId, string $title, string $message, ?string $actionUrl = null): int
    {
        return $this->create($userId, self::TYPE_SYSTEM, $title, $message, null, $actionUrl);
    }

    /**
     * Send notification to multiple users
     */
    public function notifyBulk(array $userIds, string $type, string $title, string $message, ?array $data = null): array
    {
        $results = [];

        foreach ($userIds as $userId) {
            $results[$userId] = $this->create($userId, $type, $title, $message, $data);
        }

        return $results;
    }

    /**
     * Send notification to all users
     */
    public function notifyAll(string $title, string $message, ?array $data = null): int
    {
        $users = $this->db->query("SELECT id FROM users WHERE is_blocked = 0");
        $count = 0;

        foreach ($users as $user) {
            $this->create($user['id'], self::TYPE_SYSTEM, $title, $message, $data);
            $count++;
        }

        return $count;
    }
}
