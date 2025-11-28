<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP Response helper
 */
class Response
{
    /**
     * Send JSON response
     */
    public static function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send success response
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send error response
     */
    public static function error(string $message, int $statusCode = 400, string $code = 'ERROR', array $details = []): void
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        self::json($response, $statusCode);
    }

    /**
     * Send paginated response
     */
    public static function paginated(array $data, int $total, int $page, int $perPage): void
    {
        $totalPages = (int) ceil($total / $perPage);

        self::json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    }

    /**
     * Send validation error response
     */
    public static function validationError(array $errors): void
    {
        self::json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $errors
            ]
        ], 422);
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401, 'UNAUTHORIZED');
    }

    /**
     * Send forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403, 'FORBIDDEN');
    }

    /**
     * Send not found response
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404, 'NOT_FOUND');
    }
}
