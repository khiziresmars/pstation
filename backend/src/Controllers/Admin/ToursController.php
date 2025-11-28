<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;
use App\Core\Request;

/**
 * Admin Tours Controller
 * Manage tours
 */
class ToursController extends BaseAdminController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * GET /admin/tours
     * List all tours
     */
    public function index(): void
    {
        if (!$this->authorize('tours', 'view')) {
            return;
        }

        $pagination = $this->getPagination();
        $sort = $this->getSort(['name_en', 'category', 'adult_price_thb', 'is_active', 'created_at']);

        $where = ['1=1'];
        $params = [];

        if (isset($_GET['category'])) {
            $where[] = 'category = ?';
            $params[] = $_GET['category'];
        }

        if (isset($_GET['is_active'])) {
            $where[] = 'is_active = ?';
            $params[] = (int) $_GET['is_active'];
        }

        if (isset($_GET['search'])) {
            $where[] = '(name_en LIKE ? OR name_ru LIKE ? OR description_en LIKE ?)';
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->queryOne("SELECT COUNT(*) as count FROM tours WHERE {$whereClause}", $params)['count'];

        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];

        $tours = $this->db->query("
            SELECT t.*,
                (SELECT COUNT(*) FROM bookings WHERE bookable_type = 'tour' AND bookable_id = t.id) as total_bookings,
                (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE reviewable_type = 'tour' AND reviewable_id = t.id) as avg_rating
            FROM tours t
            WHERE {$whereClause}
            ORDER BY {$sort['field']} {$sort['direction']}
            LIMIT ? OFFSET ?
        ", $params);

        $this->paginate($tours, $total, $pagination['page'], $pagination['limit']);
    }

    /**
     * GET /admin/tours/{id}
     * Get tour details
     */
    public function show(int $id): void
    {
        if (!$this->authorize('tours', 'view')) {
            return;
        }

        $tour = $this->db->queryOne("
            SELECT t.*,
                (SELECT COUNT(*) FROM bookings WHERE bookable_type = 'tour' AND bookable_id = t.id) as total_bookings,
                (SELECT COALESCE(SUM(total_price_thb), 0) FROM bookings WHERE bookable_type = 'tour' AND bookable_id = t.id AND status IN ('paid', 'completed')) as total_revenue,
                (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE reviewable_type = 'tour' AND reviewable_id = t.id) as avg_rating,
                (SELECT COUNT(*) FROM reviews WHERE reviewable_type = 'tour' AND reviewable_id = t.id) as total_reviews
            FROM tours t
            WHERE t.id = ?
        ", [$id]);

        if (!$tour) {
            Response::notFound('Tour not found');
            return;
        }

        // Parse JSON fields
        $tour['highlights_en'] = json_decode($tour['highlights_en'] ?? '[]', true);
        $tour['highlights_ru'] = json_decode($tour['highlights_ru'] ?? '[]', true);
        $tour['itinerary_en'] = json_decode($tour['itinerary_en'] ?? '[]', true);
        $tour['itinerary_ru'] = json_decode($tour['itinerary_ru'] ?? '[]', true);
        $tour['includes'] = json_decode($tour['includes'] ?? '[]', true);
        $tour['excludes'] = json_decode($tour['excludes'] ?? '[]', true);
        $tour['images'] = json_decode($tour['images'] ?? '[]', true);

        // Get recent bookings
        $tour['recent_bookings'] = $this->db->query("
            SELECT b.*, u.first_name, u.last_name, u.username
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.bookable_type = 'tour' AND b.bookable_id = ?
            ORDER BY b.created_at DESC
            LIMIT 10
        ", [$id]);

        // Get availability
        $tour['availability'] = $this->db->query("
            SELECT * FROM availability
            WHERE entity_type = 'tour' AND entity_id = ? AND date >= CURDATE()
            ORDER BY date
            LIMIT 30
        ", [$id]);

        Response::success($tour);
    }

    /**
     * POST /admin/tours
     * Create new tour
     */
    public function store(): void
    {
        if (!$this->authorize('tours', 'create')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'name_en' => 'required|string|min:3|max:200',
            'name_ru' => 'nullable|string|max:200',
            'name_th' => 'nullable|string|max:200',
            'slug' => 'required|slug|max:200',
            'category' => 'required|in:island_hopping,snorkeling,fishing,sunset,diving,private',
            'description_en' => 'required|string',
            'description_ru' => 'nullable|string',
            'description_th' => 'nullable|string',
            'duration_hours' => 'required|numeric|min:1|max:48',
            'max_participants' => 'required|integer|min:1|max:100',
            'adult_price_thb' => 'required|numeric|min:0',
            'child_price_thb' => 'required|numeric|min:0',
            'pickup_available' => 'nullable|boolean',
            'pickup_fee_thb' => 'nullable|numeric|min:0',
            'departure_time' => 'nullable|time',
            'departure_location' => 'nullable|string|max:500',
            'highlights_en' => 'nullable|array',
            'highlights_ru' => 'nullable|array',
            'itinerary_en' => 'nullable|array',
            'itinerary_ru' => 'nullable|array',
            'includes' => 'nullable|array',
            'excludes' => 'nullable|array',
            'images' => 'nullable|array',
            'thumbnail' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        // Check slug uniqueness
        $existing = $this->db->queryOne("SELECT id FROM tours WHERE slug = ?", [$data['slug']]);
        if ($existing) {
            Response::error('Slug already exists', 400, 'DUPLICATE_SLUG');
            return;
        }

        $tourId = $this->db->insert('tours', [
            'name_en' => $data['name_en'],
            'name_ru' => $data['name_ru'] ?? null,
            'name_th' => $data['name_th'] ?? null,
            'slug' => $data['slug'],
            'category' => $data['category'],
            'description_en' => $data['description_en'],
            'description_ru' => $data['description_ru'] ?? null,
            'description_th' => $data['description_th'] ?? null,
            'duration_hours' => $data['duration_hours'],
            'max_participants' => $data['max_participants'],
            'adult_price_thb' => $data['adult_price_thb'],
            'child_price_thb' => $data['child_price_thb'],
            'pickup_available' => $data['pickup_available'] ?? false,
            'pickup_fee_thb' => $data['pickup_fee_thb'] ?? 0,
            'departure_time' => $data['departure_time'] ?? null,
            'departure_location' => $data['departure_location'] ?? null,
            'highlights_en' => isset($data['highlights_en']) ? json_encode($data['highlights_en']) : null,
            'highlights_ru' => isset($data['highlights_ru']) ? json_encode($data['highlights_ru']) : null,
            'itinerary_en' => isset($data['itinerary_en']) ? json_encode($data['itinerary_en']) : null,
            'itinerary_ru' => isset($data['itinerary_ru']) ? json_encode($data['itinerary_ru']) : null,
            'includes' => isset($data['includes']) ? json_encode($data['includes']) : null,
            'excludes' => isset($data['excludes']) ? json_encode($data['excludes']) : null,
            'images' => isset($data['images']) ? json_encode($data['images']) : null,
            'thumbnail' => $data['thumbnail'] ?? null,
            'is_featured' => $data['is_featured'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->logActivity('tour.create', 'tour', $tourId, null, $data);

        Response::success(['id' => $tourId, 'message' => 'Tour created successfully'], 201);
    }

    /**
     * PUT /admin/tours/{id}
     * Update tour
     */
    public function update(int $id): void
    {
        if (!$this->authorize('tours', 'edit')) {
            return;
        }

        $tour = $this->db->queryOne("SELECT * FROM tours WHERE id = ?", [$id]);
        if (!$tour) {
            Response::notFound('Tour not found');
            return;
        }

        $data = $this->validate(Request::all(), [
            'name_en' => 'nullable|string|min:3|max:200',
            'name_ru' => 'nullable|string|max:200',
            'name_th' => 'nullable|string|max:200',
            'slug' => 'nullable|slug|max:200',
            'category' => 'nullable|in:island_hopping,snorkeling,fishing,sunset,diving,private',
            'description_en' => 'nullable|string',
            'description_ru' => 'nullable|string',
            'description_th' => 'nullable|string',
            'duration_hours' => 'nullable|numeric|min:1|max:48',
            'max_participants' => 'nullable|integer|min:1|max:100',
            'adult_price_thb' => 'nullable|numeric|min:0',
            'child_price_thb' => 'nullable|numeric|min:0',
            'pickup_available' => 'nullable|boolean',
            'pickup_fee_thb' => 'nullable|numeric|min:0',
            'departure_time' => 'nullable|time',
            'departure_location' => 'nullable|string|max:500',
            'highlights_en' => 'nullable|array',
            'highlights_ru' => 'nullable|array',
            'itinerary_en' => 'nullable|array',
            'itinerary_ru' => 'nullable|array',
            'includes' => 'nullable|array',
            'excludes' => 'nullable|array',
            'images' => 'nullable|array',
            'thumbnail' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        // Check slug uniqueness if changed
        if (isset($data['slug']) && $data['slug'] !== $tour['slug']) {
            $existing = $this->db->queryOne("SELECT id FROM tours WHERE slug = ? AND id != ?", [$data['slug'], $id]);
            if ($existing) {
                Response::error('Slug already exists', 400, 'DUPLICATE_SLUG');
                return;
            }
        }

        $updateData = [];
        $simpleFields = [
            'name_en', 'name_ru', 'name_th', 'slug', 'category',
            'description_en', 'description_ru', 'description_th',
            'duration_hours', 'max_participants',
            'adult_price_thb', 'child_price_thb',
            'pickup_fee_thb', 'departure_time', 'departure_location', 'thumbnail'
        ];

        foreach ($simpleFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $jsonFields = ['highlights_en', 'highlights_ru', 'itinerary_en', 'itinerary_ru', 'includes', 'excludes', 'images'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = json_encode($data[$field]);
            }
        }

        if (isset($data['pickup_available'])) {
            $updateData['pickup_available'] = $data['pickup_available'] ? 1 : 0;
        }
        if (isset($data['is_featured'])) {
            $updateData['is_featured'] = $data['is_featured'] ? 1 : 0;
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }

        if (!empty($updateData)) {
            $this->db->update('tours', $updateData, 'id = ?', [$id]);
            $this->logActivity('tour.update', 'tour', $id, $tour, $updateData);
        }

        Response::success(['message' => 'Tour updated successfully']);
    }

    /**
     * DELETE /admin/tours/{id}
     * Delete tour
     */
    public function destroy(int $id): void
    {
        if (!$this->authorize('tours', 'delete')) {
            return;
        }

        $tour = $this->db->queryOne("SELECT * FROM tours WHERE id = ?", [$id]);
        if (!$tour) {
            Response::notFound('Tour not found');
            return;
        }

        // Check for active bookings
        $activeBookings = $this->db->queryOne("
            SELECT COUNT(*) as count FROM bookings
            WHERE bookable_type = 'tour' AND bookable_id = ?
            AND status IN ('pending', 'confirmed', 'paid')
            AND booking_date >= CURDATE()
        ", [$id])['count'];

        if ($activeBookings > 0) {
            Response::error('Cannot delete tour with active bookings', 400, 'HAS_ACTIVE_BOOKINGS');
            return;
        }

        // Soft delete
        $this->db->update('tours', ['is_active' => 0], 'id = ?', [$id]);
        $this->logActivity('tour.delete', 'tour', $id, $tour, null);

        Response::success(['message' => 'Tour deleted successfully']);
    }

    /**
     * PUT /admin/tours/{id}/availability
     * Update tour availability
     */
    public function updateAvailability(int $id): void
    {
        if (!$this->authorize('tours', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'date' => 'required|date',
            'is_available' => 'required|boolean',
            'available_slots' => 'nullable|integer|min:0',
            'special_price_thb' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        if ($data === null) {
            return;
        }

        $existing = $this->db->queryOne(
            "SELECT id FROM availability WHERE entity_type = 'tour' AND entity_id = ? AND date = ?",
            [$id, $data['date']]
        );

        if ($existing) {
            $this->db->update('availability', [
                'is_available' => $data['is_available'] ? 1 : 0,
                'available_slots' => $data['available_slots'] ?? null,
                'special_price' => $data['special_price_thb'] ?? null,
                'note' => $data['note'] ?? null,
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('availability', [
                'entity_type' => 'tour',
                'entity_id' => $id,
                'date' => $data['date'],
                'is_available' => $data['is_available'] ? 1 : 0,
                'available_slots' => $data['available_slots'] ?? null,
                'special_price' => $data['special_price_thb'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        }

        $this->logActivity('tour.availability.update', 'tour', $id, null, $data);

        Response::success(['message' => 'Availability updated successfully']);
    }

    /**
     * GET /admin/tours/categories
     * Get available categories
     */
    public function categories(): void
    {
        $categories = [
            ['value' => 'island_hopping', 'label' => 'Island Hopping'],
            ['value' => 'snorkeling', 'label' => 'Snorkeling'],
            ['value' => 'fishing', 'label' => 'Fishing'],
            ['value' => 'sunset', 'label' => 'Sunset Cruise'],
            ['value' => 'diving', 'label' => 'Diving'],
            ['value' => 'private', 'label' => 'Private Tour'],
        ];

        Response::success($categories);
    }
}
