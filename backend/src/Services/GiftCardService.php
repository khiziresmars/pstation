<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Gift Card Service
 * Manages gift cards/vouchers
 */
class GiftCardService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Generate unique gift card code
     */
    private function generateCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4) . '-' .
                substr(md5(uniqid((string) mt_rand(), true)), 0, 4) . '-' .
                substr(md5(uniqid((string) mt_rand(), true)), 0, 4));

            $exists = $this->db->fetchOne(
                "SELECT id FROM gift_cards WHERE code = :code",
                ['code' => $code]
            );
        } while ($exists);

        return $code;
    }

    /**
     * Purchase a gift card
     */
    public function purchase(array $data): array
    {
        $code = $this->generateCode();
        $amount = (float) $data['amount_thb'];
        $validMonths = $data['valid_months'] ?? 12;

        $validFrom = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime("+{$validMonths} months"));

        $this->db->execute(
            "INSERT INTO gift_cards (code, initial_amount_thb, balance_thb, currency_purchased, amount_paid,
             purchaser_user_id, purchaser_name, purchaser_email, purchaser_phone,
             recipient_name, recipient_email, recipient_phone, personal_message,
             delivery_method, design_template, status, valid_from, valid_until, applies_to)
             VALUES (:code, :amount, :amount, :currency, :amount_paid,
             :purchaser_user_id, :purchaser_name, :purchaser_email, :purchaser_phone,
             :recipient_name, :recipient_email, :recipient_phone, :message,
             :delivery_method, :design, 'active', :valid_from, :valid_until, :applies_to)",
            [
                'code' => $code,
                'amount' => $amount,
                'currency' => $data['currency'] ?? 'THB',
                'amount_paid' => $data['amount_paid'] ?? $amount,
                'purchaser_user_id' => $data['purchaser_user_id'] ?? null,
                'purchaser_name' => $data['purchaser_name'] ?? null,
                'purchaser_email' => $data['purchaser_email'] ?? null,
                'purchaser_phone' => $data['purchaser_phone'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_email' => $data['recipient_email'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'message' => $data['personal_message'] ?? null,
                'delivery_method' => $data['delivery_method'] ?? 'email',
                'design' => $data['design_template'] ?? 'classic',
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'applies_to' => $data['applies_to'] ?? 'all'
            ]
        );

        $giftCardId = (int) $this->db->lastInsertId();

        // Log transaction
        $this->logTransaction($giftCardId, null, 'purchase', $amount, $amount);

        return $this->getByCode($code);
    }

    /**
     * Get gift card by code
     */
    public function getByCode(string $code): ?array
    {
        $giftCard = $this->db->fetchOne(
            "SELECT * FROM gift_cards WHERE code = :code",
            ['code' => strtoupper($code)]
        );

        return $giftCard ? $this->formatGiftCard($giftCard) : null;
    }

    /**
     * Validate gift card for use
     */
    public function validate(string $code, float $orderAmount, string $appliesTo = 'all'): array
    {
        $giftCard = $this->db->fetchOne(
            "SELECT * FROM gift_cards WHERE code = :code",
            ['code' => strtoupper($code)]
        );

        if (!$giftCard) {
            return ['valid' => false, 'error' => 'Gift card not found'];
        }

        if ($giftCard['status'] !== 'active') {
            return ['valid' => false, 'error' => 'Gift card is ' . $giftCard['status']];
        }

        $today = date('Y-m-d');
        if ($today < $giftCard['valid_from']) {
            return ['valid' => false, 'error' => 'Gift card is not yet valid'];
        }

        if ($today > $giftCard['valid_until']) {
            return ['valid' => false, 'error' => 'Gift card has expired'];
        }

        $balance = (float) $giftCard['balance_thb'];
        if ($balance <= 0) {
            return ['valid' => false, 'error' => 'Gift card has no remaining balance'];
        }

        if ($giftCard['applies_to'] !== 'all' && $giftCard['applies_to'] !== $appliesTo) {
            return ['valid' => false, 'error' => 'Gift card can only be used for ' . $giftCard['applies_to']];
        }

        $minOrder = (float) $giftCard['min_order_amount'];
        if ($orderAmount < $minOrder) {
            return ['valid' => false, 'error' => "Minimum order amount is ฿" . number_format($minOrder)];
        }

        $applicableAmount = min($balance, $orderAmount);

        return [
            'valid' => true,
            'gift_card' => $this->formatGiftCard($giftCard),
            'applicable_amount_thb' => $applicableAmount
        ];
    }

    /**
     * Redeem gift card for booking
     */
    public function redeem(string $code, int $bookingId, float $amount, int $userId): array
    {
        $giftCard = $this->db->fetchOne(
            "SELECT * FROM gift_cards WHERE code = :code AND status = 'active'",
            ['code' => strtoupper($code)]
        );

        if (!$giftCard) {
            return ['success' => false, 'error' => 'Gift card not found or inactive'];
        }

        $balance = (float) $giftCard['balance_thb'];
        $redeemAmount = min($balance, $amount);
        $newBalance = $balance - $redeemAmount;

        // Update balance
        $this->db->execute(
            "UPDATE gift_cards SET
             balance_thb = :balance,
             status = IF(:balance <= 0, 'used', 'active'),
             redeemed_by_user_id = COALESCE(redeemed_by_user_id, :user_id)
             WHERE id = :id",
            [
                'balance' => $newBalance,
                'user_id' => $userId,
                'id' => $giftCard['id']
            ]
        );

        // Log transaction
        $this->logTransaction($giftCard['id'], $bookingId, 'redeem', -$redeemAmount, $newBalance);

        return [
            'success' => true,
            'redeemed_amount_thb' => $redeemAmount,
            'remaining_balance_thb' => $newBalance
        ];
    }

    /**
     * Refund to gift card
     */
    public function refund(int $giftCardId, int $bookingId, float $amount): bool
    {
        $giftCard = $this->db->fetchOne(
            "SELECT * FROM gift_cards WHERE id = :id",
            ['id' => $giftCardId]
        );

        if (!$giftCard) {
            return false;
        }

        $newBalance = (float) $giftCard['balance_thb'] + $amount;

        $this->db->execute(
            "UPDATE gift_cards SET balance_thb = :balance, status = 'active' WHERE id = :id",
            ['balance' => $newBalance, 'id' => $giftCardId]
        );

        $this->logTransaction($giftCardId, $bookingId, 'refund', $amount, $newBalance);

        return true;
    }

    /**
     * Get user's gift cards
     */
    public function getUserGiftCards(int $userId): array
    {
        $giftCards = $this->db->fetchAll(
            "SELECT * FROM gift_cards
             WHERE purchaser_user_id = :user_id OR redeemed_by_user_id = :user_id
             ORDER BY created_at DESC",
            ['user_id' => $userId]
        );

        return array_map(fn($gc) => $this->formatGiftCard($gc), $giftCards);
    }

    /**
     * Get gift card transactions
     */
    public function getTransactions(int $giftCardId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM gift_card_transactions WHERE gift_card_id = :id ORDER BY created_at DESC",
            ['id' => $giftCardId]
        );
    }

    /**
     * Check and expire gift cards
     */
    public function expireOldCards(): int
    {
        $expiredCards = $this->db->fetchAll(
            "SELECT id, balance_thb FROM gift_cards
             WHERE status = 'active' AND valid_until < CURDATE()"
        );

        foreach ($expiredCards as $card) {
            $this->db->execute(
                "UPDATE gift_cards SET status = 'expired' WHERE id = :id",
                ['id' => $card['id']]
            );

            if ((float) $card['balance_thb'] > 0) {
                $this->logTransaction($card['id'], null, 'expire', -(float) $card['balance_thb'], 0);
            }
        }

        return count($expiredCards);
    }

    /**
     * Get available gift card designs
     */
    public function getDesigns(): array
    {
        return [
            [
                'id' => 'classic',
                'name' => 'Classic',
                'preview' => '/images/gift-cards/classic.jpg'
            ],
            [
                'id' => 'ocean',
                'name' => 'Ocean Blue',
                'preview' => '/images/gift-cards/ocean.jpg'
            ],
            [
                'id' => 'sunset',
                'name' => 'Sunset',
                'preview' => '/images/gift-cards/sunset.jpg'
            ],
            [
                'id' => 'tropical',
                'name' => 'Tropical',
                'preview' => '/images/gift-cards/tropical.jpg'
            ],
            [
                'id' => 'birthday',
                'name' => 'Birthday',
                'preview' => '/images/gift-cards/birthday.jpg'
            ],
            [
                'id' => 'wedding',
                'name' => 'Wedding',
                'preview' => '/images/gift-cards/wedding.jpg'
            ]
        ];
    }

    /**
     * Get available gift card amounts
     */
    public function getAvailableAmounts(): array
    {
        return [
            ['amount' => 2000, 'label' => '฿2,000'],
            ['amount' => 5000, 'label' => '฿5,000'],
            ['amount' => 10000, 'label' => '฿10,000'],
            ['amount' => 20000, 'label' => '฿20,000'],
            ['amount' => 50000, 'label' => '฿50,000'],
            ['amount' => 0, 'label' => 'Custom Amount', 'min' => 1000, 'max' => 500000]
        ];
    }

    /**
     * Log gift card transaction
     */
    private function logTransaction(int $giftCardId, ?int $bookingId, string $type, float $amount, float $balanceAfter, ?string $note = null): void
    {
        $this->db->execute(
            "INSERT INTO gift_card_transactions (gift_card_id, booking_id, type, amount_thb, balance_after_thb, note)
             VALUES (:gift_card_id, :booking_id, :type, :amount, :balance, :note)",
            [
                'gift_card_id' => $giftCardId,
                'booking_id' => $bookingId,
                'type' => $type,
                'amount' => $amount,
                'balance' => $balanceAfter,
                'note' => $note
            ]
        );
    }

    /**
     * Format gift card for API response
     */
    private function formatGiftCard(array $giftCard): array
    {
        return [
            'id' => (int) $giftCard['id'],
            'code' => $giftCard['code'],
            'initial_amount_thb' => (float) $giftCard['initial_amount_thb'],
            'balance_thb' => (float) $giftCard['balance_thb'],
            'status' => $giftCard['status'],
            'valid_from' => $giftCard['valid_from'],
            'valid_until' => $giftCard['valid_until'],
            'applies_to' => $giftCard['applies_to'],
            'recipient_name' => $giftCard['recipient_name'],
            'personal_message' => $giftCard['personal_message'],
            'design_template' => $giftCard['design_template'],
            'is_expired' => $giftCard['valid_until'] < date('Y-m-d'),
            'days_until_expiry' => max(0, (strtotime($giftCard['valid_until']) - time()) / 86400)
        ];
    }
}
