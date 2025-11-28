<?php

declare(strict_types=1);

namespace App\Core;

/**
 * File-based Cache System
 * Simple and efficient caching without Redis
 */
class Cache
{
    private static ?Cache $instance = null;
    private string $cachePath;
    private bool $enabled;
    private int $defaultTtl;

    private function __construct()
    {
        $this->cachePath = dirname(__DIR__, 2) . '/storage/cache/data';
        $this->enabled = (bool) ($_ENV['CACHE_ENABLED'] ?? true);
        $this->defaultTtl = (int) ($_ENV['CACHE_TTL'] ?? 3600);

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get cached value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->enabled) {
            return $default;
        }

        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data === false) {
            $this->delete($key);
            return $default;
        }

        // Check expiration
        if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Set cached value
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $file = $this->getFilePath($key);

        $data = [
            'value' => $value,
            'created_at' => time(),
            'expires_at' => $ttl > 0 ? time() + $ttl : null,
        ];

        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    /**
     * Check if key exists and not expired
     */
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * Delete cached value
     */
    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Delete multiple keys by pattern
     */
    public function deletePattern(string $pattern): int
    {
        $deleted = 0;
        $files = glob($this->cachePath . '/*.cache');

        foreach ($files as $file) {
            $key = basename($file, '.cache');
            if (fnmatch($pattern, $key) || fnmatch($pattern, $this->unhashKey($key))) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Clear all cache
     */
    public function clear(): int
    {
        $deleted = 0;
        $files = glob($this->cachePath . '/*.cache');

        foreach ($files as $file) {
            unlink($file);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Get or set cached value
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Get or set cached value forever
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, 0, $callback);
    }

    /**
     * Increment value
     */
    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    /**
     * Decrement value
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    /**
     * Clean expired cache files
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $files = glob($this->cachePath . '/*.cache');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = @unserialize($content);

            if ($data === false) {
                unlink($file);
                $deleted++;
                continue;
            }

            if ($data['expires_at'] !== null && $data['expires_at'] < time()) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get cache statistics
     */
    public function stats(): array
    {
        $files = glob($this->cachePath . '/*.cache');
        $totalSize = 0;
        $expired = 0;
        $valid = 0;

        foreach ($files as $file) {
            $totalSize += filesize($file);

            $content = file_get_contents($file);
            $data = @unserialize($content);

            if ($data === false) {
                $expired++;
            } elseif ($data['expires_at'] !== null && $data['expires_at'] < time()) {
                $expired++;
            } else {
                $valid++;
            }
        }

        return [
            'total_files' => count($files),
            'valid' => $valid,
            'expired' => $expired,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Get file path for cache key
     */
    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->cachePath . '/' . $hash . '.cache';
    }

    /**
     * Try to get original key from hash (for pattern matching)
     */
    private function unhashKey(string $hash): string
    {
        // Store key mappings if needed
        return $hash;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Create tagged cache group
     */
    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this, $tags);
    }
}

/**
 * Tagged Cache for grouped invalidation
 */
class TaggedCache
{
    private Cache $cache;
    private array $tags;

    public function __construct(Cache $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags = $tags;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($this->taggedKey($key), $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $taggedKey = $this->taggedKey($key);

        // Store key in tag index
        foreach ($this->tags as $tag) {
            $tagKeys = $this->cache->get("tag:{$tag}", []);
            if (!in_array($taggedKey, $tagKeys)) {
                $tagKeys[] = $taggedKey;
                $this->cache->set("tag:{$tag}", $tagKeys, 0);
            }
        }

        return $this->cache->set($taggedKey, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($this->taggedKey($key));
    }

    public function flush(): int
    {
        $deleted = 0;

        foreach ($this->tags as $tag) {
            $tagKeys = $this->cache->get("tag:{$tag}", []);
            foreach ($tagKeys as $key) {
                if ($this->cache->delete($key)) {
                    $deleted++;
                }
            }
            $this->cache->delete("tag:{$tag}");
        }

        return $deleted;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    private function taggedKey(string $key): string
    {
        return implode(':', $this->tags) . ':' . $key;
    }
}
