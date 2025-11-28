<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\LoyaltyService;

class LoyaltyController
{
    private LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Get user's loyalty status
     */
    public function status(Request $request): Response
    {
        $userId = $request->getUserId();
        $lang = $request->get('lang', 'en');

        if (!$userId) {
            return Response::json(['error' => 'Authentication required'], 401);
        }

        $status = $this->loyaltyService->getUserTier($userId, $lang);

        return Response::json($status);
    }

    /**
     * Get all loyalty tiers
     */
    public function tiers(Request $request): Response
    {
        $lang = $request->get('lang', 'en');

        $tiers = $this->loyaltyService->getTiers($lang);

        return Response::json(['tiers' => $tiers]);
    }
}
