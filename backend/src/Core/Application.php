<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Main Application class
 * Handles dependency injection and configuration
 */
class Application
{
    private static ?Application $instance = null;
    private array $config;
    private ?Database $database = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        self::$instance = $this;
    }

    public static function getInstance(): ?Application
    {
        return self::$instance;
    }

    public function getConfig(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function getDatabase(): Database
    {
        if ($this->database === null) {
            $dbConfig = require BASE_PATH . '/config/database.php';
            $this->database = new Database($dbConfig);
        }

        return $this->database;
    }

    public function isDebug(): bool
    {
        return $this->config['debug'] ?? false;
    }
}
