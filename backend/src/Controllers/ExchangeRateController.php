<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ExchangeRateService;

/**
 * Exchange Rate Controller
 * Handles currency exchange endpoints
 */
class ExchangeRateController
{
    private ExchangeRateService $exchangeService;

    public function __construct()
    {
        $this->exchangeService = new ExchangeRateService();
    }

    /**
     * GET /api/exchange-rates
     * Get all exchange rates
     */
    public function index(): void
    {
        $rates = $this->exchangeService->getAll();

        Response::success($rates);
    }

    /**
     * GET /api/exchange-rates/convert
     * Convert amount between currencies
     */
    public function convert(): void
    {
        $amount = (float) Request::query('amount', 0);
        $from = Request::query('from', 'THB');
        $to = Request::query('to', 'USD');

        if ($amount <= 0) {
            Response::error('Invalid amount', 400);
            return;
        }

        // Convert to THB first if not already
        if (strtoupper($from) !== 'THB') {
            $amountTHB = $this->exchangeService->convertToTHB($amount, $from);
            if ($amountTHB === null) {
                Response::error('Invalid source currency', 400);
                return;
            }
        } else {
            $amountTHB = $amount;
        }

        // Convert from THB to target currency
        if (strtoupper($to) !== 'THB') {
            $result = $this->exchangeService->convertFromTHB($amountTHB, $to);
            if ($result === null) {
                Response::error('Invalid target currency', 400);
                return;
            }
        } else {
            $result = $amountTHB;
        }

        $formatted = $this->exchangeService->formatPrice($amountTHB, $to);

        Response::success([
            'original_amount' => $amount,
            'original_currency' => $from,
            'converted_amount' => $result,
            'target_currency' => $to,
            'formatted' => $formatted['formatted'],
        ]);
    }
}
