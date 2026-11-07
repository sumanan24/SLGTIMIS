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
                c.course_name, d.department_name, pd.department_name AS pays_department_name, se.academic_year
                FROM `{$this->table}` p
                LEFT JOIN `student` s ON p.student_id = s.student_id
                LEFT JOIN `student_enroll` se ON s.student_id = se.student_id AND se.student_enroll_status = 'Following'
                LEFT JOIN `course` c ON se.course_id = c.course_id
                LEFT JOIN `department` d ON c.department_id = d.department_id
                LEFT JOIN `department` pd ON p.pays_department = pd.department_id
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

        if (!empty($filters['payment_subtype'])) {
            $sql .= " AND p.payment_reason = ?";
            $params[] = $filters['payment_subtype'];
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
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM `student_enroll` sey
                WHERE sey.student_id = p.student_id AND sey.academic_year = ?
            )";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }

        if (!empty($filters['pays_department'])) {
            $sql .= " AND p.pays_department = ?";
            $params[] = $filters['pays_department'];
            $types .= 's';
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

        if (!empty($filters['payment_subtype'])) {
            $sql .= " AND p.payment_reason = ?";
            $params[] = $filters['payment_subtype'];
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
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM `student_enroll` sey
                WHERE sey.student_id = p.student_id AND sey.academic_year = ?
            )";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }

        if (!empty($filters['pays_department'])) {
            $sql .= " AND p.pays_department = ?";
            $params[] = $filters['pays_department'];
            $types .= 's';
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
                ORDER BY pays_date DESC, pays_id DESC";
        
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
     * Get paginated payments for a student (for dashboards, etc.)
     */
    public function getPaymentsByStudent($studentId, $page = 1, $perPage = 5) {
        $all = $this->getByStudentId($studentId);
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;
        return array_slice($all, $offset, $perPage);
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
     * Figures for the finance dashboard. A payment line is worth pays_amount x quantity.
     *
     * @return array{summary: array<string, float|int>, months: list<array{label: string, total: float}>,
     *               by_type: list<array{name: string, total: float, count: int}>, recent: list<array<string, mixed>>}
     */
    public function getFinanceDashboard() {
        $line = '(p.pays_amount * GREATEST(p.pays_qty, 1))';
        $summary = [
            'today_total' => 0.0, 'today_count' => 0,
            'month_total' => 0.0, 'month_count' => 0,
            'year_total' => 0.0, 'year_count' => 0,
            'pending_total' => 0.0, 'pending_count' => 0,
            'approved_month_total' => 0.0,
        ];

        $result = $this->db->query("SELECT
                COALESCE(SUM(CASE WHEN p.pays_date = CURDATE() THEN {$line} END), 0) AS today_total,
                SUM(p.pays_date = CURDATE()) AS today_count,
                COALESCE(SUM(CASE WHEN YEAR(p.pays_date) = YEAR(CURDATE()) AND MONTH(p.pays_date) = MONTH(CURDATE()) THEN {$line} END), 0) AS month_total,
                SUM(YEAR(p.pays_date) = YEAR(CURDATE()) AND MONTH(p.pays_date) = MONTH(CURDATE())) AS month_count,
                COALESCE(SUM(CASE WHEN YEAR(p.pays_date) = YEAR(CURDATE()) AND p.approved = 1 AND MONTH(p.pays_date) = MONTH(CURDATE()) THEN {$line} END), 0) AS approved_month_total,
                COALESCE(SUM(CASE WHEN YEAR(p.pays_date) = YEAR(CURDATE()) THEN {$line} END), 0) AS year_total,
                SUM(YEAR(p.pays_date) = YEAR(CURDATE())) AS year_count,
                COALESCE(SUM(CASE WHEN p.approved = 0 THEN {$line} END), 0) AS pending_total,
                SUM(p.approved = 0) AS pending_count
            FROM `{$this->table}` p");
        if ($result && ($row = $result->fetch_assoc())) {
            foreach ($summary as $key => $_) {
                $summary[$key] = (strpos($key, 'count') !== false) ? (int) $row[$key] : (float) $row[$key];
            }
        }

        // Last 6 months, oldest first, with empty months filled in.
        $totals = [];
        $result = $this->db->query("SELECT DATE_FORMAT(p.pays_date, '%Y-%m') AS ym, SUM({$line}) AS total
            FROM `{$this->table}` p
            WHERE p.pays_date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
            GROUP BY ym");
        while ($result && ($row = $result->fetch_assoc())) {
            $totals[$row['ym']] = (float) $row['total'];
        }
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} month");
            $months[] = ['label' => date('M Y', $ts), 'total' => $totals[date('Y-m', $ts)] ?? 0.0];
        }

        $byType = [];
        $result = $this->db->query("SELECT p.payment_type AS name, SUM({$line}) AS total, COUNT(*) AS cnt
            FROM `{$this->table}` p
            WHERE YEAR(p.pays_date) = YEAR(CURDATE()) AND MONTH(p.pays_date) = MONTH(CURDATE())
            GROUP BY p.payment_type
            ORDER BY total DESC
            LIMIT 8");
        while ($result && ($row = $result->fetch_assoc())) {
            $byType[] = ['name' => (string) $row['name'], 'total' => (float) $row['total'], 'count' => (int) $row['cnt']];
        }

        $recent = [];
        $result = $this->db->query("SELECT p.pays_id, p.student_id, s.student_fullname, p.payment_type, p.payment_reason,
                p.pays_amount, p.pays_qty, p.pays_date, p.approved
            FROM `{$this->table}` p
            LEFT JOIN `student` s ON p.student_id = s.student_id
            ORDER BY p.pays_date DESC, p.pays_id DESC
            LIMIT 10");
        while ($result && ($row = $result->fetch_assoc())) {
            $recent[] = $row;
        }

        return ['summary' => $summary, 'months' => $months, 'by_type' => $byType, 'recent' => $recent];
    }

    /**
     * Distinct payment types already used on recorded payments.
     */
    public function getUsedPaymentTypes() {
        $sql = "SELECT DISTINCT payment_type FROM `{$this->table}`
                WHERE payment_type IS NOT NULL AND payment_type <> ''
                ORDER BY payment_type ASC";
        $result = $this->db->query($sql);

        $types = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $types[] = $row['payment_type'];
            }
        }

        return $types;
    }

    /**
     * Payment method codes already stored on payments.
     */
    public function getUsedPaymentMethods() {
        $sql = "SELECT DISTINCT payment_method FROM `{$this->table}`
                WHERE payment_method IS NOT NULL AND payment_method <> ''
                ORDER BY payment_method ASC";
        $result = $this->db->query($sql);

        $methods = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $methods[] = $row['payment_method'];
            }
        }

        return $methods;
    }

    /**
     * Type and sub type (payment reason) pairs already used on recorded payments.
     */
    public function getUsedPaymentSubtypes() {
        $sql = "SELECT DISTINCT payment_type, payment_reason FROM `{$this->table}`
                WHERE payment_reason IS NOT NULL AND payment_reason <> ''
                ORDER BY payment_type ASC, payment_reason ASC";
        $result = $this->db->query($sql);

        $rows = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
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
