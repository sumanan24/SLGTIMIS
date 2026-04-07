<?php
/**
 * Public student applications (NVQ Level 04 / 05) — no login required.
 */

class StudentApplicationController extends Controller {

    private const UPLOAD_MAX_BYTES = 5242880; // 5 MB (incoming)
    /** Stored files are compressed to JPEG at or below this size. */
    private const STORED_MAX_BYTES = 102400; // 100 KB
    private const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png'];

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
        if (empty($_SESSION['csrf_student_app'])) {
            $_SESSION['csrf_student_app'] = bin2hex(random_bytes(32));
        }
        $flash = null;
        if ($this->get('submitted') === '1' && !empty($_SESSION['flash_student_application_ok'])) {
            $flash = $_SESSION['flash_student_application_ok'];
            unset($_SESSION['flash_student_application_ok']);
        }
        return $this->renderForm($level, [], [], $flash);
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed> $old
     */
    private function renderForm(string $level, array $errors, array $old, ?string $flashSuccess = null) {
        $title = $level === '04' ? 'Level 04 application' : 'Level 05 application';
        return $this->view('student_application/form', [
            'title' => $title,
            'use_public_layout' => true,
            'application_level' => $level,
            'csrf_token' => $_SESSION['csrf_student_app'] ?? '',
            'errors' => $errors,
            'old' => $old,
            'flash_success' => $flashSuccess,
            'sl_provinces_districts' => $this->getProvinceDistrictMap(),
            'sl_district_postal_codes' => $this->getDistrictPostalMap(),
        ]);
    }

    private function processSubmit(string $level) {
        $token = (string) $this->post('csrf_token', '');
        if (empty($_SESSION['csrf_student_app']) || !hash_equals($_SESSION['csrf_student_app'], $token)) {
            $_SESSION['csrf_student_app'] = bin2hex(random_bytes(32));
            return $this->renderForm($level, ['Your session expired. Please refresh this page and try again.'], $_POST, null);
        }
        $_SESSION['csrf_student_app'] = bin2hex(random_bytes(32));

        $postedLevel = (string) $this->post('application_level', '');
        if ($postedLevel !== $level) {
            return $this->renderForm($level, ['Form level does not match. Please open the form again from the website.'], $_POST, null);
        }

        $errors = $this->validateApplication($level);
        if (!empty($errors)) {
            return $this->renderForm($level, $errors, $_POST, null);
        }

        $row = $this->buildRowFromPost($level);
        $model = $this->model('StudentApplicationModel');
        $sqlErr = null;
        $newId = $model->insertApplication($row, $sqlErr);

        if ($newId === false) {
            $msg = 'We could not save your form. ';
            if ($sqlErr && (stripos($sqlErr, 'Duplicate') !== false || stripos($sqlErr, 'uq_') !== false)) {
                $msg .= 'You already sent an application with this ID card or email for this level.';
            } else {
                $msg .= 'Please try again or phone the institute.';
            }
            if ($sqlErr) {
                error_log('StudentApplication insert: ' . $sqlErr);
            }
            return $this->renderForm($level, [$msg], $_POST, null);
        }

        $paths = [];
        try {
            $nic = $this->normalizeNic((string) $this->post('student_nic', ''));
            $paths = $this->collectUploads((int) $newId, $nic);
        } catch (Exception $e) {
            return $this->renderForm($level, ['Upload problem: ' . $e->getMessage()], $_POST, null);
        }

        if (!empty($paths)) {
            $model->updateDocumentPaths((int) $newId, $paths);
        }

        $_SESSION['flash_student_application_ok'] = 'Thank you. We received your application. Your reference number is #' . (int) $newId . '.';
        header('Location: ' . rtrim(APP_URL, '/') . '/level' . $level . 'application?submitted=1', true, 303);
        exit;
    }

    /**
     * Field keys for full G.C.E. O/L + A/L examination blocks.
     *
     * @return list<string>
     */
    private function schoolExamFieldKeys(): array {
        $keys = ['ol_index_number', 'ol_exam_year', 'al_index_number', 'al_exam_year', 'al_stream'];
        for ($i = 1; $i <= 9; $i++) {
            $s = sprintf('%02d', $i);
            $keys[] = 'ol_subject_name_' . $s;
            $keys[] = 'ol_subject_' . $s . '_marks';
        }
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
    private function isSchoolExamPathComplete(callable $t): bool {
        foreach ($this->schoolExamFieldKeys() as $k) {
            if ($t($k) === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param callable(string): string $t
     */
    private function isNvqPathComplete(callable $t): bool {
        foreach ($this->nvqFieldKeys() as $k) {
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
     * @return list<string>
     */
    private function validateApplication(string $level): array {
        $t = function (string $key): string {
            return trim((string) $this->post($key, ''));
        };

        $requiredStrings = [
            'student_title', 'student_full_name', 'student_initial_name', 'student_gender', 'student_civil_status',
            'student_email', 'student_phone', 'student_whatsapp', 'student_nic', 'student_dob',
            'student_language', 'student_religion', 'student_blood_group',
            'student_address', 'student_zip_code', 'student_district', 'student_province',
        ];
        $requiredStrings[] = 'dept_pref_1';
        $requiredStrings[] = 'course_priority_1';

        if ($level === '04') {
            $requiredStrings = array_merge($requiredStrings, $this->schoolExamFieldKeys(), $this->nvqFieldKeys());
        }

        foreach ($requiredStrings as $key) {
            if ($t($key) === '') {
                return ['Please fill in all empty boxes.'];
            }
        }

        if ($level === '05') {
            $school = $this->isSchoolExamPathComplete($t);
            $nvq = $this->isNvqPathComplete($t);
            if (!$school && !$nvq) {
                return ['For Level 05: fill all O/L and A/L, or fill all NVQ boxes, or both. One path must be complete.'];
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

        $gender = $t('student_gender');
        if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
            return ['Please choose male, female, or other.'];
        }
        $civil = $t('student_civil_status');
        if (!in_array($civil, ['Single', 'Married'], true)) {
            return ['Please choose single or married.'];
        }

        $titles = ['Mr', 'Ms', 'Mrs', 'Miss', 'Rev', 'Dr', 'Other'];
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
        $bloods = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!in_array($t('student_blood_group'), $bloods, true)) {
            return ['Please choose a blood group from the list.'];
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

        $school = $level === '05' ? $this->isSchoolExamPathComplete($t) : true;
        $nvq = $level === '05' ? $this->isNvqPathComplete($t) : true;

        if ($level === '04' || $school) {
            $yo = (int) $t('ol_exam_year');
            if ($yo < 1990 || $yo > 2100) {
                return ['O/L year must be between 1990 and 2100.'];
            }
            $ya = (int) $t('al_exam_year');
            if ($ya < 1990 || $ya > 2100) {
                return ['A/L year must be between 1990 and 2100.'];
            }
            for ($i = 1; $i <= 9; $i++) {
                $s = sprintf('%02d', $i);
                $m = $t('ol_subject_' . $s . '_marks');
                if ($m === '' || !is_numeric($m) || (int) $m < 0 || (int) $m > 100) {
                    return ['O/L marks must be numbers from 0 to 100 for every subject.'];
                }
            }
            for ($i = 1; $i <= 3; $i++) {
                $s = sprintf('%02d', $i);
                $m = $t('al_subject_' . $s . '_marks');
                if ($m === '' || !is_numeric($m) || (int) $m < 0 || (int) $m > 100) {
                    return ['A/L marks must be numbers from 0 to 100 for every subject.'];
                }
            }
        }

        if ($level === '04' || $nvq) {
            $yn = (int) $t('nvq_year_completed');
            if ($t('nvq_year_completed') === '' || $yn < 1900 || $yn > 2100) {
                return ['NVQ year finished must be between 1900 and 2100.'];
            }
        }

        $fileFields = [
            'nic_document', 'birth_certificate', 'ol_certificate', 'al_certificate', 'nvq_certificate', 'bank_receipt',
        ];
        foreach ($fileFields as $fk) {
            if (empty($_FILES[$fk]['tmp_name']) || !is_uploaded_file($_FILES[$fk]['tmp_name'])) {
                return ['Please upload every file in the upload section.'];
            }
            if (($_FILES[$fk]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return ['A file did not upload. Please try again with PDF or picture files.'];
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
     * @return array<string, string|null>
     */
    private function buildRowFromPost(string $level): array {
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
            $data['ol_subject_' . $s . '_marks'] = $intOrNull('ol_subject_' . $s . '_marks');
        }
        for ($i = 1; $i <= 3; $i++) {
            $s = sprintf('%02d', $i);
            $data['al_subject_name_' . $s] = $p('al_subject_name_' . $s) ?: null;
            $data['al_subject_' . $s . '_marks'] = $intOrNull('al_subject_' . $s . '_marks');
        }

        $nullFiles = [
            'nic_document_path' => null,
            'birth_certificate_path' => null,
            'ol_certificate_path' => null,
            'al_certificate_path' => null,
            'nvq_certificate_path' => null,
            'bank_receipt_path' => null,
        ];
        return array_merge($data, $nullFiles);
    }

    /**
     * @return array<string, string>
     */
    private function collectUploads(int $applicationId, string $nic): array {
        $map = [
            'nic_document' => 'nic_document_path',
            'birth_certificate' => 'birth_certificate_path',
            'ol_certificate' => 'ol_certificate_path',
            'al_certificate' => 'al_certificate_path',
            'nvq_certificate' => 'nvq_certificate_path',
            'bank_receipt' => 'bank_receipt_path',
        ];
        $out = [];
        foreach ($map as $fileKey => $dbCol) {
            $path = $this->handleUpload($fileKey, $applicationId, $fileKey, $nic);
            if ($path === null) {
                throw new Exception('Missing or invalid upload: ' . $fileKey);
            }
            $out[$dbCol] = $path;
        }
        return $out;
    }

    private function handleUpload(string $fieldName, int $applicationId, string $documentKey, string $nic): ?string {
        if (empty($_FILES[$fieldName]['tmp_name']) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
            return null;
        }
        if (($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
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
        $safe = $base . '.jpg';
        $full = $dir . DIRECTORY_SEPARATOR . $safe;
        $tmp = (string) $_FILES[$fieldName]['tmp_name'];

        if ($ext === 'pdf') {
            $raster = $this->pdfFirstPageToTempJpeg($tmp);
            try {
                $this->compressRasterToJpegUnderLimit($raster, $full);
            } finally {
                @unlink($raster);
            }
        } else {
            $this->compressRasterToJpegUnderLimit($tmp, $full);
        }

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
                $im->setImageBackgroundColor(new ImagickPixel('white'));
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
     * Staff: list applications (DataTables).
     */
    public function adminIndex() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        $role = $userModel->getUserRole($uid);
        $allowed = $userModel->isSAO($uid) || $userModel->isAdminOrADM($uid);
        if (!$allowed && !in_array($role, ['REG', 'DIR'], true)) {
            $_SESSION['error'] = 'You cannot open this page.';
            $this->redirect('dashboard');
            return;
        }

        $model = $this->model('StudentApplicationModel');
        $rows = $model->getAllForAdmin();

        $this->view('student_application/admin_index', [
            'title' => 'Online applications',
            'page' => 'student-applications',
            'applications' => $rows,
            'use_public_layout' => false,
        ]);
    }

    /**
     * Staff: view one application.
     */
    public function adminView() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $userModel = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        $role = $userModel->getUserRole($uid);
        $allowed = $userModel->isSAO($uid) || $userModel->isAdminOrADM($uid);
        if (!$allowed && !in_array($role, ['REG', 'DIR'], true)) {
            $_SESSION['error'] = 'You cannot open this page.';
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

        $this->view('student_application/admin_view', [
            'title' => 'Application #' . $id,
            'page' => 'student-applications',
            'app' => $app,
        ]);
    }
}
