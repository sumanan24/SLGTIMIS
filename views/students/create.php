<style>
.stu-page { --stu-navy:#0b2545; --stu-ink:#1e293b; --stu-muted:#64748b; --stu-line:#dbe4f0; --stu-soft:#f5f8fd; --stu-accent:#1d4ed8; color:var(--stu-ink); }
.stu-page .stu-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
.stu-page .stu-title { margin:0; font-size:1.35rem; font-weight:700; color:var(--stu-navy); letter-spacing:-.02em; }
.stu-page .stu-sub { margin:.2rem 0 0; color:var(--stu-muted); font-size:.86rem; max-width:38rem; }
.stu-page .stu-actions { display:flex; flex-wrap:wrap; gap:.45rem; align-items:center; }
.stu-page .stu-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:.45rem; font-size:.82rem; font-weight:600; padding:.42rem .8rem; border:1px solid transparent; text-decoration:none; line-height:1.2; cursor:pointer; background:transparent; }
.stu-page .stu-btn-primary { background:var(--stu-navy); color:#fff; }
.stu-page .stu-btn-primary:hover { background:#163a66; color:#fff; }
.stu-page .stu-btn-ghost { background:#fff; color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-ghost:hover { background:var(--stu-soft); color:var(--stu-navy); }
.stu-page .stu-btn-soft { background:var(--stu-soft); color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-soft:hover { background:#e8eef8; color:var(--stu-navy); }
.stu-page .stu-btn-success { background:#15803d; color:#fff; }
.stu-page .stu-btn-success:hover { background:#166534; color:#fff; }
.stu-page .stu-shell { max-width:960px; margin:0 auto; }
.stu-page .stu-panel { background:#fff; border:1px solid var(--stu-line); border-radius:.7rem; margin-bottom:1rem; overflow:hidden; }
.stu-page .stu-panel-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem; padding:.75rem 1rem; background:var(--stu-soft); border-bottom:1px solid var(--stu-line); }
.stu-page .stu-panel-head h2 { margin:0; font-size:.92rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-panel-head .stu-panel-note { margin:0; font-size:.76rem; color:var(--stu-muted); }
.stu-page .stu-panel-body { padding:1.05rem 1.1rem; }
.stu-page .stu-label { display:block; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--stu-muted); margin-bottom:.28rem; }
.stu-page .stu-label .req { color:#dc2626; text-transform:none; letter-spacing:0; margin-left:.15rem; }
.stu-page .stu-field .form-control,
.stu-page .stu-field .form-select { font-size:.86rem; border-color:#cfd9e6; border-radius:.45rem; min-height:2.25rem; }
.stu-page .stu-field .form-control:focus,
.stu-page .stu-field .form-select:focus { border-color:#93b4e0; box-shadow:0 0 0 .2rem rgba(29,78,216,.12); }
.stu-page .stu-field .form-text { font-size:.75rem; color:#7c8aa0; margin-top:.3rem; }
.stu-page .stu-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:.85rem 1rem; }
.stu-page .stu-span-12 { grid-column:span 12; }
.stu-page .stu-span-6 { grid-column:span 6; }
.stu-page .stu-span-4 { grid-column:span 4; }
.stu-page .stu-span-3 { grid-column:span 3; }
.stu-page .stu-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; margin-top:1.1rem; padding-top:.95rem; border-top:1px solid #eef2f7; }
.stu-page .stu-hint { margin:0; color:var(--stu-muted); font-size:.78rem; }
.stu-page .stu-import-note { background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; border-radius:.5rem; padding:.75rem .9rem; font-size:.82rem; margin-bottom:1rem; }
.stu-page .stu-import-note ul { margin:.45rem 0 0; padding-left:1.1rem; }
.stu-page .stu-import-note li + li { margin-top:.2rem; }
@media (max-width:991.98px) {
    .stu-page .stu-span-6,
    .stu-page .stu-span-4 { grid-column:span 12; }
    .stu-page .stu-span-3 { grid-column:span 6; }
}
@media (max-width:575.98px) {
    .stu-page .stu-span-3 { grid-column:span 12; }
    .stu-page .stu-title { font-size:1.15rem; }
}
</style>

<div class="container-fluid px-4 py-3 stu-page">
    <div class="stu-shell">
        <div class="stu-head">
            <div>
                <h1 class="stu-title">Create student</h1>
                <p class="stu-sub">Add a single enrolment, or import a batch from CSV. Registration number is generated from course and academic year.</p>
            </div>
            <div class="stu-actions">
                <a href="<?php echo APP_URL; ?>/students" class="stu-btn stu-btn-ghost">
                    <i class="fas fa-arrow-left"></i>Back to list
                </a>
                <button type="button" class="stu-btn stu-btn-soft" data-bs-toggle="collapse" data-bs-target="#excelImportSection" aria-expanded="false" aria-controls="excelImportSection">
                    <i class="fas fa-file-excel"></i>Import from Excel
                </button>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start" role="alert">
                <i class="fas fa-exclamation-circle me-2 mt-1"></i>
                <div><?php echo $error; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-start" role="alert">
                <i class="fas fa-check-circle me-2 mt-1"></i>
                <div><?php echo $message; ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="collapse mb-3" id="excelImportSection">
            <div class="stu-panel">
                <div class="stu-panel-head">
                    <h2><i class="fas fa-file-excel me-2"></i>Import from Excel / CSV</h2>
                    <p class="stu-panel-note">Batch enrol with shared department and course settings</p>
                </div>
                <div class="stu-panel-body">
                    <div class="stu-import-note">
                        <strong>Before you upload</strong>
                        <ul>
                            <li>File must include columns: <strong>student_id</strong>, <strong>fullname</strong>, <strong>NIC</strong></li>
                            <li>Choose department, course, academic year, mode, and status below — they apply to every row</li>
                            <li>Prefer CSV. For Excel files, use Save As → CSV first</li>
                            <li><a href="<?php echo APP_URL; ?>/students/download-sample-excel" target="_blank" class="fw-semibold">Download sample CSV</a></li>
                        </ul>
                    </div>

                    <form method="POST" action="<?php echo APP_URL; ?>/students/import-excel" enctype="multipart/form-data" id="excelImportForm">
                        <div class="stu-grid">
                            <div class="stu-field stu-span-6">
                                <label for="import_department_id" class="stu-label">Department <span class="req">*</span></label>
                                <select class="form-select" id="import_department_id" name="department_id" required>
                                    <option value="">Select department</option>
                                    <?php if (!empty($departments)): ?>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?php echo htmlspecialchars($department['department_id']); ?>">
                                                <?php echo htmlspecialchars($department['department_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="stu-field stu-span-6">
                                <label for="import_course_id" class="stu-label">Course <span class="req">*</span></label>
                                <select class="form-select" id="import_course_id" name="course_id" required disabled>
                                    <option value="">Select department first</option>
                                    <?php if (!empty($courses)): ?>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                                    data-department-id="<?php echo htmlspecialchars($course['department_id'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="stu-field stu-span-4">
                                <label for="import_academic_year" class="stu-label">Academic year <span class="req">*</span></label>
                                <select class="form-select" id="import_academic_year" name="academic_year" required>
                                    <option value="">Select year</option>
                                    <?php if (!empty($academicYears)): ?>
                                        <?php foreach ($academicYears as $year): ?>
                                            <option value="<?php echo htmlspecialchars($year); ?>">
                                                <?php echo htmlspecialchars($year); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="stu-field stu-span-4">
                                <label for="import_course_mode" class="stu-label">Course mode</label>
                                <select class="form-select" id="import_course_mode" name="course_mode">
                                    <option value="Full Time" selected>Full Time</option>
                                    <option value="Part Time">Part Time</option>
                                </select>
                            </div>
                            <div class="stu-field stu-span-4">
                                <label for="import_enroll_status" class="stu-label">Enrollment status</label>
                                <select class="form-select" id="import_enroll_status" name="student_enroll_status">
                                    <option value="Following" selected>Following</option>
                                    <option value="Dropout">Dropout</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Long Absent">Long Absent</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                            <div class="stu-field stu-span-12">
                                <label for="excel_file" class="stu-label">Excel / CSV file <span class="req">*</span></label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">Accepted: .csv (recommended), .xlsx, .xls</div>
                            </div>
                        </div>
                        <div class="stu-foot">
                            <p class="stu-hint">Imported students use the enrollment values selected above.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="stu-btn stu-btn-ghost" data-bs-toggle="collapse" data-bs-target="#excelImportSection">Cancel</button>
                                <button type="submit" class="stu-btn stu-btn-success">
                                    <i class="fas fa-upload"></i>Import students
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <form method="POST" action="<?php echo APP_URL; ?>/students/create" id="createStudentForm">
            <div class="stu-panel">
                <div class="stu-panel-head">
                    <h2><i class="fas fa-graduation-cap me-2"></i>Enrollment</h2>
                    <p class="stu-panel-note">Department and course drive registration number</p>
                </div>
                <div class="stu-panel-body">
                    <div class="stu-grid">
                        <div class="stu-field stu-span-6">
                            <label for="department_id" class="stu-label">Department <span class="req">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select department</option>
                                <?php if (!empty($departments)): ?>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?php echo htmlspecialchars($department['department_id']); ?>">
                                            <?php echo htmlspecialchars($department['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="stu-field stu-span-6">
                            <label for="academic_year" class="stu-label">Academic year <span class="req">*</span></label>
                            <select class="form-select" id="academic_year" name="academic_year" required>
                                <option value="">Select year</option>
                                <?php if (!empty($academicYears)): ?>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>">
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="stu-field stu-span-6">
                            <label for="course_id" class="stu-label">Course <span class="req">*</span></label>
                            <select class="form-select" id="course_id" name="course_id" required disabled>
                                <option value="">Select department first</option>
                                <?php if (!empty($courses)): ?>
                                    <?php foreach ($courses as $course):
                                        $courseId = $course['course_id'] ?? ($course['COURSE_ID'] ?? ($course['Course_ID'] ?? ''));
                                        $departmentId = $course['department_id'] ?? ($course['DEPARTMENT_ID'] ?? ($course['Department_ID'] ?? ''));
                                        $courseCode = $course['course_code'] ?? ($course['COURSE_CODE'] ?? ($course['Course_Code'] ?? ''));
                                        $courseName = $course['course_name'] ?? ($course['COURSE_NAME'] ?? ($course['Course_Name'] ?? ''));
                                        if ($courseId === '') continue;
                                    ?>
                                        <option value="<?php echo htmlspecialchars($courseId); ?>"
                                                data-department-id="<?php echo htmlspecialchars($departmentId); ?>"
                                                data-course-code="<?php echo htmlspecialchars($courseCode); ?>">
                                            <?php echo htmlspecialchars($courseName !== '' ? $courseName : $courseId); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No courses available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="stu-field stu-span-6">
                            <label for="student_id" class="stu-label">Registration number <span class="req">*</span></label>
                            <input type="text" class="form-control" id="student_id" name="student_id" maxlength="50" required>
                            <div class="form-text">Auto-fills when course and academic year are set. You can edit it.</div>
                        </div>
                        <div class="stu-field stu-span-6">
                            <label for="course_mode" class="stu-label">Course mode</label>
                            <select class="form-select" id="course_mode" name="course_mode">
                                <option value="Full Time" selected>Full Time</option>
                                <option value="Part Time">Part Time</option>
                            </select>
                        </div>
                        <div class="stu-field stu-span-6">
                            <label for="student_enroll_status" class="stu-label">Enrollment status</label>
                            <select class="form-select" id="student_enroll_status" name="student_enroll_status">
                                <option value="Following" selected>Following</option>
                                <option value="Dropout">Dropout</option>
                                <option value="Completed">Completed</option>
                                <option value="Long Absent">Long Absent</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stu-panel">
                <div class="stu-panel-head">
                    <h2><i class="fas fa-user me-2"></i>Personal details</h2>
                    <p class="stu-panel-note">Name and NIC are required</p>
                </div>
                <div class="stu-panel-body">
                    <div class="stu-grid">
                        <div class="stu-field stu-span-3">
                            <label for="student_title" class="stu-label">Title</label>
                            <select class="form-select" id="student_title" name="student_title">
                                <option value="">Select</option>
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Miss">Miss</option>
                            </select>
                        </div>
                        <div class="stu-field stu-span-3">
                            <label for="student_gender" class="stu-label">Gender</label>
                            <select class="form-select" id="student_gender" name="student_gender">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="stu-field stu-span-6">
                            <label for="student_nic" class="stu-label">NIC <span class="req">*</span></label>
                            <input type="text" class="form-control" id="student_nic" name="student_nic" maxlength="12" required>
                        </div>
                        <div class="stu-field stu-span-12">
                            <label for="student_fullname" class="stu-label">Full name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="student_fullname" name="student_fullname" maxlength="255" required>
                        </div>
                    </div>
                    <div class="stu-foot">
                        <p class="stu-hint">Fields marked <span class="text-danger">*</span> are required.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?php echo APP_URL; ?>/students" class="stu-btn stu-btn-ghost">
                                <i class="fas fa-times"></i>Cancel
                            </a>
                            <button type="submit" class="stu-btn stu-btn-primary">
                                <i class="fas fa-save"></i>Create student
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');
    const academicYearSelect = document.getElementById('academic_year');
    const courseModeSelect = document.getElementById('course_mode');
    const studentIdInput = document.getElementById('student_id');
    const createForm = document.getElementById('createStudentForm');

    if (!departmentSelect || !courseSelect || !academicYearSelect || !studentIdInput) {
        return;
    }

    const allCourseOptions = [];
    const wasCourseDisabled = courseSelect.disabled;
    courseSelect.disabled = false;

    for (let i = 0; i < courseSelect.options.length; i++) {
        const option = courseSelect.options[i];
        if (option.value) {
            allCourseOptions.push({
                value: option.value,
                text: option.textContent.trim(),
                departmentId: option.getAttribute('data-department-id') || '',
                courseCode: option.getAttribute('data-course-code') || ''
            });
        }
    }

    courseSelect.disabled = wasCourseDisabled;

    function loadCoursesForDepartment() {
        const selectedDeptId = departmentSelect.value.trim();
        courseSelect.innerHTML = '<option value="">Select Course</option>';
        studentIdInput.value = '';

        if (!selectedDeptId) {
            courseSelect.disabled = true;
            courseSelect.innerHTML = '<option value="">Select Department First</option>';
            return;
        }

        let matchedCount = 0;
        allCourseOptions.forEach(function(course) {
            if (course.departmentId === selectedDeptId) {
                const option = document.createElement('option');
                option.value = course.value;
                option.textContent = course.text;
                option.setAttribute('data-department-id', course.departmentId);
                option.setAttribute('data-course-code', course.courseCode);
                courseSelect.appendChild(option);
                matchedCount++;
            }
        });

        if (matchedCount > 0) {
            courseSelect.disabled = false;
        } else {
            courseSelect.disabled = true;
            courseSelect.innerHTML = '<option value="">No courses available for this department</option>';
        }
    }

    function generateStudentId() {
        const courseId = courseSelect.value.trim();
        const academicYear = academicYearSelect.value.trim();
        const courseMode = courseModeSelect ? courseModeSelect.value : 'Full Time';

        if (!courseId || !academicYear) {
            studentIdInput.value = '';
            return;
        }

        studentIdInput.value = 'Loading...';

        const url = '<?php echo APP_URL; ?>/students/get-last-reg-number?course_id=' +
                    encodeURIComponent(courseId) +
                    '&academic_year=' + encodeURIComponent(academicYear) +
                    '&course_mode=' + encodeURIComponent(courseMode);

        fetch(url)
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.lastRegNumber) {
                    studentIdInput.value = data.lastRegNumber;
                } else {
                    studentIdInput.value = '';
                }
            })
            .catch(function() {
                studentIdInput.value = '';
            });
    }

    departmentSelect.addEventListener('change', function() {
        loadCoursesForDepartment();
        courseSelect.value = '';
        studentIdInput.value = '';
    });

    courseSelect.addEventListener('change', function() {
        if (academicYearSelect.value) generateStudentId();
        else studentIdInput.value = '';
    });

    academicYearSelect.addEventListener('change', function() {
        if (courseSelect.value) generateStudentId();
        else studentIdInput.value = '';
    });

    if (courseModeSelect) {
        courseModeSelect.addEventListener('change', function() {
            if (courseSelect.value && academicYearSelect.value) generateStudentId();
        });
    }

    if (createForm) {
        createForm.addEventListener('change', function(e) {
            if (e.target.id === 'department_id') {
                loadCoursesForDepartment();
            } else if (e.target.id === 'course_id' || e.target.id === 'academic_year' || e.target.id === 'course_mode') {
                if (courseSelect.value && academicYearSelect.value) generateStudentId();
            }
        });
    }

    if (departmentSelect.value) {
        loadCoursesForDepartment();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const importDepartmentSelect = document.getElementById('import_department_id');
    const importCourseSelect = document.getElementById('import_course_id');
    const allImportCourseOptions = [];

    if (importCourseSelect) {
        for (let i = 0; i < importCourseSelect.options.length; i++) {
            allImportCourseOptions.push(importCourseSelect.options[i]);
        }
    }

    function filterImportCoursesByDepartment() {
        const selectedDepartmentId = importDepartmentSelect ? importDepartmentSelect.value : '';
        if (!importCourseSelect) return;

        importCourseSelect.innerHTML = '<option value="">Select Course</option>';

        if (!selectedDepartmentId) {
            importCourseSelect.disabled = true;
            importCourseSelect.innerHTML = '<option value="">Select Department First</option>';
            return;
        }

        let hasCourses = false;
        allImportCourseOptions.forEach(function(option) {
            if (option.value && option.getAttribute('data-department-id') === selectedDepartmentId) {
                importCourseSelect.appendChild(option.cloneNode(true));
                hasCourses = true;
            }
        });

        if (hasCourses) {
            importCourseSelect.disabled = false;
        } else {
            importCourseSelect.disabled = true;
            importCourseSelect.innerHTML = '<option value="">No courses available for this department</option>';
        }
    }

    if (importDepartmentSelect) {
        importDepartmentSelect.addEventListener('change', filterImportCoursesByDepartment);
    }

    const createForm = document.getElementById('createStudentForm');
    const studentIdInput = document.getElementById('student_id');
    const fullNameInput = document.getElementById('student_fullname');
    const nicInput = document.getElementById('student_nic');

    if (createForm && studentIdInput && fullNameInput && nicInput) {
        createForm.addEventListener('submit', function(e) {
            if (!studentIdInput.value.trim()) {
                e.preventDefault();
                alert('Student ID is required.');
                studentIdInput.focus();
                return false;
            }
            if (!fullNameInput.value.trim()) {
                e.preventDefault();
                alert('Full Name is required.');
                fullNameInput.focus();
                return false;
            }
            if (!nicInput.value.trim()) {
                e.preventDefault();
                alert('NIC is required.');
                nicInput.focus();
                return false;
            }
        });
    }
});
</script>
