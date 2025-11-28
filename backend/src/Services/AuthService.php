<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Authentication Service
 * Handles Telegram, Email/Password, and Google OAuth authentication
 */
class AuthService
{
    private Database $db;
    private string $jwtSecret;
    private int $jwtExpiry = 86400 * 30; // 30 days

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'phuket-yacht-secret-key-2024';
    }

    // ==========================================
    // TELEGRAM AUTHENTICATION
    // ==========================================

    /**
     * Authenticate via Telegram init data
     */
    public function authenticateTelegram(string $initData): array
    {
        // Validate Telegram init data
        $data = $this->validateTelegramData($initData);

        if (!$data) {
            return ['error' => 'Invalid Telegram data'];
        }

        $telegramId = $data['id'];

        // Find or create user
        $user = $this->findOrCreateTelegramUser($data);

        // Generate JWT token
        $token = $this->generateToken($user);

        return [
            'success' => true,
            'user' => $this->sanitizeUser($user),
            'token' => $token,
            'auth_method' => 'telegram',
        ];
    }

    private function validateTelegramData(string $initData): ?array
    {
        $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';

        // Parse init data
        parse_str($initData, $parsed);

        if (!isset($parsed['hash'])) {
            return null;
        }

        $hash = $parsed['hash'];
        unset($parsed['hash']);

        // Sort and create data check string
        ksort($parsed);
        $dataCheckString = urldecode(http_build_query($parsed, '', "\n"));

        // Verify signature
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $hash)) {
            // For development, allow without verification
            if ($_ENV['APP_ENV'] === 'development') {
                // Continue without strict verification
            } else {
                return null;
            }
        }

        // Check auth date (not older than 24 hours)
        if (isset($parsed['auth_date'])) {
            $authTime = (int)$parsed['auth_date'];
            if (time() - $authTime > 86400) {
                return null;
            }
        }

        // Parse user data
        if (isset($parsed['user'])) {
            return json_decode($parsed['user'], true);
        }

        return null;
    }

    private function findOrCreateTelegramUser(array $telegramData): array
    {
        $telegramId = $telegramData['id'];

        // Check existing user
        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE telegram_id = ?",
            [$telegramId]
        );

        if ($user) {
            // Update user info
            $this->db->query(
                "UPDATE users SET
                    username = COALESCE(?, username),
                    first_name = COALESCE(?, first_name),
                    last_name = COALESCE(?, last_name),
                    photo_url = COALESCE(?, photo_url),
                    language_code = COALESCE(?, language_code),
                    last_login_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?",
                [
                    $telegramData['username'] ?? null,
                    $telegramData['first_name'] ?? null,
                    $telegramData['last_name'] ?? null,
                    $telegramData['photo_url'] ?? null,
                    $telegramData['language_code'] ?? null,
                    $user['id']
                ]
            );

            return $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
        }

        // Create new user
        $this->db->query(
            "INSERT INTO users (
                telegram_id, username, first_name, last_name,
                photo_url, language_code, auth_method,
                created_at, updated_at, last_login_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'telegram', NOW(), NOW(), NOW())",
            [
                $telegramId,
                $telegramData['username'] ?? null,
                $telegramData['first_name'] ?? null,
                $telegramData['last_name'] ?? null,
                $telegramData['photo_url'] ?? null,
                $telegramData['language_code'] ?? 'en'
            ]
        );

        $userId = $this->db->lastInsertId();
        return $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    // ==========================================
    // EMAIL AUTHENTICATION
    // ==========================================

    /**
     * Register with email
     */
    public function registerEmail(string $email, string $password, array $userData = []): array
    {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid email address'];
        }

        // Check if email exists
        $existing = $this->db->queryOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );

        if ($existing) {
            return ['error' => 'Email already registered'];
        }

        // Validate password
        if (strlen($password) < 6) {
            return ['error' => 'Password must be at least 6 characters'];
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Create user
        $this->db->query(
            "INSERT INTO users (
                email, password_hash, first_name, last_name,
                auth_method, email_verified,
                created_at, updated_at, last_login_at
            ) VALUES (?, ?, ?, ?, 'email', 0, NOW(), NOW(), NOW())",
            [
                $email,
                $passwordHash,
                $userData['first_name'] ?? null,
                $userData['last_name'] ?? null
            ]
        );

        $userId = $this->db->lastInsertId();
        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);

        // Generate verification token
        $verificationToken = $this->generateVerificationToken($userId);

        // TODO: Send verification email
        // $this->sendVerificationEmail($email, $verificationToken);

        $token = $this->generateToken($user);

        return [
            'success' => true,
            'user' => $this->sanitizeUser($user),
            'token' => $token,
            'auth_method' => 'email',
            'requires_verification' => true,
        ];
    }

    /**
     * Login with email
     */
    public function loginEmail(string $email, string $password): array
    {
        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return ['error' => 'Invalid email or password'];
        }

        if (!$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
            return ['error' => 'Invalid email or password'];
        }

        // Update last login
        $this->db->query(
            "UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$user['id']]
        );

        $token = $this->generateToken($user);

        return [
            'success' => true,
            'user' => $this->sanitizeUser($user),
            'token' => $token,
            'auth_method' => 'email',
        ];
    }

    /**
     * Verify email
     */
    public function verifyEmail(string $token): array
    {
        // Find token
        $record = $this->db->queryOne(
            "SELECT * FROM email_verifications WHERE token = ? AND expires_at > NOW()",
            [$token]
        );

        if (!$record) {
            return ['error' => 'Invalid or expired verification token'];
        }

        // Update user
        $this->db->query(
            "UPDATE users SET email_verified = 1, updated_at = NOW() WHERE id = ?",
            [$record['user_id']]
        );

        // Delete token
        $this->db->query(
            "DELETE FROM email_verifications WHERE id = ?",
            [$record['id']]
        );

        return ['success' => true, 'message' => 'Email verified successfully'];
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): array
    {
        $user = $this->db->queryOne("SELECT id FROM users WHERE email = ?", [$email]);

        if (!$user) {
            // Don't reveal if email exists
            return ['success' => true, 'message' => 'If email exists, reset link will be sent'];
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->db->query(
            "INSERT INTO password_resets (user_id, token, expires_at, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE token = ?, expires_at = ?, created_at = NOW()",
            [$user['id'], $token, $expiresAt, $token, $expiresAt]
        );

        // TODO: Send reset email
        // $this->sendPasswordResetEmail($email, $token);

        return ['success' => true, 'message' => 'If email exists, reset link will be sent'];
    }

    /**
     * Reset password
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $record = $this->db->queryOne(
            "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()",
            [$token]
        );

        if (!$record) {
            return ['error' => 'Invalid or expired reset token'];
        }

        if (strlen($newPassword) < 6) {
            return ['error' => 'Password must be at least 6 characters'];
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

        $this->db->query(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$passwordHash, $record['user_id']]
        );

        $this->db->query("DELETE FROM password_resets WHERE user_id = ?", [$record['user_id']]);

        return ['success' => true, 'message' => 'Password reset successfully'];
    }

    // ==========================================
    // GOOGLE OAUTH
    // ==========================================

    /**
     * Get Google OAuth URL
     */
    public function getGoogleAuthUrl(string $redirectUri): string
    {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $scope = urlencode('email profile');
        $state = bin2hex(random_bytes(16));

        // Store state for verification
        $_SESSION['google_oauth_state'] = $state;

        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);
    }

    /**
     * Authenticate with Google OAuth code
     */
    public function authenticateGoogle(string $code, string $redirectUri): array
    {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';

        // Exchange code for tokens
        $tokenResponse = $this->httpPost('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($tokenResponse['access_token'])) {
            return ['error' => 'Failed to get access token'];
        }

        // Get user info
        $userInfo = $this->httpGet(
            'https://www.googleapis.com/oauth2/v2/userinfo',
            $tokenResponse['access_token']
        );

        if (!isset($userInfo['id'])) {
            return ['error' => 'Failed to get user info'];
        }

        // Find or create user
        $user = $this->findOrCreateGoogleUser($userInfo, $tokenResponse);
        $token = $this->generateToken($user);

        return [
            'success' => true,
            'user' => $this->sanitizeUser($user),
            'token' => $token,
            'auth_method' => 'google',
        ];
    }

    private function findOrCreateGoogleUser(array $googleData, array $tokens): array
    {
        $googleId = $googleData['id'];
        $email = $googleData['email'] ?? null;

        // Check by Google ID first
        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE google_id = ?",
            [$googleId]
        );

        if (!$user && $email) {
            // Check by email
            $user = $this->db->queryOne(
                "SELECT * FROM users WHERE email = ?",
                [$email]
            );
        }

        if ($user) {
            // Update user
            $this->db->query(
                "UPDATE users SET
                    google_id = ?,
                    email = COALESCE(?, email),
                    first_name = COALESCE(?, first_name),
                    last_name = COALESCE(?, last_name),
                    photo_url = COALESCE(?, photo_url),
                    email_verified = 1,
                    google_access_token = ?,
                    google_refresh_token = COALESCE(?, google_refresh_token),
                    last_login_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?",
                [
                    $googleId,
                    $email,
                    $googleData['given_name'] ?? null,
                    $googleData['family_name'] ?? null,
                    $googleData['picture'] ?? null,
                    $tokens['access_token'],
                    $tokens['refresh_token'] ?? null,
                    $user['id']
                ]
            );

            return $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
        }

        // Create new user
        $this->db->query(
            "INSERT INTO users (
                google_id, email, first_name, last_name, photo_url,
                email_verified, auth_method, google_access_token, google_refresh_token,
                created_at, updated_at, last_login_at
            ) VALUES (?, ?, ?, ?, ?, 1, 'google', ?, ?, NOW(), NOW(), NOW())",
            [
                $googleId,
                $email,
                $googleData['given_name'] ?? null,
                $googleData['family_name'] ?? null,
                $googleData['picture'] ?? null,
                $tokens['access_token'],
                $tokens['refresh_token'] ?? null
            ]
        );

        $userId = $this->db->lastInsertId();
        return $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    // ==========================================
    // ADMIN AUTHENTICATION
    // ==========================================

    /**
     * Admin login (email/password only)
     */
    public function adminLogin(string $email, string $password): array
    {
        // Check test admin credentials
        if ($email === 'admin@admin.com' && $password === 'admin') {
            // Create or get test admin
            $admin = $this->db->queryOne("SELECT * FROM admins WHERE email = ?", [$email]);

            if (!$admin) {
                $this->db->query(
                    "INSERT INTO admins (email, password_hash, name, role, created_at, updated_at)
                     VALUES (?, ?, 'Admin', 'super_admin', NOW(), NOW())",
                    [$email, password_hash($password, PASSWORD_BCRYPT)]
                );
                $admin = $this->db->queryOne("SELECT * FROM admins WHERE email = ?", [$email]);
            }

            $token = $this->generateAdminToken($admin);

            return [
                'success' => true,
                'admin' => [
                    'id' => $admin['id'],
                    'email' => $admin['email'],
                    'name' => $admin['name'],
                    'role' => $admin['role'],
                ],
                'token' => $token,
            ];
        }

        // Regular admin login
        $admin = $this->db->queryOne(
            "SELECT * FROM admins WHERE email = ? AND is_active = 1",
            [$email]
        );

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return ['error' => 'Invalid credentials'];
        }

        // Update last login
        $this->db->query(
            "UPDATE admins SET last_login_at = NOW() WHERE id = ?",
            [$admin['id']]
        );

        $token = $this->generateAdminToken($admin);

        return [
            'success' => true,
            'admin' => [
                'id' => $admin['id'],
                'email' => $admin['email'],
                'name' => $admin['name'],
                'role' => $admin['role'],
            ],
            'token' => $token,
        ];
    }

    // ==========================================
    // TOKEN MANAGEMENT
    // ==========================================

    private function generateToken(array $user): string
    {
        $payload = [
            'user_id' => $user['id'],
            'telegram_id' => $user['telegram_id'] ?? null,
            'email' => $user['email'] ?? null,
            'type' => 'user',
            'iat' => time(),
            'exp' => time() + $this->jwtExpiry,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    private function generateAdminToken(array $admin): string
    {
        $payload = [
            'admin_id' => $admin['id'],
            'email' => $admin['email'],
            'role' => $admin['role'],
            'type' => 'admin',
            'iat' => time(),
            'exp' => time() + $this->jwtExpiry,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateVerificationToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->db->query(
            "INSERT INTO email_verifications (user_id, token, expires_at, created_at)
             VALUES (?, ?, ?, NOW())",
            [$userId, $token, $expiresAt]
        );

        return $token;
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function sanitizeUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'telegram_id' => $user['telegram_id'] ?? null,
            'google_id' => $user['google_id'] ?? null,
            'email' => $user['email'] ?? null,
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'photo_url' => $user['photo_url'] ?? null,
            'language_code' => $user['language_code'] ?? 'en',
            'phone' => $user['phone'] ?? null,
            'whatsapp' => $user['whatsapp'] ?? null,
            'cashback_balance' => (float)($user['cashback_balance_thb'] ?? 0),
            'email_verified' => (bool)($user['email_verified'] ?? false),
            'auth_method' => $user['auth_method'] ?? 'telegram',
        ];
    }

    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    private function httpGet(string $url, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
