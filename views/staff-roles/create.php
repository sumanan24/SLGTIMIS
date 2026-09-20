<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff-roles.css?v=<?php echo time(); ?>">

<div class="container-fluid px-0 sr-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold">
                <i class="fas fa-plus-circle me-2"></i>Create Staff Role
            </h5>
            <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-sm btn-light">Back</a>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <p class="sr-create-note">Lower level numbers are higher in the hierarchy. Role ID is stored in uppercase.</p>

            <form method="POST" action="<?php echo APP_URL; ?>/staff-roles/create">
                <div class="sr-create-grid">
                    <div>
                        <label class="form-label" for="staff_position_type_id">Role ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="staff_position_type_id"
                               name="staff_position_type_id" maxlength="11" required placeholder="HOD">
                        <div class="form-text">Max 11 characters</div>
                    </div>
                    <div>
                        <label class="form-label" for="staff_position_type_name">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="staff_position_type_name"
                               name="staff_position_type_name" maxlength="64" required placeholder="Head of Department">
                        <div class="form-text">Max 64 characters</div>
                    </div>
                    <div>
                        <label class="form-label" for="staff_position">Level <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="staff_position" name="staff_position"
                               min="1" required placeholder="1">
                        <div class="form-text">1 = highest</div>
                    </div>
                </div>
                <div class="sr-form-actions">
                    <a href="<?php echo APP_URL; ?>/staff-roles" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
