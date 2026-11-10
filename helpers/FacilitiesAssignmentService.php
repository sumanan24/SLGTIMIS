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
            throw new RuntimeException('You do not have permission to assign this ticket.');
        }
        $type = (string) ($input['assignment_type'] ?? 'staff');
        if (!in_array($type, ['staff', 'team', 'vendor'], true)) {
            throw new InvalidArgumentException('Please select a valid assignee type.');
        }
        if ($type === 'vendor') {
            return $this->closeWithVendor($actor, $ticket, $input);
        }

        // The form sends every ticked person as team_staff_ids[]: one person = direct assignment, two or more = team
        if ($type !== 'vendor') {
            $picked = array_values(array_unique(array_filter(array_map(static function ($v) {
                return trim((string) $v);
            }, (array) ($input['team_staff_ids'] ?? [])), static function ($v) {
                return $v !== '';
            })));
            if ($picked) {
                $type = count($picked) > 1 ? 'team' : 'staff';
                $input['team_staff_ids'] = $picked;
                $input['staff_id'] = $picked[0];
            }
        }

        // Who will do the work: one staff member, or a team of two or more
        if ($type === 'team') {
            $rawIds = $input['team_staff_ids'] ?? [];
            $ids = array_values(array_unique(array_filter(array_map(static function ($v) {
                return trim((string) $v);
            }, is_array($rawIds) ? $rawIds : []), static function ($v) {
                return $v !== '';
            })));
            if (count($ids) < 2) {
                throw new InvalidArgumentException('Please select at least two staff members for a team.');
            }
        } else {
            $one = trim((string) ($input['staff_id'] ?? ''));
            if ($one === '') {
                throw new InvalidArgumentException('Please select a staff member.');
            }
            $ids = [$one];
        }
        $names = $this->model->staffNamesByIds($ids);
        foreach ($ids as $id) {
            if (!isset($names[$id])) {
                throw new InvalidArgumentException('One of the selected staff members could not be found.');
            }
        }
        // Everyone except an admin can only give work to people of their own department
        if (empty($actor['is_admin'])) {
            $own = (string) (($actor['hod_department_id'] ?? '') ?: ($actor['department_id'] ?? ''));
            if ($own !== '') {
                foreach ($ids as $id) {
                    if ((string) $this->model->staffDepartmentId($id) !== $own) {
                        throw new InvalidArgumentException('You can assign this ticket only to staff of your own department.');
                    }
                }
            }
        }
        if (empty($input['deadline'])) {
            // No deadline is asked on the form: keep the current one while it is still ahead, otherwise use the priority's completion time
            if (!empty($ticket['deadline']) && substr((string) $ticket['deadline'], 0, 10) >= date('Y-m-d')) {
                $input['deadline'] = substr((string) $ticket['deadline'], 0, 10);
            } else {
                $days = max(1, (int) $this->model->slaDays((string) ($ticket['current_priority'] ?? 'medium')));
                $input['deadline'] = date('Y-m-d', strtotime('+' . $days . ' days'));
            }
        }
        $leadId = $ids[0];
        $deptId = $this->model->staffDepartmentId($leadId);
        $others = [];
        foreach (array_slice($ids, 1) as $id) {
            $others[] = $names[$id];
        }
        $supporting = $type === 'team' ? implode(', ', $others) : trim((string) ($input['supporting_staff'] ?? ''));
        $startDate = !empty($input['start_date']) ? $input['start_date'] : date('Y-m-d');
        $expected = !empty($input['expected_completion_date']) ? $input['expected_completion_date'] : $input['deadline'];
        $instructions = trim((string) ($input['instructions'] ?? ''));

        $this->model->clearCurrentAssignments((int) $ticket['id']);
        foreach ($ids as $i => $id) {
            // One current assignment row per person, so every team member can see and work on the ticket
            $this->model->addChild('facilities_assignments', [
                'ticket_id' => $ticket['id'],
                'assignment_type' => $type,
                'department_id' => $this->model->staffDepartmentId($id) ?: null,
                'staff_id' => $id,
                'vendor_name' => null,
                'supporting_staff' => $supporting,
                'assigned_by' => $actor['staff_id'],
                'assigned_at' => date('Y-m-d H:i:s'),
                'start_date' => $startDate,
                'deadline' => $input['deadline'],
                'expected_completion_date' => $expected,
                'instructions' => $instructions,
                'remarks' => $type === 'team' ? ($i === 0 ? 'Team lead' : 'Team member') : trim((string) ($input['remarks'] ?? '')),
                'is_current' => 1,
            ]);
        }
        $oldDeadline = $ticket['deadline'];
        // A ticket that is waiting for another department's support job stays paused after a reassignment
        $newStatus = $this->model->openSubjobs((int) $ticket['id']) ? FacilitiesTicketService::ST_SUPPORT : FacilitiesTicketService::ST_ASSIGNED;
        $this->model->updateTicket((int) $ticket['id'], [
            'status' => $newStatus,
            'assignment_type' => $type,
            'responsible_staff_id' => $leadId,
            'responsible_department_id' => $deptId !== '' ? $deptId : ($ticket['responsible_department_id'] ?? null),
            'vendor_name' => null,
            'start_date' => $startDate,
            'deadline' => $input['deadline'],
            'expected_completion_date' => $expected,
        ]);
        $isReassign = !empty($ticket['responsible_staff_id']) || !empty($ticket['responsible_department_id']);
        $action = $isReassign ? 'Reassigned' : 'Assigned';
        $target = implode(', ', $ids);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], $newStatus, $actor['staff_id'], $instructions);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], $action, $ticket['responsible_staff_id'] ?? '', $target, $instructions);
        if ((string) $oldDeadline !== (string) $input['deadline']) {
            $this->recordDeadline($ticket, $actor, $input['deadline'], $isReassign ? 'Reassignment deadline' : 'Initial assignment deadline');
        }
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $who = $type === 'team' ? 'a team (' . implode(', ', array_values($names)) . ')' : $names[$leadId];
        $this->tickets->notifyWatchers($fresh, $isReassign ? 'ticket_reassigned' : 'ticket_assigned', $action, 'Ticket ' . $ticket['ticket_number'] . ' assigned to ' . $who . '.');
        if ($fresh) {
            // The person who raised the ticket also learns who is handling it
            $this->tickets->smsRequester($fresh, $ids, $isReassign ? 'reassigned' : 'assigned');
        }
        foreach ($ids as $i => $id) {
            if ($i > 0) {
                $this->tickets->notifyStaff($id, (int) $ticket['id'], $isReassign ? 'ticket_reassigned' : 'ticket_assigned', 'You were added to a team', $ticket['ticket_number'] . ' was assigned to a team that includes you.');
            }
            if ($fresh) {
                $this->tickets->smsResponsible($id, $fresh, $isReassign ? 'reassigned' : 'assigned', (string) $actor['staff_id']);
            }
        }
        return $fresh;
    }

    /**
     * External vendor: no staff or department is chosen. The vendor name and a proper reason
     * are recorded and the ticket is closed.
     */
    private function closeWithVendor(array $actor, array $ticket, array $input): array {
        $reason = trim((string) ($input['vendor_reason'] ?? ''));
        $len = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($len < 10) {
            throw new InvalidArgumentException('Please give a proper reason for closing this ticket (at least 10 characters).');
        }
        $remarks = 'Handled by an external vendor. Reason: ' . $reason;
        $now = date('Y-m-d H:i:s');
        $this->model->clearCurrentAssignments((int) $ticket['id']);
        $this->model->addChild('facilities_assignments', [
            'ticket_id' => $ticket['id'],
            'assignment_type' => 'vendor',
            'department_id' => null,
            'staff_id' => null,
            'vendor_name' => null,
            'supporting_staff' => '',
            'assigned_by' => $actor['staff_id'],
            'assigned_at' => $now,
            'start_date' => date('Y-m-d'),
            'deadline' => null,
            'expected_completion_date' => null,
            'instructions' => '',
            'remarks' => $reason,
            'is_current' => 1,
        ]);
        $this->model->updateTicket((int) $ticket['id'], [
            'status' => FacilitiesTicketService::ST_CLOSED,
            'assignment_type' => 'vendor',
            'responsible_staff_id' => null,
            'vendor_name' => null,
            'closed_by' => $actor['staff_id'],
            'closed_at' => $now,
            'closure_remarks' => $remarks,
            'no_evidence_reason' => $reason,
        ]);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], FacilitiesTicketService::ST_CLOSED, $actor['staff_id'], $remarks);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Closed', $ticket['status'], FacilitiesTicketService::ST_CLOSED, $remarks);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'ticket_closed', 'Ticket closed', $ticket['ticket_number'] . ' was closed. It is being handled by an external vendor.');
        return $fresh;
    }
    public function extendDeadline(array $actor, array $ticket, string $deadline, string $reason): array {
        if (!$this->tickets->canManage($actor, $ticket) && !$this->tickets->canWork($actor, $ticket)) {
            throw new RuntimeException('You cannot change this deadline.');
        }
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required for deadline changes.');
        }
        if (!$this->tickets->canManage($actor, $ticket) && $this->tickets->canWork($actor, $ticket)) {
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
