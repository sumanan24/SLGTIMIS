<?php
/**
 * Attendance Model
 */

class AttendanceModel extends Model {
    protected $table = 'attendance';
    
    protected function getPrimaryKey() {
        return 'attendance_id';
    }
    
    /**
     * Get students for attendance by filters
     */
    public function getStudentsForAttendance($filters = []) {
        $sql = "SELECT DISTINCT s.student_id, s.student_fullname, s.student_ininame, se.course_id, c.course_name, d.department_name
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                INNER JOIN `department` d ON c.department_id = d.department_id";
        
        // If group filter is provided, join with group_students table
        if (!empty($filters['group_id'])) {
            $sql .= " INNER JOIN `group_students` gs ON s.student_id = gs.student_id AND gs.status = 'active'";
        }
        
        $sql .= " WHERE se.student_enroll_status = 'Following' AND s.student_status = 'Active'";
        
        $params = [];
        $types = '';
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND d.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND c.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        if (!empty($filters['group_id'])) {
            $sql .= " AND gs.group_id = ?";
            $params[] = $filters['group_id'];
            $types .= 'i';
        }
        
        $sql .= " ORDER BY s.student_id ASC";
        
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
     * Get attendance for a student and date range
     */
    public function getAttendanceByStudentAndDateRange($studentId, $startDate, $endDate) {
        $sql = "SELECT `date`, `attendance_status` 
                FROM `{$this->table}` 
                WHERE `student_id` = ? AND `date` >= ? AND `date` <= ?
                ORDER BY `date` ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sss", $studentId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['date']] = $row['attendance_status'];
            }
        }
        
        return $data;
    }
    
    /**
     * Bulk update attendance using chunk method
     */
    public function bulkUpdateAttendance($attendanceData, $chunkSize = 100) {
        // Get the actual mysqli connection
        $conn = $this->db->getConnection();
        
        // Process in chunks to avoid memory issues
        $chunks = array_chunk($attendanceData, $chunkSize);
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($chunks as $chunk) {
            $conn->begin_transaction();
            
            try {
                foreach ($chunk as $record) {
                    $studentId = $record['student_id'];
                    $date = $record['date'];
                    $status = $record['attendance_status'];
                    $moduleName = $record['module_name'] ?? 'General';
                    $staffName = $record['staff_name'] ?? $_SESSION['user_name'] ?? 'System';
                    
                    // Check if record exists and get current status
                    $checkSql = "SELECT `attendance_id`, `attendance_status` FROM `{$this->table}` 
                                 WHERE `student_id` = ? AND `date` = ? AND `module_name` = ?";
                    $checkStmt = $conn->prepare($checkSql);
                    if (!$checkStmt) {
                        throw new Exception('Failed to prepare check statement: ' . $conn->error);
                    }
                    $checkStmt->bind_param("sss", $studentId, $date, $moduleName);
                    if (!$checkStmt->execute()) {
                        throw new Exception('Failed to execute check: ' . $checkStmt->error);
                    }
                    $existingRecord = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();
                    
                    // Skip updating if existing record is a holiday (-1) and new status is also -1
                    // This prevents unnecessary updates to holidays that are already set
                    if ($existingRecord && $existingRecord['attendance_status'] == -1 && $status == -1) {
                        // Holiday already exists, skip update to prevent timestamp changes
                        $successCount++;
                        continue;
                    }
                    
                    if ($existingRecord) {
                        // Update existing record (but not if it's already a holiday being set as holiday)
                        $updateSql = "UPDATE `{$this->table}` 
                                      SET `attendance_status` = ?, `staff_name` = ?
                                      WHERE `student_id` = ? AND `date` = ? AND `module_name` = ?";
                        $updateStmt = $conn->prepare($updateSql);
                        if (!$updateStmt) {
                            throw new Exception('Failed to prepare update statement: ' . $conn->error);
                        }
                        $updateStmt->bind_param("issss", $status, $staffName, $studentId, $date, $moduleName);
                        if (!$updateStmt->execute()) {
                            throw new Exception('Failed to execute update: ' . $updateStmt->error);
                        }
                        $updateStmt->close();
                    } else {
                        // Insert new record
                        $insertSql = "INSERT INTO `{$this->table}` 
                                      (`student_id`, `module_name`, `staff_name`, `attendance_status`, `date`) 
                                      VALUES (?, ?, ?, ?, ?)";
                        $insertStmt = $conn->prepare($insertSql);
                        if (!$insertStmt) {
                            throw new Exception('Failed to prepare insert statement: ' . $conn->error);
                        }
                        $insertStmt->bind_param("sssis", $studentId, $moduleName, $staffName, $status, $date);
                        if (!$insertStmt->execute()) {
                            throw new Exception('Failed to execute insert: ' . $insertStmt->error);
                        }
                        $insertStmt->close();
                    }
                    
                    $successCount++;
                }
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $errorCount += count($chunk);
                error_log('Attendance update error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                // Re-throw if it's a critical error (like database connection)
                if (strpos($e->getMessage(), 'Connection') !== false || 
                    strpos($e->getMessage(), 'database') !== false ||
                    strpos($e->getMessage(), 'SQL') !== false) {
                    throw $e;
                }
            }
        }
        
        return [
            'success' => $successCount,
            'errors' => $errorCount,
            'total' => count($attendanceData)
        ];
    }
    
    /**
     * Delete attendance records (for clearing/resetting)
     */
    public function deleteAttendanceByDateRange($studentIds, $startDate, $endDate) {
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $sql = "DELETE FROM `{$this->table}` 
                WHERE `student_id` IN ($placeholders) AND `date` >= ? AND `date` <= ?";
        
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($studentIds)) . 'ss';
        $params = array_merge($studentIds, [$startDate, $endDate]);
        
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));
        
        return $stmt->execute();
    }
    
    /**
     * Get attendance report with day-by-day data for active students
     * Excludes weekends and holidays (-1 values)
     */
    public function getAttendanceReport($month, $filters = []) {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return $this->getAttendanceReportByDateRange($startDate, $endDate, $filters);
    }
    
    /**
     * Get attendance report with percentages for active students
     * Excludes weekends and holidays (-1 values)
     */
    public function getAttendanceReportByDateRange($startDate, $endDate, $filters = []) {
        $conn = $this->db->getConnection();
        
        // Build query for active students with bank details (allowance_eligible_date may not exist in all DBs)
        $sql = "SELECT DISTINCT 
                    s.student_id,
                    s.student_fullname,
                    s.student_nic,
                    s.bank_name,
                    s.bank_account_no,
                    s.bank_branch,
                    s.allowance_eligible,
                    se.course_id,
                    c.course_name,
                    d.department_name,
                    se.academic_year
                FROM `student` s
                INNER JOIN `student_enroll` se ON s.student_id = se.student_id
                INNER JOIN `course` c ON se.course_id = c.course_id
                INNER JOIN `department` d ON c.department_id = d.department_id
                WHERE se.student_enroll_status = 'Following' AND s.student_status = 'Active'";
        
        $params = [];
        $types = '';
        
        // Apply filters
        if (!empty($filters['department_id'])) {
            $sql .= " AND d.department_id = ?";
            $params[] = $filters['department_id'];
            $types .= 's';
        }
        
        if (!empty($filters['course_id'])) {
            $sql .= " AND c.course_id = ?";
            $params[] = $filters['course_id'];
            $types .= 's';
        }
        
        if (!empty($filters['academic_year'])) {
            $sql .= " AND se.academic_year = ?";
            $params[] = $filters['academic_year'];
            $types .= 's';
        }
        
        // Filter by allowance eligible if requested
        if (!empty($filters['eligible_only']) && $filters['eligible_only']) {
            $sql .= " AND s.allowance_eligible = 1";
        }
        
        // Attendance report: load only full-time students (course_mode = 'Full')
        $sql .= " AND se.course_mode = 'Full'";
        
        $sql .= " ORDER BY s.student_fullname ASC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("AttendanceModel::getAttendanceReportByDateRange - Prepare failed: " . $conn->error);
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_log("AttendanceModel::getAttendanceReportByDateRange - Execute failed: " . $stmt->error);
            $stmt->close();
            return [];
        }
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $studentId = $row['student_id'];
            
            // Get attendance records for this student
            $attendanceSql = "SELECT `date`, `attendance_status` 
                            FROM `{$this->table}` 
                            WHERE `student_id` = ? AND `date` >= ? AND `date` <= ?
                            ORDER BY `date` ASC";
            $attendanceStmt = $conn->prepare($attendanceSql);
            if (!$attendanceStmt) {
                error_log("AttendanceModel::getAttendanceReportByDateRange - Attendance prepare failed: " . $conn->error);
                continue;
            }
            $attendanceStmt->bind_param("sss", $studentId, $startDate, $endDate);
            $attendanceStmt->execute();
            $attendanceResult = $attendanceStmt->get_result();
            
            $attendanceRecords = [];
            while ($attRow = $attendanceResult->fetch_assoc()) {
                $attendanceRecords[$attRow['date']] = $attRow['attendance_status'];
            }
            $attendanceStmt->close();
            
            // Get working days only (excluding weekends)
            $workingDaysList = $this->getWorkingDaysInRange($startDate, $endDate);
            $workingDays = count($workingDaysList);
            
            // Create day-by-day attendance array (only for working days)
            $dayByDayAttendance = [];
            $presentDays = 0;
            $holidayDays = 0;
            
            foreach ($workingDaysList as $day) {
                $date = $day['date'];
                $status = $attendanceRecords[$date] ?? null;
                
                if ($status == -1) {
                    $holidayDays++;
                    $dayByDayAttendance[$date] = 'H'; // Holiday
                } elseif ($status == 1) {
                    $presentDays++;
                    $dayByDayAttendance[$date] = 'P'; // Present
                } elseif ($status === 0) {
                    $dayByDayAttendance[$date] = 'A'; // Absent
                } else {
                    $dayByDayAttendance[$date] = ''; // Not marked
                }
            }
            
            // Calculate effective working days (excluding holidays)
            $effectiveWorkingDays = $workingDays - $holidayDays;
            
            // Calculate attendance percentage
            $attendancePercentage = 0;
            if ($effectiveWorkingDays > 0) {
                $attendancePercentage = ($presentDays / $effectiveWorkingDays) * 100;
            }
            
            // Calculate allowance based on percentage and eligibility date
            // 90% - 100% = 5000, 75% - 89% = 4000
            // Only provide allowance if student is eligible and eligible date is before or on the report month
            $allowance = 0;
            $isEligibleForMonth = false;
            
            // Check if student is eligible
            if (!empty($row['allowance_eligible']) && $row['allowance_eligible'] == 1) {
                // Check if eligible date is set and is before or on the report month
                if (!empty($row['allowance_eligible_date'])) {
                    $eligibleDate = $row['allowance_eligible_date'];
                    $reportMonthStart = $startDate; // First day of report month
                    
                    // If eligible date is before or on the report month start, student is eligible
                    if ($eligibleDate <= $reportMonthStart) {
                        $isEligibleForMonth = true;
                    }
                } else {
                    // If no eligible date set, assume eligible (backward compatibility)
                    $isEligibleForMonth = true;
                }
            }
            
            // Calculate allowance only if eligible for this month
            if ($isEligibleForMonth) {
                if ($attendancePercentage >= 90) {
                    $allowance = 5000;
                } elseif ($attendancePercentage >= 75) {
                    $allowance = 4000;
                }
            }
            
            $row['present_days'] = $presentDays;
            $row['holiday_days'] = $holidayDays;
            $row['working_days'] = $workingDays;
            $row['effective_working_days'] = $effectiveWorkingDays;
            $row['attendance_percentage'] = round($attendancePercentage, 2);
            $row['allowance'] = $allowance;
            $row['day_by_day'] = $dayByDayAttendance;
            $row['all_days'] = $workingDaysList;
            
            $students[] = $row;
        }
        
        $stmt->close();
        
        return $students;
    }
    
    /**
     * Count working days (excluding weekends) between two dates
     */
    private function countWorkingDays($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Include end date
        
        $workingDays = 0;
        $current = clone $start;
        
        while ($current < $end) {
            $dayOfWeek = (int)$current->format('w'); // 0 = Sunday, 6 = Saturday
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $workingDays++;
            }
            $current->modify('+1 day');
        }
        
        return $workingDays;
    }
    
    /**
     * Get all working days (excluding weekends) in a date range
     */
    private function getWorkingDaysInRange($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Include end date
        
        $days = [];
        $current = clone $start;
        
        while ($current < $end) {
            $dayOfWeek = (int)$current->format('w'); // 0 = Sunday, 6 = Saturday
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $days[] = [
                    'date' => $current->format('Y-m-d'),
                    'day' => $current->format('d'),
                    'day_name' => $current->format('D')
                ];
            }
            $current->modify('+1 day');
        }
        
        return $days;
    }
    
    /**
     * Get all days in a date range (including weekends)
     */
    private function getAllDaysInRange($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day'); // Include end date
        
        $days = [];
        $current = clone $start;
        
        while ($current < $end) {
            $dayOfWeek = (int)$current->format('w'); // 0 = Sunday, 6 = Saturday
            $days[] = [
                'date' => $current->format('Y-m-d'),
                'day' => $current->format('d'),
                'day_name' => $current->format('D'),
                'is_weekend' => ($dayOfWeek == 0 || $dayOfWeek == 6)
            ];
            $current->modify('+1 day');
        }
        
        return $days;
    }
}

