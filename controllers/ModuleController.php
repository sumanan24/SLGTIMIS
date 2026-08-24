<?php
/**
 * Module Controller - list, view, add course modules
 */

class ModuleController extends Controller {

    /**
     * Optional semester 1–12 (null if not set).
     */
    private function normalizeSemester($raw) {
        if ($raw === null || $raw === '') {
            return null;
        }
        $n = (int)$raw;
        return ($n >= 1 && $n <= 12) ? $n : null;
    }

    private function normalizeCredit($raw) {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return null;
            }
        }
        $n = (float) $raw;
        return $n >= 0 ? $n : null;
    }

    private function normalizeHeaderKey(string $s): string {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);
        return trim((string) $s, '_');
    }

    private function columnLetter(int $index): string {
        $index = max(0, $index);
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = (int) floor($index / 26) - 1;
        }

        return $letter;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readCsvRows(string $path): array {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Could not open CSV file.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        $lineNum = 1;
        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || ($data === [''] && feof($handle))) {
                continue;
            }
            $row = [];
            foreach ($data as $i => $val) {
                $row[$this->columnLetter((int) $i)] = $val;
            }
            $rows[$lineNum++] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readImportRows(string $tmp, string $ext): array {
        if ($ext === 'csv') {
            return $this->readCsvRows($tmp);
        }

        require_once BASE_PATH . '/vendor/autoload.php';
        if ($ext === 'xls') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tmp);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, true);
    }

    /**
     * Excel/CSV import for modules.
     *
     * Expected columns (header row): module_id, module_name, credit (optional), semester (optional)
     */
    public function importExcel() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkNotSAO()) {
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        $deptRestricted = $this->isDepartmentRestricted();
        if (!$deptRestricted && !$isADM) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('modules');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('modules/create');
            return;
        }

        $courseId = trim((string) $this->post('course_id', ''));
        $courseVersion = (int) $this->post('course_version', 0);
        if ($courseId === '') {
            $_SESSION['error'] = 'Please select a course.';
            $this->redirect('modules/create');
            return;
        }

        $deptId = $this->getUserDepartment();
        if ($deptId && !$isADM) {
            $courseModel = $this->model('CourseModel');
            $course = $courseModel->getById($courseId);
            if (!$course || !isset($course['department_id']) || $course['department_id'] !== $deptId) {
                $_SESSION['error'] = 'Access denied. You can only import modules for courses in your department.';
                $this->redirect('modules/create');
                return;
            }
        }

        if (empty($_FILES['modules_file']) || !is_array($_FILES['modules_file'])) {
            $_SESSION['error'] = 'Please choose an Excel file.';
            $this->redirect('modules/create');
            return;
        }
        $f = $_FILES['modules_file'];
        if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'File upload failed.';
            $this->redirect('modules/create');
            return;
        }

        $tmp = (string) ($f['tmp_name'] ?? '');
        $name = (string) ($f['name'] ?? '');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            $_SESSION['error'] = 'Unsupported file type. Please upload .xlsx or .csv.';
            $this->redirect('modules/create');
            return;
        }

        // Load spreadsheet rows
        $rows = [];
        try {
            $rows = $this->readImportRows($tmp, $ext);
        } catch (\Throwable $e) {
            error_log('ModuleController::importExcel: ' . $e->getMessage());
            $_SESSION['error'] = 'Could not read the file. Please ensure it is a valid Excel/CSV.';
            $this->redirect('modules/create');
            return;
        }

        if (count($rows) < 2) {
            $_SESSION['error'] = 'No data rows found in the file.';
            $this->redirect('modules/create');
            return;
        }

        // Build header map from first row
        $headerRow = array_shift($rows);
        $colMap = []; // normalized header -> column key (A, B, C...)
        foreach ($headerRow as $colKey => $val) {
            $k = $this->normalizeHeaderKey((string) $val);
            if ($k !== '') {
                $colMap[$k] = $colKey;
            }
        }
        $required = ['module_id', 'module_name'];
        foreach ($required as $req) {
            if (!isset($colMap[$req])) {
                $_SESSION['error'] = 'Missing required column: ' . $req . '. Expected headers: module_id, module_name, credit, semester.';
                $this->redirect('modules/create');
                return;
            }
        }

        $moduleModel = $this->model('ModuleModel');
        $imported = 0;
        $skipped = 0;
        $errors = [];
        foreach ($rows as $idx => $r) {
            $rowNo = $idx + 2; // because header was row 1
            $mid = trim((string) ($r[$colMap['module_id']] ?? ''));
            $mname = trim((string) ($r[$colMap['module_name']] ?? ''));
            if ($mid === '' && $mname === '') {
                $skipped++;
                continue;
            }
            if ($mid === '' || $mname === '') {
                $skipped++;
                $errors[] = "Row {$rowNo}: module_id and module_name are required.";
                continue;
            }
            $credit = null;
            if (isset($colMap['credit'])) {
                $credit = $this->normalizeCredit($r[$colMap['credit']] ?? null);
            }
            $semester = null;
            if (isset($colMap['semester'])) {
                $semester = $this->normalizeSemester($r[$colMap['semester']] ?? null);
            }

            if ($moduleModel->exists($courseId, $mid, $courseVersion)) {
                $skipped++;
                continue;
            }

            $sqlError = null;
            $ok = $moduleModel->createModule([
                'course_id' => $courseId,
                'course_version' => $courseVersion,
                'module_id' => $mid,
                'module_name' => $mname,
                'credit' => $credit,
                'semester' => $semester,
            ], $sqlError);
            if ($ok !== false) {
                $imported++;
            } else {
                $skipped++;
                $errors[] = "Row {$rowNo}: " . ($sqlError ?: 'Failed to insert module.');
            }
        }

        $_SESSION['message'] = "Import complete. Imported {$imported}, skipped {$skipped}.";
        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' (more errors omitted)' : '');
        }
        $this->redirect('modules?course_id=' . rawurlencode($courseId));
    }

    /**
     * Download a sample CSV file for module import (opens in Excel).
     */
    public function downloadSampleExcel() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkNotSAO()) {
            return;
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modules_import_sample.csv"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        if ($output === false) {
            $_SESSION['error'] = 'Could not generate sample file.';
            $this->redirect('modules/create');
            return;
        }

        fputcsv($output, ['module_id', 'module_name', 'credit', 'semester']);
        fputcsv($output, ['G50C001M09', 'Pneumatics', '2', '2']);
        fputcsv($output, ['G50C001M10', 'Automobile Engines', '2', '2']);
        fclose($output);
        exit;
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkNotSAO()) {
            return;
        }
        $moduleModel = $this->model('ModuleModel');
        $courseModel = $this->model('CourseModel');
        $departmentModel = $this->model('DepartmentModel');

        $userDeptId = $this->getUserDepartment();
        $filterDepartmentId = trim($this->get('department_id', ''));
        $filterCourseId = trim($this->get('course_id', ''));
        $filterVersionRaw = $this->get('course_version', null);

        if ($userDeptId) {
            $filterDepartmentId = $userDeptId;
        }

        if ($filterCourseId !== '') {
            $courseCheck = $courseModel->getById($filterCourseId);
            if (!$courseCheck) {
                $_SESSION['error'] = 'Selected course was not found.';
                $filterCourseId = '';
            } elseif ($filterDepartmentId !== '' && ($courseCheck['department_id'] ?? '') !== $filterDepartmentId) {
                $_SESSION['error'] = 'Selected course does not belong to the chosen department.';
                $filterCourseId = '';
            } elseif ($userDeptId && ($courseCheck['department_id'] ?? '') !== $userDeptId) {
                $_SESSION['error'] = 'Access denied. You can only view modules for courses in your department.';
                $filterCourseId = '';
            }
        }

        $queryFilters = [];
        if ($filterDepartmentId !== '') {
            $queryFilters['department_id'] = $filterDepartmentId;
        }
        if ($filterCourseId !== '') {
            $queryFilters['course_id'] = $filterCourseId;
        }
        if ($filterVersionRaw !== null && $filterVersionRaw !== '') {
            $queryFilters['course_version'] = (int) $filterVersionRaw;
        }

        $modules = $moduleModel->getAllWithCourseFiltered($queryFilters);

        if ($userDeptId) {
            $dept = $departmentModel->getById($userDeptId);
            $departments = $dept ? [$dept] : [];
            $courses = $courseModel->getCoursesWithDepartment(['department_id' => $userDeptId]);
        } else {
            $departments = $departmentModel->getAll();
            $courses = $courseModel->getCoursesWithDepartment([]);
        }

        $versionsByCourse = [];
        foreach ($courses as $c) {
            $cid = $c['course_id'] ?? '';
            if ($cid === '') {
                continue;
            }
            $versions = [0];
            $rows = $courseModel->getVersionsForCourse($cid);
            foreach ($rows as $v) {
                $versions[] = (int) ($v['version_no'] ?? 0);
            }
            $versions = array_values(array_unique($versions));
            sort($versions, SORT_NUMERIC);
            $versionsByCourse[$cid] = $versions;
        }

        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        $canCreate = $isADM || $this->isDepartmentRestricted();
        $canEdit = $canCreate;

        $filterCourseVersion = ($filterVersionRaw !== null && $filterVersionRaw !== '') ? (int) $filterVersionRaw : '';

        $data = [
            'title' => 'Modules',
            'page' => 'modules',
            'modules' => $modules,
            'courses' => $courses,
            'departments' => $departments,
            'versionsByCourse' => $versionsByCourse,
            'filter_department_id' => $filterDepartmentId,
            'filter_course_id' => $filterCourseId,
            'filter_course_version' => $filterCourseVersion,
            'isDepartmentRestricted' => (bool) $userDeptId,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'message' => $_SESSION['message'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        unset($_SESSION['message'], $_SESSION['error']);
        return $this->view('modules/index', $data);
    }

    public function show() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (!$this->checkNotSAO()) {
            return;
        }
        $courseId = trim($this->get('course_id', ''));
        $moduleId = trim($this->get('module_id', ''));
        $courseVersion = $this->get('course_version') !== null && $this->get('course_version') !== '' ? (int)$this->get('course_version') : null;
        if (empty($courseId) || empty($moduleId)) {
            $_SESSION['error'] = 'Course and module are required.';
            $this->redirect('modules');
            return;
        }
        $deptId = $this->getUserDepartment();
        if ($deptId) {
            $courseModel = $this->model('CourseModel');
            $course = $courseModel->getById($courseId);
            if (!$course || !isset($course['department_id']) || $course['department_id'] !== $deptId) {
                $_SESSION['error'] = 'Access denied. You can only view modules for courses in your department.';
                $this->redirect('modules');
                return;
            }
        }
        $moduleModel = $this->model('ModuleModel');
        $module = $moduleModel->getByCourseAndModule($courseId, $moduleId, $courseVersion);
        if (!$module) {
            $_SESSION['error'] = 'Module not found.';
            $this->redirect('modules');
            return;
        }
        $data = [
            'title' => 'View Module',
            'page' => 'modules',
            'module' => $module
        ];
        return $this->view('modules/view', $data);
    }

    public function create() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        $deptRestricted = $this->isDepartmentRestricted();
        if (!$deptRestricted && !$isADM) {
            $_SESSION['error'] = 'Only HOD / Instructors and ADM can add modules.';
            $this->redirect('modules');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $courseId = trim($this->post('course_id', ''));
            $courseVersion = (int) $this->post('course_version', 0);
            $moduleId = trim($this->post('module_id', ''));
            $moduleName = trim($this->post('module_name', ''));
            $credit = $this->post('credit');
            $credit = $credit === '' || $credit === null ? null : (float) $credit;
            $semester = $this->normalizeSemester($this->post('semester', ''));
            if (empty($courseId) || empty($moduleId) || empty($moduleName)) {
                $_SESSION['error'] = 'Course, version, module ID and module name are required.';
                $this->redirect('modules/create');
                return;
            }
            $deptId = $this->getUserDepartment();
            if ($deptId && !$isADM) {
                $courseModel = $this->model('CourseModel');
                $course = $courseModel->getById($courseId);
                if (!$course || !isset($course['department_id']) || $course['department_id'] !== $deptId) {
                    $_SESSION['error'] = 'Access denied. You can only add modules for courses in your department.';
                    $this->redirect('modules/create');
                    return;
                }
            }
            $moduleModel = $this->model('ModuleModel');
            if ($moduleModel->exists($courseId, $moduleId, $courseVersion)) {
                $_SESSION['error'] = 'This module ID already exists for the selected course and version.';
                $this->redirect('modules/create');
                return;
            }
            $sqlError = null;
            $result = $moduleModel->createModule([
                'course_id' => $courseId,
                'course_version' => $courseVersion,
                'module_id' => $moduleId,
                'module_name' => $moduleName,
                'credit' => $credit,
                'semester' => $semester,
            ], $sqlError);
            if ($result !== false) {
                $_SESSION['message'] = 'Module added successfully.';
                $this->redirect('modules');
            } else {
                $_SESSION['error'] = $sqlError ?: 'Failed to add module.';
                $this->redirect('modules/create');
            }
        } else {
            $courseModel = $this->model('CourseModel');
            $deptId = $this->getUserDepartment();
            $courses = $deptId ? $courseModel->getCoursesWithDepartment(['department_id' => $deptId]) : $courseModel->getCoursesWithDepartment([]);
            $versionsByCourse = [];
            foreach ($courses as $c) {
                $cid = $c['course_id'] ?? '';
                if ($cid === '') continue;
                $versions = [0];
                $rows = $courseModel->getVersionsForCourse($cid);
                foreach ($rows as $v) {
                    $versions[] = (int)($v['version_no'] ?? 0);
                }
                $versions = array_unique($versions);
                sort($versions, SORT_NUMERIC);
                $versionsByCourse[$cid] = $versions;
            }
            $data = [
                'title' => 'Add Module',
                'page' => 'modules',
                'courses' => $courses,
                'versionsByCourse' => $versionsByCourse,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('modules/create', $data);
        }
    }

    /**
     * Edit module (ADM and HOD). HOD restricted to own department's courses.
     */
    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        $deptRestricted = $this->isDepartmentRestricted();
        if (!$deptRestricted && !$isADM) {
            $_SESSION['error'] = 'Only HOD / Instructors and ADM can edit modules.';
            $this->redirect('modules');
            return;
        }
        $courseId = trim($this->get('course_id', ''));
        $moduleId = trim($this->get('module_id', ''));
        $courseVersion = $this->get('course_version') !== null && $this->get('course_version') !== '' ? (int)$this->get('course_version') : 0;
        if (empty($courseId) || empty($moduleId)) {
            $_SESSION['error'] = 'Course and module are required.';
            $this->redirect('modules');
            return;
        }
        $moduleModel = $this->model('ModuleModel');
        $courseModel = $this->model('CourseModel');
        $module = $moduleModel->getByCourseAndModule($courseId, $moduleId, $courseVersion);
        if (!$module) {
            $_SESSION['error'] = 'Module not found.';
            $this->redirect('modules');
            return;
        }
        $course = $courseModel->getById($courseId);
        $deptId = $this->getUserDepartment();
        if ($deptId && $course && isset($course['department_id']) && $course['department_id'] !== $deptId) {
            $_SESSION['error'] = 'Access denied. You can only edit modules for courses in your department.';
            $this->redirect('modules');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $moduleName = trim($this->post('module_name', ''));
            $credit = $this->post('credit');
            $credit = $credit === '' || $credit === null ? null : (float)$credit;
            $semester = $this->normalizeSemester($this->post('semester', ''));
            if (empty($moduleName)) {
                $_SESSION['error'] = 'Module name is required.';
                $this->redirect('modules/edit?course_id=' . urlencode($courseId) . '&module_id=' . urlencode($moduleId) . '&course_version=' . $courseVersion);
                return;
            }
            $sqlError = null;
            $ok = $moduleModel->updateModule($courseId, $moduleId, $courseVersion, [
                'module_name' => $moduleName,
                'credit' => $credit,
                'semester' => $semester,
            ], $sqlError);
            if ($ok) {
                $_SESSION['message'] = 'Module updated successfully.';
                $this->redirect('modules');
            } else {
                $_SESSION['error'] = $sqlError ?: 'Failed to update module.';
                $this->redirect('modules/edit?course_id=' . urlencode($courseId) . '&module_id=' . urlencode($moduleId) . '&course_version=' . $courseVersion);
            }
        } else {
            $data = [
                'title' => 'Edit Module',
                'page' => 'modules',
                'module' => $module,
                'error' => $_SESSION['error'] ?? null
            ];
            unset($_SESSION['error']);
            return $this->view('modules/edit', $data);
        }
    }

    /**
     * Delete module (ADM and HOD). HOD restricted to own department's courses.
     */
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $isHOD = $this->isHOD();
        $userRole = $userModel->getUserRole($_SESSION['user_id']);
        $isADM = ($userRole === 'ADM') || $userModel->isAdmin($_SESSION['user_id']);
        if (!$isHOD && !$isADM) {
            $_SESSION['error'] = 'Only HOD and ADM can delete modules.';
            $this->redirect('modules');
            return;
        }
        $courseId = trim($this->get('course_id', ''));
        $moduleId = trim($this->get('module_id', ''));
        $courseVersion = $this->get('course_version') !== null && $this->get('course_version') !== '' ? (int)$this->get('course_version') : 0;
        if (empty($courseId) || empty($moduleId)) {
            $_SESSION['error'] = 'Course and module are required.';
            $this->redirect('modules');
            return;
        }
        $courseModel = $this->model('CourseModel');
        $course = $courseModel->getById($courseId);
        $hodDepartmentId = $this->getHODDepartment();
        if ($hodDepartmentId && $course && isset($course['department_id']) && $course['department_id'] !== $hodDepartmentId) {
            $_SESSION['error'] = 'Access denied. You can only delete modules for courses in your department.';
            $this->redirect('modules');
            return;
        }
        $moduleModel = $this->model('ModuleModel');
        if ($moduleModel->deleteByCourseAndModule($courseId, $moduleId, $courseVersion)) {
            $_SESSION['message'] = 'Module deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete module or module not found.';
        }
        $this->redirect('modules');
    }
}
