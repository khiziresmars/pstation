<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Packages Service
 * Manages bundle packages (Romantic, Family, Corporate, etc.)
 */
class PackagesService
{
    private Database $db;
    private AddonsService $addonsService;

    public function __construct(Database $db, AddonsService $addonsService)
    {
        $this->db = $db;
        $this->addonsService = $addonsService;
    }

    /**
     * Get all active packages
     */
    public function getAll(string $lang = 'en'): array
    {
        $packages = $this->db->fetchAll(
            "SELECT * FROM packages WHERE is_active = 1 ORDER BY sort_order ASC"
        );

        return array_map(fn($pkg) => $this->formatPackage($pkg, $lang), $packages);
    }

    /**
     * Get packages by type
     */
    public function getByType(string $type, string $lang = 'en'): array
    {
        $packages = $this->db->fetchAll(
            "SELECT * FROM packages WHERE type = :type AND is_active = 1 ORDER BY sort_order ASC",
            ['type' => $type]
        );

        return array_map(fn($pkg) => $this->formatPackage($pkg, $lang), $packages);
    }

    /**
     * Get featured packages
     */
    public function getFeatured(string $lang = 'en', int $limit = 4): array
    {
        $packages = $this->db->fetchAll(
            "SELECT * FROM packages WHERE is_featured = 1 AND is_active = 1 ORDER BY sort_order ASC LIMIT :limit",
            ['limit' => $limit]
        );

        return array_map(fn($pkg) => $this->formatPackage($pkg, $lang), $packages);
    }

    /**
     * Get package by slug
     */
    public function getBySlug(string $slug, string $lang = 'en'): ?array
    {
        $package = $this->db->fetchOne(
            "SELECT * FROM packages WHERE slug = :slug",
            ['slug' => $slug]
        );

        if (!$package) {
            return null;
        }

        return $this->formatPackage($package, $lang, true);
    }

    /**
     * Get package by ID
     */
    public function getById(int $id, string $lang = 'en'): ?array
    {
        $package = $this->db->fetchOne(
            "SELECT * FROM packages WHERE id = :id",
            ['id' => $id]
        );

        if (!$package) {
            return null;
        }

        return $this->formatPackage($package, $lang, true);
    }

    /**
     * Calculate package price for specific vessel/tour and options
     */
    public function calculatePrice(int $packageId, array $options): array
    {
        $package = $this->db->fetchOne(
            "SELECT * FROM packages WHERE id = :id AND is_active = 1",
            ['id' => $packageId]
        );

        if (!$package) {
            return ['error' => 'Package not found'];
        }

        $guests = $options['guests'] ?? $package['min_guests'];
        $hours = $options['hours'] ?? $package['min_duration_hours'];
        $baseItemId = $options['base_id'] ?? $package['base_id'];

        // Get base item price
        $basePrice = 0;
        if ($package['base_type'] === 'vessel' && $baseItemId) {
            $vessel = $this->db->fetchOne(
                "SELECT price_per_hour_thb, price_per_day_thb FROM vessels WHERE id = :id",
                ['id' => $baseItemId]
            );
            if ($vessel) {
                $basePrice = $hours >= 8
                    ? (float) $vessel['price_per_day_thb']
                    : (float) $vessel['price_per_hour_thb'] * $hours;
            }
        } elseif ($package['base_type'] === 'tour' && $baseItemId) {
            $tour = $this->db->fetchOne(
                "SELECT price_adult_thb FROM tours WHERE id = :id",
                ['id' => $baseItemId]
            );
            if ($tour) {
                $basePrice = (float) $tour['price_adult_thb'] * $guests;
            }
        }

        // If no specific base item, use package base price
        if ($basePrice === 0) {
            $basePrice = (float) $package['base_price_thb'];
        }

        // Calculate included addons
        $includedAddons = json_decode($package['included_addons'], true) ?? [];
        $addonsTotal = $this->addonsService->calculateTotal($includedAddons, $guests, $hours);

        // Calculate extra addons (if any selected)
        $extraAddons = $options['extra_addons'] ?? [];
        $extrasTotal = $this->addonsService->calculateTotal($extraAddons, $guests, $hours);

        // Apply discount
        $discountPercent = (float) $package['discount_percent'];
        $subtotal = $basePrice + $addonsTotal['total_thb'];
        $discount = $subtotal * ($discountPercent / 100);
        $packageTotal = $subtotal - $discount;

        // Add extras (no discount on extras)
        $total = $packageTotal + $extrasTotal['total_thb'];

        return [
            'package_id' => (int) $package['id'],
            'package_name' => $package['name_en'],
            'base_price_thb' => $basePrice,
            'included_addons_thb' => $addonsTotal['total_thb'],
            'included_addons_breakdown' => $addonsTotal['breakdown'],
            'subtotal_thb' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_thb' => $discount,
            'package_total_thb' => $packageTotal,
            'extra_addons_thb' => $extrasTotal['total_thb'],
            'extra_addons_breakdown' => $extrasTotal['breakdown'],
            'total_thb' => $total,
            'savings_thb' => $discount,
            'guests' => $guests,
            'hours' => $hours
        ];
    }

    /**
     * Get compatible vessels for a package
     */
    public function getCompatibleVessels(int $packageId): array
    {
        $package = $this->db->fetchOne(
            "SELECT base_type, base_id, vessel_types FROM packages WHERE id = :id",
            ['id' => $packageId]
        );

        if (!$package || $package['base_type'] !== 'vessel') {
            return [];
        }

        if ($package['base_id']) {
            return $this->db->fetchAll(
                "SELECT id, name, type, thumbnail, price_per_hour_thb, price_per_day_thb
                 FROM vessels WHERE id = :id AND is_active = 1",
                ['id' => $package['base_id']]
            );
        }

        $vesselTypes = json_decode($package['vessel_types'] ?? '[]', true);

        if (empty($vesselTypes)) {
            return $this->db->fetchAll(
                "SELECT id, name, type, thumbnail, price_per_hour_thb, price_per_day_thb
                 FROM vessels WHERE is_active = 1 ORDER BY sort_order ASC"
            );
        }

        $placeholders = implode(',', array_fill(0, count($vesselTypes), '?'));
        return $this->db->fetchAll(
            "SELECT id, name, type, thumbnail, price_per_hour_thb, price_per_day_thb
             FROM vessels WHERE type IN ({$placeholders}) AND is_active = 1 ORDER BY sort_order ASC",
            $vesselTypes
        );
    }

    /**
     * Get compatible tours for a package
     */
    public function getCompatibleTours(int $packageId): array
    {
        $package = $this->db->fetchOne(
            "SELECT base_type, base_id, tour_categories FROM packages WHERE id = :id",
            ['id' => $packageId]
        );

        if (!$package || $package['base_type'] !== 'tour') {
            return [];
        }

        if ($package['base_id']) {
            return $this->db->fetchAll(
                "SELECT id, name_en as name, category, thumbnail, price_adult_thb
                 FROM tours WHERE id = :id AND is_active = 1",
                ['id' => $package['base_id']]
            );
        }

        $categories = json_decode($package['tour_categories'] ?? '[]', true);

        if (empty($categories)) {
            return $this->db->fetchAll(
                "SELECT id, name_en as name, category, thumbnail, price_adult_thb
                 FROM tours WHERE is_active = 1 ORDER BY sort_order ASC"
            );
        }

        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        return $this->db->fetchAll(
            "SELECT id, name_en as name, category, thumbnail, price_adult_thb
             FROM tours WHERE category IN ({$placeholders}) AND is_active = 1 ORDER BY sort_order ASC",
            $categories
        );
    }

    /**
     * Format package for API response
     */
    private function formatPackage(array $package, string $lang, bool $detailed = false): array
    {
        $result = [
            'id' => (int) $package['id'],
            'slug' => $package['slug'],
            'name' => $package["name_{$lang}"] ?? $package['name_en'],
            'tagline' => $package["tagline_{$lang}"] ?? $package['tagline_en'],
            'type' => $package['type'],
            'base_type' => $package['base_type'],
            'base_price_thb' => (float) $package['base_price_thb'],
            'discount_percent' => (float) $package['discount_percent'],
            'min_duration_hours' => (int) $package['min_duration_hours'],
            'min_guests' => (int) $package['min_guests'],
            'max_guests' => $package['max_guests'] ? (int) $package['max_guests'] : null,
            'thumbnail' => $package['thumbnail'],
            'badge' => $package['badge'],
            'is_featured' => (bool) $package['is_featured'],
            'bookings_count' => (int) $package['bookings_count']
        ];

        if ($detailed) {
            $result['description'] = $package["description_{$lang}"] ?? $package['description_en'];
            $result['images'] = json_decode($package['images'] ?? '[]', true);
            $result['included_features'] = json_decode($package['included_features'] ?? '[]', true);

            // Get included addons details
            $includedAddons = json_decode($package['included_addons'] ?? '[]', true);
            $result['included_addons'] = [];

            foreach ($includedAddons as $included) {
                $addon = $this->addonsService->getById($included['addon_id'], $lang);
                if ($addon) {
                    $addon['quantity'] = $included['quantity'] ?? 1;
                    $result['included_addons'][] = $addon;
                }
            }

            $result['vessel_types'] = json_decode($package['vessel_types'] ?? '[]', true);
            $result['tour_categories'] = json_decode($package['tour_categories'] ?? '[]', true);
        }

        return $result;
    }

    // ==================== Admin Methods ====================

    /**
     * Create package
     */
    public function create(array $data): int
    {
        $this->db->execute(
            "INSERT INTO packages (slug, name_en, name_ru, name_th, tagline_en, tagline_ru, tagline_th,
             description_en, description_ru, description_th, type, base_type, base_id, vessel_types,
             tour_categories, included_addons, included_features, base_price_thb, discount_percent,
             min_duration_hours, min_guests, max_guests, images, thumbnail, badge, is_featured, sort_order)
             VALUES (:slug, :name_en, :name_ru, :name_th, :tagline_en, :tagline_ru, :tagline_th,
             :description_en, :description_ru, :description_th, :type, :base_type, :base_id, :vessel_types,
             :tour_categories, :included_addons, :included_features, :base_price_thb, :discount_percent,
             :min_duration_hours, :min_guests, :max_guests, :images, :thumbnail, :badge, :is_featured, :sort_order)",
            [
                'slug' => $data['slug'],
                'name_en' => $data['name_en'],
                'name_ru' => $data['name_ru'] ?? null,
                'name_th' => $data['name_th'] ?? null,
                'tagline_en' => $data['tagline_en'] ?? null,
                'tagline_ru' => $data['tagline_ru'] ?? null,
                'tagline_th' => $data['tagline_th'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'description_ru' => $data['description_ru'] ?? null,
                'description_th' => $data['description_th'] ?? null,
                'type' => $data['type'],
                'base_type' => $data['base_type'],
                'base_id' => $data['base_id'] ?? null,
                'vessel_types' => isset($data['vessel_types']) ? json_encode($data['vessel_types']) : null,
                'tour_categories' => isset($data['tour_categories']) ? json_encode($data['tour_categories']) : null,
                'included_addons' => json_encode($data['included_addons'] ?? []),
                'included_features' => isset($data['included_features']) ? json_encode($data['included_features']) : null,
                'base_price_thb' => $data['base_price_thb'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'min_duration_hours' => $data['min_duration_hours'] ?? 4,
                'min_guests' => $data['min_guests'] ?? 2,
                'max_guests' => $data['max_guests'] ?? null,
                'images' => isset($data['images']) ? json_encode($data['images']) : '[]',
                'thumbnail' => $data['thumbnail'] ?? null,
                'badge' => $data['badge'] ?? null,
                'is_featured' => $data['is_featured'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Increment bookings count
     */
    public function incrementBookings(int $id): void
    {
        $this->db->execute(
            "UPDATE packages SET bookings_count = bookings_count + 1 WHERE id = :id",
            ['id' => $id]
        );
    }
}
