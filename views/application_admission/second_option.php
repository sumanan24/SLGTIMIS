<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$fmt = static function ($n): string {
    if ($n === null || $n === '') {
        return '—';
    }
    if (is_numeric($n) && abs((float) $n - round((float) $n)) < 0.00001) {
        return (string) (int) round((float) $n);
    }

    return is_numeric($n) ? rtrim(rtrim(sprintf('%.2f', (float) $n), '0'), '.') : (string) $n;
};
$level = (string) ($level ?? '04');
$departmentId = trim((string) ($department_id ?? ''));
$courseId = trim((string) ($course_id ?? ''));
$departments = is_array($departments ?? null) ? $departments : [];
$courses = is_array($courses ?? null) ? $courses : [];
$students = is_array($students ?? null) ? $students : [];
$groups = is_array($groups ?? null) ? $groups : [];
$minMarks = (int) ($min_marks ?? ApplicationAdmissionCutoffModel::MARKS_MIN_SECOND_OPTION);
$filterQuery = (string) ($filter_query ?? ('level=' . rawurlencode($level)));
$listFiltered = $departmentId !== '' || $courseId !== '';
$aaNavActive = 'second-option';
?>
<style>
.aa-page { width: 100%; max-width: none; margin: 0; padding-bottom: 1.5rem; }
.aa-page-header { margin-bottom: 1rem; }
.aa-page-header h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.25rem; }
.aa-page-header .aa-sub { margin: 0; font-size: 0.8125rem; color: #6c757d; }
.aa-filters {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
}
.aa-filters label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}
.aa-filters .form-select { min-width: 12rem; }
.aa-list-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 0.85rem 1rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
.aa-list-toolbar .aa-filters { margin: 0; padding: 0; border: none; background: transparent; }
.aa-list-toolbar .form-select { min-width: 14rem; max-width: 22rem; }
.aa-list-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.aa-card {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    background: #fff;
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.aa-card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    padding: 0.75rem 1rem;
    background: #eef4f9;
    border-bottom: 1px solid #dee2e6;
}
.aa-card-head h2 { font-size: 1rem; font-weight: 600; margin: 0; }
.aa-card-head .aa-meta { font-size: 0.8125rem; color: #6c757d; }
.aa-table { width: 100%; margin: 0; }
.aa-table thead th {
    padding: 0.45rem 0.65rem;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #495057;
    background: #eef3f7;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    text-align: center;
    vertical-align: middle;
    position: sticky;
    top: 0;
    z-index: 1;
}
.aa-table thead th.aa-th-left { text-align: left; }
.aa-table tbody td {
    padding: 0.4rem 0.65rem;
    font-size: 0.8125rem;
    vertical-align: middle;
    border-bottom: 1px solid #eef1f4;
}
.aa-table tbody tr:nth-child(even) td { background: #f8fafc; }
.aa-table tbody tr:hover td { background: #eef6ff; }
.aa-col-no { width: 3rem; text-align: center; color: #6c757d; }
.aa-col-roll, .aa-col-nic {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
    white-space: nowrap;
}
.aa-col-name { min-width: 11rem; text-align: left; }
.aa-col-course { min-width: 8.5rem; text-align: left; }
.aa-col-center { text-align: center; }
.aa-col-marks { text-align: center; font-variant-numeric: tabular-nums; }
.aa-region-north, .aa-region-other, .aa-choice-2, .aa-choice-3, .aa-meet-yes, .aa-meet-no {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
}
.aa-region-north { background: #cfe2ff; color: #084298; }
.aa-region-other { background: #e9ecef; color: #495057; }
.aa-choice-2 { background: #fff3cd; color: #664d03; }
.aa-choice-3 { background: #e2d9f3; color: #432874; }
.aa-meet-yes { background: #d1e7dd; color: #0f5132; }
.aa-meet-no { background: #f8d7da; color: #842029; }
.aa-option-pick {
    background: #d1e7dd !important;
    color: #0f5132;
    font-weight: 600;
}
.aa-option-skip { color: #6c757d; }
.aa-empty { text-align: center; color: #6c757d; padding: 1.5rem 1rem !important; }
</style>

<div class="container-fluid px-3 px-md-4 aa-page">
    <div class="aa-page-header">
        <div>
            <h1>2nd option</h1>
            <p class="aa-sub">
                Course-wise list of students with marks from <strong><?php echo (int) $minMarks; ?></strong>
                up to (below) that course’s cutoff. They were not selected for 1st choice.
                Use <strong>2nd option</strong> and <strong>3rd option</strong> to consider them for another course.
                If 2nd option is <strong>Automobile Technician</strong> or <strong>Computer Hardware and Network Technician</strong>,
                use 3rd option instead. If 3rd is the same restricted course, do not consider.
                The course to consider is highlighted in <strong>green</strong>.
            </p>
        </div>
    </div>

    <?php require BASE_PATH . '/views/application_admission/_module_nav.php'; ?>

    <div class="aa-list-toolbar">
        <form method="get" action="<?php echo APP_URL; ?>/application-admission/second-option" class="aa-filters" id="aa_second_option_filter">
            <div>
                <label for="aa_so_level">NVQ level</label>
                <select name="level" id="aa_so_level" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="04" <?php echo $level === '04' ? 'selected' : ''; ?>>Level 04</option>
                    <option value="05" <?php echo $level === '05' ? 'selected' : ''; ?>>Level 05</option>
                </select>
            </div>
            <div>
                <label for="aa_list_dept">Department</label>
                <select name="department_id" id="aa_list_dept" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All departments</option>
                    <?php foreach ($departments as $d):
                        $did = trim((string) ($d['department_id'] ?? ''));
                    ?>
                    <option value="<?php echo $e($did); ?>" <?php echo strcasecmp($departmentId, $did) === 0 ? 'selected' : ''; ?>>
                        <?php echo $e($d['department_name'] ?? $did); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="aa_list_course">Course</label>
                <select name="course_id" id="aa_list_course" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $c):
                        $cidOpt = trim((string) ($c['course_id'] ?? ''));
                        $cDept = trim((string) ($c['department_id'] ?? ''));
                    ?>
                    <option value="<?php echo $e($cidOpt); ?>" data-dept="<?php echo $e($cDept); ?>"
                        <?php echo strcasecmp($courseId, $cidOpt) === 0 ? 'selected' : ''; ?>>
                        <?php echo $e($c['course_name'] ?? $cidOpt); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Show</button>
            </div>
            <?php if ($listFiltered): ?>
            <div>
                <label>&nbsp;</label>
                <a href="<?php echo APP_URL; ?>/application-admission/second-option?level=<?php echo $e($level); ?>" class="btn btn-sm btn-link">Clear</a>
            </div>
            <?php endif; ?>
            <p class="small text-muted mb-0"><?php echo count($students); ?> student(s)</p>
        </form>
        <div class="aa-list-actions">
            <a href="<?php echo APP_URL; ?>/application-admission/export-second-option?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
            <a href="<?php echo APP_URL; ?>/application-admission/pdf-second-option?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    <h2 class="h6 mb-2">Students above <?php echo (int) $minMarks; ?> and below cutoff — course wise</h2>
    <?php if ($groups === []): ?>
        <div class="aa-card"><p class="aa-empty mb-0">No students with marks from <?php echo (int) $minMarks; ?> up to (below) a course cutoff. Set cutoffs on the Cutoff marks page first.</p></div>
    <?php endif; ?>
    <?php foreach ($groups as $g):
        $list = is_array($g['students'] ?? null) ? $g['students'] : [];
    ?>
    <div class="aa-card">
        <div class="aa-card-head">
            <div>
                <h2><?php echo $e($g['course_name'] ?? ''); ?></h2>
                <div class="aa-meta"><?php echo $e($g['department_name'] ?? ''); ?> · not selected for 1st choice</div>
            </div>
            <span class="aa-meta"><?php echo (int) ($g['count'] ?? count($list)); ?> student(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table aa-table mb-0">
                <thead>
                    <tr>
                        <th class="aa-col-no">#</th>
                        <th class="aa-col-roll aa-th-left">Roll / Index</th>
                        <th class="aa-th-left aa-col-name">Name</th>
                        <th class="aa-col-nic aa-th-left">NIC</th>
                        <th class="aa-col-marks">Marks</th>
                        <th class="aa-col-marks">Cutoff</th>
                        <th class="aa-th-left aa-col-course">2nd option</th>
                        <th class="aa-th-left aa-col-course">3rd option</th>
                        <th class="aa-th-left">Province</th>
                        <th class="aa-col-center">Region</th>
                    </tr>
                </thead>
                <tbody>
                <?php $n = 0; foreach ($list as $row): $n++;
                    $roll = trim((string) ($row['roll_number'] ?? ''));
                    $secondName = trim((string) ($row['second_course_name'] ?? ''));
                    $thirdName = trim((string) ($row['third_course_name'] ?? ''));
                    $isNorth = ($row['region'] ?? '') === 'Northern';
                    $consider = (int) ($row['consider_choice'] ?? 0);
                ?>
                    <tr>
                        <td class="aa-col-no"><?php echo $n; ?></td>
                        <td class="aa-col-roll"><?php echo $roll !== '' ? $e($roll) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="aa-col-name"><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td class="aa-col-nic"><?php echo $e($row['student_nic'] ?? ''); ?></td>
                        <td class="aa-col-marks"><strong><?php echo $e($fmt($row['marks_num'] ?? '')); ?></strong></td>
                        <td class="aa-col-marks"><?php echo $e($fmt($row['first_cutoff'] ?? null)); ?></td>
                        <td class="aa-col-course<?php echo $consider === 2 ? ' aa-option-pick' : (!empty($row['second_restricted']) ? ' aa-option-skip' : ''); ?>">
                            <?php echo $secondName !== '' ? $e($secondName) : '<span class="text-muted">—</span>'; ?>
                        </td>
                        <td class="aa-col-course<?php echo $consider === 3 ? ' aa-option-pick' : (!empty($row['third_restricted']) ? ' aa-option-skip' : ''); ?>">
                            <?php echo $thirdName !== '' ? $e($thirdName) : '<span class="text-muted">—</span>'; ?>
                        </td>
                        <td><?php echo $e($row['student_province'] ?? '—'); ?></td>
                        <td class="aa-col-center">
                            <?php if ($isNorth): ?>
                                <span class="aa-region-north">Northern</span>
                            <?php else: ?>
                                <span class="aa-region-other">Other</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<script>
(function () {
    var dept = document.getElementById('aa_list_dept');
    var course = document.getElementById('aa_list_course');
    if (!dept || !course) return;
    function filterCourses() {
        var d = dept.value;
        var opts = course.querySelectorAll('option[data-dept]');
        var keep = course.value;
        var visibleKeep = !keep;
        opts.forEach(function (opt) {
            var show = !d || opt.getAttribute('data-dept') === d;
            opt.hidden = !show;
            opt.disabled = !show;
            if (show && opt.value === keep) visibleKeep = true;
        });
        if (keep && !visibleKeep) course.value = '';
    }
    dept.addEventListener('change', filterCourses);
    filterCourses();
})();
</script>
