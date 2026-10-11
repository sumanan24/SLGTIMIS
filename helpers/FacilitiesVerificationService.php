<?php
/**
 * Verify, return, requester confirm, reopen, and close.
 */
class FacilitiesVerificationService {
    private $tickets;
    private $model;

    public function __construct() {
        require_once BASE_PATH . '/helpers/FacilitiesTicketService.php';
        $this->tickets = new FacilitiesTicketService();
        $this->model = $this->tickets->model();
    }

    public function verify(array $actor, array $ticket, string $remarks = ''): array {
        if (!$this->tickets->canVerify($actor, $ticket)) {
            throw new RuntimeException('This ticket is not awaiting verification.');
        }
        $hasEvidence = $this->model->hasCompletionEvidence((int) $ticket['id']);
        if (!$hasEvidence && trim((string) ($ticket['no_evidence_reason'] ?? '')) === '' && trim($remarks) === '') {
            throw new RuntimeException('Record a reason if closing without completion evidence.');
        }
        $this->model->addChild('facilities_verifications', [
            'ticket_id' => $ticket['id'],
            'action' => 'approve',
            'reason' => $remarks,
            'required_correction' => null,
            'new_deadline' => null,
            'verified_by' => $actor['staff_id'],
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
        $this->tickets->changeStatus($actor, $ticket, FacilitiesTicketService::ST_VERIFIED, $remarks ?: 'Verified');
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'ticket_verified', 'Work verified', $ticket['ticket_number'] . ' was verified.');
        return $fresh;
    }

    public function returnForCorrection(array $actor, array $ticket, string $reason, string $correction, ?string $newDeadline = null): array {
        if (empty($actor['is_officer']) || $ticket['status'] !== FacilitiesTicketService::ST_PENDING) {
            throw new RuntimeException('Only the Facilities Officer can return completion evidence.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A return reason is required.');
        }
        $this->model->addChild('facilities_verifications', [
            'ticket_id' => $ticket['id'],
            'action' => 'return',
            'reason' => $reason,
            'required_correction' => $correction,
            'new_deadline' => $newDeadline,
            'verified_by' => $actor['staff_id'],
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
        $data = ['status' => FacilitiesTicketService::ST_PROGRESS];
        if ($newDeadline) {
            $data['deadline'] = $newDeadline;
            $this->model->addChild('facilities_deadline_history', [
                'ticket_id' => $ticket['id'],
                'old_value' => $ticket['deadline'],
                'new_value' => $newDeadline,
                'reason' => 'Returned for correction',
                'changed_by' => $actor['staff_id'],
                'changed_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->model->updateTicket((int) $ticket['id'], $data);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], FacilitiesTicketService::ST_PROGRESS, $actor['staff_id'], $reason);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Returned for Correction', $ticket['status'], FacilitiesTicketService::ST_PROGRESS, $reason . ' ' . $correction);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'completion_rejected', 'Completion returned', $reason);
        return $fresh;
    }

    public function requesterConfirm(array $actor, array $ticket, bool $ok, string $comment = ''): array {
        if (!$this->tickets->canConfirm($actor, $ticket)) {
            throw new RuntimeException('Only the requester can confirm this work.');
        }
        if (!$ok && trim($comment) === '') {
            throw new InvalidArgumentException('Explain why the work is not satisfactory.');
        }
        if ($ok) {
            $this->model->updateTicket((int) $ticket['id'], [
                'requester_confirmation' => 'satisfactory',
                'requester_confirmation_at' => date('Y-m-d H:i:s'),
                'requester_confirmation_comment' => $comment,
            ]);
            $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Requester Confirmed', null, 'satisfactory', $comment);
            $fresh = $this->model->getDetailed((int) $ticket['id']);
            $this->tickets->notifyOfficers('requester_confirmed', (int) $ticket['id'], 'Requester confirmed', $ticket['ticket_number'] . ' was marked satisfactory.');
            return $fresh;
        }
        $this->model->updateTicket((int) $ticket['id'], [
            'status' => FacilitiesTicketService::ST_REOPENED,
            'requester_confirmation' => 'not_satisfactory',
            'requester_confirmation_at' => date('Y-m-d H:i:s'),
            'requester_confirmation_comment' => $comment,
        ]);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], FacilitiesTicketService::ST_REOPENED, $actor['staff_id'], $comment);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Reopened', $ticket['status'], FacilitiesTicketService::ST_REOPENED, $comment);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'ticket_reopened', 'Ticket reopened', $comment);
        return $fresh;
    }

    public function close(array $actor, array $ticket, string $remarks = '', string $noEvidenceReason = ''): array {
        if (!$this->tickets->canClose($actor, $ticket) && !($actor['is_officer'] && $ticket['status'] === FacilitiesTicketService::ST_VERIFIED)) {
            throw new RuntimeException('Assigned staff cannot close a ticket. Verification is required.');
        }
        if ($ticket['responsible_staff_id'] === $actor['staff_id'] && empty($actor['is_officer'])) {
            throw new RuntimeException('Assigned staff cannot permanently close the ticket.');
        }
        $hasEvidence = $this->model->hasCompletionEvidence((int) $ticket['id']);
        if (!$hasEvidence && $noEvidenceReason === '' && trim((string) $ticket['no_evidence_reason']) === '') {
            throw new RuntimeException('Record a reason if closing without completion evidence.');
        }
        if ($ticket['status'] !== FacilitiesTicketService::ST_VERIFIED) {
            throw new RuntimeException('Ticket must be verified before closure.');
        }
        $this->model->updateTicket((int) $ticket['id'], [
            'status' => FacilitiesTicketService::ST_CLOSED,
            'closed_by' => $actor['staff_id'],
            'closed_at' => date('Y-m-d H:i:s'),
            'closure_remarks' => $remarks,
            'no_evidence_reason' => $noEvidenceReason !== '' ? $noEvidenceReason : $ticket['no_evidence_reason'],
        ]);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], FacilitiesTicketService::ST_CLOSED, $actor['staff_id'], $remarks);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Closed', $ticket['status'], FacilitiesTicketService::ST_CLOSED, $remarks);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'ticket_closed', 'Ticket closed', $ticket['ticket_number'] . ' was closed.');
        return $fresh;
    }

    public function reopen(array $actor, array $ticket, string $reason): array {
        if (empty($actor['is_officer']) && $actor['staff_id'] !== $ticket['requester_staff_id']) {
            throw new RuntimeException('You cannot reopen this ticket.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required to reopen.');
        }
        $this->model->updateTicket((int) $ticket['id'], [
            'status' => FacilitiesTicketService::ST_REOPENED,
            'closed_by' => null,
            'closed_at' => null,
        ]);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], FacilitiesTicketService::ST_REOPENED, $actor['staff_id'], $reason);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Reopened', $ticket['status'], FacilitiesTicketService::ST_REOPENED, $reason);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'ticket_reopened', 'Ticket reopened', $reason);
        return $fresh;
    }

    public function hold(array $actor, array $ticket, string $reason): array {
        if (empty($actor['is_officer'])) {
            throw new RuntimeException('Only the Facilities Officer can hold a ticket.');
        }
        return $this->tickets->changeStatus($actor, $ticket, FacilitiesTicketService::ST_HOLD, $reason);
    }

    public function cancel(array $actor, array $ticket, string $reason): array {
        if (empty($actor['is_officer'])) {
            throw new RuntimeException('Only the Facilities Officer can cancel a ticket.');
        }
        return $this->tickets->changeStatus($actor, $ticket, FacilitiesTicketService::ST_CANCELLED, $reason);
    }

    public function review(array $actor, array $ticket): array {
        if (empty($actor['is_officer'])) {
            throw new RuntimeException('Only the Facilities Officer can review tickets.');
        }
        if ($ticket['status'] !== FacilitiesTicketService::ST_NEW) {
            return $ticket;
        }
        return $this->tickets->changeStatus($actor, $ticket, FacilitiesTicketService::ST_REVIEW, 'Taken under review');
    }
}
