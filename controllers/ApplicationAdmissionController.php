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
        $uid = $this->requireLogin();
        $userModel = $this->requireView($uid);
        $model = $this->scheduleModel();

        $tab = $this->get('tab', ApplicationAdmissionScheduleModel::TYPE_ENTRANCE);
        if (!in_array($tab, [ApplicationAdmissionScheduleModel::TYPE_ENTRANCE, ApplicationAdmissionScheduleModel::TYPE_INTERVIEW], true)) {
            $tab = ApplicationAdmissionScheduleModel::TYPE_ENTRANCE;
        }
        $level = $this->get('level', '');
        if ($level !== '' && !in_array($level, ['04', '05'], true)) {
            $level = '';
        }

        $schedules = $model->listSchedules($tab, $level !== '' ? $level : null);
        foreach ($schedules as &$s) {
            $s['entry_count'] = $model->countEntries((int) $s['schedule_id']);
            $s['public_url'] = APP_URL . '/application-admission/public/' . rawurlencode((string) $s['public_token']);
        }
        unset($s);

        return $this->view('application_admission/index', [
            'page' => 'application-admission',
            'schedules' => $schedules,
            'tab' => $tab,
            'levelFilter' => $level,
            'canManage' => $userModel->canManageApplicationAdmissionSchedules($uid),
        ]);
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
        $pickerArgs = [
            (string) $schedule['application_level'],
            $id,
            $onlyStaff,
            $this->scheduleCourseIdOrNull($schedule),
            (string) ($schedule['schedule_type'] ?? ''),
            (string) ($schedule['admission_pathway'] ?? ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW),
            (string) ($schedule['student_language'] ?? ''),
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
        if (($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW
            && $courseIdForPicker !== null
            && ApplicationAdmissionScheduleModel::normalizePathway(
                $schedule['admission_pathway'] ?? null,
                ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW
            ) === ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW
        ) {
            $levelForPicker = (string) ($schedule['application_level'] ?? '');
            $hasEntranceSchedule = $model->hasEntranceScheduleForCourse($levelForPicker, $courseIdForPicker);
            $entranceSelectedCount = count($model->getPassedEntranceApplicationIds($levelForPicker, $courseIdForPicker));
            $pickerEntranceFallback = !$hasEntranceSchedule && $entranceSelectedCount === 0;
        }

        $publicUrl = rtrim(APP_URL, '/') . '/application-admission/public/' . rawurlencode((string) $schedule['public_token']);
        $rollCourseCode = ApplicationAdmissionScheduleModel::rollIndexCourseCodeFromSchedule($schedule);
        $rollFormatPrefix = ApplicationAdmissionScheduleModel::rollNumberPrefixFromSchedule($schedule);
        $rollFormatSample = $entries !== []
            ? ApplicationAdmissionScheduleModel::formatRollNumberForEntry($schedule, $entries[0], 1)
            : ApplicationAdmissionScheduleModel::rollNumberFormatSampleFromSchedule($schedule);

        return $this->view('application_admission/entries', [
            'page' => 'application-admission',
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
        $added = $model->addApplications($id, array_map('intval', $addIds));

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
                $row = $entryRows[$entryId] ?? $entryRows[(string) $entryId] ?? [];
                if (!is_array($row)) {
                    $row = [];
                }
                $model->updateEntry($entryId, $id, [
                    'roll_number' => trim((string) ($row['roll_number'] ?? $entry['roll_number'] ?? '')),
                    'room_or_panel' => trim((string) ($row['room_or_panel'] ?? $entry['room_or_panel'] ?? '')),
                    'notes' => trim((string) ($row['notes'] ?? $entry['notes'] ?? '')),
                    'whatsapp_sent' => !empty($row['whatsapp_sent']) ? 1 : 0,
                ]);
            }
        }
        $this->assignSequentialRollNumbersWhereEmpty($model, $id, $schedule);

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
        $entries = $model->getEntriesWithApplications($id);

        return $this->view('application_admission/selection', [
            'page' => 'application-admission',
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
        $rows = $this->post('selection', []);
        if (is_array($rows)) {
            foreach ($rows as $entryId => $status) {
                $model->updateEntry((int) $entryId, $id, [
                    'selection_status' => (string) $status,
                ]);
            }
        }
        $_SESSION['success'] = $isEntrance
            ? 'Entrance exam results saved. Selected candidates are eligible for interview schedules.'
            : 'Selection list updated.';
        $this->redirect('application-admission/selection?id=' . $id);
    }

    public function pdfSchedule() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $this->streamSchedulePdf($id, true);
    }

    public function pdfSelection() {
        $uid = $this->requireLogin();
        $this->requireView($uid);
        $id = (int) $this->get('id', 0);
        $this->streamSelectionPdf($id, true);
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
        require_once BASE_PATH . '/helpers/ApplicationAdmissionPdfHelper.php';
        $inner = ApplicationAdmissionPdfHelper::renderTemplate('interview_slip.php', [
            'schedule' => $schedule,
            'entry' => $entry,
            'logo_src' => $this->admissionLogoDataUri(),
        ]);
        $html = ApplicationAdmissionPdfHelper::wrapPdfDocument($inner);
        $name = 'interview-schedule-' . preg_replace('/[^0-9A-Za-z]+/', '', $nic) . '.pdf';
        ApplicationAdmissionPdfHelper::streamHtml($html, $name);
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
        $entries = $model->getEntriesWithApplications($scheduleId);
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
        $cardTitle = $isInterview ? 'Interview — Admission Card' : 'Selection Examination — Admission Card';
        $parts = [];
        foreach ($entries as $entry) {
            $entryIdForRoll = (int) ($entry['entry_id'] ?? 0);
            $seq = $courseWiseRollSeq[$entryIdForRoll] ?? 1;
            $roll = ApplicationAdmissionScheduleModel::defaultRollIndexForEntry($schedule, $entry, $seq);
            if (trim((string) ($entry['roll_number'] ?? '')) === '') {
                $entry['roll_number'] = $roll;
            }
            $parts[] = ApplicationAdmissionPdfHelper::renderTemplate('postal_admission_card.php', [
                'schedule' => $schedule,
                'entry' => $entry,
                'logo_src' => $this->admissionLogoDataUri(),
                'mailing' => $this->formatEntryMailingBlock($entry),
                'cardTitle' => $cardTitle,
                'cardSubtitle' => (string) ($schedule['title'] ?? ''),
                'isInterview' => $isInterview,
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
     * @param array<string, mixed> $entry
     * @return array{name: string, address: string, city_line: string}
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
        ];
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
        if ($title === '' || $date === '' || $venue === '') {
            $_SESSION['error'] = 'Title, date, and venue are required.';
            return null;
        }
        $start = trim((string) $this->post('start_time', ''));
        $end = trim((string) $this->post('end_time', ''));
        $courseId = trim((string) $this->post('course_id', ''));
        if ($type === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW && $courseId === '') {
            $_SESSION['error'] = 'Select the course for this interview schedule.';
            return null;
        }
        if ($courseId !== '') {
            if (!$this->courseMatchesApplicationLevel($courseId, $level)) {
                $_SESSION['error'] = 'Selected course does not match the NVQ level.';
                return null;
            }
        }
        require_once BASE_PATH . '/models/StudentApplicationModel.php';
        $studentLanguage = StudentApplicationModel::normalizedStaffLanguageFilter($this->post('student_language', ''));
        if ($studentLanguage === null) {
            $_SESSION['error'] = 'Select the language of instruction for this schedule (Tamil, Sinhala, or English).';
            return null;
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
        if ($type === ApplicationAdmissionScheduleModel::TYPE_ENTRANCE) {
            $data['admission_pathway'] = ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW;
        } else {
            $pathway = ApplicationAdmissionScheduleModel::normalizePathway(
                $this->post('admission_pathway', ''),
                ApplicationAdmissionScheduleModel::PATHWAY_INTERVIEW_ONLY
            );
            $data['admission_pathway'] = $pathway;
        }
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

        return [
            'page' => 'application-admission',
            'schedule' => $schedule,
            'scheduleType' => $type,
            'formAction' => $formAction,
            'coursesByLevel' => $showCourseFields ? [
                '04' => $this->coursesForApplicationLevel('04', $isEntrance, $exceptScheduleId > 0 ? $exceptScheduleId : null),
                '05' => $this->coursesForApplicationLevel('05', $isEntrance, $exceptScheduleId > 0 ? $exceptScheduleId : null),
            ] : ['04' => [], '05' => []],
            'departmentsByLevel' => $showCourseFields ? [
                '04' => $this->departmentsForApplicationLevel('04'),
                '05' => $this->departmentsForApplicationLevel('05'),
            ] : ['04' => [], '05' => []],
            'selectedDepartmentId' => $selectedDepartmentId,
            'requireCourse' => $isInterview,
            'showCourseFields' => $showCourseFields,
            'isInterviewSchedule' => $isInterview,
            'pathwayDefaultMaxApplicants' => $pathwayMax,
            'selectedPathway' => ApplicationAdmissionScheduleModel::normalizePathway(
                $schedule['admission_pathway'] ?? null,
                ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW
            ),
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
            if ($courseId === null) {
                $hint = 'Assign department and course on Edit schedule for this exam centre/venue. Until then, all approved and rejected applicants for this NVQ level and language are listed.';
                $lang = trim((string) ($schedule['student_language'] ?? ''));
                if ($lang !== '') {
                    return 'Approved and rejected ' . $lang . ' applicants. ' . $hint;
                }

                return $hint;
            }
            $hint = 'Applicants already on another entrance exam for this course are not listed.';
            $lang = trim((string) ($schedule['student_language'] ?? ''));
            if ($lang !== '') {
                return 'Approved and rejected ' . $lang . ' applicants (1st preference match). ' . $hint;
            }

            return 'Approved and rejected applicants with matching 1st preference. ' . $hint;
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
        $pathway = ApplicationAdmissionScheduleModel::normalizePathway(
            $schedule['admission_pathway'] ?? null,
            ApplicationAdmissionScheduleModel::PATHWAY_EXAM_AND_INTERVIEW
        );
        $pathLabel = ApplicationAdmissionScheduleModel::pathwayLabel($pathway);
        if ($courseId === null) {
            return 'This interview is set to: ' . $pathLabel . '. Select a course to add applicants.';
        }
        if ($pathway === ApplicationAdmissionScheduleModel::PATHWAY_INTERVIEW_ONLY) {
            $hint = 'Interview only: approved and rejected applicants with matching 1st preference can be added (no entrance exam step for this schedule).';
        } elseif (!$this->scheduleModel()->hasEntranceScheduleForCourse(
            (string) ($schedule['application_level'] ?? ''),
            $courseId
        )) {
            $hint = 'Exam and interview: no entrance exam schedule exists for this course yet, so all matching approved and rejected applicants are listed. After you create an entrance exam and mark Selected candidates, only those candidates will appear here.';
        } elseif (count($this->scheduleModel()->getPassedEntranceApplicationIds(
            (string) ($schedule['application_level'] ?? ''),
            $courseId
        )) === 0) {
            $hint = 'Exam and interview: an entrance exam exists for this course but no applicant is marked Selected yet. Mark results on the entrance exam selection page, or change this schedule to Interview only.';
        } else {
            $hint = 'Exam and interview: only applicants marked Selected on an entrance examination for this course can be added.';
        }
        $lang = trim((string) ($schedule['student_language'] ?? ''));
        if ($lang !== '') {
            $hint .= ' Only applicants with language ' . $lang . ' are listed.';
        }

        return $hint;
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

    private function assignSequentialRollNumbersWhereEmpty(ApplicationAdmissionScheduleModel $model, int $scheduleId, array $schedule): void {
        $entries = ApplicationAdmissionScheduleModel::sortEntryRowsByCourseAndProvince(
            $model->getEntriesWithApplications($scheduleId)
        );
        $seqMap = ApplicationAdmissionScheduleModel::courseWiseSequenceMap($entries);
        foreach ($entries as $entry) {
            if (trim((string) ($entry['roll_number'] ?? '')) !== '') {
                continue;
            }
            $entryId = (int) ($entry['entry_id'] ?? 0);
            if ($entryId <= 0) {
                continue;
            }
            $seq = $seqMap[$entryId] ?? 1;
            $model->updateEntry($entryId, $scheduleId, [
                'roll_number' => ApplicationAdmissionScheduleModel::formatRollNumberForEntry($schedule, $entry, $seq),
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
            $roll = ApplicationAdmissionScheduleModel::defaultRollIndexForEntry($schedule, $row, $seq);
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
     * @return array{schedule_pdf: string, slip_base: string}
     */
    private function publicScheduleDownloadUrls(array $schedule): array {
        $tokenEsc = rawurlencode((string) ($schedule['public_token'] ?? ''));
        $base = rtrim(APP_URL, '/') . '/application-admission/public/' . $tokenEsc;
        $isInterview = ($schedule['schedule_type'] ?? '') === ApplicationAdmissionScheduleModel::TYPE_INTERVIEW;
        $slipSeg = $isInterview ? 'interview-slip' : 'admission-slip';

        return [
            'schedule_pdf' => $base . '/schedule-pdf',
            'slip_base' => $base . '/' . $slipSeg,
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
        $lines[] = 'Index / Roll no.: ' . $roll;
        $lines[] = '';
        $lines[] = 'Download full schedule (PDF):';
        $lines[] = $downloadUrls['schedule_pdf'];
        $lines[] = '';
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
