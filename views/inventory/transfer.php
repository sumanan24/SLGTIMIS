<div class="container-fluid px-3 py-3">
    <?php require __DIR__ . '/_nav.php'; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($scopeNoDepartment)): ?>
        <div class="alert alert-warning">Your account has no department assigned. You cannot transfer stock until an administrator links your staff profile to a department.</div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h5 class="mb-1">Transfer between departments</h5>
            <p class="text-muted small mb-0">Source quantity is reduced; destination gets a matching item row (same item code) or a new row is created.</p>
        </div>
    </div>

    <form method="post" action="<?php echo APP_URL; ?>/inventory/transfer" class="card shadow-sm border-0">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label class="form-label">Item (source) <span class="text-danger">*</span></label>
                    <select name="item_id" class="form-select" required>
                        <option value="">Select item</option>
                        <?php foreach ($items ?? [] as $it): ?>
                            <option value="<?php echo (int)$it['item_id']; ?>">
                                <?php echo htmlspecialchars($it['item_name']); ?> (<?php echo htmlspecialchars($it['item_code'] ?? ''); ?>) — <?php echo htmlspecialchars($it['department_id'] ?? ''); ?> — qty <?php echo (int)($it['quantity'] ?? 0); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label">To department <span class="text-danger">*</span></label>
                    <select name="to_department" class="form-select" required>
                        <option value="">Select…</option>
                        <?php foreach ($departments ?? [] as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['department_id']); ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control" min="1" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label">Transfer date</label>
                    <input type="date" name="transfer_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-12 col-lg-4">
                    <label class="form-label">Approved by (optional)</label>
                    <input type="text" name="approved_by" class="form-control" maxlength="255">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 px-3 px-md-4 pb-3 pb-md-4 pt-0 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Transfer</button>
            <a href="<?php echo APP_URL; ?>/inventory" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
