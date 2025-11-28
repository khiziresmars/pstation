<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Services\BookingService;
use App\Services\PromoService;

/**
 * Booking Controller
 * Handles booking operations
 */
class BookingController
{
    private BookingService $bookingService;
    private PromoService $promoService;

    public function __construct()
    {
        $this->bookingService = new BookingService();
        $this->promoService = new PromoService();
    }

    /**
     * POST /api/bookings
     * Create a new booking
     */
    public function create(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $data = Request::json();

        // Validate required fields
        $errors = Request::validate([
            'type' => 'required|in:vessel,tour',
            'item_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        // Additional validation based on type
        if (($data['type'] ?? '') === 'vessel') {
            $vesselErrors = Request::validate([
                'hours' => 'required|integer|min:4',
                'start_time' => 'required|string',
            ]);
            $errors = array_merge($errors, $vesselErrors);
        }

        if (($data['type'] ?? '') === 'tour') {
            $tourErrors = Request::validate([
                'adults' => 'required|integer|min:1',
            ]);
            $errors = array_merge($errors, $tourErrors);
        }

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Check date is in future
        if (strtotime($data['date']) < strtotime('today')) {
            Response::error('Booking date must be in the future', 400, 'INVALID_DATE');
            return;
        }

        $result = $this->bookingService->create($userId, $data);

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success($result, 'Booking created successfully', 201);
    }

    /**
     * GET /api/bookings/{reference}
     * Get booking details
     */
    public function show(array $params): void
    {
        $userId = AuthMiddleware::userId();
        $reference = $params['reference'] ?? '';

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $booking = $this->bookingService->getByReference($reference);

        if (!$booking) {
            Response::notFound('Booking not found');
            return;
        }

        // Check ownership
        if ($booking['user_id'] !== $userId) {
            Response::forbidden();
            return;
        }

        Response::success($booking);
    }

    /**
     * POST /api/bookings/{reference}/cancel
     * Cancel a booking
     */
    public function cancel(array $params): void
    {
        $userId = AuthMiddleware::userId();
        $reference = $params['reference'] ?? '';

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $reason = Request::input('reason');

        $result = $this->bookingService->cancel($reference, $userId, $reason);

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success(null, 'Booking cancelled successfully');
    }

    /**
     * POST /api/bookings/calculate
     * Calculate booking price
     */
    public function calculate(): void
    {
        $userId = AuthMiddleware::userId();
        $data = Request::json();

        // Validate
        $errors = Request::validate([
            'type' => 'required|in:vessel,tour',
            'item_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Calculate base price
        $pricing = $this->bookingService->calculateTotalPrice($data);

        if (isset($pricing['error'])) {
            Response::error($pricing['error'], 400);
            return;
        }

        // Apply promo code if provided
        $promoDiscount = 0;
        $promoInfo = null;

        if (!empty($data['promo_code']) && $userId) {
            $promoResult = $this->promoService->validate(
                $data['promo_code'],
                $userId,
                $data['type'],
                $data['item_id'],
                $pricing['subtotal_thb']
            );

            if ($promoResult['valid']) {
                $promoDiscount = $promoResult['discount'];
                $promoInfo = [
                    'code' => $promoResult['code'],
                    'type' => $promoResult['type'],
                    'value' => $promoResult['value'],
                    'discount' => $promoResult['discount'],
                ];
            } else {
                $promoInfo = [
                    'valid' => false,
                    'error' => $promoResult['error'],
                ];
            }
        }

        // Get user cashback if authenticated
        $availableCashback = 0;
        if ($userId) {
            $userService = new \App\Services\UserService();
            $user = $userService->findById($userId);
            $availableCashback = (float) ($user['cashback_balance'] ?? 0);
        }

        // Calculate max cashback usage (50% of order)
        $maxCashbackUsage = min($availableCashback, $pricing['subtotal_thb'] * 0.5);

        // Calculate final price
        $cashbackToUse = min($data['use_cashback'] ?? 0, $maxCashbackUsage);
        $totalDiscount = $promoDiscount + $cashbackToUse;
        $totalPrice = max(0, $pricing['subtotal_thb'] - $totalDiscount);

        // Calculate potential cashback earned
        $cashbackPercent = (float) \App\Core\Application::getInstance()->getConfig('loyalty.cashback_percent', 5);
        $cashbackEarned = $totalPrice * ($cashbackPercent / 100);

        Response::success([
            'pricing' => $pricing,
            'promo' => $promoInfo,
            'cashback' => [
                'available' => $availableCashback,
                'max_usage' => $maxCashbackUsage,
                'to_use' => $cashbackToUse,
                'will_earn' => round($cashbackEarned, 2),
                'percent' => $cashbackPercent,
            ],
            'discounts' => [
                'promo' => $promoDiscount,
                'cashback' => $cashbackToUse,
                'total' => $totalDiscount,
            ],
            'total_thb' => $totalPrice,
        ]);
    }
}
