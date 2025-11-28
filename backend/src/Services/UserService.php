<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * User Service
 * Handles user management operations
 */
class UserService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Get or create user from Telegram data
     */
    public function getOrCreateFromTelegram(array $telegramData): ?array
    {
        $userData = $telegramData['user'];
        $telegramId = $userData['id'];

        // Try to find existing user
        $user = $this->findByTelegramId($telegramId);

        if ($user) {
            // Update user data
            $this->updateFromTelegram($user['id'], $userData, $telegramData['start_param'] ?? null);
            return $this->findById($user['id']);
        }

        // Create new user
        return $this->createFromTelegram($userData, $telegramData['start_param'] ?? null);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE id = ?",
            [$id]
        );
    }

    /**
     * Find user by Telegram ID
     */
    public function findByTelegramId(int $telegramId): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE telegram_id = ?",
            [$telegramId]
        );
    }

    /**
     * Find user by referral code
     */
    public function findByReferralCode(string $code): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE referral_code = ?",
            [$code]
        );
    }

    /**
     * Create user from Telegram data
     */
    public function createFromTelegram(array $userData, ?string $startParam = null): ?array
    {
        // Generate unique referral code
        $referralCode = $this->generateReferralCode($userData['first_name'] ?? 'USER');

        // Check if referred by someone
        $referredBy = null;
        if ($startParam && str_starts_with($startParam, 'ref_')) {
            $refCode = substr($startParam, 4);
            $referrer = $this->findByReferralCode($refCode);
            if ($referrer) {
                $referredBy = $referrer['id'];
            }
        }

        $userId = $this->db->insert('users', [
            'telegram_id' => $userData['id'],
            'username' => $userData['username'] ?? null,
            'first_name' => $userData['first_name'] ?? 'User',
            'last_name' => $userData['last_name'] ?? null,
            'language_code' => $userData['language_code'] ?? 'en',
            'photo_url' => $userData['photo_url'] ?? null,
            'referral_code' => $referralCode,
            'referred_by' => $referredBy,
            'cashback_balance' => 0,
            'preferred_currency' => 'THB',
        ]);

        return $this->findById($userId);
    }

    /**
     * Update user from Telegram data
     */
    public function updateFromTelegram(int $userId, array $userData, ?string $startParam = null): void
    {
        $updateData = [
            'username' => $userData['username'] ?? null,
            'first_name' => $userData['first_name'] ?? 'User',
            'last_name' => $userData['last_name'] ?? null,
            'language_code' => $userData['language_code'] ?? 'en',
            'last_activity_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($userData['photo_url'])) {
            $updateData['photo_url'] = $userData['photo_url'];
        }

        $this->db->update('users', $updateData, 'id = ?', [$userId]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $allowed = ['phone', 'email', 'language_code', 'preferred_currency'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return false;
        }

        return $this->db->update('users', $updateData, 'id = ?', [$userId]) > 0;
    }

    /**
     * Generate unique referral code
     */
    private function generateReferralCode(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 4));
        $prefix = str_pad($prefix, 4, 'X');

        do {
            $code = $prefix . date('Y') . strtoupper(bin2hex(random_bytes(2)));
            $existing = $this->findByReferralCode($code);
        } while ($existing);

        return $code;
    }

    /**
     * Get user's booking history
     */
    public function getBookings(int $userId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $bookings = $this->db->query(
            "SELECT b.*,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.name
                        WHEN b.bookable_type = 'tour' THEN t.name_en
                    END as item_name,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.thumbnail
                        WHEN b.bookable_type = 'tour' THEN t.thumbnail
                    END as item_thumbnail
             FROM bookings b
             LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
             LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );

        $total = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM bookings WHERE user_id = ?",
            [$userId]
        );

        return [
            'items' => $bookings,
            'total' => (int) $total['count']
        ];
    }

    /**
     * Get user's favorites
     */
    public function getFavorites(int $userId): array
    {
        return $this->db->query(
            "SELECT f.*,
                    CASE
                        WHEN f.favoritable_type = 'vessel' THEN v.name
                        WHEN f.favoritable_type = 'tour' THEN t.name_en
                    END as item_name,
                    CASE
                        WHEN f.favoritable_type = 'vessel' THEN v.thumbnail
                        WHEN f.favoritable_type = 'tour' THEN t.thumbnail
                    END as item_thumbnail,
                    CASE
                        WHEN f.favoritable_type = 'vessel' THEN v.price_per_day_thb
                        WHEN f.favoritable_type = 'tour' THEN t.price_adult_thb
                    END as price_thb
             FROM favorites f
             LEFT JOIN vessels v ON f.favoritable_type = 'vessel' AND f.favoritable_id = v.id
             LEFT JOIN tours t ON f.favoritable_type = 'tour' AND f.favoritable_id = t.id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC",
            [$userId]
        );
    }

    /**
     * Add to favorites
     */
    public function addFavorite(int $userId, string $type, int $itemId): bool
    {
        // Check if already exists
        $existing = $this->db->queryOne(
            "SELECT id FROM favorites WHERE user_id = ? AND favoritable_type = ? AND favoritable_id = ?",
            [$userId, $type, $itemId]
        );

        if ($existing) {
            return true;
        }

        $this->db->insert('favorites', [
            'user_id' => $userId,
            'favoritable_type' => $type,
            'favoritable_id' => $itemId,
        ]);

        return true;
    }

    /**
     * Remove from favorites
     */
    public function removeFavorite(int $userId, string $type, int $itemId): bool
    {
        return $this->db->delete(
            'favorites',
            'user_id = ? AND favoritable_type = ? AND favoritable_id = ?',
            [$userId, $type, $itemId]
        ) > 0;
    }

    /**
     * Get cashback history
     */
    public function getCashbackHistory(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM cashback_transactions
             WHERE user_id = ?
             ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Add cashback
     */
    public function addCashback(int $userId, float $amount, ?int $bookingId, string $description): void
    {
        $user = $this->findById($userId);
        $newBalance = (float) $user['cashback_balance'] + $amount;

        $this->db->beginTransaction();

        try {
            // Update user balance
            $this->db->update('users', ['cashback_balance' => $newBalance], 'id = ?', [$userId]);

            // Record transaction
            $this->db->insert('cashback_transactions', [
                'user_id' => $userId,
                'booking_id' => $bookingId,
                'type' => 'earned',
                'amount_thb' => $amount,
                'balance_after_thb' => $newBalance,
                'description' => $description,
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Use cashback
     */
    public function useCashback(int $userId, float $amount, ?int $bookingId): bool
    {
        $user = $this->findById($userId);

        if ((float) $user['cashback_balance'] < $amount) {
            return false;
        }

        $newBalance = (float) $user['cashback_balance'] - $amount;

        $this->db->beginTransaction();

        try {
            $this->db->update('users', ['cashback_balance' => $newBalance], 'id = ?', [$userId]);

            $this->db->insert('cashback_transactions', [
                'user_id' => $userId,
                'booking_id' => $bookingId,
                'type' => 'used',
                'amount_thb' => -$amount,
                'balance_after_thb' => $newBalance,
                'description' => 'Applied to booking',
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get referral statistics
     */
    public function getReferralStats(int $userId): array
    {
        $user = $this->findById($userId);

        $referrals = $this->db->query(
            "SELECT u.id, u.first_name, u.created_at, rt.bonus_amount_thb, rt.status
             FROM users u
             LEFT JOIN referral_transactions rt ON rt.referred_id = u.id AND rt.referrer_id = ?
             WHERE u.referred_by = ?
             ORDER BY u.created_at DESC",
            [$userId, $userId]
        );

        $totalEarned = $this->db->queryOne(
            "SELECT COALESCE(SUM(bonus_amount_thb), 0) as total
             FROM referral_transactions
             WHERE referrer_id = ? AND status = 'credited'",
            [$userId]
        );

        return [
            'referral_code' => $user['referral_code'],
            'referral_link' => "https://t.me/" . ($_ENV['TELEGRAM_BOT_USERNAME'] ?? 'bot') . "?start=ref_{$user['referral_code']}",
            'total_referrals' => count($referrals),
            'total_earned_thb' => (float) $totalEarned['total'],
            'referrals' => $referrals,
        ];
    }
}
