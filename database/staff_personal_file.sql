-- Staff Personal File: extra columns on staff plus related records.
-- Existing staff rows remain valid; all new columns/tables are nullable or defaulted.

ALTER TABLE `staff`
    ADD COLUMN `staff_civil_status` VARCHAR(20) NULL DEFAULT NULL AFTER `staff_gender`,
    ADD COLUMN `staff_nationality` VARCHAR(50) NULL DEFAULT 'Sri Lankan' AFTER `staff_civil_status`,
    ADD COLUMN `staff_photo_path` VARCHAR(255) NULL DEFAULT NULL AFTER `staff_nationality`,
    ADD COLUMN `staff_current_address` VARCHAR(255) NULL DEFAULT NULL AFTER `staff_address`,
    ADD COLUMN `staff_emergency_name` VARCHAR(100) NULL DEFAULT NULL AFTER `staff_pno`,
    ADD COLUMN `staff_emergency_phone` VARCHAR(20) NULL DEFAULT NULL AFTER `staff_emergency_name`,
    ADD COLUMN `staff_emergency_relation` VARCHAR(50) NULL DEFAULT NULL AFTER `staff_emergency_phone`,
    ADD COLUMN `staff_designation` VARCHAR(100) NULL DEFAULT NULL AFTER `staff_position`,
    ADD COLUMN `staff_confirmation_date` DATE NULL DEFAULT NULL AFTER `staff_date_of_join`,
    ADD COLUMN `staff_retirement_date` DATE NULL DEFAULT NULL AFTER `staff_confirmation_date`,
    ADD COLUMN `staff_bank_name` VARCHAR(100) NULL DEFAULT NULL AFTER `staff_epf`,
    ADD COLUMN `staff_bank_branch` VARCHAR(100) NULL DEFAULT NULL AFTER `staff_bank_name`,
    ADD COLUMN `staff_bank_account` VARCHAR(50) NULL DEFAULT NULL AFTER `staff_bank_branch`,
    ADD COLUMN `staff_etf` VARCHAR(20) NULL DEFAULT NULL AFTER `staff_bank_account`;

CREATE TABLE IF NOT EXISTS `staff_qualification` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `qual_type` VARCHAR(40) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `institute` VARCHAR(255) DEFAULT NULL,
    `year_completed` VARCHAR(10) DEFAULT NULL,
    `result` VARCHAR(100) DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_qual_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_training` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `train_type` VARCHAR(40) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `organizer` VARCHAR(255) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_train_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_service_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `event_type` VARCHAR(40) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `from_value` VARCHAR(255) DEFAULT NULL,
    `to_value` VARCHAR(255) DEFAULT NULL,
    `effective_date` DATE DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_service_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_leave_record` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `leave_type` VARCHAR(40) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `days` DECIMAL(6,1) DEFAULT NULL,
    `reason` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'Recorded',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_leave_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_document` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `doc_type` VARCHAR(40) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) DEFAULT NULL,
    `stored_path` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `file_size` INT(11) DEFAULT 0,
    `approved` TINYINT(1) NOT NULL DEFAULT 0,
    `approved_by` INT(11) DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `uploaded_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_doc_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_family` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `member_type` VARCHAR(40) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `relationship` VARCHAR(50) DEFAULT NULL,
    `nic` VARCHAR(20) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_family_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_admin_record` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` VARCHAR(64) NOT NULL,
    `record_type` VARCHAR(40) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `record_date` DATE DEFAULT NULL,
    `notes` VARCHAR(500) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_admin_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
