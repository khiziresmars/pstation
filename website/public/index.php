<?php

declare(strict_types=1);

/**
 * Phuket Station - Public Website
 * Fast PHP-based website with server-side rendering
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Timezone
date_default_timezone_set('Asia/Bangkok');

// Define paths
define('WEBSITE_PATH', dirname(__DIR__));
define('BACKEND_PATH', dirname(WEBSITE_PATH) . '/backend');
define('BASE_PATH', BACKEND_PATH);

// Autoloader from backend
require BACKEND_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BACKEND_PATH);
$dotenv->safeLoad();

// Load backend config
$config = require BACKEND_PATH . '/config/app.php';

// Load website components
require WEBSITE_PATH . '/src/Core/View.php';
require WEBSITE_PATH . '/src/Core/WebRouter.php';
require WEBSITE_PATH . '/src/Core/Assets.php';
require WEBSITE_PATH . '/src/Core/SEO.php';

use Website\Core\View;
use Website\Core\WebRouter;
use Website\Core\Assets;
use App\Core\Application;

// Session for language preference
session_start();

// Detect language
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
if (!in_array($lang, ['en', 'ru', 'th'])) {
    $lang = 'en';
}
$_SESSION['lang'] = $lang;

// Output buffering for compression
if (!ob_start('ob_gzhandler')) {
    ob_start();
}

try {
    // Initialize backend application (for services)
    $app = new Application($config);

    // Initialize view engine
    $view = new View(WEBSITE_PATH . '/templates', $lang);
    $view->setGlobal('lang', $lang);
    $view->setGlobal('app_name', $_ENV['APP_NAME'] ?? 'Phuket Station');
    $view->setGlobal('current_url', $_SERVER['REQUEST_URI'] ?? '/');

    // Assets helper
    $assets = new Assets(WEBSITE_PATH . '/public');
    $view->setGlobal('assets', $assets);

    // Initialize router
    $router = new WebRouter($view);

    // Load translations
    $translations = require WEBSITE_PATH . '/src/translations.php';
    $view->setGlobal('t', $translations[$lang] ?? $translations['en']);

    // ==================
    // WEBSITE ROUTES
    // ==================

    // Home page
    $router->get('/', 'HomeController@index');
    $router->get('/home', 'HomeController@index');

    // Vessels (Yachts & Boats)
    $router->get('/yachts', 'VesselsController@index');
    $router->get('/yachts/{slug}', 'VesselsController@show');
    $router->get('/boats', 'VesselsController@index');
    $router->get('/vessels', 'VesselsController@index');
    $router->get('/vessels/{slug}', 'VesselsController@show');

    // Tours
    $router->get('/tours', 'ToursController@index');
    $router->get('/tours/{slug}', 'ToursController@show');
    $router->get('/island-tours', 'ToursController@index');

    // Booking
    $router->get('/book/{type}/{slug}', 'BookingController@index');
    $router->post('/book/submit', 'BookingController@submit');
    $router->get('/booking/{reference}', 'BookingController@confirmation');

    // Static pages
    $router->get('/about', 'PagesController@about');
    $router->get('/contact', 'PagesController@contact');
    $router->post('/contact', 'PagesController@sendContact');
    $router->get('/faq', 'PagesController@faq');
    $router->get('/terms', 'PagesController@terms');
    $router->get('/privacy', 'PagesController@privacy');

    // Sitemap & SEO
    $router->get('/sitemap.xml', 'SeoController@sitemap');
    $router->get('/robots.txt', 'SeoController@robots');

    // Language switch
    $router->get('/lang/{code}', 'PagesController@setLanguage');

    // Run router
    $router->run();

} catch (\Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());

    http_response_code(500);
    include WEBSITE_PATH . '/templates/errors/500.php';
}

ob_end_flush();
