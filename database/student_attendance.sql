-- Student fingerprint attendance (device sync) — CREATE IF NOT EXISTS only.
-- Does not modify existing `attendance` (class/module) table.
-- Person ID maps to existing `student.student_id`.

CREATE TABLE IF NOT EXISTS `student_attendance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id` VARCHAR(50) NOT NULL,
  `student_name` VARCHAR(150) NOT NULL DEFAULT '',
  `attendance_date` DATE NOT NULL,
  `attendance_time` TIME NOT NULL,
  `attendance_datetime` DATETIME NOT NULL,
  `machine_id` VARCHAR(64) NOT NULL DEFAULT '',
  `event_id` VARCHAR(128) NOT NULL,
  `source` VARCHAR(32) NOT NULL DEFAULT 'hikvision',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_machine` (`event_id`, `machine_id`),
  KEY `idx_person_date` (`person_id`, `attendance_date`),
  KEY `idx_attendance_datetime` (`attendance_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_attendance_unmatched` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id` VARCHAR(50) NOT NULL,
  `machine_name` VARCHAR(150) NOT NULL DEFAULT '',
  `attendance_date` DATE NOT NULL,
  `attendance_time` TIME NOT NULL,
  `attendance_datetime` DATETIME NOT NULL,
  `machine_id` VARCHAR(64) NOT NULL DEFAULT '',
  `event_id` VARCHAR(128) NOT NULL,
  `note` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unmatched_event_machine` (`event_id`, `machine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_attendance_sync_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL DEFAULT NULL,
  `username` VARCHAR(100) NOT NULL DEFAULT '',
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'running',
  `date_from` DATE NULL DEFAULT NULL,
  `date_to` DATE NULL DEFAULT NULL,
  `records_retrieved` INT NOT NULL DEFAULT 0,
  `valid_student` INT NOT NULL DEFAULT 0,
  `staff_ignored` INT NOT NULL DEFAULT 0,
  `empty_person_id` INT NOT NULL DEFAULT 0,
  `unmatched` INT NOT NULL DEFAULT 0,
  `duplicates` INT NOT NULL DEFAULT 0,
  `saved` INT NOT NULL DEFAULT 0,
  `failed` INT NOT NULL DEFAULT 0,
  `error_message` VARCHAR(500) NOT NULL DEFAULT '',
  `machine_id` VARCHAR(64) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
