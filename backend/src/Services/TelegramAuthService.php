<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Telegram Authentication Service
 * Validates initData from Telegram Web App
 */
class TelegramAuthService
{
    private string $botToken;
    private int $maxAge = 86400; // 24 hours

    public function __construct(string $botToken)
    {
        $this->botToken = $botToken;
    }

    /**
     * Validate Telegram initData
     * @see https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
     */
    public function validateInitData(string $initData): ?array
    {
        // Parse the init data
        parse_str($initData, $data);

        if (!isset($data['hash'])) {
            return null;
        }

        $hash = $data['hash'];
        unset($data['hash']);

        // Check auth_date
        if (isset($data['auth_date'])) {
            $authDate = (int) $data['auth_date'];
            if (time() - $authDate > $this->maxAge) {
                return null; // Data is too old
            }
        }

        // Sort data alphabetically
        ksort($data);

        // Create data check string
        $dataCheckString = [];
        foreach ($data as $key => $value) {
            $dataCheckString[] = "{$key}={$value}";
        }
        $dataCheckString = implode("\n", $dataCheckString);

        // Calculate secret key
        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);

        // Calculate hash
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        // Validate hash
        if (!hash_equals($calculatedHash, $hash)) {
            return null;
        }

        // Parse user data
        if (isset($data['user'])) {
            $userData = json_decode($data['user'], true);
            if ($userData) {
                return [
                    'user' => $userData,
                    'auth_date' => $data['auth_date'] ?? null,
                    'query_id' => $data['query_id'] ?? null,
                    'start_param' => $data['start_param'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Generate a hash for testing purposes
     */
    public function generateTestHash(array $userData): string
    {
        $data = [
            'user' => json_encode($userData),
            'auth_date' => time()
        ];

        ksort($data);

        $dataCheckString = [];
        foreach ($data as $key => $value) {
            $dataCheckString[] = "{$key}={$value}";
        }
        $dataCheckString = implode("\n", $dataCheckString);

        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        $data['hash'] = $hash;

        return http_build_query($data);
    }
}
