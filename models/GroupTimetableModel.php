<?php
/**
 * Weekly class timetable for one student group.
 * Uses the existing group_timetable table: weekday, period, lecturer, room, and dates.
 */
class GroupTimetableModel extends Model {
    protected $table = 'group_timetable';

    protected function getPrimaryKey() {
        return 'timetable_id';
    }

    public static function dayNames() {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    public static function periods() {
        return [
            'P1' => 'Period 1',
            'P2' => 'Period 2',
            'P3' => 'Period 3',
            'P4' => 'Period 4',
        ];
    }

    public function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `group_timetable` (
            `timetable_id` INT NOT NULL AUTO_INCREMENT,
            `group_id` INT NOT NULL,
            `module_id` VARCHAR(64) NULL DEFAULT NULL,
            `subject` VARCHAR(120) NULL DEFAULT NULL,
            `staff_id` VARCHAR(64) NOT NULL,
            `weekday` TINYINT(1) NOT NULL,
            `period` ENUM('P1','P2','P3','P4') NOT NULL,
            `classroom` VARCHAR(64) NULL DEFAULT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`timetable_id`),
            KEY `idx_group_slot` (`group_id`, `weekday`, `period`),
            KEY `idx_staff_slot` (`staff_id`, `weekday`, `period`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->query($sql);
        $conn = $this->db->getConnection();
        $subject = $conn->query("SHOW COLUMNS FROM `group_timetable` LIKE 'subject'");
        if (!$subject || $subject->num_rows === 0) {
            $conn->query("ALTER TABLE `group_timetable` ADD COLUMN `subject` VARCHAR(120) NULL DEFAULT NULL AFTER `module_id`");
        }
        $module = $conn->query("SHOW COLUMNS FROM `group_timetable` LIKE 'module_id'");
        $moduleRow = $module ? $module->fetch_assoc() : null;
        if ($moduleRow && strtoupper((string) $moduleRow['Null']) === 'NO') {
            $conn->query("ALTER TABLE `group_timetable` MODIFY `module_id` VARCHAR(64) NULL DEFAULT NULL");
        }
        require_once BASE_PATH . '/helpers/SchedulingSchema.php';
        SchedulingSchema::ensure();
    }

    public function listByGroup($groupId) {
        $this->ensureTable();
        $sql = "SELECT t.*, s.staff_name,
                    (SELECT m.module_name FROM `module` m WHERE m.module_id = t.module_id ORDER BY m.course_id LIMIT 1) AS module_name
                FROM `group_timetable` t
                LEFT JOIN `staff` s ON s.staff_id = t.staff_id
                WHERE t.group_id = ? AND t.active = 1
                ORDER BY t.weekday ASC, t.period ASC, t.start_date ASC";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $groupId;
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Active class slots of one lecturer / instructor that have not yet ended.
     */
    public function listByStaff($staffId) {
        $this->ensureTable();
        $staffId = trim((string) $staffId);
        if ($staffId === '') {
            return [];
        }
        $sql = "SELECT t.timetable_id, t.group_id, t.weekday, t.period, t.classroom, t.subject, t.start_date, t.end_date,
                    g.name AS group_name,
                    (SELECT m.module_name FROM `module` m WHERE m.module_id = t.module_id ORDER BY m.course_id LIMIT 1) AS module_name
                FROM `group_timetable` t
                LEFT JOIN `groups` g ON g.id = t.group_id
                WHERE t.staff_id = ? AND t.active = 1 AND t.publish_status = 'published' AND t.end_date >= CURDATE()
                ORDER BY t.weekday ASC, t.period ASC, t.start_date ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $staffId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function findSlot($id, $groupId) {
        $this->ensureTable();
        $sql = "SELECT * FROM `group_timetable` WHERE `timetable_id` = ? AND `group_id` = ? AND `active` = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $id = (int) $id;
        $groupId = (int) $groupId;
        $stmt->bind_param('ii', $id, $groupId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function listStaff($departmentId) {
        $sql = "SELECT `staff_id`, `staff_name` FROM `staff`";
        $departmentId = trim((string) $departmentId);
        if ($departmentId !== '') {
            $sql .= " WHERE `department_id` = ?";
        }
        $sql .= " ORDER BY `staff_name` ASC, `staff_id` ASC";
        if ($departmentId !== '') {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $departmentId);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function groupOverlaps($groupId, $weekday, $period, $startDate, $endDate, $excludeId) {
        $sql = "SELECT `timetable_id` FROM `group_timetable`
                WHERE `group_id` = ? AND `weekday` = ? AND `period` = ? AND `active` = 1 AND `timetable_id` <> ?
                  AND `start_date` <= ? AND `end_date` >= ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $groupId;
        $weekday = (int) $weekday;
        $excludeId = (int) $excludeId;
        $stmt->bind_param('iisiss', $groupId, $weekday, $period, $excludeId, $endDate, $startDate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? true : false;
    }

    public function staffOverlap($staffId, $weekday, $period, $startDate, $endDate, $excludeId) {
        $sql = "SELECT g.name AS group_name, t.period, t.weekday
                FROM `group_timetable` t
                INNER JOIN `groups` g ON g.id = t.group_id
                WHERE t.staff_id = ? AND t.weekday = ? AND t.period = ? AND t.active = 1 AND t.timetable_id <> ?
                  AND t.start_date <= ? AND t.end_date >= ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $weekday = (int) $weekday;
        $excludeId = (int) $excludeId;
        $stmt->bind_param('sisiss', $staffId, $weekday, $period, $excludeId, $endDate, $startDate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public static function sessionTypes() {
        return ['Theory' => 'Theory', 'Practical' => 'Practical'];
    }

    public static function venueTypes() {
        return ['Classroom' => 'Classroom', 'Workshop' => 'Workshop', 'Laboratory' => 'Laboratory', 'Other' => 'Other'];
    }

    /**
     * Effective start/end (HH:MM:SS) of a slot: explicit times, otherwise the configured period times.
     *
     * @return array{0:string,1:string}
     */
    public static function effectiveTimes(array $slot) {
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        return AttendanceRules::periodTimes(
            (string) ($slot['period'] ?? 'P1'),
            $slot['start_time'] ?? null,
            $slot['end_time'] ?? null
        );
    }

    /**
     * Scheduled hours for a slot.
     */
    public static function scheduledHours(array $slot) {
        list($s, $e) = self::effectiveTimes($slot);
        $a = explode(':', $s);
        $b = explode(':', $e);
        $mins = ((int) $b[0] * 60 + (int) $b[1]) - ((int) $a[0] * 60 + (int) $a[1]);
        return $mins > 0 ? round($mins / 60, 2) : 0.0;
    }

    /**
     * All timetable conflicts for a proposed slot, by effective time overlap.
     * Checks: same group (including the same module), same instructor, same classroom/venue.
     *
     * @param array<string,mixed> $slot group_id, staff_id, classroom, weekday, period, start_time, end_time, start_date, end_date, module_id
     * @return list<string>
     */
    public function findConflicts(array $slot, $excludeId = 0) {
        $this->ensureTable();
        $sql = "SELECT t.*, g.name AS group_name, s.staff_name,
                    (SELECT m.module_name FROM `module` m WHERE m.module_id = t.module_id ORDER BY m.course_id LIMIT 1) AS module_name
                FROM `group_timetable` t
                LEFT JOIN `groups` g ON g.id = t.group_id
                LEFT JOIN `staff` s ON s.staff_id = t.staff_id
                WHERE t.active = 1 AND t.weekday = ? AND t.timetable_id <> ?
                  AND t.start_date <= ? AND t.end_date >= ?
                  AND (t.group_id = ? OR t.staff_id = ? OR (? <> '' AND t.classroom IS NOT NULL AND LOWER(t.classroom) = LOWER(?)))";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['Could not check the timetable for conflicts.'];
        }
        $weekday = (int) $slot['weekday'];
        $excludeId = (int) $excludeId;
        $endDate = (string) $slot['end_date'];
        $startDate = (string) $slot['start_date'];
        $groupId = (int) $slot['group_id'];
        $staffId = (string) $slot['staff_id'];
        $room = trim((string) ($slot['classroom'] ?? ''));
        $stmt->bind_param('iississs', $weekday, $excludeId, $endDate, $startDate, $groupId, $staffId, $room, $room);
        $stmt->execute();
        $result = $stmt->get_result();

        list($ns, $ne) = self::effectiveTimes($slot);
        $messages = [];
        while ($result && ($row = $result->fetch_assoc())) {
            list($os, $oe) = self::effectiveTimes($row);
            if (!($ns < $oe && $os < $ne)) {
                continue;
            }
            $when = substr($os, 0, 5) . '-' . substr($oe, 0, 5);
            $title = trim((string) ($row['module_name'] ?? '')) !== '' ? $row['module_name'] : (trim((string) ($row['subject'] ?? '')) !== '' ? $row['subject'] : 'another class');
            if ((int) $row['group_id'] === $groupId) {
                if ((string) ($row['module_id'] ?? '') !== '' && (string) ($row['module_id'] ?? '') === (string) ($slot['module_id'] ?? '')) {
                    $messages[] = 'This group already has the same module (' . $title . ') at ' . $when . ' on that day (overlapping module).';
                } else {
                    $messages[] = 'This group already has ' . $title . ' at ' . $when . ' on that day.';
                }
            }
            if ((string) $row['staff_id'] === $staffId) {
                $messages[] = ($row['staff_name'] ?: $staffId) . ' is already teaching ' . ($row['group_name'] ?: 'another group') . ' at ' . $when . '.';
            }
            if ($room !== '' && strcasecmp(trim((string) $row['classroom']), $room) === 0) {
                $messages[] = 'Classroom ' . $room . ' is already used by ' . ($row['group_name'] ?: 'another group') . ' at ' . $when . '.';
            }
        }
        return array_values(array_unique($messages));
    }

    public function saveSlot(array $data, $slotId = 0) {
        $this->ensureTable();
        $slotId = (int) $slotId;
        $startTime = ($data['start_time'] ?? '') !== '' ? $data['start_time'] : null;
        $endTime = ($data['end_time'] ?? '') !== '' ? $data['end_time'] : null;
        $sessionType = ($data['session_type'] ?? 'Theory') === 'Practical' ? 'Practical' : 'Theory';
        $venueType = ($data['venue_type'] ?? '') !== '' ? $data['venue_type'] : null;
        if ($slotId > 0) {
            $sql = "UPDATE `group_timetable`
                    SET `weekday` = ?, `period` = ?, `module_id` = ?, `subject` = ?, `staff_id` = ?,
                        `classroom` = ?, `start_date` = ?, `end_date` = ?, `start_time` = ?, `end_time` = ?,
                        `session_type` = ?, `venue_type` = ?, `active` = 1
                    WHERE `timetable_id` = ? AND `group_id` = ?";
            $stmt = $this->db->prepare($sql);
            $weekday = (int) $data['weekday'];
            $groupId = (int) $data['group_id'];
            $stmt->bind_param(
                'isssssssssssii',
                $weekday,
                $data['period'],
                $data['module_id'],
                $data['subject'],
                $data['staff_id'],
                $data['classroom'],
                $data['start_date'],
                $data['end_date'],
                $startTime,
                $endTime,
                $sessionType,
                $venueType,
                $slotId,
                $groupId
            );
            return $stmt->execute();
        }
        $sql = "INSERT INTO `group_timetable`
                (`group_id`, `weekday`, `period`, `module_id`, `subject`, `staff_id`, `classroom`, `start_date`, `end_date`,
                 `start_time`, `end_time`, `session_type`, `venue_type`, `publish_status`, `active`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', 1)";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $data['group_id'];
        $weekday = (int) $data['weekday'];
        $stmt->bind_param(
            'iisssssssssss',
            $groupId,
            $weekday,
            $data['period'],
            $data['module_id'],
            $data['subject'],
            $data['staff_id'],
            $data['classroom'],
            $data['start_date'],
            $data['end_date'],
            $startTime,
            $endTime,
            $sessionType,
            $venueType
        );
        if (!$stmt->execute()) {
            return false;
        }
        return (int) $this->db->lastInsertId();
    }

    /**
     * Publish every draft class of a group. Returns the number of classes published.
     */
    public function publishGroup($groupId, $by = '') {
        $this->ensureTable();
        $sql = "UPDATE `group_timetable` SET `publish_status` = 'published', `published_by` = ?, `published_at` = NOW()
                WHERE `group_id` = ? AND `active` = 1 AND `publish_status` <> 'published'";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $groupId;
        $by = (string) $by;
        $stmt->bind_param('si', $by, $groupId);
        $stmt->execute();
        return (int) $stmt->affected_rows;
    }

    /**
     * Move a published class back to draft (hides it from students; not used for attendance or diary).
     */
    public function unpublishSlot($slotId, $groupId) {
        $this->ensureTable();
        $stmt = $this->db->prepare("UPDATE `group_timetable` SET `publish_status` = 'draft', `published_at` = NULL
            WHERE `timetable_id` = ? AND `group_id` = ? AND `active` = 1");
        $slotId = (int) $slotId;
        $groupId = (int) $groupId;
        $stmt->bind_param('ii', $slotId, $groupId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Published sessions occurring on a calendar date (the basis of class attendance and the instructor diary).
     *
     * @param array{group_id?:int, staff_id?:string, department_id?:string, group_ids?:list<int>} $filters
     * @return list<array<string,mixed>> each row has start_clock, end_clock, scheduled_hours, session_date
     */
    public function sessionsForDate($date, array $filters = []) {
        $this->ensureTable();
        $dt = DateTime::createFromFormat('Y-m-d', (string) $date);
        if (!$dt || $dt->format('Y-m-d') !== (string) $date) {
            return [];
        }
        $weekday = (int) $dt->format('N');
        $sql = "SELECT t.*, g.name AS group_name, g.group_code, g.course_id, s.staff_name, c.department_id,
                    (SELECT m.module_name FROM `module` m WHERE m.module_id = t.module_id ORDER BY m.course_id LIMIT 1) AS module_name
                FROM `group_timetable` t
                INNER JOIN `groups` g ON g.id = t.group_id
                LEFT JOIN `course` c ON c.course_id = g.course_id
                LEFT JOIN `staff` s ON s.staff_id = t.staff_id
                WHERE t.active = 1 AND t.publish_status = 'published' AND t.weekday = ?
                  AND t.start_date <= ? AND t.end_date >= ?";
        $types = 'iss';
        $params = [$weekday, $date, $date];
        if (!empty($filters['group_id'])) {
            $sql .= ' AND t.group_id = ?';
            $types .= 'i';
            $params[] = (int) $filters['group_id'];
        }
        if (!empty($filters['staff_id'])) {
            $sql .= ' AND t.staff_id = ?';
            $types .= 's';
            $params[] = (string) $filters['staff_id'];
        }
        if (!empty($filters['department_id'])) {
            $sql .= ' AND c.department_id = ?';
            $types .= 's';
            $params[] = (string) $filters['department_id'];
        }
        if (!empty($filters['group_ids']) && is_array($filters['group_ids'])) {
            $ids = array_map('intval', $filters['group_ids']);
            $sql .= ' AND t.group_id IN (' . implode(',', $ids) . ')';
        }
        $sql .= ' ORDER BY g.name ASC, t.start_time ASC, t.period ASC';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            list($s, $e) = self::effectiveTimes($row);
            $row['start_clock'] = $s;
            $row['end_clock'] = $e;
            $row['scheduled_hours'] = self::scheduledHours($row);
            $row['session_date'] = $date;
            $rows[] = $row;
        }
        usort($rows, static function ($a, $b) {
            return strcmp($a['start_clock'], $b['start_clock']) ?: strcmp((string) $a['group_name'], (string) $b['group_name']);
        });
        return $rows;
    }

    /**
     * Published sessions of one group in a date range, keyed by date.
     */
    public function sessionsForGroupRange($groupId, $fromDate, $toDate) {
        $out = [];
        $d = new DateTime($fromDate);
        $end = new DateTime($toDate);
        $guard = 0;
        while ($d <= $end && $guard++ < 400) {
            $ymd = $d->format('Y-m-d');
            $rows = $this->sessionsForDate($ymd, ['group_id' => (int) $groupId]);
            if ($rows) {
                $out[$ymd] = $rows;
            }
            $d->modify('+1 day');
        }
        return $out;
    }

    public function deleteSlot($id, $groupId) {
        $this->ensureTable();
        $sql = "UPDATE `group_timetable` SET `active` = 0 WHERE `timetable_id` = ? AND `group_id` = ? AND `active` = 1";
        $stmt = $this->db->prepare($sql);
        $id = (int) $id;
        $groupId = (int) $groupId;
        $stmt->bind_param('ii', $id, $groupId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
