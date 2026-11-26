<?php
$e = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$onlyDept = (is_array($departments) && count($departments) === 1) ? $departments[0] : null;
$onlyDeptId = (string) ($onlyDept['department_id'] ?? '');
$preCourseId = (string) ($preCourseId ?? '');
$preCourseName = (string) ($preCourseName ?? '');
$preDepartmentId = (string) ($preDepartmentId ?? '');
$preVersion = isset($preVersion) && $preVersion !== null && $preVersion !== '' ? (string) (int) $preVersion : '';
$selectedDept = $onlyDeptId !== '' ? $onlyDeptId : $preDepartmentId;
$defaultYear = '';
if (!empty($academicYears) && is_array($academicYears)) {
    $defaultYear = (string) $academicYears[0];
}
$backUrl = APP_URL . '/groups';
if ($preCourseId !== '') {
    $backUrl = APP_URL . '/courses/manage?id=' . urlencode($preCourseId);
}
?>
<style>
.gc {
    --ink: #0f172a;
    --muted: #64748b;
    --line: #e2e8f0;
    --soft: #f8fafc;
    --navy: #003366;
    --deep: #001f3f;
    max-width: 920px;
    margin: 0 auto;
    padding: 1.25rem 1.25rem 2.25rem;
    color: var(--ink);
}
.gc * { box-sizing: border-box; }
.gc-top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem 1rem;
    margin-bottom: 1rem;
}
.gc-top h1 {
    margin: 0 0 .2rem;
    font-size: clamp(1.2rem, 2.2vw, 1.45rem);
    font-weight: 700;
    color: var(--deep);
    line-height: 1.25;
}
.gc-top .sub {
    margin: 0;
    color: var(--muted);
    font-size: .9rem;
}
.gc-context {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-bottom: 1rem;
}
.gc-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .7rem;
    border-radius: 999px;
    background: #e8eef6;
    border: 1px solid #c7d7ea;
    color: var(--navy);
    font-size: .8rem;
    font-weight: 600;
}
.gc-card {
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(15, 23, 42, .04);
}
.gc-sec {
    padding: 1.1rem 1.15rem 1.15rem;
    border-bottom: 1px solid var(--line);
}
.gc-sec:last-child { border-bottom: 0; }
.gc-sec h2 {
    margin: 0 0 .85rem;
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--muted);
}
.gc-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .9rem 1rem;
    align-items: start;
}
.gc-grid .full { grid-column: 1 / -1; }
.gc-field label {
    display: block;
    margin-bottom: .35rem;
    font-size: .875rem;
    font-weight: 600;
    color: var(--ink);
    line-height: 1.25;
}
.gc-field .req { color: #dc2626; }
.gc-field .form-control,
.gc-field .form-select {
    min-height: 40px;
    border-radius: 8px;
    border-color: #cbd5e1;
}
.gc-field .form-control:focus,
.gc-field .form-select:focus {
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(0, 51, 102, .12);
}
.gc-field .hint {
    margin-top: .3rem;
    font-size: .78rem;
    color: var(--muted);
    min-height: 1.1em;
}
.gc-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .55rem;
    padding: 1rem 1.15rem;
    background: var(--soft);
    border-top: 1px solid var(--line);
}
.gc-actions .btn {
    border-radius: 8px;
    min-height: 40px;
    padding-left: 1rem;
    padding-right: 1rem;
}
.gc-actions .btn-primary { background: var(--navy); border-color: var(--navy); }
.gc-actions .btn-primary:hover { background: var(--deep); border-color: var(--deep); }
@media (max-width: 767.98px) {
    .gc { padding: .85rem .7rem 1.75rem; }
    .gc-grid { grid-template-columns: 1fr; }
    .gc-top .btn { width: 100%; }
    .gc-actions { flex-direction: column; }
    .gc-actions .btn { width: 100%; }
    .gc-field .form-control,
    .gc-field .form-select { font-size: 1rem; min-height: 44px; }
}
</style>

<div class="gc">
    <div class="gc-top">
        <div>
            <h1>Create group</h1>
            <p class="sub">Set course, year, and group details</p>
        </div>
        <a href="<?php echo $e($backUrl); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back
        </a>
    </div>

    <?php if ($preCourseId !== ''): ?>
    <div class="gc-context">
        <span class="gc-pill"><i class="fas fa-book"></i><?php echo $e($preCourseName !== '' ? $preCourseName : $preCourseId); ?></span>
        <span class="gc-pill"><?php echo $e($preCourseId); ?></span>
        <?php if ($preVersion !== ''): ?>
        <span class="gc-pill">Version <?php echo $e($preVersion); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 mb-3"><?php echo $e($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>/groups/create" id="groupCreateForm" class="gc-card">
        <div class="gc-sec">
            <h2>Course</h2>
            <div class="gc-grid">
                <div class="gc-field">
                    <label for="department_id">Department <span class="req">*</span></label>
                    <select class="form-select" id="department_id" name="department_id" required <?php echo $onlyDeptId !== '' ? 'disabled' : ''; ?>>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $dept): ?>
                            <?php $did = (string) ($dept['department_id'] ?? ''); ?>
                            <option value="<?php echo $e($did); ?>" <?php echo ($selectedDept !== '' && $did === $selectedDept) ? 'selected' : ''; ?>>
                                <?php echo $e($dept['department_name'] ?? $did); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($onlyDeptId !== ''): ?>
                        <input type="hidden" name="department_id" value="<?php echo $e($onlyDeptId); ?>">
                    <?php endif; ?>
                </div>

                <div class="gc-field">
                    <label for="course_id">Course <span class="req">*</span></label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Select course</option>
                    </select>
                    <div class="hint" id="courseHelp"></div>
                </div>

                <div class="gc-field">
                    <label for="course_version">Course version <span class="req">*</span></label>
                    <select class="form-select" id="course_version" name="course_version" required disabled>
                        <option value="">Select course first</option>
                    </select>
                    <div class="hint" id="versionHelp"></div>
                </div>

                <div class="gc-field">
                    <label for="academic_year">Academic year <span class="req">*</span></label>
                    <select class="form-select" id="academic_year" name="academic_year" required>
                        <option value="">Select year</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $e($year); ?>" <?php echo $defaultYear !== '' && $year === $defaultYear ? 'selected' : ''; ?>>
                                <?php echo $e($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="gc-sec">
            <h2>Group</h2>
            <div class="gc-grid">
                <div class="gc-field full">
                    <label for="name">Group name <span class="req">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" maxlength="255" required
                           placeholder="e.g. 4CS 2026 A">
                </div>

                <div class="gc-field">
                    <label for="group_code">Group code</label>
                    <input type="text" class="form-control" id="group_code" name="group_code" maxlength="30"
                           placeholder="Optional">
                </div>

                <div class="gc-field">
                    <label for="batch">Batch / intake</label>
                    <input type="text" class="form-control" id="batch" name="batch" maxlength="30"
                           placeholder="Optional">
                </div>

                <div class="gc-field">
                    <label for="instructor_staff_id">Instructor</label>
                    <select class="form-select" id="instructor_staff_id" name="instructor_staff_id">
                        <option value="">Not assigned</option>
                        <?php foreach (($instructors ?? []) as $ins): ?>
                            <option value="<?php echo $e($ins['staff_id']); ?>">
                                <?php echo $e(($ins['staff_name'] ?? '') . ' (' . $ins['staff_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="gc-field">
                    <label for="status">Status <span class="req">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="gc-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Create group
            </button>
            <a href="<?php echo $e($backUrl); ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const departmentSelect = document.getElementById('department_id');
    const courseSelect = document.getElementById('course_id');
    const versionSelect = document.getElementById('course_version');
    const courseHelp = document.getElementById('courseHelp');
    const versionHelp = document.getElementById('versionHelp');
    const versionsByCourse = {};
    let preCourseId = <?php echo json_encode($preCourseId); ?>;
    const preVersion = <?php echo json_encode($preVersion); ?>;

    function versionLabel(v) {
        v = parseInt(v, 10) || 0;
        return v === 0 ? 'Default (0)' : 'Version ' + v;
    }

    function fillVersions(courseId, preferred) {
        if (!versionSelect) return;
        versionSelect.innerHTML = '';
        versionSelect.disabled = true;
        if (!courseId) {
            versionSelect.options.add(new Option('Select course first', '', true, true));
            if (versionHelp) versionHelp.textContent = '';
            return;
        }
        const versions = versionsByCourse[courseId] && versionsByCourse[courseId].length
            ? versionsByCourse[courseId]
            : [0];
        versionSelect.options.add(new Option('Select version', '', true, true));
        versions.forEach(function (v) {
            versionSelect.options.add(new Option(versionLabel(v), String(v), false, false));
        });
        versionSelect.disabled = false;
        const latest = versions[versions.length - 1];
        if (preferred !== undefined && preferred !== null && preferred !== '' && versions.indexOf(parseInt(preferred, 10)) >= 0) {
            versionSelect.value = String(preferred);
        } else if (latest !== undefined) {
            versionSelect.value = String(latest);
        }
        if (versionHelp) versionHelp.textContent = '';
    }

    function loadCourses(departmentId) {
        courseSelect.innerHTML = '<option value="">Select course</option>';
        Object.keys(versionsByCourse).forEach(function (k) { delete versionsByCourse[k]; });
        fillVersions('');
        if (!departmentId) {
            if (courseHelp) courseHelp.textContent = '';
            return;
        }
        if (courseHelp) courseHelp.textContent = 'Loading…';
        fetch('<?php echo APP_URL; ?>/groups/get-courses-by-department?department_id=' + encodeURIComponent(departmentId), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    if (courseHelp) courseHelp.textContent = (data && data.error) ? data.error : 'Could not load courses.';
                    return;
                }
                const courses = Array.isArray(data.courses) ? data.courses : [];
                courses.forEach(function (course) {
                    const option = document.createElement('option');
                    option.value = course.course_id;
                    option.textContent = course.course_name + ' (' + course.course_id + ')';
                    courseSelect.appendChild(option);
                    versionsByCourse[course.course_id] = Array.isArray(course.versions) && course.versions.length
                        ? course.versions
                        : [0];
                });
                if (courseHelp) {
                    courseHelp.textContent = courses.length ? '' : 'No courses for this department.';
                }
                if (preCourseId && versionsByCourse[preCourseId]) {
                    courseSelect.value = preCourseId;
                    fillVersions(preCourseId, preVersion);
                    preCourseId = '';
                }
            })
            .catch(function () {
                if (courseHelp) courseHelp.textContent = 'Could not load courses.';
            });
    }

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            loadCourses(this.value);
        });
    }
    if (courseSelect) {
        courseSelect.addEventListener('change', function () {
            fillVersions(this.value);
        });
    }

    const initialDept = departmentSelect ? departmentSelect.value : '';
    if (initialDept) {
        loadCourses(initialDept);
    }
});
</script>
