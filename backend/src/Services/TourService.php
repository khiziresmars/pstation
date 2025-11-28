<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * Tour Service
 * Handles tour catalog operations
 */
class TourService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
    }

    /**
     * Get all tours with filters
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 12): array
    {
        $where = ['is_active = 1'];
        $params = [];

        // Category filter
        if (!empty($filters['category'])) {
            $where[] = 'category = ?';
            $params[] = $filters['category'];
        }

        // Price filter
        if (!empty($filters['min_price'])) {
            $where[] = 'price_adult_thb >= ?';
            $params[] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $where[] = 'price_adult_thb <= ?';
            $params[] = (float) $filters['max_price'];
        }

        // Duration filter
        if (!empty($filters['max_duration'])) {
            $where[] = 'duration_hours <= ?';
            $params[] = (float) $filters['max_duration'];
        }

        // Date availability
        if (!empty($filters['date'])) {
            // Check day of week
            $dayOfWeek = strtolower(date('l', strtotime($filters['date'])));
            $where[] = "(schedule IS NULL OR JSON_CONTAINS(schedule, ?))";
            $params[] = json_encode($dayOfWeek);

            // Check blackout dates
            $where[] = 'id NOT IN (
                SELECT available_id FROM availability
                WHERE available_type = "tour"
                AND date = ?
                AND is_available = 0
            )';
            $params[] = $filters['date'];
        }

        $whereClause = implode(' AND ', $where);

        // Sorting
        $orderBy = match ($filters['sort'] ?? 'popular') {
            'price_asc' => 'price_adult_thb ASC',
            'price_desc' => 'price_adult_thb DESC',
            'rating' => 'rating DESC',
            'duration' => 'duration_hours ASC',
            default => 'bookings_count DESC, rating DESC',
        };

        $offset = ($page - 1) * $perPage;

        // Get tours
        $tours = $this->db->query(
            "SELECT id, category, name_en, name_ru, name_th, slug,
                    short_description_en, short_description_ru,
                    duration_hours, departure_time, max_participants,
                    price_adult_thb, price_child_thb, thumbnail,
                    is_featured, rating, reviews_count
             FROM tours
             WHERE {$whereClause}
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        // Get total count
        $total = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM tours WHERE {$whereClause}",
            $params
        );

        return [
            'items' => $tours,
            'total' => (int) $total['count']
        ];
    }

    /**
     * Get featured tours
     */
    public function getFeatured(int $limit = 4): array
    {
        return $this->db->query(
            "SELECT id, category, name_en, name_ru, name_th, slug,
                    short_description_en, short_description_ru,
                    duration_hours, price_adult_thb, price_child_thb,
                    thumbnail, rating, reviews_count
             FROM tours
             WHERE is_active = 1 AND is_featured = 1
             ORDER BY rating DESC, bookings_count DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get tour by slug
     */
    public function getBySlug(string $slug): ?array
    {
        $tour = $this->db->queryOne(
            "SELECT * FROM tours WHERE slug = ? AND is_active = 1",
            [$slug]
        );

        if (!$tour) {
            return null;
        }

        // Parse JSON fields
        $tour['includes'] = json_decode($tour['includes'], true) ?? [];
        $tour['excludes'] = json_decode($tour['excludes'], true) ?? [];
        $tour['itinerary'] = json_decode($tour['itinerary'], true) ?? [];
        $tour['highlights'] = json_decode($tour['highlights'], true) ?? [];
        $tour['images'] = json_decode($tour['images'], true) ?? [];
        $tour['schedule'] = json_decode($tour['schedule'], true) ?? [];
        $tour['meeting_point_coordinates'] = json_decode($tour['meeting_point_coordinates'], true);

        return $tour;
    }

    /**
     * Get tour by ID
     */
    public function getById(int $id): ?array
    {
        $tour = $this->db->queryOne(
            "SELECT * FROM tours WHERE id = ? AND is_active = 1",
            [$id]
        );

        if ($tour) {
            $tour['includes'] = json_decode($tour['includes'], true) ?? [];
            $tour['excludes'] = json_decode($tour['excludes'], true) ?? [];
            $tour['itinerary'] = json_decode($tour['itinerary'], true) ?? [];
            $tour['highlights'] = json_decode($tour['highlights'], true) ?? [];
            $tour['images'] = json_decode($tour['images'], true) ?? [];
            $tour['schedule'] = json_decode($tour['schedule'], true) ?? [];
        }

        return $tour;
    }

    /**
     * Get availability for a tour
     */
    public function getAvailability(int $tourId, string $startDate, string $endDate): array
    {
        $tour = $this->getById($tourId);

        if (!$tour) {
            return ['error' => 'Tour not found'];
        }

        // Get blocked dates
        $availability = $this->db->query(
            "SELECT date, is_available, available_slots, booked_slots, note
             FROM availability
             WHERE available_type = 'tour'
             AND available_id = ?
             AND date BETWEEN ? AND ?",
            [$tourId, $startDate, $endDate]
        );

        // Get booking counts per date
        $bookings = $this->db->query(
            "SELECT booking_date as date, SUM(adults_count + children_count) as total_guests
             FROM bookings
             WHERE bookable_type = 'tour'
             AND bookable_id = ?
             AND status IN ('confirmed', 'paid')
             AND booking_date BETWEEN ? AND ?
             GROUP BY booking_date",
            [$tourId, $startDate, $endDate]
        );

        $bookingsByDate = array_column($bookings, 'total_guests', 'date');
        $availabilityByDate = [];

        foreach ($availability as $a) {
            $availabilityByDate[$a['date']] = $a;
        }

        $result = [];
        $schedule = $tour['schedule'] ?? [];
        $maxParticipants = $tour['max_participants'];

        $currentDate = new \DateTime($startDate);
        $endDateTime = new \DateTime($endDate);

        while ($currentDate <= $endDateTime) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayOfWeek = strtolower($currentDate->format('l'));

            $isScheduled = empty($schedule) || in_array($dayOfWeek, $schedule);
            $isBlocked = isset($availabilityByDate[$dateStr]) && !$availabilityByDate[$dateStr]['is_available'];
            $bookedGuests = $bookingsByDate[$dateStr] ?? 0;
            $availableSlots = $maxParticipants - $bookedGuests;

            $result[$dateStr] = [
                'available' => $isScheduled && !$isBlocked && $availableSlots > 0,
                'slots_remaining' => max(0, $availableSlots),
                'reason' => $isBlocked ? ($availabilityByDate[$dateStr]['note'] ?? 'Unavailable') : null
            ];

            $currentDate->modify('+1 day');
        }

        return $result;
    }

    /**
     * Get reviews for a tour
     */
    public function getReviews(int $tourId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $reviews = $this->db->query(
            "SELECT r.*, u.first_name, u.photo_url
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             WHERE r.bookable_type = 'tour'
             AND r.bookable_id = ?
             AND r.is_published = 1
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?",
            [$tourId, $perPage, $offset]
        );

        $total = $this->db->queryOne(
            "SELECT COUNT(*) as count FROM reviews
             WHERE bookable_type = 'tour' AND bookable_id = ? AND is_published = 1",
            [$tourId]
        );

        return [
            'items' => $reviews,
            'total' => (int) $total['count']
        ];
    }

    /**
     * Calculate tour price
     */
    public function calculatePrice(int $tourId, string $date, int $adults, int $children = 0, bool $pickup = false): array
    {
        $tour = $this->getById($tourId);

        if (!$tour) {
            return ['error' => 'Tour not found'];
        }

        $adultPrice = $tour['price_adult_thb'] * $adults;
        $childPrice = $tour['price_child_thb'] * $children;
        $pickupFee = $pickup ? $tour['pickup_fee_thb'] : 0;

        return [
            'adults' => $adults,
            'children' => $children,
            'adult_price_thb' => $tour['price_adult_thb'],
            'child_price_thb' => $tour['price_child_thb'],
            'adults_total_thb' => $adultPrice,
            'children_total_thb' => $childPrice,
            'pickup_fee_thb' => $pickupFee,
            'total_thb' => $adultPrice + $childPrice + $pickupFee,
        ];
    }

    /**
     * Get all categories with counts
     */
    public function getCategories(): array
    {
        return $this->db->query(
            "SELECT category, COUNT(*) as count
             FROM tours
             WHERE is_active = 1
             GROUP BY category
             ORDER BY count DESC"
        );
    }
}
