<?php
declare(strict_types=1);
$e = static function ($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$filters = $filters ?? [];
$rows = $rows ?? [];
$yearChosen = ($filters['academic_year'] ?? '') !== '';
$deptValue = (string) ($filters['department_id'] ?? '');
?>
<style>
.stu-page { --stu-navy:#0b2545; --stu-ink:#1e293b; --stu-muted:#64748b; --stu-line:#dbe4f0; --stu-soft:#f5f8fd; --stu-accent:#1d4ed8; color:var(--stu-ink); }
.stu-page .stu-head { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
.stu-page .stu-title { margin:0; font-size:1.35rem; font-weight:700; color:var(--stu-navy); letter-spacing:-.02em; }
.stu-page .stu-sub { margin:.2rem 0 0; color:var(--stu-muted); font-size:.86rem; max-width:42rem; }
.stu-page .stu-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:.45rem; font-size:.82rem; font-weight:600; padding:.42rem .8rem; border:1px solid transparent; text-decoration:none; line-height:1.2; cursor:pointer; background:transparent; }
.stu-page .stu-btn-primary { background:var(--stu-navy); color:#fff; }
.stu-page .stu-btn-primary:hover { background:#163a66; color:#fff; }
.stu-page .stu-btn-ghost { background:#fff; color:var(--stu-navy); border-color:var(--stu-line); }
.stu-page .stu-btn-ghost:hover { background:var(--stu-soft); color:var(--stu-navy); }
.stu-page .stu-panel { background:#fff; border:1px solid var(--stu-line); border-radius:.7rem; margin-bottom:1rem; overflow:hidden; }
.stu-page .stu-panel-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem; padding:.75rem 1rem; background:var(--stu-soft); border-bottom:1px solid var(--stu-line); }
.stu-page .stu-panel-head h2 { margin:0; font-size:.92rem; font-weight:700; color:var(--stu-navy); }
.stu-page .stu-panel-body { padding:1rem; }
.stu-page .stu-label { display:block; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:var(--stu-muted); margin-bottom:.28rem; }
.stu-page .stu-field .form-select { font-size:.86rem; border-color:#cfd9e6; border-radius:.45rem; min-height:2.2rem; }
.stu-page .stu-filter-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:.85rem 1rem; align-items:end; }
.stu-page .stu-span-3 { grid-column:span 3; }
.stu-page .stu-span-4 { grid-column:span 4; }
.stu-page .stu-span-auto { grid-column:span 2; }
.stu-page table.stu-table { width:100%; border-collapse:collapse; font-size:.84rem; margin:0; }
.stu-page table.stu-table th { background:var(--stu-navy); color:#fff; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.55rem .7rem; white-space:nowrap; border:0; }
.stu-page table.stu-table td { padding:.55rem .7rem; border-bottom:1px solid #eef2f7; vertical-align:middle; }
.stu-page table.stu-table tbody tr:hover td { background:#f7faff; }
.stu-page .stu-id { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.78rem; font-weight:700; color:var(--stu-accent); }
.stu-page .stu-chip { display:inline-flex; padding:.18rem .5rem; border-radius:.3rem; font-size:.7rem; font-weight:700; background:#e2e8f0; color:#334155; }
.stu-page .stu-chip.ok { background:#dcfce7; color:#166534; }
.stu-page .stu-chip.warn { background:#fef3c7; color:#92400e; }
.stu-page .stu-chip.bad { background:#fee2e2; color:#991b1b; }
.stu-page .stu-empty { text-align:center; padding:2.4rem 1rem; color:var(--stu-muted); }
@media (max-width:991.98px) {
    .stu-page .stu-span-3, .stu-page .stu-span-4, .stu-page .stu-span-auto { grid-column:span 12; }
}
</style>

<div class="container-fluid px-4 py-3 stu-page">
    <div class="stu-head">
        <div>
            <h1 class="stu-title">Registration checklist</h1>
            <p class="stu-sub">Seven registration stations, automatic fee calculation, payment verification, and a one-page A4 printable form per student.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="stu-btn stu-btn-ghost" href="<?php echo APP_URL; ?>/students">
                <i class="fas fa-arrow-left"></i>Students
            </a>
        </div>
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
            <form method="GET" action="<?php echo APP_URL; ?>/students/registration-checklist" class="stu-filter-grid">
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
                <div class="stu-field stu-span-4">
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
                <div class="stu-field stu-span-auto">
                    <button type="submit" class="stu-btn stu-btn-primary w-100 justify-content-center">
                        <i class="fas fa-users"></i>Show students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$yearChosen): ?>
        <div class="stu-panel">
            <div class="stu-empty">Choose an academic year to list students for registration checklists.</div>
        </div>
    <?php else: ?>
        <div class="stu-panel" style="margin-bottom:0;">
            <div class="stu-panel-head">
                <h2>Students <span class="stu-chip ms-1"><?php echo count($rows); ?></span></h2>
            </div>
            <?php if (empty($rows)): ?>
                <div class="stu-empty">No current students found for this selection.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="stu-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course / NVQ</th>
                                <th>Province</th>
                                <th>Fee</th>
                                <th>Progress</th>
                                <th>Payment</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r):
                                $prog = $r['progress'] ?? [];
                                $pay = (string) ($r['payment_status'] ?? 'Unpaid');
                                $payCls = $pay === 'Paid' ? 'ok' : ($pay === 'Partial' ? 'warn' : 'bad');
                            ?>
                            <tr>
                                <td><span class="stu-id"><?php echo $e($r['student_id']); ?></span></td>
                                <td><?php echo $e($r['name']); ?></td>
                                <td>
                                    <div><?php echo $e($r['course']); ?></div>
                                    <div class="small text-muted">NVQ <?php echo $e($r['nvq'] ?: '—'); ?></div>
                                </td>
                                <td><?php echo $e($r['province'] ?: '—'); ?></td>
                                <td>Rs. <?php echo number_format((float) $r['applicable_fee'], 2); ?></td>
                                <td>
                                    <span class="stu-chip"><?php echo (int) ($prog['stations_done'] ?? 0); ?>/7 stations</span>
                                    <?php if (!empty($r['final_confirmed'])): ?>
                                        <span class="stu-chip ok ms-1">Final</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="stu-chip <?php echo $payCls; ?>"><?php echo $e($pay); ?></span></td>
                                <td class="text-end">
                                    <a class="stu-btn stu-btn-primary"
                                       href="<?php echo APP_URL; ?>/students/registration-checklist/form?student_id=<?php echo urlencode($r['student_id']); ?>&academic_year=<?php echo urlencode($r['academic_year']); ?>">
                                        <i class="fas fa-clipboard-check"></i><?php echo !empty($r['has_saved']) ? 'Open' : 'Start'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dept = document.getElementById('department_id');
    var course = document.getElementById('course_id');
    if (dept && course) {
        var applyDept = function() {
            Array.prototype.forEach.call(course.options, function(opt) {
                var d = opt.getAttribute('data-department');
                opt.hidden = !!(opt.value && dept.value && d !== dept.value);
            });
            if (course.selectedOptions[0] && course.selectedOptions[0].hidden) { course.value = ''; }
        };
        dept.addEventListener('change', applyDept);
        applyDept();
    }
});
</script>
