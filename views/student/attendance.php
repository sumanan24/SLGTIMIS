<style>
    .attendance-calendar {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .calendar-header {
        background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-dark) 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 1.25rem 1.5rem;
    }
    
    .calendar-weekdays {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 0.75rem 0;
    }
    
    .calendar-weekday {
        font-weight: 600;
        color: #495057;
        text-align: center;
        padding: 0.5rem;
        font-size: 0.875rem;
    }
    
    .calendar-day {
        border: 1px solid #e9ecef;
        min-height: 80px;
        padding: 0.5rem;
        transition: all 0.2s ease;
        position: relative;
    }
    
    .calendar-day:hover {
        background-color: #f8f9fa;
        transform: scale(1.02);
        z-index: 1;
    }
    
    .calendar-day.weekend {
        background-color: #f8f9fa;
    }
    
    .calendar-day.empty {
        background-color: #ffffff;
    }
    
    .day-number {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    
    .day-status {
        font-size: 1.5rem;
        text-align: center;
        margin-top: 0.25rem;
    }
    
    .day-status.present {
        color: #28a745;
    }
    
    .day-status.absent {
        color: #dc3545;
    }
    
    .day-status.holiday {
        color: #ffc107;
    }
    
    .attendance-stats-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 1.25rem 1.5rem;
        text-align: center;
        border-left: 4px solid var(--student-primary);
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--student-primary);
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .calendar-header {
            padding: 1rem;
        }
        
        .calendar-header h4 {
            font-size: 1.25rem;
        }
        
        .calendar-header .btn-group {
            flex-wrap: wrap;
            width: 100%;
            margin-top: 1rem;
        }
        
        .calendar-header .btn-group .btn {
            flex: 1;
            min-width: 120px;
            margin: 0.25rem;
        }
        
        .calendar-day {
            min-height: 60px;
            padding: 0.375rem;
        }
        
        .day-number {
            font-size: 0.8rem;
        }
        
        .day-status {
            font-size: 1.25rem;
        }
        
        .attendance-stats-card {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
        }
        
        .calendar-days {
            overflow-x: auto;
        }
    }
    
    @media (max-width: 576px) {
        .calendar-header {
            padding: 0.75rem;
        }
        
        .calendar-header h4 {
            font-size: 1.1rem;
        }
        
        .calendar-header small {
            font-size: 0.75rem;
        }
        
        .calendar-header .btn-group {
            flex-direction: column;
            width: 100%;
        }
        
        .calendar-header .btn-group .btn {
            width: 100%;
            margin: 0.25rem 0;
        }
        
        .calendar-day {
            min-height: 50px;
            padding: 0.25rem;
        }
        
        .day-number {
            font-size: 0.75rem;
        }
        
        .day-status {
            font-size: 1rem;
        }
        
        .calendar-weekday {
            font-size: 0.7rem;
            padding: 0.375rem 0.25rem;
        }
        
        .attendance-stats-card {
            padding: 0.875rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
        }
        
        .stat-label {
            font-size: 0.7rem;
        }
    }
</style>

<div class="container-fluid px-3 px-md-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 mb-md-4 gap-2">
        <h3 class="fw-bold mb-0">
            <i class="fas fa-calendar-check me-2" style="color: var(--student-primary);"></i>My Attendance Calendar
        </h3>
        <a href="<?php echo APP_URL; ?>/student/dashboard" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-3 g-md-4 mb-3 mb-md-4">
        <div class="col-md-3">
            <div class="attendance-stats-card">
                <div class="stat-value text-primary"><?php echo $stats['attendancePercentage']; ?>%</div>
                <div class="stat-label">Attendance Rate</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="attendance-stats-card">
                <div class="stat-value text-success"><?php echo $stats['presentDays']; ?></div>
                <div class="stat-label">Present Days</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="attendance-stats-card">
                <div class="stat-value text-danger"><?php echo $stats['absentDays']; ?></div>
                <div class="stat-label">Absent Days</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="attendance-stats-card">
                <div class="stat-value text-warning"><?php echo $stats['holidayDays']; ?></div>
                <div class="stat-label">Holidays</div>
            </div>
        </div>
    </div>
    
    <!-- Calendar -->
    <div class="attendance-calendar mb-4">
        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                <div>
                    <h4 class="mb-1 mb-md-0 fw-bold"><?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></h4>
                    <small class="opacity-75">Total Working Days: <?php echo $stats['totalDays']; ?></small>
                </div>
                <div class="btn-group flex-nowrap">
                    <a href="?month=<?php echo $prevMonth; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-chevron-left"></i> <span class="d-none d-sm-inline">Previous</span>
                    </a>
                    <a href="?month=<?php echo date('Y-m'); ?>" class="btn btn-light btn-sm">
                        <span class="d-none d-md-inline">This Month</span>
                        <span class="d-md-none">Today</span>
                    </a>
                    <a href="?month=<?php echo $nextMonth; ?>" class="btn btn-light btn-sm">
                        <span class="d-none d-sm-inline">Next </span><i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Weekday Headers -->
        <div class="calendar-weekdays">
            <div class="row g-0">
                <?php
                $weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                foreach ($weekDays as $day): ?>
                    <div class="col calendar-weekday">
                        <?php echo substr($day, 0, 3); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Calendar Days -->
        <div class="calendar-days" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <div style="min-width: 100%;">
                <?php foreach ($calendarData as $week): ?>
                    <div class="row g-0">
                        <?php foreach ($week as $dayData): ?>
                        <?php
                        $day = $dayData['day'];
                        $date = $dayData['date'];
                        $status = $dayData['status'];
                        $isWeekend = $dayData['isWeekend'];
                        $dayName = $dayData['dayName'] ?? '';
                        
                        $dayClass = 'empty';
                        $statusIcon = '';
                        $statusText = 'No Record';
                        $statusClass = '';
                        
                        if ($day === null) {
                            // Empty cell
                            $dayClass = 'empty';
                        } elseif ($isWeekend) {
                            $dayClass = 'weekend';
                            $statusText = 'Weekend';
                        } elseif ($status === 1) {
                            $dayClass = '';
                            $statusIcon = '<i class="fas fa-check-circle"></i>';
                            $statusText = 'Present';
                            $statusClass = 'present';
                        } elseif ($status === 0) {
                            $dayClass = '';
                            $statusIcon = '<i class="fas fa-times-circle"></i>';
                            $statusText = 'Absent';
                            $statusClass = 'absent';
                        } elseif ($status === -1) {
                            $dayClass = '';
                            $statusIcon = '<i class="fas fa-star"></i>';
                            $statusText = 'Holiday';
                            $statusClass = 'holiday';
                        }
                        ?>
                        <div class="col calendar-day <?php echo $dayClass; ?>" 
                             title="<?php echo $date ? date('l, F j, Y', strtotime($date)) . ' - ' . $statusText : ''; ?>">
                            <?php if ($day !== null): ?>
                                <div class="day-number"><?php echo $day; ?></div>
                                <?php if ($statusIcon): ?>
                                    <div class="day-status <?php echo $statusClass; ?>">
                                        <?php echo $statusIcon; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($isWeekend): ?>
                                    <small class="text-muted d-block text-center mt-1 d-none d-md-block">Weekend</small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">
            <h6 class="fw-bold mb-3">Legend</h6>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success fa-lg fa-2x me-2 me-md-3"></i>
                        <div>
                            <div class="fw-semibold small">Present</div>
                            <small class="text-muted d-none d-md-block">Student was present</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle text-danger fa-lg fa-2x me-2 me-md-3"></i>
                        <div>
                            <div class="fw-semibold small">Absent</div>
                            <small class="text-muted d-none d-md-block">Student was absent</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-star text-warning fa-lg fa-2x me-2 me-md-3"></i>
                        <div>
                            <div class="fw-semibold small">Holiday</div>
                            <small class="text-muted d-none d-md-block">Public holiday</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-light border rounded p-2 me-2 me-md-3">
                            <small class="text-muted">Weekend</small>
                        </div>
                        <div>
                            <div class="fw-semibold small">Weekend</div>
                            <small class="text-muted d-none d-md-block">Saturday or Sunday</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

