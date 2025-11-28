<?php

declare(strict_types=1);

use App\Core\Database;

class AddAdminRoles
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    public function up(): void
    {
        // Create admin_roles table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS admin_roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description TEXT,
                permissions JSON,
                is_system TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create admins table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS admins (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                telegram_id BIGINT UNSIGNED NOT NULL UNIQUE,
                username VARCHAR(100),
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                role_id INT UNSIGNED NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                notification_settings JSON,
                last_login_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES admin_roles(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create admin_activity_log table
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS admin_activity_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_id INT UNSIGNED NOT NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50),
                entity_id INT UNSIGNED,
                old_values JSON,
                new_values JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                INDEX idx_admin_activity_admin (admin_id),
                INDEX idx_admin_activity_action (action),
                INDEX idx_admin_activity_entity (entity_type, entity_id),
                INDEX idx_admin_activity_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insert default roles
        $this->db->execute("
            INSERT INTO admin_roles (name, display_name, description, permissions, is_system) VALUES
            ('super_admin', 'Super Administrator', 'Full access to all features', JSON_OBJECT(
                'vessels', JSON_ARRAY('view', 'create', 'edit', 'delete'),
                'tours', JSON_ARRAY('view', 'create', 'edit', 'delete'),
                'bookings', JSON_ARRAY('view', 'create', 'edit', 'delete', 'confirm', 'cancel'),
                'users', JSON_ARRAY('view', 'edit', 'block'),
                'promos', JSON_ARRAY('view', 'create', 'edit', 'delete'),
                'reviews', JSON_ARRAY('view', 'approve', 'delete'),
                'settings', JSON_ARRAY('view', 'edit'),
                'admins', JSON_ARRAY('view', 'create', 'edit', 'delete'),
                'analytics', JSON_ARRAY('view'),
                'logs', JSON_ARRAY('view')
            ), 1),
            ('manager', 'Manager', 'Manage bookings and content', JSON_OBJECT(
                'vessels', JSON_ARRAY('view', 'edit'),
                'tours', JSON_ARRAY('view', 'edit'),
                'bookings', JSON_ARRAY('view', 'confirm', 'cancel'),
                'users', JSON_ARRAY('view'),
                'promos', JSON_ARRAY('view', 'create', 'edit'),
                'reviews', JSON_ARRAY('view', 'approve'),
                'analytics', JSON_ARRAY('view')
            ), 1),
            ('operator', 'Operator', 'Handle bookings and customer support', JSON_OBJECT(
                'vessels', JSON_ARRAY('view'),
                'tours', JSON_ARRAY('view'),
                'bookings', JSON_ARRAY('view', 'confirm'),
                'users', JSON_ARRAY('view'),
                'reviews', JSON_ARRAY('view')
            ), 1),
            ('content_manager', 'Content Manager', 'Manage vessels, tours, and content', JSON_OBJECT(
                'vessels', JSON_ARRAY('view', 'create', 'edit'),
                'tours', JSON_ARRAY('view', 'create', 'edit'),
                'reviews', JSON_ARRAY('view', 'approve', 'delete')
            ), 1)
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->execute("DROP TABLE IF EXISTS admin_activity_log");
        $this->db->execute("DROP TABLE IF EXISTS admins");
        $this->db->execute("DROP TABLE IF EXISTS admin_roles");
    }
}
