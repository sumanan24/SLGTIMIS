<?php
/**
 * Accept, start, progress, and completion submission.
 */
class FacilitiesProgressService {
    private $tickets;
    private $model;

    public function __construct() {
        require_once BASE_PATH . '/helpers/FacilitiesTicketService.php';
        $this->tickets = new FacilitiesTicketService();
        $this->model = $this->tickets->model();
    }

    public function accept(array $actor, array $ticket): array {
        if (!$this->tickets->canWork($actor, $ticket)) {
            throw new RuntimeException('Only the assigned person can accept this ticket.');
        }
        if (!in_array($ticket['status'], [FacilitiesTicketService::ST_ASSIGNED, FacilitiesTicketService::ST_REOPENED], true)) {
            throw new RuntimeException('This ticket is not waiting for acceptance.');
        }
        $this->tickets->changeStatus($actor, $ticket, FacilitiesTicketService::ST_ACCEPTED, 'Assignment accepted');
        $this->addProgress($actor, $ticket, (int) $ticket['progress_percent'], 'Work accepted', '');
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'ticket_accepted', 'Work accepted', $actor['name'] . ' accepted ' . $ticket['ticket_number'] . '.');
        return $fresh;
    }

    public function start(array $actor, array $ticket): array {
        if (!$this->tickets->canWork($actor, $ticket)) {
            throw new RuntimeException('Only the assigned person can start this work.');
        }
        $this->tickets->changeStatus($actor, $ticket, FacilitiesTicketService::ST_PROGRESS, 'Work started');
        $pct = max(10, (int) $ticket['progress_percent']);
        $this->model->updateTicket((int) $ticket['id'], ['progress_percent' => $pct]);
        $this->addProgress($actor, $ticket, $pct, 'Work started', '');
        return $this->model->getDetailed((int) $ticket['id']);
    }

    public function updateProgress(array $actor, array $ticket, int $percent, string $update, string $remarks = ''): array {
        if (!$this->tickets->canWork($actor, $ticket) && empty($actor['is_officer'])) {
            throw new RuntimeException('You cannot update progress on this ticket.');
        }
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Progress must be between 0 and 100.');
        }
        if (trim($update) === '') {
            throw new InvalidArgumentException('Enter a work update.');
        }
        $old = (int) $ticket['progress_percent'];
        $status = $ticket['status'];
        if ($status === FacilitiesTicketService::ST_ACCEPTED || $status === FacilitiesTicketService::ST_ASSIGNED) {
            $status = FacilitiesTicketService::ST_PROGRESS;
            $this->tickets->changeStatus($actor, $ticket, $status, 'Progress update');
        }
        $this->model->updateTicket((int) $ticket['id'], ['progress_percent' => $percent]);
        $this->addProgress($actor, $ticket, $percent, $update, $remarks);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Progress Updated', (string) $old, (string) $percent, $update);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'progress_updated', 'Progress updated', $ticket['ticket_number'] . ' is now ' . $percent . '%.');
        return $fresh;
    }

    public function submitCompletion(array $actor, array $ticket, string $description, string $completedDate, string $remarks = ''): array {
        if (!$this->tickets->canWork($actor, $ticket)) {
            throw new RuntimeException('Only the assigned person can submit completion.');
        }
        if (trim($description) === '') {
            throw new InvalidArgumentException('Completion description is required.');
        }
        $hasEvidence = $this->model->hasCompletionEvidence((int) $ticket['id']);
        if (!$hasEvidence && empty($actor['is_officer'])) {
            throw new RuntimeException('Upload at least one completion evidence file before submitting.');
        }
        $this->model->updateTicket((int) $ticket['id'], [
            'status' => FacilitiesTicketService::ST_PENDING,
            'progress_percent' => 100,
            'expected_completion_date' => $completedDate ?: date('Y-m-d'),
        ]);
        $this->addProgress($actor, $ticket, 100, $description, $remarks);
        $this->tickets->statusHistory((int) $ticket['id'], $ticket['status'], FacilitiesTicketService::ST_PENDING, $actor['staff_id'], $description);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Completed', $ticket['status'], FacilitiesTicketService::ST_PENDING, $description);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyOfficers('completion_submitted', (int) $ticket['id'], 'Completion submitted', $ticket['ticket_number'] . ' is awaiting verification.');
        $this->tickets->notifyWatchers($fresh, 'completion_submitted', 'Work completed', $ticket['ticket_number'] . ' is awaiting verification.');
        return $fresh;
    }

    private function addProgress(array $actor, array $ticket, int $percent, string $update, string $remarks): void {
        $this->model->addChild('facilities_progress_updates', [
            'ticket_id' => $ticket['id'],
            'progress_percent' => $percent,
            'status' => $ticket['status'],
            'work_update' => $update,
            'remarks' => $remarks,
            'created_by' => $actor['staff_id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
