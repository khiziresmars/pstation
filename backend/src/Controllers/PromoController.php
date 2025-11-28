<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Services\PromoService;

/**
 * Promo Controller
 * Handles promo code endpoints
 */
class PromoController
{
    private PromoService $promoService;

    public function __construct()
    {
        $this->promoService = new PromoService();
    }

    /**
     * POST /api/promo/validate
     * Validate a promo code
     */
    public function validate(): void
    {
        $data = Request::json();

        $errors = Request::validate([
            'code' => 'required|string',
            'type' => 'required|in:vessel,tour',
            'item_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Get user ID if authenticated
        $userId = AuthMiddleware::userId() ?? 0;

        $result = $this->promoService->validate(
            $data['code'],
            $userId,
            $data['type'],
            (int) $data['item_id'],
            (float) $data['amount']
        );

        if (!$result['valid']) {
            Response::error($result['error'], 400, 'INVALID_PROMO');
            return;
        }

        Response::success([
            'code' => $result['code'],
            'type' => $result['type'],
            'value' => $result['value'],
            'discount' => $result['discount'],
            'description' => $result['description'],
        ]);
    }

    /**
     * GET /api/promo/available
     * Get available public promo codes
     */
    public function available(): void
    {
        $codes = $this->promoService->getPublicCodes();

        Response::success($codes);
    }
}
