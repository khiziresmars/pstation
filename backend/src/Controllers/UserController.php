<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Services\UserService;
use App\Services\ExchangeRateService;

/**
 * User Controller
 * Handles user profile and related endpoints
 */
class UserController
{
    private UserService $userService;
    private ExchangeRateService $exchangeService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->exchangeService = new ExchangeRateService();
    }

    /**
     * GET /api/user/profile
     * Get current user profile
     */
    public function profile(): void
    {
        $user = AuthMiddleware::user();

        if (!$user) {
            Response::unauthorized();
            return;
        }

        // Format cashback in user's preferred currency
        $cashbackFormatted = $this->exchangeService->formatPrice(
            (float) $user['cashback_balance'],
            $user['preferred_currency']
        );

        $user['cashback_formatted'] = $cashbackFormatted;

        // Get referral link
        $botUsername = $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'bot';
        $user['referral_link'] = "https://t.me/{$botUsername}?start=ref_{$user['referral_code']}";

        // Remove sensitive fields
        unset($user['id']);

        Response::success($user);
    }

    /**
     * PUT /api/user/profile
     * Update user profile
     */
    public function update(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $data = Request::json();

        // Validate
        $errors = Request::validate([
            'phone' => 'string|max:20',
            'email' => 'email|max:255',
            'language_code' => 'string|in:en,ru,th',
            'preferred_currency' => 'string|in:THB,USD,EUR,RUB',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $this->userService->updateProfile($userId, $data);

        Response::success(null, 'Profile updated successfully');
    }

    /**
     * GET /api/user/bookings
     * Get user booking history
     */
    public function bookings(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(50, max(1, (int) Request::query('per_page', 10)));

        $result = $this->userService->getBookings($userId, $page, $perPage);

        Response::paginated($result['items'], $result['total'], $page, $perPage);
    }

    /**
     * GET /api/user/favorites
     * Get user favorites
     */
    public function favorites(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $favorites = $this->userService->getFavorites($userId);

        Response::success($favorites);
    }

    /**
     * POST /api/user/favorites
     * Add to favorites
     */
    public function addFavorite(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $data = Request::json();

        $errors = Request::validate([
            'type' => 'required|in:vessel,tour',
            'id' => 'required|integer',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $this->userService->addFavorite($userId, $data['type'], (int) $data['id']);

        Response::success(null, 'Added to favorites');
    }

    /**
     * DELETE /api/user/favorites/{type}/{id}
     * Remove from favorites
     */
    public function removeFavorite(array $params): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $type = $params['type'] ?? '';
        $id = (int) ($params['id'] ?? 0);

        if (!in_array($type, ['vessel', 'tour']) || $id <= 0) {
            Response::error('Invalid parameters', 400);
            return;
        }

        $this->userService->removeFavorite($userId, $type, $id);

        Response::success(null, 'Removed from favorites');
    }

    /**
     * GET /api/user/cashback
     * Get cashback transaction history
     */
    public function cashbackHistory(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $history = $this->userService->getCashbackHistory($userId);

        Response::success($history);
    }

    /**
     * GET /api/user/referrals
     * Get referral statistics
     */
    public function referrals(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $stats = $this->userService->getReferralStats($userId);

        Response::success($stats);
    }

    /**
     * GET /api/user/notifications
     * Get user notifications
     */
    public function notifications(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $db = \App\Core\Application::getInstance()->getDatabase();

        $notifications = $db->query(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [$userId]
        );

        foreach ($notifications as &$notification) {
            $notification['data'] = json_decode($notification['data'], true);
        }

        Response::success($notifications);
    }

    /**
     * PUT /api/user/notifications/{id}/read
     * Mark notification as read
     */
    public function markNotificationRead(array $params): void
    {
        $userId = AuthMiddleware::userId();
        $notificationId = (int) ($params['id'] ?? 0);

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $db = \App\Core\Application::getInstance()->getDatabase();

        $db->update('notifications', [
            'is_read' => true,
            'read_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND user_id = ?', [$notificationId, $userId]);

        Response::success(null, 'Notification marked as read');
    }
}
