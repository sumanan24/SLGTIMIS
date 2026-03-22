<div class="container-fluid px-3 py-3" style="max-width:720px;">
    <?php require __DIR__ . '/../_nav.php'; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <h5 class="mb-3">Edit item</h5>
    <form method="post" action="<?php echo APP_URL; ?>/inventory/items/edit?id=<?php echo (int)($item['item_id'] ?? 0); ?>" class="card shadow-sm border-0 p-3">
        <div class="mb-3">
            <label class="form-label">Item name <span class="text-danger">*</span></label>
            <input type="text" name="item_name" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Item code <span class="text-danger">*</span></label>
            <input type="text" name="item_code" class="form-control" required maxlength="100" value="<?php echo htmlspecialchars($item['item_code'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Department <span class="text-danger">*</span></label>
            <?php if ($departmentScope !== null): ?>
                <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($departmentScope); ?>">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($departmentScope); ?>" disabled>
            <?php else: ?>
                <select name="department_id" class="form-select" required>
                    <?php foreach ($departments ?? [] as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['department_id']); ?>" <?php echo ($item['department_id'] ?? '') === $d['department_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['department_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Unit</label>
                <input type="text" name="unit" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" min="0" value="<?php echo (int)($item['quantity'] ?? 0); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Reorder level</label>
                <input type="number" name="reorder_level" class="form-control" min="0" value="<?php echo (int)($item['reorder_level'] ?? 5); ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="active" <?php echo ($item['status'] ?? '') === 'active' ? 'selected' : ''; ?>>active</option>
                <option value="inactive" <?php echo ($item['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>inactive</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo APP_URL; ?>/inventory/items" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
