-- Student online applications (Level 04 / Level 05)
-- Run once on the target database. NIC + level and email + level are unique per application stream.

CREATE TABLE IF NOT EXISTS `student_applications` (
    `application_id` INT AUTO_INCREMENT PRIMARY KEY,
    `application_level` ENUM('04','05') NOT NULL COMMENT 'NVQ Level applied for',

    /* Personal Information */
    `student_title`            VARCHAR(10) DEFAULT NULL,
    `student_full_name`        VARCHAR(150) NOT NULL,
    `student_initial_name`     VARCHAR(100) DEFAULT NULL,
    `student_gender`           ENUM('Male','Female','Other') DEFAULT NULL,
    `student_civil_status`     ENUM('Single','Married') DEFAULT NULL,
    `student_email`            VARCHAR(100) DEFAULT NULL,
    `student_phone`            VARCHAR(20) DEFAULT NULL,
    `student_whatsapp`         VARCHAR(20) DEFAULT NULL,
    `student_nic`              VARCHAR(20) NOT NULL,
    `student_dob`              DATE DEFAULT NULL,
    `student_language`         VARCHAR(50) DEFAULT NULL,
    `student_religion`         VARCHAR(50) DEFAULT NULL,
    `student_blood_group`      VARCHAR(10) DEFAULT NULL,

    /* Address */
    `student_address`          TEXT,
    `student_zip_code`         VARCHAR(10) DEFAULT NULL,
    `student_district`         VARCHAR(100) DEFAULT NULL,
    `student_province`         VARCHAR(100) DEFAULT NULL,

    /* Course preferences */
    `course_priority_1`        VARCHAR(150) DEFAULT NULL,
    `course_priority_2`        VARCHAR(150) DEFAULT NULL,
    `course_priority_3`        VARCHAR(150) DEFAULT NULL,

    /* O/L */
    `ol_index_number`          VARCHAR(20) DEFAULT NULL,
    `ol_exam_year`             YEAR DEFAULT NULL,

    `ol_subject_name_01`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_01_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_02`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_02_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_03`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_03_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_04`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_04_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_05`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_05_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_06`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_06_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_07`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_07_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_08`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_08_marks`      VARCHAR(10) DEFAULT NULL,
    `ol_subject_name_09`       VARCHAR(100) DEFAULT NULL,
    `ol_subject_09_marks`      VARCHAR(10) DEFAULT NULL,

    /* A/L */
    `al_index_number`          VARCHAR(20) DEFAULT NULL,
    `al_exam_year`             YEAR DEFAULT NULL,
    `al_stream`                VARCHAR(100) DEFAULT NULL,

    `al_subject_name_01`       VARCHAR(100) DEFAULT NULL,
    `al_subject_01_marks`      VARCHAR(10) DEFAULT NULL,
    `al_subject_name_02`       VARCHAR(100) DEFAULT NULL,
    `al_subject_02_marks`      VARCHAR(10) DEFAULT NULL,
    `al_subject_name_03`       VARCHAR(100) DEFAULT NULL,
    `al_subject_03_marks`      VARCHAR(10) DEFAULT NULL,

    /* NVQ */
    `nvq_level`                VARCHAR(20) DEFAULT NULL,
    `nvq_course_name`          VARCHAR(150) DEFAULT NULL,
    `nvq_institute_name`       VARCHAR(150) DEFAULT NULL,
    `nvq_year_completed`       YEAR DEFAULT NULL,

    /* Documents (relative paths from web root) */
    `nic_document_path`        VARCHAR(255) DEFAULT NULL,
    `birth_certificate_path`   VARCHAR(255) DEFAULT NULL,
    `ol_certificate_path`      VARCHAR(255) DEFAULT NULL,
    `al_certificate_path`      VARCHAR(255) DEFAULT NULL,
    `nvq_certificate_path`     VARCHAR(255) DEFAULT NULL,
    `bank_receipt_path`        VARCHAR(255) DEFAULT NULL,

    `created_at`               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `uq_nic_level` (`student_nic`, `application_level`),
    UNIQUE KEY `uq_email_level` (`student_email`, `application_level`),
    KEY `idx_level_created` (`application_level`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
