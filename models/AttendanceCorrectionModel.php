<?php
/**
 * Attendance corrections, approvals and the permanent audit trail.
 *
 * Modes
 *   direct       an authorised head corrects a record of the open (current) month; applied immediately.
 *   request      a request for the open month that needs approval by a head.
 *   exceptional  a request for a closed month; needs approval by a higher authority (never the requester).
 * Original biometric punches are never touched; only the effective (final_*) result changes.
 */
class AttendanceCorrectionModel extends Model {
    protected $table = 'attendance_corrections';

    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/helpers/SchedulingSchema.php';
        require_once BASE_PATH . '/helpers/AttendanceRules.php';
        require_once BASE_PATH . '/models/DailyAttendanceModel.php';
        SchedulingSchema::ensure();
    }

    /**
     * @param array{user_id:int, user_name:string} $actor
     * @return array{ok:bool, message:string, correction_id?:int}
     */
    public function submit($studentId, $date, $newStatus, $reason, $mode, array $actor) {
        $studentId = trim((string) $studentId);
        $reason = trim((string) $reason);
        $valid = [AttendanceRules::STATUS_FULL, AttendanceRules::STATUS_HALF, AttendanceRules::STATUS_ABSENT];
        if ($studentId === '' || !in_array($newStatus, $valid, true)) {
            return ['ok' => false, 'message' => 'Choose the student and the new attendance status.'];
        }
        if (mb_strlen($reason) < 5) {
            return ['ok' => false, 'message' => 'A reason of at least 5 characters is required.'];
        }
        $state = AttendanceRules::editState($date);
        if ($mode === 'exceptional') {
            if ($state['editable'] || (string) $date > AttendanceRules::today()) {
                return ['ok' => false, 'message' => 'This date is still editable. Use the normal correction.'];
            }
        } elseif (!$state['editable']) {
            return ['ok' => false, 'message' => $state['reason'] . ' Submit an exceptional correction request for approval.'];
        }

        $daily = new DailyAttendanceModel();
        $day = $daily->getStudentDay($studentId, $date);
        if (!$day) {
            return ['ok' => false, 'message' => 'No attendance record exists for that student and date.'];
        }
        $prev = $day['final_status'];
        $prevVal = (float) $day['final_value'];
        if ($prev === $newStatus) {
            return ['ok' => false, 'message' => 'The record already has that status.'];
        }
        $newVal = AttendanceRules::valueForStatus($newStatus);
        $exceptional = $mode === 'exceptional' ? 1 : 0;
        $approved = $mode === 'direct';
        $approvalStatus = $approved ? 'approved' : 'pending';

        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO `{$this->table}`
                (`student_id`, `attendance_date`, `prev_status`, `new_status`, `prev_value`, `new_value`, `reason`, `exceptional`,
                 `requested_by`, `requested_by_user`, `approval_status`, `approved_by`, `approved_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, " . ($approved ? 'NOW()' : 'NULL') . ")");
            $pv = number_format($prevVal, 1, '.', '');
            $nv = number_format($newVal, 1, '.', '');
            $by = (string) $actor['user_name'];
            $uid = (int) $actor['user_id'];
            $approvedBy = $approved ? $by : null;
            $stmt->bind_param('sssssssisiss', $studentId, $date, $prev, $newStatus, $pv, $nv, $reason, $exceptional, $by, $uid, $approvalStatus, $approvedBy);
            if (!$stmt->execute()) {
                throw new RuntimeException('Could not save the correction.');
            }
            $cid = (int) $conn->insert_id;
            $stmt->close();

            if ($approved) {
                $this->applyToRecords($studentId, $date, $newStatus, $newVal);
                $this->audit($studentId, $date, 'correction', $cid, $prev, $newStatus, $prevVal, $newVal, $reason, $actor, 'Direct correction by authorised head');
            } else {
                $this->audit($studentId, $date, $exceptional ? 'exceptional_request' : 'request', $cid, $prev, $newStatus, $prevVal, $newVal, $reason, $actor, 'Awaiting approval');
            }
            $conn->commit();
            return [
                'ok' => true,
                'correction_id' => $cid,
                'message' => $approved ? 'Attendance corrected.' : ($exceptional ? 'Exceptional correction submitted for higher approval.' : 'Correction request submitted for approval.'),
            ];
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('AttendanceCorrectionModel::submit ' . $e->getMessage());
            return ['ok' => false, 'message' => 'The correction could not be saved.'];
        }
    }

    /**
     * Approve or reject a pending request.
     *
     * @param array{user_id:int, user_name:string} $actor
     * @return array{ok:bool, message:string}
     */
    public function decide($correctionId, $approve, $remarks, array $actor, $mayApproveExceptional) {
        $row = $this->find((int) $correctionId);
        if (!$row || $row['approval_status'] !== 'pending') {
            return ['ok' => false, 'message' => 'That request is no longer pending.'];
        }
        $exceptional = (int) $row['exceptional'] === 1;
        if ((int) $row['requested_by_user'] === (int) $actor['user_id']) {
            return ['ok' => false, 'message' => 'You cannot approve or reject your own request.'];
        }
        if ($exceptional && !$mayApproveExceptional) {
            return ['ok' => false, 'message' => 'Only the higher authority can decide exceptional corrections.'];
        }
        $remarks = trim((string) $remarks);
        if (!$approve && $remarks === '') {
            return ['ok' => false, 'message' => 'Enter a remark when rejecting a request.'];
        }
        if ($approve && !$exceptional) {
            $state = AttendanceRules::editState($row['attendance_date']);
            if (!$state['editable']) {
                return ['ok' => false, 'message' => $state['reason'] . ' Reject this request and submit an exceptional correction.'];
            }
        }
        $newVal = (float) $row['new_value'];
        $prevVal = (float) $row['prev_value'];
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $status = $approve ? 'approved' : 'rejected';
            $stmt = $conn->prepare("UPDATE `{$this->table}` SET `approval_status` = ?, `approved_by` = ?, `approved_at` = NOW(), `approval_remarks` = ?
                WHERE `id` = ? AND `approval_status` = 'pending'");
            $by = (string) $actor['user_name'];
            $rid = (int) $correctionId;
            $stmt->bind_param('sssi', $status, $by, $remarks, $rid);
            $stmt->execute();
            if ($stmt->affected_rows < 1) {
                throw new RuntimeException('That request is no longer pending.');
            }
            $stmt->close();
            if ($approve) {
                $this->applyToRecords($row['student_id'], $row['attendance_date'], $row['new_status'], $newVal);
            }
            $this->audit(
                $row['student_id'], $row['attendance_date'],
                $approve ? ($exceptional ? 'exceptional' : 'approval') : 'rejection',
                $rid, $row['prev_status'], $approve ? $row['new_status'] : $row['prev_status'], $prevVal, $approve ? $newVal : $prevVal,
                $row['reason'], $actor,
                ($approve ? 'Approved' : 'Rejected') . ' by ' . $by . ($remarks !== '' ? ': ' . $remarks : '') . ' (requested by ' . $row['requested_by'] . ')'
            );
            $conn->commit();
            return ['ok' => true, 'message' => $approve ? 'Request approved and attendance updated.' : 'Request rejected.'];
        } catch (Throwable $e) {
            $conn->rollback();
            return ['ok' => false, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'The decision could not be saved.'];
        }
    }

    /**
     * Confirm a record flagged "pending verification" without changing its status.
     *
     * @param array{user_id:int, user_name:string} $actor
     */
    public function verify($studentId, $date, $remarks, array $actor) {
        $state = AttendanceRules::editState($date);
        if (!$state['editable']) {
            return ['ok' => false, 'message' => $state['reason']];
        }
        $stmt = $this->db->prepare("UPDATE `student_daily_attendance` SET `verification_status` = 'verified'
            WHERE `student_id` = ? AND `attendance_date` = ? AND `verification_status` = 'pending'");
        $stmt->bind_param('ss', $studentId, $date);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            return ['ok' => false, 'message' => 'That record is not pending verification.'];
        }
        $day = (new DailyAttendanceModel())->storedForDate([(string) $studentId], $date)[(string) $studentId] ?? null;
        $st = $day['final_status'] ?? null;
        $val = isset($day['final_value']) ? (float) $day['final_value'] : null;
        $this->audit($studentId, $date, 'verification', null, $st, $st, $val, $val, trim((string) $remarks) !== '' ? trim((string) $remarks) : 'Verified', $actor, 'Record verified');
        return ['ok' => true, 'message' => 'Record verified.'];
    }

    private function applyToRecords($studentId, $date, $status, $value) {
        $v = number_format((float) $value, 1, '.', '');
        $stmt = $this->db->prepare("UPDATE `student_daily_attendance`
            SET `final_status` = ?, `final_value` = ?, `source` = 'manual', `manually_updated` = 1, `verification_status` = 'verified'
            WHERE `student_id` = ? AND `attendance_date` = ?");
        $stmt->bind_param('ssss', $status, $v, $studentId, $date);
        $stmt->execute();
        $stmt->close();
        $stmt = $this->db->prepare("UPDATE `class_attendance` SET `status` = ?, `value` = ?, `source` = 'manual', `verification_status` = 'verified'
            WHERE `student_id` = ? AND `session_date` = ?");
        $stmt->bind_param('ssss', $status, $v, $studentId, $date);
        $stmt->execute();
        $stmt->close();
    }

    private function audit($studentId, $date, $action, $correctionId, $prev, $new, $prevVal, $newVal, $reason, array $actor, $info) {
        $stmt = $this->db->prepare("INSERT INTO `attendance_audit_logs`
            (`student_id`, `attendance_date`, `action`, `correction_id`, `prev_status`, `new_status`, `prev_value`, `new_value`,
             `reason`, `performed_by`, `performed_by_user`, `approval_info`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return;
        }
        $pv = $prevVal === null ? null : number_format((float) $prevVal, 1, '.', '');
        $nv = $newVal === null ? null : number_format((float) $newVal, 1, '.', '');
        $by = (string) $actor['user_name'];
        $uid = (int) $actor['user_id'];
        $reason = mb_substr((string) $reason, 0, 500);
        $info = mb_substr((string) $info, 0, 255);
        $stmt->bind_param('sssissssssis', $studentId, $date, $action, $correctionId, $prev, $new, $pv, $nv, $reason, $by, $uid, $info);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param array{status?:string, department_id?:string, date_from?:string, date_to?:string, exceptional?:int} $f
     * @return list<array<string,mixed>>
     */
    public function listCorrections(array $f = [], $limit = 300) {
        $sql = "SELECT c.*, COALESCE(NULLIF(TRIM(s.student_fullname), ''), s.student_ininame, c.student_id) AS student_name
                FROM `{$this->table}` c
                LEFT JOIN `student` s ON s.student_id = c.student_id
                WHERE 1=1";
        $types = '';
        $params = [];
        if (!empty($f['status'])) {
            $sql .= ' AND c.approval_status = ?';
            $types .= 's';
            $params[] = $f['status'];
        }
        if (isset($f['exceptional']) && $f['exceptional'] !== '') {
            $sql .= ' AND c.exceptional = ?';
            $types .= 'i';
            $params[] = (int) $f['exceptional'];
        }
        if (!empty($f['date_from'])) {
            $sql .= ' AND c.attendance_date >= ?';
            $types .= 's';
            $params[] = $f['date_from'];
        }
        if (!empty($f['date_to'])) {
            $sql .= ' AND c.attendance_date <= ?';
            $types .= 's';
            $params[] = $f['date_to'];
        }
        if (!empty($f['department_id'])) {
            $sql .= ' AND c.student_id IN (SELECT se.student_id FROM student_enroll se INNER JOIN course co ON co.course_id = se.course_id WHERE co.department_id = ?)';
            $types .= 's';
            $params[] = $f['department_id'];
        }
        $sql .= ' ORDER BY c.requested_at DESC, c.id DESC LIMIT ' . (int) $limit;
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
     * @return list<array<string,mixed>>
     */
    public function auditTrail($studentId, $date) {
        $stmt = $this->db->prepare("SELECT * FROM `attendance_audit_logs` WHERE `student_id` = ? AND `attendance_date` = ? ORDER BY `id` DESC");
        $stmt->bind_param('ss', $studentId, $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $rows[] = $r;
        }
        return $rows;
    }

    /**
     * Audit log listing for the corrections page.
     *
     * @return list<array<string,mixed>>
     */
    public function listAudit($departmentId = '', $dateFrom = '', $dateTo = '', $limit = 200) {
        $sql = "SELECT a.* FROM `attendance_audit_logs` a WHERE 1=1";
        $types = '';
        $params = [];
        if ($dateFrom !== '') {
            $sql .= ' AND a.attendance_date >= ?';
            $types .= 's';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $sql .= ' AND a.attendance_date <= ?';
            $types .= 's';
            $params[] = $dateTo;
        }
        if ($departmentId !== '') {
            $sql .= ' AND a.student_id IN (SELECT se.student_id FROM student_enroll se INNER JOIN course co ON co.course_id = se.course_id WHERE co.department_id = ?)';
            $types .= 's';
            $params[] = $departmentId;
        }
        $sql .= ' ORDER BY a.id DESC LIMIT ' . (int) $limit;
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

    public function studentInDepartment($studentId, $departmentId) {
        $stmt = $this->db->prepare("SELECT 1 FROM `student_enroll` se INNER JOIN `course` c ON c.course_id = se.course_id
            WHERE se.student_id = ? AND c.department_id = ? LIMIT 1");
        $stmt->bind_param('ss', $studentId, $departmentId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
