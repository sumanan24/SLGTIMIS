<style>
@media (max-width: 768px) {
    .dept-create-wrap.container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    .dept-create-wrap .card {
        border-radius: 0;
    }
    .dept-create-wrap .card-body {
        padding: 1rem !important;
    }
    .dept-create-wrap .d-flex.gap-2 {
        flex-direction: column;
    }
    .dept-create-wrap .d-flex.gap-2 .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="container-fluid px-4 py-3 dept-create-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Department</h5>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/departments/edit?id=<?php echo urlencode($department['department_id']); ?>">
                <div class="row g-3">
                    <div class="col-md-4 col-lg-3">
                        <label for="department_id" class="form-label fw-semibold">Department ID</label>
                        <input type="text" class="form-control" id="department_id"
                               value="<?php echo htmlspecialchars($department['department_id']); ?>"
                               disabled>
                        <div class="form-text">Department ID cannot be changed</div>
                    </div>
                    <div class="col-md-8 col-lg-9">
                        <label for="department_name" class="form-label fw-semibold">
                            Department Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="department_name" name="department_name"
                               value="<?php echo htmlspecialchars($department['department_name']); ?>"
                               maxlength="60" required>
                        <div class="form-text">Maximum 60 characters</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Department
                    </button>
                    <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
