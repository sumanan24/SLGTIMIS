-- Per-staff module access overrides (Staff Member → Module → Action).
-- Role RBAC remains the default. Rows here apply only to that staff_id.

CREATE TABLE IF NOT EXISTS `staff_module_permission` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `module_key` VARCHAR(64) NOT NULL,
    `access_mode` ENUM('inherit','grant','deny','custom') NOT NULL DEFAULT 'inherit',
    `can_view` TINYINT(1) NOT NULL DEFAULT 0,
    `can_add` TINYINT(1) NOT NULL DEFAULT 0,
    `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
    `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_module` (`staff_id`, `module_key`),
    KEY `idx_staff_module_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
