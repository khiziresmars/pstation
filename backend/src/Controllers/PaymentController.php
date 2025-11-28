<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;
use App\Services\BookingService;

/**
 * Payment Controller
 * Handles all payment endpoints for Stripe, NowPayments (crypto), and Telegram Stars
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
     * GET /api/payments/methods
     * Get available payment methods
     */
    public function methods(Request $request): Response
    {
        $methods = $this->paymentService->getMethods();
        return Response::json(['methods' => $methods]);
    }

    // ==========================================
    // STRIPE ENDPOINTS
    // ==========================================

    /**
     * POST /api/payments/stripe/create
     * Create Stripe payment intent
     */
    public function createStripe(Request $request): Response
    {
        $userId = $request->getUserId();
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->json();
        $bookingReference = $data['booking_reference'] ?? null;

        if (!$bookingReference) {
            return Response::json(['error' => 'booking_reference is required'], 400);
        }

        // Verify booking belongs to user
        $booking = $this->bookingService->getByReference($bookingReference);
        if (!$booking || $booking['user_id'] !== $userId) {
            return Response::json(['error' => 'Booking not found'], 404);
        }

        $result = $this->paymentService->createStripeIntent($bookingReference);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    /**
     * POST /api/payments/stripe/confirm
     * Confirm Stripe payment
     */
    public function confirmStripe(Request $request): Response
    {
        $data = $request->json();
        $paymentIntentId = $data['payment_intent_id'] ?? null;

        if (!$paymentIntentId) {
            return Response::json(['error' => 'payment_intent_id is required'], 400);
        }

        $result = $this->paymentService->confirmStripePayment($paymentIntentId);

        return Response::json($result);
    }

    /**
     * POST /api/payments/stripe/webhook
     * Handle Stripe webhook
     */
    public function stripeWebhook(Request $request): Response
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        $result = $this->paymentService->handleStripeWebhook($payload, $signature);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    // ==========================================
    // CRYPTO (NOWPAYMENTS) ENDPOINTS
    // ==========================================

    /**
     * POST /api/payments/crypto/create
     * Create crypto payment via NowPayments
     */
    public function createCrypto(Request $request): Response
    {
        $userId = $request->getUserId();
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->json();
        $bookingReference = $data['booking_reference'] ?? null;
        $currency = $data['currency'] ?? 'btc';

        if (!$bookingReference) {
            return Response::json(['error' => 'booking_reference is required'], 400);
        }

        // Verify booking belongs to user
        $booking = $this->bookingService->getByReference($bookingReference);
        if (!$booking || $booking['user_id'] !== $userId) {
            return Response::json(['error' => 'Booking not found'], 404);
        }

        $result = $this->paymentService->createCryptoPayment($bookingReference, $currency);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    /**
     * GET /api/payments/crypto/status/{payment_id}
     * Get crypto payment status
     */
    public function cryptoStatus(Request $request): Response
    {
        $paymentId = $request->get('payment_id');

        if (!$paymentId) {
            return Response::json(['error' => 'payment_id is required'], 400);
        }

        $result = $this->paymentService->getCryptoPaymentStatus($paymentId);

        return Response::json($result);
    }

    /**
     * GET /api/payments/crypto/currencies
     * Get available crypto currencies
     */
    public function cryptoCurrencies(Request $request): Response
    {
        $currencies = $this->paymentService->getCryptoCurrencies();
        return Response::json(['currencies' => $currencies]);
    }

    /**
     * POST /api/payments/crypto/webhook
     * Handle NowPayments webhook (IPN)
     */
    public function cryptoWebhook(Request $request): Response
    {
        $payload = $request->json();
        $signature = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

        $result = $this->paymentService->handleCryptoWebhook($payload, $signature);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    // ==========================================
    // TELEGRAM STARS ENDPOINTS
    // ==========================================

    /**
     * POST /api/payments/telegram-stars/create
     * Create Telegram Stars payment invoice
     */
    public function createTelegramStars(Request $request): Response
    {
        $userId = $request->getUserId();
        $user = $request->getUser();

        if (!$userId || !$user) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->json();
        $bookingReference = $data['booking_reference'] ?? null;

        if (!$bookingReference) {
            return Response::json(['error' => 'booking_reference is required'], 400);
        }

        // Verify booking belongs to user
        $booking = $this->bookingService->getByReference($bookingReference);
        if (!$booking) {
            return Response::json(['error' => 'Booking not found'], 404);
        }

        if ($booking['user_id'] !== $userId) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        if ($booking['status'] !== 'pending') {
            return Response::json(['error' => 'Booking cannot be paid'], 400);
        }

        $result = $this->paymentService->createTelegramStarsInvoice(
            $bookingReference,
            (int) $user['telegram_id']
        );

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    /**
     * POST /api/payments/telegram-stars/confirm
     * Confirm Telegram Stars payment
     */
    public function confirmTelegramStars(Request $request): Response
    {
        $data = $request->json();

        $invoiceId = $data['invoice_id'] ?? null;
        $telegramPaymentChargeId = $data['telegram_payment_charge_id'] ?? null;

        // Handle legacy format (from webhook)
        if (!$invoiceId && isset($data['invoice_payload'])) {
            $result = $this->paymentService->processSuccessfulPayment($data);
        } else {
            if (!$invoiceId || !$telegramPaymentChargeId) {
                return Response::json(['error' => 'invoice_id and telegram_payment_charge_id are required'], 400);
            }

            $result = $this->paymentService->confirmTelegramStarsPayment($invoiceId, $telegramPaymentChargeId);
        }

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    // ==========================================
    // BANK TRANSFER ENDPOINT
    // ==========================================

    /**
     * GET /api/payments/bank-transfer/{reference}
     * Get bank transfer details
     */
    public function bankTransferDetails(Request $request): Response
    {
        $reference = $request->get('reference');

        if (!$reference) {
            return Response::json(['error' => 'reference is required'], 400);
        }

        $result = $this->paymentService->getBankTransferDetails($reference);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }
}
