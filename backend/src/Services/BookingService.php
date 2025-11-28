<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;

/**
 * Booking Service
 * Handles booking operations with full integration of:
 * - Packages and addons
 * - Gift cards
 * - Dynamic pricing
 * - Vendors
 * - Loyalty tiers
 */
class BookingService
{
    private Database $db;
    private VesselService $vesselService;
    private TourService $tourService;
    private PromoService $promoService;
    private UserService $userService;
    private ?AddonsService $addonsService = null;
    private ?PackagesService $packagesService = null;
    private ?GiftCardService $giftCardService = null;
    private ?PricingService $pricingService = null;
    private ?LoyaltyService $loyaltyService = null;
    private ?BookingStatusService $statusService = null;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
        $this->vesselService = new VesselService();
        $this->tourService = new TourService();
        $this->promoService = new PromoService();
        $this->userService = new UserService();
    }

    /**
     * Lazy-load services to avoid circular dependencies
     */
    private function getAddonsService(): AddonsService
    {
        if (!$this->addonsService) {
            $this->addonsService = new AddonsService($this->db);
        }
        return $this->addonsService;
    }

    private function getPackagesService(): PackagesService
    {
        if (!$this->packagesService) {
            $this->packagesService = new PackagesService($this->db, $this->getAddonsService());
        }
        return $this->packagesService;
    }

    private function getGiftCardService(): GiftCardService
    {
        if (!$this->giftCardService) {
            $this->giftCardService = new GiftCardService($this->db);
        }
        return $this->giftCardService;
    }

    private function getPricingService(): PricingService
    {
        if (!$this->pricingService) {
            $this->pricingService = new PricingService($this->db);
        }
        return $this->pricingService;
    }

    private function getLoyaltyService(): LoyaltyService
    {
        if (!$this->loyaltyService) {
            $this->loyaltyService = new LoyaltyService($this->db);
        }
        return $this->loyaltyService;
    }

    private function getStatusService(): BookingStatusService
    {
        if (!$this->statusService) {
            $this->statusService = new BookingStatusService($this->db);
        }
        return $this->statusService;
    }

    /**
     * Create a new booking
     */
    public function create(int $userId, array $data): array
    {
        $this->db->beginTransaction();

        try {
            // Get user and their loyalty tier
            $user = $this->userService->findById($userId);
            $loyaltyTierId = $user['loyalty_tier_id'] ?? null;

            // Validate and get item
            $item = $this->getBookableItem($data['type'], $data['item_id']);
            if (!$item) {
                throw new \Exception('Item not found');
            }

            // Get vendor from item
            $vendorId = $item['vendor_id'] ?? null;

            // Calculate base price with dynamic pricing
            $pricing = $this->calculateTotalPrice($data, $userId);
            if (isset($pricing['error'])) {
                throw new \Exception($pricing['error']);
            }

            // Handle package if selected
            $packageId = null;
            $packageDiscount = 0;
            if (!empty($data['package_id'])) {
                $packageResult = $this->applyPackage($data['package_id'], $data, $userId);
                if (!isset($packageResult['error'])) {
                    $packageId = $data['package_id'];
                    $packageDiscount = $packageResult['discount_thb'];
                    $pricing = $packageResult['pricing'];
                }
            }

            // Calculate subtotal before discounts
            $subtotal = $pricing['subtotal_thb'];

            // Apply promo code
            $promoDiscount = 0;
            $promoCodeId = null;
            if (!empty($data['promo_code'])) {
                $promoResult = $this->promoService->apply(
                    $data['promo_code'],
                    $userId,
                    $data['type'],
                    $data['item_id'],
                    $subtotal
                );

                if ($promoResult['valid']) {
                    $promoDiscount = $promoResult['discount'];
                    $promoCodeId = $promoResult['promo_code_id'];
                }
            }

            // Apply loyalty discount
            $loyaltyDiscount = 0;
            if ($loyaltyTierId) {
                $extraDiscount = $this->getLoyaltyService()->getExtraDiscount($userId);
                if ($extraDiscount > 0) {
                    $loyaltyDiscount = $subtotal * ($extraDiscount / 100);
                }
            }

            // Apply cashback
            $cashbackUsed = 0;
            if (!empty($data['use_cashback'])) {
                $maxCashback = min(
                    (float) $user['cashback_balance'],
                    $subtotal * 0.5 // Max 50% of order
                );
                $cashbackUsed = min($data['use_cashback'], $maxCashback);
            }

            // Apply gift card
            $giftCardId = null;
            $giftCardAmount = 0;
            if (!empty($data['gift_card_code'])) {
                $afterDiscounts = $subtotal - $promoDiscount - $loyaltyDiscount - $cashbackUsed;
                $gcResult = $this->getGiftCardService()->validate(
                    $data['gift_card_code'],
                    $afterDiscounts,
                    $data['type'] === 'vessel' ? 'vessels' : 'tours'
                );

                if ($gcResult['valid']) {
                    $giftCardId = $gcResult['gift_card']['id'];
                    $giftCardAmount = min($gcResult['applicable_amount_thb'], $afterDiscounts);
                }
            }

            // Calculate final price
            $totalDiscount = $promoDiscount + $loyaltyDiscount + $cashbackUsed + $giftCardAmount;
            $totalPrice = max(0, $subtotal - $totalDiscount);

            // Calculate cashback earned (based on loyalty tier)
            $cashbackPercent = $this->getLoyaltyService()->getCashbackRate($userId);
            $cashbackEarned = $totalPrice * ($cashbackPercent / 100);

            // Calculate vendor commission
            $vendorCommission = 0;
            if ($vendorId) {
                $vendorService = new VendorService($this->db);
                $vendorCommission = $vendorService->calculateCommission($vendorId, $totalPrice);
            }

            // Generate booking reference
            $reference = $this->generateReference();

            // Create booking
            $bookingId = $this->db->insert('bookings', [
                'booking_reference' => $reference,
                'user_id' => $userId,
                'bookable_type' => $data['type'],
                'bookable_id' => $data['item_id'],
                'package_id' => $packageId,
                'booking_date' => $data['date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'duration_hours' => $data['hours'] ?? null,
                'adults_count' => $data['adults'] ?? 1,
                'children_count' => $data['children'] ?? 0,
                'infants_count' => $data['infants'] ?? 0,

                // Pricing
                'base_price_thb' => $pricing['base_price_thb'],
                'dynamic_price_adjustment_thb' => $pricing['dynamic_adjustment_thb'] ?? 0,
                'pricing_rules_applied' => json_encode($pricing['applied_rules'] ?? []),
                'extras_price_thb' => $pricing['extras_price_thb'] ?? 0,
                'extras_details' => json_encode($pricing['extras_details'] ?? []),
                'pickup_fee_thb' => $pricing['pickup_fee_thb'] ?? 0,
                'pickup_address' => $data['pickup_address'] ?? null,
                'subtotal_thb' => $subtotal,

                // Discounts
                'promo_code_id' => $promoCodeId,
                'promo_discount_thb' => $promoDiscount,
                'gift_card_id' => $giftCardId,
                'gift_card_amount_thb' => $giftCardAmount,
                'cashback_used_thb' => $cashbackUsed,
                'total_discount_thb' => $totalDiscount,

                // Final
                'total_price_thb' => $totalPrice,

                // Cashback
                'cashback_percent' => $cashbackPercent,
                'cashback_earned_thb' => $cashbackEarned,
                'loyalty_tier_id' => $loyaltyTierId,
                'loyalty_discount_thb' => $loyaltyDiscount,

                // Vendor
                'vendor_id' => $vendorId,
                'vendor_commission_thb' => $vendorCommission,

                // Status
                'status' => BookingStatusService::STATUS_PENDING,

                // Additional
                'special_requests' => $data['special_requests'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'source' => $data['source'] ?? 'telegram',
            ]);

            // Save selected addons
            if (!empty($data['addons'])) {
                $this->saveBookingAddons($bookingId, $data['addons'], $data['adults'] ?? 1, $data['hours'] ?? 4);
            }

            // Deduct cashback if used
            if ($cashbackUsed > 0) {
                $this->userService->useCashback($userId, $cashbackUsed, $bookingId);
            }

            // Redeem gift card if used
            if ($giftCardId && $giftCardAmount > 0) {
                $this->getGiftCardService()->redeem($data['gift_card_code'], $bookingId, $giftCardAmount, $userId);
            }

            // Record promo code usage
            if ($promoCodeId) {
                $this->promoService->recordUsage($promoCodeId, $userId, $bookingId, $promoDiscount);
            }

            // Update package bookings count
            if ($packageId) {
                $this->getPackagesService()->incrementBookings($packageId);
            }

            // Log initial status
            $this->getStatusService()->transition(
                $bookingId,
                BookingStatusService::STATUS_PENDING,
                BookingStatusService::ACTOR_SYSTEM,
                null,
                'Booking created'
            );

            $this->db->commit();

            return $this->getByReference($reference);

        } catch (\Exception $e) {
            $this->db->rollback();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Calculate total price for a booking (with dynamic pricing)
     */
    public function calculateTotalPrice(array $data, ?int $userId = null): array
    {
        $basePrice = 0;
        $extrasPrice = 0;
        $pickupFee = 0;
        $extrasDetails = [];
        $dynamicAdjustment = 0;
        $appliedRules = [];

        if ($data['type'] === 'vessel') {
            $vessel = $this->vesselService->getById($data['item_id']);
            if (!$vessel) {
                return ['error' => 'Vessel not found'];
            }

            $hours = $data['hours'] ?? 8;

            // Calculate base price
            if ($hours >= 8) {
                $basePrice = (float) $vessel['price_per_day_thb'];
            } else {
                $basePrice = (float) $vessel['price_per_hour_thb'] * $hours;
            }

            // Apply dynamic pricing
            $pricingResult = $this->getPricingService()->calculatePrice([
                'base_price' => $basePrice,
                'booking_date' => $data['date'],
                'applies_to' => 'vessels',
                'item_type' => $vessel['type'],
                'item_id' => $data['item_id'],
                'guests' => ($data['adults'] ?? 1) + ($data['children'] ?? 0),
                'duration_hours' => $hours
            ]);

            $dynamicAdjustment = $pricingResult['total_adjustment_thb'];
            $appliedRules = $pricingResult['applied_rules'];
            $basePrice = $pricingResult['final_price_thb'];

        } elseif ($data['type'] === 'tour') {
            $tour = $this->tourService->getById($data['item_id']);
            if (!$tour) {
                return ['error' => 'Tour not found'];
            }

            $adults = $data['adults'] ?? 1;
            $children = $data['children'] ?? 0;

            $adultsTotal = (float) $tour['price_adult_thb'] * $adults;
            $childrenTotal = (float) $tour['price_child_thb'] * $children;
            $basePrice = $adultsTotal + $childrenTotal;

            // Pickup fee
            if (!empty($data['pickup']) && $tour['pickup_available']) {
                $pickupFee = (float) $tour['pickup_fee_thb'];
            }

            // Apply dynamic pricing
            $pricingResult = $this->getPricingService()->calculatePrice([
                'base_price' => $basePrice,
                'booking_date' => $data['date'],
                'applies_to' => 'tours',
                'item_type' => $tour['category'],
                'item_id' => $data['item_id'],
                'guests' => $adults + $children
            ]);

            $dynamicAdjustment = $pricingResult['total_adjustment_thb'];
            $appliedRules = $pricingResult['applied_rules'];
            $basePrice = $pricingResult['final_price_thb'];

        } else {
            return ['error' => 'Invalid booking type'];
        }

        // Calculate addons price
        if (!empty($data['addons'])) {
            $addonsResult = $this->getAddonsService()->calculateTotal(
                $data['addons'],
                ($data['adults'] ?? 1) + ($data['children'] ?? 0),
                $data['hours'] ?? 4
            );
            $extrasPrice = $addonsResult['total_thb'];
            $extrasDetails = $addonsResult['breakdown'];
        }

        $subtotal = $basePrice + $extrasPrice + $pickupFee;

        return [
            'base_price_thb' => $basePrice,
            'dynamic_adjustment_thb' => $dynamicAdjustment,
            'applied_rules' => $appliedRules,
            'extras_price_thb' => $extrasPrice,
            'extras_details' => $extrasDetails,
            'pickup_fee_thb' => $pickupFee,
            'subtotal_thb' => $subtotal,
        ];
    }

    /**
     * Apply package to booking
     */
    private function applyPackage(int $packageId, array $data, int $userId): array
    {
        $package = $this->getPackagesService()->getById($packageId);
        if (!$package) {
            return ['error' => 'Package not found'];
        }

        $result = $this->getPackagesService()->calculatePrice($packageId, [
            'guests' => ($data['adults'] ?? 2) + ($data['children'] ?? 0),
            'hours' => $data['hours'] ?? $package['min_duration_hours'],
            'base_id' => $data['item_id'],
            'extra_addons' => $data['addons'] ?? []
        ]);

        if (isset($result['error'])) {
            return $result;
        }

        return [
            'discount_thb' => $result['savings_thb'],
            'pricing' => [
                'base_price_thb' => $result['base_price_thb'],
                'extras_price_thb' => $result['included_addons_thb'] + $result['extra_addons_thb'],
                'extras_details' => array_merge($result['included_addons_breakdown'], $result['extra_addons_breakdown']),
                'pickup_fee_thb' => 0,
                'subtotal_thb' => $result['total_thb'],
                'dynamic_adjustment_thb' => 0,
                'applied_rules' => []
            ]
        ];
    }

    /**
     * Save booking addons
     */
    private function saveBookingAddons(int $bookingId, array $addons, int $guests, int $hours): void
    {
        foreach ($addons as $addonData) {
            $addon = $this->getAddonsService()->getById($addonData['addon_id']);
            if (!$addon) {
                continue;
            }

            $quantity = $addonData['quantity'] ?? 1;
            $unitPrice = $addon['price_thb'];

            // Calculate total based on price type
            $totalPrice = match ($addon['price_type']) {
                'per_person' => $unitPrice * $guests,
                'per_hour' => $unitPrice * $hours * $quantity,
                'per_item' => $unitPrice * $quantity,
                default => $unitPrice
            };

            $this->db->insert('booking_addons', [
                'booking_id' => $bookingId,
                'addon_id' => $addonData['addon_id'],
                'quantity' => $quantity,
                'unit_price_thb' => $unitPrice,
                'total_price_thb' => $totalPrice
            ]);
        }
    }

    /**
     * Get booking by reference
     */
    public function getByReference(string $reference): ?array
    {
        $booking = $this->db->queryOne(
            "SELECT b.*,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.name
                        WHEN b.bookable_type = 'tour' THEN t.name_en
                    END as item_name,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.thumbnail
                        WHEN b.bookable_type = 'tour' THEN t.thumbnail
                    END as item_thumbnail,
                    CASE
                        WHEN b.bookable_type = 'vessel' THEN v.slug
                        WHEN b.bookable_type = 'tour' THEN t.slug
                    END as item_slug,
                    p.name_en as package_name,
                    vnd.company_name as vendor_name
             FROM bookings b
             LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
             LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
             LEFT JOIN packages p ON b.package_id = p.id
             LEFT JOIN vendors vnd ON b.vendor_id = vnd.id
             WHERE b.booking_reference = ?",
            [$reference]
        );

        if ($booking) {
            $booking['extras_details'] = json_decode($booking['extras_details'], true) ?? [];
            $booking['pricing_rules_applied'] = json_decode($booking['pricing_rules_applied'], true) ?? [];

            // Get booking addons
            $booking['addons'] = $this->db->fetchAll(
                "SELECT ba.*, a.name_en as addon_name, a.icon
                 FROM booking_addons ba
                 JOIN addons a ON ba.addon_id = a.id
                 WHERE ba.booking_id = ?",
                [$booking['id']]
            );

            // Get status history
            $booking['status_history'] = $this->getStatusService()->getHistory($booking['id']);
        }

        return $booking;
    }

    /**
     * Update booking status (delegates to BookingStatusService)
     */
    public function updateStatus(
        string $reference,
        string $status,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $reason = null
    ): array {
        $booking = $this->getByReference($reference);

        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found'];
        }

        return $this->getStatusService()->transition(
            $booking['id'],
            $status,
            $actorType,
            $actorId,
            $reason
        );
    }

    /**
     * Cancel booking
     */
    public function cancel(string $reference, int $userId, ?string $reason = null): array
    {
        $booking = $this->getByReference($reference);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        if ($booking['user_id'] !== $userId) {
            return ['error' => 'Unauthorized'];
        }

        // Check cancellation policy based on loyalty tier
        $freeCancellationHours = $this->getLoyaltyService()->getFreeCancellationHours($userId);
        $hoursUntilBooking = (strtotime($booking['booking_date']) - time()) / 3600;

        if ($hoursUntilBooking < $freeCancellationHours && in_array($booking['status'], ['confirmed', 'paid'])) {
            return ['error' => "Free cancellation is only available {$freeCancellationHours}+ hours before booking"];
        }

        $result = $this->getStatusService()->transition(
            $booking['id'],
            BookingStatusService::STATUS_CANCELLED,
            BookingStatusService::ACTOR_USER,
            $userId,
            $reason
        );

        if ($result['success']) {
            return ['success' => true, 'message' => 'Booking cancelled'];
        }

        return ['error' => $result['error'] ?? 'Failed to cancel booking'];
    }

    /**
     * Confirm booking (admin/vendor)
     */
    public function confirm(string $reference, int $adminId): array
    {
        $booking = $this->getByReference($reference);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        $result = $this->getStatusService()->transition(
            $booking['id'],
            BookingStatusService::STATUS_CONFIRMED,
            BookingStatusService::ACTOR_ADMIN,
            $adminId
        );

        return $result;
    }

    /**
     * Mark as paid
     */
    public function markPaid(string $reference, string $paymentMethod, ?string $paymentId = null): array
    {
        $booking = $this->getByReference($reference);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        // Update payment info
        $this->db->update('bookings', [
            'payment_method' => $paymentMethod,
            'payment_provider_charge_id' => $paymentId,
            'amount_paid' => $booking['total_price_thb']
        ], 'id = ?', [$booking['id']]);

        $result = $this->getStatusService()->transition(
            $booking['id'],
            BookingStatusService::STATUS_PAID,
            BookingStatusService::ACTOR_SYSTEM
        );

        return $result;
    }

    /**
     * Get bookable item
     */
    private function getBookableItem(string $type, int $id): ?array
    {
        return match ($type) {
            'vessel' => $this->vesselService->getById($id),
            'tour' => $this->tourService->getById($id),
            default => null,
        };
    }

    /**
     * Generate unique booking reference
     */
    private function generateReference(): string
    {
        do {
            $reference = 'PYT-' . date('y') . date('m') . '-' . strtoupper(substr(uniqid(), -5));
            $existing = $this->db->queryOne(
                "SELECT id FROM bookings WHERE booking_reference = ?",
                [$reference]
            );
        } while ($existing);

        return $reference;
    }

    /**
     * Get allowed status transitions for user
     */
    public function getAllowedTransitions(string $reference, int $userId): array
    {
        $booking = $this->getByReference($reference);

        if (!$booking || $booking['user_id'] !== $userId) {
            return [];
        }

        return $this->getStatusService()->getAllowedTransitions(
            $booking['status'],
            BookingStatusService::ACTOR_USER
        );
    }
}
