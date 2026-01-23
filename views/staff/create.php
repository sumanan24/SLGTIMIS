<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Staff</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/staff/create">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staff_id" class="form-label fw-semibold">
                                    Staff ID <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="staff_id" name="staff_id" 
                                       maxlength="64" required placeholder="e.g., STF001">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label fw-semibold">
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staff_name" class="form-label fw-semibold">
                                    Staff Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="staff_name" name="staff_name" 
                                       maxlength="50" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="staff_email" class="form-label fw-semibold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="staff_email" name="staff_email" 
                                       maxlength="254" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staff_nic" class="form-label fw-semibold">
                                    NIC <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="staff_nic" name="staff_nic" 
                                       maxlength="15" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="staff_epf" class="form-label fw-semibold">
                                    EPF Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="staff_epf" name="staff_epf" 
                                       maxlength="20" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="staff_dob" class="form-label fw-semibold">Date of Birth</label>
                                <input type="date" class="form-control" id="staff_dob" name="staff_dob" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="staff_date_of_join" class="form-label fw-semibold">Date of Join</label>
                                <input type="date" class="form-control" id="staff_date_of_join" name="staff_date_of_join" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="staff_gender" class="form-label fw-semibold">Gender</label>
                                <select class="form-select" id="staff_gender" name="staff_gender" required>
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Transgender">Transgender</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="staff_address" class="form-label fw-semibold">Address</label>
                                <textarea class="form-control" id="staff_address" name="staff_address" rows="2" maxlength="50"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="staff_pno" class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" class="form-control" id="staff_pno" name="staff_pno" 
                                       pattern="[0-9]{9,10}">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="staff_position" class="form-label fw-semibold">
                                    Position <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="staff_position" name="staff_position" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role['staff_position_type_id']); ?>">
                                            <?php echo htmlspecialchars($role['staff_position_type_name']); ?> (<?php echo htmlspecialchars($role['staff_position_type_id']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select from available staff positions</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="staff_type" class="form-label fw-semibold">Staff Type</label>
                                <select class="form-select" id="staff_type" name="staff_type" required>
                                    <option value="">Select</option>
                                    <option value="Permanent">Permanent</option>
                                    <option value="On Contract">On Contract</option>
                                    <option value="Visiting Lecturer">Visiting Lecturer</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="staff_status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="staff_status" name="staff_status">
                                    <option value="Working" selected>Working</option>
                                    <option value="Terminated">Terminated</option>
                                    <option value="Resigned">Resigned</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Staff
                            </button>
                            <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

