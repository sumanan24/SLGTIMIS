<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Student: <?php echo htmlspecialchars($student['student_id']); ?></h5>
                        <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-eye me-1"></i>View
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
                    $activeTab = $_SESSION['active_tab'] ?? 'personal';
                    unset($_SESSION['active_tab']);
                    ?>
                    <ul class="nav nav-tabs mb-4" id="studentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'personal' ? 'active' : ''; ?>" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                                <i class="fas fa-user me-1"></i>Personal Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'enrollment' ? 'active' : ''; ?>" id="enrollment-tab" data-bs-toggle="tab" data-bs-target="#enrollment" type="button" role="tab">
                                <i class="fas fa-graduation-cap me-1"></i>Enrollment
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'bank' ? 'active' : ''; ?>" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank" type="button" role="tab">
                                <i class="fas fa-university me-1"></i>Bank Details
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="hostel-tab" data-bs-toggle="tab" data-bs-target="#hostel" type="button" role="tab">
                                <i class="fas fa-bed me-1"></i>Hostel
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'eligibility' ? 'active' : ''; ?>" id="eligibility-tab" data-bs-toggle="tab" data-bs-target="#eligibility" type="button" role="tab">
                                <i class="fas fa-check-circle me-1"></i>Eligibility
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="studentTabsContent">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'personal' ? 'show active' : ''; ?>" id="personal" role="tabpanel">
                            <form method="POST" action="<?php echo APP_URL; ?>/students/edit?id=<?php echo urlencode($student['student_id']); ?>" enctype="multipart/form-data">
                                <input type="hidden" name="update_section" value="personal">
                                
                                <!-- Profile Image Section -->
                                <div class="card mb-4 border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold"><i class="fas fa-image me-2"></i>Profile Image</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                                <?php
                                                require_once BASE_PATH . '/models/StudentModel.php';
                                                $studentModelHelper = new StudentModel();
                                                $profileImageUrl = $studentModelHelper->getProfileImagePath($student);
                                                ?>
                                                <div class="position-relative d-inline-block">
                                                    <div style="width: 150px; height: 150px; border-radius: 50%; overflow: hidden; border: 3px solid #dee2e6; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                                                        <?php if ($profileImageUrl): ?>
                                                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                                                                 alt="<?php echo htmlspecialchars($student['student_fullname']); ?>" 
                                                                 id="currentProfileImage"
                                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <i class="fas fa-user fa-4x text-muted" id="currentProfileImageIcon"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($profileImageUrl): ?>
                                                        <button type="button" class="btn btn-sm btn-danger position-absolute" 
                                                                style="top: 0; right: 0; border-radius: 50%; width: 30px; height: 30px; padding: 0;"
                                                                onclick="confirmRemoveImage()" title="Remove Image">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-2">
                                                    <?php if ($profileImageUrl): ?>
                                                        <small class="text-muted">Current Image</small>
                                                    <?php else: ?>
                                                        <small class="text-muted">No Image</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="profile_image" class="form-label fw-semibold">Upload New Profile Image</label>
                                                    <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                                           accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this)">
                                                    <div class="form-text">Accepted formats: JPG, PNG, GIF (Max 5MB)</div>
                                                </div>
                                                <div id="imagePreview" class="mb-3" style="display: none;">
                                                    <img id="previewImg" src="" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 2px solid #dee2e6;">
                                                    <div class="mt-2">
                                                        <small class="text-info"><i class="fas fa-info-circle me-1"></i>Image Preview</small>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="student_id" class="form-label fw-semibold">Student ID</label>
                                                <input type="text" class="form-control" id="student_id" 
                                                       value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                                       disabled>
                                                <div class="form-text">Student ID cannot be changed</div>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="student_title" class="form-label fw-semibold">Title</label>
                                                <select class="form-select" id="student_title" name="student_title">
                                                    <option value="">Select</option>
                                                    <option value="Mr." <?php echo ($student['student_title'] ?? '') === 'Mr.' ? 'selected' : ''; ?>>Mr.</option>
                                                    <option value="Mrs." <?php echo ($student['student_title'] ?? '') === 'Mrs.' ? 'selected' : ''; ?>>Mrs.</option>
                                                    <option value="Miss" <?php echo ($student['student_title'] ?? '') === 'Miss' ? 'selected' : ''; ?>>Miss</option>
                                                    <option value="Ms." <?php echo ($student['student_title'] ?? '') === 'Ms.' ? 'selected' : ''; ?>>Ms.</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="student_gender" class="form-label fw-semibold">Gender</label>
                                                <select class="form-select" id="student_gender" name="student_gender">
                                                    <option value="">Select</option>
                                                    <option value="Male" <?php echo ($student['student_gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo ($student['student_gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="student_fullname" class="form-label fw-semibold">
                                                    Full Name <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="student_fullname" name="student_fullname" 
                                                       value="<?php echo htmlspecialchars($student['student_fullname']); ?>" 
                                                       maxlength="255" required>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="student_ininame" class="form-label fw-semibold">Name with Initials</label>
                                                <input type="text" class="form-control" id="student_ininame" name="student_ininame" 
                                                       value="<?php echo htmlspecialchars($student['student_ininame'] ?? ''); ?>" 
                                                       maxlength="255">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="student_email" class="form-label fw-semibold">
                                                    Email <span class="text-danger">*</span>
                                                </label>
                                                <input type="email" class="form-control" id="student_email" name="student_email" 
                                                       value="<?php echo htmlspecialchars($student['student_email']); ?>" 
                                                       maxlength="254" required>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="student_nic" class="form-label fw-semibold">
                                                    NIC <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="student_nic" name="student_nic" 
                                                       value="<?php echo htmlspecialchars($student['student_nic']); ?>" 
                                                       maxlength="12" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="student_dob" class="form-label fw-semibold">Date of Birth</label>
                                                <input type="date" class="form-control" id="student_dob" name="student_dob" 
                                                       value="<?php echo htmlspecialchars($student['student_dob'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="student_phone" class="form-label fw-semibold">Phone</label>
                                                <input type="tel" class="form-control" id="student_phone" name="student_phone" 
                                                       value="<?php echo htmlspecialchars($student['student_phone'] ?? ''); ?>"
                                                       pattern="[0-9]{9,10}">
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="student_civil" class="form-label fw-semibold">Civil Status</label>
                                                <select class="form-select" id="student_civil" name="student_civil">
                                                    <option value="Single" <?php echo ($student['student_civil'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                                    <option value="Married" <?php echo ($student['student_civil'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                                    <option value="Divorced" <?php echo ($student['student_civil'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="student_address" class="form-label fw-semibold">Address</label>
                                            <textarea class="form-control" id="student_address" name="student_address" rows="2" maxlength="255"><?php echo htmlspecialchars($student['student_address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="student_zip" class="form-label fw-semibold">ZIP Code</label>
                                                <input type="number" class="form-control" id="student_zip" name="student_zip" 
                                                       value="<?php echo htmlspecialchars($student['student_zip'] ?? ''); ?>" required>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
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
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="student_district" class="form-label fw-semibold">District <span class="text-danger">*</span></label>
                                                <select class="form-select" id="student_district" name="student_district" required>
                                                    <option value="">Select District</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="student_divisions" class="form-label fw-semibold">Divisions</label>
                                                <input type="text" class="form-control" id="student_divisions" name="student_divisions" 
                                                       value="<?php echo htmlspecialchars($student['student_divisions'] ?? ''); ?>" 
                                                       maxlength="50" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="student_blood" class="form-label fw-semibold">Blood Group</label>
                                                <select class="form-select" id="student_blood" name="student_blood">
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
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="student_nationality" class="form-label fw-semibold">Nationality</label>
                                                <input type="text" class="form-control" id="student_nationality" name="student_nationality" 
                                                       value="<?php echo htmlspecialchars($student['student_nationality'] ?? ''); ?>" 
                                                       maxlength="50">
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label for="student_religion" class="form-label fw-semibold">Religion</label>
                                                <input type="text" class="form-control" id="student_religion" name="student_religion" 
                                                       value="<?php echo htmlspecialchars($student['student_religion'] ?? ''); ?>" 
                                                       maxlength="20">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="student_whatsapp" class="form-label fw-semibold">WhatsApp</label>
                                                <input type="text" class="form-control" id="student_whatsapp" name="student_whatsapp" 
                                                       value="<?php echo htmlspecialchars($student['student_whatsapp'] ?? ''); ?>" 
                                                       maxlength="20">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="student_status" class="form-label fw-semibold">Status</label>
                                                <select class="form-select" id="student_status" name="student_status">
                                                    <option value="Active" <?php echo ($student['student_status'] ?? '') === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="Inactive" <?php echo ($student['student_status'] ?? '') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="Graduated" <?php echo ($student['student_status'] ?? '') === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-4">
                                        <h6 class="fw-bold mb-3">Emergency Contact</h6>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="student_em_name" class="form-label fw-semibold">Emergency Contact Name</label>
                                                <input type="text" class="form-control" id="student_em_name" name="student_em_name" 
                                                       value="<?php echo htmlspecialchars($student['student_em_name'] ?? ''); ?>" 
                                                       maxlength="255">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="student_em_relation" class="form-label fw-semibold">Relation</label>
                                                <input type="text" class="form-control" id="student_em_relation" name="student_em_relation" 
                                                       value="<?php echo htmlspecialchars($student['student_em_relation'] ?? ''); ?>" 
                                                       maxlength="20">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="student_em_address" class="form-label fw-semibold">Emergency Address</label>
                                                <textarea class="form-control" id="student_em_address" name="student_em_address" rows="2" maxlength="255"><?php echo htmlspecialchars($student['student_em_address'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="student_em_phone" class="form-label fw-semibold">Emergency Phone</label>
                                                <input type="tel" class="form-control" id="student_em_phone" name="student_em_phone" 
                                                       value="<?php echo htmlspecialchars($student['student_em_phone'] ?? ''); ?>"
                                                       pattern="[0-9]{9,10}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Personal Information
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Enrollment Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'enrollment' ? 'show active' : ''; ?>" id="enrollment" role="tabpanel">
                            <form method="POST" action="<?php echo APP_URL; ?>/students/edit?id=<?php echo urlencode($student['student_id']); ?>">
                                <input type="hidden" name="update_section" value="enrollment">
                                
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <label for="student_id_new" class="form-label fw-semibold">
                                            Registration Number (Student ID) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="student_id_new" name="student_id_new" 
                                               value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                               maxlength="50" required>
                                        <div class="form-text">Current: <?php echo htmlspecialchars($student['student_id']); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($currentEnrollment)): ?>
                                    <div class="alert alert-info mb-4">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Current Enrollment:</strong>
                                        <?php echo htmlspecialchars($currentEnrollment['course_name'] ?? 'N/A'); ?> - 
                                        <?php echo htmlspecialchars($currentEnrollment['academic_year'] ?? 'N/A'); ?> 
                                        (<?php echo htmlspecialchars($currentEnrollment['student_enroll_status'] ?? 'N/A'); ?>)
                                    </div>
                                    
                                    <h6 class="fw-bold mb-3">Update Current Enrollment</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="course_id" class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
                                            <select class="form-select" id="course_id" name="course_id" required>
                                                <option value="">Select Course</option>
                                                <?php foreach ($courses as $course): ?>
                                                    <option value="<?php echo htmlspecialchars($course['course_id']); ?>" 
                                                            data-department="<?php echo htmlspecialchars($course['department_id'] ?? ''); ?>"
                                                            <?php echo ($currentEnrollment['course_id'] ?? '') == $course['course_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="academic_year" class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                                            <select class="form-select" id="academic_year" name="academic_year" required>
                                                <option value="">Select Academic Year</option>
                                                <?php foreach ($academicYears as $year): ?>
                                                    <option value="<?php echo htmlspecialchars($year); ?>" 
                                                            <?php echo ($currentEnrollment['academic_year'] ?? '') == $year ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($year); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="course_mode" class="form-label fw-semibold">Course Mode</label>
                                            <select class="form-select" id="course_mode" name="course_mode">
                                                <option value="Full Time" <?php echo ($currentEnrollment['course_mode'] ?? '') === 'Full Time' ? 'selected' : ''; ?>>Full Time</option>
                                                <option value="Part Time" <?php echo ($currentEnrollment['course_mode'] ?? '') === 'Part Time' ? 'selected' : ''; ?>>Part Time</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="student_enroll_status" class="form-label fw-semibold">Enrollment Status</label>
                                            <select class="form-select" id="student_enroll_status" name="student_enroll_status">
                                                <option value="Following" <?php echo ($currentEnrollment['student_enroll_status'] ?? '') === 'Following' ? 'selected' : ''; ?>>Following</option>
                                                <option value="Dropout" <?php echo ($currentEnrollment['student_enroll_status'] ?? '') === 'Dropout' ? 'selected' : ''; ?>>Dropout</option>
                                                <option value="Completed" <?php echo ($currentEnrollment['student_enroll_status'] ?? '') === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="Long Absent" <?php echo ($currentEnrollment['student_enroll_status'] ?? '') === 'Long Absent' ? 'selected' : ''; ?>>Long Absent</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="enrollment_id" value="<?php echo htmlspecialchars($currentEnrollment['student_id'] ?? ''); ?>">
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>No active enrollment found. Please create an enrollment first.
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($enrollments)): ?>
                                    <hr class="my-4">
                                    <h6 class="fw-bold mb-3">Enrollment History</h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Academic Year</th>
                                                    <th>Course</th>
                                                    <th>Department</th>
                                                    <th>Mode</th>
                                                    <th>Enroll Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($enrollments as $enroll): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($enroll['academic_year']); ?></td>
                                                        <td><?php echo htmlspecialchars($enroll['course_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($enroll['department_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($enroll['course_mode']); ?></td>
                                                        <td><?php echo htmlspecialchars($enroll['student_enroll_date']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $enroll['student_enroll_status'] === 'Following' ? 'success' : 'warning'; ?> rounded-pill">
                                                                <?php echo htmlspecialchars($enroll['student_enroll_status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Enrollment
                                    </button>
                                    <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                            
                        <!-- Bank Details Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'bank' ? 'show active' : ''; ?>" id="bank" role="tabpanel">
                            <form method="POST" action="<?php echo APP_URL; ?>/students/edit?id=<?php echo urlencode($student['student_id']); ?>">
                                <input type="hidden" name="update_section" value="bank">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="bank_name" class="form-label fw-semibold">Bank Name</label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                               value="<?php echo htmlspecialchars($student['bank_name'] ?? 'People\'s Bank'); ?>" 
                                               maxlength="100">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="bank_account_no" class="form-label fw-semibold">Account Number</label>
                                        <input type="text" class="form-control" id="bank_account_no" name="bank_account_no" 
                                               value="<?php echo htmlspecialchars($student['bank_account_no'] ?? ''); ?>" 
                                               maxlength="50">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="bank_branch" class="form-label fw-semibold">Branch</label>
                                        <input type="text" class="form-control" id="bank_branch" name="bank_branch" 
                                               value="<?php echo htmlspecialchars($student['bank_branch'] ?? ''); ?>" 
                                               maxlength="100">
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
                                    <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                            
                            <!-- Hostel Tab -->
                            <div class="tab-pane fade" id="hostel" role="tabpanel">
                                <?php if (!empty($hostelAllocation)): ?>
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3">Current Hostel Allocation</h6>
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <td class="fw-semibold text-muted" style="width: 30%;">Hostel:</td>
                                                    <td><?php echo htmlspecialchars($hostelAllocation['hostel_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-muted">Block:</td>
                                                    <td><?php echo htmlspecialchars($hostelAllocation['block_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-muted">Room Number:</td>
                                                    <td>
                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                            <?php echo htmlspecialchars($hostelAllocation['room_no'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-muted">Allocated Date:</td>
                                                    <td><?php echo htmlspecialchars($hostelAllocation['allocated_at'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-muted">Status:</td>
                                                    <td>
                                                        <span class="badge bg-success rounded-pill">
                                                            <?php echo ucfirst($hostelAllocation['status'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>Student is not allocated to any hostel.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                        <!-- Eligibility Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'eligibility' ? 'show active' : ''; ?>" id="eligibility" role="tabpanel">
                            <form method="POST" action="<?php echo APP_URL; ?>/students/edit?id=<?php echo urlencode($student['student_id']); ?>">
                                <input type="hidden" name="update_section" value="eligibility">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allowance_eligible" name="allowance_eligible" 
                                                   value="1" <?php echo ($student['allowance_eligible'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-semibold" for="allowance_eligible">
                                                Allowance Eligible
                                            </label>
                                        </div>
                                        <div class="form-text">Check if student is eligible for allowances</div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="card border">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Conduct Acceptance</h6>
                                                <?php if (!empty($student['student_conduct_accepted_at'])): ?>
                                                    <p class="mb-0">
                                                        <span class="badge bg-success rounded-pill">
                                                            Accepted on <?php echo date('Y-m-d H:i', strtotime($student['student_conduct_accepted_at'])); ?>
                                                        </span>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Not yet accepted</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card border">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Leaving Certificate</h6>
                                                <?php if (!empty($student['leaving_certificate_confirmed_at'])): ?>
                                                    <p class="mb-0">
                                                        <span class="badge bg-success rounded-pill">
                                                            Confirmed on <?php echo date('Y-m-d H:i', strtotime($student['leaving_certificate_confirmed_at'])); ?>
                                                        </span>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">Not yet confirmed</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Eligibility
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
    </div>
</div>

<script>
// Profile Image Preview and Removal
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('previewImg');
            const imagePreview = document.getElementById('imagePreview');
            if (previewImg && imagePreview) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function confirmRemoveImage() {
    if (confirm('Are you sure you want to remove the current profile image? This action cannot be undone.')) {
        document.getElementById('remove_profile_image').value = '1';
        const currentImage = document.getElementById('currentProfileImage');
        const currentIcon = document.getElementById('currentProfileImageIcon');
        const imagePreview = document.getElementById('imagePreview');
        const profileImageInput = document.getElementById('profile_image');
        
        if (currentImage) {
            currentImage.style.display = 'none';
        }
        if (currentIcon) {
            currentIcon.style.display = 'flex';
        }
        if (imagePreview) {
            imagePreview.style.display = 'none';
        }
        if (profileImageInput) {
            profileImageInput.value = '';
        }
        
        // Show message
        alert('Profile image will be removed when you save the form.');
    }
}

// Reset remove flag if user selects a new image after clicking remove
document.addEventListener('DOMContentLoaded', function() {
    const profileImageInput = document.getElementById('profile_image');
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                // User selected a new image, don't remove the old one
                document.getElementById('remove_profile_image').value = '0';
            }
        });
    }
});

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
</script>
