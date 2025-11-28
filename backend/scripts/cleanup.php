<?php

/**
 * Cleanup Script
 * Cleans up expired tokens, old sessions, and temporary data
 * Run via cron: 0 3 * * *
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "[" . date('Y-m-d H:i:s') . "] Starting cleanup...\n";

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

    $totalCleaned = 0;

    // 1. Clean expired password reset tokens (older than 24 hours)
    $stmt = $pdo->exec("
        DELETE FROM password_resets
        WHERE created_at < NOW() - INTERVAL 24 HOUR
    ");
    $cleaned = (int) $stmt;
    echo "[OK] Cleaned {$cleaned} expired password reset tokens\n";
    $totalCleaned += $cleaned;

    // 2. Clean expired email verification tokens (older than 7 days)
    $stmt = $pdo->exec("
        DELETE FROM email_verifications
        WHERE created_at < NOW() - INTERVAL 7 DAY
    ");
    $cleaned = (int) $stmt;
    echo "[OK] Cleaned {$cleaned} expired email verification tokens\n";
    $totalCleaned += $cleaned;

    // 3. Clean expired promo codes (ended more than 30 days ago)
    $stmt = $pdo->exec("
        UPDATE promo_codes
        SET is_active = 0
        WHERE end_date < NOW() - INTERVAL 30 DAY
        AND is_active = 1
    ");
    $cleaned = (int) $stmt;
    echo "[OK] Deactivated {$cleaned} expired promo codes\n";
    $totalCleaned += $cleaned;

    // 4. Clean old activity logs (older than 90 days)
    $stmt = $pdo->exec("
        DELETE FROM activity_logs
        WHERE created_at < NOW() - INTERVAL 90 DAY
    ");
    $cleaned = (int) $stmt;
    echo "[OK] Cleaned {$cleaned} old activity logs\n";
    $totalCleaned += $cleaned;

    // 5. Clean abandoned bookings (pending for more than 24 hours without payment)
    $stmt = $pdo->exec("
        UPDATE bookings
        SET status = 'expired'
        WHERE status = 'pending'
        AND payment_status = 'pending'
        AND created_at < NOW() - INTERVAL 24 HOUR
    ");
    $cleaned = (int) $stmt;
    echo "[OK] Marked {$cleaned} abandoned bookings as expired\n";
    $totalCleaned += $cleaned;

    // 6. Clean old error logs from storage (older than 30 days)
    $logsPath = dirname(__DIR__) . '/storage/logs';
    if (is_dir($logsPath)) {
        $files = glob($logsPath . '/*.log');
        $oldFiles = 0;
        $cutoffTime = time() - (30 * 24 * 60 * 60); // 30 days ago

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $oldFiles++;
                }
            }
        }
        echo "[OK] Removed {$oldFiles} old log files\n";
        $totalCleaned += $oldFiles;
    }

    // 7. Clean old cache files (older than 7 days)
    $cachePath = dirname(__DIR__) . '/cache';
    if (is_dir($cachePath)) {
        $files = glob($cachePath . '/*');
        $cacheFiles = 0;
        $cutoffTime = time() - (7 * 24 * 60 * 60); // 7 days ago

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $cacheFiles++;
                }
            }
        }
        echo "[OK] Removed {$cacheFiles} old cache files\n";
        $totalCleaned += $cacheFiles;
    }

    // 8. Clean expired cashback (if configured)
    $cashbackExpiryDays = (int) ($_ENV['CASHBACK_EXPIRY_DAYS'] ?? 365);
    $stmt = $pdo->exec("
        UPDATE cashback_transactions
        SET status = 'expired'
        WHERE status = 'pending'
        AND created_at < NOW() - INTERVAL {$cashbackExpiryDays} DAY
    ");
    $cleaned = (int) $stmt;
    echo "[OK] Expired {$cleaned} old cashback entries\n";
    $totalCleaned += $cleaned;

    // 9. Clean unused uploaded files (optional - be careful)
    // This could be added to clean orphaned uploads

    // 10. Optimize tables (run weekly - check day)
    if (date('N') == 7) { // Sunday
        echo "[INFO] Running table optimization (Sunday)...\n";
        $tables = ['bookings', 'users', 'reviews', 'activity_logs', 'payments'];
        foreach ($tables as $table) {
            try {
                $pdo->exec("OPTIMIZE TABLE {$table}");
                echo "[OK] Optimized table: {$table}\n";
            } catch (Exception $e) {
                echo "[WARN] Could not optimize {$table}: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "[DONE] Total items cleaned: {$totalCleaned}\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
