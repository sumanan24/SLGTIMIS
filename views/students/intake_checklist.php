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
$rowCount = count($rows);
?>
<style>
.stu-page { --stu-navy:#0b2545; --stu-ink:#1e293b; --stu-muted:#64748b; --stu-line:#dbe4f0; --stu-soft:#f5f8fd; color:var(--stu-ink); }
.stu-page .stu-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
.stu-page .stu-title { margin:0; font-size:1.35rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-sub { margin:.2rem 0 0; color:var(--stu-muted); font-size:.86rem; max-width:42rem; }
.stu-page .stu-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:.45rem; font-size:.82rem; font-weight:600; padding:.42rem .8rem; border:1px solid transparent; text-decoration:none; line-height:1.2; cursor:pointer; background:transparent; }
.stu-page .stu-btn-primary { background:var(--stu-navy); color:#fff; }
.stu-page .stu-btn-primary:hover { background:#163a66; color:#fff; }
.stu-page .stu-btn-ghost { background:#fff; color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-ghost:hover { background:var(--stu-soft); color:var(--stu-navy); }
.stu-page .stu-btn-pdf { background:#b91c1c; color:#fff; }
.stu-page .stu-btn-pdf:hover { background:#991b1b; color:#fff; }
.stu-page .stu-btn-pdf:disabled { opacity:.55; cursor:not-allowed; }
.stu-page .stu-panel { background:#fff; border:1px solid var(--stu-line); border-radius:.7rem; margin-bottom:1rem; overflow:hidden; }
.stu-page .stu-panel-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem; padding:.75rem 1rem; background:var(--stu-soft); border-bottom:1px solid var(--stu-line); }
.stu-page .stu-panel-head h2 { margin:0; font-size:.92rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-panel-body { padding:1rem; }
.stu-page .stu-label { display:block; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--stu-muted); margin-bottom:.28rem; }
.stu-page .stu-field .form-select { font-size:.86rem; border-color:#cfd9e6; border-radius:.45rem; min-height:2.2rem; }
.stu-page .stu-filter-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:.85rem 1rem; align-items:end; }
.stu-page .stu-span-3 { grid-column:span 3; }
.stu-page .stu-span-4 { grid-column:span 4; }
.stu-page .stu-span-auto { grid-column:span 3; }
.stu-page table.stu-table { width:100%; border-collapse:collapse; font-size:.84rem; margin:0; }
.stu-page table.stu-table th { background:var(--stu-navy); color:#fff; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.55rem .7rem; white-space:nowrap; border:0; position:sticky; top:0; z-index:1; }
.stu-page table.stu-table td { padding:.5rem .7rem; border-bottom:1px solid #eef2f7; vertical-align:middle; }
.stu-page table.stu-table tbody tr:hover td { background:#f7faff; }
.stu-page .stu-id { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.78rem; font-weight:700; color:#1d4ed8; }
.stu-page .stu-chip { display:inline-flex; padding:.18rem .5rem; border-radius:.3rem; font-size:.7rem; font-weight:700; background:#e2e8f0; color:#334155; }
.stu-page .stu-empty { text-align:center; padding:2.4rem 1rem; color:var(--stu-muted); }
.stu-page .stu-bulk-bar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; padding:.85rem 1rem; background:#fff7ed; border-bottom:1px solid #fed7aa; }
.stu-page .stu-bulk-bar strong { color:var(--stu-navy); }
@media (max-width:991.98px) {
    .stu-page .stu-span-3, .stu-page .stu-span-4, .stu-page .stu-span-auto { grid-column:span 12; }
}
</style>

<div class="container-fluid px-4 py-3 stu-page">
    <div class="stu-head">
        <div>
            <h1 class="stu-title">Registration checklist</h1>
            <p class="stu-sub">Select an academic year, optionally filter by department/course, then bulk-download one A4 checklist page per student as a single PDF.</p>
        </div>
        <a class="stu-btn stu-btn-ghost" href="<?php echo APP_URL; ?>/students">
            <i class="fas fa-arrow-left"></i>Students
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

    <div class="stu-panel">
        <div class="stu-panel-head">
            <h2><i class="fas fa-sliders-h me-2"></i>Select intake</h2>
        </div>
        <div class="stu-panel-body">
            <form method="GET" action="<?php echo APP_URL; ?>/students/intake-checklist" class="stu-filter-grid" id="icFilterForm">
                <div class="stu-field stu-span-3">
                    <label for="academic_year" class="stu-label">Academic year <span class="text-danger">*</span></label>
                    <select class="form-select" id="academic_year" name="academic_year" required>
                        <option value="">Select year</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?php echo $e($year); ?>" <?php echo ($filters['academic_year'] ?? '') === $year ? 'selected' : ''; ?>>
                                <?php echo $e($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($isHOD)): ?>
                <div class="stu-field stu-span-3">
                    <label for="department_id" class="stu-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">All departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $e($dept['department_id']); ?>" <?php echo $deptValue === (string) $dept['department_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="stu-field stu-span-3">
                    <label for="course_id" class="stu-label">Course</label>
                    <select class="form-select" id="course_id" name="course_id">
                        <option value="">All courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $e($course['course_id']); ?>"
                                    data-department="<?php echo $e($course['department_id'] ?? ''); ?>"
                                    <?php echo ($filters['course_id'] ?? '') === (string) $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo $e($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="stu-field stu-span-auto d-flex flex-wrap gap-2">
                    <button type="submit" class="stu-btn stu-btn-primary">
                        <i class="fas fa-users"></i>Show students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$yearChosen): ?>
        <div class="stu-panel">
            <div class="stu-empty">Choose an academic year, then show students to bulk-download the registration checklist PDF.</div>
        </div>
    <?php else: ?>
        <form method="POST" action="<?php echo APP_URL; ?>/students/intake-checklist" id="intakeChecklistForm">
            <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
            <input type="hidden" name="academic_year" value="<?php echo $e($filters['academic_year'] ?? ''); ?>">
            <input type="hidden" name="department_id" value="<?php echo $e($deptForForm); ?>">
            <input type="hidden" name="course_id" value="<?php echo $e($filters['course_id'] ?? ''); ?>">
            <input type="hidden" name="download_all" id="icDownloadAll" value="0">

            <div class="stu-panel" style="margin-bottom:0;">
                <div class="stu-bulk-bar">
                    <div>
                        <strong><?php echo $e($filters['academic_year'] ?? ''); ?></strong>
                        <?php if ($deptForForm !== ''): ?>
                            <span class="text-muted small">· Dept <?php echo $e($deptForForm); ?></span>
                        <?php endif; ?>
                        <div class="small text-muted mt-1">
                            <span class="stu-chip"><?php echo $rowCount; ?> students</span>
                            <span class="ms-2" id="icSelectedCount">0 selected</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($rowCount > 0): ?>
                        <button type="submit" class="stu-btn stu-btn-pdf" id="icDownloadAllBtn" title="Download checklist PDF for every student in this filter">
                            <i class="fas fa-file-pdf"></i>Download all PDF (<?php echo $rowCount; ?>)
                        </button>
                        <button type="submit" class="stu-btn stu-btn-primary" id="icDownloadBtn" disabled>
                            <i class="fas fa-file-pdf"></i>Download selected
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stu-panel-head">
                    <h2>Students</h2>
                    <input type="search" class="form-control form-control-sm" id="icSearch" placeholder="Search name or ID…" style="max-width:240px;">
                </div>
                <?php if (!$rows): ?>
                    <div class="stu-empty">No students found for this academic year / department.</div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height:560px; overflow:auto;">
                        <table class="stu-table" id="icTable">
                            <thead>
                                <tr>
                                    <th style="width:42px;">
                                        <input type="checkbox" class="form-check-input" id="icSelectAll" title="Select all" checked>
                                    </th>
                                    <th style="width:56px;">No</th>
                                    <th>Registration no.</th>
                                    <th>Student name</th>
                                    <th>Department</th>
                                    <th>Course</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $n = 1; foreach ($rows as $row): ?>
                                <tr data-search="<?php echo $e(strtolower($row['student_id'] . ' ' . $row['name'] . ' ' . $row['department'] . ' ' . $row['course'])); ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input ic-select" name="student_ids[]"
                                               value="<?php echo $e($row['student_id']); ?>" checked>
                                    </td>
                                    <td class="text-muted"><?php echo $n++; ?></td>
                                    <td><span class="stu-id"><?php echo $e($row['student_id']); ?></span></td>
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
    var allBtn = document.getElementById('icDownloadAllBtn');
    var downloadAllField = document.getElementById('icDownloadAll');
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
        if (countEl) countEl.textContent = checked + ' selected';
        if (btn) btn.disabled = checked === 0;
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

    function startBusy(el, label) {
        if (!el) return;
        el.disabled = true;
        el.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (label || 'Preparing PDF…');
        if (btn && el !== btn) btn.disabled = true;
        if (allBtn && el !== allBtn) allBtn.disabled = true;
    }

    if (allBtn) {
        allBtn.addEventListener('click', function () {
            if (downloadAllField) downloadAllField.value = '1';
            startBusy(allBtn, 'Preparing bulk PDF…');
        });
    }
    if (btn) {
        btn.addEventListener('click', function () {
            if (downloadAllField) downloadAllField.value = '0';
        });
    }

    form.addEventListener('submit', function (ev) {
        var isAll = downloadAllField && downloadAllField.value === '1';
        var checked = boxes().filter(function (cb) { return cb.checked; }).length;
        if (!isAll && checked === 0) {
            ev.preventDefault();
            alert('Select at least one student, or use Download all PDF.');
            return;
        }
        if (!isAll) {
            startBusy(btn, 'Preparing PDF…');
        }
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
