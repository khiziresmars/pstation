<?php

/**
 * Send Booking Reminders Script
 * Sends reminder notifications for upcoming bookings
 * Run via cron: 0 * * * *
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "[" . date('Y-m-d H:i:s') . "] Starting booking reminders...\n";

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

    $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';

    if (empty($botToken)) {
        echo "[WARN] Telegram bot token not configured. Skipping notifications.\n";
        exit(0);
    }

    // Find bookings that are:
    // 1. Confirmed (status = 'confirmed')
    // 2. Starting tomorrow (24 hours reminder)
    // 3. Haven't been reminded yet
    $stmt = $pdo->prepare("
        SELECT
            b.*,
            u.telegram_id,
            u.first_name,
            u.language_code,
            COALESCE(v.name, t.name) as item_name,
            CASE
                WHEN b.type = 'vessel' THEN 'yacht'
                ELSE 'tour'
            END as booking_type_label
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN vessels v ON b.type = 'vessel' AND b.item_id = v.id
        LEFT JOIN tours t ON b.type = 'tour' AND b.item_id = t.id
        WHERE b.status = 'confirmed'
        AND b.booking_date BETWEEN NOW() + INTERVAL 23 HOUR AND NOW() + INTERVAL 25 HOUR
        AND (b.reminder_sent IS NULL OR b.reminder_sent = 0)
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();

    if (empty($bookings)) {
        echo "[INFO] No reminders to send.\n";
        exit(0);
    }

    $sent = 0;
    $failed = 0;

    foreach ($bookings as $booking) {
        if (empty($booking['telegram_id'])) {
            echo "[SKIP] Booking {$booking['reference']} - no Telegram ID\n";
            continue;
        }

        // Prepare reminder message
        $lang = $booking['language_code'] ?? 'en';

        $messages = [
            'en' => "🔔 Reminder!\n\nYour {$booking['booking_type_label']} booking is tomorrow!\n\n📋 Booking: {$booking['reference']}\n🚤 {$booking['item_name']}\n📅 Date: " . date('F j, Y', strtotime($booking['booking_date'])) . "\n👥 Guests: {$booking['guests']}\n\nPlease arrive 15 minutes before departure time.\n\nHave a wonderful trip! 🌊",
            'ru' => "🔔 Напоминание!\n\nВаше бронирование {$booking['booking_type_label']} завтра!\n\n📋 Бронь: {$booking['reference']}\n🚤 {$booking['item_name']}\n📅 Дата: " . date('j.m.Y', strtotime($booking['booking_date'])) . "\n👥 Гостей: {$booking['guests']}\n\nПожалуйста, прибудьте за 15 минут до отправления.\n\nПриятного путешествия! 🌊",
            'th' => "🔔 แจ้งเตือน!\n\nการจองของคุณคือพรุ่งนี้!\n\n📋 รหัสจอง: {$booking['reference']}\n🚤 {$booking['item_name']}\n📅 วันที่: " . date('j/m/Y', strtotime($booking['booking_date'])) . "\n👥 จำนวนผู้เข้าพัก: {$booking['guests']}\n\nกรุณามาถึงก่อนเวลาออกเดินทาง 15 นาที\n\nขอให้เดินทางปลอดภัย! 🌊"
        ];

        $message = $messages[$lang] ?? $messages['en'];

        // Send via Telegram
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id' => $booking['telegram_id'],
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result !== false) {
            // Mark as sent
            $updateStmt = $pdo->prepare("UPDATE bookings SET reminder_sent = 1 WHERE id = ?");
            $updateStmt->execute([$booking['id']]);

            echo "[OK] Sent reminder for {$booking['reference']} to user {$booking['telegram_id']}\n";
            $sent++;
        } else {
            echo "[FAIL] Could not send reminder for {$booking['reference']}\n";
            $failed++;
        }

        // Rate limiting
        usleep(100000); // 100ms delay between messages
    }

    echo "[DONE] Sent {$sent} reminders, {$failed} failed.\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
