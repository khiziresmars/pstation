<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\TourService;

/**
 * Tour Controller
 * Handles tour catalog endpoints
 */
class TourController
{
    private TourService $tourService;

    public function __construct()
    {
        $this->tourService = new TourService();
    }

    /**
     * GET /api/tours
     * List all tours with filters
     */
    public function index(): void
    {
        $filters = [
            'category' => Request::query('category'),
            'min_price' => Request::query('min_price'),
            'max_price' => Request::query('max_price'),
            'max_duration' => Request::query('max_duration'),
            'date' => Request::query('date'),
            'sort' => Request::query('sort', 'popular'),
        ];

        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(50, max(1, (int) Request::query('per_page', 12)));

        $result = $this->tourService->getAll($filters, $page, $perPage);

        // Add categories for filtering
        $categories = $this->tourService->getCategories();

        Response::json([
            'success' => true,
            'data' => $result['items'],
            'categories' => $categories,
            'pagination' => [
                'total' => $result['total'],
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => (int) ceil($result['total'] / $perPage),
                'has_more' => $page < ceil($result['total'] / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/tours/featured
     * Get featured tours
     */
    public function featured(): void
    {
        $limit = min(10, max(1, (int) Request::query('limit', 4)));
        $tours = $this->tourService->getFeatured($limit);

        Response::success($tours);
    }

    /**
     * GET /api/tours/{slug}
     * Get tour details
     */
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        // Check if it's a numeric ID or slug
        if (is_numeric($slug)) {
            $tour = $this->tourService->getById((int) $slug);
        } else {
            $tour = $this->tourService->getBySlug($slug);
        }

        if (!$tour) {
            Response::notFound('Tour not found');
            return;
        }

        Response::success($tour);
    }

    /**
     * GET /api/tours/{id}/availability
     * Get tour availability calendar
     */
    public function availability(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $startDate = Request::query('start_date', date('Y-m-d'));
        $endDate = Request::query('end_date', date('Y-m-d', strtotime('+60 days')));

        $availability = $this->tourService->getAvailability($id, $startDate, $endDate);

        Response::success($availability);
    }

    /**
     * GET /api/tours/{id}/reviews
     * Get tour reviews
     */
    public function reviews(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(50, max(1, (int) Request::query('per_page', 10)));

        $result = $this->tourService->getReviews($id, $page, $perPage);

        Response::paginated($result['items'], $result['total'], $page, $perPage);
    }
}
