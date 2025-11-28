<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;
use App\Services\BookingService;
use App\Services\PromptPayService;
use App\Services\YooKassaService;

/**
 * Payment Controller
 * Handles all payment endpoints for Stripe, NowPayments (crypto), Telegram Stars,
 * PromptPay (Thai QR), and YooKassa (Russian payments)
 */
class PaymentController
{
    private PaymentService $paymentService;
    private BookingService $bookingService;
    private PromptPayService $promptPayService;
    private YooKassaService $yooKassaService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
        $this->bookingService = new BookingService();
        $this->promptPayService = new PromptPayService();
        $this->yooKassaService = new YooKassaService();
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

    // ==========================================
    // PROMPTPAY (THAI QR) ENDPOINTS
    // ==========================================

    /**
     * POST /api/payments/promptpay/create
     * Generate PromptPay QR code for payment
     */
    public function createPromptPay(Request $request): Response
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

        $result = $this->promptPayService->generateQRCode($bookingReference, $booking['total_price_thb']);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    /**
     * GET /api/payments/promptpay/pending
     * Get pending PromptPay payments (admin only)
     */
    public function promptPayPending(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $payments = $this->promptPayService->getPendingPayments();
        return Response::json(['payments' => $payments]);
    }

    /**
     * POST /api/payments/promptpay/confirm
     * Manually confirm PromptPay payment (admin only)
     */
    public function confirmPromptPay(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $data = $request->json();
        $paymentId = $data['payment_id'] ?? null;
        $transactionRef = $data['transaction_ref'] ?? null;

        if (!$paymentId || !$transactionRef) {
            return Response::json(['error' => 'payment_id and transaction_ref are required'], 400);
        }

        $result = $this->promptPayService->confirmPayment($paymentId, $transactionRef, (int) $user['id']);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    // ==========================================
    // YOOKASSA (RUSSIAN PAYMENTS) ENDPOINTS
    // ==========================================

    /**
     * POST /api/payments/yookassa/create
     * Create YooKassa payment
     */
    public function createYooKassa(Request $request): Response
    {
        $userId = $request->getUserId();
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->json();
        $bookingReference = $data['booking_reference'] ?? null;
        $paymentMethod = $data['payment_method'] ?? 'bank_card';

        if (!$bookingReference) {
            return Response::json(['error' => 'booking_reference is required'], 400);
        }

        // Verify booking belongs to user
        $booking = $this->bookingService->getByReference($bookingReference);
        if (!$booking || $booking['user_id'] !== $userId) {
            return Response::json(['error' => 'Booking not found'], 404);
        }

        $result = $this->yooKassaService->createPayment($bookingReference, $paymentMethod);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    /**
     * GET /api/payments/yookassa/status/{payment_id}
     * Get YooKassa payment status
     */
    public function yooKassaStatus(Request $request): Response
    {
        $paymentId = $request->get('payment_id');

        if (!$paymentId) {
            return Response::json(['error' => 'payment_id is required'], 400);
        }

        $result = $this->yooKassaService->getPaymentStatus($paymentId);

        return Response::json($result);
    }

    /**
     * GET /api/payments/yookassa/methods
     * Get available YooKassa payment methods
     */
    public function yooKassaMethods(Request $request): Response
    {
        $methods = $this->yooKassaService->getPaymentMethods();
        return Response::json(['methods' => $methods]);
    }

    /**
     * POST /api/payments/yookassa/webhook
     * Handle YooKassa webhook
     */
    public function yooKassaWebhook(Request $request): Response
    {
        $payload = $request->json();

        $result = $this->yooKassaService->handleWebhook($payload);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }

    /**
     * POST /api/payments/yookassa/refund
     * Create YooKassa refund (admin only)
     */
    public function yooKassaRefund(Request $request): Response
    {
        $user = $request->getUser();
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $data = $request->json();
        $paymentId = $data['payment_id'] ?? null;
        $amount = $data['amount'] ?? null;

        if (!$paymentId) {
            return Response::json(['error' => 'payment_id is required'], 400);
        }

        $result = $this->yooKassaService->createRefund($paymentId, $amount);

        if (isset($result['error'])) {
            return Response::json($result, 400);
        }

        return Response::json($result);
    }
}
