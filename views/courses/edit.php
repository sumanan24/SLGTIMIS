<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Course</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/courses/edit?id=<?php echo urlencode($course['course_id']); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="course_id" class="form-label fw-semibold">Course ID</label>
                                <input type="text" class="form-control" id="course_id" 
                                       value="<?php echo htmlspecialchars($course['course_id']); ?>" 
                                       disabled>
                                <div class="form-text">Course ID cannot be changed</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label fw-semibold">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department_id']); ?>" 
                                                <?php echo ($course['department_id'] === $dept['department_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?> (<?php echo htmlspecialchars($dept['department_id']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="course_name" class="form-label fw-semibold">
                                Course Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="course_name" name="course_name" 
                                   value="<?php echo htmlspecialchars($course['course_name']); ?>" 
                                   maxlength="255" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="course_nvq_level" class="form-label fw-semibold">
                                    NVQ Level <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="course_nvq_level" name="course_nvq_level" required>
                                    <option value="">Select NVQ Level</option>
                                    <option value="3" <?php echo ($course['course_nvq_level'] === '3') ? 'selected' : ''; ?>>Level 3</option>
                                    <option value="4" <?php echo ($course['course_nvq_level'] === '4') ? 'selected' : ''; ?>>Level 4</option>
                                    <option value="5" <?php echo ($course['course_nvq_level'] === '5') ? 'selected' : ''; ?>>Level 5</option>
                                    <option value="6" <?php echo ($course['course_nvq_level'] === '6') ? 'selected' : ''; ?>>Level 6</option>
                                    <option value="BRI" <?php echo ($course['course_nvq_level'] === 'BRI') ? 'selected' : ''; ?>>BRI</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="course_ojt_duration" class="form-label fw-semibold">
                                    OJT Duration (months) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="course_ojt_duration" name="course_ojt_duration" 
                                       value="<?php echo htmlspecialchars($course['course_ojt_duration']); ?>" 
                                       min="1" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="course_institute_training" class="form-label fw-semibold">
                                    Institute Training (months) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="course_institute_training" name="course_institute_training" 
                                       value="<?php echo htmlspecialchars($course['course_institute_training']); ?>" 
                                       min="1" required>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Course
                            </button>
                            <a href="<?php echo APP_URL; ?>/courses" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

