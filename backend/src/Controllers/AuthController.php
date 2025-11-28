<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Services\TelegramAuthService;
use App\Services\AuthService;
use App\Services\UserService;

/**
 * Auth Controller
 * Handles all authentication endpoints (Telegram, Email, Google)
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    // ==========================================
    // TELEGRAM AUTH
    // ==========================================

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

        $result = $this->authService->authenticateTelegram($initData);

        if (isset($result['error'])) {
            Response::unauthorized($result['error']);
            return;
        }

        Response::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'auth_method' => 'telegram',
        ], 'Authentication successful');
    }

    // ==========================================
    // EMAIL AUTH
    // ==========================================

    /**
     * POST /api/auth/register
     * Register with email
     */
    public function register(): void
    {
        $data = Request::json();

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $phone = $data['phone'] ?? null;
        $whatsapp = $data['whatsapp'] ?? null;

        if (!$email || !$password) {
            Response::error('Email and password are required', 400);
            return;
        }

        $result = $this->authService->registerEmail($email, $password, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
        ]);

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'auth_method' => 'email',
            'requires_verification' => $result['requires_verification'] ?? false,
        ], 'Registration successful');
    }

    /**
     * POST /api/auth/login
     * Login with email
     */
    public function login(): void
    {
        $data = Request::json();

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            Response::error('Email and password are required', 400);
            return;
        }

        $result = $this->authService->loginEmail($email, $password);

        if (isset($result['error'])) {
            Response::unauthorized($result['error']);
            return;
        }

        Response::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'auth_method' => 'email',
        ], 'Login successful');
    }

    /**
     * POST /api/auth/verify-email
     * Verify email address
     */
    public function verifyEmail(): void
    {
        $data = Request::json();
        $token = $data['token'] ?? '';

        if (!$token) {
            Response::error('Token is required', 400);
            return;
        }

        $result = $this->authService->verifyEmail($token);

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success($result, 'Email verified successfully');
    }

    /**
     * POST /api/auth/forgot-password
     * Request password reset
     */
    public function forgotPassword(): void
    {
        $data = Request::json();
        $email = $data['email'] ?? '';

        if (!$email) {
            Response::error('Email is required', 400);
            return;
        }

        $result = $this->authService->requestPasswordReset($email);

        Response::success($result, $result['message']);
    }

    /**
     * POST /api/auth/reset-password
     * Reset password
     */
    public function resetPassword(): void
    {
        $data = Request::json();
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        if (!$token || !$password) {
            Response::error('Token and password are required', 400);
            return;
        }

        $result = $this->authService->resetPassword($token, $password);

        if (isset($result['error'])) {
            Response::error($result['error'], 400);
            return;
        }

        Response::success($result, 'Password reset successful');
    }

    // ==========================================
    // GOOGLE AUTH
    // ==========================================

    /**
     * GET /api/auth/google
     * Get Google OAuth URL
     */
    public function googleUrl(): void
    {
        $redirectUri = $_GET['redirect_uri'] ?? $_ENV['GOOGLE_REDIRECT_URI'] ?? '';

        if (!$redirectUri) {
            Response::error('redirect_uri is required', 400);
            return;
        }

        $url = $this->authService->getGoogleAuthUrl($redirectUri);

        Response::success(['url' => $url]);
    }

    /**
     * POST /api/auth/google/callback
     * Google OAuth callback
     */
    public function googleCallback(): void
    {
        $data = Request::json();
        $code = $data['code'] ?? '';
        $redirectUri = $data['redirect_uri'] ?? $_ENV['GOOGLE_REDIRECT_URI'] ?? '';

        if (!$code) {
            Response::error('Authorization code is required', 400);
            return;
        }

        $result = $this->authService->authenticateGoogle($code, $redirectUri);

        if (isset($result['error'])) {
            Response::unauthorized($result['error']);
            return;
        }

        Response::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'auth_method' => 'google',
        ], 'Authentication successful');
    }

    // ==========================================
    // ADMIN AUTH
    // ==========================================

    /**
     * POST /api/admin/auth/login
     * Admin login
     */
    public function adminLogin(): void
    {
        $data = Request::json();

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            Response::error('Email and password are required', 400);
            return;
        }

        $result = $this->authService->adminLogin($email, $password);

        if (isset($result['error'])) {
            Response::unauthorized($result['error']);
            return;
        }

        Response::success([
            'admin' => $result['admin'],
            'token' => $result['token'],
        ], 'Login successful');
    }

    // ==========================================
    // USER PROFILE
    // ==========================================

    /**
     * GET /api/auth/me
     * Get current user
     */
    public function me(): void
    {
        $userId = Request::getUserId();

        if (!$userId) {
            Response::unauthorized('Not authenticated');
            return;
        }

        $userService = new UserService();
        $user = $userService->getById($userId);

        if (!$user) {
            Response::unauthorized('User not found');
            return;
        }

        Response::success(['user' => [
            'id' => $user['id'],
            'telegram_id' => $user['telegram_id'] ?? null,
            'email' => $user['email'] ?? null,
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'photo_url' => $user['photo_url'] ?? null,
            'phone' => $user['phone'] ?? null,
            'whatsapp' => $user['whatsapp'] ?? null,
            'language_code' => $user['language_code'] ?? 'en',
            'cashback_balance' => (float)($user['cashback_balance_thb'] ?? 0),
            'email_verified' => (bool)($user['email_verified'] ?? false),
            'auth_method' => $user['auth_method'] ?? 'telegram',
        ]]);
    }

    /**
     * Generate a session token (legacy support)
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

        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$signature}";
    }
}
