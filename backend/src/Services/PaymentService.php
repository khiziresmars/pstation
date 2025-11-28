<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * Payment Service
 * Handles payment processing with multiple providers:
 * - Telegram Stars
 * - Stripe (Credit/Debit cards)
 * - NowPayments (Cryptocurrency)
 */
class PaymentService
{
    private Database $db;
    private string $botToken;
    private float $starsRateTHB;
    private BookingService $bookingService;
    private array $config;

    public function __construct()
    {
        $app = Application::getInstance();
        $this->db = Database::getInstance();
        $this->botToken = $app->getConfig('telegram.bot_token', $_ENV['TELEGRAM_BOT_TOKEN'] ?? '');
        $this->starsRateTHB = $app->getConfig('telegram.stars_rate_thb', 0.46);
        $this->bookingService = new BookingService();

        $this->config = [
            'stripe' => [
                'secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? '',
                'publishable_key' => $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '',
                'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '',
            ],
            'nowpayments' => [
                'api_key' => $_ENV['NOWPAYMENTS_API_KEY'] ?? '',
                'ipn_secret' => $_ENV['NOWPAYMENTS_IPN_SECRET'] ?? '',
                'sandbox' => (bool) ($_ENV['NOWPAYMENTS_SANDBOX'] ?? true),
            ],
        ];
    }

    /**
     * Get available payment methods
     */
    public function getMethods(): array
    {
        return [
            [
                'id' => 'card',
                'name' => 'Credit/Debit Card',
                'type' => 'card',
                'icon' => '💳',
                'enabled' => !empty($this->config['stripe']['secret_key']),
                'min_amount' => 100,
                'max_amount' => 1000000,
                'fee_percent' => 2.9,
            ],
            [
                'id' => 'crypto',
                'name' => 'Cryptocurrency',
                'type' => 'crypto',
                'icon' => '₿',
                'enabled' => !empty($this->config['nowpayments']['api_key']),
                'min_amount' => 500,
                'max_amount' => 5000000,
                'fee_percent' => 0.5,
            ],
            [
                'id' => 'telegram_stars',
                'name' => 'Telegram Stars',
                'type' => 'telegram_stars',
                'icon' => '⭐',
                'enabled' => !empty($this->botToken),
                'min_amount' => 100,
                'max_amount' => 500000,
                'fee_percent' => 0,
            ],
        ];
    }

    // ==========================================
    // STRIPE INTEGRATION
    // ==========================================

    /**
     * Create Stripe payment intent
     */
    public function createStripeIntent(string $bookingReference): array
    {
        $booking = $this->bookingService->getByReference($bookingReference);
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        if ($booking['status'] !== 'pending') {
            return ['error' => 'Booking cannot be paid'];
        }

        // Convert THB to USD
        $thbToUsd = $this->getExchangeRate('THB', 'USD');
        $amountUsd = round($booking['total_price_thb'] * $thbToUsd, 2);
        $amountCents = (int) ($amountUsd * 100);

        try {
            // Stripe SDK would be loaded via Composer
            // For now, use API directly
            $response = $this->stripeRequest('POST', '/payment_intents', [
                'amount' => $amountCents,
                'currency' => 'usd',
                'metadata' => [
                    'booking_reference' => $bookingReference,
                    'amount_thb' => $booking['total_price_thb'],
                ],
                'description' => "Booking {$bookingReference} - {$booking['item_name']}",
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            if (isset($response['error'])) {
                return ['error' => $response['error']['message'] ?? 'Payment creation failed'];
            }

            // Save payment record
            $this->savePaymentRecord([
                'booking_id' => $booking['id'],
                'provider' => 'stripe',
                'provider_payment_id' => $response['id'],
                'amount_thb' => $booking['total_price_thb'],
                'amount_provider' => $amountUsd,
                'currency' => 'USD',
                'status' => 'pending',
            ]);

            return [
                'client_secret' => $response['client_secret'],
                'payment_intent_id' => $response['id'],
                'amount' => $amountUsd,
                'currency' => 'USD',
                'publishable_key' => $this->config['stripe']['publishable_key'],
            ];
        } catch (\Exception $e) {
            error_log('Stripe error: ' . $e->getMessage());
            return ['error' => 'Failed to create payment'];
        }
    }

    /**
     * Confirm Stripe payment
     */
    public function confirmStripePayment(string $paymentIntentId): array
    {
        try {
            $response = $this->stripeRequest('GET', "/payment_intents/{$paymentIntentId}");

            if ($response['status'] === 'succeeded') {
                $bookingReference = $response['metadata']['booking_reference'] ?? null;
                if ($bookingReference) {
                    $this->markBookingPaid($bookingReference, 'stripe', $paymentIntentId);
                }
                return ['success' => true];
            }

            return ['success' => false, 'status' => $response['status']];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripeWebhook(string $payload, string $signature): array
    {
        // Verify webhook signature
        $webhookSecret = $this->config['stripe']['webhook_secret'];
        if (!$this->verifyStripeSignature($payload, $signature, $webhookSecret)) {
            return ['error' => 'Invalid signature'];
        }

        $event = json_decode($payload, true);

        if ($event['type'] === 'payment_intent.succeeded') {
            $paymentIntent = $event['data']['object'];
            $bookingReference = $paymentIntent['metadata']['booking_reference'] ?? null;
            if ($bookingReference) {
                $this->markBookingPaid($bookingReference, 'stripe', $paymentIntent['id']);
            }
        }

        return ['received' => true];
    }

    /**
     * Make Stripe API request
     */
    private function stripeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = "https://api.stripe.com/v1" . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->config['stripe']['secret_key'] . ':',
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->flattenArray($data)));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    /**
     * Flatten array for Stripe
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Verify Stripe webhook signature
     */
    private function verifyStripeSignature(string $payload, string $header, string $secret): bool
    {
        $parts = explode(',', $header);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part, 2);
            if ($key === 't') $timestamp = $value;
            if ($key === 'v1') $signature = $value;
        }

        if (!$timestamp || !$signature) return false;

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    // ==========================================
    // NOWPAYMENTS (CRYPTO) INTEGRATION
    // ==========================================

    /**
     * Create NowPayments crypto payment
     */
    public function createCryptoPayment(string $bookingReference, string $currency = 'btc'): array
    {
        $booking = $this->bookingService->getByReference($bookingReference);
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        if ($booking['status'] !== 'pending') {
            return ['error' => 'Booking cannot be paid'];
        }

        // Convert THB to USD
        $thbToUsd = $this->getExchangeRate('THB', 'USD');
        $amountUsd = round($booking['total_price_thb'] * $thbToUsd, 2);

        $baseUrl = $this->config['nowpayments']['sandbox']
            ? 'https://api-sandbox.nowpayments.io/v1'
            : 'https://api.nowpayments.io/v1';

        try {
            $response = $this->nowPaymentsRequest('POST', "{$baseUrl}/payment", [
                'price_amount' => $amountUsd,
                'price_currency' => 'usd',
                'pay_currency' => $currency,
                'order_id' => $bookingReference,
                'order_description' => "Booking {$bookingReference} - {$booking['item_name']}",
                'ipn_callback_url' => ($_ENV['APP_URL'] ?? '') . '/api/payments/crypto/webhook',
            ]);

            if (isset($response['payment_id'])) {
                // Save payment record
                $this->savePaymentRecord([
                    'booking_id' => $booking['id'],
                    'provider' => 'nowpayments',
                    'provider_payment_id' => (string) $response['payment_id'],
                    'amount_thb' => $booking['total_price_thb'],
                    'amount_provider' => $response['pay_amount'],
                    'currency' => strtoupper($currency),
                    'status' => 'pending',
                    'metadata' => json_encode($response),
                ]);

                // Generate QR code URL
                $qrData = "{$currency}:{$response['pay_address']}?amount={$response['pay_amount']}";
                $qrCode = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);

                return [
                    'payment_id' => (string) $response['payment_id'],
                    'payment_url' => $response['invoice_url'] ?? null,
                    'pay_address' => $response['pay_address'],
                    'pay_amount' => $response['pay_amount'],
                    'pay_currency' => $currency,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                    'qr_code' => $qrCode,
                ];
            }

            return ['error' => $response['message'] ?? 'Failed to create crypto payment'];
        } catch (\Exception $e) {
            error_log('NowPayments error: ' . $e->getMessage());
            return ['error' => 'Failed to create crypto payment'];
        }
    }

    /**
     * Get crypto payment status
     */
    public function getCryptoPaymentStatus(string $paymentId): array
    {
        $baseUrl = $this->config['nowpayments']['sandbox']
            ? 'https://api-sandbox.nowpayments.io/v1'
            : 'https://api.nowpayments.io/v1';

        try {
            $response = $this->nowPaymentsRequest('GET', "{$baseUrl}/payment/{$paymentId}");

            return [
                'status' => $response['payment_status'] ?? 'unknown',
                'actually_paid' => $response['actually_paid'] ?? 0,
                'outcome_amount' => $response['outcome_amount'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get available crypto currencies
     */
    public function getCryptoCurrencies(): array
    {
        return [
            ['id' => 'btc', 'name' => 'Bitcoin', 'symbol' => 'BTC', 'logo' => '/images/crypto/btc.png', 'min_amount' => 0.0001],
            ['id' => 'eth', 'name' => 'Ethereum', 'symbol' => 'ETH', 'logo' => '/images/crypto/eth.png', 'min_amount' => 0.001],
            ['id' => 'usdt', 'name' => 'Tether (ERC20)', 'symbol' => 'USDT', 'logo' => '/images/crypto/usdt.png', 'min_amount' => 1],
            ['id' => 'usdttrc20', 'name' => 'Tether (TRC20)', 'symbol' => 'USDT', 'logo' => '/images/crypto/usdt.png', 'min_amount' => 1],
            ['id' => 'usdc', 'name' => 'USD Coin', 'symbol' => 'USDC', 'logo' => '/images/crypto/usdc.png', 'min_amount' => 1],
            ['id' => 'ltc', 'name' => 'Litecoin', 'symbol' => 'LTC', 'logo' => '/images/crypto/ltc.png', 'min_amount' => 0.01],
            ['id' => 'trx', 'name' => 'Tron', 'symbol' => 'TRX', 'logo' => '/images/crypto/trx.png', 'min_amount' => 10],
        ];
    }

    /**
     * Handle NowPayments webhook (IPN)
     */
    public function handleCryptoWebhook(array $payload, string $signature): array
    {
        // Verify signature
        $hmac = hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES), $this->config['nowpayments']['ipn_secret']);
        if (!hash_equals($hmac, $signature)) {
            return ['error' => 'Invalid signature'];
        }

        $paymentId = $payload['payment_id'] ?? null;
        $status = $payload['payment_status'] ?? null;
        $orderId = $payload['order_id'] ?? null;

        if (($status === 'finished' || $status === 'confirmed') && $orderId) {
            $this->markBookingPaid($orderId, 'nowpayments', (string) $paymentId);
        }

        // Update payment record
        $this->db->execute(
            "UPDATE payments SET status = ?, updated_at = NOW() WHERE provider_payment_id = ?",
            [$status, (string) $paymentId]
        );

        return ['received' => true];
    }

    /**
     * Make NowPayments API request
     */
    private function nowPaymentsRequest(string $method, string $url, array $data = []): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->config['nowpayments']['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception($error);
        }

        return json_decode($response, true) ?? [];
    }

    // ==========================================
    // TELEGRAM STARS
    // ==========================================

    /**
     * Create Telegram Stars invoice
     */
    public function createTelegramStarsInvoice(string $bookingReference, int $telegramUserId): array
    {
        $booking = $this->bookingService->getByReference($bookingReference);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        if ($booking['status'] !== 'pending') {
            return ['error' => 'Booking cannot be paid'];
        }

        // Convert THB to Stars
        $starsAmount = $this->convertTHBToStars($booking['total_price_thb']);

        // Create invoice payload
        $payload = json_encode([
            'booking_reference' => $bookingReference,
            'amount_thb' => $booking['total_price_thb'],
            'stars' => $starsAmount,
            'created_at' => time(),
        ]);

        // Create invoice via Telegram Bot API
        $invoiceData = [
            'chat_id' => $telegramUserId,
            'title' => "Booking {$bookingReference}",
            'description' => $this->getInvoiceDescription($booking),
            'payload' => $payload,
            'currency' => 'XTR', // Telegram Stars currency code
            'prices' => [
                [
                    'label' => $booking['item_name'] ?? 'Booking',
                    'amount' => $starsAmount,
                ]
            ],
        ];

        $result = $this->callTelegramAPI('sendInvoice', $invoiceData);

        if (!($result['ok'] ?? false)) {
            return ['error' => $result['description'] ?? 'Failed to create invoice'];
        }

        // Save payment record
        $invoiceId = 'stars_' . uniqid();
        $this->savePaymentRecord([
            'booking_id' => $booking['id'],
            'provider' => 'telegram_stars',
            'provider_payment_id' => $invoiceId,
            'amount_thb' => $booking['total_price_thb'],
            'amount_provider' => $starsAmount,
            'currency' => 'XTR',
            'status' => 'pending',
        ]);

        return [
            'success' => true,
            'invoice_id' => $invoiceId,
            'stars_amount' => $starsAmount,
            'thb_amount' => $booking['total_price_thb'],
        ];
    }

    /**
     * Process successful Telegram payment
     */
    public function processSuccessfulPayment(array $payment): array
    {
        $payload = json_decode($payment['invoice_payload'], true);

        if (!$payload || !isset($payload['booking_reference'])) {
            return ['error' => 'Invalid payment payload'];
        }

        $bookingReference = $payload['booking_reference'];
        $chargeId = $payment['telegram_payment_charge_id'] ?? null;

        $this->markBookingPaid($bookingReference, 'telegram_stars', $chargeId);

        return [
            'success' => true,
            'booking_reference' => $bookingReference,
        ];
    }

    /**
     * Confirm Telegram Stars payment
     */
    public function confirmTelegramStarsPayment(string $invoiceId, string $telegramPaymentChargeId): array
    {
        $payment = $this->db->fetchOne(
            "SELECT p.*, b.booking_reference
             FROM payments p
             JOIN bookings b ON p.booking_id = b.id
             WHERE p.provider_payment_id = ? AND p.provider = 'telegram_stars'",
            [$invoiceId]
        );

        if (!$payment) {
            return ['error' => 'Payment not found'];
        }

        $this->markBookingPaid($payment['booking_reference'], 'telegram_stars', $telegramPaymentChargeId);

        return ['success' => true];
    }

    /**
     * Convert THB to Telegram Stars
     */
    public function convertTHBToStars(float $thbAmount): int
    {
        // 1 Star ≈ 0.46 THB (approximate - adjust based on actual rate)
        $starsPerTHB = 1 / $this->starsRateTHB;
        return (int) ceil($thbAmount * $starsPerTHB);
    }

    /**
     * Get invoice description
     */
    private function getInvoiceDescription(array $booking): string
    {
        $type = ($booking['bookable_type'] ?? 'item') === 'vessel' ? 'Yacht Rental' : 'Tour';
        $date = date('M d, Y', strtotime($booking['booking_date'] ?? 'now'));

        $description = "{$type}: {$booking['item_name']}\n";
        $description .= "Date: {$date}\n";

        if (($booking['bookable_type'] ?? '') === 'vessel' && !empty($booking['duration_hours'])) {
            $description .= "Duration: {$booking['duration_hours']} hours\n";
        }

        if (!empty($booking['adults_count'])) {
            $description .= "Guests: {$booking['adults_count']} adults";
            if (!empty($booking['children_count'])) {
                $description .= ", {$booking['children_count']} children";
            }
            $description .= "\n";
        }

        $description .= "Total: ฿" . number_format($booking['total_price_thb'] ?? 0, 0);

        return $description;
    }

    /**
     * Call Telegram Bot API
     */
    private function callTelegramAPI(string $method, array $data): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/{$method}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'description' => $error];
        }

        return json_decode($response, true) ?? ['ok' => false, 'description' => 'Invalid response'];
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Save payment record
     */
    private function savePaymentRecord(array $data): int
    {
        $this->db->execute(
            "INSERT INTO payments (booking_id, provider, provider_payment_id, amount_thb, amount_provider, currency, status, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['booking_id'],
                $data['provider'],
                $data['provider_payment_id'],
                $data['amount_thb'],
                $data['amount_provider'],
                $data['currency'],
                $data['status'],
                $data['metadata'] ?? null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Mark booking as paid
     */
    private function markBookingPaid(string $reference, string $provider, ?string $transactionId): void
    {
        $this->db->execute(
            "UPDATE bookings
             SET status = 'paid',
                 payment_method = ?,
                 payment_transaction_id = ?,
                 paid_at = NOW(),
                 updated_at = NOW()
             WHERE booking_reference = ? AND status = 'pending'",
            [$provider, $transactionId, $reference]
        );

        if ($transactionId) {
            $this->db->execute(
                "UPDATE payments
                 SET status = 'completed',
                     updated_at = NOW()
                 WHERE provider_payment_id = ?",
                [$transactionId]
            );
        }

        // Trigger status transition and cashback
        $booking = $this->bookingService->getByReference($reference);
        if ($booking) {
            $statusService = new BookingStatusService($this->db);
            $statusService->transition(
                $booking['id'],
                'paid',
                'system',
                null,
                'Payment received via ' . $provider
            );
        }
    }

    /**
     * Get exchange rate
     */
    private function getExchangeRate(string $from, string $to): float
    {
        // Simple fallback rates
        $rates = [
            'THB' => ['USD' => 0.028, 'EUR' => 0.026],
            'USD' => ['THB' => 35.5],
        ];

        return $rates[$from][$to] ?? 0.028;
    }

    /**
     * Get bank transfer details for fallback payment
     */
    public function getBankTransferDetails(string $bookingReference): array
    {
        $booking = $this->bookingService->getByReference($bookingReference);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        return [
            'booking_reference' => $bookingReference,
            'amount_thb' => $booking['total_price_thb'],
            'bank_details' => [
                [
                    'bank' => 'Bangkok Bank',
                    'account_name' => 'Phuket Yacht Tours Co., Ltd.',
                    'account_number' => '123-4-56789-0',
                    'swift' => 'BKKBTHBK',
                ],
                [
                    'bank' => 'Kasikorn Bank',
                    'account_name' => 'Phuket Yacht Tours Co., Ltd.',
                    'account_number' => '098-7-65432-1',
                    'swift' => 'KASITHBK',
                ],
            ],
            'instructions' => [
                'Please include your booking reference in the transfer description',
                'Send payment confirmation to payments@phuket-yachts.com',
                'Booking will be confirmed within 24 hours after payment verification',
            ],
        ];
    }
}
