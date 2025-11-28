<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Services\UserNotificationService;

/**
 * Notification Controller
 * Handles user notification endpoints
 */
class NotificationController
{
    private UserNotificationService $notificationService;
    private array $user;

    public function __construct()
    {
        $this->notificationService = new UserNotificationService();
        $this->user = Request::user();
    }

    /**
     * Get user notifications
     * GET /api/user/notifications
     */
    public function index(): void
    {
        $limit = min((int) Request::get('limit', 20), 50);
        $offset = (int) Request::get('offset', 0);
        $unreadOnly = Request::get('unread_only') === 'true';

        $notifications = $this->notificationService->getForUser(
            $this->user['id'],
            $limit,
            $offset,
            $unreadOnly
        );

        $unreadCount = $this->notificationService->getUnreadCount($this->user['id']);

        Response::json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Get unread count
     * GET /api/user/notifications/unread-count
     */
    public function unreadCount(): void
    {
        $count = $this->notificationService->getUnreadCount($this->user['id']);

        Response::json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    /**
     * Mark notification as read
     * PUT /api/user/notifications/{id}/read
     */
    public function markRead(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if (!$id) {
            Response::json(['success' => false, 'error' => 'Invalid notification ID'], 400);
            return;
        }

        $success = $this->notificationService->markAsRead($id, $this->user['id']);

        if (!$success) {
            Response::json(['success' => false, 'error' => 'Notification not found'], 404);
            return;
        }

        Response::json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     * PUT /api/user/notifications/read-all
     */
    public function markAllRead(): void
    {
        $count = $this->notificationService->markAllAsRead($this->user['id']);

        Response::json([
            'success' => true,
            'data' => ['marked_count' => $count],
        ]);
    }

    /**
     * Delete notification
     * DELETE /api/user/notifications/{id}
     */
    public function delete(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if (!$id) {
            Response::json(['success' => false, 'error' => 'Invalid notification ID'], 400);
            return;
        }

        $success = $this->notificationService->delete($id, $this->user['id']);

        if (!$success) {
            Response::json(['success' => false, 'error' => 'Notification not found'], 404);
            return;
        }

        Response::json(['success' => true]);
    }
}
