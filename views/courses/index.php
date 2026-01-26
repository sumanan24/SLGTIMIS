<style>
/* Courses Page Button Styling */
.courses-actions .btn {
    min-width: 38px;
    height: 32px;
    padding: 0.375rem 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    border-radius: 0.375rem;
}

.courses-actions .btn-group {
    display: inline-flex;
}

.courses-actions .btn-group > .btn:not(:first-child) {
    margin-left: 0.25rem;
}

.courses-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.courses-actions .btn-outline-primary:hover {
    background-color: var(--primary-navy);
    border-color: var(--primary-navy);
    color: white;
}

.courses-actions .btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.courses-filter-btn {
    min-width: 40px;
    width: 40px;
    height: 32px;
    padding: 0.375rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.courses-clear-btn {
    height: 32px;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.courses-clear-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.25);
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.courses-header-btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.courses-header-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-book me-2"></i>Courses Management</h5>
                <?php if (isset($canCreate) && $canCreate): ?>
                <a href="<?php echo APP_URL; ?>/courses/create" class="btn btn-light courses-header-btn mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Add New Course
                </a>
                <?php endif; ?>
            </div>
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
                
                <!-- Filters Section -->
                <div class="card border mb-4 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3 fw-bold text-primary">
                            <i class="fas fa-filter me-2"></i>Filter Courses
                        </h6>
                        <form method="GET" action="<?php echo APP_URL; ?>/courses">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="search" class="form-label small fw-bold text-muted">Search</label>
                                    <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                                           placeholder="Course ID or Name">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="department_id" class="form-label small fw-bold text-muted">Department</label>
                                    <select class="form-select form-select-sm" id="department_id" name="department_id">
                                        <option value="">All Departments</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department_id']); ?>" 
                                                    <?php echo (isset($filters['department_id']) && $filters['department_id'] === $dept['department_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['department_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="nvq_level" class="form-label small fw-bold text-muted">NVQ Level</label>
                                    <select class="form-select form-select-sm" id="nvq_level" name="nvq_level">
                                        <option value="">All Levels</option>
                                        <option value="3" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '3') ? 'selected' : ''; ?>>Level 3</option>
                                        <option value="4" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '4') ? 'selected' : ''; ?>>Level 4</option>
                                        <option value="5" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '5') ? 'selected' : ''; ?>>Level 5</option>
                                        <option value="6" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === '6') ? 'selected' : ''; ?>>Level 6</option>
                                        <option value="BRI" <?php echo (isset($filters['nvq_level']) && $filters['nvq_level'] === 'BRI') ? 'selected' : ''; ?>>BRI</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/courses" class="btn btn-outline-secondary btn-sm courses-filter-btn">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            
                <?php if (!empty($courses)): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Showing <strong><?php echo count($courses); ?></strong> course(s)
                        </div>
                        <?php if (!empty($filters['search']) || !empty($filters['department_id']) || !empty($filters['nvq_level'])): ?>
                            <a href="<?php echo APP_URL; ?>/courses" class="btn btn-outline-danger btn-sm courses-clear-btn">
                                <i class="fas fa-times-circle me-1"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold">Course Name</th>
                                    <th class="fw-bold">NVQ Level</th>
                                    <th class="fw-bold text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($course['course_name']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary rounded-pill px-3">
                                                Level <?php echo htmlspecialchars($course['course_nvq_level']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end courses-actions">
                                            <?php if (isset($canEdit) && $canEdit): ?>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo APP_URL; ?>/courses/edit?id=<?php echo urlencode($course['course_id']); ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Edit Course">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/courses/delete?id=<?php echo urlencode($course['course_id']); ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this course?');"
                                                   title="Delete Course">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted small">View only</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No courses found.</p>
                        <?php if (isset($canCreate) && $canCreate): ?>
                        <a href="<?php echo APP_URL; ?>/courses/create" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create one now
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

