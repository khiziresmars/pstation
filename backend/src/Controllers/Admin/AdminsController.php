<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Request;
use App\Services\AdminService;

/**
 * Admin Admins Controller
 * Manage admin users and roles
 */
class AdminsController extends BaseAdminController
{
    /**
     * GET /admin/admins
     * List all admins
     */
    public function index(): void
    {
        if (!$this->authorize('admins', 'view')) {
            return;
        }

        $admins = $this->adminService->getAll();

        // Add stats for each admin
        foreach ($admins as &$admin) {
            $admin['stats'] = $this->adminService->getAdminStats($admin['id']);
        }

        Response::success($admins);
    }

    /**
     * GET /admin/admins/{id}
     * Get admin details
     */
    public function show(int $id): void
    {
        if (!$this->authorize('admins', 'view')) {
            return;
        }

        $admin = $this->adminService->getById($id);

        if (!$admin) {
            Response::notFound('Admin not found');
            return;
        }

        $admin['permissions'] = json_decode($admin['permissions'] ?? '{}', true);
        $admin['notification_settings'] = json_decode($admin['notification_settings'] ?? '{}', true);
        $admin['stats'] = $this->adminService->getAdminStats($id);

        // Get recent activity
        $admin['recent_activity'] = $this->adminService->getActivityLog(['admin_id' => $id], 20);

        Response::success($admin);
    }

    /**
     * POST /admin/admins
     * Create new admin
     */
    public function store(): void
    {
        if (!$this->authorize('admins', 'create')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'telegram_id' => 'required|integer',
            'username' => 'nullable|string|max:100',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'role_id' => 'required|integer',
            'notification_settings' => 'nullable|array',
        ]);

        if ($data === null) {
            return;
        }

        // Check if telegram_id already exists
        $existing = $this->adminService->getByTelegramId($data['telegram_id']);
        if ($existing) {
            Response::error('Admin with this Telegram ID already exists', 400, 'DUPLICATE_ADMIN');
            return;
        }

        try {
            $adminId = $this->adminService->create($data);
            $this->logActivity('admin.create', 'admin', $adminId, null, $data);

            Response::success(['id' => $adminId, 'message' => 'Admin created successfully'], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * PUT /admin/admins/{id}
     * Update admin
     */
    public function update(int $id): void
    {
        if (!$this->authorize('admins', 'edit')) {
            return;
        }

        $admin = $this->adminService->getById($id);
        if (!$admin) {
            Response::notFound('Admin not found');
            return;
        }

        // Prevent self-demotion for super admins
        $currentAdmin = $this->getAdmin();
        if ($id === $currentAdmin['id'] && isset($_POST['role_id'])) {
            $newRole = $this->adminService->getRole((int) $_POST['role_id']);
            if ($newRole && $newRole['name'] !== 'super_admin' && $currentAdmin['role_name'] === 'super_admin') {
                Response::error('Cannot demote yourself from super admin', 400, 'SELF_DEMOTION');
                return;
            }
        }

        $data = $this->validate(Request::all(), [
            'username' => 'nullable|string|max:100',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'role_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'notification_settings' => 'nullable|array',
        ]);

        if ($data === null) {
            return;
        }

        $this->adminService->update($id, $data);
        $this->logActivity('admin.update', 'admin', $id, $admin, $data);

        Response::success(['message' => 'Admin updated successfully']);
    }

    /**
     * DELETE /admin/admins/{id}
     * Delete admin
     */
    public function destroy(int $id): void
    {
        if (!$this->authorize('admins', 'delete')) {
            return;
        }

        // Prevent self-deletion
        if ($id === $this->getAdmin()['id']) {
            Response::error('Cannot delete yourself', 400, 'SELF_DELETE');
            return;
        }

        try {
            $this->adminService->delete($id);
            $this->logActivity('admin.delete', 'admin', $id, null, null);

            Response::success(['message' => 'Admin deleted successfully']);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /admin/admins/roles
     * Get all roles
     */
    public function roles(): void
    {
        if (!$this->authorize('admins', 'view')) {
            return;
        }

        $roles = $this->adminService->getRoles();

        foreach ($roles as &$role) {
            $role['permissions'] = json_decode($role['permissions'] ?? '{}', true);
        }

        Response::success($roles);
    }

    /**
     * POST /admin/admins/roles
     * Create custom role
     */
    public function createRole(): void
    {
        if (!$this->authorize('admins', 'create')) {
            return;
        }

        $data = $this->validate(Request::all(), [
            'name' => 'required|string|alpha_dash|max:50',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
        ]);

        if ($data === null) {
            return;
        }

        // Check name uniqueness
        $existing = $this->adminService->getRoleByName($data['name']);
        if ($existing) {
            Response::error('Role name already exists', 400, 'DUPLICATE_ROLE');
            return;
        }

        $roleId = $this->adminService->createRole($data);
        $this->logActivity('role.create', 'role', $roleId, null, $data);

        Response::success(['id' => $roleId, 'message' => 'Role created successfully'], 201);
    }

    /**
     * PUT /admin/admins/roles/{id}
     * Update role
     */
    public function updateRole(int $id): void
    {
        if (!$this->authorize('admins', 'edit')) {
            return;
        }

        $role = $this->adminService->getRole($id);
        if (!$role) {
            Response::notFound('Role not found');
            return;
        }

        $data = $this->validate(Request::all(), [
            'display_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
        ]);

        if ($data === null) {
            return;
        }

        try {
            $this->adminService->updateRole($id, $data);
            $this->logActivity('role.update', 'role', $id, $role, $data);

            Response::success(['message' => 'Role updated successfully']);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * DELETE /admin/admins/roles/{id}
     * Delete custom role
     */
    public function deleteRole(int $id): void
    {
        if (!$this->authorize('admins', 'delete')) {
            return;
        }

        try {
            $this->adminService->deleteRole($id);
            $this->logActivity('role.delete', 'role', $id, null, null);

            Response::success(['message' => 'Role deleted successfully']);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /admin/admins/permissions
     * Get available permissions
     */
    public function permissions(): void
    {
        if (!$this->authorize('admins', 'view')) {
            return;
        }

        Response::success($this->adminService->getAvailablePermissions());
    }

    /**
     * GET /admin/admins/me
     * Get current admin profile
     */
    public function me(): void
    {
        $admin = $this->getAdmin();

        if (!$admin) {
            Response::error('Not authenticated', 401);
            return;
        }

        $admin['permissions'] = json_decode($admin['permissions'] ?? '{}', true);
        $admin['notification_settings'] = json_decode($admin['notification_settings'] ?? '{}', true);
        $admin['stats'] = $this->adminService->getAdminStats($admin['id']);

        Response::success($admin);
    }

    /**
     * PUT /admin/admins/me
     * Update current admin profile
     */
    public function updateMe(): void
    {
        $admin = $this->getAdmin();

        $data = $this->validate(Request::all(), [
            'notification_settings' => 'nullable|array',
        ]);

        if ($data === null) {
            return;
        }

        // Only allow notification settings update for self
        $this->adminService->update($admin['id'], [
            'notification_settings' => $data['notification_settings'] ?? [],
        ]);

        Response::success(['message' => 'Profile updated successfully']);
    }
}
