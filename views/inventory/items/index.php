<div class="container-fluid px-3 py-3">
    <?php require __DIR__ . '/../_nav.php'; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($scopeNoDepartment)): ?>
        <div class="alert alert-warning">Your account has no department assigned. Inventory is hidden until an administrator links your staff profile to a department.</div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0">Inventory items</h5>
        <?php if (empty($scopeNoDepartment)): ?>
        <a href="<?php echo APP_URL; ?>/inventory/items/create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add item</a>
        <?php endif; ?>
    </div>

    <form method="get" action="<?php echo APP_URL; ?>/inventory/items" class="row g-2 align-items-end mb-3">
        <?php if (!empty($canViewAllDepartments)): ?>
        <div class="col-md-3">
            <label class="form-label small mb-0">Department</label>
            <select name="department_id" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach ($departments ?? [] as $d): ?>
                    <option value="<?php echo htmlspecialchars($d['department_id']); ?>" <?php echo ($departmentFilter ?? '') === $d['department_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-md-3">
            <label class="form-label small mb-0">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="active" <?php echo ($statusFilter ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($statusFilter ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-0">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Name, code, category">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filter</button>
        </div>
    </form>

    <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Reorder</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No items found.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['item_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['item_code'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['department_name'] ?? $row['department_id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['category'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['unit'] ?? ''); ?></td>
                            <td class="text-end"><?php echo (int)($row['quantity'] ?? 0); ?></td>
                            <td class="text-end"><?php echo (int)($row['reorder_level'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo APP_URL; ?>/inventory/items/edit?id=<?php echo (int)$row['item_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="<?php echo APP_URL; ?>/inventory/items/delete?id=<?php echo (int)$row['item_id']; ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
