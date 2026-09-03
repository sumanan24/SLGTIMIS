<?php $y = $year ?? []; ?>
<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Academic Year</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo APP_URL; ?>/academic-years/edit?id=<?php echo urlencode($y['academic_year'] ?? ''); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Academic year</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($y['academic_year'] ?? ''); ?>" disabled>
                                <div class="form-text">The year code cannot be changed.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="academic_year_status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="academic_year_status" name="academic_year_status" required>
                                    <option value="Active" <?php echo (($y['academic_year_status'] ?? '') === 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Completed" <?php echo (($y['academic_year_status'] ?? '') === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-md-6">
                                <label for="first_semi_start_date" class="form-label fw-semibold">1st semester start <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="first_semi_start_date" name="first_semi_start_date" required value="<?php echo htmlspecialchars($y['first_semi_start_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="first_semi_end_date" class="form-label fw-semibold">1st semester end <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="first_semi_end_date" name="first_semi_end_date" required value="<?php echo htmlspecialchars($y['first_semi_end_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="second_semi_start_date" class="form-label fw-semibold">2nd semester start <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="second_semi_start_date" name="second_semi_start_date" required value="<?php echo htmlspecialchars($y['second_semi_start_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="second_semi_end_date" class="form-label fw-semibold">2nd semester end <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="second_semi_end_date" name="second_semi_end_date" required value="<?php echo htmlspecialchars($y['second_semi_end_date'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top justify-content-end">
                            <a href="<?php echo APP_URL; ?>/academic-years" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
