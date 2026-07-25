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

        $inner = ExamPdfHelper::renderTemplate('admission_card.php', [
            'exam' => $exam,
            'student' => $st,
            'moduleRows' => $moduleRows,
            'meta' => $meta,
            'logo_src' => $this->admissionLogoDataUri(),
            'principal_sig_src' => $this->principalSignatureDataUri(),
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

        $html = $this->buildAdmissionCardsDocumentHtml($examModel, $exam, $studentIds);
        if ($html === null) {
            $_SESSION['error'] = 'Could not load student records.';
            $this->redirect('exams');
            return;
        }
        ExamPdfHelper::streamHtml($html, 'exam_' . $examId . '_admission_all_students.pdf');
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

        $html = $this->buildAdmissionCardsDocumentHtml($examModel, $exam, $studentIds);
        if ($html === null) {
            $_SESSION['error'] = 'Could not load student records.';
            $this->redirect('exams/admission-select?exam_id=' . $examId);
            return;
        }
        $suffix = count($studentIds) === 1 ? preg_replace('/[^a-zA-Z0-9_-]+/', '_', $studentIds[0]) : 'selected';
        ExamPdfHelper::streamHtml($html, 'exam_' . $examId . '_admission_' . $suffix . '.pdf');
    }

    /**
     * @param list<string> $studentIds Must be registered on this exam, in desired order.
     */
    private function buildAdmissionCardsDocumentHtml($examModel, array $exam, array $studentIds): ?string {
        if (empty($studentIds)) {
            return null;
        }
        $examId = (int) ($exam['id'] ?? 0);
        $studentModel = $this->model('StudentModel');
        $moduleModel = $this->model('ModuleModel');
        $courseModel = $this->model('CourseModel');
        $departmentModel = $this->model('DepartmentModel');
        $moduleRows = $this->buildAdmissionModuleRows($examModel, $moduleModel, $exam);
        $meta = $this->buildAdmissionMeta($exam, $courseModel, $departmentModel, $moduleRows);

        $logoSrc = $this->admissionLogoDataUri();
        $principalSigSrc = $this->principalSignatureDataUri();
        $innerParts = [];
        foreach ($studentIds as $sid) {
            if (!$examModel->isStudentOnExam($examId, $sid)) {
                continue;
            }
            $st = $studentModel->find($sid);
            if (!$st) {
                continue;
            }
            $innerParts[] = ExamPdfHelper::renderTemplate('admission_card.php', [
                'exam' => $exam,
                'student' => $st,
                'moduleRows' => $moduleRows,
                'meta' => $meta,
                'logo_src' => $logoSrc,
                'principal_sig_src' => $principalSigSrc,
            ]);
        }
        if (empty($innerParts)) {
            return null;
        }

        $last = count($innerParts) - 1;
        $merged = [];
        foreach ($innerParts as $i => $part) {
            $style = ($i < $last) ? ' style="page-break-after: always;"' : '';
            $merged[] = '<div class="admission-bulk-student"' . $style . '>' . $part . '</div>';
        }

        return $this->wrapAdmissionDocument(implode('', $merged));
    }

    /**
     * @return list<array{code: string, name: string, date_dmy: string, time: string, location: string}>
     */
    private function buildAdmissionModuleRows($examModel, $moduleModel, array $exam): array {
        $courseId = (string) ($exam['course_id'] ?? '');
        $out = [];
        foreach ($examModel->decodeExamModulesList($exam) as $row) {
            $mid = trim((string) ($row['module_id'] ?? ''));
            if ($mid === '') {
                continue;
            }
            $mod = $moduleModel->getByCourseAndModule($courseId, $mid);
            $name = is_array($mod) ? (string) ($mod['module_name'] ?? $mid) : $mid;
            $out[] = [
                'code' => $mid,
                'name' => $name,
                'date_dmy' => $this->formatDateDmy((string) ($row['exam_date'] ?? '')),
                'time' => (string) ($row['exam_time'] ?? ''),
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
        $sem = isset($exam['semester']) && $exam['semester'] !== null && $exam['semester'] !== ''
            ? (string) (int) $exam['semester']
            : '';
        $monthYear = '';
        if (!empty($exam['exam_date'])) {
            $ts = strtotime((string) $exam['exam_date']);
            if ($ts !== false) {
                $monthYear = date('F Y', $ts);
            }
        }
        $nvqSem = 'Level ' . ($nvq !== '' ? $nvq : '—');
        if ($sem !== '') {
            $nvqSem .= ' Semester ' . $sem;
        }

        $subtitle = 'Common Examination';
        if ($nvq !== '') {
            $subtitle .= ' – NVQ Level ' . $nvq;
        }
        if ($sem !== '') {
            $subtitle .= ' – Semester ' . $sem;
        }
        if ($monthYear !== '') {
            $subtitle .= ' – ' . $monthYear;
        }

        $centre = $this->guessExamCentre($exam, $moduleRows);

        return [
            'subtitle' => $subtitle,
            'subject_line' => $subjectLine,
            'exam_centre' => $centre,
            'nvq_semester' => $nvqSem,
        ];
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
@page { margin: 12mm 12mm 12mm 12mm; }
body { font-family: Helvetica, Arial, DejaVu Sans, sans-serif; color: #0f172a; }
.admission-student { font-size: 9.5px; }
.admission-bulk-student { display: block; }
.admission-onepage { page-break-inside: avoid; }
.head-row { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.head-row td { vertical-align: top; border: none; padding: 0; }
.head-left { width: 78%; text-align: center; padding-right: 10px; }
.head-right { width: 22%; text-align: right; vertical-align: middle; }
.logo-img { height: 46px; width: auto; max-width: 190px; display: inline-block; }
.inst { font-size: 13px; font-weight: 700; color: #0b1220; letter-spacing: 0.01em; }
.title { font-size: 12px; font-weight: 700; margin-top: 4px; color: #0b1220; }
.sub { font-size: 9.5px; font-weight: 600; margin-top: 3px; color: #334155; }
.divider { height: 1px; background: #cbd5e1; margin: 6px 0 10px; }

.section-title { font-size: 9px; font-weight: 700; margin: 0 0 6px; color: #0f2744; letter-spacing: 0.04em; text-transform: uppercase; }
.section-box { border: 1px solid #cbd5e1; background: #ffffff; padding: 8px 10px; margin-bottom: 9px; border-radius: 4px; }
.info { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.info th, .info td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; vertical-align: top; }
.info th { width: 32%; background: #f8fafc; font-weight: 600; font-size: 9px; color: #0f172a; }
.info td { font-size: 9px; background: #fff; color: #0f172a; }

.allow-block { margin: 0; padding: 10px 10px 10px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 4px; }
.allow { font-size: 9.2px; text-align: center; margin: 0 0 8px; line-height: 1.35; color: #0f172a; font-style: italic; }
.principal-auth { text-align: left; max-width: 260px; }
.principal-sig { min-height: 34px; margin-bottom: 0; }
.principal-sig img { display: block; max-height: 52px; max-width: 220px; height: auto; width: auto; }
.sig-placeholder { display: inline-block; height: 34px; width: 180px; border: 1px dashed #94a3b8; background: #fff; vertical-align: top; border-radius: 3px; }
.principal-rule { border-top: 1px solid #94a3b8; width: 200px; margin: 4px 0 4px 0; height: 1px; font-size: 0; line-height: 0; overflow: hidden; }
.principal-label { font-size: 8.8px; font-weight: 700; color: #0b1220; text-align: left; }

.part-h2 { font-size: 9px; font-weight: 700; margin: 0 0 4px; color: #0b1220; }
.attest-text { font-size: 9px; margin: 0 0 6px; color: #334155; }
.grid { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.grid-p2 { width: 100%; border-collapse: collapse; margin-bottom: 9px; table-layout: fixed; }
.grid-p2 th, .grid-p2 td { border: 1px solid #e2e8f0; padding: 6px 6px; font-size: 8.8px; vertical-align: middle; }
.grid-p2 th { background: #f1f5f9; font-weight: 700; color: #0b1220; text-align: center; font-size: 8.6px; }
.tbl-schedule .td-left { text-align: left; }
.tbl-schedule .td-center { text-align: center; }
.tbl-attendance th { text-align: center; }
.tbl-attendance td { text-align: center; height: 18px; }
.tbl-attendance td:nth-child(1) { text-align: left; }
.muted { color: #666; font-style: italic; text-align: left; }
.part-b { border: 1px solid #cbd5e1; padding: 10px 10px; margin-top: 0; background: #fff; border-radius: 4px; }
.part-b-p1 { margin-top: 0; }
.part-b p { margin: 3px 0; font-size: 9px; }
.attest { width: 100%; border-collapse: collapse; margin-top: 8px; }
.attest td { border: none; vertical-align: bottom; }
.sig-cell { width: 55%; }
.stamp-cell { width: 45%; text-align: right; }
.sig-line { margin-top: 18px; border-top: 1px solid #94a3b8; padding-top: 4px; font-size: 9px; color: #0b1220; font-weight: 600; }
.sup-p2 { margin-top: 10px; font-size: 9px; color: #0b1220; }
.sup-compact { margin-top: 8px; }
.sup-lines { width: 100%; }
.sup-line { margin: 0 0 8px; }
.sup-line-sig { margin-top: 2px; }
.sup-label { font-weight: 600; color: #0b1220; }
.sup-fill { display: inline-block; border-bottom: 1px solid #94a3b8; vertical-align: bottom; }
.sup-fill-name { width: 74%; height: 12px; margin-left: 6px; }
/* Give enough vertical room for a handwritten signature */
.sup-fill-sig { width: 54%; height: 22px; margin-left: 6px; }
.sup-date-wrap { float: right; white-space: nowrap; }
.sup-fill-date { width: 120px; height: 12px; margin-left: 6px; }
.split-row { border-collapse: collapse; margin: 0 0 7px; }
.split-row td { border: none; padding: 0; vertical-align: top; }
.split-left { width: 50%; padding-right: 8px; }
.split-right { width: 50%; padding-left: 8px; }
';
    }
}
