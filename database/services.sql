CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(40) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_providers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_code` VARCHAR(40) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `provider_name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(120) NULL,
  `phone` VARCHAR(20) NULL,
  `email` VARCHAR(150) NULL,
  `address` VARCHAR(255) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'Active',
  `remarks` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_providers_code` (`provider_code`),
  KEY `idx_service_providers_category` (`category_id`),
  KEY `idx_service_providers_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_staff` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_code` VARCHAR(40) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `photo_path` VARCHAR(255) NULL,
  `designation` VARCHAR(100) NULL,
  `nic` VARCHAR(20) NULL,
  `phone` VARCHAR(20) NULL,
  `finger_machine_no` VARCHAR(50) NULL,
  `address` VARCHAR(255) NULL,
  `email` VARCHAR(150) NULL,
  `employment_status` VARCHAR(40) NOT NULL DEFAULT 'Working',
  `joined_on` DATE NULL,
  `left_on` DATE NULL,
  `police_status` VARCHAR(40) NULL,
  `police_path` VARCHAR(255) NULL,
  `clearance_issued_on` DATE NULL,
  `clearance_valid_until` DATE NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_staff_code` (`staff_code`),
  KEY `idx_service_staff_category` (`category_id`),
  UNIQUE KEY `uq_service_staff_finger` (`finger_machine_no`),
  KEY `idx_service_staff_provider` (`provider_id`),
  KEY `idx_service_staff_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_contracts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_code` VARCHAR(40) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NULL,
  `reference_no` VARCHAR(80) NULL,
  `start_date` DATE NULL,
  `expiry_date` DATE NULL,
  `contract_value` DECIMAL(12,2) NULL,
  `monthly_amount` DECIMAL(12,2) NULL,
  `document_path` VARCHAR(255) NULL,
  `responsible_officer` VARCHAR(120) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'Active',
  `renewal_of_id` INT UNSIGNED NULL,
  `renewal_remarks` TEXT NULL,
  `terms_text` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_contracts_code` (`contract_code`),
  KEY `idx_service_contracts_category` (`category_id`),
  KEY `idx_service_contracts_expiry` (`expiry_date`),
  KEY `idx_service_contracts_status` (`status`),
  KEY `idx_service_contracts_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_no` VARCHAR(60) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NULL,
  `contract_id` INT UNSIGNED NULL,
  `invoice_date` DATE NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `attendance_days` INT UNSIGNED NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'Pending',
  `payment_date` DATE NULL,
  `remarks` TEXT NULL,
  `document_path` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_service_invoices_category` (`category_id`),
  KEY `idx_service_invoices_status` (`status`),
  KEY `idx_service_invoices_date` (`invoice_date`),
  KEY `idx_service_invoices_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_code` VARCHAR(40) NOT NULL,
  `invoice_id` INT UNSIGNED NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `paid_on` DATE NULL,
  `method` VARCHAR(40) NULL,
  `reference_no` VARCHAR(80) NULL,
  `remarks` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_payments_code` (`payment_code`),
  KEY `idx_service_payments_invoice` (`invoice_id`),
  KEY `idx_service_payments_category` (`category_id`),
  KEY `idx_service_payments_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `resource_key` VARCHAR(60) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(180) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_service_documents_record` (`resource_key`, `record_id`),
  KEY `idx_service_documents_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_performance_evaluations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `eval_code` VARCHAR(40) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NULL,
  `period_month` CHAR(7) NULL,
  `attendance_score` TINYINT UNSIGNED NULL,
  `punctuality_score` TINYINT UNSIGNED NULL,
  `discipline_score` TINYINT UNSIGNED NULL,
  `quality_score` TINYINT UNSIGNED NULL,
  `response_score` TINYINT UNSIGNED NULL,
  `food_quality` TINYINT UNSIGNED NULL,
  `hygiene_score` TINYINT UNSIGNED NULL,
  `customer_service` TINYINT UNSIGNED NULL,
  `price_compliance` TINYINT UNSIGNED NULL,
  `agreement_compliance` TINYINT UNSIGNED NULL,
  `rating` TINYINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_eval_code` (`eval_code`),
  KEY `idx_service_eval_category` (`category_id`),
  KEY `idx_service_eval_staff` (`staff_id`),
  KEY `idx_service_eval_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `contract_id` INT UNSIGNED NULL,
  `inventory_id` INT UNSIGNED NULL,
  `milestone` VARCHAR(20) NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `is_read` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_notice` (`contract_id`, `inventory_id`, `milestone`),
  KEY `idx_service_notice_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `service_audit` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `staff_id` VARCHAR(64) NULL,
  `action` VARCHAR(20) NOT NULL,
  `resource_key` VARCHAR(60) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `summary` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service_audit_record` (`resource_key`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `security_attendance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `shift` VARCHAR(20) NOT NULL,
  `location` VARCHAR(120) NULL,
  `status` VARCHAR(20) NOT NULL,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sec_att_staff` (`staff_id`),
  KEY `idx_sec_att_date` (`attendance_date`),
  KEY `idx_sec_att_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `security_duty_rosters` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT UNSIGNED NOT NULL,
  `duty_date` DATE NOT NULL,
  `shift` VARCHAR(20) NOT NULL,
  `location` VARCHAR(120) NULL,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sec_duty_date` (`duty_date`),
  KEY `idx_sec_duty_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `security_incidents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_code` VARCHAR(40) NOT NULL,
  `incident_at` DATETIME NULL,
  `category` VARCHAR(80) NULL,
  `description` TEXT NULL,
  `staff_id` INT UNSIGNED NULL,
  `location` VARCHAR(120) NULL,
  `document_path` VARCHAR(255) NULL,
  `investigation_status` VARCHAR(40) NOT NULL DEFAULT 'Open',
  `disciplinary_action` TEXT NULL,
  `remarks` TEXT NULL,
  `resolved_on` DATE NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sec_incident_code` (`incident_code`),
  KEY `idx_sec_incident_status` (`investigation_status`),
  KEY `idx_sec_incident_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `security_gate_passes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pass_code` VARCHAR(40) NOT NULL,
  `pass_type` VARCHAR(20) NOT NULL,
  `person_details` VARCHAR(255) NULL,
  `item_details` VARCHAR(255) NULL,
  `purpose` VARCHAR(255) NULL,
  `issued_on` DATE NULL,
  `expires_on` DATE NULL,
  `authorized_officer` VARCHAR(120) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'Issued',
  `returned_on` DATE NULL,
  `verified_by` VARCHAR(120) NULL,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sec_pass_code` (`pass_code`),
  KEY `idx_sec_pass_status` (`status`),
  KEY `idx_sec_pass_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `security_irb_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_no` VARCHAR(80) NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `submitted_on` DATE NULL,
  `document_path` VARCHAR(255) NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'Submitted',
  `remarks` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sec_irb_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `security_item_issues` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_name` VARCHAR(150) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1,
  `staff_id` INT UNSIGNED NULL,
  `issued_on` DATE NULL,
  `returned_on` DATE NULL,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sec_items_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_code` VARCHAR(40) NOT NULL,
  `identification_no` VARCHAR(80) NULL,
  `brand` VARCHAR(80) NULL,
  `model` VARCHAR(80) NULL,
  `capacity_btu` INT UNSIGNED NULL,
  `building` VARCHAR(120) NULL,
  `room` VARCHAR(120) NULL,
  `installed_on` DATE NULL,
  `warranty_until` DATE NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'Working',
  `provider_id` INT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ac_unit_code` (`unit_code`),
  KEY `idx_ac_units_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_maintenance_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_id` INT UNSIGNED NOT NULL,
  `service_date` DATE NULL,
  `maintenance_type` VARCHAR(40) NOT NULL DEFAULT 'Preventive',
  `work_performed` TEXT NULL,
  `technician` VARCHAR(120) NULL,
  `parts_replaced` TEXT NULL,
  `cost` DECIMAL(12,2) NULL,
  `remarks` TEXT NULL,
  `next_date` DATE NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ac_maint_unit` (`unit_id`),
  KEY `idx_ac_maint_next` (`next_date`),
  KEY `idx_ac_maint_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ac_breakdown_complaints` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `complaint_code` VARCHAR(40) NOT NULL,
  `reported_on` DATE NULL,
  `unit_id` INT UNSIGNED NULL,
  `location` VARCHAR(120) NULL,
  `description` TEXT NULL,
  `priority` VARCHAR(20) NOT NULL DEFAULT 'Medium',
  `technician` VARCHAR(120) NULL,
  `progress` VARCHAR(40) NOT NULL DEFAULT 'Open',
  `completed_on` DATE NULL,
  `remarks` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ac_complaint_code` (`complaint_code`),
  KEY `idx_ac_complaint_progress` (`progress`),
  KEY `idx_ac_complaint_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cleaning_attendance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `shift` VARCHAR(20) NULL,
  `area` VARCHAR(120) NULL,
  `supervisor` VARCHAR(120) NULL,
  `status` VARCHAR(20) NOT NULL,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cln_att_date` (`attendance_date`),
  KEY `idx_cln_att_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cleaning_duty_allocations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT UNSIGNED NOT NULL,
  `duty_date` DATE NOT NULL,
  `building` VARCHAR(120) NULL,
  `area` VARCHAR(120) NULL,
  `shift` VARCHAR(20) NULL,
  `supervisor` VARCHAR(120) NULL,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cln_duty_date` (`duty_date`),
  KEY `idx_cln_duty_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cleaning_inventory` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_code` VARCHAR(40) NOT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) NULL,
  `quantity_on_hand` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `min_level` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `location` VARCHAR(120) NULL,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cln_item_code` (`item_code`),
  KEY `idx_cln_inv_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cleaning_item_issues` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inventory_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NULL,
  `location` VARCHAR(120) NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `issued_on` DATE NULL,
  `returned_on` DATE NULL,
  `condition_note` VARCHAR(40) NULL,
  `applied_qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `restored` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cln_issue_item` (`inventory_id`),
  KEY `idx_cln_issue_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cleaning_material_purchases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) NULL,
  `supplier` VARCHAR(150) NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `purchased_on` DATE NULL,
  `inventory_id` INT UNSIGNED NULL,
  `applied_qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `remarks` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cln_purchase_date` (`purchased_on`),
  KEY `idx_cln_purchase_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cleaning_inspections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inspected_on` DATE NULL,
  `location` VARCHAR(120) NULL,
  `officer` VARCHAR(120) NULL,
  `rating` TINYINT UNSIGNED NULL,
  `checklist` TEXT NULL,
  `observations` TEXT NULL,
  `complaints` TEXT NULL,
  `corrective_action` TEXT NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cln_inspect_date` (`inspected_on`),
  KEY `idx_cln_inspect_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canteen_inspections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inspected_on` DATE NULL,
  `officer` VARCHAR(120) NULL,
  `hygiene_checklist` TEXT NULL,
  `kitchen_cleanliness` VARCHAR(40) NULL,
  `storage_conditions` VARCHAR(40) NULL,
  `handling_practices` VARCHAR(40) NULL,
  `staff_hygiene` VARCHAR(40) NULL,
  `water_sanitation` VARCHAR(40) NULL,
  `score` TINYINT UNSIGNED NULL,
  `observations` TEXT NULL,
  `corrective_action` TEXT NULL,
  `follow_up_on` DATE NULL,
  `document_path` VARCHAR(255) NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_can_inspect_date` (`inspected_on`),
  KEY `idx_can_inspect_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canteen_complaints` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `complaint_code` VARCHAR(40) NOT NULL,
  `complained_on` DATE NULL,
  `category` VARCHAR(80) NULL,
  `description` TEXT NULL,
  `is_anonymous` VARCHAR(10) NOT NULL DEFAULT 'No',
  `complainant_name` VARCHAR(120) NULL,
  `evidence_path` VARCHAR(255) NULL,
  `investigation` TEXT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'New',
  `satisfaction` TINYINT UNSIGNED NULL,
  `created_by` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_can_complaint_code` (`complaint_code`),
  KEY `idx_can_complaint_status` (`status`),
  KEY `idx_can_complaint_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `service_categories` (`slug`, `name`, `sort_order`)
SELECT 'security', 'Security', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `service_categories` WHERE `slug` = 'security');

INSERT INTO `service_categories` (`slug`, `name`, `sort_order`)
SELECT 'ac', 'Air Conditioning', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `service_categories` WHERE `slug` = 'ac');

INSERT INTO `service_categories` (`slug`, `name`, `sort_order`)
SELECT 'cleaning', 'Cleaning', 3 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `service_categories` WHERE `slug` = 'cleaning');

INSERT INTO `service_categories` (`slug`, `name`, `sort_order`)
SELECT 'canteen', 'Canteen', 4 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `service_categories` WHERE `slug` = 'canteen');
