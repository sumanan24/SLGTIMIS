<?php
/**
 * Group Model
 */

class GroupModel extends Model {
    protected $table = 'groups';
    
    protected function getPrimaryKey() {
        return 'id';
    }

    public function __construct() {
        parent::__construct();
        $this->ensureCourseVersionColumn();
        require_once BASE_PATH . '/helpers/SchedulingSchema.php';
        SchedulingSchema::ensure();
    }

    /**
     * Append one row to the permanent group membership history.
     */
    public function logHistory($studentId, $groupId, $action, $fromGroupId = null, $toGroupId = null, $performedBy = '', $remarks = '') {
        $sql = "INSERT INTO `group_student_history`
                (`student_id`, `group_id`, `action`, `from_group_id`, `to_group_id`, `performed_by`, `remarks`)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $studentId = (string) $studentId;
        $groupId = (int) $groupId;
        $action = (string) $action;
        $fromGroupId = $fromGroupId === null ? null : (int) $fromGroupId;
        $toGroupId = $toGroupId === null ? null : (int) $toGroupId;
        $performedBy = (string) $performedBy;
        $remarks = mb_substr((string) $remarks, 0, 255);
        $stmt->bind_param('sisiiss', $studentId, $groupId, $action, $fromGroupId, $toGroupId, $performedBy, $remarks);
        return $stmt->execute();
    }

    /**
     * Membership history of a group (joined / left / transferred), newest first.
     */
    public function getHistoryForGroup($groupId, $limit = 200) {
        $sql = "SELECT h.*, s.student_fullname, gf.name AS from_group_name, gt.name AS to_group_name
                FROM `group_student_history` h
                LEFT JOIN `student` s ON s.student_id = h.student_id
                LEFT JOIN `groups` gf ON gf.id = h.from_group_id
                LEFT JOIN `groups` gt ON gt.id = h.to_group_id
                WHERE h.group_id = ?
                ORDER BY h.created_at DESC, h.id DESC
                LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $groupId = (int) $groupId;
        $stmt->bind_param('i', $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Active group of a student (a student belongs to one active group at a time).
     */
    public function getActiveGroupOfStudent($studentId) {
        $sql = "SELECT g.* FROM `group_students` gs
                INNER JOIN `groups` g ON g.id = gs.group_id
                WHERE gs.student_id = ? AND gs.status = 'active'
                ORDER BY gs.enrolled_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $studentId = (string) $studentId;
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Groups a student can be moved to: same department, active, not the current group.
     */
    public function getTransferTargets($groupId) {
        $group = $this->getByIdWithDetails((int) $groupId);
        if (!$group || empty($group['department_id'])) {
            return [];
        }
        $sql = "SELECT g.id, g.name, g.group_code, g.academic_year, c.course_name
                FROM `{$this->table}` g
                INNER JOIN `course` c ON c.course_id = g.course_id
                WHERE c.department_id = ? AND g.status = 'active' AND g.id <> ?
                ORDER BY g.academic_year DESC, g.name ASC";
        $stmt = $this->db->prepare($sql);
        $dept = (string) $group['department_id'];
        $gid = (int) $groupId;
        $stmt->bind_param('si', $dept, $gid);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Move one active student from one group to another and keep the history of both sides.
     * Returns null on success or an error message.
     */
    public function transferStudent($studentId, $fromGroupId, $toGroupId, $performedBy = '', $remarks = '') {
        $studentId = (string) $studentId;
        $fromGroupId = (int) $fromGroupId;
        $toGroupId = (int) $toGroupId;
        if ($fromGroupId === $toGroupId) {
            return 'Choose a different group.';
        }
        $from = $this->getByIdWithDetails($fromGroupId);
        $to = $this->getByIdWithDetails($toGroupId);
        if (!$from || !$to) {
            return 'Group not found.';
        }
        if ((string) $from['department_id'] !== (string) $to['department_id']) {
            return 'Students can only be transferred between groups of the same department.';
        }
        if (($to['status'] ?? '') !== 'active') {
            return 'The target group is not active.';
        }
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("DELETE FROM `group_students` WHERE `group_id` = ? AND `student_id` = ? AND `status` = 'active'");
            $stmt->bind_param('is', $fromGroupId, $studentId);
            $stmt->execute();
            if ($stmt->affected_rows < 1) {
                throw new RuntimeException('The student is not an active member of the source group.');
            }
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO `group_students` (`group_id`, `student_id`, `enrolled_at`, `status`)
                VALUES (?, ?, NOW(), 'active')
                ON DUPLICATE KEY UPDATE `status` = 'active', `enrolled_at` = NOW()");
            $stmt->bind_param('is', $toGroupId, $studentId);
            if (!$stmt->execute()) {
                throw new RuntimeException('Could not add the student to the target group.');
            }
            $stmt->close();
            $this->logHistory($studentId, $fromGroupId, 'transfer_out', $fromGroupId, $toGroupId, $performedBy, $remarks);
            $this->logHistory($studentId, $toGroupId, 'transfer_in', $fromGroupId, $toGroupId, $performedBy, $remarks);
            $conn->commit();
            return null;
        } catch (Throwable $e) {
            $conn->rollback();
            return $e instanceof RuntimeException ? $e->getMessage() : 'Transfer failed.';
        }
    }

    public function groupCodeExists($code, $excludeId = 0) {
        $sql = "SELECT 1 FROM `{$this->table}` WHERE `group_code` = ? AND `id` <> ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $code = (string) $code;
        $excludeId = (int) $excludeId;
        $stmt->bind_param('si', $code, $excludeId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Suggested unique group code: COURSE-YEAR-NN.
     */
    public function suggestGroupCode($courseId, $academicYear) {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', (string) $courseId)) . '-' . preg_replace('/[^0-9]/', '', (string) $academicYear);
        $base = trim($base, '-');
        for ($i = 1; $i < 100; $i++) {
            $code = substr($base . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 0, 30);
            if (!$this->groupCodeExists($code)) {
                return $code;
            }
        }
        return substr($base . '-' . time(), 0, 30);
    }

    /**
     * Ensure groups table has course_version (INT, default 0).
     */
    public function ensureCourseVersionColumn() {
        $conn = $this->db->getConnection();
        $res = $conn->query("SHOW COLUMNS FROM `{$this->table}` LIKE 'course_version'");
        if ($res && $res->num_rows > 0) {
            return;
        }
        $conn->query("ALTER TABLE `{$this->table}` ADD COLUMN `course_version` INT(11) NOT NULL DEFAULT 0 AFTER `course_id`");
    }

    public static function versionLabel($version) {
        $v = (int) $version;
        return $v === 0 ? 'Default (0)' : 'Version ' . $v;
    }
    
    /**
     * Get all groups with course and department info
     */
    public function getAllWithDetails($departmentId = null, $courseId = null) {
        $sql = "SELECT g.*, c.course_name, c.department_id, d.department_name,
                COUNT(gs.id) as student_count,
                (SELECT COUNT(*) FROM `group_students` gsi
                    INNER JOIN `student_enroll` sei ON sei.student_id = gsi.student_id
                        AND sei.course_id = g.course_id AND sei.academic_year = g.academic_year
                        AND sei.student_enroll_status = 'Internship'
                    WHERE gsi.group_id = g.id AND gsi.status = 'active') AS internship_count
                FROM `{$this->table}` g
                LEFT JOIN `course` c ON g.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                LEFT JOIN `group_students` gs ON g.id = gs.group_id AND gs.status = 'active'
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if ($departmentId) {
            $sql .= " AND c.department_id = ?";
            $params[] = $departmentId;
            $types .= 's';
        }
        if ($courseId) {
            $sql .= " AND g.course_id = ?";
            $params[] = $courseId;
            $types .= 's';
        }
        
        $sql .= " GROUP BY g.id ORDER BY g.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Get group by ID with details
     */
    public function getByIdWithDetails($id) {
        $sql = "SELECT g.*, c.course_name, c.department_id, d.department_name
                FROM `{$this->table}` g
                LEFT JOIN `course` c ON g.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE g.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create a new group
     */
    public function createGroup($data) {
        return $this->create($data);
    }
    
    /**
     * Update group
     */
    public function updateGroup($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete group (and related group_students)
     */
    public function deleteGroup($id) {
        $conn = $this->db->getConnection();
        $conn->autocommit(false);
        
        try {
            // Delete group students first
            $deleteStudentsSql = "DELETE FROM `group_students` WHERE `group_id` = ?";
            $stmt = $conn->prepare($deleteStudentsSql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $timetable = $conn->query("SHOW TABLES LIKE 'group_timetable'");
            if ($timetable && $timetable->num_rows > 0) {
                $deleteTimetableSql = "DELETE FROM `group_timetable` WHERE `group_id` = ?";
                $stmt = $conn->prepare($deleteTimetableSql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Delete group
            $deleteGroupSql = "DELETE FROM `{$this->table}` WHERE `id` = ?";
            $stmt = $conn->prepare($deleteGroupSql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            $conn->autocommit(true);
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(true);
            error_log("Error deleting group: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get students in a group (only active students)
     */
    public function getGroupStudents($groupId) {
        $sql = "SELECT gs.*, s.student_id, s.student_ininame, s.student_fullname, s.student_email,
                       se.student_enroll_status
                FROM `group_students` gs
                INNER JOIN `student` s ON gs.student_id = s.student_id
                INNER JOIN `groups` g ON g.id = gs.group_id
                LEFT JOIN `student_enroll` se ON se.student_id = gs.student_id
                    AND se.course_id = g.course_id AND se.academic_year = g.academic_year
                WHERE gs.group_id = ? AND gs.status = 'active'
                ORDER BY s.student_id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $groupId);
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
     * Add students to group
     */
    public function addStudentToGroupQuiet(int $groupId, string $studentId, string $performedBy = ''): bool {
        $sql = "INSERT IGNORE INTO `group_students` (`group_id`, `student_id`, `enrolled_at`, `status`)
                VALUES (?, ?, NOW(), 'active')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $groupId, $studentId);
        $ok = $stmt->execute();
        if ($ok && $stmt->affected_rows > 0) {
            $this->logHistory($studentId, $groupId, 'joined', null, null, $performedBy);
        }
        return $ok;
    }

    public function addStudentsToGroup($groupId, $studentIds, $performedBy = '') {
        $conn = $this->db->getConnection();
        $conn->autocommit(false);
        
        try {
            $sql = "INSERT IGNORE INTO `group_students` (`group_id`, `student_id`, `enrolled_at`, `status`) 
                    VALUES (?, ?, NOW(), 'active')";
            $stmt = $conn->prepare($sql);
            
            foreach ($studentIds as $studentId) {
                $stmt->bind_param("is", $groupId, $studentId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add student: " . $stmt->error);
                }
                if ($stmt->affected_rows > 0) {
                    $this->logHistory($studentId, $groupId, 'joined', null, null, $performedBy);
                }
            }
            
            $stmt->close();
            $conn->commit();
            $conn->autocommit(true);
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(true);
            error_log("Error adding students to group: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove student from group
     */
    public function removeStudentFromGroup($groupId, $studentId, $performedBy = '', $remarks = '') {
        $sql = "DELETE FROM `group_students` WHERE `group_id` = ? AND `student_id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $groupId, $studentId);
        $ok = $stmt->execute();
        if ($ok && $stmt->affected_rows > 0) {
            $this->logHistory($studentId, (int) $groupId, 'left', (int) $groupId, null, $performedBy, $remarks);
        }
        return $ok;
    }
    
    /**
     * Update student status in group
     */
    public function setGroupEnrollmentStatus($groupId, $status) {
        $status = $status === 'Internship' ? 'Internship' : 'Following';
        $groupId = (int) $groupId;
        $group = $this->getByIdWithDetails($groupId);
        if (!$group || empty($group['course_id']) || empty($group['academic_year'])) {
            return 0;
        }
        require_once BASE_PATH . '/models/StudentEnrollmentModel.php';
        (new StudentEnrollmentModel())->ensureEnrollStatusValues();
        $sql = "UPDATE `student_enroll` se
                INNER JOIN `group_students` gs ON gs.student_id = se.student_id
                    AND gs.group_id = ? AND gs.status = 'active'
                SET se.student_enroll_status = ?
                WHERE se.course_id = ? AND se.academic_year = ? AND se.student_enroll_status <> ?";
        $stmt = $this->db->prepare($sql);
        $courseId = (string) $group['course_id'];
        $year = (string) $group['academic_year'];
        $stmt->bind_param('issss', $groupId, $status, $courseId, $year, $status);
        if (!$stmt->execute()) {
            return 0;
        }
        return (int) $stmt->affected_rows;
    }

    public function updateStudentStatus($groupId, $studentId, $status) {
        $sql = "UPDATE `group_students` SET `status` = ? WHERE `group_id` = ? AND `student_id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sis", $status, $groupId, $studentId);
        
        return $stmt->execute();
    }
    
    /**
     * Students who can be added: Following enrollment for course/year, not already in any active group.
     * $groupId is accepted for API compatibility; exclusion is global (one active group membership per student).
     */
    public function getAvailableStudents($courseId, $academicYear, $groupId = null, $courseVersion = null) {
        $sql = "SELECT s.student_id, s.student_fullname, s.student_email
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                WHERE se.course_id = ?
                  AND se.academic_year = ?
                  AND se.student_enroll_status = 'Following'
                  AND NOT EXISTS (
                      SELECT 1 FROM `group_students` gs
                      WHERE gs.student_id = s.student_id
                        AND gs.status = 'active'
                  )";

        $hasEnrollVersion = false;
        $verRes = $this->db->query("SHOW COLUMNS FROM `student_enroll` LIKE 'course_version'");
        if ($verRes && $verRes->num_rows > 0) {
            $hasEnrollVersion = true;
        }

        $useVersion = $hasEnrollVersion && $courseVersion !== null;
        if ($useVersion) {
            $courseVersion = (int) $courseVersion;
            $sql .= " AND (se.course_version = ? OR (? = 0 AND (se.course_version IS NULL OR se.course_version = 0)))";
        }

        $sql .= " GROUP BY s.student_id, s.student_fullname, s.student_email
                  ORDER BY s.student_fullname ASC";

        $stmt = $this->db->prepare($sql);
        if ($useVersion) {
            $stmt->bind_param('ssii', $courseId, $academicYear, $courseVersion, $courseVersion);
        } else {
            $stmt->bind_param('ss', $courseId, $academicYear);
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
     * Check if user can access group (based on department)
     */
    public function canAccessGroup($groupId, $departmentId) {
        $sql = "SELECT c.department_id 
                FROM `{$this->table}` g
                LEFT JOIN `course` c ON g.course_id = c.course_id
                WHERE g.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        // If no department restriction or department matches, allow access
        if (!$departmentId || !$row || $row['department_id'] == $departmentId) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Active groups for a course (all academic years) — e.g. exam batch picker.
     *
     * @return list<array<string, mixed>>
     */
    public function getActiveGroupsByCourse($courseId) {
        $sql = "SELECT g.*, c.course_name, d.department_name
                FROM `{$this->table}` g
                LEFT JOIN `course` c ON g.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE g.course_id = ? AND g.status = 'active'
                ORDER BY g.academic_year DESC, g.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $courseId);
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
     * Get groups by course and academic year
     */
    public function getGroupsByCourseAndYear($courseId, $academicYear) {
        $sql = "SELECT g.*, c.course_name, d.department_name
                FROM `{$this->table}` g
                LEFT JOIN `course` c ON g.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE g.course_id = ? AND g.academic_year = ? AND g.status = 'active'
                ORDER BY g.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $courseId, $academicYear);
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
}

