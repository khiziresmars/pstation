<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\VesselService;

/**
 * Vessel Controller
 * Handles vessel catalog endpoints
 */
class VesselController
{
    private VesselService $vesselService;

    public function __construct()
    {
        $this->vesselService = new VesselService();
    }

    /**
     * GET /api/vessels
     * List all vessels with filters
     */
    public function index(): void
    {
        $filters = [
            'type' => Request::query('type'),
            'min_capacity' => Request::query('min_capacity'),
            'max_capacity' => Request::query('max_capacity'),
            'min_price' => Request::query('min_price'),
            'max_price' => Request::query('max_price'),
            'date' => Request::query('date'),
            'sort' => Request::query('sort', 'popular'),
        ];

        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(50, max(1, (int) Request::query('per_page', 12)));

        $result = $this->vesselService->getAll($filters, $page, $perPage);

        Response::paginated($result['items'], $result['total'], $page, $perPage);
    }

    /**
     * GET /api/vessels/featured
     * Get featured vessels
     */
    public function featured(): void
    {
        $limit = min(10, max(1, (int) Request::query('limit', 4)));
        $vessels = $this->vesselService->getFeatured($limit);

        Response::success($vessels);
    }

    /**
     * GET /api/vessels/{slug}
     * Get vessel details
     */
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        // Check if it's a numeric ID or slug
        if (is_numeric($slug)) {
            $vessel = $this->vesselService->getById((int) $slug);
        } else {
            $vessel = $this->vesselService->getBySlug($slug);
        }

        if (!$vessel) {
            Response::notFound('Vessel not found');
            return;
        }

        Response::success($vessel);
    }

    /**
     * GET /api/vessels/{id}/availability
     * Get vessel availability calendar
     */
    public function availability(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $startDate = Request::query('start_date', date('Y-m-d'));
        $endDate = Request::query('end_date', date('Y-m-d', strtotime('+60 days')));

        $availability = $this->vesselService->getAvailability($id, $startDate, $endDate);

        Response::success($availability);
    }

    /**
     * GET /api/vessels/{id}/reviews
     * Get vessel reviews
     */
    public function reviews(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $page = max(1, (int) Request::query('page', 1));
        $perPage = min(50, max(1, (int) Request::query('per_page', 10)));

        $result = $this->vesselService->getReviews($id, $page, $perPage);

        Response::paginated($result['items'], $result['total'], $page, $perPage);
    }
}
