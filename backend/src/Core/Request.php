<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Request helper
 */
class Request
{
    private static ?array $jsonBody = null;

    /**
     * Get request method
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get request URI
     */
    public static function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    /**
     * Get query parameter
     */
    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public static function queryAll(): array
    {
        return $_GET;
    }

    /**
     * Get POST parameter
     */
    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get JSON body
     */
    public static function json(): array
    {
        if (self::$jsonBody === null) {
            $rawBody = file_get_contents('php://input');
            self::$jsonBody = json_decode($rawBody, true) ?? [];
        }

        return self::$jsonBody;
    }

    /**
     * Get JSON body parameter
     */
    public static function input(string $key, mixed $default = null): mixed
    {
        $json = self::json();
        return $json[$key] ?? $default;
    }

    /**
     * Get header value
     */
    public static function header(string $name): ?string
    {
        $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        if (isset($_SERVER[$headerName])) {
            return $_SERVER[$headerName];
        }

        // Handle special headers
        $specialHeaders = [
            'Content-Type' => 'CONTENT_TYPE',
            'Content-Length' => 'CONTENT_LENGTH',
            'Authorization' => 'HTTP_AUTHORIZATION'
        ];

        if (isset($specialHeaders[$name]) && isset($_SERVER[$specialHeaders[$name]])) {
            return $_SERVER[$specialHeaders[$name]];
        }

        // Try to get from getallheaders() if available
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $headerKey => $headerValue) {
                if (strtolower($headerKey) === strtolower($name)) {
                    return $headerValue;
                }
            }
        }

        return null;
    }

    /**
     * Get Authorization token
     */
    public static function bearerToken(): ?string
    {
        $header = self::header('Authorization');

        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get Telegram init data
     */
    public static function telegramInitData(): ?string
    {
        $header = self::header('Authorization');

        if ($header && preg_match('/tma\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get client IP address
     */
    public static function ip(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get User Agent
     */
    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax(): bool
    {
        return strtolower(self::header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Validate required fields
     */
    public static function validate(array $rules): array
    {
        $data = self::json();
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);

            foreach ($ruleList as $r) {
                $r = trim($r);

                // Required check
                if ($r === 'required' && (!isset($data[$field]) || $data[$field] === '')) {
                    $errors[$field][] = "{$field} is required";
                    continue;
                }

                if (!isset($data[$field])) {
                    continue;
                }

                $value = $data[$field];

                // Type checks
                if ($r === 'string' && !is_string($value)) {
                    $errors[$field][] = "{$field} must be a string";
                }

                if ($r === 'integer' && !is_int($value)) {
                    $errors[$field][] = "{$field} must be an integer";
                }

                if ($r === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "{$field} must be numeric";
                }

                if ($r === 'array' && !is_array($value)) {
                    $errors[$field][] = "{$field} must be an array";
                }

                if ($r === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "{$field} must be a valid email";
                }

                // Min/max checks
                if (preg_match('/^min:(\d+)$/', $r, $matches)) {
                    $min = (int) $matches[1];
                    if (is_string($value) && strlen($value) < $min) {
                        $errors[$field][] = "{$field} must be at least {$min} characters";
                    }
                    if (is_numeric($value) && $value < $min) {
                        $errors[$field][] = "{$field} must be at least {$min}";
                    }
                }

                if (preg_match('/^max:(\d+)$/', $r, $matches)) {
                    $max = (int) $matches[1];
                    if (is_string($value) && strlen($value) > $max) {
                        $errors[$field][] = "{$field} must not exceed {$max} characters";
                    }
                    if (is_numeric($value) && $value > $max) {
                        $errors[$field][] = "{$field} must not exceed {$max}";
                    }
                }

                // In check
                if (preg_match('/^in:(.+)$/', $r, $matches)) {
                    $allowed = explode(',', $matches[1]);
                    if (!in_array($value, $allowed)) {
                        $errors[$field][] = "{$field} must be one of: " . implode(', ', $allowed);
                    }
                }

                // Date check
                if ($r === 'date') {
                    $date = \DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        $errors[$field][] = "{$field} must be a valid date (Y-m-d)";
                    }
                }
            }
        }

        return $errors;
    }
}
