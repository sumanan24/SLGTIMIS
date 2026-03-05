<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-boxes me-2"></i>Inventory
                    </h5>
                    <a href="<?php echo APP_URL; ?>/inventory/create" class="btn btn-light btn-sm">
                        <i class="fas fa-plus-circle me-1"></i>Add Inventory
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?php echo htmlspecialchars($message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <?php if (!empty($departmentId)): ?>
                            <span class="badge bg-info text-dark">
                                Showing inventory for your department: <?php echo htmlspecialchars($departmentId); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">
                                Showing inventory for all departments
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Department</th>
                                    <th scope="col">Item ID</th>
                                    <th scope="col">Item Code</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventory)): ?>
                                    <?php foreach ($inventory as $row): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                    $deptLabel = $row['inventory_department_id'] ?? '';
                                                    if (!empty($row['department_name'] ?? '')) {
                                                        $deptLabel = $row['department_name'] . ' (' . $row['inventory_department_id'] . ')';
                                                    }
                                                    echo htmlspecialchars($deptLabel);
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['item_id'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['item_code'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['inventory_item_description'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['inventory_status'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($row['inventory_quantity'] ?? ''); ?></td>
                                            <td class="text-end">
                                                <a href="<?php echo APP_URL; ?>/inventory/delete?id=<?php echo urlencode($row['inventory_id']); ?>" 
                                                   class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash-alt me-1"></i>Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No inventory records found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

