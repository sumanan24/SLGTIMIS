-- SLGTI Student Registration Checklist (seven stations + payment)
CREATE TABLE IF NOT EXISTS `student_registration_checklist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(50) NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `station_data` LONGTEXT NOT NULL,
  `applicable_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `outstanding_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `receipt_number` VARCHAR(80) NOT NULL DEFAULT '',
  `payment_status` VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
  `remarks` TEXT NULL,
  `final_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `officer_name` VARCHAR(120) NOT NULL DEFAULT '',
  `coordinator_name` VARCHAR(120) NOT NULL DEFAULT '',
  `created_by` INT NULL,
  `updated_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reg_checklist_student_year` (`student_id`, `academic_year`),
  KEY `idx_reg_checklist_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
