<?php
/**
 * Payment Model
 */

class PaymentModel extends Model {
    protected $table = 'pays';
    
    protected function getPrimaryKey() {
        return 'pays_id';
    }
    
    /**
     * Get all payments with student and related information
     */
    public function getAll($filters = []) {
        $sql = "SELECT p.*, s.student_fullname, s.student_id as student_reg_no, 
                c.course_name, d.department_name, se.academic_year
                FROM `{$this->table}` p
                LEFT JOIN `student` s ON p.student_id = s.student_id
                LEFT JOIN `student_enroll` se ON s.student_id = se.student_id AND se.student_enroll_status = 'Following'
                LEFT JOIN `course` c ON se.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Apply filters
        if (!empty($filters['student_id'])) {
            $sql .= " AND p.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 's';
        }
        
        if (!empty($filters['payment_reason'])) {
            $sql .= " AND p.payment_reason LIKE ?";
            $params[] = '%' . $filters['payment_reason'] . '%';
            $types .= 's';
        }
        
        if (!empty($filters['payment_type'])) {
            $sql .= " AND p.payment_type = ?";
            $params[] = $filters['payment_type'];
            $types .= 's';
        }
        
        if (!empty($filters['approved'])) {
            $sql .= " AND p.approved = ?";
            $params[] = $filters['approved'];
            $types .= 'i';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(p.pays_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(p.pays_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (s.student_fullname LIKE ? OR s.student_id LIKE ? OR p.payment_reason LIKE ? OR p.pays_note LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ssss';
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND d.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        $sql .= " ORDER BY p.pays_date DESC, p.pays_id DESC";
        
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
     * Get payment by ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, s.student_fullname, s.student_id as student_reg_no,
                c.course_name, d.department_name, se.academic_year
                FROM `{$this->table}` p
                LEFT JOIN `student` s ON p.student_id = s.student_id
                LEFT JOIN `student_enroll` se ON s.student_id = se.student_id AND se.student_enroll_status = 'Following'
                LEFT JOIN `course` c ON se.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE p.pays_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get total count of payments
     */
    public function getTotal($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` p
                LEFT JOIN `student` s ON p.student_id = s.student_id
                LEFT JOIN `student_enroll` se ON s.student_id = se.student_id AND se.student_enroll_status = 'Following'
                LEFT JOIN `course` c ON se.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['student_id'])) {
            $sql .= " AND p.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= 's';
        }
        
        if (!empty($filters['payment_type'])) {
            $sql .= " AND p.payment_type = ?";
            $params[] = $filters['payment_type'];
            $types .= 's';
        }
        
        if (!empty($filters['approved'])) {
            $sql .= " AND p.approved = ?";
            $params[] = $filters['approved'];
            $types .= 'i';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(p.pays_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(p.pays_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (s.student_fullname LIKE ? OR s.student_id LIKE ? OR p.payment_reason LIKE ? OR p.pays_note LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ssss';
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND d.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }
    
    /**
     * Create new payment
     */
    public function createPayment($data) {
        return $this->create($data);
    }
    
    /**
     * Update payment
     */
    public function updatePayment($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Delete payment
     */
    public function deletePayment($id) {
        return $this->delete($id);
    }
    
    /**
     * Check if payment exists
     */
    public function exists($id) {
        $payment = $this->getById($id);
        return !empty($payment);
    }
    
    /**
     * Get payments by student ID
     */
    public function getByStudentId($studentId) {
        $sql = "SELECT * FROM `{$this->table}` 
                WHERE student_id = ? 
                ORDER BY pays_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $studentId);
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
     * Get payment statistics
     */
    public function getStatistics($filters = []) {
        $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(pays_amount) as total_amount,
                SUM(CASE WHEN approved = 1 THEN pays_amount ELSE 0 END) as approved_amount,
                SUM(CASE WHEN approved = 0 THEN pays_amount ELSE 0 END) as pending_amount
                FROM `{$this->table}` p
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(pays_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(pays_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get all payment reasons from payment lookup table
     */
    public function getPaymentReasons() {
        $sql = "SELECT DISTINCT payment_reason, payment_type FROM `payment` ORDER BY payment_type, payment_reason";
        $result = $this->db->query($sql);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
}
