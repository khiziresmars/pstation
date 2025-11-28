<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * YooKassa Service
 * Russian Payment Gateway (formerly Yandex.Kassa)
 *
 * Supports:
 * - Bank cards (Visa, MasterCard, Mir)
 * - YooMoney (Yandex.Money)
 * - SberPay
 * - Qiwi
 * - Mobile payments
 * - Bank transfers
 *
 * @see https://yookassa.ru/developers/api
 */
class YooKassaService
{
    private Database $db;
    private bool $enabled;
    private array $config;
    private string $apiUrl = 'https://api.yookassa.ru/v3';

    public function __construct()
    {
        $this->db = Database::getInstance();

        $this->enabled = (bool) ($_ENV['YOOKASSA_ENABLED'] ?? false);
        $this->config = [
            'shop_id' => $_ENV['YOOKASSA_SHOP_ID'] ?? '',
            'secret_key' => $_ENV['YOOKASSA_SECRET_KEY'] ?? '',
            'return_url' => ($_ENV['APP_URL'] ?? '') . '/payment/success',
            'webhook_url' => ($_ENV['APP_URL'] ?? '') . '/api/payments/yookassa/webhook',
            'test_mode' => (bool) ($_ENV['YOOKASSA_TEST_MODE'] ?? true),
        ];
    }

    /**
     * Check if YooKassa is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled
            && !empty($this->config['shop_id'])
            && !empty($this->config['secret_key']);
    }

    /**
     * Create payment
     *
     * @param string $bookingReference
     * @param string $paymentMethod bank_card, yoo_money, sberpay, qiwi, etc.
     * @return array
     */
    public function createPayment(string $bookingReference, string $paymentMethod = 'bank_card'): array
    {
        if (!$this->isEnabled()) {
            return ['error' => 'YooKassa is not configured'];
        }

        $booking = $this->getBooking($bookingReference);
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        if ($booking['status'] !== 'pending') {
            return ['error' => 'Booking cannot be paid'];
        }

        // Convert THB to RUB
        $thbToRub = $this->getExchangeRate();
        $amountRub = round($booking['total_price_thb'] * $thbToRub, 2);

        // Minimum amount is 1 RUB
        if ($amountRub < 1) {
            $amountRub = 1;
        }

        $idempotenceKey = $this->generateIdempotenceKey($bookingReference);

        $paymentData = [
            'amount' => [
                'value' => number_format($amountRub, 2, '.', ''),
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $this->config['return_url'] . '?reference=' . $bookingReference,
            ],
            'capture' => true, // Auto-capture
            'description' => "Booking {$bookingReference} - Phuket Station",
            'metadata' => [
                'booking_reference' => $bookingReference,
                'amount_thb' => $booking['total_price_thb'],
            ],
        ];

        // Add payment method if specified
        if ($paymentMethod !== 'bank_card') {
            $paymentData['payment_method_data'] = [
                'type' => $this->mapPaymentMethod($paymentMethod),
            ];
        }

        try {
            $response = $this->apiRequest('POST', '/payments', $paymentData, $idempotenceKey);

            if (isset($response['id'])) {
                // Save payment record
                $this->savePaymentRecord([
                    'booking_id' => $booking['id'],
                    'provider' => 'yookassa',
                    'provider_payment_id' => $response['id'],
                    'amount_thb' => $booking['total_price_thb'],
                    'amount_provider' => $amountRub,
                    'currency' => 'RUB',
                    'status' => $response['status'],
                    'metadata' => json_encode($response),
                ]);

                return [
                    'success' => true,
                    'payment_id' => $response['id'],
                    'confirmation_url' => $response['confirmation']['confirmation_url'] ?? null,
                    'status' => $response['status'],
                    'amount' => $amountRub,
                    'currency' => 'RUB',
                ];
            }

            return ['error' => $response['description'] ?? 'Failed to create payment'];
        } catch (\Exception $e) {
            error_log('YooKassa error: ' . $e->getMessage());
            return ['error' => 'Failed to create payment: ' . $e->getMessage()];
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $response = $this->apiRequest('GET', "/payments/{$paymentId}");

            return [
                'id' => $response['id'] ?? null,
                'status' => $response['status'] ?? 'unknown',
                'paid' => $response['paid'] ?? false,
                'amount' => $response['amount'] ?? null,
                'metadata' => $response['metadata'] ?? [],
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Handle webhook notification
     */
    public function handleWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;
        $object = $payload['object'] ?? null;

        if (!$event || !$object) {
            return ['error' => 'Invalid webhook payload'];
        }

        $paymentId = $object['id'] ?? null;
        $status = $object['status'] ?? null;

        // Log webhook
        error_log("YooKassa webhook: event={$event}, payment_id={$paymentId}, status={$status}");

        // Update payment status
        if ($paymentId && $status) {
            $this->db->execute(
                "UPDATE payments SET status = ?, metadata = ?, updated_at = NOW() WHERE provider_payment_id = ? AND provider = 'yookassa'",
                [$status, json_encode($object), $paymentId]
            );
        }

        // Handle payment success
        if ($event === 'payment.succeeded' && $status === 'succeeded') {
            $bookingReference = $object['metadata']['booking_reference'] ?? null;
            if ($bookingReference) {
                $this->markBookingPaid($bookingReference, $paymentId);
            }
        }

        // Handle payment cancellation
        if ($event === 'payment.canceled') {
            // Payment was canceled - no action needed, user can try again
        }

        return ['received' => true];
    }

    /**
     * Create refund
     */
    public function createRefund(string $paymentId, float $amount = null): array
    {
        $payment = $this->db->fetchOne(
            "SELECT * FROM payments WHERE provider_payment_id = ? AND provider = 'yookassa'",
            [$paymentId]
        );

        if (!$payment) {
            return ['error' => 'Payment not found'];
        }

        $refundAmount = $amount ?? $payment['amount_provider'];
        $idempotenceKey = $this->generateIdempotenceKey("refund_{$paymentId}");

        try {
            $response = $this->apiRequest('POST', '/refunds', [
                'payment_id' => $paymentId,
                'amount' => [
                    'value' => number_format($refundAmount, 2, '.', ''),
                    'currency' => 'RUB',
                ],
            ], $idempotenceKey);

            if (isset($response['id'])) {
                // Update payment status
                $this->db->execute(
                    "UPDATE payments SET status = 'refunded', updated_at = NOW() WHERE provider_payment_id = ?",
                    [$paymentId]
                );

                return [
                    'success' => true,
                    'refund_id' => $response['id'],
                    'status' => $response['status'],
                ];
            }

            return ['error' => $response['description'] ?? 'Failed to create refund'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get available payment methods for YooKassa
     */
    public function getPaymentMethods(): array
    {
        return [
            [
                'id' => 'bank_card',
                'name' => 'Bank Card',
                'name_ru' => 'Банковская карта',
                'icon' => 'card',
                'description' => 'Visa, MasterCard, Mir',
            ],
            [
                'id' => 'yoo_money',
                'name' => 'YooMoney',
                'name_ru' => 'ЮMoney',
                'icon' => 'yoomoney',
                'description' => 'Electronic wallet',
            ],
            [
                'id' => 'sberpay',
                'name' => 'SberPay',
                'name_ru' => 'СберPay',
                'icon' => 'sber',
                'description' => 'Pay with Sber',
            ],
            [
                'id' => 'qiwi',
                'name' => 'QIWI Wallet',
                'name_ru' => 'QIWI Кошелёк',
                'icon' => 'qiwi',
                'description' => 'QIWI electronic wallet',
            ],
            [
                'id' => 'tinkoff_bank',
                'name' => 'Tinkoff',
                'name_ru' => 'Тинькофф',
                'icon' => 'tinkoff',
                'description' => 'Tinkoff Pay',
            ],
            [
                'id' => 'alfabank',
                'name' => 'Alfa-Click',
                'name_ru' => 'Альфа-Клик',
                'icon' => 'alfa',
                'description' => 'Alfa-Bank online',
            ],
            [
                'id' => 'mobile_balance',
                'name' => 'Mobile Balance',
                'name_ru' => 'Баланс телефона',
                'icon' => 'mobile',
                'description' => 'Pay from mobile balance',
            ],
        ];
    }

    /**
     * Map payment method to YooKassa type
     */
    private function mapPaymentMethod(string $method): string
    {
        $map = [
            'bank_card' => 'bank_card',
            'yoo_money' => 'yoo_money',
            'sberpay' => 'sberpay',
            'qiwi' => 'qiwi',
            'tinkoff_bank' => 'tinkoff_bank',
            'alfabank' => 'alfabank',
            'mobile_balance' => 'mobile_balance',
        ];

        return $map[$method] ?? 'bank_card';
    }

    /**
     * Make API request to YooKassa
     */
    private function apiRequest(string $method, string $endpoint, array $data = [], string $idempotenceKey = null): array
    {
        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->config['shop_id'] . ':' . $this->config['secret_key']),
        ];

        if ($idempotenceKey) {
            $headers[] = 'Idempotence-Key: ' . $idempotenceKey;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: {$error}");
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $result['description'] ?? "HTTP {$httpCode}";
            throw new \Exception($errorMessage);
        }

        return $result ?? [];
    }

    /**
     * Generate idempotence key
     */
    private function generateIdempotenceKey(string $reference): string
    {
        return hash('sha256', $reference . '_' . $this->config['shop_id'] . '_' . date('Y-m-d'));
    }

    /**
     * Get exchange rate THB to RUB
     */
    private function getExchangeRate(): float
    {
        // Try to get from database
        $rate = $this->db->fetchOne(
            "SELECT rate_to_thb FROM exchange_rates WHERE currency_code = 'RUB' AND is_active = 1"
        );

        if ($rate && $rate['rate_to_thb'] > 0) {
            return 1 / $rate['rate_to_thb']; // Convert to THB -> RUB
        }

        // Fallback rate (approximately)
        return 2.5; // 1 THB ≈ 2.5 RUB
    }

    /**
     * Get booking by reference
     */
    private function getBooking(string $reference): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM bookings WHERE booking_reference = ?",
            [$reference]
        );
    }

    /**
     * Mark booking as paid
     */
    private function markBookingPaid(string $reference, string $transactionId): void
    {
        $this->db->execute(
            "UPDATE bookings
             SET status = 'paid',
                 payment_method = 'yookassa',
                 payment_transaction_id = ?,
                 paid_at = NOW(),
                 updated_at = NOW()
             WHERE booking_reference = ? AND status = 'pending'",
            [$transactionId, $reference]
        );
    }

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
     * Get payment method info
     */
    public function getMethodInfo(): array
    {
        return [
            'id' => 'yookassa',
            'name' => 'YooKassa',
            'name_ru' => 'ЮKassa',
            'type' => 'redirect',
            'icon' => 'yookassa',
            'enabled' => $this->isEnabled(),
            'min_amount' => 100, // ~100 THB
            'max_amount' => 3500000, // ~100k RUB
            'fee_percent' => 3.5,
            'currencies' => ['RUB'],
            'description' => [
                'en' => 'Russian payment gateway (cards, YooMoney, SberPay)',
                'ru' => 'Российская платёжная система (карты, ЮMoney, СберPay)',
                'th' => 'ระบบชำระเงินรัสเซีย',
            ],
        ];
    }
}
