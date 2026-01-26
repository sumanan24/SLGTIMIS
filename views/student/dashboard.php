<style>
    .student-dashboard-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
        border-left: 4px solid var(--student-primary);
    }
    
    .student-dashboard-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--student-primary);
        line-height: 1.2;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 0.5rem;
    }
    
    .attendance-calendar-day {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        margin: 2px;
        font-weight: 600;
        font-size: 0.875rem;
        flex-shrink: 0;
    }
    
    .attendance-present {
        background-color: #d4edda;
        color: #155724;
    }
    
    .attendance-absent {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .attendance-holiday {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .attendance-empty {
        background-color: #e9ecef;
        color: #6c757d;
    }
    
    .attendance-weekend {
        background-color: #f8f9fa;
        color: #adb5bd;
    }
    
    .calendar-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .hover-lift {
        transition: all 0.3s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-color: var(--student-primary) !important;
    }
    
    /* Utility classes */
    .min-w-0 {
        min-width: 0;
    }
    
    /* Quick link card base styles */
    .quick-link-card {
        transition: all 0.3s ease;
    }
    
    .quick-link-icon {
        flex-shrink: 0;
    }
    
    .quick-link-text,
    .quick-link-subtext {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .stat-value {
            font-size: 2rem;
        }
        
        .attendance-calendar-day {
            width: 35px;
            height: 35px;
            font-size: 0.75rem;
            margin: 1px;
        }
        
        .student-welcome-card .row {
            flex-direction: column;
            text-align: center;
        }
        
        .student-welcome-card .col-md-4 {
            margin-top: 1rem;
        }
        
        .student-welcome-card .col-md-4.text-md-end {
            text-align: center !important;
        }
        
        /* Quick Links Mobile */
        .quick-link-card {
            padding: 0.75rem !important;
            gap: 0.75rem !important;
        }
        
        .quick-link-icon {
            padding: 0.5rem !important;
            min-width: 45px;
            width: 45px;
            height: 45px;
        }
        
        .quick-link-icon i {
            font-size: 1.25rem !important;
        }
        
        .quick-link-text {
            font-size: 0.875rem;
        }
        
        .quick-link-subtext {
            font-size: 0.75rem;
        }
        
        /* Statistics Cards Mobile */
        .stat-value {
            font-size: 1.75rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
        }
        
        /* Dashboard Cards Mobile */
        .student-dashboard-card {
            padding: 1rem !important;
        }
        
        /* Quick Info Mobile */
        .quick-info-row .col-6 {
            margin-bottom: 0.75rem;
        }
    }
    
    @media (max-width: 576px) {
        .stat-value {
            font-size: 1.5rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
        }
        
        .attendance-calendar-day {
            width: 30px;
            height: 30px;
            font-size: 0.7rem;
        }
        
        .student-dashboard-card {
            margin-bottom: 1rem;
            padding: 0.875rem !important;
        }
        
        /* Quick Links Small Mobile */
        .quick-link-card {
            padding: 0.625rem !important;
            gap: 0.5rem !important;
            flex-direction: column;
            text-align: center;
            align-items: center !important;
        }
        
        .quick-link-icon {
            padding: 0.5rem !important;
            min-width: 40px;
            width: 40px;
            height: 40px;
        }
        
        .quick-link-icon i {
            font-size: 1.1rem !important;
        }
        
        .quick-link-text {
            font-size: 0.8rem;
            text-align: center;
        }
        
        .quick-link-subtext {
            font-size: 0.7rem;
            text-align: center;
        }
        
        /* Container padding */
        .container-fluid {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        
        /* Welcome card mobile */
        .student-welcome-card {
            padding: 1rem !important;
        }
        
        .student-welcome-card h2 {
            font-size: 1.25rem;
        }
        
        /* Calendar header mobile */
        .calendar-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 0.75rem;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <!-- Welcome Card -->
    <div class="student-welcome-card mb-3 mb-md-4">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-8">
                <h2 class="mb-2 mb-md-3">
                    <i class="fas fa-user-graduate me-2"></i>
                    Welcome, <?php echo htmlspecialchars($student['student_fullname'] ?? $student['student_id']); ?>!
                </h2>
                <p class="mb-0 opacity-75 small">
                    <?php if ($currentEnrollment): ?>
                        <i class="fas fa-graduation-cap me-1"></i>
                        <?php echo htmlspecialchars($currentEnrollment['course_name'] ?? ''); ?>
                        <?php if ($currentEnrollment['department_name']): ?>
                            - <?php echo htmlspecialchars($currentEnrollment['department_name']); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Student Portal
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-12 col-md-4 text-center text-md-end">
                <?php
                require_once BASE_PATH . '/models/StudentModel.php';
                $studentModelHelper = new StudentModel();
                $profileImageUrl = $studentModelHelper->getProfileImagePath($student);
                ?>
                <?php if ($profileImageUrl): ?>
                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                         alt="Profile" 
                         class="rounded-circle border border-3 border-white"
                         style="width: 80px; height: 80px; object-fit: cover; max-width: 100%;">
                <?php else: ?>
                    <div class="rounded-circle border border-3 border-white bg-white bg-opacity-25 d-inline-flex align-items-center justify-content-center"
                         style="width: 80px; height: 80px; max-width: 100%;">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Links Row -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <div class="col-12">
            <div class="student-dashboard-card p-3 p-md-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-link me-2" style="color: var(--student-primary);"></i>Quick Links
                </h5>
                <div class="row g-2 g-md-3">
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/on-peak-requests" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-primary border-opacity-25 hover-lift quick-link-card">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center quick-link-icon">
                                        <i class="fas fa-calendar-alt text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark quick-link-text">On-Peak Requests</div>
                                    <div class="small text-muted quick-link-subtext">Submit & View</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/bus-season-requests" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-success border-opacity-25 hover-lift quick-link-card">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center quick-link-icon">
                                        <i class="fas fa-bus text-success"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark quick-link-text">Bus Season Request</div>
                                    <div class="small text-muted quick-link-subtext">Season Ticket</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/student/profile" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-primary border-opacity-25 hover-lift quick-link-card">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center quick-link-icon">
                                        <i class="fas fa-user text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark quick-link-text">My Profile</div>
                                    <div class="small text-muted quick-link-subtext">View & Edit</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/student/attendance" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-primary border-opacity-25 hover-lift quick-link-card">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center quick-link-icon">
                                        <i class="fas fa-calendar-check text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark quick-link-text">Attendance</div>
                                    <div class="small text-muted quick-link-subtext">View Calendar</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/group-timetable/student-view" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-info border-opacity-25 hover-lift quick-link-card">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 rounded-circle p-3 d-flex align-items-center justify-content-center quick-link-icon">
                                        <i class="fas fa-calendar-alt text-info"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold text-dark quick-link-text">Timetable</div>
                                    <div class="small text-muted quick-link-subtext">View Schedule</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-2 g-md-3 g-lg-4 mb-3 mb-md-4">
        <div class="col-6 col-md-3">
            <div class="student-dashboard-card p-3 p-md-4">
                <div class="text-center">
                    <div class="stat-value"><?php echo $attendancePercentage; ?>%</div>
                    <div class="stat-label mt-2">Attendance Rate</div>
                    <div class="small text-muted mt-1">This Month</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="student-dashboard-card p-3 p-md-4">
                <div class="text-center">
                    <div class="stat-value text-success"><?php echo $presentDays; ?></div>
                    <div class="stat-label mt-2">Present Days</div>
                    <div class="small text-muted mt-1">Out of <?php echo $totalDays; ?> days</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="student-dashboard-card p-3 p-md-4">
                <div class="text-center">
                    <div class="stat-value text-danger"><?php echo $absentDays; ?></div>
                    <div class="stat-label mt-2">Absent Days</div>
                    <div class="small text-muted mt-1">This Month</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="student-dashboard-card p-3 p-md-4">
                <div class="text-center">
                    <div class="stat-value text-warning"><?php echo $holidayDays; ?></div>
                    <div class="stat-label mt-2">Holidays</div>
                    <div class="small text-muted mt-1">This Month</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Info Row -->
    <div class="row g-2 g-md-3 g-lg-4 mb-3 mb-md-4 quick-info-row">
        <div class="col-12 col-md-6">
            <div class="student-dashboard-card p-3 p-md-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-info-circle me-2" style="color: var(--student-primary);"></i>Quick Information
                </h5>
                <div class="row g-2 g-md-3">
                    <div class="col-6 col-sm-6">
                        <div class="small text-muted mb-1">Student ID</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($student['student_id']); ?></div>
                    </div>
                    <div class="col-6 col-sm-6">
                        <div class="small text-muted mb-1">Email</div>
                        <div class="fw-semibold small text-break"><?php echo htmlspecialchars($student['student_email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="col-6 col-sm-6">
                        <div class="small text-muted mb-1">Status</div>
                        <span class="badge bg-<?php echo $student['student_status'] === 'Active' ? 'success' : 'warning'; ?>">
                            <?php echo htmlspecialchars($student['student_status'] ?? 'Active'); ?>
                        </span>
                    </div>
                    <?php if ($currentEnrollment): ?>
                    <div class="col-6 col-sm-6">
                        <div class="small text-muted mb-1">Academic Year</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($currentEnrollment['academic_year'] ?? 'N/A'); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="student-dashboard-card p-3 p-md-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-bed me-2" style="color: var(--student-primary);"></i>Hostel Information
                </h5>
                <?php if ($hostelAllocation): ?>
                    <div class="row g-2 g-md-3">
                        <div class="col-6">
                            <div class="small text-muted mb-1">Hostel</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($hostelAllocation['hostel_name'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted mb-1">Room</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($hostelAllocation['room_no'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0 small">No hostel allocation</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Attendance Calendar View -->
    <div class="student-dashboard-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2 calendar-header">
            <h5 class="fw-bold mb-0">
                <i class="fas fa-calendar-check me-2" style="color: var(--student-primary);"></i>Attendance Calendar - <?php echo date('F Y', strtotime($currentMonth . '-01')); ?>
            </h5>
            <a href="<?php echo APP_URL; ?>/student/attendance" class="btn btn-sm btn-primary">
                View Full Calendar <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="calendar-container">
            <?php
            // Generate calendar days for the month
            $firstDay = strtotime($currentMonth . '-01');
            $daysInMonth = date('t', $firstDay);
            $firstDayOfWeek = date('w', $firstDay);
            
            // Calendar header
            $weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            ?>
            <div class="row g-1 g-md-2">
                <?php foreach ($weekDays as $day): ?>
                    <div class="col text-center fw-bold small text-muted pb-2"><?php echo $day; ?></div>
                <?php endforeach; ?>
                
                <?php
                // Empty cells for days before month starts
                for ($i = 0; $i < $firstDayOfWeek; $i++) {
                    echo '<div class="col"></div>';
                }
                
                // Calendar days
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $currentMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $dayOfWeek = date('w', strtotime($date));
                    $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
                    
                    $status = $attendanceRecords[$date] ?? null;
                    $class = 'attendance-empty';
                    $icon = '';
                    
                    if ($isWeekend) {
                        $class = 'attendance-weekend';
                    } elseif ($status === 1) {
                        $class = 'attendance-present';
                        $icon = '<i class="fas fa-check"></i>';
                    } elseif ($status === 0) {
                        $class = 'attendance-absent';
                        $icon = '<i class="fas fa-times"></i>';
                    } elseif ($status === -1) {
                        $class = 'attendance-holiday';
                        $icon = '<i class="fas fa-star"></i>';
                    }
                    
                    echo '<div class="col">';
                    echo '<div class="attendance-calendar-day ' . $class . '" title="' . date('l, F j, Y', strtotime($date)) . '">';
                    echo $icon . ' ' . $day;
                    echo '</div>';
                    echo '</div>';
                }
                
                // Fill remaining cells to complete week
                $remainingDays = 7 - (($firstDayOfWeek + $daysInMonth) % 7);
                if ($remainingDays < 7) {
                    for ($i = 0; $i < $remainingDays; $i++) {
                        echo '<div class="col"></div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="mt-3 mt-md-4 d-flex flex-wrap gap-2 gap-md-3 justify-content-center">
            <div class="d-flex align-items-center">
                <div class="attendance-calendar-day attendance-present me-2">
                    <i class="fas fa-check"></i>
                </div>
                <small>Present</small>
            </div>
            <div class="d-flex align-items-center">
                <div class="attendance-calendar-day attendance-absent me-2">
                    <i class="fas fa-times"></i>
                </div>
                <small>Absent</small>
            </div>
            <div class="d-flex align-items-center">
                <div class="attendance-calendar-day attendance-holiday me-2">
                    <i class="fas fa-star"></i>
                </div>
                <small>Holiday</small>
            </div>
            <div class="d-flex align-items-center">
                <div class="attendance-calendar-day attendance-weekend me-2"></div>
                <small>Weekend</small>
            </div>
        </div>
    </div>
</div>

<!-- Code of Conduct Acceptance Modal -->
<?php if (!$hasAcceptedConduct): ?>
<div class="modal fade" id="conductModal" tabindex="-1" aria-labelledby="conductModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="conductModalLabel">
                    <i class="fas fa-file-contract me-2"></i>SLGTI STUDENT CODE OF CONDUCT
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Important:</strong> Please read the following declaration carefully before accepting.
                </div>
                
                <div class="border rounded p-4 mb-3" style="background-color: #f8f9fa; max-height: 400px; overflow-y: auto;">
                    <h6 class="fw-bold mb-3">SLGTI Student Code of Conduct and Honor</h6>
                    <p class="text-justify">
                        I hereby confirm that I have read, understood, and agreed to comply with the SLGTI Student Code of Conduct and Honor, including all rules, regulations, policies, and procedures of the Sri Lanka–German Training Institute (SLGTI). I acknowledge my responsibility to maintain discipline, academic integrity, professional conduct, and respect for all members of the SLGTI community and its property. I understand that this Code applies to my conduct on campus, off campus, and during all SLGTI-authorized activities, including industrial training and On-the-Job Training (OJT). I further understand that any violation of this Code may result in disciplinary action in accordance with SLGTI regulations, including warnings, suspension, or expulsion. By submitting this declaration electronically, I confirm that this acceptance is legally binding and equivalent to my handwritten signature.
                    </p>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="agreeCheckbox" required>
                    <label class="form-check-label" for="agreeCheckbox">
                        <strong>I agree to the SLGTI Student Code of Conduct and Honor</strong>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="acceptConductBtn" disabled>
                    <i class="fas fa-check me-2"></i>Accept & Continue
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show modal on page load
    const conductModal = new bootstrap.Modal(document.getElementById('conductModal'));
    conductModal.show();
    
    // Enable/disable accept button based on checkbox
    const agreeCheckbox = document.getElementById('agreeCheckbox');
    const acceptBtn = document.getElementById('acceptConductBtn');
    
    agreeCheckbox.addEventListener('change', function() {
        acceptBtn.disabled = !this.checked;
    });
    
    // Handle acceptance
    acceptBtn.addEventListener('click', function() {
        if (!agreeCheckbox.checked) {
            alert('Please check the agreement box to continue.');
            return;
        }
        
        // Disable button and show loading
        acceptBtn.disabled = true;
        const originalText = acceptBtn.innerHTML;
        acceptBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        // Send AJAX request
        fetch('<?php echo APP_URL; ?>/student/accept-conduct', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                conductModal.hide();
                
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    ${data.message || 'Code of conduct accepted successfully!'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
                
                // Remove modal from DOM after hiding
                setTimeout(() => {
                    const modalElement = document.getElementById('conductModal');
                    if (modalElement) {
                        modalElement.remove();
                    }
                }, 300);
            } else {
                alert('Error: ' + (data.error || 'Failed to accept code of conduct. Please try again.'));
                acceptBtn.disabled = false;
                acceptBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            acceptBtn.disabled = false;
            acceptBtn.innerHTML = originalText;
        });
    });
    
    // Prevent closing modal by clicking outside or pressing ESC
    document.getElementById('conductModal').addEventListener('hide.bs.modal', function(e) {
        if (!agreeCheckbox.checked || !document.getElementById('acceptConductBtn').disabled) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
});
</script>
<?php endif; ?>

