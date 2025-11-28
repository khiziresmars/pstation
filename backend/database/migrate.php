<?php

/**
 * Database Migration Runner
 * Run all migrations in order
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "===========================================\n";
echo "  Phuket Station - Database Migration\n";
echo "===========================================\n\n";

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

    echo "[OK] Connected to database: {$dbname}\n\n";

    // Create migrations table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Get already run migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get migration files
    $migrationsPath = __DIR__ . '/migrations';
    $files = glob($migrationsPath . '/*.php');
    sort($files);

    if (empty($files)) {
        echo "[INFO] No migration files found.\n";
        exit(0);
    }

    // Get current batch number
    $stmt = $pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations");
    $batch = (int) $stmt->fetchColumn();

    $count = 0;
    foreach ($files as $file) {
        $migration = basename($file, '.php');

        if (in_array($migration, $executed)) {
            echo "[SKIP] {$migration} (already executed)\n";
            continue;
        }

        echo "[RUN] {$migration}... ";

        try {
            $migrationClass = require $file;

            if (is_object($migrationClass) && method_exists($migrationClass, 'up')) {
                $migrationClass->up($pdo);
            }

            // Record migration
            $stmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
            $stmt->execute([$migration, $batch]);

            echo "OK\n";
            $count++;
        } catch (Exception $e) {
            echo "FAILED\n";
            echo "[ERROR] " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\n[DONE] Executed {$count} migration(s).\n";

} catch (PDOException $e) {
    echo "[ERROR] Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
