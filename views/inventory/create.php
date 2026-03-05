<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-plus-circle me-2"></i>Add Inventory Entry
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo APP_URL; ?>/inventory/create">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control"
                                   value="<?php echo htmlspecialchars($departmentId ?? ''); ?>"
                                   disabled>
                            <div class="form-text">
                                Inventory will be recorded under your department only.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="item_id" class="form-label fw-semibold">
                                Item <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="item_id" name="item_id" required>
                                <option value="">Select Item</option>
                                <?php if (isset($items) && !empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item['item_id']); ?>">
                                            <?php echo htmlspecialchars($item['item_id']); ?> - 
                                            <?php echo htmlspecialchars($item['inventory_item_description']); ?> 
                                            (<?php echo htmlspecialchars($item['item_code']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="inventory_status" class="form-label fw-semibold">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="inventory_status" name="inventory_status"
                                       maxlength="50" required placeholder="e.g., Working, Repair, Scrap">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="inventory_quantity" class="form-label fw-semibold">
                                    Quantity <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="inventory_quantity" name="inventory_quantity"
                                       min="1" max="9999" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Inventory
                            </button>
                            <a href="<?php echo APP_URL; ?>/inventory" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

