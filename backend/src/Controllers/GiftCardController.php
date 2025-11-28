<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\GiftCardService;

class GiftCardController
{
    private GiftCardService $giftCardService;

    public function __construct(GiftCardService $giftCardService)
    {
        $this->giftCardService = $giftCardService;
    }

    /**
     * Get available gift card designs
     */
    public function designs(): Response
    {
        $designs = $this->giftCardService->getDesigns();

        return Response::json(['designs' => $designs]);
    }

    /**
     * Get available amounts
     */
    public function amounts(): Response
    {
        $amounts = $this->giftCardService->getAvailableAmounts();

        return Response::json(['amounts' => $amounts]);
    }

    /**
     * Purchase a gift card
     */
    public function purchase(Request $request): Response
    {
        $data = $request->json();
        $userId = $request->getUserId();

        // Validate amount
        $amount = (float) ($data['amount_thb'] ?? 0);
        if ($amount < 1000 || $amount > 500000) {
            return Response::json(['error' => 'Amount must be between ฿1,000 and ฿500,000'], 400);
        }

        // Validate recipient email if delivery method is email
        if (($data['delivery_method'] ?? 'email') === 'email' && empty($data['recipient_email'])) {
            return Response::json(['error' => 'Recipient email is required for email delivery'], 400);
        }

        $purchaseData = [
            'amount_thb' => $amount,
            'purchaser_user_id' => $userId,
            'purchaser_name' => $data['purchaser_name'] ?? null,
            'purchaser_email' => $data['purchaser_email'] ?? null,
            'purchaser_phone' => $data['purchaser_phone'] ?? null,
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_email' => $data['recipient_email'] ?? null,
            'recipient_phone' => $data['recipient_phone'] ?? null,
            'personal_message' => $data['personal_message'] ?? null,
            'delivery_method' => $data['delivery_method'] ?? 'email',
            'design_template' => $data['design_template'] ?? 'classic',
            'valid_months' => $data['valid_months'] ?? 12,
            'applies_to' => $data['applies_to'] ?? 'all'
        ];

        $giftCard = $this->giftCardService->purchase($purchaseData);

        return Response::json([
            'success' => true,
            'gift_card' => $giftCard,
            'message' => 'Gift card purchased successfully'
        ]);
    }

    /**
     * Validate gift card code
     */
    public function validate(Request $request): Response
    {
        $code = $request->get('code');
        $orderAmount = (float) $request->get('order_amount', 0);
        $appliesTo = $request->get('applies_to', 'all');

        if (!$code) {
            return Response::json(['error' => 'Gift card code is required'], 400);
        }

        $result = $this->giftCardService->validate($code, $orderAmount, $appliesTo);

        return Response::json($result);
    }

    /**
     * Get gift card by code
     */
    public function check(Request $request): Response
    {
        $code = $request->get('code');

        if (!$code) {
            return Response::json(['error' => 'Gift card code is required'], 400);
        }

        $giftCard = $this->giftCardService->getByCode($code);

        if (!$giftCard) {
            return Response::json(['error' => 'Gift card not found'], 404);
        }

        return Response::json(['gift_card' => $giftCard]);
    }

    /**
     * Get user's gift cards
     */
    public function userCards(Request $request): Response
    {
        $userId = $request->getUserId();

        if (!$userId) {
            return Response::json(['error' => 'Authentication required'], 401);
        }

        $giftCards = $this->giftCardService->getUserGiftCards($userId);

        return Response::json(['gift_cards' => $giftCards]);
    }

    /**
     * Get gift card transactions
     */
    public function transactions(Request $request): Response
    {
        $giftCardId = (int) $request->get('gift_card_id');

        if (!$giftCardId) {
            return Response::json(['error' => 'gift_card_id is required'], 400);
        }

        $transactions = $this->giftCardService->getTransactions($giftCardId);

        return Response::json(['transactions' => $transactions]);
    }
}
