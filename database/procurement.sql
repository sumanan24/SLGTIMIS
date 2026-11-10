-- Procurement Management module
-- Workflow: Request -> Director -> Accountant (budget) -> Purchase Officer -> Procurement Officer (verify + close)
-- Tables are created automatically by ProcurementModel::ensureSchema(). Every statement is idempotent.

-- Roles that do not exist yet (existing roles are never changed)
INSERT IGNORE INTO `staff_position_type` (`staff_position_type_id`, `staff_position_type_name`, `staff_position`) VALUES
('PRO', 'Procurement Officer', 6),
('PUR', 'Purchase Officer', 7);

-- status: draft, pending_director, returned, rejected, pending_accountant, pending_purchase,
--         in_procurement, pending_verification, verified, closed, cancelled
CREATE TABLE IF NOT EXISTS `proc_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_no` VARCHAR(30) NOT NULL,
  `request_year` SMALLINT UNSIGNED NOT NULL,
  `request_seq` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(20) NOT NULL DEFAULT 'goods',
  `priority` VARCHAR(10) NOT NULL DEFAULT 'medium',
  `department_id` VARCHAR(16) DEFAULT NULL,
  `justification` TEXT,
  `required_date` DATE DEFAULT NULL,
  `estimated_total` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `revision` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `requested_by` VARCHAR(64) NOT NULL,
  `submitted_at` DATETIME DEFAULT NULL,
  `director_decision` VARCHAR(10) DEFAULT NULL,
  `director_by` VARCHAR(64) DEFAULT NULL,
  `director_at` DATETIME DEFAULT NULL,
  `director_remarks` TEXT,
  `accountant_decision` VARCHAR(10) DEFAULT NULL,
  `accountant_by` VARCHAR(64) DEFAULT NULL,
  `accountant_at` DATETIME DEFAULT NULL,
  `accountant_remarks` TEXT,
  `budget_code` VARCHAR(60) DEFAULT NULL,
  `allocated_amount` DECIMAL(14,2) DEFAULT NULL,
  `purchase_started_by` VARCHAR(64) DEFAULT NULL,
  `purchase_started_at` DATETIME DEFAULT NULL,
  `supplier_name` VARCHAR(160) DEFAULT NULL,
  `supplier_contact` VARCHAR(160) DEFAULT NULL,
  `po_number` VARCHAR(60) DEFAULT NULL,
  `invoice_number` VARCHAR(60) DEFAULT NULL,
  `invoice_date` DATE DEFAULT NULL,
  `purchase_date` DATE DEFAULT NULL,
  `delivery_date` DATE DEFAULT NULL,
  `actual_total` DECIMAL(14,2) DEFAULT NULL,
  `purchase_remarks` TEXT,
  `purchase_by` VARCHAR(64) DEFAULT NULL,
  `purchase_completed_at` DATETIME DEFAULT NULL,
  `verified_by` VARCHAR(64) DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `verification_remarks` TEXT,
  `closed_by` VARCHAR(64) DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `closing_remarks` TEXT,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proc_request_no` (`request_no`),
  UNIQUE KEY `uq_proc_year_seq` (`request_year`, `request_seq`),
  KEY `idx_proc_status` (`status`),
  KEY `idx_proc_requester` (`requested_by`),
  KEY `idx_proc_department` (`department_id`),
  KEY `idx_proc_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proc_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NOT NULL,
  `line_no` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `item_name` VARCHAR(200) NOT NULL,
  `specification` VARCHAR(500) DEFAULT NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1,
  `unit` VARCHAR(20) DEFAULT NULL,
  `unit_price` DECIMAL(14,2) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(14,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_proc_item_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Approval history + audit trail. Rows are only ever added, never changed or removed.
CREATE TABLE IF NOT EXISTS `proc_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(40) NOT NULL,
  `from_status` VARCHAR(30) DEFAULT NULL,
  `to_status` VARCHAR(30) DEFAULT NULL,
  `actor_staff_id` VARCHAR(64) NOT NULL,
  `actor_role` VARCHAR(30) DEFAULT NULL,
  `remarks` TEXT,
  `details` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_proc_hist_request` (`request_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proc_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NOT NULL,
  `doc_type` VARCHAR(20) NOT NULL DEFAULT 'supporting',
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(120) NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` VARCHAR(64) NOT NULL,
  `uploaded_role` VARCHAR(30) DEFAULT NULL,
  `uploaded_at` DATETIME NOT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_by` VARCHAR(64) DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_proc_doc_request` (`request_id`, `is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proc_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED DEFAULT NULL,
  `staff_id` VARCHAR(64) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_proc_notif_staff` (`staff_id`, `is_read`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra role holders. The roles of a person's staff position (DIR, ACC, PUR, PRO ...) work without a row here.
CREATE TABLE IF NOT EXISTS `proc_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` VARCHAR(64) NOT NULL,
  `role` VARCHAR(30) NOT NULL,
  `assigned_by` VARCHAR(64) NOT NULL,
  `assigned_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_proc_role` (`staff_id`, `role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proc_counters` (
  `year` SMALLINT UNSIGNED NOT NULL,
  `last_seq` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
