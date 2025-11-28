<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * Vessel Service
 * Handles vessel catalog operations
 */
class VesselService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Get all vessels with filters
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $where = ['is_active = 1'];
        $params = [];

        // Type filter
        if (!empty($filters['type'])) {
            $where[] = 'type = ?';
            $params[] = $filters['type'];
        }

        // Capacity filter
        if (!empty($filters['min_capacity'])) {
            $where[] = 'capacity >= ?';
            $params[] = (int) $filters['min_capacity'];
        }

        if (!empty($filters['max_capacity'])) {
            $where[] = 'capacity <= ?';
            $params[] = (int) $filters['max_capacity'];
        }

        // Price filter
        if (!empty($filters['min_price'])) {
            $where[] = 'price_per_day_thb >= ?';
            $params[] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $where[] = 'price_per_day_thb <= ?';
            $params[] = (float) $filters['max_price'];
        }

        // Date availability
        if (!empty($filters['date'])) {
            $where[] = 'id NOT IN (
                SELECT available_id FROM availability
                WHERE available_type = "vessel"
                AND date = ?
                AND is_available = 0
            )';
            $params[] = $filters['date'];
        }

        $whereClause = implode(' AND ', $where);

        // Sorting
        $orderBy = match ($filters['sort'] ?? 'popular') {
            'price_asc' => 'price_per_day_thb ASC',
            'price_desc' => 'price_per_day_thb DESC',
            'rating' => 'rating DESC',
            'newest' => 'created_at DESC',
            default => 'bookings_count DESC, rating DESC',
        };

        $offset = ($page - 1) * $perPage;

        // Get vessels
        $vessels = $this->db->query(
            "SELECT id, type, name, slug, short_description_en, short_description_ru,
                    capacity, length_meters, price_per_hour_thb, price_per_day_thb,
                    captain_included, fuel_included, thumbnail, is_featured, rating, reviews_count
             FROM vessels
             WHERE {$whereClause}
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        // Get total count
        $total = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM vessels WHERE {$whereClause}",
            $params
        );

        return [
            'items' => $vessels,
            'total' => (int) $total['count']
        ];
    }

    /**
     * Get featured vessels
     */
    public function getFeatured(int $limit = 4): array
    {
        return $this->db->query(
            "SELECT id, type, name, slug, short_description_en, short_description_ru,
                    capacity, length_meters, price_per_hour_thb, price_per_day_thb,
                    captain_included, fuel_included, thumbnail, rating, reviews_count
             FROM vessels
             WHERE is_active = 1 AND is_featured = 1
             ORDER BY rating DESC, bookings_count DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get vessel by slug
     */
    public function getBySlug(string $slug): ?array
    {
        $vessel = $this->db->queryOne(
            "SELECT * FROM vessels WHERE slug = ? AND is_active = 1",
            [$slug]
        );

        if (!$vessel) {
            return null;
        }

        // Parse JSON fields
        $vessel['features'] = json_decode($vessel['features'], true) ?? [];
        $vessel['amenities'] = json_decode($vessel['amenities'], true) ?? [];
        $vessel['crew_info'] = json_decode($vessel['crew_info'], true) ?? [];
        $vessel['images'] = json_decode($vessel['images'], true) ?? [];

        // Get extras
        $vessel['extras'] = $this->getExtras($vessel['id']);

        return $vessel;
    }

    /**
     * Get vessel by ID
     */
    public function getById(int $id): ?array
    {
        $vessel = $this->db->queryOne(
            "SELECT * FROM vessels WHERE id = ? AND is_active = 1",
            [$id]
        );

        if ($vessel) {
            $vessel['features'] = json_decode($vessel['features'], true) ?? [];
            $vessel['amenities'] = json_decode($vessel['amenities'], true) ?? [];
            $vessel['crew_info'] = json_decode($vessel['crew_info'], true) ?? [];
            $vessel['images'] = json_decode($vessel['images'], true) ?? [];
        }

        return $vessel;
    }

    /**
     * Get vessel extras
     */
    public function getExtras(?int $vesselId = null): array
    {
        if ($vesselId) {
            return $this->db->query(
                "SELECT * FROM vessel_extras
                 WHERE (vessel_id = ? OR vessel_id IS NULL) AND is_active = 1
                 ORDER BY sort_order",
                [$vesselId]
            );
        }

        return $this->db->query(
            "SELECT * FROM vessel_extras WHERE vessel_id IS NULL AND is_active = 1 ORDER BY sort_order"
        );
    }

    /**
     * Get availability for a vessel
     */
    public function getAvailability(int $vesselId, string $startDate, string $endDate): array
    {
        $availability = $this->db->query(
            "SELECT date, is_available, special_price_thb, note
             FROM availability
             WHERE available_type = 'vessel'
             AND available_id = ?
             AND date BETWEEN ? AND ?",
            [$vesselId, $startDate, $endDate]
        );

        // Get existing bookings
        $bookings = $this->db->query(
            "SELECT booking_date as date
             FROM bookings
             WHERE bookable_type = 'vessel'
             AND bookable_id = ?
             AND status IN ('confirmed', 'paid')
             AND booking_date BETWEEN ? AND ?",
            [$vesselId, $startDate, $endDate]
        );

        $bookedDates = array_column($bookings, 'date');
        $unavailableDates = [];

        foreach ($availability as $a) {
            if (!$a['is_available']) {
                $unavailableDates[$a['date']] = $a['note'] ?? 'Unavailable';
            }
        }

        foreach ($bookedDates as $date) {
            $unavailableDates[$date] = 'Booked';
        }

        return [
            'unavailable_dates' => $unavailableDates,
            'special_prices' => array_column(
                array_filter($availability, fn($a) => $a['special_price_thb'] !== null),
                'special_price_thb',
                'date'
            )
        ];
    }

    /**
     * Get reviews for a vessel
     */
    public function getReviews(int $vesselId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $reviews = $this->db->query(
            "SELECT r.*, u.first_name, u.photo_url
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             WHERE r.bookable_type = 'vessel'
             AND r.bookable_id = ?
             AND r.is_published = 1
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?",
            [$vesselId, $perPage, $offset]
        );

        $total = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM reviews
             WHERE bookable_type = 'vessel' AND bookable_id = ? AND is_published = 1",
            [$vesselId]
        );

        // Get rating breakdown
        $ratings = $this->db->query(
            "SELECT rating, COUNT(*) as count
             FROM reviews
             WHERE bookable_type = 'vessel' AND bookable_id = ? AND is_published = 1
             GROUP BY rating",
            [$vesselId]
        );

        return [
            'items' => $reviews,
            'total' => (int) $total['count'],
            'ratings_breakdown' => array_column($ratings, 'count', 'rating')
        ];
    }

    /**
     * Calculate rental price
     */
    public function calculatePrice(int $vesselId, string $date, int $hours, array $extras = []): array
    {
        $vessel = $this->getById($vesselId);

        if (!$vessel) {
            return ['error' => 'Vessel not found'];
        }

        // Check for special price
        $specialPrice = $this->db->queryOne(
            "SELECT special_price_thb FROM availability
             WHERE available_type = 'vessel' AND available_id = ? AND date = ?",
            [$vesselId, $date]
        );

        // Calculate base price
        if ($hours >= 24) {
            $days = ceil($hours / 24);
            $basePrice = $specialPrice ? $specialPrice['special_price_thb'] * $days : $vessel['price_per_day_thb'] * $days;
        } else {
            $basePrice = $vessel['price_per_hour_thb'] * $hours;
            // Use half-day price if applicable
            if ($hours >= 4 && $hours <= 5 && $vessel['price_half_day_thb']) {
                $basePrice = min($basePrice, $vessel['price_half_day_thb']);
            }
        }

        // Calculate extras
        $extrasTotal = 0;
        $extrasDetails = [];

        foreach ($extras as $extraId => $quantity) {
            $extra = $this->db->queryOne(
                "SELECT * FROM vessel_extras WHERE id = ?",
                [$extraId]
            );

            if ($extra) {
                $extraPrice = match ($extra['price_type']) {
                    'per_hour' => $extra['price_thb'] * $hours * $quantity,
                    'per_person' => $extra['price_thb'] * $quantity,
                    default => $extra['price_thb'] * $quantity,
                };

                $extrasTotal += $extraPrice;
                $extrasDetails[] = [
                    'id' => $extra['id'],
                    'name' => $extra['name_en'],
                    'quantity' => $quantity,
                    'price' => $extraPrice
                ];
            }
        }

        return [
            'base_price_thb' => $basePrice,
            'extras_price_thb' => $extrasTotal,
            'total_thb' => $basePrice + $extrasTotal,
            'extras_details' => $extrasDetails,
            'hours' => $hours,
            'captain_included' => $vessel['captain_included'],
            'fuel_included' => $vessel['fuel_included'],
        ];
    }
}
