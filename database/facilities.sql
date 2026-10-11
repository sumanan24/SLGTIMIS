-- Facilities / Maintenance Ticket Management
-- Integrated into existing sisslgti (staff, department, user).

CREATE TABLE IF NOT EXISTS `facilities_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(80) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fac_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_subcategories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_fac_sub_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_number` VARCHAR(40) NOT NULL,
  `ticket_year` SMALLINT UNSIGNED NOT NULL,
  `ticket_seq` INT UNSIGNED NOT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'new',
  `original_priority` VARCHAR(20) NOT NULL,
  `current_priority` VARCHAR(20) NOT NULL,
  `priority_changed_by` VARCHAR(64) DEFAULT NULL,
  `priority_changed_at` DATETIME DEFAULT NULL,
  `priority_change_reason` VARCHAR(255) DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `subcategory_id` INT UNSIGNED DEFAULT NULL,
  `location` VARCHAR(180) NOT NULL,
  `building` VARCHAR(120) DEFAULT NULL,
  `floor` VARCHAR(40) DEFAULT NULL,
  `room_area` VARCHAR(120) DEFAULT NULL,
  `remarks` TEXT,
  `requester_user_id` INT NOT NULL,
  `requester_staff_id` VARCHAR(64) NOT NULL,
  `requester_department_id` VARCHAR(16) DEFAULT NULL,
  `requester_contact` VARCHAR(120) DEFAULT NULL,
  `required_date` DATE DEFAULT NULL,
  `responsible_department_id` VARCHAR(16) DEFAULT NULL,
  `responsible_staff_id` VARCHAR(64) DEFAULT NULL,
  `assignment_type` VARCHAR(20) DEFAULT NULL,
  `vendor_name` VARCHAR(160) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `deadline` DATE DEFAULT NULL,
  `expected_completion_date` DATE DEFAULT NULL,
  `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `closed_by` VARCHAR(64) DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `closure_remarks` TEXT,
  `no_evidence_reason` VARCHAR(255) DEFAULT NULL,
  `requester_confirmation` VARCHAR(30) DEFAULT NULL,
  `requester_confirmation_at` DATETIME DEFAULT NULL,
  `requester_confirmation_comment` TEXT,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fac_ticket_no` (`ticket_number`),
  UNIQUE KEY `uq_fac_ticket_year_seq` (`ticket_year`, `ticket_seq`),
  KEY `idx_fac_status` (`status`),
  KEY `idx_fac_priority` (`current_priority`),
  KEY `idx_fac_req` (`requester_staff_id`),
  KEY `idx_fac_resp` (`responsible_staff_id`),
  KEY `idx_fac_dept` (`requester_department_id`),
  KEY `idx_fac_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_assignments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `assignment_type` VARCHAR(20) NOT NULL,
  `department_id` VARCHAR(16) DEFAULT NULL,
  `staff_id` VARCHAR(64) DEFAULT NULL,
  `vendor_name` VARCHAR(160) DEFAULT NULL,
  `supporting_staff` TEXT,
  `assigned_by` VARCHAR(64) NOT NULL,
  `assigned_at` DATETIME NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `deadline` DATE DEFAULT NULL,
  `expected_completion_date` DATE DEFAULT NULL,
  `instructions` TEXT,
  `remarks` TEXT,
  `is_current` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_fac_asg_ticket` (`ticket_id`),
  KEY `idx_fac_asg_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_progress_updates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `progress_percent` TINYINT UNSIGNED NOT NULL,
  `status` VARCHAR(40) DEFAULT NULL,
  `work_update` TEXT NOT NULL,
  `remarks` TEXT,
  `created_by` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_prg_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_evidence` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `evidence_type` VARCHAR(40) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(400) NOT NULL,
  `file_type` VARCHAR(80) DEFAULT NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `description` VARCHAR(255) DEFAULT NULL,
  `uploaded_by` VARCHAR(64) NOT NULL,
  `uploaded_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_evi_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `comment` TEXT NOT NULL,
  `created_by` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_cmt_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_status_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `old_value` VARCHAR(40) DEFAULT NULL,
  `new_value` VARCHAR(40) NOT NULL,
  `remarks` TEXT,
  `changed_by` VARCHAR(64) NOT NULL,
  `changed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_sth_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_priority_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `old_value` VARCHAR(20) DEFAULT NULL,
  `new_value` VARCHAR(20) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `changed_by` VARCHAR(64) NOT NULL,
  `changed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_prh_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_deadline_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `old_value` DATE DEFAULT NULL,
  `new_value` DATE DEFAULT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `changed_by` VARCHAR(64) NOT NULL,
  `changed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_dlh_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_verifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(30) NOT NULL,
  `reason` TEXT,
  `required_correction` TEXT,
  `new_deadline` DATE DEFAULT NULL,
  `verified_by` VARCHAR(64) NOT NULL,
  `verified_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_ver_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED DEFAULT NULL,
  `staff_id` VARCHAR(64) NOT NULL,
  `event_type` VARCHAR(60) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_ntf_staff` (`staff_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facilities_activity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(80) NOT NULL,
  `old_value` TEXT,
  `new_value` TEXT,
  `remarks` TEXT,
  `actor_staff_id` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fac_act_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
