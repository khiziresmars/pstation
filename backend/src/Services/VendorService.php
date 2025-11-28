<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Vendor Service
 * Foundation for marketplace functionality - managing vessel/tour vendors
 */
class VendorService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Register new vendor (self-signup)
     */
    public function register(array $data): int
    {
        $slug = $this->generateSlug($data['company_name']);

        $this->db->execute(
            "INSERT INTO vendors (company_name, slug, contact_name, email, phone, whatsapp, telegram_id,
             business_type, tax_id, license_number, address, city, province, postal_code,
             bank_name, bank_account_name, bank_account_number, commission_rate,
             description_en, description_ru, description_th, status)
             VALUES (:company_name, :slug, :contact_name, :email, :phone, :whatsapp, :telegram_id,
             :business_type, :tax_id, :license_number, :address, :city, :province, :postal_code,
             :bank_name, :bank_account_name, :bank_account_number, :commission_rate,
             :description_en, :description_ru, :description_th, 'pending')",
            [
                'company_name' => $data['company_name'],
                'slug' => $slug,
                'contact_name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'telegram_id' => $data['telegram_id'] ?? null,
                'business_type' => $data['business_type'] ?? 'company',
                'tax_id' => $data['tax_id'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? 'Phuket',
                'province' => $data['province'] ?? 'Phuket',
                'postal_code' => $data['postal_code'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'commission_rate' => $data['commission_rate'] ?? 15.00,
                'description_en' => $data['description_en'] ?? null,
                'description_ru' => $data['description_ru'] ?? null,
                'description_th' => $data['description_th'] ?? null
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get vendor by ID
     */
    public function getById(int $id): ?array
    {
        $vendor = $this->db->fetchOne(
            "SELECT * FROM vendors WHERE id = :id",
            ['id' => $id]
        );

        return $vendor ? $this->formatVendor($vendor) : null;
    }

    /**
     * Get vendor by email
     */
    public function getByEmail(string $email): ?array
    {
        $vendor = $this->db->fetchOne(
            "SELECT * FROM vendors WHERE email = :email",
            ['email' => $email]
        );

        return $vendor ? $this->formatVendor($vendor) : null;
    }

    /**
     * Get vendor by Telegram ID
     */
    public function getByTelegramId(int $telegramId): ?array
    {
        $vendor = $this->db->fetchOne(
            "SELECT * FROM vendors WHERE telegram_id = :telegram_id",
            ['telegram_id' => $telegramId]
        );

        return $vendor ? $this->formatVendor($vendor) : null;
    }

    /**
     * Get vendor's vessels
     */
    public function getVessels(int $vendorId): array
    {
        return $this->db->fetchAll(
            "SELECT id, name, type, is_active, price_per_day_thb, bookings_count, rating
             FROM vessels WHERE vendor_id = :vendor_id ORDER BY created_at DESC",
            ['vendor_id' => $vendorId]
        );
    }

    /**
     * Get vendor's tours
     */
    public function getTours(int $vendorId): array
    {
        return $this->db->fetchAll(
            "SELECT id, name_en as name, category, is_active, price_adult_thb, bookings_count, rating
             FROM tours WHERE vendor_id = :vendor_id ORDER BY created_at DESC",
            ['vendor_id' => $vendorId]
        );
    }

    /**
     * Get vendor's bookings
     */
    public function getBookings(int $vendorId, array $filters = []): array
    {
        $sql = "SELECT b.*, u.first_name, u.last_name
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.vendor_id = :vendor_id";
        $params = ['vendor_id' => $vendorId];

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND b.booking_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND b.booking_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY b.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $filters['limit'];
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get vendor dashboard stats
     */
    public function getDashboardStats(int $vendorId): array
    {
        // Total bookings
        $totalBookings = $this->db->fetchOne(
            "SELECT COUNT(*) as count, SUM(total_price_thb) as revenue
             FROM bookings WHERE vendor_id = :vendor_id AND status IN ('paid', 'completed')",
            ['vendor_id' => $vendorId]
        );

        // This month
        $monthStart = date('Y-m-01');
        $monthBookings = $this->db->fetchOne(
            "SELECT COUNT(*) as count, SUM(total_price_thb) as revenue
             FROM bookings WHERE vendor_id = :vendor_id
             AND status IN ('paid', 'completed')
             AND created_at >= :month_start",
            ['vendor_id' => $vendorId, 'month_start' => $monthStart]
        );

        // Pending bookings
        $pendingBookings = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM bookings
             WHERE vendor_id = :vendor_id AND status = 'pending'",
            ['vendor_id' => $vendorId]
        );

        // Upcoming bookings
        $upcomingBookings = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM bookings
             WHERE vendor_id = :vendor_id AND status IN ('confirmed', 'paid')
             AND booking_date >= CURDATE()",
            ['vendor_id' => $vendorId]
        );

        // Active listings
        $activeVessels = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM vessels WHERE vendor_id = :vendor_id AND is_active = 1",
            ['vendor_id' => $vendorId]
        );
        $activeTours = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM tours WHERE vendor_id = :vendor_id AND is_active = 1",
            ['vendor_id' => $vendorId]
        );

        // Pending payout
        $pendingPayout = $this->db->fetchOne(
            "SELECT SUM(total_price_thb - vendor_commission_thb) as amount
             FROM bookings WHERE vendor_id = :vendor_id
             AND status IN ('paid', 'completed')
             AND id NOT IN (SELECT booking_id FROM vendor_payouts WHERE status = 'paid')",
            ['vendor_id' => $vendorId]
        );

        return [
            'total_bookings' => (int) ($totalBookings['count'] ?? 0),
            'total_revenue_thb' => (float) ($totalBookings['revenue'] ?? 0),
            'month_bookings' => (int) ($monthBookings['count'] ?? 0),
            'month_revenue_thb' => (float) ($monthBookings['revenue'] ?? 0),
            'pending_bookings' => (int) ($pendingBookings['count'] ?? 0),
            'upcoming_bookings' => (int) ($upcomingBookings['count'] ?? 0),
            'active_vessels' => (int) ($activeVessels['count'] ?? 0),
            'active_tours' => (int) ($activeTours['count'] ?? 0),
            'pending_payout_thb' => (float) ($pendingPayout['amount'] ?? 0)
        ];
    }

    /**
     * Get vendor payouts
     */
    public function getPayouts(int $vendorId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM vendor_payouts WHERE vendor_id = :vendor_id ORDER BY created_at DESC",
            ['vendor_id' => $vendorId]
        );
    }

    /**
     * Update vendor profile
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'company_name', 'contact_name', 'phone', 'whatsapp',
            'address', 'city', 'province', 'postal_code',
            'bank_name', 'bank_account_name', 'bank_account_number',
            'description_en', 'description_ru', 'description_th',
            'logo', 'cover_image', 'notification_settings', 'auto_confirm_bookings'
        ];

        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                if ($key === 'notification_settings') {
                    $value = is_array($value) ? json_encode($value) : $value;
                }
                $fields[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE vendors SET " . implode(', ', $fields) . " WHERE id = :id";

        return $this->db->execute($sql, $params) > 0;
    }

    /**
     * Upload documents
     */
    public function addDocument(int $vendorId, string $documentPath, string $documentType): bool
    {
        $vendor = $this->db->fetchOne(
            "SELECT documents FROM vendors WHERE id = :id",
            ['id' => $vendorId]
        );

        if (!$vendor) {
            return false;
        }

        $documents = json_decode($vendor['documents'] ?? '[]', true);
        $documents[] = [
            'type' => $documentType,
            'path' => $documentPath,
            'uploaded_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->execute(
            "UPDATE vendors SET documents = :documents WHERE id = :id",
            ['documents' => json_encode($documents), 'id' => $vendorId]
        ) > 0;
    }

    // ==================== Admin Methods ====================

    /**
     * Get all vendors (admin)
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM vendors WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (company_name LIKE :search OR email LIKE :search OR contact_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY created_at DESC";

        $vendors = $this->db->fetchAll($sql, $params);

        return array_map(fn($v) => $this->formatVendor($v), $vendors);
    }

    /**
     * Approve vendor
     */
    public function approve(int $id, int $approvedBy): bool
    {
        return $this->db->execute(
            "UPDATE vendors SET status = 'approved', verified_at = NOW(), verified_by = :verified_by WHERE id = :id",
            ['id' => $id, 'verified_by' => $approvedBy]
        ) > 0;
    }

    /**
     * Reject vendor
     */
    public function reject(int $id, string $reason): bool
    {
        return $this->db->execute(
            "UPDATE vendors SET status = 'rejected', rejection_reason = :reason WHERE id = :id",
            ['id' => $id, 'reason' => $reason]
        ) > 0;
    }

    /**
     * Suspend vendor
     */
    public function suspend(int $id): bool
    {
        // Deactivate all vendor's listings
        $this->db->execute("UPDATE vessels SET is_active = 0 WHERE vendor_id = :id", ['id' => $id]);
        $this->db->execute("UPDATE tours SET is_active = 0 WHERE vendor_id = :id", ['id' => $id]);

        return $this->db->execute(
            "UPDATE vendors SET status = 'suspended', is_active = 0 WHERE id = :id",
            ['id' => $id]
        ) > 0;
    }

    /**
     * Create payout
     */
    public function createPayout(int $vendorId, string $periodStart, string $periodEnd): ?int
    {
        // Get bookings for period
        $bookings = $this->db->fetchAll(
            "SELECT id, total_price_thb, vendor_commission_thb
             FROM bookings
             WHERE vendor_id = :vendor_id
             AND status IN ('paid', 'completed')
             AND booking_date BETWEEN :start AND :end
             AND id NOT IN (
                 SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(booking_ids, CONCAT('\$[', numbers.n, ']')))
                 FROM vendor_payouts, (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) numbers
                 WHERE vendor_payouts.vendor_id = :vendor_id AND status = 'paid'
             )",
            [
                'vendor_id' => $vendorId,
                'start' => $periodStart,
                'end' => $periodEnd
            ]
        );

        if (empty($bookings)) {
            return null;
        }

        $grossAmount = 0;
        $commission = 0;
        $bookingIds = [];

        foreach ($bookings as $booking) {
            $grossAmount += (float) $booking['total_price_thb'];
            $commission += (float) $booking['vendor_commission_thb'];
            $bookingIds[] = (int) $booking['id'];
        }

        $netAmount = $grossAmount - $commission;

        $this->db->execute(
            "INSERT INTO vendor_payouts (vendor_id, period_start, period_end, gross_amount_thb,
             commission_thb, net_amount_thb, bookings_count, booking_ids)
             VALUES (:vendor_id, :period_start, :period_end, :gross, :commission, :net, :count, :booking_ids)",
            [
                'vendor_id' => $vendorId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'gross' => $grossAmount,
                'commission' => $commission,
                'net' => $netAmount,
                'count' => count($bookingIds),
                'booking_ids' => json_encode($bookingIds)
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Process payout
     */
    public function processPayout(int $payoutId, string $paymentMethod, string $paymentReference): bool
    {
        return $this->db->execute(
            "UPDATE vendor_payouts SET status = 'paid', payment_method = :method,
             payment_reference = :reference, paid_at = NOW() WHERE id = :id",
            ['id' => $payoutId, 'method' => $paymentMethod, 'reference' => $paymentReference]
        ) > 0;
    }

    /**
     * Calculate commission for booking
     */
    public function calculateCommission(int $vendorId, float $amount): float
    {
        $vendor = $this->db->fetchOne(
            "SELECT commission_rate FROM vendors WHERE id = :id",
            ['id' => $vendorId]
        );

        $rate = $vendor ? (float) $vendor['commission_rate'] : 15.00;

        return $amount * ($rate / 100);
    }

    /**
     * Generate unique slug
     */
    private function generateSlug(string $companyName): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $companyName));
        $base = trim($base, '-');
        $slug = $base;
        $counter = 1;

        while ($this->db->fetchOne("SELECT id FROM vendors WHERE slug = :slug", ['slug' => $slug])) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Format vendor for API response
     */
    private function formatVendor(array $vendor, bool $includeFinancial = false): array
    {
        $result = [
            'id' => (int) $vendor['id'],
            'company_name' => $vendor['company_name'],
            'slug' => $vendor['slug'],
            'contact_name' => $vendor['contact_name'],
            'email' => $vendor['email'],
            'phone' => $vendor['phone'],
            'whatsapp' => $vendor['whatsapp'],
            'status' => $vendor['status'],
            'logo' => $vendor['logo'],
            'cover_image' => $vendor['cover_image'],
            'description_en' => $vendor['description_en'],
            'rating' => (float) $vendor['rating'],
            'reviews_count' => (int) $vendor['reviews_count'],
            'total_bookings' => (int) $vendor['total_bookings'],
            'verified_at' => $vendor['verified_at'],
            'created_at' => $vendor['created_at']
        ];

        if ($includeFinancial) {
            $result['commission_rate'] = (float) $vendor['commission_rate'];
            $result['total_revenue_thb'] = (float) $vendor['total_revenue_thb'];
            $result['bank_name'] = $vendor['bank_name'];
            $result['bank_account_name'] = $vendor['bank_account_name'];
            $result['bank_account_number'] = $vendor['bank_account_number'];
        }

        return $result;
    }
}
