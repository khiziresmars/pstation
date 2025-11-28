<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Services\AnalyticsService;

/**
 * Admin Analytics Controller
 * Provides comprehensive analytics and reporting endpoints
 */
class AnalyticsController
{
    private AnalyticsService $analyticsService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsService();
    }

    /**
     * GET /api/admin/analytics/dashboard
     * Get complete dashboard summary
     */
    public function dashboard(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $summary = $this->analyticsService->getDashboardSummary($period);

        return Response::json($summary);
    }

    /**
     * GET /api/admin/analytics/revenue
     * Get detailed revenue analytics
     */
    public function revenue(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $groupBy = $request->get('group_by', 'day');

        $chart = $this->analyticsService->getRevenueChart($period, $groupBy);

        return Response::json(['chart' => $chart]);
    }

    /**
     * GET /api/admin/analytics/bookings
     * Get detailed bookings analytics
     */
    public function bookings(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $groupBy = $request->get('group_by', 'day');

        $chart = $this->analyticsService->getBookingsChart($period, $groupBy);

        return Response::json(['chart' => $chart]);
    }

    /**
     * GET /api/admin/analytics/funnel
     * Get conversion funnel
     */
    public function funnel(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $funnel = $this->analyticsService->getConversionFunnel($period);

        return Response::json(['funnel' => $funnel]);
    }

    /**
     * GET /api/admin/analytics/customers
     * Get top customers
     */
    public function customers(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $limit = min((int) $request->get('limit', 10), 50);

        $customers = $this->analyticsService->getTopCustomers($period, $limit);

        return Response::json(['customers' => $customers]);
    }

    /**
     * GET /api/admin/analytics/popular-items
     * Get popular items
     */
    public function popularItems(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $limit = min((int) $request->get('limit', 10), 50);

        $startDate = match ($period) {
            '7d' => date('Y-m-d', strtotime('-7 days')),
            '30d' => date('Y-m-d', strtotime('-30 days')),
            '90d' => date('Y-m-d', strtotime('-90 days')),
            default => date('Y-m-d', strtotime('-30 days')),
        };

        $items = $this->analyticsService->getPopularItems($startDate, $limit);

        return Response::json(['items' => $items]);
    }

    /**
     * GET /api/admin/analytics/payment-methods
     * Get payment methods breakdown
     */
    public function paymentMethods(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $breakdown = $this->analyticsService->getPaymentMethodsBreakdown($period);

        return Response::json(['payment_methods' => $breakdown]);
    }

    /**
     * GET /api/admin/analytics/addons
     * Get addons statistics
     */
    public function addons(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $stats = $this->analyticsService->getAddonsStats($period);

        return Response::json($stats);
    }

    /**
     * GET /api/admin/analytics/packages
     * Get packages statistics
     */
    public function packages(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $stats = $this->analyticsService->getPackagesStats($period);

        return Response::json(['packages' => $stats]);
    }

    /**
     * GET /api/admin/analytics/gift-cards
     * Get gift cards statistics
     */
    public function giftCards(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $stats = $this->analyticsService->getGiftCardsStats($period);

        return Response::json($stats);
    }

    /**
     * GET /api/admin/analytics/vendors
     * Get vendor statistics
     */
    public function vendors(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $stats = $this->analyticsService->getVendorStats($period);

        return Response::json($stats);
    }

    /**
     * GET /api/admin/analytics/export
     * Export analytics data
     */
    public function export(Request $request): Response
    {
        $period = $request->get('period', '30d');
        $type = $request->get('type', 'summary');
        $format = $request->get('format', 'json');

        $data = match ($type) {
            'summary' => $this->analyticsService->getDashboardSummary($period),
            'revenue' => $this->analyticsService->getRevenueChart($period, 'day'),
            'bookings' => $this->analyticsService->getBookingsChart($period, 'day'),
            default => $this->analyticsService->getDashboardSummary($period),
        };

        if ($format === 'csv') {
            // Convert to CSV
            $csv = $this->arrayToCsv($data);
            return Response::csv($csv, "analytics_{$type}_{$period}.csv");
        }

        return Response::json($data);
    }

    /**
     * Convert array to CSV
     */
    private function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Flatten and write
        $flattened = $this->flattenArray($data);
        fputcsv($output, array_keys($flattened));
        fputcsv($output, array_values($flattened));

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Flatten nested array
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}_{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
