<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Staff Role</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/staff-roles/edit?id=<?php echo urlencode($role['staff_position_type_id']); ?>">
                        <div class="mb-3">
                            <label for="staff_position_type_id" class="form-label fw-semibold">Role ID</label>
                            <input type="text" class="form-control" id="staff_position_type_id" 
                                   value="<?php echo htmlspecialchars($role['staff_position_type_id']); ?>" 
                                   disabled>
                            <div class="form-text">Role ID cannot be changed.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="staff_position_type_name" class="form-label fw-semibold">
                                Role Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="staff_position_type_name" name="staff_position_type_name" 
                                   value="<?php echo htmlspecialchars($role['staff_position_type_name']); ?>" 
                                   maxlength="64" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="staff_position" class="form-label fw-semibold">
                                Position Level <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="staff_position" name="staff_position" 
                                   value="<?php echo htmlspecialchars($role['staff_position']); ?>" 
                                   min="1" required>
                            <div class="form-text">Lower numbers indicate higher positions in the hierarchy.</div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Role
                            </button>
                            <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

