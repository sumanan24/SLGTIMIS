<style>
    /* Mobile Responsive Styles for Profile Edit */
    @media (max-width: 768px) {
        .profile-edit-container {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        
        .profile-edit-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.75rem;
        }
        
        .profile-edit-header h5 {
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        .profile-edit-header .btn {
            width: 100%;
            justify-content: center;
        }
        
        .nav-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .nav-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .nav-tabs .nav-item {
            flex-shrink: 0;
        }
        
        .nav-tabs .nav-link {
            white-space: nowrap;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
        
        .nav-tabs .nav-link i {
            margin-right: 0.25rem;
        }
        
        .form-label {
            font-size: 0.875rem;
        }
        
        .form-control, .form-select {
            font-size: 0.875rem;
        }
        
        .alert {
            font-size: 0.875rem;
            padding: 0.75rem;
        }
        
        .btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
        
        .card-body {
            padding: 1rem !important;
        }
        
        .d-flex.gap-2 {
            flex-direction: column;
            gap: 0.5rem !important;
        }
        
        .d-flex.gap-2 .btn {
            width: 100%;
        }
    }
    
    @media (max-width: 576px) {
        .profile-edit-container {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
        }
        
        .profile-edit-header h5 {
            font-size: 0.9rem;
        }
        
        .profile-edit-header .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.75rem;
        }
        
        .nav-tabs .nav-link {
            font-size: 0.8rem;
            padding: 0.4rem 0.5rem;
        }
        
        .nav-tabs .nav-link i {
            display: none;
        }
        
        .card-header {
            padding: 0.75rem !important;
        }
        
        .card-body {
            padding: 0.75rem !important;
        }
        
        .form-label {
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .form-control, .form-select {
            font-size: 0.8rem;
            padding: 0.4rem 0.5rem;
        }
        
        .form-text {
            font-size: 0.75rem;
        }
        
        .alert {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        
        .alert i {
            font-size: 0.875rem;
        }
        
        .btn {
            font-size: 0.8rem;
            padding: 0.4rem 0.75rem;
        }
        
        .btn i {
            font-size: 0.75rem;
        }
        
        h6.fw-bold {
            font-size: 0.9rem;
        }
        
        hr.my-4 {
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
        }
    }
    
    /* Ensure all columns stack on mobile */
    @media (max-width: 768px) {
        .row [class*="col-md-"] {
            margin-bottom: 0.75rem;
        }
    }
    
    /* Validation Styles */
    .form-control:required:invalid,
    .form-select:required:invalid {
        border-color: #dc3545;
    }
    
    .form-control:required:invalid:focus,
    .form-select:required:invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6 .4.4.4-.4m0 4.8-.4-.4-.4.4'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        padding-right: calc(1.5em + 0.75rem);
    }
    
    .form-control.is-valid,
    .form-select.is-valid {
        border-color: #198754;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.98-.98-.98-.98-.98.98.98.98zm1.4-2.84L4.5 5.5l3.2-3.2L6.5 1.2 4.5 3.2 2.3 1l-1.2 1.2z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        padding-right: calc(1.5em + 0.75rem);
    }
    
    .form-control.is-valid:focus,
    .form-select.is-valid:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #dc3545;
    }
    
    .form-label .text-danger {
        color: #dc3545 !important;
    }
    
    .was-validated .form-control:invalid,
    .was-validated .form-select:invalid {
        border-color: #dc3545;
    }
    
    .was-validated .form-control:valid,
    .was-validated .form-select:valid {
        border-color: #198754;
    }
</style>

<div class="container-fluid px-4 py-3 profile-edit-container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm border-0 student-card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center profile-edit-header">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit My Profile</h5>
                        <a href="<?php echo APP_URL; ?>/student/profile" class="btn btn-light btn-sm">
                            <i class="fas fa-eye me-1"></i><span class="d-none d-sm-inline">View Profile</span><span class="d-sm-none">View</span>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?php echo htmlspecialchars($message); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabs Navigation -->
                    <?php 
                    $activeTab = $activeTab ?? 'personal';
                    ?>
                    <ul class="nav nav-tabs mb-4" id="studentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'personal' ? 'active' : ''; ?>" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                                <i class="fas fa-user me-1"></i>Personal Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'bank' ? 'active' : ''; ?>" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab">
                                <i class="fas fa-university me-1"></i>Bank Details
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="studentTabsContent">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'personal' ? 'show active' : ''; ?>" id="personal" role="tabpanel">
                            <form method="POST" action="<?php echo APP_URL; ?>/student/profile/edit" id="personalForm" novalidate>
                                <input type="hidden" name="update_section" value="personal">
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Note:</strong> You can edit your personal information here. Profile photos and enrollment details cannot be changed by students.
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_id" class="form-label fw-semibold">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" 
                                               value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                               disabled>
                                        <div class="form-text">Student ID cannot be changed</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="student_title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                                        <select class="form-select" id="student_title" name="student_title" required>
                                            <option value="">Select</option>
                                            <option value="Mr." <?php echo ($student['student_title'] ?? '') === 'Mr.' ? 'selected' : ''; ?>>Mr.</option>
                                            <option value="Mrs." <?php echo ($student['student_title'] ?? '') === 'Mrs.' ? 'selected' : ''; ?>>Mrs.</option>
                                            <option value="Miss" <?php echo ($student['student_title'] ?? '') === 'Miss' ? 'selected' : ''; ?>>Miss</option>
                                            <option value="Ms." <?php echo ($student['student_title'] ?? '') === 'Ms.' ? 'selected' : ''; ?>>Ms.</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a title.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="student_gender" class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                                        <select class="form-select" id="student_gender" name="student_gender" required>
                                            <option value="">Select</option>
                                            <option value="Male" <?php echo ($student['student_gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($student['student_gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                        <div class="invalid-feedback">Please select gender.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                        
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_fullname" class="form-label fw-semibold">
                                            Full Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="student_fullname" name="student_fullname" 
                                               value="<?php echo htmlspecialchars($student['student_fullname']); ?>" 
                                               maxlength="255" required>
                                        <div class="invalid-feedback">Please enter your full name.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_ininame" class="form-label fw-semibold">Name with Initials</label>
                                        <input type="text" class="form-control" id="student_ininame" name="student_ininame" 
                                               value="<?php echo htmlspecialchars($student['student_ininame'] ?? ''); ?>" 
                                               maxlength="255" required>
                                        <div class="invalid-feedback">Please enter name with initials.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_email" class="form-label fw-semibold">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control" id="student_email" name="student_email" 
                                               value="<?php echo htmlspecialchars($student['student_email']); ?>" 
                                               maxlength="254" required>
                                        <div class="invalid-feedback">Please enter a valid email address.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_nic" class="form-label fw-semibold">
                                            NIC <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="student_nic" name="student_nic" 
                                               value="<?php echo htmlspecialchars($student['student_nic']); ?>" 
                                               maxlength="12" required>
                                        <div class="invalid-feedback">Please enter your NIC number.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 col-md-4 mb-3">
                                        <label for="student_dob" class="form-label fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="student_dob" name="student_dob" 
                                               value="<?php echo htmlspecialchars($student['student_dob'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter date of birth.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-4 mb-3">
                                        <label for="student_phone" class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="student_phone" name="student_phone" 
                                               value="<?php echo htmlspecialchars($student['student_phone'] ?? ''); ?>"
                                               pattern="[0-9]{9,10}" required>
                                        <div class="invalid-feedback">Please enter a valid phone number (9-10 digits).</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-4 mb-3">
                                        <label for="student_civil" class="form-label fw-semibold">Civil Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="student_civil" name="student_civil" required>
                                            <option value="">Select</option>
                                            <option value="Single" <?php echo ($student['student_civil'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo ($student['student_civil'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Divorced" <?php echo ($student['student_civil'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        </select>
                                        <div class="invalid-feedback">Please select civil status.</div>
                                    </div>
                                </div>
                                        
                                        <div class="mb-3">
                                            <label for="student_address" class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="student_address" name="student_address" rows="2" maxlength="255" required><?php echo htmlspecialchars($student['student_address'] ?? ''); ?></textarea>
                                            <div class="invalid-feedback">Please enter your address.</div>
                                        </div>
                                        
                                <div class="row">
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="student_zip" class="form-label fw-semibold">ZIP Code <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="student_zip" name="student_zip" 
                                               value="<?php echo htmlspecialchars($student['student_zip'] ?? ''); ?>" required>
                                        <div class="invalid-feedback">Please enter ZIP code.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="student_provice" class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                                        <select class="form-select" id="student_provice" name="student_provice" required>
                                            <option value="">Select Province</option>
                                            <option value="Western Province" <?php echo ($student['student_provice'] ?? '') === 'Western Province' ? 'selected' : ''; ?>>Western Province</option>
                                            <option value="Central Province" <?php echo ($student['student_provice'] ?? '') === 'Central Province' ? 'selected' : ''; ?>>Central Province</option>
                                            <option value="Southern Province" <?php echo ($student['student_provice'] ?? '') === 'Southern Province' ? 'selected' : ''; ?>>Southern Province</option>
                                            <option value="Northern Province" <?php echo ($student['student_provice'] ?? '') === 'Northern Province' ? 'selected' : ''; ?>>Northern Province</option>
                                            <option value="Eastern Province" <?php echo ($student['student_provice'] ?? '') === 'Eastern Province' ? 'selected' : ''; ?>>Eastern Province</option>
                                            <option value="North Western Province" <?php echo ($student['student_provice'] ?? '') === 'North Western Province' ? 'selected' : ''; ?>>North Western Province</option>
                                            <option value="North Central Province" <?php echo ($student['student_provice'] ?? '') === 'North Central Province' ? 'selected' : ''; ?>>North Central Province</option>
                                            <option value="Uva Province" <?php echo ($student['student_provice'] ?? '') === 'Uva Province' ? 'selected' : ''; ?>>Uva Province</option>
                                            <option value="Sabaragamuwa Province" <?php echo ($student['student_provice'] ?? '') === 'Sabaragamuwa Province' ? 'selected' : ''; ?>>Sabaragamuwa Province</option>
                                        </select>
                                        <div class="invalid-feedback">Please select province.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="student_district" class="form-label fw-semibold">District <span class="text-danger">*</span></label>
                                        <select class="form-select" id="student_district" name="student_district" required>
                                            <option value="">Select District</option>
                                        </select>
                                        <div class="invalid-feedback">Please select district.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-3 mb-3">
                                        <label for="student_divisions" class="form-label fw-semibold">Divisions <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="student_divisions" name="student_divisions" 
                                               value="<?php echo htmlspecialchars($student['student_divisions'] ?? ''); ?>" 
                                               maxlength="50" required>
                                        <div class="invalid-feedback">Please enter divisions.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 col-md-4 mb-3">
                                        <label for="student_blood" class="form-label fw-semibold">Blood Group <span class="text-danger">*</span></label>
                                        <select class="form-select" id="student_blood" name="student_blood" required>
                                            <option value="">Select</option>
                                            <option value="A+" <?php echo ($student['student_blood'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                            <option value="A-" <?php echo ($student['student_blood'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                            <option value="B+" <?php echo ($student['student_blood'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                            <option value="B-" <?php echo ($student['student_blood'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                            <option value="AB+" <?php echo ($student['student_blood'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                            <option value="AB-" <?php echo ($student['student_blood'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                            <option value="O+" <?php echo ($student['student_blood'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                            <option value="O-" <?php echo ($student['student_blood'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                                        </select>
                                        <div class="invalid-feedback">Please select blood group.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-4 mb-3">
                                        <label for="student_nationality" class="form-label fw-semibold">Nationality <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="student_nationality" name="student_nationality" 
                                               value="<?php echo htmlspecialchars($student['student_nationality'] ?? ''); ?>" 
                                               maxlength="50" required>
                                        <div class="invalid-feedback">Please enter nationality.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-4 mb-3">
                                        <label for="student_religion" class="form-label fw-semibold">Religion <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="student_religion" name="student_religion" 
                                               value="<?php echo htmlspecialchars($student['student_religion'] ?? ''); ?>" 
                                               maxlength="20" required>
                                        <div class="invalid-feedback">Please enter religion.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_whatsapp" class="form-label fw-semibold">WhatsApp <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="student_whatsapp" name="student_whatsapp" 
                                               value="<?php echo htmlspecialchars($student['student_whatsapp'] ?? ''); ?>" 
                                               maxlength="20" required>
                                        <div class="invalid-feedback">Please enter WhatsApp number.</div>
                                    </div>
                                </div>
                                        
                                        <hr class="my-4">
                                        <h6 class="fw-bold mb-3">Emergency Contact</h6>
                                        
                                <div class="row">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_em_name" class="form-label fw-semibold">Emergency Contact Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="student_em_name" name="student_em_name" 
                                               value="<?php echo htmlspecialchars($student['student_em_name'] ?? ''); ?>" 
                                               maxlength="255" required>
                                        <div class="invalid-feedback">Please enter emergency contact name.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_em_relation" class="form-label fw-semibold">Relation <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="student_em_relation" name="student_em_relation" 
                                               value="<?php echo htmlspecialchars($student['student_em_relation'] ?? ''); ?>" 
                                               maxlength="20" required>
                                        <div class="invalid-feedback">Please enter relation.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_em_address" class="form-label fw-semibold">Emergency Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="student_em_address" name="student_em_address" rows="2" maxlength="255" required><?php echo htmlspecialchars($student['student_em_address'] ?? ''); ?></textarea>
                                        <div class="invalid-feedback">Please enter emergency address.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="student_em_phone" class="form-label fw-semibold">Emergency Phone <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="student_em_phone" name="student_em_phone" 
                                               value="<?php echo htmlspecialchars($student['student_em_phone'] ?? ''); ?>"
                                               pattern="[0-9]{9,10}" required>
                                        <div class="invalid-feedback">Please enter a valid emergency phone number (9-10 digits).</div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Personal Information
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/student/profile" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Bank Details Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'bank' ? 'show active' : ''; ?>" id="bank" role="tabpanel">
                            <form method="POST" action="<?php echo APP_URL; ?>/student/profile/edit" id="bankForm" novalidate>
                                <input type="hidden" name="update_section" value="bank">
                                
                                <div class="row">
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="bank_name" class="form-label fw-semibold">Bank Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                               value="<?php echo htmlspecialchars($student['bank_name'] ?? 'People\'s Bank'); ?>" 
                                               maxlength="100" required>
                                        <div class="invalid-feedback">Please enter bank name.</div>
                                    </div>
                                    
                                    <div class="col-12 col-md-6 mb-3">
                                        <label for="bank_account_no" class="form-label fw-semibold">Account Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bank_account_no" name="bank_account_no" 
                                               value="<?php echo htmlspecialchars($student['bank_account_no'] ?? ''); ?>" 
                                               maxlength="50" required>
                                        <div class="invalid-feedback">Please enter account number.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="bank_branch" class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bank_branch" name="bank_branch" 
                                               value="<?php echo htmlspecialchars($student['bank_branch'] ?? ''); ?>" 
                                               maxlength="100" required>
                                        <div class="invalid-feedback">Please enter branch name.</div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($student['bank_frontsheet_path'])): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-file-pdf me-2"></i>
                                        Bank frontsheet: <a href="<?php echo APP_URL . '/assets/' . htmlspecialchars($student['bank_frontsheet_path']); ?>" target="_blank">View Document</a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Bank Details
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/student/profile" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sri Lanka Provinces and Districts mapping
const sriLankaDistricts = {
    'Western Province': ['Colombo', 'Gampaha', 'Kalutara'],
    'Central Province': ['Kandy', 'Matale', 'Nuwara Eliya'],
    'Southern Province': ['Galle', 'Matara', 'Hambantota'],
    'Northern Province': ['Jaffna', 'Kilinochchi', 'Mannar', 'Mullaitivu', 'Vavuniya'],
    'Eastern Province': ['Batticaloa', 'Ampara', 'Trincomalee'],
    'North Western Province': ['Kurunegala', 'Puttalam'],
    'North Central Province': ['Anuradhapura', 'Polonnaruwa'],
    'Uva Province': ['Badulla', 'Monaragala'],
    'Sabaragamuwa Province': ['Ratnapura', 'Kegalle']
};

// Function to load districts based on selected province
function loadDistricts(province, selectedDistrict = '') {
    const districtSelect = document.getElementById('student_district');
    if (!districtSelect) return;
    
    districtSelect.innerHTML = '<option value="">Select District</option>';
    
    if (province && sriLankaDistricts[province]) {
        sriLankaDistricts[province].forEach(function(district) {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            if (district === selectedDistrict) {
                option.selected = true;
            }
            districtSelect.appendChild(option);
        });
    }
}

// Initialize Bootstrap tabs using Bootstrap's Tab API
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('#studentTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            // Use Bootstrap's Tab API
            const tab = new bootstrap.Tab(button);
            tab.show();
        });
    });
    
    // Initialize province and district on page load
    const provinceSelect = document.getElementById('student_provice');
    const districtSelect = document.getElementById('student_district');
    const currentProvince = provinceSelect ? provinceSelect.value : '';
    const currentDistrict = '<?php echo htmlspecialchars($student['student_district'] ?? ''); ?>';
    
    if (provinceSelect && currentProvince) {
        loadDistricts(currentProvince, currentDistrict);
    }
    
    // Handle province change
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            loadDistricts(this.value);
        });
    }
});

// Form Validation
(function() {
    'use strict';
    
    // Personal Information Form
    const personalForm = document.getElementById('personalForm');
    if (personalForm) {
        personalForm.addEventListener('submit', function(event) {
            if (!personalForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Add validation classes to all fields
            const fields = personalForm.querySelectorAll('input, select, textarea');
            fields.forEach(function(field) {
                if (field.hasAttribute('required')) {
                    if (!field.value || (field.type === 'select-one' && field.value === '')) {
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                }
            });
            
            personalForm.classList.add('was-validated');
        });
        
        // Real-time validation on blur
        const personalFields = personalForm.querySelectorAll('input, select, textarea');
        personalFields.forEach(function(field) {
            if (field.hasAttribute('required')) {
                field.addEventListener('blur', function() {
                    if (!this.value || (this.type === 'select-one' && this.value === '')) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
                
                field.addEventListener('input', function() {
                    if (this.value && !(this.type === 'select-one' && this.value === '')) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
            }
        });
    }
    
    // Bank Details Form
    const bankForm = document.getElementById('bankForm');
    if (bankForm) {
        bankForm.addEventListener('submit', function(event) {
            if (!bankForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Add validation classes to all fields
            const fields = bankForm.querySelectorAll('input, select, textarea');
            fields.forEach(function(field) {
                if (field.hasAttribute('required')) {
                    if (!field.value) {
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                }
            });
            
            bankForm.classList.add('was-validated');
        });
        
        // Real-time validation on blur
        const bankFields = bankForm.querySelectorAll('input, select, textarea');
        bankFields.forEach(function(field) {
            if (field.hasAttribute('required')) {
                field.addEventListener('blur', function() {
                    if (!this.value) {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
                
                field.addEventListener('input', function() {
                    if (this.value) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
            }
        });
    }
})();
</script>

