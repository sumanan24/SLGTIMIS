<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Room</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/rooms/edit?id=<?php echo urlencode($room['id'] ?? ''); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hostel_id" class="form-label fw-semibold">Hostel</label>
                                <select class="form-select" id="hostel_id" name="hostel_id" onchange="loadBlocks(this.value)" disabled>
                                    <option value="">Select Hostel</option>
                                    <?php foreach ($hostels as $hostel): ?>
                                        <option value="<?php echo htmlspecialchars($hostel['id']); ?>" 
                                                <?php echo ($room['hostel_id'] ?? '') == $hostel['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($hostel['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hostel cannot be changed</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="block_id" class="form-label fw-semibold">
                                    Block <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="block_id" name="block_id" required>
                                    <option value="">Select Block</option>
                                    <?php foreach ($blocks as $block): ?>
                                        <option value="<?php echo htmlspecialchars($block['id']); ?>" 
                                                <?php echo ($room['block_id'] ?? '') == $block['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($block['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_no" class="form-label fw-semibold">
                                    Room Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="room_no" name="room_no" 
                                       value="<?php echo htmlspecialchars($room['room_no'] ?? ''); ?>" 
                                       required maxlength="50">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="room_type" class="form-label fw-semibold">
                                    Room Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="room_type" name="room_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Single" <?php echo ($room['room_type'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Double" <?php echo ($room['room_type'] ?? '') === 'Double' ? 'selected' : ''; ?>>Double</option>
                                    <option value="Triple" <?php echo ($room['room_type'] ?? '') === 'Triple' ? 'selected' : ''; ?>>Triple</option>
                                    <option value="Quad" <?php echo ($room['room_type'] ?? '') === 'Quad' ? 'selected' : ''; ?>>Quad</option>
                                    <option value="Dormitory" <?php echo ($room['room_type'] ?? '') === 'Dormitory' ? 'selected' : ''; ?>>Dormitory</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label fw-semibold">
                                    Capacity <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="capacity" name="capacity" 
                                       value="<?php echo htmlspecialchars($room['capacity'] ?? 1); ?>" 
                                       required min="1">
                                <div class="form-text">Currently occupied: <?php echo number_format($room['occupied_beds'] ?? 0); ?> beds</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($room['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($room['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" maxlength="500"><?php echo htmlspecialchars($room['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/rooms" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Room
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

