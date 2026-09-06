<?php
/**
 * Student fingerprint punch events (device sync). Not the class/module `attendance` table.
 */
declare(strict_types=1);

class StudentDeviceAttendanceModel extends Model {
    protected $table = 'student_attendance';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `student_id` VARCHAR(50) NOT NULL DEFAULT '',
            `employee_no` VARCHAR(50) NOT NULL DEFAULT '',
            `person_id` VARCHAR(50) NOT NULL,
            `student_name` VARCHAR(150) NOT NULL DEFAULT '',
            `attendance_date` DATE NOT NULL,
            `attendance_time` TIME NOT NULL,
            `attendance_datetime` DATETIME NOT NULL,
            `machine_id` VARCHAR(64) NOT NULL DEFAULT '',
            `event_id` VARCHAR(128) NOT NULL,
            `source` VARCHAR(32) NOT NULL DEFAULT 'hikvision',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_event_machine` (`event_id`, `machine_id`),
            KEY `idx_person_date` (`person_id`, `attendance_date`),
            KEY `idx_attendance_datetime` (`attendance_datetime`),
            KEY `idx_student_name` (`student_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql);

        $sqlU = "CREATE TABLE IF NOT EXISTS `student_attendance_unmatched` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `person_id` VARCHAR(50) NOT NULL,
            `machine_name` VARCHAR(150) NOT NULL DEFAULT '',
            `attendance_date` DATE NOT NULL,
            `attendance_time` TIME NOT NULL,
            `attendance_datetime` DATETIME NOT NULL,
            `machine_id` VARCHAR(64) NOT NULL DEFAULT '',
            `event_id` VARCHAR(128) NOT NULL,
            `note` VARCHAR(255) NOT NULL DEFAULT '',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_unmatched_event_machine` (`event_id`, `machine_id`),
            KEY `idx_unmatched_person` (`person_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sqlU);

        $sqlM = "CREATE TABLE IF NOT EXISTS `student_attendance_machine_users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `employee_no` VARCHAR(50) NOT NULL,
            `name` VARCHAR(150) NOT NULL DEFAULT '',
            `user_type` VARCHAR(40) NOT NULL DEFAULT 'normal',
            `machine_id` VARCHAR(64) NOT NULL DEFAULT '',
            `finger_count` INT NOT NULL DEFAULT 0,
            `face_count` INT NOT NULL DEFAULT 0,
            `synced_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_machine_emp` (`machine_id`, `employee_no`),
            KEY `idx_emp_name` (`name`),
            KEY `idx_emp_no` (`employee_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sqlM);
        $this->ensureMachineUserExtraColumns();

        $this->ensureFingerIdColumn();
        $this->ensureAttendanceExtraColumns();
        $this->ensureCredentialSyncTables();
    }

    /**
     * Multi-device credential sync queue + attempt logs (additive; does not alter punches).
     */
    public function ensureCredentialSyncTables(): void {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `student_attendance_credential_sync` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `employee_no` VARCHAR(50) NOT NULL,
                `student_id` VARCHAR(50) NOT NULL DEFAULT '',
                `name` VARCHAR(150) NOT NULL DEFAULT '',
                `device_host` VARCHAR(64) NOT NULL,
                `operation` VARCHAR(40) NOT NULL DEFAULT 'credentials',
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `finger_slots` VARCHAR(40) NOT NULL DEFAULT '',
                `include_face` TINYINT(1) NOT NULL DEFAULT 0,
                `attempt_count` INT NOT NULL DEFAULT 0,
                `last_error` VARCHAR(500) NOT NULL DEFAULT '',
                `last_attempt_at` DATETIME NULL DEFAULT NULL,
                `synced_at` DATETIME NULL DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_emp_device_op` (`employee_no`, `device_host`, `operation`),
                KEY `idx_cred_status` (`status`),
                KEY `idx_cred_device` (`device_host`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `student_attendance_credential_sync_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `employee_no` VARCHAR(50) NOT NULL DEFAULT '',
                `device_host` VARCHAR(64) NOT NULL DEFAULT '',
                `operation` VARCHAR(40) NOT NULL DEFAULT '',
                `success` TINYINT(1) NOT NULL DEFAULT 0,
                `message` VARCHAR(500) NOT NULL DEFAULT '',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_cred_log_emp` (`employee_no`),
                KEY `idx_cred_log_device` (`device_host`),
                KEY `idx_cred_log_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Upsert credential sync queue row for one employee → device.
     *
     * @param list<int> $fingerSlots
     */
    public function upsertCredentialSyncJob(
        string $employeeNo,
        string $deviceHost,
        string $name = '',
        string $studentId = '',
        array $fingerSlots = [],
        bool $includeFace = false,
        string $status = 'pending'
    ): void {
        $this->ensureCredentialSyncTables();
        $employeeNo = trim($employeeNo);
        $deviceHost = trim($deviceHost);
        if ($employeeNo === '' || $deviceHost === '') {
            return;
        }
        $slots = [];
        foreach ($fingerSlots as $s) {
            $n = (int) $s;
            if ($n > 0) {
                $slots[] = $n;
            }
        }
        $slots = array_values(array_unique($slots));
        sort($slots);
        $slotStr = implode(',', $slots);
        $includeFaceInt = $includeFace ? 1 : 0;
        $status = in_array($status, ['pending', 'syncing', 'success', 'failed'], true) ? $status : 'pending';

        $stmt = $this->db->prepare(
            "INSERT INTO `student_attendance_credential_sync`
                (`employee_no`, `student_id`, `name`, `device_host`, `operation`, `status`,
                 `finger_slots`, `include_face`, `attempt_count`, `last_error`)
             VALUES (?, ?, ?, ?, 'credentials', ?, ?, ?, 0, '')
             ON DUPLICATE KEY UPDATE
                `student_id` = VALUES(`student_id`),
                `name` = VALUES(`name`),
                `status` = VALUES(`status`),
                `finger_slots` = VALUES(`finger_slots`),
                `include_face` = VALUES(`include_face`),
                `last_error` = IF(VALUES(`status`) = 'pending', '', `last_error`),
                `updated_at` = CURRENT_TIMESTAMP"
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param(
            'ssssssi',
            $employeeNo,
            $studentId,
            $name,
            $deviceHost,
            $status,
            $slotStr,
            $includeFaceInt
        );
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param list<string> $statuses
     * @return list<array<string,mixed>>
     */
    public function listCredentialSyncJobs(array $statuses = [], int $limit = 200): array {
        $this->ensureCredentialSyncTables();
        $limit = max(1, min(1000, $limit));
        $sql = 'SELECT * FROM `student_attendance_credential_sync`';
        $types = '';
        $params = [];
        if ($statuses !== []) {
            $ph = implode(',', array_fill(0, count($statuses), '?'));
            $sql .= " WHERE `status` IN ({$ph})";
            $types = str_repeat('s', count($statuses));
            $params = array_values($statuses);
        }
        $sql .= " ORDER BY FIELD(`status`,'syncing','pending','failed','success'), `updated_at` DESC LIMIT {$limit}";
        if ($types === '') {
            $res = $this->db->query($sql);
        } else {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function updateCredentialSyncJob(
        int $id,
        string $status,
        string $lastError = '',
        bool $bumpAttempt = true,
        bool $setSyncedAt = false
    ): void {
        $this->ensureCredentialSyncTables();
        $status = in_array($status, ['pending', 'syncing', 'success', 'failed'], true) ? $status : 'failed';
        $lastError = mb_substr($lastError, 0, 500);
        if ($bumpAttempt) {
            $sql = "UPDATE `student_attendance_credential_sync`
                    SET `status` = ?, `last_error` = ?, `attempt_count` = `attempt_count` + 1,
                        `last_attempt_at` = NOW()"
                . ($setSyncedAt ? ', `synced_at` = NOW()' : '')
                . ' WHERE `id` = ?';
        } else {
            $sql = "UPDATE `student_attendance_credential_sync`
                    SET `status` = ?, `last_error` = ?, `last_attempt_at` = NOW()"
                . ($setSyncedAt ? ', `synced_at` = NOW()' : '')
                . ' WHERE `id` = ?';
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ssi', $status, $lastError, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function requeueFailedCredentialJobs(): int {
        $this->ensureCredentialSyncTables();
        $ok = $this->db->query(
            "UPDATE `student_attendance_credential_sync`
             SET `status` = 'pending', `last_error` = ''
             WHERE `status` = 'failed'"
        );
        return $ok ? (int) $this->db->affected_rows : 0;
    }

    /**
     * Remove credential sync queue rows for an employee (optionally limited to hosts).
     *
     * @param list<string> $hosts Empty = all hosts for this employee
     */
    public function deleteCredentialJobsForEmployee(string $employeeNo, array $hosts = []): int {
        $this->ensureCredentialSyncTables();
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return 0;
        }
        $hosts = array_values(array_filter(array_map(
            static fn ($h): string => trim((string) $h),
            $hosts
        ), static fn (string $h): bool => $h !== ''));

        if ($hosts === []) {
            $stmt = $this->db->prepare(
                'DELETE FROM `student_attendance_credential_sync` WHERE `employee_no` = ?'
            );
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param('s', $employeeNo);
            $stmt->execute();
            $n = (int) $stmt->affected_rows;
            $stmt->close();
            return $n;
        }

        $placeholders = implode(',', array_fill(0, count($hosts), '?'));
        $types = 's' . str_repeat('s', count($hosts));
        $params = array_merge([$employeeNo], $hosts);
        $stmt = $this->db->prepare(
            "DELETE FROM `student_attendance_credential_sync`
             WHERE `employee_no` = ? AND `device_host` IN ({$placeholders})"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $n = (int) $stmt->affected_rows;
        $stmt->close();
        return $n;
    }

    /**
     * Remove cached machine user row(s) after device delete.
     *
     * @param list<string> $hosts Empty = all machines for this employee
     */
    public function deleteMachineUserRows(string $employeeNo, array $hosts = []): int {
        $this->ensureTable();
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return 0;
        }
        $hosts = array_values(array_filter(array_map(
            static fn ($h): string => trim((string) $h),
            $hosts
        ), static fn (string $h): bool => $h !== ''));

        if ($hosts === []) {
            $stmt = $this->db->prepare(
                'DELETE FROM `student_attendance_machine_users` WHERE `employee_no` = ?'
            );
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param('s', $employeeNo);
            $stmt->execute();
            $n = (int) $stmt->affected_rows;
            $stmt->close();
            return $n;
        }

        $placeholders = implode(',', array_fill(0, count($hosts), '?'));
        $types = 's' . str_repeat('s', count($hosts));
        $params = array_merge([$employeeNo], $hosts);
        $stmt = $this->db->prepare(
            "DELETE FROM `student_attendance_machine_users`
             WHERE `employee_no` = ? AND `machine_id` IN ({$placeholders})"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $n = (int) $stmt->affected_rows;
        $stmt->close();
        return $n;
    }

    public function logCredentialSyncAttempt(
        string $employeeNo,
        string $deviceHost,
        string $operation,
        bool $success,
        string $message
    ): void {
        $this->ensureCredentialSyncTables();
        $stmt = $this->db->prepare(
            'INSERT INTO `student_attendance_credential_sync_logs`
                (`employee_no`, `device_host`, `operation`, `success`, `message`)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return;
        }
        $successInt = $success ? 1 : 0;
        $message = mb_substr($message, 0, 500);
        $stmt->bind_param('sssis', $employeeNo, $deviceHost, $operation, $successInt, $message);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listCredentialSyncLogs(int $limit = 100, string $employeeNo = ''): array {
        $this->ensureCredentialSyncTables();
        $limit = max(1, min(500, $limit));
        $employeeNo = trim($employeeNo);
        if ($employeeNo !== '') {
            $stmt = $this->db->prepare(
                "SELECT * FROM `student_attendance_credential_sync_logs`
                 WHERE `employee_no` = ?
                 ORDER BY `id` DESC LIMIT {$limit}"
            );
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('s', $employeeNo);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = $this->db->query(
                "SELECT * FROM `student_attendance_credential_sync_logs`
                 ORDER BY `id` DESC LIMIT {$limit}"
            );
        }
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * Per-device sync aggregates for dashboard.
     *
     * @return array<string, array{pending:int,syncing:int,success:int,failed:int,last_sync:?string}>
     */
    public function credentialSyncStatsByDevice(): array {
        $this->ensureCredentialSyncTables();
        $res = $this->db->query(
            "SELECT `device_host`, `status`, COUNT(*) AS `cnt`, MAX(`synced_at`) AS `last_sync`
             FROM `student_attendance_credential_sync`
             GROUP BY `device_host`, `status`"
        );
        $out = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $host = (string) ($r['device_host'] ?? '');
                if ($host === '') {
                    continue;
                }
                if (!isset($out[$host])) {
                    $out[$host] = [
                        'pending' => 0,
                        'syncing' => 0,
                        'success' => 0,
                        'failed' => 0,
                        'last_sync' => null,
                    ];
                }
                $st = (string) ($r['status'] ?? '');
                if (isset($out[$host][$st])) {
                    $out[$host][$st] = (int) ($r['cnt'] ?? 0);
                }
                $ls = $r['last_sync'] ?? null;
                if ($ls && ($out[$host]['last_sync'] === null || $ls > $out[$host]['last_sync'])) {
                    $out[$host]['last_sync'] = (string) $ls;
                }
            }
        }
        return $out;
    }

    /** Add finger/face counts on machine users cache. */
    private function ensureMachineUserExtraColumns(): void {
        foreach ([
            'finger_count' => "ALTER TABLE `student_attendance_machine_users` ADD COLUMN `finger_count` INT NOT NULL DEFAULT 0 AFTER `machine_id`",
            'face_count' => "ALTER TABLE `student_attendance_machine_users` ADD COLUMN `face_count` INT NOT NULL DEFAULT 0 AFTER `finger_count`",
            'finger_slots' => "ALTER TABLE `student_attendance_machine_users` ADD COLUMN `finger_slots` VARCHAR(40) NOT NULL DEFAULT '' AFTER `face_count`",
        ] as $col => $alter) {
            $c = $this->db->query("SHOW COLUMNS FROM `student_attendance_machine_users` LIKE '{$col}'");
            if ($c && $c->num_rows > 0) {
                continue;
            }
            $this->db->query($alter);
        }
    }

    /** Add student.finger_id once (stores machine employee_no only). */
    public function ensureFingerIdColumn(): void {
        $check = $this->db->query("SHOW COLUMNS FROM `student` LIKE 'finger_id'");
        if ($check && $check->num_rows > 0) {
            return;
        }
        $this->db->query(
            "ALTER TABLE `student`
             ADD COLUMN `finger_id` VARCHAR(50) NULL DEFAULT NULL
             COMMENT 'Hikvision employee_no for fingerprint attendance'
             AFTER `student_id`"
        );
        $this->db->query('CREATE INDEX `idx_student_finger_id` ON `student` (`finger_id`)');
    }

    /** Ensure student_attendance has student_id + employee_no. */
    private function ensureAttendanceExtraColumns(): void {
        $need = [
            'student_id' => "ALTER TABLE `{$this->table}` ADD COLUMN `student_id` VARCHAR(50) NOT NULL DEFAULT '' AFTER `id`",
            'employee_no' => "ALTER TABLE `{$this->table}` ADD COLUMN `employee_no` VARCHAR(50) NOT NULL DEFAULT '' AFTER `student_id`",
        ];
        foreach ($need as $col => $alter) {
            $c = $this->db->query("SHOW COLUMNS FROM `{$this->table}` LIKE '{$col}'");
            if ($c && $c->num_rows > 0) {
                continue;
            }
            $this->db->query($alter);
        }
        // Backfill person_id index helper
        $idx = $this->db->query("SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'idx_employee_no'");
        if (!$idx || $idx->num_rows === 0) {
            @$this->db->query("CREATE INDEX `idx_employee_no` ON `{$this->table}` (`employee_no`)");
        }
        $idx2 = $this->db->query("SHOW INDEX FROM `{$this->table}` WHERE Key_name = 'idx_student_id_att'");
        if (!$idx2 || $idx2->num_rows === 0) {
            @$this->db->query("CREATE INDEX `idx_student_id_att` ON `{$this->table}` (`student_id`)");
        }
    }

    /**
     * 2022/MET/4MA010 → 224MA010 (YY + last path segment).
     */
    public static function generateEmployeeNoFromStudentId(string $studentId): ?string {
        $studentId = trim($studentId);
        if ($studentId === '') {
            return null;
        }
        $parts = preg_split('#[/\\\\]+#', $studentId);
        if (!is_array($parts) || count($parts) < 2) {
            return null;
        }
        $year = trim((string) $parts[0]);
        $tail = trim((string) $parts[count($parts) - 1]);
        if (!preg_match('/^\d{4}$/', $year) || $tail === '') {
            return null;
        }
        return substr($year, -2) . $tail;
    }

    /**
     * Device "normal" users are students; also accept explicit student types.
     */
    public static function isStudentUserType(string $userType): bool {
        $t = strtolower(trim($userType));
        if ($t === '' || $t === 'normal' || $t === 'student' || $t === 'students') {
            return true;
        }
        return false;
    }

    public static function isStaffUserType(string $userType): bool {
        $t = strtolower(trim($userType));
        foreach (['staff', 'employee', 'administrator', 'administrators', 'admin', 'visitor'] as $hint) {
            if ($t !== '' && strpos($t, $hint) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Match machine_users.employee_no to students; set student.finger_id = employee_no.
     *
     * @return array{linked: int, skipped_staff: int, no_student: int}
     */
    public function linkFingerIdsFromMachineUsers(?string $machineId = null): array {
        $this->ensureTable();
        $out = ['linked' => 0, 'skipped_staff' => 0, 'no_student' => 0];

        // employee_no => user_type for student-type machine users
        $empMap = [];
        if ($machineId !== null && $machineId !== '') {
            $st = $this->db->prepare(
                'SELECT `employee_no`, `user_type` FROM `student_attendance_machine_users` WHERE `machine_id` = ?'
            );
            if ($st) {
                $st->bind_param('s', $machineId);
                $st->execute();
                $res = $st->get_result();
                while ($r = $res->fetch_assoc()) {
                    $eno = trim((string) ($r['employee_no'] ?? ''));
                    if ($eno === '') {
                        continue;
                    }
                    $empMap[$eno] = (string) ($r['user_type'] ?? 'normal');
                }
                $st->close();
            }
        } else {
            $res = $this->db->query('SELECT `employee_no`, `user_type` FROM `student_attendance_machine_users`');
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $eno = trim((string) ($r['employee_no'] ?? ''));
                    if ($eno === '') {
                        continue;
                    }
                    $empMap[$eno] = (string) ($r['user_type'] ?? 'normal');
                }
            }
        }

        if ($empMap === []) {
            return $out;
        }

        $students = $this->db->query('SELECT `student_id` FROM `student`');
        if (!$students) {
            return $out;
        }

        $upd = $this->db->prepare('UPDATE `student` SET `finger_id` = ? WHERE `student_id` = ?');
        if (!$upd) {
            return $out;
        }

        while ($s = $students->fetch_assoc()) {
            $sid = (string) ($s['student_id'] ?? '');
            $eno = self::generateEmployeeNoFromStudentId($sid);
            if ($eno === null || !isset($empMap[$eno])) {
                continue;
            }
            $ut = $empMap[$eno];
            if (self::isStaffUserType($ut)) {
                $out['skipped_staff']++;
                continue;
            }
            if (!self::isStudentUserType($ut)) {
                $out['skipped_staff']++;
                continue;
            }
            $upd->bind_param('ss', $eno, $sid);
            if ($upd->execute() && $upd->affected_rows >= 0) {
                // affected_rows can be 0 if value unchanged — still counts as linked
                $out['linked']++;
            }
        }
        $upd->close();

        // Count machine student users with no matching student_id pattern
        foreach ($empMap as $eno => $ut) {
            if (!self::isStudentUserType($ut) || self::isStaffUserType($ut)) {
                continue;
            }
            $chk = $this->db->prepare('SELECT 1 FROM `student` WHERE `finger_id` = ? LIMIT 1');
            if (!$chk) {
                break;
            }
            $chk->bind_param('s', $eno);
            $chk->execute();
            if (!$chk->get_result()->fetch_row()) {
                $out['no_student']++;
            }
            $chk->close();
        }

        return $out;
    }

    /**
     * Match machine employee_no via student.finger_id (preferred) or legacy student_id.
     *
     * @return array{student_id: string, student_name: string, finger_id: string}|null
     */
    public function findStudentByFingerId(string $employeeNo): ?array {
        $this->ensureFingerIdColumn();
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT `student_id`, `student_fullname`, `student_ininame`, `finger_id`
             FROM `student` WHERE `finger_id` = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $employeeNo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }
        $name = trim((string) ($row['student_fullname'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($row['student_ininame'] ?? ''));
        }
        return [
            'student_id' => (string) $row['student_id'],
            'student_name' => $name,
            'finger_id' => (string) ($row['finger_id'] ?? $employeeNo),
        ];
    }

    /**
     * @deprecated Use findStudentByFingerId
     * @return array{student_id: string, student_name: string}|null
     */
    public function findStudentByPersonId(string $personId): ?array {
        $byFinger = $this->findStudentByFingerId($personId);
        if ($byFinger !== null) {
            return [
                'student_id' => $byFinger['student_id'],
                'student_name' => $byFinger['student_name'],
            ];
        }
        // Legacy: direct student_id match
        $personId = trim($personId);
        if ($personId === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT `student_id`, `student_fullname`, `student_ininame`
             FROM `student` WHERE `student_id` = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $personId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }
        $name = trim((string) ($row['student_fullname'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($row['student_ininame'] ?? ''));
        }
        return [
            'student_id' => (string) $row['student_id'],
            'student_name' => $name,
        ];
    }

    /**
     * @param list<array{employee_no: string, name: string, user_type: string, finger_count?: int, face_count?: int}> $users
     */
    public function upsertMachineUsers(array $users, string $machineId): int {
        $this->ensureTable();
        $saved = 0;
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "INSERT INTO `student_attendance_machine_users`
                (`employee_no`, `name`, `user_type`, `machine_id`, `finger_count`, `face_count`, `finger_slots`, `synced_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `name`=VALUES(`name`),
                `user_type`=VALUES(`user_type`),
                `finger_count`=IF(? < 0, `finger_count`, ?),
                `face_count`=IF(? < 0, `face_count`, ?),
                `finger_slots`=IF(? = 0, `finger_slots`, VALUES(`finger_slots`)),
                `synced_at`=VALUES(`synced_at`)"
        );
        if (!$stmt) {
            return 0;
        }
        foreach ($users as $u) {
            $eno = (string) ($u['employee_no'] ?? '');
            if ($eno === '') {
                continue;
            }
            $name = (string) ($u['name'] ?? '');
            $type = (string) ($u['user_type'] ?? 'normal');
            $fingers = array_key_exists('finger_count', $u) ? (int) $u['finger_count'] : -1;
            $faces = array_key_exists('face_count', $u) ? (int) $u['face_count'] : -1;
            $fingersIns = $fingers < 0 ? 0 : $fingers;
            $facesIns = $faces < 0 ? 0 : $faces;
            $slots = '';
            $slotsProvided = 0;
            if (array_key_exists('finger_slots', $u)) {
                $slotsProvided = 1;
                if (is_array($u['finger_slots'])) {
                    $ids = array_values(array_unique(array_filter(array_map('intval', $u['finger_slots']), static fn ($n) => $n > 0)));
                    sort($ids);
                    $slots = implode(',', $ids);
                } else {
                    $slots = trim((string) $u['finger_slots']);
                }
            }
            $stmt->bind_param(
                'ssssiissiiiii',
                $eno,
                $name,
                $type,
                $machineId,
                $fingersIns,
                $facesIns,
                $slots,
                $now,
                $fingers,
                $fingersIns,
                $faces,
                $facesIns,
                $slotsProvided
            );
            if ($stmt->execute()) {
                $saved++;
            }
        }
        $stmt->close();
        return $saved;
    }

    /**
     * Search / list machine users with linked student details (card UI).
     *
     * @return list<array<string,mixed>>
     */
    public function listMachineUsersForEnroll(
        string $search = '',
        int $limit = 300,
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        string $courseMode = ''
    ): array {
        $this->ensureTable();
        // Profile photo column used in SELECT / cards
        $col = $this->db->query("SHOW COLUMNS FROM `student` LIKE 'student_profile_img'");
        if (!$col || $col->num_rows === 0) {
            @$this->db->query(
                "ALTER TABLE `student` ADD COLUMN `student_profile_img` VARCHAR(255) DEFAULT NULL AFTER `student_status`"
            );
        }
        $limit = max(1, min(500, $limit));
        $search = trim($search);
        $departmentId = trim($departmentId);
        $courseId = trim($courseId);
        $academicYear = trim($academicYear);
        $normalizedMode = self::normalizeCourseMode($courseMode);
        $hasFilters = $departmentId !== '' || $courseId !== '' || $academicYear !== '' || $normalizedMode !== '';

        $sql = "SELECT
                    mu.`employee_no`,
                    mu.`name` AS `machine_name`,
                    mu.`user_type`,
                    mu.`machine_id`,
                    mu.`finger_count`,
                    mu.`face_count`,
                    mu.`finger_slots`,
                    mu.`synced_at`,
                    s.`student_id`,
                    COALESCE(NULLIF(TRIM(s.`student_ininame`), ''), NULLIF(TRIM(s.`student_fullname`), ''), mu.`name`) AS `student_name`,
                    s.`student_status`,
                    s.`student_phone`,
                    s.`student_email`,
                    s.`student_gender`,
                    s.`student_profile_img`,
                    se.`academic_year`,
                    se.`course_mode`,
                    c.`department_id`,
                    c.`course_id`,
                    c.`course_name`,
                    d.`department_name`
                FROM `student_attendance_machine_users` mu
                LEFT JOIN `student` s ON s.`finger_id` = mu.`employee_no`
                LEFT JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                    AND UPPER(TRIM(se.`student_enroll_status`)) = 'FOLLOWING'
                LEFT JOIN `course` c ON c.`course_id` = se.`course_id`
                LEFT JOIN `department` d ON d.`department_id` = c.`department_id`";
        $types = '';
        $params = [];
        $where = [];
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(mu.`employee_no` LIKE ?
                        OR mu.`name` LIKE ?
                        OR s.`student_id` LIKE ?
                        OR s.`student_fullname` LIKE ?
                        OR s.`student_ininame` LIKE ?)';
            $types .= 'sssss';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($hasFilters) {
            // Filters require a linked Active Following enrollment.
            $where[] = 's.`student_id` IS NOT NULL';
            $where[] = "UPPER(TRIM(s.`student_status`)) = 'ACTIVE'";
            if ($departmentId !== '') {
                $where[] = 'c.`department_id` = ?';
                $types .= 's';
                $params[] = $departmentId;
            }
            if ($courseId !== '') {
                $where[] = 'se.`course_id` = ?';
                $types .= 's';
                $params[] = $courseId;
            }
            if ($academicYear !== '') {
                $where[] = 'se.`academic_year` = ?';
                $types .= 's';
                $params[] = $academicYear;
            }
            if ($normalizedMode !== '') {
                $where[] = 'LOWER(TRIM(se.`course_mode`)) = LOWER(TRIM(?))';
                $types .= 's';
                $params[] = $normalizedMode;
            }
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " GROUP BY mu.`employee_no`, mu.`name`, mu.`user_type`, mu.`machine_id`,
                           mu.`finger_count`, mu.`face_count`, mu.`finger_slots`, mu.`synced_at`,
                           s.`student_id`, s.`student_ininame`, s.`student_fullname`, s.`student_status`,
                           s.`student_phone`, s.`student_email`, s.`student_gender`, s.`student_profile_img`,
                           se.`academic_year`, se.`course_mode`, c.`department_id`, c.`course_id`,
                           c.`course_name`, d.`department_name`
                  ORDER BY mu.`employee_no` ASC LIMIT {$limit}";

        $rows = [];
        if ($types === '') {
            $res = $this->db->query($sql);
        } else {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }

        // Attach Active student by generated employee code when finger_id is not linked yet.
        foreach ($rows as &$r) {
            if (trim((string) ($r['student_id'] ?? '')) !== '') {
                continue;
            }
            if ($hasFilters) {
                // Already constrained to linked students when filters are active.
                continue;
            }
            $eno = trim((string) ($r['employee_no'] ?? ''));
            if ($eno === '') {
                continue;
            }
            $st = $this->findActiveStudentByEmployeeNo($eno);
            if (!$st) {
                continue;
            }
            $ini = trim((string) ($st['student_ininame'] ?? ''));
            $full = trim((string) ($st['student_fullname'] ?? ''));
            $r['student_id'] = (string) ($st['student_id'] ?? '');
            $r['student_name'] = $ini !== '' ? $ini : $full;
            $r['student_status'] = (string) ($st['student_status'] ?? '');
            $r['student_phone'] = (string) ($st['student_phone'] ?? '');
            $r['student_email'] = (string) ($st['student_email'] ?? '');
            $r['student_gender'] = (string) ($st['student_gender'] ?? '');
            $r['student_profile_img'] = (string) ($st['student_profile_img'] ?? '');
        }
        unset($r);

        // Also surface Active students matching search / filters who are not yet on the machine.
        if ($search !== '' || $hasFilters) {
            $extra = $this->findActiveStudentsForEnrollCards(
                $search,
                $departmentId,
                $courseId,
                $academicYear,
                $normalizedMode,
                min(120, $limit)
            );
            $seen = [];
            foreach ($rows as $r) {
                $seen[strtoupper((string) ($r['employee_no'] ?? ''))] = true;
            }
            foreach ($extra as $st) {
                $eno = (string) ($st['employee_no'] ?? '');
                $key = strtoupper($eno);
                if ($eno === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $rows[] = [
                    'employee_no' => $eno,
                    'machine_name' => (string) ($st['student_name'] ?? ''),
                    'user_type' => 'normal',
                    'machine_id' => '',
                    'finger_count' => 0,
                    'face_count' => 0,
                    'synced_at' => '',
                    'student_id' => (string) ($st['student_id'] ?? ''),
                    'student_name' => (string) ($st['student_name'] ?? ''),
                    'student_status' => (string) ($st['student_status'] ?? ''),
                    'student_phone' => (string) ($st['student_phone'] ?? ''),
                    'student_email' => (string) ($st['student_email'] ?? ''),
                    'student_gender' => (string) ($st['student_gender'] ?? ''),
                    'student_profile_img' => (string) ($st['student_profile_img'] ?? ''),
                    'department_id' => (string) ($st['department_id'] ?? ''),
                    'department_name' => (string) ($st['department_name'] ?? ''),
                    'course_id' => (string) ($st['course_id'] ?? ''),
                    'course_name' => (string) ($st['course_name'] ?? ''),
                    'academic_year' => (string) ($st['academic_year'] ?? ''),
                    'course_mode' => (string) ($st['course_mode'] ?? ''),
                    'on_machine' => false,
                ];
            }
        }

        foreach ($rows as &$r) {
            if (!array_key_exists('on_machine', $r)) {
                $r['on_machine'] = trim((string) ($r['machine_id'] ?? '')) !== ''
                    || trim((string) ($r['synced_at'] ?? '')) !== '';
            }
            $slotsRaw = trim((string) ($r['finger_slots'] ?? ''));
            $slots = [];
            if ($slotsRaw !== '') {
                foreach (explode(',', $slotsRaw) as $p) {
                    $n = (int) trim($p);
                    if ($n > 0) {
                        $slots[] = $n;
                    }
                }
            }
            $r['finger_slots'] = $slots;
            $r['has_finger_01'] = in_array(1, $slots, true);
            $r['has_finger_02'] = in_array(2, $slots, true);
            $r['has_face'] = (int) ($r['face_count'] ?? 0) > 0;
            if ((int) ($r['finger_count'] ?? 0) < count($slots)) {
                $r['finger_count'] = count($slots);
            }
        }
        unset($r);

        // One card per Employee No (same person on MAIN + readers must not create 4 cards)
        return $this->dedupeEnrollCardsByEmployeeNo($rows);
    }

    /**
     * Collapse machine_users rows that share employee_no across devices into one enroll card.
     * Prefers MAIN host; merges max finger/face flags.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function dedupeEnrollCardsByEmployeeNo(array $rows): array {
        if ($rows === []) {
            return [];
        }

        $mainHost = '';
        try {
            $cfg = require BASE_PATH . '/config/student_attendance_machine.php';
            $mainHost = trim((string) ($cfg['host'] ?? ''));
        } catch (Throwable $e) {
            $mainHost = '';
        }

        $byKey = [];
        foreach ($rows as $r) {
            $eno = trim((string) ($r['employee_no'] ?? ''));
            if ($eno === '') {
                continue;
            }
            $key = strtoupper($eno);
            $host = trim((string) ($r['machine_id'] ?? ''));
            $isMain = ($mainHost !== '' && $host === $mainHost);

            if (!isset($byKey[$key])) {
                $byKey[$key] = $r;
                $byKey[$key]['_is_main'] = $isMain ? 1 : 0;
                continue;
            }

            $cur = &$byKey[$key];
            // Prefer MAIN row as base identity / machine_id for enroll actions
            if ($isMain && empty($cur['_is_main'])) {
                $mergedSlots = array_values(array_unique(array_merge(
                    is_array($cur['finger_slots'] ?? null) ? $cur['finger_slots'] : [],
                    is_array($r['finger_slots'] ?? null) ? $r['finger_slots'] : []
                )));
                sort($mergedSlots);
                $name = (string) ($cur['student_name'] ?? '');
                $sid = (string) ($cur['student_id'] ?? '');
                $photo = (string) ($cur['student_profile_img'] ?? '');
                $cur = $r;
                $cur['_is_main'] = 1;
                if ($sid !== '' && trim((string) ($cur['student_id'] ?? '')) === '') {
                    $cur['student_id'] = $sid;
                    $cur['student_name'] = $name !== '' ? $name : ($cur['student_name'] ?? '');
                    $cur['student_profile_img'] = $photo !== '' ? $photo : ($cur['student_profile_img'] ?? '');
                }
                $cur['finger_slots'] = $mergedSlots;
            } else {
                // Keep current base; merge biometric flags / student link
                if (trim((string) ($cur['student_id'] ?? '')) === '' && trim((string) ($r['student_id'] ?? '')) !== '') {
                    foreach ([
                        'student_id', 'student_name', 'student_status', 'student_phone',
                        'student_email', 'student_gender', 'student_profile_img',
                        'department_id', 'department_name', 'course_id', 'course_name',
                        'academic_year', 'course_mode',
                    ] as $field) {
                        if (array_key_exists($field, $r)) {
                            $cur[$field] = $r[$field];
                        }
                    }
                }
                $slotsA = is_array($cur['finger_slots'] ?? null) ? $cur['finger_slots'] : [];
                $slotsB = is_array($r['finger_slots'] ?? null) ? $r['finger_slots'] : [];
                $mergedSlots = array_values(array_unique(array_merge($slotsA, $slotsB)));
                sort($mergedSlots);
                $cur['finger_slots'] = $mergedSlots;
            }

            $cur['finger_count'] = max((int) ($cur['finger_count'] ?? 0), (int) ($r['finger_count'] ?? 0), count($cur['finger_slots'] ?? []));
            $cur['face_count'] = max((int) ($cur['face_count'] ?? 0), (int) ($r['face_count'] ?? 0));
            $cur['has_finger_01'] = !empty($cur['has_finger_01']) || !empty($r['has_finger_01']) || in_array(1, $cur['finger_slots'] ?? [], true);
            $cur['has_finger_02'] = !empty($cur['has_finger_02']) || !empty($r['has_finger_02']) || in_array(2, $cur['finger_slots'] ?? [], true);
            $cur['has_face'] = !empty($cur['has_face']) || !empty($r['has_face']) || ((int) ($cur['face_count'] ?? 0) > 0);
            $cur['on_machine'] = !empty($cur['on_machine']) || !empty($r['on_machine']);
            // Prefer showing MAIN host when known
            if ($isMain || (trim((string) ($cur['machine_id'] ?? '')) === '' && $host !== '')) {
                $cur['machine_id'] = $isMain ? $host : (trim((string) ($cur['machine_id'] ?? '')) !== '' ? $cur['machine_id'] : $host);
            }
            unset($cur);
        }

        $out = [];
        foreach ($byKey as $row) {
            unset($row['_is_main']);
            // Enroll UI always targets MAIN — pin machine_id to main when configured
            if ($mainHost !== '') {
                $row['machine_id'] = $mainHost;
                $row['on_machine'] = !empty($row['on_machine']);
            }
            $slots = is_array($row['finger_slots'] ?? null) ? $row['finger_slots'] : [];
            $row['has_finger_01'] = in_array(1, $slots, true);
            $row['has_finger_02'] = in_array(2, $slots, true);
            $row['has_face'] = ((int) ($row['face_count'] ?? 0) > 0) || !empty($row['has_face']);
            $out[] = $row;
        }

        usort($out, static function (array $a, array $b): int {
            return strcmp((string) ($a['employee_no'] ?? ''), (string) ($b['employee_no'] ?? ''));
        });

        return $out;
    }

    /**
     * Active Following students for Users cards (search + academic filters).
     *
     * @return list<array<string,string>>
     */
    public function findActiveStudentsForEnrollCards(
        string $search = '',
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        string $courseMode = '',
        int $limit = 80
    ): array {
        $search = trim($search);
        $departmentId = trim($departmentId);
        $courseId = trim($courseId);
        $academicYear = trim($academicYear);
        $normalizedMode = self::normalizeCourseMode($courseMode);
        $hasFilters = $departmentId !== '' || $courseId !== '' || $academicYear !== '' || $normalizedMode !== '';
        if ($search === '' && !$hasFilters) {
            return [];
        }
        $limit = max(1, min(200, $limit));

        $sql = "SELECT s.`student_id`, s.`student_fullname`, s.`student_ininame`, s.`student_status`,
                       s.`student_phone`, s.`student_email`, s.`student_gender`, s.`finger_id`,
                       s.`student_profile_img`,
                       se.`academic_year`, se.`course_mode`, se.`course_id`,
                       c.`department_id`, c.`course_name`, d.`department_name`
                FROM `student` s
                INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                LEFT JOIN `department` d ON d.`department_id` = c.`department_id`
                WHERE UPPER(TRIM(s.`student_status`)) = 'ACTIVE'
                  AND UPPER(TRIM(se.`student_enroll_status`)) = 'FOLLOWING'";
        $types = '';
        $params = [];
        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= ' AND (
                s.`student_id` LIKE ?
                OR s.`finger_id` LIKE ?
                OR s.`student_fullname` LIKE ?
                OR s.`student_ininame` LIKE ?
            )';
            $types .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }
        if ($departmentId !== '') {
            $sql .= ' AND c.`department_id` = ?';
            $types .= 's';
            $params[] = $departmentId;
        }
        if ($courseId !== '') {
            $sql .= ' AND se.`course_id` = ?';
            $types .= 's';
            $params[] = $courseId;
        }
        if ($academicYear !== '') {
            $sql .= ' AND se.`academic_year` = ?';
            $types .= 's';
            $params[] = $academicYear;
        }
        if ($normalizedMode !== '') {
            $sql .= ' AND LOWER(TRIM(se.`course_mode`)) = LOWER(TRIM(?))';
            $types .= 's';
            $params[] = $normalizedMode;
        }
        $sql .= ' GROUP BY s.`student_id`, s.`student_fullname`, s.`student_ininame`, s.`student_status`,
                           s.`student_phone`, s.`student_email`, s.`student_gender`, s.`finger_id`,
                           s.`student_profile_img`, se.`academic_year`, se.`course_mode`, se.`course_id`,
                           c.`department_id`, c.`course_name`, d.`department_name`
                  ORDER BY d.`department_name` ASC, c.`course_name` ASC, s.`student_id` ASC
                  LIMIT ' . $limit;

        if ($types === '') {
            $res = $this->db->query($sql);
        } else {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        $out = [];
        if (!$res) {
            return [];
        }
        while ($r = $res->fetch_assoc()) {
            $sid = (string) ($r['student_id'] ?? '');
            $eno = trim((string) ($r['finger_id'] ?? ''));
            if ($eno === '') {
                $eno = (string) (self::generateEmployeeNoFromStudentId($sid)
                    ?? self::generatePersonIdForStudentExport($sid)
                    ?? '');
            }
            if ($search !== '' && $eno !== '') {
                $needleOk = stripos($eno, $search) !== false
                    || stripos($sid, $search) !== false
                    || stripos((string) ($r['student_fullname'] ?? ''), $search) !== false
                    || stripos((string) ($r['student_ininame'] ?? ''), $search) !== false;
                if (!$needleOk) {
                    continue;
                }
            }
            $ini = trim((string) ($r['student_ininame'] ?? ''));
            $full = trim((string) ($r['student_fullname'] ?? ''));
            $out[] = [
                'student_id' => $sid,
                'student_name' => $ini !== '' ? $ini : $full,
                'employee_no' => $eno,
                'student_status' => (string) ($r['student_status'] ?? ''),
                'student_phone' => (string) ($r['student_phone'] ?? ''),
                'student_email' => (string) ($r['student_email'] ?? ''),
                'student_gender' => (string) ($r['student_gender'] ?? ''),
                'student_profile_img' => (string) ($r['student_profile_img'] ?? ''),
                'department_id' => (string) ($r['department_id'] ?? ''),
                'department_name' => (string) ($r['department_name'] ?? ''),
                'course_id' => (string) ($r['course_id'] ?? ''),
                'course_name' => (string) ($r['course_name'] ?? ''),
                'academic_year' => (string) ($r['academic_year'] ?? ''),
                'course_mode' => (string) ($r['course_mode'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Active students whose Person ID / finger code matches search (or student id / name).
     *
     * @return list<array{student_id:string,student_name:string,employee_no:string,student_status:string,student_phone:string,student_email:string,student_gender:string}>
     */
    public function findActiveStudentsByEmployeeSearch(string $search, int $limit = 40): array {
        return $this->findActiveStudentsForEnrollCards($search, '', '', '', '', $limit);
    }

    /** Resolve Active student for an employee/person code. */
    public function findActiveStudentByEmployeeNo(string $employeeNo): ?array {
        $employeeNo = trim($employeeNo);
        if ($employeeNo === '') {
            return null;
        }
        $this->ensureFingerIdColumn();
        $stmt = $this->db->prepare(
            'SELECT s.`student_id`, s.`student_fullname`, s.`student_ininame`, s.`student_status`,
                    s.`student_phone`, s.`student_email`, s.`finger_id`, s.`student_profile_img`
             FROM `student` s
             WHERE s.`finger_id` = ?
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $employeeNo);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return $row;
            }
        }

        // Match generated employee no from student_id for Active + Following.
        $sql = "SELECT s.`student_id`, s.`student_fullname`, s.`student_ininame`, s.`student_status`,
                       s.`student_phone`, s.`student_email`, s.`finger_id`, s.`student_profile_img`
                FROM `student` s
                INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                WHERE UPPER(TRIM(s.`student_status`)) = 'ACTIVE'
                  AND UPPER(TRIM(se.`student_enroll_status`)) = 'FOLLOWING'
                GROUP BY s.`student_id`, s.`student_fullname`, s.`student_ininame`, s.`student_status`,
                         s.`student_phone`, s.`student_email`, s.`finger_id`, s.`student_profile_img`";
        $res = $this->db->query($sql);
        if (!$res) {
            return null;
        }
        while ($r = $res->fetch_assoc()) {
            $sid = (string) ($r['student_id'] ?? '');
            $eno = self::generateEmployeeNoFromStudentId($sid)
                ?? self::generatePersonIdForStudentExport($sid);
            if ($eno !== null && strcasecmp($eno, $employeeNo) === 0) {
                return $r;
            }
        }
        return null;
    }

    public function setStudentFingerId(string $studentId, string $employeeNo): bool {
        $studentId = trim($studentId);
        $employeeNo = trim($employeeNo);
        if ($studentId === '' || $employeeNo === '') {
            return false;
        }
        $this->ensureFingerIdColumn();
        $stmt = $this->db->prepare('UPDATE `student` SET `finger_id` = ? WHERE `student_id` = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $employeeNo, $studentId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** @return list<array<string,mixed>> */
    public function listMachineUsers(int $limit = 200): array {
        $this->ensureTable();
        $limit = max(1, min(1000, $limit));
        $rows = [];
        $res = $this->db->query(
            "SELECT * FROM `student_attendance_machine_users` ORDER BY `employee_no` ASC LIMIT {$limit}"
        );
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /**
     * User counts per machine_id for dashboard.
     *
     * @return array<string, array{users:int,last_synced:?string}>
     */
    public function machineUserStatsByHost(): array {
        $this->ensureTable();
        $out = [];
        $res = $this->db->query(
            "SELECT `machine_id`,
                    COUNT(*) AS `users`,
                    MAX(`synced_at`) AS `last_synced`
             FROM `student_attendance_machine_users`
             WHERE TRIM(`machine_id`) <> ''
             GROUP BY `machine_id`"
        );
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $host = (string) ($r['machine_id'] ?? '');
                if ($host === '') {
                    continue;
                }
                $out[$host] = [
                    'users' => (int) ($r['users'] ?? 0),
                    'last_synced' => $r['last_synced'] !== null ? (string) $r['last_synced'] : null,
                ];
            }
        }
        return $out;
    }

    /**
     * Person presence matrix across configured machines (from student_attendance_machine_users cache).
     *
     * @param list<string> $hosts Device IPs / machine_ids in column order
     * @return list<array{
     *   employee_no:string,
     *   name:string,
     *   present_count:int,
     *   missing_count:int,
     *   missing_hosts:list<string>,
     *   devices: array<string, array{present:bool,finger_count:int,face_count:int,synced_at:?string}>
     * }>
     */
    public function personPresenceByHosts(array $hosts, int $limit = 2000): array {
        $this->ensureTable();
        $hosts = array_values(array_unique(array_filter(array_map(
            static fn ($h): string => trim((string) $h),
            $hosts
        ), static fn (string $h): bool => $h !== '')));
        if ($hosts === []) {
            return [];
        }

        $limit = max(200, min(20000, $limit));
        // Row cap: each person may appear once per host
        $rowCap = min(50000, $limit * max(1, count($hosts)));
        $byEmp = [];

        // Pull all machine_users for these hosts
        $placeholders = implode(',', array_fill(0, count($hosts), '?'));
        $types = str_repeat('s', count($hosts));
        $stmt = $this->db->prepare(
            "SELECT `employee_no`, `name`, `machine_id`, `finger_count`, `face_count`, `synced_at`
             FROM `student_attendance_machine_users`
             WHERE `machine_id` IN ({$placeholders})
               AND TRIM(`employee_no`) <> ''
             ORDER BY `employee_no` ASC
             LIMIT {$rowCap}"
        );
        if ($stmt) {
            $stmt->bind_param($types, ...$hosts);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $eno = trim((string) ($r['employee_no'] ?? ''));
                $mid = trim((string) ($r['machine_id'] ?? ''));
                if ($eno === '' || $mid === '') {
                    continue;
                }
                if (!isset($byEmp[$eno])) {
                    $byEmp[$eno] = [
                        'employee_no' => $eno,
                        'name' => (string) ($r['name'] ?? ''),
                        'devices' => [],
                    ];
                }
                if ($byEmp[$eno]['name'] === '' && trim((string) ($r['name'] ?? '')) !== '') {
                    $byEmp[$eno]['name'] = (string) $r['name'];
                }
                $byEmp[$eno]['devices'][$mid] = [
                    'present' => true,
                    'finger_count' => (int) ($r['finger_count'] ?? 0),
                    'face_count' => (int) ($r['face_count'] ?? 0),
                    'synced_at' => $r['synced_at'] !== null ? (string) $r['synced_at'] : null,
                ];
            }
            $stmt->close();
        }

        $out = [];
        foreach ($byEmp as $eno => $row) {
            $devices = [];
            $present = 0;
            $missingHosts = [];
            foreach ($hosts as $h) {
                if (!empty($row['devices'][$h]['present'])) {
                    $devices[$h] = $row['devices'][$h];
                    $present++;
                } else {
                    $devices[$h] = [
                        'present' => false,
                        'finger_count' => 0,
                        'face_count' => 0,
                        'synced_at' => null,
                    ];
                    $missingHosts[] = $h;
                }
            }
            $out[] = [
                'employee_no' => $eno,
                'name' => (string) ($row['name'] ?? ''),
                'present_count' => $present,
                'missing_count' => count($missingHosts),
                'missing_hosts' => $missingHosts,
                'devices' => $devices,
            ];
        }

        // Incomplete first (missing on some readers), then employee_no
        usort($out, static function (array $a, array $b): int {
            $mc = ((int) $b['missing_count']) <=> ((int) $a['missing_count']);
            if ($mc !== 0) {
                return $mc;
            }
            return strcmp((string) $a['employee_no'], (string) $b['employee_no']);
        });

        return $out;
    }

    public function isStaffPersonId(string $personId): bool {
        $personId = trim($personId);
        if ($personId === '') {
            return false;
        }
        $stmt = $this->db->prepare('SELECT 1 FROM `staff` WHERE `staff_id` = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $personId);
        $stmt->execute();
        $ok = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();
        return $ok;
    }

    /**
     * @return array{inserted: bool, duplicate: bool}
     */
    public function insertEvent(array $row): array {
        $this->ensureAttendanceExtraColumns();
        $employeeNo = (string) ($row['employee_no'] ?? $row['person_id'] ?? '');
        $studentId = (string) ($row['student_id'] ?? '');
        $personId = $employeeNo; // legacy column keeps employee_no for duplicate key compatibility
        $sql = "INSERT IGNORE INTO `{$this->table}`
            (`student_id`, `employee_no`, `person_id`, `student_name`, `attendance_date`, `attendance_time`,
             `attendance_datetime`, `machine_id`, `event_id`, `source`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['inserted' => false, 'duplicate' => false];
        }
        $source = (string) ($row['source'] ?? 'hikvision');
        $stmt->bind_param(
            'ssssssssss',
            $studentId,
            $employeeNo,
            $personId,
            $row['student_name'],
            $row['attendance_date'],
            $row['attendance_time'],
            $row['attendance_datetime'],
            $row['machine_id'],
            $row['event_id'],
            $source
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return [
            'inserted' => $affected > 0,
            'duplicate' => $affected === 0,
        ];
    }

    /**
     * @return array{inserted: bool, duplicate: bool}
     */
    public function insertUnmatched(array $row): array {
        $sql = 'INSERT IGNORE INTO `student_attendance_unmatched`
            (`person_id`, `machine_name`, `attendance_date`, `attendance_time`, `attendance_datetime`,
             `machine_id`, `event_id`, `note`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['inserted' => false, 'duplicate' => false];
        }
        $note = (string) ($row['note'] ?? 'Person ID not found in student table');
        $stmt->bind_param(
            'ssssssss',
            $row['person_id'],
            $row['machine_name'],
            $row['attendance_date'],
            $row['attendance_time'],
            $row['attendance_datetime'],
            $row['machine_id'],
            $row['event_id'],
            $note
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return [
            'inserted' => $affected > 0,
            'duplicate' => $affected === 0,
        ];
    }

    /**
     * @return array{rows: list<array<string,mixed>>, total: int}
     */
    public function search(array $filters, int $page = 1, int $perPage = 50): array {
        $this->ensureTable();
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $types = '';
        $params = [];

        if (!empty($filters['person_id'])) {
            $where[] = '(`person_id` LIKE ? OR `employee_no` LIKE ? OR `student_id` LIKE ?)';
            $types .= 'sss';
            $like = '%' . $filters['person_id'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['student_id'])) {
            $where[] = '`student_id` LIKE ?';
            $types .= 's';
            $params[] = '%' . $filters['student_id'] . '%';
        }
        if (!empty($filters['employee_no'])) {
            $where[] = '`employee_no` LIKE ?';
            $types .= 's';
            $params[] = '%' . $filters['employee_no'] . '%';
        }
        if (!empty($filters['student_name'])) {
            $where[] = '`student_name` LIKE ?';
            $types .= 's';
            $params[] = '%' . $filters['student_name'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = '`attendance_date` >= ?';
            $types .= 's';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = '`attendance_date` <= ?';
            $types .= 's';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['date'])) {
            $where[] = '`attendance_date` = ?';
            $types .= 's';
            $params[] = $filters['date'];
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS c FROM `{$this->table}` WHERE {$whereSql}";
        $total = 0;
        if ($types === '') {
            $cr = $this->db->query($countSql);
            if ($cr && ($crow = $cr->fetch_assoc())) {
                $total = (int) $crow['c'];
            }
        } else {
            $cst = $this->db->prepare($countSql);
            if ($cst) {
                $cst->bind_param($types, ...$params);
                $cst->execute();
                $crow = $cst->get_result()->fetch_assoc();
                $total = (int) ($crow['c'] ?? 0);
                $cst->close();
            }
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE {$whereSql}
                ORDER BY `attendance_datetime` DESC LIMIT ? OFFSET ?";
        $rows = [];
        $st = $this->db->prepare($sql);
        if ($st) {
            $types2 = $types . 'ii';
            $params2 = $params;
            $params2[] = $perPage;
            $params2[] = $offset;
            $st->bind_param($types2, ...$params2);
            $st->execute();
            $res = $st->get_result();
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $st->close();
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Split ordered punch times: first = In, last = Out, middle = Others.
     *
     * @param list<string> $times
     * @return array{in: string, out: string, others: string, others_list: list<string>, punch_count: int}
     */
    public static function splitDayTimes(array $times): array {
        $parts = [];
        foreach ($times as $t) {
            $t = trim((string) $t);
            if ($t === '') {
                continue;
            }
            // Normalize to HH:MM:SS display (trim fractional if any)
            if (preg_match('/^(\d{1,2}:\d{2}(?::\d{2})?)/', $t, $m)) {
                $t = $m[1];
                if (substr_count($t, ':') === 1) {
                    $t .= ':00';
                }
            }
            $parts[] = $t;
        }
        $parts = array_values(array_unique($parts));
        sort($parts);
        $n = count($parts);
        if ($n === 0) {
            return ['in' => '', 'out' => '', 'others' => '', 'others_list' => [], 'punch_count' => 0];
        }
        if ($n === 1) {
            return [
                'in' => $parts[0],
                'out' => '',
                'others' => '',
                'others_list' => [],
                'punch_count' => 1,
            ];
        }
        $othersList = array_slice($parts, 1, $n - 2);
        return [
            'in' => $parts[0],
            'out' => $parts[$n - 1],
            'others' => implode(', ', $othersList),
            'others_list' => $othersList,
            'punch_count' => $n,
        ];
    }

    /**
     * One row per student per day with In / Out / Others times.
     *
     * @return array{rows: list<array<string,mixed>>, total: int}
     */
    public function searchDailyGrouped(array $filters, int $page = 1, int $perPage = 50): array {
        $this->ensureTable();
        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ["`student_id` <> ''"];
        $types = '';
        $params = [];

        if (!empty($filters['person_id'])) {
            $where[] = '(`person_id` LIKE ? OR `employee_no` LIKE ? OR `student_id` LIKE ?)';
            $types .= 'sss';
            $like = '%' . $filters['person_id'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['student_id'])) {
            $where[] = '`student_id` LIKE ?';
            $types .= 's';
            $params[] = '%' . $filters['student_id'] . '%';
        }
        if (!empty($filters['employee_no'])) {
            $where[] = '`employee_no` LIKE ?';
            $types .= 's';
            $params[] = '%' . $filters['employee_no'] . '%';
        }
        if (!empty($filters['student_name'])) {
            $where[] = '`student_name` LIKE ?';
            $types .= 's';
            $params[] = '%' . $filters['student_name'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = '`attendance_date` >= ?';
            $types .= 's';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = '`attendance_date` <= ?';
            $types .= 's';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['date'])) {
            $where[] = '`attendance_date` = ?';
            $types .= 's';
            $params[] = $filters['date'];
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS c FROM (
            SELECT 1 FROM `{$this->table}` WHERE {$whereSql}
            GROUP BY `student_id`, `attendance_date`
        ) g";
        $total = 0;
        if ($types === '') {
            $cr = $this->db->query($countSql);
            if ($cr && ($crow = $cr->fetch_assoc())) {
                $total = (int) $crow['c'];
            }
        } else {
            $cst = $this->db->prepare($countSql);
            if ($cst) {
                $cst->bind_param($types, ...$params);
                $cst->execute();
                $crow = $cst->get_result()->fetch_assoc();
                $total = (int) ($crow['c'] ?? 0);
                $cst->close();
            }
        }

        $sql = "SELECT
                    `student_id`,
                    MAX(`employee_no`) AS `employee_no`,
                    MAX(`student_name`) AS `student_name`,
                    `attendance_date`,
                    MAX(`machine_id`) AS `machine_id`,
                    GROUP_CONCAT(`attendance_time` ORDER BY `attendance_time` ASC SEPARATOR ',') AS `times_csv`,
                    COUNT(*) AS `punch_count`
                FROM `{$this->table}`
                WHERE {$whereSql}
                GROUP BY `student_id`, `attendance_date`
                ORDER BY `attendance_date` DESC, `student_id` ASC
                LIMIT ? OFFSET ?";

        $rows = [];
        $st = $this->db->prepare($sql);
        if ($st) {
            $types2 = $types . 'ii';
            $params2 = $params;
            $params2[] = $perPage;
            $params2[] = $offset;
            $st->bind_param($types2, ...$params2);
            $st->execute();
            $res = $st->get_result();
            while ($r = $res->fetch_assoc()) {
                $times = array_map('trim', explode(',', (string) ($r['times_csv'] ?? '')));
                $split = self::splitDayTimes($times);
                $rows[] = [
                    'student_id' => (string) ($r['student_id'] ?? ''),
                    'employee_no' => (string) ($r['employee_no'] ?? ''),
                    'student_name' => (string) ($r['student_name'] ?? ''),
                    'attendance_date' => (string) ($r['attendance_date'] ?? ''),
                    'machine_id' => (string) ($r['machine_id'] ?? ''),
                    'time_in' => $split['in'],
                    'time_out' => $split['out'],
                    'time_others' => $split['others'],
                    'punch_count' => (int) ($r['punch_count'] ?? $split['punch_count']),
                ];
            }
            $st->close();
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return list<array<string,mixed>> */
    public function exportDailyGrouped(array $filters, int $limit = 20000): array {
        $result = $this->searchDailyGrouped($filters, 1, max(1, min(50000, $limit)));
        return $result['rows'];
    }

    /**
     * Distinct students seen on the device (for month-report filter).
     *
     * @return list<array{student_id:string,employee_no:string,student_name:string}>
     */
    public function listDistinctStudents(int $limit = 2000): array {
        $this->ensureTable();
        $limit = max(1, min(5000, $limit));
        $rows = [];
        $sql = "SELECT
                    `student_id`,
                    MAX(`employee_no`) AS `employee_no`,
                    MAX(`student_name`) AS `student_name`
                FROM `{$this->table}`
                WHERE `student_id` <> ''
                GROUP BY `student_id`
                ORDER BY MAX(`student_name`) ASC, `student_id` ASC
                LIMIT {$limit}";
        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'student_id' => (string) ($r['student_id'] ?? ''),
                    'employee_no' => (string) ($r['employee_no'] ?? ''),
                    'student_name' => (string) ($r['student_name'] ?? ''),
                ];
            }
        }
        return $rows;
    }

    /**
     * Month report: one row per student per day for YYYY-MM.
     *
     * @return array{rows: list<array<string,mixed>>, total: int, date_from: string, date_to: string}
     */
    public function getMonthReport(string $reportMonth, string $personId = ''): array {
        $reportMonth = trim($reportMonth);
        if ($reportMonth === '' || !preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            return ['rows' => [], 'total' => 0, 'date_from' => '', 'date_to' => ''];
        }
        $dateFrom = $reportMonth . '-01';
        $ts = strtotime($dateFrom . ' 12:00:00');
        $dateTo = $ts ? date('Y-m-t', $ts) : $dateFrom;

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
        $personId = trim($personId);
        if ($personId !== '') {
            $filters['person_id'] = $personId;
        }

        $result = $this->searchDailyGrouped($filters, 1, 20000);
        $rows = $result['rows'];
        usort($rows, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['student_name'] ?? ''), (string) ($b['student_name'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcmp((string) ($a['student_id'] ?? ''), (string) ($b['student_id'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($a['attendance_date'] ?? ''), (string) ($b['attendance_date'] ?? ''));
        });

        return [
            'rows' => $rows,
            'total' => count($rows),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /** Present when In is on/before 08:40 and Out is on/after 16:00. */
    public const PRESENT_IN_CUTOFF = '08:40:00';
    public const PRESENT_OUT_CUTOFF = '16:00:00';

    /**
     * Classify a day's punches.
     *
     * @return array{status:string,label:string,time_in:string,time_out:string,time_others:string}
     */
    public static function classifyDayStatus(?string $timeIn, ?string $timeOut, string $timeOthers = ''): array {
        $in = self::normalizeTime($timeIn);
        $out = self::normalizeTime($timeOut);
        $others = trim($timeOthers);

        if ($in === '' && $out === '') {
            return [
                'status' => 'absent',
                'label' => 'Absent',
                'time_in' => '',
                'time_out' => '',
                'time_others' => $others,
            ];
        }

        $inOk = $in !== '' && $in <= self::PRESENT_IN_CUTOFF;
        $outOk = $out !== '' && $out >= self::PRESENT_OUT_CUTOFF;

        if ($inOk && $outOk) {
            $status = 'present';
            $label = 'Present';
        } elseif ($in !== '' && $out !== '') {
            $status = 'late';
            $label = 'Late / Incomplete hours';
        } else {
            $status = 'incomplete';
            $label = 'Incomplete punch';
        }

        return [
            'status' => $status,
            'label' => $label,
            'time_in' => $in !== '' ? substr($in, 0, 5) : '',
            'time_out' => $out !== '' ? substr($out, 0, 5) : '',
            'time_others' => $others,
        ];
    }

    private static function normalizeTime(?string $time): string {
        $time = trim((string) $time);
        if ($time === '' || $time === '—') {
            return '';
        }
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            $parts = explode(':', $time);
            $h = str_pad((string) ((int) $parts[0]), 2, '0', STR_PAD_LEFT);
            $m = str_pad((string) ((int) ($parts[1] ?? 0)), 2, '0', STR_PAD_LEFT);
            $s = str_pad((string) ((int) ($parts[2] ?? 0)), 2, '0', STR_PAD_LEFT);
            return $h . ':' . $m . ':' . $s;
        }
        return '';
    }

    /**
     * Active Following students for department / course / group (or all).
     * One row per student (primary active group when present).
     *
     * @return list<array{student_id:string,student_name:string,department_id:string,course_id:string,department_name:string,course_name:string,group_id:string,group_name:string}>
     */
    public function listActiveStudentsForReport(
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        string $groupId = '',
        string $studentId = ''
    ): array {
        $gid = $groupId !== '' ? (int) $groupId : 0;
        if ($gid > 0) {
            $groupJoin = "INNER JOIN `group_students` gs ON gs.`student_id` = s.`student_id` AND gs.`status` = 'active' AND gs.`group_id` = {$gid}
                INNER JOIN `groups` g ON g.`id` = gs.`group_id`";
        } else {
            $groupJoin = "LEFT JOIN (
                    SELECT `student_id`, MIN(`group_id`) AS `group_id`
                    FROM `group_students`
                    WHERE `status` = 'active'
                    GROUP BY `student_id`
                ) gsp ON gsp.`student_id` = s.`student_id`
                LEFT JOIN `groups` g ON g.`id` = gsp.`group_id`";
        }

        $sql = "SELECT
                    s.`student_id`,
                    COALESCE(NULLIF(TRIM(s.`student_ininame`), ''), s.`student_fullname`, s.`student_id`) AS `student_name`,
                    c.`department_id`,
                    se.`course_id`,
                    d.`department_name`,
                    c.`course_name`,
                    COALESCE(CAST(g.`id` AS CHAR), '') AS `group_id`,
                    COALESCE(g.`name`, '') AS `group_name`
                FROM `student` s
                INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                LEFT JOIN `department` d ON d.`department_id` = c.`department_id`
                {$groupJoin}
                WHERE s.`student_status` = 'Active'
                  AND se.`student_enroll_status` = 'Following'";
        $types = '';
        $params = [];
        if ($departmentId !== '') {
            $sql .= ' AND c.`department_id` = ?';
            $types .= 's';
            $params[] = $departmentId;
        }
        if ($courseId !== '') {
            $sql .= ' AND se.`course_id` = ?';
            $types .= 's';
            $params[] = $courseId;
        }
        if ($academicYear !== '') {
            $sql .= ' AND se.`academic_year` = ?';
            $types .= 's';
            $params[] = $academicYear;
        }
        if ($studentId !== '') {
            $sql .= ' AND s.`student_id` = ?';
            $types .= 's';
            $params[] = $studentId;
        }
        $sql .= ' GROUP BY s.`student_id`, c.`department_id`, se.`course_id`, d.`department_name`, c.`course_name`, g.`id`, g.`name`, s.`student_ininame`, s.`student_fullname`
                  ORDER BY d.`department_name` ASC, g.`name` ASC, `student_name` ASC, s.`student_id` ASC';

        $rows = [];
        if ($types === '') {
            $res = $this->db->query($sql);
        } else {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = [
                    'student_id' => (string) ($r['student_id'] ?? ''),
                    'student_name' => (string) ($r['student_name'] ?? ''),
                    'department_id' => (string) ($r['department_id'] ?? ''),
                    'course_id' => (string) ($r['course_id'] ?? ''),
                    'department_name' => (string) ($r['department_name'] ?? ''),
                    'course_name' => (string) ($r['course_name'] ?? ''),
                    'group_id' => (string) ($r['group_id'] ?? ''),
                    'group_name' => (string) ($r['group_name'] ?? ''),
                ];
            }
        }
        return $rows;
    }

    /**
     * Person ID for student information Excel export.
     * From Student ID only: year last 2 digits (first segment) + last 6 characters of the ID tail.
     * Example: 2022/MET/4MA010 → 22 + 4MA010 = 224MA010.
     * Academic year filter is not used for Person ID.
     */
    public static function generatePersonIdForStudentExport(string $studentId, string $academicYear = ''): ?string {
        $studentId = trim($studentId);
        if ($studentId === '') {
            return null;
        }
        $parts = preg_split('#[/\\\\]+#', $studentId);
        if (!is_array($parts) || $parts === []) {
            return null;
        }

        $yearPart = trim((string) $parts[0]);
        if (!preg_match('/^\d{4}$/', $yearPart)) {
            return null;
        }
        $yy = substr($yearPart, -2);

        $tail = trim((string) $parts[count($parts) - 1]);
        if ($tail === '') {
            return null;
        }
        $last6 = mb_strlen($tail, 'UTF-8') >= 6
            ? mb_substr($tail, -6, null, 'UTF-8')
            : $tail;
        $last6 = preg_replace('/\s+/', '', $last6) ?? $last6;
        if ($last6 === '') {
            return null;
        }

        return $yy . $last6;
    }

    /**
     * Active/Following students for Student Information Excel export (read-only).
     *
     * @return list<array{
     *   student_id: string,
     *   person_id: string,
     *   person_name: string,
     *   gender_code: string,
     *   contact: string,
     *   email: string,
     *   department_id: string,
     *   department_name: string,
     *   course_name: string,
     *   academic_year: string,
     *   group_name: string
     * }>
     */
    public function listStudentsForFingerprintImport(
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        string $groupId = '',
        string $courseMode = ''
    ): array {
        $gid = $groupId !== '' ? (int) $groupId : 0;
        $normalizedMode = self::normalizeCourseMode($courseMode);
        if ($gid > 0) {
            $groupJoin = "INNER JOIN `group_students` gs ON gs.`student_id` = s.`student_id`
                    AND gs.`status` = 'active' AND gs.`group_id` = {$gid}
                INNER JOIN `groups` g ON g.`id` = gs.`group_id`";
        } else {
            $groupJoin = "LEFT JOIN (
                    SELECT `student_id`, MIN(`group_id`) AS `group_id`
                    FROM `group_students`
                    WHERE `status` = 'active'
                    GROUP BY `student_id`
                ) gsp ON gsp.`student_id` = s.`student_id`
                LEFT JOIN `groups` g ON g.`id` = gsp.`group_id`";
        }

        // Active students only: student_status=Active AND enrollment still Following.
        $sql = "SELECT
                    s.`student_id`,
                    s.`student_fullname`,
                    s.`student_ininame`,
                    s.`student_gender`,
                    s.`student_phone`,
                    s.`student_email`,
                    s.`student_status`,
                    se.`student_enroll_status`,
                    se.`academic_year`,
                    se.`course_mode`,
                    c.`department_id`,
                    d.`department_name`,
                    c.`course_name`,
                    COALESCE(g.`name`, '') AS `group_name`
                FROM `student` s
                INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                LEFT JOIN `department` d ON d.`department_id` = c.`department_id`
                {$groupJoin}
                WHERE UPPER(TRIM(s.`student_status`)) = 'ACTIVE'
                  AND UPPER(TRIM(se.`student_enroll_status`)) = 'FOLLOWING'";
        $types = '';
        $params = [];
        if ($departmentId !== '') {
            $sql .= ' AND c.`department_id` = ?';
            $types .= 's';
            $params[] = $departmentId;
        }
        if ($courseId !== '') {
            $sql .= ' AND se.`course_id` = ?';
            $types .= 's';
            $params[] = $courseId;
        }
        if ($academicYear !== '') {
            $sql .= ' AND se.`academic_year` = ?';
            $types .= 's';
            $params[] = $academicYear;
        }
        if ($normalizedMode !== '') {
            $sql .= ' AND LOWER(TRIM(se.`course_mode`)) = LOWER(TRIM(?))';
            $types .= 's';
            $params[] = $normalizedMode;
        }
        $sql .= ' GROUP BY s.`student_id`, s.`student_fullname`, s.`student_ininame`, s.`student_gender`,
                           s.`student_phone`, s.`student_email`, s.`student_status`, se.`student_enroll_status`,
                           se.`academic_year`, se.`course_mode`,
                           c.`department_id`, d.`department_name`, c.`course_name`, g.`name`
                  ORDER BY d.`department_name` ASC, c.`course_name` ASC, s.`student_id` ASC';

        $raw = [];
        if ($types === '') {
            $res = $this->db->query($sql);
        } else {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $raw[] = $r;
            }
        }

        $out = [];
        $seenStudent = [];
        $usedPersonIds = [];
        foreach ($raw as $r) {
            // Belt-and-suspenders: never export Inactive / Dropout / Graduated, etc.
            $stStatus = strtoupper(trim((string) ($r['student_status'] ?? '')));
            $enStatus = strtoupper(trim((string) ($r['student_enroll_status'] ?? '')));
            if ($stStatus !== 'ACTIVE' || $enStatus !== 'FOLLOWING') {
                continue;
            }
            $sid = trim((string) ($r['student_id'] ?? ''));
            if ($sid === '' || isset($seenStudent[$sid])) {
                continue;
            }
            $enrollYear = trim((string) ($r['academic_year'] ?? ''));
            $personId = self::generatePersonIdForStudentExport($sid);
            if ($personId === null || $personId === '') {
                continue;
            }
            // Keep Person ID unique within this export set.
            $basePersonId = $personId;
            $n = 2;
            while (isset($usedPersonIds[$personId])) {
                $personId = $basePersonId . '-' . $n;
                $n++;
            }
            $usedPersonIds[$personId] = true;
            $seenStudent[$sid] = true;

            $ini = trim((string) ($r['student_ininame'] ?? ''));
            $full = trim((string) ($r['student_fullname'] ?? ''));
            $name = $ini !== '' ? $ini : ($full !== '' ? $full : $sid);
            $gender = strtolower(trim((string) ($r['student_gender'] ?? '')));
            $genderCode = '';
            if ($gender === 'male' || $gender === 'm') {
                $genderCode = '1';
            } elseif ($gender === 'female' || $gender === 'f') {
                $genderCode = '2';
            }
            $mode = (string) ($r['course_mode'] ?? '');
            $out[] = [
                'student_id' => $sid,
                'person_id' => $personId,
                'person_name' => mb_strtoupper($name, 'UTF-8'),
                'gender_code' => $genderCode,
                'contact' => trim((string) ($r['student_phone'] ?? '')),
                'email' => trim((string) ($r['student_email'] ?? '')),
                'department_id' => (string) ($r['department_id'] ?? ''),
                'department_name' => (string) ($r['department_name'] ?? ''),
                'course_name' => (string) ($r['course_name'] ?? ''),
                'academic_year' => $enrollYear,
                'group_name' => (string) ($r['group_name'] ?? ''),
                'course_mode' => $mode,
                'course_mode_label' => self::courseModeLabel($mode),
            ];
        }

        return $out;
    }

    /**
     * Punch map for students in a date range: student_id|date => day row.
     *
     * @param list<string> $studentIds
     * @return array<string, array<string,mixed>>
     */
    public function getDailyPunchMapForStudents(array $studentIds, string $dateFrom, string $dateTo): array {
        $this->ensureTable();
        $studentIds = array_values(array_unique(array_filter(array_map('strval', $studentIds))));
        if ($studentIds === []) {
            return [];
        }

        $map = [];
        foreach (array_chunk($studentIds, 200) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $sql = "SELECT
                        `student_id`,
                        MAX(`employee_no`) AS `employee_no`,
                        MAX(`student_name`) AS `student_name`,
                        `attendance_date`,
                        GROUP_CONCAT(`attendance_time` ORDER BY `attendance_time` ASC SEPARATOR ',') AS `times_csv`
                    FROM `{$this->table}`
                    WHERE `student_id` IN ({$placeholders})
                      AND `attendance_date` BETWEEN ? AND ?
                    GROUP BY `student_id`, `attendance_date`";
            $types = str_repeat('s', count($chunk)) . 'ss';
            $params = $chunk;
            $params[] = $dateFrom;
            $params[] = $dateTo;

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $times = array_map('trim', explode(',', (string) ($r['times_csv'] ?? '')));
                $split = self::splitDayTimes($times);
                $sid = (string) ($r['student_id'] ?? '');
                $d = (string) ($r['attendance_date'] ?? '');
                $map[$sid . '|' . $d] = [
                    'student_id' => $sid,
                    'employee_no' => (string) ($r['employee_no'] ?? ''),
                    'student_name' => (string) ($r['student_name'] ?? ''),
                    'attendance_date' => $d,
                    'time_in' => $split['in'],
                    'time_out' => $split['out'],
                    'time_others' => $split['others'],
                ];
            }
            $stmt->close();
        }
        return $map;
    }

    /**
     * Status report for a month (paginated). Working days exclude weekends,
     * public holidays, and SAO/ADM leave. Day detail rows are built only for the current page.
     *
     * @return array<string,mixed>
     */
    public function buildStatusReport(
        string $reportMonth,
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        int $page = 1,
        int $perPage = 20
    ): array {
        require_once BASE_PATH . '/helpers/SriLankaPublicHolidays.php';
        require_once BASE_PATH . '/models/StudentAttendanceHolidayModel.php';

        $emptySummary = [
            'students' => 0,
            'working_days' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'incomplete' => 0,
            'leave' => 0,
        ];
        if (!preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            return [
                'students' => [],
                'working_days' => [],
                'excluded_days' => [],
                'summary' => $emptySummary,
                'date_from' => '',
                'date_to' => '',
                'page' => 1,
                'per_page' => $perPage,
                'total_students' => 0,
                'total_pages' => 0,
            ];
        }

        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));

        $dateFrom = $reportMonth . '-01';
        $dateTo = date('Y-m-t', strtotime($dateFrom . ' 12:00:00'));
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
        if ($dateTo > $today) {
            $dateTo = $today;
        }
        if ($dateFrom > $dateTo) {
            return [
                'students' => [],
                'working_days' => [],
                'excluded_days' => [],
                'summary' => $emptySummary,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'page' => 1,
                'per_page' => $perPage,
                'total_students' => 0,
                'total_pages' => 0,
                'in_cutoff' => substr(self::PRESENT_IN_CUTOFF, 0, 5),
                'out_cutoff' => substr(self::PRESENT_OUT_CUTOFF, 0, 5),
            ];
        }
        $holidayModel = new StudentAttendanceHolidayModel();

        $excluded = [];
        $workingDays = [];
        $start = new DateTimeImmutable($dateFrom);
        $end = new DateTimeImmutable($dateTo);
        for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
            $ymd = $d->format('Y-m-d');
            $w = (int) $d->format('w');
            if ($w === 0 || $w === 6) {
                $excluded[$ymd] = ['reason' => 'weekend', 'label' => 'Weekend'];
                continue;
            }
            if (SriLankaPublicHolidays::isPublicHoliday($ymd)) {
                $excluded[$ymd] = ['reason' => 'public_holiday', 'label' => 'Public holiday'];
                continue;
            }
            $workingDays[] = [
                'date' => $ymd,
                'day' => $d->format('d'),
                'day_name' => $d->format('D'),
                'day_full' => $d->format('l'),
            ];
        }

        $allStudents = $this->listActiveStudentsForReport($departmentId, $courseId, $academicYear);
        $totalStudents = count($allStudents);
        $totalPages = $totalStudents > 0 ? (int) ceil($totalStudents / $perPage) : 0;
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $pageStudents = array_slice($allStudents, $offset, $perPage);
        $ids = array_column($pageStudents, 'student_id');
        $punchMap = $this->getDailyPunchMapForStudents($ids, $dateFrom, $dateTo);

        $summary = [
            'students' => count($pageStudents),
            'working_days' => count($workingDays),
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'incomplete' => 0,
            'leave' => 0,
        ];

        $leaveCache = [];
        $reportStudents = [];
        foreach ($pageStudents as $st) {
            $deptKey = (string) ($st['department_id'] ?? $departmentId);
            $courseKey = (string) ($st['course_id'] ?? $courseId);
            $cacheKey = $deptKey . '|' . $courseKey;
            if (!isset($leaveCache[$cacheKey])) {
                $leaveCache[$cacheKey] = $holidayModel->mapForScope($dateFrom, $dateTo, $deptKey, $courseKey);
            }
            $leaveMap = $leaveCache[$cacheKey];

            $days = [];
            $stPresent = 0;
            $stAbsent = 0;
            $stLate = 0;
            $stIncomplete = 0;
            $stLeave = 0;

            foreach ($workingDays as $wd) {
                $ymd = $wd['date'];
                if (isset($leaveMap[$ymd])) {
                    $leave = $leaveMap[$ymd];
                    $type = (string) ($leave['leave_type'] ?? 'holiday');
                    $title = trim((string) ($leave['title'] ?? ''));
                    $label = $type === 'special_leave' ? 'Special leave' : 'Holiday';
                    if ($title !== '') {
                        $label .= ': ' . $title;
                    }
                    $days[] = [
                        'date' => $ymd,
                        'day_name' => $wd['day_name'],
                        'day_full' => $wd['day_full'],
                        'status' => 'leave',
                        'label' => $label,
                        'time_in' => '',
                        'time_out' => '',
                        'time_others' => '',
                        'counted' => false,
                    ];
                    $stLeave++;
                    $summary['leave']++;
                    continue;
                }

                $key = $st['student_id'] . '|' . $ymd;
                $punch = $punchMap[$key] ?? null;
                $classified = self::classifyDayStatus(
                    $punch['time_in'] ?? '',
                    $punch['time_out'] ?? '',
                    $punch['time_others'] ?? ''
                );
                $days[] = [
                    'date' => $ymd,
                    'day_name' => $wd['day_name'],
                    'day_full' => $wd['day_full'],
                    'counted' => true,
                    'status' => $classified['status'],
                    'label' => $classified['label'],
                    'time_in' => $classified['time_in'],
                    'time_out' => $classified['time_out'],
                    'time_others' => $classified['time_others'],
                ];

                if ($classified['status'] === 'present') {
                    $stPresent++;
                    $summary['present']++;
                } elseif ($classified['status'] === 'late') {
                    $stLate++;
                    $summary['late']++;
                } elseif ($classified['status'] === 'incomplete') {
                    $stIncomplete++;
                    $summary['incomplete']++;
                } else {
                    $stAbsent++;
                    $summary['absent']++;
                }
            }

            $countedDays = $stPresent + $stAbsent + $stLate + $stIncomplete;
            $reportStudents[] = [
                'student_id' => $st['student_id'],
                'student_name' => $st['student_name'],
                'department_id' => $st['department_id'],
                'department_name' => $st['department_name'],
                'course_id' => $st['course_id'],
                'course_name' => $st['course_name'],
                'present' => $stPresent,
                'absent' => $stAbsent,
                'late' => $stLate,
                'incomplete' => $stIncomplete,
                'leave' => $stLeave,
                'counted_days' => $countedDays,
                'attendance_pct' => $countedDays > 0 ? round(($stPresent / $countedDays) * 100, 1) : 0.0,
                'days' => $days,
            ];
        }

        return [
            'students' => $reportStudents,
            'working_days' => $workingDays,
            'excluded_days' => $excluded,
            'summary' => $summary,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'in_cutoff' => substr(self::PRESENT_IN_CUTOFF, 0, 5),
            'out_cutoff' => substr(self::PRESENT_OUT_CUTOFF, 0, 5),
            'page' => $page,
            'per_page' => $perPage,
            'total_students' => $totalStudents,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Common month matrix: columns = weekdays (no weekends), cells = 1 / 0 / H,
     * with attendance %, allowance tiers, and bank details.
     *
     * @return array<string,mixed>
     */
    public function buildMatrixMonthReport(
        string $reportMonth,
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        string $studentId = '',
        bool $eligibleOnly = false,
        int $page = 1,
        int $perPage = 50,
        string $courseMode = ''
    ): array {
        require_once BASE_PATH . '/helpers/SriLankaPublicHolidays.php';
        require_once BASE_PATH . '/models/StudentAttendanceHolidayModel.php';

        $emptySummary = [
            'students' => 0,
            'working_days' => 0,
            'effective_working_days' => 0,
            'present' => 0,
            'absent' => 0,
            'holiday' => 0,
            'total_allowance' => 0,
            'above_90' => 0,
            'above_75' => 0,
            'below_75' => 0,
        ];
        if (!preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            return [
                'students' => [],
                'columns' => [],
                'summary' => $emptySummary,
                'date_from' => '',
                'date_to' => '',
                'page' => 1,
                'per_page' => $perPage,
                'total_students' => 0,
                'total_pages' => 0,
                'allowance_high' => 7500,
                'allowance_mid' => 6000,
                'in_cutoff' => substr(self::PRESENT_IN_CUTOFF, 0, 5),
                'out_cutoff' => substr(self::PRESENT_OUT_CUTOFF, 0, 5),
            ];
        }

        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));

        $dateFrom = $reportMonth . '-01';
        $monthEnd = date('Y-m-t', strtotime($dateFrom . ' 12:00:00'));
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
        $dateTo = $monthEnd > $today ? $today : $monthEnd;
        if ($dateFrom > $dateTo) {
            return [
                'students' => [],
                'columns' => [],
                'summary' => $emptySummary,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'page' => 1,
                'per_page' => $perPage,
                'total_students' => 0,
                'total_pages' => 0,
                'allowance_high' => ($reportMonth >= '2026-01') ? 7500 : 5000,
                'allowance_mid' => ($reportMonth >= '2026-01') ? 6000 : 4000,
                'in_cutoff' => substr(self::PRESENT_IN_CUTOFF, 0, 5),
                'out_cutoff' => substr(self::PRESENT_OUT_CUTOFF, 0, 5),
            ];
        }

        $allowanceHigh = ($reportMonth >= '2026-01') ? 7500 : 5000;
        $allowanceMid = ($reportMonth >= '2026-01') ? 6000 : 4000;
        $holidayModel = new StudentAttendanceHolidayModel();

        // Weekday columns only (no Sat/Sun). Public holidays + SAO leave shown as H.
        $columns = [];
        $start = new DateTimeImmutable($dateFrom);
        $end = new DateTimeImmutable($dateTo);
        for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
            $ymd = $d->format('Y-m-d');
            $w = (int) $d->format('w');
            if ($w === 0 || $w === 6) {
                continue;
            }
            $isPublic = SriLankaPublicHolidays::isPublicHoliday($ymd);
            $columns[] = [
                'date' => $ymd,
                'day' => $d->format('d'),
                'day_name' => $d->format('D'),
                'is_public_holiday' => $isPublic,
            ];
        }

        $allStudents = $this->listStudentsForMatrixReport(
            $departmentId,
            $courseId,
            $academicYear,
            $studentId,
            $eligibleOnly,
            $reportMonth,
            $courseMode
        );
        $totalStudents = count($allStudents);
        $totalPages = $totalStudents > 0 ? (int) ceil($totalStudents / $perPage) : 0;
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        // Summary uses all students; page slice for display only when perPage > 0
        $ids = array_column($allStudents, 'student_id');
        $punchMap = $this->getDailyPunchMapForStudents($ids, $dateFrom, $dateTo);

        $leaveCache = [];
        $summary = $emptySummary;
        $summary['working_days'] = count($columns);
        $reportStudents = [];

        foreach ($allStudents as $st) {
            $deptKey = (string) ($st['department_id'] ?? $departmentId);
            $courseKey = (string) ($st['course_id'] ?? $courseId);
            $cacheKey = $deptKey . '|' . $courseKey;
            if (!isset($leaveCache[$cacheKey])) {
                $leaveCache[$cacheKey] = $holidayModel->mapForScope($dateFrom, $dateTo, $deptKey, $courseKey);
            }
            $leaveMap = $leaveCache[$cacheKey];

            $dayByDay = [];
            $presentDays = 0;
            $absentDays = 0;
            $holidayDays = 0;

            foreach ($columns as $col) {
                $ymd = $col['date'];
                if (!empty($col['is_public_holiday']) || isset($leaveMap[$ymd])) {
                    $dayByDay[$ymd] = 'H';
                    $holidayDays++;
                    continue;
                }
                $punch = $punchMap[$st['student_id'] . '|' . $ymd] ?? null;
                $classified = self::classifyDayStatus(
                    $punch['time_in'] ?? '',
                    $punch['time_out'] ?? '',
                    $punch['time_others'] ?? ''
                );
                if ($classified['status'] === 'present') {
                    $dayByDay[$ymd] = '1';
                    $presentDays++;
                } else {
                    $dayByDay[$ymd] = '0';
                    $absentDays++;
                }
            }

            $effective = $presentDays + $absentDays;
            $pct = $effective > 0 ? round(($presentDays / $effective) * 100, 1) : 0.0;

            $isEligible = !empty($st['allowance_eligible']);
            if ($isEligible && !empty($st['allowance_eligible_date'])) {
                $eligibleYm = substr((string) $st['allowance_eligible_date'], 0, 7);
                if ($reportMonth < $eligibleYm) {
                    $isEligible = false;
                }
            }
            $allowance = 0;
            if ($isEligible) {
                if ($pct >= 90) {
                    $allowance = $allowanceHigh;
                } elseif ($pct >= 75) {
                    $allowance = $allowanceMid;
                }
            }

            $summary['present'] += $presentDays;
            $summary['absent'] += $absentDays;
            $summary['holiday'] += $holidayDays;
            $summary['total_allowance'] += $allowance;
            if ($pct >= 90) {
                $summary['above_90']++;
            } elseif ($pct >= 75) {
                $summary['above_75']++;
            } else {
                $summary['below_75']++;
            }

            $reportStudents[] = [
                'student_id' => $st['student_id'],
                'student_name' => $st['student_name'],
                'student_fullname' => $st['student_fullname'],
                'student_nic' => $st['student_nic'],
                'department_id' => $st['department_id'],
                'department_name' => $st['department_name'],
                'course_id' => $st['course_id'],
                'course_name' => $st['course_name'],
                'course_mode' => $st['course_mode'] ?? '',
                'course_mode_label' => $st['course_mode_label'] ?? '',
                'bank_name' => $st['bank_name'],
                'bank_account_no' => $st['bank_account_no'],
                'bank_branch' => $st['bank_branch'],
                'allowance_eligible' => $isEligible ? 1 : 0,
                'day_by_day' => $dayByDay,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'holiday_days' => $holidayDays,
                'effective_working_days' => $effective,
                'attendance_percentage' => $pct,
                'allowance' => $allowance,
            ];
        }

        $summary['students'] = $totalStudents;
        if ($reportStudents !== []) {
            $summary['effective_working_days'] = (int) $reportStudents[0]['effective_working_days'];
        } else {
            $summary['effective_working_days'] = count($columns);
        }

        $pageStudents = array_slice($reportStudents, $offset, $perPage);

        return [
            'students' => $pageStudents,
            'all_students' => $reportStudents,
            'columns' => $columns,
            'summary' => $summary,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'page' => $page,
            'per_page' => $perPage,
            'total_students' => $totalStudents,
            'total_pages' => $totalPages,
            'allowance_high' => $allowanceHigh,
            'allowance_mid' => $allowanceMid,
            'in_cutoff' => substr(self::PRESENT_IN_CUTOFF, 0, 5),
            'out_cutoff' => substr(self::PRESENT_OUT_CUTOFF, 0, 5),
        ];
    }

    /**
     * Active + Following students with bank + allowance fields for matrix month report.
     *
     * @return list<array<string,mixed>>
     */
    public function listStudentsForMatrixReport(
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        string $studentId = '',
        bool $eligibleOnly = false,
        string $reportMonth = '',
        string $courseMode = ''
    ): array {
        $normalizedMode = self::normalizeCourseMode($courseMode);

        $sql = "SELECT
                    s.`student_id`,
                    COALESCE(NULLIF(TRIM(s.`student_ininame`), ''), s.`student_fullname`, s.`student_id`) AS `student_name`,
                    COALESCE(s.`student_fullname`, '') AS `student_fullname`,
                    COALESCE(s.`student_nic`, '') AS `student_nic`,
                    COALESCE(s.`bank_name`, '') AS `bank_name`,
                    COALESCE(s.`bank_account_no`, '') AS `bank_account_no`,
                    COALESCE(s.`bank_branch`, '') AS `bank_branch`,
                    COALESCE(s.`allowance_eligible`, 0) AS `allowance_eligible`,
                    s.`allowance_eligible_date`,
                    c.`department_id`,
                    se.`course_id`,
                    se.`course_mode`,
                    d.`department_name`,
                    c.`course_name`
                FROM `student` s
                INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id`
                INNER JOIN `course` c ON c.`course_id` = se.`course_id`
                LEFT JOIN `department` d ON d.`department_id` = c.`department_id`
                WHERE s.`student_status` = 'Active'
                  AND se.`student_enroll_status` = 'Following'";
        $types = '';
        $params = [];
        if ($departmentId !== '') {
            $sql .= ' AND c.`department_id` = ?';
            $types .= 's';
            $params[] = $departmentId;
        }
        if ($courseId !== '') {
            $sql .= ' AND se.`course_id` = ?';
            $types .= 's';
            $params[] = $courseId;
        }
        if ($academicYear !== '') {
            $sql .= ' AND se.`academic_year` = ?';
            $types .= 's';
            $params[] = $academicYear;
        }
        if ($normalizedMode !== '') {
            $sql .= ' AND LOWER(TRIM(se.`course_mode`)) = LOWER(TRIM(?))';
            $types .= 's';
            $params[] = $normalizedMode;
        }
        if ($studentId !== '') {
            $sql .= ' AND s.`student_id` = ?';
            $types .= 's';
            $params[] = $studentId;
        }
        if ($eligibleOnly) {
            $sql .= ' AND s.`allowance_eligible` = 1';
            if ($reportMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
                $sql .= " AND (s.`allowance_eligible_date` IS NULL OR DATE_FORMAT(s.`allowance_eligible_date`, '%Y-%m') <= ?)";
                $types .= 's';
                $params[] = $reportMonth;
            }
        }
        $sql .= ' GROUP BY s.`student_id`, c.`department_id`, se.`course_id`, se.`course_mode`, d.`department_name`, c.`course_name`,
                    s.`student_ininame`, s.`student_fullname`, s.`student_nic`, s.`bank_name`, s.`bank_account_no`,
                    s.`bank_branch`, s.`allowance_eligible`, s.`allowance_eligible_date`
                  ORDER BY d.`department_name` ASC, `student_name` ASC, s.`student_id` ASC';

        $rows = [];
        if ($types === '') {
            $res = $this->db->query($sql);
        } else {
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        }
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $mode = (string) ($r['course_mode'] ?? '');
                $modeLabel = self::courseModeLabel($mode);
                $rows[] = [
                    'student_id' => (string) ($r['student_id'] ?? ''),
                    'student_name' => (string) ($r['student_name'] ?? ''),
                    'student_fullname' => (string) ($r['student_fullname'] ?? ''),
                    'student_nic' => (string) ($r['student_nic'] ?? ''),
                    'bank_name' => (string) ($r['bank_name'] ?? ''),
                    'bank_account_no' => (string) ($r['bank_account_no'] ?? ''),
                    'bank_branch' => (string) ($r['bank_branch'] ?? ''),
                    'allowance_eligible' => (int) ($r['allowance_eligible'] ?? 0),
                    'allowance_eligible_date' => $r['allowance_eligible_date'] ?? null,
                    'department_id' => (string) ($r['department_id'] ?? ''),
                    'course_id' => (string) ($r['course_id'] ?? ''),
                    'course_mode' => $mode,
                    'course_mode_label' => $modeLabel,
                    'department_name' => (string) ($r['department_name'] ?? ''),
                    'course_name' => (string) ($r['course_name'] ?? ''),
                ];
            }
        }
        return $rows;
    }

    /** Normalize UI course mode to DB enum Full / Part. */
    public static function normalizeCourseMode(string $courseMode): string {
        $courseMode = trim($courseMode);
        if ($courseMode === '') {
            return '';
        }
        $lower = strtolower($courseMode);
        if (in_array($lower, ['full', 'full time', 'fulltime', 'ft'], true)) {
            return 'Full';
        }
        if (in_array($lower, ['part', 'part time', 'parttime', 'pt'], true)) {
            return 'Part';
        }
        return '';
    }

    public static function courseModeLabel(string $mode): string {
        $n = self::normalizeCourseMode($mode);
        if ($n === 'Full') {
            return 'Full Time';
        }
        if ($n === 'Part') {
            return 'Part Time';
        }
        return $mode !== '' ? $mode : '—';
    }

    /**
     * Monthly SAO dashboard: summary KPIs + students with ≥N consecutive unauthorized absences.
     *
     * @return array<string,mixed>
     */
    public function buildSaoDashboard(
        string $reportMonth,
        string $departmentId = '',
        string $courseId = '',
        string $academicYear = '',
        string $groupId = '',
        string $studentId = '',
        string $statusFilter = 'flagged',
        int $consecutiveThreshold = 3
    ): array {
        require_once BASE_PATH . '/helpers/SriLankaPublicHolidays.php';
        require_once BASE_PATH . '/models/StudentAttendanceHolidayModel.php';

        $consecutiveThreshold = max(2, min(15, $consecutiveThreshold));
        $empty = [
            'flagged' => [],
            'summary' => [
                'students' => 0,
                'working_days' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'incomplete' => 0,
                'leave' => 0,
                'flagged' => 0,
                'avg_attendance_pct' => 0.0,
            ],
            'working_days' => [],
            'date_from' => '',
            'date_to' => '',
            'in_cutoff' => substr(self::PRESENT_IN_CUTOFF, 0, 5),
            'out_cutoff' => substr(self::PRESENT_OUT_CUTOFF, 0, 5),
            'consecutive_threshold' => $consecutiveThreshold,
        ];

        if (!preg_match('/^\d{4}-\d{2}$/', $reportMonth)) {
            return $empty;
        }

        $dateFrom = $reportMonth . '-01';
        $dateTo = date('Y-m-t', strtotime($dateFrom . ' 12:00:00'));
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
        if ($dateTo > $today) {
            $dateTo = $today;
        }
        if ($dateFrom > $dateTo) {
            return $empty;
        }
        $holidayModel = new StudentAttendanceHolidayModel();

        $workingDays = [];
        $start = new DateTimeImmutable($dateFrom);
        $end = new DateTimeImmutable($dateTo);
        for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
            $ymd = $d->format('Y-m-d');
            $w = (int) $d->format('w');
            if ($w === 0 || $w === 6) {
                continue;
            }
            if (SriLankaPublicHolidays::isPublicHoliday($ymd)) {
                continue;
            }
            $workingDays[] = [
                'date' => $ymd,
                'day_name' => $d->format('D'),
            ];
        }

        $students = $this->listActiveStudentsForReport($departmentId, $courseId, $academicYear, $groupId, $studentId);
        $ids = array_column($students, 'student_id');
        $punchMap = $this->getDailyPunchMapForStudents($ids, $dateFrom, $dateTo);

        $summary = [
            'students' => count($students),
            'working_days' => count($workingDays),
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'incomplete' => 0,
            'leave' => 0,
            'flagged' => 0,
            'avg_attendance_pct' => 0.0,
        ];

        $leaveCache = [];
        $flagged = [];
        $pctSum = 0.0;
        $pctN = 0;

        foreach ($students as $st) {
            $deptKey = (string) ($st['department_id'] ?? $departmentId);
            $courseKey = (string) ($st['course_id'] ?? '');
            $cacheKey = $deptKey . '|' . $courseKey;
            if (!isset($leaveCache[$cacheKey])) {
                $leaveCache[$cacheKey] = $holidayModel->mapForScope($dateFrom, $dateTo, $deptKey, $courseKey);
            }
            $leaveMap = $leaveCache[$cacheKey];

            $stPresent = 0;
            $stAbsent = 0;
            $stLate = 0;
            $stIncomplete = 0;
            $stLeave = 0;
            $streak = 0;
            $streakDates = [];
            $bestStreak = 0;
            $bestDates = [];

            foreach ($workingDays as $wd) {
                $ymd = $wd['date'];
                if (isset($leaveMap[$ymd])) {
                    $stLeave++;
                    $summary['leave']++;
                    // Authorized leave breaks unauthorized absence streak
                    $streak = 0;
                    $streakDates = [];
                    continue;
                }

                $punch = $punchMap[$st['student_id'] . '|' . $ymd] ?? null;
                $classified = self::classifyDayStatus(
                    $punch['time_in'] ?? '',
                    $punch['time_out'] ?? '',
                    $punch['time_others'] ?? ''
                );
                $status = $classified['status'];

                if ($status === 'present') {
                    $stPresent++;
                    $summary['present']++;
                    $streak = 0;
                    $streakDates = [];
                } elseif ($status === 'late') {
                    $stLate++;
                    $summary['late']++;
                    $streak = 0;
                    $streakDates = [];
                } elseif ($status === 'incomplete') {
                    $stIncomplete++;
                    $summary['incomplete']++;
                    $streak = 0;
                    $streakDates = [];
                } else {
                    $stAbsent++;
                    $summary['absent']++;
                    $streak++;
                    $streakDates[] = $ymd;
                    if ($streak > $bestStreak) {
                        $bestStreak = $streak;
                        $bestDates = $streakDates;
                    }
                }
            }

            $counted = $stPresent + $stAbsent + $stLate + $stIncomplete;
            $pct = $counted > 0 ? round(($stPresent / $counted) * 100, 1) : 0.0;
            if ($counted > 0) {
                $pctSum += $pct;
                $pctN++;
            }

            $isFlagged = $bestStreak >= $consecutiveThreshold;
            if ($isFlagged) {
                $summary['flagged']++;
            }

            $include = false;
            if ($statusFilter === 'all') {
                $include = true;
            } elseif ($statusFilter === '' || $statusFilter === 'flagged') {
                $include = $isFlagged;
            } elseif ($statusFilter === 'low') {
                $include = $pct < 80.0 && $counted > 0;
            } elseif ($statusFilter === 'ok') {
                $include = !$isFlagged && $pct >= 80.0;
            }
            if ($studentId !== '') {
                $include = true;
            }

            if (!$include) {
                continue;
            }

            $flagged[] = [
                'student_id' => $st['student_id'],
                'student_name' => $st['student_name'],
                'department_id' => $st['department_id'],
                'department_name' => $st['department_name'],
                'course_id' => $st['course_id'],
                'course_name' => $st['course_name'],
                'group_id' => $st['group_id'],
                'group_name' => $st['group_name'] !== '' ? $st['group_name'] : '—',
                'present' => $stPresent,
                'absent' => $stAbsent,
                'late' => $stLate,
                'incomplete' => $stIncomplete,
                'leave' => $stLeave,
                'leave_days' => $bestStreak,
                'leave_dates' => $bestDates,
                'leave_dates_label' => $bestDates !== [] ? implode(', ', $bestDates) : '—',
                'attendance_pct' => $pct,
                'flagged' => $isFlagged,
                'status_label' => $isFlagged ? 'Flagged' : ($pct < 80 ? 'Low attendance' : 'OK'),
            ];
        }

        usort($flagged, static function (array $a, array $b): int {
            $cmp = ($b['leave_days'] <=> $a['leave_days']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ($a['attendance_pct'] <=> $b['attendance_pct']);
        });

        $summary['avg_attendance_pct'] = $pctN > 0 ? round($pctSum / $pctN, 1) : 0.0;

        return [
            'flagged' => $flagged,
            'summary' => $summary,
            'working_days' => $workingDays,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'in_cutoff' => substr(self::PRESENT_IN_CUTOFF, 0, 5),
            'out_cutoff' => substr(self::PRESENT_OUT_CUTOFF, 0, 5),
            'consecutive_threshold' => $consecutiveThreshold,
        ];
    }

    /**
     * Single student row for warning letter (same rules as dashboard).
     *
     * @return array<string,mixed>|null
     */
    public function getSaoDashboardStudentRow(string $reportMonth, string $studentId, string $departmentId = ''): ?array {
        $dash = $this->buildSaoDashboard($reportMonth, $departmentId, '', '', '', $studentId, 'all', 3);
        foreach ($dash['flagged'] as $row) {
            if ((string) ($row['student_id'] ?? '') === $studentId) {
                return $row;
            }
        }
        return null;
    }

    /** @return list<array<string,mixed>> */
    public function listUnmatched(int $limit = 100): array {
        $this->ensureTable();
        $limit = max(1, min(500, $limit));
        $rows = [];
        $res = $this->db->query(
            "SELECT * FROM `student_attendance_unmatched` ORDER BY `attendance_datetime` DESC LIMIT {$limit}"
        );
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function countToday(): int {
        $this->ensureTable();
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
        $st = $this->db->prepare("SELECT COUNT(*) AS c FROM `{$this->table}` WHERE `attendance_date` = ?");
        if (!$st) {
            return 0;
        }
        $st->bind_param('s', $today);
        $st->execute();
        $c = (int) ($st->get_result()->fetch_assoc()['c'] ?? 0);
        $st->close();
        return $c;
    }

    public function countUniqueStudentsToday(): int {
        $this->ensureTable();
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d');
        $st = $this->db->prepare(
            "SELECT COUNT(DISTINCT CASE WHEN `student_id` <> '' THEN `student_id` ELSE `person_id` END) AS c
             FROM `{$this->table}` WHERE `attendance_date` = ?"
        );
        if (!$st) {
            return 0;
        }
        $st->bind_param('s', $today);
        $st->execute();
        $c = (int) ($st->get_result()->fetch_assoc()['c'] ?? 0);
        $st->close();
        return $c;
    }

    public function countAll(): int {
        $this->ensureTable();
        $res = $this->db->query("SELECT COUNT(*) AS c FROM `{$this->table}`");
        if ($res && ($r = $res->fetch_assoc())) {
            return (int) $r['c'];
        }
        return 0;
    }

    /** @return list<array<string,mixed>> */
    public function exportRows(array $filters, int $limit = 20000): array {
        $filters = $filters;
        $result = $this->search($filters, 1, max(1, min(50000, $limit)));
        return $result['rows'];
    }
}
