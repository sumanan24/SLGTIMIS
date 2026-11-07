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
        $this->svc->runEscalations();
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
        $group = (string) ($filters['group'] ?? '');
        if (!isset(FacilitiesTicketModel::STATUS_GROUPS[$group])) {
            unset($filters['group']);
            $group = '';
        }
        $groupTitles = ['pending' => 'Pending Tickets', 'in_progress' => 'In Progress', 'resolved' => 'Resolved Tickets', 'closed' => 'Closed Tickets'];
        $result = $this->markAssignable($this->svc->model()->search($filters, (int) $this->get('page', 1), 20), $actor);
        return $this->view('facilities/index', $this->pack([
            'title' => $group !== '' ? $groupTitles[$group] : 'Facilities Tickets',
            'heading' => $group !== '' ? $groupTitles[$group] : 'All Tickets',
            'section' => $group !== '' ? $group : 'list',
            'result' => $result,
            'filters' => $filters,
        ], $actor));
    }

    public function tasks() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        $filters = $this->svc->scopeFilters($actor, $this->listFilters());
        $filters['assigned_to_me'] = 1;
        $filters['scope_staff_id'] = $actor['staff_id'];
        $result = $this->markAssignable($this->svc->model()->search($filters, (int) $this->get('page', 1), 20), $actor);
        return $this->view('facilities/index', $this->pack([
            'title' => 'My Assigned Tickets',
            'heading' => 'My Assigned Tickets',
            'section' => 'tasks',
            'result' => $result,
            'filters' => $filters,
        ], $actor));
    }

    public function feedback() {
        $this->ticketAction(function ($actor, $ticket) {
            $this->svc->saveFeedback($actor, $ticket, $this->post());
            $_SESSION['message'] = 'Thank you. Your feedback was saved.';
        }, 'view');
    }

    public function settings() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (empty($actor['is_admin'])) {
            $_SESSION['error'] = 'Only a system administrator can change maintenance settings.';
            $this->redirect('facilities/dashboard');
            return;
        }
        $model = $this->svc->model();
        return $this->view('facilities/settings', $this->pack([
            'title' => 'Ticket Settings',
            'section' => 'settings',
            'sla' => [
                'low' => $model->slaDays('low'),
                'medium' => $model->slaDays('medium'),
                'high' => $model->slaDays('high'),
                'critical' => $model->slaDays('critical'),
            ],
            'settings' => [
                'unassigned_hours' => $model->setting('unassigned_hours', '24'),
                'facility_escalate_days' => $model->setting('facility_escalate_days', '3'),
                'director_escalate_days' => $model->setting('director_escalate_days', '7'),
            ],
        ], $actor));
    }

    public function settingsSave() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (empty($actor['is_admin'])) {
            $_SESSION['error'] = 'Only a system administrator can change maintenance settings.';
            $this->redirect('facilities/dashboard');
            return;
        }
        $model = $this->svc->model();
        $routes = $this->post('route_staff', []);
        if (is_array($routes)) {
            foreach ($routes as $categoryId => $staffId) {
                $model->saveRouteStaff((int) $categoryId, trim((string) $staffId));
            }
        }
        foreach (['low', 'medium', 'high', 'critical'] as $priority) {
            $days = (int) $this->post('sla_' . $priority, 7);
            $model->saveSla($priority, max(1, min(60, $days)));
        }
        foreach (['unassigned_hours', 'facility_escalate_days', 'director_escalate_days'] as $key) {
            $model->saveSetting($key, (string) max(1, (int) $this->post($key, 1)));
        }
        $_SESSION['message'] = 'Ticket settings saved.';
        $this->redirect('facilities/settings');
    }

    /** Assign Officers: which person receives each ticket category. */
    public function officers() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (empty($actor['is_admin'])) {
            $_SESSION['error'] = 'Only a system administrator can assign officers.';
            $this->redirect('facilities/dashboard');
            return;
        }
        $model = $this->svc->model();
        return $this->view('facilities/officers', $this->pack([
            'title' => 'Assign Officers',
            'section' => 'officers',
            'routes' => $model->routes(),
            'staff_options' => $model->staffOptions(),
        ], $actor));
    }

    public function officersSave() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (empty($actor['is_admin'])) {
            $_SESSION['error'] = 'Only a system administrator can assign officers.';
            $this->redirect('facilities/dashboard');
            return;
        }
        $model = $this->svc->model();
        $routes = $this->post('route_staff', []);
        if (is_array($routes)) {
            foreach ($routes as $categoryId => $staffId) {
                $model->saveRouteStaff((int) $categoryId, trim((string) $staffId));
            }
        }
        $_SESSION['message'] = 'Officer assignments saved.';
        $this->redirect('facilities/officers');
    }

    /** Tickets by department (officers, monitors, management; a HOD sees only their own department). */
    public function departments() {
        if (!$this->boot('view')) {
            return;
        }
        $actor = $this->actor();
        if (empty($actor['is_officer']) && empty($actor['is_monitor']) && empty($actor['is_hod']) && empty($actor['is_admin'])) {
            $_SESSION['error'] = 'Department tickets are available to Facilities Officer, HOD, and management.';
            $this->redirect('facilities');
            return;
        }
        $rows = $this->svc->model()->departmentPerformance();
        $seeAll = !empty($actor['is_officer']) || !empty($actor['is_monitor']) || !empty($actor['is_admin']);
        if (!$seeAll) {
            $own = (string) ($actor['hod_department_id'] ?? '');
            $rows = array_values(array_filter($rows, static function ($r) use ($own) {
                return $own !== '' && (string) ($r['department_id'] ?? '') === $own;
            }));
        }
        return $this->view('facilities/departments', $this->pack([
            'title' => 'Departments',
            'section' => 'departments',
            'rows' => $rows,
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
            $_SESSION['message'] = 'Request submitted. Your reference number is ' . $ticket['ticket_number'] . '.';
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
        if (!$ticket || !$this->svc->canAssign($actor, $ticket)) {
            if ($ticket) {
                $_SESSION['error'] = 'You do not have permission to assign this ticket.';
                $this->redirect('facilities/view?id=' . (int) $ticket['id']);
            }
            return;
        }
        if ($ticket['status'] === 'new') {
            $ticket = $this->verify->review($actor, $ticket);
        }
        $deptId = $this->get('department_id', $ticket['responsible_department_id'] ?? '');
        // Suggest a deadline that is not already in the past (keeps the current one if it is still ahead)
        $suggested = date('Y-m-d', strtotime('+' . max(1, $this->svc->model()->slaDays((string) ($ticket['current_priority'] ?? 'medium'))) . ' days'));
        if (!empty($ticket['deadline']) && $ticket['deadline'] >= date('Y-m-d')) {
            $suggested = substr((string) $ticket['deadline'], 0, 10);
        }
        return $this->view('facilities/assign', $this->detailData($actor, $ticket, ($ticket['responsible_staff_id'] ?? '') !== '' ? 'Reassign ' . $ticket['ticket_number'] : 'Assign ' . $ticket['ticket_number'], [
            'suggested_deadline' => $suggested,
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
        $wasAssigned = !empty($ticket['responsible_staff_id']) || !empty($ticket['responsible_department_id']);
        try {
            $ticket = $this->assign->assign($actor, $ticket, $this->post());
            $this->logActivity('UPDATE', 'facilities', $ticket['id'], ($wasAssigned ? 'Reassigned ' : 'Assigned ') . $ticket['ticket_number'], null, $this->post());
            $_SESSION['message'] = $wasAssigned ? 'The ticket has been reassigned.' : 'The ticket has been assigned.';
        } catch (Throwable $e) {
            // Stay on the form so the user can correct the problem
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('facilities/assign?id=' . (int) $ticket['id']);
            return;
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
            $this->svc->recordMaterial($actor, $ticket, (string) $this->post('material_item', ''), (string) $this->post('material_qty', ''), (string) $this->post('material_notes', ''));
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
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        $ticket = $this->loadTicket($actor, true);
        if (!$ticket || !$this->svc->canVerify($actor, $ticket)) {
            if ($ticket) {
                $_SESSION['error'] = 'You cannot verify this request.';
                $this->redirect('facilities/view?id=' . (int) $ticket['id']);
            }
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
        }, 'edit');
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
            $this->svc->notifyStaff((string) $ticket['requester_staff_id'], (int) $ticket['id'], 'feedback_reminder', 'Please rate this request', 'Request ' . $ticket['ticket_number'] . ' is closed. Your feedback helps improve the service.');
        }, 'edit');
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
        $model = $this->svc->model();
        return $this->view('facilities/categories', $this->pack([
            'title' => 'Ticket Categories',
            'section' => 'categories',
            'rows' => $model->categoriesWithRoutes(),
            'subcategories' => $model->subcategories(null, false),
            'staff_options' => $model->staffOptions(),
        ], $actor));
    }

    /** Create or update a category (name, responsible person, order, active) or add a subcategory. */
    public function categorySave() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (!$this->requireOfficer($actor)) {
            return;
        }
        $model = $this->svc->model();
        $kind = $this->post('kind', 'category');
        if ($kind === 'subcategory') {
            $name = trim((string) $this->post('name', ''));
            if ($name === '' || !(int) $this->post('category_id', 0)) {
                $_SESSION['error'] = 'Choose a category and enter a subcategory name.';
                $this->redirect('facilities/categories');
                return;
            }
            $model->saveSubcategory([
                'category_id' => $this->post('category_id'),
                'name' => $name,
                'sort_order' => (int) $this->post('sort_order', 100),
                'is_active' => (int) $this->post('is_active', 1),
            ], $this->post('id') ?: null);
            $_SESSION['message'] = 'Subcategory saved.';
            $this->redirect('facilities/categories');
            return;
        }

        $id = (int) $this->post('id', 0);
        $name = trim((string) preg_replace('/\s+/', ' ', (string) $this->post('name', '')));
        if ($name === '') {
            $_SESSION['error'] = 'Enter a category name.';
            $this->redirect('facilities/categories');
            return;
        }
        if (strlen($name) > 120) {
            $name = substr($name, 0, 120);
        }
        if ($model->categoryByName($name, $id)) {
            $_SESSION['error'] = 'A category named "' . $name . '" already exists.';
            $this->redirect('facilities/categories');
            return;
        }
        $staffId = trim((string) $this->post('staff_id', ''));
        $data = [
            'name' => $name,
            'slug' => $model->uniqueCategorySlug($name, $id),
            'sort_order' => max(0, (int) $this->post('sort_order', 100)),
            'is_active' => (int) $this->post('is_active', 1) ? 1 : 0,
        ];
        if ($id > 0) {
            $model->saveCategory($data, $id);
            $categoryId = $id;
        } else {
            $categoryId = (int) $model->saveCategory($data);
        }
        if ($categoryId > 0) {
            $model->saveRouteStaff($categoryId, $staffId);
            $this->logActivity($id > 0 ? 'UPDATE' : 'CREATE', 'facilities', $categoryId, ($id > 0 ? 'Updated' : 'Created') . ' ticket category ' . $name);
        }
        $_SESSION['message'] = $id > 0 ? 'Category updated.' : 'Category added.';
        $this->redirect('facilities/categories');
    }

    public function categoryDelete() {
        if (!$this->boot('edit')) {
            return;
        }
        $actor = $this->actor();
        if (!$this->requireOfficer($actor)) {
            return;
        }
        $id = (int) $this->post('id', 0);
        $model = $this->svc->model();
        if ($id <= 0) {
            $_SESSION['error'] = 'Category not found.';
        } elseif ($model->categoryTicketCount($id) > 0) {
            $_SESSION['error'] = 'This category already has tickets, so it cannot be deleted. Set it to Inactive instead.';
        } elseif ($model->deleteCategory($id)) {
            $this->logActivity('DELETE', 'facilities', $id, 'Deleted ticket category #' . $id);
            $_SESSION['message'] = 'Category deleted.';
        } else {
            $_SESSION['error'] = 'The category could not be deleted.';
        }
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
            if (strpos($uri, 'reopen') === false && strpos($uri, 'feedback') === false) {
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
            if (!$this->svc->canManage($actor, $ticket)) {
                throw new RuntimeException('You cannot change this request.');
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
        $keys = ['q', 'status', 'group', 'priority', 'category_id', 'department_id', 'staff_id', 'date_from', 'date_to', 'location', 'request_type'];
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
        if ($this->get('escalated')) {
            $out['escalated'] = 1;
        }
        return $out;
    }

    /** Flags each listed ticket the current user may assign or reassign (drives the Action button). */
    private function markAssignable(array $result, array $actor): array {
        foreach ($result['rows'] as &$row) {
            $row['can_assign'] = $this->svc->canAssign($actor, $row) ? 1 : 0;
        }
        unset($row);
        return $result;
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
            'team' => $this->svc->model()->currentTeam($id),
            'progress_rows' => $this->svc->model()->children('facilities_progress_updates', $id, 'created_at ASC'),
            'comments' => $this->svc->model()->children('facilities_comments', $id, 'created_at ASC'),
            'timeline' => $this->svc->model()->children('facilities_activity', $id, 'created_at ASC'),
            'evidence' => $this->svc->model()->evidenceByType($id),
            'verifications' => $this->svc->model()->children('facilities_verifications', $id, 'verified_at DESC'),
            'deadline_history' => $this->svc->model()->children('facilities_deadline_history', $id, 'changed_at DESC'),
            'feedback' => $this->svc->model()->feedbackFor($id),
            'materials' => $this->svc->model()->children('facilities_materials', $id, 'recorded_at ASC'),
            'escalations' => $this->svc->model()->children('facilities_escalations', $id, 'created_at ASC'),
            'can_feedback' => $this->svc->canFeedback($actor, $ticket),
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
