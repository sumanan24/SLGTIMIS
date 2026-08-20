<?php
/**
 * Student complaint letter records, student links, and audit trail.
 */
class ComplaintLetterModel extends Model {
    protected $table = 'complaint_letters';
    private static $tablesEnsured = false;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';

    public function __construct() {
        parent::__construct();
        $this->ensureTables();
    }

    protected function getPrimaryKey() {
        return 'id';
    }

    public function ensureTables(): void {
        if (self::$tablesEnsured) {
            return;
        }
        $sqlFile = BASE_PATH . '/database/complaint_letters.sql';
        if (!is_readable($sqlFile)) {
            self::$tablesEnsured = true;
            return;
        }
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            self::$tablesEnsured = true;
            return;
        }
        try {
            $conn = $this->db->getConnection();
            $conn->multi_query($sql);
            while ($conn->more_results() && $conn->next_result()) {
                /* flush */
            }
        } catch (Throwable $e) {
            error_log('ComplaintLetterModel::ensureTables: ' . $e->getMessage());
        }
        self::$tablesEnsured = true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllPrepared(string $sql, string $types, array $params): array {
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '' && $params !== []) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        return $rows;
    }

    public function generateReferenceNo(): string {
        $year = date('Y');
        $prefix = 'CL-' . $year . '-';
        $sql = 'SELECT `reference_no` FROM `complaint_letters` WHERE `reference_no` LIKE ? ORDER BY `id` DESC LIMIT 1';
        $like = $prefix . '%';
        $rows = $this->fetchAllPrepared($sql, 's', [$like]);
        $next = 1;
        if (!empty($rows[0]['reference_no'])) {
            $tail = substr((string) $rows[0]['reference_no'], strlen($prefix));
            $next = max(1, (int) $tail + 1);
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listComplaints(array $filters, int $page = 1, int $perPage = 20, ?string $forcedDepartmentId = null): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['cl.`deleted_at` IS NULL'];
        $types = '';
        $params = [];

        if ($forcedDepartmentId !== null && $forcedDepartmentId !== '') {
            $where[] = 'cl.`department_id` = ?';
            $types .= 's';
            $params[] = $forcedDepartmentId;
        } elseif (!empty($filters['department_id'])) {
            $where[] = 'cl.`department_id` = ?';
            $types .= 's';
            $params[] = trim((string) $filters['department_id']);
        }
        if (!empty($filters['course_id'])) {
            $where[] = 'cl.`course_id` = ?';
            $types .= 's';
            $params[] = trim((string) $filters['course_id']);
        }
        if (!empty($filters['academic_year'])) {
            $where[] = 'cl.`academic_year` = ?';
            $types .= 's';
            $params[] = trim((string) $filters['academic_year']);
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(cl.`reference_no` LIKE ? OR cl.`subject` LIKE ? OR cl.`recipient_name` LIKE ? OR EXISTS (
                SELECT 1 FROM `complaint_letter_students` cls
                WHERE cls.`complaint_letter_id` = cl.`id`
                AND (cls.`student_id` LIKE ? OR cls.`student_name` LIKE ?)
            ))';
            $q = '%' . $search . '%';
            $types .= str_repeat('s', 5);
            for ($i = 0; $i < 5; $i++) {
                $params[] = $q;
            }
        }

        $whereSql = implode(' AND ', $where);
        $countSql = 'SELECT COUNT(*) AS c FROM `complaint_letters` cl WHERE ' . $whereSql;
        $countRows = $this->fetchAllPrepared($countSql, $types, $params);
        $total = (int) ($countRows[0]['c'] ?? 0);

        $sql = 'SELECT cl.*, d.`department_name`, c.`course_name`, '
            . 'u1.`user_name` AS created_by_name, u2.`user_name` AS updated_by_name '
            . 'FROM `complaint_letters` cl '
            . 'LEFT JOIN `department` d ON d.`department_id` = cl.`department_id` '
            . 'LEFT JOIN `course` c ON c.`course_id` = cl.`course_id` '
            . 'LEFT JOIN `user` u1 ON u1.`user_id` = cl.`created_by` '
            . 'LEFT JOIN `user` u2 ON u2.`user_id` = cl.`updated_by` '
            . 'WHERE ' . $whereSql . ' ORDER BY cl.`letter_date` DESC, cl.`id` DESC LIMIT ? OFFSET ?';
        $typesList = $types . 'ii';
        $paramsList = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAllPrepared($sql, $typesList, $paramsList);

        foreach ($rows as &$row) {
            $row['student_count'] = $this->countStudents((int) ($row['id'] ?? 0));
        }
        unset($row);

        return ['rows' => $rows, 'total' => $total];
    }

    public function countStudents(int $complaintId): int {
        $sql = 'SELECT COUNT(*) AS c FROM `complaint_letter_students` WHERE `complaint_letter_id` = ?';
        $rows = $this->fetchAllPrepared($sql, 'i', [$complaintId]);

        return (int) ($rows[0]['c'] ?? 0);
    }

    public function findComplaint(int $id, bool $includeDeleted = false): ?array {
        $sql = 'SELECT cl.*, d.`department_name`, c.`course_name` '
            . 'FROM `complaint_letters` cl '
            . 'LEFT JOIN `department` d ON d.`department_id` = cl.`department_id` '
            . 'LEFT JOIN `course` c ON c.`course_id` = cl.`course_id` '
            . 'WHERE cl.`id` = ?';
        if (!$includeDeleted) {
            $sql .= ' AND cl.`deleted_at` IS NULL';
        }
        $sql .= ' LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 'i', [$id]);

        return $rows[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getComplaintStudents(int $complaintId): array {
        $sql = 'SELECT * FROM `complaint_letter_students` WHERE `complaint_letter_id` = ? ORDER BY `student_name` ASC, `id` ASC';

        return $this->fetchAllPrepared($sql, 'i', [$complaintId]);
    }

    /**
     * @param list<string> $studentIds
     * @return list<array<string, mixed>>
     */
    public function resolveStudentsForComplaint(array $studentIds, string $departmentId, string $courseId, string $academicYear): array {
        $studentIds = array_values(array_unique(array_filter(array_map('strval', $studentIds))));
        if ($studentIds === []) {
            return [];
        }
        $resolved = [];
        require_once BASE_PATH . '/models/StudentEnrollmentModel.php';
        $enrollModel = new StudentEnrollmentModel();
        foreach ($studentIds as $studentId) {
            $studentId = trim($studentId);
            if ($studentId === '') {
                continue;
            }
            $enroll = $this->getStudentEnrollmentInScope($studentId, $departmentId, $courseId, $academicYear);
            if (!$enroll) {
                continue;
            }
            $resolved[] = [
                'student_id' => $studentId,
                'student_name' => trim((string) ($enroll['student_fullname'] ?? $enroll['student_name'] ?? '')),
                'student_reg_no' => trim((string) ($enroll['student_id'] ?? $studentId)),
                'course_name' => trim((string) ($enroll['course_name'] ?? '')),
                'department_id' => trim((string) ($enroll['department_id'] ?? $departmentId)),
            ];
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStudentEnrollmentInScope(string $studentId, string $departmentId, string $courseId, string $academicYear): ?array {
        $sql = 'SELECT s.`student_id`, s.`student_fullname`, se.`academic_year`, se.`course_id`, '
            . 'c.`course_name`, c.`department_id`, d.`department_name` '
            . 'FROM `student` s '
            . 'INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id` '
            . 'INNER JOIN `course` c ON c.`course_id` = se.`course_id` '
            . 'LEFT JOIN `department` d ON d.`department_id` = c.`department_id` '
            . 'WHERE s.`student_id` = ? AND se.`student_enroll_status` = \'Following\' '
            . 'AND c.`department_id` = ? AND se.`course_id` = ? AND se.`academic_year` = ? '
            . 'LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 'ssss', [$studentId, $departmentId, $courseId, $academicYear]);

        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, mixed>> $students
     */
    public function createComplaint(array $data, array $students, int $userId): ?int {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $ref = $this->generateReferenceNo();
            $stmt = $this->db->prepare(
                'INSERT INTO `complaint_letters` (`reference_no`, `letter_date`, `subject`, `recipient_name`, `recipient_address`, '
                . '`complaint_body`, `action_required`, `department_id`, `course_id`, `academic_year`, `status`, `created_by`, `updated_by`) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            if (!$stmt) {
                throw new RuntimeException('Prepare failed');
            }
            $letterDate = (string) ($data['letter_date'] ?? date('Y-m-d'));
            $subject = (string) ($data['subject'] ?? '');
            $recipientName = (string) ($data['recipient_name'] ?? '');
            $recipientAddress = (string) ($data['recipient_address'] ?? '');
            $body = (string) ($data['complaint_body'] ?? '');
            $actionRequired = (string) ($data['action_required'] ?? '');
            $departmentId = (string) ($data['department_id'] ?? '');
            $courseId = (string) ($data['course_id'] ?? '');
            $academicYear = (string) ($data['academic_year'] ?? '');
            $status = (string) ($data['status'] ?? self::STATUS_DRAFT);
            $stmt->bind_param(
                'sssssssssssii',
                $ref,
                $letterDate,
                $subject,
                $recipientName,
                $recipientAddress,
                $body,
                $actionRequired,
                $departmentId,
                $courseId,
                $academicYear,
                $status,
                $userId,
                $userId
            );
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('Insert failed');
            }
            $id = (int) $stmt->insert_id;
            $stmt->close();
            $this->syncStudents($id, $students);
            $conn->commit();

            return $id;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('ComplaintLetterModel::createComplaint: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, mixed>> $students
     */
    public function updateComplaint(int $id, array $data, array $students, int $userId): bool {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE `complaint_letters` SET `letter_date` = ?, `subject` = ?, `recipient_name` = ?, `recipient_address` = ?, '
                . '`complaint_body` = ?, `action_required` = ?, `department_id` = ?, `course_id` = ?, `academic_year` = ?, '
                . '`status` = ?, `updated_by` = ? WHERE `id` = ? AND `deleted_at` IS NULL'
            );
            if (!$stmt) {
                throw new RuntimeException('Prepare failed');
            }
            $letterDate = (string) ($data['letter_date'] ?? date('Y-m-d'));
            $subject = (string) ($data['subject'] ?? '');
            $recipientName = (string) ($data['recipient_name'] ?? '');
            $recipientAddress = (string) ($data['recipient_address'] ?? '');
            $body = (string) ($data['complaint_body'] ?? '');
            $actionRequired = (string) ($data['action_required'] ?? '');
            $departmentId = (string) ($data['department_id'] ?? '');
            $courseId = (string) ($data['course_id'] ?? '');
            $academicYear = (string) ($data['academic_year'] ?? '');
            $status = (string) ($data['status'] ?? self::STATUS_DRAFT);
            $stmt->bind_param(
                'ssssssssssii',
                $letterDate,
                $subject,
                $recipientName,
                $recipientAddress,
                $body,
                $actionRequired,
                $departmentId,
                $courseId,
                $academicYear,
                $status,
                $userId,
                $id
            );
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException('Update failed');
            }
            $stmt->close();
            $this->syncStudents($id, $students);
            $conn->commit();

            return true;
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('ComplaintLetterModel::updateComplaint: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @param list<array<string, mixed>> $students
     */
    private function syncStudents(int $complaintId, array $students): void {
        $stmt = $this->db->prepare('DELETE FROM `complaint_letter_students` WHERE `complaint_letter_id` = ?');
        if ($stmt) {
            $stmt->bind_param('i', $complaintId);
            $stmt->execute();
            $stmt->close();
        }
        if ($students === []) {
            return;
        }
        $ins = $this->db->prepare(
            'INSERT INTO `complaint_letter_students` (`complaint_letter_id`, `student_id`, `student_name`, `student_reg_no`, `course_name`, `department_id`) '
            . 'VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$ins) {
            return;
        }
        foreach ($students as $row) {
            $studentId = trim((string) ($row['student_id'] ?? ''));
            if ($studentId === '') {
                continue;
            }
            $studentName = trim((string) ($row['student_name'] ?? ''));
            $regNo = trim((string) ($row['student_reg_no'] ?? $studentId));
            $courseName = trim((string) ($row['course_name'] ?? ''));
            $deptId = trim((string) ($row['department_id'] ?? ''));
            $ins->bind_param('isssss', $complaintId, $studentId, $studentName, $regNo, $courseName, $deptId);
            $ins->execute();
        }
        $ins->close();
    }

    public function softDeleteComplaint(int $id, int $userId): bool {
        $stmt = $this->db->prepare(
            'UPDATE `complaint_letters` SET `deleted_at` = NOW(), `deleted_by` = ?, `updated_by` = ? WHERE `id` = ? AND `deleted_at` IS NULL'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('iii', $userId, $userId, $id);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool) $ok;
    }

    public function markGenerated(int $id, int $userId): bool {
        $stmt = $this->db->prepare(
            'UPDATE `complaint_letters` SET `generated_by` = ?, `generated_at` = NOW(), `updated_by` = ?, `status` = ? WHERE `id` = ? AND `deleted_at` IS NULL'
        );
        if (!$stmt) {
            return false;
        }
        $status = self::STATUS_FINAL;
        $stmt->bind_param('iisi', $userId, $userId, $status, $id);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool) $ok;
    }

    public function markPrinted(int $id, int $userId): bool {
        $stmt = $this->db->prepare(
            'UPDATE `complaint_letters` SET `printed_by` = ?, `printed_at` = NOW(), `updated_by` = ? WHERE `id` = ? AND `deleted_at` IS NULL'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('iii', $userId, $userId, $id);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool) $ok;
    }

    /**
     * @param list<string>|null $studentIds
     * @param array<string, mixed>|null $details
     */
    public function logAudit(
        ?int $complaintId,
        int $userId,
        string $userRole,
        ?string $departmentId,
        string $action,
        ?array $studentIds = null,
        ?array $details = null
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO `complaint_letter_audit_logs` (`complaint_letter_id`, `user_id`, `user_role`, `department_id`, `action`, `student_ids`, `details`, `ip_address`, `user_agent`) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return;
        }
        $studentJson = $studentIds !== null ? json_encode(array_values($studentIds), JSON_UNESCAPED_UNICODE) : null;
        $detailsJson = $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        $stmt->bind_param('iisssssss', $complaintId, $userId, $userRole, $departmentId, $action, $studentJson, $detailsJson, $ip, $ua);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAuditLogs(?int $complaintId = null, int $limit = 100): array {
        $limit = max(1, min(500, $limit));
        if ($complaintId !== null && $complaintId > 0) {
            $sql = 'SELECT l.*, u.`user_name` FROM `complaint_letter_audit_logs` l '
                . 'LEFT JOIN `user` u ON u.`user_id` = l.`user_id` '
                . 'WHERE l.`complaint_letter_id` = ? ORDER BY l.`id` DESC LIMIT ?';

            return $this->fetchAllPrepared($sql, 'ii', [$complaintId, $limit]);
        }
        $sql = 'SELECT l.*, u.`user_name`, cl.`reference_no` FROM `complaint_letter_audit_logs` l '
            . 'LEFT JOIN `user` u ON u.`user_id` = l.`user_id` '
            . 'LEFT JOIN `complaint_letters` cl ON cl.`id` = l.`complaint_letter_id` '
            . 'ORDER BY l.`id` DESC LIMIT ?';

        return $this->fetchAllPrepared($sql, 'i', [$limit]);
    }

    /**
     * Students available for complaint letter selection (scoped by dept/course/year).
     *
     * @return list<array<string, mixed>>
     */
    public function listStudentsForSelection(string $departmentId, string $courseId, string $academicYear, string $search = ''): array {
        if ($departmentId === '' || $courseId === '' || $academicYear === '') {
            return [];
        }
        $where = [
            'se.`student_enroll_status` = \'Following\'',
            'c.`department_id` = ?',
            'se.`course_id` = ?',
            'se.`academic_year` = ?',
        ];
        $types = 'sss';
        $params = [$departmentId, $courseId, $academicYear];
        $search = trim($search);
        if ($search !== '') {
            $where[] = '(s.`student_id` LIKE ? OR s.`student_fullname` LIKE ? OR s.`student_nic` LIKE ?)';
            $q = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }
        $sql = 'SELECT s.`student_id`, s.`student_fullname`, s.`student_nic`, se.`academic_year`, '
            . 'se.`course_id`, c.`course_name`, c.`department_id`, d.`department_name` '
            . 'FROM `student` s '
            . 'INNER JOIN `student_enroll` se ON se.`student_id` = s.`student_id` '
            . 'INNER JOIN `course` c ON c.`course_id` = se.`course_id` '
            . 'LEFT JOIN `department` d ON d.`department_id` = c.`department_id` '
            . 'WHERE ' . implode(' AND ', $where) . ' ORDER BY s.`student_fullname` ASC, s.`student_id` ASC LIMIT 500';

        return $this->fetchAllPrepared($sql, $types, $params);
    }

    /**
     * @param list<array<string, mixed>> $students
     * @return list<array<string, mixed>>
     */
    public function enrichStudentsWithMailing(array $students): array {
        foreach ($students as &$row) {
            $studentId = trim((string) ($row['student_id'] ?? ''));
            if ($studentId === '') {
                continue;
            }
            $mail = $this->getStudentMailingDetails($studentId);
            $row['mail_address'] = $mail['address'] ?? '';
            $row['mail_city_line'] = $mail['city_line'] ?? '';
            if (empty($row['student_name']) && !empty($mail['student_name'])) {
                $row['student_name'] = $mail['student_name'];
            }
        }
        unset($row);

        return $students;
    }

    /**
     * @return array{student_name: string, address: string, city_line: string}
     */
    public function getStudentMailingDetails(string $studentId): array {
        $studentId = trim($studentId);
        if ($studentId === '') {
            return ['student_name' => '', 'address' => '', 'city_line' => ''];
        }
        $sql = 'SELECT `student_id`, `student_fullname`, `student_address`, `student_district`, `student_provice`, `student_zip` '
            . 'FROM `student` WHERE `student_id` = ? LIMIT 1';
        $rows = $this->fetchAllPrepared($sql, 's', [$studentId]);
        if ($rows === []) {
            return ['student_name' => '', 'address' => '', 'city_line' => ''];
        }
        $row = $rows[0];
        $parts = array_filter([
            trim((string) ($row['student_district'] ?? '')),
            trim((string) ($row['student_provice'] ?? '')),
            trim((string) ($row['student_zip'] ?? '')),
        ], static fn ($v) => $v !== '');

        return [
            'student_name' => trim((string) ($row['student_fullname'] ?? '')),
            'address' => trim((string) ($row['student_address'] ?? '')),
            'city_line' => implode(', ', $parts),
        ];
    }
}
