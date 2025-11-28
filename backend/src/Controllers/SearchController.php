<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Services\SearchService;

/**
 * Search Controller
 * Handles search API endpoints
 */
class SearchController
{
    private SearchService $searchService;

    public function __construct()
    {
        $this->searchService = new SearchService();
    }

    /**
     * Main search endpoint
     * GET /api/search?q=query&limit=20
     */
    public function search(): void
    {
        $query = Request::get('q', '');
        $limit = min((int) Request::get('limit', 20), 50); // Max 50

        if (strlen($query) < 2) {
            Response::json([
                'success' => true,
                'data' => [
                    'vessels' => [],
                    'tours' => [],
                    'total' => 0,
                ],
            ]);
            return;
        }

        $results = $this->searchService->search($query, $limit);

        // Log search for analytics
        $this->searchService->logSearch($query, $results['total']);

        Response::json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Search suggestions (autocomplete)
     * GET /api/search/suggestions?q=query
     */
    public function suggestions(): void
    {
        $query = Request::get('q', '');
        $limit = min((int) Request::get('limit', 5), 10);

        $suggestions = $this->searchService->getSuggestions($query, $limit);

        Response::json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Popular searches
     * GET /api/search/popular
     */
    public function popular(): void
    {
        $limit = min((int) Request::get('limit', 10), 20);

        $popular = $this->searchService->getPopularSearches($limit);

        Response::json([
            'success' => true,
            'data' => $popular,
        ]);
    }

    /**
     * Search vessels only
     * GET /api/search/vessels?q=query
     */
    public function vessels(): void
    {
        $query = Request::get('q', '');
        $limit = min((int) Request::get('limit', 20), 50);

        $vessels = $this->searchService->searchVessels($query, $limit);

        Response::json([
            'success' => true,
            'data' => $vessels,
        ]);
    }

    /**
     * Search tours only
     * GET /api/search/tours?q=query
     */
    public function tours(): void
    {
        $query = Request::get('q', '');
        $limit = min((int) Request::get('limit', 20), 50);

        $tours = $this->searchService->searchTours($query, $limit);

        Response::json([
            'success' => true,
            'data' => $tours,
        ]);
    }
}
