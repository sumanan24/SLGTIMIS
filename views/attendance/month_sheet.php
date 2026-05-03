<div class="container-fluid px-4 py-3">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-file-excel me-2"></i>Month Attendance Sheet (Excel)</h5>
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

                    <p class="text-muted small mb-3">
                        Download a blank sheet: weekdays only (no Saturday/Sunday). Each day uses a 2×2 block (two columns × two rows) for four slot marks.
                        Columns: row number, student ID, initials only (no full name), NIC — merged vertically with the two slot rows.
                        <?php if (!empty($lockDepartmentSelection)): ?>
                            <span class="d-block mt-1"><strong>Department:</strong> your own department only.</span>
                        <?php else: ?>
                            <span class="d-block mt-1"><strong>Department:</strong> you may select any department.</span>
                        <?php endif; ?>
                    </p>

                    <form method="GET" action="<?php echo APP_URL; ?>/attendance/month-sheet" id="monthSheetFilterForm" class="mb-4">
                        <div class="row g-3">
                            <?php
                            $lockDepartmentSelection = !empty($lockDepartmentSelection);
                            $forcedDepartmentId = isset($forcedDepartmentId) ? (string) $forcedDepartmentId : '';
                            ?>
                            <?php if (!$lockDepartmentSelection): ?>
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
                                <input type="hidden" name="department_id" id="department_id" value="<?php echo htmlspecialchars($forcedDepartmentId); ?>">
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
                                <label for="month" class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                                <input type="month" class="form-control" id="month" name="month"
                                    value="<?php echo htmlspecialchars($selectedMonth ?? date('Y-m')); ?>" required>
                            </div>

                            <div class="col-md-2">
                                <label for="group" class="form-label fw-semibold">Group</label>
                                <select class="form-select" id="group" name="group" <?php echo (empty($selectedCourse) || empty($selectedAcademicYear)) ? 'disabled' : ''; ?>>
                                    <option value="">All groups</option>
                                    <?php if (isset($groups) && !empty($groups)): ?>
                                        <?php foreach ($groups as $groupItem): ?>
                                            <option value="<?php echo htmlspecialchars($groupItem['id']); ?>"
                                                <?php echo ($selectedGroup ?? '') == $groupItem['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($groupItem['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-1"></i>Apply filters
                                </button>
                                <a href="<?php echo APP_URL; ?>/attendance/month-sheet" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                                <button type="button" class="btn btn-success" id="monthSheetDownloadBtn">
                                    <i class="fas fa-file-excel me-1"></i>Download Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');
    const academicYearSelect = document.getElementById('academic_year');
    const groupSelect = document.getElementById('group');
    const monthInput = document.getElementById('month');
    const appBase = <?php echo json_encode(rtrim(APP_URL, '/')); ?>;

    function loadGroups() {
        if (!courseSelect || !academicYearSelect || !groupSelect) return;
        const courseId = courseSelect.value;
        const academicYear = academicYearSelect.value;
        if (courseId && academicYear) {
            groupSelect.disabled = false;
            groupSelect.innerHTML = '<option value="">Loading…</option>';
            fetch(appBase + '/attendance/get-groups-by-course-and-year?course_id=' + encodeURIComponent(courseId) + '&academic_year=' + encodeURIComponent(academicYear))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    groupSelect.innerHTML = '<option value="">All groups</option>';
                    if (data.success && data.groups) {
                        data.groups.forEach(function(group) {
                            const option = document.createElement('option');
                            option.value = group.id;
                            option.textContent = group.name;
                            groupSelect.appendChild(option);
                        });
                    }
                })
                .catch(function() {
                    groupSelect.innerHTML = '<option value="">All groups</option>';
                });
        } else {
            groupSelect.innerHTML = '<option value="">All groups</option>';
            groupSelect.disabled = true;
        }
    }

    if (departmentSelect && courseSelect) {
        departmentSelect.addEventListener('change', function() {
            const departmentId = this.value;
            if (departmentId) {
                fetch(appBase + '/courses/get-by-department?department_id=' + encodeURIComponent(departmentId))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
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
                        if (groupSelect) {
                            groupSelect.innerHTML = '<option value="">All groups</option>';
                            groupSelect.disabled = true;
                        }
                    });
            } else {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                courseSelect.disabled = true;
                if (groupSelect) {
                    groupSelect.innerHTML = '<option value="">All groups</option>';
                    groupSelect.disabled = true;
                }
            }
        });
    }
    if (courseSelect && academicYearSelect && groupSelect) {
        courseSelect.addEventListener('change', loadGroups);
        academicYearSelect.addEventListener('change', loadGroups);
    }

    document.getElementById('monthSheetDownloadBtn')?.addEventListener('click', function() {
        const deptEl = document.getElementById('department_id');
        const departmentId = deptEl ? deptEl.value : '';
        const courseId = courseSelect ? courseSelect.value : '';
        const academicYear = academicYearSelect ? academicYearSelect.value : '';
        const month = monthInput ? monthInput.value : '';
        const group = groupSelect ? groupSelect.value : '';
        if (!departmentId || !courseId || !academicYear || !month) {
            alert('Please select Department, Course, Academic Year, and Month.');
            return;
        }
        const params = new URLSearchParams({
            department_id: departmentId,
            course_id: courseId,
            academic_year: academicYear,
            month: month
        });
        if (group) params.set('group', group);
        window.location.href = appBase + '/attendance/export-month-sheet?' + params.toString();
    });
});
</script>
