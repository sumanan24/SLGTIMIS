<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Allocation</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <strong>Student:</strong> <?php echo htmlspecialchars($allocation['student_fullname'] ?? 'N/A'); ?> 
                        (<?php echo htmlspecialchars($allocation['student_id'] ?? 'N/A'); ?>)
                    </div>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/room-allocations/edit?id=<?php echo urlencode($allocation['id'] ?? ''); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hostel_id" class="form-label fw-semibold">Hostel</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($allocation['hostel_name'] ?? 'N/A'); ?>" 
                                       disabled>
                                <div class="form-text">Hostel cannot be changed</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($allocation['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($allocation['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="room_id" class="form-label fw-semibold">
                                Room <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="room_id" name="room_id" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room['id']); ?>" 
                                            <?php echo ($allocation['room_id'] ?? '') == $room['id'] ? 'selected' : ''; ?>
                                            data-available="<?php echo $room['available_beds'] ?? 0; ?>">
                                        <?php echo htmlspecialchars($room['room_no'] ?? 'N/A'); ?> - 
                                        <?php echo htmlspecialchars($room['block_name'] ?? 'N/A'); ?> 
                                        (Available: <?php echo $room['available_beds'] ?? 0; ?> beds)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Current: <?php echo htmlspecialchars($allocation['room_no'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($allocation['block_name'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/room-allocations" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Allocation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

