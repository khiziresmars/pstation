<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;

/**
 * Admin Dashboard Controller
 * Overview and analytics
 */
class DashboardController extends BaseAdminController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * GET /admin/dashboard
     * Get dashboard overview
     */
    public function index(): void
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m-01');
        $lastMonth = date('Y-m-01', strtotime('-1 month'));

        Response::success([
            'today' => $this->getTodayStats(),
            'this_month' => $this->getMonthStats($thisMonth),
            'last_month' => $this->getMonthStats($lastMonth),
            'recent_bookings' => $this->getRecentBookings(),
            'pending_actions' => $this->getPendingActions(),
            'revenue_chart' => $this->getRevenueChart(),
            'popular_items' => $this->getPopularItems(),
        ]);
    }

    /**
     * GET /admin/dashboard/stats
     * Get quick stats for header
     */
    public function quickStats(): void
    {
        $today = date('Y-m-d');

        $stats = $this->db->queryOne("
            SELECT
                (SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?) as today_bookings,
                (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings,
                (SELECT COUNT(*) FROM reviews WHERE status = 'pending') as pending_reviews,
                (SELECT COALESCE(SUM(total_price_thb), 0) FROM bookings WHERE DATE(created_at) = ? AND status IN ('paid', 'confirmed', 'completed')) as today_revenue
        ", [$today, $today]);

        Response::success($stats);
    }

    /**
     * GET /admin/analytics
     * Detailed analytics
     */
    public function analytics(): void
    {
        if (!$this->authorize('analytics', 'view')) {
            return;
        }

        $period = $_GET['period'] ?? '30d';
        $startDate = $this->getStartDate($period);

        Response::success([
            'bookings' => $this->getBookingAnalytics($startDate),
            'revenue' => $this->getRevenueAnalytics($startDate),
            'customers' => $this->getCustomerAnalytics($startDate),
            'items' => $this->getItemAnalytics($startDate),
            'conversions' => $this->getConversionAnalytics($startDate),
        ]);
    }

    private function getTodayStats(): array
    {
        $today = date('Y-m-d');

        return $this->db->queryOne("
            SELECT
                COUNT(*) as bookings,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'confirmed', 'completed') THEN total_price_thb ELSE 0 END), 0) as revenue,
                COALESCE(SUM(adults_count + children_count), 0) as guests,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
            FROM bookings
            WHERE DATE(created_at) = ?
        ", [$today]);
    }

    private function getMonthStats(string $monthStart): array
    {
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        return $this->db->queryOne("
            SELECT
                COUNT(*) as bookings,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'confirmed', 'completed') THEN total_price_thb ELSE 0 END), 0) as revenue,
                COALESCE(SUM(adults_count + children_count), 0) as guests,
                COUNT(DISTINCT user_id) as unique_customers
            FROM bookings
            WHERE created_at BETWEEN ? AND ?
        ", [$monthStart, $monthEnd . ' 23:59:59']);
    }

    private function getRecentBookings(): array
    {
        return $this->db->query("
            SELECT
                b.id, b.booking_reference, b.bookable_type, b.booking_date,
                b.total_price_thb, b.status, b.created_at,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as item_name,
                u.first_name, u.last_name, u.username
            FROM bookings b
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            LEFT JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC
            LIMIT 10
        ");
    }

    private function getPendingActions(): array
    {
        return [
            'pending_bookings' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'"
            )['count'],
            'unconfirmed_payments' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending' AND payment_method = 'bank_transfer'"
            )['count'],
            'pending_reviews' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'"
            )['count'],
            'today_departures' => $this->db->queryOne(
                "SELECT COUNT(*) as count FROM bookings WHERE booking_date = CURDATE() AND status IN ('confirmed', 'paid')"
            )['count'],
        ];
    }

    private function getRevenueChart(): array
    {
        $days = 30;
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $this->db->query("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as bookings,
                COALESCE(SUM(CASE WHEN status IN ('paid', 'confirmed', 'completed') THEN total_price_thb ELSE 0 END), 0) as revenue
            FROM bookings
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate]);
    }

    private function getPopularItems(): array
    {
        $startDate = date('Y-m-d', strtotime('-30 days'));

        return $this->db->query("
            SELECT
                b.bookable_type as type,
                b.bookable_id as id,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as name,
                COUNT(*) as bookings,
                COALESCE(SUM(b.total_price_thb), 0) as revenue
            FROM bookings b
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            WHERE b.created_at >= ?
            GROUP BY b.bookable_type, b.bookable_id, name
            ORDER BY bookings DESC
            LIMIT 5
        ", [$startDate]);
    }

    private function getStartDate(string $period): string
    {
        return match ($period) {
            '7d' => date('Y-m-d', strtotime('-7 days')),
            '30d' => date('Y-m-d', strtotime('-30 days')),
            '90d' => date('Y-m-d', strtotime('-90 days')),
            '1y' => date('Y-m-d', strtotime('-1 year')),
            default => date('Y-m-d', strtotime('-30 days')),
        };
    }

    private function getBookingAnalytics(string $startDate): array
    {
        return $this->db->query("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                COUNT(CASE WHEN bookable_type = 'vessel' THEN 1 END) as vessels,
                COUNT(CASE WHEN bookable_type = 'tour' THEN 1 END) as tours
            FROM bookings
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate]);
    }

    private function getRevenueAnalytics(string $startDate): array
    {
        $daily = $this->db->query("
            SELECT
                DATE(created_at) as date,
                COALESCE(SUM(total_price_thb), 0) as total,
                COALESCE(SUM(promo_discount_thb), 0) as discounts,
                COALESCE(SUM(cashback_used_thb), 0) as cashback_used,
                COALESCE(SUM(cashback_earned_thb), 0) as cashback_earned
            FROM bookings
            WHERE created_at >= ? AND status IN ('paid', 'confirmed', 'completed')
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate]);

        $byPaymentMethod = $this->db->query("
            SELECT
                payment_method,
                COUNT(*) as count,
                COALESCE(SUM(total_price_thb), 0) as amount
            FROM bookings
            WHERE created_at >= ? AND status IN ('paid', 'confirmed', 'completed')
            GROUP BY payment_method
        ", [$startDate]);

        return [
            'daily' => $daily,
            'by_payment_method' => $byPaymentMethod,
        ];
    }

    private function getCustomerAnalytics(string $startDate): array
    {
        $newCustomers = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM users
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate]);

        $topCustomers = $this->db->query("
            SELECT
                u.id, u.first_name, u.last_name, u.username,
                COUNT(*) as bookings,
                COALESCE(SUM(b.total_price_thb), 0) as total_spent
            FROM users u
            JOIN bookings b ON u.id = b.user_id
            WHERE b.created_at >= ? AND b.status IN ('paid', 'confirmed', 'completed')
            GROUP BY u.id
            ORDER BY total_spent DESC
            LIMIT 10
        ", [$startDate]);

        return [
            'new_customers' => $newCustomers,
            'top_customers' => $topCustomers,
        ];
    }

    private function getItemAnalytics(string $startDate): array
    {
        $vessels = $this->db->query("
            SELECT
                v.id, v.name,
                COUNT(b.id) as bookings,
                COALESCE(SUM(b.total_price_thb), 0) as revenue,
                COALESCE(AVG(r.rating), 0) as avg_rating
            FROM vessels v
            LEFT JOIN bookings b ON v.id = b.bookable_id AND b.bookable_type = 'vessel' AND b.created_at >= ?
            LEFT JOIN reviews r ON v.id = r.reviewable_id AND r.reviewable_type = 'vessel'
            GROUP BY v.id
            ORDER BY bookings DESC
        ", [$startDate]);

        $tours = $this->db->query("
            SELECT
                t.id, t.name_en as name,
                COUNT(b.id) as bookings,
                COALESCE(SUM(b.total_price_thb), 0) as revenue,
                COALESCE(AVG(r.rating), 0) as avg_rating
            FROM tours t
            LEFT JOIN bookings b ON t.id = b.bookable_id AND b.bookable_type = 'tour' AND b.created_at >= ?
            LEFT JOIN reviews r ON t.id = r.reviewable_id AND r.reviewable_type = 'tour'
            GROUP BY t.id
            ORDER BY bookings DESC
        ", [$startDate]);

        return [
            'vessels' => $vessels,
            'tours' => $tours,
        ];
    }

    private function getConversionAnalytics(string $startDate): array
    {
        $stats = $this->db->queryOne("
            SELECT
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                COUNT(CASE WHEN promo_code_id IS NOT NULL THEN 1 END) as with_promo,
                COUNT(CASE WHEN cashback_used_thb > 0 THEN 1 END) as with_cashback
            FROM bookings
            WHERE created_at >= ?
        ", [$startDate]);

        return $stats;
    }
}
