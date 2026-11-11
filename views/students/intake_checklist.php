<?php
declare(strict_types=1);
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$filters = $filters ?? [];
$rows = $rows ?? [];
$yearChosen = ($filters['academic_year'] ?? '') !== '';
$deptValue = (string) ($filters['department_id'] ?? '');
$deptForForm = ($deptValue === '__none__') ? '' : $deptValue;
?>
<div class="container-fluid px-4 py-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-clipboard-check me-2 text-danger"></i>Student Intake Checklist</h4>
            <div class="text-muted small">Select academic year and department, tick students, then download one A4 checklist page per student.</div>
        </div>
        <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/students">
            <i class="fas fa-arrow-left me-1"></i> Students
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $e($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body bg-light">
            <form method="GET" action="<?php echo APP_URL; ?>/students/intake-checklist" class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label for="academic_year" class="form-label fw-bold small">Academic Year / Intake <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="academic_year" name="academic_year" required>
                        <option value="">Select academic year</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $e($year); ?>" <?php echo ($filters['academic_year'] ?? '') === $year ? 'selected' : ''; ?>>
                                <?php echo $e($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($isHOD)): ?>
                <div class="col-md-4 col-lg-3">
                    <label for="department_id" class="form-label fw-bold small">Department</label>
                    <select class="form-select form-select-sm" id="department_id" name="department_id">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $e($dept['department_id']); ?>" <?php echo $deptValue === (string) $dept['department_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-4 col-lg-3">
                    <label for="course_id" class="form-label fw-bold small">Course</label>
                    <select class="form-select form-select-sm" id="course_id" name="course_id">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $e($course['course_id']); ?>"
                                    data-department="<?php echo $e($course['department_id'] ?? ''); ?>"
                                    <?php echo ($filters['course_id'] ?? '') === (string) $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-users me-1"></i>Show Students</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$yearChosen): ?>
        <div class="alert alert-secondary mb-0">
            <i class="fas fa-info-circle me-2"></i>Choose an academic year to list registered students for the intake checklist.
        </div>
    <?php else: ?>
        <form method="POST" action="<?php echo APP_URL; ?>/students/intake-checklist" id="intakeChecklistForm">
            <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
            <input type="hidden" name="academic_year" value="<?php echo $e($filters['academic_year'] ?? ''); ?>">
            <input type="hidden" name="department_id" value="<?php echo $e($deptForForm); ?>">
            <input type="hidden" name="course_id" value="<?php echo $e($filters['course_id'] ?? ''); ?>">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="fw-bold">
                        Students
                        <span class="badge bg-secondary ms-1"><?php echo count($rows); ?></span>
                        <span class="text-muted fw-normal small ms-2" id="icSelectedCount">0 selected</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <input type="search" class="form-control form-control-sm" id="icSearch" placeholder="Search name or ID…" style="min-width:200px;">
                        <button type="submit" class="btn btn-danger btn-sm" id="icDownloadBtn" disabled>
                            <i class="fas fa-file-pdf me-1"></i>Download Checklist PDF
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!$rows): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                            No students found for this academic year / department.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height:560px; overflow:auto;">
                            <table class="table table-hover align-middle mb-0" id="icTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width:42px;">
                                            <input type="checkbox" class="form-check-input" id="icSelectAll" title="Select all">
                                        </th>
                                        <th style="width:56px;">No</th>
                                        <th>Registration No.</th>
                                        <th>Student Name</th>
                                        <th>Department</th>
                                        <th>Course</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $n = 1; foreach ($rows as $row): ?>
                                    <tr data-search="<?php echo $e(strtolower($row['student_id'] . ' ' . $row['name'] . ' ' . $row['department'] . ' ' . $row['course'])); ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input ic-select" name="student_ids[]" value="<?php echo $e($row['student_id']); ?>">
                                        </td>
                                        <td class="text-muted"><?php echo $n++; ?></td>
                                        <td class="fw-semibold"><?php echo $e($row['student_id']); ?></td>
                                        <td><?php echo $e($row['name']); ?></td>
                                        <td><?php echo $e($row['department'] !== '' ? $row['department'] : '—'); ?></td>
                                        <td><?php echo $e($row['course'] !== '' ? $row['course'] : '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('intakeChecklistForm');
    if (!form) return;
    var selectAll = document.getElementById('icSelectAll');
    var boxes = function () { return Array.prototype.slice.call(document.querySelectorAll('.ic-select')); };
    var countEl = document.getElementById('icSelectedCount');
    var btn = document.getElementById('icDownloadBtn');
    var search = document.getElementById('icSearch');
    var dept = document.getElementById('department_id');
    var course = document.getElementById('course_id');

    function visibleBoxes() {
        return boxes().filter(function (cb) {
            var tr = cb.closest('tr');
            return tr && tr.style.display !== 'none';
        });
    }
    function refresh() {
        var checked = boxes().filter(function (cb) { return cb.checked; }).length;
        countEl.textContent = checked + ' selected';
        btn.disabled = checked === 0;
        var vis = visibleBoxes();
        if (selectAll) {
            selectAll.checked = vis.length > 0 && vis.every(function (cb) { return cb.checked; });
            selectAll.indeterminate = vis.some(function (cb) { return cb.checked; }) && !selectAll.checked;
        }
    }
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            visibleBoxes().forEach(function (cb) { cb.checked = selectAll.checked; });
            refresh();
        });
    }
    boxes().forEach(function (cb) { cb.addEventListener('change', refresh); });
    if (search) {
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            document.querySelectorAll('#icTable tbody tr').forEach(function (tr) {
                var hay = tr.getAttribute('data-search') || '';
                tr.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
            });
            refresh();
        });
    }
    form.addEventListener('submit', function (ev) {
        if (boxes().filter(function (cb) { return cb.checked; }).length === 0) {
            ev.preventDefault();
            alert('Select at least one student.');
            return;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Preparing PDF…';
    });

    function filterCourses() {
        if (!dept || !course) return;
        var d = dept.value;
        Array.prototype.forEach.call(course.options, function (opt, i) {
            if (i === 0) { opt.hidden = false; return; }
            var od = opt.getAttribute('data-department') || '';
            opt.hidden = d !== '' && od !== '' && od !== d;
            if (opt.hidden && opt.selected) course.value = '';
        });
    }
    if (dept) dept.addEventListener('change', filterCourses);
    filterCourses();
    refresh();
})();
</script>
