<?php

declare(strict_types=1);

namespace Website\Controllers;

use App\Services\VesselService;

/**
 * Vessels (Yachts & Boats) controller
 */
class VesselsController extends BaseController
{
    private VesselService $vesselService;

    public function __construct($view)
    {
        parent::__construct($view);
        $this->vesselService = new VesselService();
    }

    /**
     * List all vessels
     */
    public function index(array $params): void
    {
        $type = $this->get('type');
        $sort = $this->get('sort', 'popular');
        $page = (int) $this->get('page', 1);
        $perPage = 12;

        // Build filters
        $filters = [];
        if ($type && in_array($type, ['yacht', 'speedboat', 'catamaran', 'sailboat'])) {
            $filters['type'] = $type;
        }

        // Get vessels
        $vessels = $this->vesselService->getAll($filters, $sort, $page, $perPage);
        $total = $this->vesselService->count($filters);
        $totalPages = ceil($total / $perPage);

        // SEO
        $title = $type ? ucfirst($type) . 's for Rent in Phuket' : 'Yachts & Boats for Rent in Phuket';
        $this->seo
            ->title($title)
            ->description("Rent luxury yachts, speedboats, and catamarans in Phuket. Private charters with captain, perfect for island hopping and celebrations.")
            ->keywords("phuket yacht rental, {$type} charter phuket, boat hire thailand")
            ->canonical('/yachts')
            ->breadcrumbs([
                'Home' => '/',
                'Yachts & Boats' => '/yachts',
            ]);

        $this->render('vessels/index', [
            'vessels' => $vessels,
            'currentType' => $type,
            'currentSort' => $sort,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    /**
     * Show single vessel
     */
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        $vessel = $this->vesselService->getBySlug($slug);

        if (!$vessel) {
            http_response_code(404);
            $this->view->display('errors/404');
            return;
        }

        // Get reviews
        $reviews = $this->db->query("
            SELECT r.*, u.first_name, u.last_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.vessel_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT 10
        ", [$vessel['id']]);

        // Get similar vessels
        $similar = $this->vesselService->getSimilar($vessel['id'], $vessel['type'], 4);

        // Get extras
        $extras = json_decode($vessel['extras'] ?? '[]', true);

        // SEO
        $this->seo
            ->title($vessel['name'] . ' - Charter in Phuket')
            ->description($this->view->truncate($vessel['description'], 155))
            ->image($vessel['images'][0] ?? '')
            ->canonical('/yachts/' . $slug)
            ->product([
                'name' => $vessel['name'],
                'description' => $vessel['description'],
                'price' => $vessel['price_per_hour'],
                'image' => $vessel['images'][0] ?? '',
                'rating' => $vessel['rating'] ?? null,
                'review_count' => $vessel['review_count'] ?? 0,
            ])
            ->breadcrumbs([
                'Home' => '/',
                'Yachts & Boats' => '/yachts',
                $vessel['name'] => '/yachts/' . $slug,
            ]);

        $this->render('vessels/show', [
            'vessel' => $vessel,
            'reviews' => $reviews,
            'similar' => $similar,
            'extras' => $extras,
            'csrfToken' => $this->csrfToken(),
        ]);
    }
}
