<?php

declare(strict_types=1);

namespace Website\Controllers;

use App\Services\TourService;

/**
 * Tours controller
 */
class ToursController extends BaseController
{
    private TourService $tourService;

    public function __construct($view)
    {
        parent::__construct($view);
        $this->tourService = new TourService();
    }

    /**
     * List all tours
     */
    public function index(array $params): void
    {
        $category = $this->get('category');
        $sort = $this->get('sort', 'popular');
        $page = (int) $this->get('page', 1);
        $perPage = 12;

        // Build filters
        $filters = [];
        if ($category) {
            $filters['category'] = $category;
        }

        // Get tours
        $tours = $this->tourService->getAll($filters, $sort, $page, $perPage);
        $total = $this->tourService->count($filters);
        $totalPages = ceil($total / $perPage);

        // Get categories
        $categories = $this->cache('tour_categories', 3600, function () {
            return $this->db->query("SELECT DISTINCT category FROM tours WHERE is_active = 1 ORDER BY category");
        });

        // SEO
        $this->seo
            ->title('Island Tours & Excursions in Phuket')
            ->description("Discover Phuket's stunning islands. Book tours to Phi Phi Islands, James Bond Island, Similan Islands, and more. Best prices and small groups.")
            ->keywords('phi phi island tour, james bond island tour, similan islands, phuket day trips')
            ->canonical('/tours')
            ->breadcrumbs([
                'Home' => '/',
                'Island Tours' => '/tours',
            ]);

        $this->render('tours/index', [
            'tours' => $tours,
            'categories' => $categories,
            'currentCategory' => $category,
            'currentSort' => $sort,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    /**
     * Show single tour
     */
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $tour = $this->tourService->getBySlug($slug);

        if (!$tour) {
            http_response_code(404);
            $this->view->display('errors/404');
            return;
        }

        // Get reviews
        $reviews = $this->db->query("
            SELECT r.*, u.first_name, u.last_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.tour_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT 10
        ", [$tour['id']]);

        // Get similar tours
        $similar = $this->tourService->getSimilar($tour['id'], $tour['category'], 4);

        // Parse JSON fields
        $includes = json_decode($tour['includes'] ?? '[]', true);
        $excludes = json_decode($tour['excludes'] ?? '[]', true);
        $itinerary = json_decode($tour['itinerary'] ?? '[]', true);
        $highlights = json_decode($tour['highlights'] ?? '[]', true);

        // SEO
        $this->seo
            ->title($tour['name'] . ' - Phuket Tour')
            ->description($this->view->truncate($tour['description'], 155))
            ->image($tour['images'][0] ?? '')
            ->canonical('/tours/' . $slug)
            ->product([
                'name' => $tour['name'],
                'description' => $tour['description'],
                'price' => $tour['price_adult'],
                'image' => $tour['images'][0] ?? '',
                'rating' => $tour['rating'] ?? null,
                'review_count' => $tour['review_count'] ?? 0,
            ])
            ->breadcrumbs([
                'Home' => '/',
                'Island Tours' => '/tours',
                $tour['name'] => '/tours/' . $slug,
            ]);

        $this->render('tours/show', [
            'tour' => $tour,
            'reviews' => $reviews,
            'similar' => $similar,
            'includes' => $includes,
            'excludes' => $excludes,
            'itinerary' => $itinerary,
            'highlights' => $highlights,
            'csrfToken' => $this->csrfToken(),
        ]);
    }
}
