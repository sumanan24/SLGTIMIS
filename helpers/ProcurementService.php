<?php
/**
 * Procurement workflow rules.
 *
 *   Requester -> Director -> Accountant -> Purchase Officer -> Procurement Officer
 *
 * Every step runs in one database transaction on a locked request row, re-checks the status
 * and the person's rights, writes the approval history and sends notifications.
 */
require_once BASE_PATH . '/models/ProcurementModel.php';
require_once BASE_PATH . '/helpers/ProcurementAccess.php';

class ProcurementService {
    const CATEGORIES = ['goods' => 'Goods', 'services' => 'Services', 'works' => 'Works', 'other' => 'Other'];
    const PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

    const DOC_TYPES = [
        'quotation' => 'Quotation',
        'specification' => 'Specification',
        'budget' => 'Budget Note',
        'invoice' => 'Invoice',
        'delivery_note' => 'Delivery Note',
        'receipt' => 'Receipt',
        'verification' => 'Verification',
        'supporting' => 'Supporting',
    ];

    const ACTION_LABELS = [
        'created' => 'Created',
        'updated' => 'Edited',
        'submitted' => 'Submitted',
        'resubmitted' => 'Resubmitted',
        'director_approved' => 'Director approved',
        'director_rejected' => 'Director rejected',
        'director_returned' => 'Director returned',
        'accountant_forwarded' => 'Budget verified and forwarded',
        'accountant_rejected' => 'Accountant rejected',
        'accountant_returned' => 'Accountant returned',
        'purchase_started' => 'Purchase started',
        'purchase_updated' => 'Purchase details saved',
        'purchase_completed' => 'Purchase completed',
        'verification_approved' => 'Verification approved',
        'verification_returned' => 'Verification returned',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
        'document_added' => 'Document added',
        'document_removed' => 'Document removed',
        'role_assigned' => 'Role assigned',
        'role_revoked' => 'Role removed',
    ];

    const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    const ALLOWED_MIME = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip', 'application/octet-stream', 'application/x-ole-storage', 'application/CDFV2',
    ];
    const MAX_FILE = 5242880;
    const MAX_ITEMS = 100;

    private $m;

    public function __construct(?ProcurementModel $model = null) {
        $this->m = $model ?: new ProcurementModel();
    }

    public function model(): ProcurementModel {
        return $this->m;
    }

    public function actor(int $userId): array {
        return ProcurementAccess::actor($userId, $this->m);
    }

    /* ================================================================ permissions */

    /** Both approvals are on record for the current version of the request. */
    public function gateOpen(array $r): bool {
        return ($r['director_decision'] ?? '') === 'approved' && !empty($r['director_at']) && !empty($r['director_by'])
            && ($r['accountant_decision'] ?? '') === 'approved' && !empty($r['accountant_at']) && !empty($r['accountant_by'])
            && in_array($r['status'], ProcurementModel::PURCHASE_STATUSES, true);
    }

    public function canView(array $actor, array $r): bool {
        list($sql, $params) = $this->m->visibility($actor);
        $row = $this->m->one_('SELECT 1 AS ok FROM proc_requests r WHERE r.id = ? AND ' . $sql, array_merge([(int) $r['id']], $params));
        return (bool) $row;
    }

    /** What the person may do with this request right now. */
    public function permissions(array $actor, array $r): array {
        $sid = (string) $actor['staff_id'];
        $st = (string) $r['status'];
        $own = $sid !== '' && (string) $r['requested_by'] === $sid;
        $has = function ($role) use ($actor) {
            return ProcurementAccess::has($actor, $role);
        };
        $gate = $this->gateOpen($r);
        $p = [
            'own' => $own,
            'edit' => $own && in_array($st, ['draft', 'returned'], true),
            'submit' => $own && in_array($st, ['draft', 'returned'], true),
            'cancel' => $own && in_array($st, ['draft', 'returned', 'pending_director'], true),
            'director' => $has('director') && !$own && $st === 'pending_director',
            'accountant' => $has('accountant') && !$own && $st === 'pending_accountant' && $sid !== (string) $r['director_by'],
            'purchase_start' => $has('purchase_officer') && !$own && $gate && $st === 'pending_purchase',
            'purchase_edit' => $has('purchase_officer') && !$own && $gate && $st === 'in_procurement',
            'purchase_complete' => $has('purchase_officer') && !$own && $gate && $st === 'in_procurement',
            'verify' => $has('procurement_officer') && $gate && $st === 'pending_verification'
                && $sid !== (string) $r['requested_by'] && $sid !== (string) $r['purchase_started_by'] && $sid !== (string) $r['purchase_by'],
            'close' => $has('procurement_officer') && $gate && $st === 'verified',
        ];
        $p['upload_types'] = $this->uploadTypes($actor, $r, $p);
        return $p;
    }

    public function uploadTypes(array $actor, array $r, ?array $p = null): array {
        $p = $p ?: $this->permissions($actor, $r);
        $types = [];
        if ($p['edit']) {
            $types = ['quotation', 'specification', 'supporting'];
        } elseif ($p['director']) {
            $types = ['supporting'];
        } elseif ($p['accountant']) {
            $types = ['budget', 'supporting'];
        } elseif ($p['purchase_edit']) {
            $types = ['invoice', 'delivery_note', 'receipt', 'supporting'];
        } elseif (ProcurementAccess::has($actor, 'procurement_officer') && $this->gateOpen($r) && in_array($r['status'], ['pending_verification', 'verified'], true)) {
            $types = ['verification', 'supporting'];
        }
        return $types;
    }

    /** Which role the person is acting as for a given workflow action. */
    private function roleFor(string $action): string {
        if (strpos($action, 'director') === 0) {
            return 'director';
        }
        if (strpos($action, 'accountant') === 0) {
            return 'accountant';
        }
        if (strpos($action, 'purchase') === 0) {
            return 'purchase_officer';
        }
        if (in_array($action, ['verify_approve', 'verify_return', 'close'], true)) {
            return 'procurement_officer';
        }
        return 'requester';
    }

    /** Requests that wait for this person, per role: [role => [statuses]]. */
    public function queues(array $actor): array {
        $q = [];
        if (ProcurementAccess::has($actor, 'director')) {
            $q['director'] = ['pending_director'];
        }
        if (ProcurementAccess::has($actor, 'accountant')) {
            $q['accountant'] = ['pending_accountant'];
        }
        if (ProcurementAccess::has($actor, 'purchase_officer')) {
            $q['purchase_officer'] = ['pending_purchase', 'in_procurement'];
        }
        if (ProcurementAccess::has($actor, 'procurement_officer')) {
            $q['procurement_officer'] = ['pending_verification', 'verified'];
        }
        return $q;
    }

    /* ================================================================ input validation */

    /** Clean + validate the request header and item rows. Returns [data, items]. */
    public function validate(array $in, array $actor, bool $forSubmit): array {
        $title = trim((string) ($in['title'] ?? ''));
        if (mb_strlen($title) < 3) {
            throw new InvalidArgumentException('Enter a title of at least 3 characters.');
        }
        if (mb_strlen($title) > 200) {
            throw new InvalidArgumentException('Title is too long (200 characters at most).');
        }
        $category = (string) ($in['category'] ?? 'goods');
        if (!isset(self::CATEGORIES[$category])) {
            throw new InvalidArgumentException('Choose a valid category.');
        }
        $priority = (string) ($in['priority'] ?? 'medium');
        if (!isset(self::PRIORITIES[$priority])) {
            throw new InvalidArgumentException('Choose a valid priority.');
        }
        $justification = trim((string) ($in['justification'] ?? ''));
        if ($forSubmit && mb_strlen($justification) < 10) {
            throw new InvalidArgumentException('Explain why this is needed (at least 10 characters).');
        }
        if (mb_strlen($justification) > 5000) {
            throw new InvalidArgumentException('Justification is too long.');
        }
        $required = trim((string) ($in['required_date'] ?? ''));
        if ($required !== '') {
            if (!$this->validDate($required)) {
                throw new InvalidArgumentException('Required date is not a valid date.');
            }
            if ($forSubmit && $required < date('Y-m-d')) {
                throw new InvalidArgumentException('Required date cannot be in the past.');
            }
        }
        $dept = trim((string) ($in['department_id'] ?? ''));
        if ($dept === '' || (!$actor['is_admin'] && $actor['department_id'])) {
            // Requesters always order for their own department
            $dept = (string) ($actor['department_id'] ?? $dept);
        }
        $items = [];
        $names = isset($in['item_name']) && is_array($in['item_name']) ? $in['item_name'] : [];
        $total = 0.0;
        foreach ($names as $i => $nameRaw) {
            $name = trim((string) $nameRaw);
            $spec = trim((string) ($in['item_spec'][$i] ?? ''));
            $qtyRaw = trim((string) ($in['item_qty'][$i] ?? ''));
            $unit = trim((string) ($in['item_unit'][$i] ?? ''));
            $priceRaw = trim((string) ($in['item_price'][$i] ?? ''));
            if ($name === '' && $spec === '' && $qtyRaw === '' && $priceRaw === '') {
                continue; // blank row
            }
            $line = count($items) + 1;
            if ($name === '') {
                throw new InvalidArgumentException("Item $line needs a name.");
            }
            if (mb_strlen($name) > 200 || mb_strlen($spec) > 500 || mb_strlen($unit) > 20) {
                throw new InvalidArgumentException("Item $line has a text that is too long.");
            }
            if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0 || (float) $qtyRaw > 10000000) {
                throw new InvalidArgumentException("Item $line needs a quantity above zero.");
            }
            if ($priceRaw === '') {
                $priceRaw = '0';
            }
            if (!is_numeric($priceRaw) || (float) $priceRaw < 0 || (float) $priceRaw > 1000000000) {
                throw new InvalidArgumentException("Item $line has an invalid unit price.");
            }
            $qty = round((float) $qtyRaw, 2);
            $price = round((float) $priceRaw, 2);
            $lineTotal = round($qty * $price, 2);
            $total += $lineTotal;
            $items[] = ['item_name' => $name, 'specification' => $spec, 'quantity' => $qty, 'unit' => $unit, 'unit_price' => $price, 'line_total' => $lineTotal];
            if (count($items) > self::MAX_ITEMS) {
                throw new InvalidArgumentException('A request can hold at most ' . self::MAX_ITEMS . ' items.');
            }
        }
        if ($forSubmit) {
            if (!$items) {
                throw new InvalidArgumentException('Add at least one item.');
            }
            if ($total <= 0) {
                throw new InvalidArgumentException('Enter unit prices so the estimated total is above zero.');
            }
        }
        $data = [
            'title' => $title,
            'category' => $category,
            'priority' => $priority,
            'department_id' => $dept !== '' ? $dept : null,
            'justification' => $justification,
            'required_date' => $required !== '' ? $required : null,
            'estimated_total' => round($total, 2),
        ];
        return [$data, $items];
    }

    private function validDate(string $d): bool {
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    }

    private function money($v): string {
        return number_format((float) $v, 2);
    }

    /* ================================================================ create / edit */

    /** Create a request. $submit true sends it to the Director straight away. */
    public function create(array $actor, array $in, bool $submit): int {
        if ($actor['staff_id'] === '') {
            throw new RuntimeException('Only staff members can raise procurement requests.');
        }
        list($data, $items) = $this->validate($in, $actor, $submit);
        $data['requested_by'] = $actor['staff_id'];
        $this->m->begin();
        try {
            $id = $this->m->insertRequest($data);
            $this->m->replaceItems($id, $items);
            $this->m->addHistory($id, 'created', null, 'draft', $actor['staff_id'], 'requester', null, ['items' => count($items), 'estimated_total' => $data['estimated_total']]);
            if ($submit) {
                $this->doSubmit($actor, $this->m->lockRow($id));
            }
            $this->m->commit();
        } catch (Throwable $e) {
            $this->m->rollback();
            throw $e;
        }
        return $id;
    }

    public function update(array $actor, int $id, array $in, bool $submit): void {
        $this->m->begin();
        try {
            $r = $this->m->lockRow($id);
            if (!$r) {
                throw new RuntimeException('Request not found.');
            }
            $p = $this->permissions($actor, $r);
            if (!$p['edit']) {
                throw new RuntimeException('This request can no longer be edited.');
            }
            list($data, $items) = $this->validate($in, $actor, $submit);
            $old = $this->m->items($id);
            $changes = [];
            foreach (['title', 'category', 'priority', 'department_id', 'justification', 'required_date'] as $k) {
                if ((string) $r[$k] !== (string) $data[$k]) {
                    $changes[$k] = [$r[$k], $data[$k]];
                }
            }
            if (abs((float) $r['estimated_total'] - (float) $data['estimated_total']) > 0.004) {
                $changes['estimated_total'] = [$r['estimated_total'], $data['estimated_total']];
            }
            if (count($old) !== count($items) || $this->itemsSignature($old) !== $this->itemsSignature($items)) {
                $changes['items'] = [count($old) . ' item(s)', count($items) . ' item(s)'];
            }
            $this->m->updateFields($id, $data);
            $this->m->replaceItems($id, $items);
            if ($changes) {
                $this->m->addHistory($id, 'updated', $r['status'], $r['status'], $actor['staff_id'], 'requester', null, ['changes' => $changes]);
            }
            if ($submit) {
                $this->doSubmit($actor, $this->m->lockRow($id));
            }
            $this->m->commit();
        } catch (Throwable $e) {
            $this->m->rollback();
            throw $e;
        }
    }

    private function itemsSignature(array $items): string {
        $parts = [];
        foreach ($items as $it) {
            $parts[] = implode('|', [$it['item_name'], $it['specification'], (float) $it['quantity'], $it['unit'], (float) $it['unit_price']]);
        }
        return md5(implode("\n", $parts));
    }

    /** Move draft/returned -> pending_director. Caller holds the transaction and the row lock. */
    private function doSubmit(array $actor, array $r): void {
        $id = (int) $r['id'];
        $p = $this->permissions($actor, $r);
        if (!$p['submit']) {
            throw new RuntimeException('Only the requester can submit this request.');
        }
        $items = $this->m->items($id);
        if (!$items || (float) $r['estimated_total'] <= 0) {
            throw new InvalidArgumentException('Add at least one priced item before you submit.');
        }
        if (mb_strlen(trim((string) $r['justification'])) < 10) {
            throw new InvalidArgumentException('Explain why this is needed (at least 10 characters).');
        }
        $resubmit = $r['status'] === 'returned';
        // A new version needs fresh approvals from both approvers
        $this->m->updateNullable($id, [
            'status' => 'pending_director',
            'submitted_at' => date('Y-m-d H:i:s'),
            'revision' => $resubmit ? (int) $r['revision'] + 1 : (int) $r['revision'],
            'director_decision' => null, 'director_by' => null, 'director_at' => null, 'director_remarks' => null,
            'accountant_decision' => null, 'accountant_by' => null, 'accountant_at' => null, 'accountant_remarks' => null,
            'budget_code' => null, 'allocated_amount' => null,
        ]);
        $this->m->addHistory($id, $resubmit ? 'resubmitted' : 'submitted', $r['status'], 'pending_director', $actor['staff_id'], 'requester', null, ['estimated_total' => $r['estimated_total']]);
        $label = $r['request_no'] . ' - ' . $r['title'];
        $this->notifyRole('director', $id, ($resubmit ? 'Request resubmitted' : 'Approval needed'), "$label (LKR " . $this->money($r['estimated_total']) . ') is waiting for your decision.', [$actor['staff_id']]);
    }

    /* ================================================================ workflow actions */

    /**
     * Run one workflow step. $action is one of: submit, cancel, director_approve, director_reject,
     * director_return, accountant_forward, accountant_reject, accountant_return, purchase_start,
     * purchase_complete, verify_approve, verify_return, close.
     * Returns a short success message.
     */
    public function act(array $actor, int $id, string $action, array $in): string {
        $remarks = trim((string) ($in['remarks'] ?? ''));
        if (mb_strlen($remarks) > 2000) {
            throw new InvalidArgumentException('Remarks are too long (2000 characters at most).');
        }
        $this->m->begin();
        try {
            $r = $this->m->lockRow($id);
            if (!$r) {
                throw new RuntimeException('Request not found.');
            }
            if (!$this->canView($actor, $r)) {
                throw new RuntimeException('You do not have access to this request.');
            }
            $p = $this->permissions($actor, $r);
            $msg = $this->dispatch($actor, $r, $p, $action, $remarks, $in);
            $this->m->commit();
            return $msg;
        } catch (Throwable $e) {
            $this->m->rollback();
            throw $e;
        }
    }

    private function dispatch(array $actor, array $r, array $p, string $action, string $remarks, array $in): string {
        $id = (int) $r['id'];
        $sid = (string) $actor['staff_id'];
        $now = date('Y-m-d H:i:s');
        $no = (string) $r['request_no'];
        $label = $no . ' - ' . $r['title'];
        $from = (string) $r['status'];
        $need = function ($ok, $msg) {
            if (!$ok) {
                throw new RuntimeException($msg);
            }
        };
        $needRemarks = function () use ($remarks) {
            if (mb_strlen($remarks) < 5) {
                throw new InvalidArgumentException('Enter remarks (at least 5 characters).');
            }
        };
        $role = $this->roleFor($action);

        switch ($action) {
            case 'submit':
                $this->doSubmit($actor, $r);
                return "$no was sent to the Director.";

            case 'cancel':
                $need($p['cancel'], 'This request cannot be cancelled now.');
                $this->m->updateFields($id, ['status' => 'cancelled']);
                $this->m->addHistory($id, 'cancelled', $from, 'cancelled', $sid, 'requester', $remarks);
                if ($from === 'pending_director') {
                    $this->notifyRole('director', $id, 'Request cancelled', "$label was cancelled by the requester.", [$sid]);
                }
                return "$no was cancelled.";

            case 'director_approve':
            case 'director_reject':
            case 'director_return':
                $need($p['director'], 'You cannot decide on this request now.');
                if ($action !== 'director_approve') {
                    $needRemarks();
                }
                $map = [
                    'director_approve' => ['approved', 'pending_accountant', 'director_approved'],
                    'director_reject' => ['rejected', 'rejected', 'director_rejected'],
                    'director_return' => ['returned', 'returned', 'director_returned'],
                ];
                list($decision, $to, $hist) = $map[$action];
                $this->m->updateFields($id, ['status' => $to, 'director_decision' => $decision, 'director_by' => $sid, 'director_at' => $now, 'director_remarks' => $remarks]);
                $this->m->addHistory($id, $hist, $from, $to, $sid, $role, $remarks);
                if ($action === 'director_approve') {
                    $this->notifyRole('accountant', $id, 'Budget check needed', "$label was approved by the Director. Please verify the budget.", [$sid]);
                    $this->m->notify((string) $r['requested_by'], $id, 'Director approved', "$label was approved and sent to the Accountant.");
                    return "$no approved and sent to the Accountant.";
                }
                $this->m->notify((string) $r['requested_by'], $id, $action === 'director_reject' ? 'Request rejected' : 'Request returned', "$label: $remarks");
                return $action === 'director_reject' ? "$no was rejected." : "$no was returned to the requester.";

            case 'accountant_forward':
                $need($p['accountant'], 'You cannot decide on this request now.');
                $code = trim((string) ($in['budget_code'] ?? ''));
                if ($code === '' || mb_strlen($code) > 60) {
                    throw new InvalidArgumentException('Enter the budget code (60 characters at most).');
                }
                $alloc = trim((string) ($in['allocated_amount'] ?? ''));
                if (!is_numeric($alloc) || (float) $alloc <= 0 || (float) $alloc > 100000000000) {
                    throw new InvalidArgumentException('Enter the amount allocated from the budget.');
                }
                $alloc = round((float) $alloc, 2);
                if ($alloc + 0.004 < (float) $r['estimated_total']) {
                    throw new InvalidArgumentException('The allocated amount (' . $this->money($alloc) . ') is below the estimated total (' . $this->money($r['estimated_total']) . '). Return or reject the request instead.');
                }
                $this->m->updateFields($id, [
                    'status' => 'pending_purchase', 'accountant_decision' => 'approved', 'accountant_by' => $sid, 'accountant_at' => $now,
                    'accountant_remarks' => $remarks, 'budget_code' => $code, 'allocated_amount' => $alloc,
                ]);
                $this->m->addHistory($id, 'accountant_forwarded', $from, 'pending_purchase', $sid, $role, $remarks, ['budget_code' => $code, 'allocated_amount' => $alloc]);
                $this->notifyRole('purchase_officer', $id, 'Purchase needed', "$label is approved and funded (budget $code). Please start the purchase.", [$sid]);
                $this->m->notify((string) $r['requested_by'], $id, 'Budget verified', "$label was funded and forwarded to the Purchase Officer.");
                if (!empty($r['director_by'])) {
                    $this->m->notify((string) $r['director_by'], $id, 'Budget verified', "$label was funded and forwarded for purchase.");
                }
                return "$no forwarded to the Purchase Officer.";

            case 'accountant_reject':
            case 'accountant_return':
                $need($p['accountant'], 'You cannot decide on this request now.');
                $needRemarks();
                $reject = $action === 'accountant_reject';
                $to = $reject ? 'rejected' : 'returned';
                $this->m->updateFields($id, ['status' => $to, 'accountant_decision' => $to, 'accountant_by' => $sid, 'accountant_at' => $now, 'accountant_remarks' => $remarks]);
                $this->m->addHistory($id, $reject ? 'accountant_rejected' : 'accountant_returned', $from, $to, $sid, $role, $remarks);
                $this->m->notify((string) $r['requested_by'], $id, $reject ? 'Request rejected' : 'Request returned', "$label: $remarks");
                if (!empty($r['director_by'])) {
                    $this->m->notify((string) $r['director_by'], $id, $reject ? 'Request rejected by Accountant' : 'Request returned by Accountant', "$label: $remarks");
                }
                return $reject ? "$no was rejected." : "$no was returned to the requester.";

            case 'purchase_start':
                $need($p['purchase_start'], 'You cannot start this purchase now.');
                $this->m->updateFields($id, ['status' => 'in_procurement', 'purchase_started_by' => $sid, 'purchase_started_at' => $now]);
                $this->m->addHistory($id, 'purchase_started', $from, 'in_procurement', $sid, $role, $remarks);
                $this->m->notify((string) $r['requested_by'], $id, 'Purchase started', "$label is now being purchased.");
                return "Purchase of $no started.";

            case 'purchase_complete':
                $need($p['purchase_complete'], 'You cannot complete this purchase now.');
                $d = $this->validatePurchase($in, $r, true);
                if ($this->m->countDocuments($id, 'invoice') < 1) {
                    throw new InvalidArgumentException('Upload at least one invoice before you complete the purchase.');
                }
                $this->m->updateFields($id, $d + ['status' => 'pending_verification', 'purchase_by' => $sid, 'purchase_completed_at' => $now]);
                $this->m->addHistory($id, 'purchase_completed', $from, 'pending_verification', $sid, $role, $remarks, ['supplier' => $d['supplier_name'], 'actual_total' => $d['actual_total'], 'invoice_number' => $d['invoice_number']]);
                $this->notifyRole('procurement_officer', $id, 'Verification needed', "$label was purchased. Please verify it.", [$sid]);
                $this->m->notify((string) $r['requested_by'], $id, 'Purchase completed', "$label was purchased and is waiting for verification.");
                return "$no sent to the Procurement Officer for verification.";

            case 'verify_approve':
                $need($p['verify'], 'You cannot verify this request.');
                $this->m->updateFields($id, ['status' => 'verified', 'verified_by' => $sid, 'verified_at' => $now, 'verification_remarks' => $remarks]);
                $this->m->addHistory($id, 'verification_approved', $from, 'verified', $sid, $role, $remarks);
                $this->m->notify((string) $r['requested_by'], $id, 'Purchase verified', "$label was verified.");
                return "$no verified. It can now be closed.";

            case 'verify_return':
                $need($p['verify'], 'You cannot verify this request.');
                $needRemarks();
                $this->m->updateNullable($id, ['status' => 'in_procurement', 'verification_remarks' => $remarks, 'verified_by' => null, 'verified_at' => null, 'purchase_by' => null, 'purchase_completed_at' => null]);
                $this->m->addHistory($id, 'verification_returned', $from, 'in_procurement', $sid, $role, $remarks);
                foreach ($this->m->staffIdsForRole('purchase_officer', ProcurementAccess::POSITION_CODES['purchase_officer']) as $pid) {
                    if ($pid !== $sid) {
                        $this->m->notify($pid, $id, 'Purchase returned', "$label: $remarks");
                    }
                }
                return "$no was returned to the Purchase Officer.";

            case 'close':
                $need($p['close'], 'You cannot close this request.');
                $this->m->updateFields($id, ['status' => 'closed', 'closed_by' => $sid, 'closed_at' => $now, 'closing_remarks' => $remarks]);
                $this->m->addHistory($id, 'closed', $from, 'closed', $sid, $role, $remarks);
                foreach (array_unique(array_filter([(string) $r['requested_by'], (string) $r['director_by'], (string) $r['accountant_by'], (string) $r['purchase_by']])) as $pid) {
                    if ($pid !== $sid) {
                        $this->m->notify($pid, $id, 'Request closed', "$label is complete and closed.");
                    }
                }
                return "$no closed.";
        }
        throw new InvalidArgumentException('Unknown action.');
    }

    /* ================================================================ purchase details */

    private function validatePurchase(array $in, array $r, bool $complete): array {
        $text = function ($key, $max, $label, $required) use ($in) {
            $v = trim((string) ($in[$key] ?? ''));
            if ($required && $v === '') {
                throw new InvalidArgumentException("Enter the $label.");
            }
            if (mb_strlen($v) > $max) {
                throw new InvalidArgumentException("$label is too long ($max characters at most).");
            }
            return $v;
        };
        $date = function ($key, $label, $required) use ($in) {
            $v = trim((string) ($in[$key] ?? ''));
            if ($v === '') {
                if ($required) {
                    throw new InvalidArgumentException("Enter the $label.");
                }
                return null;
            }
            if (!$this->validDate($v)) {
                throw new InvalidArgumentException(ucfirst($label) . ' is not a valid date.');
            }
            if ($v > date('Y-m-d', strtotime('+1 day'))) {
                throw new InvalidArgumentException(ucfirst($label) . ' cannot be in the future.');
            }
            return $v;
        };
        $supplier = $text('supplier_name', 160, 'supplier name', $complete);
        $contact = $text('supplier_contact', 160, 'supplier contact', false);
        $po = $text('po_number', 60, 'purchase order number', false);
        $invNo = $text('invoice_number', 60, 'invoice number', $complete);
        $remarks = $text('purchase_remarks', 2000, 'purchase remarks', false);
        $invDate = $date('invoice_date', 'invoice date', false);
        $purDate = $date('purchase_date', 'purchase date', $complete);
        $delDate = $date('delivery_date', 'delivery date', false);
        $actualRaw = trim((string) ($in['actual_total'] ?? ''));
        if ($actualRaw === '') {
            if ($complete) {
                throw new InvalidArgumentException('Enter the actual total paid.');
            }
            $actual = null;
        } else {
            if (!is_numeric($actualRaw) || (float) $actualRaw < 0 || (float) $actualRaw > 100000000000) {
                throw new InvalidArgumentException('The actual total is not a valid amount.');
            }
            $actual = round((float) $actualRaw, 2);
            if ($r['allocated_amount'] !== null && $actual > (float) $r['allocated_amount'] + 0.004) {
                throw new InvalidArgumentException('The actual total (' . $this->money($actual) . ') is more than the budget allocated (' . $this->money($r['allocated_amount']) . '). Ask the Accountant to approve more budget first.');
            }
        }
        return [
            'supplier_name' => $supplier, 'supplier_contact' => $contact, 'po_number' => $po, 'invoice_number' => $invNo,
            'invoice_date' => $invDate, 'purchase_date' => $purDate, 'delivery_date' => $delDate,
            'actual_total' => $actual, 'purchase_remarks' => $remarks,
        ];
    }

    public function savePurchase(array $actor, int $id, array $in): void {
        $this->m->begin();
        try {
            $r = $this->m->lockRow($id);
            if (!$r) {
                throw new RuntimeException('Request not found.');
            }
            $p = $this->permissions($actor, $r);
            if (!$p['purchase_edit']) {
                throw new RuntimeException('Purchase details can only be changed while the request is in procurement.');
            }
            $d = $this->validatePurchase($in, $r, false);
            $changes = [];
            foreach ($d as $k => $v) {
                if ((string) $r[$k] !== (string) $v) {
                    $changes[$k] = [$r[$k], $v];
                }
            }
            $this->m->updateNullable($id, $d);
            if ($changes) {
                $this->m->addHistory($id, 'purchase_updated', $r['status'], $r['status'], $actor['staff_id'], 'purchase_officer', null, ['changes' => $changes]);
            }
            $this->m->commit();
        } catch (Throwable $e) {
            $this->m->rollback();
            throw $e;
        }
    }

    /* ================================================================ documents */

    private function uploadDir(int $requestId): string {
        $dir = BASE_PATH . '/uploads/procurement/' . $requestId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ht = BASE_PATH . '/uploads/procurement/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
        }
        return $dir;
    }

    public function upload(array $actor, int $id, string $docType, array $file): array {
        $r = $this->m->get($id);
        if (!$r) {
            throw new RuntimeException('Request not found.');
        }
        $p = $this->permissions($actor, $r);
        if (!in_array($docType, $p['upload_types'], true)) {
            throw new RuntimeException('You cannot add this type of document to the request now.');
        }
        if (empty($file['tmp_name']) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            throw new RuntimeException($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE ? 'The file is too large (5 MB at most).' : 'Choose a file to upload.');
        }
        $size = (int) $file['size'];
        if ($size <= 0 || $size > self::MAX_FILE) {
            throw new RuntimeException('The file is too large (5 MB at most).');
        }
        $original = trim(preg_replace('/[^\w.\- ()]+/u', '_', basename((string) $file['name'])));
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new RuntimeException('Allowed file types: ' . implode(', ', self::ALLOWED_EXT) . '.');
        }
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $detected = $fi ? finfo_file($fi, $file['tmp_name']) : false;
            if ($fi) {
                finfo_close($fi);
            }
            if ($detected) {
                $mime = $detected;
            }
        }
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new RuntimeException('This file content is not allowed.');
        }
        $dir = $this->uploadDir($id);
        $stored = bin2hex(random_bytes(12)) . '.' . $ext;
        $target = $dir . '/' . $stored;
        $moved = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $target) : (PHP_SAPI === 'cli' && @rename($file['tmp_name'], $target));
        if (!$moved) {
            throw new RuntimeException('The file could not be saved.');
        }
        $role = $this->uploaderRole($p);
        try {
            $this->m->begin();
            $docId = $this->m->addDocument([
                'request_id' => $id, 'doc_type' => $docType, 'original_name' => mb_substr($original, 0, 255), 'stored_name' => $stored,
                'mime_type' => $mime, 'file_size' => $size, 'uploaded_by' => $actor['staff_id'], 'uploaded_role' => $role,
            ]);
            $this->m->addHistory($id, 'document_added', $r['status'], $r['status'], $actor['staff_id'], $role, null, ['document_id' => $docId, 'type' => $docType, 'name' => $original]);
            $this->m->commit();
        } catch (Throwable $e) {
            $this->m->rollback();
            @unlink($target);
            throw $e;
        }
        return ['id' => $docId, 'name' => $original, 'type' => $docType, 'size' => $size];
    }

    private function uploaderRole(array $p): string {
        if ($p['edit']) {
            return 'requester';
        }
        if ($p['director']) {
            return 'director';
        }
        if ($p['accountant']) {
            return 'accountant';
        }
        if ($p['purchase_edit']) {
            return 'purchase_officer';
        }
        return 'procurement_officer';
    }

    public function removeDocument(array $actor, int $docId): void {
        $doc = $this->m->document($docId);
        if (!$doc || (int) $doc['is_deleted'] === 1) {
            throw new RuntimeException('Document not found.');
        }
        $this->m->begin();
        try {
            $r = $this->m->lockRow((int) $doc['request_id']);
            $p = $this->permissions($actor, $r);
            if ((string) $doc['uploaded_by'] !== (string) $actor['staff_id'] || !in_array($doc['doc_type'], $p['upload_types'], true)) {
                throw new RuntimeException('You cannot remove this document.');
            }
            // The file stays on disk (audit); it is only hidden
            $this->m->softDeleteDocument($docId, $actor['staff_id']);
            $this->m->addHistory((int) $r['id'], 'document_removed', $r['status'], $r['status'], $actor['staff_id'], $doc['uploaded_role'], null, ['document_id' => $docId, 'name' => $doc['original_name'], 'type' => $doc['doc_type']]);
            $this->m->commit();
        } catch (Throwable $e) {
            $this->m->rollback();
            throw $e;
        }
    }

    /** Absolute file path of a stored document, or null when missing / outside the upload folder. */
    public function documentPath(array $doc): ?string {
        $base = realpath(BASE_PATH . '/uploads/procurement');
        $path = realpath(BASE_PATH . '/uploads/procurement/' . (int) $doc['request_id'] . '/' . basename((string) $doc['stored_name']));
        if ($base === false || $path === false || strpos($path, $base) !== 0 || !is_file($path)) {
            return null;
        }
        return $path;
    }

    /* ================================================================ roles (admin) */

    public function assignRole(array $actor, string $staffId, string $role): void {
        if (empty($actor['is_admin'])) {
            throw new RuntimeException('Only a system administrator can assign procurement roles.');
        }
        if (!in_array($role, ProcurementAccess::ASSIGNABLE, true)) {
            throw new InvalidArgumentException('Choose a valid role.');
        }
        if ($staffId === '' || !$this->m->one_('SELECT staff_id FROM staff WHERE staff_id = ?', [$staffId])) {
            throw new InvalidArgumentException('Choose a staff member.');
        }
        if ($this->m->assignRole($staffId, $role, $actor['staff_id'])) {
            $this->m->addHistory(null, 'role_assigned', null, null, $actor['staff_id'], 'admin', null, ['staff_id' => $staffId, 'role' => $role]);
            $this->m->notify($staffId, null, 'Procurement role', 'You are now a ' . ProcurementAccess::label($role) . ' in the Procurement module.');
        }
    }

    public function revokeRole(array $actor, int $assignmentId): void {
        if (empty($actor['is_admin'])) {
            throw new RuntimeException('Only a system administrator can remove procurement roles.');
        }
        $row = $this->m->revokeRole($assignmentId);
        if ($row) {
            $this->m->addHistory(null, 'role_revoked', null, null, $actor['staff_id'], 'admin', null, ['staff_id' => $row['staff_id'], 'role' => $row['role']]);
        }
    }

    /* ================================================================ notifications */

    private function notifyRole(string $role, int $requestId, string $title, string $message, array $exclude = []): void {
        $ids = $this->m->staffIdsForRole($role, ProcurementAccess::POSITION_CODES[$role] ?? []);
        foreach ($ids as $sid) {
            if (!in_array($sid, $exclude, true)) {
                $this->m->notify($sid, $requestId, $title, $message);
            }
        }
    }

    /* ================================================================ view helpers */

    /** Position of the request on the five-stage path, for the progress bar. */
    public function stages(array $r): array {
        $st = (string) $r['status'];
        $stop = in_array($st, ['rejected', 'cancelled'], true);
        $order = [
            ['key' => 'request', 'label' => 'Request'],
            ['key' => 'director', 'label' => 'Director'],
            ['key' => 'accountant', 'label' => 'Accountant'],
            ['key' => 'purchase', 'label' => 'Purchase'],
            ['key' => 'verification', 'label' => 'Verification'],
            ['key' => 'closed', 'label' => 'Closed'],
        ];
        $current = [
            'draft' => 0, 'returned' => 0, 'pending_director' => 1, 'pending_accountant' => 2, 'pending_purchase' => 3,
            'in_procurement' => 3, 'pending_verification' => 4, 'verified' => 4, 'closed' => 5,
        ];
        $idx = $current[$st] ?? ($st === 'rejected' ? (($r['accountant_decision'] ?? '') === 'rejected' ? 2 : 1) : 0);
        foreach ($order as $i => &$s) {
            if ($st === 'closed') {
                $s['state'] = 'done';
            } elseif ($stop && $i === $idx) {
                $s['state'] = 'stopped';
            } elseif ($i < $idx) {
                $s['state'] = 'done';
            } elseif ($i === $idx) {
                $s['state'] = $st === 'verified' && $i === 4 ? 'done' : 'current';
            } else {
                $s['state'] = 'todo';
            }
        }
        unset($s);
        return $order;
    }
}
