<?php
/**
 * Database objects for Academic Scheduling, Attendance and Instructor Diary integration.
 *
 * Idempotent: only CREATE TABLE IF NOT EXISTS / ADD COLUMN when missing. Existing tables and the
 * original fingerprint data (`student_attendance`) are never altered or deleted. A version flag in
 * `system_setting` keeps this to one cheap query per request once the schema is current.
 */
class SchedulingSchema {
    const VERSION = '2026-11-09.2';
    const SETTING_KEY = 'sched_schema_version';

    private static $done = false;

    public static function ensure() {
        if (self::$done) {
            return;
        }
        self::$done = true;
        $db = Database::getInstance();
        try {
            $db->query("CREATE TABLE IF NOT EXISTS `system_setting` (
                `setting_key` VARCHAR(64) NOT NULL,
                `setting_value` TEXT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $res = $db->query("SELECT `setting_value` FROM `system_setting` WHERE `setting_key` = '" . self::SETTING_KEY . "' LIMIT 1");
            $row = $res ? $res->fetch_assoc() : null;
            if ($row && $row['setting_value'] === self::VERSION) {
                return;
            }
            self::build($db);
            $stmt = $db->prepare("INSERT INTO `system_setting` (`setting_key`, `setting_value`) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
            if ($stmt) {
                $key = self::SETTING_KEY;
                $ver = self::VERSION;
                $stmt->bind_param('ss', $key, $ver);
                $stmt->execute();
            }
        } catch (Throwable $e) {
            error_log('SchedulingSchema::ensure ' . $e->getMessage());
        }
    }

    private static function hasColumn($db, $table, $column) {
        $r = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $db->escape($column) . "'");
        return $r && $r->num_rows > 0;
    }

    private static function hasTable($db, $table) {
        $r = $db->query("SHOW TABLES LIKE '" . $db->escape($table) . "'");
        return $r && $r->num_rows > 0;
    }

    private static function addColumns($db, $table, array $columns) {
        if (!self::hasTable($db, $table)) {
            return;
        }
        foreach ($columns as $name => $ddl) {
            if (!self::hasColumn($db, $table, $name)) {
                $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$ddl}");
            }
        }
    }

    private static function addIndex($db, $table, $name, $columns) {
        if (!self::hasTable($db, $table)) {
            return;
        }
        $r = $db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '" . $db->escape($name) . "'");
        if (!$r || $r->num_rows === 0) {
            $db->query("ALTER TABLE `{$table}` ADD KEY `{$name}` ({$columns})");
        }
    }

    private static function build($db) {
        $tail = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        // Make sure the base tables that are extended below exist (they self-migrate elsewhere).
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        require_once BASE_PATH . '/models/InstructorDiaryModel.php';
        try {
            (new GroupTimetableModel())->ensureTable();
            (new InstructorDiaryModel())->ensureTableStructure();
        } catch (Throwable $e) {
            error_log('SchedulingSchema base tables: ' . $e->getMessage());
        }

        // ---- Phase 2: student groups (extends `groups` / `group_students`) ----
        self::addColumns($db, 'groups', [
            'group_code' => 'VARCHAR(30) NULL DEFAULT NULL',
            'batch' => 'VARCHAR(30) NULL DEFAULT NULL',
            'instructor_staff_id' => 'VARCHAR(50) NULL DEFAULT NULL',
        ]);
        $db->query("CREATE TABLE IF NOT EXISTS `group_student_history` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` VARCHAR(50) NOT NULL,
            `group_id` INT NOT NULL,
            `action` VARCHAR(20) NOT NULL COMMENT 'joined, left, transfer_in, transfer_out',
            `from_group_id` INT NULL DEFAULT NULL,
            `to_group_id` INT NULL DEFAULT NULL,
            `performed_by` VARCHAR(100) NOT NULL DEFAULT '',
            `remarks` VARCHAR(255) NOT NULL DEFAULT '',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_gsh_student` (`student_id`, `created_at`),
            KEY `idx_gsh_group` (`group_id`, `created_at`)
        ) {$tail}");

        // ---- Phase 3: group timetable (extends `group_timetable`) ----
        self::addColumns($db, 'group_timetable', [
            'start_time' => 'TIME NULL DEFAULT NULL',
            'end_time' => 'TIME NULL DEFAULT NULL',
            'session_type' => "VARCHAR(20) NOT NULL DEFAULT 'Theory'",
            'venue_type' => "VARCHAR(20) NULL DEFAULT NULL",
            'publish_status' => "VARCHAR(12) NOT NULL DEFAULT 'published'",
            'published_by' => 'VARCHAR(100) NULL DEFAULT NULL',
            'published_at' => 'DATETIME NULL DEFAULT NULL',
        ]);
        self::addIndex($db, 'group_timetable', 'idx_gt_day', '`weekday`, `active`, `publish_status`');

        // ---- Phase 4: training programmes ----
        $db->query("CREATE TABLE IF NOT EXISTS `training_programmes` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(200) NOT NULL,
            `description` TEXT NULL,
            `department_id` VARCHAR(16) NULL DEFAULT NULL,
            `course_id` VARCHAR(50) NULL DEFAULT NULL,
            `batch` VARCHAR(30) NULL DEFAULT NULL,
            `academic_year` VARCHAR(20) NULL DEFAULT NULL,
            `training_type` VARCHAR(20) NOT NULL DEFAULT 'Practical',
            `instructor_staff_id` VARCHAR(50) NULL DEFAULT NULL,
            `learning_outcomes` TEXT NULL,
            `linked_timetable_id` INT NULL DEFAULT NULL COMMENT 'Set only when explicitly linked to the regular timetable',
            `status` VARCHAR(12) NOT NULL DEFAULT 'draft' COMMENT 'draft, published, cancelled',
            `cancel_reason` VARCHAR(255) NULL DEFAULT NULL,
            `created_by` VARCHAR(100) NOT NULL DEFAULT '',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `published_at` DATETIME NULL DEFAULT NULL,
            `cancelled_at` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_tp_dept_status` (`department_id`, `status`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `programme_schedules` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `programme_id` INT UNSIGNED NOT NULL,
            `session_date` DATE NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `location` VARCHAR(120) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_ps_programme_slot` (`programme_id`, `session_date`, `start_time`),
            KEY `idx_ps_date` (`session_date`),
            CONSTRAINT `fk_ps_programme` FOREIGN KEY (`programme_id`) REFERENCES `training_programmes` (`id`) ON DELETE CASCADE
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `programme_participants` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `programme_id` INT UNSIGNED NOT NULL,
            `student_id` VARCHAR(50) NOT NULL,
            `group_id` INT NULL DEFAULT NULL,
            `source` VARCHAR(12) NOT NULL DEFAULT 'individual' COMMENT 'group or individual',
            `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_pp_programme_student` (`programme_id`, `student_id`),
            KEY `idx_pp_student` (`student_id`),
            CONSTRAINT `fk_pp_programme` FOREIGN KEY (`programme_id`) REFERENCES `training_programmes` (`id`) ON DELETE CASCADE
        ) {$tail}");

        // ---- Phases 5-7: calculated attendance, class attendance, corrections, audit ----
        $db->query("CREATE TABLE IF NOT EXISTS `student_daily_attendance` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` VARCHAR(50) NOT NULL,
            `attendance_date` DATE NOT NULL,
            `group_id` INT NULL DEFAULT NULL,
            `first_in` TIME NULL DEFAULT NULL,
            `last_out` TIME NULL DEFAULT NULL,
            `punch_count` SMALLINT NOT NULL DEFAULT 0,
            `duplicate_count` SMALLINT NOT NULL DEFAULT 0,
            `morning_present` TINYINT(1) NOT NULL DEFAULT 0,
            `evening_present` TINYINT(1) NOT NULL DEFAULT 0,
            `calc_status` VARCHAR(10) NOT NULL DEFAULT 'absent' COMMENT 'full, half, absent (from biometric)',
            `calc_value` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
            `calc_reason` VARCHAR(400) NOT NULL DEFAULT '',
            `final_status` VARCHAR(10) NOT NULL DEFAULT 'absent' COMMENT 'effective status after approved corrections',
            `final_value` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
            `source` VARCHAR(12) NOT NULL DEFAULT 'biometric' COMMENT 'biometric or manual',
            `verification_status` VARCHAR(10) NOT NULL DEFAULT 'ok' COMMENT 'ok, pending, verified',
            `manually_updated` TINYINT(1) NOT NULL DEFAULT 0,
            `calculated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_sda_student_date` (`student_id`, `attendance_date`),
            KEY `idx_sda_date_group` (`attendance_date`, `group_id`),
            KEY `idx_sda_verification` (`verification_status`, `attendance_date`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `class_attendance` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `timetable_id` INT NOT NULL,
            `session_date` DATE NOT NULL,
            `group_id` INT NOT NULL,
            `student_id` VARCHAR(50) NOT NULL,
            `status` VARCHAR(10) NOT NULL DEFAULT 'absent',
            `value` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
            `source` VARCHAR(12) NOT NULL DEFAULT 'biometric',
            `verification_status` VARCHAR(10) NOT NULL DEFAULT 'ok',
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_ca_slot_student` (`timetable_id`, `session_date`, `student_id`),
            KEY `idx_ca_group_date` (`group_id`, `session_date`),
            KEY `idx_ca_student` (`student_id`, `session_date`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `attendance_corrections` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` VARCHAR(50) NOT NULL,
            `attendance_date` DATE NOT NULL,
            `prev_status` VARCHAR(10) NOT NULL,
            `new_status` VARCHAR(10) NOT NULL,
            `prev_value` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
            `new_value` DECIMAL(3,1) NOT NULL DEFAULT 0.0,
            `reason` VARCHAR(500) NOT NULL,
            `exceptional` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'post month-end request, higher approval required',
            `requested_by` VARCHAR(100) NOT NULL DEFAULT '',
            `requested_by_user` INT NOT NULL DEFAULT 0,
            `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `approval_status` VARCHAR(10) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
            `approved_by` VARCHAR(100) NULL DEFAULT NULL,
            `approved_at` DATETIME NULL DEFAULT NULL,
            `approval_remarks` VARCHAR(255) NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_ac_status` (`approval_status`, `requested_at`),
            KEY `idx_ac_student_date` (`student_id`, `attendance_date`)
        ) {$tail}");
        $db->query("CREATE TABLE IF NOT EXISTS `attendance_audit_logs` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` VARCHAR(50) NOT NULL,
            `attendance_date` DATE NOT NULL,
            `action` VARCHAR(24) NOT NULL COMMENT 'correction, verification, approval, rejection, exceptional',
            `correction_id` INT UNSIGNED NULL DEFAULT NULL,
            `prev_status` VARCHAR(10) NULL DEFAULT NULL,
            `new_status` VARCHAR(10) NULL DEFAULT NULL,
            `prev_value` DECIMAL(3,1) NULL DEFAULT NULL,
            `new_value` DECIMAL(3,1) NULL DEFAULT NULL,
            `reason` VARCHAR(500) NOT NULL DEFAULT '',
            `performed_by` VARCHAR(100) NOT NULL DEFAULT '',
            `performed_by_user` INT NOT NULL DEFAULT 0,
            `performed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `approval_info` VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            KEY `idx_aal_student_date` (`student_id`, `attendance_date`),
            KEY `idx_aal_performed` (`performed_at`)
        ) {$tail}");

        // ---- Phase 8: instructor diary linked to timetable sessions ----
        self::addColumns($db, 'instructor_diary', [
            'timetable_id' => 'INT NULL DEFAULT NULL',
            'session_date' => 'DATE NULL DEFAULT NULL',
            'group_id' => 'INT NULL DEFAULT NULL',
            'session_type' => "VARCHAR(20) NULL DEFAULT NULL",
            'scheduled_hours' => 'DECIMAL(4,2) NULL DEFAULT NULL',
            'actual_hours' => 'DECIMAL(4,2) NULL DEFAULT NULL',
            'learning_outcomes' => 'TEXT NULL',
            'teaching_methods' => 'TEXT NULL',
            'activities_completed' => 'TEXT NULL',
            'diary_status' => "VARCHAR(24) NOT NULL DEFAULT 'Submitted'",
            'not_conducted_reason' => 'VARCHAR(255) NULL DEFAULT NULL',
            'started_at' => 'DATETIME NULL DEFAULT NULL',
            'submitted_at' => 'DATETIME NULL DEFAULT NULL',
        ]);
        self::addIndex($db, 'instructor_diary', 'idx_diary_slot', '`timetable_id`, `session_date`');
        // The legacy column is NOT NULL; scheduled sessions have no topic yet.
        if (self::hasTable($db, 'instructor_diary')) {
            $c = $db->query("SHOW COLUMNS FROM `instructor_diary` LIKE 'topic_covered'");
            $col = $c ? $c->fetch_assoc() : null;
            if ($col && strtoupper((string) $col['Null']) === 'NO' && $col['Default'] === null) {
                $db->query("ALTER TABLE `instructor_diary` MODIFY `topic_covered` VARCHAR(255) NOT NULL DEFAULT ''");
            }
        }
        $db->query("CREATE TABLE IF NOT EXISTS `instructor_diary_reviews` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `instructor_diary_id` INT NOT NULL,
            `reviewer_staff_id` VARCHAR(50) NOT NULL DEFAULT '',
            `status` VARCHAR(20) NOT NULL DEFAULT 'Reviewed',
            `remarks` VARCHAR(500) NOT NULL DEFAULT '',
            `reviewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_idr_diary` (`instructor_diary_id`)
        ) {$tail}");
    }
}
