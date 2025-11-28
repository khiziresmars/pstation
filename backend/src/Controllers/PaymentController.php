<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Services\PaymentService;
use App\Services\BookingService;

/**
 * Payment Controller
 * Handles payment endpoints
 */
class PaymentController
{
    private PaymentService $paymentService;
    private BookingService $bookingService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
        $this->bookingService = new BookingService();
    }

    /**
     * POST /api/payments/telegram-stars/create
     * Create Telegram Stars payment invoice
     */
    public function createTelegramStars(): void
    {
        $user = AuthMiddleware::user();

        if (!$user) {
            Response::unauthorized();
            return;
        }

        $data = Request::json();

        $errors = Request::validate([
            'booking_reference' => 'required|string',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Verify booking belongs to user
        $booking = $this->bookingService->getByReference($data['booking_reference']);

        if (!$booking) {
            Response::notFound('Booking not found');
            return;
        }

        if ($booking['user_id'] !== AuthMiddleware::userId()) {
            Response::forbidden();
            return;
        }

        if ($booking['status'] !== 'pending') {
            Response::error('Booking cannot be paid', 400);
            return;
        }

        $result = $this->paymentService->createTelegramStarsInvoice(
            $data['booking_reference'],
            (int) $user['telegram_id']
        );

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success($result);
    }

    /**
     * POST /api/payments/telegram-stars/confirm
     * Confirm Telegram Stars payment (webhook callback)
     */
    public function confirmTelegramStars(): void
    {
        $data = Request::json();

        if (empty($data['invoice_payload']) || empty($data['telegram_payment_charge_id'])) {
            Response::error('Invalid payment data', 400);
            return;
        }

        $result = $this->paymentService->processSuccessfulPayment($data);

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success($result, 'Payment confirmed');
    }

    /**
     * GET /api/payments/bank-transfer/{reference}
     * Get bank transfer details
     */
    public function bankTransferDetails(array $params): void
    {
        $reference = $params['reference'] ?? '';

        $result = $this->paymentService->getBankTransferDetails($reference);

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success($result);
    }
}
