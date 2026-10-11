<?php
/**
 * Assign / reassign facilities tickets and record deadline history.
 */
class FacilitiesAssignmentService {
    private $tickets;
    private $model;

    public function __construct() {
        require_once BASE_PATH . '/helpers/FacilitiesTicketService.php';
        $this->tickets = new FacilitiesTicketService();
        $this->model = $this->tickets->model();
    }

    public function assign(array $actor, array $ticket, array $input): array {
        if (!$this->tickets->canAssign($actor, $ticket)) {
            throw new RuntimeException('You cannot assign this ticket.');
        }
        $type = $input['assignment_type'] ?? 'staff';
        if (!in_array($type, ['staff', 'department', 'team', 'vendor'], true)) {
            throw new InvalidArgumentException('Invalid assignment type.');
        }
        $staffId = trim((string) ($input['staff_id'] ?? ''));
        $deptId = trim((string) ($input['department_id'] ?? ''));
        $vendor = trim((string) ($input['vendor_name'] ?? ''));
        if ($type === 'staff' && $staffId === '') {
            throw new InvalidArgumentException('Select a responsible person.');
        }
        if ($type === 'department' && $deptId === '') {
            throw new InvalidArgumentException('Select a responsible department.');
        }
        if ($type === 'vendor' && $vendor === '') {
            throw new InvalidArgumentException('Enter the vendor name.');
        }
        if (empty($input['deadline'])) {
            throw new InvalidArgumentException('Deadline is required.');
        }
        $this->model->clearCurrentAssignments((int) $ticket['id']);
        $this->model->addChild('facilities_assignments', [
            'ticket_id' => $ticket['id'],
            'assignment_type' => $type,
            'department_id' => $deptId !== '' ? $deptId : null,
            'staff_id' => $staffId !== '' ? $staffId : null,
            'vendor_name' => $vendor !== '' ? $vendor : null,
            'supporting_staff' => trim((string) ($input['supporting_staff'] ?? '')),
            'assigned_by' => $actor['staff_id'],
            'assigned_at' => date('Y-m-d H:i:s'),
            'start_date' => !empty($input['start_date']) ? $input['start_date'] : date('Y-m-d'),
            'deadline' => $input['deadline'],
            'expected_completion_date' => (!empty($input['expected_completion_date']) ? $input['expected_completion_date'] : $input['deadline']),
            'instructions' => trim((string) ($input['instructions'] ?? '')),
            'remarks' => trim((string) ($input['remarks'] ?? '')),
            'is_current' => 1,
        ]);
        $oldDeadline = $ticket['deadline'];
        $this->model->updateTicket((int) $ticket['id'], [
            'status' => FacilitiesTicketService::ST_ASSIGNED,
            'assignment_type' => $type,
            'responsible_staff_id' => $staffId !== '' ? $staffId : null,
            'responsible_department_id' => $deptId !== '' ? $deptId : ($ticket['responsible_department_id'] ?? null),
            'vendor_name' => $vendor !== '' ? $vendor : null,
            'start_date' => !empty($input['start_date']) ? $input['start_date'] : date('Y-m-d'),
            'deadline' => $input['deadline'],
            'expected_completion_date' => (!empty($input['expected_completion_date']) ? $input['expected_completion_date'] : $input['deadline']),
        ]);
        $isReassign = !empty($ticket['responsible_staff_id']) || !empty($ticket['responsible_department_id']);
        $action = $isReassign ? 'Reassigned' : 'Assigned';
        $target = $staffId !== '' ? $staffId : ($deptId !== '' ? $deptId : $vendor);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], FacilitiesTicketService::ST_ASSIGNED, $actor['staff_id'], $input['instructions'] ?? '');
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], $action, $ticket['responsible_staff_id'] ?? '', $target, (string) ($input['instructions'] ?? ''));
        if ((string) $oldDeadline !== (string) $input['deadline']) {
            $this->recordDeadline($ticket, $actor, $input['deadline'], $isReassign ? 'Reassignment deadline' : 'Initial assignment deadline');
        }
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, $isReassign ? 'ticket_reassigned' : 'ticket_assigned', $action, 'Ticket ' . $ticket['ticket_number'] . ' assigned to ' . $target . '.');
        return $fresh;
    }

    public function extendDeadline(array $actor, array $ticket, string $deadline, string $reason): array {
        if (empty($actor['is_officer']) && !$this->tickets->canWork($actor, $ticket)) {
            throw new RuntimeException('You cannot change this deadline.');
        }
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required for deadline changes.');
        }
        if (empty($actor['is_officer']) && $this->tickets->canWork($actor, $ticket)) {
            $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Deadline Extension Requested', $ticket['deadline'], $deadline, $reason);
            $this->tickets->notifyOfficers('deadline_updated', (int) $ticket['id'], 'Deadline extension requested', $actor['name'] . ' requested ' . $deadline . ': ' . $reason);
            return $this->model->getDetailed((int) $ticket['id']);
        }
        $this->model->updateTicket((int) $ticket['id'], [
            'deadline' => $deadline,
            'expected_completion_date' => $deadline,
        ]);
        $this->recordDeadline($ticket, $actor, $deadline, $reason);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Deadline Updated', $ticket['deadline'], $deadline, $reason);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'deadline_updated', 'Deadline updated', 'New deadline: ' . $deadline . '.');
        return $fresh;
    }

    private function recordDeadline(array $ticket, array $actor, string $new, string $reason): void {
        $this->model->addChild('facilities_deadline_history', [
            'ticket_id' => $ticket['id'],
            'old_value' => $ticket['deadline'] ?: null,
            'new_value' => $new,
            'reason' => $reason,
            'changed_by' => $actor['staff_id'],
            'changed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
