<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Application;
use App\Core\Response;
use App\Core\Request;
use App\Services\NotificationService;
use App\Services\BookingService;

/**
 * Admin Bookings Controller
 * Manage bookings
 */
class BookingsController extends BaseAdminController
{
    private $db;
    private NotificationService $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->db = Application::getInstance()->getDatabase();
        $this->notifications = new NotificationService();
    }

    /**
     * GET /admin/bookings
     * List all bookings
     */
    public function index(): void
    {
        if (!$this->authorize('bookings', 'view')) {
            return;
        }

        $pagination = $this->getPagination();
        $sort = $this->getSort(['booking_reference', 'booking_date', 'total_price_thb', 'status', 'created_at']);

        $where = ['1=1'];
        $params = [];

        if (isset($_GET['status'])) {
            $where[] = 'b.status = ?';
            $params[] = $_GET['status'];
        }

        if (isset($_GET['type'])) {
            $where[] = 'b.bookable_type = ?';
            $params[] = $_GET['type'];
        }

        if (isset($_GET['date_from'])) {
            $where[] = 'b.booking_date >= ?';
            $params[] = $_GET['date_from'];
        }

        if (isset($_GET['date_to'])) {
            $where[] = 'b.booking_date <= ?';
            $params[] = $_GET['date_to'];
        }

        if (isset($_GET['search'])) {
            $where[] = '(b.booking_reference LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ?)';
            $search = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->queryOne("
            SELECT COUNT(*) as count
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE {$whereClause}
        ", $params)['count'];

        $params[] = $pagination['limit'];
        $params[] = $pagination['offset'];

        $bookings = $this->db->query("
            SELECT
                b.*,
                u.first_name, u.last_name, u.username, u.telegram_id as user_telegram_id,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as item_name,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.thumbnail
                    WHEN b.bookable_type = 'tour' THEN t.thumbnail
                END as item_thumbnail
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            WHERE {$whereClause}
            ORDER BY b.{$sort['field']} {$sort['direction']}
            LIMIT ? OFFSET ?
        ", $params);

        $this->paginate($bookings, $total, $pagination['page'], $pagination['limit']);
    }

    /**
     * GET /admin/bookings/{reference}
     * Get booking details
     */
    public function show(string $reference): void
    {
        if (!$this->authorize('bookings', 'view')) {
            return;
        }

        $booking = $this->db->queryOne("
            SELECT
                b.*,
                u.id as user_id, u.first_name, u.last_name, u.username,
                u.telegram_id as user_telegram_id, u.phone, u.email,
                u.cashback_balance, u.total_spent,
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
                p.code as promo_code
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            LEFT JOIN promo_codes p ON b.promo_code_id = p.id
            WHERE b.booking_reference = ?
        ", [$reference]);

        if (!$booking) {
            Response::notFound('Booking not found');
            return;
        }

        // Parse extras
        $booking['extras_details'] = json_decode($booking['extras_details'] ?? '[]', true);

        // Get status history
        $booking['history'] = $this->db->query("
            SELECT * FROM admin_activity_log
            WHERE entity_type = 'booking' AND entity_id = ?
            ORDER BY created_at DESC
        ", [$booking['id']]);

        // Get user's other bookings
        $booking['user_bookings_count'] = $this->db->queryOne("
            SELECT COUNT(*) as count FROM bookings WHERE user_id = ?
        ", [$booking['user_id']])['count'];

        Response::success($booking);
    }

    /**
     * PUT /admin/bookings/{reference}/status
     * Update booking status
     */
    public function updateStatus(string $reference): void
    {
        if (!$this->authorize('bookings', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'status' => 'required|in:pending,confirmed,paid,completed,cancelled,refunded',
            'note' => 'nullable|string|max:500',
        ]);

        if ($data === null) {
            return;
        }

        $booking = $this->db->queryOne("SELECT * FROM bookings WHERE booking_reference = ?", [$reference]);
        if (!$booking) {
            Response::notFound('Booking not found');
            return;
        }

        $oldStatus = $booking['status'];
        $newStatus = $data['status'];

        // Validate status transition
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['paid', 'cancelled'],
            'paid' => ['completed', 'refunded'],
            'completed' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            Response::error("Cannot change status from {$oldStatus} to {$newStatus}", 400, 'INVALID_TRANSITION');
            return;
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'confirmed') {
            $updateData['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'paid') {
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'completed') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
            // Credit cashback
            if ($booking['cashback_earned_thb'] > 0 && $booking['cashback_status'] !== 'credited') {
                $userService = new \App\Services\UserService();
                $userService->addCashback(
                    $booking['user_id'],
                    $booking['cashback_earned_thb'],
                    $booking['id'],
                    "Cashback from booking {$reference}"
                );
                $updateData['cashback_status'] = 'credited';
            }
        } elseif ($newStatus === 'cancelled') {
            $updateData['cancelled_at'] = date('Y-m-d H:i:s');
            $updateData['cancellation_reason'] = $data['note'] ?? 'Cancelled by admin';
            // Refund used cashback
            if ($booking['cashback_used_thb'] > 0) {
                $userService = new \App\Services\UserService();
                $userService->addCashback(
                    $booking['user_id'],
                    $booking['cashback_used_thb'],
                    $booking['id'],
                    "Refund from cancelled booking {$reference}"
                );
            }
        } elseif ($newStatus === 'refunded') {
            $updateData['refunded_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update('bookings', $updateData, 'booking_reference = ?', [$reference]);

        $this->logActivity(
            'booking.status_change',
            'booking',
            $booking['id'],
            ['status' => $oldStatus],
            ['status' => $newStatus, 'note' => $data['note'] ?? null]
        );

        // Send notification
        if ($newStatus === 'confirmed') {
            $bookingWithDetails = $this->getBookingWithDetails($reference);
            $this->notifications->sendUserBookingConfirmation($bookingWithDetails);
        } elseif ($newStatus === 'cancelled') {
            $bookingWithDetails = $this->getBookingWithDetails($reference);
            $this->notifications->notifyBookingCancelled($bookingWithDetails, $data['note'] ?? null);
        }

        Response::success(['message' => 'Status updated successfully']);
    }

    /**
     * PUT /admin/bookings/{reference}/confirm
     * Confirm booking (shortcut)
     */
    public function confirm(string $reference): void
    {
        if (!$this->authorize('bookings', 'confirm')) {
            return;
        }

        $_POST['status'] = 'confirmed';
        $this->updateStatus($reference);
    }

    /**
     * PUT /admin/bookings/{reference}/cancel
     * Cancel booking
     */
    public function cancel(string $reference): void
    {
        if (!$this->authorize('bookings', 'cancel')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        $_POST['status'] = 'cancelled';
        $_POST['note'] = $data['reason'] ?? 'Cancelled by admin';
        $this->updateStatus($reference);
    }

    /**
     * PUT /admin/bookings/{reference}/payment
     * Update payment info
     */
    public function updatePayment(string $reference): void
    {
        if (!$this->authorize('bookings', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'payment_method' => 'required|in:telegram_stars,bank_transfer,credit_card,cash,crypto',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:200',
        ]);

        if ($data === null) {
            return;
        }

        $booking = $this->db->queryOne("SELECT * FROM bookings WHERE booking_reference = ?", [$reference]);
        if (!$booking) {
            Response::notFound('Booking not found');
            return;
        }

        $updateData = [
            'payment_method' => $data['payment_method'],
        ];

        if (isset($data['amount_paid'])) {
            $updateData['amount_paid'] = $data['amount_paid'];
            $updateData['amount_paid_original'] = $data['amount_paid'];
            $updateData['currency_paid'] = 'THB';
        }

        if (isset($data['payment_reference'])) {
            $updateData['payment_reference'] = $data['payment_reference'];
        }

        $this->db->update('bookings', $updateData, 'booking_reference = ?', [$reference]);

        $this->logActivity('booking.payment_update', 'booking', $booking['id'], null, $data);

        // If payment confirmed, update status to paid
        if ($booking['status'] === 'confirmed' && isset($data['amount_paid']) && $data['amount_paid'] >= $booking['total_price_thb']) {
            $_POST['status'] = 'paid';
            $this->updateStatus($reference);
            return;
        }

        Response::success(['message' => 'Payment info updated successfully']);
    }

    /**
     * POST /admin/bookings/{reference}/note
     * Add note to booking
     */
    public function addNote(string $reference): void
    {
        if (!$this->authorize('bookings', 'edit')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'note' => 'required|string|max:1000',
        ]);

        if ($data === null) {
            return;
        }

        $booking = $this->db->queryOne("SELECT id, admin_notes FROM bookings WHERE booking_reference = ?", [$reference]);
        if (!$booking) {
            Response::notFound('Booking not found');
            return;
        }

        $notes = json_decode($booking['admin_notes'] ?? '[]', true);
        $notes[] = [
            'text' => $data['note'],
            'admin_id' => $this->getAdmin()['id'],
            'admin_name' => $this->getAdmin()['first_name'] ?? $this->getAdmin()['username'],
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->update('bookings', [
            'admin_notes' => json_encode($notes),
        ], 'booking_reference = ?', [$reference]);

        $this->logActivity('booking.note_added', 'booking', $booking['id'], null, ['note' => $data['note']]);

        Response::success(['message' => 'Note added successfully']);
    }

    /**
     * GET /admin/bookings/calendar
     * Get bookings for calendar view
     */
    public function calendar(): void
    {
        if (!$this->authorize('bookings', 'view')) {
            return;
        }

        $month = $_GET['month'] ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $bookings = $this->db->query("
            SELECT
                b.id, b.booking_reference, b.bookable_type, b.booking_date,
                b.start_time, b.duration_hours, b.status,
                b.adults_count, b.children_count,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as item_name,
                u.first_name, u.last_name
            FROM bookings b
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.booking_date BETWEEN ? AND ?
            AND b.status NOT IN ('cancelled', 'refunded')
            ORDER BY b.booking_date, b.start_time
        ", [$startDate, $endDate]);

        // Group by date
        $calendar = [];
        foreach ($bookings as $booking) {
            $date = $booking['booking_date'];
            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }
            $calendar[$date][] = $booking;
        }

        Response::success($calendar);
    }

    /**
     * GET /admin/bookings/today
     * Get today's bookings
     */
    public function today(): void
    {
        if (!$this->authorize('bookings', 'view')) {
            return;
        }

        $today = date('Y-m-d');

        $bookings = $this->db->query("
            SELECT
                b.*,
                u.first_name, u.last_name, u.username, u.phone,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as item_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            WHERE b.booking_date = ? AND b.status IN ('confirmed', 'paid')
            ORDER BY b.start_time
        ", [$today]);

        Response::success($bookings);
    }

    /**
     * GET /admin/bookings/export
     * Export bookings to CSV
     */
    public function export(): void
    {
        if (!$this->authorize('bookings', 'view')) {
            return;
        }

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        $bookings = $this->db->query("
            SELECT
                b.booking_reference, b.bookable_type, b.booking_date, b.start_time,
                b.adults_count, b.children_count, b.total_price_thb, b.status,
                b.payment_method, b.created_at,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as item_name,
                u.first_name, u.last_name, u.username, u.phone
            FROM bookings b
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.booking_date BETWEEN ? AND ?
            ORDER BY b.booking_date, b.created_at
        ", [$dateFrom, $dateTo]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bookings_' . $dateFrom . '_' . $dateTo . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Reference', 'Type', 'Item', 'Date', 'Time', 'Adults', 'Children',
            'Total (THB)', 'Status', 'Payment', 'Customer', 'Phone', 'Created'
        ]);

        foreach ($bookings as $row) {
            fputcsv($output, [
                $row['booking_reference'],
                $row['bookable_type'],
                $row['item_name'],
                $row['booking_date'],
                $row['start_time'],
                $row['adults_count'],
                $row['children_count'],
                $row['total_price_thb'],
                $row['status'],
                $row['payment_method'],
                trim($row['first_name'] . ' ' . $row['last_name']),
                $row['phone'],
                $row['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }

    private function getBookingWithDetails(string $reference): array
    {
        return $this->db->queryOne("
            SELECT b.*, u.telegram_id as user_telegram_id, u.first_name as user_first_name,
                CASE
                    WHEN b.bookable_type = 'vessel' THEN v.name
                    WHEN b.bookable_type = 'tour' THEN t.name_en
                END as item_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN vessels v ON b.bookable_type = 'vessel' AND b.bookable_id = v.id
            LEFT JOIN tours t ON b.bookable_type = 'tour' AND b.bookable_id = t.id
            WHERE b.booking_reference = ?
        ", [$reference]);
    }
}
