<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Group</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/groups/create">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Group Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   maxlength="255" required placeholder="e.g., Group A, Batch 2024">
                            <div class="form-text">Enter a descriptive name for the group</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label fw-semibold">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department_id']); ?>">
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="course_id" class="form-label fw-semibold">
                                    Course <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                </select>
                                <div class="form-text">Select a department first</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="academic_year" class="form-label fw-semibold">
                                    Academic Year <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="academic_year" name="academic_year" required>
                                    <option value="">Select Academic Year</option>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>">
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Group
                            </button>
                            <a href="<?php echo APP_URL; ?>/groups" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');
    
    departmentSelect.addEventListener('change', function() {
        const departmentId = this.value;
        courseSelect.innerHTML = '<option value="">Select Course</option>';
        
        if (departmentId) {
            fetch('<?php echo APP_URL; ?>/groups/get-courses-by-department?department_id=' + encodeURIComponent(departmentId))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.courses) {
                        data.courses.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.course_id;
                            option.textContent = course.course_name;
                            courseSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching courses:', error);
                });
        }
    });
});
</script>

