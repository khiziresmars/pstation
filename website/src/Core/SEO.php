<?php

declare(strict_types=1);

namespace Website\Core;

/**
 * SEO helper for meta tags and structured data
 */
class SEO
{
    private string $title = '';
    private string $description = '';
    private string $keywords = '';
    private string $canonicalUrl = '';
    private string $image = '';
    private string $type = 'website';
    private array $alternates = [];
    private array $structuredData = [];

    private string $siteName = 'Phuket Yacht & Tours';
    private string $defaultImage = '/images/og-default.jpg';
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = ($_ENV['APP_URL'] ?? 'https://phuket-yachts.com');
    }

    /**
     * Set page title
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set meta description
     */
    public function description(string $description): self
    {
        $this->description = mb_substr(strip_tags($description), 0, 160);
        return $this;
    }

    /**
     * Set keywords
     */
    public function keywords(string $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Set canonical URL
     */
    public function canonical(string $url): self
    {
        $this->canonicalUrl = str_starts_with($url, 'http') ? $url : $this->baseUrl . $url;
        return $this;
    }

    /**
     * Set OG image
     */
    public function image(string $image): self
    {
        $this->image = str_starts_with($image, 'http') ? $image : $this->baseUrl . $image;
        return $this;
    }

    /**
     * Set page type
     */
    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Add alternate language URL
     */
    public function alternate(string $lang, string $url): self
    {
        $this->alternates[$lang] = str_starts_with($url, 'http') ? $url : $this->baseUrl . $url;
        return $this;
    }

    /**
     * Add structured data (JSON-LD)
     */
    public function structuredData(array $data): self
    {
        $this->structuredData[] = $data;
        return $this;
    }

    /**
     * Add LocalBusiness schema
     */
    public function localBusiness(): self
    {
        $this->structuredData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $this->siteName,
            'image' => $this->baseUrl . $this->defaultImage,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Chalong Bay',
                'addressLocality' => 'Phuket',
                'addressCountry' => 'TH',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => 7.8366,
                'longitude' => 98.3628,
            ],
            'url' => $this->baseUrl,
            'telephone' => '+66-XX-XXX-XXXX',
            'priceRange' => '฿฿฿',
        ];

        return $this;
    }

    /**
     * Add Product schema for vessel/tour
     */
    public function product(array $item): self
    {
        $this->structuredData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $item['name'],
            'description' => $item['description'] ?? '',
            'image' => $item['image'] ?? $this->baseUrl . $this->defaultImage,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'THB',
                'price' => $item['price'],
                'availability' => 'https://schema.org/InStock',
            ],
            'aggregateRating' => isset($item['rating']) ? [
                '@type' => 'AggregateRating',
                'ratingValue' => $item['rating'],
                'reviewCount' => $item['review_count'] ?? 0,
            ] : null,
        ];

        return $this;
    }

    /**
     * Add BreadcrumbList schema
     */
    public function breadcrumbs(array $items): self
    {
        $list = [];
        $position = 1;

        foreach ($items as $name => $url) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => str_starts_with($url, 'http') ? $url : $this->baseUrl . $url,
            ];
        }

        $this->structuredData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];

        return $this;
    }

    /**
     * Render all meta tags
     */
    public function render(): string
    {
        $html = '';
        $fullTitle = $this->title ? $this->title . ' | ' . $this->siteName : $this->siteName;
        $image = $this->image ?: $this->baseUrl . $this->defaultImage;

        // Basic meta tags
        $html .= '<title>' . htmlspecialchars($fullTitle) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . htmlspecialchars($this->description) . '">' . "\n";

        if ($this->keywords) {
            $html .= '<meta name="keywords" content="' . htmlspecialchars($this->keywords) . '">' . "\n";
        }

        // Canonical
        if ($this->canonicalUrl) {
            $html .= '<link rel="canonical" href="' . htmlspecialchars($this->canonicalUrl) . '">' . "\n";
        }

        // Open Graph
        $html .= '<meta property="og:type" content="' . $this->type . '">' . "\n";
        $html .= '<meta property="og:title" content="' . htmlspecialchars($this->title ?: $this->siteName) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . htmlspecialchars($this->description) . '">' . "\n";
        $html .= '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
        $html .= '<meta property="og:site_name" content="' . htmlspecialchars($this->siteName) . '">' . "\n";

        if ($this->canonicalUrl) {
            $html .= '<meta property="og:url" content="' . htmlspecialchars($this->canonicalUrl) . '">' . "\n";
        }

        // Twitter Card
        $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($this->title ?: $this->siteName) . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($this->description) . '">' . "\n";
        $html .= '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";

        // Alternates (hreflang)
        foreach ($this->alternates as $lang => $url) {
            $html .= '<link rel="alternate" hreflang="' . $lang . '" href="' . htmlspecialchars($url) . '">' . "\n";
        }

        // Structured Data (JSON-LD)
        foreach ($this->structuredData as $data) {
            $data = array_filter($data, fn($v) => $v !== null);
            $html .= '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }

        return $html;
    }

    /**
     * Get just the title
     */
    public function getTitle(): string
    {
        return $this->title ? $this->title . ' | ' . $this->siteName : $this->siteName;
    }
}
