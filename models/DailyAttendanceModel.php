<?php
/**
 * Calculated daily student attendance from the fingerprint punches (`student_attendance`).
 *
 * The original punches are never modified. `student_daily_attendance` stores the calculated
 * result (calc_*) next to the effective result (final_*), which only differs after an approved
 * correction. Rows of a closed (locked) month are never recalculated.
 */
class DailyAttendanceModel extends Model {
    protected $table = 'student_daily_attendance';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/helpers/SchedulingSchema.php';
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        SchedulingSchema::ensure();
    }

    /**
     * Raw punches per student and date (read only), oldest first.
     *
     * @param list<string> $studentIds
     * @return array<string, list<array{time:string, machine:string, event:string}>> keyed "student|Y-m-d"
     */
    public function rawPunches(array $studentIds, $dateFrom, $dateTo) {
        $studentIds = array_values(array_unique(array_filter(array_map('strval', $studentIds), 'strlen')));
        $map = [];
        foreach (array_chunk($studentIds, 300) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $sql = "SELECT `student_id`, `attendance_date`, `attendance_time`, `machine_id`, `event_id`
                    FROM `student_attendance`
                    WHERE `student_id` IN ({$ph}) AND `attendance_date` BETWEEN ? AND ?
                    ORDER BY `attendance_datetime` ASC, `id` ASC";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $params = $chunk;
            $params[] = (string) $dateFrom;
            $params[] = (string) $dateTo;
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) {
                $map[$r['student_id'] . '|' . $r['attendance_date']][] = [
                    'time' => (string) $r['attendance_time'],
                    'machine' => (string) $r['machine_id'],
                    'event' => (string) $r['event_id'],
                ];
            }
            $stmt->close();
        }
        return $map;
    }

    /**
     * Stored rows for students on one date, keyed by student id.
     *
     * @param list<string> $studentIds
     * @return array<string, array<string,mixed>>
     */
    public function storedForDate(array $studentIds, $date) {
        $out = [];
        $studentIds = array_values(array_unique(array_filter(array_map('strval', $studentIds), 'strlen')));
        foreach (array_chunk($studentIds, 300) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `attendance_date` = ? AND `student_id` IN ({$ph})");
            if (!$stmt) {
                continue;
            }
            $params = array_merge([(string) $date], $chunk);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) {
                $out[$r['student_id']] = $r;
            }
            $stmt->close();
        }
        return $out;
    }

    /**
     * Calculate and store one date for a list of students. Manually corrected rows keep their final
     * status; rows of a closed month are left exactly as they were calculated at the time.
     *
     * @param list<array{student_id:string, group_id?:mixed}> $students
     * @return int number of rows written
     */
    public function syncDay(array $students, $date) {
        $today = AttendanceRules::today();
        if ((string) $date > $today || !$students) {
            return 0;
        }
        $ids = [];
        $groupOf = [];
        foreach ($students as $s) {
            $sid = (string) $s['student_id'];
            $ids[] = $sid;
            $gid = isset($s['group_id']) && $s['group_id'] !== '' && $s['group_id'] !== null ? (int) $s['group_id'] : null;
            $groupOf[$sid] = $gid;
        }
        $editable = AttendanceRules::isEditable($date);
        $stored = $this->storedForDate($ids, $date);
        $punches = $this->rawPunches($ids, $date, $date);
        $cfg = AttendanceRules::config();

        $sql = "INSERT INTO `{$this->table}`
            (`student_id`, `attendance_date`, `group_id`, `first_in`, `last_out`, `punch_count`, `duplicate_count`,
             `morning_present`, `evening_present`, `calc_status`, `calc_value`, `calc_reason`,
             `final_status`, `final_value`, `source`, `verification_status`, `manually_updated`, `calculated_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'biometric', ?, 0, NOW())
            ON DUPLICATE KEY UPDATE
              `group_id` = VALUES(`group_id`), `first_in` = VALUES(`first_in`), `last_out` = VALUES(`last_out`),
              `punch_count` = VALUES(`punch_count`), `duplicate_count` = VALUES(`duplicate_count`),
              `morning_present` = VALUES(`morning_present`), `evening_present` = VALUES(`evening_present`),
              `calc_status` = VALUES(`calc_status`), `calc_value` = VALUES(`calc_value`), `calc_reason` = VALUES(`calc_reason`),
              `final_status` = IF(`manually_updated` = 1, `final_status`, VALUES(`final_status`)),
              `final_value` = IF(`manually_updated` = 1, `final_value`, VALUES(`final_value`)),
              `verification_status` = IF(`manually_updated` = 1 OR `verification_status` = 'verified', `verification_status`, VALUES(`verification_status`)),
              `calculated_at` = NOW()";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $written = 0;
        foreach ($ids as $sid) {
            if (isset($stored[$sid]) && !$editable) {
                continue; // closed month: keep the stored result
            }
            $times = [];
            foreach ($punches[$sid . '|' . $date] ?? [] as $p) {
                $times[] = $p['time'];
            }
            $calc = AttendanceRules::calculateDay($times, $cfg);
            $verification = ($calc['status'] === AttendanceRules::STATUS_ABSENT) ? 'pending' : 'ok';
            $gid = $groupOf[$sid];
            $firstIn = $calc['first_in'] !== '' ? $calc['first_in'] : null;
            $lastOut = $calc['last_out'] !== '' ? $calc['last_out'] : null;
            $pc = (int) $calc['punch_count'];
            $dc = (int) $calc['duplicate_count'];
            $mp = $calc['morning_present'] ? 1 : 0;
            $ep = $calc['evening_present'] ? 1 : 0;
            $status = $calc['status'];
            $value = number_format((float) $calc['value'], 1, '.', '');
            $reason = mb_substr($calc['reason'], 0, 400);
            $stmt->bind_param(
                'ssissiiiissssss',
                $sid, $date, $gid, $firstIn, $lastOut, $pc, $dc, $mp, $ep, $status, $value, $reason,
                $status, $value, $verification
            );
            if ($stmt->execute()) {
                $written++;
            }
        }
        $stmt->close();
        return $written;
    }

    /**
     * Students in scope with their daily result (calculating on demand).
     *
     * @param array{department_id?:string, course_id?:string, academic_year?:string, group_id?:string, status?:string, verification?:string, search?:string} $f
     * @return list<array<string,mixed>>
     */
    public function listDay($date, array $f = []) {
        require_once BASE_PATH . '/models/StudentDeviceAttendanceModel.php';
        $dev = new StudentDeviceAttendanceModel();
        $students = $dev->listActiveStudentsForReport(
            (string) ($f['department_id'] ?? ''),
            (string) ($f['course_id'] ?? ''),
            (string) ($f['academic_year'] ?? ''),
            (string) ($f['group_id'] ?? ''),
            ''
        );
        // one row per student
        $seen = [];
        $unique = [];
        foreach ($students as $s) {
            if (!isset($seen[$s['student_id']])) {
                $seen[$s['student_id']] = true;
                $unique[] = $s;
            }
        }
        $students = $unique;
        $this->syncDay($students, $date);
        $ids = array_column($students, 'student_id');
        $stored = $this->storedForDate($ids, $date);
        $punches = $this->rawPunches($ids, $date, $date);
        $editState = AttendanceRules::editState($date);
        $search = mb_strtolower(trim((string) ($f['search'] ?? '')));

        $rows = [];
        foreach ($students as $s) {
            $sid = $s['student_id'];
            $r = $stored[$sid] ?? null;
            if ($search !== '' && mb_strpos(mb_strtolower($sid . ' ' . $s['student_name']), $search) === false) {
                continue;
            }
            $row = [
                'student_id' => $sid,
                'student_name' => $s['student_name'],
                'group_id' => $s['group_id'],
                'group_name' => $s['group_name'],
                'course_name' => $s['course_name'],
                'department_id' => $s['department_id'],
                'attendance_date' => $date,
                'first_in' => $r['first_in'] ?? null,
                'last_out' => $r['last_out'] ?? null,
                'punch_count' => (int) ($r['punch_count'] ?? 0),
                'duplicate_count' => (int) ($r['duplicate_count'] ?? 0),
                'calc_status' => $r['calc_status'] ?? AttendanceRules::STATUS_ABSENT,
                'calc_reason' => $r['calc_reason'] ?? '',
                'final_status' => $r['final_status'] ?? AttendanceRules::STATUS_ABSENT,
                'final_value' => (float) ($r['final_value'] ?? 0),
                'source' => $r['source'] ?? 'biometric',
                'verification_status' => $r['verification_status'] ?? 'ok',
                'manually_updated' => (int) ($r['manually_updated'] ?? 0),
                'punch_times' => array_column($punches[$sid . '|' . $date] ?? [], 'time'),
                'editable' => $editState['editable'],
            ];
            $row['indicator'] = self::indicator($row);
            if (!empty($f['status']) && $row['final_status'] !== $f['status']) {
                continue;
            }
            if (!empty($f['verification']) && $f['verification'] === 'pending' && $row['verification_status'] !== 'pending') {
                continue;
            }
            if (!empty($f['indicator']) && $row['indicator'] !== $f['indicator']) {
                continue;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Display indicator for a stored/calculated row.
     */
    public static function indicator(array $row) {
        if (!empty($row['manually_updated'])) {
            return 'Manually Updated';
        }
        if (($row['verification_status'] ?? '') === 'pending') {
            return 'Pending Verification';
        }
        switch ($row['final_status'] ?? '') {
            case AttendanceRules::STATUS_FULL:
                return 'Present';
            case AttendanceRules::STATUS_HALF:
                return 'Half Day';
            default:
                return 'Absent';
        }
    }

    /**
     * One student's result for a date (calculated on demand).
     *
     * @return array<string,mixed>|null
     */
    public function getStudentDay($studentId, $date, $groupId = null) {
        $this->syncDay([['student_id' => (string) $studentId, 'group_id' => $groupId]], $date);
        $rows = $this->storedForDate([(string) $studentId], $date);
        return $rows[(string) $studentId] ?? null;
    }

    /**
     * Month result per student: counts and day values. Closed or corrected days use the stored
     * result; other days are calculated from the punches without being stored.
     *
     * @param list<array<string,mixed>> $students rows from listActiveStudentsForReport
     * @return array{working_days:list<string>, rows:list<array<string,mixed>>}
     */
    public function monthSummary($month, array $students, $departmentId = '') {
        require_once BASE_PATH . '/helpers/SriLankaPublicHolidays.php';
        require_once BASE_PATH . '/models/StudentAttendanceHolidayModel.php';
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            return ['working_days' => [], 'rows' => []];
        }
        $today = AttendanceRules::today();
        $first = $month . '-01';
        $last = date('Y-m-t', strtotime($first));
        $to = min($last, $today);
        $leave = (new StudentAttendanceHolidayModel())->mapForScope($first, $last, (string) $departmentId, '');
        $days = [];
        foreach (SriLankaPublicHolidays::weekdayTeachingDaysInMonth($month) as $d) {
            if ($d['date'] <= $to && !isset($leave[$d['date']])) {
                $days[] = $d['date'];
            }
        }
        $ids = array_values(array_unique(array_column($students, 'student_id')));
        $punches = $to >= $first ? $this->rawPunches($ids, $first, $to) : [];
        $stored = [];
        if ($ids) {
            foreach (array_chunk($ids, 300) as $chunk) {
                $ph = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `attendance_date` BETWEEN ? AND ? AND `student_id` IN ({$ph})");
                if (!$stmt) {
                    continue;
                }
                $params = array_merge([$first, $last], $chunk);
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($res && ($r = $res->fetch_assoc())) {
                    $stored[$r['student_id'] . '|' . $r['attendance_date']] = $r;
                }
                $stmt->close();
            }
        }
        $cfg = AttendanceRules::config();
        $seen = [];
        $rows = [];
        foreach ($students as $s) {
            $sid = $s['student_id'];
            if (isset($seen[$sid])) {
                continue;
            }
            $seen[$sid] = true;
            $full = $half = $absent = 0;
            $total = 0.0;
            $dayMap = [];
            foreach ($days as $d) {
                $key = $sid . '|' . $d;
                if (isset($stored[$key]) && (!AttendanceRules::isEditable($d) || (int) $stored[$key]['manually_updated'] === 1)) {
                    $status = $stored[$key]['final_status'];
                    $value = (float) $stored[$key]['final_value'];
                } else {
                    $times = array_column($punches[$key] ?? [], 'time');
                    $calc = AttendanceRules::calculateDay($times, $cfg);
                    $status = $calc['status'];
                    $value = $calc['value'];
                }
                if ($status === AttendanceRules::STATUS_FULL) {
                    $full++;
                } elseif ($status === AttendanceRules::STATUS_HALF) {
                    $half++;
                } else {
                    $absent++;
                }
                $total += $value;
                $dayMap[$d] = $status;
            }
            $wd = count($days);
            $rows[] = [
                'student_id' => $sid,
                'student_name' => $s['student_name'],
                'group_name' => $s['group_name'],
                'course_name' => $s['course_name'],
                'full_days' => $full,
                'half_days' => $half,
                'absent_days' => $absent,
                'working_days' => $wd,
                'attendance_days' => $total,
                'percentage' => $wd > 0 ? round($total * 100 / $wd, 1) : 0.0,
                'days' => $dayMap,
            ];
        }
        return ['working_days' => $days, 'rows' => $rows];
    }

    /**
     * Read-only listing of the original fingerprint punches with the rule classification of each.
     *
     * @return list<array<string,mixed>>
     */
    public function biometricRecords($dateFrom, $dateTo, $departmentId = '', $search = '', $limit = 600) {
        $cfg = AttendanceRules::config();
        $sql = "SELECT sa.`id`, sa.`student_id`, sa.`student_name`, sa.`attendance_date`, sa.`attendance_time`, sa.`machine_id`, sa.`event_id`,
                       s.`student_fullname`
                FROM `student_attendance` sa
                LEFT JOIN `student` s ON s.`student_id` = sa.`student_id`
                WHERE sa.`attendance_date` BETWEEN ? AND ? AND sa.`student_id` <> ''";
        $types = 'ss';
        $params = [(string) $dateFrom, (string) $dateTo];
        if ($departmentId !== '' && $departmentId !== null) {
            $sql .= " AND sa.`student_id` IN (SELECT se.`student_id` FROM `student_enroll` se INNER JOIN `course` c ON c.`course_id` = se.`course_id` WHERE c.`department_id` = ?)";
            $types .= 's';
            $params[] = (string) $departmentId;
        }
        $search = trim((string) $search);
        if ($search !== '') {
            $sql .= " AND (sa.`student_id` LIKE ? OR sa.`student_name` LIKE ? OR s.`student_fullname` LIKE ?)";
            $like = '%' . $search . '%';
            $types .= 'sss';
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY sa.`attendance_date` DESC, sa.`student_id` ASC, sa.`attendance_time` ASC LIMIT ' . (int) $limit;
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        $last = [];
        $mStart = $cfg['morning_start'] . ':00';
        $mEnd = $cfg['morning_end'] . ':59';
        $eStart = $cfg['evening_start'] . ':00';
        $vFrom = $cfg['valid_from'] . ':00';
        $vTo = $cfg['valid_to'] . ':59';
        $dup = (int) $cfg['duplicate_seconds'];
        // rows are date DESC but time ASC inside a student/day, so duplicate detection is per student/day group
        while ($res && ($r = $res->fetch_assoc())) {
            $t = AttendanceRules::hms($r['attendance_time']);
            $key = $r['student_id'] . '|' . $r['attendance_date'];
            $sec = ((int) substr($t, 0, 2)) * 3600 + ((int) substr($t, 3, 2)) * 60 + (int) substr($t, 6, 2);
            $class = 'Other time';
            $valid = true;
            if ($t < $vFrom || $t > $vTo) {
                $class = 'Outside valid range';
                $valid = false;
            } elseif ($t >= $mStart && $t <= $mEnd) {
                $class = 'Morning window (IN)';
            } elseif ($t >= $eStart) {
                $class = 'Evening (OUT)';
            }
            $isDup = false;
            if ($valid && isset($last[$key]) && ($sec - $last[$key]) <= $dup) {
                $isDup = true;
            }
            if ($valid && !$isDup) {
                $last[$key] = $sec;
            }
            $r['classification'] = $isDup ? 'Duplicate scan (ignored)' : $class;
            $r['counted'] = $valid && !$isDup;
            $r['name'] = trim((string) $r['student_fullname']) !== '' ? $r['student_fullname'] : $r['student_name'];
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Counts for the dashboard cards (one date, scope).
     *
     * @param list<array<string,mixed>> $rows result of listDay()
     * @return array<string,int>
     */
    public static function summarise(array $rows) {
        $sum = ['total' => count($rows), 'full' => 0, 'half' => 0, 'absent' => 0, 'pending' => 0, 'manual' => 0];
        foreach ($rows as $r) {
            if ($r['final_status'] === AttendanceRules::STATUS_FULL) {
                $sum['full']++;
            } elseif ($r['final_status'] === AttendanceRules::STATUS_HALF) {
                $sum['half']++;
            } else {
                $sum['absent']++;
            }
            if ($r['verification_status'] === 'pending') {
                $sum['pending']++;
            }
            if (!empty($r['manually_updated'])) {
                $sum['manual']++;
            }
        }
        return $sum;
    }
}
