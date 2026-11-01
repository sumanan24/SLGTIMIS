CREATE TABLE IF NOT EXISTS `student_status_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_audit_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `action` varchar(40) NOT NULL,
  `field_name` varchar(60) DEFAULT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `changed_by` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_module_progress` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `theory_marks` decimal(6,2) DEFAULT NULL,
  `practical_marks` decimal(6,2) DEFAULT NULL,
  `continuous_marks` decimal(6,2) DEFAULT NULL,
  `final_marks` decimal(6,2) DEFAULT NULL,
  `competency_result` varchar(40) DEFAULT NULL,
  `repeat_note` varchar(255) DEFAULT NULL,
  `reassessment_note` varchar(255) DEFAULT NULL,
  `attendance_percent` decimal(5,2) DEFAULT NULL,
  `completion_status` varchar(30) DEFAULT NULL,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_progress_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_certificates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `certificate_no` varchar(40) NOT NULL,
  `printed_name` varchar(255) NOT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `trade` varchar(120) DEFAULT NULL,
  `nvq_level` varchar(10) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `certificate_type` varchar(80) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `certification_date` date DEFAULT NULL,
  `issuance_date` date DEFAULT NULL,
  `awarding_authority` varchar(120) DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Pending',
  `collection_status` varchar(40) DEFAULT NULL,
  `collection_date` date DEFAULT NULL,
  `pending_reason` varchar(255) DEFAULT NULL,
  `transcript_note` varchar(255) DEFAULT NULL,
  `tvec_note` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificate_no` (`certificate_no`),
  KEY `idx_certificate_student` (`student_id`),
  KEY `idx_certificate_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `certificate_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `certificate_id` int unsigned NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `event_type` varchar(20) NOT NULL,
  `status` varchar(40) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cert_event` (`certificate_id`),
  KEY `idx_cert_event_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ojt_industries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `sector` varchar(80) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `supervisor` varchar(120) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_industry_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_ojt` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `industry_id` int unsigned DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'Not Allocated',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `recommendation_note` varchar(255) DEFAULT NULL,
  `confirmation_note` varchar(255) DEFAULT NULL,
  `attendance_percent` decimal(5,2) DEFAULT NULL,
  `completion_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `pending_note` varchar(255) DEFAULT NULL,
  `follow_up` varchar(255) DEFAULT NULL,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ojt_student` (`student_id`),
  KEY `idx_ojt_industry` (`industry_id`),
  KEY `idx_ojt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ojt_monitoring` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ojt_id` int unsigned NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `visit_date` date DEFAULT NULL,
  `officer_name` varchar(120) DEFAULT NULL,
  `report` varchar(500) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_monitor_ojt` (`ojt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ojt_evaluations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ojt_id` int unsigned NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `evaluator` varchar(120) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `comments` varchar(500) DEFAULT NULL,
  `evaluated_on` date DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eval_ojt` (`ojt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `graduate_employment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `status_before` varchar(40) DEFAULT NULL,
  `status_during` varchar(40) DEFAULT NULL,
  `status_after` varchar(40) DEFAULT NULL,
  `organisation` varchar(160) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `sector` varchar(80) DEFAULT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `job_role` varchar(120) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `employment_type` varchar(40) DEFAULT NULL,
  `location_scope` varchar(20) DEFAULT NULL,
  `salary_range` varchar(40) DEFAULT NULL,
  `course_related` varchar(10) DEFAULT NULL,
  `days_to_first_job` int DEFAULT NULL,
  `self_employed_detail` varchar(255) DEFAULT NULL,
  `business_detail` varchar(255) DEFAULT NULL,
  `placement_assistance` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `current_status` varchar(40) DEFAULT NULL,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employment_student` (`student_id`),
  KEY `idx_employment_type` (`employment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `graduate_higher_education` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `institution` varchar(160) NOT NULL,
  `programme` varchar(160) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `qualification` varchar(160) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_higher_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employer_feedback` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `employment_id` int unsigned DEFAULT NULL,
  `employer_name` varchar(160) DEFAULT NULL,
  `rating` tinyint DEFAULT NULL,
  `feedback` varchar(500) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `alumni_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `alumni_code` varchar(40) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `membership` varchar(20) NOT NULL DEFAULT 'Pending',
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(120) DEFAULT NULL,
  `contact_address` varchar(255) DEFAULT NULL,
  `awards` varchar(255) DEFAULT NULL,
  `professional_qualifications` varchar(255) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alumni_code` (`alumni_code`),
  UNIQUE KEY `uq_alumni_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `alumni_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(160) NOT NULL,
  `event_date` date DEFAULT NULL,
  `location` varchar(160) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `alumni_engagement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `alumni_id` int unsigned NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `event_id` int unsigned DEFAULT NULL,
  `kind` varchar(40) NOT NULL,
  `title` varchar(160) NOT NULL,
  `detail` varchar(500) DEFAULT NULL,
  `engaged_on` date DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_engage_alumni` (`alumni_id`),
  KEY `idx_engage_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_clearance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `section` varchar(40) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `note` varchar(255) DEFAULT NULL,
  `cleared_by` varchar(50) DEFAULT NULL,
  `cleared_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clearance_section` (`student_id`, `section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_documents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `category` varchar(80) NOT NULL,
  `title` varchar(160) NOT NULL,
  `reference_no` varchar(60) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(160) DEFAULT NULL,
  `version_no` int unsigned NOT NULL DEFAULT 1,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_document_student` (`student_id`),
  KEY `idx_document_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `graduate_tracer_surveys` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `survey_year` int NOT NULL,
  `employed` varchar(10) DEFAULT NULL,
  `course_related` varchar(10) DEFAULT NULL,
  `location_scope` varchar(20) DEFAULT NULL,
  `higher_study` varchar(10) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `record_origin` varchar(20) NOT NULL DEFAULT 'system',
  `created_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tracer_student` (`student_id`),
  KEY `idx_tracer_year` (`survey_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `historical_imports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `file_name` varchar(160) NOT NULL,
  `imported_by` varchar(50) DEFAULT NULL,
  `total_rows` int unsigned NOT NULL DEFAULT 0,
  `success_rows` int unsigned NOT NULL DEFAULT 0,
  `error_rows` int unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'preview',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_discipline` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `incident_date` date DEFAULT NULL,
  `category` varchar(80) NOT NULL,
  `description` varchar(500) NOT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Open',
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(50) DEFAULT NULL,
  `delete_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_discipline_student` (`student_id`),
  KEY `idx_discipline_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_welfare` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `aid_date` date DEFAULT NULL,
  `aid_type` varchar(80) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Requested',
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(50) DEFAULT NULL,
  `delete_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_welfare_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_correspondence` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `reference_no` varchar(40) DEFAULT NULL,
  `letter_date` date DEFAULT NULL,
  `subject` varchar(160) NOT NULL,
  `direction` varchar(20) NOT NULL DEFAULT 'Outgoing',
  `body` varchar(1000) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(50) DEFAULT NULL,
  `delete_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_letter_student` (`student_id`),
  KEY `idx_letter_ref` (`reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lifecycle_approvals` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `module_name` varchar(40) NOT NULL,
  `record_id` int unsigned DEFAULT NULL,
  `subject` varchar(160) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Submitted',
  `submitted_by` varchar(50) DEFAULT NULL,
  `reviewed_by` varchar(50) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(50) DEFAULT NULL,
  `delete_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_approval_student` (`student_id`),
  KEY `idx_approval_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `historical_import_rows` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `import_id` int unsigned NOT NULL,
  `row_no` int unsigned NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `outcome` varchar(20) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_import_row` (`import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
