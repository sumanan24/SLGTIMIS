<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Group</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/groups/edit?id=<?php echo urlencode($group['id']); ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Group Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($group['name']); ?>" 
                                   maxlength="255" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Department</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($group['department_name'] ?? 'N/A'); ?>" 
                                       disabled>
                                <div class="form-text">Department cannot be changed</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Course</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($group['course_name'] ?? 'N/A'); ?>" 
                                       disabled>
                                <div class="form-text">Course cannot be changed</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Academic Year</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($group['academic_year'] ?? 'N/A'); ?>" 
                                       disabled>
                                <div class="form-text">Academic year cannot be changed</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo ($group['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($group['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Group
                            </button>
                            <a href="<?php echo APP_URL; ?>/groups/show?id=<?php echo urlencode($group['id']); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

