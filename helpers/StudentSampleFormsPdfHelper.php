<?php
/**
 * Student Application Form PDF — filled from logged-in student data.
 */
declare(strict_types=1);

require_once BASE_PATH . '/helpers/ExamPdfHelper.php';
require_once BASE_PATH . '/helpers/ComplaintLetterPdfHelper.php';

class StudentSampleFormsPdfHelper {

    /**
     * @param array<string, mixed> $student
     * @param array<string, mixed>|null $enrollment
     * @param array<string, mixed>|null $application
     */
    public static function streamStudentApplicationBlank(
        bool $attachment = true,
        array $student = [],
        ?array $enrollment = null,
        ?array $application = null
    ): void {
        if (!ExamPdfHelper::dompdfAvailable()) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'PDF download is not available. Please contact the institute.';
            exit;
        }

        $html = self::renderStudentApplicationBlankHtml($student, $enrollment, $application);
        ExamPdfHelper::loadDompdf();
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        if (defined('BASE_PATH')) {
            $options->setChroot(BASE_PATH);
        }
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $sid = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($student['student_id'] ?? 'student'));
        $filename = 'SLGTI_Student_Application_Form_' . $sid . '.pdf';
        $dompdf->stream($filename, ['Attachment' => $attachment]);
        exit;
    }

    /**
     * @param array<string, mixed> $student
     * @param array<string, mixed>|null $enrollment
     * @param array<string, mixed>|null $application
     */
    public static function renderStudentApplicationBlankHtml(
        array $student = [],
        ?array $enrollment = null,
        ?array $application = null
    ): string {
        $path = BASE_PATH . '/views/student/pdf/application_form_blank.php';
        if (!is_file($path)) {
            throw new RuntimeException('Application form PDF template not found.');
        }
        $logoSrc = ComplaintLetterPdfHelper::logoDataUri();
        $institute = ComplaintLetterPdfHelper::institutePostFrom();
        $year = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y');
        $sample = $student !== []
            ? self::formDataFromLoggedInStudent($student, $enrollment, $application, $year)
            : self::sampleApplicationData($year);
        extract([
            'logoSrc' => $logoSrc,
            'institute' => $institute,
            'year' => $year,
            'sample' => $sample,
        ], EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }

    /**
     * Map logged-in student (+ enrollment / original application) into PDF sample keys.
     *
     * @param array<string, mixed> $student
     * @param array<string, mixed>|null $enrollment
     * @param array<string, mixed>|null $application
     * @return array<string, mixed>
     */
    public static function formDataFromLoggedInStudent(
        array $student,
        ?array $enrollment,
        ?array $application,
        string $year
    ): array {
        $t = static function ($v): string {
            return trim((string) ($v ?? ''));
        };

        $studentId = $t($student['student_id'] ?? '');
        $titleRaw = $t($student['student_title'] ?? '');
        $title = self::normalizeTitle($titleRaw);

        $genderRaw = $t($student['student_gender'] ?? '');
        $gender = self::normalizeChoice($genderRaw, ['Male', 'Female', 'Other'], 'Male');

        $civilRaw = $t($student['student_civil'] ?? ($student['student_civil_status'] ?? ''));
        $civil = self::normalizeChoice($civilRaw, ['Single', 'Married'], 'Single');

        $langRaw = $t($student['student_language'] ?? ($application['student_language'] ?? ''));
        $language = self::normalizeChoice($langRaw, ['Tamil', 'Sinhala', 'English'], 'Tamil');

        $dob = self::formatDateDmY($t($student['student_dob'] ?? ''));
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('d/m/Y');

        $courseModeRaw = $t($enrollment['course_mode'] ?? '');
        $mode = self::normalizeCourseMode($courseModeRaw);

        $dept = $t($enrollment['department_name'] ?? '');
        $course = $t($enrollment['course_name'] ?? '');

        $nvq = $t($enrollment['course_nvq_level'] ?? '');
        if ($nvq === '' && is_array($application)) {
            $nvq = $t($application['application_level'] ?? '');
        }
        $level04 = in_array($nvq, ['4', '04', 'Level 04', 'LEVEL 04'], true)
            || stripos($course, '04') !== false;
        $level05 = in_array($nvq, ['5', '05', 'Level 05', 'LEVEL 05'], true)
            || stripos($course, '05') !== false;
        if (!$level04 && !$level05) {
            $level04 = true;
        }

        $olIndex = '';
        $olYear = '';
        $olSubjects = array_fill(0, 9, ['subject' => '', 'grade' => '', 'year' => '']);
        $alIndex = '';
        $alYear = '';
        $alStream = '';
        $alResults = '';

        if (is_array($application) && $application !== []) {
            if (!$level04 && !$level05) {
                $appLevel = $t($application['application_level'] ?? '');
                $level04 = ($appLevel === '04');
                $level05 = ($appLevel === '05');
            }

            $olIndex = $t($application['ol_index_number'] ?? '');
            $olYear = $t($application['ol_exam_year'] ?? '');
            for ($i = 1; $i <= 9; $i++) {
                $pad = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $olSubjects[$i - 1] = [
                    'subject' => $t($application['ol_subject_name_' . $pad] ?? ''),
                    'grade' => $t($application['ol_subject_' . $pad . '_marks'] ?? ''),
                    'year' => $olYear,
                ];
            }

            $alIndex = $t($application['al_index_number'] ?? '');
            $alYear = $t($application['al_exam_year'] ?? '');
            $alStream = $t($application['al_stream'] ?? '');
            $alParts = [];
            for ($i = 1; $i <= 3; $i++) {
                $pad = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $sub = $t($application['al_subject_name_' . $pad] ?? '');
                $gr = $t($application['al_subject_' . $pad . '_marks'] ?? '');
                if ($sub !== '' || $gr !== '') {
                    $alParts[] = trim($sub . ($gr !== '' ? ' — ' . $gr : ''));
                }
            }
            $nvqLvl = $t($application['nvq_level'] ?? '');
            $nvqCourse = $t($application['nvq_course_name'] ?? '');
            if ($nvqLvl !== '' || $nvqCourse !== '') {
                $alStream = trim(($alStream !== '' ? $alStream . ' / ' : '') . 'NVQ ' . $nvqLvl . ($nvqCourse !== '' ? ' — ' . $nvqCourse : ''));
            }
            $alResults = implode('; ', $alParts);
        }

        return [
            'student_id' => $studentId,
            'student_regnumber' => $studentId,
            'date' => $today,
            'level_04' => $level04,
            'level_05' => $level05,
            'title' => $title,
            'full_name' => $t($student['student_fullname'] ?? ''),
            'name_initials' => $t($student['student_ininame'] ?? ''),
            'nic' => $t($student['student_nic'] ?? ''),
            'dob' => $dob,
            'gender' => $gender,
            'civil' => $civil,
            'language' => $language,
            'religion' => $t($student['student_religion'] ?? ($application['student_religion'] ?? '')),
            'blood' => $t($student['student_blood'] ?? ($application['student_blood_group'] ?? '')),
            'nationality' => $t($student['student_nationality'] ?? 'Sri Lankan'),
            'phone' => $t($student['student_phone'] ?? ''),
            'whatsapp' => $t($student['student_whatsapp'] ?? ($student['student_phone'] ?? '')),
            'email' => $t($student['student_email'] ?? ''),
            'address' => $t($student['student_address'] ?? ''),
            'province' => $t($student['student_provice'] ?? ($student['student_province'] ?? ($application['student_province'] ?? ''))),
            'district' => $t($student['student_district'] ?? ''),
            'postal' => $t($student['student_zip'] ?? ($application['student_zip_code'] ?? '')),
            'gn' => $t($student['student_divisions'] ?? ''),
            'guardian_name' => $t($student['student_em_name'] ?? ''),
            'guardian_rel' => $t($student['student_em_relation'] ?? ''),
            'guardian_phone' => $t($student['student_em_phone'] ?? ''),
            'guardian_job' => $t($student['student_em_occupation'] ?? ($student['student_em_job'] ?? '')),
            'department' => $dept,
            'course' => $course,
            'mode' => $mode,
            'ol_index' => $olIndex,
            'ol_year' => $olYear,
            'ol_subjects' => $olSubjects,
            'al_index' => $alIndex,
            'al_year' => $alYear,
            'al_stream' => $alStream,
            'al_results' => $alResults,
        ];
    }

    /**
     * Try load original online application by student NIC (Level 05 then 04).
     *
     * @return array<string, mixed>|null
     */
    public static function findApplicationForStudent(array $student): ?array {
        $nic = strtoupper(preg_replace('/\s+|-|_/', '', trim((string) ($student['student_nic'] ?? ''))));
        if ($nic === '' || !preg_match('/^(\d{9}[VX]|\d{12})$/', $nic)) {
            return null;
        }
        try {
            require_once BASE_PATH . '/models/StudentApplicationModel.php';
            $appModel = new StudentApplicationModel();
            foreach (['05', '04'] as $level) {
                $row = $appModel->findByNicAndLevel($nic, $level);
                if (is_array($row) && $row !== []) {
                    return $row;
                }
            }
        } catch (Throwable $e) {
            return null;
        }
        return null;
    }

    private static function normalizeTitle(string $raw): string {
        $r = strtolower(rtrim(trim($raw), '.'));
        if ($r === 'mr') {
            return 'Mr';
        }
        if ($r === 'ms' || $r === 'miss') {
            return 'Ms';
        }
        if ($r === 'mrs') {
            return 'Mrs';
        }
        if ($r === '') {
            return 'Mr';
        }
        return 'Other';
    }

    /**
     * @param list<string> $allowed
     */
    private static function normalizeChoice(string $raw, array $allowed, string $default): string {
        $r = trim($raw);
        foreach ($allowed as $a) {
            if (strcasecmp($r, $a) === 0) {
                return $a;
            }
        }
        return $default;
    }

    private static function normalizeCourseMode(string $raw): string {
        $r = strtolower(trim($raw));
        if ($r === '' || str_contains($r, 'full')) {
            return 'Full Time';
        }
        if (str_contains($r, 'part')) {
            return 'Part Time';
        }
        return 'Full Time';
    }

    private static function formatDateDmY(string $raw): string {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
            return $raw;
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return $raw;
        }
        return date('d/m/Y', $ts);
    }

    /**
     * Fallback empty form data (only when student record missing).
     *
     * @return array<string, mixed>
     */
    public static function sampleApplicationData(string $year): array {
        return [
            'student_id' => '',
            'student_regnumber' => '',
            'date' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('d/m/Y'),
            'level_04' => false,
            'level_05' => false,
            'title' => 'Mr',
            'full_name' => '',
            'name_initials' => '',
            'nic' => '',
            'dob' => '',
            'gender' => 'Male',
            'civil' => 'Single',
            'language' => 'Tamil',
            'religion' => '',
            'blood' => '',
            'nationality' => 'SRI LANKAN',
            'phone' => '',
            'whatsapp' => '',
            'email' => '',
            'address' => '',
            'province' => '',
            'district' => '',
            'postal' => '',
            'gn' => '',
            'guardian_name' => '',
            'guardian_rel' => '',
            'guardian_phone' => '',
            'guardian_job' => '',
            'department' => '',
            'course' => '',
            'mode' => 'Full Time',
            'ol_index' => '',
            'ol_year' => '',
            'ol_subjects' => array_fill(0, 9, ['subject' => '', 'grade' => '', 'year' => '']),
            'al_index' => '',
            'al_year' => '',
            'al_stream' => '',
            'al_results' => '',
        ];
    }
}
