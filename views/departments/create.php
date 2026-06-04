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
    .dept-create-wrap .card-header {
        padding: 0.75rem 1rem;
    }
    .dept-create-wrap .card-header h5 {
        font-size: 1rem;
    }
    .dept-create-wrap .card-body {
        padding: 1rem !important;
    }
    .dept-create-wrap .dept-create-actions {
        flex-direction: column;
    }
    .dept-create-wrap .dept-create-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="container-fluid px-4 py-3 dept-create-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Department</h5>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/departments/create">
                <div class="row g-3">
                    <div class="col-md-4 col-lg-3">
                        <label for="department_id" class="form-label fw-semibold">
                            Department ID <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="department_id" name="department_id"
                               maxlength="6" required placeholder="e.g., AUT, ICT, MEC">
                        <div class="form-text">Maximum 6 characters</div>
                    </div>
                    <div class="col-md-8 col-lg-9">
                        <label for="department_name" class="form-label fw-semibold">
                            Department Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="department_name" name="department_name"
                               maxlength="60" required placeholder="e.g., Automotive Technology">
                        <div class="form-text">Maximum 60 characters</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Department
                    </button>
                    <a href="<?php echo APP_URL; ?>/departments" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
