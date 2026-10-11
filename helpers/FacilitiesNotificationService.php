<?php
/**
 * In-system facilities notifications. Email/SMS/WhatsApp can hook here later.
 */
class FacilitiesNotificationService {
    private $model;

    public function __construct() {
        require_once BASE_PATH . '/models/FacilitiesTicketModel.php';
        $this->model = new FacilitiesTicketModel();
    }

    public function notify(string $staffId, ?int $ticketId, string $event, string $title, string $message): void {
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

    public function notifyTicket(array $ticket, string $event, string $title, string $message): void {
        $ids = array_filter(array_unique([
            $ticket['requester_staff_id'] ?? '',
            $ticket['responsible_staff_id'] ?? '',
        ]));
        foreach ($ids as $sid) {
            $this->notify((string) $sid, (int) $ticket['id'], $event, $title, $message);
        }
        $this->notifyOfficers((int) $ticket['id'], $event, $title, $message, $ids);
    }

    public function notifyOfficers(?int $ticketId, string $event, string $title, string $message, array $except = []): void {
        foreach ($this->officerStaffIds() as $sid) {
            if (in_array($sid, $except, true)) {
                continue;
            }
            $this->notify($sid, $ticketId, $event, $title, $message);
        }
    }

    public function officerStaffIds(): array {
        $ids = [];
        $res = $this->model->rawQuery("SELECT u.user_name
            FROM `user` u
            WHERE u.user_table <> 'student'
              AND (u.staff_position_type_id IN ('ADM','FAC','FO','MHF') OR LOWER(u.user_name) = 'admin')");
        while ($res && ($row = $res->fetch_assoc())) {
            if (!empty($row['user_name'])) {
                $ids[] = $row['user_name'];
            }
        }
        return array_values(array_unique($ids));
    }

    public function markDueSoonAndOverdue(): int {
        $n = 0;
        $due = $this->model->approachingDeadlines(2);
        foreach ($due as $ticket) {
            if ($this->alreadyNotifiedToday((int) $ticket['id'], 'deadline_approaching')) {
                continue;
            }
            $this->notifyTicket($ticket, 'deadline_approaching', 'Deadline approaching', $ticket['ticket_number'] . ' is due on ' . $ticket['deadline'] . '.');
            $n++;
        }
        $over = $this->model->search(['overdue' => 1], 1, 100);
        foreach ($over['rows'] as $ticket) {
            if ($this->alreadyNotifiedToday((int) $ticket['id'], 'ticket_overdue')) {
                continue;
            }
            $this->notifyTicket($ticket, 'ticket_overdue', 'Ticket overdue', $ticket['ticket_number'] . ' is overdue by ' . (int) $ticket['overdue_days'] . ' day(s).');
            $n++;
        }
        return $n;
    }

    private function alreadyNotifiedToday(int $ticketId, string $event): bool {
        return $this->model->hasNotificationToday($ticketId, $event);
    }
}
