<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;
use App\Core\Request;

/**
 * Admin Settings Controller
 * Manage application settings
 */
class SettingsController extends BaseAdminController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * GET /admin/settings
     * Get all settings
     */
    public function index(): void
    {
        if (!$this->authorize('settings', 'view')) {
            return;
        }

        $settings = $this->db->query("SELECT * FROM settings ORDER BY setting_key");

        // Group by category
        $grouped = [];
        foreach ($settings as $setting) {
            $parts = explode('_', $setting['setting_key'], 2);
            $category = $parts[0] ?? 'general';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $setting;
        }

        Response::success([
            'settings' => $settings,
            'grouped' => $grouped,
        ]);
    }

    /**
     * PUT /admin/settings
     * Update settings
     */
    public function update(): void
    {
        if (!$this->authorize('settings', 'edit')) {
            return;
        }

        $data = Request::all();

        if (empty($data)) {
            Response::error('No settings provided', 400);
            return;
        }

        $updated = [];
        foreach ($data as $key => $value) {
            // Validate key exists
            $existing = $this->db->queryOne(
                "SELECT * FROM settings WHERE setting_key = ?",
                [$key]
            );

            if (!$existing) {
                continue;
            }

            // Type conversion
            $finalValue = match ($existing['setting_type']) {
                'boolean' => $value ? 'true' : 'false',
                'integer' => (string) (int) $value,
                'float' => (string) (float) $value,
                'json' => is_string($value) ? $value : json_encode($value),
                default => (string) $value,
            };

            $this->db->update('settings', [
                'setting_value' => $finalValue,
            ], 'setting_key = ?', [$key]);

            $updated[$key] = $finalValue;
        }

        $this->logActivity('settings.update', null, null, null, $updated);

        Response::success([
            'message' => count($updated) . ' settings updated',
            'updated' => $updated,
        ]);
    }

    /**
     * GET /admin/settings/exchange-rates
     * Get exchange rates
     */
    public function exchangeRates(): void
    {
        if (!$this->authorize('settings', 'view')) {
            return;
        }

        $rates = $this->db->query("SELECT * FROM exchange_rates ORDER BY currency_code");

        Response::success($rates);
    }

    /**
     * PUT /admin/settings/exchange-rates
     * Update exchange rates
     */
    public function updateExchangeRates(): void
    {
        if (!$this->authorize('settings', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'rates' => 'required|array',
        ]);

        if ($data === null) {
            return;
        }

        foreach ($data['rates'] as $currency => $rate) {
            $this->db->execute("
                INSERT INTO exchange_rates (currency_code, rate_to_thb, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE rate_to_thb = VALUES(rate_to_thb), updated_at = NOW()
            ", [strtoupper($currency), (float) $rate]);
        }

        $this->logActivity('settings.exchange_rates', null, null, null, $data['rates']);

        Response::success(['message' => 'Exchange rates updated']);
    }

    /**
     * GET /admin/settings/logs
     * Get activity logs
     */
    public function logs(): void
    {
        if (!$this->authorize('logs', 'view')) {
            return;
        }

        $pagination = $this->getPagination();

        $where = ['1=1'];
        $params = [];

        if (isset($_GET['admin_id'])) {
            $where[] = 'l.admin_id = ?';
            $params[] = (int) $_GET['admin_id'];
        }

        if (isset($_GET['action'])) {
            $where[] = 'l.action LIKE ?';
            $params[] = '%' . $_GET['action'] . '%';
        }

        if (isset($_GET['entity_type'])) {
            $where[] = 'l.entity_type = ?';
            $params[] = $_GET['entity_type'];
        }

        if (isset($_GET['date_from'])) {
            $where[] = 'l.created_at >= ?';
            $params[] = $_GET['date_from'];
        }

        if (isset($_GET['date_to'])) {
            $where[] = 'l.created_at <= ?';
            $params[] = $_GET['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->queryOne("
            SELECT COUNT(*) as count FROM admin_activity_log l WHERE {$whereClause}
        ", $params)['count'];

        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];

        $logs = $this->db->query("
            SELECT l.*, a.username, a.first_name, a.last_name
            FROM admin_activity_log l
            LEFT JOIN admins a ON l.admin_id = a.id
            WHERE {$whereClause}
            ORDER BY l.created_at DESC
            LIMIT ? OFFSET ?
        ", $params);

        // Parse JSON fields
        foreach ($logs as &$log) {
            $log['old_values'] = json_decode($log['old_values'] ?? 'null', true);
            $log['new_values'] = json_decode($log['new_values'] ?? 'null', true);
        }

        $this->paginate($logs, $total, $pagination['page'], $pagination['limit']);
    }

    /**
     * GET /admin/settings/notifications
     * Get notification templates
     */
    public function notifications(): void
    {
        if (!$this->authorize('settings', 'view')) {
            return;
        }

        $templates = $this->db->query("SELECT * FROM notification_templates ORDER BY code");

        foreach ($templates as &$template) {
            $template['variables'] = json_decode($template['variables'] ?? '[]', true);
        }

        Response::success($templates);
    }

    /**
     * PUT /admin/settings/notifications/{code}
     * Update notification template
     */
    public function updateNotification(string $code): void
    {
        if (!$this->authorize('settings', 'edit')) {
            return;
        }

        $template = $this->db->queryOne(
            "SELECT * FROM notification_templates WHERE code = ?",
            [$code]
        );

        if (!$template) {
            Response::notFound('Template not found');
            return;
        }

        $data = $this->validate(Request::all(), [
            'template_text' => 'nullable|string',
            'template_html' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($data === null) {
            return;
        }

        $updateData = [];
        if (isset($data['template_text'])) {
            $updateData['template_text'] = $data['template_text'];
        }
        if (isset($data['template_html'])) {
            $updateData['template_html'] = $data['template_html'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }

        if (!empty($updateData)) {
            $this->db->update('notification_templates', $updateData, 'code = ?', [$code]);
            $this->logActivity('settings.notification_update', null, null, $template, $updateData);
        }

        Response::success(['message' => 'Template updated']);
    }

    /**
     * POST /admin/settings/test-notification
     * Send test notification
     */
    public function testNotification(): void
    {
        if (!$this->authorize('settings', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'template_code' => 'required|string',
        ]);

        if ($data === null) {
            return;
        }

        $admin = $this->getAdmin();
        $notification = new \App\Services\NotificationService();

        $result = $notification->send(
            $admin['telegram_id'],
            "🧪 Test notification from template: {$data['template_code']}\n\nThis is a test message.",
            'admin',
            $admin['id'],
            $data['template_code']
        );

        if ($result['success']) {
            Response::success(['message' => 'Test notification sent']);
        } else {
            Response::error('Failed to send: ' . ($result['error'] ?? 'Unknown error'), 500);
        }
    }

    /**
     * GET /admin/settings/system
     * Get system information
     */
    public function system(): void
    {
        if (!$this->authorize('settings', 'view')) {
            return;
        }

        $dbStats = $this->db->queryOne("
            SELECT
                (SELECT COUNT(*) FROM users) as users,
                (SELECT COUNT(*) FROM vessels) as vessels,
                (SELECT COUNT(*) FROM tours) as tours,
                (SELECT COUNT(*) FROM bookings) as bookings,
                (SELECT COUNT(*) FROM reviews) as reviews,
                (SELECT COUNT(*) FROM promo_codes) as promos,
                (SELECT COUNT(*) FROM admins) as admins
        ");

        $recentActivity = $this->db->queryOne("
            SELECT
                (SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()) as today_bookings,
                (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as today_users,
                (SELECT COUNT(*) FROM reviews WHERE DATE(created_at) = CURDATE()) as today_reviews
        ");

        Response::success([
            'php_version' => PHP_VERSION,
            'database' => $dbStats,
            'recent' => $recentActivity,
            'server_time' => date('Y-m-d H:i:s T'),
            'timezone' => date_default_timezone_get(),
        ]);
    }
}
