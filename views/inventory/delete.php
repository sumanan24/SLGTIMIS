<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-trash-alt me-2"></i>Delete Inventory Record
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!isset($inventory)): ?>
                        <div class="alert alert-danger">
                            Inventory record not found.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                You are about to delete this inventory record. This action cannot be undone.
                            </div>
                        </div>

                        <dl class="row mb-4">
                            <dt class="col-sm-4">Department</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($inventory['inventory_department_id'] ?? ''); ?></dd>

                            <dt class="col-sm-4">Item ID</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($inventory['item_id'] ?? ''); ?></dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($inventory['inventory_status'] ?? ''); ?></dd>

                            <dt class="col-sm-4">Quantity</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($inventory['inventory_quantity'] ?? ''); ?></dd>
                        </dl>

                        <form method="POST" action="<?php echo APP_URL; ?>/inventory/delete?id=<?php echo urlencode($inventory['inventory_id']); ?>">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo APP_URL; ?>/inventory" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash-alt me-1"></i>Delete
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

