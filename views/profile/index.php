<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user me-2"></i>My Profile</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?php echo htmlspecialchars($message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($user)): ?>
                    <form method="POST" action="<?php echo APP_URL; ?>/profile/update">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="user_email" class="form-label fw-semibold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="user_email" name="user_email" 
                                       value="<?php echo htmlspecialchars($user['user_email'] ?? ''); ?>" 
                                       required>
                            </div>
                        </div>

                        <?php if (isset($staff) && !empty($staff)): ?>
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3"><i class="fas fa-id-badge me-2"></i>Staff Details</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Staff ID</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($staff['staff_id'] ?? ''); ?>"
                                       disabled>
                                <div class="form-text">Staff ID cannot be changed</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Department</label>
                                <input type="text" class="form-control"
                                       value="<?php
                                           if (isset($staffDepartment) && $staffDepartment) {
                                               echo htmlspecialchars(($staffDepartment['department_name'] ?? '') . ' (' . ($staffDepartment['department_id'] ?? '') . ')');
                                           } elseif (isset($staff['department_id'])) {
                                               echo htmlspecialchars($staff['department_id']);
                                           }
                                       ?>"
                                       disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staff_name" class="form-label fw-semibold">
                                    Staff Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="staff_name" name="staff_name"
                                       maxlength="50"
                                       value="<?php echo htmlspecialchars($staff['staff_name'] ?? ''); ?>"
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="staff_nic" class="form-label fw-semibold">
                                    NIC <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="staff_nic" name="staff_nic"
                                       maxlength="15"
                                       value="<?php echo htmlspecialchars($staff['staff_nic'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="staff_dob" class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" class="form-control" id="staff_dob" name="staff_dob"
                                       value="<?php echo htmlspecialchars($staff['staff_dob'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="staff_date_of_join" class="form-label fw-semibold">Date of Join</label>
                                <input type="date" class="form-control" id="staff_date_of_join" name="staff_date_of_join"
                                       value="<?php echo htmlspecialchars($staff['staff_date_of_join'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="staff_gender" class="form-label fw-semibold">Gender</label>
                                <select class="form-select" id="staff_gender" name="staff_gender">
                                    <option value="">Select</option>
                                    <option value="Male" <?php echo (isset($staff['staff_gender']) && $staff['staff_gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($staff['staff_gender']) && $staff['staff_gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Transgender" <?php echo (isset($staff['staff_gender']) && $staff['staff_gender'] === 'Transgender') ? 'selected' : ''; ?>>Transgender</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staff_address" class="form-label fw-semibold">Address</label>
                                <textarea class="form-control" id="staff_address" name="staff_address" rows="2" maxlength="50"><?php echo htmlspecialchars($staff['staff_address'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="staff_pno" class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" class="form-control" id="staff_pno" name="staff_pno"
                                       pattern="[0-9]{9,10}"
                                       value="<?php echo htmlspecialchars($staff['staff_pno'] ?? ''); ?>">
                            </div>
                        </div>
                        <?php endif; ?>

                        <hr class="my-4">
                        
                        <h6 class="fw-bold mb-3"><i class="fas a-lock me-2"></i>Change Password</h6>
                        <p class="text-muted small mb-3">Leave blank if you don't want to change your password.</p>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label fw-semibold">
                                    Current Password
                                </label>
                                <input type="password" class="form-control" id="current_password" name="current_password" 
                                       placeholder="Enter current password">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="new_password" class="form-label fw-semibold">
                                    New Password
                                </label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       placeholder="Enter new password" minlength="6">
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label fw-semibold">
                                    Confirm New Password
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm new password" minlength="6">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/dashboard" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

