<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus me-2"></i>Create Hostel</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/hostels/create">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-semibold">
                                    Hostel Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       required maxlength="100">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label fw-semibold">
                                    Address / Location <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       required maxlength="255">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label fw-semibold">
                                    Gender <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Mixed">Mixed</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <?php $initialFlatRows = 1; include BASE_PATH . '/views/hostels/partials/flats_form.php'; ?>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/hostels" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Hostel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>