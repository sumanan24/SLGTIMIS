<?php
$editingEnrollment = $editingEnrollment ?? null;
$isEditMode = !empty($editingEnrollment);
$currentEnrollDate = $isEditMode
    ? substr($editingEnrollment['staff_module_enrollment_date'] ?? date('Y-m-d'), 0, 10)
    : date('Y-m-d');
?>

<div class="container mt-4">
    <h3>Staff Module Enrollment (HOD)</h3>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <form action="<?php echo APP_URL; ?>/hod/staff-module-enroll" method="post" class="card p-3 mb-4">
                <h5 class="mb-3">
                    <?php echo $isEditMode ? 'Edit Staff Module Enrollment' : 'New Staff Module Enrollment'; ?>
                </h5>

                <input type="hidden" name="action" value="<?php echo $isEditMode ? 'update' : 'create'; ?>">
                <?php if ($isEditMode): ?>
                    <input type="hidden" name="enrollment_id" value="<?php echo htmlspecialchars($editingEnrollment['staff_module_enrollment_id']); ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label for="staff_id" class="form-label">Staff</label>
                    <select name="staff_id" id="staff_id" class="form-select" required>
                        <option value="">-- Select Staff --</option>
                        <?php if (!empty($staffList)): ?>
                            <?php foreach ($staffList as $staff): ?>
                                <option value="<?php echo htmlspecialchars($staff['staff_id']); ?>"
                                    <?php echo ($isEditMode && ($editingEnrollment['staff_id'] ?? '') === $staff['staff_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($staff['staff_name'] ?? '') . ' (' . $staff['staff_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="course_id" class="form-label">Course</label>
                    <select name="course_id" id="course_id" class="form-select" required>
                        <option value="">-- Select Course --</option>
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                    <?php echo ($isEditMode && ($editingEnrollment['course_id'] ?? '') === $course['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(($course['course_name'] ?? '') . ' (' . $course['course_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="module_id" class="form-label">Module</label>
                    <select
                        name="module_id"
                        id="module_id"
                        class="form-select"
                        data-current-module-id="<?php echo $isEditMode ? htmlspecialchars($editingEnrollment['module_id']) : ''; ?>"
                        required
                    >
                        <option value="">-- Select Module --</option>
                        <!-- Optionally populated via JS based on course -->
                    </select>
                </div>

                <div class="mb-3">
                    <label for="academic_year" class="form-label">Academic Year</label>
                    <select name="academic_year" id="academic_year" class="form-select" required>
                        <option value="">-- Select Academic Year --</option>
                        <?php if (!empty($academicYears)): ?>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>"
                                    <?php echo (!empty($selectedAcademicYear) && $selectedAcademicYear === $year) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="enrollment_date" class="form-label">Enrollment Date</label>
                    <input
                        type="date"
                        name="enrollment_date"
                        id="enrollment_date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($currentEnrollDate); ?>"
                    >
                    <small class="text-muted">If left empty, today&apos;s date will be used.</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php echo $isEditMode ? 'Update Enrollment' : 'Enroll Staff to Module'; ?>
                </button>
                <?php if ($isEditMode): ?>
                    <a href="<?php echo APP_URL; ?>/hod/staff-module-enroll" class="btn btn-outline-secondary ms-2">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Current Enrollments (Department)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Course</th>
                                    <th>Module</th>
                                    <th>Academic Year</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($enrollments)): ?>
                                    <?php foreach ($enrollments as $enroll): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(($enroll['staff_name'] ?? '') . ' (' . $enroll['staff_id'] . ')'); ?></td>
                                            <td><?php echo htmlspecialchars(($enroll['course_name'] ?? '') . ' (' . $enroll['course_id'] . ')'); ?></td>
                                            <td><?php echo htmlspecialchars(($enroll['module_name'] ?? '') . ' (' . $enroll['module_id'] . ')'); ?></td>
                                            <td><?php echo htmlspecialchars($enroll['academic_year']); ?></td>
                                            <td><?php echo htmlspecialchars($enroll['staff_module_enrollment_date']); ?></td>
                                            <td>
                                                <a href="<?php echo APP_URL; ?>/hod/staff-module-enroll?edit_id=<?php echo htmlspecialchars($enroll['staff_module_enrollment_id']); ?>" class="btn btn-sm btn-outline-primary mb-1">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this enrollment?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="enrollment_id" value="<?php echo htmlspecialchars($enroll['staff_module_enrollment_id']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No enrollments found for this department.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const courseSelect = document.getElementById('course_id');
    const moduleSelect = document.getElementById('module_id');

    function clearModules() {
        moduleSelect.innerHTML = '<option value=\"\">-- Select Module --</option>';
    }

    courseSelect.addEventListener('change', function () {
        const courseId = this.value;
        clearModules();
        if (!courseId) {
            return;
        }

        fetch('<?php echo APP_URL; ?>/hod/get-modules-by-course?course_id=' + encodeURIComponent(courseId))
            .then(response => response.json())
            .then(data => {
                if (!data.success || !Array.isArray(data.modules)) {
                    return;
                }
                const currentModuleId = moduleSelect.getAttribute('data-current-module-id') || '';
                data.modules.forEach(m => {
                    if (!m.module_id) return;
                    const opt = document.createElement('option');
                    opt.value = m.module_id;
                    opt.textContent = (m.module_name || '') + ' (' + m.module_id + ')';
                    if (currentModuleId && currentModuleId === m.module_id) {
                        opt.selected = true;
                    }
                    moduleSelect.appendChild(opt);
                });
            })
            .catch(() => {
                // Silently fail; user can retry by re-selecting the course
            });
    });

    // If editing, trigger initial load of modules for selected course
    <?php if ($isEditMode && !empty($editingEnrollment['course_id'])): ?>
    if (courseSelect) {
        courseSelect.dispatchEvent(new Event('change'));
    }
    <?php endif; ?>
});
</script>
