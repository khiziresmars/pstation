<?php

declare(strict_types=1);

/**
 * Phuket Yacht & Tours API
 * Main entry point
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Load configuration
$config = require BASE_PATH . '/config/app.php';

// Initialize application
use App\Core\Application;
use App\Core\Router;
use App\Core\Response;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;

try {
    // Create application instance
    $app = new Application($config);

    // Apply CORS middleware
    CorsMiddleware::handle();

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        Response::json(['status' => 'ok']);
    }

    // Initialize router
    $router = new Router();

    // ===================
    // PUBLIC ROUTES
    // ===================

    // Health check
    $router->get('/api/health', 'HealthController@check');

    // Vessels
    $router->get('/api/vessels', 'VesselController@index');
    $router->get('/api/vessels/featured', 'VesselController@featured');
    $router->get('/api/vessels/{slug}', 'VesselController@show');
    $router->get('/api/vessels/{id}/availability', 'VesselController@availability');
    $router->get('/api/vessels/{id}/reviews', 'VesselController@reviews');

    // Tours
    $router->get('/api/tours', 'TourController@index');
    $router->get('/api/tours/featured', 'TourController@featured');
    $router->get('/api/tours/{slug}', 'TourController@show');
    $router->get('/api/tours/{id}/availability', 'TourController@availability');
    $router->get('/api/tours/{id}/reviews', 'TourController@reviews');

    // Exchange rates
    $router->get('/api/exchange-rates', 'ExchangeRateController@index');

    // Promo codes (public validation)
    $router->post('/api/promo/validate', 'PromoController@validate');

    // Settings (public)
    $router->get('/api/settings', 'SettingsController@public');

    // ===================
    // AUTHENTICATED ROUTES (Telegram Auth)
    // ===================

    // User profile
    $router->get('/api/user/profile', 'UserController@profile', [AuthMiddleware::class]);
    $router->put('/api/user/profile', 'UserController@update', [AuthMiddleware::class]);
    $router->get('/api/user/bookings', 'UserController@bookings', [AuthMiddleware::class]);
    $router->get('/api/user/favorites', 'UserController@favorites', [AuthMiddleware::class]);
    $router->post('/api/user/favorites', 'UserController@addFavorite', [AuthMiddleware::class]);
    $router->delete('/api/user/favorites/{type}/{id}', 'UserController@removeFavorite', [AuthMiddleware::class]);
    $router->get('/api/user/cashback', 'UserController@cashbackHistory', [AuthMiddleware::class]);
    $router->get('/api/user/referrals', 'UserController@referrals', [AuthMiddleware::class]);
    $router->get('/api/user/notifications', 'UserController@notifications', [AuthMiddleware::class]);
    $router->put('/api/user/notifications/{id}/read', 'UserController@markNotificationRead', [AuthMiddleware::class]);

    // Bookings
    $router->post('/api/bookings', 'BookingController@create', [AuthMiddleware::class]);
    $router->get('/api/bookings/{reference}', 'BookingController@show', [AuthMiddleware::class]);
    $router->post('/api/bookings/{reference}/cancel', 'BookingController@cancel', [AuthMiddleware::class]);
    $router->post('/api/bookings/calculate', 'BookingController@calculate', [AuthMiddleware::class]);

    // Reviews
    $router->post('/api/reviews', 'ReviewController@create', [AuthMiddleware::class]);

    // Payments
    $router->post('/api/payments/telegram-stars/create', 'PaymentController@createTelegramStars', [AuthMiddleware::class]);
    $router->post('/api/payments/telegram-stars/confirm', 'PaymentController@confirmTelegramStars', [AuthMiddleware::class]);

    // ===================
    // TELEGRAM WEBHOOK
    // ===================
    $router->post('/api/telegram/webhook', 'TelegramController@webhook');

    // ===================
    // AUTH ROUTES
    // ===================
    $router->post('/api/auth/telegram', 'AuthController@telegramAuth');

    // Run router
    $router->run();

} catch (\Throwable $e) {
    // Log error
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());

    // Return error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => $config['debug'] ? $e->getMessage() : 'Internal server error'
        ]
    ]);
}
