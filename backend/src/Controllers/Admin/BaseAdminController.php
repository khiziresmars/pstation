<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Validator;
use App\Middleware\AdminAuthMiddleware;
use App\Services\AdminService;

/**
 * Base Admin Controller
 * Common functionality for all admin controllers
 */
abstract class BaseAdminController
{
    protected AdminService $adminService;

    public function __construct()
    {
        $this->adminService = new AdminService();
        $admin = AdminAuthMiddleware::getAdmin();
        if ($admin) {
            $this->adminService->setCurrentAdmin($admin);
        }
    }

    /**
     * Get current admin
     */
    protected function getAdmin(): ?array
    {
        return AdminAuthMiddleware::getAdmin();
    }

    /**
     * Check permission
     */
    protected function can(string $resource, string $action): bool
    {
        return $this->adminService->can($resource, $action);
    }

    /**
     * Require permission
     */
    protected function authorize(string $resource, string $action): bool
    {
        if (!$this->can($resource, $action)) {
            Response::error(
                "You don't have permission to {$action} {$resource}",
                403,
                'PERMISSION_DENIED'
            );
            return false;
        }
        return true;
    }

    /**
     * Log admin activity
     */
    protected function logActivity(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $this->adminService->logActivity($action, $entityType, $entityId, $oldValues, $newValues);
    }

    /**
     * Validate request data
     */
    protected function validate(array $data, array $rules): ?array
    {
        $validator = Validator::make($data)->validate($rules);

        if ($validator->fails()) {
            Response::validationError($validator->errors());
            return null;
        }

        return $validator->validated();
    }

    /**
     * Get pagination parameters
     */
    protected function getPagination(): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Get sort parameters
     */
    protected function getSort(array $allowedFields, string $defaultField = 'created_at', string $defaultDirection = 'DESC'): array
    {
        $field = $_GET['sort'] ?? $defaultField;
        $direction = strtoupper($_GET['direction'] ?? $defaultDirection);

        if (!in_array($field, $allowedFields)) {
            $field = $defaultField;
        }

        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = $defaultDirection;
        }

        return [
            'field' => $field,
            'direction' => $direction,
        ];
    }

    /**
     * Format paginated response
     */
    protected function paginate(array $items, int $total, int $page, int $limit): void
    {
        Response::success([
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
                'has_more' => ($page * $limit) < $total,
            ],
        ]);
    }
}
