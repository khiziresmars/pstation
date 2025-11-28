<?php

/**
 * Queue Worker Script
 * Processes background jobs from the queue
 * Run as systemd service or manually
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "[" . date('Y-m-d H:i:s') . "] Queue worker started\n";

// Graceful shutdown handling
$running = true;

pcntl_async_signals(true);
pcntl_signal(SIGTERM, function () use (&$running) {
    echo "\n[INFO] Received SIGTERM, shutting down gracefully...\n";
    $running = false;
});
pcntl_signal(SIGINT, function () use (&$running) {
    echo "\n[INFO] Received SIGINT, shutting down gracefully...\n";
    $running = false;
});

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

    // Create jobs table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL DEFAULT 'default',
            payload JSON NOT NULL,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 3,
            available_at INT UNSIGNED NOT NULL,
            reserved_at INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_queue_available (queue, available_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS failed_jobs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            queue VARCHAR(255) NOT NULL,
            payload JSON NOT NULL,
            exception TEXT NOT NULL,
            failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
    $sleepSeconds = 5; // Poll every 5 seconds

    while ($running) {
        // Get next available job
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                SELECT * FROM jobs
                WHERE queue = 'default'
                AND available_at <= UNIX_TIMESTAMP()
                AND reserved_at IS NULL
                ORDER BY available_at ASC
                LIMIT 1
                FOR UPDATE SKIP LOCKED
            ");
            $stmt->execute();
            $job = $stmt->fetch();

            if (!$job) {
                $pdo->rollBack();
                sleep($sleepSeconds);
                continue;
            }

            // Reserve the job
            $reserveStmt = $pdo->prepare("
                UPDATE jobs
                SET reserved_at = UNIX_TIMESTAMP(),
                    attempts = attempts + 1
                WHERE id = ?
            ");
            $reserveStmt->execute([$job['id']]);
            $pdo->commit();

            // Process the job
            echo "[" . date('H:i:s') . "] Processing job #{$job['id']}...\n";

            $payload = json_decode($job['payload'], true);
            $jobType = $payload['type'] ?? 'unknown';

            try {
                switch ($jobType) {
                    case 'send_telegram_message':
                        processTelegramMessage($botToken, $payload['data']);
                        break;

                    case 'send_email':
                        processEmail($payload['data']);
                        break;

                    case 'process_payment_webhook':
                        processPaymentWebhook($pdo, $payload['data']);
                        break;

                    case 'generate_invoice':
                        generateInvoice($pdo, $payload['data']);
                        break;

                    case 'update_analytics':
                        updateAnalytics($pdo, $payload['data']);
                        break;

                    default:
                        throw new Exception("Unknown job type: {$jobType}");
                }

                // Job completed successfully - delete it
                $deleteStmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                $deleteStmt->execute([$job['id']]);

                echo "[OK] Job #{$job['id']} completed\n";

            } catch (Exception $e) {
                // Job failed
                if ($job['attempts'] >= $job['max_attempts']) {
                    // Move to failed jobs
                    $failStmt = $pdo->prepare("
                        INSERT INTO failed_jobs (queue, payload, exception)
                        VALUES (?, ?, ?)
                    ");
                    $failStmt->execute([$job['queue'], $job['payload'], $e->getMessage()]);

                    $deleteStmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                    $deleteStmt->execute([$job['id']]);

                    echo "[FAIL] Job #{$job['id']} failed permanently: " . $e->getMessage() . "\n";
                } else {
                    // Release for retry (with delay)
                    $delay = pow(2, $job['attempts']) * 60; // Exponential backoff
                    $releaseStmt = $pdo->prepare("
                        UPDATE jobs
                        SET reserved_at = NULL,
                            available_at = UNIX_TIMESTAMP() + ?
                        WHERE id = ?
                    ");
                    $releaseStmt->execute([$delay, $job['id']]);

                    echo "[RETRY] Job #{$job['id']} will retry in {$delay}s: " . $e->getMessage() . "\n";
                }
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "[ERROR] Transaction failed: " . $e->getMessage() . "\n";
            sleep($sleepSeconds);
        }
    }

    echo "[INFO] Queue worker stopped\n";

} catch (Exception $e) {
    echo "[FATAL] " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Send Telegram message
 */
function processTelegramMessage(string $botToken, array $data): void
{
    if (empty($botToken)) {
        throw new Exception('Telegram bot token not configured');
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode([
                'chat_id' => $data['chat_id'],
                'text' => $data['text'],
                'parse_mode' => $data['parse_mode'] ?? 'HTML'
            ]),
            'timeout' => 10
        ]
    ]);

    $result = file_get_contents($url, false, $context);
    if ($result === false) {
        throw new Exception('Failed to send Telegram message');
    }
}

/**
 * Send email (placeholder - implement with your mail service)
 */
function processEmail(array $data): void
{
    // This would integrate with your email service (SMTP, SendGrid, etc.)
    $to = $data['to'];
    $subject = $data['subject'];
    $body = $data['body'];

    // For now, just log it
    error_log("Email to {$to}: {$subject}");

    // TODO: Implement actual email sending
    // mail($to, $subject, $body, $headers);
}

/**
 * Process payment webhook
 */
function processPaymentWebhook(PDO $pdo, array $data): void
{
    $reference = $data['reference'];
    $status = $data['status'];

    $stmt = $pdo->prepare("
        UPDATE bookings
        SET payment_status = ?,
            status = CASE WHEN ? = 'paid' THEN 'confirmed' ELSE status END,
            updated_at = NOW()
        WHERE reference = ?
    ");
    $stmt->execute([$status, $status, $reference]);
}

/**
 * Generate invoice PDF (placeholder)
 */
function generateInvoice(PDO $pdo, array $data): void
{
    $bookingId = $data['booking_id'];

    // TODO: Implement PDF generation
    // For now, just mark as generated
    $stmt = $pdo->prepare("
        UPDATE bookings
        SET invoice_generated = 1
        WHERE id = ?
    ");
    $stmt->execute([$bookingId]);
}

/**
 * Update analytics
 */
function updateAnalytics(PDO $pdo, array $data): void
{
    $date = $data['date'] ?? date('Y-m-d');
    $type = $data['type'];

    // Update daily stats
    $stmt = $pdo->prepare("
        INSERT INTO daily_stats (date, metric, value)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE value = value + 1
    ");
    $stmt->execute([$date, $type]);
}
