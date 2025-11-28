<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Phuket Station',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Bangkok',

    'telegram' => [
        'bot_token' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '',
        'bot_username' => $_ENV['TELEGRAM_BOT_USERNAME'] ?? '',
        'payment_token' => $_ENV['TELEGRAM_PAYMENT_TOKEN'] ?? '',
        'webhook_secret' => $_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? '',
        'stars_enabled' => filter_var($_ENV['TELEGRAM_STARS_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'stars_rate_thb' => (float) ($_ENV['TELEGRAM_STARS_RATE_THB'] ?? 0.013),
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'change-this-secret',
        'expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 86400),
        'algorithm' => 'HS256',
    ],

    'loyalty' => [
        'cashback_percent' => (float) ($_ENV['CASHBACK_PERCENT'] ?? 5),
        'referral_bonus_thb' => (float) ($_ENV['REFERRAL_BONUS_THB'] ?? 200),
        'cashback_expiry_days' => (int) ($_ENV['CASHBACK_EXPIRY_DAYS'] ?? 365),
    ],

    'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
        'allowed_methods' => explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS'),
        'allowed_headers' => explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization'),
    ],

    'rate_limit' => [
        'requests' => (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 100),
        'window' => (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60),
    ],

    'upload' => [
        'max_size' => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
        'allowed_types' => explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,webp'),
    ],

    'supported_languages' => ['en', 'ru', 'th'],
    'default_language' => 'en',

    'supported_currencies' => ['THB', 'USD', 'EUR', 'RUB'],
    'default_currency' => 'THB',
];
