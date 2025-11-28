<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Analytics Service
 * Provides comprehensive analytics and reporting for admin dashboard
 */
class AnalyticsService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get dashboard summary
     */
    public function getDashboardSummary(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);
        $prevStartDate = $this->getPreviousStartDate($period);

        return [
            'period' => $period,
            'revenue' => $this->getRevenueStats($startDate, $prevStartDate),
            'bookings' => $this->getBookingStats($startDate, $prevStartDate),
            'users' => $this->getUserStats($startDate, $prevStartDate),
            'popular_items' => $this->getPopularItems($startDate, 10),
            'recent_activity' => $this->getRecentActivity(10),
        ];
    }

    /**
     * Get revenue statistics
     */
    public function getRevenueStats(string $startDate, string $prevStartDate): array
    {
        // Current period revenue
        $current = $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(total_price_thb), 0) as total,
                COALESCE(SUM(CASE WHEN bookable_type = 'vessel' THEN total_price_thb ELSE 0 END), 0) as vessels,
                COALESCE(SUM(CASE WHEN bookable_type = 'tour' THEN total_price_thb ELSE 0 END), 0) as tours,
                COUNT(*) as count
             FROM bookings
             WHERE status IN ('paid', 'completed')
               AND created_at >= ?",
            [$startDate]
        );

        // Previous period revenue
        $previous = $this->db->fetchOne(
            "SELECT COALESCE(SUM(total_price_thb), 0) as total
             FROM bookings
             WHERE status IN ('paid', 'completed')
               AND created_at >= ? AND created_at < ?",
            [$prevStartDate, $startDate]
        );

        $prevTotal = (float) $previous['total'];
        $currentTotal = (float) $current['total'];
        $changePercent = $prevTotal > 0 ? (($currentTotal - $prevTotal) / $prevTotal) * 100 : 0;

        return [
            'total_thb' => $currentTotal,
            'change_percent' => round($changePercent, 1),
            'by_type' => [
                'vessels' => (float) $current['vessels'],
                'tours' => (float) $current['tours'],
            ],
            'transactions' => (int) $current['count'],
        ];
    }

    /**
     * Get booking statistics
     */
    public function getBookingStats(string $startDate, string $prevStartDate): array
    {
        // Current period bookings
        $current = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM bookings WHERE created_at >= ?",
            [$startDate]
        );

        // Previous period bookings
        $previous = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM bookings WHERE created_at >= ? AND created_at < ?",
            [$prevStartDate, $startDate]
        );

        $prevTotal = (int) $previous['total'];
        $currentTotal = (int) $current['total'];
        $changePercent = $prevTotal > 0 ? (($currentTotal - $prevTotal) / $prevTotal) * 100 : 0;

        // By status
        $byStatus = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count
             FROM bookings WHERE created_at >= ?
             GROUP BY status",
            [$startDate]
        );

        $statusCounts = [];
        foreach ($byStatus as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }

        return [
            'total' => $currentTotal,
            'change_percent' => round($changePercent, 1),
            'by_status' => $statusCounts,
        ];
    }

    /**
     * Get user statistics
     */
    public function getUserStats(string $startDate, string $prevStartDate): array
    {
        $total = $this->db->fetchOne("SELECT COUNT(*) as count FROM users");
        $new = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE created_at >= ?",
            [$startDate]
        );
        $active = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT user_id) as count FROM bookings WHERE created_at >= ?",
            [$startDate]
        );

        return [
            'total' => (int) $total['count'],
            'new' => (int) $new['count'],
            'active' => (int) $active['count'],
        ];
    }

    /**
     * Get popular items
     */
    public function getPopularItems(string $startDate, int $limit = 10): array
    {
        $items = $this->db->fetchAll(
            "SELECT
                b.bookable_type as type,
                b.bookable_id as id,
                COALESCE(v.name, t.name_en) as name,
                COUNT(*) as bookings_count,
                COALESCE(SUM(b.total_price_thb), 0) as revenue_thb
             FROM bookings b
             LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
             LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
             WHERE b.created_at >= ?
               AND b.status NOT IN ('cancelled', 'refunded')
             GROUP BY b.bookable_type, b.bookable_id, v.name, t.name_en
             ORDER BY bookings_count DESC
             LIMIT ?",
            [$startDate, $limit]
        );

        return array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'type' => $item['type'],
                'name' => $item['name'],
                'bookings_count' => (int) $item['bookings_count'],
                'revenue_thb' => (float) $item['revenue_thb'],
            ];
        }, $items);
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(int $limit = 10): array
    {
        $bookings = $this->db->fetchAll(
            "SELECT
                b.booking_reference,
                b.status,
                b.total_price_thb,
                b.created_at,
                COALESCE(v.name, t.name_en) as item_name,
                u.first_name, u.last_name
             FROM bookings b
             LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
             LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
             LEFT JOIN users u ON b.user_id = u.id
             ORDER BY b.created_at DESC
             LIMIT ?",
            [$limit]
        );

        return array_map(function ($b) {
            return [
                'reference' => $b['booking_reference'],
                'status' => $b['status'],
                'amount_thb' => (float) $b['total_price_thb'],
                'item' => $b['item_name'],
                'customer' => trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')),
                'created_at' => $b['created_at'],
            ];
        }, $bookings);
    }

    /**
     * Get revenue chart data
     */
    public function getRevenueChart(string $period = '30d', string $groupBy = 'day'): array
    {
        $startDate = $this->getStartDate($period);

        $format = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $data = $this->db->fetchAll(
            "SELECT
                DATE_FORMAT(created_at, ?) as label,
                COALESCE(SUM(CASE WHEN bookable_type = 'vessel' THEN total_price_thb ELSE 0 END), 0) as vessels,
                COALESCE(SUM(CASE WHEN bookable_type = 'tour' THEN total_price_thb ELSE 0 END), 0) as tours,
                COALESCE(SUM(total_price_thb), 0) as total
             FROM bookings
             WHERE status IN ('paid', 'completed')
               AND created_at >= ?
             GROUP BY DATE_FORMAT(created_at, ?)
             ORDER BY label ASC",
            [$format, $startDate, $format]
        );

        $labels = [];
        $vessels = [];
        $tours = [];
        $total = [];

        foreach ($data as $row) {
            $labels[] = $row['label'];
            $vessels[] = (float) $row['vessels'];
            $tours[] = (float) $row['tours'];
            $total[] = (float) $row['total'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Vessels', 'data' => $vessels, 'color' => '#3B82F6'],
                ['label' => 'Tours', 'data' => $tours, 'color' => '#10B981'],
                ['label' => 'Total', 'data' => $total, 'color' => '#8B5CF6'],
            ],
        ];
    }

    /**
     * Get bookings chart data
     */
    public function getBookingsChart(string $period = '30d', string $groupBy = 'day'): array
    {
        $startDate = $this->getStartDate($period);

        $format = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $data = $this->db->fetchAll(
            "SELECT
                DATE_FORMAT(created_at, ?) as label,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
             FROM bookings
             WHERE created_at >= ?
             GROUP BY DATE_FORMAT(created_at, ?)
             ORDER BY label ASC",
            [$format, $startDate, $format]
        );

        $labels = [];
        $totalData = [];
        $completedData = [];
        $cancelledData = [];

        foreach ($data as $row) {
            $labels[] = $row['label'];
            $totalData[] = (int) $row['total'];
            $completedData[] = (int) $row['completed'];
            $cancelledData[] = (int) $row['cancelled'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Total', 'data' => $totalData, 'color' => '#3B82F6'],
                ['label' => 'Completed', 'data' => $completedData, 'color' => '#10B981'],
                ['label' => 'Cancelled', 'data' => $cancelledData, 'color' => '#EF4444'],
            ],
        ];
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);

        $stats = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status != 'pending' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status IN ('paid', 'completed') THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM bookings
             WHERE created_at >= ?",
            [$startDate]
        );

        $total = (int) $stats['total'];

        return [
            ['stage' => 'Created', 'count' => $total, 'percent' => 100],
            ['stage' => 'Confirmed', 'count' => (int) $stats['confirmed'], 'percent' => $total > 0 ? round(($stats['confirmed'] / $total) * 100, 1) : 0],
            ['stage' => 'Paid', 'count' => (int) $stats['paid'], 'percent' => $total > 0 ? round(($stats['paid'] / $total) * 100, 1) : 0],
            ['stage' => 'Completed', 'count' => (int) $stats['completed'], 'percent' => $total > 0 ? round(($stats['completed'] / $total) * 100, 1) : 0],
        ];
    }

    /**
     * Get top customers
     */
    public function getTopCustomers(string $period = '30d', int $limit = 10): array
    {
        $startDate = $this->getStartDate($period);

        return $this->db->fetchAll(
            "SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.username,
                COUNT(b.id) as bookings_count,
                COALESCE(SUM(b.total_price_thb), 0) as total_spent_thb
             FROM users u
             JOIN bookings b ON u.id = b.user_id
             WHERE b.created_at >= ?
               AND b.status IN ('paid', 'completed')
             GROUP BY u.id, u.first_name, u.last_name, u.username
             ORDER BY total_spent_thb DESC
             LIMIT ?",
            [$startDate, $limit]
        );
    }

    /**
     * Get payment methods breakdown
     */
    public function getPaymentMethodsBreakdown(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);

        $data = $this->db->fetchAll(
            "SELECT
                COALESCE(payment_method, 'pending') as method,
                COUNT(*) as count,
                COALESCE(SUM(total_price_thb), 0) as amount_thb
             FROM bookings
             WHERE created_at >= ?
             GROUP BY payment_method
             ORDER BY amount_thb DESC",
            [$startDate]
        );

        return array_map(function ($row) {
            return [
                'method' => $row['method'],
                'count' => (int) $row['count'],
                'amount_thb' => (float) $row['amount_thb'],
            ];
        }, $data);
    }

    /**
     * Get addons statistics
     */
    public function getAddonsStats(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);

        // Top addons
        $topAddons = $this->db->fetchAll(
            "SELECT
                a.id,
                a.name_en as name,
                ac.name_en as category,
                COUNT(ba.id) as usage_count,
                COALESCE(SUM(ba.price_thb * ba.quantity), 0) as revenue_thb
             FROM addons a
             JOIN addon_categories ac ON a.category_id = ac.id
             LEFT JOIN booking_addons ba ON a.id = ba.addon_id
             LEFT JOIN bookings b ON ba.booking_id = b.id AND b.created_at >= ?
             GROUP BY a.id, a.name_en, ac.name_en
             ORDER BY usage_count DESC
             LIMIT 10",
            [$startDate]
        );

        // Total addon revenue
        $totalAddonRevenue = $this->db->fetchOne(
            "SELECT COALESCE(SUM(addons_price_thb), 0) as total
             FROM bookings
             WHERE created_at >= ? AND status IN ('paid', 'completed')",
            [$startDate]
        );

        return [
            'top_addons' => $topAddons,
            'total_revenue_thb' => (float) $totalAddonRevenue['total'],
        ];
    }

    /**
     * Get packages statistics
     */
    public function getPackagesStats(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);

        $packages = $this->db->fetchAll(
            "SELECT
                p.id,
                p.name_en as name,
                p.slug,
                COUNT(b.id) as usage_count,
                COALESCE(SUM(b.total_price_thb), 0) as revenue_thb
             FROM packages p
             LEFT JOIN bookings b ON p.id = b.package_id AND b.created_at >= ?
             GROUP BY p.id, p.name_en, p.slug
             ORDER BY usage_count DESC",
            [$startDate]
        );

        return $packages;
    }

    /**
     * Get gift cards statistics
     */
    public function getGiftCardsStats(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);

        $stats = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_issued,
                COALESCE(SUM(amount_thb), 0) as total_value_thb,
                COALESCE(SUM(amount_thb - balance_thb), 0) as total_redeemed_thb,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
             FROM gift_cards
             WHERE created_at >= ?",
            [$startDate]
        );

        return [
            'total_issued' => (int) $stats['total_issued'],
            'total_value_thb' => (float) $stats['total_value_thb'],
            'total_redeemed_thb' => (float) $stats['total_redeemed_thb'],
            'active_count' => (int) $stats['active_count'],
        ];
    }

    /**
     * Get vendor statistics
     */
    public function getVendorStats(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);

        $vendors = $this->db->fetchAll(
            "SELECT
                v.id,
                v.company_name,
                COUNT(b.id) as bookings_count,
                COALESCE(SUM(b.total_price_thb), 0) as total_revenue_thb,
                COALESCE(SUM(b.vendor_commission_thb), 0) as total_commission_thb
             FROM vendors v
             LEFT JOIN vessels vs ON v.id = vs.vendor_id
             LEFT JOIN tours t ON v.id = t.vendor_id
             LEFT JOIN bookings b ON (b.bookable_type = 'vessel' AND b.bookable_id = vs.id)
                                  OR (b.bookable_type = 'tour' AND b.bookable_id = t.id)
             WHERE b.created_at >= ?
             GROUP BY v.id, v.company_name
             ORDER BY total_revenue_thb DESC
             LIMIT 10",
            [$startDate]
        );

        $pendingPayouts = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount_thb), 0) as total
             FROM vendor_payouts
             WHERE status = 'pending'"
        );

        return [
            'top_vendors' => $vendors,
            'pending_payouts_thb' => (float) $pendingPayouts['total'],
        ];
    }

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period): string
    {
        return match ($period) {
            '1d' => date('Y-m-d H:i:s', strtotime('-1 day')),
            '7d' => date('Y-m-d H:i:s', strtotime('-7 days')),
            '30d' => date('Y-m-d H:i:s', strtotime('-30 days')),
            '90d' => date('Y-m-d H:i:s', strtotime('-90 days')),
            '1y' => date('Y-m-d H:i:s', strtotime('-1 year')),
            'all' => '1970-01-01 00:00:00',
            default => date('Y-m-d H:i:s', strtotime('-30 days')),
        };
    }

    /**
     * Get previous period start date for comparison
     */
    private function getPreviousStartDate(string $period): string
    {
        return match ($period) {
            '1d' => date('Y-m-d H:i:s', strtotime('-2 days')),
            '7d' => date('Y-m-d H:i:s', strtotime('-14 days')),
            '30d' => date('Y-m-d H:i:s', strtotime('-60 days')),
            '90d' => date('Y-m-d H:i:s', strtotime('-180 days')),
            '1y' => date('Y-m-d H:i:s', strtotime('-2 years')),
            'all' => '1970-01-01 00:00:00',
            default => date('Y-m-d H:i:s', strtotime('-60 days')),
        };
    }
}
