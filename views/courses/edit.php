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
            <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Course</h5>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php
            $versions = $versions ?? [];
            $latestVersion = $latestVersion ?? 0;
            ?>
            <div class="card border mb-4 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-2 fw-bold text-muted small">Course versions</h6>
                    <p class="text-muted small mb-2">Latest version for new enrollments: <strong><?php echo (int)$latestVersion; ?></strong> (0 = default)</p>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <form method="POST" action="<?php echo APP_URL; ?>/courses/new-version?id=<?php echo urlencode($course['course_id']); ?>" class="d-inline">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>Add version
                            </button>
                        </form>
                    </div>
                    <?php if (!empty($versions)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($versions as $v): ?>
                                <li class="list-group-item d-flex align-items-center justify-content-between py-1 px-0">
                                    <span>Version <?php echo (int)$v['version_no']; ?></span>
                                    <form method="POST" action="<?php echo APP_URL; ?>/courses/remove-version?id=<?php echo urlencode($course['course_id']); ?>&version_no=<?php echo (int)$v['version_no']; ?>" class="d-inline" onsubmit="return confirm('Remove version <?php echo (int)$v['version_no']; ?>?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No versions yet. Click &quot;Add version&quot; to create one.</p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="<?php echo APP_URL; ?>/courses/edit?id=<?php echo urlencode($course['course_id']); ?>" class="course-form">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="course_id" class="form-label fw-semibold mb-1">Course ID</label>
                        <input type="text" class="form-control" id="course_id"
                               value="<?php echo htmlspecialchars($course['course_id']); ?>"
                               disabled>
                        <div class="form-text">Course ID cannot be changed</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="department_id" class="form-label fw-semibold mb-1">
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

                    <div class="col-12 col-md-4">
                        <label for="course_nvq_level" class="form-label fw-semibold mb-1">
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

                    <div class="col-12">
                        <label for="course_name" class="form-label fw-semibold mb-1">
                            Course Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="course_name" name="course_name"
                               value="<?php echo htmlspecialchars($course['course_name']); ?>"
                               maxlength="255" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="course_ojt_duration" class="form-label fw-semibold mb-1">
                            OJT Duration (months) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="course_ojt_duration" name="course_ojt_duration"
                               value="<?php echo htmlspecialchars($course['course_ojt_duration']); ?>"
                               min="1" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="course_institute_training" class="form-label fw-semibold mb-1">
                            Institute Training (months) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="course_institute_training" name="course_institute_training"
                               value="<?php echo htmlspecialchars($course['course_institute_training']); ?>"
                               min="1" required>
                    </div>
                </div>

                <div class="course-create-actions d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
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
