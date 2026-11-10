<?php
/**
 * Class (session) attendance. A class session comes from the PUBLISHED group timetable; a student's
 * class attendance follows the effective daily attendance (biometric result or approved correction).
 */
class ClassAttendanceModel extends Model {
    protected $table = 'class_attendance';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/helpers/SchedulingSchema.php';
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        require_once BASE_PATH . '/models/DailyAttendanceModel.php';
        SchedulingSchema::ensure();
    }

    /**
     * Active members of a group.
     *
     * @return list<array{student_id:string, student_name:string, group_id:int}>
     */
    public function groupMembers($groupId) {
        $sql = "SELECT s.`student_id`, COALESCE(NULLIF(TRIM(s.`student_fullname`), ''), NULLIF(TRIM(s.`student_ininame`), ''), s.`student_id`) AS student_name
                FROM `group_students` gs
                INNER JOIN `student` s ON s.`student_id` = gs.`student_id`
                WHERE gs.`group_id` = ? AND gs.`status` = 'active'
                ORDER BY s.`student_id` ASC";
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $groupId;
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $rows[] = ['student_id' => (string) $r['student_id'], 'student_name' => (string) $r['student_name'], 'group_id' => $groupId];
        }
        return $rows;
    }

    /**
     * Class attendance for every student of a group on one date, one block per published session.
     *
     * @return array{sessions:list<array<string,mixed>>, members:list<array<string,mixed>>, blocks:list<array<string,mixed>>}
     */
    public function forGroupDate($groupId, $date) {
        $timetable = new GroupTimetableModel();
        $daily = new DailyAttendanceModel();
        $sessions = $timetable->sessionsForDate($date, ['group_id' => (int) $groupId]);
        $members = $this->groupMembers($groupId);
        $blocks = [];
        if (!$sessions || !$members || $date > AttendanceRules::today()) {
            return ['sessions' => $sessions, 'members' => $members, 'blocks' => []];
        }
        $daily->syncDay($members, $date);
        $ids = array_column($members, 'student_id');
        $day = $daily->storedForDate($ids, $date);
        $punches = $daily->rawPunches($ids, $date, $date);
        $correctionState = $this->latestCorrectionStatus($ids, $date);

        $up = $this->db->prepare("INSERT INTO `{$this->table}`
            (`timetable_id`, `session_date`, `group_id`, `student_id`, `status`, `value`, `source`, `verification_status`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `value` = VALUES(`value`), `source` = VALUES(`source`),
                `verification_status` = VALUES(`verification_status`), `updated_at` = NOW()");
        $editable = AttendanceRules::isEditable($date);
        foreach ($sessions as $session) {
            $rows = [];
            foreach ($members as $m) {
                $sid = $m['student_id'];
                $d = $day[$sid] ?? null;
                $status = $d['final_status'] ?? AttendanceRules::STATUS_ABSENT;
                $value = (float) ($d['final_value'] ?? 0);
                $source = $d['source'] ?? 'biometric';
                $verification = $d['verification_status'] ?? 'pending';
                $manual = (int) ($d['manually_updated'] ?? 0);
                if ($up) {
                    $tid = (int) $session['timetable_id'];
                    $gid = (int) $groupId;
                    $valStr = number_format($value, 1, '.', '');
                    $up->bind_param('isisssss', $tid, $date, $gid, $sid, $status, $valStr, $source, $verification);
                    if ($editable) {
                        $up->execute();
                    } else {
                        // closed month: only create the row when missing (INSERT IGNORE semantics)
                        $chk = $this->db->prepare("SELECT 1 FROM `{$this->table}` WHERE `timetable_id` = ? AND `session_date` = ? AND `student_id` = ? LIMIT 1");
                        $chk->bind_param('iss', $tid, $date, $sid);
                        $chk->execute();
                        if ($chk->get_result()->num_rows === 0) {
                            $up->execute();
                        }
                        $chk->close();
                    }
                }
                $row = [
                    'student_id' => $sid,
                    'student_name' => $m['student_name'],
                    'first_in' => $d['first_in'] ?? null,
                    'last_out' => $d['last_out'] ?? null,
                    'punch_count' => (int) ($d['punch_count'] ?? 0),
                    'calc_status' => $d['calc_status'] ?? AttendanceRules::STATUS_ABSENT,
                    'calc_reason' => $d['calc_reason'] ?? '',
                    'final_status' => $status,
                    'verification_status' => $verification,
                    'manually_updated' => $manual,
                    'source' => $source,
                    'correction_status' => $correctionState[$sid] ?? '',
                ];
                $row['indicator'] = DailyAttendanceModel::indicator($row);
                $rows[] = $row;
            }
            $blocks[] = ['session' => $session, 'rows' => $rows];
        }
        if ($up) {
            $up->close();
        }
        return ['sessions' => $sessions, 'members' => $members, 'blocks' => $blocks];
    }

    /**
     * Latest correction approval state per student for one date: pending / approved / rejected.
     *
     * @param list<string> $ids
     * @return array<string,string>
     */
    public function latestCorrectionStatus(array $ids, $date) {
        $out = [];
        $ids = array_values(array_unique(array_filter($ids, 'strlen')));
        foreach (array_chunk($ids, 300) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = $this->db->prepare("SELECT `student_id`, `approval_status` FROM `attendance_corrections`
                WHERE `attendance_date` = ? AND `student_id` IN ({$ph}) ORDER BY `id` ASC");
            if (!$stmt) {
                continue;
            }
            $params = array_merge([(string) $date], $chunk);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) {
                $out[$r['student_id']] = $r['approval_status'];
            }
            $stmt->close();
        }
        return $out;
    }

    /**
     * Class attendance summary of a student over a date range (for the student portal / reports).
     *
     * @return list<array<string,mixed>>
     */
    public function studentHistory($studentId, $dateFrom, $dateTo) {
        $sql = "SELECT ca.*, t.session_type, t.period, t.start_time, t.end_time, t.classroom,
                       (SELECT m.module_name FROM `module` m WHERE m.module_id = t.module_id ORDER BY m.course_id LIMIT 1) AS module_name, t.subject
                FROM `{$this->table}` ca
                LEFT JOIN `group_timetable` t ON t.timetable_id = ca.timetable_id
                WHERE ca.student_id = ? AND ca.session_date BETWEEN ? AND ?
                ORDER BY ca.session_date DESC, t.start_time ASC, t.period ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $studentId, $dateFrom, $dateTo);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $rows[] = $r;
        }
        return $rows;
    }
}
