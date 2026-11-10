<?php
/**
 * Builds the "road map" of a facilities ticket:
 * Create -> Review -> Assign -> [Procurement] -> Work -> Verify -> Close.
 * Procurement is optional: it only appears when materials were not available and a request was raised.
 *
 * Pure presentation logic: it only reads data that FacilitiesController::show() already loads
 * (ticket row, status history, assignments, materials, evidence ...) and works out, for every
 * stage, whether it is done / current / pending / skipped, who did it, when, and the key facts.
 */
class FacilitiesRoadmap {

    /**
     * @param array<string,mixed>            $t              ticket row (getDetailed)
     * @param list<array<string,mixed>>      $statusHistory  facilities_status_history, oldest first
     * @param list<array<string,mixed>>      $assignments    facilities_assignments, newest first
     * @param list<array<string,mixed>>      $team           current team (staff_id, staff_name)
     * @param array<string,list<array>>      $evidence       evidence grouped by type
     * @param list<array<string,mixed>>      $materials
     * @param list<array<string,mixed>>      $progress       progress updates, oldest first
     * @param list<array<string,mixed>>      $verifications
     * @param array<string,string>           $names          staff_id => name
     * @param array<string,string>           $statusLabels
     * @param string                         $routedTo       label of the officer / HOD the ticket was routed to
     * @param list<array<string,mixed>>      $procurements   facilities_procurement rows (optional stage), oldest first
     * @return array<string,mixed>
     */
    public static function build(array $t, array $statusHistory, array $assignments, array $team, array $evidence, array $materials, array $progress, array $verifications, array $names, array $statusLabels = [], string $routedTo = '', array $procurements = []): array {
        $status = (string) ($t['status'] ?? 'new');
        $isVendor = (($t['assignment_type'] ?? '') === 'vendor');
        $name = static function ($id) use ($names): string {
            $id = (string) $id;
            if ($id === '') {
                return '';
            }
            if ($id === 'system') {
                return 'System';
            }
            return $names[$id] ?? $id;
        };

        // Index of the most recent reopen: verify / close only count after it
        $reopenIdx = -1;
        foreach ($statusHistory as $i => $row) {
            if (($row['new_value'] ?? '') === 'reopened') {
                $reopenIdx = $i;
            }
        }
        $pick = static function (array $values, int $after = -1) use ($statusHistory) {
            foreach ($statusHistory as $i => $row) {
                if ($i > $after && in_array($row['new_value'] ?? '', $values, true)) {
                    return $row;
                }
            }
            return null;
        };

        $latestAssign = $assignments[0] ?? null;
        $hasAssignment = $latestAssign !== null || $pick(['assigned']) !== null || !empty($t['responsible_staff_id']) || $isVendor;

        // ---- Create -------------------------------------------------------------------------
        $created = [
            'key' => 'create', 'title' => 'Create', 'icon' => 'fa-file-circle-plus',
            'done' => true,
            'date' => $t['created_at'] ?? null,
            'actor' => (string) ($t['requester_name'] ?? $name($t['requester_staff_id'] ?? '')),
            'facts' => array_values(array_filter([
                ['Ticket', (string) ($t['ticket_number'] ?? '')],
                ['Requested by', (string) ($t['requester_name'] ?? $name($t['requester_staff_id'] ?? ''))],
                ['Location', (string) ($t['location'] ?? '')],
                ['Priority', ucfirst((string) ($t['original_priority'] ?? ''))],
            ], static function ($f) { return trim((string) $f[1]) !== ''; })),
            'files' => $evidence['initial'] ?? [],
            'items' => [],
            'note' => '',
        ];

        // ---- Review -------------------------------------------------------------------------
        $reviewRow = $pick(['under_review']);
        $reviewDone = $reviewRow !== null || $hasAssignment;
        $review = [
            'key' => 'review', 'title' => 'Review', 'icon' => 'fa-magnifying-glass',
            'done' => $reviewDone,
            'date' => $reviewRow['changed_at'] ?? null,
            'actor' => $reviewRow ? $name($reviewRow['changed_by'] ?? '') : '',
            'facts' => array_values(array_filter([
                ['Routed to', $routedTo],
                ['Reviewed by', $reviewRow ? $name($reviewRow['changed_by'] ?? '') : ''],
            ], static function ($f) { return trim((string) $f[1]) !== ''; })),
            'files' => [], 'items' => [], 'note' => '',
        ];

        // ---- Assign -------------------------------------------------------------------------
        $assignRow = $pick(['assigned']);
        $assignDate = $latestAssign['assigned_at'] ?? ($assignRow['changed_at'] ?? null);
        $assignActor = $latestAssign['assigned_by'] ?? ($assignRow['changed_by'] ?? '');
        if ($isVendor) {
            $who = 'External vendor' . (!empty($t['vendor_name']) ? ' - ' . $t['vendor_name'] : '');
        } elseif (count($team) > 1) {
            $parts = [];
            foreach ($team as $i => $m) {
                $parts[] = ($m['staff_name'] ?? $m['staff_id']) . ($i === 0 ? ' (lead)' : '');
            }
            $who = implode(', ', $parts);
        } else {
            $who = (string) ($t['responsible_name'] ?? $name($t['responsible_staff_id'] ?? ''));
        }
        $batches = [];
        foreach ($assignments as $a) {
            $batches[(string) ($a['assigned_at'] ?? '')] = true;
        }
        $reassigns = max(0, count($batches) - 1);
        $assign = [
            'key' => 'assign', 'title' => 'Assign', 'icon' => 'fa-user-check',
            'done' => $hasAssignment,
            'date' => $assignDate,
            'actor' => $name($assignActor),
            'facts' => array_values(array_filter([
                [count($team) > 1 ? 'Team' : 'Assigned to', $hasAssignment ? $who : ''],
                ['Assigned by', $hasAssignment ? $name($assignActor) : ''],
                ['Start date', (string) ($t['start_date'] ?? '')],
                ['Instructions', trim((string) ($latestAssign['instructions'] ?? ''))],
            ], static function ($f) { return trim((string) $f[1]) !== ''; })),
            'files' => [], 'items' => [],
            'due' => $hasAssignment && !$isVendor,
            'note' => ($hasAssignment && $reassigns > 0) ? 'Reassigned ' . $reassigns . ' time(s).' : '',
        ];

        // ---- Procurement (optional: only when materials were not available) -----------------
        $procure = null;
        $procureOpen = false;
        $procureRow = null;
        if ($procurements) {
            $openRow = null;
            $receivedRow = null;
            foreach ($procurements as $p) {
                if (in_array($p['status'] ?? '', ['requested', 'ordered'], true)) {
                    $openRow = $p;
                }
                if (($p['status'] ?? '') === 'received') {
                    $receivedRow = $p;
                }
            }
            $procureRow = $openRow ?? $receivedRow ?? end($procurements);
            $procureOpen = $openRow !== null;
            $pStatus = (string) $procureRow['status'];
            $pFacts = [
                ['Items required', (string) $procureRow['items']],
                ['Reason', (string) ($procureRow['reason'] ?? '')],
                ['Requested', self::fmt($procureRow['requested_at'] ?? null) . ' by ' . $name($procureRow['requested_by'] ?? '')],
            ];
            if (!empty($procureRow['supplier'])) {
                $pFacts[] = ['Supplier / vendor', (string) $procureRow['supplier']];
            }
            if (!empty($procureRow['ordered_at'])) {
                $pFacts[] = ['Ordered', self::fmt($procureRow['ordered_at']) . ' by ' . $name($procureRow['ordered_by'] ?? '')];
            }
            if (!empty($procureRow['expected_date'])) {
                $pFacts[] = ['Expected delivery', date('d M Y', strtotime((string) $procureRow['expected_date']))];
            }
            if (!empty($procureRow['received_at'])) {
                $pFacts[] = ['Received', self::fmt($procureRow['received_at']) . ' by ' . $name($procureRow['received_by'] ?? '')];
            }
            if (!empty($procureRow['remarks']) && $pStatus !== 'cancelled') {
                $pFacts[] = ['Remarks', (string) $procureRow['remarks']];
            }
            $pNote = '';
            if ($pStatus === 'cancelled') {
                $pNote = 'Request cancelled' . (!empty($procureRow['remarks']) ? ': ' . $procureRow['remarks'] : '.');
            }
            if (count($procurements) > 1) {
                $pNote = trim($pNote . ' ' . (count($procurements) - 1) . ' other request(s) on this ticket.');
            }
            $procure = [
                'key' => 'procure', 'title' => 'Procurement', 'icon' => 'fa-cart-shopping',
                'optional' => true,
                'done' => $pStatus === 'received',
                'skipped_always' => $pStatus === 'cancelled',
                'date' => $procureRow['received_at'] ?? null,
                'actor' => !empty($procureRow['received_by']) ? $name($procureRow['received_by']) : '',
                'facts' => array_values(array_filter($pFacts, static function ($f) { return trim((string) $f[1]) !== '' && trim((string) $f[1]) !== ' by '; })),
                'steps' => [
                    ['Requested', true, $procureRow['requested_at'] ?? null],
                    ['Ordered', in_array($pStatus, ['ordered', 'received'], true) || !empty($procureRow['ordered_at']), $procureRow['ordered_at'] ?? null],
                    ['Received', $pStatus === 'received', $procureRow['received_at'] ?? null],
                ],
                'files' => array_merge($evidence['invoice'] ?? [], $evidence['service_report'] ?? []),
                'items' => [],
                'row' => $procureRow,
                'note' => $pNote,
            ];
        }

        // ---- Work ---------------------------------------------------------------------------
        $acceptedRow = $pick(['accepted']);
        $startedRow = $pick(['in_progress']);
        $completedRow = $pick(['completed_pending_verification', 'verified'], $reopenIdx);
        $workDone = $isVendor ? $hasAssignment : ($completedRow !== null);
        $lastUpdate = $progress ? end($progress) : null;
        // Invoices belong to the procurement stage when there is one
        $invoices = $procure ? [] : array_merge($evidence['invoice'] ?? [], $evidence['service_report'] ?? []);
        $workFacts = [];
        $workNote = '';
        if ($isVendor) {
            $reason = trim((string) ($latestAssign['remarks'] ?? ''));
            $workFacts[] = ['Handled by', 'External vendor' . (!empty($t['vendor_name']) ? ' - ' . $t['vendor_name'] : '')];
            if ($reason !== '') {
                $workFacts[] = ['Reason', $reason];
            }
        } else {
            if ($acceptedRow) {
                $workFacts[] = ['Acknowledged', self::fmt($acceptedRow['changed_at'] ?? null) . ' by ' . $name($acceptedRow['changed_by'] ?? '')];
            }
            if ($startedRow) {
                $workFacts[] = ['Work started', self::fmt($startedRow['changed_at'] ?? null)];
            }
            if ($hasAssignment) {
                $workFacts[] = ['Progress', (int) ($t['progress_percent'] ?? 0) . '%'];
            }
            if ($lastUpdate) {
                $workFacts[] = ['Latest update', trim((string) $lastUpdate['work_update']) . ' (' . self::fmt($lastUpdate['created_at'] ?? null) . ')'];
            }
            if ($progress) {
                $workFacts[] = ['Updates logged', (string) count($progress)];
            }
            if ($completedRow) {
                $workFacts[] = ['Work finished', self::fmt($completedRow['changed_at'] ?? null) . ' by ' . $name($completedRow['changed_by'] ?? '')];
            }
            if ($procureOpen) {
                $workNote = 'On hold pending procurement.';
            } elseif ($status === 'waiting_support') {
                $workNote = 'Paused: waiting for a support job from another department.';
            } elseif ($status === 'on_hold') {
                $workNote = 'Ticket is on hold.';
            } elseif ($status === 'reopened') {
                $workNote = 'Reopened for rework.';
            } elseif ($hasAssignment && !$workDone && $acceptedRow === null && $startedRow === null) {
                $workNote = 'Awaiting acceptance by the assignee.';
            }
        }
        $items = [];
        foreach ($materials as $m) {
            $items[] = trim((string) $m['item_name']) . ' x ' . trim((string) $m['quantity']);
        }
        $work = [
            'key' => 'work', 'title' => $isVendor ? 'Work (external vendor)' : 'Work', 'icon' => $isVendor ? 'fa-truck-fast' : 'fa-screwdriver-wrench',
            'done' => $workDone,
            'date' => $isVendor ? $assignDate : ($completedRow['changed_at'] ?? null),
            'actor' => $isVendor ? $name($assignActor) : ($completedRow ? $name($completedRow['changed_by'] ?? '') : ''),
            'facts' => $workFacts,
            'files' => $invoices,
            'items' => $items,
            'progress' => $isVendor ? null : (int) ($t['progress_percent'] ?? 0),
            'note' => $workNote,
        ];

        // ---- Verify -------------------------------------------------------------------------
        $verifyRow = $pick(['verified'], $reopenIdx);
        $returns = 0;
        foreach ($verifications as $v) {
            if (($v['action'] ?? '') === 'return') {
                $returns++;
            }
        }
        $verifyFacts = [];
        if ($verifyRow) {
            $verifyFacts[] = ['Verified by', $name($verifyRow['changed_by'] ?? '')];
            if (trim((string) ($verifyRow['remarks'] ?? '')) !== '' && $verifyRow['remarks'] !== 'Verified') {
                $verifyFacts[] = ['Remarks', trim((string) $verifyRow['remarks'])];
            }
        }
        if (!empty($t['requester_confirmation'])) {
            $verifyFacts[] = ['Requester confirmed', $t['requester_confirmation'] === 'satisfactory' ? 'Satisfied' : 'Not satisfied'];
        }
        $verify = [
            'key' => 'verify', 'title' => 'Verify', 'icon' => 'fa-clipboard-check',
            'done' => $verifyRow !== null,
            'skipped_always' => $isVendor,
            'date' => $verifyRow['changed_at'] ?? null,
            'actor' => $verifyRow ? $name($verifyRow['changed_by'] ?? '') : '',
            'facts' => $verifyFacts,
            'files' => [], 'items' => [],
            'note' => $isVendor ? 'Not required - external vendor.' : ($returns > 0 ? 'Returned for correction ' . $returns . ' time(s).' : ''),
        ];

        // ---- Close --------------------------------------------------------------------------
        $closeRow = $pick(['closed'], $reopenIdx);
        $isClosed = ($status === 'closed');
        $closeFacts = [];
        if ($isClosed) {
            $closeFacts[] = ['Closed by', $name($t['closed_by'] ?? ($closeRow['changed_by'] ?? ''))];
            if (trim((string) ($t['closure_remarks'] ?? '')) !== '') {
                $closeFacts[] = ['Closing remarks', trim((string) $t['closure_remarks'])];
            }
            if (trim((string) ($t['no_evidence_reason'] ?? '')) !== '' && !$isVendor) {
                $closeFacts[] = ['No photo because', trim((string) $t['no_evidence_reason'])];
            }
        }
        $close = [
            'key' => 'close', 'title' => 'Close', 'icon' => 'fa-flag-checkered',
            'done' => $isClosed,
            'date' => $t['closed_at'] ?? ($closeRow['changed_at'] ?? null),
            'actor' => $isClosed ? $name($t['closed_by'] ?? ($closeRow['changed_by'] ?? '')) : '',
            'facts' => $closeFacts,
            'files' => [], 'items' => [], 'note' => '',
        ];

        $stages = [$created, $review, $assign];
        if ($procure) {
            $stages[] = $procure;
        }
        array_push($stages, $work, $verify, $close);

        // ---- Terminal outcome (rejected / cancelled) ----------------------------------------
        $terminal = null;
        if (in_array($status, ['rejected', 'cancelled'], true)) {
            $row = $pick([$status]);
            $terminal = [
                'label' => $statusLabels[$status] ?? ucfirst($status),
                'date' => $row['changed_at'] ?? ($t['updated_at'] ?? null),
                'actor' => $row ? $name($row['changed_by'] ?? '') : '',
                'remarks' => trim((string) ($row['remarks'] ?? '')),
            ];
        }

        // ---- States: done / skipped / current / pending / stopped ---------------------------
        $currentKey = null;
        foreach ($stages as $i => &$s) {
            if (!empty($s['done'])) {
                $s['state'] = 'done';
                continue;
            }
            if (!empty($s['skipped_always'])) {
                $s['state'] = 'skipped';
                continue;
            }
            if ($isClosed) {
                // Closed without passing through this stage
                $s['state'] = 'skipped';
                continue;
            }
            if ($terminal) {
                $s['state'] = $currentKey === null ? 'stopped' : 'pending';
                $currentKey = $currentKey ?? $s['key'];
                continue;
            }
            if ($currentKey === null) {
                $s['state'] = 'current';
                $currentKey = $s['key'];
            } else {
                $s['state'] = 'pending';
            }
        }
        unset($s);

        // Time taken: from the previous dated stage
        $prevTime = null;
        foreach ($stages as &$s) {
            $s['took'] = '';
            $ts = !empty($s['date']) ? strtotime((string) $s['date']) : false;
            if ($ts && $s['state'] === 'done') {
                if ($prevTime && $ts >= $prevTime) {
                    $s['took'] = self::duration($ts - $prevTime);
                }
                $prevTime = $ts;
            }
        }
        unset($s);

        $doneCount = 0;
        foreach ($stages as $s) {
            if (in_array($s['state'], ['done', 'skipped'], true)) {
                $doneCount++;
            }
        }
        $total = count($stages);

        // ---- One-line "where are we now" ----------------------------------------------------
        $summary = '';
        if ($terminal) {
            $summary = $terminal['label'];
        } elseif ($isClosed) {
            $summary = 'Closed';
        } elseif ($status === 'waiting_support') {
            $summary = 'Waiting for support from another department';
        } else {
            switch ($currentKey) {
                case 'review':
                    $summary = 'Pending review';
                    break;
                case 'assign':
                    $summary = 'Pending assignment';
                    break;
                case 'procure':
                    $pr = $procureRow['status'] ?? 'requested';
                    $summary = $pr === 'ordered'
                        ? 'Procurement ordered' . (!empty($procureRow['expected_date']) ? ' - delivery expected ' . date('d M Y', strtotime((string) $procureRow['expected_date'])) : '')
                        : 'Procurement requested';
                    break;
                case 'work':
                    if ($status === 'waiting_support') {
                        $summary = 'Waiting for support from another department';
                    } elseif ($status === 'on_hold') {
                        $summary = 'On hold';
                    } elseif ($status === 'reopened') {
                        $summary = 'Reopened';
                    } elseif ($acceptedRow === null && $startedRow === null) {
                        $summary = 'Awaiting acceptance' . ($who !== '' ? ' - ' . $who : '');
                    } else {
                        $summary = 'In progress - ' . (int) ($t['progress_percent'] ?? 0) . '%';
                    }
                    break;
                case 'verify':
                    $summary = 'Awaiting verification';
                    break;
                case 'close':
                    $summary = 'Verified - pending closure';
                    break;
            }
        }

        $start = !empty($t['created_at']) ? strtotime((string) $t['created_at']) : false;
        $endTs = $isClosed && !empty($t['closed_at']) ? strtotime((string) $t['closed_at']) : time();
        $age = $start ? self::duration(max(0, $endTs - $start)) : '';

        return [
            'stages' => $stages,
            'done' => $doneCount,
            'total' => $total,
            'percent' => (int) round($doneCount / $total * 100),
            'current' => $currentKey,
            'terminal' => $terminal,
            'summary' => $summary,
            'age' => $age,
            'age_label' => $isClosed ? 'Total time to close' : 'Open for',
            'is_vendor' => $isVendor,
        ];
    }

    public static function fmt($date): string {
        $ts = $date ? strtotime((string) $date) : false;
        return $ts ? date('d M Y, H:i', $ts) : '';
    }

    public static function duration(int $seconds): string {
        if ($seconds < 60) {
            return 'under a minute';
        }
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);
        if ($days > 0) {
            return $days . 'd' . ($hours > 0 ? ' ' . $hours . 'h' : '');
        }
        if ($hours > 0) {
            return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
        }
        return $mins . 'm';
    }
}
