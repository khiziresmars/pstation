<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * Promo Code Service
 * Handles promo code validation and application
 */
class PromoService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Validate a promo code
     */
    public function validate(string $code, int $userId, string $type, int $itemId, float $orderAmount): array
    {
        $promo = $this->db->queryOne(
            "SELECT * FROM promo_codes WHERE code = ? AND is_active = 1",
            [strtoupper($code)]
        );

        if (!$promo) {
            return ['valid' => false, 'error' => 'Invalid promo code'];
        }

        // Check validity period
        $now = date('Y-m-d H:i:s');
        if ($now < $promo['valid_from'] || $now > $promo['valid_until']) {
            return ['valid' => false, 'error' => 'Promo code has expired'];
        }

        // Check max uses
        if ($promo['max_uses'] !== null && $promo['used_count'] >= $promo['max_uses']) {
            return ['valid' => false, 'error' => 'Promo code usage limit reached'];
        }

        // Check user usage limit
        $userUsage = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM promo_code_usage
             WHERE promo_code_id = ? AND user_id = ?",
            [$promo['id'], $userId]
        );

        if ($userUsage['count'] >= $promo['max_uses_per_user']) {
            return ['valid' => false, 'error' => 'You have already used this promo code'];
        }

        // Check minimum order amount
        if ($orderAmount < $promo['min_order_amount']) {
            return [
                'valid' => false,
                'error' => "Minimum order amount is {$promo['min_order_amount']} THB"
            ];
        }

        // Check applies_to
        if ($promo['applies_to'] !== 'all') {
            if ($promo['applies_to'] === 'vessels' && $type !== 'vessel') {
                return ['valid' => false, 'error' => 'This code only applies to vessel rentals'];
            }
            if ($promo['applies_to'] === 'tours' && $type !== 'tour') {
                return ['valid' => false, 'error' => 'This code only applies to tours'];
            }
        }

        // Check specific item restrictions
        if ($promo['vessel_ids'] && $type === 'vessel') {
            $allowedIds = json_decode($promo['vessel_ids'], true) ?? [];
            if (!in_array($itemId, $allowedIds)) {
                return ['valid' => false, 'error' => 'This code does not apply to this vessel'];
            }
        }

        if ($promo['tour_ids'] && $type === 'tour') {
            $allowedIds = json_decode($promo['tour_ids'], true) ?? [];
            if (!in_array($itemId, $allowedIds)) {
                return ['valid' => false, 'error' => 'This code does not apply to this tour'];
            }
        }

        // Calculate discount
        $discount = $this->calculateDiscount($promo, $orderAmount);

        return [
            'valid' => true,
            'promo_code_id' => $promo['id'],
            'code' => $promo['code'],
            'type' => $promo['type'],
            'value' => (float) $promo['value'],
            'discount' => $discount,
            'description' => $promo['description'],
        ];
    }

    /**
     * Apply promo code (validate and return discount)
     */
    public function apply(string $code, int $userId, string $type, int $itemId, float $orderAmount): array
    {
        return $this->validate($code, $userId, $type, $itemId, $orderAmount);
    }

    /**
     * Record promo code usage
     */
    public function recordUsage(int $promoCodeId, int $userId, int $bookingId, float $discountApplied): void
    {
        $this->db->insert('promo_code_usage', [
            'promo_code_id' => $promoCodeId,
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'discount_applied_thb' => $discountApplied,
        ]);

        // Increment usage count
        $this->db->execute(
            "UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?",
            [$promoCodeId]
        );
    }

    /**
     * Calculate discount amount
     */
    private function calculateDiscount(array $promo, float $orderAmount): float
    {
        if ($promo['type'] === 'percentage') {
            $discount = $orderAmount * ($promo['value'] / 100);
        } else {
            $discount = (float) $promo['value'];
        }

        // Apply max discount limit
        if ($promo['max_discount_amount'] !== null) {
            $discount = min($discount, (float) $promo['max_discount_amount']);
        }

        // Discount cannot exceed order amount
        return min($discount, $orderAmount);
    }

    /**
     * Get public promo codes
     */
    public function getPublicCodes(): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->query(
            "SELECT code, name, description, type, value, min_order_amount, applies_to, valid_until
             FROM promo_codes
             WHERE is_active = 1
             AND is_public = 1
             AND valid_from <= ?
             AND valid_until >= ?
             AND (max_uses IS NULL OR used_count < max_uses)
             ORDER BY value DESC",
            [$now, $now]
        );
    }
}
