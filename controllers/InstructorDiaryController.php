<?php
/**
 * Instructor Diary Controller
 * IN1, IN2, IN3, HOD, LE1, LE2, SLE share the same functions for creating diary entries.
 * HOD can view staff-wise and course/group-wise reports.
 */
class InstructorDiaryController extends Controller {
    
    /**
     * Check if current user can access instructor diary (teaching staff or HOD).
     */
    private function checkInstructorAccess() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $role = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        
        // Teaching roles + HOD + ADM
        $allowedRoles = ['HOD', 'IN1', 'IN2', 'IN3', 'LE1', 'LE2', 'SLE', 'ADM'];
        if (!in_array($role, $allowedRoles) && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Instructor diary is only available for teaching staff, HOD, and ADM.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Instructor diary list + quick form for current staff member.
     */
    public function index() {
        if (!$this->checkInstructorAccess()) {
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $user = $userModel->find($_SESSION['user_id']);
        $staffId = $user['user_name'] ?? null; // staff_id is stored in user_name for staff users
        if (!$staffId) {
            $_SESSION['error'] = 'Unable to determine your staff ID.';
            $this->redirect('dashboard');
            return;
        }
        
        $diaryModel = $this->model('InstructorDiaryModel');
        $enrollModel = $this->model('StaffModuleEnrollmentModel');
        $enrollModel->ensureTableStructure();
        
        // Get this staff member's module enrollments for dropdown
        $enrollments = [];
        try {
            $sql = "SELECT sme.*, c.course_name, m.module_name
                    FROM `staff_module_enrollment` sme
                    LEFT JOIN `course` c ON sme.course_id = c.course_id
                    LEFT JOIN `module` m ON sme.module_id = m.module_id
                    WHERE sme.staff_id = ?
                    ORDER BY sme.academic_year DESC, c.course_name, m.module_name";
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $staffId);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $enrollments[] = $row;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            // If anything fails, leave enrollments empty
        }
        
        // Filters for list
        $fromDate = $this->get('from_date', '');
        $toDate = $this->get('to_date', '');
        $moduleId = $this->get('module_id', '');
        
        $entries = $diaryModel->getByStaff($staffId, [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'module_id' => $moduleId,
        ]);
        
        // Optional: load entry being edited
        $editId = (int)$this->get('edit_id', 0);
        $editingEntry = null;
        if ($editId > 0) {
            foreach ($entries as $row) {
                if ((int)$row['instructor_diary_id'] === $editId && $row['staff_id'] === $staffId) {
                    $editingEntry = $row;
                    break;
                }
            }
        }
        
        $data = [
            'title' => 'Instructor Diary',
            'page' => 'instructor-diary',
            'staffId' => $staffId,
            'enrollments' => $enrollments,
            'entries' => $entries,
            'editingEntry' => $editingEntry,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'module_id' => $moduleId,
            ],
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('instructor-diary/index', $data);
    }
    
    /**
     * Handle diary entry creation (POST only, form is on index page).
     */
    public function create() {
        if (!$this->checkInstructorAccess()) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('instructor-diary');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $user = $userModel->find($_SESSION['user_id']);
        $staffId = $user['user_name'] ?? null;
        if (!$staffId) {
            $_SESSION['error'] = 'Unable to determine your staff ID.';
            $this->redirect('instructor-diary');
            return;
        }
        
        $action = $this->post('action', 'create');
        $entryId = (int)$this->post('diary_id', 0);
        $enrollmentId = (int)$this->post('staff_module_enrollment_id', 0);
        $diaryDate = trim($this->post('diary_date', ''));
        $startTime = trim($this->post('start_time', ''));
        $endTime = trim($this->post('end_time', ''));
        $topic = trim($this->post('topic_covered', ''));
        
        $diaryModel = $this->model('InstructorDiaryModel');
        
        // Delete
        if ($action === 'delete') {
            if ($entryId <= 0) {
                $_SESSION['error'] = 'Diary entry ID is required.';
                $this->redirect('instructor-diary');
                return;
            }
            $existing = $diaryModel->find($entryId);
            if (!$existing || ($existing['staff_id'] ?? '') !== $staffId) {
                $_SESSION['error'] = 'You cannot delete this diary entry.';
                $this->redirect('instructor-diary');
                return;
            }
            if ($diaryModel->delete($entryId)) {
                $_SESSION['message'] = 'Diary entry deleted.';
            } else {
                $_SESSION['error'] = 'Failed to delete diary entry.';
            }
            $this->redirect('instructor-diary');
            return;
        }
        
        if (!$enrollmentId || $diaryDate === '' || $startTime === '' || $endTime === '' || $topic === '') {
            $_SESSION['error'] = 'All fields are required.';
            $this->redirect('instructor-diary');
            return;
        }
        
        // Validate times
        if (strtotime($endTime) <= strtotime($startTime)) {
            $_SESSION['error'] = 'End time must be after start time.';
            $this->redirect('instructor-diary');
            return;
        }
        
        // Load enrollment to get module_id and verify ownership
        $enrollModel = $this->model('StaffModuleEnrollmentModel');
        $enrollment = $enrollModel->find($enrollmentId);
        if (!$enrollment || ($enrollment['staff_id'] ?? '') !== $staffId) {
            $_SESSION['error'] = 'Invalid module selection.';
            $this->redirect('instructor-diary');
            return;
        }
        
        // Update
        if ($action === 'update') {
            if ($entryId <= 0) {
                $_SESSION['error'] = 'Diary entry ID is required for update.';
                $this->redirect('instructor-diary');
                return;
            }
            $existing = $diaryModel->find($entryId);
            if (!$existing || ($existing['staff_id'] ?? '') !== $staffId) {
                $_SESSION['error'] = 'You cannot edit this diary entry.';
                $this->redirect('instructor-diary');
                return;
            }
            
            $ok = $diaryModel->update($entryId, [
                'staff_module_enrollment_id' => $enrollmentId,
                'staff_id' => $staffId,
                'module_id' => $enrollment['module_id'],
                'diary_date' => $diaryDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'topic_covered' => $topic,
            ]);
            
            if ($ok) {
                $_SESSION['message'] = 'Diary entry updated successfully.';
            } else {
                $_SESSION['error'] = 'Failed to update diary entry.';
            }
            
            $this->redirect('instructor-diary');
            return;
        }
        
        // Create (default)
        // Avoid duplicate records: check only instructor_diary table for same staff/module/date/time
        if ($diaryModel->existsEntry($staffId, $enrollment['module_id'], $diaryDate, $startTime, $endTime)) {
            $_SESSION['error'] = 'A diary entry already exists for this module and time range.';
            $this->redirect('instructor-diary');
            return;
        }
        
        $ok = $diaryModel->createEntry([
            'staff_module_enrollment_id' => $enrollmentId,
            'staff_id' => $staffId,
            'module_id' => $enrollment['module_id'],
            'diary_date' => $diaryDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'topic_covered' => $topic,
        ]);
        
        if ($ok) {
            $_SESSION['message'] = 'Diary entry recorded successfully.';
        } else {
            $_SESSION['error'] = 'Failed to save diary entry.';
        }
        
        $this->redirect('instructor-diary');
    }
    
    /**
     * HOD report view: staff-wise or course/group-wise diary.
     */
    public function hodReport() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Allow HOD, DIR, DPA, DPI, REG, ADM and Admin
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $role = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $allowedMgmtRoles = ['HOD', 'DIR', 'DPA', 'DPI', 'REG', 'ADM'];
        if (!in_array($role, $allowedMgmtRoles) && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Only HOD, DIR, DPA, DPI, or REG can view instructor diary reports.';
            $this->redirect('dashboard');
            return;
        }
        
        // HOD is limited to their department; DIR/DPA/DPI/REG/ADM/Admin can see all
        $departmentId = null;
        if ($role === 'HOD') {
            $departmentId = $this->getHODDepartment();
            if (!$departmentId) {
                $_SESSION['error'] = 'Department not found for your HOD account.';
                $this->redirect('dashboard');
                return;
            }
        }
        
        $diaryModel = $this->model('InstructorDiaryModel');
        $staffModel = $this->model('StaffModel');
        $groupModel = $this->model('GroupModel');
        $courseModel = $this->model('CourseModel');
        $studentModel = $this->model('StudentModel');
        
        // Dropdown data
        $staffList = $staffModel->getStaffWithDepartment(1, 1000, '', $departmentId ?? '');
        $courseFilters = [];
        if (!empty($departmentId)) {
            $courseFilters['department_id'] = $departmentId;
        }
        $courses = $courseModel->getCoursesWithDepartment($courseFilters);
        $groups = $groupModel->getAllWithDetails($departmentId);
        $academicYears = $studentModel->getAcademicYears();
        
        // Filters
        $staffId = $this->get('staff_id', '');
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $fromDate = $this->get('from_date', '');
        $toDate = $this->get('to_date', '');
        
        // Load entries for report:
        // - For HOD: default is "all entries in my department" when no filters are set.
        // - For DIR/DPA/DPI/REG/ADM/Admin: default is "all entries in institute".
        $entries = $diaryModel->getForReport($departmentId, [
            'staff_id' => $staffId,
            'course_id' => $courseId,
            'academic_year' => $academicYear,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        
        $data = [
            'title' => 'Instructor Diary - HOD Report',
            'page' => 'hod-instructor-diary',
            'staffList' => $staffList,
            'courses' => $courses,
            'groups' => $groups,
            'academicYears' => $academicYears,
            'entries' => $entries,
            'filters' => [
                'staff_id' => $staffId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ];
        
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('instructor-diary/hod_report', $data);
    }

    /**
     * Export HOD/management instructor diary report to Excel (CSV).
     * Respects department restriction for HOD and current filters.
     */
    public function hodReportExport() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $role = $userModel->getUserRole($_SESSION['user_id']);
        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
        $allowedMgmtRoles = ['HOD', 'DIR', 'DPA', 'DPI', 'REG', 'ADM'];
        if (!in_array($role, $allowedMgmtRoles) && !$isAdmin) {
            $_SESSION['error'] = 'Access denied. Only HOD, DIR, DPA, DPI, or REG can export instructor diary reports.';
            $this->redirect('dashboard');
            return;
        }
        
        // HOD is limited to their department; DIR/DPA/DPI/REG/ADM/Admin can see all
        $departmentId = null;
        if ($role === 'HOD') {
            $departmentId = $this->getHODDepartment();
            if (!$departmentId) {
                $_SESSION['error'] = 'Department not found for your HOD account.';
                $this->redirect('dashboard');
                return;
            }
        }
        
        $diaryModel = $this->model('InstructorDiaryModel');
        
        // Same filters as HOD report view
        $staffId = $this->get('staff_id', '');
        $courseId = $this->get('course_id', '');
        $academicYear = $this->get('academic_year', '');
        $fromDate = $this->get('from_date', '');
        $toDate = $this->get('to_date', '');
        
        $entries = $diaryModel->getForReport($departmentId, [
            'staff_id' => $staffId,
            'course_id' => $courseId,
            'academic_year' => $academicYear,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
        
        $filename = 'instructor_diary_report_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // BOM for UTF-8 so Excel opens correctly
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'w');
        
        // CSV header row
        fputcsv($output, [
            'Diary Date',
            'Staff ID',
            'Staff Name',
            'Course',
            'Academic Year',
            'Module',
            'Start Time',
            'End Time',
            'Topic Covered',
        ]);
        
        foreach ($entries as $row) {
            $courseLabel = $row['course_name'] ?? '';
            if ($courseLabel === '' && !empty($row['course_id'] ?? '')) {
                $courseLabel = $row['course_id'];
            } elseif ($courseLabel !== '' && !empty($row['course_id'] ?? '')) {
                $courseLabel .= ' (' . $row['course_id'] . ')';
            }
            
            fputcsv($output, [
                $row['diary_date'] ?? '',
                $row['staff_id'] ?? '',
                $row['staff_name'] ?? '',
                $courseLabel,
                $row['academic_year'] ?? '',
                $row['module_name'] ?? ($row['module_id'] ?? ''),
                isset($row['start_time']) ? substr($row['start_time'], 0, 5) : '',
                isset($row['end_time']) ? substr($row['end_time'], 0, 5) : '',
                $row['topic_covered'] ?? '',
            ]);
        }
        
        fclose($output);
        exit;
    }
}

