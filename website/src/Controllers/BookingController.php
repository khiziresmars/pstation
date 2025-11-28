<?php

declare(strict_types=1);

namespace Website\Controllers;

use App\Services\VesselService;
use App\Services\TourService;
use App\Services\BookingService;
use App\Services\PromoService;

/**
 * Booking controller
 */
class BookingController extends BaseController
{
    /**
     * Booking form
     */
    public function index(array $params): void
    {
        $type = $params['type'] ?? '';
        $slug = $params['slug'] ?? '';

        // Get item
        $item = null;
        if ($type === 'vessel') {
            $service = new VesselService();
            $item = $service->getBySlug($slug);
        } elseif ($type === 'tour') {
            $service = new TourService();
            $item = $service->getBySlug($slug);
        }

        if (!$item) {
            http_response_code(404);
            $this->view->display('errors/404');
            return;
        }

        // SEO
        $this->seo
            ->title('Book ' . $item['name'])
            ->description("Complete your booking for {$item['name']}. Secure payment and instant confirmation.")
            ->canonical("/book/{$type}/{$slug}");

        $this->render('booking/index', [
            'type' => $type,
            'item' => $item,
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    /**
     * Submit booking
     */
    public function submit(array $params): void
    {
        // Validate CSRF
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid token'], 403);
            return;
        }

        $data = [
            'type' => $this->post('type'),
            'item_id' => (int) $this->post('item_id'),
            'date' => $this->post('date'),
            'time' => $this->post('time'),
            'guests' => (int) $this->post('guests', 1),
            'adults' => (int) $this->post('adults', 1),
            'children' => (int) $this->post('children', 0),
            'hours' => (int) $this->post('hours', 4),
            'name' => $this->post('name'),
            'email' => $this->post('email'),
            'phone' => $this->post('phone'),
            'promo_code' => $this->post('promo_code'),
            'special_requests' => $this->post('special_requests'),
        ];

        // Basic validation
        $errors = [];
        if (empty($data['name'])) $errors[] = 'Name is required';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required';
        }
        if (empty($data['date'])) $errors[] = 'Date is required';

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors], 400);
            return;
        }

        try {
            // Create user or get existing
            $user = $this->db->queryOne("SELECT id FROM users WHERE email = ?", [$data['email']]);

            if (!$user) {
                $userId = $this->db->insert('users', [
                    'email' => $data['email'],
                    'first_name' => explode(' ', $data['name'])[0],
                    'last_name' => implode(' ', array_slice(explode(' ', $data['name']), 1)),
                    'phone' => $data['phone'],
                    'source' => 'website',
                ]);
            } else {
                $userId = $user['id'];
            }

            // Calculate price
            $bookingService = new BookingService();
            $calculation = $bookingService->calculate([
                'type' => $data['type'],
                'item_id' => $data['item_id'],
                'date' => $data['date'],
                'hours' => $data['hours'],
                'adults' => $data['adults'],
                'children' => $data['children'],
                'promo_code' => $data['promo_code'],
            ]);

            // Create booking
            $reference = 'WEB' . strtoupper(substr(md5(uniqid()), 0, 8));

            $bookingId = $this->db->insert('bookings', [
                'reference' => $reference,
                'user_id' => $userId,
                'vessel_id' => $data['type'] === 'vessel' ? $data['item_id'] : null,
                'tour_id' => $data['type'] === 'tour' ? $data['item_id'] : null,
                'booking_date' => $data['date'],
                'start_time' => $data['time'] ?? '09:00:00',
                'hours' => $data['hours'],
                'adults' => $data['adults'],
                'children' => $data['children'],
                'total_guests' => $data['adults'] + $data['children'],
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount'],
                'total_amount' => $calculation['total'],
                'promo_code' => $data['promo_code'],
                'special_requests' => $data['special_requests'],
                'status' => 'pending',
                'source' => 'website',
            ]);

            $this->json([
                'success' => true,
                'reference' => $reference,
                'redirect' => '/booking/' . $reference,
            ]);

        } catch (\Exception $e) {
            error_log('Booking error: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Booking failed. Please try again.'], 500);
        }
    }

    /**
     * Booking confirmation page
     */
    public function confirmation(array $params): void
    {
        $reference = $params['reference'] ?? '';

        $booking = $this->db->queryOne("
            SELECT b.*,
                   v.name as vessel_name, v.images as vessel_images,
                   t.name as tour_name, t.images as tour_images,
                   u.first_name, u.last_name, u.email, u.phone
            FROM bookings b
            LEFT JOIN vessels v ON b.vessel_id = v.id
            LEFT JOIN tours t ON b.tour_id = t.id
            JOIN users u ON b.user_id = u.id
            WHERE b.reference = ?
        ", [$reference]);

        if (!$booking) {
            http_response_code(404);
            $this->view->display('errors/404');
            return;
        }

        // Determine item name and images
        $itemName = $booking['vessel_name'] ?? $booking['tour_name'];
        $images = json_decode($booking['vessel_images'] ?? $booking['tour_images'] ?? '[]', true);

        // SEO
        $this->seo
            ->title('Booking Confirmation - ' . $reference)
            ->description("Your booking {$reference} has been received. Thank you for choosing us!");

        $this->render('booking/confirmation', [
            'booking' => $booking,
            'itemName' => $itemName,
            'itemImage' => $images[0] ?? '',
        ]);
    }
}
