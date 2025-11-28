<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * Exchange Rate Service
 * Handles currency conversion and rate updates
 */
class ExchangeRateService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Get all exchange rates
     */
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT currency_code, currency_name, currency_symbol, rate_to_thb, rate_from_thb, last_updated_at
             FROM exchange_rates
             WHERE is_active = 1
             ORDER BY currency_code"
        );
    }

    /**
     * Get rate for a specific currency
     */
    public function getRate(string $currencyCode): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM exchange_rates WHERE currency_code = ? AND is_active = 1",
            [strtoupper($currencyCode)]
        );
    }

    /**
     * Convert THB to another currency
     */
    public function convertFromTHB(float $amountTHB, string $toCurrency): ?float
    {
        $rate = $this->getRate($toCurrency);

        if (!$rate) {
            return null;
        }

        return round($amountTHB * (float) $rate['rate_from_thb'], 2);
    }

    /**
     * Convert to THB from another currency
     */
    public function convertToTHB(float $amount, string $fromCurrency): ?float
    {
        $rate = $this->getRate($fromCurrency);

        if (!$rate) {
            return null;
        }

        return round($amount * (float) $rate['rate_to_thb'], 2);
    }

    /**
     * Convert amount with formatted output
     */
    public function formatPrice(float $amountTHB, string $currency): array
    {
        if ($currency === 'THB') {
            return [
                'amount' => $amountTHB,
                'formatted' => '฿' . number_format($amountTHB, 0),
                'currency' => 'THB',
            ];
        }

        $rate = $this->getRate($currency);

        if (!$rate) {
            return [
                'amount' => $amountTHB,
                'formatted' => '฿' . number_format($amountTHB, 0),
                'currency' => 'THB',
            ];
        }

        $converted = $amountTHB * (float) $rate['rate_from_thb'];

        return [
            'amount' => round($converted, 2),
            'formatted' => $rate['currency_symbol'] . number_format($converted, 2),
            'currency' => $currency,
            'original_thb' => $amountTHB,
        ];
    }

    /**
     * Update exchange rates from external API
     */
    public function updateRates(): array
    {
        $apiKey = $_ENV['EXCHANGE_RATE_API_KEY'] ?? '';

        if (empty($apiKey)) {
            return ['error' => 'API key not configured'];
        }

        // Using exchangerate-api.com
        $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/THB";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'Failed to fetch rates: ' . $error];
        }

        $data = json_decode($response, true);

        if (!$data || $data['result'] !== 'success') {
            return ['error' => 'Invalid API response'];
        }

        $rates = $data['conversion_rates'] ?? [];
        $updated = [];

        foreach (['USD', 'EUR', 'RUB', 'GBP', 'CNY', 'AUD'] as $currency) {
            if (isset($rates[$currency])) {
                $rateFromTHB = $rates[$currency];
                $rateToTHB = 1 / $rateFromTHB;

                $this->db->update('exchange_rates', [
                    'rate_to_thb' => $rateToTHB,
                    'rate_from_thb' => $rateFromTHB,
                    'last_updated_at' => date('Y-m-d H:i:s'),
                ], 'currency_code = ?', [$currency]);

                $updated[] = $currency;
            }
        }

        return ['success' => true, 'updated' => $updated];
    }
}
