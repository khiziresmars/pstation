<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;

/**
 * CORS Middleware
 * Handles Cross-Origin Resource Sharing headers
 */
class CorsMiddleware
{
    public static function handle(): void
    {
        $app = Application::getInstance();
        $config = $app->getConfig('cors', []);

        $allowedOrigins = $config['allowed_origins'] ?? ['*'];
        $allowedMethods = $config['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        $allowedHeaders = $config['allowed_headers'] ?? ['Content-Type', 'Authorization'];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        // Check if origin is allowed
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
}
