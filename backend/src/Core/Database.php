<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database connection and query helper
 */
class Database
{
    private PDO $pdo;
    private static ?Database $instance = null;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }

        self::$instance = $this;
    }

    public static function getInstance(): ?Database
    {
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return all results
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return single result
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a query and return affected rows count
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert a row and return the last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update rows and return affected count
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...array_values($data), ...$whereParams]);

        return $stmt->rowCount();
    }

    /**
     * Delete rows and return affected count
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get the last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Quote a value for safe use in queries
     */
    public function quote(string $value): string
    {
        return $this->pdo->quote($value);
    }
}
