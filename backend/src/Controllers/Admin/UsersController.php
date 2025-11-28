<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;
use App\Core\Request;

/**
 * Admin Users Controller
 * Manage users
 */
class UsersController extends BaseAdminController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * GET /admin/users
     * List all users
     */
    public function index(): void
    {
        if (!$this->authorize('users', 'view')) {
            return;
        }

        $pagination = $this->getPagination();
        $sort = $this->getSort(['first_name', 'total_spent', 'cashback_balance', 'created_at'], 'created_at');

        $where = ['1=1'];
        $params = [];

        if (isset($_GET['search'])) {
            $where[] = '(first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR phone LIKE ?)';
            $search = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        if (isset($_GET['has_bookings'])) {
            if ($_GET['has_bookings'] === '1') {
                $where[] = '(SELECT COUNT(*) FROM bookings WHERE user_id = u.id) > 0';
            } else {
                $where[] = '(SELECT COUNT(*) FROM bookings WHERE user_id = u.id) = 0';
            }
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->queryOne("SELECT COUNT(*) as count FROM users u WHERE {$whereClause}", $params)['count'];

        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];

        $users = $this->db->query("
            SELECT u.*,
                (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as bookings_count,
                (SELECT COUNT(*) FROM bookings WHERE user_id = u.id AND status = 'completed') as completed_bookings,
                (SELECT COUNT(*) FROM users WHERE referred_by = u.id) as referrals_count
            FROM users u
            WHERE {$whereClause}
            ORDER BY u.{$sort['field']} {$sort['direction']}
            LIMIT ? OFFSET ?
        ", $params);

        $this->paginate($users, $total, $pagination['page'], $pagination['limit']);
    }

    /**
     * GET /admin/users/{id}
     * Get user details
     */
    public function show(int $id): void
    {
        if (!$this->authorize('users', 'view')) {
            return;
        }

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$id]);

        if (!$user) {
            Response::notFound('User not found');
            return;
        }

        // Get bookings
        $user['bookings'] = $this->db->query("
            SELECT b.*,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as item_name
            FROM bookings b
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT 20
        ", [$id]);

        // Get cashback transactions
        $user['cashback_transactions'] = $this->db->query("
            SELECT * FROM cashback_transactions
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ", [$id]);

        // Get referrals
        $user['referrals'] = $this->db->query("
            SELECT id, first_name, last_name, username, created_at
            FROM users
            WHERE referred_by = ?
            ORDER BY created_at DESC
        ", [$id]);

        // Get referrer
        if ($user['referred_by']) {
            $user['referrer'] = $this->db->queryOne(
                "SELECT id, first_name, last_name, username FROM users WHERE id = ?",
                [$user['referred_by']]
            );
        }

        // Get favorites
        $user['favorites'] = $this->db->query("
            SELECT f.*,
                CASE
                    WHEN f.entity_type = 'vessel' THEN v.name
                    WHEN f.entity_type = 'tour' THEN t.name_en
                END as item_name
            FROM favorites f
            LEFT JOIN vessels v ON f.entity_type = 'vessel' AND f.entity_id = v.id
            LEFT JOIN tours t ON f.entity_type = 'tour' AND f.entity_id = t.id
            WHERE f.user_id = ?
        ", [$id]);

        // Get reviews
        $user['reviews'] = $this->db->query("
            SELECT r.*,
                CASE
                    WHEN r.reviewable_type = 'vessel' THEN v.name
                    WHEN r.reviewable_type = 'tour' THEN t.name_en
                END as item_name
            FROM reviews r
            LEFT JOIN vessels v ON r.reviewable_type = 'vessel' AND r.reviewable_id = v.id
            LEFT JOIN tours t ON r.reviewable_type = 'tour' AND r.reviewable_id = t.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ", [$id]);

        // Statistics
        $user['stats'] = [
            'total_bookings' => count($user['bookings']),
            'completed_bookings' => count(array_filter($user['bookings'], fn($b) => $b['status'] === 'completed')),
            'total_referrals' => count($user['referrals']),
            'total_reviews' => count($user['reviews']),
        ];

        Response::success($user);
    }

    /**
     * PUT /admin/users/{id}
     * Update user
     */
    public function update(int $id): void
    {
        if (!$this->authorize('users', 'edit')) {
            return;
        }

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }

        $data = $this->validate(Request::all(), [
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|phone',
            'email' => 'nullable|email',
            'is_blocked' => 'nullable|boolean',
            'block_reason' => 'nullable|string|max:500',
        ]);

        if ($data === null) {
            return;
        }

        $updateData = array_filter([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ], fn($v) => $v !== null);

        if (isset($data['is_blocked'])) {
            $updateData['is_blocked'] = $data['is_blocked'] ? 1 : 0;
            if ($data['is_blocked'] && isset($data['block_reason'])) {
                $updateData['block_reason'] = $data['block_reason'];
            }
        }

        if (!empty($updateData)) {
            $this->db->update('users', $updateData, 'id = ?', [$id]);
            $this->logActivity('user.update', 'user', $id, $user, $updateData);
        }

        Response::success(['message' => 'User updated successfully']);
    }

    /**
     * PUT /admin/users/{id}/block
     * Block user
     */
    public function block(int $id): void
    {
        if (!$this->authorize('users', 'block')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($data === null) {
            return;
        }

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }

        $this->db->update('users', [
            'is_blocked' => 1,
            'block_reason' => $data['reason'],
            'blocked_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->logActivity('user.block', 'user', $id, null, ['reason' => $data['reason']]);

        Response::success(['message' => 'User blocked successfully']);
    }

    /**
     * PUT /admin/users/{id}/unblock
     * Unblock user
     */
    public function unblock(int $id): void
    {
        if (!$this->authorize('users', 'block')) {
            return;
        }

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }

        $this->db->update('users', [
            'is_blocked' => 0,
            'block_reason' => null,
            'blocked_at' => null,
        ], 'id = ?', [$id]);

        $this->logActivity('user.unblock', 'user', $id, null, null);

        Response::success(['message' => 'User unblocked successfully']);
    }

    /**
     * POST /admin/users/{id}/cashback
     * Adjust user cashback
     */
    public function adjustCashback(int $id): void
    {
        if (!$this->authorize('users', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'amount' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ]);

        if ($data === null) {
            return;
        }

        $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }

        $amount = (float) $data['amount'];
        $newBalance = max(0, $user['cashback_balance'] + $amount);

        $this->db->update('users', [
            'cashback_balance' => $newBalance,
        ], 'id = ?', [$id]);

        $this->db->insert('cashback_transactions', [
            'user_id' => $id,
            'type' => $amount >= 0 ? 'credit' : 'debit',
            'amount' => abs($amount),
            'balance_after' => $newBalance,
            'description' => $data['reason'] . ' (Admin adjustment)',
        ]);

        $this->logActivity('user.cashback_adjust', 'user', $id, [
            'old_balance' => $user['cashback_balance']
        ], [
            'new_balance' => $newBalance,
            'amount' => $amount,
            'reason' => $data['reason'],
        ]);

        Response::success([
            'message' => 'Cashback adjusted successfully',
            'new_balance' => $newBalance,
        ]);
    }

    /**
     * GET /admin/users/export
     * Export users to CSV
     */
    public function export(): void
    {
        if (!$this->authorize('users', 'view')) {
            return;
        }

        $users = $this->db->query("
            SELECT
                u.id, u.telegram_id, u.username, u.first_name, u.last_name,
                u.phone, u.email, u.language_code,
                u.cashback_balance, u.total_spent,
                (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as bookings,
                (SELECT COUNT(*) FROM users WHERE referred_by = u.id) as referrals,
                u.created_at
            FROM users u
            ORDER BY u.created_at DESC
        ");

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'ID', 'Telegram ID', 'Username', 'First Name', 'Last Name',
            'Phone', 'Email', 'Language', 'Cashback', 'Total Spent',
            'Bookings', 'Referrals', 'Created'
        ]);

        foreach ($users as $row) {
            fputcsv($output, array_values($row));
        }

        fclose($output);
        exit;
    }
}
