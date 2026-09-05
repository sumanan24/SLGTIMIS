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
            `synced_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_machine_emp` (`machine_id`, `employee_no`),
            KEY `idx_emp_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sqlM);

        $this->ensureFingerIdColumn();
        $this->ensureAttendanceExtraColumns();
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
     * @param list<array{employee_no: string, name: string, user_type: string}> $users
     */
    public function upsertMachineUsers(array $users, string $machineId): int {
        $this->ensureTable();
        $saved = 0;
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            'INSERT INTO `student_attendance_machine_users`
                (`employee_no`, `name`, `user_type`, `machine_id`, `synced_at`)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `name`=VALUES(`name`),
                `user_type`=VALUES(`user_type`),
                `synced_at`=VALUES(`synced_at`)'
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
            // Normalize device "normal" display as Student for MIS logic (stored as-is from device)
            $stmt->bind_param('sssss', $eno, $name, $type, $machineId, $now);
            if ($stmt->execute()) {
                $saved++;
            }
        }
        $stmt->close();
        return $saved;
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
