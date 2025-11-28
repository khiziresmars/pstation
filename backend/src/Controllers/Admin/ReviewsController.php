<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;
use App\Core\Request;

/**
 * Admin Reviews Controller
 * Manage reviews
 */
class ReviewsController extends BaseAdminController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * GET /admin/reviews
     * List all reviews
     */
    public function index(): void
    {
        if (!$this->authorize('reviews', 'view')) {
            return;
        }

        $pagination = $this->getPagination();
        $sort = $this->getSort(['rating', 'status', 'created_at'], 'created_at');

        $where = ['1=1'];
        $params = [];

        if (isset($_GET['status'])) {
            $where[] = 'r.status = ?';
            $params[] = $_GET['status'];
        }

        if (isset($_GET['rating'])) {
            $where[] = 'r.rating = ?';
            $params[] = (int) $_GET['rating'];
        }

        if (isset($_GET['type'])) {
            $where[] = 'r.reviewable_type = ?';
            $params[] = $_GET['type'];
        }

        if (isset($_GET['search'])) {
            $where[] = '(r.comment LIKE ? OR u.first_name LIKE ? OR u.username LIKE ?)';
            $search = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->queryOne("
            SELECT COUNT(*) as count
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE {$whereClause}
        ", $params)['count'];

        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];

        $reviews = $this->db->query("
            SELECT r.*,
                u.first_name, u.last_name, u.username,
                CASE
                    WHEN r.reviewable_type = 'vessel' THEN v.name
                    WHEN r.reviewable_type = 'tour' THEN t.name_en
                END as item_name,
                CASE
                    WHEN r.reviewable_type = 'vessel' THEN v.thumbnail
                    WHEN r.reviewable_type = 'tour' THEN t.thumbnail
                END as item_thumbnail
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN vessels v ON r.reviewable_type = 'vessel' AND r.reviewable_id = v.id
            LEFT JOIN tours t ON r.reviewable_type = 'tour' AND r.reviewable_id = t.id
            WHERE {$whereClause}
            ORDER BY r.{$sort['field']} {$sort['direction']}
            LIMIT ? OFFSET ?
        ", $params);

        // Parse photos JSON
        foreach ($reviews as &$review) {
            $review['photos'] = json_decode($review['photos'] ?? '[]', true);
        }

        $this->paginate($reviews, $total, $pagination['page'], $pagination['limit']);
    }

    /**
     * GET /admin/reviews/{id}
     * Get review details
     */
    public function show(int $id): void
    {
        if (!$this->authorize('reviews', 'view')) {
            return;
        }

        $review = $this->db->queryOne("
            SELECT r.*,
                u.id as user_id, u.first_name, u.last_name, u.username, u.telegram_id,
                CASE
                    WHEN r.reviewable_type = 'vessel' THEN v.name
                    WHEN r.reviewable_type = 'tour' THEN t.name_en
                END as item_name,
                b.booking_reference, b.booking_date
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN vessels v ON r.reviewable_type = 'vessel' AND r.reviewable_id = v.id
            LEFT JOIN tours t ON r.reviewable_type = 'tour' AND r.reviewable_id = t.id
            LEFT JOIN bookings b ON r.booking_id = b.id
            WHERE r.id = ?
        ", [$id]);

        if (!$review) {
            Response::notFound('Review not found');
            return;
        }

        $review['photos'] = json_decode($review['photos'] ?? '[]', true);

        // Get user's other reviews
        $review['user_reviews_count'] = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM reviews WHERE user_id = ?",
            [$review['user_id']]
        )['count'];

        Response::success($review);
    }

    /**
     * PUT /admin/reviews/{id}/approve
     * Approve review
     */
    public function approve(int $id): void
    {
        if (!$this->authorize('reviews', 'approve')) {
            return;
        }

        $review = $this->db->queryOne("SELECT * FROM reviews WHERE id = ?", [$id]);
        if (!$review) {
            Response::notFound('Review not found');
            return;
        }

        $this->db->update('reviews', [
            'status' => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->logActivity('review.approve', 'review', $id, null, null);

        Response::success(['message' => 'Review approved successfully']);
    }

    /**
     * PUT /admin/reviews/{id}/reject
     * Reject review
     */
    public function reject(int $id): void
    {
        if (!$this->authorize('reviews', 'approve')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        $review = $this->db->queryOne("SELECT * FROM reviews WHERE id = ?", [$id]);
        if (!$review) {
            Response::notFound('Review not found');
            return;
        }

        $this->db->update('reviews', [
            'status' => 'rejected',
            'rejection_reason' => $data['reason'] ?? null,
        ], 'id = ?', [$id]);

        $this->logActivity('review.reject', 'review', $id, null, ['reason' => $data['reason'] ?? null]);

        Response::success(['message' => 'Review rejected']);
    }

    /**
     * DELETE /admin/reviews/{id}
     * Delete review
     */
    public function destroy(int $id): void
    {
        if (!$this->authorize('reviews', 'delete')) {
            return;
        }

        $review = $this->db->queryOne("SELECT * FROM reviews WHERE id = ?", [$id]);
        if (!$review) {
            Response::notFound('Review not found');
            return;
        }

        $this->db->delete('reviews', 'id = ?', [$id]);
        $this->logActivity('review.delete', 'review', $id, $review, null);

        Response::success(['message' => 'Review deleted successfully']);
    }

    /**
     * PUT /admin/reviews/{id}/reply
     * Add admin reply to review
     */
    public function reply(int $id): void
    {
        if (!$this->authorize('reviews', 'approve')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($data === null) {
            return;
        }

        $review = $this->db->queryOne("SELECT * FROM reviews WHERE id = ?", [$id]);
        if (!$review) {
            Response::notFound('Review not found');
            return;
        }

        $this->db->update('reviews', [
            'admin_reply' => $data['reply'],
            'admin_reply_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        $this->logActivity('review.reply', 'review', $id, null, ['reply' => $data['reply']]);

        Response::success(['message' => 'Reply added successfully']);
    }

    /**
     * POST /admin/reviews/bulk-approve
     * Bulk approve reviews
     */
    public function bulkApprove(): void
    {
        if (!$this->authorize('reviews', 'approve')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'ids' => 'required|array',
        ]);

        if ($data === null) {
            return;
        }

        $ids = array_map('intval', $data['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $this->db->execute("
            UPDATE reviews SET status = 'approved', approved_at = NOW()
            WHERE id IN ({$placeholders}) AND status = 'pending'
        ", $ids);

        $this->logActivity('review.bulk_approve', null, null, null, ['ids' => $ids]);

        Response::success(['message' => count($ids) . ' reviews approved']);
    }

    /**
     * GET /admin/reviews/stats
     * Get review statistics
     */
    public function stats(): void
    {
        if (!$this->authorize('reviews', 'view')) {
            return;
        }

        $stats = $this->db->queryOne("
            SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
                COALESCE(AVG(rating), 0) as avg_rating
            FROM reviews
        ");

        $ratingDistribution = $this->db->query("
            SELECT rating, COUNT(*) as count
            FROM reviews
            WHERE status = 'approved'
            GROUP BY rating
            ORDER BY rating DESC
        ");

        $recentTrend = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count, AVG(rating) as avg_rating
            FROM reviews
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");

        Response::success([
            'overview' => $stats,
            'rating_distribution' => $ratingDistribution,
            'recent_trend' => $recentTrend,
        ]);
    }
}
