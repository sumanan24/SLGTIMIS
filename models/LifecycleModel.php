<?php
class LifecycleModel extends Model {
    private static $schemaReady = false;
    private $columnCache = [];

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureSchema() {
        if (self::$schemaReady) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/lifecycle.sql';
        $sql = is_file($sqlFile) ? file_get_contents($sqlFile) : false;
        if ($sql === false) {
            throw new RuntimeException('Lifecycle schema file is missing.');
        }
        $chunks = array_filter(array_map('trim', preg_split('/;\s*$/m', $sql)));
        foreach ($chunks as $chunk) {
            $chunk = trim(preg_replace('/^--.*$/m', '', $chunk));
            if ($chunk === '') {
                continue;
            }
            if (!$this->db->query($chunk)) {
                throw new RuntimeException('Lifecycle schema could not be prepared.');
            }
        }
        $this->ensureStudentColumns();
        $this->ensureCrudColumns();
        self::$schemaReady = true;
    }

    public static function label(array $student) {
        if (!empty($student['lifecycle_status'])) {
            return (string) $student['lifecycle_status'];
        }
        return (($student['student_status'] ?? '') === 'Active') ? 'Active' : 'Inactive';
    }

    public function findStudent($id) {
        $rows = $this->fetchAll('SELECT * FROM `student` WHERE `student_id` = ? LIMIT 1', 's', [$id]);
        return $rows ? $rows[0] : null;
    }

    public function visibleToDepartment($studentId, $departmentId) {
        if ($departmentId === '') {
            return false;
        }
        $rows = $this->fetchAll(
            'SELECT 1 FROM `student_enroll` e JOIN `course` c ON c.course_id = e.course_id WHERE e.student_id = ? AND c.department_id = ? LIMIT 1',
            'ss',
            [$studentId, $departmentId]
        );
        return !empty($rows);
    }

    public function courses() {
        return $this->fetchAll('SELECT `course_id`, `course_name`, `department_id`, `course_nvq_level` FROM `course` ORDER BY `course_name`', '', []);
    }

    public function departments() {
        return $this->fetchAll('SELECT `department_id`, `department_name` FROM `department` ORDER BY `department_name`', '', []);
    }

    public function groups() {
        return $this->fetchAll('SELECT g.`id`, g.`name`, g.`academic_year`, g.`course_id`, c.`course_name`, c.`department_id`, c.`course_nvq_level` FROM `groups` g LEFT JOIN `course` c ON c.course_id = g.course_id ORDER BY g.`academic_year` DESC, g.`name`', '', []);
    }

    public function filterLinks() {
        return [
            'years' => $this->fetchAll('SELECT DISTINCT e.`academic_year` AS year, e.`course_id`, c.`department_id`, c.`course_nvq_level` AS nvq FROM `student_enroll` e JOIN `course` c ON c.course_id = e.course_id WHERE e.`academic_year` IS NOT NULL AND e.`academic_year` <> \'\'', '', []),
            'trades' => $this->fetchAll('SELECT DISTINCT s.`trade`, e.`course_id`, c.`department_id`, c.`course_nvq_level` AS nvq FROM `student` s JOIN `student_enroll` e ON e.student_id = s.student_id JOIN `course` c ON c.course_id = e.course_id WHERE s.`trade` IS NOT NULL AND s.`trade` <> \'\' ORDER BY s.`trade`', '', []),
        ];
    }

    public function academicYears() {
        $rows = $this->fetchAll(
            'SELECT `academic_year` FROM (SELECT `academic_year` FROM `student_enroll` WHERE `academic_year` IS NOT NULL AND `academic_year` <> \'\' UNION SELECT `academic_year` FROM `groups` WHERE `academic_year` IS NOT NULL AND `academic_year` <> \'\') years ORDER BY `academic_year` DESC',
            '',
            []
        );
        $years = [];
        foreach ($rows as $row) {
            $year = trim((string) ($row['academic_year'] ?? ''));
            if ($year !== '') {
                $years[] = $year;
            }
        }
        return $years;
    }

    public function searchStudents(array $filters, $page, $perPage) {
        $page = max(1, (int) $page);
        $perPage = max(1, min(500, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        list($where, $types, $params) = $this->studentWhere($filters);
        $count = $this->fetchAll('SELECT COUNT(*) AS c FROM `student` s WHERE ' . $where, $types, $params);
        $total = $count ? (int) $count[0]['c'] : 0;
        $sql = 'SELECT s.`student_id`, s.`student_fullname`, s.`student_nic`, s.`student_status`, s.`lifecycle_status`, s.`admission_no`, s.`record_origin`, s.`trade`, s.`intake`,
            e.`course_id`, e.`academic_year`, e.`student_enroll_date`, e.`student_enroll_status`, c.`course_name`, c.`course_nvq_level`, d.`department_name`
            FROM `student` s
            LEFT JOIN `student_enroll` e ON e.student_id = s.student_id
                AND e.course_id = (SELECT e2.course_id FROM `student_enroll` e2 WHERE e2.student_id = s.student_id ORDER BY e2.student_enroll_date DESC, e2.course_id DESC LIMIT 1)
            LEFT JOIN `course` c ON c.course_id = e.course_id
            LEFT JOIN `department` d ON d.department_id = c.department_id
            WHERE ' . $where . ' ORDER BY s.student_id DESC LIMIT ' . (int) $offset . ', ' . (int) $perPage;
        return [
            'rows' => $this->fetchAll($sql, $types, $params),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function profile($studentId) {
        $student = $this->findStudent($studentId);
        if (!$student) {
            return null;
        }
        $this->ensureClearance($studentId);
        $enroll = $this->fetchAll('SELECT e.*, c.course_name, c.course_nvq_level, c.department_id, d.department_name
            FROM `student_enroll` e
            LEFT JOIN `course` c ON c.course_id = e.course_id
            LEFT JOIN `department` d ON d.department_id = c.department_id
            WHERE e.student_id = ? ORDER BY e.student_enroll_date DESC', 's', [$studentId]);
        $courseId = $enroll ? (string) $enroll[0]['course_id'] : '';
        $modules = $courseId === '' ? [] : $this->fetchAll(
            'SELECT `module_id`, `module_name`, `semester` FROM `module` WHERE `course_id` = ? ORDER BY `semester`, `module_name`',
            's',
            [$courseId]
        );
        $group = $this->fetchAll('SELECT g.name, g.academic_year FROM `group_students` gs JOIN `groups` g ON g.id = gs.group_id WHERE gs.student_id = ? LIMIT 1', 's', [$studentId]);
        return [
            'student' => $student,
            'status_label' => self::label($student),
            'enrollments' => $enroll,
            'modules' => $modules,
            'group' => $group ? $group[0] : null,
            'attendance' => $this->fetchAll('SELECT `module_name`, SUM(CASE WHEN `attendance_status` = 1 THEN 1 ELSE 0 END) AS present_count, COUNT(*) AS total_count FROM `attendance` WHERE `student_id` = ? GROUP BY `module_name` ORDER BY `module_name`', 's', [$studentId]),
            'device_days' => $this->scalar('SELECT COUNT(DISTINCT `attendance_date`) FROM `student_attendance` WHERE `student_id` = ?', 's', [$studentId]),
            'exams' => $this->fetchAll('SELECT e.exam_date, e.semester, e.location, m.module_id, m.marks, m.marks_final FROM `exam_students` es JOIN `exams` e ON e.id = es.exam_id LEFT JOIN `marks` m ON m.exam_id = e.id AND m.student_id = es.student_id WHERE es.student_id = ? ORDER BY e.exam_date DESC', 's', [$studentId]),
            'qualifications' => $this->fetchAll('SELECT * FROM `student_qualification` WHERE `qualification_student_id` = ?', 's', [$studentId]),
            'progress' => $this->rowsFor('student_module_progress', $studentId),
            'legacy_ojt' => $this->fetchAll('SELECT * FROM `ojt` WHERE `student_id` = ?', 's', [$studentId]),
            'industries' => $this->fetchAll('SELECT * FROM `ojt_industries` WHERE `deleted_at` IS NULL ORDER BY `name` LIMIT 300', '', []),
            'placements' => $this->fetchAll('SELECT p.*, i.name AS industry_name FROM `student_ojt` p LEFT JOIN `ojt_industries` i ON i.id = p.industry_id WHERE p.student_id = ? AND p.deleted_at IS NULL ORDER BY p.id DESC', 's', [$studentId]),
            'monitoring' => $this->rowsFor('ojt_monitoring', $studentId),
            'evaluations' => $this->rowsFor('ojt_evaluations', $studentId),
            'certificates' => $this->rowsFor('student_certificates', $studentId),
            'certificate_events' => $this->fetchAll('SELECT ev.*, c.certificate_no FROM `certificate_events` ev JOIN `student_certificates` c ON c.id = ev.certificate_id WHERE ev.student_id = ? AND ev.deleted_at IS NULL ORDER BY ev.id DESC', 's', [$studentId]),
            'employment' => $this->rowsFor('graduate_employment', $studentId),
            'education' => $this->rowsFor('graduate_higher_education', $studentId),
            'feedback' => $this->rowsFor('employer_feedback', $studentId),
            'alumni' => $this->fetchAll('SELECT * FROM `alumni_profiles` WHERE `student_id` = ? AND `deleted_at` IS NULL LIMIT 1', 's', [$studentId]),
            'engagements' => $this->rowsFor('alumni_engagement', $studentId),
            'events' => $this->fetchAll('SELECT * FROM `alumni_events` WHERE `deleted_at` IS NULL ORDER BY `event_date` DESC LIMIT 100', '', []),
            'complaints' => $this->fetchAll('SELECT * FROM `complaint_letter_students` WHERE `student_id` = ? ORDER BY `id` DESC LIMIT 50', 's', [$studentId]),
            'discipline' => $this->rowsFor('student_discipline', $studentId),
            'welfare' => $this->rowsFor('student_welfare', $studentId),
            'correspondence' => $this->rowsFor('student_correspondence', $studentId),
            'approvals' => $this->rowsFor('lifecycle_approvals', $studentId),
            'clearance' => $this->fetchAll('SELECT * FROM `student_clearance` WHERE `student_id` = ? ORDER BY `id`', 's', [$studentId]),
            'documents' => $this->rowsFor('student_documents', $studentId),
            'tracer' => $this->rowsFor('graduate_tracer_surveys', $studentId),
            'history' => $this->fetchAll('SELECT * FROM `student_status_history` WHERE `student_id` = ? ORDER BY `id` DESC LIMIT 50', 's', [$studentId]),
            'audit' => $this->fetchAll('SELECT * FROM `student_audit_logs` WHERE `student_id` = ? ORDER BY `id` DESC LIMIT 80', 's', [$studentId]),
            'hostel' => $this->fetchAll('SELECT * FROM `hostel_allocations` WHERE `student_id` = ? ORDER BY `allocated_at` DESC LIMIT 10', 's', [$studentId]),
        ];
    }

    public function admissionTaken($number, $exceptId) {
        $number = trim((string) $number);
        if ($number === '') {
            return false;
        }
        $rows = $this->fetchAll('SELECT `student_id` FROM `student` WHERE `admission_no` = ? AND `student_id` <> ? LIMIT 1', 'ss', [$number, $exceptId]);
        return !empty($rows);
    }

    public function saveStudentFields($studentId, array $input, $by) {
        $student = $this->findStudent($studentId);
        if (!$student) {
            return 'Student record was not found.';
        }
        $allowed = ['admission_no', 'intake', 'trade', 'course_commencement', 'expected_completion', 'actual_completion', 'previous_employment', 'parent_name', 'parent_phone', 'lifecycle_status', 'discontinuation_reason', 'historical_import_id'];
        $dates = ['course_commencement', 'expected_completion', 'actual_completion'];
        $sets = [];
        $types = '';
        $params = [];
        $changes = [];
        foreach ($allowed as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            $value = trim((string) $input[$field]);
            if ($field === 'admission_no' || in_array($field, $dates, true)) {
                $value = $value === '' ? null : $value;
            }
            if ($field === 'lifecycle_status' && $value !== '' && !in_array($value, LifecycleForms::statuses(), true)) {
                return 'Choose a valid student status.';
            }
            if ($field === 'admission_no' && $value !== null && $this->admissionTaken($value, $studentId)) {
                return 'That admission number is already used.';
            }
            $old = $student[$field] ?? null;
            $oldText = $old === null ? '' : (string) $old;
            $newText = $value === null ? '' : (string) $value;
            if ($oldText === $newText) {
                continue;
            }
            $sets[] = '`' . $field . '` = ?';
            $types .= 's';
            $params[] = $value;
            $changes[] = [$field, $oldText, $newText];
        }
        if (array_key_exists('lifecycle_status', $input)) {
            $next = trim((string) $input['lifecycle_status']);
            if (in_array($next, ['Discontinued', 'Suspended', 'Withdrawn'], true) && ($student['student_status'] ?? '') !== 'Inactive') {
                $sets[] = '`student_status` = ?';
                $types .= 's';
                $params[] = 'Inactive';
                $changes[] = ['student_status', (string) $student['student_status'], 'Inactive'];
            } elseif ($next === 'Active' && ($student['student_status'] ?? '') !== 'Active') {
                $sets[] = '`student_status` = ?';
                $types .= 's';
                $params[] = 'Active';
                $changes[] = ['student_status', (string) $student['student_status'], 'Active'];
            }
        }
        if (!$sets) {
            return '';
        }
        $types .= 's';
        $params[] = $studentId;
        $stmt = $this->db->prepare('UPDATE `student` SET ' . implode(', ', $sets) . ' WHERE `student_id` = ?');
        $this->bind($stmt, $types, $params);
        if (!$stmt->execute()) {
            return 'The student record could not be updated.';
        }
        foreach ($changes as $change) {
            $this->audit($studentId, 'update', $change[0], $change[1], $change[2], $by);
            if ($change[0] === 'lifecycle_status') {
                $this->insertRow('student_status_history', [
                    'student_id' => $studentId,
                    'old_status' => $change[1] === '' ? self::label($student) : $change[1],
                    'new_status' => $change[2],
                    'reason' => isset($input['discontinuation_reason']) ? $input['discontinuation_reason'] : '',
                    'changed_by' => $by,
                ]);
                if ($change[2] === 'Completed') {
                    $this->registerAlumni($studentId, $by);
                }
            }
        }
        return '';
    }

    public function insertRow($table, array $data) {
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        $fields = [];
        $holders = [];
        $types = '';
        $params = [];
        foreach ($data as $key => $value) {
            if (!isset($cols[$key]) || $key === 'id') {
                continue;
            }
            $fields[] = '`' . $key . '`';
            $holders[] = '?';
            $types .= 's';
            $params[] = $value;
        }
        if (!$fields) {
            throw new RuntimeException('Nothing to save.');
        }
        $stmt = $this->db->prepare('INSERT INTO `' . $table . '` (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $holders) . ')');
        $this->bind($stmt, $types, $params);
        if (!$stmt || !$stmt->execute()) {
            throw new RuntimeException('The record could not be saved.');
        }
        return (int) $this->db->lastInsertId();
    }

    public function updateRow($table, $id, array $data) {
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        $sets = [];
        $types = '';
        $params = [];
        foreach ($data as $key => $value) {
            if (!isset($cols[$key]) || $key === 'id') {
                continue;
            }
            $sets[] = '`' . $key . '` = ?';
            $types .= 's';
            $params[] = $value;
        }
        if (!$sets) {
            return;
        }
        $types .= 'i';
        $params[] = (int) $id;
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `id` = ?';
        if (isset($cols['deleted_at'])) {
            $sql .= ' AND `deleted_at` IS NULL';
        }
        $stmt = $this->db->prepare($sql);
        $this->bind($stmt, $types, $params);
        if (!$stmt->execute()) {
            throw new RuntimeException('The record could not be updated.');
        }
    }

    public function softDelete($table, $id, $by = '', $reason = '') {
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        if (!isset($cols['deleted_at'])) {
            return false;
        }
        $sets = ['`deleted_at` = NOW()'];
        $types = '';
        $params = [];
        if (isset($cols['deleted_by'])) {
            $sets[] = '`deleted_by` = ?';
            $types .= 's';
            $params[] = $by;
        }
        if (isset($cols['delete_reason'])) {
            $sets[] = '`delete_reason` = ?';
            $types .= 's';
            $params[] = substr((string) $reason, 0, 255);
        }
        $types .= 'i';
        $params[] = (int) $id;
        $stmt = $this->db->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `id` = ? AND `deleted_at` IS NULL');
        $this->bind($stmt, $types, $params);
        return $stmt && $stmt->execute();
    }

    public function restoreRow($table, $id) {
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        if (!isset($cols['deleted_at'])) {
            return false;
        }
        $sets = ['`deleted_at` = NULL'];
        if (isset($cols['deleted_by'])) {
            $sets[] = '`deleted_by` = NULL';
        }
        if (isset($cols['delete_reason'])) {
            $sets[] = '`delete_reason` = NULL';
        }
        $stmt = $this->db->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `id` = ? AND `deleted_at` IS NOT NULL');
        $id = (int) $id;
        $stmt->bind_param('i', $id);
        return $stmt && $stmt->execute() && $stmt->affected_rows > 0;
    }

    public function findRow($table, $id) {
        $this->assertTable($table);
        $rows = $this->fetchAll('SELECT * FROM `' . $table . '` WHERE `id` = ? LIMIT 1', 'i', [(int) $id]);
        return $rows ? $rows[0] : null;
    }

    public function searchModule(array $spec, array $filters, $page, $perPage) {
        $table = $spec['table'];
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        $page = max(1, (int) $page);
        $perPage = in_array((int) $perPage, [10, 20, 50], true) ? (int) $perPage : 20;
        $where = ['1=1'];
        $types = '';
        $params = [];
        $hasStudent = isset($cols['student_id']);
        if (isset($cols['deleted_at'])) {
            $where[] = empty($filters['deleted']) ? 't.`deleted_at` IS NULL' : 't.`deleted_at` IS NOT NULL';
        }
        if (!empty($spec['fixed'])) {
            foreach ($spec['fixed'] as $key => $value) {
                if (isset($cols[$key]) && preg_match('/^[a-z_]+$/', (string) $key)) {
                    $where[] = 't.`' . $key . '` = ?';
                    $types .= 's';
                    $params[] = $value;
                }
            }
        }
        if (!empty($filters['student_id']) && $hasStudent) {
            $where[] = 't.`student_id` = ?';
            $types .= 's';
            $params[] = $filters['student_id'];
        }
        if (!empty($filters['status']) && isset($cols['status'])) {
            $where[] = 't.`status` = ?';
            $types .= 's';
            $params[] = $filters['status'];
        }
        if (!empty($filters['year']) && $hasStudent && strpos((string) $filters['year'], '/') !== false) {
            $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` ey WHERE ey.student_id = t.student_id AND ey.academic_year = ?)';
            $types .= 's';
            $params[] = $filters['year'];
        }
        if (!empty($filters['department_limit']) && $hasStudent) {
            $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` ex JOIN `course` cx ON cx.course_id = ex.course_id WHERE ex.student_id = t.student_id AND cx.department_id = ?)';
            $types .= 's';
            $params[] = $filters['department_limit'];
        }
        if (!empty($filters['q'])) {
            $like = '%' . $filters['q'] . '%';
            $parts = [];
            if ($hasStudent) {
                $parts[] = 't.student_id LIKE ?';
                $parts[] = 's.student_fullname LIKE ?';
                $parts[] = 's.student_nic LIKE ?';
                $types .= 'sss';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            foreach ($spec['fields'] as $field) {
                $name = $field['name'];
                $sqlType = isset($cols[$name]) ? strtolower($cols[$name]) : '';
                if ($sqlType !== '' && strpos($sqlType, 'char') !== false && preg_match('/^[a-z_]+$/', $name)) {
                    $parts[] = 't.`' . $name . '` LIKE ?';
                    $types .= 's';
                    $params[] = $like;
                }
            }
            if (isset($cols['certificate_no'])) {
                $parts[] = 't.`certificate_no` LIKE ?';
                $types .= 's';
                $params[] = $like;
            }
            if (isset($cols['reference_no'])) {
                $parts[] = 't.`reference_no` LIKE ?';
                $types .= 's';
                $params[] = $like;
            }
            if ($parts) {
                $where[] = '(' . implode(' OR ', $parts) . ')';
            }
        }
        $sort = 't.`id`';
        $requested = (string) (isset($filters['sort']) ? $filters['sort'] : '');
        if ($requested === 'name' && $hasStudent) {
            $sort = 's.`student_fullname`';
        } elseif ($requested !== '' && isset($cols[$requested]) && preg_match('/^[a-z_]+$/', $requested)) {
            $sort = 't.`' . $requested . '`';
        }
        $dir = (isset($filters['dir']) && strtolower((string) $filters['dir']) === 'asc') ? 'ASC' : 'DESC';
        $join = $hasStudent ? ' LEFT JOIN `student` s ON s.student_id = t.student_id' : '';
        $select = $hasStudent ? 't.*, s.student_fullname, s.student_nic' : 't.*';
        $whereSql = implode(' AND ', $where);
        $count = $this->fetchAll('SELECT COUNT(*) AS c FROM `' . $table . '` t' . $join . ' WHERE ' . $whereSql, $types, $params);
        $total = $count ? (int) $count[0]['c'] : 0;
        $offset = ($page - 1) * $perPage;
        $rows = $this->fetchAll(
            'SELECT ' . $select . ' FROM `' . $table . '` t' . $join . ' WHERE ' . $whereSql . ' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . (int) $offset . ', ' . (int) $perPage,
            $types,
            $params
        );
        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
            'per_page' => $perPage,
        ];
    }

    public function writeAudit($studentId, $module, $recordId, $operation, $old, $new, $by) {
        $studentId = trim((string) $studentId);
        if ($studentId === '') {
            $studentId = 'office';
        }
        $this->insertRow('student_audit_logs', [
            'student_id' => substr($studentId, 0, 20),
            'action' => substr((string) $operation, 0, 40),
            'field_name' => substr((string) $module, 0, 60),
            'old_value' => substr((string) $old, 0, 255),
            'new_value' => substr((string) $new, 0, 255),
            'changed_by' => $by,
            'module_name' => substr((string) $module, 0, 40),
            'record_id' => (int) $recordId,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? substr((string) $_SERVER['REMOTE_ADDR'], 0, 45) : '',
        ]);
    }

    public function duplicateText($table, $column, $value, $exceptId) {
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        if (!isset($cols[$column]) || !preg_match('/^[a-z_]+$/', $column) || trim((string) $value) === '') {
            return false;
        }
        $sql = 'SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = ? AND `id` <> ?';
        if (isset($cols['deleted_at'])) {
            $sql .= ' AND `deleted_at` IS NULL';
        }
        $sql .= ' LIMIT 1';
        $rows = $this->fetchAll($sql, 'si', [$value, (int) $exceptId]);
        return !empty($rows);
    }

    public function ownsRow($table, $id, $studentId) {
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        if (!isset($cols['student_id'])) {
            return true;
        }
        $rows = $this->fetchAll('SELECT `id` FROM `' . $table . '` WHERE `id` = ? AND `student_id` = ? LIMIT 1', 'is', [(int) $id, $studentId]);
        return !empty($rows);
    }

    public function nextCode($prefix, $table, $column) {
        $this->assertTable($table);
        $year = date('Y');
        $lock = 'slgti_lc_' . preg_replace('/[^A-Z]/', '', $prefix);
        $this->db->query("SELECT GET_LOCK('" . $this->db->escape($lock) . "', 5)");
        $like = 'SLGTI/' . $prefix . '/' . $year . '/%';
        $rows = $this->fetchAll('SELECT `' . $column . '` AS code FROM `' . $table . '` WHERE `' . $column . '` LIKE ? ORDER BY `' . $column . '` DESC LIMIT 1', 's', [$like]);
        $n = 1;
        if ($rows) {
            $parts = explode('/', (string) $rows[0]['code']);
            $n = ((int) end($parts)) + 1;
        }
        $this->db->query("SELECT RELEASE_LOCK('" . $this->db->escape($lock) . "')");
        return sprintf('SLGTI/%s/%s/%04d', $prefix, $year, $n);
    }

    public function registerAlumni($studentId, $by) {
        $existing = $this->fetchAll('SELECT `id` FROM `alumni_profiles` WHERE `student_id` = ? AND `deleted_at` IS NULL LIMIT 1', 's', [$studentId]);
        if ($existing) {
            return (int) $existing[0]['id'];
        }
        return $this->insertRow('alumni_profiles', [
            'alumni_code' => $this->nextCode('ALM', 'alumni_profiles', 'alumni_code'),
            'student_id' => $studentId,
            'membership' => 'Pending',
            'verified' => 0,
            'record_origin' => 'system',
            'created_by' => $by,
        ]);
    }

    public function verifyAlumni($studentId, $by) {
        $rows = $this->fetchAll('SELECT * FROM `alumni_profiles` WHERE `student_id` = ? AND `deleted_at` IS NULL LIMIT 1', 's', [$studentId]);
        if (!$rows) {
            return 'Register the graduate as alumni first.';
        }
        $profile = $rows[0];
        $stmt = $this->db->prepare("UPDATE `alumni_profiles` SET `verified` = 1, `membership` = 'Active' WHERE `id` = ?");
        $id = (int) $profile['id'];
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!empty($profile['contact_phone']) || !empty($profile['contact_address'])) {
            $phone = preg_replace('/\D+/', '', (string) $profile['contact_phone']);
            if ($phone !== '' && strlen($phone) <= 9) {
                $student = $this->findStudent($studentId);
                $this->audit($studentId, 'alumni', 'student_phone', (string) ($student['student_phone'] ?? ''), $phone, $by);
                $upd = $this->db->prepare('UPDATE `student` SET `student_phone` = ?, `student_address` = IF(? = \'\', `student_address`, ?) WHERE `student_id` = ?');
                $address = (string) ($profile['contact_address'] ?? '');
                $upd->bind_param('ssss', $phone, $address, $address, $studentId);
                $upd->execute();
            }
        }
        $this->audit($studentId, 'alumni', 'verified', '0', '1', $by);
        return '';
    }

    public function savePortal(array $student, array $input) {
        $studentId = (string) $student['student_id'];
        $eligible = self::label($student) === 'Completed';
        if (!$eligible) {
            $done = $this->fetchAll("SELECT 1 FROM `student_enroll` WHERE `student_id` = ? AND `student_enroll_status` = 'Completed' LIMIT 1", 's', [$studentId]);
            $eligible = !empty($done);
        }
        $alumni = $this->fetchAll('SELECT `id` FROM `alumni_profiles` WHERE `student_id` = ? AND `deleted_at` IS NULL LIMIT 1', 's', [$studentId]);
        if (!$eligible && !$alumni) {
            return 'Alumni updates open after course completion.';
        }
        if (!$alumni) {
            $this->registerAlumni($studentId, $studentId);
        }
        $phone = trim((string) ($input['contact_phone'] ?? ''));
        $email = trim((string) ($input['contact_email'] ?? ''));
        $address = trim((string) ($input['contact_address'] ?? ''));
        $bio = trim((string) ($input['bio'] ?? ''));
        $stmt = $this->db->prepare('UPDATE `alumni_profiles` SET `contact_phone` = ?, `contact_email` = ?, `contact_address` = ?, `bio` = ?, `verified` = 0, `membership` = \'Pending\' WHERE `student_id` = ? AND `deleted_at` IS NULL');
        $stmt->bind_param('sssss', $phone, $email, $address, $bio, $studentId);
        $stmt->execute();
        if (trim((string) ($input['organisation'] ?? '')) !== '') {
            $this->insertRow('graduate_employment', [
                'student_id' => $studentId,
                'organisation' => trim((string) $input['organisation']),
                'designation' => trim((string) ($input['designation'] ?? '')),
                'employment_type' => trim((string) ($input['employment_type'] ?? '')),
                'current_status' => trim((string) ($input['employment_type'] ?? '')),
                'location_scope' => trim((string) ($input['location_scope'] ?? '')),
                'verified' => 0,
                'record_origin' => 'system',
                'created_by' => $studentId,
            ]);
        }
        $this->audit($studentId, 'portal', 'contact', '', 'Submitted for verification', $studentId);
        return '';
    }

    public function ensureClearance($studentId) {
        foreach (LifecycleForms::clearanceSections() as $section) {
            $stmt = $this->db->prepare('INSERT IGNORE INTO `student_clearance` (`student_id`, `section`, `status`) VALUES (?, ?, \'Pending\')');
            $stmt->bind_param('ss', $studentId, $section);
            $stmt->execute();
        }
    }

    public function setClearance($studentId, $section, $status, $note, $by) {
        if (!in_array($section, LifecycleForms::clearanceSections(), true)) {
            return 'Unknown clearance section.';
        }
        if (!in_array($status, ['Pending', 'Cleared', 'Held'], true)) {
            return 'Unknown clearance status.';
        }
        if ($section === 'Final' && $status === 'Cleared') {
            $open = $this->scalar("SELECT COUNT(*) FROM `student_clearance` WHERE `student_id` = ? AND `section` <> 'Final' AND `status` <> 'Cleared'", 's', [$studentId]);
            if ((int) $open > 0) {
                return 'Final approval waits until every section is cleared.';
            }
        }
        $clearedAt = $status === 'Cleared' ? date('Y-m-d H:i:s') : null;
        $stmt = $this->db->prepare('UPDATE `student_clearance` SET `status` = ?, `note` = ?, `cleared_by` = ?, `cleared_at` = ? WHERE `student_id` = ? AND `section` = ?');
        $stmt->bind_param('ssssss', $status, $note, $by, $clearedAt, $studentId, $section);
        $stmt->execute();
        $this->audit($studentId, 'clearance', $section, '', $status, $by);
        return '';
    }

    public function documentVersion($studentId, $category, $title) {
        $n = $this->scalar('SELECT MAX(`version_no`) FROM `student_documents` WHERE `student_id` = ? AND `category` = ? AND `title` = ?', 'sss', [$studentId, $category, $title]);
        return ((int) $n) + 1;
    }

    public function findCertificate($id) {
        $rows = $this->fetchAll('SELECT * FROM `student_certificates` WHERE `id` = ? AND `deleted_at` IS NULL LIMIT 1', 'i', [(int) $id]);
        return $rows ? $rows[0] : null;
    }

    public function findDocument($id) {
        $rows = $this->fetchAll('SELECT * FROM `student_documents` WHERE `id` = ? AND `deleted_at` IS NULL LIMIT 1', 'i', [(int) $id]);
        return $rows ? $rows[0] : null;
    }

    public function publicCertificate($number) {
        $number = trim((string) $number);
        if ($number === '') {
            return null;
        }
        $rows = $this->fetchAll('SELECT `certificate_no`, `printed_name`, `course_name`, `trade`, `nvq_level`, `qualification`, `status`, `issuance_date`, `awarding_authority` FROM `student_certificates` WHERE `certificate_no` = ? AND `deleted_at` IS NULL LIMIT 1', 's', [$number]);
        return $rows ? $rows[0] : null;
    }

    public function dashboard(array $filters) {
        list($where, $types, $params) = $this->studentWhere($filters);
        $one = function ($extra) use ($where, $types, $params) {
            $sql = 'SELECT COUNT(*) AS c FROM `student` s WHERE ' . $where . $extra;
            $rows = $this->fetchAll($sql, $types, $params);
            return $rows ? (int) $rows[0]['c'] : 0;
        };
        $certs = $this->fetchAll('SELECT sc.`status`, COUNT(*) AS c FROM `student_certificates` sc JOIN `student` s ON s.student_id = sc.student_id WHERE sc.`deleted_at` IS NULL AND ' . $where . ' GROUP BY sc.`status`', $types, $params);
        $issued = 0;
        $pending = 0;
        foreach ($certs as $row) {
            if (in_array($row['status'], ['Issued', 'Replaced'], true)) {
                $issued += (int) $row['c'];
            } elseif ($row['status'] !== 'Cancelled') {
                $pending += (int) $row['c'];
            }
        }
        $ojt = $this->fetchAll('SELECT p.`status`, COUNT(*) AS c FROM `student_ojt` p JOIN `student` s ON s.student_id = p.student_id WHERE p.`deleted_at` IS NULL AND ' . $where . ' GROUP BY p.`status`', $types, $params);
        $ojtMap = [];
        foreach ($ojt as $row) {
            $ojtMap[$row['status']] = (int) $row['c'];
        }
        $countLinked = function ($sql) use ($types, $params) {
            $rows = $this->fetchAll($sql, $types, $params);
            return $rows ? (int) $rows[0]['c'] : 0;
        };
        $years = $this->fetchAll('SELECT LEFT(e.`student_enroll_date`, 4) AS y, COUNT(DISTINCT s.`student_id`) AS c FROM `student` s JOIN `student_enroll` e ON e.student_id = s.student_id WHERE e.`student_enroll_date` IS NOT NULL AND ' . $where . ' GROUP BY y ORDER BY y', $types, $params);
        $courses = $this->fetchAll('SELECT c.course_name AS label, COUNT(DISTINCT s.`student_id`) AS c FROM `student` s JOIN `student_enroll` e ON e.student_id = s.student_id JOIN `course` c ON c.course_id = e.course_id WHERE ' . $where . ' GROUP BY c.course_id, c.course_name ORDER BY c DESC LIMIT 8', $types, $params);
        return [
            'total' => $one(''),
            'active' => $one(" AND (s.lifecycle_status = 'Active' OR (s.lifecycle_status IS NULL AND s.student_status = 'Active'))"),
            'inactive' => $one(" AND s.lifecycle_status IS NULL AND s.student_status <> 'Active'"),
            'completed' => $one(" AND s.lifecycle_status = 'Completed'"),
            'discontinued' => $one(" AND s.lifecycle_status = 'Discontinued'"),
            'suspended' => $one(" AND s.lifecycle_status = 'Suspended'"),
            'withdrawn' => $one(" AND s.lifecycle_status = 'Withdrawn'"),
            'certificates_issued' => $issued,
            'certificates_pending' => $pending,
            'ojt_completed' => isset($ojtMap['Completed']) ? $ojtMap['Completed'] : 0,
            'ojt_ongoing' => (isset($ojtMap['Ongoing']) ? $ojtMap['Ongoing'] : 0) + (isset($ojtMap['Allocated']) ? $ojtMap['Allocated'] : 0),
            'ojt_pending' => (isset($ojtMap['Not Allocated']) ? $ojtMap['Not Allocated'] : 0) + (isset($ojtMap['Pending Placement']) ? $ojtMap['Pending Placement'] : 0) + (isset($ojtMap['Incomplete']) ? $ojtMap['Incomplete'] : 0),
            'legacy_ojt' => $countLinked('SELECT COUNT(*) AS c FROM `ojt` o JOIN `student` s ON s.student_id = o.student_id WHERE ' . $where),
            'employed' => $countLinked("SELECT COUNT(DISTINCT g.`student_id`) AS c FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.`deleted_at` IS NULL AND g.`employment_type` NOT IN ('Unemployed', 'Further Studies') AND g.`employment_type` IS NOT NULL AND g.`employment_type` <> '' AND " . $where),
            'unemployed' => $countLinked("SELECT COUNT(DISTINCT g.`student_id`) AS c FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.`deleted_at` IS NULL AND (g.`employment_type` = 'Unemployed' OR g.`current_status` = 'Unemployed') AND " . $where),
            'course_related' => $countLinked("SELECT COUNT(DISTINCT g.`student_id`) AS c FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.`deleted_at` IS NULL AND g.`course_related` = 'Yes' AND " . $where),
            'foreign_jobs' => $countLinked("SELECT COUNT(DISTINCT g.`student_id`) AS c FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.`deleted_at` IS NULL AND (g.`location_scope` = 'Foreign' OR g.`employment_type` = 'Foreign Employment') AND " . $where),
            'self_employed' => $countLinked("SELECT COUNT(DISTINCT g.`student_id`) AS c FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.`deleted_at` IS NULL AND g.`employment_type` = 'Self-employed' AND " . $where),
            'local_jobs' => $countLinked("SELECT COUNT(DISTINCT g.`student_id`) AS c FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.`deleted_at` IS NULL AND g.`location_scope` = 'Local' AND " . $where),
            'higher' => $countLinked('SELECT COUNT(DISTINCT h.`student_id`) AS c FROM `graduate_higher_education` h JOIN `student` s ON s.student_id = h.student_id WHERE h.`deleted_at` IS NULL AND ' . $where),
            'alumni' => $countLinked('SELECT COUNT(*) AS c FROM `alumni_profiles` a JOIN `student` s ON s.student_id = a.student_id WHERE a.`deleted_at` IS NULL AND ' . $where),
            'alumni_verified' => $countLinked('SELECT COUNT(*) AS c FROM `alumni_profiles` a JOIN `student` s ON s.student_id = a.student_id WHERE a.`deleted_at` IS NULL AND a.`verified` = 1 AND ' . $where),
            'imports' => (int) $this->scalar('SELECT COUNT(*) FROM `historical_imports`', '', []),
            'years' => $years,
            'courses' => $courses,
        ];
    }

    public function report($key, array $filters, $limit) {
        $limit = max(1, min(2000, (int) $limit));
        list($where, $types, $params) = $this->studentWhere($filters);
        $headers = ['Registration', 'Name', 'NIC', 'Status', 'Course', 'Year', 'Origin'];
        if ($key === 'by_year') {
            $rows = $this->fetchAll('SELECT LEFT(e.student_enroll_date, 4) AS y, COUNT(DISTINCT s.student_id) AS students FROM `student` s JOIN `student_enroll` e ON e.student_id = s.student_id WHERE e.student_enroll_date IS NOT NULL AND ' . $where . ' GROUP BY y ORDER BY y', $types, $params);
            return ['headers' => ['Admission year', 'Students'], 'rows' => $rows];
        }
        if ($key === 'by_course') {
            $rows = $this->fetchAll('SELECT c.course_name, c.course_nvq_level, d.department_name, COUNT(DISTINCT s.student_id) AS students FROM `student` s JOIN `student_enroll` e ON e.student_id = s.student_id JOIN `course` c ON c.course_id = e.course_id LEFT JOIN `department` d ON d.department_id = c.department_id WHERE ' . $where . ' GROUP BY c.course_id, c.course_name, c.course_nvq_level, d.department_name ORDER BY students DESC', $types, $params);
            return ['headers' => ['Course', 'NVQ', 'Department', 'Students'], 'rows' => $rows];
        }
        if ($key === 'by_batch') {
            $rows = $this->fetchAll('SELECT g.name, g.academic_year, c.course_name, COUNT(DISTINCT s.student_id) AS students FROM `groups` g JOIN `group_students` gs ON gs.group_id = g.id JOIN `student` s ON s.student_id = gs.student_id LEFT JOIN `course` c ON c.course_id = g.course_id WHERE ' . $where . ' GROUP BY g.id, g.name, g.academic_year, c.course_name ORDER BY g.academic_year DESC, g.name', $types, $params);
            return ['headers' => ['Batch', 'Academic year', 'Course', 'Students'], 'rows' => $rows];
        }
        if ($key === 'completion_rate') {
            $rows = $this->fetchAll("SELECT c.course_name, COUNT(DISTINCT s.student_id) AS enrolled, COUNT(DISTINCT CASE WHEN s.lifecycle_status = 'Completed' OR e.student_enroll_status = 'Completed' THEN s.student_id END) AS completed FROM `student` s JOIN `student_enroll` e ON e.student_id = s.student_id JOIN `course` c ON c.course_id = e.course_id WHERE " . $where . ' GROUP BY c.course_id, c.course_name ORDER BY c.course_name', $types, $params);
            $out = [];
            foreach ($rows as $row) {
                $enrolled = (int) $row['enrolled'];
                $out[] = [$row['course_name'], $enrolled, (int) $row['completed'], $enrolled > 0 ? round(((int) $row['completed'] / $enrolled) * 100, 1) . '%' : '0%'];
            }
            return ['headers' => ['Course', 'Enrolled', 'Completed', 'Rate'], 'rows' => $out, 'plain' => true];
        }
        if ($key === 'nvq_rate') {
            $rows = $this->fetchAll("SELECT c.course_nvq_level, COUNT(DISTINCT s.student_id) AS students, COUNT(DISTINCT CASE WHEN sc.status IN ('Issued','Replaced') THEN sc.student_id END) AS certified FROM `student` s JOIN `student_enroll` e ON e.student_id = s.student_id JOIN `course` c ON c.course_id = e.course_id LEFT JOIN `student_certificates` sc ON sc.student_id = s.student_id AND sc.deleted_at IS NULL WHERE " . $where . ' GROUP BY c.course_nvq_level ORDER BY c.course_nvq_level', $types, $params);
            $out = [];
            foreach ($rows as $row) {
                $students = (int) $row['students'];
                $out[] = [$row['course_nvq_level'], $students, (int) $row['certified'], $students > 0 ? round(((int) $row['certified'] / $students) * 100, 1) . '%' : '0%'];
            }
            return ['headers' => ['NVQ level', 'Students', 'Certificates issued', 'Rate'], 'rows' => $out, 'plain' => true];
        }
        if ($key === 'local_foreign') {
            $rows = $this->fetchAll('SELECT IFNULL(g.location_scope, \'Not recorded\') AS location_scope, g.employment_type, COUNT(DISTINCT s.student_id) AS students FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.deleted_at IS NULL AND ' . $where . ' GROUP BY g.location_scope, g.employment_type ORDER BY students DESC', $types, $params);
            return ['headers' => ['Location', 'Employment type', 'Graduates'], 'rows' => $rows];
        }
        if ($key === 'self_employed') {
            $rows = $this->fetchAll("SELECT s.student_id, s.student_fullname, g.organisation, g.self_employed_detail, g.business_detail, g.start_date FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.deleted_at IS NULL AND g.employment_type = 'Self-employed' AND " . $where . ' ORDER BY g.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Registration', 'Name', 'Organisation', 'Self-employment', 'Business', 'Start'], 'rows' => $rows];
        }
        if ($key === 'first_job') {
            $rows = $this->fetchAll('SELECT s.student_id, s.student_fullname, g.organisation, g.days_to_first_job, g.start_date, g.course_related FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.deleted_at IS NULL AND g.days_to_first_job IS NOT NULL AND ' . $where . ' ORDER BY g.days_to_first_job LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Registration', 'Name', 'Organisation', 'Days to first job', 'Start', 'Course related'], 'rows' => $rows];
        }
        if ($key === 'feedback') {
            $rows = $this->fetchAll('SELECT s.student_id, s.student_fullname, f.employer_name, f.rating, f.feedback FROM `employer_feedback` f JOIN `student` s ON s.student_id = f.student_id WHERE f.deleted_at IS NULL AND ' . $where . ' ORDER BY f.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Registration', 'Name', 'Employer', 'Rating', 'Feedback'], 'rows' => $rows];
        }
        if ($key === 'certificates' || $key === 'certificates_pending') {
            $statusSql = $key === 'certificates_pending' ? " AND sc.status IN ('Pending','Processing','Received','Ready for Collection')" : '';
            $rows = $this->fetchAll('SELECT sc.certificate_no, sc.printed_name, s.student_id, sc.course_name, sc.nvq_level, sc.status, sc.issuance_date, sc.pending_reason FROM `student_certificates` sc JOIN `student` s ON s.student_id = sc.student_id WHERE sc.deleted_at IS NULL ' . $statusSql . ' AND ' . $where . ' ORDER BY sc.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Certificate', 'Name on certificate', 'Registration', 'Course', 'NVQ', 'Status', 'Issued', 'Pending reason'], 'rows' => $rows];
        }
        if ($key === 'ojt' || $key === 'ojt_completed') {
            $ojtExtra = $key === 'ojt_completed' ? " AND p.status = 'Completed'" : '';
            $rows = $this->fetchAll('SELECT s.student_id, s.student_fullname, i.name, p.status, p.start_date, p.end_date, p.attendance_percent FROM `student_ojt` p JOIN `student` s ON s.student_id = p.student_id LEFT JOIN `ojt_industries` i ON i.id = p.industry_id WHERE p.deleted_at IS NULL' . $ojtExtra . ' AND ' . $where . ' ORDER BY p.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Registration', 'Name', 'Industry', 'Status', 'Start', 'End', 'Attendance %'], 'rows' => $rows];
        }
        if ($key === 'employment' || $key === 'unemployed') {
            $extra = $key === 'unemployed' ? " AND (g.employment_type = 'Unemployed' OR g.current_status = 'Unemployed')" : '';
            $rows = $this->fetchAll('SELECT s.student_id, s.student_fullname, g.organisation, g.designation, g.employment_type, g.location_scope, g.course_related, g.current_status, g.days_to_first_job FROM `graduate_employment` g JOIN `student` s ON s.student_id = g.student_id WHERE g.deleted_at IS NULL ' . $extra . ' AND ' . $where . ' ORDER BY g.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Registration', 'Name', 'Organisation', 'Designation', 'Type', 'Location', 'Course related', 'Current', 'Days to first job'], 'rows' => $rows];
        }
        if ($key === 'higher') {
            $rows = $this->fetchAll('SELECT s.student_id, s.student_fullname, h.institution, h.programme, h.qualification, h.start_date FROM `graduate_higher_education` h JOIN `student` s ON s.student_id = h.student_id WHERE h.deleted_at IS NULL AND ' . $where . ' ORDER BY h.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Registration', 'Name', 'Institution', 'Programme', 'Qualification', 'Start'], 'rows' => $rows];
        }
        if ($key === 'alumni') {
            $rows = $this->fetchAll('SELECT a.alumni_code, s.student_id, s.student_fullname, a.membership, a.verified, a.contact_phone FROM `alumni_profiles` a JOIN `student` s ON s.student_id = a.student_id WHERE a.deleted_at IS NULL AND ' . $where . ' ORDER BY a.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Alumni ID', 'Registration', 'Name', 'Membership', 'Verified', 'Phone'], 'rows' => $rows];
        }
        if ($key === 'tracer') {
            $rows = $this->fetchAll('SELECT s.student_id, s.student_fullname, t.survey_year, t.employed, t.course_related, t.location_scope, t.higher_study FROM `graduate_tracer_surveys` t JOIN `student` s ON s.student_id = t.student_id WHERE t.deleted_at IS NULL AND ' . $where . ' ORDER BY t.id DESC LIMIT ' . $limit, $types, $params);
            return ['headers' => ['Registration', 'Name', 'Year', 'Employed', 'Course related', 'Location', 'Higher study'], 'rows' => $rows];
        }
        if ($key === 'attendance') {
            $rows = $this->fetchAll('SELECT s.student_id, s.student_fullname, SUM(CASE WHEN a.attendance_status = 1 THEN 1 ELSE 0 END) AS present_count, COUNT(a.attendance_id) AS total_count FROM `student` s JOIN `attendance` a ON a.student_id = s.student_id WHERE ' . $where . ' GROUP BY s.student_id, s.student_fullname ORDER BY s.student_id LIMIT ' . $limit, $types, $params);
            $out = [];
            foreach ($rows as $row) {
                $total = (int) $row['total_count'];
                $out[] = [
                    $row['student_id'],
                    $row['student_fullname'],
                    $row['present_count'],
                    $total,
                    $total > 0 ? round(((int) $row['present_count'] / $total) * 100, 1) . '%' : '0%',
                ];
            }
            return ['headers' => ['Registration', 'Name', 'Present', 'Sessions', 'Percent'], 'rows' => $out, 'plain' => true];
        }
        $status = '';
        if ($key === 'active') {
            $status = 'Active';
        } elseif ($key === 'completed') {
            $status = 'Completed';
        } elseif ($key === 'discontinued') {
            $status = 'Discontinued';
        } elseif ($key === 'suspended') {
            $status = 'Suspended';
        } elseif ($key === 'withdrawn') {
            $status = 'Withdrawn';
        }
        if ($status !== '') {
            $filters['status'] = $status;
        }
        $found = $this->searchStudents($filters, 1, $limit);
        $rows = [];
        foreach ($found['rows'] as $row) {
            $rows[] = [
                $row['student_id'],
                $row['student_fullname'],
                $row['student_nic'],
                self::label($row),
                $row['course_name'],
                $row['academic_year'],
                $row['record_origin'],
            ];
        }
        return ['headers' => $headers, 'rows' => $rows, 'plain' => true];
    }

    public function startImport($fileName, $by) {
        return $this->insertRow('historical_imports', [
            'file_name' => $fileName,
            'imported_by' => $by,
            'status' => 'committed',
        ]);
    }

    public function finishImport($importId, $total, $success, $errors) {
        $stmt = $this->db->prepare('UPDATE `historical_imports` SET `total_rows` = ?, `success_rows` = ?, `error_rows` = ? WHERE `id` = ?');
        $importId = (int) $importId;
        $stmt->bind_param('iiii', $total, $success, $errors, $importId);
        $stmt->execute();
    }

    public function logImport($importId, $rowNo, $studentId, $outcome, $message) {
        $this->insertRow('historical_import_rows', [
            'import_id' => (int) $importId,
            'row_no' => (int) $rowNo,
            'student_id' => substr((string) $studentId, 0, 20),
            'outcome' => $outcome,
            'message' => substr((string) $message, 0, 255),
        ]);
    }

    public function classifyImport(array $row) {
        $id = trim((string) ($row['student_id'] ?? ''));
        $name = trim((string) ($row['full_name'] ?? ''));
        $nic = strtoupper(trim((string) ($row['nic'] ?? '')));
        if ($id === '' || $name === '' || $nic === '') {
            return ['outcome' => 'incomplete', 'message' => 'Registration number, full name and NIC are required.'];
        }
        if (strlen($id) > 20 || strlen($nic) > 20 || strlen($name) > 255) {
            return ['outcome' => 'error', 'message' => 'A value is longer than the student record allows.'];
        }
        $byId = $this->findStudent($id);
        $byNic = $this->fetchAll('SELECT `student_id`, `student_nic` FROM `student` WHERE UPPER(TRIM(`student_nic`)) = ? LIMIT 1', 's', [$nic]);
        $nicOwner = $byNic ? $byNic[0]['student_id'] : '';
        if ($nicOwner !== '' && $nicOwner !== $id) {
            return ['outcome' => 'duplicate', 'message' => 'NIC already belongs to ' . $nicOwner . '.'];
        }
        if ($byId && strtoupper(trim((string) $byId['student_nic'])) !== $nic) {
            return ['outcome' => 'duplicate', 'message' => 'Registration number already belongs to a different NIC.'];
        }
        if ($byId) {
            return ['outcome' => 'matched', 'message' => 'Existing student. Blank lifecycle fields can be filled. Name and NIC stay as they are.'];
        }
        return ['outcome' => 'create', 'message' => 'New historical student.'];
    }

    public function applyImport($importId, array $row, $by) {
        $class = $this->classifyImport($row);
        $id = trim((string) ($row['student_id'] ?? ''));
        if ($class['outcome'] === 'duplicate' || $class['outcome'] === 'incomplete' || $class['outcome'] === 'error') {
            return $class;
        }
        $blank = [
            'admission_no' => trim((string) ($row['admission_no'] ?? '')),
            'intake' => trim((string) ($row['intake'] ?? '')),
            'trade' => trim((string) ($row['trade'] ?? '')),
            'course_commencement' => $this->dateOrNull($row['course_commencement'] ?? ''),
            'expected_completion' => $this->dateOrNull($row['expected_completion'] ?? ''),
            'actual_completion' => $this->dateOrNull($row['actual_completion'] ?? ''),
            'previous_employment' => trim((string) ($row['previous_employment'] ?? '')),
            'parent_name' => trim((string) ($row['parent_name'] ?? '')),
            'parent_phone' => trim((string) ($row['parent_phone'] ?? '')),
            'discontinuation_reason' => trim((string) ($row['discontinuation_reason'] ?? '')),
        ];
        $status = $this->mapStatus($row['status'] ?? '');
        if ($class['outcome'] === 'matched') {
            $student = $this->findStudent($id);
            $fill = [];
            foreach ($blank as $field => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $current = $student[$field] ?? null;
                if ($current === null || $current === '') {
                    $fill[$field] = $value;
                }
            }
            if ($status !== '' && empty($student['lifecycle_status'])) {
                $fill['lifecycle_status'] = $status;
            }
            if (empty($student['historical_import_id'])) {
                $fill['historical_import_id'] = (int) $importId;
            }
            if ($fill) {
                $error = $this->saveStudentFields($id, $fill, $by);
                if ($error !== '') {
                    return ['outcome' => 'error', 'message' => $error];
                }
            }
            $this->linkCourse($id, $row, $status);
            return ['outcome' => 'matched', 'message' => 'Matched. Existing name, NIC and filled fields were kept.'];
        }
        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $id . '@slgtimis.local';
        }
        $used = $this->fetchAll('SELECT `student_id` FROM `student` WHERE `student_email` = ? LIMIT 1', 's', [$email]);
        if ($used) {
            return ['outcome' => 'error', 'message' => 'Email is already used by another student.'];
        }
        $life = $status !== '' ? $status : 'Active';
        $active = in_array($life, ['Active', 'Completed'], true) ? 'Active' : 'Inactive';
        $data = array_merge($blank, [
            'student_id' => $id,
            'student_fullname' => trim((string) $row['full_name']),
            'student_ininame' => trim((string) ($row['initials'] ?? '')),
            'student_nic' => strtoupper(trim((string) $row['nic'])),
            'student_email' => $email,
            'student_gender' => trim((string) ($row['gender'] ?? '')),
            'student_dob' => trim((string) ($row['dob'] ?? '')),
            'student_address' => trim((string) ($row['address'] ?? '')),
            'student_status' => $active,
            'lifecycle_status' => $life,
            'record_origin' => 'historical',
            'historical_import_id' => (int) $importId,
        ]);
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }
        $data['student_id'] = $id;
        $data['student_fullname'] = trim((string) $row['full_name']);
        $data['student_nic'] = strtoupper(trim((string) $row['nic']));
        $data['student_email'] = $email;
        $data['student_status'] = $active;
        $data['lifecycle_status'] = $life;
        $data['record_origin'] = 'historical';
        $this->insertRow('student', $data);
        $this->linkCourse($id, $row, $life);
        $this->audit($id, 'import', 'record_origin', '', 'historical', $by);
        if ($life === 'Completed') {
            $this->registerAlumni($id, $by);
        }
        return ['outcome' => 'created', 'message' => 'Historical student added.'];
    }

    public function importSummary() {
        return [
            'batches' => (int) $this->scalar('SELECT COUNT(*) FROM `historical_imports`', '', []),
            'rows' => $this->fetchAll('SELECT `outcome`, COUNT(*) AS c FROM `historical_import_rows` GROUP BY `outcome`', '', []),
        ];
    }

    private function linkCourse($studentId, array $row, $status) {
        $courseId = trim((string) ($row['course_id'] ?? ''));
        if ($courseId === '') {
            return;
        }
        $course = $this->fetchAll('SELECT `course_id` FROM `course` WHERE `course_id` = ? LIMIT 1', 's', [$courseId]);
        if (!$course) {
            return;
        }
        $exists = $this->fetchAll('SELECT 1 FROM `student_enroll` WHERE `student_id` = ? AND `course_id` = ? LIMIT 1', 'ss', [$studentId, $courseId]);
        if ($exists) {
            return;
        }
        $year = trim((string) ($row['admission_year'] ?? ''));
        if ($year === '' && !empty($row['registration_date'])) {
            $year = substr((string) $row['registration_date'], 0, 4);
        }
        $academic = (preg_match('/^\d{4}$/', $year)) ? $year . '/' . ((int) $year + 1) : ($year !== '' ? $year : date('Y') . '/' . ((int) date('Y') + 1));
        $enrollDate = $this->dateOrNull($row['registration_date'] ?? '');
        if ($enrollDate === null && preg_match('/^\d{4}$/', $year)) {
            $enrollDate = $year . '-01-01';
        }
        if ($enrollDate === null) {
            $enrollDate = date('Y-m-d');
        }
        $exit = $this->dateOrNull($row['actual_completion'] ?? '');
        if ($exit === null) {
            $exit = $enrollDate;
        }
        $enrollStatus = 'Following';
        if ($status === 'Completed') {
            $enrollStatus = 'Completed';
        } elseif (in_array($status, ['Discontinued', 'Withdrawn'], true)) {
            $enrollStatus = 'Dropout';
        }
        $mode = 'Full';
        $stmt = $this->db->prepare('INSERT INTO `student_enroll` (`student_id`, `course_id`, `course_mode`, `academic_year`, `student_enroll_date`, `student_enroll_exit_date`, `student_enroll_status`) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssss', $studentId, $courseId, $mode, $academic, $enrollDate, $exit, $enrollStatus);
        $stmt->execute();
    }

    private function mapStatus($value) {
        $value = trim((string) $value);
        $map = [
            'active' => 'Active',
            'following' => 'Active',
            'completed' => 'Completed',
            'dropout' => 'Discontinued',
            'discontinued' => 'Discontinued',
            'inactive' => 'Discontinued',
            'suspended' => 'Suspended',
            'withdrawn' => 'Withdrawn',
            'long absent' => 'Suspended',
        ];
        $key = strtolower($value);
        if (isset($map[$key])) {
            return $map[$key];
        }
        return in_array($value, LifecycleForms::statuses(), true) ? $value : '';
    }

    private function dateOrNull($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function ensureClearanceRowsSeen() {
        return true;
    }

    private function rowsFor($table, $studentId) {
        $this->assertTable($table);
        $cols = $this->tableColumns($table);
        $sql = 'SELECT * FROM `' . $table . '` WHERE `student_id` = ?';
        if (isset($cols['deleted_at'])) {
            $sql .= ' AND `deleted_at` IS NULL';
        }
        $sql .= ' ORDER BY `id` DESC';
        return $this->fetchAll($sql, 's', [$studentId]);
    }

    private function studentWhere(array $filters) {
        $where = ['1=1'];
        $types = '';
        $params = [];
        if (!empty($filters['department_limit'])) {
            $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` ex JOIN `course` cx ON cx.course_id = ex.course_id WHERE ex.student_id = s.student_id AND cx.department_id = ?)';
            $types .= 's';
            $params[] = $filters['department_limit'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(s.student_id LIKE ? OR s.student_fullname LIKE ? OR s.student_nic LIKE ? OR s.admission_no LIKE ? OR EXISTS (SELECT 1 FROM `student_certificates` scq WHERE scq.student_id = s.student_id AND scq.deleted_at IS NULL AND scq.certificate_no LIKE ?))';
            $like = '%' . $filters['q'] . '%';
            $types .= 'sssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['year'])) {
            if (strpos((string) $filters['year'], '/') !== false) {
                $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` ey WHERE ey.student_id = s.student_id AND ey.academic_year = ?)';
                $types .= 's';
                $params[] = $filters['year'];
            } else {
                $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` ey WHERE ey.student_id = s.student_id AND (LEFT(ey.student_enroll_date, 4) = ? OR ey.academic_year LIKE ?))';
                $types .= 'ss';
                $params[] = $filters['year'];
                $params[] = $filters['year'] . '%';
            }
        }
        if (!empty($filters['course_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` ec WHERE ec.student_id = s.student_id AND ec.course_id = ?)';
            $types .= 's';
            $params[] = $filters['course_id'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` ed JOIN `course` cd ON cd.course_id = ed.course_id WHERE ed.student_id = s.student_id AND cd.department_id = ?)';
            $types .= 's';
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'Active') {
                $where[] = "(s.lifecycle_status = 'Active' OR (s.lifecycle_status IS NULL AND s.student_status = 'Active'))";
            } elseif ($filters['status'] === 'Inactive') {
                $where[] = "(s.lifecycle_status IS NULL AND s.student_status <> 'Active')";
            } else {
                $where[] = 's.lifecycle_status = ?';
                $types .= 's';
                $params[] = $filters['status'];
            }
        }
        if (!empty($filters['graduation_year'])) {
            $where[] = '(LEFT(s.actual_completion, 4) = ? OR EXISTS (SELECT 1 FROM `student_enroll` eg WHERE eg.student_id = s.student_id AND eg.student_enroll_status = \'Completed\' AND LEFT(eg.student_enroll_exit_date, 4) = ?))';
            $types .= 'ss';
            $params[] = $filters['graduation_year'];
            $params[] = $filters['graduation_year'];
        }
        if (!empty($filters['nvq'])) {
            $where[] = 'EXISTS (SELECT 1 FROM `student_enroll` en JOIN `course` cn ON cn.course_id = en.course_id WHERE en.student_id = s.student_id AND cn.course_nvq_level = ?)';
            $types .= 's';
            $params[] = $filters['nvq'];
        }
        if (!empty($filters['batch'])) {
            $where[] = 'EXISTS (SELECT 1 FROM `group_students` gb WHERE gb.student_id = s.student_id AND gb.group_id = ?)';
            $types .= 's';
            $params[] = (string) $filters['batch'];
        }
        if (!empty($filters['trade'])) {
            $where[] = 's.trade LIKE ?';
            $types .= 's';
            $params[] = '%' . $filters['trade'] . '%';
        }
        if (!empty($filters['origin'])) {
            $where[] = 's.record_origin = ?';
            $types .= 's';
            $params[] = $filters['origin'];
        }
        return [implode(' AND ', $where), $types, $params];
    }

    private function ensureStudentColumns() {
        $columns = [
            'admission_no' => 'VARCHAR(40) NULL DEFAULT NULL',
            'intake' => 'VARCHAR(40) NULL DEFAULT NULL',
            'trade' => 'VARCHAR(120) NULL DEFAULT NULL',
            'course_commencement' => 'DATE NULL DEFAULT NULL',
            'expected_completion' => 'DATE NULL DEFAULT NULL',
            'actual_completion' => 'DATE NULL DEFAULT NULL',
            'previous_employment' => 'VARCHAR(255) NULL DEFAULT NULL',
            'parent_name' => 'VARCHAR(255) NULL DEFAULT NULL',
            'parent_phone' => 'VARCHAR(20) NULL DEFAULT NULL',
            'lifecycle_status' => 'VARCHAR(20) NULL DEFAULT NULL',
            'discontinuation_reason' => 'VARCHAR(255) NULL DEFAULT NULL',
            'record_origin' => "VARCHAR(20) NOT NULL DEFAULT 'system'",
            'historical_import_id' => 'INT NULL DEFAULT NULL',
        ];
        foreach ($columns as $name => $definition) {
            if (!$this->hasColumn('student', $name)) {
                if (!$this->db->query('ALTER TABLE `student` ADD COLUMN `' . $name . '` ' . $definition)) {
                    throw new RuntimeException('Student record could not be extended.');
                }
                $this->columnCache['student'] = null;
            }
        }
        $index = $this->db->query("SHOW INDEX FROM `student` WHERE Key_name = 'uq_student_admission_no'");
        if ($index && $index->num_rows === 0) {
            $this->db->query('ALTER TABLE `student` ADD UNIQUE KEY `uq_student_admission_no` (`admission_no`)');
        }
        $nic = $this->db->query("SHOW COLUMNS FROM `student` LIKE 'student_nic'");
        $nicRow = $nic ? $nic->fetch_assoc() : null;
        if ($nicRow && stripos((string) $nicRow['Type'], 'varchar(12)') !== false) {
            $this->db->query('ALTER TABLE `student` MODIFY `student_nic` VARCHAR(20) NOT NULL');
        }
    }

    private function ensureCrudColumns() {
        $tables = [
            'student_module_progress', 'student_certificates', 'certificate_events', 'ojt_industries',
            'student_ojt', 'ojt_monitoring', 'ojt_evaluations', 'graduate_employment',
            'graduate_higher_education', 'employer_feedback', 'alumni_profiles', 'alumni_events',
            'alumni_engagement', 'student_documents', 'graduate_tracer_surveys',
            'student_discipline', 'student_welfare', 'student_correspondence', 'lifecycle_approvals',
        ];
        foreach ($tables as $table) {
            if (!$this->tableColumns($table)) {
                continue;
            }
            $this->addColumn($table, 'updated_by', 'VARCHAR(50) NULL DEFAULT NULL');
            if ($this->hasColumn($table, 'deleted_at')) {
                $this->addColumn($table, 'deleted_by', 'VARCHAR(50) NULL DEFAULT NULL');
                $this->addColumn($table, 'delete_reason', 'VARCHAR(255) NULL DEFAULT NULL');
            }
        }
        $this->addColumn('student_audit_logs', 'module_name', 'VARCHAR(40) NULL DEFAULT NULL');
        $this->addColumn('student_audit_logs', 'record_id', 'INT NULL DEFAULT NULL');
        $this->addColumn('student_audit_logs', 'ip_address', 'VARCHAR(45) NULL DEFAULT NULL');
    }

    private function addColumn($table, $name, $definition) {
        if ($this->hasColumn($table, $name)) {
            return;
        }
        if (!$this->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $name . '` ' . $definition)) {
            throw new RuntimeException('A lifecycle table could not be extended.');
        }
        $this->columnCache[$table] = null;
    }

    private function audit($studentId, $action, $field, $old, $new, $by) {
        $this->insertRow('student_audit_logs', [
            'student_id' => $studentId,
            'action' => $action,
            'field_name' => $field,
            'old_value' => substr((string) $old, 0, 255),
            'new_value' => substr((string) $new, 0, 255),
            'changed_by' => $by,
        ]);
    }

    private function scalar($sql, $types, array $params) {
        $rows = $this->fetchAll($sql, $types, $params);
        if (!$rows) {
            return 0;
        }
        $row = array_values($rows[0]);
        return $row[0];
    }

    private function fetchAll($sql, $types, array $params) {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '') {
            $this->bind($stmt, $types, $params);
        }
        if (!$stmt->execute()) {
            return [];
        }
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    private function bind($stmt, $types, array $params) {
        $refs = [];
        $refs[] = &$types;
        foreach ($params as $i => $value) {
            $refs[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    private function hasColumn($table, $column) {
        $cols = $this->tableColumns($table);
        return isset($cols[$column]);
    }

    private function tableColumns($table) {
        if (!isset($this->columnCache[$table]) || $this->columnCache[$table] === null) {
            $this->columnCache[$table] = [];
            $result = $this->db->query('SHOW COLUMNS FROM `' . $table . '`');
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $this->columnCache[$table][$row['Field']] = $row['Type'];
                }
            }
        }
        return $this->columnCache[$table];
    }

    private function assertTable($table) {
        $allowed = [
            'student', 'student_status_history', 'student_audit_logs', 'student_module_progress',
            'student_certificates', 'certificate_events', 'ojt_industries', 'student_ojt',
            'ojt_monitoring', 'ojt_evaluations', 'graduate_employment', 'graduate_higher_education',
            'employer_feedback', 'alumni_profiles', 'alumni_events', 'alumni_engagement',
            'student_clearance', 'student_documents', 'graduate_tracer_surveys',
            'historical_imports', 'historical_import_rows',
            'student_discipline', 'student_welfare', 'student_correspondence', 'lifecycle_approvals',
        ];
        if (!in_array($table, $allowed, true)) {
            throw new RuntimeException('Unknown record type.');
        }
    }
}
