<div class="container-fluid px-3 py-3" style="max-width:560px;">
    <?php require __DIR__ . '/_nav.php'; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($scopeNoDepartment)): ?>
        <div class="alert alert-warning">Your account has no department assigned. You cannot issue stock until an administrator links your staff profile to a department.</div>
    <?php endif; ?>

    <h5 class="mb-3">Stock out</h5>
    <form method="post" action="<?php echo APP_URL; ?>/inventory/stock-out" class="card shadow-sm border-0 p-3">
        <div class="mb-3">
            <label class="form-label">Item <span class="text-danger">*</span></label>
            <select name="item_id" class="form-select" required>
                <option value="">Select item</option>
                <?php foreach ($items ?? [] as $it): ?>
                    <option value="<?php echo (int)$it['item_id']; ?>">
                        <?php echo htmlspecialchars($it['item_name']); ?> (<?php echo htmlspecialchars($it['item_code'] ?? ''); ?>) — qty <?php echo (int)($it['quantity'] ?? 0); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" name="quantity" class="form-control" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Issued to</label>
            <input type="text" name="issued_to" class="form-control" maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">Issued type</label>
            <select name="issued_type" class="form-select">
                <option value="staff">staff</option>
                <option value="student">student</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control" maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date_out" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Issue stock</button>
        <a href="<?php echo APP_URL; ?>/inventory" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>
