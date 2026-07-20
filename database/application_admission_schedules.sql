-- Entrance exam / interview schedules for online student applications
-- Run once on the target database.

CREATE TABLE IF NOT EXISTS `application_admission_schedule` (
    `schedule_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `schedule_type` ENUM('entrance_exam','interview') NOT NULL,
    `application_level` ENUM('04','05') NOT NULL,
    `course_id` VARCHAR(50) DEFAULT NULL COMMENT 'Entrance/interview scoped to one NVQ course; NULL = all courses at level',
    `title` VARCHAR(200) NOT NULL,
    `schedule_date` DATE NOT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `venue` VARCHAR(255) NOT NULL DEFAULT '',
    `instructions` TEXT DEFAULT NULL,
    `public_token` VARCHAR(64) NOT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`schedule_id`),
    UNIQUE KEY `uq_public_token` (`public_token`),
    KEY `idx_type_level` (`schedule_type`, `application_level`),
    KEY `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `application_admission_schedule_entry` (
    `entry_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `schedule_id` INT UNSIGNED NOT NULL,
    `application_id` INT NOT NULL,
    `roll_number` VARCHAR(30) DEFAULT NULL,
    `room_or_panel` VARCHAR(100) DEFAULT NULL,
    `selection_status` ENUM('scheduled','selected','not_selected','waitlist') NOT NULL DEFAULT 'scheduled',
    `sort_order` INT NOT NULL DEFAULT 0,
    `notes` VARCHAR(255) DEFAULT NULL,
    `whatsapp_sent` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Staff marked schedule link sent via WhatsApp',
    PRIMARY KEY (`entry_id`),
    UNIQUE KEY `uq_schedule_application` (`schedule_id`, `application_id`),
    KEY `idx_schedule` (`schedule_id`),
    KEY `idx_application` (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
