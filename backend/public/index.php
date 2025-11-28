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

    // Search
    $router->get('/api/search', 'SearchController@search');
    $router->get('/api/search/suggestions', 'SearchController@suggestions');
    $router->get('/api/search/popular', 'SearchController@popular');
    $router->get('/api/search/vessels', 'SearchController@vessels');
    $router->get('/api/search/tours', 'SearchController@tours');

    // Error reporting endpoint
    $router->post('/api/errors/report', 'ErrorController@report');

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
    $router->get('/api/user/notifications', 'NotificationController@index', [AuthMiddleware::class]);
    $router->get('/api/user/notifications/unread-count', 'NotificationController@unreadCount', [AuthMiddleware::class]);
    $router->put('/api/user/notifications/read-all', 'NotificationController@markAllRead', [AuthMiddleware::class]);
    $router->put('/api/user/notifications/{id}/read', 'NotificationController@markRead', [AuthMiddleware::class]);
    $router->delete('/api/user/notifications/{id}', 'NotificationController@delete', [AuthMiddleware::class]);

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

    // ===================
    // ADMIN ROUTES
    // ===================
    use App\Middleware\AdminAuthMiddleware;

    // Admin Dashboard
    $router->get('/api/admin/dashboard', 'Admin\\DashboardController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/dashboard/stats', 'Admin\\DashboardController@quickStats', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/analytics', 'Admin\\DashboardController@analytics', [AdminAuthMiddleware::class]);

    // Admin Vessels Management
    $router->get('/api/admin/vessels', 'Admin\\VesselsController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/vessels/{id}', 'Admin\\VesselsController@show', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/vessels', 'Admin\\VesselsController@store', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/vessels/{id}', 'Admin\\VesselsController@update', [AdminAuthMiddleware::class]);
    $router->delete('/api/admin/vessels/{id}', 'Admin\\VesselsController@destroy', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/vessels/{id}/extras', 'Admin\\VesselsController@addExtra', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/vessels/{id}/availability', 'Admin\\VesselsController@updateAvailability', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/vessels/{id}/duplicate', 'Admin\\VesselsController@duplicate', [AdminAuthMiddleware::class]);

    // Admin Tours Management
    $router->get('/api/admin/tours', 'Admin\\ToursController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/tours/categories', 'Admin\\ToursController@categories', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/tours/{id}', 'Admin\\ToursController@show', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/tours', 'Admin\\ToursController@store', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/tours/{id}', 'Admin\\ToursController@update', [AdminAuthMiddleware::class]);
    $router->delete('/api/admin/tours/{id}', 'Admin\\ToursController@destroy', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/tours/{id}/availability', 'Admin\\ToursController@updateAvailability', [AdminAuthMiddleware::class]);

    // Admin Bookings Management
    $router->get('/api/admin/bookings', 'Admin\\BookingsController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/bookings/calendar', 'Admin\\BookingsController@calendar', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/bookings/today', 'Admin\\BookingsController@today', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/bookings/export', 'Admin\\BookingsController@export', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/bookings/{reference}', 'Admin\\BookingsController@show', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/bookings/{reference}/status', 'Admin\\BookingsController@updateStatus', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/bookings/{reference}/confirm', 'Admin\\BookingsController@confirm', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/bookings/{reference}/cancel', 'Admin\\BookingsController@cancel', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/bookings/{reference}/payment', 'Admin\\BookingsController@updatePayment', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/bookings/{reference}/note', 'Admin\\BookingsController@addNote', [AdminAuthMiddleware::class]);

    // Admin Users Management
    $router->get('/api/admin/users', 'Admin\\UsersController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/users/export', 'Admin\\UsersController@export', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/users/{id}', 'Admin\\UsersController@show', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/users/{id}', 'Admin\\UsersController@update', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/users/{id}/block', 'Admin\\UsersController@block', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/users/{id}/unblock', 'Admin\\UsersController@unblock', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/users/{id}/cashback', 'Admin\\UsersController@adjustCashback', [AdminAuthMiddleware::class]);

    // Admin Promos Management
    $router->get('/api/admin/promos', 'Admin\\PromosController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/promos/stats', 'Admin\\PromosController@stats', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/promos/{id}', 'Admin\\PromosController@show', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/promos', 'Admin\\PromosController@store', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/promos/generate', 'Admin\\PromosController@generate', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/promos/{id}', 'Admin\\PromosController@update', [AdminAuthMiddleware::class]);
    $router->delete('/api/admin/promos/{id}', 'Admin\\PromosController@destroy', [AdminAuthMiddleware::class]);

    // Admin Reviews Management
    $router->get('/api/admin/reviews', 'Admin\\ReviewsController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/reviews/stats', 'Admin\\ReviewsController@stats', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/reviews/{id}', 'Admin\\ReviewsController@show', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/reviews/{id}/approve', 'Admin\\ReviewsController@approve', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/reviews/{id}/reject', 'Admin\\ReviewsController@reject', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/reviews/{id}/reply', 'Admin\\ReviewsController@reply', [AdminAuthMiddleware::class]);
    $router->delete('/api/admin/reviews/{id}', 'Admin\\ReviewsController@destroy', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/reviews/bulk-approve', 'Admin\\ReviewsController@bulkApprove', [AdminAuthMiddleware::class]);

    // Admin Settings
    $router->get('/api/admin/settings', 'Admin\\SettingsController@index', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/settings', 'Admin\\SettingsController@update', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/settings/exchange-rates', 'Admin\\SettingsController@exchangeRates', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/settings/exchange-rates', 'Admin\\SettingsController@updateExchangeRates', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/settings/logs', 'Admin\\SettingsController@logs', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/settings/notifications', 'Admin\\SettingsController@notifications', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/settings/notifications/{code}', 'Admin\\SettingsController@updateNotification', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/settings/test-notification', 'Admin\\SettingsController@testNotification', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/settings/system', 'Admin\\SettingsController@system', [AdminAuthMiddleware::class]);

    // Admin Admins Management
    $router->get('/api/admin/admins', 'Admin\\AdminsController@index', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/admins/me', 'Admin\\AdminsController@me', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/admins/me', 'Admin\\AdminsController@updateMe', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/admins/roles', 'Admin\\AdminsController@roles', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/admins/permissions', 'Admin\\AdminsController@permissions', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/admins/roles', 'Admin\\AdminsController@createRole', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/admins/roles/{id}', 'Admin\\AdminsController@updateRole', [AdminAuthMiddleware::class]);
    $router->delete('/api/admin/admins/roles/{id}', 'Admin\\AdminsController@deleteRole', [AdminAuthMiddleware::class]);
    $router->get('/api/admin/admins/{id}', 'Admin\\AdminsController@show', [AdminAuthMiddleware::class]);
    $router->post('/api/admin/admins', 'Admin\\AdminsController@store', [AdminAuthMiddleware::class]);
    $router->put('/api/admin/admins/{id}', 'Admin\\AdminsController@update', [AdminAuthMiddleware::class]);
    $router->delete('/api/admin/admins/{id}', 'Admin\\AdminsController@destroy', [AdminAuthMiddleware::class]);

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
