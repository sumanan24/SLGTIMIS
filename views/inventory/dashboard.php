<div class="container-fluid px-3 py-3">
    <?php require __DIR__ . '/_nav.php'; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($scopeNoDepartment)): ?>
        <div class="alert alert-warning">Your account has no department assigned. Inventory totals and activity are hidden until an administrator links your staff profile to a department.</div>
    <?php endif; ?>
    <?php if (empty($logTableReady) && !empty($logTableError)): ?>
        <div class="alert alert-warning"><?php echo htmlspecialchars($logTableError); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total items</div>
                    <div class="fs-3 fw-bold text-primary"><?php echo (int)($totalItems ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-warning">
                <div class="card-body">
                    <div class="text-muted small">Low stock (below reorder level)</div>
                    <div class="fs-3 fw-bold text-warning"><?php echo count($lowStock ?? []); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Quick actions</div>
                        <a href="<?php echo APP_URL; ?>/inventory/stock-in" class="btn btn-sm btn-primary mt-1">Stock In</a>
                        <a href="<?php echo APP_URL; ?>/inventory/stock-out" class="btn btn-sm btn-outline-secondary mt-1">Stock Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Low stock items</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr>
                                <th>Item</th><th>Code</th><th>Qty</th><th>Reorder</th><th>Dept</th>
                            </tr></thead>
                            <tbody>
                                <?php if (empty($lowStock)): ?>
                                    <tr><td colspan="5" class="text-muted text-center py-3">No low stock items.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($lowStock as $r): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['item_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($r['item_code'] ?? ''); ?></td>
                                            <td><?php echo (int)($r['quantity'] ?? 0); ?></td>
                                            <td><?php echo (int)($r['reorder_level'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($r['department_name'] ?? $r['department_id'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Recent activity</span>
                    <a href="<?php echo APP_URL; ?>/inventory/log" class="btn btn-sm btn-outline-primary">View full log</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr>
                                <th>When</th><th>Type</th><th>Item</th><th>Qty</th>
                            </tr></thead>
                            <tbody>
                                <?php if (empty($recentActivity)): ?>
                                    <tr><td colspan="4" class="text-muted text-center py-3">No log entries yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentActivity as $r): ?>
                                        <tr>
                                            <td class="text-nowrap small"><?php echo htmlspecialchars(substr($r['action_date'] ?? '', 0, 16)); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($r['action_type'] ?? ''); ?></span></td>
                                            <td><?php echo htmlspecialchars($r['item_name'] ?? ''); ?></td>
                                            <td><?php echo (int)($r['quantity'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-white fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Inventory log</span>
            <a href="<?php echo APP_URL; ?>/inventory/log" class="btn btn-sm btn-outline-primary">Filters &amp; full history</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
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
                        <?php if (empty($inventoryLogs)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No log entries yet. Stock in, out, and transfers will appear here.</td></tr>
                        <?php else: ?>
                            <?php foreach ($inventoryLogs as $r): ?>
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
    </div>
</div>
