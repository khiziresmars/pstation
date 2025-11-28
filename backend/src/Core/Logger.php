<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PSR-3 Compatible Logger
 * Supports multiple log channels and structured logging
 */
class Logger
{
    public const EMERGENCY = 'emergency';
    public const ALERT = 'alert';
    public const CRITICAL = 'critical';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const NOTICE = 'notice';
    public const INFO = 'info';
    public const DEBUG = 'debug';

    private const LEVEL_PRIORITY = [
        self::EMERGENCY => 800,
        self::ALERT => 700,
        self::CRITICAL => 600,
        self::ERROR => 500,
        self::WARNING => 400,
        self::NOTICE => 300,
        self::INFO => 200,
        self::DEBUG => 100,
    ];

    private string $channel;
    private string $logPath;
    private string $minLevel;
    private bool $enabled;
    private array $processors = [];

    public function __construct(
        string $channel = 'app',
        ?string $logPath = null,
        string $minLevel = self::DEBUG
    ) {
        $this->channel = $channel;
        $this->logPath = $logPath ?? dirname(__DIR__, 2) . '/storage/logs';
        $this->minLevel = $minLevel;
        $this->enabled = (bool) ($_ENV['LOG_ENABLED'] ?? true);

        $this->ensureLogDirectory();
        $this->registerDefaultProcessors();
    }

    /**
     * Create a new logger for a specific channel
     */
    public function channel(string $channel): self
    {
        $logger = clone $this;
        $logger->channel = $channel;
        return $logger;
    }

    /**
     * Log with dynamic level
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled || !$this->shouldLog($level)) {
            return;
        }

        $record = $this->createRecord($level, $message, $context);
        $record = $this->processRecord($record);
        $this->write($record);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Add a processor to modify log records
     */
    public function addProcessor(callable $processor): self
    {
        $this->processors[] = $processor;
        return $this;
    }

    /**
     * Check if level should be logged
     */
    private function shouldLog(string $level): bool
    {
        $levelPriority = self::LEVEL_PRIORITY[$level] ?? 0;
        $minPriority = self::LEVEL_PRIORITY[$this->minLevel] ?? 0;
        return $levelPriority >= $minPriority;
    }

    /**
     * Create a log record
     */
    private function createRecord(string $level, string $message, array $context): array
    {
        return [
            'timestamp' => date('c'),
            'level' => strtoupper($level),
            'channel' => $this->channel,
            'message' => $this->interpolate($message, $context),
            'context' => $context,
            'extra' => [],
        ];
    }

    /**
     * Process record through all processors
     */
    private function processRecord(array $record): array
    {
        foreach ($this->processors as $processor) {
            $record = $processor($record);
        }
        return $record;
    }

    /**
     * Write log record to file
     */
    private function write(array $record): void
    {
        $filename = $this->getFilename();
        $line = $this->formatRecord($record);

        file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get log filename for today
     */
    private function getFilename(): string
    {
        $date = date('Y-m-d');
        return "{$this->logPath}/{$this->channel}-{$date}.log";
    }

    /**
     * Format record as log line
     */
    private function formatRecord(array $record): string
    {
        $context = !empty($record['context']) ? json_encode($record['context'], JSON_UNESCAPED_UNICODE) : '';
        $extra = !empty($record['extra']) ? json_encode($record['extra'], JSON_UNESCAPED_UNICODE) : '';

        $parts = [
            "[{$record['timestamp']}]",
            "[{$record['channel']}]",
            "{$record['level']}:",
            $record['message'],
        ];

        if ($context) {
            $parts[] = "context: {$context}";
        }

        if ($extra) {
            $parts[] = "extra: {$extra}";
        }

        return implode(' ', $parts) . PHP_EOL;
    }

    /**
     * Interpolate context values into message
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Register default processors
     */
    private function registerDefaultProcessors(): void
    {
        // Add request info
        $this->addProcessor(function (array $record): array {
            if (php_sapi_name() !== 'cli') {
                $record['extra']['request_id'] = $_SERVER['HTTP_X_REQUEST_ID'] ?? substr(md5(uniqid('', true)), 0, 8);
                $record['extra']['method'] = $_SERVER['REQUEST_METHOD'] ?? '';
                $record['extra']['uri'] = $_SERVER['REQUEST_URI'] ?? '';
                $record['extra']['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            }
            return $record;
        });

        // Add memory usage
        $this->addProcessor(function (array $record): array {
            $record['extra']['memory_usage'] = round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB';
            return $record;
        });
    }

    /**
     * Get logs for a specific date
     */
    public function getLogs(string $date, ?string $level = null, int $limit = 100): array
    {
        $filename = "{$this->logPath}/{$this->channel}-{$date}.log";

        if (!file_exists($filename)) {
            return [];
        }

        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];

        foreach (array_reverse($lines) as $line) {
            if ($level && !str_contains($line, strtoupper($level) . ':')) {
                continue;
            }

            $logs[] = $this->parseLine($line);

            if (count($logs) >= $limit) {
                break;
            }
        }

        return $logs;
    }

    /**
     * Parse a log line into structured data
     */
    private function parseLine(string $line): array
    {
        $pattern = '/^\[([^\]]+)\]\s*\[([^\]]+)\]\s*(\w+):\s*(.+)$/';

        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'channel' => $matches[2],
                'level' => $matches[3],
                'message' => $matches[4],
            ];
        }

        return ['raw' => $line];
    }

    /**
     * Clean old log files
     */
    public function cleanOldLogs(int $daysToKeep = 30): int
    {
        $deleted = 0;
        $cutoff = strtotime("-{$daysToKeep} days");

        $files = glob("{$this->logPath}/*.log");
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}

/**
 * Specialized loggers for different purposes
 */
class ActivityLogger extends Logger
{
    private Database $db;

    public function __construct(Database $db)
    {
        parent::__construct('activity');
        $this->db = $db;
    }

    /**
     * Log user activity to database
     */
    public function logActivity(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void {
        $this->db->insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);

        // Also log to file
        $this->info("Activity: {$action}", [
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }
}

/**
 * Telegram-specific logger
 */
class TelegramLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('telegram');
    }

    public function logWebhook(array $update): void
    {
        $this->info('Webhook received', [
            'update_id' => $update['update_id'] ?? null,
            'type' => $this->getUpdateType($update),
        ]);
    }

    public function logPayment(string $reference, string $status, ?string $chargeId = null): void
    {
        $this->info("Payment {$status}", [
            'booking_reference' => $reference,
            'charge_id' => $chargeId,
        ]);
    }

    private function getUpdateType(array $update): string
    {
        $types = ['message', 'callback_query', 'pre_checkout_query', 'successful_payment'];
        foreach ($types as $type) {
            if (isset($update[$type])) {
                return $type;
            }
        }
        return 'unknown';
    }
}

/**
 * Booking-specific logger
 */
class BookingLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('booking');
    }

    public function logCreated(string $reference, int $userId, string $type, int $itemId): void
    {
        $this->info("Booking created: {$reference}", [
            'user_id' => $userId,
            'type' => $type,
            'item_id' => $itemId,
        ]);
    }

    public function logStatusChange(string $reference, string $oldStatus, string $newStatus): void
    {
        $this->info("Booking status changed: {$reference}", [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }

    public function logCancelled(string $reference, int $userId, ?string $reason = null): void
    {
        $this->warning("Booking cancelled: {$reference}", [
            'user_id' => $userId,
            'reason' => $reason,
        ]);
    }
}
