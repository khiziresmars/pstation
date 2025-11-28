<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Services\TelegramAuthService;

/**
 * Authentication Middleware
 * Validates Telegram initData and sets current user
 */
class AuthMiddleware
{
    private static ?array $currentUser = null;

    public function handle(): bool
    {
        $initData = Request::telegramInitData();

        if (!$initData) {
            Response::unauthorized('Missing authentication');
            return false;
        }

        $app = Application::getInstance();
        $botToken = $app->getConfig('telegram.bot_token');

        $telegramAuth = new TelegramAuthService($botToken);
        $userData = $telegramAuth->validateInitData($initData);

        if (!$userData) {
            Response::unauthorized('Invalid authentication');
            return false;
        }

        // Get or create user
        $userService = new \App\Services\UserService();
        $user = $userService->getOrCreateFromTelegram($userData);

        if (!$user) {
            Response::error('Failed to authenticate user', 500);
            return false;
        }

        self::$currentUser = $user;

        return true;
    }

    public static function user(): ?array
    {
        return self::$currentUser;
    }

    public static function userId(): ?int
    {
        return self::$currentUser['id'] ?? null;
    }

    public static function setUser(array $user): void
    {
        self::$currentUser = $user;
    }
}
