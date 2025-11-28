<?php

declare(strict_types=1);

namespace Website\Controllers;

/**
 * SEO controller for sitemap and robots.txt
 */
class SeoController extends BaseController
{
    /**
     * Generate XML sitemap
     */
    public function sitemap(array $params): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $baseUrl = $_ENV['APP_URL'] ?? 'https://phuket-yachts.com';

        // Get all vessels
        $vessels = $this->db->query("SELECT slug, updated_at FROM vessels WHERE is_active = 1");

        // Get all tours
        $tours = $this->db->query("SELECT slug, updated_at FROM tours WHERE is_active = 1");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Static pages
        $staticPages = [
            ['/', 'daily', '1.0'],
            ['/yachts', 'daily', '0.9'],
            ['/tours', 'daily', '0.9'],
            ['/about', 'monthly', '0.5'],
            ['/contact', 'monthly', '0.5'],
            ['/faq', 'monthly', '0.5'],
        ];

        foreach ($staticPages as [$path, $freq, $priority]) {
            $xml .= $this->urlEntry($baseUrl . $path, date('Y-m-d'), $freq, $priority);
        }

        // Vessels
        foreach ($vessels as $vessel) {
            $xml .= $this->urlEntry(
                $baseUrl . '/yachts/' . $vessel['slug'],
                substr($vessel['updated_at'], 0, 10),
                'weekly',
                '0.8'
            );
        }

        // Tours
        foreach ($tours as $tour) {
            $xml .= $this->urlEntry(
                $baseUrl . '/tours/' . $tour['slug'],
                substr($tour['updated_at'], 0, 10),
                'weekly',
                '0.8'
            );
        }

        $xml .= '</urlset>';

        echo $xml;
        exit;
    }

    /**
     * Generate URL entry
     */
    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return <<<XML
  <url>
    <loc>{$loc}</loc>
    <lastmod>{$lastmod}</lastmod>
    <changefreq>{$changefreq}</changefreq>
    <priority>{$priority}</priority>
  </url>

XML;
    }

    /**
     * Generate robots.txt
     */
    public function robots(array $params): void
    {
        header('Content-Type: text/plain');

        $baseUrl = $_ENV['APP_URL'] ?? 'https://phuket-yachts.com';

        echo <<<TXT
User-agent: *
Allow: /

# Disallow admin and API paths
Disallow: /api/
Disallow: /admin/

# Sitemap location
Sitemap: {$baseUrl}/sitemap.xml

# Crawl delay (optional)
Crawl-delay: 1
TXT;

        exit;
    }
}
