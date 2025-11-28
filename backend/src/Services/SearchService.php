<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Application;

/**
 * Search Service
 * Full-text search for vessels and tours
 */
class SearchService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Search vessels and tours
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [
                'vessels' => [],
                'tours' => [],
                'total' => 0,
            ];
        }

        // Search vessels
        $vessels = $this->searchVessels($query, $limit);

        // Search tours
        $tours = $this->searchTours($query, $limit);

        return [
            'vessels' => $vessels,
            'tours' => $tours,
            'total' => count($vessels) + count($tours),
        ];
    }

    /**
     * Search vessels
     */
    public function searchVessels(string $query, int $limit = 10): array
    {
        $searchTerm = '%' . $this->prepareSearchTerm($query) . '%';

        $sql = "
            SELECT
                v.*,
                COALESCE(AVG(r.rating), 0) as rating,
                COUNT(DISTINCT r.id) as review_count
            FROM vessels v
            LEFT JOIN reviews r ON r.vessel_id = v.id AND r.status = 'approved'
            WHERE v.is_active = 1
              AND (
                v.name LIKE ?
                OR v.description LIKE ?
                OR v.type LIKE ?
                OR v.location LIKE ?
                OR JSON_SEARCH(v.amenities, 'one', ?) IS NOT NULL
              )
            GROUP BY v.id
            ORDER BY
                CASE
                    WHEN v.name LIKE ? THEN 1
                    WHEN v.type LIKE ? THEN 2
                    ELSE 3
                END,
                v.is_featured DESC,
                rating DESC
            LIMIT ?
        ";

        $exactTerm = '%' . $query . '%';

        $vessels = $this->db->query($sql, [
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $query,
            $exactTerm,
            $exactTerm,
            $limit,
        ]);

        // Process images
        foreach ($vessels as &$vessel) {
            $vessel['images'] = json_decode($vessel['images'] ?? '[]', true);
            $vessel['amenities'] = json_decode($vessel['amenities'] ?? '[]', true);
            $vessel['rating'] = round((float) $vessel['rating'], 1);
        }

        return $vessels;
    }

    /**
     * Search tours
     */
    public function searchTours(string $query, int $limit = 10): array
    {
        $searchTerm = '%' . $this->prepareSearchTerm($query) . '%';

        $sql = "
            SELECT
                t.*,
                COALESCE(AVG(r.rating), 0) as rating,
                COUNT(DISTINCT r.id) as review_count
            FROM tours t
            LEFT JOIN reviews r ON r.tour_id = t.id AND r.status = 'approved'
            WHERE t.is_active = 1
              AND (
                t.name LIKE ?
                OR t.description LIKE ?
                OR t.category LIKE ?
                OR t.destination LIKE ?
                OR JSON_SEARCH(t.highlights, 'one', ?) IS NOT NULL
              )
            GROUP BY t.id
            ORDER BY
                CASE
                    WHEN t.name LIKE ? THEN 1
                    WHEN t.category LIKE ? THEN 2
                    WHEN t.destination LIKE ? THEN 3
                    ELSE 4
                END,
                t.is_featured DESC,
                rating DESC
            LIMIT ?
        ";

        $exactTerm = '%' . $query . '%';

        $tours = $this->db->query($sql, [
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $query,
            $exactTerm,
            $exactTerm,
            $exactTerm,
            $limit,
        ]);

        // Process JSON fields
        foreach ($tours as &$tour) {
            $tour['images'] = json_decode($tour['images'] ?? '[]', true);
            $tour['highlights'] = json_decode($tour['highlights'] ?? '[]', true);
            $tour['includes'] = json_decode($tour['includes'] ?? '[]', true);
            $tour['rating'] = round((float) $tour['rating'], 1);
        }

        return $tours;
    }

    /**
     * Get search suggestions (autocomplete)
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $searchTerm = $query . '%';
        $suggestions = [];

        // Vessel names
        $vesselNames = $this->db->query("
            SELECT DISTINCT name
            FROM vessels
            WHERE is_active = 1 AND name LIKE ?
            LIMIT ?
        ", [$searchTerm, $limit]);

        foreach ($vesselNames as $v) {
            $suggestions[] = ['type' => 'vessel', 'text' => $v['name']];
        }

        // Tour names
        $tourNames = $this->db->query("
            SELECT DISTINCT name
            FROM tours
            WHERE is_active = 1 AND name LIKE ?
            LIMIT ?
        ", [$searchTerm, $limit]);

        foreach ($tourNames as $t) {
            $suggestions[] = ['type' => 'tour', 'text' => $t['name']];
        }

        // Categories/destinations
        $destinations = $this->db->query("
            SELECT DISTINCT destination
            FROM tours
            WHERE is_active = 1 AND destination LIKE ?
            LIMIT ?
        ", [$searchTerm, $limit]);

        foreach ($destinations as $d) {
            if ($d['destination']) {
                $suggestions[] = ['type' => 'destination', 'text' => $d['destination']];
            }
        }

        // Remove duplicates and limit
        $unique = [];
        $seen = [];
        foreach ($suggestions as $s) {
            $key = strtolower($s['text']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $s;
            }
        }

        return array_slice($unique, 0, $limit);
    }

    /**
     * Get popular searches
     */
    public function getPopularSearches(int $limit = 10): array
    {
        // Could be from search_log table or predefined
        return [
            'Phi Phi Island',
            'Luxury Yacht',
            'James Bond Island',
            'Speedboat',
            'Sunset Cruise',
            'Similan Islands',
            'Catamaran',
            'Private Charter',
            'Snorkeling Tour',
            'Fishing Trip',
        ];
    }

    /**
     * Log search query for analytics
     */
    public function logSearch(string $query, int $resultsCount, ?int $userId = null): void
    {
        try {
            $this->db->insert('search_logs', [
                'query' => substr($query, 0, 255),
                'results_count' => $resultsCount,
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (\Exception $e) {
            // Silently fail - logging should not break search
            error_log('Failed to log search: ' . $e->getMessage());
        }
    }

    /**
     * Prepare search term (escape special characters)
     */
    private function prepareSearchTerm(string $query): string
    {
        // Escape MySQL LIKE special characters
        $query = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        // Remove multiple spaces
        $query = preg_replace('/\s+/', ' ', $query);

        return $query;
    }
}
