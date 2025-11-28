<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;
use App\Core\Request;

/**
 * Admin Promos Controller
 * Manage promo codes
 */
class PromosController extends BaseAdminController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * GET /admin/promos
     * List all promo codes
     */
    public function index(): void
    {
        if (!$this->authorize('promos', 'view')) {
            return;
        }

        $pagination = $this->getPagination();
        $sort = $this->getSort(['code', 'discount_type', 'discount_value', 'times_used', 'is_active', 'created_at']);

        $where = ['1=1'];
        $params = [];

        if (isset($_GET['is_active'])) {
            $where[] = 'is_active = ?';
            $params[] = (int) $_GET['is_active'];
        }

        if (isset($_GET['discount_type'])) {
            $where[] = 'discount_type = ?';
            $params[] = $_GET['discount_type'];
        }

        if (isset($_GET['search'])) {
            $where[] = '(code LIKE ? OR description LIKE ?)';
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->queryOne("SELECT COUNT(*) as count FROM promo_codes WHERE {$whereClause}", $params)['count'];

        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];

        $promos = $this->db->query("
            SELECT p.*,
                (SELECT COALESCE(SUM(discount_amount), 0) FROM promo_code_usage WHERE promo_code_id = p.id) as total_discount_given
            FROM promo_codes p
            WHERE {$whereClause}
            ORDER BY {$sort['field']} {$sort['direction']}
            LIMIT ? OFFSET ?
        ", $params);

        // Parse applicable_to JSON
        foreach ($promos as &$promo) {
            $promo['applicable_to'] = json_decode($promo['applicable_to'] ?? 'null', true);
        }

        $this->paginate($promos, $total, $pagination['page'], $pagination['limit']);
    }

    /**
     * GET /admin/promos/{id}
     * Get promo code details
     */
    public function show(int $id): void
    {
        if (!$this->authorize('promos', 'view')) {
            return;
        }

        $promo = $this->db->queryOne("
            SELECT p.*,
                (SELECT COALESCE(SUM(discount_amount), 0) FROM promo_code_usage WHERE promo_code_id = p.id) as total_discount_given
            FROM promo_codes p
            WHERE p.id = ?
        ", [$id]);

        if (!$promo) {
            Response::notFound('Promo code not found');
            return;
        }

        $promo['applicable_to'] = json_decode($promo['applicable_to'] ?? 'null', true);

        // Get usage history
        $promo['usage_history'] = $this->db->query("
            SELECT pu.*, u.first_name, u.last_name, u.username,
                b.booking_reference, b.total_price_thb
            FROM promo_code_usage pu
            JOIN users u ON pu.user_id = u.id
            JOIN bookings b ON pu.booking_id = b.id
            WHERE pu.promo_code_id = ?
            ORDER BY pu.created_at DESC
            LIMIT 50
        ", [$id]);

        // Usage by day chart
        $promo['usage_by_day'] = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count, SUM(discount_amount) as discount
            FROM promo_code_usage
            WHERE promo_code_id = ?
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", [$id]);

        Response::success($promo);
    }

    /**
     * POST /admin/promos
     * Create new promo code
     */
    public function store(): void
    {
        if (!$this->authorize('promos', 'create')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'code' => 'required|string|min:3|max:50',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_order_thb' => 'nullable|numeric|min:0',
            'max_discount_thb' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'applicable_to' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        // Normalize code
        $code = strtoupper(trim($data['code']));

        // Check uniqueness
        $existing = $this->db->queryOne("SELECT id FROM promo_codes WHERE code = ?", [$code]);
        if ($existing) {
            Response::error('Promo code already exists', 400, 'DUPLICATE_CODE');
            return;
        }

        // Validate discount value
        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            Response::error('Percentage discount cannot exceed 100%', 400, 'INVALID_DISCOUNT');
            return;
        }

        $promoId = $this->db->insert('promo_codes', [
            'code' => $code,
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_order_thb' => $data['min_order_thb'] ?? 0,
            'max_discount_thb' => $data['max_discount_thb'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? 1,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'applicable_to' => isset($data['applicable_to']) ? json_encode($data['applicable_to']) : null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->logActivity('promo.create', 'promo', $promoId, null, $data);

        Response::success(['id' => $promoId, 'code' => $code, 'message' => 'Promo code created successfully'], 201);
    }

    /**
     * PUT /admin/promos/{id}
     * Update promo code
     */
    public function update(int $id): void
    {
        if (!$this->authorize('promos', 'edit')) {
            return;
        }

        $promo = $this->db->queryOne("SELECT * FROM promo_codes WHERE id = ?", [$id]);
        if (!$promo) {
            Response::notFound('Promo code not found');
            return;
        }

        $data = $this->validate(Request::all(), [
            'description' => 'nullable|string|max:500',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'min_order_thb' => 'nullable|numeric|min:0',
            'max_discount_thb' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'applicable_to' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        $updateData = array_filter([
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? null,
            'min_order_thb' => $data['min_order_thb'] ?? null,
            'max_discount_thb' => $data['max_discount_thb'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? null,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
        ], fn($v) => $v !== null);

        if (isset($data['applicable_to'])) {
            $updateData['applicable_to'] = json_encode($data['applicable_to']);
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }

        if (!empty($updateData)) {
            $this->db->update('promo_codes', $updateData, 'id = ?', [$id]);
            $this->logActivity('promo.update', 'promo', $id, $promo, $updateData);
        }

        Response::success(['message' => 'Promo code updated successfully']);
    }

    /**
     * DELETE /admin/promos/{id}
     * Delete promo code
     */
    public function destroy(int $id): void
    {
        if (!$this->authorize('promos', 'delete')) {
            return;
        }

        $promo = $this->db->queryOne("SELECT * FROM promo_codes WHERE id = ?", [$id]);
        if (!$promo) {
            Response::notFound('Promo code not found');
            return;
        }

        // Check if used
        if ($promo['times_used'] > 0) {
            // Soft delete
            $this->db->update('promo_codes', ['is_active' => 0], 'id = ?', [$id]);
            $this->logActivity('promo.deactivate', 'promo', $id, null, null);
            Response::success(['message' => 'Promo code deactivated (has usage history)']);
            return;
        }

        $this->db->delete('promo_codes', 'id = ?', [$id]);
        $this->logActivity('promo.delete', 'promo', $id, $promo, null);

        Response::success(['message' => 'Promo code deleted successfully']);
    }

    /**
     * POST /admin/promos/generate
     * Generate multiple promo codes
     */
    public function generate(): void
    {
        if (!$this->authorize('promos', 'create')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'prefix' => 'required|string|min:2|max:10',
            'count' => 'required|integer|min:1|max:100',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_until' => 'nullable|date',
        ]);

        if ($data === null) {
            return;
        }

        $prefix = strtoupper($data['prefix']);
        $codes = [];

        for ($i = 0; $i < $data['count']; $i++) {
            $suffix = strtoupper(bin2hex(random_bytes(3)));
            $code = $prefix . '-' . $suffix;

            // Ensure unique
            while ($this->db->queryOne("SELECT id FROM promo_codes WHERE code = ?", [$code])) {
                $suffix = strtoupper(bin2hex(random_bytes(3)));
                $code = $prefix . '-' . $suffix;
            }

            $this->db->insert('promo_codes', [
                'code' => $code,
                'description' => "Generated batch: {$prefix}",
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'max_uses' => $data['max_uses'] ?? 1,
                'max_uses_per_user' => 1,
                'valid_until' => $data['valid_until'] ?? null,
                'is_active' => 1,
            ]);

            $codes[] = $code;
        }

        $this->logActivity('promo.generate', null, null, null, [
            'prefix' => $prefix,
            'count' => count($codes),
        ]);

        Response::success([
            'message' => count($codes) . ' promo codes generated',
            'codes' => $codes,
        ], 201);
    }

    /**
     * GET /admin/promos/stats
     * Get promo code statistics
     */
    public function stats(): void
    {
        if (!$this->authorize('promos', 'view')) {
            return;
        }

        $stats = $this->db->queryOne("
            SELECT
                COUNT(*) as total_codes,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_codes,
                SUM(times_used) as total_uses,
                (SELECT COALESCE(SUM(discount_amount), 0) FROM promo_code_usage) as total_discount_given
            FROM promo_codes
        ");

        $topCodes = $this->db->query("
            SELECT code, times_used,
                (SELECT COALESCE(SUM(discount_amount), 0) FROM promo_code_usage WHERE promo_code_id = p.id) as discount_given
            FROM promo_codes p
            WHERE times_used > 0
            ORDER BY times_used DESC
            LIMIT 10
        ");

        $recentUsage = $this->db->query("
            SELECT DATE(pu.created_at) as date, COUNT(*) as uses, SUM(discount_amount) as discount
            FROM promo_code_usage pu
            WHERE pu.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(pu.created_at)
            ORDER BY date
        ");

        Response::success([
            'overview' => $stats,
            'top_codes' => $topCodes,
            'recent_usage' => $recentUsage,
        ]);
    }
}
