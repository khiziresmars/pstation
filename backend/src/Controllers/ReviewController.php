<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;

/**
 * Review Controller
 * Handles review operations
 */
class ReviewController
{
    /**
     * POST /api/reviews
     * Create a new review
     */
    public function create(): void
    {
        $userId = AuthMiddleware::userId();

        if (!$userId) {
            Response::unauthorized();
            return;
        }

        $data = Request::json();

        $errors = Request::validate([
            'bookable_type' => 'required|in:vessel,tour',
            'bookable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        $db = Application::getInstance()->getDatabase();

        // Check if user has a completed booking for this item
        $booking = $db->queryOne(
            "SELECT id FROM bookings
             WHERE user_id = ?
             AND bookable_type = ?
             AND bookable_id = ?
             AND status IN ('completed', 'paid')
             LIMIT 1",
            [$userId, $data['bookable_type'], $data['bookable_id']]
        );

        // Check if user already reviewed this item
        $existingReview = $db->queryOne(
            "SELECT id FROM reviews
             WHERE user_id = ?
             AND bookable_type = ?
             AND bookable_id = ?",
            [$userId, $data['bookable_type'], $data['bookable_id']]
        );

        if ($existingReview) {
            Response::error('You have already reviewed this item', 400);
            return;
        }

        // Create review
        $reviewId = $db->insert('reviews', [
            'user_id' => $userId,
            'bookable_type' => $data['bookable_type'],
            'bookable_id' => $data['bookable_id'],
            'booking_id' => $booking['id'] ?? null,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'pros' => $data['pros'] ?? null,
            'cons' => $data['cons'] ?? null,
            'images' => json_encode($data['images'] ?? []),
            'is_verified' => $booking ? true : false,
            'is_published' => true,
        ]);

        // Update item rating
        $this->updateItemRating($data['bookable_type'], $data['bookable_id']);

        Response::success(['id' => $reviewId], 'Review submitted successfully', 201);
    }

    /**
     * Update vessel/tour average rating
     */
    private function updateItemRating(string $type, int $id): void
    {
        $db = Application::getInstance()->getDatabase();

        $stats = $db->queryOne(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as count
             FROM reviews
             WHERE bookable_type = ?
             AND bookable_id = ?
             AND is_published = 1",
            [$type, $id]
        );

        $table = $type === 'vessel' ? 'vessels' : 'tours';

        $db->update($table, [
            'rating' => round((float) $stats['avg_rating'], 1),
            'reviews_count' => (int) $stats['count'],
        ], 'id = ?', [$id]);
    }
}
