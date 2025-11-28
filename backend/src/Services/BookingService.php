<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * Booking Service
 * Handles booking operations
 */
class BookingService
{
    private Database $db;
    private VesselService $vesselService;
    private TourService $tourService;
    private PromoService $promoService;
    private UserService $userService;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
        $this->vesselService = new VesselService();
        $this->tourService = new TourService();
        $this->promoService = new PromoService();
        $this->userService = new UserService();
    }

    /**
     * Create a new booking
     */
    public function create(int $userId, array $data): array
    {
        $this->db->beginTransaction();

        try {
            // Validate and get item
            $item = $this->getBookableItem($data['type'], $data['item_id']);
            if (!$item) {
                throw new \Exception('Item not found');
            }

            // Calculate price
            $pricing = $this->calculateTotalPrice($data);
            if (isset($pricing['error'])) {
                throw new \Exception($pricing['error']);
            }

            // Apply promo code
            $promoDiscount = 0;
            $promoCodeId = null;
            if (!empty($data['promo_code'])) {
                $promoResult = $this->promoService->apply(
                    $data['promo_code'],
                    $userId,
                    $data['type'],
                    $data['item_id'],
                    $pricing['subtotal_thb']
                );

                if ($promoResult['valid']) {
                    $promoDiscount = $promoResult['discount'];
                    $promoCodeId = $promoResult['promo_code_id'];
                }
            }

            // Apply cashback
            $cashbackUsed = 0;
            if (!empty($data['use_cashback'])) {
                $user = $this->userService->findById($userId);
                $maxCashback = min(
                    (float) $user['cashback_balance'],
                    $pricing['subtotal_thb'] * 0.5 // Max 50% of order
                );
                $cashbackUsed = min($data['use_cashback'], $maxCashback);
            }

            // Calculate final price
            $totalDiscount = $promoDiscount + $cashbackUsed;
            $totalPrice = max(0, $pricing['subtotal_thb'] - $totalDiscount);

            // Calculate cashback earned
            $cashbackPercent = (float) Application::getInstance()->getConfig('loyalty.cashback_percent', 5);
            $cashbackEarned = $totalPrice * ($cashbackPercent / 100);

            // Generate booking reference
            $reference = $this->generateReference();

            // Create booking
            $bookingId = $this->db->insert('bookings', [
                'booking_reference' => $reference,
                'user_id' => $userId,
                'bookable_type' => $data['type'],
                'bookable_id' => $data['item_id'],
                'booking_date' => $data['date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'duration_hours' => $data['hours'] ?? null,
                'adults_count' => $data['adults'] ?? 1,
                'children_count' => $data['children'] ?? 0,
                'infants_count' => $data['infants'] ?? 0,
                'base_price_thb' => $pricing['base_price_thb'],
                'extras_price_thb' => $pricing['extras_price_thb'] ?? 0,
                'extras_details' => json_encode($pricing['extras_details'] ?? []),
                'pickup_fee_thb' => $pricing['pickup_fee_thb'] ?? 0,
                'pickup_address' => $data['pickup_address'] ?? null,
                'subtotal_thb' => $pricing['subtotal_thb'],
                'promo_code_id' => $promoCodeId,
                'promo_discount_thb' => $promoDiscount,
                'cashback_used_thb' => $cashbackUsed,
                'total_discount_thb' => $totalDiscount,
                'total_price_thb' => $totalPrice,
                'cashback_percent' => $cashbackPercent,
                'cashback_earned_thb' => $cashbackEarned,
                'status' => 'pending',
                'special_requests' => $data['special_requests'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'source' => 'telegram',
            ]);

            // Deduct cashback if used
            if ($cashbackUsed > 0) {
                $this->userService->useCashback($userId, $cashbackUsed, $bookingId);
            }

            // Record promo code usage
            if ($promoCodeId) {
                $this->promoService->recordUsage($promoCodeId, $userId, $bookingId, $promoDiscount);
            }

            $this->db->commit();

            return $this->getByReference($reference);

        } catch (\Exception $e) {
            $this->db->rollback();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get booking by reference
     */
    public function getByReference(string $reference): ?array
    {
        $booking = $this->db->queryOne(
            "SELECT b.*,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.name
                        WHEN b.bookable_type = 'tour' THEN t.name_en
                    END as item_name,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.thumbnail
                        WHEN b.bookable_type = 'tour' THEN t.thumbnail
                    END as item_thumbnail,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.slug
                        WHEN b.bookable_type = 'tour' THEN t.slug
                    END as item_slug
             FROM bookings b
             LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
             LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
             WHERE b.booking_reference = ?",
            [$reference]
        );

        if ($booking) {
            $booking['extras_details'] = json_decode($booking['extras_details'], true) ?? [];
        }

        return $booking;
    }

    /**
     * Update booking status
     */
    public function updateStatus(string $reference, string $status, ?string $paymentMethod = null): bool
    {
        $updateData = ['status' => $status];

        if ($paymentMethod) {
            $updateData['payment_method'] = $paymentMethod;
        }

        if ($status === 'paid') {
            $updateData['paid_at'] = date('Y-m-d H:i:s');

            // Credit cashback
            $booking = $this->getByReference($reference);
            if ($booking && $booking['cashback_earned_thb'] > 0) {
                $this->userService->addCashback(
                    $booking['user_id'],
                    $booking['cashback_earned_thb'],
                    $booking['id'],
                    "Cashback from booking {$reference}"
                );

                // Update cashback status
                $this->db->update(
                    'bookings',
                    ['cashback_status' => 'credited'],
                    'booking_reference = ?',
                    [$reference]
                );

                // Check and credit referral bonus
                $this->creditReferralBonus($booking['user_id'], $booking['id']);
            }
        }

        if ($status === 'cancelled') {
            $updateData['cancelled_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->update('bookings', $updateData, 'booking_reference = ?', [$reference]) > 0;
    }

    /**
     * Cancel booking
     */
    public function cancel(string $reference, int $userId, ?string $reason = null): array
    {
        $booking = $this->getByReference($reference);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        if ($booking['user_id'] !== $userId) {
            return ['error' => 'Unauthorized'];
        }

        if (!in_array($booking['status'], ['pending', 'confirmed'])) {
            return ['error' => 'Cannot cancel this booking'];
        }

        // Refund cashback used
        if ($booking['cashback_used_thb'] > 0) {
            $this->userService->addCashback(
                $userId,
                $booking['cashback_used_thb'],
                $booking['id'],
                "Refund from cancelled booking {$reference}"
            );
        }

        $this->db->update('bookings', [
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => date('Y-m-d H:i:s'),
        ], 'booking_reference = ?', [$reference]);

        return ['success' => true, 'message' => 'Booking cancelled'];
    }

    /**
     * Calculate total price for a booking
     */
    public function calculateTotalPrice(array $data): array
    {
        if ($data['type'] === 'vessel') {
            $pricing = $this->vesselService->calculatePrice(
                $data['item_id'],
                $data['date'],
                $data['hours'] ?? 8,
                $data['extras'] ?? []
            );

            if (isset($pricing['error'])) {
                return $pricing;
            }

            return [
                'base_price_thb' => $pricing['base_price_thb'],
                'extras_price_thb' => $pricing['extras_price_thb'],
                'extras_details' => $pricing['extras_details'],
                'pickup_fee_thb' => 0,
                'subtotal_thb' => $pricing['total_thb'],
            ];
        }

        if ($data['type'] === 'tour') {
            $pricing = $this->tourService->calculatePrice(
                $data['item_id'],
                $data['date'],
                $data['adults'] ?? 1,
                $data['children'] ?? 0,
                $data['pickup'] ?? false
            );

            if (isset($pricing['error'])) {
                return $pricing;
            }

            return [
                'base_price_thb' => $pricing['adults_total_thb'] + $pricing['children_total_thb'],
                'extras_price_thb' => 0,
                'extras_details' => [],
                'pickup_fee_thb' => $pricing['pickup_fee_thb'],
                'subtotal_thb' => $pricing['total_thb'],
            ];
        }

        return ['error' => 'Invalid booking type'];
    }

    /**
     * Credit referral bonus for first booking
     */
    private function creditReferralBonus(int $userId, int $bookingId): void
    {
        $user = $this->userService->findById($userId);

        if (!$user['referred_by']) {
            return;
        }

        // Check if this is the user's first completed booking
        $previousBookings = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM bookings
             WHERE user_id = ? AND status IN ('paid', 'completed') AND id != ?",
            [$userId, $bookingId]
        );

        if ($previousBookings['count'] > 0) {
            return; // Not first booking
        }

        // Check if bonus already credited
        $existingBonus = $this->db->queryOne(
            "SELECT id FROM referral_transactions
             WHERE referred_id = ? AND status = 'credited'",
            [$userId]
        );

        if ($existingBonus) {
            return; // Bonus already credited
        }

        $bonusAmount = (float) Application::getInstance()->getConfig('loyalty.referral_bonus_thb', 200);

        // Credit bonus to referrer
        $this->userService->addCashback(
            $user['referred_by'],
            $bonusAmount,
            $bookingId,
            "Referral bonus for {$user['first_name']}'s first booking"
        );

        // Record referral transaction
        $this->db->insert('referral_transactions', [
            'referrer_id' => $user['referred_by'],
            'referred_id' => $userId,
            'booking_id' => $bookingId,
            'bonus_amount_thb' => $bonusAmount,
            'status' => 'credited',
            'credited_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get bookable item
     */
    private function getBookableItem(string $type, int $id): ?array
    {
        return match ($type) {
            'vessel' => $this->vesselService->getById($id),
            'tour' => $this->tourService->getById($id),
            default => null,
        };
    }

    /**
     * Generate unique booking reference
     */
    private function generateReference(): string
    {
        do {
            $reference = 'PYT-' . date('Y') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $existing = $this->db->queryOne(
                "SELECT id FROM bookings WHERE booking_reference = ?",
                [$reference]
            );
        } while ($existing);

        return $reference;
    }
}
