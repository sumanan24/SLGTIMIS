<?php
class LifecycleController extends Controller {
    private $model;

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/models/LifecycleModel.php';
        require_once BASE_PATH . '/helpers/LifecycleAccess.php';
        require_once BASE_PATH . '/helpers/LifecycleForms.php';
        $this->model = new LifecycleModel();
    }

    public function dashboard() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        return $this->view('lifecycle/dashboard', $this->pack($actor, [
            'title' => 'Overview',
            'section' => 'dashboard',
            'filters' => $this->scopeFilters($actor, $this->filters()),
            'summary' => $this->model->dashboard($this->scopeFilters($actor, $this->filters())),
            'courses' => $this->model->courses(),
            'departments' => $this->model->departments(),
            'groups' => $this->model->groups(),
            'import_summary' => $this->model->importSummary(),
        ]));
    }

    public function students() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $filters = $this->scopeFilters($actor, $this->filters());
        return $this->view('lifecycle/students', $this->pack($actor, [
            'title' => 'Student records',
            'section' => 'students',
            'filters' => $filters,
            'result' => $this->model->searchStudents($filters, (int) $this->get('page', 1), 20),
            'courses' => $this->model->courses(),
            'departments' => $this->model->departments(),
            'groups' => $this->model->groups(),
        ]));
    }

    public function profile() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $id = trim((string) $this->get('id', ''));
        if (!$this->canSee($actor, $id)) {
            $_SESSION['error'] = 'That student record is not available to you.';
            $this->redirect('lifecycle/students');
            return;
        }
        $bundle = $this->model->profile($id);
        if (!$bundle) {
            $_SESSION['error'] = 'Student record was not found.';
            $this->redirect('lifecycle/students');
            return;
        }
        $tabs = ['personal', 'admission', 'academic', 'attendance', 'exams', 'progress', 'ojt', 'certificates', 'employment', 'education', 'alumni', 'discipline', 'clearance', 'documents', 'activity'];
        $tab = (string) $this->get('tab', 'personal');
        if (!in_array($tab, $tabs, true)) {
            $tab = 'personal';
        }
        return $this->view('lifecycle/profile', $this->pack($actor, [
            'title' => $bundle['student']['student_fullname'],
            'section' => 'students',
            'tab' => $tab,
            'tabs' => $tabs,
            'bundle' => $bundle,
            'forms' => LifecycleForms::all(),
            'can_write' => !empty($actor['office']) || !empty($actor['hod']) || !empty($actor['finance']),
        ]));
    }

    public function saveProfile() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $id = trim((string) $this->post('student_id', ''));
        $tab = $this->safeTab($this->post('tab', 'personal'));
        if (!$this->csrf() || empty($actor['office']) || !$this->canSee($actor, $id)) {
            $_SESSION['error'] = 'You cannot update this student record.';
            $this->redirect('lifecycle/profile?id=' . urlencode($id) . '&tab=' . $tab);
            return;
        }
        $fields = ['admission_no', 'intake', 'trade', 'course_commencement', 'expected_completion', 'actual_completion', 'previous_employment', 'parent_name', 'parent_phone', 'lifecycle_status', 'discontinuation_reason'];
        $input = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $_POST)) {
                $input[$field] = $this->post($field, '');
            }
        }
        $error = $this->model->saveStudentFields($id, $input, (string) $actor['staff_id']);
        $_SESSION[$error === '' ? 'message' : 'error'] = $error === '' ? 'Student record updated.' : $error;
        $this->redirect('lifecycle/profile?id=' . urlencode($id) . '&tab=' . $tab);
    }

    public function save() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->post('kind', '');
        $id = trim((string) $this->post('student_id', ''));
        $tab = $this->safeTab($this->post('tab', 'personal'));
        $back = 'lifecycle/profile?id=' . urlencode($id) . '&tab=' . $tab;
        if (!$this->csrf() || !LifecycleAccess::canWrite($actor, $kind) || ($id !== '' && !$this->canSee($actor, $id))) {
            $_SESSION['error'] = 'You cannot save that record.';
            $this->redirect($id === '' ? 'lifecycle/dashboard' : $back);
            return;
        }
        try {
            if ($kind === 'register_alumni') {
                $this->model->registerAlumni($id, (string) $actor['staff_id']);
                $_SESSION['message'] = 'Alumni profile created. It stays pending until you verify it.';
                $this->redirect($back);
                return;
            }
            if ($kind === 'verify_alumni') {
                if (empty($actor['can_approve'])) {
                    $_SESSION['error'] = 'Verification is limited to the registrar and directors.';
                    $this->redirect($back);
                    return;
                }
                $error = $this->model->verifyAlumni($id, (string) $actor['staff_id']);
                $_SESSION[$error === '' ? 'message' : 'error'] = $error === '' ? 'Alumni profile verified.' : $error;
                $this->redirect($back);
                return;
            }
            if ($kind === 'clearance') {
                $section = (string) $this->post('section', '');
                if ($section === 'Final' && empty($actor['can_approve'])) {
                    $_SESSION['error'] = 'Final completion approval is limited to authorised officers.';
                    $this->redirect($back);
                    return;
                }
                if (!LifecycleAccess::canClearSection($actor, $section)) {
                    $_SESSION['error'] = 'You cannot update that clearance section.';
                    $this->redirect($back);
                    return;
                }
                $error = $this->model->setClearance($id, $section, (string) $this->post('status', ''), trim((string) $this->post('note', '')), (string) $actor['staff_id']);
                $_SESSION[$error === '' ? 'message' : 'error'] = $error === '' ? 'Clearance updated.' : $error;
                $this->redirect($back);
                return;
            }
            $spec = LifecycleForms::kind($kind);
            if (!$spec) {
                $_SESSION['error'] = 'Unknown record.';
                $this->redirect($back);
                return;
            }
            if (!empty($spec['needs_student']) && $id === '') {
                $_SESSION['error'] = 'Choose a student first.';
                $this->redirect('lifecycle/students');
                return;
            }
            $data = $this->collect($spec);
            if ($data === null) {
                $this->redirect($back);
                return;
            }
            if (!empty($spec['fixed'])) {
                foreach ($spec['fixed'] as $key => $value) {
                    $data[$key] = $value;
                }
            }
            if (!empty($spec['needs_student'])) {
                $data['student_id'] = $id;
            }
            foreach (['certificate_id' => 'student_certificates', 'ojt_id' => 'student_ojt', 'employment_id' => 'graduate_employment'] as $linkedField => $linkedTable) {
                if (!empty($data[$linkedField]) && !$this->model->ownsRow($linkedTable, (int) $data[$linkedField], $id)) {
                    $_SESSION['error'] = 'Choose a record that belongs to this student.';
                    $this->redirect($back);
                    return;
                }
            }
            if (isset($data['rating']) && $data['rating'] !== null && ((int) $data['rating'] < 1 || (int) $data['rating'] > 5)) {
                $_SESSION['error'] = 'Rating must be from 1 to 5.';
                $this->redirect($back);
                return;
            }
            if (isset($data['survey_year']) && $data['survey_year'] !== null && ((int) $data['survey_year'] < 2016 || (int) $data['survey_year'] > 2100)) {
                $_SESSION['error'] = 'Survey year must be 2016 or later.';
                $this->redirect($back);
                return;
            }
            foreach (['completion_confirmed', 'verified'] as $flag) {
                if (array_key_exists($flag, $data) && ($data[$flag] === null || $data[$flag] === '')) {
                    $data[$flag] = 0;
                }
            }
            if ($kind === 'engagement') {
                $bundle = $this->model->profile($id);
                $alumni = $bundle && !empty($bundle['alumni']) ? $bundle['alumni'][0] : null;
                if (!$alumni) {
                    $_SESSION['error'] = 'Register the alumni profile before adding engagement.';
                    $this->redirect($back);
                    return;
                }
                $data['alumni_id'] = (int) $alumni['id'];
            }
            if ($kind === 'certificate' && empty($data['certificate_no'])) {
                $data['certificate_no'] = $this->model->nextCode('CERT', 'student_certificates', 'certificate_no');
                if (empty($data['awarding_authority'])) {
                    $data['awarding_authority'] = 'SLGTI';
                }
            }
            $attachment = null;
            if ($kind === 'certificate' && !empty($_FILES['attachment']['name'])) {
                $attachment = $this->storeFile('attachment');
                if ($attachment === null) {
                    $this->redirect($back);
                    return;
                }
            }
            $data['created_by'] = (string) $actor['staff_id'];
            $recordId = (int) $this->post('record_id', 0);
            if ($kind === 'industry' && $this->model->duplicateText('ojt_industries', 'name', (string) $data['name'], $recordId)) {
                $_SESSION['error'] = 'An employer with that name is already on file.';
                $this->redirect($this->afterSave($id, $tab, $kind));
                return;
            }
            if ($kind === 'correspondence' && $recordId === 0) {
                $data['reference_no'] = $this->model->nextCode('LET', 'student_correspondence', 'reference_no');
            }
            if ($kind === 'approval') {
                if ($recordId > 0) {
                    $existingApproval = $this->model->findRow('lifecycle_approvals', $recordId);
                    if (!$existingApproval || $existingApproval['status'] !== 'Submitted') {
                        $_SESSION['error'] = 'A reviewed request cannot be edited. Submit a new one.';
                        $this->redirect($this->afterSave($id, $tab, $kind));
                        return;
                    }
                }
                $data['status'] = 'Submitted';
                $data['submitted_by'] = (string) $actor['staff_id'];
            }
            if ($recordId > 0) {
                if (!$this->model->ownsRow($spec['table'], $recordId, $id)) {
                    $_SESSION['error'] = 'That record does not belong to this student.';
                    $this->redirect($this->afterSave($id, $tab, $kind));
                    return;
                }
                $before = $this->model->findRow($spec['table'], $recordId);
                unset($data['created_by']);
                $data['updated_by'] = (string) $actor['staff_id'];
                $this->model->updateRow($spec['table'], $recordId, $data);
                $savedId = $recordId;
                $this->model->writeAudit($id, $kind, $savedId, 'update', isset($before['status']) ? $before['status'] : '', isset($data['status']) ? $data['status'] : $spec['title'], (string) $actor['staff_id']);
            } else {
                $savedId = $this->model->insertRow($spec['table'], $data);
                $this->model->writeAudit($id, $kind, $savedId, 'create', '', $spec['title'], (string) $actor['staff_id']);
            }
            if ($attachment && $savedId) {
                $this->model->updateRow('student_certificates', $savedId, ['attachment_path' => $attachment['path']]);
            }
            $_SESSION['message'] = $spec['title'] . ' saved.';
        } catch (RuntimeException $e) {
            $_SESSION['error'] = 'The record could not be saved.';
        }
        $this->redirect($this->afterSave($id, $tab, $kind));
    }

    public function remove() {
        if (!$this->boot('delete')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->post('kind', '');
        $id = trim((string) $this->post('student_id', ''));
        $tab = $this->safeTab($this->post('tab', 'personal'));
        $spec = LifecycleForms::kind($kind);
        if (!$this->csrf() || !$spec || empty($actor['can_delete']) || !$this->canSee($actor, $id)) {
            $_SESSION['error'] = 'You cannot remove that record.';
            $this->redirect('lifecycle/profile?id=' . urlencode($id) . '&tab=' . $tab);
            return;
        }
        $recordId = (int) $this->post('record_id', 0);
        $reason = trim((string) $this->post('reason', ''));
        if (strlen($reason) < 3) {
            $_SESSION['error'] = 'Enter a short reason before removing the record.';
            $this->redirect($this->afterSave($id, $tab, $kind));
            return;
        }
        if ($spec['table'] === 'student') {
            $_SESSION['error'] = 'Student records are not deleted from this register.';
            $this->redirect($this->afterSave($id, $tab, $kind));
            return;
        }
        if ($this->model->ownsRow($spec['table'], $recordId, $id)) {
            $this->model->softDelete($spec['table'], $recordId, (string) $actor['staff_id'], $reason);
            $this->model->writeAudit($id, $kind, $recordId, 'delete', '', $reason, (string) $actor['staff_id']);
            $_SESSION['message'] = 'Record archived.';
        }
        $this->redirect($this->afterSave($id, $tab, $kind));
    }

    public function restore() {
        if (!$this->boot('delete')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->post('kind', '');
        $spec = LifecycleForms::kind($kind);
        $recordId = (int) $this->post('record_id', 0);
        if (!$this->csrf() || !$spec || empty($actor['can_delete'])) {
            $_SESSION['error'] = 'You cannot restore that record.';
            $this->redirect('lifecycle/records?kind=' . urlencode($kind));
            return;
        }
        $row = $this->model->findRow($spec['table'], $recordId);
        if ($row && $this->model->restoreRow($spec['table'], $recordId)) {
            $studentId = isset($row['student_id']) ? $row['student_id'] : '';
            $this->model->writeAudit($studentId, $kind, $recordId, 'restore', 'archived', 'active', (string) $actor['staff_id']);
            $_SESSION['message'] = 'Record restored.';
        } else {
            $_SESSION['error'] = 'That record is not archived.';
        }
        $this->redirect('lifecycle/records?kind=' . urlencode($kind) . '&deleted=1');
    }

    public function approve() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $recordId = (int) $this->post('record_id', 0);
        $decision = (string) $this->post('decision', '');
        $reason = trim((string) $this->post('rejection_reason', ''));
        $remarks = trim((string) $this->post('remarks', ''));
        if (!$this->csrf() || empty($actor['can_approve']) || !in_array($decision, ['Approved', 'Rejected'], true)) {
            $_SESSION['error'] = 'You cannot review that request.';
            $this->redirect('lifecycle/records?kind=approval');
            return;
        }
        if ($decision === 'Rejected' && $reason === '') {
            $_SESSION['error'] = 'A rejection needs a reason.';
            $this->redirect('lifecycle/records?kind=approval');
            return;
        }
        $row = $this->model->findRow('lifecycle_approvals', $recordId);
        if (!$row || !empty($row['deleted_at']) || $row['status'] !== 'Submitted') {
            $_SESSION['error'] = 'That request is not waiting for review.';
            $this->redirect('lifecycle/records?kind=approval');
            return;
        }
        $this->model->updateRow('lifecycle_approvals', $recordId, [
            'status' => $decision,
            'reviewed_by' => (string) $actor['staff_id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $decision === 'Rejected' ? $reason : null,
            'remarks' => $remarks !== '' ? $remarks : $row['remarks'],
            'updated_by' => (string) $actor['staff_id'],
        ]);
        $this->model->writeAudit($row['student_id'], 'approval', $recordId, $decision, 'Submitted', $decision, (string) $actor['staff_id']);
        $_SESSION['message'] = 'Request ' . strtolower($decision) . '.';
        $this->redirect('lifecycle/records?kind=approval');
    }

    public function bulkStatus() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->post('kind', '');
        $spec = LifecycleForms::kind($kind);
        $status = (string) $this->post('status', '');
        $ids = $this->post('ids', []);
        if (!$this->csrf() || !$spec || empty($actor['office']) || !is_array($ids)) {
            $_SESSION['error'] = 'You cannot update those records.';
            $this->redirect('lifecycle/records?kind=' . urlencode($kind));
            return;
        }
        $allowed = [];
        foreach ($spec['fields'] as $field) {
            if ($field['name'] === 'status' && !empty($field['options'])) {
                $allowed = $field['options'];
            }
        }
        if (!isset($allowed[$status])) {
            $_SESSION['error'] = 'Choose a valid status.';
            $this->redirect('lifecycle/records?kind=' . urlencode($kind));
            return;
        }
        $updated = 0;
        foreach ($ids as $rawId) {
            $recordId = (int) $rawId;
            $row = $this->model->findRow($spec['table'], $recordId);
            if (!$row || !empty($row['deleted_at'])) {
                continue;
            }
            $studentId = isset($row['student_id']) ? (string) $row['student_id'] : '';
            if ($studentId !== '' && !$this->canSee($actor, $studentId)) {
                continue;
            }
            $this->model->updateRow($spec['table'], $recordId, [
                'status' => $status,
                'updated_by' => (string) $actor['staff_id'],
            ]);
            $this->model->writeAudit($studentId, $kind, $recordId, 'bulk-status', isset($row['status']) ? $row['status'] : '', $status, (string) $actor['staff_id']);
            $updated++;
        }
        $_SESSION['message'] = $updated . ' record(s) set to ' . $status . '.';
        $this->redirect('lifecycle/records?kind=' . urlencode($kind));
    }

    public function records() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->get('kind', '');
        $registers = LifecycleForms::registers();
        if ($kind === '' || !isset($registers[$kind])) {
            return $this->view('lifecycle/records', $this->pack($actor, [
                'title' => 'Record registers',
                'section' => 'records',
                'kind' => '',
                'registers' => $registers,
            ]));
        }
        $spec = LifecycleForms::kind($kind);
        $filters = $this->scopeFilters($actor, $this->filters());
        $filters['deleted'] = $this->get('deleted', '') === '1' && !empty($actor['can_delete']) ? '1' : '';
        $filters['sort'] = (string) $this->get('sort', '');
        $filters['dir'] = (string) $this->get('dir', '');
        $perPage = (int) $this->get('per_page', 20);
        $result = $this->model->searchModule($spec, $filters, (int) $this->get('page', 1), $perPage);
        return $this->view('lifecycle/records', $this->pack($actor, [
            'title' => $registers[$kind],
            'section' => 'records',
            'kind' => $kind,
            'spec' => $spec,
            'registers' => $registers,
            'filters' => $filters,
            'result' => $result,
            'per_page' => $result['per_page'],
        ]));
    }

    public function recordForm() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->get('kind', '');
        $spec = LifecycleForms::kind($kind);
        if (!$spec || !LifecycleAccess::canWrite($actor, $kind) || !isset(LifecycleForms::registers()[$kind])) {
            $_SESSION['error'] = 'You cannot open that form.';
            $this->redirect('lifecycle/records');
            return;
        }
        $recordId = (int) $this->get('record_id', 0);
        $record = $recordId > 0 ? $this->model->findRow($spec['table'], $recordId) : [];
        if ($recordId > 0 && (!$record || !empty($record['deleted_at']))) {
            $_SESSION['error'] = 'That record is not available to edit.';
            $this->redirect('lifecycle/records?kind=' . urlencode($kind));
            return;
        }
        $studentId = $record ? (string) ($record['student_id'] ?? '') : trim((string) $this->get('student_id', ''));
        if ($studentId !== '' && !$this->canSee($actor, $studentId)) {
            $_SESSION['error'] = 'That student record is not available to you.';
            $this->redirect('lifecycle/records?kind=' . urlencode($kind));
            return;
        }
        return $this->view('lifecycle/record_form', $this->pack($actor, [
            'title' => ($record ? 'Edit ' : 'Add ') . $spec['title'],
            'section' => 'records',
            'kind' => $kind,
            'spec' => $spec,
            'record' => $record ?: [],
            'student_id' => $studentId,
            'options' => $this->recordOptions($studentId),
            'tab' => (string) $this->get('tab', ''),
        ]));
    }

    public function exportRecords() {
        if (!$this->boot('download')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->get('kind', '');
        $spec = LifecycleForms::kind($kind);
        if (!$spec || !isset(LifecycleForms::registers()[$kind])) {
            $this->redirect('lifecycle/records');
            return;
        }
        $filters = $this->scopeFilters($actor, $this->filters());
        $filters['sort'] = (string) $this->get('sort', '');
        $filters['dir'] = (string) $this->get('dir', '');
        $rows = [];
        $page = 1;
        $pages = 1;
        do {
            $chunk = $this->model->searchModule($spec, $filters, $page, 50);
            $pages = (int) $chunk['pages'];
            foreach ($chunk['rows'] as $row) {
                $rows[] = $row;
            }
            $page++;
        } while ($page <= $pages && count($rows) < 2000);
        $headers = !empty($spec['needs_student']) ? ['Registration', 'Name', 'NIC'] : [];
        foreach (array_slice($spec['fields'], 0, 6) as $field) {
            $headers[] = $field['label'];
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="lifecycle-' . preg_replace('/[^a-z]/', '', $kind) . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            $line = [];
            if (!empty($spec['needs_student'])) {
                $line[] = isset($row['student_id']) ? $row['student_id'] : '';
                $line[] = isset($row['student_fullname']) ? $row['student_fullname'] : '';
                $line[] = isset($row['student_nic']) ? $row['student_nic'] : '';
            }
            foreach (array_slice($spec['fields'], 0, 6) as $field) {
                $line[] = isset($row[$field['name']]) ? $row[$field['name']] : '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit();
    }

    public function printRecords() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $kind = (string) $this->get('kind', '');
        $spec = LifecycleForms::kind($kind);
        if (!$spec || !isset(LifecycleForms::registers()[$kind])) {
            $this->redirect('lifecycle/records');
            return;
        }
        $filters = $this->scopeFilters($actor, $this->filters());
        $chunk = $this->model->searchModule($spec, $filters, 1, 50);
        $rows = $chunk['rows'];
        return $this->view('lifecycle/records_print', [
            'title' => LifecycleForms::registers()[$kind],
            'use_print_layout' => true,
            'spec' => $spec,
            'rows' => $rows,
        ]);
    }

    public function upload() {
        if (!$this->boot('upload')) {
            return;
        }
        $actor = $this->actor();
        $id = trim((string) $this->post('student_id', ''));
        if (!$this->csrf() || empty($actor['office']) || !$this->canSee($actor, $id)) {
            $_SESSION['error'] = 'You cannot upload that document.';
            $this->redirect('lifecycle/profile?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $category = (string) $this->post('category', '');
        $title = trim((string) $this->post('title', ''));
        if (!in_array($category, LifecycleForms::documentCategories(), true) || $title === '') {
            $_SESSION['error'] = 'Choose a category and a document title.';
            $this->redirect('lifecycle/profile?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $stored = $this->storeFile('document');
        if ($stored === null) {
            $this->redirect('lifecycle/profile?id=' . urlencode($id) . '&tab=documents');
            return;
        }
        $issue = trim((string) $this->post('issue_date', ''));
        $this->model->insertRow('student_documents', [
            'student_id' => $id,
            'category' => $category,
            'title' => $title,
            'reference_no' => trim((string) $this->post('reference_no', '')),
            'issue_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $issue) ? $issue : null,
            'file_path' => $stored['path'],
            'original_name' => $stored['name'],
            'version_no' => $this->model->documentVersion($id, $category, $title),
            'record_origin' => 'system',
            'created_by' => (string) $actor['staff_id'],
        ]);
        $_SESSION['message'] = 'Document stored.';
        $this->redirect('lifecycle/profile?id=' . urlencode($id) . '&tab=documents');
    }

    public function download() {
        $this->sendDocument(false);
    }

    public function preview() {
        $this->sendDocument(true);
    }

    public function certificateFile() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $row = $this->model->findCertificate((int) $this->get('id', 0));
        if (!$row || empty($row['attachment_path']) || !$this->canSee($actor, (string) $row['student_id'])) {
            $_SESSION['error'] = 'Certificate file not available.';
            $this->redirect('lifecycle/students');
            return;
        }
        $this->sendStoredFile((string) $row['attachment_path'], (string) $row['student_id'], 'certificate', true);
    }

    private function sendDocument($inline) {
        if (!$this->boot($inline ? 'view' : 'download')) {
            return;
        }
        $actor = $this->actor();
        $doc = $this->model->findDocument((int) $this->get('id', 0));
        if (!$doc || !$this->canSee($actor, (string) $doc['student_id'])) {
            $_SESSION['error'] = 'Document not available.';
            $this->redirect('lifecycle/students');
            return;
        }
        $this->sendStoredFile((string) $doc['file_path'], (string) $doc['student_id'], (string) $doc['original_name'], $inline);
    }

    private function sendStoredFile($relative, $studentId, $downloadName, $inline) {
        $root = realpath(BASE_PATH . '/uploads/lifecycle');
        $file = realpath(BASE_PATH . '/uploads/lifecycle/' . $relative);
        if ($root === false || $file === false || strpos($file, $root) !== 0 || !is_file($file)) {
            $_SESSION['error'] = 'The file is missing.';
            $this->redirect('lifecycle/profile?id=' . urlencode($studentId) . '&tab=documents');
            return;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $downloadName);
        header('Content-Type: ' . (isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . ($name !== '' ? $name : 'document') . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    }

    public function reports() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $filters = $this->scopeFilters($actor, $this->filters());
        $key = $this->reportKey();
        return $this->view('lifecycle/reports', $this->pack($actor, [
            'title' => 'Lifecycle reports',
            'section' => 'reports',
            'filters' => $filters,
            'report_key' => $key,
            'report' => $this->model->report($key, $filters, 50),
            'courses' => $this->model->courses(),
            'departments' => $this->model->departments(),
            'groups' => $this->model->groups(),
        ]));
    }

    public function export() {
        if (!$this->boot('download')) {
            return;
        }
        $actor = $this->actor();
        $report = $this->model->report($this->reportKey(), $this->scopeFilters($actor, $this->filters()), 2000);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="slgti-lifecycle.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $report['headers']);
        foreach ($report['rows'] as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit();
    }

    public function printReport() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $key = $this->reportKey();
        return $this->view('lifecycle/print', [
            'title' => LifecycleForms::reports()[$key],
            'use_print_layout' => true,
            'report' => $this->model->report($key, $this->scopeFilters($actor, $this->filters()), 2000),
        ]);
    }

    public function importForm() {
        if (!$this->officeOnly()) {
            return;
        }
        $actor = $this->actor();
        return $this->view('lifecycle/import', $this->pack($actor, [
            'title' => 'Historical import',
            'section' => 'import',
            'preview' => isset($_SESSION['lifecycle_import']) ? $_SESSION['lifecycle_import'] : null,
            'summary' => $this->model->importSummary(),
        ]));
    }

    public function sampleCsv() {
        if (!$this->officeOnly()) {
            return;
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="slgti-historical-students.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['student_id', 'full_name', 'initials', 'nic', 'gender', 'dob', 'address', 'email', 'admission_no', 'admission_year', 'intake', 'course_id', 'trade', 'registration_date', 'status', 'expected_completion', 'actual_completion', 'parent_name', 'parent_phone', 'previous_employment', 'discontinuation_reason']);
        fclose($out);
        exit();
    }

    public function importPreview() {
        if (!$this->officeOnly()) {
            return;
        }
        if (!$this->csrf()) {
            $_SESSION['error'] = 'The form expired. Open the import page again.';
            $this->redirect('lifecycle/import');
            return;
        }
        $rows = $this->readUpload();
        if ($rows === null) {
            $this->redirect('lifecycle/import');
            return;
        }
        $preview = [];
        foreach ($rows as $i => $row) {
            $class = $this->model->classifyImport($row);
            $preview[] = [
                'row_no' => $i + 2,
                'student_id' => $row['student_id'] ?? '',
                'full_name' => $row['full_name'] ?? '',
                'outcome' => $class['outcome'],
                'message' => $class['message'],
                'data' => $row,
            ];
        }
        $_SESSION['lifecycle_import'] = [
            'file' => (string) ($_FILES['import_file']['name'] ?? 'import.csv'),
            'rows' => $preview,
        ];
        $_SESSION['message'] = 'Preview ready. Nothing has been saved yet.';
        $this->redirect('lifecycle/import');
    }

    public function importCommit() {
        if (!$this->officeOnly()) {
            return;
        }
        if (!$this->csrf() || empty($_SESSION['lifecycle_import']['rows'])) {
            $_SESSION['error'] = 'Preview an import file before saving.';
            $this->redirect('lifecycle/import');
            return;
        }
        $batch = $_SESSION['lifecycle_import'];
        $actor = $this->actor();
        $importId = $this->model->startImport((string) $batch['file'], (string) $actor['staff_id']);
        $success = 0;
        $errors = 0;
        foreach ($batch['rows'] as $row) {
            $result = $this->model->applyImport($importId, $row['data'], (string) $actor['staff_id']);
            $ok = in_array($result['outcome'], ['matched', 'created'], true);
            if ($ok) {
                $success++;
            } else {
                $errors++;
            }
            $this->model->logImport($importId, (int) $row['row_no'], $row['student_id'], $result['outcome'], $result['message']);
            $row['outcome'] = $result['outcome'];
            $row['message'] = $result['message'];
        }
        $this->model->finishImport($importId, count($batch['rows']), $success, $errors);
        unset($_SESSION['lifecycle_import']);
        $_SESSION['message'] = $success . ' rows saved. ' . $errors . ' rows need attention.';
        $this->redirect('lifecycle/import');
    }

    public function verify() {
        require_once BASE_PATH . '/models/LifecycleModel.php';
        $number = trim((string) $this->get('number', ''));
        $certificate = null;
        if ($number !== '') {
            $certificate = (new LifecycleModel())->publicCertificate($number);
        }
        return $this->view('lifecycle/verify', [
            'title' => 'Certificate verification',
            'page' => 'certificate-verify',
            'use_public_layout' => true,
            'seo_robots' => 'noindex, nofollow',
            'number' => $number,
            'certificate' => $certificate,
        ]);
    }

    public function portal() {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if (($_SESSION['user_table'] ?? '') !== 'student') {
            $_SESSION['error'] = 'The alumni page is for a graduate signed in with a student account.';
            $this->redirect('dashboard');
            return;
        }
        require_once BASE_PATH . '/helpers/StudentPortalGuard.php';
        $student = StudentPortalGuard::currentStudent();
        if (!$student) {
            $_SESSION['error'] = 'Your student record could not be opened.';
            $this->redirect('student/dashboard');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrf()) {
                $_SESSION['error'] = 'The form expired. Try again.';
                $this->redirect('alumni/portal');
                return;
            }
            $error = $this->model->savePortal($student, $_POST);
            $_SESSION[$error === '' ? 'message' : 'error'] = $error === '' ? 'Details submitted for verification.' : $error;
            $this->redirect('alumni/portal');
            return;
        }
        $bundle = $this->model->profile($student['student_id']);
        return $this->view('lifecycle/portal', [
            'title' => 'Alumni record',
            'page' => 'alumni-portal',
            'student' => $student,
            'bundle' => $bundle,
            'csrf' => $this->token(),
            'employment_types' => LifecycleForms::employmentTypes(),
        ]);
    }

    private function officeOnly() {
        if (!$this->boot('edit')) {
            return false;
        }
        $actor = $this->actor();
        if (empty($actor['office'])) {
            $_SESSION['error'] = 'Historical import is limited to the registrar and student affairs.';
            $this->redirect('lifecycle/dashboard');
            return false;
        }
        return true;
    }

    private function boot($action) {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (($_SESSION['user_table'] ?? '') === 'student') {
            $this->redirect('student/dashboard');
            return false;
        }
        return $this->requireModule('lifecycle', $action);
    }

    private function actor() {
        return LifecycleAccess::actor((int) $_SESSION['user_id']);
    }

    private function canSee(array $actor, $studentId) {
        if ($studentId === '' || !$this->model->findStudent($studentId)) {
            return false;
        }
        if (!empty($actor['hod']) && empty($actor['office']) && empty($actor['director'])) {
            return $this->model->visibleToDepartment($studentId, (string) $actor['department_id']);
        }
        return true;
    }

    private function scopeFilters(array $actor, array $filters) {
        if (!empty($actor['hod']) && empty($actor['office']) && empty($actor['director'])) {
            $filters['department_limit'] = (string) $actor['department_id'];
        }
        return $filters;
    }

    private function filters() {
        return [
            'q' => trim((string) $this->get('q', '')),
            'year' => $this->academicYearFilter($this->get('year', '')),
            'graduation_year' => preg_match('/^\d{4}$/', (string) $this->get('graduation_year', '')) ? $this->get('graduation_year') : '',
            'course_id' => trim((string) $this->get('course_id', '')),
            'department_id' => trim((string) $this->get('department_id', '')),
            'nvq' => in_array((string) $this->get('nvq', ''), ['3', '4', '5', '6', 'BRI'], true) ? (string) $this->get('nvq') : '',
            'batch' => ctype_digit((string) $this->get('batch', '')) ? (string) $this->get('batch') : '',
            'trade' => substr(trim((string) $this->get('trade', '')), 0, 80),
            'status' => trim((string) $this->get('status', '')),
            'origin' => trim((string) $this->get('origin', '')),
        ];
    }

    private function academicYearFilter($value) {
        $value = trim((string) $value);
        if (preg_match('/^\d{4}\/\d{4}$/', $value) || preg_match('/^\d{4}$/', $value)) {
            return $value;
        }
        return '';
    }

    private function reportKey() {
        $key = (string) $this->get('report', 'enrolment');
        return isset(LifecycleForms::reports()[$key]) ? $key : 'enrolment';
    }

    private function pack(array $actor, array $data) {
        $data['actor'] = $actor;
        $data['page'] = 'lifecycle';
        $data['academic_years'] = $this->model->academicYears();
        $data['filter_links'] = $this->model->filterLinks();
        $data['csrf'] = $this->token();
        $data['message'] = $_SESSION['message'] ?? '';
        $data['error'] = $_SESSION['error'] ?? '';
        unset($_SESSION['message'], $_SESSION['error']);
        return $data;
    }

    private function token() {
        if (empty($_SESSION['lifecycle_csrf'])) {
            $_SESSION['lifecycle_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['lifecycle_csrf'];
    }

    private function csrf() {
        $sent = (string) $this->post('csrf', '');
        $known = (string) ($this->token());
        return $sent !== '' && hash_equals($known, $sent);
    }

    private function safeTab($tab) {
        $tabs = ['personal', 'admission', 'academic', 'attendance', 'exams', 'progress', 'ojt', 'certificates', 'employment', 'education', 'alumni', 'discipline', 'clearance', 'documents', 'activity'];
        return in_array($tab, $tabs, true) ? $tab : 'personal';
    }

    private function collect(array $spec) {
        $parsed = LifecycleForms::normalize($spec, $this->post());
        if ($parsed['error'] !== '') {
            $_SESSION['error'] = $parsed['error'];
            return null;
        }
        return $parsed['data'];
    }

    private function afterSave($studentId, $tab, $kind) {
        if ((string) $this->post('return_to', '') === 'records') {
            return 'lifecycle/records?kind=' . urlencode((string) $kind);
        }
        if ($studentId === '') {
            return 'lifecycle/records?kind=' . urlencode((string) $kind);
        }
        return 'lifecycle/profile?id=' . urlencode((string) $studentId) . '&tab=' . $tab;
    }

    private function recordOptions($studentId) {
        $options = ['certificates' => [], 'industries' => [], 'placements' => [], 'employment' => [], 'events' => []];
        $industries = $this->model->searchModule(LifecycleForms::kind('industry'), [], 1, 50);
        foreach ($industries['rows'] as $row) {
            $options['industries'][$row['id']] = $row['name'];
        }
        $events = $this->model->searchModule(LifecycleForms::kind('event'), [], 1, 50);
        foreach ($events['rows'] as $row) {
            $options['events'][$row['id']] = $row['title'];
        }
        if ($studentId !== '') {
            $owned = ['student_id' => $studentId];
            foreach (['certificate' => 'certificates', 'ojt' => 'placements', 'employment' => 'employment'] as $optionKind => $optionKey) {
                $found = $this->model->searchModule(LifecycleForms::kind($optionKind), $owned, 1, 50);
                foreach ($found['rows'] as $row) {
                    if ($optionKey === 'certificates') {
                        $options[$optionKey][$row['id']] = $row['certificate_no'];
                    } elseif ($optionKey === 'placements') {
                        $options[$optionKey][$row['id']] = (isset($row['status']) ? $row['status'] : 'Placement') . ' ' . $row['id'];
                    } else {
                        $options[$optionKey][$row['id']] = !empty($row['organisation']) ? $row['organisation'] : ('Record ' . $row['id']);
                    }
                }
            }
        }
        return $options;
    }

    private function readUpload() {
        if (empty($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Choose a CSV or Excel file.';
            return null;
        }
        $file = $_FILES['import_file'];
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'The file is larger than 5 MB.';
            return null;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $rows = [];
        if ($ext === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                $_SESSION['error'] = 'The file could not be read.';
                return null;
            }
            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                $_SESSION['error'] = 'The file has no header row.';
                return null;
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $keys = array_map(function ($item) {
                return strtolower(trim((string) $item));
            }, $header);
            while (($line = fgetcsv($handle)) !== false) {
                if (count($rows) >= 500) {
                    break;
                }
                $assoc = [];
                foreach ($keys as $i => $key) {
                    $assoc[$key] = isset($line[$i]) ? trim((string) $line[$i]) : '';
                }
                if (implode('', $assoc) === '') {
                    continue;
                }
                $rows[] = $assoc;
            }
            fclose($handle);
        } elseif (in_array($ext, ['xlsx', 'xls'], true) && is_file(BASE_PATH . '/vendor/autoload.php')) {
            require_once BASE_PATH . '/vendor/autoload.php';
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                $_SESSION['error'] = 'Save the spreadsheet as CSV and upload it again.';
                return null;
            }
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name'])->getActiveSheet()->toArray();
            if (!$sheet) {
                $_SESSION['error'] = 'The spreadsheet is empty.';
                return null;
            }
            $keys = array_map(function ($item) {
                return strtolower(trim((string) $item));
            }, array_shift($sheet));
            foreach ($sheet as $line) {
                if (count($rows) >= 500) {
                    break;
                }
                $assoc = [];
                foreach ($keys as $i => $key) {
                    if ($key === '') {
                        continue;
                    }
                    $assoc[$key] = isset($line[$i]) ? trim((string) $line[$i]) : '';
                }
                if (implode('', $assoc) === '') {
                    continue;
                }
                $rows[] = $assoc;
            }
        } else {
            $_SESSION['error'] = 'Upload a CSV file, or an Excel file saved from this system.';
            return null;
        }
        if (!$rows) {
            $_SESSION['error'] = 'The file has no data rows.';
            return null;
        }
        return $rows;
    }

    private function storeFile($field) {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Choose a PDF or image file.';
            return null;
        }
        $file = $_FILES[$field];
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'The file is larger than 5 MB.';
            return null;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (!isset($allowed[$ext])) {
            $_SESSION['error'] = 'Upload a PDF, JPG or PNG file.';
            return null;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($mime !== $allowed[$ext]) {
            $_SESSION['error'] = 'The file content does not match its extension.';
            return null;
        }
        $folder = date('Y') . '/' . date('m');
        $dir = BASE_PATH . '/uploads/lifecycle/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $_SESSION['error'] = 'The upload folder is not available.';
            return null;
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            $_SESSION['error'] = 'The file could not be stored.';
            return null;
        }
        return ['path' => $folder . '/' . $name, 'name' => (string) $file['name']];
    }
}
