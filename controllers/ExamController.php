<?php

class ExamController extends Controller {

    public function index() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examModel = $this->model('ExamModel');
        $exams = $examModel->listExamsWithCourse();
        return $this->view('exams/index', [
            'title' => 'Exams',
            'page' => 'exams',
            'exams' => $exams,
        ]);
    }

    public function showExam() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('id', 0);
        if ($examId < 1) {
            $_SESSION['error'] = 'Invalid exam.';
            $this->redirect('exams');
            return;
        }
        $examModel = $this->model('ExamModel');
        $exam = $examModel->findWithCourse($examId);
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found.';
            $this->redirect('exams');
            return;
        }

        $moduleModel = $this->model('ModuleModel');
        $courseId = (string) ($exam['course_id'] ?? '');
        $moduleIndex = [];
        if ($courseId !== '') {
            foreach ($moduleModel->getAllWithCourse($courseId) as $modRow) {
                $mid = trim((string) ($modRow['module_id'] ?? ''));
                if ($mid !== '') {
                    $moduleIndex[$mid] = (string) ($modRow['module_name'] ?? '');
                }
            }
        }
        $modules = [];
        foreach ($examModel->decodeExamModulesList($exam) as $m) {
            $mid = trim((string) ($m['module_id'] ?? ''));
            if ($mid === '') {
                continue;
            }
            $m['module_name'] = $moduleIndex[$mid] ?? '';
            $modules[] = $m;
        }
        $students = $examModel->getRegisteredStudentsBasicForExam($examId);
        require_once BASE_PATH . '/helpers/ExamRollHelper.php';
        $students = ExamRollHelper::assignRollNumbersToStudents($exam, $students);

        return $this->view('exams/view', [
            'title' => 'Exam #' . $examId,
            'page' => 'exams',
            'exam' => $exam,
            'modules' => $modules,
            'students' => $students,
        ]);
    }

    /**
     * Choose registered students (number and name) before downloading admission PDFs.
     */
    public function admissionSelect() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        if ($examId < 1) {
            $_SESSION['error'] = 'Missing exam.';
            $this->redirect('exams');
            return;
        }
        $examModel = $this->model('ExamModel');
        $exam = $examModel->findWithCourse($examId);
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found.';
            $this->redirect('exams');
            return;
        }
        $students = $examModel->getRegisteredStudentsBasicForExam($examId);
        if (empty($students)) {
            $_SESSION['error'] = 'No students registered for this exam.';
            $this->redirect('exams');
            return;
        }

        return $this->view('exams/admission_select', [
            'title' => 'Admission — select students',
            'page' => 'exams',
            'exam' => $exam,
            'students' => $students,
        ]);
    }

    public function create() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $courseModel = $this->model('CourseModel');
        $courses = $courseModel->getCoursesWithDepartment([]);
        return $this->view('exams/form', [
            'title' => 'Create exam',
            'page' => 'exams',
            'courses' => $courses,
        ]);
    }

    /**
     * @return array{errors: list<string>, course_id: string, group_id: int|null, semester: int, schedule: list<array>, studentIds: list<string>}
     */
    private function parseExamPost(): array {
        $courseId = trim((string) $this->post('course_id', ''));
        $groupIdRaw = $this->post('group_id', '');
        $groupId = ($groupIdRaw === '' || $groupIdRaw === null) ? null : (int) $groupIdRaw;
        $semester = (int) $this->post('semester', 0);

        $modIn = $this->post('mod', []);
        if (!is_array($modIn)) {
            $modIn = [];
        }
        $schedule = [];
        foreach ($modIn as $mid => $row) {
            if (!is_array($row)) {
                continue;
            }
            $mid = trim((string) $mid);
            if ($mid === '') {
                continue;
            }
            if (empty($row['include'])) {
                continue;
            }
            $date = trim((string) ($row['date'] ?? ''));
            $time = trim((string) ($row['time'] ?? ''));
            $loc = trim((string) ($row['location'] ?? ''));
            if ($date === '' || $time === '' || $loc === '') {
                continue;
            }
            $schedule[] = [
                'module_id' => $mid,
                'exam_date' => $date,
                'exam_time' => $time,
                'location' => $loc,
            ];
        }

        $studentIds = isset($_POST['student_ids']) && is_array($_POST['student_ids'])
            ? array_map('strval', $_POST['student_ids'])
            : [];
        $studentIds = array_values(array_filter(array_map('trim', $studentIds)));

        $errors = [];
        if ($courseId === '') {
            $errors[] = 'Please select a course.';
        }
        if ($semester < 1 || $semester > 12) {
            $errors[] = 'Please select a semester.';
        }
        if ($groupId === null || $groupId < 1) {
            $errors[] = 'Please select a valid batch (group).';
        }
        if (count($schedule) < 1) {
            $errors[] = 'Select at least one module and set date, time, and venue for each selected module.';
        }
        $incomplete = false;
        foreach ($modIn as $mid => $row) {
            if (!is_array($row) || empty($row['include'])) {
                continue;
            }
            $mid = trim((string) $mid);
            $d = trim((string) ($row['date'] ?? ''));
            $t = trim((string) ($row['time'] ?? ''));
            $l = trim((string) ($row['location'] ?? ''));
            if ($mid !== '' && ($d === '' || $t === '' || $l === '')) {
                $incomplete = true;
            }
        }
        if ($incomplete) {
            $errors[] = 'Each selected module must have date, time, and venue filled in.';
        }
        if (count($studentIds) < 1) {
            $errors[] = 'No students selected. Choose a batch and tick at least one student.';
        }

        return [
            'errors' => $errors,
            'course_id' => $courseId,
            'group_id' => $groupId,
            'semester' => $semester,
            'schedule' => $schedule,
            'studentIds' => $studentIds,
        ];
    }

    public function store() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('exams/create');
            return;
        }

        $parsed = $this->parseExamPost();
        $errors = $parsed['errors'];
        if (!empty($errors)) {
            $courseModel = $this->model('CourseModel');
            $courses = $courseModel->getCoursesWithDepartment([]);
            return $this->view('exams/form', [
                'title' => 'Create exam',
                'page' => 'exams',
                'courses' => $courses,
                'errors' => $errors,
                'old' => $_POST,
            ]);
        }

        try {
            $examModel = $this->model('ExamModel');
            $newId = $examModel->createExamAndAssignStudents(
                $parsed['course_id'],
                $parsed['group_id'],
                $parsed['semester'],
                $parsed['schedule'],
                $parsed['studentIds']
            );
            $_SESSION['message'] = 'Exam #' . $newId . ' created and students assigned.';
            $this->logActivity('CREATE', 'exam', (string) $newId, 'Created exam and assigned ' . count($parsed['studentIds']) . ' students.', null, [
                'course_id' => $parsed['course_id'],
                'semester' => $parsed['semester'],
            ]);
            $this->redirect('exams');
        } catch (Throwable $e) {
            error_log('ExamController::store: ' . $e->getMessage());
            $courseModel = $this->model('CourseModel');
            $courses = $courseModel->getCoursesWithDepartment([]);
            return $this->view('exams/form', [
                'title' => 'Create exam',
                'page' => 'exams',
                'courses' => $courses,
                'errors' => ['Could not save the exam. If tables are missing, import database/exam_module.sql.'],
                'old' => $_POST,
            ]);
        }
    }

    public function edit() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('id', 0);
        if ($examId < 1) {
            $_SESSION['error'] = 'Invalid exam.';
            $this->redirect('exams');
            return;
        }
        $examModel = $this->model('ExamModel');
        $exam = $examModel->findWithCourse($examId);
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found.';
            $this->redirect('exams');
            return;
        }
        $courseModel = $this->model('CourseModel');
        $courses = $courseModel->getCoursesWithDepartment([]);
        $studentIds = $examModel->getStudentIdsForExam($examId);
        $mods = $examModel->decodeExamModulesList($exam);
        $modPost = [];
        foreach ($mods as $row) {
            $mid = $row['module_id'];
            if ($mid === '') {
                continue;
            }
            $modPost[$mid] = [
                'include' => '1',
                'date' => $row['exam_date'],
                'time' => $row['exam_time'],
                'location' => $row['location'],
            ];
        }
        $sem = $exam['semester'] ?? 1;
        if ($sem === null || $sem === '') {
            $sem = 1;
        }
        $old = [
            'course_id' => $exam['course_id'],
            'semester' => (string) (int) $sem,
            'group_id' => (string) ($exam['group_id'] ?? ''),
            'mod' => $modPost,
            'student_ids' => $studentIds,
        ];
        return $this->view('exams/form', [
            'title' => 'Edit exam',
            'page' => 'exams',
            'courses' => $courses,
            'formAction' => 'exams/update',
            'heading' => 'Edit exam',
            'headingIcon' => 'fa-pen-to-square',
            'lead' => 'Update modules, schedule, and registered students. Marks for removed modules are deleted.',
            'hiddenExamId' => $examId,
            'submitLabel' => 'Update exam',
            'old' => $old,
        ]);
    }

    public function update() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('exams');
            return;
        }
        $examId = (int) $this->post('exam_id', 0);
        if ($examId < 1) {
            $_SESSION['error'] = 'Invalid exam.';
            $this->redirect('exams');
            return;
        }

        $parsed = $this->parseExamPost();
        $errors = $parsed['errors'];
        if (!empty($errors)) {
            $courseModel = $this->model('CourseModel');
            $courses = $courseModel->getCoursesWithDepartment([]);
            $_POST['exam_id'] = (string) $examId;
            return $this->view('exams/form', [
                'title' => 'Edit exam',
                'page' => 'exams',
                'courses' => $courses,
                'formAction' => 'exams/update',
                'heading' => 'Edit exam',
                'headingIcon' => 'fa-pen-to-square',
                'lead' => 'Update modules, schedule, and registered students. Marks for removed modules are deleted.',
                'hiddenExamId' => $examId,
                'submitLabel' => 'Update exam',
                'errors' => $errors,
                'old' => $_POST,
            ]);
        }

        try {
            $examModel = $this->model('ExamModel');
            $examModel->updateExamAndAssignStudents(
                $examId,
                $parsed['course_id'],
                $parsed['group_id'],
                $parsed['semester'],
                $parsed['schedule'],
                $parsed['studentIds']
            );
            $_SESSION['message'] = 'Exam #' . $examId . ' updated.';
            $this->logActivity('UPDATE', 'exam', (string) $examId, 'Updated exam.', null, [
                'course_id' => $parsed['course_id'],
            ]);
            $this->redirect('exams');
        } catch (Throwable $e) {
            error_log('ExamController::update: ' . $e->getMessage());
            $courseModel = $this->model('CourseModel');
            $courses = $courseModel->getCoursesWithDepartment([]);
            $_POST['exam_id'] = (string) $examId;
            return $this->view('exams/form', [
                'title' => 'Edit exam',
                'page' => 'exams',
                'courses' => $courses,
                'formAction' => 'exams/update',
                'heading' => 'Edit exam',
                'headingIcon' => 'fa-pen-to-square',
                'lead' => 'Update modules, schedule, and registered students.',
                'hiddenExamId' => $examId,
                'submitLabel' => 'Update exam',
                'errors' => ['Could not update the exam.'],
                'old' => $_POST,
            ]);
        }
    }

    public function deleteExam() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('exams');
            return;
        }
        $examId = (int) $this->post('exam_id', 0);
        if ($examId < 1) {
            $_SESSION['error'] = 'Invalid exam.';
            $this->redirect('exams');
            return;
        }
        $examModel = $this->model('ExamModel');
        $exam = $examModel->findWithCourse($examId);
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found.';
            $this->redirect('exams');
            return;
        }
        if ($examModel->deleteExam($examId)) {
            $_SESSION['message'] = 'Exam #' . $examId . ' deleted.';
            $this->logActivity('DELETE', 'exam', (string) $examId, 'Deleted exam.', null, []);
        } else {
            $_SESSION['error'] = 'Could not delete the exam.';
        }
        $this->redirect('exams');
    }

    /**
     * AJAX: modules for a course and semester (uses module.semester).
     */
    public function ajaxModules() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $courseId = trim((string) $this->get('course_id', ''));
        $semester = (int) $this->get('semester', 0);
        if ($courseId === '' || $semester < 1) {
            return $this->json(['success' => false, 'modules' => [], 'message' => 'Course and semester required.']);
        }
        $moduleModel = $this->model('ModuleModel');
        $modules = $moduleModel->getByCourseAndSemester($courseId, $semester);
        return $this->json(['success' => true, 'modules' => $modules]);
    }

    /**
     * AJAX: batches (groups) for a course.
     */
    public function ajaxGroups() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $courseId = trim((string) $this->get('course_id', ''));
        if ($courseId === '') {
            return $this->json(['success' => false, 'groups' => []]);
        }
        $groupModel = $this->model('GroupModel');
        $groups = $groupModel->getActiveGroupsByCourse($courseId);
        return $this->json(['success' => true, 'groups' => $groups]);
    }

    /**
     * AJAX: students in a group (batch).
     */
    public function ajaxStudents() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $groupId = (int) $this->get('group_id', 0);
        if ($groupId < 1) {
            return $this->json(['success' => false, 'students' => []]);
        }
        $groupModel = $this->model('GroupModel');
        $students = $groupModel->getGroupStudents($groupId);
        require_once BASE_PATH . '/helpers/FormatHelper.php';
        foreach ($students as &$row) {
            $row['display_name'] = FormatHelper::studentInitialsName($row);
        }
        unset($row);
        return $this->json(['success' => true, 'students' => $students]);
    }
}
