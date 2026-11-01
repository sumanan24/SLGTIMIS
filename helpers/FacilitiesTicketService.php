<?php
/**
 * Facilities ticket workflow, access, and audit.
 */
class FacilitiesTicketService {
    const ST_NEW = 'new';
    const ST_REVIEW = 'under_review';
    const ST_ASSIGNED = 'assigned';
    const ST_ACCEPTED = 'accepted';
    const ST_PROGRESS = 'in_progress';
    const ST_PENDING = 'completed_pending_verification';
    const ST_VERIFIED = 'verified';
    const ST_CLOSED = 'closed';
    const ST_REJECTED = 'rejected';
    const ST_HOLD = 'on_hold';
    const ST_REOPENED = 'reopened';
    const ST_CANCELLED = 'cancelled';

    const PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent', 'critical' => 'Critical'];
    const REQUEST_TYPES = [
        'maintenance' => 'Maintenance Request',
        'complaint' => 'Complaint',
        'service' => 'Service Request',
        'technical' => 'Technical Support Request',
    ];
    const ROUTE_LABELS = [
        'ict_hod' => 'ICT HOD',
        'construction_hod' => 'Construction HOD',
        'electrical_hod' => 'Electrical HOD',
        'facility_officer' => 'Facility Officer',
    ];
    const STATUSES = [
        'new' => 'New',
        'under_review' => 'Pending Review',
        'assigned' => 'Assigned',
        'accepted' => 'Acknowledged',
        'in_progress' => 'In Progress',
        'completed_pending_verification' => 'Awaiting Verification',
        'verified' => 'Completed',
        'closed' => 'Closed',
        'rejected' => 'Rejected',
        'on_hold' => 'On Hold',
        'reopened' => 'Reopened',
        'cancelled' => 'Cancelled',
    ];
    const CLOSED = ['closed', 'cancelled', 'rejected'];

    private $model;

    public function __construct() {
        require_once BASE_PATH . '/models/FacilitiesTicketModel.php';
        $this->model = new FacilitiesTicketModel();
    }

    public function model(): FacilitiesTicketModel {
        return $this->model;
    }

    public function actor(int $userId): array {
        require_once BASE_PATH . '/models/UserModel.php';
        require_once BASE_PATH . '/models/StaffModel.php';
        $userModel = new UserModel();
        $user = $userModel->find($userId) ?: [];
        $staffId = (string) ($user['user_name'] ?? '');
        $staffModel = new StaffModel();
        $staff = $staffId !== '' ? $staffModel->find($staffId) : null;
        $role = (string) $userModel->getUserRole($userId);
        $officer = $userModel->isFacilitiesOfficer($userId);
        $monitor = $userModel->isFacilitiesMonitor($userId);
        $hodDept = $userModel->isHOD($userId) ? $userModel->getHODDepartment($userId) : null;
        return [
            'user_id' => $userId,
            'staff_id' => $staffId,
            'staff' => $staff,
            'name' => $staff['staff_name'] ?? $staffId,
            'department_id' => $staff['department_id'] ?? null,
            'contact' => $this->phoneOnly((string) ($staff['staff_pno'] ?? '')),
            'role' => $role,
            'is_officer' => $officer,
            'is_monitor' => $monitor,
            'is_hod' => $userModel->isHOD($userId),
            'hod_department_id' => $hodDept,
            'is_admin' => $userModel->isAdmin($userId) || $userModel->isAdminOrADM($userId),
        ];
    }

    public function canViewTicket(array $actor, array $ticket): bool {
        if (!empty($actor['is_officer']) || !empty($actor['is_monitor']) || !empty($actor['is_admin'])) {
            return true;
        }
        $sid = $actor['staff_id'];
        if ($sid !== '' && ($ticket['requester_staff_id'] === $sid || $ticket['responsible_staff_id'] === $sid)) {
            return true;
        }
        if (!empty($actor['is_hod']) && $actor['hod_department_id']) {
            $dept = $actor['hod_department_id'];
            return $ticket['requester_department_id'] === $dept || $ticket['responsible_department_id'] === $dept;
        }
        return false;
    }

    public function canComment(array $actor, array $ticket): bool {
        return $this->canViewTicket($actor, $ticket) && !in_array($ticket['status'], self::CLOSED, true);
    }

    public function canManage(array $actor, array $ticket): bool {
        if (!empty($actor['is_monitor']) && empty($actor['is_officer']) && empty($actor['is_admin'])) {
            return false;
        }
        if (!empty($actor['is_officer']) || !empty($actor['is_admin'])) {
            return true;
        }
        if ($actor['staff_id'] !== '' && $actor['staff_id'] === (string) ($ticket['routed_staff_id'] ?? '')) {
            return true;
        }
        if (!empty($actor['is_hod']) && !empty($actor['hod_department_id'])) {
            return (string) ($ticket['responsible_department_id'] ?? '') === (string) $actor['hod_department_id'];
        }
        return false;
    }

    public function canAssign(array $actor, array $ticket): bool {
        return $this->canManage($actor, $ticket) && !in_array($ticket['status'], ['closed', 'cancelled'], true);
    }

    public function canWork(array $actor, array $ticket): bool {
        return $actor['staff_id'] !== '' && $ticket['responsible_staff_id'] === $actor['staff_id']
            && !in_array($ticket['status'], self::CLOSED, true);
    }

    public function canVerify(array $actor, array $ticket): bool {
        return $this->canManage($actor, $ticket) && $ticket['status'] === self::ST_PENDING;
    }

    public function canClose(array $actor, array $ticket): bool {
        return $this->canManage($actor, $ticket) && in_array($ticket['status'], [self::ST_VERIFIED], true);
    }

    public function canFeedback(array $actor, array $ticket): bool {
        return $actor['staff_id'] !== ''
            && $actor['staff_id'] === (string) ($ticket['requester_staff_id'] ?? '')
            && $ticket['status'] === self::ST_CLOSED
            && $this->model->feedbackFor((int) $ticket['id']) === null;
    }

    public function canConfirm(array $actor, array $ticket): bool {
        return $actor['staff_id'] === $ticket['requester_staff_id'] && $ticket['status'] === self::ST_VERIFIED;
    }

    public function scopeFilters(array $actor, array $filters = []): array {
        if (!empty($actor['is_officer']) || !empty($actor['is_monitor']) || !empty($actor['is_admin'])) {
            $filters['officer_all'] = 1;
            return $filters;
        }
        $filters['scope_staff_id'] = $actor['staff_id'];
        if (!empty($actor['is_hod']) && $actor['hod_department_id']) {
            $filters['hod_scope'] = 1;
            $filters['scope_department_id'] = $actor['hod_department_id'];
        }
        return $filters;
    }

    public function createTicket(array $actor, array $input): array {
        $type = (string) ($input['request_type'] ?? 'maintenance');
        if (!isset(self::REQUEST_TYPES[$type])) {
            throw new InvalidArgumentException('Choose a request type.');
        }
        if (($input['title'] ?? '') === '' || ($input['description'] ?? '') === '' || empty($input['category_id']) || ($input['location'] ?? '') === '' || empty($input['priority'])) {
            throw new InvalidArgumentException('Title, description, category, location and priority are required.');
        }
        if (!isset(self::PRIORITIES[$input['priority']])) {
            throw new InvalidArgumentException('Invalid priority.');
        }
        $route = $this->resolveRoute((int) $input['category_id']);
        $days = $this->model->slaDays((string) $input['priority']);
        $expected = date('Y-m-d', strtotime('+' . $days . ' days'));
        $status = $route['staff_id'] !== '' ? self::ST_REVIEW : self::ST_NEW;
        $id = false;
        $seq = ['ticket_number' => ''];
        $this->model->lockTicketSequence();
        try {
            $seq = $this->model->nextTicketNumber();
            $id = $this->model->create([
                'ticket_number' => $seq['ticket_number'],
                'ticket_year' => $seq['ticket_year'],
                'ticket_seq' => $seq['ticket_seq'],
                'status' => $status,
                'request_type' => $type,
                'original_priority' => $input['priority'],
                'current_priority' => $input['priority'],
                'title' => trim($input['title']),
                'description' => trim($input['description']),
                'category_id' => (int) $input['category_id'],
                'subcategory_id' => !empty($input['subcategory_id']) ? (int) $input['subcategory_id'] : null,
                'location' => trim($input['location']),
                'building' => trim((string) ($input['building'] ?? '')),
                'floor' => trim((string) ($input['floor'] ?? '')),
                'room_area' => trim((string) ($input['room_area'] ?? '')),
                'remarks' => trim((string) ($input['remarks'] ?? '')),
                'requester_user_id' => (int) $actor['user_id'],
                'requester_staff_id' => $actor['staff_id'],
                'requester_department_id' => $actor['department_id'],
                'requester_contact' => $this->requirePhone((string) ($input['contact'] ?? $actor['contact'] ?? '')),
                'required_date' => !empty($input['required_date']) ? $input['required_date'] : null,
                'responsible_department_id' => $route['department_id'] !== '' ? $route['department_id'] : null,
                'routed_role' => $route['route_key'],
                'routed_staff_id' => $route['staff_id'] !== '' ? $route['staff_id'] : null,
                'deadline' => $expected,
                'expected_completion_date' => $expected,
                'progress_percent' => 0,
            ]);
        } finally {
            $this->model->unlockTicketSequence();
        }
        if (!$id) {
            throw new RuntimeException('Could not create ticket.');
        }
        $this->history((int) $id, $actor['staff_id'], 'Ticket Created', null, $status, $seq['ticket_number']);
        $this->statusHistory((int) $id, null, $status, $actor['staff_id'], 'Request submitted');
        $this->notifyStaff($actor['staff_id'], (int) $id, 'ticket_created', 'Request submitted', 'Your reference number is ' . $seq['ticket_number'] . '.');
        if ($route['staff_id'] !== '') {
            $label = self::ROUTE_LABELS[$route['route_key']] ?? 'Responsible officer';
            $this->history((int) $id, $actor['staff_id'], 'Routed', '', $route['staff_id'], $label);
            $this->notifyStaff($route['staff_id'], (int) $id, 'ticket_routed', 'New request assigned to you', $seq['ticket_number'] . ' was routed to you as ' . $label . '.');
        } else {
            $this->notifyOfficers('ticket_created', (int) $id, 'New maintenance request', $seq['ticket_number'] . ' needs a responsible officer.');
        }
        return $this->model->getDetailed((int) $id);
    }

    public function resolveRoute(int $categoryId): array {
        $route = $this->model->routeForCategory($categoryId);
        $key = $route['route_key'] ?? 'facility_officer';
        $staffId = trim((string) ($route['staff_id'] ?? ''));
        $departmentId = '';
        if ($staffId === '') {
            $found = $this->officerForRoute($key);
            $staffId = $found['staff_id'];
            $departmentId = $found['department_id'];
        }
        return ['route_key' => $key, 'staff_id' => $staffId, 'department_id' => $departmentId];
    }

    private function officerForRoute(string $key): array {
        $likes = [
            'ict_hod' => ['%ICT%', '%Computer%', '%Information%'],
            'construction_hod' => ['%Construct%', '%Civil%', '%Building%'],
            'electrical_hod' => ['%Electr%'],
        ];
        if (isset($likes[$key])) {
            $hod = $this->model->findHodByDepartmentLike($likes[$key]);
            if ($hod) {
                return ['staff_id' => (string) $hod['staff_id'], 'department_id' => (string) $hod['department_id']];
            }
        }
        $officers = $this->model->findStaffByRoles(['FAC', 'FO', 'MHF']);
        return ['staff_id' => $officers[0] ?? '', 'department_id' => ''];
    }

    public function saveFeedback(array $actor, array $ticket, array $input): void {
        if (!$this->canFeedback($actor, $ticket)) {
            throw new RuntimeException('Feedback can be given once, by the requester, after the request is closed.');
        }
        $ratings = [];
        foreach (['overall_rating', 'response_rating', 'quality_rating', 'behaviour_rating'] as $key) {
            $value = (int) ($input[$key] ?? 0);
            if ($value < 1 || $value > 5) {
                throw new InvalidArgumentException('Each rating must be from 1 to 5.');
            }
            $ratings[$key] = $value;
        }
        $this->model->addChild('facilities_feedback', [
            'ticket_id' => (int) $ticket['id'],
            'overall_rating' => $ratings['overall_rating'],
            'response_rating' => $ratings['response_rating'],
            'quality_rating' => $ratings['quality_rating'],
            'behaviour_rating' => $ratings['behaviour_rating'],
            'comments' => trim((string) ($input['comments'] ?? '')),
            'created_by' => $actor['staff_id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->history((int) $ticket['id'], $actor['staff_id'], 'Feedback Submitted', '', (string) $ratings['overall_rating'], trim((string) ($input['comments'] ?? '')));
    }

    public function recordMaterial(array $actor, array $ticket, string $item, string $quantity, string $notes = ''): void {
        $item = trim($item);
        if ($item === '') {
            return;
        }
        $this->model->addChild('facilities_materials', [
            'ticket_id' => (int) $ticket['id'],
            'item_name' => $item,
            'quantity' => trim($quantity) !== '' ? trim($quantity) : '1',
            'notes' => trim($notes),
            'recorded_by' => $actor['staff_id'],
            'recorded_at' => date('Y-m-d H:i:s'),
        ]);
        $this->history((int) $ticket['id'], $actor['staff_id'], 'Materials Recorded', '', $item, $quantity);
    }

    public function runEscalations(): void {
        $hours = max(1, (int) $this->model->setting('unassigned_hours', '24'));
        $facilityDays = max(1, (int) $this->model->setting('facility_escalate_days', '3'));
        $directorDays = max($facilityDays, (int) $this->model->setting('director_escalate_days', '7'));
        $open = "status NOT IN ('closed','cancelled','rejected')";
        $level1 = $this->model->escalationCandidates(
            "SELECT id, ticket_number, routed_staff_id FROM `facilities_tickets`
             WHERE {$open}
               AND (responsible_staff_id IS NULL OR responsible_staff_id = '')
               AND created_at < DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
               AND escalation_level < 1"
        );
        foreach ($level1 as $row) {
            $this->escalateRow($row, 1, 'Unassigned beyond ' . $hours . ' hours', (string) ($row['routed_staff_id'] ?? ''));
        }
        $level2 = $this->model->escalationCandidates(
            "SELECT id, ticket_number, routed_staff_id FROM `facilities_tickets`
             WHERE {$open} AND deadline IS NOT NULL AND deadline < CURDATE() AND escalation_level < 2"
        );
        foreach ($level2 as $row) {
            $this->escalateRow($row, 2, 'Past the expected completion date', (string) ($row['routed_staff_id'] ?? ''));
        }
        $level3 = $this->model->escalationCandidates(
            "SELECT id, ticket_number FROM `facilities_tickets`
             WHERE {$open} AND deadline IS NOT NULL AND deadline < DATE_SUB(CURDATE(), INTERVAL {$facilityDays} DAY) AND escalation_level < 3"
        );
        $officers = $this->model->findStaffByRoles(['FAC', 'FO', 'MHF', 'ADM']);
        foreach ($level3 as $row) {
            $this->escalateRow($row, 3, 'Escalated to the Facility Officer', $officers[0] ?? '');
            foreach ($officers as $staffId) {
                $this->notifyStaff($staffId, (int) $row['id'], 'ticket_escalated', 'Escalated request', $row['ticket_number'] . ' is still unresolved.');
            }
        }
        $level4 = $this->model->escalationCandidates(
            "SELECT id, ticket_number FROM `facilities_tickets`
             WHERE {$open} AND deadline IS NOT NULL AND deadline < DATE_SUB(CURDATE(), INTERVAL {$directorDays} DAY) AND escalation_level < 4"
        );
        $directors = $this->model->findStaffByRoles(['DIR', 'DPA']);
        foreach ($level4 as $row) {
            $this->escalateRow($row, 4, 'Escalated to Director Administration', $directors[0] ?? '');
            foreach ($directors as $staffId) {
                $this->notifyStaff($staffId, (int) $row['id'], 'ticket_escalated', 'Escalated to Director Administration', $row['ticket_number'] . ' remains unresolved.');
            }
        }
    }

    private function escalateRow(array $row, int $level, string $reason, string $staffId): void {
        $ticketId = (int) $row['id'];
        if ($this->model->hasEscalation($ticketId, $level)) {
            return;
        }
        $this->model->addChild('facilities_escalations', [
            'ticket_id' => $ticketId,
            'level' => $level,
            'reason' => $reason,
            'notified_staff_id' => $staffId !== '' ? $staffId : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->model->updateTicket($ticketId, ['escalation_level' => $level]);
        $this->history($ticketId, 'system', 'Escalated', '', (string) $level, $reason);
        if ($staffId !== '' && $level < 3) {
            $this->notifyStaff($staffId, $ticketId, 'ticket_escalated', 'Request needs attention', ($row['ticket_number'] ?? '') . ' — ' . $reason);
        }
    }

    public function notifyStaff(string $staffId, int $ticketId, string $event, string $title, string $message): void {
        if ($staffId === '') {
            return;
        }
        $this->model->addChild('facilities_notifications', [
            'ticket_id' => $ticketId,
            'staff_id' => $staffId,
            'event_type' => $event,
            'title' => $title,
            'message' => $message,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function changeStatus(array $actor, array $ticket, string $to, string $remarks = ''): array {
        if (in_array($ticket['status'], ['closed'], true) && $to !== self::ST_REOPENED) {
            throw new RuntimeException('Closed tickets cannot be edited.');
        }
        $from = $ticket['status'];
        $this->model->updateTicket((int) $ticket['id'], ['status' => $to]);
        $this->statusHistory((int) $ticket['id'], $from, $to, $actor['staff_id'], $remarks);
        $this->history((int) $ticket['id'], $actor['staff_id'], 'Status Changed', $from, $to, $remarks);
        return $this->model->getDetailed((int) $ticket['id']);
    }

    public function changePriority(array $actor, array $ticket, string $priority, string $reason): array {
        if (!$this->canManage($actor, $ticket)) {
            throw new RuntimeException('You cannot change the priority of this request.');
        }
        if (!isset(self::PRIORITIES[$priority])) {
            throw new InvalidArgumentException('Invalid priority.');
        }
        $old = $ticket['current_priority'];
        $this->model->updateTicket((int) $ticket['id'], [
            'current_priority' => $priority,
            'priority_changed_by' => $actor['staff_id'],
            'priority_changed_at' => date('Y-m-d H:i:s'),
            'priority_change_reason' => $reason,
        ]);
        $this->model->addChild('facilities_priority_history', [
            'ticket_id' => $ticket['id'],
            'old_value' => $old,
            'new_value' => $priority,
            'reason' => $reason,
            'changed_by' => $actor['staff_id'],
            'changed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->history((int) $ticket['id'], $actor['staff_id'], 'Priority Changed', $old, $priority, $reason);
        $this->notifyWatchers($ticket, 'priority_changed', 'Priority updated', 'Priority changed to ' . self::PRIORITIES[$priority] . '.');
        return $this->model->getDetailed((int) $ticket['id']);
    }

    public function history(int $ticketId, string $actor, string $action, $old, $new, string $remarks = ''): void {
        $this->model->addChild('facilities_activity', [
            'ticket_id' => $ticketId,
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'remarks' => $remarks,
            'actor_staff_id' => $actor,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function statusHistory(int $ticketId, $old, string $new, string $actor, string $remarks = ''): void {
        $this->model->addChild('facilities_status_history', [
            'ticket_id' => $ticketId,
            'old_value' => $old,
            'new_value' => $new,
            'remarks' => $remarks,
            'changed_by' => $actor,
            'changed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function notifyWatchers(array $ticket, string $event, string $title, string $message): void {
        require_once BASE_PATH . '/helpers/FacilitiesNotificationService.php';
        (new FacilitiesNotificationService())->notifyTicket($ticket, $event, $title, $message);
    }

    public function notifyOfficers(string $event, int $ticketId, string $title, string $message): void {
        require_once BASE_PATH . '/helpers/FacilitiesNotificationService.php';
        (new FacilitiesNotificationService())->notifyOfficers($ticketId, $event, $title, $message);
    }

    public function labels(): array {
        return ['statuses' => self::STATUSES, 'priorities' => self::PRIORITIES, 'types' => self::REQUEST_TYPES, 'routes' => self::ROUTE_LABELS];
    }

    private function phoneOnly(string $value): string {
        $digits = preg_replace('/\D+/', '', $value);
        if (strpos($digits, '94') === 0 && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        } elseif (strlen($digits) === 9 && strpos($digits, '7') === 0) {
            $digits = '0' . $digits;
        }
        return preg_match('/^0\d{9}$/', $digits) ? $digits : '';
    }

    private function requirePhone(string $value): string {
        $phone = $this->phoneOnly($value);
        if ($phone === '') {
            throw new InvalidArgumentException('Enter a 10-digit phone number.');
        }
        return $phone;
    }
}
