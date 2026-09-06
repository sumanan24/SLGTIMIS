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
                COUNT(gs.id) as student_count
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
        $sql = "SELECT gs.*, s.student_id, s.student_ininame, s.student_fullname, s.student_email
                FROM `group_students` gs
                INNER JOIN `student` s ON gs.student_id = s.student_id
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
    public function addStudentsToGroup($groupId, $studentIds) {
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
    public function removeStudentFromGroup($groupId, $studentId) {
        $sql = "DELETE FROM `group_students` WHERE `group_id` = ? AND `student_id` = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $groupId, $studentId);
        
        return $stmt->execute();
    }
    
    /**
     * Update student status in group
     */
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

