<?php

declare(strict_types=1);

namespace Website\Controllers;

use Website\Core\View;
use Website\Core\SEO;
use App\Core\Application;
use App\Core\Database;

/**
 * Base controller for website
 */
abstract class BaseController
{
    protected View $view;
    protected SEO $seo;
    protected Database $db;

    public function __construct(View $view)
    {
        $this->view = $view;
        $this->seo = new SEO();
        $this->db = Application::getInstance()->getDatabase();

        // Set common view data
        $this->view->setGlobal('seo', $this->seo);
    }

    /**
     * Render template with layout
     */
    protected function render(string $template, array $data = []): void
    {
        // Add SEO to data
        $data['seo'] = $this->seo;

        // Use main layout
        $this->view->layout('main', ['seo' => $this->seo]);
        $this->view->display($template, $data);
    }

    /**
     * JSON response
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect
     */
    protected function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    /**
     * Get POST data
     */
    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->post('_token');
        return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Generate CSRF token
     */
    protected function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Cache output
     */
    protected function cache(string $key, int $ttl, callable $callback): mixed
    {
        $cacheDir = WEBSITE_PATH . '/cache';
        $cacheFile = $cacheDir . '/' . md5($key) . '.cache';

        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));
            if ($data['expires'] > time()) {
                return $data['content'];
            }
        }

        $content = $callback();

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cacheFile, serialize([
            'expires' => time() + $ttl,
            'content' => $content,
        ]));

        return $content;
    }
}
