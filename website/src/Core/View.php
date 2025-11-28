<?php

declare(strict_types=1);

namespace Website\Core;

/**
 * Simple and fast template engine
 */
class View
{
    private string $templatesPath;
    private string $lang;
    private array $globals = [];
    private array $sections = [];
    private ?string $currentSection = null;
    private ?string $layout = null;
    private array $layoutData = [];

    public function __construct(string $templatesPath, string $lang = 'en')
    {
        $this->templatesPath = rtrim($templatesPath, '/');
        $this->lang = $lang;
    }

    /**
     * Set global variable available in all templates
     */
    public function setGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    /**
     * Render a template
     */
    public function render(string $template, array $data = []): string
    {
        $this->sections = [];
        $this->layout = null;
        $this->layoutData = [];

        // Merge globals with data
        $data = array_merge($this->globals, $data);

        // Render content
        $content = $this->renderTemplate($template, $data);

        // If layout is set, render it with content
        if ($this->layout) {
            $this->layoutData['content'] = $content;
            $this->layoutData = array_merge($data, $this->layoutData);
            $content = $this->renderTemplate($this->layout, $this->layoutData);
        }

        return $content;
    }

    /**
     * Render and output template
     */
    public function display(string $template, array $data = []): void
    {
        echo $this->render($template, $data);
    }

    /**
     * Set layout for current render
     */
    public function layout(string $layout, array $data = []): void
    {
        $this->layout = 'layouts/' . $layout;
        $this->layoutData = $data;
    }

    /**
     * Start a section
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End current section
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * Yield a section
     */
    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Include a partial
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->renderTemplate('partials/' . $template, array_merge($this->globals, $data));
    }

    /**
     * Escape HTML
     */
    public function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format price
     */
    public function price(float $amount, string $currency = 'THB'): string
    {
        $symbols = ['THB' => '฿', 'USD' => '$', 'EUR' => '€', 'RUB' => '₽'];
        $symbol = $symbols[$currency] ?? $currency;

        return $symbol . number_format($amount, 0, '.', ',');
    }

    /**
     * Format date
     */
    public function date(string $date, string $format = 'M j, Y'): string
    {
        return date($format, strtotime($date));
    }

    /**
     * Generate URL with language
     */
    public function url(string $path, array $params = []): string
    {
        $url = '/' . ltrim($path, '/');

        if ($this->lang !== 'en') {
            $params['lang'] = $this->lang;
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Get asset URL with cache busting
     */
    public function asset(string $path): string
    {
        $fullPath = $this->templatesPath . '/../public' . $path;
        $version = file_exists($fullPath) ? filemtime($fullPath) : time();

        return $path . '?v=' . $version;
    }

    /**
     * Check if current URL matches
     */
    public function isActive(string $path): bool
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return $current === $path || str_starts_with($current, $path . '/');
    }

    /**
     * Truncate text
     */
    public function truncate(string $text, int $length = 150): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }

    /**
     * Render template file
     */
    private function renderTemplate(string $template, array $data): string
    {
        $file = $this->templatesPath . '/' . $template . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Extract data to variables
        extract($data);

        // Make $view available in templates
        $view = $this;

        ob_start();
        include $file;
        return ob_get_clean();
    }
}
