<div class="container-fluid px-3 py-3">
    <?php require __DIR__ . '/_nav.php'; ?>

    <h5 class="mb-3">Inventory log</h5>
    <?php if (!empty($scopeNoDepartment)): ?>
        <div class="alert alert-warning mb-3">Your account has no department assigned. The log is empty until an administrator links your staff profile to a department.</div>
    <?php endif; ?>

    <form method="get" action="<?php echo APP_URL; ?>/inventory/log" class="row g-2 align-items-end mb-3">
        <?php if (!empty($canViewAllDepartments)): ?>
        <div class="col-md-2">
            <label class="form-label small mb-0">Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach ($departments ?? [] as $d): ?>
                    <option value="<?php echo htmlspecialchars($d['department_id']); ?>" <?php echo ($filters['department_id'] ?? '') === $d['department_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-md-2">
            <label class="form-label small mb-0">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0">Item</label>
            <select name="item_id" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach ($allItems ?? [] as $it): ?>
                    <option value="<?php echo (int)$it['item_id']; ?>" <?php echo (string)($filters['item_id'] ?? '') === (string)$it['item_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($it['item_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0">Action</label>
            <select name="action_type" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="IN" <?php echo ($filters['action_type'] ?? '') === 'IN' ? 'selected' : ''; ?>>IN</option>
                <option value="OUT" <?php echo ($filters['action_type'] ?? '') === 'OUT' ? 'selected' : ''; ?>>OUT</option>
                <option value="TRANSFER" <?php echo ($filters['action_type'] ?? '') === 'TRANSFER' ? 'selected' : ''; ?>>TRANSFER</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100">Apply</button>
        </div>
    </form>

    <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Item</th>
                    <th>Dept</th>
                    <th class="text-end">Qty</th>
                    <th>Ref</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No records.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $r): ?>
                        <tr>
                            <td class="text-nowrap small"><?php echo htmlspecialchars($r['action_date'] ?? ''); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($r['action_type'] ?? ''); ?></span></td>
                            <td><?php echo htmlspecialchars($r['item_name'] ?? ''); ?> <?php if (!empty($r['item_code'])): ?><small class="text-muted">(<?php echo htmlspecialchars($r['item_code']); ?>)</small><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($r['department_name'] ?? $r['department_id'] ?? ''); ?></td>
                            <td class="text-end"><?php echo (int)($r['quantity'] ?? 0); ?></td>
                            <td><?php echo (int)($r['reference_id'] ?? 0); ?></td>
                            <td class="small"><?php echo htmlspecialchars($r['remarks'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
