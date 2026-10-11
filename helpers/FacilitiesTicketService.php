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
    const STATUSES = [
        'new' => 'New',
        'under_review' => 'Under Review',
        'assigned' => 'Assigned',
        'accepted' => 'Accepted',
        'in_progress' => 'In Progress',
        'completed_pending_verification' => 'Completed – Awaiting Verification',
        'verified' => 'Verified',
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
            'contact' => trim((string) (($staff['staff_pno'] ?? '') . ' ' . ($staff['staff_email'] ?? ''))),
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

    public function canAssign(array $actor, array $ticket): bool {
        return !empty($actor['is_officer']) && !in_array($ticket['status'], ['closed', 'cancelled'], true);
    }

    public function canWork(array $actor, array $ticket): bool {
        return $actor['staff_id'] !== '' && $ticket['responsible_staff_id'] === $actor['staff_id']
            && !in_array($ticket['status'], self::CLOSED, true);
    }

    public function canVerify(array $actor, array $ticket): bool {
        return !empty($actor['is_officer']) && $ticket['status'] === self::ST_PENDING;
    }

    public function canClose(array $actor, array $ticket): bool {
        return !empty($actor['is_officer']) && in_array($ticket['status'], [self::ST_VERIFIED], true);
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
        if (($input['title'] ?? '') === '' || ($input['description'] ?? '') === '' || empty($input['category_id']) || ($input['location'] ?? '') === '' || empty($input['priority'])) {
            throw new InvalidArgumentException('Title, description, category, location and priority are required.');
        }
        if (!isset(self::PRIORITIES[$input['priority']])) {
            throw new InvalidArgumentException('Invalid priority.');
        }
        $seq = $this->model->nextTicketNumber();
        $id = $this->model->create([
            'ticket_number' => $seq['ticket_number'],
            'ticket_year' => $seq['ticket_year'],
            'ticket_seq' => $seq['ticket_seq'],
            'status' => self::ST_NEW,
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
            'requester_contact' => trim((string) ($input['contact'] ?? $actor['contact'])),
            'required_date' => !empty($input['required_date']) ? $input['required_date'] : null,
            'progress_percent' => 0,
        ]);
        if (!$id) {
            throw new RuntimeException('Could not create ticket.');
        }
        $this->history((int) $id, $actor['staff_id'], 'Ticket Created', null, self::ST_NEW, $seq['ticket_number']);
        $this->statusHistory((int) $id, null, self::ST_NEW, $actor['staff_id'], 'Ticket created');
        $this->notifyOfficers('ticket_created', (int) $id, 'New facilities ticket', $seq['ticket_number'] . ' was submitted.');
        return $this->model->getDetailed((int) $id);
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
        if (empty($actor['is_officer'])) {
            throw new RuntimeException('Only the Facilities Officer can change priority.');
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
        return ['statuses' => self::STATUSES, 'priorities' => self::PRIORITIES];
    }
}
