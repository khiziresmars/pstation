<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Database;
use App\Core\Logger;
use App\Core\AuthorizationException;

/**
 * Admin Service
 * Handles admin users, roles, and permissions
 */
class AdminService
{
    private Database $db;
    private Logger $logger;
    private ?array $currentAdmin = null;

    public function __construct()
    {
        $this->db = Application::getInstance()->getDatabase();
        $this->logger = new Logger('admin');
    }

    // ==================== Admin Management ====================

    /**
     * Get admin by Telegram ID
     */
    public function getByTelegramId(int $telegramId): ?array
    {
        return $this->db->queryOne("
            SELECT a.*, r.name as role_name, r.display_name as role_display_name, r.permissions
            FROM admins a
            JOIN admin_roles r ON a.role_id = r.id
            WHERE a.telegram_id = ? AND a.is_active = 1
        ", [$telegramId]);
    }

    /**
     * Get admin by ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->queryOne("
            SELECT a.*, r.name as role_name, r.display_name as role_display_name, r.permissions
            FROM admins a
            JOIN admin_roles r ON a.role_id = r.id
            WHERE a.id = ?
        ", [$id]);
    }

    /**
     * Get all admins
     */
    public function getAll(): array
    {
        return $this->db->query("
            SELECT a.*, r.name as role_name, r.display_name as role_display_name
            FROM admins a
            JOIN admin_roles r ON a.role_id = r.id
            ORDER BY a.created_at DESC
        ");
    }

    /**
     * Create new admin
     */
    public function create(array $data): int
    {
        // Validate role exists
        $role = $this->getRole($data['role_id']);
        if (!$role) {
            throw new \InvalidArgumentException('Invalid role');
        }

        $adminId = $this->db->insert('admins', [
            'telegram_id' => $data['telegram_id'],
            'username' => $data['username'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'role_id' => $data['role_id'],
            'is_active' => $data['is_active'] ?? 1,
            'notification_settings' => json_encode($data['notification_settings'] ?? []),
        ]);

        $this->logger->info("Admin created", [
            'admin_id' => $adminId,
            'telegram_id' => $data['telegram_id'],
            'role' => $role['name'],
        ]);

        return $adminId;
    }

    /**
     * Update admin
     */
    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['username'])) {
            $updateData['username'] = $data['username'];
        }
        if (isset($data['first_name'])) {
            $updateData['first_name'] = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $updateData['last_name'] = $data['last_name'];
        }
        if (isset($data['role_id'])) {
            $updateData['role_id'] = $data['role_id'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }
        if (isset($data['notification_settings'])) {
            $updateData['notification_settings'] = json_encode($data['notification_settings']);
        }

        if (empty($updateData)) {
            return false;
        }

        $updated = $this->db->update('admins', $updateData, 'id = ?', [$id]);

        if ($updated) {
            $this->logger->info("Admin updated", ['admin_id' => $id, 'changes' => array_keys($updateData)]);
        }

        return $updated > 0;
    }

    /**
     * Delete admin
     */
    public function delete(int $id): bool
    {
        $admin = $this->getById($id);
        if (!$admin) {
            return false;
        }

        // Prevent deleting the last super admin
        if ($admin['role_name'] === 'super_admin') {
            $count = $this->db->queryOne("
                SELECT COUNT(*) as count FROM admins a
                JOIN admin_roles r ON a.role_id = r.id
                WHERE r.name = 'super_admin' AND a.is_active = 1
            ")['count'];

            if ($count <= 1) {
                throw new \RuntimeException('Cannot delete the last super admin');
            }
        }

        $deleted = $this->db->delete('admins', 'id = ?', [$id]);

        if ($deleted) {
            $this->logger->info("Admin deleted", ['admin_id' => $id]);
        }

        return $deleted > 0;
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $adminId): void
    {
        $this->db->update('admins', [
            'last_login_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$adminId]);
    }

    // ==================== Role Management ====================

    /**
     * Get all roles
     */
    public function getRoles(): array
    {
        return $this->db->query("SELECT * FROM admin_roles ORDER BY id");
    }

    /**
     * Get role by ID
     */
    public function getRole(int $id): ?array
    {
        $role = $this->db->queryOne("SELECT * FROM admin_roles WHERE id = ?", [$id]);
        if ($role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?? [];
        }
        return $role;
    }

    /**
     * Get role by name
     */
    public function getRoleByName(string $name): ?array
    {
        $role = $this->db->queryOne("SELECT * FROM admin_roles WHERE name = ?", [$name]);
        if ($role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?? [];
        }
        return $role;
    }

    /**
     * Create custom role
     */
    public function createRole(array $data): int
    {
        return $this->db->insert('admin_roles', [
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'permissions' => json_encode($data['permissions'] ?? []),
            'is_system' => 0,
        ]);
    }

    /**
     * Update role permissions
     */
    public function updateRole(int $id, array $data): bool
    {
        $role = $this->getRole($id);
        if (!$role) {
            return false;
        }

        // Prevent modifying system roles
        if ($role['is_system'] && isset($data['name'])) {
            throw new \RuntimeException('Cannot modify system role name');
        }

        $updateData = [];
        if (isset($data['display_name'])) {
            $updateData['display_name'] = $data['display_name'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['permissions'])) {
            $updateData['permissions'] = json_encode($data['permissions']);
        }

        return $this->db->update('admin_roles', $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Delete custom role
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->getRole($id);
        if (!$role || $role['is_system']) {
            throw new \RuntimeException('Cannot delete system role');
        }

        // Check if role is in use
        $count = $this->db->queryOne("SELECT COUNT(*) as count FROM admins WHERE role_id = ?", [$id])['count'];
        if ($count > 0) {
            throw new \RuntimeException('Role is assigned to admins');
        }

        return $this->db->delete('admin_roles', 'id = ?', [$id]) > 0;
    }

    // ==================== Permission Checking ====================

    /**
     * Set current admin for permission checks
     */
    public function setCurrentAdmin(?array $admin): void
    {
        $this->currentAdmin = $admin;
    }

    /**
     * Get current admin
     */
    public function getCurrentAdmin(): ?array
    {
        return $this->currentAdmin;
    }

    /**
     * Check if current admin has permission
     */
    public function can(string $resource, string $action): bool
    {
        if (!$this->currentAdmin) {
            return false;
        }

        $permissions = json_decode($this->currentAdmin['permissions'] ?? '{}', true);

        // Super admin has all permissions
        if ($this->currentAdmin['role_name'] === 'super_admin') {
            return true;
        }

        if (!isset($permissions[$resource])) {
            return false;
        }

        return in_array($action, $permissions[$resource]);
    }

    /**
     * Require permission or throw exception
     */
    public function authorize(string $resource, string $action): void
    {
        if (!$this->can($resource, $action)) {
            $this->logger->warning("Authorization denied", [
                'admin_id' => $this->currentAdmin['id'] ?? null,
                'resource' => $resource,
                'action' => $action,
            ]);

            throw new AuthorizationException("You don't have permission to {$action} {$resource}");
        }
    }

    /**
     * Get available permissions
     */
    public function getAvailablePermissions(): array
    {
        return [
            'vessels' => ['view', 'create', 'edit', 'delete'],
            'tours' => ['view', 'create', 'edit', 'delete'],
            'bookings' => ['view', 'create', 'edit', 'delete', 'confirm', 'cancel'],
            'users' => ['view', 'edit', 'block'],
            'promos' => ['view', 'create', 'edit', 'delete'],
            'reviews' => ['view', 'approve', 'delete'],
            'settings' => ['view', 'edit'],
            'admins' => ['view', 'create', 'edit', 'delete'],
            'analytics' => ['view'],
            'logs' => ['view'],
        ];
    }

    // ==================== Activity Logging ====================

    /**
     * Log admin activity
     */
    public function logActivity(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        if (!$this->currentAdmin) {
            return;
        }

        $this->db->insert('admin_activity_log', [
            'admin_id' => $this->currentAdmin['id'],
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    /**
     * Get activity log
     */
    public function getActivityLog(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];

        if (isset($filters['admin_id'])) {
            $where[] = 'l.admin_id = ?';
            $params[] = $filters['admin_id'];
        }

        if (isset($filters['action'])) {
            $where[] = 'l.action = ?';
            $params[] = $filters['action'];
        }

        if (isset($filters['entity_type'])) {
            $where[] = 'l.entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (isset($filters['date_from'])) {
            $where[] = 'l.created_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $where[] = 'l.created_at <= ?';
            $params[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->query("
            SELECT l.*, a.username, a.first_name, a.last_name
            FROM admin_activity_log l
            JOIN admins a ON l.admin_id = a.id
            WHERE {$whereClause}
            ORDER BY l.created_at DESC
            LIMIT ? OFFSET ?
        ", $params);
    }

    /**
     * Get admin statistics
     */
    public function getAdminStats(int $adminId): array
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m-01');

        return [
            'actions_today' => $this->db->queryOne("
                SELECT COUNT(*) as count FROM admin_activity_log
                WHERE admin_id = ? AND DATE(created_at) = ?
            ", [$adminId, $today])['count'],

            'actions_this_month' => $this->db->queryOne("
                SELECT COUNT(*) as count FROM admin_activity_log
                WHERE admin_id = ? AND created_at >= ?
            ", [$adminId, $thisMonth])['count'],

            'bookings_confirmed' => $this->db->queryOne("
                SELECT COUNT(*) as count FROM admin_activity_log
                WHERE admin_id = ? AND action = 'booking.confirm' AND created_at >= ?
            ", [$adminId, $thisMonth])['count'],
        ];
    }
}
