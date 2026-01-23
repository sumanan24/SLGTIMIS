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
    }
    
    @media (max-width: 576px) {
        .stat-value {
            font-size: 1.75rem;
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
        }
    }
</style>

<div class="container-fluid px-3 px-md-4">
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
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/on-peak-requests" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-primary border-opacity-25 hover-lift">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-calendar-alt text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">On-Peak Requests</div>
                                    <div class="small text-muted">Submit & View</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/bus-season-requests" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-success border-opacity-25 hover-lift">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-bus text-success fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">Bus Season Request</div>
                                    <div class="small text-muted">Season Ticket</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/student/profile" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-primary border-opacity-25 hover-lift">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-user text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">My Profile</div>
                                    <div class="small text-muted">View & Edit</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/student/attendance" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-primary border-opacity-25 hover-lift">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-calendar-check text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">Attendance</div>
                                    <div class="small text-muted">View Calendar</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?php echo APP_URL; ?>/group-timetable/student-view" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-2 border-info border-opacity-25 hover-lift">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                        <i class="fas fa-calendar-alt text-info fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">Timetable</div>
                                    <div class="small text-muted">View Schedule</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <div class="col-md-3">
            <div class="student-dashboard-card p-4">
                <div class="text-center">
                    <div class="stat-value"><?php echo $attendancePercentage; ?>%</div>
                    <div class="stat-label mt-2">Attendance Rate</div>
                    <div class="small text-muted mt-1">This Month</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="student-dashboard-card p-4">
                <div class="text-center">
                    <div class="stat-value text-success"><?php echo $presentDays; ?></div>
                    <div class="stat-label mt-2">Present Days</div>
                    <div class="small text-muted mt-1">Out of <?php echo $totalDays; ?> days</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="student-dashboard-card p-4">
                <div class="text-center">
                    <div class="stat-value text-danger"><?php echo $absentDays; ?></div>
                    <div class="stat-label mt-2">Absent Days</div>
                    <div class="small text-muted mt-1">This Month</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="student-dashboard-card p-4">
                <div class="text-center">
                    <div class="stat-value text-warning"><?php echo $holidayDays; ?></div>
                    <div class="stat-label mt-2">Holidays</div>
                    <div class="small text-muted mt-1">This Month</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Info Row -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <div class="col-12 col-md-6">
            <div class="student-dashboard-card p-3 p-md-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-info-circle me-2" style="color: var(--student-primary);"></i>Quick Information
                </h5>
                <div class="row g-3">
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
                    <div class="row g-3">
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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
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

