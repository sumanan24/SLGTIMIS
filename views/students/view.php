<style>
/* Modern CV Portfolio Design Styles */
.cv-portfolio-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.cv-header {
    background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-navy) 100%);
    color: white;
    padding: 2.5rem 0;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.cv-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.cv-profile-section {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: relative;
    margin-bottom: 2rem;
}

.cv-profile-header {
    background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-navy) 100%);
    padding: 3rem 2rem 2rem;
    text-align: center;
    position: relative;
}

.cv-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 5px solid white;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 4rem;
    color: white;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
}

.cv-profile-header h1 {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    letter-spacing: 0.5px;
}

.cv-profile-header .cv-id {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    font-weight: 400;
    letter-spacing: 1px;
}

.cv-status-badge {
    display: inline-block;
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    margin-top: 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.cv-body {
    padding: 2rem;
}

.cv-section {
    margin-bottom: 2.5rem;
}

.cv-section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-navy);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid var(--primary-navy-light);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.cv-section-title i {
    font-size: 1.5rem;
    color: var(--primary-navy-light);
}

.cv-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.cv-info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.cv-info-item:hover {
    background: #e9ecef;
    border-left-color: var(--primary-navy-light);
    transform: translateX(5px);
}

.cv-info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-navy-light) 0%, var(--primary-navy) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.125rem;
    flex-shrink: 0;
}

.cv-info-content {
    flex: 1;
}

.cv-info-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.cv-info-value {
    font-size: 0.9375rem;
    color: #212529;
    font-weight: 500;
}

.cv-info-value a {
    color: var(--primary-navy);
    text-decoration: none;
    transition: color 0.3s ease;
}

.cv-info-value a:hover {
    color: var(--primary-navy-light);
    text-decoration: underline;
}

.cv-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    height: 100%;
    border: 1px solid #e9ecef;
}

.cv-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    transform: translateY(-5px);
}

.cv-timeline {
    position: relative;
    padding-left: 2rem;
}

.cv-timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--primary-navy-light), var(--primary-navy));
}

.cv-timeline-item {
    position: relative;
    padding-bottom: 2rem;
}

.cv-timeline-item::before {
    content: '';
    position: absolute;
    left: -1.75rem;
    top: 0.25rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-navy);
    border: 3px solid white;
    box-shadow: 0 0 0 3px var(--primary-navy-light);
}

.cv-timeline-content {
    background: #f8f9fa;
    padding: 1.25rem;
    border-radius: 12px;
    border-left: 4px solid var(--primary-navy-light);
}

.cv-timeline-title {
    font-weight: 700;
    color: var(--primary-navy);
    margin-bottom: 0.5rem;
    font-size: 1.125rem;
}

.cv-timeline-meta {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.75rem;
}

.cv-timeline-description {
    font-size: 0.9375rem;
    color: #495057;
    line-height: 1.6;
}

.cv-badge {
    display: inline-block;
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
    font-size: 0.8125rem;
    font-weight: 600;
    margin: 0.25rem;
}

.cv-badge-primary {
    background: rgba(0, 31, 63, 0.1);
    color: var(--primary-navy);
}

.cv-badge-success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.cv-badge-warning {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.cv-badge-info {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
}

.cv-badge-secondary {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.cv-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.cv-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.cv-btn-primary {
    background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-navy) 100%);
    color: white;
}

.cv-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 31, 63, 0.3);
    color: white;
}

.cv-btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: #212529;
}

.cv-btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);
    color: #212529;
}

.cv-btn-outline {
    background: white;
    color: var(--primary-navy);
    border: 2px solid var(--primary-navy);
}

.cv-btn-outline:hover {
    background: var(--primary-navy);
    color: white;
    transform: translateY(-2px);
}

.cv-empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
}

.cv-empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .cv-profile-header {
        padding: 2rem 1.5rem 1.5rem;
    }
    
    .cv-avatar {
        width: 120px;
        height: 120px;
        font-size: 3rem;
    }
    
    .cv-profile-header h1 {
        font-size: 1.5rem;
    }
    
    .cv-body {
        padding: 1.5rem;
    }
    
    .cv-info-grid {
        grid-template-columns: 1fr;
    }
    
    .cv-actions {
        flex-direction: column;
    }
    
    .cv-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="cv-portfolio-wrapper">
    <div class="container-fluid px-4">
        <!-- Header Actions -->
        <?php if (!isset($page) || $page !== 'student-profile'): ?>
        <div class="d-flex justify-content-end align-items-center mb-4">
            <div class="cv-actions">
                <?php if (isset($canEdit) && $canEdit): ?>
                <a href="<?php echo APP_URL; ?>/students/edit?id=<?php echo urlencode($student['student_id']); ?>" class="cv-btn cv-btn-primary">
                    <i class="fas fa-edit"></i>Edit Profile
                </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>/students" class="cv-btn cv-btn-outline">
                    <i class="fas fa-arrow-left"></i>Back
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Profile Section -->
        <div class="cv-profile-section">
            <div class="cv-profile-header">
                <?php
                require_once BASE_PATH . '/models/StudentModel.php';
                $studentModelHelper = new StudentModel();
                $profileImageUrl = $studentModelHelper->getProfileImagePath($student);
                ?>
                <div class="cv-avatar">
                    <?php if ($profileImageUrl): ?>
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                             alt="<?php echo htmlspecialchars($student['student_fullname']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <h1><?php echo htmlspecialchars($student['student_fullname']); ?></h1>
                <div class="cv-id"><?php echo htmlspecialchars($student['student_id']); ?></div>
                <div class="cv-status-badge">
                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                    <?php echo htmlspecialchars($student['student_status'] ?? 'Active'); ?>
                </div>
            </div>
            
            <div class="cv-body">
                <div class="row g-4">
                    <!-- Personal Information -->
                    <div class="col-lg-6">
                        <div class="cv-section">
                            <h3 class="cv-section-title">
                                <i class="fas fa-user"></i>
                                Personal Information
                            </h3>
                            <div class="cv-info-grid">
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-id-card"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">NIC Number</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_nic'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-venus-mars"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Gender</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_gender'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-birthday-cake"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Date of Birth</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_dob'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-heartbeat"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Blood Group</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_blood'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-globe"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Nationality</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_nationality'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-pray"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Religion</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_religion'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="col-lg-6">
                        <div class="cv-section">
                            <h3 class="cv-section-title">
                                <i class="fas fa-address-book"></i>
                                Contact Information
                            </h3>
                            <div class="cv-info-grid">
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-envelope"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Email</div>
                                        <div class="cv-info-value">
                                            <a href="mailto:<?php echo htmlspecialchars($student['student_email']); ?>">
                                                <?php echo htmlspecialchars($student['student_email']); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-phone"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Phone</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_phone'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fab fa-whatsapp"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">WhatsApp</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_whatsapp'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Address</div>
                                        <div class="cv-info-value">
                                            <?php 
                                            $address = [];
                                            if (!empty($student['student_address'])) $address[] = $student['student_address'];
                                            if (!empty($student['student_district'])) $address[] = $student['student_district'];
                                            if (!empty($student['student_provice'])) $address[] = $student['student_provice'];
                                            echo htmlspecialchars(implode(', ', $address) ?: 'N/A');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h4 class="cv-section-title mt-4" style="font-size: 1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Emergency Contact
                            </h4>
                            <div class="cv-info-grid">
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-user-shield"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Name</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_em_name'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-link"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Relation</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_em_relation'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="cv-info-item">
                                    <div class="cv-info-icon"><i class="fas fa-phone-alt"></i></div>
                                    <div class="cv-info-content">
                                        <div class="cv-info-label">Emergency Phone</div>
                                        <div class="cv-info-value"><?php echo htmlspecialchars($student['student_em_phone'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Education & Enrollment Section -->
        <?php if (!empty($currentEnrollment) || !empty($enrollments)): ?>
        <div class="cv-card mb-4">
            <h3 class="cv-section-title">
                <i class="fas fa-graduation-cap"></i>
                Education & Enrollment
            </h3>
            
            <?php if (!empty($currentEnrollment)): ?>
            <div class="cv-timeline">
                <div class="cv-timeline-item">
                    <div class="cv-timeline-content">
                        <div class="cv-timeline-title"><?php echo htmlspecialchars($currentEnrollment['course_name'] ?? 'N/A'); ?></div>
                        <div class="cv-timeline-meta">
                            <span class="cv-badge cv-badge-primary"><?php echo htmlspecialchars($currentEnrollment['department_name'] ?? 'N/A'); ?></span>
                            <span class="cv-badge cv-badge-info"><?php echo htmlspecialchars($currentEnrollment['academic_year'] ?? 'N/A'); ?></span>
                            <span class="cv-badge cv-badge-<?php echo $currentEnrollment['student_enroll_status'] === 'Following' ? 'success' : 'warning'; ?>">
                                <?php echo htmlspecialchars($currentEnrollment['student_enroll_status'] ?? 'N/A'); ?>
                            </span>
                        </div>
                        <div class="cv-timeline-description">
                            <p><strong>Mode:</strong> <?php echo htmlspecialchars($currentEnrollment['course_mode'] ?? 'Full Time'); ?></p>
                            <p><strong>Enrollment Date:</strong> <?php echo htmlspecialchars($currentEnrollment['student_enroll_date'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (count($enrollments) > 1): ?>
            <div class="mt-4">
                <h4 style="font-size: 1rem; font-weight: 600; color: var(--primary-navy); margin-bottom: 1rem;">
                    <i class="fas fa-history me-2"></i>Enrollment History
                </h4>
                <div class="cv-timeline">
                    <?php foreach (array_slice($enrollments, 1, 5) as $enroll): ?>
                    <div class="cv-timeline-item">
                        <div class="cv-timeline-content">
                            <div class="cv-timeline-title"><?php echo htmlspecialchars($enroll['course_name'] ?? 'N/A'); ?></div>
                            <div class="cv-timeline-meta">
                                <span class="cv-badge cv-badge-primary"><?php echo htmlspecialchars($enroll['academic_year'] ?? 'N/A'); ?></span>
                                <span class="cv-badge cv-badge-secondary"><?php echo htmlspecialchars($enroll['student_enroll_status'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Additional Information Row -->
        <div class="row g-4">
            <!-- Hostel Information -->
            <div class="col-lg-6">
                <div class="cv-card">
                    <h3 class="cv-section-title">
                        <i class="fas fa-bed"></i>
                        Hostel Information
                    </h3>
                    <?php if ($hasHostel && !empty($hostelAllocation)): ?>
                        <div class="cv-info-grid">
                            <div class="cv-info-item">
                                <div class="cv-info-icon"><i class="fas fa-building"></i></div>
                                <div class="cv-info-content">
                                    <div class="cv-info-label">Hostel</div>
                                    <div class="cv-info-value"><?php echo htmlspecialchars($hostelAllocation['hostel_name'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            <div class="cv-info-item">
                                <div class="cv-info-icon"><i class="fas fa-layer-group"></i></div>
                                <div class="cv-info-content">
                                    <div class="cv-info-label">Block</div>
                                    <div class="cv-info-value"><?php echo htmlspecialchars($hostelAllocation['block_name'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            <div class="cv-info-item">
                                <div class="cv-info-icon"><i class="fas fa-door-open"></i></div>
                                <div class="cv-info-content">
                                    <div class="cv-info-label">Room Number</div>
                                    <div class="cv-info-value">
                                        <span class="cv-badge cv-badge-info">
                                            <?php echo htmlspecialchars($hostelAllocation['room_no'] ?? 'N/A'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="cv-info-item">
                                <div class="cv-info-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="cv-info-content">
                                    <div class="cv-info-label">Allocated Date</div>
                                    <div class="cv-info-value"><?php echo htmlspecialchars($hostelAllocation['allocated_at'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            <div class="cv-info-item">
                                <div class="cv-info-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="cv-info-content">
                                    <div class="cv-info-label">Status</div>
                                    <div class="cv-info-value">
                                        <span class="cv-badge cv-badge-success">
                                            <?php echo ucfirst($hostelAllocation['status'] ?? 'Active'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="cv-empty-state">
                            <i class="fas fa-bed"></i>
                            <p>Student is not allocated to any hostel.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bank & Eligibility Information -->
            <div class="col-lg-6">
                <div class="cv-card">
                    <h3 class="cv-section-title">
                        <i class="fas fa-university"></i>
                        Financial & Eligibility
                    </h3>
                    <div class="cv-info-grid">
                        <div class="cv-info-item">
                            <div class="cv-info-icon"><i class="fas fa-landmark"></i></div>
                            <div class="cv-info-content">
                                <div class="cv-info-label">Bank Name</div>
                                <div class="cv-info-value"><?php echo htmlspecialchars($student['bank_name'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="cv-info-item">
                            <div class="cv-info-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="cv-info-content">
                                <div class="cv-info-label">Account Number</div>
                                <div class="cv-info-value"><?php echo htmlspecialchars($student['bank_account_no'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="cv-info-item">
                            <div class="cv-info-icon"><i class="fas fa-code-branch"></i></div>
                            <div class="cv-info-content">
                                <div class="cv-info-label">Branch</div>
                                <div class="cv-info-value"><?php echo htmlspecialchars($student['bank_branch'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="cv-info-item">
                            <div class="cv-info-icon"><i class="fas fa-money-check-alt"></i></div>
                            <div class="cv-info-content">
                                <div class="cv-info-label">Allowance Eligible</div>
                                <div class="cv-info-value">
                                    <span class="cv-badge cv-badge-<?php echo ($student['allowance_eligible'] ?? 0) ? 'success' : 'secondary'; ?>">
                                        <?php echo ($student['allowance_eligible'] ?? 0) ? 'Yes' : 'No'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Section -->
        <div class="cv-card mt-4">
            <h3 class="cv-section-title">
                <i class="fas fa-cogs"></i>
                Quick Actions
            </h3>
            <div class="cv-actions">
                <?php if (isset($canEdit) && $canEdit): ?>
                <a href="<?php echo APP_URL; ?>/students/edit?id=<?php echo urlencode($student['student_id']); ?>" class="cv-btn cv-btn-primary">
                    <i class="fas fa-edit"></i>Edit Profile
                </a>
                <a href="<?php echo APP_URL; ?>/students/reset-password?id=<?php echo urlencode($student['student_id']); ?>" class="cv-btn cv-btn-warning" onclick="return confirm('Are you sure you want to reset the password for <?php echo htmlspecialchars($student['student_fullname']); ?>? This will set the password to their NIC number.');">
                    <i class="fas fa-key"></i>Reset Password
                </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>/students" class="cv-btn cv-btn-outline">
                    <i class="fas fa-arrow-left"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>
