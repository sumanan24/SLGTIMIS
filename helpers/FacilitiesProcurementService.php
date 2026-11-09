<?php
/**
 * Optional procurement for a facilities ticket.
 *
 * Most tickets never need it. It is used only when the materials / items required for the job
 * are not available: the assignee (or an officer) raises a request, an officer records the order
 * and then the receipt. Work cannot be submitted for verification while a request is still open.
 *
 * Flow: requested -> ordered -> received   (or cancelled at any time before it is received)
 */
class FacilitiesProcurementService {
    const REQUESTED = 'requested';
    const ORDERED = 'ordered';
    const RECEIVED = 'received';
    const CANCELLED = 'cancelled';

    /** Ticket statuses in which a procurement request may be raised. */
    const REQUESTABLE = ['under_review', 'assigned', 'accepted', 'in_progress', 'reopened', 'on_hold'];

    private $tickets;
    private $model;

    public function __construct() {
        require_once BASE_PATH . '/helpers/FacilitiesTicketService.php';
        $this->tickets = new FacilitiesTicketService();
        $this->model = $this->tickets->model();
    }

    public function canRequest(array $actor, array $ticket): bool {
        if (!in_array($ticket['status'] ?? '', self::REQUESTABLE, true)) {
            return false;
        }
        if ($this->model->openProcurement((int) $ticket['id']) !== null) {
            return false;
        }
        return $this->tickets->canWork($actor, $ticket) || $this->tickets->canManage($actor, $ticket);
    }

    /** Officers record the order and the receipt. */
    public function canProcess(array $actor, array $ticket): bool {
        return $this->tickets->canManage($actor, $ticket) && !in_array($ticket['status'] ?? '', ['closed', 'cancelled', 'rejected'], true);
    }

    public function canCancel(array $actor, array $ticket, array $row): bool {
        if (!in_array($row['status'] ?? '', [self::REQUESTED, self::ORDERED], true)) {
            return false;
        }
        return $this->canProcess($actor, $ticket) || (string) ($row['requested_by'] ?? '') === (string) ($actor['staff_id'] ?? '');
    }

    public function request(array $actor, array $ticket, string $items, string $reason): array {
        if (!$this->canRequest($actor, $ticket)) {
            throw new RuntimeException('Procurement cannot be requested for this ticket in its current status.');
        }
        $items = $this->cleanItems($items);
        if ($items === '') {
            throw new InvalidArgumentException('Enter the items required.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Enter the reason for procurement.');
        }
        $this->model->addChild('facilities_procurement', [
            'ticket_id' => $ticket['id'],
            'items' => $items,
            'reason' => $this->cut($reason, 255),
            'status' => self::REQUESTED,
            'requested_by' => $actor['staff_id'],
            'requested_at' => date('Y-m-d H:i:s'),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Procurement Requested', '', self::REQUESTED, $reason);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyOfficers('procurement_requested', (int) $ticket['id'], 'Procurement requested', $ticket['ticket_number'] . ': ' . $this->cut(str_replace("\n", ', ', $items), 120));
        $this->tickets->notifyWatchers($fresh, 'procurement_requested', 'Procurement requested', $ticket['ticket_number'] . ': procurement requested.');
        return $fresh;
    }

    public function markOrdered(array $actor, array $ticket, int $id, string $supplier, string $expectedDate, string $remarks = ''): void {
        $row = $this->requireRow($ticket, $id);
        if (!$this->canProcess($actor, $ticket)) {
            throw new RuntimeException('Only a facilities officer can record an order.');
        }
        if ($row['status'] !== self::REQUESTED) {
            throw new RuntimeException('This request has already been ordered.');
        }
        $supplier = trim($supplier);
        if ($supplier === '') {
            throw new InvalidArgumentException('Enter the supplier.');
        }
        $expected = null;
        if (trim($expectedDate) !== '') {
            $d = DateTime::createFromFormat('Y-m-d', trim($expectedDate));
            if (!$d || $d->format('Y-m-d') !== trim($expectedDate)) {
                throw new InvalidArgumentException('Invalid expected delivery date.');
            }
            $expected = $d->format('Y-m-d');
        }
        $this->model->updateChild('facilities_procurement', $id, [
            'status' => self::ORDERED,
            'supplier' => $this->cut($supplier, 160),
            'expected_date' => $expected,
            'ordered_by' => $actor['staff_id'],
            'ordered_at' => date('Y-m-d H:i:s'),
            'remarks' => trim($remarks) !== '' ? trim($remarks) : ($row['remarks'] ?? null),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Procurement Ordered', self::REQUESTED, self::ORDERED, $supplier . ($expected ? ' - expected ' . $expected : ''));
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'procurement_ordered', 'Procurement ordered', $ticket['ticket_number'] . ': items ordered from ' . $supplier . '.');
    }

    public function markReceived(array $actor, array $ticket, int $id, string $remarks = ''): void {
        $row = $this->requireRow($ticket, $id);
        if (!$this->canProcess($actor, $ticket)) {
            throw new RuntimeException('Only a facilities officer can record a goods receipt.');
        }
        if (!in_array($row['status'], [self::REQUESTED, self::ORDERED], true)) {
            throw new RuntimeException('This request is already closed.');
        }
        $this->model->updateChild('facilities_procurement', $id, [
            'status' => self::RECEIVED,
            'received_by' => $actor['staff_id'],
            'received_at' => date('Y-m-d H:i:s'),
            'remarks' => trim($remarks) !== '' ? trim($remarks) : ($row['remarks'] ?? null),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Procurement Received', (string) $row['status'], self::RECEIVED, $remarks);
        $fresh = $this->model->getDetailed((int) $ticket['id']);
        $this->tickets->notifyWatchers($fresh, 'procurement_received', 'Procurement received', $ticket['ticket_number'] . ': items received.');
    }

    public function cancel(array $actor, array $ticket, int $id, string $reason): void {
        $row = $this->requireRow($ticket, $id);
        if (!$this->canCancel($actor, $ticket, $row)) {
            throw new RuntimeException('You cannot cancel this procurement request.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Enter the reason for cancellation.');
        }
        $this->model->updateChild('facilities_procurement', $id, [
            'status' => self::CANCELLED,
            'cancelled_by' => $actor['staff_id'],
            'cancelled_at' => date('Y-m-d H:i:s'),
            'remarks' => $this->cut($reason, 500),
        ]);
        $this->tickets->history((int) $ticket['id'], $actor['staff_id'], 'Procurement Cancelled', (string) $row['status'], self::CANCELLED, $reason);
    }

    private function requireRow(array $ticket, int $id): array {
        $row = $this->model->procurementById($id);
        if (!$row || (int) $row['ticket_id'] !== (int) $ticket['id']) {
            throw new RuntimeException('Procurement request not found.');
        }
        return $row;
    }

    /** One item per line, trimmed, no empty lines. */
    private function cleanItems(string $items): string {
        $lines = preg_split('/\R/', $items) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $this->cut($line, 200);
            }
        }
        return implode("\n", array_slice($out, 0, 30));
    }

    private function cut(string $s, int $max): string {
        return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
    }
}
