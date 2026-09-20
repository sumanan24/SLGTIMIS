-- SLGTI MIS — Staff Role Salary Structure
-- Grade 3 / Grade 2 / Grade 1 scales, 18-year increments, revision history.
-- Auto-applied via StaffRoleSalaryModel::ensureTables(); safe to re-run.

CREATE TABLE IF NOT EXISTS `staff_role_salary_scale` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_position_type_id` VARCHAR(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    `grade` TINYINT UNSIGNED NOT NULL COMMENT '1=Grade 1, 2=Grade 2, 3=Grade 3',
    `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `allowances` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `gross_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `max_basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `effective_date` DATE NOT NULL,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_role_salary_grade` (`staff_position_type_id`, `grade`),
    KEY `idx_role_salary_effective` (`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_role_salary_increment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `salary_scale_id` INT UNSIGNED NOT NULL,
    `service_year` TINYINT UNSIGNED NOT NULL COMMENT '1-18 individual increment amount',
    `increment_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_scale_service_year` (`salary_scale_id`, `service_year`),
    KEY `idx_increment_year` (`service_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_role_salary_revision` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_position_type_id` VARCHAR(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    `grade` TINYINT UNSIGNED NOT NULL,
    `salary_scale_id` INT UNSIGNED NOT NULL,
    `revision_date` DATE NOT NULL,
    `effective_date` DATE NOT NULL,
    `old_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `old_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `old_gross` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `old_max_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_gross` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_max_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_revision_role_grade` (`staff_position_type_id`, `grade`, `effective_date`),
    KEY `idx_revision_scale` (`salary_scale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_salary` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
    `staff_position_type_id` VARCHAR(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    `grade` TINYINT UNSIGNED NOT NULL,
    `current_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `current_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `current_gross` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `last_applied_service_year` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_event_type` VARCHAR(20) DEFAULT NULL,
    `last_increment_date` DATE DEFAULT NULL,
    `last_revision_id` INT UNSIGNED DEFAULT NULL,
    `effective_date` DATE NOT NULL,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_staff_salary_staff` (`staff_id`),
    KEY `idx_staff_salary_role` (`staff_position_type_id`, `grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_salary_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
    `staff_position_type_id` VARCHAR(11) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
    `grade` TINYINT UNSIGNED NOT NULL,
    `event_type` ENUM('INITIAL','INCREMENT','REVISION') NOT NULL,
    `service_year` TINYINT UNSIGNED DEFAULT NULL,
    `old_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `old_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `old_gross` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_basic` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_allowance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_gross` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `increment_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `effective_date` DATE NOT NULL,
    `remarks` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_salary_hist_staff` (`staff_id`, `effective_date`),
    KEY `idx_staff_salary_hist_role` (`staff_position_type_id`, `grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_role_salary_allowance` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `salary_scale_id` INT UNSIGNED NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL COMMENT '1, 2 or 3',
    `allowance_name` VARCHAR(100) NOT NULL DEFAULT '',
    `allowance_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_scale_allowance_order` (`salary_scale_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_role_salary_revision_allowance` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `revision_id` INT UNSIGNED NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL,
    `allowance_name` VARCHAR(100) NOT NULL DEFAULT '',
    `old_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `new_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `idx_rev_allowance_revision` (`revision_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_role_salary_increment_slab` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `salary_scale_id` INT UNSIGNED NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL COMMENT '1, 2 or 3',
    `years` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'How many years this amount applies',
    `increment_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_scale_increment_slab` (`salary_scale_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
