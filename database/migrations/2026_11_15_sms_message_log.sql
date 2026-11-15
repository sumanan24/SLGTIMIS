-- Local log of outbound SMS and inbound replies (Mobitel gateway sync).

CREATE TABLE IF NOT EXISTS `sms_outbox` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` VARCHAR(40) DEFAULT NULL,
  `mobile` VARCHAR(20) NOT NULL,
  `message` TEXT NOT NULL,
  `source` VARCHAR(40) NOT NULL DEFAULT 'unknown',
  `sent_by` VARCHAR(64) DEFAULT NULL,
  `result_code` VARCHAR(20) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'unknown',
  `gateway_message_id` VARCHAR(64) DEFAULT NULL,
  `time_sent` DATETIME DEFAULT NULL,
  `time_delivered` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_outbox_created` (`created_at`),
  KEY `idx_sms_outbox_mobile` (`mobile`),
  KEY `idx_sms_outbox_gateway` (`gateway_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sms_inbox` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gateway_message_id` VARCHAR(64) DEFAULT NULL,
  `mobile` VARCHAR(20) NOT NULL,
  `message` TEXT NOT NULL,
  `received_at` DATETIME DEFAULT NULL,
  `channel` VARCHAR(40) DEFAULT NULL COMMENT 'long_number or shortcode',
  `raw_json` MEDIUMTEXT DEFAULT NULL,
  `fetched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sms_inbox_dedupe` (`gateway_message_id`, `mobile`(20), `received_at`),
  KEY `idx_sms_inbox_fetched` (`fetched_at`),
  KEY `idx_sms_inbox_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
