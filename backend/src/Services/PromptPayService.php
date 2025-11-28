<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * PromptPay Service
 * Thai QR Code Payment System (EMVCo Standard)
 *
 * PromptPay is Thailand's national e-payment system that supports:
 * - Phone number based payments
 * - National ID based payments
 * - QR code scanning for instant transfers
 */
class PromptPayService
{
    private Database $db;
    private bool $enabled;
    private array $config;

    // EMVCo QR Code Tags
    private const TAG_PAYLOAD_FORMAT = '00';
    private const TAG_POI_METHOD = '01';
    private const TAG_MERCHANT_INFO = '29';
    private const TAG_TRANSACTION_CURRENCY = '53';
    private const TAG_TRANSACTION_AMOUNT = '54';
    private const TAG_COUNTRY_CODE = '58';
    private const TAG_MERCHANT_NAME = '59';
    private const TAG_MERCHANT_CITY = '60';
    private const TAG_CRC = '63';

    // PromptPay AID (Application ID)
    private const PROMPTPAY_AID = 'A000000677010111';
    private const COUNTRY_CODE_TH = 'TH';
    private const CURRENCY_CODE_THB = '764';

    public function __construct()
    {
        $this->db = Database::getInstance();

        $this->enabled = (bool) ($_ENV['PROMPTPAY_ENABLED'] ?? false);
        $this->config = [
            'account_type' => $_ENV['PROMPTPAY_ACCOUNT_TYPE'] ?? 'phone', // 'phone', 'national_id', 'ewallet'
            'account_id' => $_ENV['PROMPTPAY_ACCOUNT_ID'] ?? '',
            'merchant_name' => $_ENV['PROMPTPAY_MERCHANT_NAME'] ?? 'Phuket Station',
            'merchant_city' => $_ENV['PROMPTPAY_MERCHANT_CITY'] ?? 'PHUKET',
        ];
    }

    /**
     * Check if PromptPay is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->config['account_id']);
    }

    /**
     * Generate PromptPay QR Code data
     */
    public function generateQRCode(string $bookingReference, float $amount): array
    {
        if (!$this->isEnabled()) {
            return ['error' => 'PromptPay is not configured'];
        }

        $booking = $this->getBooking($bookingReference);
        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        if ($booking['status'] !== 'pending') {
            return ['error' => 'Booking cannot be paid'];
        }

        // Generate QR code payload
        $qrPayload = $this->generateEMVCoQRPayload($amount, $bookingReference);

        // Generate QR code image URL
        $qrImageUrl = $this->generateQRImageUrl($qrPayload);

        // Save payment record
        $paymentId = $this->savePaymentRecord([
            'booking_id' => $booking['id'],
            'provider' => 'promptpay',
            'provider_payment_id' => 'pp_' . $bookingReference . '_' . time(),
            'amount_thb' => $amount,
            'amount_provider' => $amount,
            'currency' => 'THB',
            'status' => 'pending',
            'metadata' => json_encode([
                'qr_payload' => $qrPayload,
                'booking_reference' => $bookingReference,
                'account_id' => $this->maskAccountId($this->config['account_id']),
            ]),
        ]);

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'qr_payload' => $qrPayload,
            'qr_image_url' => $qrImageUrl,
            'amount' => $amount,
            'currency' => 'THB',
            'account_name' => $this->config['merchant_name'],
            'account_id_masked' => $this->maskAccountId($this->config['account_id']),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'instructions' => [
                'th' => 'สแกน QR Code ด้วยแอปธนาคารของคุณ',
                'en' => 'Scan QR Code with your banking app',
                'ru' => 'Отсканируйте QR-код банковским приложением',
            ],
        ];
    }

    /**
     * Generate EMVCo QR Code Payload for PromptPay
     * Following Bank of Thailand specifications
     */
    private function generateEMVCoQRPayload(float $amount, string $reference = ''): string
    {
        $data = '';

        // Payload Format Indicator (ID: 00)
        $data .= $this->formatTag(self::TAG_PAYLOAD_FORMAT, '01');

        // Point of Initiation Method (ID: 01)
        // 11 = Static QR (reusable), 12 = Dynamic QR (one-time)
        $data .= $this->formatTag(self::TAG_POI_METHOD, $amount > 0 ? '12' : '11');

        // Merchant Account Information - PromptPay (ID: 29)
        $merchantInfo = $this->generateMerchantInfo();
        $data .= $this->formatTag(self::TAG_MERCHANT_INFO, $merchantInfo);

        // Transaction Currency (ID: 53)
        $data .= $this->formatTag(self::TAG_TRANSACTION_CURRENCY, self::CURRENCY_CODE_THB);

        // Transaction Amount (ID: 54) - only if amount > 0
        if ($amount > 0) {
            $formattedAmount = number_format($amount, 2, '.', '');
            $data .= $this->formatTag(self::TAG_TRANSACTION_AMOUNT, $formattedAmount);
        }

        // Country Code (ID: 58)
        $data .= $this->formatTag(self::TAG_COUNTRY_CODE, self::COUNTRY_CODE_TH);

        // Merchant Name (ID: 59)
        $merchantName = substr($this->config['merchant_name'], 0, 25);
        $data .= $this->formatTag(self::TAG_MERCHANT_NAME, $merchantName);

        // Merchant City (ID: 60)
        $merchantCity = substr($this->config['merchant_city'], 0, 15);
        $data .= $this->formatTag(self::TAG_MERCHANT_CITY, $merchantCity);

        // CRC (ID: 63) - calculated at the end
        $data .= '6304'; // CRC tag + length placeholder
        $crc = $this->calculateCRC16($data);
        $data = substr($data, 0, -4) . '6304' . strtoupper(dechex($crc));

        // Ensure CRC is 4 characters
        $crcHex = strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
        $data = substr($data, 0, -4) . $crcHex;

        return $data;
    }

    /**
     * Generate Merchant Account Information for PromptPay
     */
    private function generateMerchantInfo(): string
    {
        $info = '';

        // Application ID (Sub-tag 00)
        $info .= $this->formatTag('00', self::PROMPTPAY_AID);

        // Account Information (Sub-tag 01 for phone, 02 for National ID, 03 for E-Wallet)
        $accountId = $this->formatAccountId($this->config['account_id']);
        $subTag = match ($this->config['account_type']) {
            'phone' => '01',
            'national_id' => '02',
            'ewallet' => '03',
            default => '01',
        };
        $info .= $this->formatTag($subTag, $accountId);

        return $info;
    }

    /**
     * Format account ID based on type
     */
    private function formatAccountId(string $accountId): string
    {
        // Remove any non-digit characters
        $accountId = preg_replace('/\D/', '', $accountId);

        // For phone numbers, ensure it starts with country code
        if ($this->config['account_type'] === 'phone') {
            if (str_starts_with($accountId, '0')) {
                $accountId = '66' . substr($accountId, 1);
            } elseif (!str_starts_with($accountId, '66')) {
                $accountId = '66' . $accountId;
            }
        }

        return $accountId;
    }

    /**
     * Format EMVCo tag
     */
    private function formatTag(string $id, string $value): string
    {
        $length = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $length . $value;
    }

    /**
     * Calculate CRC16-CCITT checksum
     */
    private function calculateCRC16(string $data): int
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0; $i < strlen($data); $i++) {
            $byte = ord($data[$i]);
            for ($j = 0; $j < 8; $j++) {
                $bit = (($byte >> (7 - $j)) & 1) == 1;
                $c15 = (($crc >> 15) & 1) == 1;
                $crc <<= 1;
                if ($c15 ^ $bit) {
                    $crc ^= $polynomial;
                }
            }
        }

        return $crc & 0xFFFF;
    }

    /**
     * Generate QR code image URL
     */
    private function generateQRImageUrl(string $payload): string
    {
        // Use Google Charts API for QR generation
        $encodedPayload = urlencode($payload);
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$encodedPayload}&format=png";
    }

    /**
     * Mask account ID for display
     */
    private function maskAccountId(string $accountId): string
    {
        $len = strlen($accountId);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return substr($accountId, 0, 3) . str_repeat('*', $len - 6) . substr($accountId, -3);
    }

    /**
     * Manual payment confirmation (admin action)
     */
    public function confirmPayment(string $paymentId, string $transactionRef, int $adminId): array
    {
        $payment = $this->db->fetchOne(
            "SELECT p.*, b.booking_reference
             FROM payments p
             JOIN bookings b ON p.booking_id = b.id
             WHERE p.id = ? AND p.provider = 'promptpay'",
            [$paymentId]
        );

        if (!$payment) {
            return ['error' => 'Payment not found'];
        }

        if ($payment['status'] === 'completed') {
            return ['error' => 'Payment already confirmed'];
        }

        // Update payment status
        $this->db->execute(
            "UPDATE payments
             SET status = 'completed',
                 provider_payment_id = ?,
                 metadata = JSON_SET(COALESCE(metadata, '{}'), '$.confirmed_by', ?, '$.confirmed_at', ?),
                 updated_at = NOW()
             WHERE id = ?",
            [$transactionRef, $adminId, date('Y-m-d H:i:s'), $paymentId]
        );

        // Update booking status
        $this->db->execute(
            "UPDATE bookings
             SET status = 'paid',
                 payment_method = 'promptpay',
                 payment_transaction_id = ?,
                 paid_at = NOW(),
                 updated_at = NOW()
             WHERE booking_reference = ?",
            [$transactionRef, $payment['booking_reference']]
        );

        return [
            'success' => true,
            'booking_reference' => $payment['booking_reference'],
        ];
    }

    /**
     * Get pending PromptPay payments (for admin review)
     */
    public function getPendingPayments(): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, b.booking_reference, b.total_price_thb, u.first_name, u.last_name, u.phone
             FROM payments p
             JOIN bookings b ON p.booking_id = b.id
             LEFT JOIN users u ON b.user_id = u.id
             WHERE p.provider = 'promptpay' AND p.status = 'pending'
             ORDER BY p.created_at DESC"
        );
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
            'id' => 'promptpay',
            'name' => 'PromptPay',
            'name_th' => 'พร้อมเพย์',
            'type' => 'qr',
            'icon' => 'promptpay',
            'enabled' => $this->isEnabled(),
            'min_amount' => 1,
            'max_amount' => 2000000, // 2 million THB max per transaction
            'fee_percent' => 0,
            'currencies' => ['THB'],
            'description' => [
                'en' => 'Instant bank transfer via Thai QR code',
                'ru' => 'Мгновенный банковский перевод через тайский QR-код',
                'th' => 'โอนเงินทันทีผ่าน QR Code ธนาคาร',
            ],
        ];
    }
}
