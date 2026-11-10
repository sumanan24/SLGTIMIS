<?php
/**
 * Training programmes (practical / industrial / workshop / short courses) scheduled for groups or
 * individual students. Separate from the weekly class timetable and from the Circuit Program (staff).
 */
class TrainingProgrammeModel extends Model {
    protected $table = 'training_programmes';

    protected function getPrimaryKey() {
        return 'id';
    }

    public static function types() {
        return ['Practical' => 'Practical training', 'Industrial' => 'Industrial / on-the-job', 'Workshop' => 'Workshop', 'Short Course' => 'Short course', 'Other' => 'Other'];
    }

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/helpers/SchedulingSchema.php';
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/DailyAttendanceModel.php';
        SchedulingSchema::ensure();
    }

    /**
     * Create a programme with its schedule rows and participants in one transaction.
     *
     * @param array<string,mixed> $data
     * @param list<array{date:string,start:string,end:string,location:string}> $schedule
     * @param list<string> $studentIds individually selected students
     * @param list<int> $groupIds whole groups (active members are added)
     * @return array{ok:bool, message:string, id?:int}
     */
    public function createProgramme(array $data, array $schedule, array $studentIds, array $groupIds) {
        if (!$schedule) {
            return ['ok' => false, 'message' => 'Add at least one session date.'];
        }
        $participants = [];
        foreach ($groupIds as $gid) {
            foreach ($this->groupMemberIds((int) $gid) as $sid) {
                $participants[$sid] = ['group' => (int) $gid, 'source' => 'group'];
            }
        }
        foreach ($studentIds as $sid) {
            $sid = trim((string) $sid);
            if ($sid !== '' && !isset($participants[$sid])) {
                $participants[$sid] = ['group' => null, 'source' => 'individual'];
            }
        }
        if (!$participants) {
            return ['ok' => false, 'message' => 'Select at least one group or student.'];
        }
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO `{$this->table}`
                (`title`, `description`, `department_id`, `course_id`, `batch`, `academic_year`, `training_type`, `instructor_staff_id`,
                 `learning_outcomes`, `linked_timetable_id`, `status`, `created_by`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)");
            $link = !empty($data['linked_timetable_id']) ? (int) $data['linked_timetable_id'] : null;
            $stmt->bind_param(
                'sssssssssis',
                $data['title'], $data['description'], $data['department_id'], $data['course_id'], $data['batch'],
                $data['academic_year'], $data['training_type'], $data['instructor_staff_id'], $data['learning_outcomes'],
                $link, $data['created_by']
            );
            if (!$stmt->execute()) {
                throw new RuntimeException('Could not create the programme.');
            }
            $pid = (int) $conn->insert_id;
            $stmt->close();

            $s = $conn->prepare("INSERT IGNORE INTO `programme_schedules` (`programme_id`, `session_date`, `start_time`, `end_time`, `location`) VALUES (?, ?, ?, ?, ?)");
            foreach ($schedule as $row) {
                $s->bind_param('issss', $pid, $row['date'], $row['start'], $row['end'], $row['location']);
                $s->execute();
            }
            $s->close();

            $p = $conn->prepare("INSERT IGNORE INTO `programme_participants` (`programme_id`, `student_id`, `group_id`, `source`) VALUES (?, ?, ?, ?)");
            foreach ($participants as $sid => $info) {
                $g = $info['group'];
                $src = $info['source'];
                $sidStr = (string) $sid;
                $p->bind_param('isis', $pid, $sidStr, $g, $src);
                $p->execute();
            }
            $p->close();
            $conn->commit();
            return ['ok' => true, 'message' => 'Programme saved as draft with ' . count($participants) . ' participant(s).', 'id' => $pid];
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('TrainingProgrammeModel::createProgramme ' . $e->getMessage());
            return ['ok' => false, 'message' => 'The programme could not be saved.'];
        }
    }

    /**
     * @return list<string>
     */
    public function groupMemberIds($groupId) {
        $stmt = $this->db->prepare("SELECT `student_id` FROM `group_students` WHERE `group_id` = ? AND `status` = 'active'");
        $groupId = (int) $groupId;
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $ids[] = (string) $r['student_id'];
        }
        return $ids;
    }

    /**
     * @param array{department_id?:string, status?:string, type?:string, date_from?:string, date_to?:string, instructor?:string, student_id?:string, published_only?:bool} $f
     * @return list<array<string,mixed>>
     */
    public function listProgrammes(array $f = []) {
        $sql = "SELECT p.*, d.department_name, s.staff_name AS instructor_name,
                    (SELECT COUNT(*) FROM `programme_participants` pp WHERE pp.programme_id = p.id) AS participant_count,
                    (SELECT MIN(ps.session_date) FROM `programme_schedules` ps WHERE ps.programme_id = p.id) AS first_date,
                    (SELECT MAX(ps.session_date) FROM `programme_schedules` ps WHERE ps.programme_id = p.id) AS last_date,
                    (SELECT COUNT(*) FROM `programme_schedules` ps WHERE ps.programme_id = p.id) AS session_count
                FROM `{$this->table}` p
                LEFT JOIN `department` d ON d.department_id = p.department_id
                LEFT JOIN `staff` s ON s.staff_id = p.instructor_staff_id
                WHERE 1=1";
        $types = '';
        $params = [];
        if (!empty($f['department_id'])) {
            $sql .= ' AND p.department_id = ?';
            $types .= 's';
            $params[] = $f['department_id'];
        }
        if (!empty($f['status'])) {
            $sql .= ' AND p.status = ?';
            $types .= 's';
            $params[] = $f['status'];
        }
        if (!empty($f['published_only'])) {
            $sql .= " AND p.status = 'published'";
        }
        if (!empty($f['type'])) {
            $sql .= ' AND p.training_type = ?';
            $types .= 's';
            $params[] = $f['type'];
        }
        if (!empty($f['instructor'])) {
            $sql .= ' AND p.instructor_staff_id = ?';
            $types .= 's';
            $params[] = $f['instructor'];
        }
        if (!empty($f['student_id'])) {
            $sql .= ' AND p.id IN (SELECT programme_id FROM programme_participants WHERE student_id = ?)';
            $types .= 's';
            $params[] = $f['student_id'];
        }
        if (!empty($f['date_from'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM programme_schedules x WHERE x.programme_id = p.id AND x.session_date >= ?)';
            $types .= 's';
            $params[] = $f['date_from'];
        }
        if (!empty($f['date_to'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM programme_schedules x WHERE x.programme_id = p.id AND x.session_date <= ?)';
            $types .= 's';
            $params[] = $f['date_to'];
        }
        $sql .= ' ORDER BY first_date DESC, p.id DESC';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $rows[] = $r;
        }
        return $rows;
    }

    public function getProgramme($id) {
        $stmt = $this->db->prepare("SELECT p.*, d.department_name, s.staff_name AS instructor_name, c.course_name
            FROM `{$this->table}` p
            LEFT JOIN `department` d ON d.department_id = p.department_id
            LEFT JOIN `staff` s ON s.staff_id = p.instructor_staff_id
            LEFT JOIN `course` c ON c.course_id = p.course_id
            WHERE p.id = ? LIMIT 1");
        $id = (int) $id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function schedules($programmeId) {
        $stmt = $this->db->prepare("SELECT * FROM `programme_schedules` WHERE `programme_id` = ? ORDER BY `session_date`, `start_time`");
        $programmeId = (int) $programmeId;
        $stmt->bind_param('i', $programmeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $rows[] = $r;
        }
        return $rows;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function participants($programmeId) {
        $stmt = $this->db->prepare("SELECT pp.*, COALESCE(NULLIF(TRIM(s.student_fullname), ''), NULLIF(TRIM(s.student_ininame), ''), pp.student_id) AS student_name,
                g.name AS group_name
            FROM `programme_participants` pp
            LEFT JOIN `student` s ON s.student_id = pp.student_id
            LEFT JOIN `groups` g ON g.id = pp.group_id
            WHERE pp.programme_id = ? ORDER BY pp.student_id");
        $programmeId = (int) $programmeId;
        $stmt->bind_param('i', $programmeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $rows[] = $r;
        }
        return $rows;
    }

    public function removeParticipant($programmeId, $studentId) {
        $stmt = $this->db->prepare("DELETE FROM `programme_participants` WHERE `programme_id` = ? AND `student_id` = ?");
        $programmeId = (int) $programmeId;
        $stmt->bind_param('is', $programmeId, $studentId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function addParticipants($programmeId, array $studentIds, array $groupIds = []) {
        $added = 0;
        $stmt = $this->db->prepare("INSERT IGNORE INTO `programme_participants` (`programme_id`, `student_id`, `group_id`, `source`) VALUES (?, ?, ?, ?)");
        $programmeId = (int) $programmeId;
        foreach ($groupIds as $gid) {
            foreach ($this->groupMemberIds((int) $gid) as $sid) {
                $g = (int) $gid;
                $src = 'group';
                $stmt->bind_param('isis', $programmeId, $sid, $g, $src);
                $stmt->execute();
                $added += $stmt->affected_rows > 0 ? 1 : 0;
            }
        }
        foreach ($studentIds as $sid) {
            $sid = trim((string) $sid);
            if ($sid === '') {
                continue;
            }
            $g = null;
            $src = 'individual';
            $stmt->bind_param('isis', $programmeId, $sid, $g, $src);
            $stmt->execute();
            $added += $stmt->affected_rows > 0 ? 1 : 0;
        }
        return $added;
    }

    public function setStatus($id, $status, $reason = '') {
        $id = (int) $id;
        if ($status === 'published') {
            $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `status` = 'published', `published_at` = NOW() WHERE `id` = ? AND `status` = 'draft'");
            $stmt->bind_param('i', $id);
        } elseif ($status === 'cancelled') {
            $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `status` = 'cancelled', `cancelled_at` = NOW(), `cancel_reason` = ? WHERE `id` = ? AND `status` <> 'cancelled'");
            $reason = mb_substr(trim((string) $reason), 0, 255);
            $stmt->bind_param('si', $reason, $id);
        } else {
            return false;
        }
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /**
     * Session conflicts for an instructor (other published/draft programmes on the same date and time).
     *
     * @return list<string>
     */
    public function instructorConflicts($staffId, array $schedule, $excludeId = 0) {
        if ($staffId === '' || $staffId === null) {
            return [];
        }
        $msgs = [];
        $stmt = $this->db->prepare("SELECT p.title, ps.session_date, ps.start_time, ps.end_time
            FROM `programme_schedules` ps INNER JOIN `{$this->table}` p ON p.id = ps.programme_id
            WHERE p.instructor_staff_id = ? AND p.status <> 'cancelled' AND p.id <> ? AND ps.session_date = ?
              AND ps.start_time < ? AND ps.end_time > ?");
        $excludeId = (int) $excludeId;
        foreach ($schedule as $row) {
            $stmt->bind_param('sisss', $staffId, $excludeId, $row['date'], $row['end'], $row['start']);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) {
                $msgs[] = 'Instructor already has "' . $r['title'] . '" on ' . $r['session_date'] . ' ' . substr($r['start_time'], 0, 5) . '-' . substr($r['end_time'], 0, 5) . '.';
            }
        }
        return $msgs;
    }

    /**
     * Programme attendance for one programme: per schedule date and participant.
     * Present  : full-day biometric result, or a valid punch inside the session window (60 min before to 60 min after).
     * Partial  : half-day biometric result without a punch in the window.
     * Absent   : no qualifying biometric result.
     * Sessions in the future are reported as "Upcoming".
     *
     * @return array{schedules:list<array<string,mixed>>, participants:list<array<string,mixed>>, matrix:array<string,array<string,string>>, totals:array<string,array<string,int>>}
     */
    public function attendance($programmeId) {
        $schedules = $this->schedules($programmeId);
        $participants = $this->participants($programmeId);
        $daily = new DailyAttendanceModel();
        $ids = array_column($participants, 'student_id');
        $cfg = AttendanceRules::config();
        $today = AttendanceRules::today();
        $matrix = [];
        $totals = [];
        foreach ($schedules as $sch) {
            $date = $sch['session_date'];
            $key = $sch['id'];
            $totals[$key] = ['present' => 0, 'partial' => 0, 'absent' => 0, 'upcoming' => 0];
            if ($date > $today) {
                foreach ($ids as $sid) {
                    $matrix[$sid][$key] = 'upcoming';
                }
                $totals[$key]['upcoming'] = count($ids);
                continue;
            }
            $punches = $daily->rawPunches($ids, $date, $date);
            $stored = $daily->storedForDate($ids, $date);
            $from = date('H:i:s', strtotime($sch['start_time']) - 3600);
            $to = date('H:i:s', strtotime($sch['end_time']) + 3600);
            foreach ($ids as $sid) {
                $times = array_column($punches[$sid . '|' . $date] ?? [], 'time');
                $calc = AttendanceRules::calculateDay($times, $cfg);
                $final = isset($stored[$sid]) ? $stored[$sid]['final_status'] : $calc['status'];
                $inWindow = false;
                foreach ($times as $t) {
                    $t = AttendanceRules::hms($t);
                    if ($t >= $from && $t <= $to) {
                        $inWindow = true;
                        break;
                    }
                }
                if ($final === AttendanceRules::STATUS_FULL || ($inWindow && $calc['status'] !== AttendanceRules::STATUS_ABSENT)) {
                    $state = 'present';
                } elseif ($final === AttendanceRules::STATUS_HALF) {
                    $state = 'partial';
                } else {
                    $state = 'absent';
                }
                $matrix[$sid][$key] = $state;
                $totals[$key][$state]++;
            }
        }
        return ['schedules' => $schedules, 'participants' => $participants, 'matrix' => $matrix, 'totals' => $totals];
    }

    /**
     * Flat rows for the programme attendance report.
     *
     * @param array<string,mixed> $filters see listProgrammes
     * @return list<list<mixed>>
     */
    public function reportRows(array $filters) {
        $out = [];
        foreach ($this->listProgrammes($filters) as $p) {
            if ($p['status'] === 'draft') {
                continue;
            }
            $att = $this->attendance($p['id']);
            $labels = ['present' => 'Present', 'partial' => 'Partial', 'absent' => 'Absent', 'upcoming' => 'Upcoming'];
            foreach ($att['participants'] as $pt) {
                foreach ($att['schedules'] as $sch) {
                    $out[] = [
                        $p['title'], $p['training_type'], $sch['session_date'], substr($sch['start_time'], 0, 5) . '-' . substr($sch['end_time'], 0, 5),
                        $pt['student_id'], $pt['student_name'], $pt['group_name'] ?: '-', $p['status'] === 'cancelled' ? 'Cancelled' : ($labels[$att['matrix'][$pt['student_id']][$sch['id']] ?? 'absent'] ?? ''),
                    ];
                }
            }
        }
        return $out;
    }

    /**
     * Per participant progress: sessions attended vs total (past) sessions.
     *
     * @return list<array<string,mixed>>
     */
    public function progress($programmeId) {
        $att = $this->attendance($programmeId);
        $past = 0;
        foreach ($att['schedules'] as $s) {
            if ($s['session_date'] <= AttendanceRules::today()) {
                $past++;
            }
        }
        $rows = [];
        foreach ($att['participants'] as $pt) {
            $present = 0;
            $partial = 0;
            foreach ($att['schedules'] as $s) {
                $st = $att['matrix'][$pt['student_id']][$s['id']] ?? '';
                if ($st === 'present') {
                    $present++;
                } elseif ($st === 'partial') {
                    $partial++;
                }
            }
            $rows[] = [
                'student_id' => $pt['student_id'], 'student_name' => $pt['student_name'], 'present' => $present, 'partial' => $partial,
                'sessions_held' => $past, 'total_sessions' => count($att['schedules']),
                'percent' => $past > 0 ? round(($present + 0.5 * $partial) * 100 / $past, 1) : 0.0,
            ];
        }
        return $rows;
    }
}
