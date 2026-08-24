<?php
/**
 * Student Complaint Letter Generation — RBAC enforced server-side.
 */

require_once BASE_PATH . '/models/ComplaintLetterModel.php';
require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';

class ComplaintLetterController extends Controller {

    private function requireLogin(): int {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
        }

        return (int) $_SESSION['user_id'];
    }

    private function userModel(): UserModel {
        require_once BASE_PATH . '/models/UserModel.php';

        return new UserModel();
    }

    private function complaintModel(): ComplaintLetterModel {
        return $this->model('ComplaintLetterModel');
    }

    private function requireView(int $uid): UserModel {
        $um = $this->userModel();
        if (!$um->canViewComplaintLetters($uid)) {
            $_SESSION['error'] = 'You do not have permission to view complaint letters.';
            $this->redirect('dashboard');
        }

        return $um;
    }

    private function requireManage(int $uid): UserModel {
        $um = $this->requireView($uid);
        if (!$um->canManageComplaintLetters($uid)) {
            $_SESSION['error'] = 'You do not have permission to manage complaint letters.';
            $this->redirect('complaint-letters');
        }
        if ($um->isHOD($uid) && trim((string) ($um->getHODDepartment($uid) ?? '')) === '') {
            $_SESSION['error'] = 'Your HOD department is not configured. Contact administration.';
            $this->redirect('complaint-letters');
        }

        return $um;
    }

    /**
     * null = all departments (SAO, ADM, REG, DIR)
     * string = locked department (HOD)
     */
    private function departmentScope(UserModel $um, int $uid): ?string {
        if ($um->isHOD($uid) && $um->canManageComplaintLetters($uid)) {
            $dept = trim((string) ($um->getHODDepartment($uid) ?? ''));

            return $dept !== '' ? $dept : null;
        }

        return null;
    }

    /**
     * Resolve department filter from request — HOD cannot override scope.
     */
    private function resolveFilterDepartment(UserModel $um, int $uid, ?string $requested = null): string {
        $scope = $this->departmentScope($um, $uid);
        if ($scope !== null) {
            return $scope;
        }

        return trim((string) ($requested ?? ''));
    }

    private function assertComplaintInScope(UserModel $um, int $uid, array $complaint): void {
        $scope = $this->departmentScope($um, $uid);
        if ($scope === null) {
            return;
        }
        if (trim((string) ($complaint['department_id'] ?? '')) !== $scope) {
            $_SESSION['error'] = 'Access denied. This complaint belongs to another department.';
            $this->redirect('complaint-letters');
        }
    }

    private function loadComplaintOrFail(ComplaintLetterModel $model, int $id): array {
        $complaint = $model->findComplaint($id);
        if (!$complaint) {
            $_SESSION['error'] = 'Complaint letter not found.';
            $this->redirect('complaint-letters');
        }

        return $complaint;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseViewData(int $uid, UserModel $um): array {
        $scope = $this->departmentScope($um, $uid);

        return [
            'canManage' => $um->canManageComplaintLetters($uid),
            'readOnly' => $um->isComplaintLetterReadOnly($uid),
            'isHodScoped' => $scope !== null,
            'hodDepartmentId' => $scope,
            'userRole' => $um->getUserRole($uid),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listFilters(UserModel $um, int $uid): array {
        return [
            'department_id' => $this->resolveFilterDepartment($um, $uid, $this->get('department_id', '')),
            'course_id' => trim((string) $this->get('course_id', '')),
            'academic_year' => trim((string) $this->get('academic_year', '')),
            'search' => trim((string) $this->get('q', '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(UserModel $um, int $uid): array {
        require_once BASE_PATH . '/models/DepartmentModel.php';
        require_once BASE_PATH . '/models/StudentModel.php';
        require_once BASE_PATH . '/models/CourseModel.php';

        $scope = $this->departmentScope($um, $uid);
        $deptModel = new DepartmentModel();
        $studentModel = new StudentModel();
        $courseModel = new CourseModel();

        $departments = $deptModel->getAll();
        if ($scope !== null) {
            $departments = array_values(array_filter($departments, static function ($d) use ($scope) {
                return ($d['department_id'] ?? '') === $scope;
            }));
        }

        $filters = $this->listFilters($um, $uid);
        $courseFilters = [];
        if ($filters['department_id'] !== '') {
            $courseFilters['department_id'] = $filters['department_id'];
        } elseif ($scope !== null) {
            $courseFilters['department_id'] = $scope;
        }
        $courses = $courseFilters !== []
            ? $courseModel->getCoursesWithDepartment($courseFilters)
            : [];
        $academicYears = $studentModel->getAcademicYears();

        return [
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $academicYears,
            'filters' => $filters,
        ];
    }

    /**
     * Filter options for create/edit forms (uses complaint or request prefill, not list GET filters).
     *
     * @param array<string, mixed> $prefill
     * @return array<string, mixed>
     */
    private function formFilterOptions(UserModel $um, int $uid, array $prefill = []): array {
        require_once BASE_PATH . '/models/DepartmentModel.php';
        require_once BASE_PATH . '/models/StudentModel.php';
        require_once BASE_PATH . '/models/CourseModel.php';

        $scope = $this->departmentScope($um, $uid);
        $deptModel = new DepartmentModel();
        $studentModel = new StudentModel();
        $courseModel = new CourseModel();

        $departments = $deptModel->getAll();
        if ($scope !== null) {
            $departments = array_values(array_filter($departments, static function ($d) use ($scope) {
                return ($d['department_id'] ?? '') === $scope;
            }));
        }

        $departmentId = trim((string) ($prefill['department_id'] ?? $this->get('department_id', '')));
        if ($departmentId === '' && $scope !== null) {
            $departmentId = $scope;
        }

        $courses = $departmentId !== ''
            ? $courseModel->getCoursesWithDepartment(['department_id' => $departmentId])
            : [];

        return [
            'departments' => $departments,
            'courses' => $courses,
            'academicYears' => $studentModel->getAcademicYears(),
            'filters' => [
                'department_id' => $departmentId,
                'course_id' => trim((string) ($prefill['course_id'] ?? $this->get('course_id', ''))),
                'academic_year' => trim((string) ($prefill['academic_year'] ?? $this->get('academic_year', ''))),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPost(UserModel $um, int $uid, ?int $exceptId = null): ?array {
        $scope = $this->departmentScope($um, $uid);
        $departmentId = trim((string) $this->post('department_id', ''));
        if ($scope !== null) {
            $departmentId = $scope;
        }
        $courseId = trim((string) $this->post('course_id', ''));
        $academicYear = trim((string) $this->post('academic_year', ''));
        $subject = trim((string) $this->post('subject', ''));
        $complaintBody = ComplaintLetterPdfHelper::sanitizeLetterHtml((string) $this->post('complaint_body', ''));
        $studentIds = $this->post('student_ids', []);
        if (!is_array($studentIds)) {
            $studentIds = [];
        }
        $studentIds = array_values(array_unique(array_filter(array_map('strval', $studentIds))));

        if ($departmentId === '' || $courseId === '' || $academicYear === '') {
            $_SESSION['error'] = 'Department, course, and academic year are required.';
            return null;
        }
        if ($subject === '' || trim(strip_tags($complaintBody)) === '') {
            $_SESSION['error'] = 'Subject and complaint details are required.';
            return null;
        }
        if ($studentIds === []) {
            $_SESSION['error'] = 'Select at least one student.';
            return null;
        }

        $model = $this->complaintModel();
        $students = $model->resolveStudentsForComplaint($studentIds, $departmentId, $courseId, $academicYear);
        if (count($students) !== count($studentIds)) {
            $_SESSION['error'] = 'One or more selected students are not valid for the chosen department, course, and academic year.';
            return null;
        }

        $letterDate = trim((string) $this->post('letter_date', date('Y-m-d')));
        if ($letterDate === '') {
            $letterDate = date('Y-m-d');
        }

        return [
            'data' => [
                'letter_date' => $letterDate,
                'subject' => $subject,
                'recipient_name' => '',
                'recipient_address' => '',
                'complaint_body' => $complaintBody,
                'action_required' => '',
                'department_id' => $departmentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'status' => $this->post('status', ComplaintLetterModel::STATUS_DRAFT) === ComplaintLetterModel::STATUS_FINAL
                    ? ComplaintLetterModel::STATUS_FINAL
                    : ComplaintLetterModel::STATUS_DRAFT,
            ],
            'students' => $students,
            'student_ids' => $studentIds,
        ];
    }

    private function auditContext(UserModel $um, int $uid): array {
        return [
            'role' => $um->getUserRole($uid),
            'department_id' => $this->departmentScope($um, $uid),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function complaintStudentsWithMailing(ComplaintLetterModel $model, int $id): array {
        return $model->enrichStudentsWithMailing($model->getComplaintStudents($id));
    }

    public function index() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->complaintModel();
        $scope = $this->departmentScope($um, $uid);
        $filters = $this->listFilters($um, $uid);
        $page = max(1, (int) $this->get('page', 1));
        $result = $model->listComplaints($filters, $page, 20, $scope);
        $opts = $this->filterOptions($um, $uid);

        return $this->view('complaint-letters/index', array_merge($this->baseViewData($uid, $um), $opts, [
            'page' => 'complaint-letters',
            'complaints' => $result['rows'],
            'total' => $result['total'],
            'currentPage' => $page,
            'perPage' => 20,
        ]));
    }

    public function create() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $opts = $this->formFilterOptions($um, $uid);

        return $this->view('complaint-letters/form', array_merge($this->baseViewData($uid, $um), $opts, [
            'page' => 'complaint-letters',
            'complaint' => null,
            'selectedStudentIds' => [],
            'formAction' => APP_URL . '/complaint-letters/store',
            'formTitle' => 'Create Complaint Letter',
        ]));
    }

    public function store() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $payload = $this->validatedPost($um, $uid);
        if ($payload === null) {
            $this->redirect('complaint-letters/create');
        }
        $model = $this->complaintModel();
        $id = $model->createComplaint($payload['data'], $payload['students'], $uid);
        if (!$id) {
            $_SESSION['error'] = 'Could not save complaint letter.';
            $this->redirect('complaint-letters/create');
        }
        $ctx = $this->auditContext($um, $uid);
        $model->logAudit($id, $uid, $ctx['role'], $ctx['department_id'], 'created', $payload['student_ids'], [
            'reference' => $model->findComplaint($id)['reference_no'] ?? '',
        ]);
        $this->logActivity('CREATE', 'complaint-letters', (string) $id, 'Complaint letter created');
        $_SESSION['success'] = 'Complaint letter saved.';
        $this->redirect('complaint-letters/view?id=' . $id);
    }

    public function show() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->complaintModel();
        $complaint = $this->loadComplaintOrFail($model, $id);
        $this->assertComplaintInScope($um, $uid, $complaint);
        $students = $model->getComplaintStudents($id);
        $auditLogs = $model->getAuditLogs($id, 50);

        return $this->view('complaint-letters/view', array_merge($this->baseViewData($uid, $um), [
            'page' => 'complaint-letters',
            'complaint' => $complaint,
            'students' => $students,
            'auditLogs' => $auditLogs,
        ]));
    }

    public function edit() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->complaintModel();
        $complaint = $this->loadComplaintOrFail($model, $id);
        $this->assertComplaintInScope($um, $uid, $complaint);
        $students = $model->getComplaintStudents($id);
        $selectedStudentIds = array_map(static fn ($s) => (string) ($s['student_id'] ?? ''), $students);
        $opts = $this->formFilterOptions($um, $uid, $complaint);

        return $this->view('complaint-letters/form', array_merge($this->baseViewData($uid, $um), $opts, [
            'page' => 'complaint-letters',
            'complaint' => $complaint,
            'linkedStudents' => $students,
            'selectedStudentIds' => $selectedStudentIds,
            'formAction' => APP_URL . '/complaint-letters/update',
            'formTitle' => 'Edit Complaint Letter',
        ]));
    }

    public function update() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->post('id', 0);
        $model = $this->complaintModel();
        $existing = $this->loadComplaintOrFail($model, $id);
        $this->assertComplaintInScope($um, $uid, $existing);
        $payload = $this->validatedPost($um, $uid, $id);
        if ($payload === null) {
            $this->redirect('complaint-letters/edit?id=' . $id);
        }
        if (!$model->updateComplaint($id, $payload['data'], $payload['students'], $uid)) {
            $_SESSION['error'] = 'Could not update complaint letter.';
            $this->redirect('complaint-letters/edit?id=' . $id);
        }
        $ctx = $this->auditContext($um, $uid);
        $model->logAudit($id, $uid, $ctx['role'], $ctx['department_id'], 'updated', $payload['student_ids']);
        $this->logActivity('UPDATE', 'complaint-letters', (string) $id, 'Complaint letter updated');
        $_SESSION['success'] = 'Complaint letter updated.';
        $this->redirect('complaint-letters/view?id=' . $id);
    }

    public function delete() {
        $uid = $this->requireLogin();
        $um = $this->requireManage($uid);
        $id = (int) $this->post('id', 0);
        $model = $this->complaintModel();
        $complaint = $this->loadComplaintOrFail($model, $id);
        $this->assertComplaintInScope($um, $uid, $complaint);
        $studentIds = array_map(static fn ($s) => (string) ($s['student_id'] ?? ''), $model->getComplaintStudents($id));
        if (!$model->softDeleteComplaint($id, $uid)) {
            $_SESSION['error'] = 'Could not delete complaint letter.';
            $this->redirect('complaint-letters/view?id=' . $id);
        }
        $ctx = $this->auditContext($um, $uid);
        $model->logAudit($id, $uid, $ctx['role'], $ctx['department_id'], 'deleted', $studentIds, [
            'reference_no' => $complaint['reference_no'] ?? '',
        ]);
        $this->logActivity('DELETE', 'complaint-letters', (string) $id, 'Complaint letter soft-deleted: ' . ($complaint['reference_no'] ?? ''));
        $_SESSION['success'] = 'Complaint letter deleted.';
        $this->redirect('complaint-letters');
    }

    public function preview() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->complaintModel();
        $complaint = $this->loadComplaintOrFail($model, $id);
        $this->assertComplaintInScope($um, $uid, $complaint);
        $students = $this->complaintStudentsWithMailing($model, $id);

        return $this->view('complaint-letters/preview', array_merge($this->baseViewData($uid, $um), [
            'page' => 'complaint-letters',
            'complaint' => $complaint,
            'students' => $students,
            'use_print_layout' => true,
        ]));
    }

    public function printLetter() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->complaintModel();
        $complaint = $this->loadComplaintOrFail($model, $id);
        $this->assertComplaintInScope($um, $uid, $complaint);
        $students = $this->complaintStudentsWithMailing($model, $id);
        if ($um->canManageComplaintLetters($uid)) {
            $model->markPrinted($id, $uid);
            $ctx = $this->auditContext($um, $uid);
            $model->logAudit($id, $uid, $ctx['role'], $ctx['department_id'], 'printed', array_map(static fn ($s) => (string) ($s['student_id'] ?? ''), $students));
        }

        return $this->view('complaint-letters/preview', array_merge($this->baseViewData($uid, $um), [
            'page' => 'complaint-letters',
            'complaint' => $complaint,
            'students' => $students,
            'autoPrint' => true,
            'use_print_layout' => true,
        ]));
    }

    public function pdf() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->complaintModel();
        $complaint = $this->loadComplaintOrFail($model, $id);
        $this->assertComplaintInScope($um, $uid, $complaint);
        $students = $this->complaintStudentsWithMailing($model, $id);

        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not available. Run composer install on the server.';
            $this->redirect('complaint-letters/view?id=' . $id);
        }

        if ($um->canManageComplaintLetters($uid)) {
            $model->markGenerated($id, $uid);
            $ctx = $this->auditContext($um, $uid);
            $model->logAudit($id, $uid, $ctx['role'], $ctx['department_id'], 'generated', array_map(static fn ($s) => (string) ($s['student_id'] ?? ''), $students));
        }

        ComplaintLetterPdfHelper::streamPdf($complaint, $students, !$um->isComplaintLetterReadOnly($uid));
    }

    public function history() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->complaintModel();
        $scope = $this->departmentScope($um, $uid);
        $logs = $model->getAuditLogs(null, 200);
        if ($scope !== null) {
            $allowedIds = [];
            $filters = ['department_id' => $scope];
            $all = $model->listComplaints($filters, 1, 5000, $scope);
            foreach ($all['rows'] as $row) {
                $allowedIds[(int) ($row['id'] ?? 0)] = true;
            }
            $logs = array_values(array_filter($logs, static function ($log) use ($allowedIds, $scope) {
                $cid = (int) ($log['complaint_letter_id'] ?? 0);
                if ($cid > 0) {
                    return isset($allowedIds[$cid]);
                }

                return trim((string) ($log['department_id'] ?? '')) === $scope;
            }));
        }

        return $this->view('complaint-letters/history', array_merge($this->baseViewData($uid, $um), [
            'page' => 'complaint-letters',
            'auditLogs' => $logs,
        ]));
    }

    public function coursesJson() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        require_once BASE_PATH . '/models/CourseModel.php';
        $departmentId = $this->resolveFilterDepartment($um, $uid, $this->get('department_id', ''));
        if ($departmentId === '') {
            return $this->json(['courses' => []]);
        }
        $courseModel = new CourseModel();
        $courses = $courseModel->getCoursesWithDepartment(['department_id' => $departmentId]);
        $out = array_map(static function ($c) {
            return [
                'course_id' => $c['course_id'] ?? '',
                'course_name' => $c['course_name'] ?? '',
                'department_id' => $c['department_id'] ?? '',
            ];
        }, $courses);

        return $this->json(['courses' => $out]);
    }

    public function studentsJson() {
        $uid = $this->requireLogin();
        $um = $this->requireView($uid);
        $model = $this->complaintModel();
        $departmentId = $this->resolveFilterDepartment($um, $uid, $this->get('department_id', ''));
        $courseId = trim((string) $this->get('course_id', ''));
        $academicYear = trim((string) $this->get('academic_year', ''));
        $search = trim((string) $this->get('q', ''));
        if ($departmentId === '' || $courseId === '' || $academicYear === '') {
            return $this->json(['students' => []]);
        }
        $students = $model->listStudentsForSelection($departmentId, $courseId, $academicYear, $search);
        $out = array_map(static function ($s) {
            return [
                'student_id' => $s['student_id'] ?? '',
                'student_fullname' => $s['student_fullname'] ?? '',
                'student_nic' => $s['student_nic'] ?? '',
                'course_name' => $s['course_name'] ?? '',
                'department_name' => $s['department_name'] ?? '',
            ];
        }, $students);

        return $this->json(['students' => $out, 'readOnly' => $um->isComplaintLetterReadOnly($uid)]);
    }
}
