<?php
/**
 * Support jobs: the person doing a facilities ticket asks ANOTHER department for help.
 *
 *  1. The assignee (or the manager of the ticket) raises a support job and picks the department.
 *     The main ticket becomes "Waiting for Support".
 *  2. The HOD of that department receives it (gets a notice + SMS, opening the ticket marks it "received").
 *  3. The HOD assigns a person of their own department.
 *  4. That person (or the HOD) completes the support job.
 *  5. When no support job is open any more the main ticket returns to the status it had before and the main job continues.
 *
 * Flow of one support job: requested -> assigned -> completed   (or declined by the HOD / cancelled by the requester)
 */
class FacilitiesSubjobService {
    const REQUESTED = 'requested';
    const ASSIGNED = 'assigned';
    const COMPLETED = 'completed';
    const DECLINED = 'declined';
    const CANCELLED = 'cancelled';

    const LABELS = [
        'requested' => 'Waiting for HOD',
        'assigned' => 'Assigned',
        'completed' => 'Completed',
        'declined' => 'Declined',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Ticket statuses in which support can be requested: the main job is running, or the HOD / officer has just
     * received the ticket (new / under review) and wants help from another department before assigning it.
     */
    const REQUESTABLE = ['new', 'under_review', 'assigned', 'accepted', 'in_progress', 'reopened', 'waiting_support'];

    private $tickets;
    private $model;

    public function __construct() {
        require_once BASE_PATH . '/helpers/FacilitiesTicketService.php';
        $this->tickets = new FacilitiesTicketService();
        $this->model = $this->tickets->model();
    }

    // ---- Permissions -------------------------------------------------------------------------

    public function canRequest(array $actor, array $ticket): bool {
        if (!in_array($ticket['status'] ?? '', self::REQUESTABLE, true)) {
            return false;
        }
        return $this->tickets->canWork($actor, $ticket) || $this->tickets->canManage($actor, $ticket);
    }

    /** The HOD of the department the job was sent to (also officers and admins). */
    public function canHandle(array $actor, array $row): bool {
        if (!empty($actor['is_officer']) || !empty($actor['is_admin'])) {
            return true;
        }
        return !empty($actor['is_hod']) && (string) ($actor['hod_department_id'] ?? '') !== ''
            && (string) $actor['hod_department_id'] === (string) ($row['department_id'] ?? '');
    }

    public function canComplete(array $actor, array $row): bool {
        if (($row['status'] ?? '') !== self::ASSIGNED) {
            return false;
        }
        return ((string) ($row['assigned_staff_id'] ?? '') !== '' && (string) $row['assigned_staff_id'] === (string) ($actor['staff_id'] ?? ''))
            || $this->canHandle($actor, $row);
    }

    public function canCancel(array $actor, array $ticket, array $row): bool {
        if (!$this->isOpen($row)) {
            return false;
        }
        return (string) ($row['requested_by'] ?? '') === (string) ($actor['staff_id'] ?? '')
            || $this->tickets->canManage($actor, $ticket);
    }

    public function isOpen(array $row): bool {
        return in_array($row['status'] ?? '', [self::REQUESTED, self::ASSIGNED], true);
    }

    // ---- Actions -----------------------------------------------------------------------------

    public function request(array $actor, array $ticket, string $departmentId, string $description): array {
        if (!$this->canRequest($actor, $ticket)) {
            throw new RuntimeException('Support cannot be requested for this ticket in its current status.');
        }
        $departmentId = trim($departmentId);
        $deptName = $departmentId !== '' ? $this->model->departmentName($departmentId) : '';
        if ($deptName === '') {
            throw new InvalidArgumentException('Choose the department that should help.');
        }
        if ($departmentId === (string) ($ticket['responsible_department_id'] ?? '')) {
            throw new InvalidArgumentException('Choose another department. This ticket already belongs to ' . $deptName . '.');
        }
        $description = trim($description);
        if ($description === '') {
            throw new InvalidArgumentException('Describe the support job that is needed.');
        }
        foreach ($this->model->openSubjobs((int) $ticket['id']) as $open) {
            if ((string) $open['department_id'] === $departmentId) {
                throw new InvalidArgumentException('A support job for ' . $deptName . ' is already open on this ticket.');
            }
        }
        $hods = $this->model->hodStaffIds($departmentId);
        if (!$hods) {
            throw new RuntimeException('No HOD is set for ' . $deptName . ', so the request cannot be sent.');
        }

        $firstOne = ($ticket['status'] ?? '') !== FacilitiesTicketService::ST_SUPPORT;
        $id = $this->model->addChild('facilities_subjobs', [
            'ticket_id' => (int) $ticket['id'],
            'department_id' => $departmentId,
            'description' => $this->cut($description, 1000),
            'status' => self::REQUESTED,
            'prev_status' => $firstOne ? (string) $ticket['status'] : null,
            'requested_by' => (string) $actor['staff_id'],
            'requested_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$id) {
            throw new RuntimeException('Could not save the support job.');
        }
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Support Requested', '', self::REQUESTED, $deptName . ': ' . $this->cut($description, 200));
        if ($firstOne) {
            $this->tickets->changeStatus($actor, $ticket, FacilitiesTicketService::ST_SUPPORT, 'Support requested from ' . $deptName);
        }
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        foreach ($hods as $hod) {
            $this->tickets->notifyStaff($hod, (int) $ticket['id'], 'subjob_requested', 'Support requested', $ticket['ticket_number'] . ': ' . ($actor['name'] ?? '') . ' needs help from ' . $deptName . '.');
            $this->sms($hod, (int) $ticket['id'], $this->requestSms($ticket, $actor, $deptName, $description));
        }
        $this->tickets->notifyWatchers($fresh, 'subjob_requested', 'Support requested', $ticket['ticket_number'] . ': waiting for support from ' . $deptName . '.');
        return $fresh;
    }

    /** The HOD of the department opens the ticket: their waiting requests are marked as received. */
    public function markReceivedByHod(array $actor, array $ticket): void {
        if (empty($actor['is_hod']) || (string) ($actor['hod_department_id'] ?? '') === '') {
            return;
        }
        foreach ($this->model->openSubjobs((int) $ticket['id']) as $row) {
            if ($row['status'] !== self::REQUESTED || !empty($row['received_at'])
                || (string) $row['department_id'] !== (string) $actor['hod_department_id']) {
                continue;
            }
            $this->model->updateChild('facilities_subjobs', (int) $row['id'], [
                'received_by' => (string) $actor['staff_id'],
                'received_at' => date('Y-m-d H:i:s'),
            ]);
            $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Support Received', self::REQUESTED, self::REQUESTED, 'Received by HOD of ' . $row['department_name']);
            $this->tickets->notifyStaff((string) $row['requested_by'], (int) $ticket['id'], 'subjob_received', 'Support request received', $ticket['ticket_number'] . ': the HOD of ' . $row['department_name'] . ' has received your request.');
        }
    }

    /** The HOD gives the support job to one person of their own department. */
    public function assign(array $actor, array $ticket, int $id, string $staffId, string $instructions = ''): void {
        $row = $this->requireRow($ticket, $id);
        if (!$this->canHandle($actor, $row)) {
            throw new RuntimeException('Only the HOD of ' . $row['department_name'] . ' can assign this support job.');
        }
        if (!$this->isOpen($row)) {
            throw new RuntimeException('This support job is already closed.');
        }
        $staffId = trim($staffId);
        if ($staffId === '') {
            throw new InvalidArgumentException('Select the person who will do the support job.');
        }
        $names = $this->model->staffNamesByIds([$staffId]);
        if (!isset($names[$staffId])) {
            throw new InvalidArgumentException('The selected staff member could not be found.');
        }
        if ((string) $this->model->staffDepartmentId($staffId) !== (string) $row['department_id']) {
            throw new InvalidArgumentException('Choose a person from ' . $row['department_name'] . ' only.');
        }
        $this->model->updateChild('facilities_subjobs', $id, [
            'status' => self::ASSIGNED,
            'assigned_staff_id' => $staffId,
            'assigned_by' => (string) $actor['staff_id'],
            'assigned_at' => date('Y-m-d H:i:s'),
            'instructions' => $this->cut(trim($instructions), 500) ?: null,
            'received_by' => $row['received_by'] ?: (string) $actor['staff_id'],
            'received_at' => $row['received_at'] ?: date('Y-m-d H:i:s'),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Support Assigned', (string) $row['status'], self::ASSIGNED, $row['department_name'] . ': ' . $names[$staffId]);
        if ($staffId !== (string) $actor['staff_id']) {
            $this->tickets->notifyStaff($staffId, (int) $ticket['id'], 'subjob_assigned', 'Support job assigned to you', $ticket['ticket_number'] . ': ' . $this->cut((string) $row['description'], 120));
            $this->sms($staffId, (int) $ticket['id'], $this->assignedSms($ticket, $row, $instructions));
        }
        $this->tickets->notifyStaff((string) $row['requested_by'], (int) $ticket['id'], 'subjob_assigned', 'Support person assigned', $ticket['ticket_number'] . ': ' . $names[$staffId] . ' (' . $row['department_name'] . ') will help.');
    }

    public function complete(array $actor, array $ticket, int $id, string $note): void {
        $row = $this->requireRow($ticket, $id);
        if (!$this->canComplete($actor, $row)) {
            throw new RuntimeException('Only the assigned person or the HOD can complete this support job.');
        }
        $note = trim($note);
        if ($note === '') {
            throw new InvalidArgumentException('Describe the work that was done.');
        }
        $this->model->updateChild('facilities_subjobs', $id, [
            'status' => self::COMPLETED,
            'completed_by' => (string) $actor['staff_id'],
            'completed_at' => date('Y-m-d H:i:s'),
            'closing_note' => $this->cut($note, 1000),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Support Completed', self::ASSIGNED, self::COMPLETED, $row['department_name'] . ': ' . $this->cut($note, 200));
        $this->afterClose($actor, $ticket, $row, 'subjob_completed', 'Support job completed', $row['department_name'] . ' finished the support job: ' . $this->cut($note, 120));
    }

    public function decline(array $actor, array $ticket, int $id, string $reason): void {
        $row = $this->requireRow($ticket, $id);
        if (!$this->canHandle($actor, $row)) {
            throw new RuntimeException('Only the HOD of ' . $row['department_name'] . ' can decline this support job.');
        }
        if (!$this->isOpen($row)) {
            throw new RuntimeException('This support job is already closed.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Enter the reason for declining.');
        }
        $this->model->updateChild('facilities_subjobs', $id, [
            'status' => self::DECLINED,
            'completed_by' => (string) $actor['staff_id'],
            'completed_at' => date('Y-m-d H:i:s'),
            'closing_note' => $this->cut($reason, 1000),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Support Declined', (string) $row['status'], self::DECLINED, $row['department_name'] . ': ' . $reason);
        $this->afterClose($actor, $ticket, $row, 'subjob_declined', 'Support job declined', $row['department_name'] . ' cannot help: ' . $this->cut($reason, 120));
    }

    public function cancel(array $actor, array $ticket, int $id, string $reason): void {
        $row = $this->requireRow($ticket, $id);
        if (!$this->canCancel($actor, $ticket, $row)) {
            throw new RuntimeException('You cannot cancel this support job.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Enter the reason for cancellation.');
        }
        $this->model->updateChild('facilities_subjobs', $id, [
            'status' => self::CANCELLED,
            'completed_by' => (string) $actor['staff_id'],
            'completed_at' => date('Y-m-d H:i:s'),
            'closing_note' => $this->cut($reason, 1000),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Support Cancelled', (string) $row['status'], self::CANCELLED, $row['department_name'] . ': ' . $reason);
        foreach ($this->model->hodStaffIds((string) $row['department_id']) as $hod) {
            $this->tickets->notifyStaff($hod, (int) $ticket['id'], 'subjob_cancelled', 'Support job cancelled', $ticket['ticket_number'] . ': the support request was cancelled.');
        }
        if (!empty($row['assigned_staff_id'])) {
            $this->tickets->notifyStaff((string) $row['assigned_staff_id'], (int) $ticket['id'], 'subjob_cancelled', 'Support job cancelled', $ticket['ticket_number'] . ': the support request was cancelled.');
        }
        $this->resume($actor, $ticket);
    }

    // ---- Helpers -----------------------------------------------------------------------------

    /** Tell the people of the main job and let the main job continue once no support job is open. */
    private function afterClose(array $actor, array $ticket, array $row, string $event, string $title, string $message): void {
        $targets = array_unique(array_filter([(string) $row['requested_by'], (string) ($ticket['responsible_staff_id'] ?? '')]));
        foreach ($targets as $sid) {
            if ($sid !== (string) $actor['staff_id']) {
                $this->tickets->notifyStaff($sid, (int) $ticket['id'], $event, $title, $ticket['ticket_number'] . ': ' . $message);
            }
        }
        $this->resume($actor, $ticket);
    }

    /** No open support job left: the main ticket goes back to the status it had before, so the main job continues. */
    private function resume(array $actor, array $ticket): void {
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        if (!$fresh || ($fresh['status'] ?? '') !== FacilitiesTicketService::ST_SUPPORT) {
            return;
        }
        if ($this->model->openSubjobs((int) $ticket['id'])) {
            return;
        }
        $back = FacilitiesTicketService::ST_PROGRESS;
        foreach (array_reverse($this->model->subjobs((int) $ticket['id'])) as $row) {
            if (!empty($row['prev_status'])) {
                $back = (string) $row['prev_status'];
                break;
            }
        }
        $fresh = $this->tickets->changeStatus($actor, $fresh, $back, 'Support finished - main job continues');
        $this->tickets->notifyWatchers($fresh, 'subjob_resumed', 'Main job continues', $fresh['ticket_number'] . ': support finished, the main job continues.');
        if (!empty($fresh['responsible_staff_id']) && (string) $fresh['responsible_staff_id'] !== (string) $actor['staff_id']) {
            $this->sms((string) $fresh['responsible_staff_id'], (int) $fresh['id'], "SLGTI MIS - Support Finished\n\nTicket ID: " . $fresh['ticket_number'] . "\nThe support job is finished. Please continue the main job.\n\nOpen: " . APP_URL . '/facilities/view?id=' . (int) $fresh['id']);
        }
    }

    private function requireRow(array $ticket, int $id): array {
        $row = $this->model->subjobById($id);
        if (!$row || (int) $row['ticket_id'] !== (int) $ticket['id']) {
            throw new RuntimeException('Support job not found.');
        }
        return $row;
    }

    private function requestSms(array $ticket, array $actor, string $deptName, string $description): string {
        return "SLGTI MIS - Support Request\n\n"
            . 'Ticket ID: ' . $ticket['ticket_number'] . "\n"
            . 'From: ' . $this->cut((string) ($actor['name'] ?? ''), 40) . "\n"
            . 'Needed: ' . $this->cut(preg_replace('/\s+/', ' ', $description), 100) . "\n"
            . 'Location: ' . $this->cut((string) ($ticket['location'] ?? ''), 60) . "\n\n"
            . 'Please assign a person of ' . $this->cut($deptName, 40) . ".\n\n"
            . 'Open: ' . APP_URL . '/facilities/view?id=' . (int) $ticket['id'];
    }

    private function assignedSms(array $ticket, array $row, string $instructions): string {
        $text = "SLGTI MIS - Support Job Assigned\n\n"
            . 'Ticket ID: ' . $ticket['ticket_number'] . "\n"
            . 'Job: ' . $this->cut(preg_replace('/\s+/', ' ', (string) $row['description']), 100) . "\n"
            . 'Location: ' . $this->cut((string) ($ticket['location'] ?? ''), 60) . "\n";
        if (trim($instructions) !== '') {
            $text .= 'Note: ' . $this->cut(preg_replace('/\s+/', ' ', $instructions), 80) . "\n";
        }
        return $text . "\nOpen: " . APP_URL . '/facilities/view?id=' . (int) $ticket['id'];
    }

    /** Never blocks the workflow: problems are only logged. */
    private function sms(string $staffId, int $ticketId, string $text): void {
        try {
            require_once BASE_PATH . '/services/MobitelSmsService.php';
            $sms = new MobitelSmsService();
            $mobile = $this->model->staffPhone($staffId);
            if ($sms->normalizeSriLankanMobile($mobile) === null) {
                $this->tickets->history($ticketId, $staffId, 'SMS not sent', '', $staffId, 'No valid mobile number on the staff record.');
                return;
            }
            $result = $sms->sendSms($mobile, $text);
            $this->tickets->history($ticketId, $staffId, $result['ok'] ? 'SMS sent' : 'SMS failed', '', $staffId, $result['ok'] ? 'Gateway accepted the message.' : ('Gateway code ' . $result['result_code'] . ' (' . (string) $result['error'] . ').'));
        } catch (Throwable $e) {
            error_log('[FacilitiesSMS] subjob: ' . $e->getMessage());
        }
    }

    private function cut(string $s, int $max): string {
        $s = trim($s);
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }
}
