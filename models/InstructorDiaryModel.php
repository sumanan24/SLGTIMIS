<?php
/**
 * Instructor Diary Model
 */
class InstructorDiaryModel extends Model {
    protected $table = 'instructor_diary';
    protected $lastError = '';
    
    protected function getPrimaryKey() {
        return 'instructor_diary_id';
    }
    
    /**
     * Ensure instructor_diary table exists with correct structure.
     * Uses VARCHAR staff_id/module_id to match existing schema.
     */
    public function ensureTableStructure() {
        $check = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
        if ($check && $check->num_rows === 0) {
            $sql = "CREATE TABLE `{$this->table}` (
                        `instructor_diary_id` INT(11) NOT NULL AUTO_INCREMENT,
                        `staff_module_enrollment_id` INT(11) NOT NULL,
                        `staff_id` VARCHAR(50) NOT NULL,
                        `module_id` VARCHAR(50) NOT NULL,
                        `diary_date` DATE NOT NULL,
                        `start_time` TIME NOT NULL,
                        `end_time` TIME NOT NULL,
                        `topic_covered` VARCHAR(255) NOT NULL,
                        PRIMARY KEY (`instructor_diary_id`),
                        KEY `idx_staff` (`staff_id`),
                        KEY `idx_module` (`module_id`),
                        KEY `idx_enrollment` (`staff_module_enrollment_id`),
                        KEY `idx_diary_date` (`diary_date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (!$this->db->query($sql)) {
                $this->lastError = $this->db->getConnection()->error;
                error_log('InstructorDiaryModel::ensureTableStructure create failed: ' . $this->lastError);
            }
        }
        
        require_once BASE_PATH . '/helpers/SchedulingSchema.php';
        SchedulingSchema::ensure();
        return true;
    }

    // ------------------------------------------------------------------
    // Timetable-linked diary (Phase 8)
    // ------------------------------------------------------------------
    const ST_SCHEDULED = 'Scheduled';
    const ST_PROGRESS = 'In Progress';
    const ST_COMPLETED = 'Completed';
    const ST_NOT_CONDUCTED = 'Not Conducted';
    const ST_SUBMITTED = 'Submitted';
    const ST_REVIEWED = 'Reviewed by HOD';

    /**
     * Display status: a session that is over but not yet submitted is "Pending Submission".
     */
    public static function displayStatus(array $row) {
        $st = (string) ($row['diary_status'] ?? self::ST_SCHEDULED);
        $date = (string) ($row['session_date'] ?? $row['diary_date'] ?? '');
        if (in_array($st, [self::ST_SCHEDULED, self::ST_PROGRESS, self::ST_COMPLETED], true)
            && $date !== '' && $date < date('Y-m-d')) {
            return 'Pending Submission';
        }
        return $st;
    }

    /**
     * Create diary rows for every published timetable session of an instructor on a date (past or today).
     * Existing rows are never modified or duplicated. Sessions are never marked completed automatically.
     *
     * @return int rows created
     */
    public function generateForDate($staffId, $date) {
        $this->ensureTableStructure();
        require_once BASE_PATH . '/models/GroupTimetableModel.php';
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        if ((string) $date > AttendanceRules::today()) {
            return 0;
        }
        if (is_array($staffId)) {
            $filters = $staffId; // e.g. ['department_id' => 'D1']
        } else {
            $filters = ($staffId !== '' && $staffId !== null) ? ['staff_id' => (string) $staffId] : [];
        }
        $sessions = (new GroupTimetableModel())->sessionsForDate($date, $filters);
        $created = 0;
        $chk = $this->db->prepare("SELECT 1 FROM `{$this->table}` WHERE `timetable_id` = ? AND `session_date` = ? LIMIT 1");
        $ins = $this->db->prepare("INSERT INTO `{$this->table}`
            (`staff_module_enrollment_id`, `staff_id`, `module_id`, `diary_date`, `start_time`, `end_time`, `topic_covered`,
             `timetable_id`, `session_date`, `group_id`, `session_type`, `scheduled_hours`, `diary_status`)
            VALUES (?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, 'Scheduled')");
        if (!$chk || !$ins) {
            return 0;
        }
        foreach ($sessions as $s) {
            $tid = (int) $s['timetable_id'];
            $chk->bind_param('is', $tid, $date);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                continue;
            }
            $staffId = (string) $s['staff_id'];
            $enrol = $this->findEnrollmentId($staffId, (string) ($s['module_id'] ?? ''), (string) ($s['course_id'] ?? ''));
            $module = (string) ($s['module_id'] ?? '');
            $gid = (int) $s['group_id'];
            $type = (string) ($s['session_type'] ?? 'Theory');
            $hours = number_format((float) $s['scheduled_hours'], 2, '.', '');
            $start = $s['start_clock'];
            $end = $s['end_clock'];
            $sid = (string) $staffId;
            $ins->bind_param('isssssisiss', $enrol, $sid, $module, $date, $start, $end, $tid, $date, $gid, $type, $hours);
            if ($ins->execute()) {
                $created++;
            }
        }
        return $created;
    }

    /**
     * Generate every day of a date range (capped at 62 days) up to today.
     */
    public function generateForRange($staffId, $from, $to) {
        $total = 0;
        $d = new DateTime($from);
        $end = new DateTime(min($to, date('Y-m-d')));
        $n = 0;
        while ($d <= $end && $n++ < 62) {
            $total += $this->generateForDate($staffId, $d->format('Y-m-d'));
            $d->modify('+1 day');
        }
        return $total;
    }

    private function findEnrollmentId($staffId, $moduleId, $courseId) {
        if ($moduleId === '') {
            return 0;
        }
        $stmt = $this->db->prepare("SELECT `staff_module_enrollment_id` FROM `staff_module_enrollment`
            WHERE `staff_id` = ? AND `module_id` = ? ORDER BY `staff_module_enrollment_id` DESC LIMIT 1");
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ss', $staffId, $moduleId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['staff_module_enrollment_id'] : 0;
    }

    /**
     * Session diary rows with group / module names.
     *
     * @param array{staff_id?:string, department_id?:string, from?:string, to?:string, status?:string, session_type?:string, group_id?:int, module_id?:string} $f
     * @return list<array<string,mixed>>
     */
    public function sessionEntries(array $f = []) {
        $this->ensureTableStructure();
        $sql = "SELECT d.*, g.name AS group_name, g.group_code, s.staff_name,
                    (SELECT m.module_name FROM `module` m WHERE m.module_id = d.module_id ORDER BY m.course_id LIMIT 1) AS module_name,
                    t.subject, t.classroom,
                    (SELECT r.remarks FROM `instructor_diary_reviews` r WHERE r.instructor_diary_id = d.instructor_diary_id ORDER BY r.id DESC LIMIT 1) AS review_remarks,
                    (SELECT r.status FROM `instructor_diary_reviews` r WHERE r.instructor_diary_id = d.instructor_diary_id ORDER BY r.id DESC LIMIT 1) AS review_status
                FROM `{$this->table}` d
                LEFT JOIN `groups` g ON g.id = d.group_id
                LEFT JOIN `staff` s ON s.staff_id = d.staff_id
                LEFT JOIN `group_timetable` t ON t.timetable_id = d.timetable_id
                WHERE d.timetable_id IS NOT NULL";
        $types = '';
        $params = [];
        if (!empty($f['staff_id'])) {
            $sql .= ' AND d.staff_id = ?';
            $types .= 's';
            $params[] = $f['staff_id'];
        }
        if (!empty($f['department_id'])) {
            $sql .= ' AND s.department_id = ?';
            $types .= 's';
            $params[] = $f['department_id'];
        }
        if (!empty($f['from'])) {
            $sql .= ' AND d.session_date >= ?';
            $types .= 's';
            $params[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $sql .= ' AND d.session_date <= ?';
            $types .= 's';
            $params[] = $f['to'];
        }
        if (!empty($f['session_type'])) {
            $sql .= ' AND d.session_type = ?';
            $types .= 's';
            $params[] = $f['session_type'];
        }
        if (!empty($f['group_id'])) {
            $sql .= ' AND d.group_id = ?';
            $types .= 'i';
            $params[] = (int) $f['group_id'];
        }
        if (!empty($f['module_id'])) {
            $sql .= ' AND d.module_id = ?';
            $types .= 's';
            $params[] = $f['module_id'];
        }
        $sql .= ' ORDER BY d.session_date DESC, d.start_time ASC';
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
            $r['display_status'] = self::displayStatus($r);
            if (!empty($f['status']) && $r['display_status'] !== $f['status'] && $r['diary_status'] !== $f['status']) {
                continue;
            }
            $rows[] = $r;
        }
        return $rows;
    }

    public function findSessionEntry($id) {
        $this->ensureTableStructure();
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `instructor_diary_id` = ? AND `timetable_id` IS NOT NULL LIMIT 1");
        $id = (int) $id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Instructor actions on their own session entry.
     * actions: start | complete | not_conducted | submit
     *
     * @param array<string,mixed> $data
     * @return array{ok:bool, message:string}
     */
    public function instructorAction($id, $staffId, $action, array $data = []) {
        $row = $this->findSessionEntry($id);
        if (!$row || (string) $row['staff_id'] !== (string) $staffId) {
            return ['ok' => false, 'message' => 'Diary entry not found.'];
        }
        $st = $row['diary_status'];
        if (in_array($st, [self::ST_SUBMITTED, self::ST_REVIEWED], true)) {
            return ['ok' => false, 'message' => 'A submitted entry can no longer be edited.'];
        }
        $today = date('Y-m-d');
        if ((string) $row['session_date'] > $today) {
            return ['ok' => false, 'message' => 'A future session cannot be recorded yet.'];
        }
        $id = (int) $id;
        switch ($action) {
            case 'start':
                if ($st !== self::ST_SCHEDULED) {
                    return ['ok' => false, 'message' => 'Only a scheduled session can be started.'];
                }
                $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `diary_status` = 'In Progress', `started_at` = NOW() WHERE `instructor_diary_id` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                return ['ok' => true, 'message' => 'Session started.'];

            case 'complete':
                $topic = trim((string) ($data['topic_covered'] ?? ''));
                $methods = trim((string) ($data['teaching_methods'] ?? ''));
                $outcomes = trim((string) ($data['learning_outcomes'] ?? ''));
                $activities = trim((string) ($data['activities_completed'] ?? ''));
                $hours = (float) ($data['actual_hours'] ?? 0);
                if (mb_strlen($topic) < 3 || $methods === '') {
                    return ['ok' => false, 'message' => 'Enter the topic covered and the teaching methods used.'];
                }
                if ($hours <= 0 || $hours > 12) {
                    return ['ok' => false, 'message' => 'Actual hours must be between 0 and 12.'];
                }
                $topic = mb_substr($topic, 0, 255);
                $h = number_format($hours, 2, '.', '');
                $stmt = $this->db->prepare("UPDATE `{$this->table}`
                    SET `diary_status` = 'Completed', `topic_covered` = ?, `teaching_methods` = ?, `learning_outcomes` = ?,
                        `activities_completed` = ?, `actual_hours` = ?, `not_conducted_reason` = NULL
                    WHERE `instructor_diary_id` = ?");
                $stmt->bind_param('sssssi', $topic, $methods, $outcomes, $activities, $h, $id);
                $stmt->execute();
                return ['ok' => true, 'message' => 'Session recorded. Submit it when you are ready for review.'];

            case 'not_conducted':
                $reason = trim((string) ($data['reason'] ?? ''));
                if (mb_strlen($reason) < 5) {
                    return ['ok' => false, 'message' => 'Enter the reason the session was not conducted.'];
                }
                $reason = mb_substr($reason, 0, 255);
                $stmt = $this->db->prepare("UPDATE `{$this->table}`
                    SET `diary_status` = 'Not Conducted', `not_conducted_reason` = ?, `actual_hours` = 0 WHERE `instructor_diary_id` = ?");
                $stmt->bind_param('si', $reason, $id);
                $stmt->execute();
                return ['ok' => true, 'message' => 'Session marked as not conducted.'];

            case 'submit':
                if (!in_array($st, [self::ST_COMPLETED, self::ST_NOT_CONDUCTED], true)) {
                    return ['ok' => false, 'message' => 'Record the session (or mark it not conducted) before submitting.'];
                }
                $stmt = $this->db->prepare("UPDATE `{$this->table}` SET `diary_status` = 'Submitted', `submitted_at` = NOW() WHERE `instructor_diary_id` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                return ['ok' => true, 'message' => 'Submitted to the Head of Department.'];
        }
        return ['ok' => false, 'message' => 'Unknown action.'];
    }

    /**
     * HOD review of a submitted entry: approve (Reviewed by HOD) or return for correction.
     *
     * @return array{ok:bool, message:string}
     */
    public function review($id, $reviewerStaffId, $approve, $remarks) {
        $row = $this->findSessionEntry($id);
        if (!$row || $row['diary_status'] !== self::ST_SUBMITTED) {
            return ['ok' => false, 'message' => 'Only submitted entries can be reviewed.'];
        }
        $remarks = mb_substr(trim((string) $remarks), 0, 500);
        if (!$approve && $remarks === '') {
            return ['ok' => false, 'message' => 'Enter remarks when returning an entry.'];
        }
        $id = (int) $id;
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $newStatus = $approve ? self::ST_REVIEWED : ($row['topic_covered'] === '' ? self::ST_NOT_CONDUCTED : self::ST_COMPLETED);
            $stmt = $conn->prepare("UPDATE `{$this->table}` SET `diary_status` = ? WHERE `instructor_diary_id` = ? AND `diary_status` = 'Submitted'");
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            if ($stmt->affected_rows < 1) {
                throw new RuntimeException('Entry is no longer awaiting review.');
            }
            $stmt->close();
            $status = $approve ? 'Reviewed' : 'Returned';
            $r = $conn->prepare("INSERT INTO `instructor_diary_reviews` (`instructor_diary_id`, `reviewer_staff_id`, `status`, `remarks`) VALUES (?, ?, ?, ?)");
            $rid = (string) $reviewerStaffId;
            $r->bind_param('isss', $id, $rid, $status, $remarks);
            $r->execute();
            $conn->commit();
            return ['ok' => true, 'message' => $approve ? 'Entry reviewed.' : 'Entry returned to the instructor.'];
        } catch (Throwable $e) {
            $conn->rollback();
            return ['ok' => false, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'The review could not be saved.'];
        }
    }

    /**
     * Teaching progress: scheduled vs actual hours per instructor and module.
     *
     * @return list<array<string,mixed>>
     */
    public function progressByModule(array $f = []) {
        $this->ensureTableStructure();
        $sql = "SELECT d.staff_id, s.staff_name, d.module_id,
                    COALESCE((SELECT m.module_name FROM `module` m WHERE m.module_id = d.module_id ORDER BY m.course_id LIMIT 1), '(no module)') AS module_name,
                    d.group_id, g.name AS group_name,
                    COUNT(*) AS sessions,
                    SUM(d.diary_status IN ('Completed','Submitted','Reviewed by HOD')) AS completed,
                    SUM(d.diary_status = 'Not Conducted') AS not_conducted,
                    SUM(d.diary_status IN ('Scheduled','In Progress')) AS outstanding,
                    SUM(COALESCE(d.scheduled_hours, 0)) AS scheduled_hours,
                    SUM(COALESCE(d.actual_hours, 0)) AS actual_hours
                FROM `{$this->table}` d
                LEFT JOIN `staff` s ON s.staff_id = d.staff_id
                LEFT JOIN `groups` g ON g.id = d.group_id
                WHERE d.timetable_id IS NOT NULL";
        $types = '';
        $params = [];
        foreach (['staff_id' => 'd.staff_id', 'department_id' => 's.department_id'] as $k => $col) {
            if (!empty($f[$k])) {
                $sql .= " AND {$col} = ?";
                $types .= 's';
                $params[] = $f[$k];
            }
        }
        if (!empty($f['from'])) {
            $sql .= ' AND d.session_date >= ?';
            $types .= 's';
            $params[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $sql .= ' AND d.session_date <= ?';
            $types .= 's';
            $params[] = $f['to'];
        }
        $sql .= ' GROUP BY d.staff_id, s.staff_name, d.module_id, d.group_id, g.name ORDER BY s.staff_name, module_name, g.name';
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
    
    /**
     * Create a new diary entry.
     */
    public function createEntry($data) {
        $this->ensureTableStructure();
        return $this->create($data);
    }
    
    /**
     * Check if an entry already exists for the same staff, module, date and time range.
     */
    public function existsEntry($staffId, $moduleId, $diaryDate, $startTime, $endTime) {
        $this->ensureTableStructure();
        
        $sql = "SELECT 1 FROM `{$this->table}` 
                WHERE staff_id = ? 
                  AND module_id = ? 
                  AND diary_date = ? 
                  AND start_time = ? 
                  AND end_time = ?
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->lastError = $this->db->getConnection()->error;
            error_log('InstructorDiaryModel::existsEntry prepare failed: ' . $this->lastError);
            return false;
        }
        
        $stmt->bind_param("sssss", $staffId, $moduleId, $diaryDate, $startTime, $endTime);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Get diary entries for a staff member (optional date range, module, course, group).
     */
    public function getByStaff($staffId, $filters = []) {
        $this->ensureTableStructure();
        
        $sql = "SELECT d.*, 
                       ANY_VALUE(sme.course_id) AS course_id,
                       ANY_VALUE(sme.academic_year) AS academic_year,
                       ANY_VALUE(m.module_name) AS module_name,
                       ANY_VALUE(s.staff_name) AS staff_name
                FROM `{$this->table}` d
                LEFT JOIN `staff_module_enrollment` sme 
                    ON d.staff_module_enrollment_id = sme.staff_module_enrollment_id
                LEFT JOIN `module` m ON d.module_id = m.module_id
                LEFT JOIN `staff` s ON d.staff_id = s.staff_id
                WHERE d.staff_id = ? AND (d.timetable_id IS NULL OR d.diary_status IN ('Submitted','Reviewed by HOD'))";
        
        $params = [$staffId];
        $types = 's';
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND d.diary_date >= ?";
            $params[] = $filters['from_date'];
            $types .= 's';
        }
        
        if (!empty($filters['to_date'])) {
            $sql .= " AND d.diary_date <= ?";
            $params[] = $filters['to_date'];
            $types .= 's';
        }
        
        if (!empty($filters['module_id'])) {
            $sql .= " AND d.module_id = ?";
            $params[] = $filters['module_id'];
            $types .= 's';
        }
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND sme.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND sme.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.instructor_diary_id
                  ORDER BY d.diary_date DESC, d.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->lastError = $this->db->getConnection()->error;
            error_log('InstructorDiaryModel::getByStaff prepare failed: ' . $this->lastError);
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get diary entries for a course/group via course_id + academic_year filters.
     * HOD can use this to view class/group-wise reports.
     */
    public function getByCourseAndYear($courseId, $academicYear, $filters = []) {
        $this->ensureTableStructure();
        
        $sql = "SELECT d.*, 
                       ANY_VALUE(sme.course_id) AS course_id,
                       ANY_VALUE(sme.academic_year) AS academic_year,
                       ANY_VALUE(m.module_name) AS module_name,
                       ANY_VALUE(s.staff_name) AS staff_name
                FROM `{$this->table}` d
                LEFT JOIN `staff_module_enrollment` sme 
                    ON d.staff_module_enrollment_id = sme.staff_module_enrollment_id
                LEFT JOIN `module` m ON d.module_id = m.module_id
                LEFT JOIN `staff` s ON d.staff_id = s.staff_id
                WHERE sme.course_id = ? AND sme.academic_year = ?";
        
        $params = [$courseId, $academicYear];
        $types = 'ss';
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND d.diary_date >= ?";
            $params[] = $filters['from_date'];
            $types .= 's';
        }
        
        if (!empty($filters['to_date'])) {
            $sql .= " AND d.diary_date <= ?";
            $params[] = $filters['to_date'];
            $types .= 's';
        }
        
        if (!empty($filters['module_id'])) {
            $sql .= " AND d.module_id = ?";
            $params[] = $filters['module_id'];
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.instructor_diary_id
                  ORDER BY d.diary_date DESC, d.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->lastError = $this->db->getConnection()->error;
            error_log('InstructorDiaryModel::getByCourseAndYear prepare failed: ' . $this->lastError);
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }

    /**
     * Generic report query for HOD/management.
     * - If $departmentId is provided, restrict to that department via staff.department_id.
     * - Supports optional filters: staff_id, course_id, academic_year, from_date, to_date.
     * - Used for initial "all department" load and for filtered report + Excel export.
     */
    public function getForReport($departmentId = null, $filters = []) {
        $this->ensureTableStructure();
        
        $sql = "SELECT d.*, 
                       ANY_VALUE(sme.course_id) AS course_id,
                       ANY_VALUE(sme.academic_year) AS academic_year,
                       ANY_VALUE(m.module_name) AS module_name,
                       ANY_VALUE(s.staff_name) AS staff_name,
                       ANY_VALUE(c.course_name) AS course_name
                FROM `{$this->table}` d
                LEFT JOIN `staff_module_enrollment` sme 
                    ON d.staff_module_enrollment_id = sme.staff_module_enrollment_id
                LEFT JOIN `module` m ON d.module_id = m.module_id
                LEFT JOIN `staff` s ON d.staff_id = s.staff_id
                LEFT JOIN `course` c ON sme.course_id = c.course_id
                WHERE (d.timetable_id IS NULL OR d.diary_status IN ('Submitted','Reviewed by HOD'))";
        
        $params = [];
        $types = '';
        
        // Department restriction (for HOD)
        if (!empty($departmentId)) {
            $sql .= " AND s.department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        
        if (!empty($filters['staff_id'])) {
            $sql .= " AND d.staff_id = ?";
            $params[] = $filters['staff_id'];
            $types .= 's';
        }
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND sme.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND sme.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND d.diary_date >= ?";
            $params[] = $filters['from_date'];
            $types .= 's';
        }
        
        if (!empty($filters['to_date'])) {
            $sql .= " AND d.diary_date <= ?";
            $params[] = $filters['to_date'];
            $types .= 's';
        }
        
        $sql .= " GROUP BY d.instructor_diary_id
                  ORDER BY d.diary_date DESC, d.start_time ASC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->lastError = $this->db->getConnection()->error;
            error_log('InstructorDiaryModel::getForReport prepare failed: ' . $this->lastError);
            return [];
        }
        
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }

    /**
     * Get last database error message for debugging (read-only).
     */
    public function getLastError() {
        return $this->lastError;
    }
}

