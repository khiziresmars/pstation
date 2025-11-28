<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Addons Service
 * Manages additional services/upsells for vessels and tours
 */
class AddonsService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all addon categories
     */
    public function getCategories(string $appliesTo = 'all'): array
    {
        $sql = "SELECT * FROM addon_categories WHERE is_active = 1";

        if ($appliesTo !== 'all') {
            $sql .= " AND (applies_to = 'all' OR applies_to = :applies_to)";
        }

        $sql .= " ORDER BY sort_order ASC";

        if ($appliesTo !== 'all') {
            return $this->db->fetchAll($sql, ['applies_to' => $appliesTo]);
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Get addons by category
     */
    public function getByCategory(int $categoryId, string $lang = 'en'): array
    {
        $addons = $this->db->fetchAll(
            "SELECT * FROM addons WHERE category_id = :category_id AND is_active = 1 ORDER BY sort_order ASC",
            ['category_id' => $categoryId]
        );

        return array_map(fn($addon) => $this->formatAddon($addon, $lang), $addons);
    }

    /**
     * Get addons for a specific vessel
     */
    public function getForVessel(int $vesselId, string $vesselType, string $lang = 'en'): array
    {
        $addons = $this->db->fetchAll(
            "SELECT a.*, ac.name_{$lang} as category_name, ac.icon as category_icon
             FROM addons a
             JOIN addon_categories ac ON a.category_id = ac.id
             WHERE a.is_active = 1
             AND (a.applies_to = 'all' OR a.applies_to = 'vessels')
             AND (a.vessel_types IS NULL OR JSON_CONTAINS(a.vessel_types, :vessel_type))
             AND (a.vessel_ids IS NULL OR JSON_CONTAINS(a.vessel_ids, :vessel_id))
             ORDER BY ac.sort_order ASC, a.sort_order ASC",
            [
                'vessel_type' => json_encode($vesselType),
                'vessel_id' => json_encode($vesselId)
            ]
        );

        return $this->groupByCategory($addons, $lang);
    }

    /**
     * Get addons for a specific tour
     */
    public function getForTour(int $tourId, string $tourCategory, string $lang = 'en'): array
    {
        $addons = $this->db->fetchAll(
            "SELECT a.*, ac.name_{$lang} as category_name, ac.icon as category_icon
             FROM addons a
             JOIN addon_categories ac ON a.category_id = ac.id
             WHERE a.is_active = 1
             AND (a.applies_to = 'all' OR a.applies_to = 'tours')
             AND (a.tour_categories IS NULL OR JSON_CONTAINS(a.tour_categories, :tour_category))
             AND (a.tour_ids IS NULL OR JSON_CONTAINS(a.tour_ids, :tour_id))
             ORDER BY ac.sort_order ASC, a.sort_order ASC",
            [
                'tour_category' => json_encode($tourCategory),
                'tour_id' => json_encode($tourId)
            ]
        );

        return $this->groupByCategory($addons, $lang);
    }

    /**
     * Get popular addons
     */
    public function getPopular(string $lang = 'en', int $limit = 8): array
    {
        $addons = $this->db->fetchAll(
            "SELECT a.*, ac.name_{$lang} as category_name, ac.icon as category_icon
             FROM addons a
             JOIN addon_categories ac ON a.category_id = ac.id
             WHERE a.is_active = 1 AND a.is_popular = 1
             ORDER BY a.sort_order ASC
             LIMIT :limit",
            ['limit' => $limit]
        );

        return array_map(fn($addon) => $this->formatAddon($addon, $lang), $addons);
    }

    /**
     * Get recommended addons for cart upsell
     */
    public function getRecommended(array $selectedAddonIds, string $appliesTo, string $lang = 'en'): array
    {
        $placeholders = implode(',', array_fill(0, count($selectedAddonIds) ?: 1, '?'));
        $params = $selectedAddonIds ?: [0];

        $addons = $this->db->fetchAll(
            "SELECT a.*, ac.name_{$lang} as category_name
             FROM addons a
             JOIN addon_categories ac ON a.category_id = ac.id
             WHERE a.is_active = 1
             AND a.is_recommended = 1
             AND a.id NOT IN ({$placeholders})
             AND (a.applies_to = 'all' OR a.applies_to = ?)
             ORDER BY a.sort_order ASC
             LIMIT 4",
            array_merge($params, [$appliesTo])
        );

        return array_map(fn($addon) => $this->formatAddon($addon, $lang), $addons);
    }

    /**
     * Calculate total price for selected addons
     */
    public function calculateTotal(array $selectedAddons, int $guests, int $hours): array
    {
        $total = 0;
        $breakdown = [];

        foreach ($selectedAddons as $selection) {
            $addon = $this->db->fetchOne(
                "SELECT * FROM addons WHERE id = :id AND is_active = 1",
                ['id' => $selection['addon_id']]
            );

            if (!$addon) {
                continue;
            }

            $quantity = $selection['quantity'] ?? 1;
            $price = (float) $addon['price_thb'];

            switch ($addon['price_type']) {
                case 'per_person':
                    $subtotal = $price * $guests;
                    break;
                case 'per_hour':
                    $subtotal = $price * $hours * $quantity;
                    break;
                case 'per_item':
                    $subtotal = $price * $quantity;
                    break;
                case 'fixed':
                default:
                    $subtotal = $price;
                    break;
            }

            $total += $subtotal;
            $breakdown[] = [
                'addon_id' => (int) $addon['id'],
                'name' => $addon['name_en'],
                'price_thb' => $price,
                'price_type' => $addon['price_type'],
                'quantity' => $quantity,
                'subtotal_thb' => $subtotal
            ];
        }

        return [
            'total_thb' => $total,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Get addon by ID
     */
    public function getById(int $id, string $lang = 'en'): ?array
    {
        $addon = $this->db->fetchOne(
            "SELECT a.*, ac.name_{$lang} as category_name, ac.icon as category_icon
             FROM addons a
             JOIN addon_categories ac ON a.category_id = ac.id
             WHERE a.id = :id",
            ['id' => $id]
        );

        return $addon ? $this->formatAddon($addon, $lang) : null;
    }

    /**
     * Format addon for API response
     */
    private function formatAddon(array $addon, string $lang): array
    {
        return [
            'id' => (int) $addon['id'],
            'slug' => $addon['slug'],
            'name' => $addon["name_{$lang}"] ?? $addon['name_en'],
            'description' => $addon["description_{$lang}"] ?? $addon['description_en'],
            'category' => $addon['category_name'] ?? null,
            'category_icon' => $addon['category_icon'] ?? null,
            'price_thb' => (float) $addon['price_thb'],
            'price_type' => $addon['price_type'],
            'min_quantity' => (int) $addon['min_quantity'],
            'max_quantity' => $addon['max_quantity'] ? (int) $addon['max_quantity'] : null,
            'image' => $addon['image'],
            'icon' => $addon['icon'],
            'is_popular' => (bool) $addon['is_popular'],
            'is_recommended' => (bool) $addon['is_recommended']
        ];
    }

    /**
     * Group addons by category
     */
    private function groupByCategory(array $addons, string $lang): array
    {
        $grouped = [];

        foreach ($addons as $addon) {
            $categoryId = $addon['category_id'];

            if (!isset($grouped[$categoryId])) {
                $grouped[$categoryId] = [
                    'id' => (int) $categoryId,
                    'name' => $addon['category_name'],
                    'icon' => $addon['category_icon'],
                    'addons' => []
                ];
            }

            $grouped[$categoryId]['addons'][] = $this->formatAddon($addon, $lang);
        }

        return array_values($grouped);
    }

    // ==================== Admin Methods ====================

    /**
     * Create addon
     */
    public function create(array $data): int
    {
        $this->db->execute(
            "INSERT INTO addons (category_id, slug, name_en, name_ru, name_th, description_en, description_ru, description_th,
             price_thb, price_type, min_quantity, max_quantity, applies_to, vessel_types, tour_categories,
             vessel_ids, tour_ids, image, icon, is_popular, is_recommended, sort_order)
             VALUES (:category_id, :slug, :name_en, :name_ru, :name_th, :description_en, :description_ru, :description_th,
             :price_thb, :price_type, :min_quantity, :max_quantity, :applies_to, :vessel_types, :tour_categories,
             :vessel_ids, :tour_ids, :image, :icon, :is_popular, :is_recommended, :sort_order)",
            [
                'category_id' => $data['category_id'],
                'slug' => $data['slug'],
                'name_en' => $data['name_en'],
                'name_ru' => $data['name_ru'] ?? null,
                'name_th' => $data['name_th'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'description_ru' => $data['description_ru'] ?? null,
                'description_th' => $data['description_th'] ?? null,
                'price_thb' => $data['price_thb'],
                'price_type' => $data['price_type'] ?? 'fixed',
                'min_quantity' => $data['min_quantity'] ?? 1,
                'max_quantity' => $data['max_quantity'] ?? null,
                'applies_to' => $data['applies_to'] ?? 'all',
                'vessel_types' => isset($data['vessel_types']) ? json_encode($data['vessel_types']) : null,
                'tour_categories' => isset($data['tour_categories']) ? json_encode($data['tour_categories']) : null,
                'vessel_ids' => isset($data['vessel_ids']) ? json_encode($data['vessel_ids']) : null,
                'tour_ids' => isset($data['tour_ids']) ? json_encode($data['tour_ids']) : null,
                'image' => $data['image'] ?? null,
                'icon' => $data['icon'] ?? null,
                'is_popular' => $data['is_popular'] ?? false,
                'is_recommended' => $data['is_recommended'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update addon
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['vessel_types', 'tour_categories', 'vessel_ids', 'tour_ids'])) {
                $value = is_array($value) ? json_encode($value) : $value;
            }
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "UPDATE addons SET " . implode(', ', $fields) . " WHERE id = :id";

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Delete addon
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM addons WHERE id = :id", ['id' => $id]) > 0;
    }

    /**
     * Toggle addon status
     */
    public function toggleStatus(int $id): bool
    {
        return $this->db->execute(
            "UPDATE addons SET is_active = NOT is_active WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }
}
