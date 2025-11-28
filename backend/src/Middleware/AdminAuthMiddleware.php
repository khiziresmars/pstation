<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Services\AdminService;
use App\Services\TelegramAuthService;

/**
 * Admin Authentication Middleware
 * Validates admin access via Telegram initData
 */
class AdminAuthMiddleware
{
    private static ?array $currentAdmin = null;

    public static function handle(): bool
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($authHeader, 'tma ')) {
            Response::error('Admin authentication required', 401, 'ADMIN_AUTH_REQUIRED');
            return false;
        }

        $initData = substr($authHeader, 4);
        $telegramAuth = new TelegramAuthService();
        $userData = $telegramAuth->validateInitData($initData);

        if (!$userData) {
            Response::error('Invalid admin credentials', 401, 'INVALID_ADMIN_CREDENTIALS');
            return false;
        }

        $adminService = new AdminService();
        $admin = $adminService->getByTelegramId((int) $userData['id']);

        if (!$admin) {
            Response::error('Admin access denied', 403, 'NOT_AN_ADMIN');
            return false;
        }

        if (!$admin['is_active']) {
            Response::error('Admin account is disabled', 403, 'ADMIN_DISABLED');
            return false;
        }

        self::$currentAdmin = $admin;
        $adminService->setCurrentAdmin($admin);
        $adminService->updateLastLogin($admin['id']);

        return true;
    }

    /**
     * Get current authenticated admin
     */
    public static function getAdmin(): ?array
    {
        return self::$currentAdmin;
    }

    /**
     * Check if admin has permission
     */
    public static function can(string $resource, string $action): bool
    {
        if (!self::$currentAdmin) {
            return false;
        }

        $adminService = new AdminService();
        $adminService->setCurrentAdmin(self::$currentAdmin);
        return $adminService->can($resource, $action);
    }

    /**
     * Require permission
     */
    public static function authorize(string $resource, string $action): bool
    {
        if (!self::can($resource, $action)) {
            Response::error(
                "You don't have permission to {$action} {$resource}",
                403,
                'PERMISSION_DENIED'
            );
            return false;
        }
        return true;
    }
}
