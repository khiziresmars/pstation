<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

/**
 * Payment Service
 * Handles Telegram Stars and other payment methods
 */
class PaymentService
{
    private string $botToken;
    private float $starsRateTHB;
    private BookingService $bookingService;

    public function __construct()
    {
        $app = Application::getInstance();
        $this->botToken = $app->getConfig('telegram.bot_token');
        $this->starsRateTHB = $app->getConfig('telegram.stars_rate_thb', 0.013);
        $this->bookingService = new BookingService();
    }

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
                    'label' => $booking['item_name'],
                    'amount' => $starsAmount,
                ]
            ],
        ];

        $result = $this->callTelegramAPI('sendInvoice', $invoiceData);

        if (!$result['ok']) {
            return ['error' => $result['description'] ?? 'Failed to create invoice'];
        }

        return [
            'success' => true,
            'invoice_id' => $result['result']['message_id'] ?? null,
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

        // Update booking
        $booking = $this->bookingService->getByReference($bookingReference);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        // Update with payment info
        $db = Application::getInstance()->getDatabase();
        $db->update('bookings', [
            'status' => 'paid',
            'payment_method' => 'telegram_stars',
            'telegram_payment_charge_id' => $chargeId,
            'currency_paid' => 'XTR',
            'amount_paid' => $payment['total_amount'] ?? null,
            'amount_paid_original' => $payment['total_amount'] ?? null,
            'paid_at' => date('Y-m-d H:i:s'),
        ], 'booking_reference = ?', [$bookingReference]);

        // Credit cashback
        $this->bookingService->updateStatus($bookingReference, 'paid', 'telegram_stars');

        return [
            'success' => true,
            'booking_reference' => $bookingReference,
        ];
    }

    /**
     * Convert THB to Telegram Stars
     */
    public function convertTHBToStars(float $thbAmount): int
    {
        // 1 Star ≈ 0.013 USD ≈ 0.46 THB (approximate)
        // Adjust based on actual Telegram Stars rate
        $starsPerTHB = 1 / 0.46; // Approximately 2.17 stars per THB
        return (int) ceil($thbAmount * $starsPerTHB);
    }

    /**
     * Convert Stars to THB
     */
    public function convertStarsToTHB(int $stars): float
    {
        $thbPerStar = 0.46; // Approximately
        return round($stars * $thbPerStar, 2);
    }

    /**
     * Get invoice description
     */
    private function getInvoiceDescription(array $booking): string
    {
        $type = $booking['bookable_type'] === 'vessel' ? 'Yacht Rental' : 'Tour';
        $date = date('M d, Y', strtotime($booking['booking_date']));

        $description = "{$type}: {$booking['item_name']}\n";
        $description .= "Date: {$date}\n";

        if ($booking['bookable_type'] === 'vessel' && $booking['duration_hours']) {
            $description .= "Duration: {$booking['duration_hours']} hours\n";
        }

        if ($booking['adults_count']) {
            $description .= "Guests: {$booking['adults_count']} adults";
            if ($booking['children_count']) {
                $description .= ", {$booking['children_count']} children";
            }
            $description .= "\n";
        }

        $description .= "Total: ฿" . number_format($booking['total_price_thb'], 0);

        return $description;
    }

    /**
     * Call Telegram Bot API
     */
    private function callTelegramAPI(string $method, array $data): array
    {
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
