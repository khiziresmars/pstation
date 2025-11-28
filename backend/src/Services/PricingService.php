<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Dynamic Pricing Service
 * Calculates prices based on seasons, weekends, early bird, last minute, etc.
 */
class PricingService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Calculate price with all applicable pricing rules
     */
    public function calculatePrice(array $params): array
    {
        $basePrice = (float) $params['base_price'];
        $bookingDate = $params['booking_date']; // Y-m-d format
        $appliesTo = $params['applies_to'] ?? 'all'; // 'vessels' or 'tours'
        $itemType = $params['item_type'] ?? null; // vessel type or tour category
        $itemId = $params['item_id'] ?? null;
        $guests = $params['guests'] ?? 1;
        $durationHours = $params['duration_hours'] ?? null;

        // Calculate days until booking
        $daysAhead = (strtotime($bookingDate) - strtotime('today')) / 86400;
        $dayOfWeek = strtolower(date('l', strtotime($bookingDate)));

        // Get all applicable rules
        $rules = $this->getApplicableRules($bookingDate, $appliesTo, $itemType, $itemId);

        $adjustments = [];
        $totalAdjustment = 0;
        $appliedRules = [];

        // First pass: non-stackable rules (take the best discount or highest premium)
        $nonStackableDiscounts = [];
        $nonStackablePremiums = [];

        foreach ($rules as $rule) {
            if (!$this->ruleMatchesConditions($rule, $bookingDate, $daysAhead, $dayOfWeek, $guests, $durationHours)) {
                continue;
            }

            $adjustment = $this->calculateAdjustment($rule, $basePrice);

            if ((bool) $rule['is_stackable']) {
                // Stackable rules are applied separately
                $adjustments[] = [
                    'rule_id' => (int) $rule['id'],
                    'rule_name' => $rule['name'],
                    'type' => $rule['type'],
                    'adjustment_type' => $rule['adjustment_type'],
                    'adjustment_value' => (float) $rule['adjustment_value'],
                    'amount_thb' => $adjustment
                ];
                $totalAdjustment += $adjustment;
                $appliedRules[] = (int) $rule['id'];
            } else {
                // Non-stackable: separate discounts from premiums
                if ($adjustment < 0) {
                    $nonStackableDiscounts[] = [
                        'rule' => $rule,
                        'adjustment' => $adjustment
                    ];
                } else {
                    $nonStackablePremiums[] = [
                        'rule' => $rule,
                        'adjustment' => $adjustment
                    ];
                }
            }
        }

        // Apply best non-stackable discount (most discount)
        if (!empty($nonStackableDiscounts)) {
            usort($nonStackableDiscounts, fn($a, $b) => $a['adjustment'] <=> $b['adjustment']);
            $best = $nonStackableDiscounts[0];
            $adjustments[] = [
                'rule_id' => (int) $best['rule']['id'],
                'rule_name' => $best['rule']['name'],
                'type' => $best['rule']['type'],
                'adjustment_type' => $best['rule']['adjustment_type'],
                'adjustment_value' => (float) $best['rule']['adjustment_value'],
                'amount_thb' => $best['adjustment']
            ];
            $totalAdjustment += $best['adjustment'];
            $appliedRules[] = (int) $best['rule']['id'];
        }

        // Apply all non-stackable premiums (cumulative)
        foreach ($nonStackablePremiums as $premium) {
            $adjustments[] = [
                'rule_id' => (int) $premium['rule']['id'],
                'rule_name' => $premium['rule']['name'],
                'type' => $premium['rule']['type'],
                'adjustment_type' => $premium['rule']['adjustment_type'],
                'adjustment_value' => (float) $premium['rule']['adjustment_value'],
                'amount_thb' => $premium['adjustment']
            ];
            $totalAdjustment += $premium['adjustment'];
            $appliedRules[] = (int) $premium['rule']['id'];
        }

        $finalPrice = max(0, $basePrice + $totalAdjustment);

        return [
            'base_price_thb' => $basePrice,
            'adjustments' => $adjustments,
            'total_adjustment_thb' => $totalAdjustment,
            'final_price_thb' => $finalPrice,
            'applied_rules' => $appliedRules,
            'discount_percent' => $totalAdjustment < 0 ? round(abs($totalAdjustment) / $basePrice * 100, 1) : 0,
            'premium_percent' => $totalAdjustment > 0 ? round($totalAdjustment / $basePrice * 100, 1) : 0
        ];
    }

    /**
     * Get price calendar for a month
     */
    public function getPriceCalendar(int $itemId, string $itemType, string $month, float $basePrice): array
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $calendar = [];
        $current = $startDate;

        while ($current <= $endDate) {
            $priceData = $this->calculatePrice([
                'base_price' => $basePrice,
                'booking_date' => $current,
                'applies_to' => $itemType === 'vessel' ? 'vessels' : 'tours',
                'item_id' => $itemId
            ]);

            $calendar[$current] = [
                'date' => $current,
                'price_thb' => $priceData['final_price_thb'],
                'base_price_thb' => $basePrice,
                'has_discount' => $priceData['total_adjustment_thb'] < 0,
                'has_premium' => $priceData['total_adjustment_thb'] > 0,
                'adjustment_percent' => $priceData['discount_percent'] ?: $priceData['premium_percent'],
                'rules_applied' => count($priceData['applied_rules'])
            ];

            $current = date('Y-m-d', strtotime($current . ' +1 day'));
        }

        return $calendar;
    }

    /**
     * Get applicable rules for a booking
     */
    private function getApplicableRules(string $bookingDate, string $appliesTo, ?string $itemType, ?int $itemId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM pricing_rules
             WHERE is_active = 1
             AND (applies_to = 'all' OR applies_to = :applies_to)
             AND (
                 (type IN ('day_of_week', 'early_bird', 'last_minute', 'group_size', 'duration'))
                 OR (type = 'season' AND start_date <= :date AND end_date >= :date)
                 OR (type = 'special_date' AND start_date <= :date AND end_date >= :date)
             )
             ORDER BY priority DESC",
            [
                'applies_to' => $appliesTo,
                'date' => $bookingDate
            ]
        );
    }

    /**
     * Check if rule matches additional conditions
     */
    private function ruleMatchesConditions(array $rule, string $bookingDate, float $daysAhead, string $dayOfWeek, int $guests, ?int $durationHours): bool
    {
        switch ($rule['type']) {
            case 'day_of_week':
                $allowedDays = json_decode($rule['days_of_week'] ?? '[]', true);
                if (!empty($allowedDays) && !in_array($dayOfWeek, $allowedDays)) {
                    return false;
                }
                break;

            case 'early_bird':
                if ($rule['days_before_booking'] && $daysAhead < $rule['days_before_booking']) {
                    return false;
                }
                break;

            case 'last_minute':
                if ($rule['days_before_max'] && $daysAhead > $rule['days_before_max']) {
                    return false;
                }
                break;

            case 'group_size':
                if ($rule['min_guests'] && $guests < $rule['min_guests']) {
                    return false;
                }
                if ($rule['max_guests'] && $guests > $rule['max_guests']) {
                    return false;
                }
                break;

            case 'duration':
                if ($durationHours === null) {
                    return false;
                }
                if ($rule['min_duration_hours'] && $durationHours < $rule['min_duration_hours']) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Calculate adjustment amount
     */
    private function calculateAdjustment(array $rule, float $basePrice): float
    {
        $value = (float) $rule['adjustment_value'];

        if ($rule['adjustment_type'] === 'percentage') {
            return $basePrice * ($value / 100);
        }

        return $value;
    }

    // ==================== Admin Methods ====================

    /**
     * Get all pricing rules
     */
    public function getAllRules(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM pricing_rules ORDER BY priority DESC, type ASC"
        );
    }

    /**
     * Get rule by ID
     */
    public function getRule(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM pricing_rules WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Create pricing rule
     */
    public function createRule(array $data): int
    {
        $this->db->execute(
            "INSERT INTO pricing_rules (name, description, type, applies_to, vessel_types, tour_categories,
             vessel_ids, tour_ids, start_date, end_date, days_of_week, days_before_booking, days_before_max,
             min_guests, max_guests, min_duration_hours, adjustment_type, adjustment_value, priority, is_stackable)
             VALUES (:name, :description, :type, :applies_to, :vessel_types, :tour_categories,
             :vessel_ids, :tour_ids, :start_date, :end_date, :days_of_week, :days_before_booking, :days_before_max,
             :min_guests, :max_guests, :min_duration_hours, :adjustment_type, :adjustment_value, :priority, :is_stackable)",
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'applies_to' => $data['applies_to'] ?? 'all',
                'vessel_types' => isset($data['vessel_types']) ? json_encode($data['vessel_types']) : null,
                'tour_categories' => isset($data['tour_categories']) ? json_encode($data['tour_categories']) : null,
                'vessel_ids' => isset($data['vessel_ids']) ? json_encode($data['vessel_ids']) : null,
                'tour_ids' => isset($data['tour_ids']) ? json_encode($data['tour_ids']) : null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'days_of_week' => isset($data['days_of_week']) ? json_encode($data['days_of_week']) : null,
                'days_before_booking' => $data['days_before_booking'] ?? null,
                'days_before_max' => $data['days_before_max'] ?? null,
                'min_guests' => $data['min_guests'] ?? null,
                'max_guests' => $data['max_guests'] ?? null,
                'min_duration_hours' => $data['min_duration_hours'] ?? null,
                'adjustment_type' => $data['adjustment_type'],
                'adjustment_value' => $data['adjustment_value'],
                'priority' => $data['priority'] ?? 0,
                'is_stackable' => $data['is_stackable'] ?? false
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update pricing rule
     */
    public function updateRule(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['vessel_types', 'tour_categories', 'vessel_ids', 'tour_ids', 'days_of_week'])) {
                $value = is_array($value) ? json_encode($value) : $value;
            }
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "UPDATE pricing_rules SET " . implode(', ', $fields) . " WHERE id = :id";

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Delete pricing rule
     */
    public function deleteRule(int $id): bool
    {
        return $this->db->execute("DELETE FROM pricing_rules WHERE id = :id", ['id' => $id]) > 0;
    }

    /**
     * Toggle rule status
     */
    public function toggleRule(int $id): bool
    {
        return $this->db->execute(
            "UPDATE pricing_rules SET is_active = NOT is_active WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }

    /**
     * Get pricing summary for dashboard
     */
    public function getPricingSummary(): array
    {
        $activeRules = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM pricing_rules WHERE is_active = 1"
        );

        $currentSeasons = $this->db->fetchAll(
            "SELECT name, adjustment_value, adjustment_type
             FROM pricing_rules
             WHERE type = 'season' AND is_active = 1
             AND start_date <= CURDATE() AND end_date >= CURDATE()"
        );

        $upcomingSpecialDates = $this->db->fetchAll(
            "SELECT name, start_date, end_date, adjustment_value
             FROM pricing_rules
             WHERE type = 'special_date' AND is_active = 1
             AND start_date > CURDATE()
             ORDER BY start_date ASC
             LIMIT 5"
        );

        return [
            'active_rules_count' => (int) $activeRules['count'],
            'current_seasons' => $currentSeasons,
            'upcoming_special_dates' => $upcomingSpecialDates
        ];
    }
}
