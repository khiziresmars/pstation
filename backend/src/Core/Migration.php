<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Database Migration System
 * Handles versioned database schema changes
 */
class Migration
{
    private Database $db;
    private Logger $logger;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(Database $db, ?Logger $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger ?? new Logger('migration');
        $this->migrationsPath = dirname(__DIR__, 2) . '/database/migrations';

        $this->ensureMigrationsTable();
    }

    /**
     * Run all pending migrations
     */
    public function migrate(): array
    {
        $results = [
            'migrated' => [],
            'errors' => [],
        ];

        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            $this->logger->info('No pending migrations');
            return $results;
        }

        foreach ($pending as $migration) {
            try {
                $this->runMigration($migration);
                $results['migrated'][] = $migration;
                $this->logger->info("Migrated: {$migration}");
            } catch (\Throwable $e) {
                $results['errors'][$migration] = $e->getMessage();
                $this->logger->error("Migration failed: {$migration}", [
                    'error' => $e->getMessage(),
                ]);
                break; // Stop on first error
            }
        }

        return $results;
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback(int $steps = 1): array
    {
        $results = [
            'rolledBack' => [],
            'errors' => [],
        ];

        $migrations = $this->db->query(
            "SELECT migration, batch FROM {$this->migrationsTable}
             ORDER BY batch DESC, id DESC LIMIT ?",
            [$steps]
        );

        foreach ($migrations as $row) {
            try {
                $this->runRollback($row['migration']);
                $results['rolledBack'][] = $row['migration'];
                $this->logger->info("Rolled back: {$row['migration']}");
            } catch (\Throwable $e) {
                $results['errors'][$row['migration']] = $e->getMessage();
                $this->logger->error("Rollback failed: {$row['migration']}", [
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        }

        return $results;
    }

    /**
     * Reset all migrations (rollback all, then migrate)
     */
    public function reset(): array
    {
        $this->logger->warning('Resetting all migrations');

        $migrations = $this->db->query(
            "SELECT migration FROM {$this->migrationsTable} ORDER BY batch DESC, id DESC"
        );

        foreach ($migrations as $row) {
            $this->runRollback($row['migration']);
        }

        return $this->migrate();
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $ran = $this->getRanMigrations();
        $all = $this->getAllMigrations();

        $status = [];
        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration,
                'status' => in_array($migration, $ran) ? 'Ran' : 'Pending',
                'batch' => $this->getBatch($migration),
            ];
        }

        return $status;
    }

    /**
     * Create a new migration file
     */
    public function create(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        $className = $this->getClassName($name);
        $filename = "{$timestamp}_{$name}.php";
        $filepath = "{$this->migrationsPath}/{$filename}";

        $template = $this->getMigrationTemplate($className);
        file_put_contents($filepath, $template);

        $this->logger->info("Created migration: {$filename}");

        return $filepath;
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        $ran = $this->getRanMigrations();
        $all = $this->getAllMigrations();

        return array_diff($all, $ran);
    }

    /**
     * Get all ran migrations
     */
    private function getRanMigrations(): array
    {
        $result = $this->db->query(
            "SELECT migration FROM {$this->migrationsTable} ORDER BY batch, id"
        );

        return array_column($result, 'migration');
    }

    /**
     * Get all available migrations
     */
    private function getAllMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return [];
        }

        $files = glob("{$this->migrationsPath}/*.php");
        $migrations = [];

        foreach ($files as $file) {
            $migrations[] = pathinfo($file, PATHINFO_FILENAME);
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Run a single migration
     */
    private function runMigration(string $migration): void
    {
        $class = $this->loadMigrationClass($migration);
        $instance = new $class($this->db);

        $this->db->beginTransaction();

        try {
            $instance->up();

            $batch = $this->getNextBatch();
            $this->db->insert($this->migrationsTable, [
                'migration' => $migration,
                'batch' => $batch,
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Run rollback for a single migration
     */
    private function runRollback(string $migration): void
    {
        $class = $this->loadMigrationClass($migration);
        $instance = new $class($this->db);

        $this->db->beginTransaction();

        try {
            $instance->down();

            $this->db->execute(
                "DELETE FROM {$this->migrationsTable} WHERE migration = ?",
                [$migration]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Load migration class from file
     */
    private function loadMigrationClass(string $migration): string
    {
        $filepath = "{$this->migrationsPath}/{$migration}.php";

        if (!file_exists($filepath)) {
            throw new \RuntimeException("Migration file not found: {$migration}");
        }

        require_once $filepath;

        $className = $this->getClassName($migration);

        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class not found: {$className}");
        }

        return $className;
    }

    /**
     * Get class name from migration name
     */
    private function getClassName(string $migration): string
    {
        // Remove timestamp prefix (YYYY_MM_DD_HHMMSS_)
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration);

        // Convert to PascalCase
        $parts = explode('_', $name);
        $className = implode('', array_map('ucfirst', $parts));

        return $className;
    }

    /**
     * Get next batch number
     */
    private function getNextBatch(): int
    {
        $result = $this->db->queryOne(
            "SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}"
        );

        return ($result['max_batch'] ?? 0) + 1;
    }

    /**
     * Get batch number for a migration
     */
    private function getBatch(string $migration): ?int
    {
        $result = $this->db->queryOne(
            "SELECT batch FROM {$this->migrationsTable} WHERE migration = ?",
            [$migration]
        );

        return $result['batch'] ?? null;
    }

    /**
     * Ensure migrations table exists
     */
    private function ensureMigrationsTable(): void
    {
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Get migration file template
     */
    private function getMigrationTemplate(string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use App\Core\Database;

class {$className}
{
    private Database \$db;

    public function __construct(Database \$db)
    {
        \$this->db = \$db;
    }

    /**
     * Run the migration
     */
    public function up(): void
    {
        \$this->db->execute("
            -- Add your migration SQL here
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        \$this->db->execute("
            -- Add your rollback SQL here
        ");
    }
}
PHP;
    }
}

/**
 * Schema Builder for fluent migration syntax
 */
class Schema
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new table
     */
    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = "CREATE TABLE {$table} (\n";
        $sql .= implode(",\n", $blueprint->getColumns());

        if ($blueprint->hasPrimaryKey()) {
            $sql .= ",\nPRIMARY KEY ({$blueprint->getPrimaryKey()})";
        }

        foreach ($blueprint->getIndexes() as $index) {
            $sql .= ",\n{$index}";
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $sql .= ",\n{$fk}";
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
    }

    /**
     * Modify an existing table
     */
    public function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);

        foreach ($blueprint->getStatements() as $sql) {
            $this->db->execute($sql);
        }
    }

    /**
     * Drop a table
     */
    public function drop(string $table): void
    {
        $this->db->execute("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Rename a table
     */
    public function rename(string $from, string $to): void
    {
        $this->db->execute("RENAME TABLE {$from} TO {$to}");
    }

    /**
     * Check if table exists
     */
    public function hasTable(string $table): bool
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?",
            [$table]
        );

        return $result['count'] > 0;
    }

    /**
     * Check if column exists
     */
    public function hasColumn(string $table, string $column): bool
    {
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            [$table, $column]
        );

        return $result['count'] > 0;
    }
}

/**
 * Table Blueprint for schema definitions
 */
class Blueprint
{
    private string $table;
    private bool $modifying;
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private array $statements = [];
    private ?string $primaryKey = null;

    public function __construct(string $table, bool $modifying = false)
    {
        $this->table = $table;
        $this->modifying = $modifying;
    }

    // Column types
    public function id(string $name = 'id'): self
    {
        return $this->addColumn("{$name} BIGINT UNSIGNED AUTO_INCREMENT", $name, true);
    }

    public function bigInteger(string $name): self
    {
        return $this->addColumn("{$name} BIGINT");
    }

    public function integer(string $name): self
    {
        return $this->addColumn("{$name} INT");
    }

    public function tinyInteger(string $name): self
    {
        return $this->addColumn("{$name} TINYINT");
    }

    public function smallInteger(string $name): self
    {
        return $this->addColumn("{$name} SMALLINT");
    }

    public function unsignedBigInteger(string $name): self
    {
        return $this->addColumn("{$name} BIGINT UNSIGNED");
    }

    public function unsignedInteger(string $name): self
    {
        return $this->addColumn("{$name} INT UNSIGNED");
    }

    public function string(string $name, int $length = 255): self
    {
        return $this->addColumn("{$name} VARCHAR({$length})");
    }

    public function text(string $name): self
    {
        return $this->addColumn("{$name} TEXT");
    }

    public function longText(string $name): self
    {
        return $this->addColumn("{$name} LONGTEXT");
    }

    public function boolean(string $name): self
    {
        return $this->addColumn("{$name} TINYINT(1)");
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): self
    {
        return $this->addColumn("{$name} DECIMAL({$precision},{$scale})");
    }

    public function float(string $name): self
    {
        return $this->addColumn("{$name} FLOAT");
    }

    public function date(string $name): self
    {
        return $this->addColumn("{$name} DATE");
    }

    public function datetime(string $name): self
    {
        return $this->addColumn("{$name} DATETIME");
    }

    public function timestamp(string $name): self
    {
        return $this->addColumn("{$name} TIMESTAMP");
    }

    public function time(string $name): self
    {
        return $this->addColumn("{$name} TIME");
    }

    public function json(string $name): self
    {
        return $this->addColumn("{$name} JSON");
    }

    public function enum(string $name, array $values): self
    {
        $vals = implode("','", $values);
        return $this->addColumn("{$name} ENUM('{$vals}')");
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->nullable()->onUpdate('CURRENT_TIMESTAMP');
        return $this;
    }

    public function softDeletes(): self
    {
        return $this->timestamp('deleted_at')->nullable();
    }

    // Modifiers
    public function nullable(): self
    {
        $this->modifyLast(' NULL');
        return $this;
    }

    public function notNull(): self
    {
        $this->modifyLast(' NOT NULL');
        return $this;
    }

    public function default(mixed $value): self
    {
        if ($value === null) {
            $this->modifyLast(' DEFAULT NULL');
        } elseif (is_string($value) && !in_array($value, ['CURRENT_TIMESTAMP', 'NULL'])) {
            $this->modifyLast(" DEFAULT '{$value}'");
        } else {
            $this->modifyLast(" DEFAULT {$value}");
        }
        return $this;
    }

    public function onUpdate(string $value): self
    {
        $this->modifyLast(" ON UPDATE {$value}");
        return $this;
    }

    public function unsigned(): self
    {
        $lastKey = array_key_last($this->columns);
        $this->columns[$lastKey] = str_replace(['INT', 'BIGINT'], ['INT UNSIGNED', 'BIGINT UNSIGNED'], $this->columns[$lastKey]);
        return $this;
    }

    public function after(string $column): self
    {
        $this->modifyLast(" AFTER {$column}");
        return $this;
    }

    public function first(): self
    {
        $this->modifyLast(' FIRST');
        return $this;
    }

    public function comment(string $comment): self
    {
        $this->modifyLast(" COMMENT '{$comment}'");
        return $this;
    }

    // Indexes
    public function primary(string|array $columns): self
    {
        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $this->primaryKey = $cols;
        return $this;
    }

    public function unique(string|array $columns, ?string $name = null): self
    {
        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $name = $name ?? $this->table . '_' . str_replace(', ', '_', $cols) . '_unique';
        $this->indexes[] = "UNIQUE KEY {$name} ({$cols})";
        return $this;
    }

    public function index(string|array $columns, ?string $name = null): self
    {
        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $name = $name ?? $this->table . '_' . str_replace(', ', '_', $cols) . '_index';
        $this->indexes[] = "KEY {$name} ({$cols})";
        return $this;
    }

    public function fulltext(string|array $columns, ?string $name = null): self
    {
        $cols = is_array($columns) ? implode(', ', $columns) : $columns;
        $name = $name ?? $this->table . '_' . str_replace(', ', '_', $cols) . '_fulltext';
        $this->indexes[] = "FULLTEXT KEY {$name} ({$cols})";
        return $this;
    }

    // Foreign keys
    public function foreign(string $column): ForeignKeyDefinition
    {
        return new ForeignKeyDefinition($this, $column);
    }

    public function addForeignKey(string $definition): void
    {
        $this->foreignKeys[] = $definition;
    }

    // Alter table operations
    public function dropColumn(string $column): self
    {
        $this->statements[] = "ALTER TABLE {$this->table} DROP COLUMN {$column}";
        return $this;
    }

    public function renameColumn(string $from, string $to): self
    {
        $this->statements[] = "ALTER TABLE {$this->table} RENAME COLUMN {$from} TO {$to}";
        return $this;
    }

    public function dropIndex(string $name): self
    {
        $this->statements[] = "ALTER TABLE {$this->table} DROP INDEX {$name}";
        return $this;
    }

    public function dropForeign(string $name): self
    {
        $this->statements[] = "ALTER TABLE {$this->table} DROP FOREIGN KEY {$name}";
        return $this;
    }

    // Internal methods
    private function addColumn(string $definition, ?string $primaryKey = null, bool $isPrimary = false): self
    {
        if ($this->modifying) {
            $this->statements[] = "ALTER TABLE {$this->table} ADD COLUMN {$definition}";
        } else {
            $this->columns[] = $definition;
        }

        if ($isPrimary && $primaryKey) {
            $this->primaryKey = $primaryKey;
        }

        return $this;
    }

    private function modifyLast(string $modifier): void
    {
        if ($this->modifying) {
            $lastKey = array_key_last($this->statements);
            if ($lastKey !== null) {
                $this->statements[$lastKey] .= $modifier;
            }
        } else {
            $lastKey = array_key_last($this->columns);
            if ($lastKey !== null) {
                $this->columns[$lastKey] .= $modifier;
            }
        }
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getStatements(): array
    {
        return $this->statements;
    }

    public function hasPrimaryKey(): bool
    {
        return $this->primaryKey !== null;
    }

    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }
}

/**
 * Foreign Key Definition Builder
 */
class ForeignKeyDefinition
{
    private Blueprint $blueprint;
    private string $column;
    private string $references;
    private string $on;
    private string $onDelete = 'RESTRICT';
    private string $onUpdate = 'RESTRICT';

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->on = $table;
        $this->build();
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        $this->build();
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        $this->build();
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }

    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate('CASCADE');
    }

    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }

    private function build(): void
    {
        $name = "fk_{$this->column}";
        $definition = "CONSTRAINT {$name} FOREIGN KEY ({$this->column}) " .
            "REFERENCES {$this->on}({$this->references}) " .
            "ON DELETE {$this->onDelete} ON UPDATE {$this->onUpdate}";

        $this->blueprint->addForeignKey($definition);
    }
}
