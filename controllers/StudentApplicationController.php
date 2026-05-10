<?php
/**
 * Public student applications (NVQ Level 04 / 05) — no login required.
 */

class StudentApplicationController extends Controller {

    private const UPLOAD_MAX_BYTES = 5242880; // 5 MB (incoming)
    /** Stored files are compressed to JPEG at or below this size. */
    private const STORED_MAX_BYTES = 102400; // 100 KB
    private const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png'];
    /** CSRF token lifetime (seconds); long enough to fill the form without relying on PHP session. */
    private const STUDENT_APP_CSRF_TTL = 604800; // 7 days

    /** Whitelist: staff download only these DB columns (paths under uploads/student_applications/). Keep in sync with StudentApplicationModel::DOCUMENT_PATH_COLUMNS. */
    private const STAFF_DOWNLOAD_DOCUMENT_COLUMNS = [
        'nic_document_path',
        'birth_certificate_path',
        'ol_certificate_path',
        'al_certificate_path',
        'nvq_certificate_path',
        'bank_receipt_path',
    ];

    /**
     * NIC folder segment (uploads/student_applications/{NIC}/) — digits + V/X only.
     */
    private function nicFolderSegment(string $nic): string {
        $nic = strtoupper(preg_replace('/[^0-9VX]/', '', $nic));
        return $nic !== '' ? $nic : 'unknown';
    }

    /**
     * Recursively delete a directory only if it lives under uploads/student_applications/.
     */
    private function safeDeleteStudentApplicationUploadDir(string $nic): bool {
        $root = realpath(BASE_PATH);
        if ($root === false) {
            return false;
        }
        $uploadsBase = realpath($root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'student_applications');
        if ($uploadsBase === false) {
            // Nothing to delete if uploads root doesn't exist.
            return true;
        }

        $seg = $this->nicFolderSegment($nic);
        $target = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'student_applications' . DIRECTORY_SEPARATOR . $seg;
        $targetReal = realpath($target);
        if ($targetReal === false) {
            return true;
        }

        $uploadsPrefix = rtrim($uploadsBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($targetReal, $uploadsPrefix) !== 0) {
            return false;
        }
        if (!is_dir($targetReal)) {
            return true;
        }

        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($targetReal, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $fs) {
                /** @var SplFileInfo $fs */
                $p = $fs->getPathname();
                if ($fs->isDir() && !$fs->isLink()) {
                    @rmdir($p);
                } else {
                    @unlink($p);
                }
            }
            @rmdir($targetReal);
        } catch (Throwable $e) {
            error_log('StudentApplicationController::safeDeleteStudentApplicationUploadDir: ' . $e->getMessage());
            return false;
        }

        return !is_dir($targetReal);
    }

    /**
     * Signing key for public application form (no session storage).
     */
    private function studentApplicationCsrfKey(): string {
        return hash('sha256', 'SLGTI|student_app_csrf_v1|' . DB_NAME . '|' . DB_USER . '|' . DB_PASS, true);
    }

    private function generateStudentApplicationCsrfToken(): string {
        $exp = time() + self::STUDENT_APP_CSRF_TTL;
        $nonce = bin2hex(random_bytes(16));
        $payload = $exp . '|' . $nonce;
        $mac = hash_hmac('sha256', $payload, $this->studentApplicationCsrfKey());
        return base64_encode($payload . '|' . $mac);
    }

    private function verifyStudentApplicationCsrfToken(string $token): bool {
        $raw = base64_decode(trim($token), true);
        if ($raw === false || $raw === '') {
            return false;
        }
        $lastSep = strrpos($raw, '|');
        if ($lastSep === false || $lastSep < 1) {
            return false;
        }
        $mac = substr($raw, $lastSep + 1);
        $payload = substr($raw, 0, $lastSep);
        if ($mac === '' || $payload === '' || strlen($mac) !== 64) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, $this->studentApplicationCsrfKey());
        if (!hash_equals($expected, $mac)) {
            return false;
        }
        $parts = explode('|', $payload);
        if (count($parts) !== 2) {
            return false;
        }
        $exp = (int) $parts[0];
        return $exp >= time();
    }

    public function level04() {
        return $this->showForm('04');
    }

    public function level05() {
        return $this->showForm('05');
    }

    private function showForm(string $level) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->processSubmit($level);
        }
        $flash = null;
        if ($this->get('submitted') === '1' && !empty($_SESSION['flash_student_application_ok'])) {
            $flash = $_SESSION['flash_student_application_ok'];
            unset($_SESSION['flash_student_application_ok']);
        }
        return $this->renderForm($level, [], [], $flash);
    }

    /**
     * On validation/upload errors while updating, merge stored document paths into `old` so the wizard can show "Current file" hints.
     *
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function oldPostWithStudentApplicationDocPaths(string $level, array $post): array {
        $aid = (int) ($post['application_id'] ?? 0);
        if ($aid < 1) {
            return $post;
        }
        $model = $this->model('StudentApplicationModel');
        $row = $model->findById($aid);
        if (!$row || trim((string) ($row['application_level'] ?? '')) !== $level) {
            return $post;
        }
        $postedNic = $this->normalizeNic((string) ($post['student_nic'] ?? ''));
        if (trim((string) ($row['student_nic'] ?? '')) !== $postedNic) {
            return $post;
        }
        $out = $post;
        $st = trim((string) ($row['status'] ?? ''));
        if ($st !== '') {
            $out['application_workflow_status'] = $st;
        }
        foreach (StudentApplicationModel::DOCUMENT_PATH_COLUMNS as $col) {
            $v = trim((string) ($row[$col] ?? ''));
            if ($v !== '') {
                $out[$col] = $v;
            }
        }
        return $out;
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed> $old
     */
    private function renderForm(string $level, array $errors, array $old, ?string $flashSuccess = null) {
        $title = $level === '04' ? 'Level 04 application' : 'Level 05 application';
        $view = $level === '04' ? 'student_application/form_wizard' : 'student_application/form';
        return $this->view($view, [
            'title' => $title,
            'use_public_layout' => true,
            'application_level' => $level,
            'csrf_token' => $this->generateStudentApplicationCsrfToken(),
            'errors' => $errors,
            'old' => $old,
            'flash_success' => $flashSuccess,
            'sl_provinces_districts' => $this->getProvinceDistrictMap(),
            'sl_district_postal_codes' => $this->getDistrictPostalMap(),
        ]);
    }

    private function processSubmit(string $level) {
        $token = (string) $this->post('csrf_token', '');
        if (!$this->verifyStudentApplicationCsrfToken($token)) {
            return $this->renderForm($level, ['This form is out of date or the security check failed. Please refresh the page and send again.'], $_POST, null);
        }

        $postedLevel = (string) $this->post('application_level', '');
        if ($postedLevel !== $level) {
            return $this->renderForm($level, ['Form level does not match. Please open the form again from the website.'], $_POST, null);
        }

        $model = $this->model('StudentApplicationModel');
        $postedNicBlock = $this->normalizeNic((string) $this->post('student_nic', ''));
        if ($this->isValidNic($postedNicBlock)) {
            $cross = $this->studentApplicationNicBlockedByOtherLevel($model, $postedNicBlock, $level);
            if ($cross !== null) {
                return $this->renderForm($level, [$cross], $this->oldPostWithStudentApplicationDocPaths($level, $_POST), null);
            }
        }
        $appId = (int) $this->post('application_id', 0);
        $existingDocPaths = null;
        $targetId = 0;

        if ($appId > 0) {
            $existing = $model->findById($appId);
            if (!$existing) {
                return $this->renderForm($level, ['We could not find that application. Please start again from step 1.'], $_POST, null);
            }
            if (trim((string) ($existing['application_level'] ?? '')) !== $level) {
                return $this->renderForm($level, ['This form does not match the saved application level. Open the correct application link.'], $_POST, null);
            }
            $postedNic = $this->normalizeNic((string) $this->post('student_nic', ''));
            if (trim((string) ($existing['student_nic'] ?? '')) !== $postedNic) {
                return $this->renderForm($level, ['The NIC does not match this application record.'], $_POST, null);
            }
            $existingDocPaths = [];
            foreach (StudentApplicationModel::DOCUMENT_PATH_COLUMNS as $col) {
                $existingDocPaths[$col] = trim((string) ($existing[$col] ?? ''));
            }
            $targetId = $appId;
        }

        $errors = $this->validateApplication($level, $existingDocPaths);
        if (!empty($errors)) {
            return $this->renderForm($level, $errors, $this->oldPostWithStudentApplicationDocPaths($level, $_POST), null);
        }

        $row = $this->buildRowFromPost($level, $existingDocPaths !== null);
        $sqlErr = null;

        if ($targetId > 0) {
            $ok = $model->update($targetId, $row, $sqlErr);
            if (!$ok) {
                $msg = 'We could not update your form. ';
                if ($sqlErr && (stripos($sqlErr, 'Duplicate') !== false || stripos($sqlErr, 'uq_') !== false)) {
                    $msg .= 'Another application may already use this email for this level.';
                } else {
                    $msg .= 'Please try again or phone the institute.';
                }
                if ($sqlErr) {
                    error_log('StudentApplication update: ' . $sqlErr);
                }
                return $this->renderForm($level, [$msg], $this->oldPostWithStudentApplicationDocPaths($level, $_POST), null);
            }
        } else {
            $newId = $model->insertApplication($row, $sqlErr);
            if ($newId === false) {
                $msg = 'We could not save your form. ';
                if ($sqlErr && (stripos($sqlErr, 'Duplicate') !== false || stripos($sqlErr, 'uq_') !== false)) {
                    $msg .= 'You already sent an application with this ID card or email for this level. Use the same NIC on step 1 to load and edit it.';
                } else {
                    $msg .= 'Please try again or phone the institute.';
                }
                if ($sqlErr) {
                    error_log('StudentApplication insert: ' . $sqlErr);
                }
                return $this->renderForm($level, [$msg], $this->oldPostWithStudentApplicationDocPaths($level, $_POST), null);
            }
            $targetId = (int) $newId;
        }

        $paths = [];
        try {
            $nic = $this->normalizeNic((string) $this->post('student_nic', ''));
            $paths = $this->collectUploads($targetId, $nic, $level, $existingDocPaths);
        } catch (Exception $e) {
            return $this->renderForm($level, ['Upload problem: ' . $e->getMessage()], $this->oldPostWithStudentApplicationDocPaths($level, $_POST), null);
        }

        if (!empty($paths)) {
            $model->updateDocumentPaths($targetId, $paths);
        }

        if ($appId > 0) {
            $_SESSION['flash_student_application_ok'] = 'Your Level ' . $level . ' application #' . $targetId . ' was updated successfully.';
        } else {
            $_SESSION['flash_student_application_ok'] = 'Thank you. We received your application. Your reference number is #' . $targetId . '.';
        }
        header('Location: ' . rtrim(APP_URL, '/') . '/level' . $level . 'application?submitted=1', true, 303);
        exit;
    }

    /**
     * Field keys for full G.C.E. O/L + A/L examination blocks (used for completeness checks when a path is started).
     *
     * @return list<string>
     */
    private function schoolExamFieldKeys(): array {
        return array_merge($this->olExamFieldKeys(), $this->alExamFieldKeys());
    }

    /**
     * @return list<string>
     */
    private function olExamFieldKeys(): array {
        $keys = ['ol_index_number', 'ol_exam_year'];
        for ($i = 1; $i <= 9; $i++) {
            $s = sprintf('%02d', $i);
            $keys[] = 'ol_subject_name_' . $s;
            $keys[] = 'ol_subject_' . $s . '_marks';
        }
        return $keys;
    }

    /**
     * @return list<string>
     */
    private function alExamFieldKeys(): array {
        $keys = ['al_index_number', 'al_exam_year', 'al_stream'];
        for ($i = 1; $i <= 3; $i++) {
            $s = sprintf('%02d', $i);
            $keys[] = 'al_subject_name_' . $s;
            $keys[] = 'al_subject_' . $s . '_marks';
        }
        return $keys;
    }

    /**
     * @return list<string>
     */
    private function nvqFieldKeys(): array {
        return ['nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'];
    }

    /**
     * @param callable(string): string $t
     */
    private function isOlPathComplete(callable $t): bool {
        foreach ($this->olExamFieldKeys() as $k) {
            if ($t($k) === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * True if the applicant started the O/L block. Fixed slots 1–6 always post hidden subject names — ignore those.
     *
     * @param callable(string): string $t
     */
    private function isOlAnyFilled(callable $t): bool {
        if ($t('ol_index_number') !== '' || $t('ol_exam_year') !== '') {
            return true;
        }
        for ($i = 1; $i <= 9; $i++) {
            $s = sprintf('%02d', $i);
            if ($t('ol_subject_' . $s . '_marks') !== '') {
                return true;
            }
        }
        for ($i = 7; $i <= 9; $i++) {
            $s = sprintf('%02d', $i);
            if ($t('ol_subject_name_' . $s) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param callable(string): string $t
     */
    private function isAlPathComplete(callable $t): bool {
        foreach ($this->alExamFieldKeys() as $k) {
            if ($t($k) === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param callable(string): string $t
     */
    private function isAlAnyFilled(callable $t): bool {
        foreach ($this->alExamFieldKeys() as $k) {
            if ($t($k) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param callable(string): string $t
     */
    private function isNvqAnyFilled(callable $t): bool {
        foreach ($this->nvqFieldKeys() as $k) {
            if ($t($k) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Full NVQ path: Level 05 allows NVQ Level 4 only (+ course, institute, year). Level 04 uses Level 3 on the form.
     *
     * @param callable(string): string $t
     */
    private function isNvqPathComplete(callable $t, string $applicationLevel): bool {
        $lvl = trim($t('nvq_level'));
        $allowed = $applicationLevel === '05'
            ? ['4']
            : ['3', '4', '5'];
        if ($lvl === '' || !in_array($lvl, $allowed, true)) {
            return false;
        }
        foreach (['nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'] as $k) {
            if ($t($k) === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getProvinceDistrictMap(): array {
        static $m = null;
        if ($m === null) {
            $path = BASE_PATH . '/config/sl_provinces_districts.php';
            $m = is_file($path) ? require $path : [];
        }
        return $m;
    }

    /**
     * @return array<string, string>
     */
    private function getDistrictPostalMap(): array {
        static $m = null;
        if ($m === null) {
            $path = BASE_PATH . '/config/sl_district_postal_codes.php';
            $m = is_file($path) ? require $path : [];
        }
        return $m;
    }

    /**
     * DB path column for a multipart file field name.
     */
    private function studentApplicationUploadColumnForField(string $fileKey): ?string {
        static $map = [
            'nic_document' => 'nic_document_path',
            'birth_certificate' => 'birth_certificate_path',
            'ol_certificate' => 'ol_certificate_path',
            'al_certificate' => 'al_certificate_path',
            'nvq_certificate' => 'nvq_certificate_path',
            'bank_receipt' => 'bank_receipt_path',
        ];
        return $map[$fileKey] ?? null;
    }

    /**
     * @param array<string, string>|null $existingDocPaths DB column => relative path (non-empty = already stored)
     * @return list<string>
     */
    private function validateApplication(string $level, ?array $existingDocPaths = null): array {
        $t = function (string $key): string {
            return trim((string) $this->post($key, ''));
        };

        $requiredStrings = [
            'student_title', 'student_full_name', 'student_initial_name', 'student_gender', 'student_civil_status',
            'student_email', 'student_phone', 'student_whatsapp', 'student_nic', 'student_dob',
            'student_language', 'student_religion',
            'student_address', 'student_zip_code', 'student_district', 'student_province',
        ];
        $requiredStrings[] = 'dept_pref_1';
        $requiredStrings[] = 'course_priority_1';

        foreach ($requiredStrings as $key) {
            if ($t($key) === '') {
                return ['Please fill in all empty boxes.'];
            }
        }

        if ($level === '05') {
            $nvqLvlEarly = $t('nvq_level');
            if ($nvqLvlEarly !== '' && $nvqLvlEarly !== '4') {
                return ['NVQ level must be NVQ Level 4 only, or None (empty).'];
            }
            $olOk = $this->isOlPathComplete($t);
            $alOk = $this->isAlPathComplete($t);
            $nvqOk = $this->isNvqPathComplete($t, $level);
            if ($this->isOlAnyFilled($t) && !$olOk && !$alOk) {
                return ['For Level 05: either complete all O/L fields or clear index, year, results, and basket subjects if you use NVQ only.'];
            }
            if ($this->isAlAnyFilled($t) && !$alOk) {
                return ['For Level 05: either complete all A/L fields or clear them if you use NVQ only.'];
            }
            if ($this->isNvqAnyFilled($t) && !$nvqOk) {
                return ['For Level 05: either complete all NVQ fields or clear them if you declare school qualifications (complete G.C.E. A/L) instead.'];
            }
            if (!$nvqOk && !$alOk) {
                return ['For Level 05: complete G.C.E. A/L in full, or provide full NVQ Level 4 (course, institute, year). NVQ is not required when A/L is complete.'];
            }
        }

        for ($i = 2; $i <= 3; $i++) {
            $d = $t('dept_pref_' . $i);
            $c = $t('course_priority_' . $i);
            if ($d !== '' && $c === '') {
                return ['For choice ' . $i . ': pick a course or clear the department.'];
            }
            if ($d === '' && $c !== '') {
                return ['For choice ' . $i . ': pick a department or clear the course.'];
            }
        }

        $email = $t('student_email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Please type a correct email address.'];
        }

        if (!$this->isValidSriLankaPhone($t('student_phone'))) {
            return ['Phone must be a valid Sri Lanka number (e.g. 0771234567 or +94 77 123 4567).'];
        }
        if (!$this->isValidSriLankaPhone($t('student_whatsapp'))) {
            return ['WhatsApp must be a valid Sri Lanka number (e.g. 0771234567 or +94 77 123 4567).'];
        }

        $gender = $t('student_gender');
        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
            return ['Please choose male, female, or other.'];
        }
        $civil = $t('student_civil_status');
        if (!in_array($civil, ['Single', 'Married'], true)) {
            return ['Please choose single or married.'];
        }

        $titles = ['Mr', 'Miss', 'Mrs'];
        if (!in_array($t('student_title'), $titles, true)) {
            return ['Please choose a title from the list.'];
        }
        $langs = ['Sinhala', 'Tamil', 'English'];
        if (!in_array($t('student_language'), $langs, true)) {
            return ['Please choose Sinhala, Tamil, or English.'];
        }
        $rels = ['Buddhism', 'Hinduism', 'Islam', 'Christianity'];
        if (!in_array($t('student_religion'), $rels, true)) {
            return ['Please choose a religion from the list.'];
        }
        $bloodVal = $t('student_blood_group');
        if ($bloodVal !== '') {
            $bloods = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
            if (!in_array($bloodVal, $bloods, true)) {
                return ['If you enter a blood group, choose a value from the list.'];
            }
        }

        $map = $this->getProvinceDistrictMap();
        $prov = $t('student_province');
        $dist = $t('student_district');
        if (!isset($map[$prov])) {
            return ['Please choose a province from the list.'];
        }
        if (!in_array($dist, $map[$prov], true)) {
            return ['Please choose a district that belongs to the selected province.'];
        }

        $nic = $this->normalizeNic($t('student_nic'));
        if (!$this->isValidNic($nic)) {
            return ['ID card (NIC) must be 9 numbers + V or X, or 12 numbers only.'];
        }

        $dobStr = $t('student_dob');
        $dob = \DateTimeImmutable::createFromFormat('Y-m-d', $dobStr);
        if ($dob === false || $dob->format('Y-m-d') !== $dobStr) {
            return ['Please enter a valid date of birth.'];
        }
        $dobCutoff = (new \DateTimeImmutable('today'))->modify('-16 years');
        if ($dob > $dobCutoff) {
            return ['You must be at least 16 years old to apply.'];
        }

        if ($level === '04') {
            // Level 04: no A/L on this form — require full O/L and/or full NVQ (at least one).
            $olOk = $this->isOlPathComplete($t);
            $nvqOk = $this->isNvqPathComplete($t, $level);
            if ($this->isOlAnyFilled($t) && !$olOk) {
                return ['For Level 04: either complete all O/L fields or clear the O/L section.'];
            }
            if ($this->isNvqAnyFilled($t) && !$nvqOk) {
                return ['For Level 04: either complete all NVQ fields or clear them if you use O/L only.'];
            }
            if (!$olOk && !$nvqOk) {
                return ['For Level 04: provide either complete O/L details or complete NVQ details (or both).'];
            }
            if ($olOk) {
                $yo = (int) $t('ol_exam_year');
                if ($yo < 1990 || $yo > 2100) {
                    return ['O/L year must be between 1990 and 2100.'];
                }
                for ($i = 1; $i <= 9; $i++) {
                    $s = sprintf('%02d', $i);
                    $m = $t('ol_subject_' . $s . '_marks');
                    if ($m === '' || !$this->isValidOlExamResult($m)) {
                        return ['O/L results: choose A, B, C, S, or W for every subject.'];
                    }
                }
            }
            if ($nvqOk) {
                $yn = (int) $t('nvq_year_completed');
                if ($t('nvq_year_completed') === '' || $yn < 1900 || $yn > 2100) {
                    return ['NVQ year finished must be between 1900 and 2100.'];
                }
            }
        } elseif ($level === '05') {
            // NVQ-complete applicants do not need O/L. Only validate O/L if they fully completed it.
            if (!$this->isNvqPathComplete($t, $level) && $this->isOlPathComplete($t)) {
                $yo = (int) $t('ol_exam_year');
                if ($yo < 1990 || $yo > 2100) {
                    return ['O/L year must be between 1990 and 2100.'];
                }
                for ($i = 1; $i <= 9; $i++) {
                    $s = sprintf('%02d', $i);
                    $m = $t('ol_subject_' . $s . '_marks');
                    if ($m === '' || !$this->isValidOlExamResult($m)) {
                        return ['O/L results: choose A, B, C, S, or W for every subject.'];
                    }
                }
            }
            if ($this->isAlPathComplete($t)) {
                $ya = (int) $t('al_exam_year');
                if ($ya < 1990 || $ya > 2100) {
                    return ['A/L year must be between 1990 and 2100.'];
                }
                for ($i = 1; $i <= 3; $i++) {
                    $s = sprintf('%02d', $i);
                    $m = $t('al_subject_' . $s . '_marks');
                    if ($m === '' || !$this->isValidOlExamResult($m)) {
                        return ['A/L results: choose A, B, C, S, or W for every subject.'];
                    }
                }
            }
            if ($this->isNvqPathComplete($t, $level)) {
                $yn = (int) $t('nvq_year_completed');
                if ($t('nvq_year_completed') === '' || $yn < 1900 || $yn > 2100) {
                    return ['NVQ year finished must be between 1900 and 2100.'];
                }
            }
        }

        $fileFields = ['nic_document', 'birth_certificate', 'bank_receipt'];
        if ($level === '05') {
            $fileFields = ['nic_document', 'birth_certificate', 'ol_certificate', 'al_certificate', 'nvq_certificate', 'bank_receipt'];
        } else {
            if ($this->isOlPathComplete($t)) {
                $fileFields[] = 'ol_certificate';
            }
            if ($this->isNvqPathComplete($t, $level)) {
                $fileFields[] = 'nvq_certificate';
            }
        }
        foreach ($fileFields as $fk) {
            $col = $this->studentApplicationUploadColumnForField($fk);
            $hasExisting = $existingDocPaths !== null && $col !== null && trim((string) ($existingDocPaths[$col] ?? '')) !== '';
            if ($hasExisting && !$this->isSuccessfulClientUpload($fk)) {
                continue;
            }
            if (!$this->isSuccessfulClientUpload($fk)) {
                return [$this->studentApplicationUploadMessage($fk)];
            }
        }

        return [];
    }

    private function normalizeNic(string $nic): string {
        $nic = strtoupper(trim($nic));
        return preg_replace('/\s+|-|_/', '', $nic);
    }

    private function isValidNic(string $nic): bool {
        return (bool) preg_match('/^(\d{9}[VX]|\d{12})$/', $nic);
    }

    /**
     * Sri Lanka NSN: 9 digits, first digit 1–9, after optional leading 0 or country code 94.
     */
    private function isValidSriLankaPhone(string $raw): bool {
        $d = preg_replace('/\D+/', '', trim($raw));
        if ($d === '') {
            return false;
        }
        if (str_starts_with($d, '94') && strlen($d) > 2) {
            $d = substr($d, 2);
        } elseif (str_starts_with($d, '0') && strlen($d) > 1) {
            $d = substr($d, 1);
        }
        return strlen($d) === 9 && (bool) preg_match('/^[1-9]\d{8}$/', $d);
    }

    /**
     * School exam result for Level 05 O/L and A/L: A, B, C, S, or W only.
     */
    private function isValidOlExamResult(string $raw): bool {
        $m = strtoupper(trim($raw));
        return in_array($m, ['A', 'B', 'C', 'S', 'W'], true);
    }

    /**
     * O/L & A/L result: letter grade (A–F, S, W with optional +/−).
     */
    private function isValidExamResult(string $raw): bool {
        $m = trim($raw);
        if ($m === '') {
            return false;
        }
        if ((bool) preg_match('/^[A-FSW][+-]?$/i', $m)) {
            return true;
        }
        return false;
    }

    /** @return string|null Normalized O/L or Level 05 A/L grade (A–W only) for storage */
    private function normalizedOlExamResult(string $raw): ?string {
        $m = strtoupper(trim($raw));
        return $this->isValidOlExamResult($raw) ? $m : null;
    }

    /** @return string|null Normalized value for storage */
    private function normalizedExamResult(string $raw): ?string {
        $m = trim($raw);
        if ($m === '') {
            return null;
        }
        if ((bool) preg_match('/^[A-FSW][+-]?$/i', $m)) {
            return strtoupper($m);
        }
        return null;
    }

    /**
     * @return array<string, string|null>
     */
    private function buildRowFromPost(string $level, bool $forUpdate = false): array {
        $p = function (string $key, $default = null) {
            $v = $this->post($key, '');
            if ($v === null || $v === '') {
                return $default;
            }
            return is_string($v) ? trim($v) : $v;
        };

        $intOrNull = function ($key) use ($p) {
            $v = $p($key, '');
            if ($v === null || $v === '') {
                return null;
            }
            return (string) (int) $v;
        };

        $yearOrNull = function ($key) use ($p) {
            $v = $p($key, '');
            if ($v === null || $v === '') {
                return null;
            }
            $y = (int) $v;
            if ($y < 1970 || $y > 2100) {
                return null;
            }
            return (string) $y;
        };

        $examResultOrNull = function (string $key) use ($p) {
            $v = (string) $p($key, '');
            return $this->normalizedExamResult($v);
        };

        $olExamResultOrNull = function (string $key) use ($p) {
            $v = (string) $p($key, '');
            return $this->normalizedOlExamResult($v);
        };

        $data = [
            'application_level' => $level,
            'student_title' => $p('student_title') ?: null,
            'student_full_name' => $p('student_full_name', ''),
            'student_initial_name' => $p('student_initial_name') ?: null,
            'student_gender' => $p('student_gender') ?: null,
            'student_civil_status' => $p('student_civil_status') ?: null,
            'student_email' => $p('student_email') ?: null,
            'student_phone' => $p('student_phone') ?: null,
            'student_whatsapp' => $p('student_whatsapp') ?: null,
            'student_nic' => $this->normalizeNic((string) $p('student_nic', '')),
            'student_dob' => $p('student_dob') ?: null,
            'student_language' => $p('student_language') ?: null,
            'student_religion' => $p('student_religion') ?: null,
            'student_blood_group' => $p('student_blood_group') ?: null,
            'student_address' => $p('student_address') ?: null,
            'student_zip_code' => $p('student_zip_code') ?: null,
            'student_district' => $p('student_district') ?: null,
            'student_province' => $p('student_province') ?: null,
            'course_priority_1' => $p('course_priority_1') ?: null,
            'course_priority_2' => $p('course_priority_2') ?: null,
            'course_priority_3' => $p('course_priority_3') ?: null,
            'ol_index_number' => $p('ol_index_number') ?: null,
            'ol_exam_year' => $yearOrNull('ol_exam_year'),
            'al_index_number' => $p('al_index_number') ?: null,
            'al_exam_year' => $yearOrNull('al_exam_year'),
            'al_stream' => $p('al_stream') ?: null,
            'nvq_level' => $p('nvq_level') ?: null,
            'nvq_course_name' => $p('nvq_course_name') ?: null,
            'nvq_institute_name' => $p('nvq_institute_name') ?: null,
            'nvq_year_completed' => $yearOrNull('nvq_year_completed'),
        ];

        for ($i = 1; $i <= 9; $i++) {
            $s = sprintf('%02d', $i);
            $data['ol_subject_name_' . $s] = $p('ol_subject_name_' . $s) ?: null;
            $data['ol_subject_' . $s . '_marks'] = $olExamResultOrNull('ol_subject_' . $s . '_marks');
        }
        for ($i = 1; $i <= 3; $i++) {
            $s = sprintf('%02d', $i);
            $data['al_subject_name_' . $s] = $p('al_subject_name_' . $s) ?: null;
            $data['al_subject_' . $s . '_marks'] = $olExamResultOrNull('al_subject_' . $s . '_marks');
        }

        $nullFiles = [
            'nic_document_path' => null,
            'birth_certificate_path' => null,
            'ol_certificate_path' => null,
            'al_certificate_path' => null,
            'nvq_certificate_path' => null,
            'bank_receipt_path' => null,
        ];
        if ($forUpdate) {
            unset($data['application_level']);
            return $data;
        }
        $data['status'] = 'new';
        return array_merge($data, $nullFiles);
    }

    /**
     * Flatten application row for JSON (wizard NIC check / prefill).
     *
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function studentApplicationRowForApiJson(StudentApplicationModel $model, array $row): array {
        $out = [];
        foreach ($row as $k => $v) {
            if ($v === null) {
                $out[$k] = '';
            } elseif (is_scalar($v)) {
                $out[$k] = (string) $v;
            } else {
                $out[$k] = '';
            }
        }
        for ($n = 1; $n <= 3; $n++) {
            $cp = trim((string) ($row['course_priority_' . $n] ?? ''));
            $deptId = '';
            if ($cp !== '') {
                $r = $model->resolveCourseDepartmentForPreference($cp);
                $deptId = trim((string) ($r['department_id'] ?? ''));
            }
            $out['dept_pref_' . $n] = $deptId;
        }
        return $out;
    }

    /**
     * @param array<string, string>|null $existingDocPaths DB column => stored relative path (for updates: keep path when no new file)
     * @return array<string, string>
     */
    private function collectUploads(int $applicationId, string $nic, string $level, ?array $existingDocPaths = null): array {
        $t = function (string $key): string {
            return trim((string) $this->post($key, ''));
        };
        $map = [
            'nic_document' => 'nic_document_path',
            'birth_certificate' => 'birth_certificate_path',
            'bank_receipt' => 'bank_receipt_path',
        ];
        if ($level === '05') {
            $map['ol_certificate'] = 'ol_certificate_path';
            $map['al_certificate'] = 'al_certificate_path';
            $map['nvq_certificate'] = 'nvq_certificate_path';
        } else {
            if ($this->isOlPathComplete($t)) {
                $map['ol_certificate'] = 'ol_certificate_path';
            }
            if ($this->isNvqPathComplete($t, $level)) {
                $map['nvq_certificate'] = 'nvq_certificate_path';
            }
        }
        $out = [];
        foreach ($map as $fileKey => $dbCol) {
            if (!$this->isSuccessfulClientUpload($fileKey)) {
                $prev = ($existingDocPaths !== null && isset($existingDocPaths[$dbCol])) ? trim((string) $existingDocPaths[$dbCol]) : '';
                if ($prev !== '') {
                    $out[$dbCol] = $prev;
                    continue;
                }
                throw new Exception('Missing or invalid upload: ' . $fileKey);
            }
            $path = $this->handleUpload($fileKey, $applicationId, $fileKey, $nic);
            if ($path === null) {
                throw new Exception('Missing or invalid upload: ' . $fileKey);
            }
            $out[$dbCol] = $path;
        }
        return $out;
    }

    /**
     * True when PHP accepted an upload for this input (multipart file field).
     * Some Windows/WAMP setups report UPLOAD_ERR_OK but is_uploaded_file() is false; allow readable temp file.
     */
    private function isSuccessfulClientUpload(string $fieldName): bool {
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            return false;
        }
        if ((int) ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }
        $tmp = (string) ($_FILES[$fieldName]['tmp_name'] ?? '');
        if ($tmp === '') {
            return false;
        }
        if (is_uploaded_file($tmp)) {
            return true;
        }
        return is_file($tmp) && is_readable($tmp);
    }

    /**
     * User-facing message when a required document upload is missing or failed.
     */
    private function studentApplicationUploadMessage(string $fieldName): string {
        $names = [
            'nic_document' => 'NIC copy',
            'birth_certificate' => 'Birth certificate',
            'ol_certificate' => 'O/L certificate',
            'al_certificate' => 'A/L certificate',
            'nvq_certificate' => 'NVQ certificate',
            'bank_receipt' => 'Bank payment slip',
        ];
        $label = $names[$fieldName] ?? $fieldName;
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            return 'Missing upload for ' . $label . '. Please attach it on the Documents step.';
        }
        $e = (int) ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($e === UPLOAD_ERR_NO_FILE) {
            return 'Please attach your ' . $label . ' (Documents step).';
        }
        if ($e === UPLOAD_ERR_INI_SIZE || $e === UPLOAD_ERR_FORM_SIZE) {
            return $label . ' is too large. Each file must be 5 MB or smaller.';
        }
        if ($e === UPLOAD_ERR_PARTIAL) {
            return $label . ' did not finish uploading. Please try again.';
        }
        if ($e !== UPLOAD_ERR_OK) {
            return 'Could not read ' . $label . ' (upload error ' . $e . '). Please try again or use JPG/PNG.';
        }
        return 'The server could not accept ' . $label . '. Please try another PDF, JPG, or PNG.';
    }

    private function handleUpload(string $fieldName, int $applicationId, string $documentKey, string $nic): ?string {
        if (!$this->isSuccessfulClientUpload($fieldName)) {
            return null;
        }
        $size = (int) ($_FILES[$fieldName]['size'] ?? 0);
        if ($size > self::UPLOAD_MAX_BYTES) {
            throw new Exception('Each file must be 5 MB or smaller.');
        }
        $orig = (string) ($_FILES[$fieldName]['name'] ?? '');
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new Exception('Allowed file types: PDF, JPG, PNG.');
        }
        $dir = BASE_PATH . '/uploads/student_applications/' . $applicationId;
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new Exception('Could not create upload directory.');
            }
        }
        $base = $this->uploadBasename($documentKey, $nic);
        $tmp = (string) $_FILES[$fieldName]['tmp_name'];

        if ($ext === 'pdf') {
            if (extension_loaded('imagick') && class_exists('Imagick')) {
                try {
                    $raster = $this->pdfFirstPageToTempJpeg($tmp);
                    try {
                        $safe = $base . '.jpg';
                        $full = $dir . DIRECTORY_SEPARATOR . $safe;
                        $this->compressRasterToJpegUnderLimit($raster, $full);
                        return 'uploads/student_applications/' . $applicationId . '/' . $safe;
                    } finally {
                        @unlink($raster);
                    }
                } catch (Throwable $e) {
                    error_log('StudentApplication PDF to JPEG: ' . $e->getMessage());
                }
            }
            $safe = $base . '.pdf';
            $full = $dir . DIRECTORY_SEPARATOR . $safe;
            if (!@move_uploaded_file($tmp, $full) && !@copy($tmp, $full)) {
                throw new Exception('Could not save PDF. Check that uploads/student_applications is writable.');
            }
            return 'uploads/student_applications/' . $applicationId . '/' . $safe;
        }

        $safe = $base . '.jpg';
        $full = $dir . DIRECTORY_SEPARATOR . $safe;
        $this->compressRasterToJpegUnderLimit($tmp, $full);

        return 'uploads/student_applications/' . $applicationId . '/' . $safe;
    }

    /**
     * Filename stem: document key + NIC (e.g. nic_document_123456789V).
     */
    private function uploadBasename(string $documentKey, string $nic): string {
        $key = strtolower(preg_replace('/[^a-z0-9_]/', '', $documentKey));
        if ($key === '') {
            $key = 'document';
        }
        $nicPart = strtoupper(preg_replace('/[^0-9VX]/', '', $nic));
        if ($nicPart === '') {
            $nicPart = 'unknown';
        }
        return $key . '_' . $nicPart;
    }

    /**
     * Rasterize first PDF page to a temporary JPEG, then caller compresses further.
     *
     * @return string Path to temp .jpg file
     */
    private function pdfFirstPageToTempJpeg(string $pdfPath): string {
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            throw new Exception('PDF could not be processed: please upload JPG or PNG instead, or ask the institute to enable ImageMagick on the server.');
        }
        $out = tempnam(sys_get_temp_dir(), 'sapdf');
        if ($out === false) {
            throw new Exception('Could not create temporary file for PDF.');
        }
        @unlink($out);
        $outJpg = $out . '.jpg';
        try {
            $im = new Imagick();
            $im->setResolution(144, 144);
            $im->readImage($pdfPath . '[0]');
            if (method_exists($im, 'setImageBackgroundColor')) {
                $im->setImageBackgroundColor(new \ImagickPixel('white'));
            }
            if (defined('Imagick::ALPHACHANNEL_REMOVE')) {
                $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            }
            if (defined('Imagick::LAYERMETHOD_FLATTEN')) {
                $im = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            }
            $im->setImageFormat('jpeg');
            $im->setImageCompression(Imagick::COMPRESSION_JPEG);
            $im->setImageCompressionQuality(82);
            $im->writeImage($outJpg);
            $im->clear();
            $im->destroy();
        } catch (Throwable $e) {
            @unlink($outJpg);
            error_log('StudentApplication PDF rasterize: ' . $e->getMessage());
            throw new Exception('Could not read or convert this PDF. Please upload a JPG or PNG scan instead.');
        }
        return $outJpg;
    }

    /**
     * Resize and recompress to JPEG until file size is at most STORED_MAX_BYTES.
     */
    private function compressRasterToJpegUnderLimit(string $srcPath, string $destJpgPath): void {
        if (!extension_loaded('gd')) {
            throw new Exception('Server GD extension is required to save documents.');
        }
        $raw = @file_get_contents($srcPath);
        if ($raw === false || $raw === '') {
            throw new Exception('Could not read uploaded file.');
        }
        $srcImg = @imagecreatefromstring($raw);
        if ($srcImg === false) {
            throw new Exception('Could not read image data.');
        }
        $w = imagesx($srcImg);
        $h = imagesy($srcImg);
        if ($w < 1 || $h < 1) {
            imagedestroy($srcImg);
            throw new Exception('Invalid image dimensions.');
        }
        $maxBytes = self::STORED_MAX_BYTES;
        $scale = 1.0;
        for ($round = 0; $round < 28; $round++) {
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $nw, $nh, $w, $h);
            for ($q = 90; $q >= 35; $q -= 2) {
                $tmpFile = tempnam(sys_get_temp_dir(), 'sacmp');
                if ($tmpFile === false) {
                    imagedestroy($dst);
                    imagedestroy($srcImg);
                    throw new Exception('Could not create temporary file.');
                }
                imagejpeg($dst, $tmpFile, $q);
                $sz = @filesize($tmpFile);
                if ($sz !== false && $sz <= $maxBytes) {
                    if (!@rename($tmpFile, $destJpgPath)) {
                        if (!@copy($tmpFile, $destJpgPath)) {
                            @unlink($tmpFile);
                            imagedestroy($dst);
                            imagedestroy($srcImg);
                            throw new Exception('Could not save compressed file.');
                        }
                        @unlink($tmpFile);
                    }
                    imagedestroy($dst);
                    imagedestroy($srcImg);
                    return;
                }
                @unlink($tmpFile);
            }
            imagedestroy($dst);
            $scale *= 0.82;
            if ($scale < 0.1) {
                break;
            }
        }
        imagedestroy($srcImg);
        throw new Exception('Could not compress this file under 100 KB. Please upload a smaller or simpler scan.');
    }

    /**
     * One NIC may not have both Level 04 and Level 05 applications.
     */
    private function studentApplicationNicBlockedByOtherLevel(StudentApplicationModel $model, string $nic, string $level): ?string {
        if (!in_array($level, ['04', '05'], true)) {
            return null;
        }
        $other = $level === '04' ? '05' : '04';
        $row = $model->findByNicAndLevel($nic, $other);
        if ($row === null) {
            return null;
        }
        if ($level === '05') {
            return 'This NIC already has a Level 04 application. You cannot apply for Level 05 with the same NIC.';
        }
        return 'This NIC already has a Level 05 application. You cannot use the Level 04 online application with the same NIC.';
    }

    /**
     * Public JSON: lookup existing application by NIC + level (same idea as level05application/check_nic.php).
     * POST JSON: { "nic": "…", "application_level": "04" } — level defaults to "04".
     */
    public function apiCheckApplication(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Use POST.']);
            exit;
        }
        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = [];
        }
        $nic = $this->normalizeNic((string) ($input['nic'] ?? ''));
        $level = trim((string) ($input['application_level'] ?? ''));
        if ($level === '') {
            $level = '04';
        }
        if (!$this->isValidNic($nic)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Enter a valid NIC (9 digits + V or X, or 12 digits).']);
            exit;
        }
        if (!in_array($level, ['04', '05'], true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Invalid application level.']);
            exit;
        }
        try {
            $model = $this->model('StudentApplicationModel');
            $blockMsg = $this->studentApplicationNicBlockedByOtherLevel($model, $nic, $level);
            if ($blockMsg !== null) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => $blockMsg]);
                exit;
            }
            $row = $model->findByNicAndLevel($nic, $level);
        } catch (Throwable $e) {
            error_log('apiCheckApplication: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Could not verify NIC.']);
            exit;
        }
        if ($row) {
            $data = $this->studentApplicationRowForApiJson($model, $row);
            echo json_encode(['status' => 'exists', 'data' => $data], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['status' => 'new']);
        exit;
    }

    /**
     * Public JSON: departments (for online application course preferences).
     */
    public function apiDepartments() {
        header('Content-Type: application/json; charset=utf-8');
        $nvq = trim((string) $this->get('nvq_level', ''));
        if (!in_array($nvq, ['4', '5'], true)) {
            echo json_encode(['success' => true, 'departments' => []]);
            exit;
        }
        try {
            $model = $this->model('DepartmentModel');
            $depts = $model->getDepartmentsWithNvqCourses($nvq);
        } catch (Throwable $e) {
            error_log('apiDepartments: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'departments' => [], 'error' => 'Service unavailable']);
            exit;
        }
        echo json_encode(['success' => true, 'departments' => $depts]);
        exit;
    }

    /**
     * Public JSON: courses by department and NVQ level (4 = Level 04, 5 = Level 05).
     */
    public function apiCourses() {
        header('Content-Type: application/json; charset=utf-8');
        $deptId = trim((string) $this->get('department_id', ''));
        $nvq = trim((string) $this->get('nvq_level', ''));
        if ($deptId === '' || !in_array($nvq, ['4', '5'], true)) {
            echo json_encode(['success' => true, 'courses' => []]);
            exit;
        }
        try {
            $courseModel = $this->model('CourseModel');
            $courses = $courseModel->getCoursesWithDepartment([
                'department_id' => $deptId,
                'nvq_level' => $nvq,
            ]);
        } catch (Throwable $e) {
            error_log('apiCourses: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'courses' => [], 'error' => 'Service unavailable']);
            exit;
        }
        echo json_encode(['success' => true, 'courses' => $courses]);
        exit;
    }

    /**
     * Staff (SAO, ADM, admin): list online applications.
     */
    public function adminIndex() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            $_SESSION['error'] = 'You cannot open this page. Only Student Affairs (SAO) and Administrators (ADM) may access online applications.';
            $this->redirect('dashboard');
            return;
        }

        $model = $this->model('StudentApplicationModel');
        $levelRaw = trim((string) $this->get('level', ''));
        $filterLevel = in_array($levelRaw, ['04', '05'], true) ? $levelRaw : null;
        $tabRaw = strtolower(trim((string) $this->get('tab', '')));
        $activeTab = in_array($tabRaw, ['approved', 'rejected'], true) ? $tabRaw : 'new';

        $deptRaw = trim((string) $this->get('dept', ''));
        $courseRaw = trim((string) $this->get('course', ''));
        $filterDeptId = null;
        $filterCourseId = null;
        if ($deptRaw !== '') {
            $deptModel = $this->model('DepartmentModel');
            $drow = $deptModel->find($deptRaw);
            if (!empty($drow['department_id'])) {
                $filterDeptId = (string) $drow['department_id'];
            }
        }
        if ($courseRaw !== '') {
            $courseModel = $this->model('CourseModel');
            $crow = $courseModel->find($courseRaw);
            if (!empty($crow['course_id'])) {
                $filterCourseId = (string) $crow['course_id'];
                if ($filterDeptId !== null && (string) ($crow['department_id'] ?? '') !== $filterDeptId) {
                    $filterCourseId = null;
                }
            }
        }

        $viewRaw = strtolower(trim((string) $this->get('view', '')));
        $activeView = $viewRaw === 'dashboard' ? 'dashboard' : 'table';

        $perPage = 20;
        $dashboardStats = $model->getDashboardStats($filterLevel, $filterDeptId, $filterCourseId);

        if ($activeView === 'table') {
            $countNew = $model->countListForAdmin('new', $filterLevel, $filterDeptId, $filterCourseId);
            $countApproved = $model->countListForAdmin('approved', $filterLevel, $filterDeptId, $filterCourseId);
            $countRejected = $model->countListForAdmin('rejected', $filterLevel, $filterDeptId, $filterCourseId);
            $maxPageNew = max(1, (int) ceil($countNew / $perPage));
            $maxPageApproved = max(1, (int) ceil($countApproved / $perPage));
            $maxPageRejected = max(1, (int) ceil($countRejected / $perPage));
            $pageNew = max(1, min((int) $this->get('pn', 1), $maxPageNew));
            $pageApproved = max(1, min((int) $this->get('pa', 1), $maxPageApproved));
            $pageRejected = max(1, min((int) $this->get('pr', 1), $maxPageRejected));

            $applicationsNew = $activeTab === 'new'
                ? $model->getListPageForAdmin('new', $filterLevel, $pageNew, $perPage, $filterDeptId, $filterCourseId)
                : [];
            $applicationsApproved = $activeTab === 'approved'
                ? $model->getListPageForAdmin('approved', $filterLevel, $pageApproved, $perPage, $filterDeptId, $filterCourseId)
                : [];
            $applicationsRejected = $activeTab === 'rejected'
                ? $model->getListPageForAdmin('rejected', $filterLevel, $pageRejected, $perPage, $filterDeptId, $filterCourseId)
                : [];
        } else {
            $countNew = 0;
            $countApproved = 0;
            $countRejected = 0;
            $maxPageNew = 1;
            $maxPageApproved = 1;
            $maxPageRejected = 1;
            $pageNew = 1;
            $pageApproved = 1;
            $pageRejected = 1;
            $applicationsNew = [];
            $applicationsApproved = [];
            $applicationsRejected = [];
        }

        return $this->view('student_application/admin_index', [
            'title' => 'Online applications',
            'page' => 'student-applications',
            'filter_level' => $filterLevel,
            'filter_department_id' => $filterDeptId,
            'filter_course_id' => $filterCourseId,
            // Filter dropdowns: load full catalogue (not just values used by applications).
            'filter_departments' => $this->model('DepartmentModel')->getAll(),
            'filter_courses' => $this->model('CourseModel')->getCoursesWithDepartment([
                'department_id' => $filterDeptId,
                // application_level 04/05 corresponds to course_nvq_level 4/5
                'nvq_level' => $filterLevel === '04' ? '4' : ($filterLevel === '05' ? '5' : null),
            ]),
            'active_view' => $activeView,
            'active_tab' => $activeTab,
            'per_page' => $perPage,
            'page_new' => $pageNew,
            'page_approved' => $pageApproved,
            'page_rejected' => $pageRejected,
            'count_new' => $countNew,
            'count_approved' => $countApproved,
            'count_rejected' => $countRejected,
            'max_page_new' => $maxPageNew,
            'max_page_approved' => $maxPageApproved,
            'max_page_rejected' => $maxPageRejected,
            'applications_new' => $applicationsNew,
            'applications_approved' => $applicationsApproved,
            'applications_rejected' => $applicationsRejected,
            'dashboard_stats' => $dashboardStats,
            'can_delete' => $userModel->isAdminOrADM($uid),
            'use_public_layout' => false,
        ]);
    }

    /**
     * Staff (SAO, ADM, admin): view one application (full row + documents).
     */
    public function adminView() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            $_SESSION['error'] = 'You cannot open this page. Only Student Affairs (SAO) and Administrators (ADM) may access online applications.';
            $this->redirect('dashboard');
            return;
        }

        $id = (int) $this->get('id', 0);
        if ($id < 1) {
            $this->redirect('student-applications');
            return;
        }
        $model = $this->model('StudentApplicationModel');
        $app = $model->findById($id);
        if (!$app) {
            $_SESSION['error'] = 'That application was not found.';
            $this->redirect('student-applications');
            return;
        }

        $app = $model->enrichApplicationForStaffExport($app);

        return $this->view('student_application/admin_view', [
            'title' => 'Application #' . $id,
            'page' => 'student-applications',
            'app' => $app,
            'can_delete' => $userModel->isAdminOrADM($uid),
            'use_public_layout' => false,
        ]);
    }

    /**
     * Staff (SAO, ADM, admin): delete an application and its uploaded NIC folder.
     */
    public function adminDelete(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->isAdminOrADM($uid)) {
            $_SESSION['error'] = 'Only Administrators (ADM) can delete applications.';
            $this->redirect('dashboard');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('student-applications');
            return;
        }

        $id = (int) $this->post('application_id', 0);
        if ($id < 1) {
            $_SESSION['error'] = 'Invalid application.';
            $this->redirect('student-applications');
            return;
        }

        $model = $this->model('StudentApplicationModel');
        $app = $model->findById($id);
        if (!$app) {
            $_SESSION['error'] = 'That application was not found.';
            $this->redirect('student-applications');
            return;
        }

        $ok = false;
        try {
            $ok = (bool) $model->delete($id);
        } catch (Throwable $e) {
            error_log('StudentApplicationController::adminDelete delete: ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            $_SESSION['error'] = 'Could not delete the application.';
            $this->redirect('student-applications/view?id=' . $id);
            return;
        }

        $nic = (string) ($app['student_nic'] ?? '');
        $filesOk = true;
        if (trim($nic) !== '') {
            $filesOk = $this->safeDeleteStudentApplicationUploadDir($nic);
        }

        $this->logActivity(
            'DELETE',
            'student_application',
            (string) $id,
            'Deleted online student application #' . $id . '.',
            $app,
            null
        );

        $_SESSION['message'] = $filesOk
            ? 'Application #' . $id . ' deleted.'
            : 'Application #' . $id . ' deleted, but some uploaded files could not be removed.';
        $this->redirect('student-applications');
    }

    /**
     * Staff (SAO, ADM, admin): approve an application (status = approved).
     */
    public function adminApprove(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            $_SESSION['error'] = 'You cannot open this page.';
            $this->redirect('dashboard');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('student-applications');
            return;
        }

        $id = (int) $this->post('application_id', 0);
        if ($id < 1) {
            $_SESSION['error'] = 'Invalid application.';
            $this->redirect('student-applications');
            return;
        }

        $model = $this->model('StudentApplicationModel');
        $app = $model->findById($id);
        if (!$app) {
            $_SESSION['error'] = 'That application was not found.';
            $this->redirect('student-applications');
            return;
        }

        $ok = false;
        try {
            $ok = $model->setStatus($id, 'approved');
        } catch (Throwable $e) {
            error_log('StudentApplicationController::adminApprove: ' . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            $_SESSION['message'] = 'Application #' . $id . ' approved.';
        } else {
            $_SESSION['error'] = 'Could not approve application.';
        }
        $this->redirect('student-applications/view?id=' . $id);
    }

    /**
     * Staff (SAO, ADM, admin): reject an application (status = rejected; only from new).
     */
    public function adminReject(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            $_SESSION['error'] = 'You cannot open this page.';
            $this->redirect('dashboard');
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('student-applications');
            return;
        }

        $id = (int) $this->post('application_id', 0);
        if ($id < 1) {
            $_SESSION['error'] = 'Invalid application.';
            $this->redirect('student-applications');
            return;
        }

        $model = $this->model('StudentApplicationModel');
        $app = $model->findById($id);
        if (!$app) {
            $_SESSION['error'] = 'That application was not found.';
            $this->redirect('student-applications');
            return;
        }

        $st = strtolower(trim((string) ($app['status'] ?? '')));
        if ($st !== 'new') {
            $_SESSION['error'] = 'Only new applications can be rejected.';
            $this->redirect('student-applications/view?id=' . $id);
            return;
        }

        $ok = false;
        try {
            $ok = $model->setStatus($id, 'rejected');
        } catch (Throwable $e) {
            error_log('StudentApplicationController::adminReject: ' . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            $_SESSION['message'] = 'Application #' . $id . ' rejected.';
        } else {
            $_SESSION['error'] = 'Could not reject application.';
        }
        $this->redirect('student-applications/view?id=' . $id);
    }

    /**
     * Staff (SAO, ADM, admin): download one uploaded document with Content-Disposition: attachment.
     */
    public function adminDownloadDocument(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        $applicationId = (int) $this->get('id', 0);
        $col = trim((string) $this->get('col', ''));
        if ($applicationId < 1 || !in_array($col, self::STAFF_DOWNLOAD_DOCUMENT_COLUMNS, true)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }

        $model = $this->model('StudentApplicationModel');
        $app = $model->findById($applicationId);
        if (!$app) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }

        $rel = isset($app[$col]) ? trim(str_replace('\\', '/', (string) $app[$col])) : '';
        $relLower = strtolower($rel);
        if ($rel === '' || strpos($rel, '..') !== false || !str_starts_with($relLower, 'uploads/student_applications/')) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }

        $full = realpath(BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
        $uploadsRoot = realpath(BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'student_applications');
        if ($full === false || $uploadsRoot === false || !is_file($full)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }
        $uploadsPrefix = $uploadsRoot . DIRECTORY_SEPARATOR;
        if (strpos($full, $uploadsPrefix) !== 0) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        $baseName = basename($full);
        if ($baseName === '' || $baseName === '.' || $baseName === '..') {
            http_response_code(404);
            exit;
        }

        $ext = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));
        // Use if/elseif for PHP < 8.0 compatibility (no match expression).
        $mime = 'application/octet-stream';
        if ($ext === 'pdf') {
            $mime = 'application/pdf';
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            $mime = 'image/jpeg';
        } elseif ($ext === 'png') {
            $mime = 'image/png';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        }

        $safeStem = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo($baseName, PATHINFO_FILENAME)) ?: 'document';
        $dispName = 'app' . $applicationId . '_' . $safeStem . ($ext !== '' ? '.' . $ext : '');

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $dispName) . '"');
        header('Content-Length: ' . (string) filesize($full));
        header('Cache-Control: private, max-age=0');
        header('Pragma: public');

        readfile($full);
        exit;
    }

    /**
     * Staff: download all application fields as CSV (no upload paths / no document files).
     */
    public function adminExportApplicationData(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        $applicationId = (int) $this->get('id', 0);
        if ($applicationId < 1) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }

        $model = $this->model('StudentApplicationModel');
        $app = $model->findById($applicationId);
        if (!$app) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }

        $app = $model->enrichApplicationForStaffExport($app);

        $filename = 'application_' . $applicationId . '_data.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, max-age=0');

        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if ($out === false) {
            http_response_code(500);
            exit;
        }
        fputcsv($out, ['Field', 'Value']);
        foreach (StudentApplicationModel::getStaffExportColumnOrder() as $col) {
            $val = isset($app[$col]) ? (string) $app[$col] : '';
            $val = str_replace(["\r\n", "\r", "\n"], ' | ', $val);
            fputcsv($out, [$col, $val]);
        }
        fclose($out);
        exit;
    }

    /**
     * Staff: download application data as a one-page PDF summary (no document files / no upload paths).
     */
    public function adminExportApplicationPdf(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        $applicationId = (int) $this->get('id', 0);
        if ($applicationId < 1) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }

        $model = $this->model('StudentApplicationModel');
        $app = $model->findById($applicationId);
        if (!$app) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Not found';
            exit;
        }

        $app = $model->enrichApplicationForStaffExport($app);

        require_once BASE_PATH . '/helpers/StudentApplicationSummaryPdf.php';

        $level = trim((string) ($app['application_level'] ?? ''));
        $title = 'Application #' . $applicationId . ($level !== '' ? ' (Level ' . $level . ')' : '');
        $rows = [];
        foreach (StudentApplicationModel::getStaffExportColumnOrder() as $col) {
            $val = isset($app[$col]) ? (string) $app[$col] : '';
            $val = str_replace(["\r\n", "\r", "\n"], ' | ', $val);
            if ($col === 'created_at' && $val !== '') {
                $ts = strtotime($val);
                if ($ts) {
                    $val = date('Y-m-d H:i:s', $ts);
                }
            }
            $rows[] = [$col, $val];
        }

        $pdf = StudentApplicationSummaryPdf::build($title, $rows);
        $filename = 'application_' . $applicationId . '_summary.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . (string) strlen($pdf));
        header('Cache-Control: private, max-age=0');

        echo $pdf;
        exit;
    }

    /**
     * Staff: download all applications as Excel (.xlsx) using staff export column order (no document paths).
     */
    public function adminExportExcel(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        if (!$userModel->canViewOnlineStudentApplications($uid)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        // PhpSpreadsheet has several PHP extension requirements; if any are missing,
        // export as an Excel-readable HTML table (.xls) instead of failing.
        $xlsFallback = static function (array $rows, array $cols, ?string $exportStatus): void {
            $filename = 'student_applications_' . date('Y-m-d_H-i') . '_' . ($exportStatus ?: 'all') . '.xls';
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Cache-Control: private, max-age=0');
            echo "\xEF\xBB\xBF";
            $esc = static function (string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
            echo "<table border=\"1\">\n<thead><tr>";
            foreach ($cols as $h) {
                echo '<th>' . $esc((string) $h) . '</th>';
            }
            echo "</tr></thead>\n<tbody>\n";
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($cols as $colName) {
                    $v = isset($row[$colName]) ? (string) $row[$colName] : '';
                    echo '<td style="mso-number-format:\'\@\';">' . $esc($v) . '</td>';
                }
                echo "</tr>\n";
            }
            echo "</tbody></table>";
            exit;
        };

        try {
            require_once BASE_PATH . '/vendor/autoload.php';
        } catch (Throwable $e) {
            error_log('StudentApplicationController::adminExportExcel autoload: ' . $e->getMessage());
            // Autoloader missing → fallback.
            $model = $this->model('StudentApplicationModel');
            $rows = $model->getAllForStaffExport(null, null, null, null);
            $xlsFallback($rows, StudentApplicationModel::getStaffExportColumnOrder(), null);
        }

        $model = $this->model('StudentApplicationModel');
        $statusParam = strtolower(trim((string) $this->get('status', '')));
        $exportStatus = in_array($statusParam, ['new', 'approved', 'rejected'], true) ? $statusParam : null;
        $levelRaw = trim((string) $this->get('level', ''));
        $exportLevel = in_array($levelRaw, ['04', '05'], true) ? $levelRaw : null;
        $deptRaw = trim((string) $this->get('dept', ''));
        $courseRaw = trim((string) $this->get('course', ''));
        $exportDeptId = null;
        $exportCourseId = null;
        if ($deptRaw !== '') {
            $deptModel = $this->model('DepartmentModel');
            $drow = $deptModel->find($deptRaw);
            if (!empty($drow['department_id'])) {
                $exportDeptId = (string) $drow['department_id'];
            }
        }
        if ($courseRaw !== '') {
            $courseModel = $this->model('CourseModel');
            $crow = $courseModel->find($courseRaw);
            if (!empty($crow['course_id'])) {
                $exportCourseId = (string) $crow['course_id'];
                if ($exportDeptId !== null && (string) ($crow['department_id'] ?? '') !== $exportDeptId) {
                    $exportCourseId = null;
                }
            }
        }
        $rows = $model->getAllForStaffExport($exportStatus, $exportLevel, $exportDeptId, $exportCourseId);
        $allCols = StudentApplicationModel::getStaffExportColumnOrder();
        $colsParam = trim((string) $this->get('cols', ''));
        $cols = $allCols;
        if ($colsParam !== '') {
            $want = array_values(array_filter(array_map('trim', explode(',', $colsParam)), static function ($s) {
                return $s !== '';
            }));
            if ($want !== []) {
                $allow = array_fill_keys($allCols, true);
                $picked = [];
                foreach ($want as $c) {
                    if (isset($allow[$c])) {
                        $picked[] = $c;
                    }
                }
                if ($picked !== []) {
                    $cols = $picked;
                }
            }
        }

        // PhpSpreadsheet XLSX export requires common XML/ZIP extensions (+ mbstring/iconv on many builds).
        $needs = [
            'zip' => extension_loaded('zip') && class_exists('ZipArchive'),
            'xmlwriter' => extension_loaded('xmlwriter'),
            'dom' => extension_loaded('dom'),
            'simplexml' => extension_loaded('simplexml'),
            'xml' => extension_loaded('xml'),
            'mbstring' => extension_loaded('mbstring') && function_exists('mb_strlen'),
            'iconv' => extension_loaded('iconv') && function_exists('iconv'),
        ];
        foreach ($needs as $ok) {
            if (!$ok) {
                $xlsFallback($rows, $cols, $exportStatus);
            }
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheetTitle = 'Applications';
            if ($exportStatus === 'new') {
                $sheetTitle = 'New';
            } elseif ($exportStatus === 'approved') {
                $sheetTitle = 'Approved';
            } elseif ($exportStatus === 'rejected') {
                $sheetTitle = 'Rejected';
            }
            if ($exportLevel !== null) {
                $sheetTitle .= ' L' . $exportLevel;
            }
            if ($exportDeptId !== null) {
                $sheetTitle .= ' Dept';
            }
            if ($exportCourseId !== null) {
                $sheetTitle .= ' Course';
            }
            $sheetTitle = substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $sheetTitle), 0, 31) ?: 'Applications';
            $sheet->setTitle($sheetTitle);

            // Header row (A1-style coordinates; PhpSpreadsheet 1.x / 2.x)
            $c = 1;
            foreach ($cols as $colName) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . '1';
                $sheet->setCellValue($coord, $colName);
                $c++;
            }
            $sheet->freezePane('A2');

            // Data rows
            $r = 2;
            foreach ($rows as $row) {
                $c = 1;
                foreach ($cols as $colName) {
                    $val = isset($row[$colName]) ? (string) $row[$colName] : '';
                    $val = str_replace(["\r\n", "\r", "\n"], ' | ', $val);
                    $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                    $sheet->setCellValueExplicit(
                        $coord,
                        $val,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                    $c++;
                }
                $r++;
            }

            // Simple formatting
            $sheet->getStyle('1:1')->getFont()->setBold(true);
            if ($rows !== []) {
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
            }
            foreach (range(1, count($cols)) as $colIdx) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->getColumnDimension($letter)->setAutoSize(true);
            }

            $parts = ['student_applications', date('Y-m-d_H-i')];
            if ($exportStatus !== null) {
                $parts[] = $exportStatus;
            }
            if ($exportLevel !== null) {
                $parts[] = 'level' . $exportLevel;
            }
            if ($exportDeptId !== null) {
                $parts[] = 'dept' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $exportDeptId);
            }
            if ($exportCourseId !== null) {
                $parts[] = 'course' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $exportCourseId);
            }
            $filename = implode('_', $parts) . '.xlsx';

            // Build XLSX on disk first so we never send headers then fail mid-stream; on any failure use HTML/.xls fallback.
            $tmpPath = tempnam(sys_get_temp_dir(), 'slgti_sa_xlsx_');
            if ($tmpPath === false) {
                throw new RuntimeException('Could not create temp file for XLSX export.');
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tmpPath);
            $size = filesize($tmpPath);
            if ($size === false) {
                @unlink($tmpPath);
                throw new RuntimeException('XLSX temp file not readable after write.');
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Cache-Control: private, max-age=0');
            header('Content-Length: ' . (string) $size);
            readfile($tmpPath);
            @unlink($tmpPath);
            exit;
        } catch (Throwable $e) {
            if (isset($tmpPath) && is_string($tmpPath) && $tmpPath !== '') {
                @unlink($tmpPath);
            }
            error_log('StudentApplicationController::adminExportExcel: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . (string) $e->getLine());
            $xlsFallback($rows, $cols, $exportStatus);
        }
    }
}
