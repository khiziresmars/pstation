<?php

declare(strict_types=1);

namespace Website\Core;

/**
 * Asset management with optimization
 */
class Assets
{
    private string $publicPath;
    private array $cssFiles = [];
    private array $jsFiles = [];
    private bool $minify = true;

    public function __construct(string $publicPath)
    {
        $this->publicPath = rtrim($publicPath, '/');
        $this->minify = ($_ENV['APP_DEBUG'] ?? 'false') !== 'true';
    }

    /**
     * Add CSS file
     */
    public function addCss(string $path): void
    {
        $this->cssFiles[] = $path;
    }

    /**
     * Add JS file
     */
    public function addJs(string $path): void
    {
        $this->jsFiles[] = $path;
    }

    /**
     * Get versioned asset URL
     */
    public function url(string $path): string
    {
        $fullPath = $this->publicPath . '/' . ltrim($path, '/');
        $version = file_exists($fullPath) ? filemtime($fullPath) : time();

        return '/' . ltrim($path, '/') . '?v=' . $version;
    }

    /**
     * Render CSS tags
     */
    public function renderCss(): string
    {
        $html = '';

        foreach ($this->cssFiles as $file) {
            $html .= '<link rel="stylesheet" href="' . $this->url($file) . '">' . "\n";
        }

        return $html;
    }

    /**
     * Render JS tags
     */
    public function renderJs(): string
    {
        $html = '';

        foreach ($this->jsFiles as $file) {
            $html .= '<script src="' . $this->url($file) . '" defer></script>' . "\n";
        }

        return $html;
    }

    /**
     * Generate critical CSS inline styles
     */
    public function criticalCss(): string
    {
        $criticalPath = $this->publicPath . '/css/critical.css';

        if (file_exists($criticalPath)) {
            return '<style>' . file_get_contents($criticalPath) . '</style>';
        }

        return '';
    }

    /**
     * Preload image
     */
    public function preloadImage(string $src): string
    {
        return '<link rel="preload" as="image" href="' . $src . '">';
    }

    /**
     * Generate responsive image srcset
     */
    public function imageSrcset(string $basePath, array $sizes = [320, 640, 1024, 1920]): string
    {
        $srcset = [];
        $ext = pathinfo($basePath, PATHINFO_EXTENSION);
        $base = str_replace('.' . $ext, '', $basePath);

        foreach ($sizes as $size) {
            $srcset[] = "{$base}-{$size}w.{$ext} {$size}w";
        }

        return implode(', ', $srcset);
    }

    /**
     * Generate picture element with WebP support
     */
    public function picture(
        string $src,
        string $alt,
        array $sizes = [],
        string $class = '',
        bool $lazy = true
    ): string {
        $ext = pathinfo($src, PATHINFO_EXTENSION);
        $base = str_replace('.' . $ext, '', $src);
        $webpSrc = $base . '.webp';

        $loadingAttr = $lazy ? 'loading="lazy"' : '';
        $classAttr = $class ? 'class="' . htmlspecialchars($class) . '"' : '';

        $html = '<picture>';

        // WebP source
        $html .= '<source type="image/webp" srcset="' . $webpSrc . '">';

        // Original format
        $html .= '<img src="' . $src . '" alt="' . htmlspecialchars($alt) . '" ' . $loadingAttr . ' ' . $classAttr . '>';

        $html .= '</picture>';

        return $html;
    }

    /**
     * Lazy load image with placeholder
     */
    public function lazyImage(string $src, string $alt, string $class = ''): string
    {
        $placeholder = '/images/placeholder.svg';

        return sprintf(
            '<img src="%s" data-src="%s" alt="%s" class="lazy %s" loading="lazy">',
            $placeholder,
            htmlspecialchars($src),
            htmlspecialchars($alt),
            htmlspecialchars($class)
        );
    }
}
