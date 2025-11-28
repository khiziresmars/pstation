<?php

/**
 * Update Exchange Rates Script
 * Fetches latest exchange rates from external API and updates database
 * Run via cron: 0 */6 * * *
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "[" . date('Y-m-d H:i:s') . "] Starting exchange rates update...\n";

try {
    // Database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_DATABASE'] ?? 'phuket_yachts';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Base currency is THB
    $baseCurrency = 'THB';

    // Try to fetch from exchangerate-api (free tier)
    $apiUrl = "https://api.exchangerate-api.com/v4/latest/{$baseCurrency}";

    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'PhuketStation/1.0'
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        echo "[WARN] Could not fetch exchange rates from API. Using fallback rates.\n";
        // Fallback rates (approximate) - rate_from_thb values (1 THB = X currency)
        $fallbackRates = [
            'USD' => 0.0282,
            'EUR' => 0.0262,
            'GBP' => 0.0225,
            'RUB' => 2.63,
            'CNY' => 0.204,
            'AUD' => 0.0435
        ];
        $rates = $fallbackRates;
        $useFallback = true;
    } else {
        $data = json_decode($response, true);
        if (!isset($data['rates'])) {
            throw new Exception('Invalid API response format');
        }
        $rates = $data['rates'];
        $useFallback = false;
    }

    $updated = 0;

    // Update each currency rate
    foreach ($rates as $currency => $rateFromThb) {
        if ($currency === 'THB') {
            continue;
        }

        // Calculate rate_to_thb (how much THB for 1 unit of currency)
        $rateToThb = 1 / $rateFromThb;

        // Update the rate in database
        $stmt = $pdo->prepare("
            UPDATE exchange_rates
            SET rate_to_thb = ?,
                rate_from_thb = ?,
                last_updated_at = NOW()
            WHERE currency_code = ?
        ");
        $result = $stmt->execute([$rateToThb, $rateFromThb, $currency]);

        if ($stmt->rowCount() > 0) {
            echo "[OK] Updated {$currency}: 1 {$currency} = " . number_format($rateToThb, 4) . " THB\n";
            $updated++;
        }
    }

    if ($useFallback) {
        echo "[INFO] Used fallback rates (API unavailable)\n";
    }

    echo "[DONE] Updated {$updated} exchange rates.\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
