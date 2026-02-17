<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-chart-line me-2"></i>Attendance Report
                        </h5>
                        <div class="d-flex gap-2">
                            <?php if (!empty($reportData) && isset($isMonthLocked) && $isMonthLocked): ?>
                                <span class="badge bg-danger d-flex align-items-center" style="height: 38px;">
                                    <i class="fas fa-lock me-1"></i>Month Locked
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($reportData) && isset($isMonthLocked) && $isMonthLocked && !empty($selectedDepartment) && !empty($selectedMonth)): ?>
                                <a href="<?php echo APP_URL; ?>/attendance/export-report?<?php echo http_build_query([
                                    'department_id' => $selectedDepartment,
                                    'course_id' => $selectedCourse,
                                    'academic_year' => $selectedAcademicYear,
                                    'month' => $selectedMonth
                                ]); ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel me-1"></i>Export to Excel
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($reportData) && isset($isHOD) && $isHOD && !empty($selectedDepartment) && !empty($selectedMonth) && (!isset($isMonthLocked) || !$isMonthLocked)): ?>
                                <button type="button" class="btn btn-warning btn-sm" id="lockMonthBtn" data-department-id="<?php echo htmlspecialchars($selectedDepartment); ?>" data-month="<?php echo htmlspecialchars($selectedMonth); ?>">
                                    <i class="fas fa-lock me-1"></i>Lock This Month
                                </button>
                            <?php endif; ?>
                            <?php if (!empty($reportData) && isset($isAdmin) && $isAdmin && isset($isMonthLocked) && $isMonthLocked && !empty($selectedDepartment) && !empty($selectedMonth)): ?>
                                <button type="button" class="btn btn-success btn-sm" id="unlockMonthBtn" data-department-id="<?php echo htmlspecialchars($selectedDepartment); ?>" data-month="<?php echo htmlspecialchars($selectedMonth); ?>">
                                    <i class="fas fa-unlock me-1"></i>Unlock Month
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="<?php echo APP_URL; ?>/attendance/report" class="mb-4">
                        <div class="row g-3">
                            <?php if (!isset($isHOD) || !$isHOD): ?>
                            <div class="col-md-3">
                                <label for="department_id" class="form-label fw-semibold">Department</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">All Departments</option>
                                    <?php if (isset($departments) && !empty($departments)): ?>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept['department_id']); ?>" 
                                                    <?php echo ($selectedDepartment ?? '') == $dept['department_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['department_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <?php else: ?>
                                <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($selectedDepartment ?? ''); ?>">
                            <?php endif; ?>
                            
                            <div class="col-md-3">
                                <label for="course_id" class="form-label fw-semibold">Course</label>
                                <select class="form-select" id="course_id" name="course_id">
                                    <option value="">All Courses</option>
                                    <?php if (isset($courses) && !empty($courses)): ?>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo htmlspecialchars($course['course_id']); ?>" 
                                                    <?php echo ($selectedCourse ?? '') == $course['course_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="academic_year" class="form-label fw-semibold">Academic Year</label>
                                <select class="form-select" id="academic_year" name="academic_year">
                                    <option value="">All Years</option>
                                    <?php if (isset($academicYears) && !empty($academicYears)): ?>
                                        <?php foreach ($academicYears as $year): ?>
                                            <option value="<?php echo htmlspecialchars($year); ?>" 
                                                    <?php echo ($selectedAcademicYear ?? '') == $year ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($year); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="month" class="form-label fw-semibold">Month</label>
                                <input type="month" class="form-control" id="month" name="month" 
                                       value="<?php echo htmlspecialchars($selectedMonth ?? date('Y-m')); ?>" required>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">&nbsp;</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="eligible_only" name="eligible_only" value="1"
                                           <?php echo (isset($eligibleOnly) && $eligibleOnly) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="eligible_only">
                                        Eligible Students Only
                                    </label>
                                </div>
                                <div class="form-text small">Show only allowance eligible students</div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Generate Report
                                </button>
                                <a href="<?php echo APP_URL; ?>/attendance/report" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                    
                        <?php if (isset($error) && !empty($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($message) && !empty($message)): ?>
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?php echo htmlspecialchars($message); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($isFIN) && $isFIN && (!isset($isMonthLocked) || !$isMonthLocked) && !empty($selectedDepartment) && !empty($selectedMonth)): ?>
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <div>
                                    <strong>Access Restricted:</strong> Attendance reports can only be viewed after HOD approval and monthly lock. Please wait until the month is locked.
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)): ?>
                            <div class="alert alert-warning d-flex align-items-center">
                                <i class="fas fa-lock me-2"></i>
                                <div>
                                    <strong>Month Locked:</strong> Attendance for this month has been locked.
                                    <?php if (isset($lockStatus) && !empty($lockStatus['locked_by_name'])): ?>
                                        <br><small>Locked by: <?php echo htmlspecialchars($lockStatus['locked_by_name']); ?> 
                                        on <?php echo date('Y-m-d H:i:s', strtotime($lockStatus['locked_at'])); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($reportData)): ?>
                        
                        <!-- Report Table with Day-by-Day Attendance -->
                        <div class="table-responsive" style="max-height: 80vh; overflow-x: auto; overflow-y: auto;">
                            <table class="table table-bordered table-striped" style="font-size: 0.85rem;">
                                <thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th rowspan="2" style="position: sticky; left: 0; z-index: 11; background: #212529; min-width: 120px;">Student ID</th>
                                        <th rowspan="2" style="position: sticky; left: 120px; z-index: 11; background: #212529; min-width: 150px;">Full Name</th>
                                        <th rowspan="2" style="position: sticky; left: 270px; z-index: 11; background: #212529; min-width: 100px;">NIC</th>
                                        <th rowspan="2" style="position: sticky; left: 370px; z-index: 11; background: #212529; min-width: 100px;">Bank Name</th>
                                        <th rowspan="2" style="position: sticky; left: 470px; z-index: 11; background: #212529; min-width: 120px;">Account No</th>
                                        <th rowspan="2" style="position: sticky; left: 590px; z-index: 11; background: #212529; min-width: 100px;">Branch</th>
                                        <?php if (!empty($allDays)): ?>
                                            <?php foreach ($allDays as $day): ?>
                                                <th class="text-center" style="min-width: 50px; writing-mode: vertical-rl; text-orientation: mixed;">
                                                    <?php echo htmlspecialchars($day['day']); ?>
                                                </th>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <th rowspan="2" class="text-center" style="min-width: 70px;">Total Days</th>
                                        <th rowspan="2" class="text-center" style="min-width: 60px;">P</th>
                                        <th rowspan="2" class="text-center" style="min-width: 60px;">%</th>
                                        <th rowspan="2" class="text-center" style="min-width: 80px;">Allowance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $student): ?>
                                        <tr>
                                            <td style="position: sticky; left: 0; z-index: 9; background: #ffffff;"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td style="position: sticky; left: 120px; z-index: 9; background: #ffffff;"><?php echo htmlspecialchars($student['student_fullname']); ?></td>
                                            <td style="position: sticky; left: 270px; z-index: 9; background: #ffffff;"><?php echo htmlspecialchars($student['student_nic']); ?></td>
                                            <td style="position: sticky; left: 370px; z-index: 9; background: #ffffff;"><?php echo htmlspecialchars($student['bank_name'] ?? '-'); ?></td>
                                            <td style="position: sticky; left: 470px; z-index: 9; background: #ffffff;"><?php echo htmlspecialchars($student['bank_account_no'] ?? '-'); ?></td>
                                            <td style="position: sticky; left: 590px; z-index: 9; background: #ffffff;"><?php echo htmlspecialchars($student['bank_branch'] ?? '-'); ?></td>
                                            <?php if (!empty($allDays)): ?>
                                                <?php foreach ($allDays as $day): ?>
                                                    <?php 
                                                    $status = $student['day_by_day'][$day['date']] ?? '';
                                                    $class = '';
                                                    $statusValue = '';
                                                    
                                                    if ($status == 'P') {
                                                        $class = 'bg-success text-white';
                                                        $statusValue = '1'; // Present = 1
                                                    } elseif ($status == 'A') {
                                                        $class = 'bg-danger text-white';
                                                        $statusValue = '0'; // Absent = 0
                                                    } elseif ($status == 'H') {
                                                        $class = 'bg-warning text-dark';
                                                        $statusValue = ''; // Holiday - no text, only color
                                                    } else {
                                                        $statusValue = ''; // Not marked = empty
                                                    }
                                                    ?>
                                                    <td class="text-center <?php echo $class; ?>" style="min-width: 60px; padding: 0.5rem;">
                                                        <div style="font-size: 1.2rem; font-weight: bold;">
                                                            <?php echo $statusValue; ?>
                                                        </div>
                                                    </td>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <td class="text-center fw-bold"><?php echo $student['effective_working_days']; ?></td>
                                            <td class="text-center fw-bold"><?php echo $student['present_days']; ?></td>
                                            <td class="text-center">
                                                <span class="badge <?php 
                                                    echo $student['attendance_percentage'] >= 90 ? 'bg-success' : 
                                                        ($student['attendance_percentage'] >= 75 ? 'bg-warning' : 'bg-danger'); 
                                                ?>">
                                                    <?php echo number_format($student['attendance_percentage'], 1); ?>%
                                                </span>
                                            </td>
                                            <td class="text-center fw-bold">
                                                Rs. <?php echo number_format($student['allowance'], 0); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!empty($reportData)): ?>
                                        <tr class="table-secondary fw-bold">
                                            <td colspan="<?php echo 6 + count($allDays); ?>" class="text-end">Total:</td>
                                            <td class="text-center"><?php echo number_format($summary['total_effective_working_days'] ?? 0, 0); ?></td>
                                            <td class="text-center"><?php echo number_format($summary['total_present'] ?? 0, 0); ?></td>
                                            <td class="text-center">-</td>
                                            <td class="text-center">Rs. <?php echo number_format($summary['total_allowance'] ?? 0, 0); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Legend:</strong> 
                                        <span class="badge bg-success text-white">1</span> = Present, 
                                        <span class="badge bg-danger text-white">0</span> = Absent, 
                                        <span class="badge bg-warning text-dark" style="min-width: 20px;">&nbsp;</span> = Holiday,
                                        <span class="badge bg-light text-dark">Empty</span> = Not Marked
                                    </small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small class="text-muted">
                                        <strong>Note:</strong> Weekends (Saturday & Sunday) are excluded from working days
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Please select filters and click "Generate Report" to view attendance data.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load courses when department is selected
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');
    
    if (departmentSelect && courseSelect) {
        departmentSelect.addEventListener('change', function() {
            const departmentId = this.value;
            
            if (departmentId) {
                // Fetch courses for selected department
                fetch('<?php echo APP_URL; ?>/courses/get-by-department?department_id=' + encodeURIComponent(departmentId))
                    .then(response => response.json())
                    .then(data => {
                        courseSelect.innerHTML = '<option value="">All Courses</option>';
                        if (data.success && data.courses) {
                            data.courses.forEach(function(course) {
                                const option = document.createElement('option');
                                option.value = course.course_id;
                                option.textContent = course.course_name;
                                courseSelect.appendChild(option);
                            });
                        }
                        courseSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading courses:', error);
                        courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                    });
            } else {
                courseSelect.innerHTML = '<option value="">All Courses</option>';
                courseSelect.disabled = false;
            }
        });
    }
    
    // Lock Month Button (HOD only)
    const lockMonthBtn = document.getElementById('lockMonthBtn');
    if (lockMonthBtn) {
        lockMonthBtn.addEventListener('click', function() {
            const departmentId = this.dataset.departmentId;
            const month = this.dataset.month;
            
            if (!confirm('Are you sure you want to lock attendance for this month?')) {
                return;
            }
            
            fetch('<?php echo APP_URL; ?>/attendance/lock-month', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'department_id=' + encodeURIComponent(departmentId) + '&month=' + encodeURIComponent(month)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while locking the month.');
            });
        });
    }
    
    // Unlock Month Button (Admin only)
    const unlockMonthBtn = document.getElementById('unlockMonthBtn');
    if (unlockMonthBtn) {
        unlockMonthBtn.addEventListener('click', function() {
            const departmentId = this.dataset.departmentId;
            const month = this.dataset.month;
            
            if (!confirm('Are you sure you want to unlock attendance for this month?\n\nAfter unlocking, attendance can be modified again.')) {
                return;
            }
            
            fetch('<?php echo APP_URL; ?>/attendance/unlock-month', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'department_id=' + encodeURIComponent(departmentId) + '&month=' + encodeURIComponent(month)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while unlocking the month.');
            });
        });
    }
});
</script>

