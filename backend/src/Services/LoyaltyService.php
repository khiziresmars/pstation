<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Loyalty Service
 * Manages loyalty tiers and user progression
 */
class LoyaltyService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all loyalty tiers
     */
    public function getTiers(string $lang = 'en'): array
    {
        $tiers = $this->db->fetchAll(
            "SELECT * FROM loyalty_tiers WHERE is_active = 1 ORDER BY sort_order ASC"
        );

        return array_map(fn($tier) => $this->formatTier($tier, $lang), $tiers);
    }

    /**
     * Get tier by ID
     */
    public function getTier(int $id, string $lang = 'en'): ?array
    {
        $tier = $this->db->fetchOne(
            "SELECT * FROM loyalty_tiers WHERE id = :id",
            ['id' => $id]
        );

        return $tier ? $this->formatTier($tier, $lang) : null;
    }

    /**
     * Get user's current tier
     */
    public function getUserTier(int $userId, string $lang = 'en'): array
    {
        $user = $this->db->fetchOne(
            "SELECT loyalty_tier_id, total_spent_thb, total_bookings FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user || !$user['loyalty_tier_id']) {
            // Default to bronze
            $tier = $this->db->fetchOne(
                "SELECT * FROM loyalty_tiers WHERE slug = 'bronze'"
            );
        } else {
            $tier = $this->db->fetchOne(
                "SELECT * FROM loyalty_tiers WHERE id = :id",
                ['id' => $user['loyalty_tier_id']]
            );
        }

        $currentTier = $this->formatTier($tier, $lang);

        // Get next tier
        $nextTier = $this->db->fetchOne(
            "SELECT * FROM loyalty_tiers
             WHERE is_active = 1
             AND (min_bookings > :bookings OR min_spent_thb > :spent)
             ORDER BY sort_order ASC
             LIMIT 1",
            [
                'bookings' => $user['total_bookings'] ?? 0,
                'spent' => $user['total_spent_thb'] ?? 0
            ]
        );

        $progress = null;
        if ($nextTier) {
            $nextTierFormatted = $this->formatTier($nextTier, $lang);
            $currentBookings = (int) ($user['total_bookings'] ?? 0);
            $currentSpent = (float) ($user['total_spent_thb'] ?? 0);

            $bookingsProgress = $nextTier['min_bookings'] > 0
                ? min(100, ($currentBookings / $nextTier['min_bookings']) * 100)
                : 100;

            $spentProgress = $nextTier['min_spent_thb'] > 0
                ? min(100, ($currentSpent / $nextTier['min_spent_thb']) * 100)
                : 100;

            $progress = [
                'next_tier' => $nextTierFormatted,
                'bookings_required' => (int) $nextTier['min_bookings'],
                'bookings_current' => $currentBookings,
                'bookings_progress' => round($bookingsProgress, 1),
                'spent_required_thb' => (float) $nextTier['min_spent_thb'],
                'spent_current_thb' => $currentSpent,
                'spent_progress' => round($spentProgress, 1)
            ];
        }

        return [
            'current_tier' => $currentTier,
            'total_bookings' => (int) ($user['total_bookings'] ?? 0),
            'total_spent_thb' => (float) ($user['total_spent_thb'] ?? 0),
            'progress_to_next' => $progress
        ];
    }

    /**
     * Update user tier based on activity
     */
    public function updateUserTier(int $userId): void
    {
        $user = $this->db->fetchOne(
            "SELECT total_spent_thb, total_bookings FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user) {
            return;
        }

        // Find highest eligible tier
        $tier = $this->db->fetchOne(
            "SELECT id FROM loyalty_tiers
             WHERE is_active = 1
             AND min_bookings <= :bookings
             AND min_spent_thb <= :spent
             ORDER BY sort_order DESC
             LIMIT 1",
            [
                'bookings' => $user['total_bookings'],
                'spent' => $user['total_spent_thb']
            ]
        );

        if ($tier) {
            $this->db->execute(
                "UPDATE users SET loyalty_tier_id = :tier_id WHERE id = :id",
                ['tier_id' => $tier['id'], 'id' => $userId]
            );
        }
    }

    /**
     * Record booking and update user stats
     */
    public function recordBooking(int $userId, float $amount): void
    {
        $this->db->execute(
            "UPDATE users SET
             total_bookings = total_bookings + 1,
             total_spent_thb = total_spent_thb + :amount
             WHERE id = :id",
            ['amount' => $amount, 'id' => $userId]
        );

        $this->updateUserTier($userId);
    }

    /**
     * Get user's effective cashback rate
     */
    public function getCashbackRate(int $userId): float
    {
        $user = $this->db->fetchOne(
            "SELECT u.loyalty_tier_id, lt.cashback_percent
             FROM users u
             LEFT JOIN loyalty_tiers lt ON u.loyalty_tier_id = lt.id
             WHERE u.id = :id",
            ['id' => $userId]
        );

        if ($user && $user['cashback_percent']) {
            return (float) $user['cashback_percent'];
        }

        // Default rate
        $defaultTier = $this->db->fetchOne(
            "SELECT cashback_percent FROM loyalty_tiers WHERE slug = 'bronze'"
        );

        return $defaultTier ? (float) $defaultTier['cashback_percent'] : 5.00;
    }

    /**
     * Get user's extra discount from tier
     */
    public function getExtraDiscount(int $userId): float
    {
        $user = $this->db->fetchOne(
            "SELECT lt.extra_discount_percent
             FROM users u
             LEFT JOIN loyalty_tiers lt ON u.loyalty_tier_id = lt.id
             WHERE u.id = :id",
            ['id' => $userId]
        );

        return $user ? (float) ($user['extra_discount_percent'] ?? 0) : 0;
    }

    /**
     * Check if user has priority support
     */
    public function hasPrioritySupport(int $userId): bool
    {
        $user = $this->db->fetchOne(
            "SELECT lt.priority_support
             FROM users u
             LEFT JOIN loyalty_tiers lt ON u.loyalty_tier_id = lt.id
             WHERE u.id = :id",
            ['id' => $userId]
        );

        return $user ? (bool) $user['priority_support'] : false;
    }

    /**
     * Get free cancellation hours for user
     */
    public function getFreeCancellationHours(int $userId): int
    {
        $user = $this->db->fetchOne(
            "SELECT lt.free_cancellation_hours
             FROM users u
             LEFT JOIN loyalty_tiers lt ON u.loyalty_tier_id = lt.id
             WHERE u.id = :id",
            ['id' => $userId]
        );

        return $user && $user['free_cancellation_hours']
            ? (int) $user['free_cancellation_hours']
            : 48;
    }

    /**
     * Format tier for API response
     */
    private function formatTier(array $tier, string $lang): array
    {
        return [
            'id' => (int) $tier['id'],
            'slug' => $tier['slug'],
            'name' => $tier["name_{$lang}"] ?? $tier['name_en'],
            'min_bookings' => (int) $tier['min_bookings'],
            'min_spent_thb' => (float) $tier['min_spent_thb'],
            'benefits' => [
                'cashback_percent' => (float) $tier['cashback_percent'],
                'extra_discount_percent' => (float) $tier['extra_discount_percent'],
                'priority_support' => (bool) $tier['priority_support'],
                'free_cancellation_hours' => (int) $tier['free_cancellation_hours'],
                'exclusive_offers' => (bool) $tier['exclusive_offers']
            ],
            'icon' => $tier['icon'],
            'color' => $tier['color'],
            'badge_image' => $tier['badge_image']
        ];
    }
}
