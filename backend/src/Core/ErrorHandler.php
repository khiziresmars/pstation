<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Centralized Error Handler
 * Handles exceptions and errors consistently
 */
class ErrorHandler
{
    private static ?ErrorHandler $instance = null;
    private bool $debug = false;
    private ?Logger $logger = null;

    private function __construct()
    {
        $this->debug = (bool) ($_ENV['APP_DEBUG'] ?? false);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set logger instance
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Register error and exception handlers
     */
    public function register(): void
    {
        error_reporting(E_ALL);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        $this->handleException($exception);

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException(\Throwable $e): void
    {
        $statusCode = $this->getStatusCode($e);
        $errorCode = $this->getErrorCode($e);

        // Log the error
        $this->logException($e);

        // Send JSON response
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $this->getMessage($e),
            ],
        ];

        if ($this->debug) {
            $response['error']['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->getSimplifiedTrace($e),
            ];
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Handle fatal errors on shutdown
     */
    public function handleShutdown(): void
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
            $this->handleException($exception);
        }
    }

    /**
     * Get HTTP status code for exception
     */
    private function getStatusCode(\Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof NotFoundException) {
            return 404;
        }

        if ($e instanceof RateLimitException) {
            return 429;
        }

        return 500;
    }

    /**
     * Get error code for exception
     */
    private function getErrorCode(\Throwable $e): string
    {
        if ($e instanceof AppException) {
            return $e->getErrorCode();
        }

        return match (true) {
            $e instanceof \PDOException => 'DATABASE_ERROR',
            $e instanceof \InvalidArgumentException => 'INVALID_ARGUMENT',
            $e instanceof \RuntimeException => 'RUNTIME_ERROR',
            default => 'INTERNAL_ERROR',
        };
    }

    /**
     * Get user-friendly error message
     */
    private function getMessage(\Throwable $e): string
    {
        if ($e instanceof AppException) {
            return $e->getMessage();
        }

        if ($this->debug) {
            return $e->getMessage();
        }

        // Hide sensitive details in production
        if ($e instanceof \PDOException) {
            return 'A database error occurred. Please try again later.';
        }

        return 'An unexpected error occurred. Please try again later.';
    }

    /**
     * Log exception
     */
    private function logException(\Throwable $e): void
    {
        $context = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ],
        ];

        if ($this->logger) {
            $level = $this->getStatusCode($e) >= 500 ? 'error' : 'warning';
            $this->logger->log($level, "Unhandled exception: {$e->getMessage()}", $context);
        } else {
            // Fallback to error_log
            error_log(json_encode($context));
        }
    }

    /**
     * Get simplified stack trace
     */
    private function getSimplifiedTrace(\Throwable $e): array
    {
        $trace = [];
        foreach ($e->getTrace() as $frame) {
            $trace[] = [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
            ];

            if (count($trace) >= 10) {
                break;
            }
        }
        return $trace;
    }
}

// ==================== Custom Exception Classes ====================

/**
 * Base application exception
 */
class AppException extends \Exception
{
    protected string $errorCode = 'APP_ERROR';

    public function __construct(string $message, string $errorCode = 'APP_ERROR', int $code = 0, ?\Throwable $previous = null)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

/**
 * HTTP exception with status code
 */
class HttpException extends AppException
{
    protected int $statusCode = 500;

    public function __construct(int $statusCode, string $message, string $errorCode = 'HTTP_ERROR', ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $errorCode, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

/**
 * Validation exception
 */
class ValidationException extends AppException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message, 'VALIDATION_ERROR', 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/**
 * Authentication exception
 */
class AuthenticationException extends AppException
{
    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct($message, 'AUTHENTICATION_ERROR', 401);
    }
}

/**
 * Authorization exception
 */
class AuthorizationException extends AppException
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct($message, 'AUTHORIZATION_ERROR', 403);
    }
}

/**
 * Not found exception
 */
class NotFoundException extends AppException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 'NOT_FOUND', 404);
    }
}

/**
 * Rate limit exception
 */
class RateLimitException extends AppException
{
    private int $retryAfter;

    public function __construct(int $retryAfter = 60, string $message = 'Too many requests')
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, 'RATE_LIMIT_EXCEEDED', 429);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

/**
 * Business logic exception
 */
class BusinessException extends AppException
{
    public function __construct(string $message, string $errorCode = 'BUSINESS_ERROR')
    {
        parent::__construct($message, $errorCode, 400);
    }
}
