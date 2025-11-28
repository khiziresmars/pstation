<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Services\TelegramAuthService;
use App\Services\UserService;

/**
 * Auth Controller
 * Handles authentication endpoints
 */
class AuthController
{
    /**
     * POST /api/auth/telegram
     * Authenticate with Telegram initData
     */
    public function telegramAuth(): void
    {
        $data = Request::json();
        $initData = $data['init_data'] ?? '';

        if (empty($initData)) {
            Response::error('Missing init_data', 400);
            return;
        }

        $app = Application::getInstance();
        $botToken = $app->getConfig('telegram.bot_token');

        $telegramAuth = new TelegramAuthService($botToken);
        $userData = $telegramAuth->validateInitData($initData);

        if (!$userData) {
            Response::unauthorized('Invalid Telegram authentication');
            return;
        }

        // Get or create user
        $userService = new UserService();
        $user = $userService->getOrCreateFromTelegram($userData);

        if (!$user) {
            Response::error('Failed to authenticate user', 500);
            return;
        }

        // Generate session token (for web usage)
        $token = $this->generateSessionToken($user['id']);

        Response::success([
            'user' => [
                'telegram_id' => $user['telegram_id'],
                'username' => $user['username'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'language_code' => $user['language_code'],
                'photo_url' => $user['photo_url'],
                'cashback_balance' => (float) $user['cashback_balance'],
                'preferred_currency' => $user['preferred_currency'],
                'referral_code' => $user['referral_code'],
            ],
            'token' => $token,
        ], 'Authentication successful');
    }

    /**
     * Generate a session token
     */
    private function generateSessionToken(int $userId): string
    {
        $app = Application::getInstance();
        $secret = $app->getConfig('jwt.secret');
        $expiry = $app->getConfig('jwt.expiry', 86400);

        $payload = [
            'user_id' => $userId,
            'exp' => time() + $expiry,
            'iat' => time(),
        ];

        // Simple HMAC-based token (for production, use proper JWT library)
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$signature}";
    }
}
