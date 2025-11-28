<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PricingService;

class PricingController
{
    private PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Calculate price with dynamic pricing rules
     */
    public function calculate(Request $request): Response
    {
        $data = $request->json();

        $basePrice = (float) ($data['base_price'] ?? 0);
        $bookingDate = $data['booking_date'] ?? date('Y-m-d');

        if ($basePrice <= 0) {
            return Response::json(['error' => 'base_price is required'], 400);
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate)) {
            return Response::json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
        }

        $params = [
            'base_price' => $basePrice,
            'booking_date' => $bookingDate,
            'applies_to' => $data['applies_to'] ?? 'all',
            'item_type' => $data['item_type'] ?? null,
            'item_id' => $data['item_id'] ?? null,
            'guests' => (int) ($data['guests'] ?? 1),
            'duration_hours' => isset($data['duration_hours']) ? (int) $data['duration_hours'] : null
        ];

        $result = $this->pricingService->calculatePrice($params);

        return Response::json($result);
    }

    /**
     * Get price calendar for a month
     */
    public function calendar(Request $request): Response
    {
        $itemId = (int) $request->get('item_id');
        $itemType = $request->get('item_type', 'vessel');
        $month = $request->get('month', date('Y-m'));
        $basePrice = (float) $request->get('base_price', 0);

        if (!$itemId || $basePrice <= 0) {
            return Response::json(['error' => 'item_id and base_price are required'], 400);
        }

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Response::json(['error' => 'Invalid month format. Use YYYY-MM'], 400);
        }

        $calendar = $this->pricingService->getPriceCalendar($itemId, $itemType, $month, $basePrice);

        return Response::json([
            'month' => $month,
            'calendar' => $calendar
        ]);
    }

    /**
     * Get current active pricing rules summary
     */
    public function summary(): Response
    {
        $summary = $this->pricingService->getPricingSummary();

        return Response::json($summary);
    }

    /**
     * Check if date has special pricing
     */
    public function checkDate(Request $request): Response
    {
        $date = $request->get('date', date('Y-m-d'));

        // Simple price check with base 10000
        $result = $this->pricingService->calculatePrice([
            'base_price' => 10000,
            'booking_date' => $date,
            'applies_to' => 'all'
        ]);

        $hasSpecialPricing = !empty($result['applied_rules']);
        $isPremium = $result['total_adjustment_thb'] > 0;
        $isDiscount = $result['total_adjustment_thb'] < 0;

        return Response::json([
            'date' => $date,
            'has_special_pricing' => $hasSpecialPricing,
            'is_premium' => $isPremium,
            'is_discount' => $isDiscount,
            'adjustment_percent' => $isPremium ? $result['premium_percent'] : -$result['discount_percent'],
            'rules_applied' => count($result['applied_rules'])
        ]);
    }
}
