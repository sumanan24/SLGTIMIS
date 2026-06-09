-- Inventory log table only (safe to run if inventory_log is missing)
-- No foreign keys — avoids type mismatch with existing inventory tables.

CREATE TABLE IF NOT EXISTS `inventory_log` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `item_id` INT NULL,
    `department_id` VARCHAR(6) NULL,
    `action_type` ENUM('IN','OUT','TRANSFER') NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `reference_id` INT NULL,
    `action_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `remarks` TEXT NULL,
    PRIMARY KEY (`log_id`),
    KEY `idx_log_item` (`item_id`),
    KEY `idx_log_dept` (`department_id`),
    KEY `idx_log_date` (`action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
