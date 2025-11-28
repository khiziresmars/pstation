<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;
use App\Core\Request;
use App\Services\NotificationService;

/**
 * Admin Vessels Controller
 * Manage vessels/yachts
 */
class VesselsController extends BaseAdminController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * GET /admin/vessels
     * List all vessels with filters
     */
    public function index(): void
    {
        if (!$this->authorize('vessels', 'view')) {
            return;
        }

        $pagination = $this->getPagination();
        $sort = $this->getSort(['name', 'type', 'price_per_hour_thb', 'is_active', 'created_at']);

        $where = ['1=1'];
        $params = [];

        if (isset($_GET['type'])) {
            $where[] = 'type = ?';
            $params[] = $_GET['type'];
        }

        if (isset($_GET['is_active'])) {
            $where[] = 'is_active = ?';
            $params[] = (int) $_GET['is_active'];
        }

        if (isset($_GET['search'])) {
            $where[] = '(name LIKE ? OR description LIKE ?)';
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->queryOne("SELECT COUNT(*) as count FROM vessels WHERE {$whereClause}", $params)['count'];

        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];

        $vessels = $this->db->query("
            SELECT v.*,
                (SELECT COUNT(*) FROM bookings WHERE bookable_type = 'vessel' AND bookable_id = v.id) as total_bookings,
                (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE reviewable_type = 'vessel' AND reviewable_id = v.id) as avg_rating
            FROM vessels v
            WHERE {$whereClause}
            ORDER BY {$sort['field']} {$sort['direction']}
            LIMIT ? OFFSET ?
        ", $params);

        $this->paginate($vessels, $total, $pagination['page'], $pagination['limit']);
    }

    /**
     * GET /admin/vessels/{id}
     * Get vessel details
     */
    public function show(int $id): void
    {
        if (!$this->authorize('vessels', 'view')) {
            return;
        }

        $vessel = $this->db->queryOne("
            SELECT v.*,
                (SELECT COUNT(*) FROM bookings WHERE bookable_type = 'vessel' AND bookable_id = v.id) as total_bookings,
                (SELECT COALESCE(SUM(total_price_thb), 0) FROM bookings WHERE bookable_type = 'vessel' AND bookable_id = v.id AND status IN ('paid', 'completed')) as total_revenue,
                (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE reviewable_type = 'vessel' AND reviewable_id = v.id) as avg_rating,
                (SELECT COUNT(*) FROM reviews WHERE reviewable_type = 'vessel' AND reviewable_id = v.id) as total_reviews
            FROM vessels v
            WHERE v.id = ?
        ", [$id]);

        if (!$vessel) {
            Response::notFound('Vessel not found');
            return;
        }

        // Get extras
        $vessel['extras'] = $this->db->query(
            "SELECT * FROM vessel_extras WHERE vessel_id = ? ORDER BY price_thb",
            [$id]
        );

        // Get recent bookings
        $vessel['recent_bookings'] = $this->db->query("
            SELECT b.*, u.first_name, u.last_name, u.username
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.bookable_type = 'vessel' AND b.bookable_id = ?
            ORDER BY b.created_at DESC
            LIMIT 10
        ", [$id]);

        // Get availability
        $vessel['availability'] = $this->db->query("
            SELECT * FROM availability
            WHERE entity_type = 'vessel' AND entity_id = ? AND date >= CURDATE()
            ORDER BY date
            LIMIT 30
        ", [$id]);

        Response::success($vessel);
    }

    /**
     * POST /admin/vessels
     * Create new vessel
     */
    public function store(): void
    {
        if (!$this->authorize('vessels', 'create')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'name' => 'required|string|min:3|max:200',
            'slug' => 'required|slug|max:200',
            'type' => 'required|in:yacht,speedboat,catamaran,sailboat',
            'description' => 'required|string',
            'capacity' => 'required|integer|min:1|max:100',
            'length_ft' => 'nullable|numeric|min:10|max:500',
            'year_built' => 'nullable|integer|min:1900|max:2030',
            'crew_count' => 'nullable|integer|min:0|max:50',
            'cabins' => 'nullable|integer|min:0|max:20',
            'amenities' => 'nullable|json',
            'price_per_hour_thb' => 'required|numeric|min:0',
            'min_rental_hours' => 'required|integer|min:1|max:24',
            'images' => 'nullable|array',
            'thumbnail' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        // Check slug uniqueness
        $existing = $this->db->queryOne("SELECT id FROM vessels WHERE slug = ?", [$data['slug']]);
        if ($existing) {
            Response::error('Slug already exists', 400, 'DUPLICATE_SLUG');
            return;
        }

        $vesselId = $this->db->insert('vessels', [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'type' => $data['type'],
            'description' => $data['description'],
            'capacity' => $data['capacity'],
            'length_ft' => $data['length_ft'] ?? null,
            'year_built' => $data['year_built'] ?? null,
            'crew_count' => $data['crew_count'] ?? null,
            'cabins' => $data['cabins'] ?? null,
            'amenities' => isset($data['amenities']) ? json_encode($data['amenities']) : null,
            'price_per_hour_thb' => $data['price_per_hour_thb'],
            'min_rental_hours' => $data['min_rental_hours'],
            'images' => isset($data['images']) ? json_encode($data['images']) : null,
            'thumbnail' => $data['thumbnail'] ?? null,
            'is_featured' => $data['is_featured'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->logActivity('vessel.create', 'vessel', $vesselId, null, $data);

        Response::success(['id' => $vesselId, 'message' => 'Vessel created successfully'], 201);
    }

    /**
     * PUT /admin/vessels/{id}
     * Update vessel
     */
    public function update(int $id): void
    {
        if (!$this->authorize('vessels', 'edit')) {
            return;
        }

        $vessel = $this->db->queryOne("SELECT * FROM vessels WHERE id = ?", [$id]);
        if (!$vessel) {
            Response::notFound('Vessel not found');
            return;
        }

        $data = $this->validate(Request::all(), [
            'name' => 'nullable|string|min:3|max:200',
            'slug' => 'nullable|slug|max:200',
            'type' => 'nullable|in:yacht,speedboat,catamaran,sailboat',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1|max:100',
            'length_ft' => 'nullable|numeric|min:10|max:500',
            'year_built' => 'nullable|integer|min:1900|max:2030',
            'crew_count' => 'nullable|integer|min:0|max:50',
            'cabins' => 'nullable|integer|min:0|max:20',
            'amenities' => 'nullable|json',
            'price_per_hour_thb' => 'nullable|numeric|min:0',
            'min_rental_hours' => 'nullable|integer|min:1|max:24',
            'images' => 'nullable|array',
            'thumbnail' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        // Check slug uniqueness if changed
        if (isset($data['slug']) && $data['slug'] !== $vessel['slug']) {
            $existing = $this->db->queryOne("SELECT id FROM vessels WHERE slug = ? AND id != ?", [$data['slug'], $id]);
            if ($existing) {
                Response::error('Slug already exists', 400, 'DUPLICATE_SLUG');
                return;
            }
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'slug' => $data['slug'] ?? null,
            'type' => $data['type'] ?? null,
            'description' => $data['description'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'length_ft' => $data['length_ft'] ?? null,
            'year_built' => $data['year_built'] ?? null,
            'crew_count' => $data['crew_count'] ?? null,
            'cabins' => $data['cabins'] ?? null,
            'amenities' => isset($data['amenities']) ? json_encode($data['amenities']) : null,
            'price_per_hour_thb' => $data['price_per_hour_thb'] ?? null,
            'min_rental_hours' => $data['min_rental_hours'] ?? null,
            'images' => isset($data['images']) ? json_encode($data['images']) : null,
            'thumbnail' => $data['thumbnail'] ?? null,
        ], fn($v) => $v !== null);

        if (isset($data['is_featured'])) {
            $updateData['is_featured'] = $data['is_featured'] ? 1 : 0;
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }

        if (!empty($updateData)) {
            $this->db->update('vessels', $updateData, 'id = ?', [$id]);
            $this->logActivity('vessel.update', 'vessel', $id, $vessel, $updateData);
        }

        Response::success(['message' => 'Vessel updated successfully']);
    }

    /**
     * DELETE /admin/vessels/{id}
     * Delete vessel
     */
    public function destroy(int $id): void
    {
        if (!$this->authorize('vessels', 'delete')) {
            return;
        }

        $vessel = $this->db->queryOne("SELECT * FROM vessels WHERE id = ?", [$id]);
        if (!$vessel) {
            Response::notFound('Vessel not found');
            return;
        }

        // Check for active bookings
        $activeBookings = $this->db->queryOne("
            SELECT COUNT(*) as count FROM bookings
            WHERE bookable_type = 'vessel' AND bookable_id = ?
            AND status IN ('pending', 'confirmed', 'paid')
            AND booking_date >= CURDATE()
        ", [$id])['count'];

        if ($activeBookings > 0) {
            Response::error('Cannot delete vessel with active bookings', 400, 'HAS_ACTIVE_BOOKINGS');
            return;
        }

        // Soft delete by deactivating
        $this->db->update('vessels', ['is_active' => 0], 'id = ?', [$id]);
        $this->logActivity('vessel.delete', 'vessel', $id, $vessel, null);

        Response::success(['message' => 'Vessel deleted successfully']);
    }

    /**
     * POST /admin/vessels/{id}/extras
     * Add extra to vessel
     */
    public function addExtra(int $id): void
    {
        if (!$this->authorize('vessels', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'price_thb' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        $extraId = $this->db->insert('vessel_extras', [
            'vessel_id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_thb' => $data['price_thb'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->logActivity('vessel.extra.create', 'vessel', $id, null, $data);

        Response::success(['id' => $extraId, 'message' => 'Extra added successfully'], 201);
    }

    /**
     * PUT /admin/vessels/{id}/availability
     * Update vessel availability
     */
    public function updateAvailability(int $id): void
    {
        if (!$this->authorize('vessels', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'date' => 'required|date',
            'is_available' => 'required|boolean',
            'special_price_thb' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        if ($data === null) {
            return;
        }

        // Upsert availability
        $existing = $this->db->queryOne(
            "SELECT id FROM availability WHERE entity_type = 'vessel' AND entity_id = ? AND date = ?",
            [$id, $data['date']]
        );

        if ($existing) {
            $this->db->update('availability', [
                'is_available' => $data['is_available'] ? 1 : 0,
                'special_price' => $data['special_price_thb'] ?? null,
                'note' => $data['note'] ?? null,
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('availability', [
                'entity_type' => 'vessel',
                'entity_id' => $id,
                'date' => $data['date'],
                'is_available' => $data['is_available'] ? 1 : 0,
                'special_price' => $data['special_price_thb'] ?? null,
                'note' => $data['note'] ?? null,
            ]);
        }

        $this->logActivity('vessel.availability.update', 'vessel', $id, null, $data);

        Response::success(['message' => 'Availability updated successfully']);
    }

    /**
     * POST /admin/vessels/{id}/duplicate
     * Duplicate vessel
     */
    public function duplicate(int $id): void
    {
        if (!$this->authorize('vessels', 'create')) {
            return;
        }

        $vessel = $this->db->queryOne("SELECT * FROM vessels WHERE id = ?", [$id]);
        if (!$vessel) {
            Response::notFound('Vessel not found');
            return;
        }

        // Generate unique slug
        $baseSlug = $vessel['slug'] . '-copy';
        $slug = $baseSlug;
        $counter = 1;
        while ($this->db->queryOne("SELECT id FROM vessels WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . $counter++;
        }

        unset($vessel['id'], $vessel['created_at'], $vessel['updated_at']);
        $vessel['name'] = $vessel['name'] . ' (Copy)';
        $vessel['slug'] = $slug;
        $vessel['is_active'] = 0;

        $newId = $this->db->insert('vessels', $vessel);

        // Copy extras
        $extras = $this->db->query("SELECT * FROM vessel_extras WHERE vessel_id = ?", [$id]);
        foreach ($extras as $extra) {
            unset($extra['id']);
            $extra['vessel_id'] = $newId;
            $this->db->insert('vessel_extras', $extra);
        }

        $this->logActivity('vessel.duplicate', 'vessel', $newId, null, ['source_id' => $id]);

        Response::success(['id' => $newId, 'message' => 'Vessel duplicated successfully'], 201);
    }
}
