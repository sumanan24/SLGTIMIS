-- Course + medium entrance cutoff marks: Northern province vs all other provinces
CREATE TABLE IF NOT EXISTS `application_admission_cutoff` (
    `cutoff_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_level` ENUM('04','05') NOT NULL,
    `course_id` VARCHAR(50) NOT NULL,
    `medium` VARCHAR(20) NOT NULL DEFAULT 'English' COMMENT 'Tamil, Sinhala, or English',
    `cutoff_northern` DECIMAL(6,2) DEFAULT NULL COMMENT 'Minimum marks for Northern province',
    `cutoff_other` DECIMAL(6,2) DEFAULT NULL COMMENT 'Minimum marks for other provinces',
    `updated_by` INT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`cutoff_id`),
    UNIQUE KEY `uq_cutoff_level_course_medium` (`application_level`, `course_id`, `medium`),
    KEY `idx_cutoff_level` (`application_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
