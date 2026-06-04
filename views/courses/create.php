<style>
.course-create-wrap .course-form .form-control,
.course-create-wrap .course-form .form-select {
    width: 100%;
}

@media (max-width: 768px) {
    .course-create-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .course-create-wrap .card {
        border-radius: 0;
    }
    .course-create-wrap .card-header {
        padding: 0.75rem 1rem;
    }
    .course-create-wrap .card-header h5 {
        font-size: 1rem;
    }
    .course-create-wrap .card-body {
        padding: 1rem !important;
    }
    .course-create-wrap .course-create-actions {
        flex-direction: column;
    }
    .course-create-wrap .course-create-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="container-fluid px-4 py-3 course-create-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Course</h5>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/courses/create" class="course-form">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="course_id" class="form-label fw-semibold mb-1">
                            Course ID <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="course_id" name="course_id"
                               maxlength="11" required placeholder="e.g., 4AT, 5IT">
                        <div class="form-text">Maximum 11 characters</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="department_id" class="form-label fw-semibold mb-1">
                            Department <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department_id']); ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?> (<?php echo htmlspecialchars($dept['department_id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="course_nvq_level" class="form-label fw-semibold mb-1">
                            NVQ Level <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="course_nvq_level" name="course_nvq_level" required>
                            <option value="">Select NVQ Level</option>
                            <option value="3">Level 3</option>
                            <option value="4">Level 4</option>
                            <option value="5">Level 5</option>
                            <option value="6">Level 6</option>
                            <option value="BRI">BRI</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="course_name" class="form-label fw-semibold mb-1">
                            Course Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="course_name" name="course_name"
                               maxlength="255" required placeholder="e.g., Technician in Automotive Technology">
                        <div class="form-text">Maximum 255 characters</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="course_ojt_duration" class="form-label fw-semibold mb-1">
                            OJT Duration (months) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="course_ojt_duration" name="course_ojt_duration"
                               min="1" required placeholder="e.g., 6">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="course_institute_training" class="form-label fw-semibold mb-1">
                            Institute Training (months) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="course_institute_training" name="course_institute_training"
                               min="1" required placeholder="e.g., 12">
                    </div>
                </div>

                <div class="course-create-actions d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Course
                    </button>
                    <a href="<?php echo APP_URL; ?>/courses" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
