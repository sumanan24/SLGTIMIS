-- Password reset OTPs for SLGTI MIS.
-- Stores a hash only. History is kept so resend limits can be enforced.
-- Run against the SIS database if the table does not already exist.
-- The application also creates these tables on first use.

CREATE TABLE IF NOT EXISTS `password_reset_otps` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `otp_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `verified_at` DATETIME NULL DEFAULT NULL,
    `invalidated_at` DATETIME NULL DEFAULT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `ip_address` VARCHAR(45) NOT NULL,
    `campaign_key` VARCHAR(80) NULL DEFAULT NULL,
    `sms_result` VARCHAR(40) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pro_user_created` (`user_id`, `created_at`),
    KEY `idx_pro_ip_created` (`ip_address`, `created_at`),
    KEY `idx_pro_expires` (`expires_at`),
    CONSTRAINT `fk_pro_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_rate_events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `event_type` VARCHAR(20) NOT NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_prr_ip` (`ip_address`, `event_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
