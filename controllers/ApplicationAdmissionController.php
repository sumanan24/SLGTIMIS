<?php
/**
 * Entrance exam & interview schedules for online student applications.
 */

require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';

class ApplicationAdmissionController extends Controller {

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

    private function scheduleModel(): ApplicationAdmissionScheduleModel {
        return $this->model('ApplicationAdmissionScheduleModel');
    }

    private function requireView(int $uid): UserModel {
        $userModel = $this->userModel();
        if (!$userModel->canViewApplicationAdmissionSchedules($uid)) {
            $_SESSION['error'] = 'You do not have permission to view admission schedules.';
            $this->redirect('dashboard');
        }
        return $userModel;
    }

    private function requireManage(int $uid): UserModel {
        $userModel = $this->requireView($uid);
        if (!$userModel->canManageApplicationAdmissionSchedules($uid)) {
            $_SESSION['error'] = 'You do not have permission to manage admission schedules.';
            $this->redirect('application-admission');
        }
        return $userModel;
    }

    private function requireSelectionUpdate(int $uid): UserModel {
        $userModel = $this->requireView($uid);
        if (!$userModel->canUpdateApplicationAdmissionSelection($uid)) {
            $_SESSION['error'] = 'Only Student Affairs (SAO) and Administrator (ADM) can update the interview selection list.';
            $this->redirect('application-admission');
        }
        return $userModel;
    }

    private function admissionLogoDataUri(): string {
        $paths = [
            BASE_PATH . '/assets/img/logo.png',
            BASE_PATH . '/assets/img/slgtilogo.png',
            BASE_PATH . '/public/images/slgti-logo.png',
        ];
        foreach ($paths as $p) {
            if (!is_file($p)) {
                continue;
            }
            $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                $mime = $ext === 'jpg' ? 'jpeg' : $ext;
                return 'data:image/' . $mime . ';base64,' . base64_encode((string) file_get_contents($p));
            }
        }
        return '';
    }

    /**
     * Branch Principal digital signature for interview letters / slips.
     * Prefer processed transparent PNG; fall back to ID-card signature asset.
     */
    private function principalSignatureDataUri(): ?string {
        $paths = [
            BASE_PATH . '/public/images/principal-signature.png',
            BASE_PATH . '/assets/img/principal-signature.png',
            BASE_PATH . '/assets/img/sign.png',
        ];
        foreach ($paths as $p) {
            if (!is_file($p)) {
                continue;
            }
            $raw = (string) file_get_contents($p);
            if ($raw === '') {
                continue;
            }
            $processed = $this->signaturePngWithTransparentBackground($raw);
            if ($processed !== null) {
                return 'data:image/png;base64,' . base64_encode($processed);
            }
            $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
            $mime = $ext === 'jpg' ? 'jpeg' : ($ext === 'jpeg' ? 'jpeg' : 'png');

            return 'data:image/' . $mime . ';base64,' . base64_encode($raw);
        }

        return null;
    }

    /**
     * Make near-black pixels transparent so signature sits cleanly on white letterhead.
     */
    private function signaturePngWithTransparentBackground(string $raw): ?string {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
            return null;
        }
        $im = @imagecreatefromstring($raw);
        if (!$im) {
            return null;
        }
        imagesavealpha($im, true);
        imagealphablending($im, false);
        $w = imagesx($im);
        $h = imagesy($im);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($im, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if ($r < 48 && $g < 48 && $b < 48) {
                    imagesetpixel($im, $x, $y, $transparent);
                }
            }
        }
        ob_start();
        imagepng($im);
        $out = ob_get_clean();
        imagedestroy($im);

        return is_string($out) && $out !== '' ? $out : null;
    }

    private function formatTime(?string $t): string {
        if ($t === null || trim($t) === '') {
            return '—';
        }
        $ts = strtotime($t);
        return $ts ? date('g:i A', $ts) : $t;
    }

    private function formatDate(?string $d): string {
        if ($d === null || trim($d) === '') {
            return '—';
        }
        $ts = strtotime($d);
        return $ts ? date('d M Y', $ts) : $d;
    }

    public function index() {
        $tab = $this->get('tab', ApplicationAdmissionScheduleModel::TYPE_ENTRANCE);
        if ($tab === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            // Keep old bookmarks working
            $this->redirect('application-admission/interviews');
            return;
        }
        return $this->renderScheduleIndex(ApplicationAdmissionScheduleModel::TYPE_ENTRANCE);
    }

    /** Interview schedules list (separate navigation from entrance exams). */
    public function interviews() {
        return $this->renderScheduleIndex(ApplicationAdmissionScheduleModel::TYPE_INTERVIEW);
    }

    public function report() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $data = $this->selectionReportPageData();
        $viewData = [
            'page' => 'application-admission-report',
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'course_id' => $data['course_id'],
            'departments' => $data['departments'],
            'courses' => $data['courses'],
            'groups' => $data['groups'],
            'fail_groups' => $data['fail_groups'],
            'total_students' => $data['total_students'],
            'total_fail' => $data['total_fail'],
            'choice_counts' => $data['choice_counts'],
            'fail_counts' => $data['fail_counts'],
            'min_marks' => $data['min_marks'],
            'overview' => $data['overview'],
            'has_course' => !empty($data['has_filter'] ?? $data['has_course']),
            'has_filter' => !empty($data['has_filter'] ?? $data['has_course']),
            'filter_query' => $data['filter_query'],
            'filter_summary' => $data['filter_summary'],
        ];
        if ($this->wantsReportPartial()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store');
            echo $this->renderSelectionReportResults($viewData);
            exit;
        }

        return $this->view('application_admission/report', $viewData);
    }

    public function nicResult() {
        $this->requireView($this->requireLogin());
        $nic = trim((string) ($this->post('nic', $this->get('nic', ''))));
        $level = trim((string) ($this->post('level', $this->get('level', ''))));
        if (!in_array($level, ['', '04', '05'], true)) {
            $level = '';
        }
        $results = [];
        $searched = $nic !== '';
        if ($searched) {
            require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
            $results = (new ApplicationAdmissionCutoffModel())->lookupSelectionByNic($nic, $level);
        }

        return $this->view('application_admission/nic_result', [
            'page' => 'application-admission-nic-result',
            'nic' => $nic,
            'level' => $level,
            'results' => $results,
            'searched' => $searched,
        ]);
    }

    private function wantsReportPartial(): bool {
        $xrw = strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')));
        if ($xrw === 'xmlhttprequest') {
            return true;
        }

        return (string) $this->get('partial', '') === '1';
    }

    /**
     * @param array<string,mixed> $data
     */
    private function renderSelectionReportResults(array $data): string {
        extract($data, EXTR_SKIP);
        ob_start();
        include BASE_PATH . '/views/application_admission/_report_results.php';

        return (string) ob_get_clean();
    }

    public function pdfReport() {
        $this->requireView($this->requireLogin());
        $data = $this->selectionReportPageData();
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('selection_report.php', [
            'level' => $data['level'],
            'groups' => $data['groups'],
            'fail_groups' => $data['fail_groups'],
            'total_students' => $data['total_students'],
            'total_fail' => $data['total_fail'],
            'min_marks' => $data['min_marks'],
            'overview' => $data['overview'],
            'filter_summary' => $data['filter_summary'],
            'logo_src' => $this->admissionLogoDataUri(),
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument(
            $inner,
            ApplicationAdmissionPdfHelper::selectionReportStyles()
        );
        ApplicationAdmissionPdfHelper::streamHtml(
            $html,
            'admission-selection-report-nvq-' . $data['level'] . '.pdf',
            'A4',
            'landscape',
            true
        );
    }

    public function exportReport() {
        $this->requireView($this->requireLogin());
        $data = $this->selectionReportPageData();
        $rows = $this->flattenSelectionOverviewRows($data['overview'] ?? []);
        $level = (string) ($data['level'] ?? '04');
        $filterSummary = implode(' · ', array_filter([
            'NVQ Level ' . $level,
            (string) ($data['filter_summary'] ?? ''),
            count($rows) . ' course row(s)',
        ]));
        $baseName = 'selection_report_nvq' . $level . '_' . date('Y-m-d_H-i');
        $cols = [
            'no', 'nvq_level', 'department', 'course_name', 'medium',
            'applied_students', 'exam_students', 'met_cutoff', 'below_cutoff',
            'selected_students', 'selected_1st', 'selected_2nd', 'selected_3rd', 'selected_other', 'cutoff',
            'failed_students', 'absent_students',
        ];
        $colLabels = [
            'no' => 'No',
            'nvq_level' => 'NVQ',
            'department' => 'Department',
            'course_name' => 'Course',
            'medium' => 'Medium',
            'applied_students' => 'Applied students',
            'exam_students' => 'Exam students',
            'met_cutoff' => 'Met cutoff',
            'below_cutoff' => 'Below cutoff (not failed)',
            'selected_students' => 'Selected students',
            'selected_1st' => '1st option',
            'selected_2nd' => '2nd option',
            'selected_3rd' => '3rd option',
            'selected_other' => 'Selected other course',
            'cutoff' => 'Cutoff (Northern / Other)',
            'failed_students' => 'Failed students (below 30)',
            'absent_students' => 'Absent students',
        ];

        $xlsFallback = static function () use ($rows, $cols, $colLabels, $baseName, $filterSummary): void {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xls"');
            header('Cache-Control: private, max-age=0');
            echo "\xEF\xBB\xBF";
            $esc = static function (string $s): string {
                return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            };
            echo '<table border="1" cellspacing="0" cellpadding="4">' . "\n";
            echo '<tr><td colspan="' . count($cols) . '"><b>SLGTI — Admission selection report</b></td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">' . $esc($filterSummary) . '</td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">Exported: ' . $esc(date('Y-m-d H:i')) . '</td></tr>' . "\n";
            echo '<thead><tr>';
            foreach ($cols as $h) {
                echo '<th style="background:#1F4E79;color:#fff;font-weight:bold;padding:6px;text-align:center;">'
                    . $esc((string) ($colLabels[$h] ?? $h)) . '</th>';
            }
            echo "</tr></thead>\n<tbody>\n";
            $centerCols = [
                'no' => true, 'applied_students' => true, 'exam_students' => true,
                'met_cutoff' => true, 'below_cutoff' => true, 'selected_students' => true,
                'selected_1st' => true, 'selected_2nd' => true, 'selected_3rd' => true, 'selected_other' => true,
                'failed_students' => true, 'absent_students' => true,
            ];
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($cols as $colName) {
                    $v = isset($row[$colName]) ? (string) $row[$colName] : '';
                    $align = isset($centerCols[$colName]) ? 'center' : 'left';
                    echo '<td style="mso-number-format:\'\@\';text-align:' . $align . ';">' . $esc($v) . '</td>';
                }
                echo "</tr>\n";
            }
            if ($rows === []) {
                echo '<tr><td colspan="' . count($cols) . '">No course counts for the selected filters.</td></tr>' . "\n";
            }
            echo "</tbody></table>";
            exit;
        };

        $needs = [
            extension_loaded('zip') && class_exists('ZipArchive', false),
            extension_loaded('xmlwriter'),
            extension_loaded('dom'),
            extension_loaded('simplexml'),
            extension_loaded('xml'),
            extension_loaded('mbstring') && function_exists('mb_strlen'),
            extension_loaded('iconv') && function_exists('iconv'),
        ];
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!is_readable($autoload) || in_array(false, $needs, true)) {
            $xlsFallback();
        }

        try {
            require_once $autoload;
            require_once BASE_PATH . '/helpers/StudentApplicationExportXlsx.php';
            if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                $xlsFallback();
            }
            $spreadsheet = StudentApplicationExportXlsx::buildSpreadsheet(
                $rows,
                $cols,
                $colLabels,
                'Selection report',
                $filterSummary
            );
            $spreadsheet->getActiveSheet()->setCellValue('A1', 'SLGTI — Admission selection report');
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $tmpPath = tempnam(sys_get_temp_dir(), 'slgti_selreport_xlsx_');
            if ($tmpPath === false) {
                throw new RuntimeException('Could not create temp file for XLSX export.');
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tmpPath);
            $size = filesize($tmpPath);
            if ($size === false || $size < 1) {
                @unlink($tmpPath);
                throw new RuntimeException('XLSX temp file not readable after write.');
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xlsx"');
            header('Cache-Control: private, max-age=0');
            header('Content-Length: ' . (string) $size);
            readfile($tmpPath);
            @unlink($tmpPath);
            $spreadsheet->disconnectWorksheets();
            exit;
        } catch (Throwable $e) {
            if (isset($tmpPath) && is_string($tmpPath) && $tmpPath !== '') {
                @unlink($tmpPath);
            }
            error_log('ApplicationAdmission exportReport: ' . $e->getMessage());
            $xlsFallback();
        }
    }

    public function cutoffs() {
        $uid = $this->requireLogin();
        $userModel = $this->requireView($uid);
        $data = $this->cutoffPageData();

        return $this->view('application_admission/cutoffs', [
            'page' => 'application-admission-cutoff',
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'course_id' => $data['course_id'],
            'departments' => $data['departments'],
            'mediums' => ApplicationAdmissionCutoffModel::mediumsForLevel($data['level']),
            'courses' => $data['courses'],
            'cutoffMap' => $data['cutoff_map'],
            'groups' => $data['groups'],
            'qualify_total_all' => $data['qualify_total_all'],
            'filter_query' => $data['filter_query'],
            'canManage' => $userModel->canManageApplicationAdmissionSchedules($uid),
        ]);
    }

    public function cutoffsSave() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $level = (string) $this->post('level', '04');
        if (!in_array($level, ['04', '05'], true)) {
            $level = '04';
        }
        $posted = $this->post('cutoffs', []);
        if (!is_array($posted)) {
            $posted = [];
        }
        require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
        $result = (new ApplicationAdmissionCutoffModel())->saveLevelCutoffs($level, $posted, $uid);
        if ($result['invalid'] > 0) {
            $_SESSION['error'] = 'Cutoffs saved, but ' . $result['invalid'] . ' value(s) were skipped. Use a number such as 45 or 52.5.';
        } else {
            $_SESSION['success'] = 'Cutoffs saved. Automobile Tamil is separate; Sinhala and English share one cutoff. Other courses use Northern / other-province only.';
        }
        $qs = ['level' => $level];
        $departmentId = trim((string) $this->post('department_id', ''));
        $courseId = trim((string) $this->post('course_id', ''));
        if ($departmentId !== '') {
            $qs['department_id'] = $departmentId;
        }
        if ($courseId !== '') {
            $qs['course_id'] = $courseId;
        }
        $this->redirect('application-admission/cutoffs?' . http_build_query($qs));
    }

    public function pdfCutoffs() {
        $this->requireView($this->requireLogin());
        $data = $this->cutoffPageData();
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('cutoff_list.php', [
            'level' => $data['level'],
            'mediums' => ApplicationAdmissionCutoffModel::mediumsForLevel($data['level']),
            'groups' => $data['groups'],
            'filter_summary' => $data['filter_summary'],
            'logo_src' => $this->admissionLogoDataUri(),
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument($inner);
        ApplicationAdmissionPdfHelper::streamHtml(
            $html,
            'cutoff-list-nvq-' . $data['level'] . '.pdf',
            'A4',
            'landscape'
        );
    }

    public function exportCutoffs() {
        $this->requireView($this->requireLogin());
        $data = $this->cutoffPageData();
        $rows = $this->flattenCutoffStudents($data['groups']);
        $level = $data['level'];
        $filterSummary = implode(' · ', array_filter([
            'NVQ Level ' . $level,
            $data['filter_summary'],
            count($rows) . ' student(s)',
        ]));
        $baseName = 'cutoff_students_nvq' . $level . '_' . date('Y-m-d_H-i');

        $cols = [
            'no', 'department', 'course_name', 'language_group', 'roll_number',
            'student_full_name', 'student_nic', 'course_priority_2',
            'student_province', 'region', 'medium',
            'exam_marks', 'cutoff_applied',
        ];
        $colLabels = [
            'no' => 'No',
            'department' => 'Department',
            'course_name' => 'Course',
            'language_group' => 'Language / group',
            'roll_number' => 'Roll / Index',
            'student_full_name' => 'Name',
            'student_nic' => 'NIC',
            'course_priority_2' => '2nd choice course',
            'student_province' => 'Province',
            'region' => 'Region',
            'medium' => 'Medium',
            'exam_marks' => 'Marks',
            'cutoff_applied' => 'Min cutoff',
        ];

        $xlsFallback = static function () use ($rows, $cols, $colLabels, $baseName, $filterSummary): void {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xls"');
            header('Cache-Control: private, max-age=0');
            echo "\xEF\xBB\xBF";
            $esc = static function (string $s): string {
                return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            };
            echo '<table border="1" cellspacing="0" cellpadding="4">' . "\n";
            echo '<tr><td colspan="' . count($cols) . '"><b>SLGTI — Cutoff qualifying students</b></td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">' . $esc($filterSummary) . '</td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">Exported: ' . $esc(date('Y-m-d H:i')) . '</td></tr>' . "\n";
            echo '<thead><tr>';
            foreach ($cols as $h) {
                echo '<th style="background:#1F4E79;color:#fff;font-weight:bold;padding:6px;text-align:center;">'
                    . $esc((string) ($colLabels[$h] ?? $h)) . '</th>';
            }
            echo "</tr></thead>\n<tbody>\n";
            $centerCols = ['no' => true, 'region' => true, 'medium' => true, 'exam_marks' => true, 'cutoff_applied' => true];
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($cols as $colName) {
                    $v = isset($row[$colName]) ? (string) $row[$colName] : '';
                    $align = isset($centerCols[$colName]) ? 'center' : 'left';
                    echo '<td style="mso-number-format:\'\@\';text-align:' . $align . ';">' . $esc($v) . '</td>';
                }
                echo "</tr>\n";
            }
            if ($rows === []) {
                echo '<tr><td colspan="' . count($cols) . '">No qualifying students for the selected filters.</td></tr>' . "\n";
            }
            echo "</tbody></table>";
            exit;
        };

        $needs = [
            extension_loaded('zip') && class_exists('ZipArchive', false),
            extension_loaded('xmlwriter'),
            extension_loaded('dom'),
            extension_loaded('simplexml'),
            extension_loaded('xml'),
            extension_loaded('mbstring') && function_exists('mb_strlen'),
            extension_loaded('iconv') && function_exists('iconv'),
        ];
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!is_readable($autoload) || in_array(false, $needs, true)) {
            $xlsFallback();
        }

        try {
            require_once $autoload;
            require_once BASE_PATH . '/helpers/StudentApplicationExportXlsx.php';
            if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                $xlsFallback();
            }

            $spreadsheet = StudentApplicationExportXlsx::buildSpreadsheet(
                $rows,
                $cols,
                $colLabels,
                'Cutoff list',
                $filterSummary
            );
            $spreadsheet->getActiveSheet()->setCellValue('A1', 'SLGTI — Cutoff qualifying students');

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $tmpPath = tempnam(sys_get_temp_dir(), 'slgti_cutoff_xlsx_');
            if ($tmpPath === false) {
                throw new RuntimeException('Could not create temp file for XLSX export.');
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tmpPath);
            $size = filesize($tmpPath);
            if ($size === false || $size < 1) {
                @unlink($tmpPath);
                throw new RuntimeException('XLSX temp file not readable after write.');
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xlsx"');
            header('Cache-Control: private, max-age=0');
            header('Content-Length: ' . (string) $size);
            readfile($tmpPath);
            @unlink($tmpPath);
            $spreadsheet->disconnectWorksheets();
            exit;
        } catch (Throwable $e) {
            if (isset($tmpPath) && is_string($tmpPath) && $tmpPath !== '') {
                @unlink($tmpPath);
            }
            error_log('ApplicationAdmission exportCutoffs: ' . $e->getMessage());
            $xlsFallback();
        }
    }

    public function secondOption() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $data = $this->secondOptionPageData();

        return $this->view('application_admission/second_option', [
            'page' => 'application-admission-second-option',
            'level' => $data['level'],
            'department_id' => $data['department_id'],
            'course_id' => $data['course_id'],
            'departments' => $data['departments'],
            'courses' => $data['courses'],
            'students' => $data['students'],
            'groups' => $data['groups'],
            'min_marks' => $data['min_marks'],
            'filter_query' => $data['filter_query'],
        ]);
    }

    public function pdfSecondOption() {
        $this->requireView($this->requireLogin());
        $data = $this->secondOptionPageData();
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('second_option_list.php', [
            'level' => $data['level'],
            'min_marks' => $data['min_marks'],
            'students' => $data['students'],
            'groups' => $data['groups'],
            'filter_summary' => $data['filter_summary'],
            'logo_src' => $this->admissionLogoDataUri(),
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument($inner);
        ApplicationAdmissionPdfHelper::streamHtml(
            $html,
            'second-option-nvq-' . $data['level'] . '.pdf',
            'A4',
            'landscape'
        );
    }

    public function exportSecondOption() {
        $this->requireView($this->requireLogin());
        $data = $this->secondOptionPageData();
        $rows = $this->flattenSecondOptionStudents($data['groups']);
        $level = $data['level'];
        $filterSummary = implode(' · ', array_filter([
            'NVQ Level ' . $level,
            'Marks ' . $data['min_marks'] . ' to below 1st-choice cutoff',
            $data['filter_summary'],
            count($rows) . ' student(s)',
        ]));
        $baseName = 'second_option_nvq' . $level . '_' . date('Y-m-d_H-i');

        $cols = [
            'no', 'department', 'first_course_name', 'roll_number', 'student_full_name', 'student_nic',
            'exam_marks', 'first_cutoff', 'second_course_name', 'third_course_name', 'consider_choice',
            'student_province', 'region',
        ];
        $colLabels = [
            'no' => 'No',
            'department' => 'Department',
            'first_course_name' => '1st choice course',
            'roll_number' => 'Roll / Index',
            'student_full_name' => 'Name',
            'student_nic' => 'NIC',
            'exam_marks' => 'Marks',
            'first_cutoff' => 'Cutoff',
            'second_course_name' => '2nd option',
            'third_course_name' => '3rd option',
            'consider_choice' => 'Consider',
            'student_province' => 'Province',
            'region' => 'Region',
        ];

        $xlsFallback = static function () use ($rows, $cols, $colLabels, $baseName, $filterSummary): void {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xls"');
            header('Cache-Control: private, max-age=0');
            echo "\xEF\xBB\xBF";
            $esc = static function (string $s): string {
                return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            };
            echo '<table border="1" cellspacing="0" cellpadding="4">' . "\n";
            echo '<tr><td colspan="' . count($cols) . '"><b>SLGTI — 2nd option students (below 1st-choice cutoff)</b></td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">' . $esc($filterSummary) . '</td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">Exported: ' . $esc(date('Y-m-d H:i')) . '</td></tr>' . "\n";
            echo '<thead><tr>';
            foreach ($cols as $h) {
                echo '<th style="background:#1F4E79;color:#fff;font-weight:bold;padding:6px;text-align:center;">'
                    . $esc((string) ($colLabels[$h] ?? $h)) . '</th>';
            }
            echo "</tr></thead>\n<tbody>\n";
            $centerCols = [
                'no' => true, 'exam_marks' => true, 'first_cutoff' => true, 'consider_choice' => true, 'region' => true,
            ];
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($cols as $colName) {
                    $v = isset($row[$colName]) ? (string) $row[$colName] : '';
                    $align = isset($centerCols[$colName]) ? 'center' : 'left';
                    echo '<td style="mso-number-format:\'\@\';text-align:' . $align . ';">' . $esc($v) . '</td>';
                }
                echo "</tr>\n";
            }
            if ($rows === []) {
                echo '<tr><td colspan="' . count($cols) . '">No students in this marks band for the selected filters.</td></tr>' . "\n";
            }
            echo "</tbody></table>";
            exit;
        };

        $needs = [
            extension_loaded('zip') && class_exists('ZipArchive', false),
            extension_loaded('xmlwriter'),
            extension_loaded('dom'),
            extension_loaded('simplexml'),
            extension_loaded('xml'),
            extension_loaded('mbstring') && function_exists('mb_strlen'),
            extension_loaded('iconv') && function_exists('iconv'),
        ];
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!is_readable($autoload) || in_array(false, $needs, true)) {
            $xlsFallback();
        }

        try {
            require_once $autoload;
            require_once BASE_PATH . '/helpers/StudentApplicationExportXlsx.php';
            if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                $xlsFallback();
            }

            $spreadsheet = StudentApplicationExportXlsx::buildSpreadsheet(
                $rows,
                $cols,
                $colLabels,
                '2nd option',
                $filterSummary
            );
            $spreadsheet->getActiveSheet()->setCellValue('A1', 'SLGTI — 2nd option students (below 1st-choice cutoff)');

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $tmpPath = tempnam(sys_get_temp_dir(), 'slgti_secondopt_xlsx_');
            if ($tmpPath === false) {
                throw new RuntimeException('Could not create temp file for XLSX export.');
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tmpPath);
            $size = filesize($tmpPath);
            if ($size === false || $size < 1) {
                @unlink($tmpPath);
                throw new RuntimeException('XLSX temp file not readable after write.');
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xlsx"');
            header('Cache-Control: private, max-age=0');
            header('Content-Length: ' . (string) $size);
            readfile($tmpPath);
            @unlink($tmpPath);
            $spreadsheet->disconnectWorksheets();
            exit;
        } catch (Throwable $e) {
            if (isset($tmpPath) && is_string($tmpPath) && $tmpPath !== '') {
                @unlink($tmpPath);
            }
            error_log('ApplicationAdmission exportSecondOption: ' . $e->getMessage());
            $xlsFallback();
        }
    }

    /**
     * @return array{
     *   level:string,
     *   department_id:string,
     *   course_id:string,
     *   courses:array<int,array<string,mixed>>,
     *   departments:array<int,array{department_id:string,department_name:string}>,
     *   cutoff_map:array<string,mixed>,
     *   groups:array<int,array<string,mixed>>,
     *   qualify_total_all:int,
     *   filter_query:string,
     *   filter_summary:string
     * }
     */
    private function cutoffPageData(): array {
        $level = (string) $this->get('level', '04');
        if (!in_array($level, ['04', '05'], true)) {
            $level = '04';
        }
        $departmentId = trim((string) $this->get('department_id', ''));
        $courseId = trim((string) $this->get('course_id', ''));

        require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
        require_once BASE_PATH . '/models/CourseModel.php';
        $cutoffModel = new ApplicationAdmissionCutoffModel();
        $nvq = $level === '05' ? '5' : '4';
        $courses = (new CourseModel())->getCoursesWithDepartment([
            'nvq_level' => $nvq,
            'active_only' => true,
        ]);
        $departments = $this->departmentsFromCourses($courses);

        $validDept = false;
        foreach ($departments as $d) {
            if (strcasecmp((string) ($d['department_id'] ?? ''), $departmentId) === 0) {
                $validDept = true;
                break;
            }
        }
        if (!$validDept) {
            $departmentId = '';
        }

        $validCourse = false;
        foreach ($courses as $c) {
            if (strcasecmp((string) ($c['course_id'] ?? ''), $courseId) !== 0) {
                continue;
            }
            if ($departmentId !== '' && strcasecmp((string) ($c['department_id'] ?? ''), $departmentId) !== 0) {
                continue;
            }
            $validCourse = true;
            break;
        }
        if (!$validCourse) {
            $courseId = '';
        }

        $allGroups = $cutoffModel->qualifyingStudentsByCourse($level);
        $qualifyTotalAll = 0;
        foreach ($allGroups as $g) {
            $qualifyTotalAll += (int) ($g['qualify_count'] ?? 0);
        }
        $groups = $this->filterCutoffGroups($allGroups, $departmentId, $courseId);

        $qs = ['level' => $level];
        if ($departmentId !== '') {
            $qs['department_id'] = $departmentId;
        }
        if ($courseId !== '') {
            $qs['course_id'] = $courseId;
        }

        return [
            'level' => $level,
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'courses' => $courses,
            'departments' => $departments,
            'cutoff_map' => $cutoffModel->getCutoffMap($level),
            'groups' => $groups,
            'qualify_total_all' => $qualifyTotalAll,
            'filter_query' => http_build_query($qs),
            'filter_summary' => $this->cutoffFilterSummary($departmentId, $courseId, $courses, $departments),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @return array<int,array{department_id:string,department_name:string}>
     */
    private function departmentsFromCourses(array $courses): array {
        $departments = [];
        foreach ($courses as $c) {
            $did = trim((string) ($c['department_id'] ?? ''));
            if ($did === '') {
                continue;
            }
            if (!isset($departments[$did])) {
                $departments[$did] = [
                    'department_id' => $did,
                    'department_name' => trim((string) ($c['department_name'] ?? $did)),
                ];
            }
        }
        uasort($departments, static function (array $a, array $b): int {
            return strcasecmp((string) $a['department_name'], (string) $b['department_name']);
        });

        return array_values($departments);
    }

    /**
     * @param array<int,array<string,mixed>> $groups
     * @return array<int,array<string,mixed>>
     */
    private function filterCutoffGroups(array $groups, string $departmentId, string $courseId, bool $matchOr = false): array {
        if ($departmentId === '' && $courseId === '') {
            return array_values($groups);
        }
        if ($matchOr && $departmentId !== '' && $courseId !== '') {
            return array_values(array_filter($groups, static function ($g) use ($departmentId, $courseId) {
                return strcasecmp((string) ($g['department_id'] ?? ''), $departmentId) === 0
                    || strcasecmp((string) ($g['course_id'] ?? ''), $courseId) === 0;
            }));
        }
        if ($departmentId !== '') {
            $groups = array_values(array_filter($groups, static function ($g) use ($departmentId) {
                return strcasecmp((string) ($g['department_id'] ?? ''), $departmentId) === 0;
            }));
        }
        if ($courseId !== '') {
            $groups = array_values(array_filter($groups, static function ($g) use ($courseId) {
                return strcasecmp((string) ($g['course_id'] ?? ''), $courseId) === 0;
            }));
        }

        return array_values($groups);
    }

    /**
     * @return array{
     *   level:string,
     *   department_id:string,
     *   course_id:string,
     *   courses:array<int,array<string,mixed>>,
     *   departments:array<int,array{department_id:string,department_name:string}>,
     *   groups:array<int,array<string,mixed>>,
     *   fail_groups:array<int,array<string,mixed>>,
     *   total_students:int,
     *   total_fail:int,
     *   choice_counts:array<string,int>,
     *   fail_counts:array<string,int>,
     *   min_marks:int,
     *   overview:array{totals:array<string,int>,departments:list<array<string,mixed>>,courses:list<array<string,mixed>>},
     *   filter_query:string,
     *   filter_summary:string
     * }
     */
    private function selectionReportPageData(): array {
        $level = trim((string) $this->get('level', ''));
        if (!in_array($level, ['04', '05', ''], true)) {
            $level = '';
        }
        $departmentId = trim((string) $this->get('department_id', ''));
        $courseId = trim((string) $this->get('course_id', ''));
        $levels = $level !== '' ? [$level] : ['04', '05'];

        require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
        require_once BASE_PATH . '/models/CourseModel.php';
        $cutoffModel = new ApplicationAdmissionCutoffModel();
        $courseModel = new CourseModel();
        $courses = [];
        foreach ($levels as $lv) {
            $batch = $courseModel->getCoursesWithDepartment([
                'nvq_level' => $lv === '05' ? '5' : '4',
                'active_only' => true,
            ]);
            foreach ($batch as $c) {
                $c['application_level'] = $lv;
                $courses[] = $c;
            }
        }
        $departments = $this->departmentsFromCourses($courses);

        $validDept = false;
        foreach ($departments as $d) {
            if (strcasecmp((string) ($d['department_id'] ?? ''), $departmentId) === 0) {
                $validDept = true;
                break;
            }
        }
        if (!$validDept) {
            $departmentId = '';
        }
        $validCourse = false;
        foreach ($courses as $c) {
            if (strcasecmp((string) ($c['course_id'] ?? ''), $courseId) !== 0) {
                continue;
            }
            if ($departmentId !== '' && strcasecmp((string) ($c['department_id'] ?? ''), $departmentId) !== 0) {
                continue;
            }
            $validCourse = true;
            break;
        }
        if (!$validCourse) {
            $courseId = '';
        }

        $hasFilter = $level !== '' || $departmentId !== '' || $courseId !== '';

        $minMarks = ApplicationAdmissionCutoffModel::MARKS_MIN_SECOND_OPTION;
        $qs = [];
        if ($level !== '') {
            $qs['level'] = $level;
        }
        if ($departmentId !== '') {
            $qs['department_id'] = $departmentId;
        }
        if ($courseId !== '') {
            $qs['course_id'] = $courseId;
        }

        $allGroups = [];
        $allFailGroups = [];
        $overview = [
            'totals' => [
                'applied' => 0, 'sat' => 0, 'selected' => 0, 'failed' => 0, 'absent' => 0,
                'second_option' => 0, 'interview' => 0, 'cutoff_courses' => 0,
                'courses' => 0, 'departments' => 0,
            ],
            'departments' => [],
            'courses' => [],
        ];
        foreach ($levels as $lv) {
            $levelGroups = $cutoffModel->selectionReportGroups($lv);
            foreach ($levelGroups as &$g) {
                $g['application_level'] = $lv;
            }
            unset($g);
            $selectedIds = [];
            foreach ($levelGroups as $g) {
                foreach ((is_array($g['students'] ?? null) ? $g['students'] : []) as $row) {
                    $aid = (int) ($row['application_id'] ?? 0);
                    if ($aid > 0) {
                        $selectedIds[] = $aid;
                    }
                }
            }
            $levelFail = $cutoffModel->selectionFailReportGroups($lv, $selectedIds);
            foreach ($levelFail as &$fg) {
                $fg['application_level'] = $lv;
            }
            unset($fg);
            $levelOverview = $cutoffModel->selectionOverviewCards($lv, $levelGroups, $levelFail);
            $allGroups = array_merge($allGroups, $levelGroups);
            $allFailGroups = array_merge($allFailGroups, $levelFail);
            $overview = $this->mergeSelectionOverviews($overview, $levelOverview);
        }
        $overview = $this->filterSelectionOverview($overview, $departmentId, $courseId);
        $groups = $this->filterCutoffGroups($allGroups, $departmentId, $courseId);
        $failGroups = $this->filterCutoffGroups($allFailGroups, $departmentId, $courseId);
        $total = 0;
        $choiceCounts = ['1st' => 0, '2nd' => 0, '3rd' => 0, 'interview' => 0];
        foreach ($groups as $g) {
            $list = is_array($g['students'] ?? null) ? $g['students'] : [];
            $total += count($list);
            foreach ($list as $row) {
                $choice = (int) ($row['choice'] ?? 0);
                if ($choice === 1) {
                    $choiceCounts['1st']++;
                } elseif ($choice === 2) {
                    $choiceCounts['2nd']++;
                } elseif ($choice === 3) {
                    $choiceCounts['3rd']++;
                } else {
                    $choiceCounts['interview']++;
                }
            }
        }
        $totalFail = 0;
        $failCounts = ['below_min' => 0, 'absent' => (int) ($overview['totals']['absent'] ?? 0), 'missed_cutoff' => 0];
        foreach ($failGroups as $g) {
            $list = is_array($g['students'] ?? null) ? $g['students'] : [];
            foreach ($list as $row) {
                $reason = strtolower(trim((string) ($row['fail_reason'] ?? '')));
                if ($reason === 'absent') {
                    continue;
                }
                if (strpos($reason, 'below') === 0) {
                    $failCounts['below_min']++;
                    $totalFail++;
                } else {
                    $failCounts['missed_cutoff']++;
                }
            }
        }

        return [
            'level' => $level,
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'has_course' => $courseId !== '',
            'has_filter' => $hasFilter,
            'courses' => $courses,
            'departments' => $departments,
            'groups' => $groups,
            'fail_groups' => $failGroups,
            'total_students' => $total,
            'total_fail' => $totalFail,
            'choice_counts' => $choiceCounts,
            'fail_counts' => $failCounts,
            'min_marks' => $minMarks,
            'overview' => $overview,
            'filter_query' => http_build_query($qs),
            'filter_summary' => $this->selectionReportFilterSummary($level, $departmentId, $courseId, $courses, $departments),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @param array<int,array{department_id:string,department_name:string}> $departments
     */
    private function cutoffFilterSummary(string $departmentId, string $courseId, array $courses, array $departments): string {
        $bits = [];
        if ($departmentId !== '') {
            foreach ($departments as $d) {
                if (strcasecmp((string) ($d['department_id'] ?? ''), $departmentId) === 0) {
                    $bits[] = 'Department: ' . (string) ($d['department_name'] ?? $departmentId);
                    break;
                }
            }
        }
        if ($courseId !== '') {
            foreach ($courses as $c) {
                if (strcasecmp((string) ($c['course_id'] ?? ''), $courseId) === 0) {
                    $bits[] = 'Course: ' . (string) ($c['course_name'] ?? $courseId);
                    break;
                }
            }
        }

        return $bits !== [] ? implode('  |  ', $bits) : 'All departments and courses';
    }

    /**
     * Keep overview cards for the selected course (and its department) only.
     *
     * @param array{totals:array<string,int>,departments:list<array<string,mixed>>,courses:list<array<string,mixed>>} $overview
     * @return array{totals:array<string,int>,departments:list<array<string,mixed>>,courses:list<array<string,mixed>>}
     */
    private function filterSelectionOverview(array $overview, string $departmentId, string $courseId): array {
        $courses = is_array($overview['courses'] ?? null) ? $overview['courses'] : [];
        if ($courseId !== '') {
            $courses = array_values(array_filter($courses, static function ($c) use ($courseId) {
                return strcasecmp((string) ($c['course_id'] ?? ''), $courseId) === 0;
            }));
        } elseif ($departmentId !== '') {
            $courses = array_values(array_filter($courses, static function ($c) use ($departmentId) {
                return strcasecmp((string) ($c['department_id'] ?? ''), $departmentId) === 0;
            }));
        }
        $deptIds = [];
        $totals = [
            'applied' => 0, 'sat' => 0, 'met_cutoff' => 0, 'below_cutoff' => 0,
            'selected' => 0, 'selected_1st' => 0, 'selected_2nd' => 0, 'selected_3rd' => 0, 'selected_other' => 0,
            'failed' => 0, 'absent' => 0, 'second_option' => 0,
            'interview' => 0, 'cutoff_courses' => 0, 'courses' => count($courses), 'departments' => 0,
        ];
        foreach ($courses as $c) {
            $did = trim((string) ($c['department_id'] ?? ''));
            if ($did !== '') {
                $deptIds[strtolower($did)] = true;
            }
            $totals['applied'] += (int) ($c['applied'] ?? 0);
            $totals['sat'] += (int) ($c['sat'] ?? 0);
            $totals['met_cutoff'] += (int) ($c['met_cutoff'] ?? 0);
            $totals['below_cutoff'] += (int) ($c['below_cutoff'] ?? 0);
            $totals['selected'] += (int) ($c['selected'] ?? 0);
            $totals['selected_1st'] += (int) ($c['selected_1st'] ?? 0);
            $totals['selected_2nd'] += (int) ($c['selected_2nd'] ?? 0);
            $totals['selected_3rd'] += (int) ($c['selected_3rd'] ?? 0);
            $totals['selected_other'] += (int) ($c['selected_other'] ?? 0);
            $totals['failed'] += (int) ($c['failed'] ?? 0);
            $totals['absent'] += (int) ($c['absent'] ?? 0);
            $totals['second_option'] += (int) ($c['second_option'] ?? 0);
            $totals['interview'] += (int) ($c['interview'] ?? 0);
            if (!empty($c['has_cutoff'])) {
                $totals['cutoff_courses']++;
            }
        }
        $departments = is_array($overview['departments'] ?? null) ? $overview['departments'] : [];
        if ($deptIds !== []) {
            $departments = array_values(array_filter($departments, static function ($d) use ($deptIds) {
                return isset($deptIds[strtolower(trim((string) ($d['department_id'] ?? '')))]);
            }));
        } elseif ($courseId !== '') {
            $departments = [];
        }
        $totals['departments'] = count($departments);

        return [
            'totals' => $totals,
            'departments' => $departments,
            'courses' => $courses,
        ];
    }

    /**
     * @param array{totals:array<string,int>,departments:list<array<string,mixed>>,courses:list<array<string,mixed>>} $left
     * @param array{totals:array<string,int>,departments:list<array<string,mixed>>,courses:list<array<string,mixed>>} $right
     * @return array{totals:array<string,int>,departments:list<array<string,mixed>>,courses:list<array<string,mixed>>}
     */
    private function mergeSelectionOverviews(array $left, array $right): array {
        $courses = array_merge(
            is_array($left['courses'] ?? null) ? $left['courses'] : [],
            is_array($right['courses'] ?? null) ? $right['courses'] : []
        );
        $deptMap = [];
        foreach (array_merge(
            is_array($left['departments'] ?? null) ? $left['departments'] : [],
            is_array($right['departments'] ?? null) ? $right['departments'] : []
        ) as $d) {
            $did = strtolower(trim((string) ($d['department_id'] ?? '')));
            $dname = mb_strtolower(trim((string) ($d['department_name'] ?? '')), 'UTF-8');
            $key = $did !== '' ? 'id:' . $did : ('n:' . ($dname !== '' ? $dname : 'other'));
            if (!isset($deptMap[$key])) {
                $deptMap[$key] = $d;
                continue;
            }
            foreach (['applied', 'sat', 'met_cutoff', 'below_cutoff', 'selected', 'selected_1st', 'selected_2nd', 'selected_3rd', 'selected_other', 'failed', 'absent', 'second_option', 'interview', 'courses', 'cutoff_courses'] as $k) {
                $deptMap[$key][$k] = (int) ($deptMap[$key][$k] ?? 0) + (int) ($d[$k] ?? 0);
            }
        }
        $departments = array_values($deptMap);
        usort($departments, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['department_name'] ?? ''), (string) ($b['department_name'] ?? ''));
        });
        usort($courses, static function (array $a, array $b): int {
            $lv = strcasecmp((string) ($a['application_level'] ?? ''), (string) ($b['application_level'] ?? ''));
            if ($lv !== 0) {
                return $lv;
            }
            $dept = strcasecmp((string) ($a['department_name'] ?? ''), (string) ($b['department_name'] ?? ''));
            if ($dept !== 0) {
                return $dept;
            }
            $course = strcasecmp((string) ($a['course_name'] ?? ''), (string) ($b['course_name'] ?? ''));
            if ($course !== 0) {
                return $course;
            }

            return strcasecmp((string) ($a['medium_label'] ?? ''), (string) ($b['medium_label'] ?? ''));
        });
        $totals = [
            'applied' => 0, 'sat' => 0, 'met_cutoff' => 0, 'below_cutoff' => 0,
            'selected' => 0, 'selected_1st' => 0, 'selected_2nd' => 0, 'selected_3rd' => 0, 'selected_other' => 0,
            'failed' => 0, 'absent' => 0,
            'second_option' => 0, 'interview' => 0, 'cutoff_courses' => 0,
            'courses' => count($courses), 'departments' => count($departments),
        ];
        foreach ($courses as $c) {
            $totals['applied'] += (int) ($c['applied'] ?? 0);
            $totals['sat'] += (int) ($c['sat'] ?? 0);
            $totals['met_cutoff'] += (int) ($c['met_cutoff'] ?? 0);
            $totals['below_cutoff'] += (int) ($c['below_cutoff'] ?? 0);
            $totals['selected'] += (int) ($c['selected'] ?? 0);
            $totals['selected_1st'] += (int) ($c['selected_1st'] ?? 0);
            $totals['selected_2nd'] += (int) ($c['selected_2nd'] ?? 0);
            $totals['selected_3rd'] += (int) ($c['selected_3rd'] ?? 0);
            $totals['selected_other'] += (int) ($c['selected_other'] ?? 0);
            $totals['failed'] += (int) ($c['failed'] ?? 0);
            $totals['absent'] += (int) ($c['absent'] ?? 0);
            $totals['second_option'] += (int) ($c['second_option'] ?? 0);
            $totals['interview'] += (int) ($c['interview'] ?? 0);
            if (!empty($c['has_cutoff'])) {
                $totals['cutoff_courses']++;
            }
        }

        return [
            'totals' => $totals,
            'departments' => $departments,
            'courses' => $courses,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @param array<int,array{department_id:string,department_name:string}> $departments
     */
    private function selectionReportFilterSummary(
        string $level,
        string $departmentId,
        string $courseId,
        array $courses,
        array $departments
    ): string {
        $bits = [];
        $bits[] = $level !== '' ? ('NVQ Level ' . $level) : 'All NVQ levels';
        $rest = $this->cutoffFilterSummary($departmentId, $courseId, $courses, $departments);
        if ($rest !== '' && $rest !== 'All departments and courses') {
            $bits[] = $rest;
        } elseif ($level === '' && $departmentId === '' && $courseId === '') {
            $bits[] = 'All departments and courses';
        }

        return implode('  |  ', $bits);
    }

    /**
     * @param array<int,array<string,mixed>> $groups
     * @return array<int,array<string,string>>
     */
    private function flattenCutoffStudents(array $groups): array {
        $rows = [];
        foreach ($groups as $group) {
            if (empty($group['has_cutoff'])) {
                continue;
            }
            foreach (($group['by_medium'] ?? []) as $block) {
                if (empty($block['has_cutoff'])) {
                    continue;
                }
                $n = 0;
                foreach (($block['students'] ?? []) as $row) {
                    $n++;
                    $rows[] = [
                        'no' => (string) $n,
                        'department' => (string) ($group['department_name'] ?? ''),
                        'course_name' => (string) ($group['course_name'] ?? ''),
                        'language_group' => (string) ($block['label'] ?? ''),
                        'roll_number' => (string) ($row['roll_number'] ?? ''),
                        'student_full_name' => (string) ($row['student_full_name'] ?? ''),
                        'student_nic' => (string) ($row['student_nic'] ?? ''),
                        'course_priority_2' => (string) ($row['course_priority_2'] ?? ''),
                        'student_province' => (string) ($row['student_province'] ?? ''),
                        'region' => (string) ($row['region'] ?? ''),
                        'medium' => (string) ($row['medium'] ?? ''),
                        'exam_marks' => $this->formatCutoffNumber($row['marks_num'] ?? $row['exam_marks'] ?? ''),
                        'cutoff_applied' => $this->formatCutoffNumber($row['cutoff_applied'] ?? ''),
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @param array{totals?:array<string,int>,courses?:list<array<string,mixed>>} $overview
     * @return list<array<string,string>>
     */
    private function flattenSelectionOverviewRows(array $overview): array {
        $rows = [];
        $n = 0;
        foreach ((is_array($overview['courses'] ?? null) ? $overview['courses'] : []) as $c) {
            $n++;
            $medium = trim((string) ($c['medium_label'] ?? ''));
            $rows[] = [
                'no' => (string) $n,
                'nvq_level' => trim((string) ($c['application_level'] ?? '')),
                'department' => trim((string) ($c['department_name'] ?? '')),
                'course_name' => trim((string) ($c['course_name'] ?? '')),
                'medium' => $medium !== '' ? $medium : 'All languages',
                'applied_students' => (string) (int) ($c['applied'] ?? 0),
                'exam_students' => (string) (int) ($c['sat'] ?? 0),
                'met_cutoff' => (string) (int) ($c['met_cutoff'] ?? 0),
                'below_cutoff' => (string) (int) ($c['below_cutoff'] ?? 0),
                'selected_students' => (string) (int) ($c['selected'] ?? 0),
                'selected_1st' => (string) (int) ($c['selected_1st'] ?? 0),
                'selected_2nd' => (string) (int) ($c['selected_2nd'] ?? 0),
                'selected_3rd' => (string) (int) ($c['selected_3rd'] ?? 0),
                'selected_other' => (string) (int) ($c['selected_other'] ?? 0),
                'cutoff' => $this->formatOverviewCutoffText($c),
                'failed_students' => (string) (int) ($c['failed'] ?? 0),
                'absent_students' => (string) (int) ($c['absent'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $card
     */
    private function formatOverviewCutoffText(array $card): string {
        $fmt = static function ($n): string {
            if ($n === null || $n === '') {
                return '—';
            }
            if (is_numeric($n) && abs((float) $n - round((float) $n)) < 0.00001) {
                return (string) (int) round((float) $n);
            }

            return is_numeric($n) ? rtrim(rtrim(sprintf('%.2f', (float) $n), '0'), '.') : (string) $n;
        };
        $parts = is_array($card['cutoff_parts'] ?? null) ? $card['cutoff_parts'] : [];
        if ($parts === []) {
            if (empty($card['has_cutoff'])) {
                return 'Not set';
            }

            return 'Northern ' . $fmt($card['cutoff_northern'] ?? null)
                . ' / Other ' . $fmt($card['cutoff_other'] ?? null);
        }
        $bits = [];
        foreach ($parts as $p) {
            $pair = 'Northern ' . $fmt($p['cutoff_northern'] ?? null)
                . ' / Other ' . $fmt($p['cutoff_other'] ?? null);
            $label = trim((string) ($p['label'] ?? ''));
            $bits[] = ($label !== '' && strcasecmp($label, 'All languages') !== 0)
                ? ($label . ': ' . $pair)
                : $pair;
        }

        return implode(' · ', $bits);
    }

    private function formatCutoffNumber($n): string {
        if ($n === null || $n === '') {
            return '';
        }
        if (is_numeric($n) && abs((float) $n - round((float) $n)) < 0.00001) {
            return (string) (int) round((float) $n);
        }

        return is_numeric($n) ? rtrim(rtrim(sprintf('%.2f', (float) $n), '0'), '.') : (string) $n;
    }

    /**
     * @return array{
     *   level:string,
     *   department_id:string,
     *   course_id:string,
     *   courses:array<int,array<string,mixed>>,
     *   departments:array<int,array{department_id:string,department_name:string}>,
     *   students:array<int,array<string,mixed>>,
     *   groups:array<int,array<string,mixed>>,
     *   min_marks:int,
     *   filter_query:string,
     *   filter_summary:string
     * }
     */
    private function secondOptionPageData(): array {
        $level = (string) $this->get('level', '04');
        if (!in_array($level, ['04', '05'], true)) {
            $level = '04';
        }
        $departmentId = trim((string) $this->get('department_id', ''));
        $courseId = trim((string) $this->get('course_id', ''));

        require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
        require_once BASE_PATH . '/models/CourseModel.php';
        $cutoffModel = new ApplicationAdmissionCutoffModel();
        $nvq = $level === '05' ? '5' : '4';
        $courses = (new CourseModel())->getCoursesWithDepartment([
            'nvq_level' => $nvq,
            'active_only' => true,
        ]);
        $departments = $this->departmentsFromCourses($courses);

        $validDept = false;
        foreach ($departments as $d) {
            if (strcasecmp((string) ($d['department_id'] ?? ''), $departmentId) === 0) {
                $validDept = true;
                break;
            }
        }
        if (!$validDept) {
            $departmentId = '';
        }

        $validCourse = false;
        foreach ($courses as $c) {
            if (strcasecmp((string) ($c['course_id'] ?? ''), $courseId) !== 0) {
                continue;
            }
            if ($departmentId !== '' && strcasecmp((string) ($c['department_id'] ?? ''), $departmentId) !== 0) {
                continue;
            }
            $validCourse = true;
            break;
        }
        if (!$validCourse) {
            $courseId = '';
        }

        $payload = $cutoffModel->secondOptionStudents($level);
        $filtered = $this->filterSecondOptionPayload($payload, $departmentId, $courseId);

        $qs = ['level' => $level];
        if ($departmentId !== '') {
            $qs['department_id'] = $departmentId;
        }
        if ($courseId !== '') {
            $qs['course_id'] = $courseId;
        }

        return [
            'level' => $level,
            'department_id' => $departmentId,
            'course_id' => $courseId,
            'courses' => $courses,
            'departments' => $departments,
            'students' => $filtered['students'],
            'groups' => $filtered['groups'],
            'min_marks' => (int) ($payload['min_marks'] ?? ApplicationAdmissionCutoffModel::MARKS_MIN_SECOND_OPTION),
            'filter_query' => http_build_query($qs),
            'filter_summary' => $this->cutoffFilterSummary($departmentId, $courseId, $courses, $departments),
        ];
    }

    /**
     * @param array{students?:array,groups?:array} $payload
     * @return array{students:array<int,array<string,mixed>>,groups:array<int,array<string,mixed>>}
     */
    private function filterSecondOptionPayload(array $payload, string $departmentId, string $courseId): array {
        $students = is_array($payload['students'] ?? null) ? $payload['students'] : [];
        $groups = is_array($payload['groups'] ?? null) ? $payload['groups'] : [];
        if ($departmentId === '' && $courseId === '') {
            return ['students' => $students, 'groups' => $groups];
        }

        $students = array_values(array_filter($students, static function ($row) use ($departmentId, $courseId) {
            if ($courseId !== '' && strcasecmp((string) ($row['first_course_id'] ?? ''), $courseId) !== 0) {
                return false;
            }
            if ($departmentId !== '' && strcasecmp((string) ($row['first_department_id'] ?? ''), $departmentId) !== 0) {
                return false;
            }

            return true;
        }));

        $keep = [];
        foreach ($students as $row) {
            $keep[(int) ($row['application_id'] ?? 0)] = true;
        }

        $outGroups = [];
        foreach ($groups as $g) {
            if ($courseId !== '' && strcasecmp((string) ($g['course_id'] ?? ''), $courseId) !== 0) {
                continue;
            }
            if ($departmentId !== '' && strcasecmp((string) ($g['department_id'] ?? ''), $departmentId) !== 0) {
                continue;
            }
            $list = [];
            foreach (($g['students'] ?? []) as $row) {
                if (isset($keep[(int) ($row['application_id'] ?? 0)])) {
                    $list[] = $row;
                }
            }
            if ($list === []) {
                continue;
            }
            $g['students'] = $list;
            $g['count'] = count($list);
            $outGroups[] = $g;
        }

        return ['students' => $students, 'groups' => $outGroups];
    }

    /**
     * @param array<int,array<string,mixed>> $groups
     * @return array<int,array<string,string>>
     */
    private function flattenSecondOptionStudents(array $groups): array {
        $rows = [];
        foreach ($groups as $group) {
            $n = 0;
            foreach (($group['students'] ?? []) as $row) {
                $n++;
                $rows[] = [
                    'no' => (string) $n,
                    'department' => (string) ($group['department_name'] ?? $row['first_department_name'] ?? ''),
                    'first_course_name' => (string) ($group['course_name'] ?? $row['first_course_name'] ?? ''),
                    'roll_number' => (string) ($row['roll_number'] ?? ''),
                    'student_full_name' => (string) ($row['student_full_name'] ?? ''),
                    'student_nic' => (string) ($row['student_nic'] ?? ''),
                    'exam_marks' => $this->formatCutoffNumber($row['marks_num'] ?? $row['exam_marks'] ?? ''),
                    'first_cutoff' => $this->formatCutoffNumber($row['first_cutoff'] ?? ''),
                    'second_course_name' => (string) ($row['second_course_name'] ?? ''),
                    'third_course_name' => (string) ($row['third_course_name'] ?? ''),
                    'consider_choice' => ((int) ($row['consider_choice'] ?? 0) === 3)
                        ? '3rd'
                        : (((int) ($row['consider_choice'] ?? 0) === 2) ? '2nd' : 'Do not consider'),
                    'student_province' => (string) ($row['student_province'] ?? ''),
                    'region' => (string) ($row['region'] ?? ''),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return mixed
     */
    private function renderScheduleIndex(string $tab) {
        $uid = $this->requireLogin();
        $userModel = $this->requireView($uid);
        $model = $this->scheduleModel();

        if (!in_array($tab, [ApplicationAdmissionScheduleModel::TYPE_ENTRANCE, ApplicationAdmissionScheduleModel::TYPE_INTERVIEW], true)) {
            $tab = ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        }
        $level = $this->get('level', '');
        if ($level !== '' && !in_array($level, ['04', '05'], true)) {
            $level = '';
        }
        $venue = trim((string) $this->get('venue', ''));
        if ($tab === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            $venue = '';
        }

        $schedules = $model->listSchedules(
            $tab,
            $level !== '' ? $level : null,
            $venue !== '' ? $venue : null
        );
        foreach ($schedules as &$s) {
            $s['entry_count'] = $model->countEntries((int) $s['schedule_id']);
            $s['public_url'] = APP_URL . '/application-admission/public/' . rawurlencode((string) $s['public_token']);
        }
        unset($s);

        $isInterview = $tab === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;

        return $this->view('application_admission/index', [
            'page' => $isInterview ? 'application-admission-interview' : 'application-admission-entrance',
            'schedules' => $schedules,
            'tab' => $tab,
            'levelFilter' => $level,
            'venueFilter' => $venue,
            'venueOptions' => $isInterview ? [] : $model->listDistinctVenues($tab),
            'canManage' => $userModel->canManageApplicationAdmissionSchedules($uid),
            'listBaseUrl' => $isInterview
                ? (rtrim(APP_URL, '/') . '/application-admission/interviews')
                : (rtrim(APP_URL, '/') . '/application-admission'),
            'interviewLetterPublicUrl' => $isInterview
                ? (rtrim(APP_URL, '/') . '/application-admission/interview-letter')
                : '',
        ]);
    }

    /**
     * Excel of participants for one schedule (?id=).
     */
    public function exportParticipants() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $model = $this->scheduleModel();

        $scheduleId = (int) $this->get('id', 0);
        if ($scheduleId < 1) {
            $_SESSION['error'] = 'Invalid schedule.';
            $this->redirect('application-admission');
        }
        $schedule = $model->findSchedule($scheduleId);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }

        $tab = (string) ($schedule['schedule_type'] ?? ApplicationAdmissionScheduleModel::TYPE_ENTRANCE);
        $entries = $model->getParticipantsAcrossSchedules(null, null, null, $scheduleId);
        if ($entries === []) {
            $_SESSION['error'] = 'No participants found on this schedule.';
            $this->redirect('application-admission?tab=' . rawurlencode($tab));
        }

        usort($entries, static function (array $a, array $b): int {
            $rollA = trim((string) ($a['roll_number'] ?? ''));
            $rollB = trim((string) ($b['roll_number'] ?? ''));
            if ($rollA === '' && $rollB === '') {
                return strcasecmp((string) ($a['student_full_name'] ?? ''), (string) ($b['student_full_name'] ?? ''));
            }
            if ($rollA === '') {
                return 1;
            }
            if ($rollB === '') {
                return -1;
            }
            $cmp = strnatcasecmp($rollA, $rollB);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['student_full_name'] ?? ''), (string) ($b['student_full_name'] ?? ''));
        });

        $typeLabel = $tab === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW ? 'Interview' : 'Entrance exam';
        $isInterviewExport = $tab === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $rows = [];
        $n = 0;
        foreach ($entries as $row) {
            $n++;
            $rows[] = [
                'no' => (string) $n,
                'schedule_title' => (string) ($row['schedule_title'] ?? ''),
                'schedule_type' => $typeLabel,
                'schedule_date' => (string) ($row['schedule_date'] ?? ''),
                'venue' => (string) ($row['venue'] ?? ''),
                'roll_number' => $isInterviewExport ? '' : (string) ($row['roll_number'] ?? ''),
                'student_full_name' => (string) ($row['student_full_name'] ?? ''),
                'student_nic' => (string) ($row['student_nic'] ?? ''),
                'student_phone' => (string) ($row['student_phone'] ?? ''),
                'student_whatsapp' => (string) ($row['student_whatsapp'] ?? ''),
                'student_email' => (string) ($row['student_email'] ?? ''),
                'student_province' => (string) ($row['student_province'] ?? ''),
                'student_district' => (string) ($row['student_district'] ?? ''),
                'department' => ApplicationAdmissionScheduleModel::departmentCodeFromEntry($row),
                'course_name' => ApplicationAdmissionScheduleModel::courseNameFromEntry($row),
                'application_level' => (string) ($row['application_level'] ?? $row['schedule_level'] ?? ''),
                'application_status' => (string) ($row['application_status'] ?? ''),
                'selection_status' => (string) ($row['selection_status'] ?? ''),
                'exam_marks' => (string) ($row['exam_marks'] ?? ''),
                'room_or_panel' => (string) ($row['room_or_panel'] ?? ''),
                'notes' => (string) ($row['notes'] ?? ''),
            ];
        }

        $cols = [
            'no', 'schedule_title', 'schedule_type', 'schedule_date', 'venue',
            'student_full_name', 'student_nic', 'student_phone', 'student_whatsapp', 'student_email',
            'student_province', 'student_district', 'department', 'course_name', 'application_level',
            'application_status', 'selection_status', 'exam_marks', 'room_or_panel', 'notes',
        ];
        if (!$isInterviewExport) {
            array_splice($cols, 5, 0, ['roll_number']);
        }
        $colLabels = [
            'no' => 'No',
            'schedule_title' => 'Schedule',
            'schedule_type' => 'Type',
            'schedule_date' => 'Date',
            'venue' => 'Centre / Venue',
            'roll_number' => 'Roll / Index',
            'student_full_name' => 'Name',
            'student_nic' => 'NIC',
            'student_phone' => 'Phone',
            'student_whatsapp' => 'WhatsApp',
            'student_email' => 'Email',
            'student_province' => 'Province',
            'student_district' => 'District',
            'department' => 'Dept',
            'course_name' => 'Course (1st preference)',
            'application_level' => 'NVQ Level',
            'application_status' => 'Application status',
            'selection_status' => 'Selection status',
            'exam_marks' => 'Marks',
            'room_or_panel' => 'Room / Panel',
            'notes' => 'Notes',
        ];

        $venue = trim((string) ($schedule['venue'] ?? ''));
        $filterSummary = implode(' · ', array_filter([
            $typeLabel . ' participants',
            trim((string) ($schedule['title'] ?? '')),
            $venue !== '' ? ('Centre: ' . $venue) : '',
            count($rows) . ' participant(s)',
        ]));
        $baseName = 'admission_participants_schedule' . $scheduleId . '_' . date('Y-m-d_H-i');

        // Excel-readable HTML table (.xls) — always works without Zip/PhpSpreadsheet.
        $xlsFallback = static function () use ($rows, $cols, $colLabels, $baseName, $filterSummary): void {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xls"');
            header('Cache-Control: private, max-age=0');
            echo "\xEF\xBB\xBF";
            $esc = static function (string $s): string {
                return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
            };
            echo '<table border="1">' . "\n";
            echo '<tr><td colspan="' . count($cols) . '"><b>SLGTI — Admission schedule participants</b></td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">' . $esc($filterSummary) . '</td></tr>' . "\n";
            echo '<tr><td colspan="' . count($cols) . '">Exported: ' . $esc(date('Y-m-d H:i')) . '</td></tr>' . "\n";
            echo '<thead><tr>';
            foreach ($cols as $h) {
                $label = $colLabels[$h] ?? $h;
                echo '<th style="background:#1F4E79;color:#fff;font-weight:bold;padding:6px;">' . $esc((string) $label) . '</th>';
            }
            echo "</tr></thead>\n<tbody>\n";
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($cols as $colName) {
                    $v = isset($row[$colName]) ? (string) $row[$colName] : '';
                    $v = str_replace(["\r\n", "\r", "\n"], ' | ', $v);
                    echo '<td style="mso-number-format:\'\@\';">' . $esc($v) . '</td>';
                }
                echo "</tr>\n";
            }
            echo "</tbody></table>";
            exit;
        };

        $needs = [
            extension_loaded('zip') && class_exists('ZipArchive', false),
            extension_loaded('xmlwriter'),
            extension_loaded('dom'),
            extension_loaded('simplexml'),
            extension_loaded('xml'),
            extension_loaded('mbstring') && function_exists('mb_strlen'),
            extension_loaded('iconv') && function_exists('iconv'),
        ];
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!is_readable($autoload) || in_array(false, $needs, true)) {
            $xlsFallback();
        }

        try {
            require_once $autoload;
            require_once BASE_PATH . '/helpers/StudentApplicationExportXlsx.php';
            if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                $xlsFallback();
            }

            $spreadsheet = StudentApplicationExportXlsx::buildSpreadsheet(
                $rows,
                $cols,
                $colLabels,
                'Participants',
                $filterSummary
            );
            $spreadsheet->getActiveSheet()->setCellValue('A1', 'SLGTI — Admission schedule participants');

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $tmpPath = tempnam(sys_get_temp_dir(), 'slgti_adm_xlsx_');
            if ($tmpPath === false) {
                throw new RuntimeException('Could not create temp file for XLSX export.');
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tmpPath);
            $size = filesize($tmpPath);
            if ($size === false || $size < 1) {
                @unlink($tmpPath);
                throw new RuntimeException('XLSX temp file not readable after write.');
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $baseName) . '.xlsx"');
            header('Cache-Control: private, max-age=0');
            header('Content-Length: ' . (string) $size);
            readfile($tmpPath);
            @unlink($tmpPath);
            $spreadsheet->disconnectWorksheets();
            exit;
        } catch (Throwable $e) {
            if (isset($tmpPath) && is_string($tmpPath) && $tmpPath !== '') {
                @unlink($tmpPath);
            }
            error_log('ApplicationAdmission exportParticipants: ' . $e->getMessage());
            $xlsFallback();
        }
    }

    public function create() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $type = $this->get('type', ApplicationAdmissionScheduleModel::TYPE_ENTRANCE);
        if (!in_array($type, [ApplicationAdmissionScheduleModel::TYPE_ENTRANCE, ApplicationAdmissionScheduleModel::TYPE_INTERVIEW], true)) {
            $type = ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        }
        return $this->view('application_admission/form', $this->formViewData($type, null, 'application-admission/store'));
    }

    public function edit() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($id);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        return $this->view('application_admission/form', $this->formViewData(
            (string) $schedule['schedule_type'],
            $schedule,
            'application-admission/update?id=' . $id
        ));
    }

    public function store() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $model = $this->scheduleModel();
        $data = $this->validatedSchedulePost();
        if ($data === null) {
            $this->redirect('application-admission/create?type=' . urlencode((string) $this->post('schedule_type', 'entrance_exam')));
        }
        $data['created_by'] = $uid;
        $err = null;
        $id = $model->createSchedule($data, $err);
        if ($id === null) {
            $_SESSION['error'] = $err ?: 'Could not save schedule.';
            $this->redirect('application-admission/create');
        }
        $_SESSION['success'] = 'Schedule created. Add applicants and publish when ready.';
        $this->redirect('application-admission/entries?id=' . $id);
    }

    public function update() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->scheduleModel();
        if (!$model->findSchedule($id)) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        $data = $this->validatedSchedulePost();
        if ($data === null) {
            $this->redirect('application-admission/edit?id=' . $id);
        }
        unset($data['public_token']);
        if (!$model->updateSchedule($id, $data)) {
            $_SESSION['error'] = 'Could not update schedule.';
        } else {
            $_SESSION['success'] = 'Schedule updated.';
        }
        $this->redirect('application-admission/edit?id=' . $id);
    }

    public function deleteSchedule() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->scheduleModel();
        if ($model->findSchedule($id) && $model->deleteSchedule($id)) {
            $_SESSION['success'] = 'Schedule deleted.';
        } else {
            $_SESSION['error'] = 'Could not delete schedule.';
        }
        $this->redirect('application-admission');
    }

    public function publish() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->post('schedule_id', 0);
        $action = $this->post('action', 'publish');
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($id);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        $publish = ($action === 'publish');
        if ($publish && $model->countEntries($id) === 0) {
            $_SESSION['error'] = 'Add at least one applicant before publishing.';
            $this->redirect('application-admission/entries?id=' . $id);
        }
        $model->setPublished($id, $publish);
        $_SESSION['success'] = $publish ? 'Schedule published. Public link is active.' : 'Schedule unpublished.';
        $this->redirect('application-admission/entries?id=' . $id);
    }

    public function entries() {
        $uid = $this->requireLogin();
        $userModel = $this->requireView($uid);
        $canManage = $userModel->canManageApplicationAdmissionSchedules($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($id);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        $onlyStaff = $userModel->usesLimitedStudentApplicationList($uid);
        $entries = ApplicationAdmissionScheduleModel::sortEntryRowsByCourseAndProvince(
            $model->getEntriesWithApplications($id)
        );
        $courseWiseRollSeq = ApplicationAdmissionScheduleModel::courseWiseSequenceMap($entries);
        $filterProvinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($this->get('province', ''));
        $pickerLanguage = ApplicationAdmissionScheduleModel::scheduleIgnoresApplicantLanguage($schedule)
            ? null
            : (string) ($schedule['student_language'] ?? '');
        $pickerArgs = [
            (string) $schedule['application_level'],
            $id,
            $onlyStaff,
            $this->scheduleCourseIdOrNull($schedule),
            (string) ($schedule['schedule_type'] ?? ''),
            (string) ($schedule['admission_pathway'] ?? ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW),
            $pickerLanguage,
        ];
        $pickerUnfiltered = $canManage
            ? ApplicationAdmissionScheduleModel::sortEntryRowsByCourseAndProvince(
                $model->getPickerApplications(...array_merge($pickerArgs, [null]))
            )
            : [];
        $provinceOptions = ApplicationAdmissionScheduleModel::collectProvinceOptions($pickerUnfiltered, $entries);
        $picker = $canManage
            ? ($filterProvinces !== []
                ? ApplicationAdmissionScheduleModel::sortEntryRowsByCourseAndProvince(
                    $model->getPickerApplications(...array_merge($pickerArgs, [$filterProvinces]))
                )
                : $pickerUnfiltered)
            : [];

        $pickerHint = $this->schedulePickerHint($schedule);

        $courseIdForPicker = $this->scheduleCourseIdOrNull($schedule);
        $pickerEntranceFallback = false;
        $hasEntranceSchedule = false;
        $entranceSelectedCount = 0;
        $cutoffEligible = [];
        if (($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW
            && $courseIdForPicker !== null
        ) {
            $levelForPicker = (string) ($schedule['application_level'] ?? '');
            $hasEntranceSchedule = $model->hasEntranceScheduleForCourse($levelForPicker, $courseIdForPicker);
            require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
            $cutoffEligible = (new ApplicationAdmissionCutoffModel())->interviewEligibleForCourse(
                $levelForPicker,
                $courseIdForPicker
            );
            $entranceSelectedCount = count($cutoffEligible);
            $entries = $this->mergeInterviewCutoffDetails($entries, $cutoffEligible);
            if ($canManage) {
                $pickerUnfiltered = $this->mergeInterviewCutoffDetails($pickerUnfiltered, $cutoffEligible);
                $picker = $this->mergeInterviewCutoffDetails($picker, $cutoffEligible);
            }
        }

        $publicUrl = rtrim(APP_URL, '/') . '/application-admission/public/' . rawurlencode((string) $schedule['public_token']);
        $rollCourseCode = ApplicationAdmissionScheduleModel::rollIndexCourseCodeFromSchedule($schedule);
        $rollFormatPrefix = ApplicationAdmissionScheduleModel::rollNumberPrefixFromSchedule($schedule);
        $rollFormatSample = $entries !== []
            ? ApplicationAdmissionScheduleModel::formatRollNumberForEntry($schedule, $entries[0], 1)
            : ApplicationAdmissionScheduleModel::rollNumberFormatSampleFromSchedule($schedule);

        return $this->view('application_admission/entries', [
            'page' => (($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW)
                ? 'application-admission-interview'
                : 'application-admission-entrance',
            'schedule' => $schedule,
            'entries' => $entries,
            'picker' => $picker,
            'canManage' => $canManage,
            'publicUrl' => $publicUrl,
            'rollCourseCode' => $rollCourseCode,
            'rollFormatPrefix' => $rollFormatPrefix,
            'rollFormatSample' => $rollFormatSample,
            'courseWiseRollSeq' => $courseWiseRollSeq,
            'whatsAppRecipients' => $this->buildWhatsAppRecipients($schedule, $entries, $publicUrl, $courseWiseRollSeq),
            'pickerHint' => $pickerHint,
            'picker_entrance_fallback' => $pickerEntranceFallback,
            'has_entrance_schedule' => $hasEntranceSchedule,
            'entrance_selected_count' => $entranceSelectedCount,
            'filter_provinces' => $filterProvinces,
            'province_options' => $provinceOptions,
            'picker_unfiltered_count' => count($pickerUnfiltered),
        ]);
    }

    public function entriesSave() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $id = (int) $this->post('schedule_id', 0);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($id);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }

        $addIds = $this->post('add_application_ids', []);
        if (!is_array($addIds)) {
            $addIds = [];
        }
        $addIds = array_map('intval', $addIds);
        if (($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            $cid = $this->scheduleCourseIdOrNull($schedule);
            $level = (string) ($schedule['application_level'] ?? '');
            $already = $model->interviewScheduledApplicationIds($level, $id > 0 ? $id : null);
            if ($cid !== null) {
                require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
                $eligible = (new ApplicationAdmissionCutoffModel())->interviewEligibleForCourse($level, $cid);
                $addIds = array_values(array_filter($addIds, static function ($appId) use ($eligible, $already): bool {
                    $appId = (int) $appId;

                    return isset($eligible[$appId]) && !isset($already[$appId]);
                }));
            } else {
                $addIds = array_values(array_filter($addIds, static function ($appId) use ($already): bool {
                    return !isset($already[(int) $appId]);
                }));
            }
        }
        $added = $model->addApplications($id, $addIds);

        $removeIds = $this->post('remove_entry_ids', []);
        if (is_array($removeIds)) {
            foreach ($removeIds as $eid) {
                $model->removeEntry((int) $eid, $id);
            }
        }

        $entryRows = $this->post('entries', []);
        $existingEntries = $model->getEntriesWithApplications($id);
        if (is_array($entryRows)) {
            foreach ($existingEntries as $entry) {
                $entryId = (int) ($entry['entry_id'] ?? 0);
                if ($entryId <= 0) {
                    continue;
                }
                // Newly added applicants are not in the form — leave roll empty for auto-assign.
                $row = $entryRows[$entryId] ?? $entryRows[(string) $entryId] ?? null;
                if (!is_array($row)) {
                    continue;
                }
                $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
                $model->updateEntry($entryId, $id, [
                    'roll_number' => $isInterview
                        ? trim((string) ($entry['roll_number'] ?? ''))
                        : trim((string) ($row['roll_number'] ?? $entry['roll_number'] ?? '')),
                    'room_or_panel' => trim((string) ($row['room_or_panel'] ?? $entry['room_or_panel'] ?? '')),
                    'notes' => trim((string) ($row['notes'] ?? $entry['notes'] ?? '')),
                    'whatsapp_sent' => !empty($row['whatsapp_sent']) ? 1 : 0,
                ]);
            }
        }
        if (($schedule['schedule_type'] ?? '') !== ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            $this->assignSequentialRollNumbersWhereEmpty($model, $id, $schedule);
        }

        $_SESSION['success'] = $added > 0
            ? "Saved. {$added} applicant(s) added."
            : 'Applicant list saved.';
        $filterProvinces = $this->post('filter_provinces', []);
        if (!is_array($filterProvinces)) {
            $filterProvinces = [$filterProvinces];
        }
        $this->redirect($this->entriesRedirectUrl($id, $filterProvinces));
    }

    public function markWhatsappSent() {
        $uid = $this->requireLogin();
        $this->requireManage($uid);
        $scheduleId = (int) $this->post('schedule_id', 0);
        $entryId = (int) $this->post('entry_id', 0);
        $sent = (int) $this->post('sent', 0) === 1;
        $model = $this->scheduleModel();
        if (!$model->findSchedule($scheduleId)) {
            $this->json(['success' => false, 'error' => 'Schedule not found.'], 404);
        }
        $entries = $model->getEntriesWithApplications($scheduleId);
        $found = false;
        foreach ($entries as $e) {
            if ((int) ($e['entry_id'] ?? 0) === $entryId) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $this->json(['success' => false, 'error' => 'Entry not found.'], 404);
        }
        if (!$model->setWhatsAppSent($entryId, $scheduleId, $sent)) {
            $this->json([
                'success' => false,
                'error' => 'Could not save sent status. If this persists, run database/application_admission_whatsapp_sent.sql on your database.',
            ], 500);
        }
        $this->json(['success' => true, 'sent' => $sent ? 1 : 0]);
    }

    public function selection() {
        $uid = $this->requireLogin();
        $userModel = $this->requireView($uid);
        $canManage = $userModel->canManageApplicationAdmissionSchedules($uid);
        $canUpdateInterviewSelection = $userModel->canUpdateApplicationAdmissionSelection($uid);
        $id = (int) $this->get('id', 0);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($id);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $isEntrance = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        if (!$isInterview && !$isEntrance) {
            $_SESSION['error'] = 'Invalid schedule type.';
            $this->redirect('application-admission');
        }
        $canUpdateSelection = $isInterview ? $canUpdateInterviewSelection : $canManage;
        $entries = $this->sortEntriesByRollNumber($model->getEntriesWithApplications($id));

        return $this->view('application_admission/selection', [
            'page' => $isInterview ? 'application-admission-interview' : 'application-admission-entrance',
            'schedule' => $schedule,
            'entries' => $entries,
            'canUpdateSelection' => $canUpdateSelection,
            'isEntranceResults' => $isEntrance,
        ]);
    }

    public function selectionSave() {
        $uid = $this->requireLogin();
        $id = (int) $this->post('schedule_id', 0);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($id);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $isEntrance = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        if ($isInterview) {
            $this->requireSelectionUpdate($uid);
        } elseif ($isEntrance) {
            $this->requireManage($uid);
        } else {
            $_SESSION['error'] = 'Invalid schedule type.';
            $this->redirect('application-admission');
        }
        $entryIds = $this->post('entry_ids', []);
        $marksPosted = $this->post('exam_marks', []);
        if (!is_array($entryIds)) {
            $entryIds = [];
        }
        if (!is_array($marksPosted)) {
            $marksPosted = [];
        }
        // Prefer posted entry list; fall back to all schedule entries.
        if ($entryIds === []) {
            foreach ($model->getEntriesWithApplications($id) as $row) {
                $entryIds[] = (int) ($row['entry_id'] ?? 0);
            }
        }
        $invalidMarks = 0;
        foreach ($entryIds as $entryId) {
            $entryId = (int) $entryId;
            if ($entryId < 1) {
                continue;
            }
            $rawMarks = (string) ($marksPosted[$entryId] ?? $marksPosted[(string) $entryId] ?? '');
            $normalizedMarks = ApplicationAdmissionScheduleModel::normalizeExamMarks($rawMarks);
            if ($normalizedMarks === false) {
                $invalidMarks++;
                continue;
            }
            $model->updateEntry($entryId, $id, [
                'exam_marks' => $normalizedMarks === null ? '' : $normalizedMarks,
            ]);
        }
        $savedMsg = $isEntrance ? 'Exam marks saved.' : 'Marks saved.';
        if ($invalidMarks > 0) {
            $savedMsg .= ' ' . $invalidMarks . ' mark(s) skipped — use a number or ab for absent.';
        }
        $_SESSION['success'] = $savedMsg;
        $this->redirect('application-admission/selection?id=' . $id);
    }

    public function pdfSchedule() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $this->streamSchedulePdf($id, true);
    }

    /**
     * Printable attendance sheet (roll, name, NIC, signatures) for entrance / interview schedules.
     */
    public function pdfAttendance() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        if ($id < 1) {
            $_SESSION['error'] = 'Invalid schedule.';
            $this->redirect('application-admission');
        }
        $provinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($this->get('province', ''));
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
            $this->redirect($this->entriesRedirectUrl($id, $provinces));
        }
        try {
            $this->streamAttendancePdf($id, $provinces);
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect($this->entriesRedirectUrl($id, $provinces));
        }
    }

    public function pdfSelection() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $this->streamSelectionPdf($id, true);
    }

    /**
     * Combined interview result sheet (roll number, name, selected course).
     */
    public function pdfInterviewResults() {
        $this->requireView($this->requireLogin());
        $level = trim((string) $this->get('level', ''));
        if ($level !== '' && !in_array($level, ['04', '05'], true)) {
            $level = '';
        }
        $groups = $this->scheduleModel()->interviewCommonResultGroups($level !== '' ? $level : null);
        $total = 0;
        foreach ($groups as $group) {
            $total += count($group['rows'] ?? []);
        }
        $levelLabel = $level !== '' ? $level : '';
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('interview_result_sheet.php', [
            'groups' => $groups,
            'rows' => [],
            'total' => $total,
            'level' => $levelLabel,
            'logo_src' => $this->admissionLogoDataUri(),
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument(
            $inner,
            ApplicationAdmissionPdfHelper::interviewResultSheetStyles()
        );
        $fileLevel = $levelLabel !== '' ? $levelLabel : 'all';
        ApplicationAdmissionPdfHelper::streamHtml(
            $html,
            'interview-result-sheet-nvq-' . $fileLevel . '.pdf',
            'A4',
            'landscape',
            true
        );
    }

    /**
     * Single applicant postal admission / interview card (mailing panel on top).
     */
    public function admissionCard() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $scheduleId = (int) $this->get('id', 0);
        $entryId = (int) $this->get('entry_id', 0);
        if ($scheduleId < 1 || $entryId < 1) {
            $_SESSION['error'] = 'Invalid schedule or applicant.';
            $this->redirect('application-admission');
        }
        $this->streamAdmissionCardsPdf($scheduleId, $entryId, []);
    }

    /**
     * Bulk postal admission cards — optional province filter (same as entries page).
     */
    public function admissionCardsBulk() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $scheduleId = (int) $this->get('id', 0);
        if ($scheduleId < 1) {
            $_SESSION['error'] = 'Invalid schedule.';
            $this->redirect('application-admission');
        }
        $provinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($this->get('province', ''));
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
            $this->redirect($this->entriesRedirectUrl($scheduleId, $provinces));
        }
        try {
            $this->streamAdmissionCardsPdf($scheduleId, null, $provinces);
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect($this->entriesRedirectUrl($scheduleId, $provinces));
        }
    }

    /** Single DL long envelope (From / To) for one applicant. */
    public function envelope() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $scheduleId = (int) $this->get('id', 0);
        $entryId = (int) $this->get('entry_id', 0);
        if ($scheduleId < 1 || $entryId < 1) {
            $_SESSION['error'] = 'Invalid schedule or applicant.';
            $this->redirect('application-admission');
        }
        $this->streamEnvelopesPdf($scheduleId, $entryId, []);
    }

    /** Bulk DL long envelopes — optional province filter (same as entries page). */
    public function envelopesBulk() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $scheduleId = (int) $this->get('id', 0);
        if ($scheduleId < 1) {
            $_SESSION['error'] = 'Invalid schedule.';
            $this->redirect('application-admission');
        }
        $provinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($this->get('province', ''));
        require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
        if (!ExamPdfHelper::dompdfAvailable()) {
            $_SESSION['error'] = 'PDF engine not installed. Run: composer install.';
            $this->redirect($this->entriesRedirectUrl($scheduleId, $provinces));
        }
        try {
            $this->streamEnvelopesPdf($scheduleId, null, $provinces);
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect($this->entriesRedirectUrl($scheduleId, $provinces));
        }
    }

    /**
     * Public page: students enter NIC, see applied vs selected course, then download the letter.
     */
    public function publicInterviewLetter() {
        $nic = trim((string) $this->post('nic', $this->get('nic', '')));
        $result = null;
        if ($nic !== '') {
            $found = $this->findPublishedInterviewLetterOrError($nic);
            if ($found !== null) {
                $result = $this->buildPublicInterviewLetterResult($found['schedule'], $found['entry']);
                $nic = trim((string) ($found['entry']['student_nic'] ?? $nic));
            }
        }

        return $this->view('application_admission/public_interview_letter', [
            'use_public_layout' => true,
            'page' => 'public-interview-letter',
            'title' => 'INVITATION FOR THE SELECTION INTERVIEW – 2026 INTAKE',
            'seo_robots' => 'noindex, nofollow',
            'nic' => $nic,
            'result' => $result,
            'lookupAction' => rtrim(APP_URL, '/') . '/application-admission/interview-letter',
            'formAction' => rtrim(APP_URL, '/') . '/application-admission/interview-letter/download',
        ]);
    }

    public function publicInterviewLetterDownload() {
        $nic = trim((string) $this->post('nic', $this->get('nic', '')));
        $found = $this->findPublishedInterviewLetterOrError($nic);
        if ($found === null) {
            $qs = ApplicationAdmissionScheduleModel::normalizedNic($nic);
            $this->redirect(
                'application-admission/interview-letter' . ($qs !== '' ? ('?nic=' . rawurlencode($nic)) : '')
            );
        }
        $this->streamInterviewLetterPdf($found['schedule'], $found['entry']);
    }

    /**
     * @return array{schedule: array<string, mixed>, entry: array<string, mixed>}|null
     */
    private function findPublishedInterviewLetterOrError(string $nic): ?array {
        $normalized = ApplicationAdmissionScheduleModel::normalizedNic($nic);
        if ($normalized === '') {
            $_SESSION['error'] = 'Check your NIC number.';
            return null;
        }
        $found = $this->scheduleModel()->findPublishedInterviewByNic($normalized);
        if ($found === null) {
            $_SESSION['error'] = 'Check your NIC number.';
            return null;
        }

        return $found;
    }

    /**
     * @param array<string, mixed> $schedule
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function buildPublicInterviewLetterResult(array $schedule, array $entry): array {
        $choice = $this->interviewLetterChoice($schedule, $entry);
        $dateTs = !empty($schedule['schedule_date']) ? strtotime((string) $schedule['schedule_date']) : false;
        $startTs = !empty($schedule['start_time']) ? strtotime((string) $schedule['start_time']) : false;
        $endTs = !empty($schedule['end_time']) ? strtotime((string) $schedule['end_time']) : false;
        $time = $startTs ? date('g:i A', $startTs) : '';
        if ($endTs && $time !== '') {
            $time .= ' – ' . date('g:i A', $endTs);
        }
        $venue = trim((string) ($schedule['venue'] ?? ''));
        if ($venue === '') {
            $venue = 'Sri Lanka – German Training Institute, Ariviyal Nagar, Kilinochchi';
        }

        return [
            'name' => trim((string) ($entry['student_full_name'] ?? '')),
            'nic' => trim((string) ($entry['student_nic'] ?? '')),
            'level' => (string) ($schedule['application_level'] ?? $entry['application_level'] ?? ''),
            'interview_date' => $dateTs ? date('d F Y', $dateTs) : '',
            'interview_time' => $time,
            'venue' => $venue,
            'selected_course' => trim((string) ($choice['course_name'] ?? '')),
            'selected_choice' => (int) ($choice['choice'] ?? 0),
            'selected_label' => trim((string) ($choice['choice_label'] ?? '')),
            'preferences' => is_array($choice['preferences'] ?? null) ? $choice['preferences'] : [1 => '', 2 => '', 3 => ''],
            'choice' => $choice,
        ];
    }

    /** Public landing — no login */
    public function publicLanding($token) {
        $model = $this->scheduleModel();
        $schedule = $model->findByPublicToken((string) $token);
        if (!$schedule) {
            http_response_code(404);
            return $this->view('application_admission/public_not_found', [
                'use_public_layout' => true,
                'page' => 'public-admission',
            ]);
        }
        $tokenEsc = rawurlencode((string) $schedule['public_token']);
        $isInterview = $schedule['schedule_type'] === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $slipPath = $isInterview ? 'interview-slip' : 'admission-slip';
        return $this->view('application_admission/public', [
            'use_public_layout' => true,
            'page' => 'public-admission',
            'schedule' => $schedule,
            'token' => $schedule['public_token'],
            'pdfScheduleUrl' => APP_URL . '/application-admission/public/' . $tokenEsc . '/schedule-pdf',
            'pdfSelectionUrl' => $isInterview
                ? APP_URL . '/application-admission/public/' . $tokenEsc . '/selection-pdf'
                : null,
            'slipFormAction' => APP_URL . '/application-admission/public/' . $tokenEsc . '/' . $slipPath,
        ]);
    }

    public function publicPdfSchedule($token) {
        $model = $this->scheduleModel();
        $schedule = $model->findByPublicToken((string) $token);
        if (!$schedule) {
            http_response_code(404);
            echo 'Schedule not found or not published.';
            exit;
        }
        $this->streamSchedulePdf((int) $schedule['schedule_id'], false);
    }

    public function publicPdfSelection($token) {
        $model = $this->scheduleModel();
        $schedule = $model->findByPublicToken((string) $token);
        if (!$schedule || $schedule['schedule_type'] !== ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            http_response_code(404);
            echo 'Selection list not available.';
            exit;
        }
        $this->streamSelectionPdf((int) $schedule['schedule_id'], false);
    }

    public function publicAdmissionSlip($token) {
        $model = $this->scheduleModel();
        $schedule = $model->findByPublicToken((string) $token);
        if (!$schedule || $schedule['schedule_type'] !== ApplicationAdmissionScheduleModel::TYPE_ENTRANCE) {
            http_response_code(404);
            echo 'Admission slip not available.';
            exit;
        }
        $nic = trim((string) $this->post('nic', $this->get('nic', '')));
        $entry = $model->findEntryByNic((int) $schedule['schedule_id'], $nic);
        if (!$entry) {
            $_SESSION['error'] = 'No matching applicant on this schedule. Check your NIC and try again.';
            $this->redirect('application-admission/public/' . rawurlencode((string) $token));
        }
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('admission_slip.php', [
            'schedule' => $schedule,
            'entry' => $entry,
            'logo_src' => $this->admissionLogoDataUri(),
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument($inner);
        $name = 'entrance-admission-' . preg_replace('/[^0-9A-Za-z]+/', '', $nic) . '.pdf';
        ApplicationAdmissionPdfHelper::streamHtml($html, $name);
    }

    public function publicInterviewSlip($token) {
        $model = $this->scheduleModel();
        $schedule = $model->findByPublicToken((string) $token);
        if (!$schedule || $schedule['schedule_type'] !== ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            http_response_code(404);
            echo 'Interview slip not available.';
            exit;
        }
        $nic = trim((string) $this->post('nic', $this->get('nic', '')));
        $entry = $model->findEntryByNic((int) $schedule['schedule_id'], $nic);
        if (!$entry) {
            $_SESSION['error'] = 'No matching applicant on this interview schedule.';
            $this->redirect('application-admission/public/' . rawurlencode((string) $token));
        }
        $this->streamInterviewLetterPdf($schedule, $entry);
    }

    /**
     * Same interview invitation letter staff download from Applicants (postal card).
     *
     * @param array<string, mixed> $schedule
     * @param array<string, mixed> $entry
     */
    private function streamInterviewLetterPdf(array $schedule, array $entry): void {
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $entry['roll_number'] = '';
        $nic = preg_replace('/[^0-9A-Za-z]+/', '', ApplicationAdmissionScheduleModel::normalizedNic((string) ($entry['student_nic'] ?? '')));
        $scheduleId = (int) ($schedule['schedule_id'] ?? 0);
        $entryId = (int) ($entry['entry_id'] ?? 0);
        $html = ApplicationAdmissionPdfHelper::wrapPostalAdmissionCardsDocument(
            ApplicationAdmissionPdfHelper::renderTemplate('postal_admission_card.php', [
                'schedule' => $schedule,
                'entry' => $entry,
                'logo_src' => $this->admissionLogoDataUri(),
                'mailing' => $this->formatEntryMailingBlock($entry),
                'cardTitle' => 'INTERVIEW — ADMISSION CARD',
                'cardSubtitle' => (string) ($schedule['title'] ?? ''),
                'isInterview' => true,
                'principal_sig_src' => $this->principalSignatureDataUri(),
                'principal_name' => 'R. Mathaan',
                'interview_choice' => $this->interviewLetterChoice($schedule, $entry),
            ])
        );
        $filename = 'interview-letter-' . $scheduleId . '-' . ($nic !== '' ? $nic : (string) $entryId) . '.pdf';
        ApplicationAdmissionPdfHelper::streamHtml($html, $filename);
    }

    private function streamSchedulePdf(int $scheduleId, bool $allowUnpublishedForStaff): void {
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($scheduleId);
        if (!$schedule) {
            http_response_code(404);
            echo 'Not found.';
            exit;
        }
        if (!$allowUnpublishedForStaff && !(int) ($schedule['is_published'] ?? 0)) {
            http_response_code(404);
            echo 'Not published.';
            exit;
        }
        $entries = $model->getEntriesWithApplications($scheduleId);
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('schedule_list.php', [
            'schedule' => $schedule,
            'entries' => $entries,
            'logo_src' => $this->admissionLogoDataUri(),
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument($inner);
        $label = $schedule['schedule_type'] === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW ? 'interview' : 'entrance';
        ApplicationAdmissionPdfHelper::streamHtml($html, $label . '-schedule-' . $scheduleId . '.pdf', 'A4', 'landscape');
    }

    /**
     * @param list<string>|string|null $provinces
     */
    private function streamAttendancePdf(int $scheduleId, $provinces = null): void {
        $provinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($provinces);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($scheduleId);
        if (!$schedule) {
            throw new RuntimeException('Schedule not found.');
        }
        $entries = $model->getEntriesWithApplications($scheduleId);
        if ($provinces !== []) {
            $entries = array_values(array_filter($entries, static function (array $row) use ($provinces): bool {
                return ApplicationAdmissionScheduleModel::rowMatchesProvinceFilter($row, $provinces);
            }));
        }
        if ($entries === []) {
            throw new RuntimeException('No applicants to include on the attendance sheet.');
        }
        $isInterviewAttendance = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        if ($isInterviewAttendance) {
            usort($entries, static function (array $a, array $b): int {
                return strcasecmp(
                    (string) ($a['student_full_name'] ?? ''),
                    (string) ($b['student_full_name'] ?? '')
                );
            });
        } else {
            // Hall sheet must follow roll / index order (not course/province/name list order).
            usort($entries, static function (array $a, array $b): int {
                $rollA = trim((string) ($a['roll_number'] ?? ''));
                $rollB = trim((string) ($b['roll_number'] ?? ''));
                if ($rollA === '' && $rollB === '') {
                    return strcasecmp(
                        (string) ($a['student_full_name'] ?? ''),
                        (string) ($b['student_full_name'] ?? '')
                    );
                }
                if ($rollA === '') {
                    return 1;
                }
                if ($rollB === '') {
                    return -1;
                }
                $cmp = strnatcasecmp($rollA, $rollB);
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcasecmp(
                    (string) ($a['student_full_name'] ?? ''),
                    (string) ($b['student_full_name'] ?? '')
                );
            });
        }
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('attendance_sheet.php', [
            'schedule' => $schedule,
            'entries' => $entries,
            'logo_src' => $this->admissionLogoDataUri(),
            'province_filter_label' => $provinces !== []
                ? ApplicationAdmissionScheduleModel::provinceFilterLabel($provinces)
                : '',
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument($inner);
        $label = $schedule['schedule_type'] === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW ? 'interview' : 'entrance';
        $suffix = $provinces !== []
            ? '-' . preg_replace('/[^A-Za-z0-9]+/', '_', ApplicationAdmissionScheduleModel::provinceFilterLabel($provinces))
            : '';
        ApplicationAdmissionPdfHelper::streamHtml(
            $html,
            $label . '-attendance-' . $scheduleId . $suffix . '.pdf',
            'A4',
            'landscape'
        );
    }

    /**
     * Sort schedule applicants by roll / index, then name.
     *
     * @param list<array<string, mixed>> $entries
     * @return list<array<string, mixed>>
     */
    private function sortEntriesByRollNumber(array $entries): array {
        usort($entries, static function (array $a, array $b): int {
            $rollA = trim((string) ($a['roll_number'] ?? ''));
            $rollB = trim((string) ($b['roll_number'] ?? ''));
            if ($rollA === '' && $rollB === '') {
                return strcasecmp((string) ($a['student_full_name'] ?? ''), (string) ($b['student_full_name'] ?? ''));
            }
            if ($rollA === '') {
                return 1;
            }
            if ($rollB === '') {
                return -1;
            }
            $cmp = strnatcasecmp($rollA, $rollB);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['student_full_name'] ?? ''), (string) ($b['student_full_name'] ?? ''));
        });

        return $entries;
    }

    private function streamSelectionPdf(int $scheduleId, bool $allowUnpublishedForStaff): void {
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($scheduleId);
        if (!$schedule || !in_array($schedule['schedule_type'] ?? '', [
            ApplicationAdmissionScheduleModel::TYPE_INTERVIEW,
            ApplicationAdmissionScheduleModel::TYPE_ENTRANCE,
        ], true)) {
            http_response_code(404);
            echo 'Not found.';
            exit;
        }
        if (!$allowUnpublishedForStaff && !(int) ($schedule['is_published'] ?? 0)) {
            http_response_code(404);
            echo 'Not published.';
            exit;
        }
        $entries = $this->sortEntriesByRollNumber($model->getEntriesWithApplications($scheduleId));
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('selection_list.php', [
            'schedule' => $schedule,
            'entries' => $entries,
            'logo_src' => $this->admissionLogoDataUri(),
            'isEntranceResults' => ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE,
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument($inner);
        $pdfName = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE
            ? 'entrance-exam-results-' . $scheduleId . '.pdf'
            : 'selection-list-' . $scheduleId . '.pdf';
        ApplicationAdmissionPdfHelper::streamHtml($html, $pdfName, 'A4', 'landscape');
    }

    private function streamAdmissionCardsPdf(int $scheduleId, ?int $entryId, ?array $provinces = null): void {
        $provinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($provinces);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($scheduleId);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        $entries = ApplicationAdmissionScheduleModel::sortEntryRowsByCourseAndProvince(
            $model->getEntriesWithApplications($scheduleId)
        );
        $courseWiseRollSeq = ApplicationAdmissionScheduleModel::courseWiseSequenceMap($entries);
        if ($entryId !== null && $entryId > 0) {
            $entries = array_values(array_filter($entries, static function (array $row) use ($entryId): bool {
                return (int) ($row['entry_id'] ?? 0) === $entryId;
            }));
        } elseif ($provinces !== []) {
            $entries = array_values(array_filter($entries, static function (array $row) use ($provinces): bool {
                return ApplicationAdmissionScheduleModel::rowMatchesProvinceFilter($row, $provinces);
            }));
        }
        if ($entries === []) {
            $_SESSION['error'] = $entryId !== null && $entryId > 0
                ? 'Applicant not found on this schedule.'
                : 'No applicants on this schedule for admission cards.';
            $this->redirect($this->entriesRedirectUrl($scheduleId, $provinces));
        }

        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $cardTitle = $isInterview ? 'INTERVIEW — ADMISSION CARD' : 'SELECTION EXAMINATION — ADMISSION CARD';
        $principalSig = $isInterview ? $this->principalSignatureDataUri() : null;
        $parts = [];
        foreach ($entries as $entry) {
            if (!$isInterview) {
                $entryIdForRoll = (int) ($entry['entry_id'] ?? 0);
                $seq = $courseWiseRollSeq[$entryIdForRoll] ?? 1;
                $entry['roll_number'] = ApplicationAdmissionScheduleModel::defaultRollIndexForEntry($schedule, $entry, $seq);
            } else {
                $entry['roll_number'] = '';
            }
            $parts[] = ApplicationAdmissionPdfHelper::renderTemplate('postal_admission_card.php', [
                'schedule' => $schedule,
                'entry' => $entry,
                'logo_src' => $this->admissionLogoDataUri(),
                'mailing' => $this->formatEntryMailingBlock($entry),
                'cardTitle' => $cardTitle,
                'cardSubtitle' => (string) ($schedule['title'] ?? ''),
                'isInterview' => $isInterview,
                'principal_sig_src' => $principalSig,
                'principal_name' => 'R. Mathaan',
                'interview_choice' => $isInterview ? $this->interviewLetterChoice($schedule, $entry) : null,
            ]);
        }
        if ($entryId !== null && $entryId > 0) {
            $nic = preg_replace('/[^0-9A-Za-z]+/', '', (string) ($entries[0]['student_nic'] ?? ''));
            $filename = ($isInterview ? 'interview' : 'admission') . '-card-' . $scheduleId . '-' . ($nic !== '' ? $nic : (string) $entryId) . '.pdf';
            $html = ApplicationAdmissionPdfHelper::wrapPostalAdmissionCardsDocument(implode('', $parts));
            ApplicationAdmissionPdfHelper::streamHtml($html, $filename);
        }
        $suffix = $provinces !== []
            ? '-' . preg_replace('/[^A-Za-z0-9]+/', '_', ApplicationAdmissionScheduleModel::provinceFilterLabel($provinces))
            : '';
        $filename = ($isInterview ? 'interview' : 'admission') . '-cards-' . $scheduleId . $suffix . '.pdf';
        ApplicationAdmissionPdfHelper::streamPostalAdmissionCardsMerged($parts, $filename);
    }

    /**
     * @param list<string>|null $provinces
     */
    private function streamEnvelopesPdf(int $scheduleId, ?int $entryId, ?array $provinces = null): void {
        $provinces = ApplicationAdmissionScheduleModel::normalizedProvinceFilters($provinces);
        $model = $this->scheduleModel();
        $schedule = $model->findSchedule($scheduleId);
        if (!$schedule) {
            $_SESSION['error'] = 'Schedule not found.';
            $this->redirect('application-admission');
        }
        $entries = ApplicationAdmissionScheduleModel::sortEntryRowsByCourseAndProvince(
            $model->getEntriesWithApplications($scheduleId)
        );
        $courseWiseRollSeq = ApplicationAdmissionScheduleModel::courseWiseSequenceMap($entries);
        if ($entryId !== null && $entryId > 0) {
            $entries = array_values(array_filter($entries, static function (array $row) use ($entryId): bool {
                return (int) ($row['entry_id'] ?? 0) === $entryId;
            }));
        } elseif ($provinces !== []) {
            $entries = array_values(array_filter($entries, static function (array $row) use ($provinces): bool {
                return ApplicationAdmissionScheduleModel::rowMatchesProvinceFilter($row, $provinces);
            }));
        }
        if ($entries === []) {
            $_SESSION['error'] = $entryId !== null && $entryId > 0
                ? 'Applicant not found on this schedule.'
                : 'No applicants on this schedule for envelopes.';
            $this->redirect($this->entriesRedirectUrl($scheduleId, $provinces));
        }

        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $logoSrc = ApplicationAdmissionPdfHelper::grayscaleImageDataUri($this->admissionLogoDataUri());
        $parts = [];
        foreach ($entries as $entry) {
            if (!$isInterview) {
                $entryIdForRoll = (int) ($entry['entry_id'] ?? 0);
                $seq = $courseWiseRollSeq[$entryIdForRoll] ?? 1;
                $entry['roll_number'] = ApplicationAdmissionScheduleModel::defaultRollIndexForEntry($schedule, $entry, $seq);
            } else {
                $entry['roll_number'] = '';
            }
            $parts[] = ApplicationAdmissionPdfHelper::renderTemplate('envelope.php', [
                'schedule' => $schedule,
                'entry' => $entry,
                'logo_src' => $logoSrc,
                'mailing' => $this->formatEntryMailingBlock($entry),
                'isInterview' => $isInterview,
            ]);
        }
        if ($entryId !== null && $entryId > 0) {
            $nic = preg_replace('/[^0-9A-Za-z]+/', '', (string) ($entries[0]['student_nic'] ?? ''));
            $filename = 'envelope-' . $scheduleId . '-' . ($nic !== '' ? $nic : (string) $entryId) . '.pdf';
            ApplicationAdmissionPdfHelper::streamEnvelopesMerged($parts, $filename);

            return;
        }
        $suffix = $provinces !== []
            ? '-' . preg_replace('/[^A-Za-z0-9]+/', '_', ApplicationAdmissionScheduleModel::provinceFilterLabel($provinces))
            : '';
        ApplicationAdmissionPdfHelper::streamEnvelopesMerged($parts, 'envelopes-' . $scheduleId . $suffix . '.pdf');
    }

    /**
     * @param array<string, mixed> $entry
     * @return array{name: string, address: string, city_line: string, phone: string}
     */
    private function formatEntryMailingBlock(array $entry): array {
        $district = trim((string) ($entry['student_district'] ?? ''));
        $province = trim((string) ($entry['student_province'] ?? ''));
        $zip = trim((string) ($entry['student_zip_code'] ?? ''));
        $cityParts = array_values(array_filter([$district, $province, $zip], static function (string $part): bool {
            return $part !== '';
        }));

        return [
            'name' => trim((string) ($entry['student_full_name'] ?? '')),
            'address' => trim((string) ($entry['student_address'] ?? '')),
            'city_line' => implode(', ', $cityParts),
            'phone' => trim((string) ($entry['student_phone'] ?? '')),
        ];
    }

    /**
     * Course and 1st/2nd/3rd choice to print on the interview letter.
     *
     * @param array<string, mixed> $schedule
     * @param array<string, mixed> $entry
     * @return array{choice:int,choice_label:string,course_id:string,course_name:string,preferences:array{1:string,2:string,3:string}}
     */
    private function interviewLetterChoice(array $schedule, array $entry): array {
        require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
        require_once BASE_PATH . '/models/CourseModel.php';
        $cutoffModel = new ApplicationAdmissionCutoffModel();
        $level = (string) ($schedule['application_level'] ?? $entry['application_level'] ?? '04');
        if (!in_array($level, ['04', '05'], true)) {
            $level = '04';
        }
        $payload = [
            'choice' => 1,
            'choice_label' => ApplicationAdmissionCutoffModel::choiceOrdinal(1),
            'course_id' => '',
            'course_name' => '',
        ];
        $scheduleCid = trim((string) ($schedule['course_id'] ?? ''));
        if ($scheduleCid !== '') {
            $course = (new CourseModel())->find($scheduleCid);
            if (is_array($course)) {
                $rank = $cutoffModel->preferenceRankForCourse($entry, $scheduleCid, $course);
                $name = trim((string) ($course['course_name'] ?? $schedule['course_name'] ?? ''));
                if ($rank > 0 && $name !== '') {
                    $payload = [
                        'choice' => $rank,
                        'choice_label' => ApplicationAdmissionCutoffModel::choiceOrdinal($rank),
                        'course_id' => $scheduleCid,
                        'course_name' => $name,
                    ];
                    if ($rank === 2 && ApplicationAdmissionCutoffModel::isRestrictedSecondOptionCourse($course, $name, $level)) {
                        $eligible = $cutoffModel->eligibleChoiceForApplicant($entry, $level);
                        if (is_array($eligible) && (int) ($eligible['choice'] ?? 0) === 3) {
                            $payload = $eligible;
                        }
                    }
                }
            }
        }
        if ($payload['course_name'] === '') {
            $eligible = $cutoffModel->eligibleChoiceForApplicant($entry, $level);
            if (is_array($eligible) && trim((string) ($eligible['course_name'] ?? '')) !== '') {
                $payload = $eligible;
            }
        }
        if ($payload['course_name'] === '') {
            $fallbackName = '';
            if (class_exists('ApplicationAdmissionScheduleModel')) {
                $fallbackName = ApplicationAdmissionScheduleModel::courseNameFromEntry($entry);
            }
            if ($fallbackName === '') {
                $fallbackName = trim((string) ($entry['course_priority_1'] ?? $schedule['course_name'] ?? ''));
            }
            $payload['course_name'] = $fallbackName;
        }

        $prefs = ApplicationAdmissionCutoffModel::preferenceCourseNames($entry);
        $choice = (int) ($payload['choice'] ?? 0);
        $selectedName = trim((string) ($payload['course_name'] ?? ''));
        if ($choice >= 1 && $choice <= 3 && $selectedName !== '' && trim((string) ($prefs[$choice] ?? '')) === '') {
            $prefs[$choice] = $selectedName;
        }
        $payload['preferences'] = $prefs;

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validatedSchedulePost(): ?array {
        $type = $this->post('schedule_type', '');
        $level = $this->post('application_level', '');
        $title = trim((string) $this->post('title', ''));
        $date = trim((string) $this->post('schedule_date', ''));
        $venue = trim((string) $this->post('venue', ''));
        if (!in_array($type, [ApplicationAdmissionScheduleModel::TYPE_ENTRANCE, ApplicationAdmissionScheduleModel::TYPE_INTERVIEW], true)) {
            $_SESSION['error'] = 'Invalid schedule type.';
            return null;
        }
        if (!in_array($level, ['04', '05'], true)) {
            $_SESSION['error'] = 'Select NVQ Level 04 or 05.';
            return null;
        }
        $courseId = trim((string) $this->post('course_id', ''));
        $isInterview = $type === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        if ($isInterview && $courseId === '') {
            $_SESSION['error'] = 'Select the department course for this interview schedule.';
            return null;
        }
        if (!$isInterview && ($title === '' || $date === '' || $venue === '')) {
            $_SESSION['error'] = 'Title, date, and venue are required.';
            return null;
        }
        if ($isInterview && ($title === '' || $date === '')) {
            $_SESSION['error'] = 'Title and date are required.';
            return null;
        }
        $start = trim((string) $this->post('start_time', ''));
        $end = trim((string) $this->post('end_time', ''));
        if ($courseId !== '') {
            if (!$this->courseMatchesApplicationLevel($courseId, $level)) {
                $_SESSION['error'] = 'Selected course does not match the NVQ level.';
                return null;
            }
        }
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        if ($isInterview) {
            // Interview is department/course based — language & centre not required.
            $studentLanguage = null;
        } elseif ($level === '05') {
            // Level 05 exams are English medium for every applicant (no language filter).
            $studentLanguage = 'English';
        } else {
            $studentLanguage = StudentApplicationModel::normalizedStaffLanguageFilter($this->post('student_language', ''));
            if ($studentLanguage === null) {
                $_SESSION['error'] = 'Select the language of instruction for this schedule (Tamil, Sinhala, or English).';
                return null;
            }
        }
        $data = [
            'schedule_type' => $type,
            'application_level' => $level,
            'title' => $title,
            'schedule_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'venue' => $venue,
            'instructions' => trim((string) $this->post('instructions', '')),
            'student_language' => $studentLanguage,
        ];
        if ($courseId !== '') {
            $data['course_id'] = $courseId;
        } else {
            $data['course_id'] = '';
        }
        // Interviews always follow entrance exam Selected results (department/course wise).
        $data['admission_pathway'] = ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW;
        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formViewData(string $type, ?array $schedule, string $formAction): array {
        $selectedDepartmentId = '';
        if ($schedule !== null && !empty($schedule['course_id'])) {
            require_once BASE_PATH . '/models/CourseModel.php';
            $course = (new CourseModel())->find((string) $schedule['course_id']);
            if ($course) {
                $selectedDepartmentId = trim((string) ($course['department_id'] ?? ''));
            }
        }
        $isInterview = $type === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $isEntrance = $type === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        $exceptScheduleId = $schedule !== null ? (int) ($schedule['schedule_id'] ?? 0) : 0;
        $pathwayMax = ApplicationAdmissionScheduleModel::INTERVIEW_ONLY_DEFAULT_MAX_APPLICANTS;
        require_once BASE_PATH . '/models/StudentApplicationModel.php';

        $showCourseFields = $isInterview || ($isEntrance && $schedule !== null);
        $coursesByLevel = ['04' => [], '05' => []];
        if ($showCourseFields) {
            $coursesByLevel = [
                '04' => $this->coursesForApplicationLevel('04', $isEntrance, $exceptScheduleId > 0 ? $exceptScheduleId : null),
                '05' => $this->coursesForApplicationLevel('05', $isEntrance, $exceptScheduleId > 0 ? $exceptScheduleId : null),
            ];
            if ($isInterview) {
                $scheduleModel = $this->scheduleModel();
                foreach (['04', '05'] as $lvl) {
                    foreach ($coursesByLevel[$lvl] as &$courseRow) {
                        $cid = trim((string) ($courseRow['course_id'] ?? ''));
                        $courseRow['selected_entrance_count'] = $cid !== ''
                            ? count($scheduleModel->getPassedEntranceApplicationIds($lvl, $cid))
                            : 0;
                    }
                    unset($courseRow);
                }
            }
        }

        return [
            'page' => $isInterview ? 'application-admission-interview' : 'application-admission-entrance',
            'schedule' => $schedule,
            'scheduleType' => $type,
            'formAction' => $formAction,
            'coursesByLevel' => $coursesByLevel,
            'departmentsByLevel' => $showCourseFields ? [
                '04' => $this->departmentsForApplicationLevel('04'),
                '05' => $this->departmentsForApplicationLevel('05'),
            ] : ['04' => [], '05' => []],
            'selectedDepartmentId' => $selectedDepartmentId,
            'requireCourse' => $isInterview,
            'showCourseFields' => $showCourseFields,
            'isInterviewSchedule' => $isInterview,
            'pathwayDefaultMaxApplicants' => $pathwayMax,
            'selectedPathway' => ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW,
            'languageOptions' => StudentApplicationModel::STAFF_LANGUAGE_FILTER_VALUES,
            'selectedStudentLanguage' => StudentApplicationModel::normalizedStaffLanguageFilter(
                $schedule['student_language'] ?? null
            ) ?? '',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function departmentsForApplicationLevel(string $applicationLevel): array {
        if (!in_array($applicationLevel, ['04', '05'], true)) {
            return [];
        }
        $nvq = $applicationLevel === '05' ? '5' : '4';
        require_once BASE_PATH . '/models/DepartmentModel.php';
        return (new DepartmentModel())->getDepartmentsWithNvqCourses($nvq);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function coursesForApplicationLevel(
        string $applicationLevel,
        bool $excludeAlreadyOnEntranceExam = false,
        ?int $exceptEntranceScheduleId = null
    ): array {
        if (!in_array($applicationLevel, ['04', '05'], true)) {
            return [];
        }
        $nvq = $applicationLevel === '05' ? '5' : '4';
        require_once BASE_PATH . '/models/CourseModel.php';
        $courses = (new CourseModel())->getCoursesWithDepartment([
            'nvq_level' => $nvq,
            'active_only' => true,
        ]);
        $scheduleModel = $this->scheduleModel();
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        $languageOptions = StudentApplicationModel::STAFF_LANGUAGE_FILTER_VALUES;
        $onEntranceByCourse = $excludeAlreadyOnEntranceExam
            ? $scheduleModel->entranceScheduledApplicationIdsByCourse($applicationLevel, $exceptEntranceScheduleId)
            : [];
        foreach ($courses as &$course) {
            $cid = trim((string) ($course['course_id'] ?? ''));
            $excludeIds = ($cid !== '' && isset($onEntranceByCourse[$cid])) ? $onEntranceByCourse[$cid] : null;
            $countsByLang = [];
            foreach ($languageOptions as $lang) {
                $countsByLang[$lang] = $cid !== ''
                    ? $scheduleModel->countApprovedApplicationsForCourse($applicationLevel, $cid, $lang, $excludeIds)
                    : 0;
            }
            $course['approved_counts_by_language'] = $countsByLang;
            $course['approved_application_count'] = $cid !== ''
                ? array_sum($countsByLang)
                : 0;
        }
        unset($course);

        return $courses;
    }

    private function courseMatchesApplicationLevel(string $courseId, string $applicationLevel): bool {
        require_once BASE_PATH . '/models/CourseModel.php';
        $course = (new CourseModel())->find($courseId);
        if (!$course) {
            return false;
        }
        $nvq = trim((string) ($course['course_nvq_level'] ?? ''));
        $expected = $applicationLevel === '05' ? '5' : '4';

        return $nvq === $expected;
    }

    /**
     * @param array<string, mixed> $schedule
     */
    private function schedulePickerHint(array $schedule): string {
        if (($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE) {
            $courseId = $this->scheduleCourseIdOrNull($schedule);
            $level = trim((string) ($schedule['application_level'] ?? ''));
            $alreadyAssigned = 'Applicants already assigned to an entrance exam are not listed.';
            if ($level === '05') {
                if ($courseId === null) {
                    return 'Level 05 English medium: loading all approved and rejected applicants (every application language). Course filter is optional — set a course on Edit schedule only if you want 1st-preference filtering. ' . $alreadyAssigned;
                }

                return 'Level 05 English medium: all approved and rejected applicants with matching 1st preference (every application language). ' . $alreadyAssigned;
            }
            if ($courseId === null) {
                $lang = trim((string) ($schedule['student_language'] ?? ''));
                $hint = 'Level-only schedule: loading approved and rejected applicants for NVQ '
                    . ($level !== '' ? $level : 'level');
                if ($lang !== '') {
                    $hint .= ' (' . $lang . ' medium)';
                }
                $hint .= '. Course filter is optional — set a course on Edit schedule only if you want 1st-preference filtering. ' . $alreadyAssigned;

                return $hint;
            }
            $lang = trim((string) ($schedule['student_language'] ?? ''));
            if ($lang !== '') {
                return 'Approved and rejected ' . $lang . ' applicants (1st preference match). ' . $alreadyAssigned;
            }

            return 'Approved and rejected applicants with matching 1st preference. ' . $alreadyAssigned;
        }

        return $this->interviewPickerHint($schedule);
    }

    /**
     * @param array<string, mixed> $schedule
     */
    private function interviewPickerHint(array $schedule): string {
        if (($schedule['schedule_type'] ?? '') !== ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            return '';
        }
        $courseId = $this->scheduleCourseIdOrNull($schedule);
        if ($courseId === null) {
            return 'Select a department course. Candidates who meet the cutoff (1st choice) or who are eligible as 2nd/3rd option can be added.';
        }
        $level = (string) ($schedule['application_level'] ?? '');
        require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
        $eligible = (new ApplicationAdmissionCutoffModel())->interviewEligibleForCourse($level, $courseId);
        $already = $this->scheduleModel()->interviewScheduledApplicationIds(
            $level,
            (int) ($schedule['schedule_id'] ?? 0)
        );
        $selectedCount = 0;
        foreach ($eligible as $appId => $_row) {
            if (!isset($already[(int) $appId])) {
                $selectedCount++;
            }
        }
        if (!$this->scheduleModel()->hasEntranceScheduleForCourse($level, $courseId)) {
            return 'No entrance exam found for this level. Create an entrance exam, enter marks, and set cutoffs first.';
        }
        if ($selectedCount === 0) {
            return 'No cutoff-eligible candidates left to add for this course. Students already assigned to an interview are not listed.';
        }

        return 'Listed by exam marks: students who met this course cutoff (1st choice) or who are eligible as 2nd/3rd option (' . $selectedCount . ' eligible). Applicants already assigned to an interview are not listed.';
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<int, array<string, mixed>> $details
     * @return list<array<string, mixed>>
     */
    private function mergeInterviewCutoffDetails(array $rows, array $details): array {
        foreach ($rows as &$row) {
            $aid = (int) ($row['application_id'] ?? 0);
            if ($aid > 0 && isset($details[$aid])) {
                $row['exam_marks_num'] = $details[$aid]['exam_marks_num'];
                $row['cutoff_applied'] = $details[$aid]['cutoff_applied'];
                $row['interview_choice'] = $details[$aid]['choice'];
                $row['interview_choice_label'] = $details[$aid]['choice_label'];
                $row['interview_course_name'] = $details[$aid]['course_name'];
            }
        }
        unset($row);
        usort($rows, static function (array $a, array $b): int {
            $ma = isset($a['exam_marks_num']) ? (float) $a['exam_marks_num'] : -1.0;
            $mb = isset($b['exam_marks_num']) ? (float) $b['exam_marks_num'] : -1.0;
            $cmp = $mb <=> $ma;
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['student_full_name'] ?? ''), (string) ($b['student_full_name'] ?? ''));
        });

        return $rows;
    }

    /**
     * @param array<string, mixed> $schedule
     */
    private function scheduleCourseIdOrNull(array $schedule): ?string {
        $cid = trim((string) ($schedule['course_id'] ?? ''));
        return $cid !== '' ? $cid : null;
    }

    private function entriesRedirectUrl(int $scheduleId, $provinces = null): string {
        $url = 'application-admission/entries?id=' . $scheduleId;
        foreach (ApplicationAdmissionScheduleModel::normalizedProvinceFilters($provinces) as $province) {
            $url .= '&province[]=' . rawurlencode($province);
        }

        return $url;
    }

    /**
     * Assign roll numbers only where empty: for each department prefix, read the highest
     * existing serial and give new applicants the next numbers in sort order.
     */
    private function assignSequentialRollNumbersWhereEmpty(ApplicationAdmissionScheduleModel $model, int $scheduleId, array $schedule): void {
        if (($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW) {
            return;
        }
        $entries = ApplicationAdmissionScheduleModel::sortEntryRowsByCourseAndProvince(
            $model->getEntriesWithApplications($scheduleId)
        );
        $maxByPrefix = [];
        foreach ($entries as $entry) {
            $roll = trim((string) ($entry['roll_number'] ?? ''));
            if ($roll === '') {
                continue;
            }
            $prefix = ApplicationAdmissionScheduleModel::rollNumberPrefixForEntry($schedule, $entry);
            $serial = ApplicationAdmissionScheduleModel::serialFromRollNumber($roll);
            if ($serial === null) {
                continue;
            }
            // Prefer serials that match this applicant's prefix; still count same trailing serial
            // if the stored roll uses the expected prefix.
            if (strncasecmp($roll, $prefix . '/', strlen($prefix) + 1) !== 0) {
                continue;
            }
            $maxByPrefix[$prefix] = max($maxByPrefix[$prefix] ?? 0, $serial);
        }

        foreach ($entries as $entry) {
            $entryId = (int) ($entry['entry_id'] ?? 0);
            if ($entryId <= 0) {
                continue;
            }
            if (trim((string) ($entry['roll_number'] ?? '')) !== '') {
                continue;
            }
            $prefix = ApplicationAdmissionScheduleModel::rollNumberPrefixForEntry($schedule, $entry);
            $next = ($maxByPrefix[$prefix] ?? 0) + 1;
            $maxByPrefix[$prefix] = $next;
            $model->updateEntry($entryId, $scheduleId, [
                'roll_number' => ApplicationAdmissionScheduleModel::formatRollNumberForEntry($schedule, $entry, $next),
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return list<array<string, mixed>>
     */
    private function buildWhatsAppRecipients(array $schedule, array $entries, string $publicUrl, ?array $courseWiseRollSeq = null): array {
        require_once BASE_PATH . '/models/StudentModel.php';
        $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $downloadUrls = $this->publicScheduleDownloadUrls($schedule);
        if ($courseWiseRollSeq === null) {
            $courseWiseRollSeq = ApplicationAdmissionScheduleModel::courseWiseSequenceMap($entries);
        }
        $recipients = [];
        foreach ($entries as $row) {
            $entryId = (int) ($row['entry_id'] ?? 0);
            $seq = $courseWiseRollSeq[$entryId] ?? 1;
            $roll = $isInterview
                ? ''
                : ApplicationAdmissionScheduleModel::defaultRollIndexForEntry($schedule, $row, $seq);
            $digits = StudentModel::digitsForWhatsAppMe($row);
            $displayPhone = trim((string) ($row['student_whatsapp'] ?? ''));
            if ($displayPhone === '') {
                $displayPhone = trim((string) ($row['student_phone'] ?? ''));
            }
            $message = $this->whatsappScheduleMessage($schedule, $publicUrl, $row, $roll, $isInterview, $downloadUrls);
            $recipients[] = [
                'entry_id' => (int) ($row['entry_id'] ?? 0),
                'name' => (string) ($row['student_full_name'] ?? ''),
                'nic' => (string) ($row['student_nic'] ?? ''),
                'roll' => $roll,
                'digits' => $digits,
                'display_phone' => $displayPhone,
                'has_phone' => $digits !== null,
                'url' => $digits !== null ? 'https://wa.me/' . $digits . '?text=' . rawurlencode($message) : null,
            ];
        }

        return $recipients;
    }

    /**
     * Public PDF / slip URLs for a published schedule (token-based).
     *
     * @param array<string, mixed> $schedule
     * @return array{schedule_pdf: string, slip_base: string, interview_letter: string}
     */
    private function publicScheduleDownloadUrls(array $schedule): array {
        $tokenEsc = rawurlencode((string) ($schedule['public_token'] ?? ''));
        $base = rtrim(APP_URL, '/') . '/application-admission/public/' . $tokenEsc;
        $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $slipSeg = $isInterview ? 'interview-slip' : 'admission-slip';

        return [
            'schedule_pdf' => $base . '/schedule-pdf',
            'slip_base' => $base . '/' . $slipSeg,
            'interview_letter' => rtrim(APP_URL, '/') . '/application-admission/interview-letter',
        ];
    }

    /**
     * @param array<string, mixed> $schedule
     * @param array<string, mixed> $entry
     * @param array{schedule_pdf: string, slip_base: string} $downloadUrls
     */
    private function whatsappScheduleMessage(
        array $schedule,
        string $publicUrl,
        array $entry,
        string $roll,
        bool $isInterview,
        array $downloadUrls
    ): string {
        $name = trim((string) ($entry['student_full_name'] ?? ''));
        $greeting = $name !== '' ? "Dear {$name},\n\n" : '';
        $title = trim((string) ($schedule['title'] ?? 'SLGTI schedule'));
        $date = trim((string) ($schedule['schedule_date'] ?? ''));
        $venue = trim((string) ($schedule['venue'] ?? ''));
        $course = trim((string) ($schedule['course_name'] ?? ''));
        $nic = trim((string) ($entry['student_nic'] ?? ''));
        $slipLink = $downloadUrls['slip_base'];
        if ($nic !== '') {
            $slipLink .= '?nic=' . rawurlencode($nic);
        }
        $lines = [$greeting];
        if ($isInterview) {
            $lines[] = 'Your interview schedule at Sri Lanka German Training Institute (SLGTI) is published.';
        } else {
            $lines[] = 'Your entrance examination schedule at Sri Lanka German Training Institute (SLGTI) is published.';
        }
        $lines[] = '';
        $lines[] = $title;
        if ($course !== '') {
            $lines[] = 'Course: ' . $course;
        }
        if ($date !== '') {
            $lines[] = 'Date: ' . $date;
        }
        if ($venue !== '') {
            $lines[] = 'Venue: ' . $venue;
        }
        if (!$isInterview && $roll !== '') {
            $lines[] = 'Index / Roll no.: ' . $roll;
        }
        $lines[] = '';
        $lines[] = 'Download full schedule (PDF):';
        $lines[] = $downloadUrls['schedule_pdf'];
        $lines[] = '';
        if ($isInterview) {
            $letterLink = (string) ($downloadUrls['interview_letter'] ?? '');
            if ($letterLink !== '') {
                $lines[] = 'Download your interview letter (enter your NIC):';
                $lines[] = $letterLink;
                $lines[] = '';
            }
        }
        $lines[] = 'Download your personal slip (PDF):';
        $lines[] = $slipLink;
        $lines[] = '';
        $lines[] = 'Or open this page for all download options (enter NIC if needed):';
        $lines[] = $publicUrl;
        $lines[] = '';
        $lines[] = '— SLGTI Student Affairs';

        return implode("\n", $lines);
    }
}
