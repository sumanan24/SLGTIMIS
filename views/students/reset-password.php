<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-key me-2"></i>Reset Password</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-info-circle me-2 fa-lg"></i>
                        <div>
                            <strong>Student:</strong> <?php echo htmlspecialchars($student['student_fullname']); ?><br>
                            <strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?>
                        </div>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/students/reset-password?id=<?php echo urlencode($student['student_id']); ?>">
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   required minlength="6" placeholder="Enter new password">
                            <div class="form-text">Minimum 6 characters. Default password is usually the student's NIC number.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required minlength="6" placeholder="Confirm new password">
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-1"></i>Reset Password
                            </button>
                            <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

