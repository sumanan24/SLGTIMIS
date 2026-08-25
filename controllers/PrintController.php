<?php

class PrintController extends Controller {

    /**
     * @return array{exam: array, module_id: string, module_name: string, moduleLine: string}|null
     */
    private function resolveModuleContext(array $exam, string $moduleId): ?array {
        $moduleId = trim($moduleId);
        if ($moduleId === '') {
            return null;
        }
        $examModel = $this->model('ExamModel');
        if (!$examModel->examHasModule($exam, $moduleId)) {
            return null;
        }
        $entry = $examModel->getModuleScheduleEntry($exam, $moduleId);
        if (!$entry) {
            return null;
        }
        $courseId = (string) ($exam['course_id'] ?? '');
        $moduleModel = $this->model('ModuleModel');
        $mod = $moduleModel->getByCourseAndModule($courseId, $moduleId);
        $name = is_array($mod) ? (string) ($mod['module_name'] ?? $moduleId) : $moduleId;
        $overlay = $exam;
        if (($entry['exam_date'] ?? '') !== '') {
            $overlay['exam_date'] = $entry['exam_date'];
        }
        if (($entry['exam_time'] ?? '') !== '') {
            $overlay['exam_time'] = $entry['exam_time'];
        }
        if (($entry['location'] ?? '') !== '') {
            $overlay['location'] = $entry['location'];
        }
        return [
            'exam' => $overlay,
            'module_id' => $moduleId,
            'module_name' => $name,
            'moduleLine' => $name . ' (' . $moduleId . ')',
        ];
    }

    public function attendanceSheet() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        $moduleId = trim((string) $this->get('module_id', ''));
        if ($examId < 1 || $moduleId === '') {
            $_SESSION['error'] = 'Missing exam or module.';
            $this->redirect('exams');
            return;
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install (see project composer.json).';
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
        $ctx = $this->resolveModuleContext($exam, $moduleId);
        if (!$ctx) {
            $_SESSION['error'] = 'Invalid module for this exam.';
            $this->redirect('exams');
            return;
        }
        $students = $examModel->getStudentsWithMarksForModule($examId, $moduleId);
        $html = ExamPdfHelper::renderTemplate('attendance_sheet.php', [
            'exam' => $ctx['exam'],
            'students' => $students,
            'moduleLine' => $ctx['moduleLine'],
        ]);
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $moduleId);
        ExamPdfHelper::streamHtml($html, 'exam_' . $examId . '_mod_' . $safe . '_attendance.pdf');
    }

    public function firstMarkSheet() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        $moduleId = trim((string) $this->get('module_id', ''));
        if ($examId < 1 || $moduleId === '') {
            $_SESSION['error'] = 'Missing exam or module.';
            $this->redirect('exams');
            return;
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
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
        $ctx = $this->resolveModuleContext($exam, $moduleId);
        if (!$ctx) {
            $_SESSION['error'] = 'Invalid module for this exam.';
            $this->redirect('exams');
            return;
        }
        $rows = $examModel->getStudentsWithMarksForModule($examId, $moduleId);
        $html = ExamPdfHelper::renderTemplate('first_mark_sheet.php', [
            'exam' => $ctx['exam'],
            'rows' => $rows,
            'moduleLine' => $ctx['moduleLine'],
        ]);
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $moduleId);
        ExamPdfHelper::streamHtml($html, 'exam_' . $examId . '_mod_' . $safe . '_first_marking.pdf');
    }

    public function secondMarkSheet() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        $moduleId = trim((string) $this->get('module_id', ''));
        if ($examId < 1 || $moduleId === '') {
            $_SESSION['error'] = 'Missing exam or module.';
            $this->redirect('exams');
            return;
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
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
        $ctx = $this->resolveModuleContext($exam, $moduleId);
        if (!$ctx) {
            $_SESSION['error'] = 'Invalid module for this exam.';
            $this->redirect('exams');
            return;
        }
        $students = $examModel->getStudentsWithMarksForModule($examId, $moduleId);
        $html = ExamPdfHelper::renderTemplate('second_mark_sheet.php', [
            'exam' => $ctx['exam'],
            'students' => $students,
            'moduleLine' => $ctx['moduleLine'],
        ]);
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $moduleId);
        ExamPdfHelper::streamHtml($html, 'exam_' . $examId . '_mod_' . $safe . '_second_marking.pdf');
    }

    public function examRollStickers() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        $copies = (int) $this->get('copies', 1);
        if ($examId < 1) {
            $_SESSION['error'] = 'Missing exam.';
            $this->redirect('exams');
            return;
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        require_once BASE_PATH . '/helpers/ExamRollHelper.php';

        $examModel = $this->model('ExamModel');
        $exam = $examModel->findWithCourse($examId);
        if (!$exam) {
            $_SESSION['error'] = 'Exam not found.';
            $this->redirect('exams');
            return;
        }
        $students = $examModel->getRegisteredStudentsBasicForExam($examId);
        $students = ExamRollHelper::assignRollNumbersToStudents($exam, $students);
        if ($students === []) {
            $_SESSION['error'] = 'No students registered for this exam.';
            $this->redirect('exams/view?id=' . $examId);
            return;
        }
        try {
            ExamPdfHelper::streamRollStickersPdf(
                $students,
                $copies,
                'exam_' . $examId . '_roll_stickers.pdf'
            );
        } catch (Throwable $e) {
            error_log('PrintController::examRollStickers: ' . $e->getMessage());
            $_SESSION['error'] = 'Could not create stickers PDF.';
            $this->redirect('exams/view?id=' . $examId);
        }
    }

    public function admissionCard() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        $studentId = trim((string) $this->get('student_id', ''));
        if ($examId < 1 || $studentId === '') {
            $_SESSION['error'] = 'Missing exam or student.';
            $this->redirect('exams');
            return;
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
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
        if (!$examModel->isStudentOnExam($examId, $studentId)) {
            $_SESSION['error'] = 'Student is not registered for this exam.';
            $this->redirect('exams');
            return;
        }

        $studentModel = $this->model('StudentModel');
        $st = $studentModel->find($studentId);
        if (!$st) {
            $_SESSION['error'] = 'Student not found.';
            $this->redirect('exams');
            return;
        }

        $moduleModel = $this->model('ModuleModel');
        $courseModel = $this->model('CourseModel');
        $departmentModel = $this->model('DepartmentModel');
        $moduleRows = $this->buildAdmissionModuleRows($examModel, $moduleModel, $exam);
        $meta = $this->buildAdmissionMeta($exam, $courseModel, $departmentModel, $moduleRows);

        require_once BASE_PATH . '/helpers/FormatHelper.php';
        require_once BASE_PATH . '/helpers/ExamRollHelper.php';
        $st['display_name'] = FormatHelper::studentInitialsName($st);
        $rollMap = ExamRollHelper::buildRollMapForExam($exam, $examModel->getStudentIdsForExam($examId));
        $st['exam_roll_number'] = $rollMap[$studentId] ?? '';
        $assets = $this->admissionPdfAssets();

        $inner = ExamPdfHelper::renderTemplate('admission_card.php', [
            'exam' => $exam,
            'student' => $st,
            'moduleRows' => $moduleRows,
            'meta' => $meta,
            'logo_src' => $assets['logo'],
            'principal_sig_src' => $assets['signature'],
            'principal_name' => $assets['principal_name'] ?? 'R.Mathaan',
            'exam_rules' => $this->examAdmissionRules(),
            'layout' => $this->admissionPageLayout(count($moduleRows)),
        ]);
        $html = $this->wrapAdmissionDocument($inner);
        $fn = 'admission_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $studentId) . '.pdf';
        ExamPdfHelper::streamHtml($html, $fn);
    }

    /**
     * All students registered for the exam — one PDF, one page per student.
     */
    public function admissionCardsBulk() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        $examId = (int) $this->get('exam_id', 0);
        if ($examId < 1) {
            $_SESSION['error'] = 'Missing exam.';
            $this->redirect('exams');
            return;
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
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

        $studentIds = $examModel->getStudentIdsForExam($examId);
        if (empty($studentIds)) {
            $_SESSION['error'] = 'No students registered for this exam.';
            $this->redirect('exams');
            return;
        }

        try {
            $this->streamAdmissionCardsPdf($examModel, $exam, $studentIds, 'exam_' . $examId . '_admission_all_students.pdf');
        } catch (Throwable $e) {
            error_log('PrintController::admissionCardsBulk: ' . $e->getMessage());
            $_SESSION['error'] = 'Could not generate admission PDF. Try selecting fewer students from Admission → Select students.';
            $this->redirect('exams');
        }
    }

    /**
     * Selected students only (POST from exams/admission-select).
     */
    public function admissionCardsSelected() {
        if (!$this->checkExamsAccess()) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $_SESSION['error'] = 'Use the admission selection page to download.';
            $this->redirect('exams');
            return;
        }
        $examId = (int) $this->post('exam_id', 0);
        $raw = $this->post('student_ids', []);
        if (!is_array($raw)) {
            $raw = [];
        }
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
            $this->redirect('exams/admission-select?exam_id=' . $examId);
            return;
        }
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

        $allowed = array_flip($examModel->getStudentIdsForExam($examId));
        $seen = [];
        $studentIds = [];
        foreach ($raw as $rid) {
            $sid = trim((string) $rid);
            if ($sid === '' || !isset($allowed[$sid]) || isset($seen[$sid])) {
                continue;
            }
            $seen[$sid] = true;
            $studentIds[] = $sid;
        }
        if (empty($studentIds)) {
            $_SESSION['error'] = 'Select at least one student.';
            $this->redirect('exams/admission-select?exam_id=' . $examId);
            return;
        }

        try {
            $suffix = count($studentIds) === 1 ? preg_replace('/[^a-zA-Z0-9_-]+/', '_', $studentIds[0]) : 'selected';
            $this->streamAdmissionCardsPdf($examModel, $exam, $studentIds, 'exam_' . $examId . '_admission_' . $suffix . '.pdf');
        } catch (Throwable $e) {
            error_log('PrintController::admissionCardsSelected: ' . $e->getMessage());
            $_SESSION['error'] = 'Could not generate admission PDF. Try fewer students at a time.';
            $this->redirect('exams/admission-select?exam_id=' . $examId);
        }
    }

    /**
     * One merged PDF — render each student separately then combine with FPDI (fast, no 120s timeout).
     *
     * @param list<string> $studentIds
     * @throws RuntimeException
     */
    private function streamAdmissionCardsPdf($examModel, array $exam, array $studentIds, string $filename): void {
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        $parts = $this->buildAdmissionCardHtmlParts($examModel, $exam, $studentIds);
        if ($parts === null || $parts === []) {
            throw new RuntimeException('Could not load student records.');
        }
        ExamPdfHelper::streamAdmissionInnerPartsMerged(
            $parts,
            function (string $body): string {
                return $this->wrapAdmissionDocument($body);
            },
            $filename
        );
    }

    /**
     * @param list<string> $studentIds
     * @return list<string>|null Inner HTML fragments (one per student, 2 pages each)
     */
    private function buildAdmissionCardHtmlParts($examModel, array $exam, array $studentIds): ?array {
        if (empty($studentIds)) {
            return null;
        }
        $examId = (int) ($exam['id'] ?? 0);
        $moduleModel = $this->model('ModuleModel');
        $courseModel = $this->model('CourseModel');
        $departmentModel = $this->model('DepartmentModel');
        $moduleRows = $this->buildAdmissionModuleRows($examModel, $moduleModel, $exam);
        $meta = $this->buildAdmissionMeta($exam, $courseModel, $departmentModel, $moduleRows);
        $assets = $this->admissionPdfAssets();

        $students = $examModel->getExamStudentsForAdmission($examId, $studentIds);
        if (empty($students)) {
            return null;
        }

        require_once BASE_PATH . '/helpers/ExamRollHelper.php';
        $rollMap = ExamRollHelper::buildRollMapForExam($exam, $examModel->getStudentIdsForExam($examId));
        foreach ($students as &$st) {
            $sid = (string) ($st['student_id'] ?? '');
            $st['exam_roll_number'] = $rollMap[$sid] ?? '';
        }
        unset($st);

        $innerParts = [];
        foreach ($students as $st) {
            $innerParts[] = ExamPdfHelper::renderTemplate('admission_card.php', [
                'exam' => $exam,
                'student' => $st,
                'moduleRows' => $moduleRows,
                'meta' => $meta,
                'logo_src' => $assets['logo'],
                'principal_sig_src' => $assets['signature'],
                'principal_name' => $assets['principal_name'] ?? 'R.Mathaan',
                'exam_rules' => $this->examAdmissionRules(),
                'layout' => $this->admissionPageLayout(count($moduleRows)),
            ]);
        }

        return $innerParts;
    }

    /**
     * @return list<array{code: string, name: string, date_dmy: string, time: string, location: string}>
     */
    private function buildAdmissionModuleRows($examModel, $moduleModel, array $exam): array {
        $courseId = (string) ($exam['course_id'] ?? '');
        $moduleIndex = [];
        if ($courseId !== '') {
            foreach ($moduleModel->getAllWithCourse($courseId) as $modRow) {
                $mid = trim((string) ($modRow['module_id'] ?? ''));
                if ($mid !== '') {
                    $moduleIndex[$mid] = (string) ($modRow['module_name'] ?? $mid);
                }
            }
        }
        $out = [];
        foreach ($examModel->decodeExamModulesList($exam) as $row) {
            $mid = trim((string) ($row['module_id'] ?? ''));
            if ($mid === '') {
                continue;
            }
            $out[] = [
                'code' => $mid,
                'name' => $moduleIndex[$mid] ?? $mid,
                'date_dmy' => $this->formatDateDmy((string) ($row['exam_date'] ?? '')),
                'time' => (string) ($row['exam_time'] ?? ''),
                'time_display' => $this->formatAdmissionTimeDisplay((string) ($row['exam_time'] ?? '')),
                'location' => (string) ($row['location'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @param list<array{code: string, name: string, date_dmy: string, time: string, location: string}> $moduleRows
     * @return array{subtitle: string, subject_line: string, exam_centre: string, nvq_semester: string}
     */
    private function buildAdmissionMeta(array $exam, $courseModel, $departmentModel, array $moduleRows): array {
        $course = null;
        if (!empty($exam['course_id'])) {
            $course = $courseModel->find($exam['course_id']);
        }
        $deptName = '';
        if (is_array($course) && !empty($course['department_id'])) {
            $dept = $departmentModel->find($course['department_id']);
            if (is_array($dept)) {
                $deptName = (string) ($dept['department_name'] ?? '');
            }
        }
        $courseName = (string) ($course['course_name'] ?? $exam['course_name'] ?? '');
        $subjectLine = $courseName;
        if ($deptName !== '') {
            $subjectLine .= ($subjectLine !== '' ? ' — ' : '') . $deptName;
        }

        $nvq = is_array($course) ? trim((string) ($course['course_nvq_level'] ?? '')) : '';
        $semInt = isset($exam['semester']) && $exam['semester'] !== null && $exam['semester'] !== ''
            ? (int) $exam['semester']
            : 0;
        $semRoman = $semInt > 0 ? $this->semesterToRoman($semInt) : '';
        $monthYear = '';
        if (!empty($exam['exam_date'])) {
            $ts = strtotime((string) $exam['exam_date']);
            if ($ts !== false) {
                $monthYear = date('F Y', $ts);
            }
        }

        $nvqDisplay = $nvq !== '' ? $nvq : '—';
        $nvqPadded = '';
        if ($nvq !== '') {
            $nvqPadded = str_pad((string) (int) preg_replace('/\D/', '', $nvq), 2, '0', STR_PAD_LEFT);
        }

        $nvqSem = 'Level ' . $nvqDisplay;
        if ($semRoman !== '') {
            $nvqSem .= ' Semester ' . $semRoman;
        }

        $subtitle = 'Common Examination';
        if ($nvqPadded !== '') {
            $subtitle .= ' – NVQ Level ' . $nvqPadded;
        }
        if ($semRoman !== '') {
            $subtitle .= ' – Semester ' . $semRoman;
        }
        if ($monthYear !== '') {
            $subtitle .= ' ' . $monthYear;
        }

        $centre = $this->guessExamCentre($exam, $moduleRows);

        return [
            'subtitle' => $subtitle,
            'subject_line' => $subjectLine,
            'subject_short' => $courseName,
            'exam_centre' => $centre,
            'nvq_semester' => $nvqSem,
        ];
    }

    private function semesterToRoman(int $semester): string {
        static $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$semester] ?? (string) $semester;
    }

    private function formatAdmissionTimeDisplay(string $time): string {
        $time = trim($time);
        if ($time === '') {
            return '';
        }
        if (preg_match('/[–—]/u', $time)) {
            return $time;
        }
        $parts = preg_split('/\s*-\s*/', $time);
        if (is_array($parts) && count($parts) === 2) {
            $a = $this->formatSingleAdmissionTime(trim($parts[0]));
            $b = $this->formatSingleAdmissionTime(trim($parts[1]));
            if ($a !== '' && $b !== '') {
                return $a . ' – ' . $b;
            }
        }

        return $this->formatSingleAdmissionTime($time);
    }

    private function formatSingleAdmissionTime(string $time): string {
        $time = trim($time);
        if ($time === '') {
            return '';
        }
        $ts = strtotime($time);
        if ($ts === false) {
            return $time;
        }
        $formatted = date('h.i a', $ts);

        return str_replace(['AM', 'PM'], ['am', 'pm'], $formatted);
    }

    /**
     * @param list<array{location: string}> $moduleRows
     */
    private function guessExamCentre(array $exam, array $moduleRows): string {
        foreach ($moduleRows as $mr) {
            $loc = trim((string) ($mr['location'] ?? ''));
            if ($loc !== '') {
                return $this->normalizeCentreLabel($loc);
            }
        }
        $loc = trim((string) ($exam['location'] ?? ''));
        if ($loc !== '' && strcasecmp($loc, 'Various') !== 0) {
            return $this->normalizeCentreLabel($loc);
        }
        return 'SLGTI';
    }

    private function normalizeCentreLabel(string $s): string {
        $s = trim($s);
        if ($s === '') {
            return $s;
        }
        // Common misspelling seen in data exports
        $s = preg_replace('/\bauduiduim\b/i', 'Auditorium', $s);
        return $s ?? '';
    }

    private function formatDateDmy(string $ymd): string {
        $ymd = trim($ymd);
        if ($ymd === '') {
            return '';
        }
        $ts = strtotime($ymd);
        if ($ts === false) {
            return $ymd;
        }
        return date('d.m.Y', $ts);
    }

    private function wrapAdmissionDocument(string $innerHtml): string {
        $css = $this->admissionPdfCss();
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admission Form</title>'
            . '<style>' . $css . '</style></head><body>' . $innerHtml . '</body></html>';
    }

    /**
     * Logo/signature paths for Dompdf (file paths are much faster than base64 on bulk export).
     *
     * @return array{logo: string, signature: string|null}
     */
    /**
     * Layout hints for admission PDF (attendance rows fixed at 10).
     *
     * @return array{attendance_rows: int}
     */
    private function admissionPageLayout(int $moduleCount): array {
        return [
            'attendance_rows' => 10,
        ];
    }

    /**
     * Standard written-examination rules for NVQ semester admission forms.
     *
     * @return list<string>
     */
    private function examAdmissionRules(): array {
        return [
            'Report to the examination centre at least 30 minutes before the scheduled time of each paper.',
            'Bring this admission form and the original National Identity Card (NIC) for verification.',
            'No candidate will be admitted to the examination hall after the paper has commenced.',
            'Mobile phones, smart watches, and unauthorised materials are strictly prohibited in the examination hall.',
            'Candidates must follow all instructions given by the supervisor and invigilator.',
            'Any malpractice, impersonation, or misconduct will result in immediate disqualification.',
        ];
    }

    private function admissionPdfAssets(): array {
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        $logoCandidates = [
            'assets/img/logo.png',
            'assets/img/slgtilogo.png',
            'public/images/slgti-logo.png',
        ];
        $logo = '';
        foreach ($logoCandidates as $rel) {
            $path = ExamPdfHelper::assetPathForPdf($rel);
            if ($path !== null) {
                $logo = $path;
                break;
            }
        }
        if ($logo === '') {
            $logo = $this->admissionLogoDataUri();
        }

        $sig = ExamPdfHelper::assetPathForPdf('public/images/principal-signature.png');
        if ($sig === null) {
            $sig = ExamPdfHelper::assetPathForPdf('assets/img/principal-signature.png');
        }
        if ($sig === null) {
            $sig = $this->principalSignatureDataUri();
        }

        return [
            'logo' => $logo,
            'signature' => $sig,
            'principal_name' => 'R.Mathaan',
        ];
    }

    /**
     * Logo for PDF header. Primary: assets/img/logo.png (black on white for print).
     */
    private function admissionLogoDataUri(): string {
        $paths = [
            BASE_PATH . '/assets/img/logo.png',
            BASE_PATH . '/assets/img/slgtilogo.png',
            BASE_PATH . '/public/images/slgti-logo.png',
            BASE_PATH . '/public/images/slgti-logo.svg',
        ];
        foreach ($paths as $p) {
            if (!is_file($p)) {
                continue;
            }
            $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
            if ($ext === 'png' || $ext === 'jpg' || $ext === 'jpeg') {
                $mime = $ext === 'jpg' ? 'jpeg' : $ext;

                return 'data:image/' . $mime . ';base64,' . base64_encode((string) file_get_contents($p));
            }
            if ($ext === 'svg') {
                return 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($p));
            }
        }
        $fallback = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40"><rect fill="#1e3a5f" width="120" height="40" rx="3"/><text x="60" y="26" fill="#fff" font-family="DejaVu Sans,sans-serif" font-size="13" font-weight="bold" text-anchor="middle">SLGTI</text></svg>';

        return 'data:image/svg+xml,' . rawurlencode($fallback);
    }

    /**
     * Optional digital signature image (place file at public/images/principal-signature.png).
     *
     * @return string|null Data URI or null to show a signature placeholder box.
     */
    private function principalSignatureDataUri(): ?string {
        $f = BASE_PATH . '/public/images/principal-signature.png';
        if (!is_file($f)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($f));
    }

    private function admissionPdfCss(): string {
        return '
@page { size: A4 portrait; margin: 8mm 10mm 8mm 10mm; }
body {
  font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
  color: #1a2332; font-size: 10pt; margin: 0; padding: 0;
}

.admission-student { font-size: 10pt; line-height: 1.3; color: #1a2332; width: 100%; }
.admission-bulk-student { display: block; }
.admission-bulk-student + .admission-bulk-student { page-break-before: always; }

.adm-page { width: 100%; }
.adm-page-1 { page-break-after: always; page-break-inside: auto; }
.adm-page-2 { page-break-after: avoid; page-break-inside: auto; }

.page-shell { width: 100%; border-collapse: collapse; table-layout: fixed; }
.shell-cell { vertical-align: top; padding: 0; border: none; }
.shell-bottom { vertical-align: bottom; padding-top: 2px; }
.adm-page-2 .section-block { margin-bottom: 4px; }
.adm-page-2 .section-bar { padding: 3px 10px; font-size: 8.5pt; }
.adm-page-2 .card-attached { padding-top: 6px; }

/* Header — logo row, then full-width centred text */
.adm-header {
  width: 100%; border-collapse: collapse; margin-bottom: 8px; table-layout: fixed;
  border-bottom: 1px solid #d0dce8;
}
.adm-header td { border: none; padding: 0; background-color: #ffffff; }
.adm-header-logo {
  text-align: left; vertical-align: middle;
  padding: 0 0 4px 0; width: 100%;
}
.adm-header-text {
  text-align: center; vertical-align: middle;
  padding: 2px 0 8px; width: 100%;
}
.adm-logo { height: 50px; width: auto; max-width: 180px; display: block; }
.adm-inst {
  font-size: 12pt; font-weight: 700; color: #1e3a5f; line-height: 1.3;
  text-align: center; margin: 0; padding: 0; white-space: nowrap;
}
.adm-title {
  font-size: 11pt; font-weight: 700; color: #1e3a5f; line-height: 1.3;
  text-transform: uppercase; letter-spacing: 0.3px; text-align: center;
  margin: 2px 0 0; padding: 0; white-space: nowrap;
}
.adm-sub {
  font-size: 9pt; font-weight: 600; color: #3d5168; margin: 2px 0 0; line-height: 1.3;
  text-align: center; padding: 0; white-space: nowrap;
}

/* Sections */
.section-block { margin-bottom: 6px; }
.section-bar {
  background-color: #1e3a5f; color: #ffffff; padding: 4px 12px;
  font-size: 9pt; font-weight: 700; letter-spacing: 0.2px; margin: 0;
}

.card {
  border: 1px solid #b8c9dc; background-color: #fafcff;
  padding: 9px 12px; box-sizing: border-box;
}
.card-attached { border-top: none; margin-top: 0; padding-top: 8px; }
.card-table-wrap { padding: 0; background-color: #ffffff; overflow: hidden; }
.part-a-box { background-color: #f3f7fb; padding: 8px 12px 10px; }

/* Part A — simple 2-column rows (label : | value) */
.part-a-table { border-collapse: collapse; width: 100%; table-layout: fixed; }
.pa-col-label { width: 36%; }
.pa-col-value { width: 64%; }
.part-a-table td {
  border: none; padding: 4px 0; vertical-align: top;
  font-size: 9.5pt; line-height: 1.4;
}
.pa-label {
  font-weight: 600; color: #1e3a5f; text-align: left;
  padding-right: 10px; white-space: nowrap;
}
.pa-value {
  text-align: left; color: #1a2332;
  word-wrap: normal; overflow-wrap: normal;
}
.pa-value-id { font-weight: 700; color: #1e3a5f; }
.pa-value-name { font-weight: 700; color: #1e3a5f; }

/* Authorization */
.auth-card { background-color: #eef4fb; border-color: #9bb8d9; margin-bottom: 6px; padding: 8px 12px; }
.auth-row { border-collapse: collapse; table-layout: fixed; width: 100%; }
.auth-row td { border: none; padding: 0; vertical-align: middle; }
.auth-msg { width: 55%; padding-right: 10px; }
.auth-principal { width: 45%; text-align: right; }
.allow-text { font-size: 9pt; font-style: italic; margin: 0; color: #1e3a5f; line-height: 1.35; font-weight: 600; }

.principal-panel {
  background-color: #ffffff; border: 1px solid #c5d8ef;
  padding: 6px 10px; text-align: left; display: inline-block; min-width: 138px;
}
.principal-sig-img { display: block; height: 28px; width: auto; max-width: 115px; margin-bottom: 1px; }
.sig-space { display: block; height: 28px; width: 115px; border-bottom: 1px solid #9bb8d9; margin-bottom: 1px; }
.principal-name-cell { font-size: 9.5pt; font-weight: 700; color: #1e3a5f; line-height: 1.2; }
.principal-role-cell { font-size: 8.5pt; font-weight: 700; color: #c8102e; line-height: 1.2; }

/* Module list table */
.module-summary-table { border-collapse: collapse; width: 100%; table-layout: fixed; }
.module-summary-table .ms-th {
  background-color: #2a5080; color: #ffffff; font-size: 8.5pt; font-weight: 700;
  padding: 5px 10px; text-align: left; border: 1px solid #2a5080;
}
.module-summary-table .ms-th:first-child { width: 130px; }
.module-summary-table td {
  font-size: 9pt; line-height: 1.35; vertical-align: middle;
  border: 1px solid #d0dce8; padding: 4px 10px;
}
.module-summary-table tr.ms-data td { background-color: #ffffff; }
.module-summary-table tr.ms-alt td { background-color: #f3f7fb; }
.ms-code { font-weight: 700; color: #1e3a5f; white-space: nowrap; width: 130px; }
.ms-name { text-align: left; color: #1a2332; }

/* Part B */
.part-b-box { border-left: 3px solid #c8102e; }
.part-b-text { font-size: 9pt; margin: 0 0 12px; color: #1a2332; line-height: 1.35; }
.part-b-sign { border-collapse: collapse; table-layout: fixed; width: 100%; }
.part-b-sign td { vertical-align: bottom; border: none; padding: 0; }
.pb-hod { width: 40%; padding-right: 10px; }
.pb-date { width: 22%; text-align: center; }
.pb-stamp { width: 38%; text-align: right; }
.pb-line { border-bottom: 1px solid #1e3a5f; height: 22px; margin-bottom: 3px; }
.pb-stamp-box { border: 1px dashed #7a94b0; height: 42px; width: 86px; margin: 0 0 3px auto; background-color: #ffffff; }
.pb-label { font-size: 8pt; font-weight: 700; color: #1e3a5f; text-align: left; }
.pb-date .pb-label { text-align: center; }
.pb-stamp-label { font-size: 8pt; font-weight: 700; color: #3d5168; text-align: right; }

/* Page 2 */
.rules-card { background-color: #fffbf0; border-color: #e8c840; border-left: 3px solid #d4a017; padding: 6px 10px 5px; }
.exam-rules-list { margin: 0; padding: 0 0 0 16px; }
.exam-rules-list li { font-size: 8pt; line-height: 1.3; margin-bottom: 2px; color: #3d3020; }

/* Simple tables (schedule, attendance) */
.simple-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.simple-table td {
  border: 1px solid #d0dce8; padding: 3px 7px; font-size: 8.5pt;
  vertical-align: middle; line-height: 1.25; color: #1a2332; background-color: #ffffff;
}
.simple-table .simple-head td {
  background-color: #eef4fb; font-weight: 700; color: #1e3a5f;
  border-color: #b8c9dc; font-size: 8.5pt; padding: 4px 7px;
}
.st-code { font-weight: 700; color: #1e3a5f; white-space: nowrap; }
.st-name { text-align: left; }
.st-center { text-align: center; }

.attendance-table tbody tr.att-row td { height: 18px; padding: 2px 5px; }

.supervisor-card { background-color: #fafcff; border-color: #b8c9dc; margin-bottom: 2px; padding: 5px 10px 4px; }
.supervisor-footer { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9pt; }
.supervisor-footer td { border: none; padding: 4px 0 0; vertical-align: bottom; }
.sup-label { width: 168px; white-space: nowrap; text-align: left; font-weight: 600; color: #1e3a5f; }
.sup-line { border-bottom: 1px solid #1e3a5f; height: 15px; }
.sup-line-mid { border-bottom: 1px solid #1e3a5f; height: 15px; }
.sup-date-label { width: 42px; text-align: right; padding-left: 12px; white-space: nowrap; font-weight: 600; color: #1e3a5f; }
.sup-date-line { width: 115px; border-bottom: 1px solid #1e3a5f; height: 15px; }

.center { text-align: center; }
.muted { color: #6b7c90; font-style: italic; }
';
    }
}
