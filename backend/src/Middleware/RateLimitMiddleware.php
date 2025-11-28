<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Response;
use App\Core\RateLimitException;

/**
 * Rate Limiting Middleware
 * Limits requests per IP/user using file-based storage
 */
class RateLimitMiddleware
{
    private static string $storagePath;
    private static array $config = [
        'default' => ['limit' => 60, 'window' => 60],      // 60 requests per minute
        'api' => ['limit' => 120, 'window' => 60],         // 120 requests per minute for API
        'auth' => ['limit' => 5, 'window' => 60],          // 5 auth attempts per minute
        'booking' => ['limit' => 10, 'window' => 60],      // 10 booking attempts per minute
        'upload' => ['limit' => 20, 'window' => 300],      // 20 uploads per 5 minutes
    ];

    public static function init(): void
    {
        self::$storagePath = dirname(__DIR__, 2) . '/storage/cache/rate_limits';
        if (!is_dir(self::$storagePath)) {
            mkdir(self::$storagePath, 0755, true);
        }
    }

    /**
     * Handle rate limiting
     */
    public static function handle(string $type = 'default'): bool
    {
        self::init();

        $config = self::$config[$type] ?? self::$config['default'];
        $key = self::getKey($type);

        $data = self::getData($key);
        $now = time();

        // Clean old entries
        $data = array_filter($data, fn($timestamp) => $timestamp > ($now - $config['window']));

        // Check limit
        if (count($data) >= $config['limit']) {
            $retryAfter = min($data) + $config['window'] - $now;

            header('X-RateLimit-Limit: ' . $config['limit']);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . ($now + $retryAfter));
            header('Retry-After: ' . $retryAfter);

            Response::error('Too many requests. Please try again later.', 429, 'RATE_LIMIT_EXCEEDED');
            return false;
        }

        // Add current request
        $data[] = $now;
        self::setData($key, $data);

        // Set rate limit headers
        header('X-RateLimit-Limit: ' . $config['limit']);
        header('X-RateLimit-Remaining: ' . ($config['limit'] - count($data)));
        header('X-RateLimit-Reset: ' . ($now + $config['window']));

        return true;
    }

    /**
     * Check rate limit without blocking
     */
    public static function check(string $type = 'default'): array
    {
        self::init();

        $config = self::$config[$type] ?? self::$config['default'];
        $key = self::getKey($type);
        $data = self::getData($key);
        $now = time();

        $data = array_filter($data, fn($timestamp) => $timestamp > ($now - $config['window']));

        return [
            'limit' => $config['limit'],
            'remaining' => max(0, $config['limit'] - count($data)),
            'reset' => $now + $config['window'],
            'exceeded' => count($data) >= $config['limit'],
        ];
    }

    /**
     * Get unique key for current request
     */
    private static function getKey(string $type): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ip = explode(',', $ip)[0]; // Get first IP if multiple

        // Add user ID if authenticated
        $userId = AuthMiddleware::getUser()['id'] ?? 'guest';

        return md5("{$type}:{$ip}:{$userId}");
    }

    /**
     * Get rate limit data from file
     */
    private static function getData(string $key): array
    {
        $file = self::$storagePath . '/' . $key . '.json';

        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    /**
     * Save rate limit data to file
     */
    private static function setData(string $key, array $data): void
    {
        $file = self::$storagePath . '/' . $key . '.json';
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Clear rate limit for key
     */
    public static function clear(string $type = 'default'): void
    {
        self::init();

        $key = self::getKey($type);
        $file = self::$storagePath . '/' . $key . '.json';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Clean up old rate limit files
     */
    public static function cleanup(): int
    {
        self::init();

        $deleted = 0;
        $files = glob(self::$storagePath . '/*.json');

        foreach ($files as $file) {
            // Delete files older than 10 minutes
            if (filemtime($file) < (time() - 600)) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Configure rate limits
     */
    public static function configure(string $type, int $limit, int $window): void
    {
        self::$config[$type] = [
            'limit' => $limit,
            'window' => $window,
        ];
    }
}
