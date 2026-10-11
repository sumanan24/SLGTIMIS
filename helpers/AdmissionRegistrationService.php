<?php
/**
 * Confirm a selected applicant into the existing student / enrollment / login records.
 */

require_once BASE_PATH . '/models/StudentModel.php';
require_once BASE_PATH . '/models/StudentEnrollmentModel.php';
require_once BASE_PATH . '/models/StudentApplicationModel.php';
require_once BASE_PATH . '/models/AcademicYearModel.php';
require_once BASE_PATH . '/models/CourseModel.php';
require_once BASE_PATH . '/models/GroupModel.php';
require_once BASE_PATH . '/models/UserModel.php';
require_once BASE_PATH . '/helpers/StudentApplicationMergedPdf.php';

class AdmissionRegistrationService {
    public const DOCUMENT_LABELS = [
        'nic_document_path' => 'NIC copy',
        'birth_certificate_path' => 'Birth certificate',
        'ol_certificate_path' => 'O/L certificate',
        'al_certificate_path' => 'A/L certificate',
        'nvq_certificate_path' => 'NVQ certificate',
        'bank_receipt_path' => 'Bank receipt',
    ];

    public const COURSE_MODES = [
        'Full' => 'Full Time (Day / Morning)',
        'Part' => 'Part Time (Evening / Afternoon)',
    ];

    private StudentModel $students;
    private StudentEnrollmentModel $enrollments;
    private StudentApplicationModel $applications;
    private AcademicYearModel $years;
    private CourseModel $courses;
    private GroupModel $groups;
    private UserModel $users;

    public function __construct() {
        $this->students = new StudentModel();
        $this->enrollments = new StudentEnrollmentModel();
        $this->applications = new StudentApplicationModel();
        $this->years = new AcademicYearModel();
        $this->courses = new CourseModel();
        $this->groups = new GroupModel();
        $this->users = new UserModel();
        $this->users->ensureMustChangePasswordColumn();
        $this->students->addStudentDocumentsPdfColumnIfNotExists();
    }

    public function assignmentOptions(?string $preferredCourseId = null, ?string $preferredCourseName = null): array {
        $academicYears = $this->years->getAll();
        $courses = $this->courses->getCoursesWithDepartment();
        $suggestedCourse = $this->resolveCourse($preferredCourseId, $preferredCourseName);
        if ($suggestedCourse) {
            $sid = (string) ($suggestedCourse['course_id'] ?? '');
            $found = false;
            foreach ($courses as $c) {
                if ((string) ($c['course_id'] ?? '') === $sid) {
                    $found = true;
                    break;
                }
            }
            if (!$found && $sid !== '') {
                array_unshift($courses, $suggestedCourse);
            }
        }
        $defaultYear = '';
        foreach ($academicYears as $year) {
            if (($year['academic_year_status'] ?? '') === 'Active') {
                $defaultYear = (string) $year['academic_year'];
                break;
            }
        }
        if ($defaultYear === '' && $academicYears !== []) {
            $defaultYear = (string) $academicYears[0]['academic_year'];
        }

        return [
            'academic_years' => $academicYears,
            'courses' => $courses,
            'course_modes' => self::COURSE_MODES,
            'suggested_course' => $suggestedCourse,
            'default_academic_year' => $defaultYear,
        ];
    }

    public function groupsFor(string $courseId, string $academicYear): array {
        $courseId = trim($courseId);
        $academicYear = trim($academicYear);
        if ($courseId === '' || $academicYear === '') {
            return [];
        }
        return $this->groups->getGroupsByCourseAndYear($courseId, $academicYear);
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array<string, array<string, mixed>> keyed by normalized NIC
     */
    public function statusesForEntries(array $entries): array {
        $nics = [];
        foreach ($entries as $row) {
            $nic = $this->normalizeNic((string) ($row['student_nic'] ?? ''));
            if ($nic !== '') {
                $nics[$nic] = $nic;
            }
        }
        if ($nics === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($nics), '?'));
        $types = str_repeat('s', count($nics));
        $values = array_values($nics);
        $db = Database::getInstance();
        $sql = "SELECT `student_id`, `student_nic`, `student_documents_pdf` FROM `student` WHERE UPPER(TRIM(`student_nic`)) IN ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $students = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[$this->normalizeNic((string) $row['student_nic'])] = $row;
        }

        $studentIds = [];
        foreach ($students as $row) {
            $studentIds[] = (string) $row['student_id'];
        }

        $enrolled = [];
        $accounts = [];
        if ($studentIds !== []) {
            $ph = implode(',', array_fill(0, count($studentIds), '?'));
            $t = str_repeat('s', count($studentIds));
            $st = $db->prepare("SELECT `student_id`, `course_id`, `academic_year`, `course_mode` FROM `student_enroll` WHERE `student_id` IN ({$ph})");
            $st->bind_param($t, ...$studentIds);
            $st->execute();
            $er = $st->get_result();
            while ($row = $er->fetch_assoc()) {
                $enrolled[(string) $row['student_id']][] = $row;
            }

            $names = array_merge($studentIds, array_values($nics));
            $ph2 = implode(',', array_fill(0, count($names), '?'));
            $t2 = str_repeat('s', count($names));
            $st2 = $db->prepare("SELECT `user_name`, `user_active` FROM `user` WHERE `user_table` = 'student' AND `user_name` IN ({$ph2})");
            $st2->bind_param($t2, ...$names);
            $st2->execute();
            $ur = $st2->get_result();
            while ($row = $ur->fetch_assoc()) {
                $accounts[strtoupper(trim((string) $row['user_name']))] = $row;
            }
        }

        $out = [];
        foreach ($nics as $nic) {
            $student = $students[$nic] ?? null;
            $sid = $student['student_id'] ?? '';
            $out[$nic] = [
                'student' => $student,
                'student_id' => $sid,
                'enrollments' => $sid !== '' ? ($enrolled[$sid] ?? []) : [],
                'has_account' => $sid !== '' && (
                    isset($accounts[strtoupper((string) $sid)]) || isset($accounts[$nic])
                ),
                'registered' => $sid !== '' && !empty($enrolled[$sid]),
            ];
        }
        return $out;
    }

    public function loadContext(int $scheduleId, int $entryId): array {
        require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';
        $scheduleModel = new ApplicationAdmissionScheduleModel();
        $schedule = $scheduleModel->findSchedule($scheduleId);
        if (!$schedule) {
            throw new RuntimeException('Schedule not found.');
        }
        $entry = $this->findEntry($scheduleModel, $scheduleId, $entryId);
        if (!$entry) {
            throw new RuntimeException('Applicant is not on this schedule.');
        }
        $application = $this->applications->findById((int) $entry['application_id']);
        if (!$application) {
            throw new RuntimeException('Admission application not found.');
        }
        $nic = $this->normalizeNic((string) ($application['student_nic'] ?? ''));
        if ($nic === '') {
            throw new RuntimeException('This application has no NIC, so it cannot be registered.');
        }
        $student = $this->students->findByNic($nic);
        $options = $this->assignmentOptions(
            (string) ($schedule['course_id'] ?? ''),
            (string) ($application['course_priority_1'] ?? '')
        );
        $enrollment = null;
        $account = null;
        $groupRow = null;
        if ($student) {
            $suggestedId = (string) (($options['suggested_course']['course_id'] ?? ''));
            $year = (string) $options['default_academic_year'];
            if ($suggestedId !== '' && $year !== '') {
                $enrollment = $this->enrollments->findEnrollment((string) $student['student_id'], $suggestedId, $year);
            }
            if (!$enrollment) {
                $enrollment = $this->enrollments->getCurrentEnrollment((string) $student['student_id']);
            }
            $account = $this->users->findStudentLogin($nic, (string) $student['student_id']);
            $groupRow = $this->activeGroupForStudent((string) $student['student_id']);
        }

        $documents = $this->applicationDocuments($application);

        return [
            'schedule' => $schedule,
            'entry' => $entry,
            'application' => $application,
            'student' => $student,
            'enrollment' => $enrollment,
            'account' => $account,
            'group' => $groupRow,
            'documents' => $documents,
            'options' => $options,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors:list<string>, summary:?array<string, mixed>}
     */
    public function register(array $input): array {
        $scheduleId = (int) ($input['schedule_id'] ?? 0);
        $entryId = (int) ($input['entry_id'] ?? 0);
        $academicYear = trim((string) ($input['academic_year'] ?? ''));
        $courseId = trim((string) ($input['course_id'] ?? ''));
        $courseMode = $this->normalizeCourseMode((string) ($input['course_mode'] ?? ''));
        $groupId = (int) ($input['group_id'] ?? 0);
        $confirmed = !empty($input['confirm_registration']);

        $errors = [];
        if (!$confirmed) {
            $errors[] = 'Confirm the registration summary before saving.';
        }
        if ($academicYear === '' || !$this->years->exists($academicYear)) {
            $errors[] = 'Select a valid academic year.';
        }
        $course = $courseId !== '' ? $this->courses->find($courseId) : null;
        if (!$course) {
            $errors[] = 'Select a valid course.';
        } elseif (!$this->courses->isActiveForStudents($courseId)) {
            $errors[] = 'The selected course is not open for enrollment.';
        }
        if ($courseMode === '') {
            $errors[] = 'Select a shift / course mode (Full Time or Part Time).';
        }

        $matchingGroups = ($course && $academicYear !== '') ? $this->groupsFor($courseId, $academicYear) : [];
        $selectedGroup = null;
        if ($matchingGroups !== []) {
            foreach ($matchingGroups as $g) {
                if ((int) $g['id'] === $groupId) {
                    $selectedGroup = $g;
                    break;
                }
            }
            if ($selectedGroup === null) {
                $errors[] = 'Select a student group that matches the course and academic year.';
            }
        } elseif ($groupId > 0) {
            $errors[] = 'The selected group does not belong to this course and academic year.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'summary' => null];
        }

        try {
            $ctx = $this->loadContext($scheduleId, $entryId);
        } catch (RuntimeException $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()], 'summary' => null];
        }

        $application = $ctx['application'];
        $nic = $this->normalizeNic((string) ($application['student_nic'] ?? ''));
        $existingStudent = $ctx['student'];

        if ($existingStudent) {
            $dup = $this->enrollments->findEnrollment((string) $existingStudent['student_id'], $courseId, $academicYear);
            if ($dup) {
                return [
                    'ok' => false,
                    'errors' => ['This student is already enrolled in the selected course for this academic year.'],
                    'summary' => null,
                ];
            }
        }

        // MySQL treats DDL as an implicit commit. Run schema guards before the transaction.
        require_once BASE_PATH . '/models/CourseModel.php';
        (new CourseModel())->ensureCourseVersionTable();
        $this->enrollments->ensureCourseVersionColumn();
        $this->users->ensureMustChangePasswordColumn();

        $db = Database::getInstance();
        $db->begin_transaction();
        $newStudent = false;
        try {
            if ($existingStudent) {
                $student = $existingStudent;
                $studentId = (string) $student['student_id'];
            } else {
                $studentId = $this->createStudentFromApplication($application, $courseId, $academicYear, $courseMode);
                $student = $this->students->find($studentId);
                if (!$student) {
                    throw new RuntimeException('The student record could not be created.');
                }
                $newStudent = true;
            }

            $exitDate = $this->enrollmentExitDate($academicYear);
            $enrolled = $this->enrollments->createEnrollment([
                'student_id' => $studentId,
                'course_id' => $courseId,
                'academic_year' => $academicYear,
                'course_mode' => $courseMode,
                'student_enroll_status' => 'Following',
                'student_enroll_date' => date('Y-m-d'),
                'student_enroll_exit_date' => $exitDate,
            ]);
            if (!$enrolled) {
                $detail = trim((string) $this->enrollments->getLastSqlError());
                throw new RuntimeException(
                    $detail !== ''
                        ? 'Enrollment could not be saved. ' . $detail
                        : 'Enrollment could not be saved. The student may already be enrolled for this course and year.'
                );
            }

            if ($selectedGroup !== null) {
                if (!$this->groups->addStudentToGroupQuiet((int) $selectedGroup['id'], $studentId)) {
                    throw new RuntimeException('The student could not be added to the selected group.');
                }
            }

            $this->markEntrySelected($scheduleId, $entryId);
            $pdfInfo = $this->storeCombinedDocumentsPdf($application, $studentId);

            $email = trim((string) ($student['student_email'] ?? $application['student_email'] ?? ''));
            $account = $this->users->linkOrCreateStudentAccount($nic, $studentId, $email, $newStudent);
            if (empty($account['user_id'])) {
                throw new RuntimeException('The student login account could not be created.');
            }

            $db->commit();

            $this->students->syncUserActiveWithStudentStatus($studentId);

            return [
                'ok' => true,
                'errors' => [],
                'summary' => [
                    'student_id' => $studentId,
                    'student_name' => (string) ($student['student_fullname'] ?? $application['student_full_name'] ?? ''),
                    'nic' => $nic,
                    'course_id' => $courseId,
                    'course_name' => (string) ($course['course_name'] ?? $courseId),
                    'academic_year' => $academicYear,
                    'course_mode' => $courseMode,
                    'course_mode_label' => self::COURSE_MODES[$courseMode] ?? $courseMode,
                    'group_name' => $selectedGroup['name'] ?? 'Not assigned (no matching group)',
                    'username' => $account['username'],
                    'account_created' => !empty($account['created']) || $newStudent,
                    'account_existing' => empty($account['created']) && !$newStudent,
                    'must_change_password' => true,
                    'default_password_note' => 'Initial password is the student NIC. The student must change the password and complete personal and parent details at first login.',
                    'student_created' => $newStudent,
                    'documents_pdf' => $pdfInfo['stored'] ?? false,
                    'documents_skipped' => $pdfInfo['skipped'] ?? [],
                    'document_count' => $pdfInfo['count'] ?? 0,
                ],
            ];
        } catch (Throwable $e) {
            $db->rollback();
            error_log('AdmissionRegistrationService: ' . $e->getMessage());
            return [
                'ok' => false,
                'errors' => ['Registration was not saved. ' . $e->getMessage()],
                'summary' => null,
            ];
        }
    }

    /**
     * @return list<array{column:string,label:string,path:string,absolute:?string,available:bool}>
     */
    public function applicationDocuments(array $application): array {
        $docs = [];
        foreach (StudentApplicationModel::DOCUMENT_PATH_COLUMNS as $col) {
            $rel = trim((string) ($application[$col] ?? ''));
            $abs = $rel !== '' ? $this->resolveUploadedFileAbsolutePath($rel) : null;
            $docs[] = [
                'column' => $col,
                'label' => self::DOCUMENT_LABELS[$col] ?? $col,
                'path' => $rel,
                'absolute' => $abs,
                'available' => $abs !== null,
            ];
        }
        return $docs;
    }

    public function buildCombinedPdfBinary(array $application, string $studentLabel, array &$skipped = []): string {
        $docs = [];
        foreach ($this->applicationDocuments($application) as $doc) {
            if (!empty($doc['available']) && !empty($doc['absolute'])) {
                $docs[] = ['label' => $doc['label'], 'path' => $doc['absolute']];
            } elseif (($doc['path'] ?? '') !== '') {
                $skipped[] = $doc['label'] . ' — stored path is not readable.';
            }
        }
        $title = 'Student documents — ' . $studentLabel;
        return StudentApplicationMergedPdf::mergeDocumentsOnly($docs, $title, $skipped);
    }

    public function findApplicationForStudent(string $studentId): ?array {
        $student = $this->students->find($studentId);
        if (!$student) {
            return null;
        }
        $nic = $this->normalizeNic((string) ($student['student_nic'] ?? ''));
        if ($nic === '') {
            return null;
        }
        $rows = $this->applications->findAllByNormalizedNic($nic);
        return $rows[0] ?? null;
    }

    public function resolveStudentPortalId(string $loginName): ?string {
        $loginName = trim($loginName);
        if ($loginName === '') {
            return null;
        }
        $byId = $this->students->find($loginName);
        if ($byId) {
            return (string) $byId['student_id'];
        }
        $byNic = $this->students->findByNic($loginName);
        return $byNic ? (string) $byNic['student_id'] : null;
    }

    private function createStudentFromApplication(array $application, string $courseId, string $academicYear, string $courseMode): string {
        $nic = $this->normalizeNic((string) ($application['student_nic'] ?? ''));
        $fullName = trim((string) ($application['student_full_name'] ?? ''));
        if ($fullName === '') {
            throw new RuntimeException('The application has no student name.');
        }
        $displayMode = $courseMode === 'Part' ? 'Part Time' : 'Full Time';
        $studentId = $this->students->getNextAvailableRegistrationNumber($courseId, $academicYear, $displayMode);
        if (!$studentId) {
            throw new RuntimeException('A student registration number could not be generated.');
        }

        $email = trim((string) ($application['student_email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $this->users->findByEmail($email)) {
            $email = strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $studentId) ?: 'student') . '@slgtimis.local';
        }

        $data = [
            'student_id' => $studentId,
            'student_fullname' => $fullName,
            'student_nic' => $nic,
            'student_email' => $email,
            'student_status' => 'Active',
        ];
        $title = trim((string) ($application['student_title'] ?? ''));
        if ($title !== '') {
            $data['student_title'] = substr($title, 0, 5);
        }
        $ini = trim((string) ($application['student_initial_name'] ?? ''));
        if ($ini !== '') {
            $data['student_ininame'] = $ini;
        }
        $gender = trim((string) ($application['student_gender'] ?? ''));
        if ($gender !== '') {
            $data['student_gender'] = substr($gender, 0, 10);
        }
        $civil = trim((string) ($application['student_civil_status'] ?? ''));
        if ($civil !== '') {
            $data['student_civil'] = substr($civil, 0, 10);
        }
        $dob = trim((string) ($application['student_dob'] ?? ''));
        if ($dob !== '') {
            $data['student_dob'] = substr($dob, 0, 10);
        }
        $phoneDigits = preg_replace('/\D+/', '', (string) ($application['student_phone'] ?? ''));
        if ($phoneDigits !== '' && strlen($phoneDigits) <= 10) {
            $data['student_phone'] = (string) (int) $phoneDigits;
        }
        $address = trim((string) ($application['student_address'] ?? ''));
        if ($address !== '') {
            $data['student_address'] = $address;
        }
        $zip = preg_replace('/\D+/', '', (string) ($application['student_zip_code'] ?? ''));
        if ($zip !== '') {
            $data['student_zip'] = $zip;
        }
        $district = trim((string) ($application['student_district'] ?? ''));
        if ($district !== '') {
            $data['student_district'] = substr($district, 0, 20);
        }
        $province = trim((string) ($application['student_province'] ?? ''));
        if ($province !== '') {
            $data['student_provice'] = $province;
        }
        $blood = trim((string) ($application['student_blood_group'] ?? ''));
        if ($blood !== '') {
            $data['student_blood'] = substr($blood, 0, 5);
        }
        $whatsapp = trim((string) ($application['student_whatsapp'] ?? ''));
        if ($whatsapp !== '') {
            $data['student_whatsapp'] = substr($whatsapp, 0, 20);
        }
        $religion = trim((string) ($application['student_religion'] ?? ''));
        if ($religion !== '') {
            $data['student_religion'] = substr($religion, 0, 20);
        }
        $language = trim((string) ($application['student_language'] ?? ''));
        if ($language !== '') {
            $data['student_nationality'] = substr($language, 0, 20);
        }

        $created = $this->students->createStudent($data);
        if (!$created) {
            throw new RuntimeException('The student record could not be created. Check that the NIC and email are unique.');
        }
        return $studentId;
    }

    private function storeCombinedDocumentsPdf(array $application, string $studentId): array {
        $skipped = [];
        $docs = $this->applicationDocuments($application);
        $available = array_values(array_filter($docs, static fn ($d) => !empty($d['available'])));
        if ($available === []) {
            return ['stored' => false, 'count' => 0, 'skipped' => []];
        }
        $label = $studentId . ' / ' . (string) ($application['student_full_name'] ?? '');
        try {
            $binary = $this->buildCombinedPdfBinary($application, $label, $skipped);
        } catch (Throwable $e) {
            error_log('Admission documents PDF: ' . $e->getMessage());
            return ['stored' => false, 'count' => count($available), 'skipped' => [$e->getMessage()]];
        }

        $dir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'student_documents';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['stored' => false, 'count' => count($available), 'skipped' => $skipped];
        }
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $studentId) ?: 'student';
        $filename = $safe . '.pdf';
        $full = $dir . DIRECTORY_SEPARATOR . $filename;
        if (@file_put_contents($full, $binary) === false) {
            return ['stored' => false, 'count' => count($available), 'skipped' => $skipped];
        }
        $this->students->updateStudent($studentId, ['student_documents_pdf' => $filename]);
        return ['stored' => true, 'count' => count($available), 'skipped' => $skipped];
    }

    private function markEntrySelected(int $scheduleId, int $entryId): void {
        require_once BASE_PATH . '/models/ApplicationAdmissionScheduleModel.php';
        $model = new ApplicationAdmissionScheduleModel();
        $model->updateEntry($entryId, $scheduleId, [
            'selection_status' => ApplicationAdmissionScheduleModel::SELECTION_SELECTED,
        ]);
    }

    private function findEntry(ApplicationAdmissionScheduleModel $model, int $scheduleId, int $entryId): ?array {
        foreach ($model->getEntriesWithApplications($scheduleId) as $row) {
            if ((int) ($row['entry_id'] ?? 0) === $entryId) {
                return $row;
            }
        }
        return null;
    }

    private function resolveCourse(?string $courseId, ?string $courseName): ?array {
        $courseId = trim((string) $courseId);
        if ($courseId !== '') {
            $found = $this->courses->find($courseId);
            if ($found) {
                return $found;
            }
        }
        $name = trim((string) $courseName);
        if ($name === '') {
            return null;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM `course` WHERE `course_name` = ? OR `course_id` = ? LIMIT 1');
        $stmt->bind_param('ss', $name, $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            return $row;
        }
        $like = '%' . $name . '%';
        $stmt = $db->prepare('SELECT * FROM `course` WHERE `course_name` LIKE ? LIMIT 1');
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    private function normalizeCourseMode(string $mode): string {
        $mode = trim($mode);
        if (in_array($mode, ['Full', 'Part'], true)) {
            return $mode;
        }
        if (strcasecmp($mode, 'Full Time') === 0 || strcasecmp($mode, 'Morning') === 0 || strcasecmp($mode, 'Day') === 0) {
            return 'Full';
        }
        if (strcasecmp($mode, 'Part Time') === 0 || strcasecmp($mode, 'Evening') === 0 || strcasecmp($mode, 'Afternoon') === 0) {
            return 'Part';
        }
        return '';
    }

    private function enrollmentExitDate(string $academicYear): string {
        $year = $this->years->find($academicYear);
        $end = trim((string) ($year['second_semi_end_date'] ?? ''));
        if ($end !== '' && $end !== '0000-00-00') {
            return $end;
        }
        return date('Y-m-d', strtotime('+1 year'));
    }

    private function activeGroupForStudent(string $studentId): ?array {
        $db = Database::getInstance();
        $sql = "SELECT g.* FROM `group_students` gs
                INNER JOIN `groups` g ON g.id = gs.group_id
                WHERE gs.student_id = ? AND gs.status = 'active'
                ORDER BY gs.enrolled_at DESC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('s', $studentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function normalizeNic(string $nic): string {
        return strtoupper(preg_replace('/\s+/', '', trim($nic)) ?? '');
    }

    public function resolveUploadedFileAbsolutePath(string $rel): ?string {
        $rel = trim(str_replace('\\', '/', $rel));
        if ($rel === '' || strpos($rel, '..') !== false) {
            return null;
        }
        $relLower = strtolower($rel);
        $allowed = ['uploads/student_applications/', 'uploads/students_applications/'];
        $ok = false;
        foreach ($allowed as $prefix) {
            if (strncmp($relLower, $prefix, strlen($prefix)) === 0) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            return null;
        }
        $full = realpath(BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
        if ($full === false || !is_file($full)) {
            return null;
        }
        foreach (['student_applications', 'students_applications'] as $dirname) {
            $base = realpath(BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $dirname);
            if ($base !== false && strpos($full, $base . DIRECTORY_SEPARATOR) === 0) {
                return $full;
            }
        }
        return null;
    }
}
