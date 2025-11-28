<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Application;

/**
 * Booking Status Service
 * Manages booking status transitions with state machine validation
 */
class BookingStatusService
{
    private Database $db;

    // Valid statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_NO_SHOW = 'no_show';

    // Actor types
    public const ACTOR_USER = 'user';
    public const ACTOR_ADMIN = 'admin';
    public const ACTOR_VENDOR = 'vendor';
    public const ACTOR_SYSTEM = 'system';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Check if status transition is allowed
     */
    public function canTransition(string $fromStatus, string $toStatus, string $actorType): bool
    {
        $transition = $this->db->fetchOne(
            "SELECT * FROM booking_status_transitions
             WHERE from_status = :from AND to_status = :to",
            ['from' => $fromStatus, 'to' => $toStatus]
        );

        if (!$transition) {
            return false;
        }

        $allowedBy = json_decode($transition['allowed_by'], true) ?? [];

        return in_array($actorType, $allowedBy);
    }

    /**
     * Get allowed transitions from current status
     */
    public function getAllowedTransitions(string $currentStatus, string $actorType): array
    {
        $transitions = $this->db->fetchAll(
            "SELECT to_status, requires_reason FROM booking_status_transitions
             WHERE from_status = :from",
            ['from' => $currentStatus]
        );

        $allowed = [];
        foreach ($transitions as $t) {
            $allowedBy = json_decode($t['allowed_by'] ?? '[]', true);
            if (in_array($actorType, $allowedBy)) {
                $allowed[] = [
                    'status' => $t['to_status'],
                    'requires_reason' => (bool) $t['requires_reason']
                ];
            }
        }

        return $allowed;
    }

    /**
     * Transition booking status
     */
    public function transition(
        int $bookingId,
        string $newStatus,
        string $actorType,
        ?int $actorId = null,
        ?string $reason = null,
        array $metadata = []
    ): array {
        // Get current booking
        $booking = $this->db->fetchOne(
            "SELECT * FROM bookings WHERE id = :id",
            ['id' => $bookingId]
        );

        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }

        $currentStatus = $booking['status'];

        // Same status - no change needed
        if ($currentStatus === $newStatus) {
            return ['success' => true, 'message' => 'Status unchanged'];
        }

        // Validate transition
        if (!$this->canTransition($currentStatus, $newStatus, $actorType)) {
            return [
                'success' => false,
                'error' => "Cannot transition from '{$currentStatus}' to '{$newStatus}' as {$actorType}"
            ];
        }

        // Get transition rules
        $transition = $this->db->fetchOne(
            "SELECT * FROM booking_status_transitions
             WHERE from_status = :from AND to_status = :to",
            ['from' => $currentStatus, 'to' => $newStatus]
        );

        // Check if reason required
        if ($transition['requires_reason'] && empty($reason)) {
            return ['success' => false, 'error' => 'Reason is required for this status change'];
        }

        $this->db->beginTransaction();

        try {
            // Update booking status
            $updateData = ['status' => $newStatus];

            // Add timestamps based on new status
            switch ($newStatus) {
                case self::STATUS_CONFIRMED:
                    $updateData['confirmed_at'] = date('Y-m-d H:i:s');
                    break;
                case self::STATUS_PAID:
                    $updateData['paid_at'] = date('Y-m-d H:i:s');
                    break;
                case self::STATUS_COMPLETED:
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                    break;
                case self::STATUS_CANCELLED:
                    $updateData['cancelled_at'] = date('Y-m-d H:i:s');
                    $updateData['cancellation_reason'] = $reason;
                    break;
            }

            $this->db->execute(
                "UPDATE bookings SET " . $this->buildUpdateSql($updateData) . " WHERE id = :id",
                array_merge($updateData, ['id' => $bookingId])
            );

            // Log status change
            $this->logStatusChange($bookingId, $currentStatus, $newStatus, $actorType, $actorId, $reason, $metadata);

            // Execute auto actions
            $this->executeAutoActions($booking, $currentStatus, $newStatus, $transition);

            $this->db->commit();

            return [
                'success' => true,
                'old_status' => $currentStatus,
                'new_status' => $newStatus
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log status change to history
     */
    private function logStatusChange(
        int $bookingId,
        ?string $oldStatus,
        string $newStatus,
        string $actorType,
        ?int $actorId,
        ?string $reason,
        array $metadata
    ): void {
        $this->db->execute(
            "INSERT INTO booking_status_history
             (booking_id, old_status, new_status, changed_by_type, changed_by_id, reason, metadata)
             VALUES (:booking_id, :old_status, :new_status, :changed_by_type, :changed_by_id, :reason, :metadata)",
            [
                'booking_id' => $bookingId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by_type' => $actorType,
                'changed_by_id' => $actorId,
                'reason' => $reason,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null
            ]
        );
    }

    /**
     * Execute automatic actions on status transition
     */
    private function executeAutoActions(array $booking, string $fromStatus, string $toStatus, array $transition): void
    {
        $actions = json_decode($transition['auto_actions'] ?? '{}', true);

        if (empty($actions)) {
            return;
        }

        $userService = new UserService();
        $notificationService = new UserNotificationService($this->db);

        // Credit cashback on payment
        if (!empty($actions['credit_cashback']) && $toStatus === self::STATUS_PAID) {
            if ($booking['cashback_earned_thb'] > 0) {
                $userService->addCashback(
                    $booking['user_id'],
                    $booking['cashback_earned_thb'],
                    $booking['id'],
                    "Cashback from booking {$booking['booking_reference']}"
                );

                $this->db->execute(
                    "UPDATE bookings SET cashback_status = 'credited' WHERE id = :id",
                    ['id' => $booking['id']]
                );

                // Update user loyalty stats
                $loyaltyService = new LoyaltyService($this->db);
                $loyaltyService->recordBooking($booking['user_id'], $booking['total_price_thb']);
            }
        }

        // Refund cashback on cancellation
        if (!empty($actions['refund_cashback'])) {
            if ($booking['cashback_used_thb'] > 0) {
                $userService->addCashback(
                    $booking['user_id'],
                    $booking['cashback_used_thb'],
                    $booking['id'],
                    "Refund from cancelled booking {$booking['booking_reference']}"
                );
            }
        }

        // Deduct cashback on refund
        if (!empty($actions['deduct_cashback'])) {
            if ($booking['cashback_status'] === 'credited' && $booking['cashback_earned_thb'] > 0) {
                $userService->useCashback(
                    $booking['user_id'],
                    $booking['cashback_earned_thb'],
                    $booking['id']
                );

                $this->db->execute(
                    "UPDATE bookings SET cashback_status = 'cancelled' WHERE id = :id",
                    ['id' => $booking['id']]
                );
            }
        }

        // Refund gift card
        if (!empty($actions['process_refund']) && $booking['gift_card_id'] && $booking['gift_card_amount_thb'] > 0) {
            $giftCardService = new GiftCardService($this->db);
            $giftCardService->refund($booking['gift_card_id'], $booking['id'], $booking['gift_card_amount_thb']);
        }

        // Update vendor stats
        if (!empty($actions['update_stats']) && $booking['vendor_id']) {
            $this->db->execute(
                "UPDATE vendors SET
                 total_bookings = total_bookings + 1,
                 total_revenue_thb = total_revenue_thb + :revenue
                 WHERE id = :id",
                ['revenue' => $booking['total_price_thb'], 'id' => $booking['vendor_id']]
            );
        }

        // Send notifications
        if (!empty($actions['notify_user'])) {
            $this->notifyUser($booking, $toStatus);
        }

        if (!empty($actions['notify_admin'])) {
            // Notify admin via existing NotificationService
        }

        if (!empty($actions['notify_vendor']) && $booking['vendor_id']) {
            $this->notifyVendor($booking, $toStatus);
        }
    }

    /**
     * Notify user about status change
     */
    private function notifyUser(array $booking, string $newStatus): void
    {
        $notificationService = new UserNotificationService($this->db);

        $typeMap = [
            self::STATUS_CONFIRMED => UserNotificationService::TYPE_BOOKING_CONFIRMED,
            self::STATUS_CANCELLED => UserNotificationService::TYPE_BOOKING_CANCELLED,
            self::STATUS_PAID => UserNotificationService::TYPE_PAYMENT_RECEIVED,
        ];

        if (isset($typeMap[$newStatus])) {
            switch ($newStatus) {
                case self::STATUS_CONFIRMED:
                    $notificationService->notifyBookingConfirmed($booking['user_id'], $booking);
                    break;
                case self::STATUS_CANCELLED:
                    $notificationService->notifyBookingCancelled($booking['user_id'], $booking);
                    break;
            }
        }
    }

    /**
     * Notify vendor about booking
     */
    private function notifyVendor(array $booking, string $status): void
    {
        // Get vendor
        $vendor = $this->db->fetchOne(
            "SELECT telegram_id FROM vendors WHERE id = :id",
            ['id' => $booking['vendor_id']]
        );

        if (!$vendor || !$vendor['telegram_id']) {
            return;
        }

        // Send Telegram notification to vendor
        // This would use the TelegramService to send a message
    }

    /**
     * Get status history for booking
     */
    public function getHistory(int $bookingId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM booking_status_history
             WHERE booking_id = :booking_id
             ORDER BY created_at ASC",
            ['booking_id' => $bookingId]
        );
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabel(string $status, string $lang = 'en'): string
    {
        $labels = [
            'en' => [
                self::STATUS_PENDING => 'Pending',
                self::STATUS_CONFIRMED => 'Confirmed',
                self::STATUS_PAID => 'Paid',
                self::STATUS_COMPLETED => 'Completed',
                self::STATUS_CANCELLED => 'Cancelled',
                self::STATUS_REFUNDED => 'Refunded',
                self::STATUS_NO_SHOW => 'No Show'
            ],
            'ru' => [
                self::STATUS_PENDING => 'Ожидает',
                self::STATUS_CONFIRMED => 'Подтверждено',
                self::STATUS_PAID => 'Оплачено',
                self::STATUS_COMPLETED => 'Завершено',
                self::STATUS_CANCELLED => 'Отменено',
                self::STATUS_REFUNDED => 'Возврат',
                self::STATUS_NO_SHOW => 'Не явился'
            ],
            'th' => [
                self::STATUS_PENDING => 'รอดำเนินการ',
                self::STATUS_CONFIRMED => 'ยืนยันแล้ว',
                self::STATUS_PAID => 'ชำระแล้ว',
                self::STATUS_COMPLETED => 'เสร็จสิ้น',
                self::STATUS_CANCELLED => 'ยกเลิก',
                self::STATUS_REFUNDED => 'คืนเงิน',
                self::STATUS_NO_SHOW => 'ไม่มาตามนัด'
            ]
        ];

        return $labels[$lang][$status] ?? $labels['en'][$status] ?? $status;
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_COMPLETED => 'gray',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_REFUNDED => 'orange',
            self::STATUS_NO_SHOW => 'purple',
            default => 'gray'
        };
    }

    /**
     * Build SQL SET clause from array
     */
    private function buildUpdateSql(array $data): string
    {
        $parts = [];
        foreach (array_keys($data) as $key) {
            $parts[] = "{$key} = :{$key}";
        }
        return implode(', ', $parts);
    }

    /**
     * Batch update expired pending bookings
     */
    public function expirePendingBookings(int $hoursOld = 24): int
    {
        // Get pending bookings older than X hours
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$hoursOld} hours"));

        $pendingBookings = $this->db->fetchAll(
            "SELECT id FROM bookings
             WHERE status = 'pending'
             AND created_at < :cutoff",
            ['cutoff' => $cutoff]
        );

        $count = 0;
        foreach ($pendingBookings as $booking) {
            $result = $this->transition(
                $booking['id'],
                self::STATUS_CANCELLED,
                self::ACTOR_SYSTEM,
                null,
                'Automatically cancelled: payment not received within 24 hours'
            );

            if ($result['success']) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Auto-complete bookings after trip date
     */
    public function autoCompleteBookings(): int
    {
        // Get paid bookings where booking date has passed
        $paidBookings = $this->db->fetchAll(
            "SELECT id FROM bookings
             WHERE status = 'paid'
             AND booking_date < CURDATE()"
        );

        $count = 0;
        foreach ($paidBookings as $booking) {
            $result = $this->transition(
                $booking['id'],
                self::STATUS_COMPLETED,
                self::ACTOR_SYSTEM
            );

            if ($result['success']) {
                $count++;
            }
        }

        return $count;
    }
}
