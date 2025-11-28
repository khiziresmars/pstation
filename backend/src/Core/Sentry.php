<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Sentry Error Tracking Integration
 * Lightweight implementation without SDK
 */
class Sentry
{
    private static ?Sentry $instance = null;
    private bool $enabled;
    private string $dsn;
    private string $environment;
    private string $release;
    private Logger $logger;

    private function __construct()
    {
        $this->dsn = $_ENV['SENTRY_DSN'] ?? '';
        $this->enabled = !empty($this->dsn);
        $this->environment = $_ENV['APP_ENV'] ?? 'production';
        $this->release = $_ENV['APP_VERSION'] ?? '1.0.0';
        $this->logger = new Logger('sentry');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize Sentry and register error handlers
     */
    public function init(): void
    {
        if (!$this->enabled) {
            return;
        }

        set_exception_handler([$this, 'captureException']);
        set_error_handler([$this, 'captureError']);
        register_shutdown_function([$this, 'captureShutdown']);
    }

    /**
     * Capture exception
     */
    public function captureException(\Throwable $exception, array $context = []): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $event = $this->buildEvent('exception', [
            'exception' => [
                'values' => [[
                    'type' => get_class($exception),
                    'value' => $exception->getMessage(),
                    'stacktrace' => $this->buildStacktrace($exception),
                ]],
            ],
        ], $context);

        return $this->send($event);
    }

    /**
     * Capture error
     */
    public function captureError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        $this->captureException($exception);

        return false; // Continue with normal error handling
    }

    /**
     * Capture fatal errors on shutdown
     */
    public function captureShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $exception = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            $this->captureException($exception);
        }
    }

    /**
     * Capture message
     */
    public function captureMessage(string $message, string $level = 'info', array $context = []): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $event = $this->buildEvent('message', [
            'message' => [
                'formatted' => $message,
            ],
            'level' => $level,
        ], $context);

        return $this->send($event);
    }

    /**
     * Add breadcrumb
     */
    public function addBreadcrumb(string $message, string $category = 'default', string $level = 'info', array $data = []): void
    {
        // Store in session/memory for next event
        $_SESSION['sentry_breadcrumbs'][] = [
            'timestamp' => microtime(true),
            'category' => $category,
            'message' => $message,
            'level' => $level,
            'data' => $data,
        ];

        // Keep only last 50 breadcrumbs
        if (count($_SESSION['sentry_breadcrumbs'] ?? []) > 50) {
            array_shift($_SESSION['sentry_breadcrumbs']);
        }
    }

    /**
     * Set user context
     */
    public function setUser(?int $id = null, ?string $email = null, ?string $username = null, array $extra = []): void
    {
        $_SESSION['sentry_user'] = array_filter([
            'id' => $id,
            'email' => $email,
            'username' => $username,
            ...$extra,
        ]);
    }

    /**
     * Set extra context
     */
    public function setExtra(string $key, mixed $value): void
    {
        $_SESSION['sentry_extra'][$key] = $value;
    }

    /**
     * Set tag
     */
    public function setTag(string $key, string $value): void
    {
        $_SESSION['sentry_tags'][$key] = $value;
    }

    /**
     * Build event payload
     */
    private function buildEvent(string $type, array $data, array $context = []): array
    {
        $event = [
            'event_id' => str_replace('-', '', $this->generateUuid()),
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'platform' => 'php',
            'level' => $data['level'] ?? 'error',
            'logger' => 'php',
            'server_name' => gethostname(),
            'release' => $this->release,
            'environment' => $this->environment,
            'contexts' => [
                'os' => [
                    'name' => PHP_OS,
                    'version' => php_uname('r'),
                ],
                'runtime' => [
                    'name' => 'php',
                    'version' => PHP_VERSION,
                ],
            ],
            'request' => $this->buildRequest(),
            'user' => $_SESSION['sentry_user'] ?? null,
            'breadcrumbs' => $_SESSION['sentry_breadcrumbs'] ?? [],
            'extra' => array_merge($_SESSION['sentry_extra'] ?? [], $context),
            'tags' => $_SESSION['sentry_tags'] ?? [],
        ];

        return array_merge($event, $data);
    }

    /**
     * Build request context
     */
    private function buildRequest(): array
    {
        if (php_sapi_name() === 'cli') {
            return ['cli' => true];
        }

        return [
            'url' => ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http'
                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . ($_SERVER['REQUEST_URI'] ?? '/'),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'headers' => $this->getHeaders(),
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'data' => $_POST ?: null,
            'env' => [
                'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            ],
        ];
    }

    /**
     * Build stacktrace from exception
     */
    private function buildStacktrace(\Throwable $exception): array
    {
        $frames = [];

        foreach ($exception->getTrace() as $frame) {
            $frames[] = [
                'filename' => $frame['file'] ?? 'unknown',
                'lineno' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '',
                'module' => $frame['class'] ?? '',
            ];
        }

        // Add the exception location
        array_unshift($frames, [
            'filename' => $exception->getFile(),
            'lineno' => $exception->getLine(),
            'function' => '',
        ]);

        return ['frames' => array_reverse($frames)];
    }

    /**
     * Get request headers
     */
    private function getHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                // Filter sensitive headers
                if (!in_array($name, ['AUTHORIZATION', 'COOKIE', 'X-AUTH-TOKEN'])) {
                    $headers[$name] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Send event to Sentry
     */
    private function send(array $event): ?string
    {
        try {
            $parsed = parse_url($this->dsn);
            if (!$parsed) {
                return null;
            }

            $key = $parsed['user'] ?? '';
            $projectId = ltrim($parsed['path'] ?? '', '/');
            $host = $parsed['host'] ?? '';
            $scheme = $parsed['scheme'] ?? 'https';

            $url = "{$scheme}://{$host}/api/{$projectId}/store/";

            $headers = [
                'Content-Type: application/json',
                "X-Sentry-Auth: Sentry sentry_version=7, sentry_client=php/1.0, sentry_key={$key}",
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($event),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $result = json_decode($response, true);
                $this->logger->debug('Event sent to Sentry', ['id' => $result['id'] ?? null]);
                return $result['id'] ?? $event['event_id'];
            }

            $this->logger->warning('Failed to send to Sentry', [
                'http_code' => $httpCode,
                'response' => $response,
            ]);

            return null;

        } catch (\Throwable $e) {
            $this->logger->error('Sentry send error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate UUID v4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
