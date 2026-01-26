<?php
/**
 * Group Model
 */

class GroupModel extends Model {
    protected $table = 'groups';
    
    protected function getPrimaryKey() {
        return 'id';
    }
    
    /**
     * Get all groups with course and department info
     */
    public function getAllWithDetails($departmentId = null) {
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
        $sql = "SELECT gs.*, s.student_id, s.student_fullname, s.student_email
                FROM `group_students` gs
                INNER JOIN `student` s ON gs.student_id = s.student_id
                WHERE gs.group_id = ? AND gs.status = 'active'
                ORDER BY s.student_fullname ASC";
        
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
            $sql = "INSERT INTO `group_students` (`group_id`, `student_id`, `enrolled_at`, `status`) 
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
     * Get available students for a course (not already in the group)
     */
    public function getAvailableStudents($courseId, $academicYear, $groupId = null) {
        $sql = "SELECT DISTINCT s.student_id, s.student_fullname, s.student_email
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                WHERE se.course_id = ? AND se.academic_year = ? AND se.student_enroll_status = 'Following'";
        
        if ($groupId) {
            $sql .= " AND s.student_id NOT IN (
                        SELECT student_id FROM `group_students` WHERE group_id = ? AND status = 'active'
                    )";
        }
        
        $sql .= " ORDER BY s.student_fullname ASC";
        
        $stmt = $this->db->prepare($sql);
        if ($groupId) {
            $stmt->bind_param("ssi", $courseId, $academicYear, $groupId);
        } else {
            $stmt->bind_param("ss", $courseId, $academicYear);
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

