-- =========================================
-- INVENTORY MANAGEMENT (SLGTI)
-- department_id is VARCHAR(6) to match `department` table
-- =========================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `inventory` (
    `item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(255) NOT NULL,
    `item_code` VARCHAR(100) NULL,
    `department_id` VARCHAR(6) NULL,
    `category` VARCHAR(100) NULL,
    `unit` VARCHAR(50) NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `reorder_level` INT NOT NULL DEFAULT 5,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_inventory_item_code_dept` (`item_code`, `department_id`),
    KEY `idx_inventory_department` (`department_id`),
    CONSTRAINT `fk_inventory_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_in` (
    `stock_in_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `department_id` VARCHAR(6) NULL,
    `quantity` INT NOT NULL,
    `purchase_price` DECIMAL(10,2) NULL,
    `supplier` VARCHAR(255) NULL,
    `date_in` DATE NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_stock_in_item` (`item_id`),
    KEY `idx_stock_in_dept` (`department_id`),
    CONSTRAINT `fk_stock_in_item` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stock_in_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_out` (
    `stock_out_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `department_id` VARCHAR(6) NULL,
    `quantity` INT NOT NULL,
    `issued_to` VARCHAR(255) NULL,
    `issued_type` ENUM('student','staff') NOT NULL DEFAULT 'staff',
    `reason` VARCHAR(255) NULL,
    `date_out` DATE NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_stock_out_item` (`item_id`),
    KEY `idx_stock_out_dept` (`department_id`),
    CONSTRAINT `fk_stock_out_item` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_stock_out_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transfer` (
    `transfer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `from_department` VARCHAR(6) NULL,
    `to_department` VARCHAR(6) NULL,
    `quantity` INT NOT NULL,
    `transfer_date` DATE NULL,
    `approved_by` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_transfer_item` (`item_id`),
    CONSTRAINT `fk_transfer_item` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_transfer_from` FOREIGN KEY (`from_department`) REFERENCES `department` (`department_id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_transfer_to` FOREIGN KEY (`to_department`) REFERENCES `department` (`department_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `inventory_log` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NULL,
    `department_id` VARCHAR(6) NULL,
    `action_type` ENUM('IN','OUT','TRANSFER') NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `reference_id` INT NULL,
    `action_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `remarks` TEXT NULL,
    KEY `idx_log_item` (`item_id`),
    KEY `idx_log_dept` (`department_id`),
    KEY `idx_log_date` (`action_date`),
    CONSTRAINT `fk_log_item` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_log_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
