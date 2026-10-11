<?php
/**
 * Facilities / Maintenance tickets.
 */
class FacilitiesController extends Controller {
    private $svc;
    private $assign;
    private $progress;
    private $verify;
    private $reports;

    public function __construct() {
        parent::__construct();
        require_once BASE_PATH . '/helpers/FacilitiesTicketService.php';
        require_once BASE_PATH . '/helpers/FacilitiesAssignmentService.php';
        require_once BASE_PATH . '/helpers/FacilitiesProgressService.php';
        require_once BASE_PATH . '/helpers/FacilitiesVerificationService.php';
        require_once BASE_PATH . '/helpers/FacilitiesReportService.php';
        $this->svc = new FacilitiesTicketService();
        $this->assign = new FacilitiesAssignmentService();
        $this->progress = new FacilitiesProgressService();
        $this->verify = new FacilitiesVerificationService();
        $this->reports = new FacilitiesReportService();
    }

    public function dashboard() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $filters = $this->svc->scopeFilters($actor);
        $summary = $this->reports->summary($filters);
        require_once BASE_PATH . '/helpers/FacilitiesNotificationService.php';
        (new FacilitiesNotificationService())->markDueSoonAndOverdue();
        $queues = [];
        if (!empty($actor['is_officer'])) {
            $queues = [
                'new' => $this->svc->model()->search(['status' => 'new'] + $filters, 1, 6),
                'unassigned' => $this->svc->model()->search(['unassigned' => 1] + $filters, 1, 6),
                'assigned' => $this->svc->model()->search(['status' => 'assigned'] + $filters, 1, 6),
                'in_progress' => $this->svc->model()->search(['status' => 'in_progress'] + $filters, 1, 6),
                'pending' => $this->svc->model()->search(['status' => 'completed_pending_verification'] + $filters, 1, 6),
                'overdue' => $this->svc->model()->search(['overdue' => 1] + $filters, 1, 6),
                'critical' => $this->svc->model()->search(['priority' => 'critical'] + $filters, 1, 6),
                'approaching' => $this->svc->model()->search(['due_soon' => 1] + $filters, 1, 6),
            ];
        }
        $mineScope = $this->svc->scopeFilters($actor, ['staff_id' => $actor['staff_id']]);
        $mineCounts = [
            'mine' => $this->svc->model()->dashboardCounts($this->svc->scopeFilters($actor, ['requester_staff_id' => $actor['staff_id']])),
            'assigned' => $this->svc->model()->dashboardCounts($this->svc->scopeFilters($actor, ['responsible_staff_id' => $actor['staff_id']])),
            'due_soon' => $this->svc->model()->search($this->svc->scopeFilters($actor, ['staff_id' => $actor['staff_id'], 'due_soon' => 1]), 1, 1)['total'] ?? 0,
            'overdue' => $this->svc->model()->search($this->svc->scopeFilters($actor, ['staff_id' => $actor['staff_id'], 'overdue' => 1]), 1, 1)['total'] ?? 0,
        ];
        return $this->view('facilities/dashboard', $this->pack([
            'title' => !empty($actor['is_monitor']) && empty($actor['is_officer']) ? 'Facilities Monitoring' : 'Facilities Dashboard',
            'section' => 'dashboard',
            'summary' => $summary,
            'queues' => $queues,
            'mine_counts' => $mineCounts,
            'mine' => $this->svc->model()->search($mineScope, 1, 8),
        ], $actor));
    }

    public function index() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $filters = $this->svc->scopeFilters($actor, $this->listFilters());
        $result = $this->svc->model()->search($filters, (int) $this->get('page', 1), 20);
        return $this->view('facilities/index', $this->pack([
            'title' => 'Facilities Tickets',
            'section' => 'list',
            'result' => $result,
            'filters' => $filters,
        ], $actor));
    }

    public function create() {
        if (!$this->boot('add')) {
            return;
        }
        $actor = $this->actor();
        return $this->view('facilities/create', $this->pack([
            'title' => 'New Facilities Ticket',
            'section' => 'create',
            'ticket' => null,
        ], $actor));
    }

    public function store() {
        if (!$this->boot('add')) {
            return;
        }
        try {
            $actor = $this->actor();
            $ticket = $this->svc->createTicket($actor, $this->post());
            $this->saveUploads($ticket, $actor, 'initial');
            $this->logActivity('CREATE', 'facilities', $ticket['id'], 'Created ' . $ticket['ticket_number']);
            $_SESSION['message'] = 'Ticket ' . $ticket['ticket_number'] . ' submitted.';
            $this->redirect('facilities/view?id=' . $ticket['id']);
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('facilities/create');
        }
    }

    public function show() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor);
        if (!$ticket) {
            return;
        }
        if (!empty($actor['is_monitor']) || !empty($actor['is_officer'])) {
            $this->logActivity('VIEW', 'facilities', $ticket['id'], $actor['name'] . ' viewed ' . $ticket['ticket_number']);
        }
        return $this->view('facilities/show', $this->detailData($actor, $ticket, 'Ticket ' . $ticket['ticket_number']));
    }

    public function assign() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor, true);
        if (!$ticket || !$this->requireOfficer($actor)) {
            return;
        }
        if ($ticket['status'] === 'new') {
            $ticket = $this->verify->review($actor, $ticket);
        }
        $deptId = $this->get('department_id', $ticket['responsible_department_id'] ?? '');
        return $this->view('facilities/assign', $this->detailData($actor, $ticket, 'Assign ' . $ticket['ticket_number'], [
            'picker' => $this->svc->model()->staffPickerRows($deptId ?: null),
            'selected_department' => $deptId,
        ]));
    }

    public function assignSave() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor, true);
        if (!$ticket) {
            return;
        }
        try {
            $ticket = $this->assign->assign($actor, $ticket, $this->post());
            $this->logActivity('UPDATE', 'facilities', $ticket['id'], 'Assigned ' . $ticket['ticket_number'], null, $this->post());
            $_SESSION['message'] = 'Ticket assigned.';
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirect('facilities/view?id=' . (int) $ticket['id']);
    }

    public function review() {
        $this->officerAction(function ($actor, $ticket) {
            $this->verify->review($actor, $ticket);
            $_SESSION['message'] = 'Ticket is under review.';
        });
    }

    public function accept() {
        $this->workAction(function ($actor, $ticket) {
            $this->progress->accept($actor, $ticket);
            $_SESSION['message'] = 'Assignment accepted.';
        });
    }

    public function start() {
        $this->workAction(function ($actor, $ticket) {
            $this->progress->start($actor, $ticket);
            $_SESSION['message'] = 'Work started.';
        });
    }

    public function progress() {
        $this->workAction(function ($actor, $ticket) {
            $this->progress->updateProgress($actor, $ticket, (int) $this->post('progress_percent', 0), (string) $this->post('work_update', ''), (string) $this->post('remarks', ''));
            $this->saveUploads($ticket, $actor, $this->post('evidence_type', 'during'));
            $_SESSION['message'] = 'Progress saved.';
        });
    }

    public function complete() {
        $this->workAction(function ($actor, $ticket) {
            $this->saveUploads($ticket, $actor, $this->post('evidence_type', 'completion'));
            $this->progress->submitCompletion($actor, $ticket, (string) $this->post('description', ''), (string) $this->post('completed_date', date('Y-m-d')), (string) $this->post('remarks', ''));
            $_SESSION['message'] = 'Completion submitted for verification.';
        });
    }

    public function verify() {
        if (!$this->boot('approve')) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor, true);
        if (!$ticket || !$this->requireOfficer($actor)) {
            return;
        }
        return $this->view('facilities/verify', $this->detailData($actor, $ticket, 'Verify ' . $ticket['ticket_number']));
    }

    public function verifySave() {
        $this->officerAction(function ($actor, $ticket) {
            $decision = $this->post('decision', 'approve');
            if ($decision === 'return') {
                $this->verify->returnForCorrection($actor, $ticket, (string) $this->post('reason', ''), (string) $this->post('required_correction', ''), $this->post('new_deadline') ?: null);
                $_SESSION['message'] = 'Returned for correction.';
            } else {
                $this->verify->verify($actor, $ticket, (string) $this->post('remarks', ''));
                $_SESSION['message'] = 'Work verified.';
            }
        }, 'approve');
    }

    public function confirm() {
        $this->ticketAction(function ($actor, $ticket) {
            $ok = $this->post('decision') === 'satisfactory';
            $this->verify->requesterConfirm($actor, $ticket, $ok, (string) $this->post('comment', ''));
            $_SESSION['message'] = $ok ? 'Thank you. Work marked satisfactory.' : 'Ticket reopened.';
        });
    }

    public function close() {
        $this->officerAction(function ($actor, $ticket) {
            $this->verify->close($actor, $ticket, (string) $this->post('remarks', ''), (string) $this->post('no_evidence_reason', ''));
            $_SESSION['message'] = 'Ticket closed.';
        }, 'approve');
    }

    public function reopen() {
        $this->ticketAction(function ($actor, $ticket) {
            $this->verify->reopen($actor, $ticket, (string) $this->post('reason', ''));
            $_SESSION['message'] = 'Ticket reopened.';
        });
    }

    public function hold() {
        $this->officerAction(function ($actor, $ticket) {
            $this->verify->hold($actor, $ticket, (string) $this->post('reason', 'On hold'));
            $_SESSION['message'] = 'Ticket placed on hold.';
        });
    }

    public function cancel() {
        $this->officerAction(function ($actor, $ticket) {
            $this->verify->cancel($actor, $ticket, (string) $this->post('reason', 'Cancelled'));
            $_SESSION['message'] = 'Ticket cancelled.';
        });
    }

    public function comment() {
        $this->ticketAction(function ($actor, $ticket) {
            if (!$this->svc->canComment($actor, $ticket)) {
                throw new RuntimeException('You cannot comment on this ticket.');
            }
            $text = trim((string) $this->post('comment', ''));
            if ($text === '') {
                throw new InvalidArgumentException('Comment is required.');
            }
            $this->svc->model()->addChild('facilities_comments', [
                'ticket_id' => $ticket['id'],
                'comment' => $text,
                'created_by' => $actor['staff_id'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->svc->history((int) $ticket['id'], $actor['staff_id'], 'Comment', null, null, $text);
            $_SESSION['message'] = 'Comment added.';
        });
    }

    public function evidence() {
        $this->ticketAction(function ($actor, $ticket) {
            if (!$this->svc->canWork($actor, $ticket) && empty($actor['is_officer']) && $actor['staff_id'] !== $ticket['requester_staff_id']) {
                throw new RuntimeException('You cannot upload evidence for this ticket.');
            }
            $n = $this->saveUploads($ticket, $actor, $this->post('evidence_type', 'other'));
            if ($n < 1) {
                throw new InvalidArgumentException('Select a valid file to upload.');
            }
            $_SESSION['message'] = 'Evidence uploaded.';
        }, 'upload');
    }

    public function download() {
        if (!$this->boot('download')) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor);
        if (!$ticket) {
            return;
        }
        $eid = (int) $this->get('file');
        $files = $this->svc->model()->children('facilities_evidence', (int) $ticket['id']);
        $file = null;
        foreach ($files as $row) {
            if ((int) $row['id'] === $eid) {
                $file = $row;
                break;
            }
        }
        if (!$file) {
            $_SESSION['error'] = 'File not found.';
            $this->redirect('facilities/view?id=' . $ticket['id']);
            return;
        }
        $path = BASE_PATH . '/' . ltrim($file['file_path'], '/');
        if (!is_file($path)) {
            $_SESSION['error'] = 'File is missing on the server.';
            $this->redirect('facilities/view?id=' . $ticket['id']);
            return;
        }
        header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . basename($file['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function deadline() {
        $this->ticketAction(function ($actor, $ticket) {
            $this->assign->extendDeadline($actor, $ticket, (string) $this->post('deadline', ''), (string) $this->post('reason', ''));
            $_SESSION['message'] = !empty($actor['is_officer']) ? 'Deadline updated.' : 'Extension requested.';
        });
    }

    public function priority() {
        $this->officerAction(function ($actor, $ticket) {
            $this->svc->changePriority($actor, $ticket, (string) $this->post('priority', 'medium'), (string) $this->post('reason', ''));
            $_SESSION['message'] = 'Priority updated.';
        });
    }

    public function reports() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        if (empty($actor['is_officer']) && empty($actor['is_monitor']) && empty($actor['is_hod']) && empty($actor['is_admin'])) {
            $_SESSION['error'] = 'Reports are available to Facilities Officer, HOD, and management.';
            $this->redirect('facilities');
            return;
        }
        $filters = $this->svc->scopeFilters($actor, $this->listFilters());
        $summary = $this->reports->summary($filters);
        $rows = $this->reports->rows($filters);
        return $this->view('facilities/reports', $this->pack([
            'title' => 'Facilities Reports',
            'section' => 'reports',
            'summary' => $summary,
            'rows' => $rows,
            'filters' => $filters,
        ], $actor));
    }

    public function export() {
        if (!$this->boot('download')) {
            return;
        }
        $actor = $this->actor();
        $filters = $this->svc->scopeFilters($actor, $this->listFilters());
        $rows = $this->reports->rows($filters);
        $format = $this->get('format', 'csv');
        if ($format === 'print' || $format === 'pdf') {
            return $this->view('facilities/print_report', $this->pack([
                'title' => 'Facilities Report',
                'section' => 'reports',
                'rows' => $rows,
                'summary' => $this->reports->summary($filters),
                'filters' => $filters,
                'use_print_layout' => true,
            ], $actor));
        }
        $csv = $this->reports->toCsv($rows);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="facilities-tickets-' . date('Ymd') . '.csv"');
        echo $csv;
        exit;
    }

    public function categories() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (!$this->requireOfficer($actor)) {
            return;
        }
        return $this->view('facilities/categories', $this->pack([
            'title' => 'Facility Categories',
            'section' => 'categories',
            'categories' => $this->svc->model()->categories(false),
            'subcategories' => $this->svc->model()->subcategories(null, false),
        ], $actor));
    }

    public function categorySave() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (!$this->requireOfficer($actor)) {
            return;
        }
        $kind = $this->post('kind', 'category');
        if ($kind === 'subcategory') {
            $this->svc->model()->saveSubcategory([
                'category_id' => $this->post('category_id'),
                'name' => trim((string) $this->post('name', '')),
                'sort_order' => (int) $this->post('sort_order', 100),
                'is_active' => (int) $this->post('is_active', 1),
            ], $this->post('id') ?: null);
        } else {
            $name = trim((string) $this->post('name', ''));
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $this->svc->model()->saveCategory([
                'name' => $name,
                'slug' => $slug,
                'sort_order' => (int) $this->post('sort_order', 100),
                'is_active' => (int) $this->post('is_active', 1),
            ], $this->post('id') ?: null);
        }
        $_SESSION['message'] = 'Category saved.';
        $this->redirect('facilities/categories');
    }

    public function notifications() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $items = $this->svc->model()->notificationsFor($actor['staff_id']);
        $this->svc->model()->markNotificationsRead($actor['staff_id']);
        return $this->view('facilities/notifications', $this->pack([
            'title' => 'Facilities Notifications',
            'section' => 'notifications',
            'items' => $items,
        ], $actor));
    }

    public function subcategoriesJson() {
        if (!$this->boot('view')) {
            return;
        }
        $rows = $this->svc->model()->subcategories((int) $this->get('category_id'));
        $this->json(['ok' => true, 'rows' => $rows]);
    }

    public function staffJson() {
        if (!$this->boot('edit')) {
            return;
        }
        $rows = $this->svc->model()->staffPickerRows($this->get('department_id') ?: null);
        $this->json(['ok' => true, 'rows' => $rows]);
    }

    public function printTicket() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor);
        if (!$ticket) {
            return;
        }
        $data = $this->detailData($actor, $ticket, $ticket['ticket_number']);
        $data['use_print_layout'] = true;
        return $this->view('facilities/print', $data);
    }

    private function boot(string $action): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return false;
        }
        if (!empty($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
            $_SESSION['error'] = 'Facilities tickets are available to staff only.';
            $this->redirect('student/dashboard');
            return false;
        }
        return $this->requireModule('facilities', $action);
    }

    private function actor(): array {
        return $this->svc->actor((int) $_SESSION['user_id']);
    }

    private function loadTicket(array $actor, bool $write = false): ?array {
        $id = (int) ($this->get('id') ?: $this->post('id'));
        $ticket = $id ? $this->svc->model()->getDetailed($id) : null;
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket not found.';
            $this->redirect('facilities');
            return null;
        }
        $ticket = $this->svc->model()->withDeadlineState($ticket);
        if (!$this->svc->canViewTicket($actor, $ticket)) {
            $_SESSION['error'] = 'You do not have permission to view this ticket.';
            $this->redirect('facilities');
            return null;
        }
        if ($write && in_array($ticket['status'], ['closed'], true) && $this->post('action') !== 'reopen') {
            $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
            if (strpos($uri, 'reopen') === false) {
                $_SESSION['error'] = 'Closed tickets cannot be edited.';
                $this->redirect('facilities/view?id=' . $ticket['id']);
                return null;
            }
        }
        return $ticket;
    }

    private function requireOfficer(array $actor): bool {
        if (!empty($actor['is_officer'])) {
            return true;
        }
        $_SESSION['error'] = 'Only the Facilities Officer can perform this action.';
        $this->redirect('facilities');
        return false;
    }

    private function ticketAction(callable $fn, string $moduleAction = 'edit'): void {
        if (!$this->boot($moduleAction)) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor, true);
        if (!$ticket) {
            return;
        }
        try {
            $fn($actor, $ticket);
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirect('facilities/view?id=' . (int) $ticket['id']);
    }

    private function officerAction(callable $fn, string $moduleAction = 'edit'): void {
        $this->ticketAction(function ($actor, $ticket) use ($fn) {
            if (empty($actor['is_officer'])) {
                throw new RuntimeException('Only the Facilities Officer can perform this action.');
            }
            $fn($actor, $ticket);
        }, $moduleAction);
    }

    private function workAction(callable $fn): void {
        $this->ticketAction(function ($actor, $ticket) use ($fn) {
            if (!$this->svc->canWork($actor, $ticket) && empty($actor['is_officer'])) {
                throw new RuntimeException('Only the assigned person can update this work.');
            }
            $fn($actor, $ticket);
        });
    }

    private function listFilters(): array {
        $keys = ['q', 'status', 'priority', 'category_id', 'department_id', 'staff_id', 'date_from', 'date_to', 'location'];
        $out = [];
        foreach ($keys as $key) {
            $val = trim((string) $this->get($key, ''));
            if ($val !== '') {
                $out[$key] = $val;
            }
        }
        if ($this->get('overdue')) {
            $out['overdue'] = 1;
        }
        if ($this->get('due_soon')) {
            $out['due_soon'] = 1;
        }
        if ($this->get('unassigned')) {
            $out['unassigned'] = 1;
        }
        return $out;
    }

    private function pack(array $data, array $actor): array {
        $deptModel = $this->model('DepartmentModel');
        $data['page'] = 'facilities';
        $data['actor'] = $actor;
        $data['labels'] = $this->svc->labels();
        $data['categories'] = $this->svc->model()->categories();
        $data['departments'] = $deptModel->getAll();
        $data['unread'] = $this->svc->model()->unreadCount($actor['staff_id']);
        $data['message'] = $_SESSION['message'] ?? null;
        $data['error'] = $_SESSION['error'] ?? null;
        unset($_SESSION['message'], $_SESSION['error']);
        return $data;
    }

    private function detailData(array $actor, array $ticket, string $title, array $extra = []): array {
        $id = (int) $ticket['id'];
        return $this->pack(array_merge([
            'title' => $title,
            'section' => 'view',
            'ticket' => $ticket,
            'assignments' => $this->svc->model()->children('facilities_assignments', $id, 'assigned_at DESC'),
            'progress_rows' => $this->svc->model()->children('facilities_progress_updates', $id, 'created_at ASC'),
            'comments' => $this->svc->model()->children('facilities_comments', $id, 'created_at ASC'),
            'timeline' => $this->svc->model()->children('facilities_activity', $id, 'created_at ASC'),
            'evidence' => $this->svc->model()->evidenceByType($id),
            'verifications' => $this->svc->model()->children('facilities_verifications', $id, 'verified_at DESC'),
            'deadline_history' => $this->svc->model()->children('facilities_deadline_history', $id, 'changed_at DESC'),
            'can_assign' => $this->svc->canAssign($actor, $ticket),
            'can_work' => $this->svc->canWork($actor, $ticket),
            'can_verify' => $this->svc->canVerify($actor, $ticket),
            'can_close' => $this->svc->canClose($actor, $ticket),
            'can_confirm' => $this->svc->canConfirm($actor, $ticket),
            'can_comment' => $this->svc->canComment($actor, $ticket),
        ], $extra), $actor);
    }

    private function saveUploads(array $ticket, array $actor, string $type = 'other'): int {
        if (empty($_FILES['evidence']) || empty($_FILES['evidence']['name'])) {
            return 0;
        }
        $names = $_FILES['evidence']['name'];
        if (!is_array($names)) {
            $names = [$names];
            $_FILES['evidence']['tmp_name'] = [$_FILES['evidence']['tmp_name']];
            $_FILES['evidence']['type'] = [$_FILES['evidence']['type']];
            $_FILES['evidence']['size'] = [$_FILES['evidence']['size']];
            $_FILES['evidence']['error'] = [$_FILES['evidence']['error']];
        }
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $dirRel = 'uploads/facilities/' . (int) $ticket['id'];
        $dir = BASE_PATH . '/' . $dirRel;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ht = $dir . '/.htaccess';
        if (!is_file($ht)) {
            file_put_contents($ht, "Require all denied\n");
        }
        $saved = 0;
        foreach ($names as $i => $name) {
            if (($name === '') || (int) ($_FILES['evidence']['error'][$i] ?? 4) !== 0) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                continue;
            }
            if ((int) $_FILES['evidence']['size'][$i] > 5 * 1024 * 1024) {
                continue;
            }
            $stored = uniqid('fac_', true) . '.' . $ext;
            $dest = $dir . '/' . $stored;
            if (!move_uploaded_file($_FILES['evidence']['tmp_name'][$i], $dest)) {
                continue;
            }
            $this->svc->model()->addChild('facilities_evidence', [
                'ticket_id' => $ticket['id'],
                'evidence_type' => $type,
                'original_name' => $name,
                'stored_name' => $stored,
                'file_path' => $dirRel . '/' . $stored,
                'file_type' => $_FILES['evidence']['type'][$i] ?? '',
                'file_size' => (int) $_FILES['evidence']['size'][$i],
                'description' => (string) $this->post('evidence_description', ''),
                'uploaded_by' => $actor['staff_id'],
                'uploaded_at' => date('Y-m-d H:i:s'),
            ]);
            $this->svc->history((int) $ticket['id'], $actor['staff_id'], 'Evidence Uploaded', null, $type, $name);
            $saved++;
        }
        return $saved;
    }
}
