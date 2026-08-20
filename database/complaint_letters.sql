-- SLGTI Student Complaint Letter Module
-- Auto-created via ComplaintLetterModel::ensureTables()

CREATE TABLE IF NOT EXISTS `complaint_letters` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference_no` VARCHAR(40) NOT NULL,
    `letter_date` DATE NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `recipient_name` VARCHAR(200) DEFAULT NULL,
    `recipient_address` TEXT DEFAULT NULL,
    `complaint_body` TEXT NOT NULL,
    `action_required` TEXT DEFAULT NULL,
    `department_id` VARCHAR(50) NOT NULL,
    `course_id` VARCHAR(50) DEFAULT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `status` ENUM('draft','final') NOT NULL DEFAULT 'draft',
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `deleted_by` INT DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    `generated_by` INT DEFAULT NULL,
    `generated_at` DATETIME DEFAULT NULL,
    `printed_by` INT DEFAULT NULL,
    `printed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_complaint_ref` (`reference_no`),
    KEY `idx_complaint_dept` (`department_id`),
    KEY `idx_complaint_year` (`academic_year`),
    KEY `idx_complaint_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `complaint_letter_students` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `complaint_letter_id` INT UNSIGNED NOT NULL,
    `student_id` VARCHAR(30) NOT NULL,
    `student_name` VARCHAR(200) NOT NULL,
    `student_reg_no` VARCHAR(50) DEFAULT NULL,
    `course_name` VARCHAR(200) DEFAULT NULL,
    `department_id` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cls_complaint` (`complaint_letter_id`),
    KEY `idx_cls_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `complaint_letter_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `complaint_letter_id` INT UNSIGNED DEFAULT NULL,
    `user_id` INT DEFAULT NULL,
    `user_role` VARCHAR(20) DEFAULT NULL,
    `department_id` VARCHAR(50) DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `student_ids` JSON DEFAULT NULL,
    `details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cla_complaint` (`complaint_letter_id`),
    KEY `idx_cla_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
