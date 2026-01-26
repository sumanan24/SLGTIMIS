<div class="container-fluid px-4 py-3">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-check me-2"></i>Student Attendance Management</h5>
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
                    
                    <!-- Filters -->
                    <form method="GET" action="<?php echo APP_URL; ?>/attendance" id="filterForm" class="mb-4">
                        <div class="row g-3">
                            <?php 
                            // Get HOD's department if user is HOD
                            $isHODAtt = false;
                            $hodDeptIdAtt = '';
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModelAtt = new UserModel();
                                $isHODAtt = $userModelAtt->isHOD($_SESSION['user_id']);
                                if ($isHODAtt) {
                                    $hodDeptIdAtt = $userModelAtt->getHODDepartment($_SESSION['user_id']);
                                }
                            }
                            ?>
                            <?php if (!$isHODAtt): ?>
                            <div class="col-md-3">
                                <label for="department_id" class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
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
                                <input type="hidden" name="department_id" id="department_id" value="<?php echo htmlspecialchars($hodDeptIdAtt); ?>">
                            <?php endif; ?>
                            
                            <div class="col-md-3">
                                <label for="course_id" class="form-label fw-semibold">Course <span class="text-danger">*</span></label>
                                <select class="form-select" id="course_id" name="course_id" required <?php echo empty($selectedDepartment) ? 'disabled' : ''; ?>>
                                    <option value="">Select Course</option>
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
                                <label for="academic_year" class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                                <select class="form-select" id="academic_year" name="academic_year" required>
                                    <option value="">Select Year</option>
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
                                <label for="group" class="form-label fw-semibold">Group</label>
                                <select class="form-select" id="group" name="group" <?php echo (empty($selectedCourse) || empty($selectedAcademicYear)) ? 'disabled' : ''; ?>>
                                    <option value="">Select Group (Optional)</option>
                                    <?php 
                                    // Load groups if course and academic year are selected
                                    if (isset($groups) && !empty($groups)) {
                                        foreach ($groups as $groupItem) {
                                            $selected = ($selectedGroup ?? '') == $groupItem['id'] ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($groupItem['id']) . '" ' . $selected . '>' . htmlspecialchars($groupItem['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Load Students
                                </button>
                                <a href="<?php echo APP_URL; ?>/attendance" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                    
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
                    
                    <?php if (!empty($students)): ?>
                        <hr class="my-4">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">
                                <i class="fas fa-users me-2"></i>
                                Students List (<?php echo count($students); ?> students)
                            </h6>
                            <?php if (!isset($isMonthLocked) || !$isMonthLocked || (isset($isAdmin) && $isAdmin)): ?>
                            <div>
                                <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()">
                                    <i class="fas fa-check me-1"></i>Mark All Present
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="markAllAbsent()">
                                    <i class="fas fa-times me-1"></i>Mark All Absent
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <form id="attendanceForm" method="POST" action="<?php echo APP_URL; ?>/attendance/bulk-update">
                            <input type="hidden" name="module_name" value="General">
                            <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($selectedDepartment ?? ''); ?>">
                            <input type="hidden" name="month" value="<?php echo htmlspecialchars($selectedMonth ?? date('Y-m')); ?>">
                            
                            <div class="table-responsive" style="max-height: 70vh; overflow-x: auto; overflow-y: auto;">
                                <table class="table table-bordered align-middle mb-0" id="attendanceTable">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10; background: #f8f9fa;">
                                        <tr>
                                            <th class="fw-bold student-col" style="width: 250px; min-width: 250px; max-width: 250px; padding: 0.5rem; position: sticky; left: 0; z-index: 11; background: #f8f9fa;">
                                                <div>Student ID / Name</div>
                                                <div style="height: 60px;"></div>
                                            </th>
                                            <?php foreach ($calendarDays as $day): ?>
                                                <th class="text-center fw-bold date-header" style="min-width: 100px;" data-date="<?php echo $day['date']; ?>">
                                                    <div><?php echo htmlspecialchars($day['day']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($day['day_name']); ?></div>
                                                    <div class="mt-1">
                                                        <label class="form-check-label small d-block" style="cursor: pointer;">
                                                            <input type="checkbox" 
                                                                   class="form-check-input holiday-date-checkbox" 
                                                                   data-date="<?php echo $day['date']; ?>"
                                                                   id="holiday_date_<?php echo str_replace(['-'], '_', $day['date']); ?>"
                                                                   onchange="toggleHolidayForDate('<?php echo $day['date']; ?>', this.checked)"
                                                                   <?php echo (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)) ? 'disabled' : ''; ?>
                                                                   style="cursor: pointer; margin-right: 0.25rem;">
                                                            Holiday
                                                        </label>
                                                    </div>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td class="fw-semibold student-col" style="width: 250px; min-width: 250px; max-width: 250px; padding: 0.5rem; position: sticky; left: 0; z-index: 9; background: #ffffff;">
                                                    <div><?php echo htmlspecialchars($student['student_id']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($student['student_fullname']); ?></div>
                                                </td>
                                                <?php 
                                                $studentAttendance = $attendanceData[$student['student_id']] ?? [];
                                                foreach ($calendarDays as $day): 
                                                    $date = $day['date'];
                                                    $currentStatus = $studentAttendance[$date] ?? null;
                                                ?>
                                                    <td class="text-center">
                                                        <?php 
                                                        $uniqueId = str_replace(['-', '/'], '_', $student['student_id'] . '_' . $date);
                                                        $isPresent = ($currentStatus === 1);
                                                        $isHoliday = ($currentStatus === -1);
                                                        ?>
                                                        <div class="form-check d-flex justify-content-center align-items-center">
                                                            <input type="checkbox" 
                                                                   class="form-check-input attendance-checkbox <?php echo $isHoliday ? 'holiday-checkbox' : ''; ?>" 
                                                                   data-student="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                                                   data-date="<?php echo $date; ?>"
                                                                   id="attendance_<?php echo $uniqueId; ?>" 
                                                                   <?php echo $isPresent ? 'checked' : ''; ?>
                                                                   <?php echo ($isHoliday || (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin))) ? 'disabled' : ''; ?>
                                                                   style="width: 1.5rem; height: 1.5rem; cursor: pointer;">
                                                            <label class="form-check-label" 
                                                                   for="attendance_<?php echo $uniqueId; ?>"
                                                                   style="cursor: pointer; user-select: none; margin: 0;">
                                                            </label>
                                                        </div>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" 
                                        <?php echo (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)) ? 'disabled' : ''; ?>
                                        id="saveAttendanceBtn">
                                    <i class="fas fa-save me-1"></i>Save Attendance
                                </button>
                                <?php if (!isset($isMonthLocked) || !$isMonthLocked || (isset($isAdmin) && $isAdmin)): ?>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearAll()">
                                    <i class="fas fa-eraser me-1"></i>Clear All
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)): ?>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-lock me-2"></i>
                                    <strong>Month Locked:</strong> Attendance for this month has been locked.
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Please select Department, Course, and Academic Year to load students for attendance.
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
    const academicYearSelect = document.getElementById('academic_year');
    const groupSelect = document.getElementById('group');
    
    // Function to load groups
    function loadGroups() {
        const courseId = courseSelect.value;
        const academicYear = academicYearSelect.value;
        
        if (courseId && academicYear) {
            groupSelect.disabled = false;
            groupSelect.innerHTML = '<option value="">Loading groups...</option>';
            
            fetch('<?php echo APP_URL; ?>/attendance/get-groups-by-course-and-year?course_id=' + encodeURIComponent(courseId) + '&academic_year=' + encodeURIComponent(academicYear))
                .then(response => response.json())
                .then(data => {
                    groupSelect.innerHTML = '<option value="">Select Group (Optional)</option>';
                    if (data.success && data.groups) {
                        data.groups.forEach(function(group) {
                            const option = document.createElement('option');
                            option.value = group.id;
                            option.textContent = group.name;
                            groupSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading groups:', error);
                    groupSelect.innerHTML = '<option value="">Error loading groups</option>';
                });
        } else {
            groupSelect.innerHTML = '<option value="">Select Group (Optional)</option>';
            groupSelect.disabled = true;
        }
    }
    
    if (departmentSelect && courseSelect) {
        departmentSelect.addEventListener('change', function() {
            const departmentId = this.value;
            
            if (departmentId) {
                // Fetch courses for selected department
                fetch('<?php echo APP_URL; ?>/courses/get-by-department?department_id=' + encodeURIComponent(departmentId))
                    .then(response => response.json())
                    .then(data => {
                        courseSelect.innerHTML = '<option value="">Select Course</option>';
                        if (data.success && data.courses) {
                            data.courses.forEach(function(course) {
                                const option = document.createElement('option');
                                option.value = course.course_id;
                                option.textContent = course.course_name;
                                courseSelect.appendChild(option);
                            });
                        }
                        courseSelect.disabled = false;
                        // Reset groups when department changes
                        groupSelect.innerHTML = '<option value="">Select Group (Optional)</option>';
                        groupSelect.disabled = true;
                    })
                    .catch(error => {
                        console.error('Error loading courses:', error);
                        courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                    });
            } else {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                courseSelect.disabled = true;
                groupSelect.innerHTML = '<option value="">Select Group (Optional)</option>';
                groupSelect.disabled = true;
            }
        });
    }
    
    // Load groups when course or academic year changes
    if (courseSelect && academicYearSelect && groupSelect) {
        courseSelect.addEventListener('change', loadGroups);
        academicYearSelect.addEventListener('change', loadGroups);
    }
    
    // Initialize holiday checkboxes on page load
    document.querySelectorAll('.holiday-date-checkbox').forEach(function(holidayCheckbox) {
        if (holidayCheckbox.checked) {
            const date = holidayCheckbox.getAttribute('data-date');
            toggleHolidayForDate(date, true);
        }
    });
    
            // Handle form submission with AJAX
    const attendanceForm = document.getElementById('attendanceForm');
    if (attendanceForm) {
        attendanceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if month is locked (only allow admin to modify)
            const isMonthLocked = <?php echo (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)) ? 'true' : 'false'; ?>;
            if (isMonthLocked) {
                alert('Attendance for this month has been locked and cannot be modified. Please contact administrator to unlock.');
                return;
            }
            
            // Collect attendance data from all checkboxes
            const attendanceData = [];
            const holidayDates = new Set();
            
            // First, collect all holiday dates
            document.querySelectorAll('.holiday-date-checkbox:checked').forEach(function(holidayCheckbox) {
                const date = holidayCheckbox.getAttribute('data-date');
                if (date) {
                    holidayDates.add(date);
                }
            });
            
            // Then, collect attendance data from checkboxes
            document.querySelectorAll('.attendance-checkbox').forEach(function(checkbox) {
                const studentId = checkbox.getAttribute('data-student');
                const date = checkbox.getAttribute('data-date');
                
                if (studentId && date) {
                    let status;
                    
                    // If date is marked as holiday, status is -1
                    if (holidayDates.has(date)) {
                        status = -1;
                    } else {
                        // Otherwise: checked = 1 (present), unchecked = 0 (absent)
                        status = checkbox.checked ? 1 : 0;
                    }
                    
                    attendanceData.push({
                        student_id: studentId,
                        date: date,
                        status: status
                    });
                }
            });
            
            if (attendanceData.length === 0) {
                alert('Please mark attendance for at least one student.');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            
            // Get department and month for lock checking
            const departmentId = document.querySelector('select[name="department_id"]')?.value || '';
            const month = document.querySelector('input[name="month"]')?.value || '';
            
            // Submit via AJAX
            fetch('<?php echo APP_URL; ?>/attendance/bulk-update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attendance: attendanceData,
                    module_name: document.querySelector('input[name="module_name"]').value || 'General',
                    department_id: departmentId,
                    month: month
                })
            })
            .then(response => {
                // Check if response is OK
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                    });
                }
                return response.json();
            })
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    alert(data.message || 'Attendance saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to save attendance'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                alert('Error saving attendance: ' + error.message + '\n\nPlease check the browser console for more details.');
            });
        });
    }
});

// Toggle holiday for a specific date
function toggleHolidayForDate(date, isHoliday) {
    const isMonthLocked = <?php echo (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)) ? 'true' : 'false'; ?>;
    if (isMonthLocked) {
        alert('Cannot modify attendance: Month is locked. Only administrators can modify locked attendance.');
        // Revert checkbox state
        const holidayCheckbox = document.querySelector(`.holiday-date-checkbox[data-date="${date}"]`);
        if (holidayCheckbox) {
            holidayCheckbox.checked = !isHoliday;
        }
        return;
    }
    
    const checkboxes = document.querySelectorAll(`.attendance-checkbox[data-date="${date}"]`);
    
    checkboxes.forEach(function(checkbox) {
        if (isHoliday) {
            // Mark as holiday: disable checkbox and uncheck it, add holiday class
            checkbox.disabled = true;
            checkbox.checked = false;
            checkbox.classList.add('holiday-checkbox');
        } else {
            // Remove holiday: enable checkbox, remove holiday class
            checkbox.disabled = false;
            checkbox.classList.remove('holiday-checkbox');
        }
    });
}

// Mark all students present
function markAllPresent() {
    const isMonthLocked = <?php echo (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)) ? 'true' : 'false'; ?>;
    if (isMonthLocked) {
        alert('Cannot modify attendance: Month is locked. Only administrators can modify locked attendance.');
        return;
    }
    document.querySelectorAll('.attendance-checkbox:not(:disabled)').forEach(function(checkbox) {
        checkbox.checked = true;
    });
}

// Mark all students absent
function markAllAbsent() {
    const isMonthLocked = <?php echo (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)) ? 'true' : 'false'; ?>;
    if (isMonthLocked) {
        alert('Cannot modify attendance: Month is locked. Only administrators can modify locked attendance.');
        return;
    }
    document.querySelectorAll('.attendance-checkbox:not(:disabled)').forEach(function(checkbox) {
        checkbox.checked = false;
    });
}

// Clear all attendance
function clearAll() {
    const isMonthLocked = <?php echo (isset($isMonthLocked) && $isMonthLocked && (!isset($isAdmin) || !$isAdmin)) ? 'true' : 'false'; ?>;
    if (isMonthLocked) {
        alert('Cannot modify attendance: Month is locked. Only administrators can modify locked attendance.');
        return;
    }
    
    if (confirm('Are you sure you want to clear all attendance marks?')) {
        // Uncheck all holiday checkboxes first
        document.querySelectorAll('.holiday-date-checkbox').forEach(function(checkbox) {
            checkbox.checked = false;
            const date = checkbox.getAttribute('data-date');
            toggleHolidayForDate(date, false);
        });
        // Then mark all as absent
        markAllAbsent();
    }
}
</script>

