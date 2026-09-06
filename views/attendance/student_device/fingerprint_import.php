<?php
declare(strict_types=1);
/** @var array $urls */
/** @var array $departments */
/** @var array $courses */
/** @var array $academicYears */
/** @var array $groups */
/** @var string $departmentId */
/** @var string $courseId */
/** @var string $academicYear */
/** @var string $groupId */
/** @var string $courseMode */
/** @var array $students */
/** @var bool $run */
/** @var string $exportUrl */
/** @var bool $isHodScoped */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$departmentId = (string) ($departmentId ?? '');
$courseId = (string) ($courseId ?? '');
$academicYear = (string) ($academicYear ?? '');
$groupId = (string) ($groupId ?? '');
$courseMode = (string) ($courseMode ?? '');
$departments = $departments ?? [];
$courses = $courses ?? [];
$academicYears = $academicYears ?? [];
$groups = $groups ?? [];
$students = $students ?? [];
$run = !empty($run);
$exportUrl = (string) ($exportUrl ?? '#');
$isHodScoped = !empty($isHodScoped);
$isFullMode = in_array($courseMode, ['Full', 'full', 'Full Time'], true);
$isPartMode = in_array($courseMode, ['Part', 'part', 'Part Time'], true);

$studentDeviceSection = 'fingerprint-import';
$pageTitle = 'Student Information Excel Export';
$pageSubtitle = 'Filter authorized students, then download fingerprint-format Excel';

ob_start();
?>
<?php if ($run && count($students) > 0): ?>
    <a class="btn btn-success" href="<?php echo $e($exportUrl); ?>">
        <i class="fas fa-file-excel me-1"></i>Download Excel
    </a>
<?php endif; ?>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<section class="sd-excel-layout">
    <div class="sd-excel-hint">
        <div class="sd-excel-hint-title">Person ID rule</div>
        <div class="sd-excel-hint-text">
            Lists <strong>Active</strong> students only (enrollment status <strong>Following</strong>).
            Person ID from Student ID: year last 2 digits + last 6 characters
            (e.g. <code>2022/MET/4MA010</code> → <code>224MA010</code>).
            Organization = <strong>SLGTI</strong> · Gender: <strong>1</strong> Male, <strong>2</strong> Female.
            <?php if ($isHodScoped): ?>
                <span class="sd-excel-hint-badge">HOD · your department only</span>
            <?php endif; ?>
        </div>
    </div>

    <form method="get" action="<?php echo $e($urls['fingerprint_import'] ?? '#'); ?>" class="sd-excel-filters" id="sdStudentExcelForm">
        <input type="hidden" name="run" value="1">
        <div class="sd-excel-filters-grid">
            <div class="sd-field">
                <label class="form-label" for="sdFpDept">Department</label>
                <select id="sdFpDept" name="department_id" class="form-select" <?php echo $isHodScoped ? 'disabled' : ''; ?>>
                    <?php if (!$isHodScoped): ?>
                        <option value="">All</option>
                    <?php endif; ?>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $e($d['department_id'] ?? ''); ?>"
                            <?php echo $departmentId === (string) ($d['department_id'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo $e($d['department_name'] ?? $d['department_id'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isHodScoped): ?>
                    <input type="hidden" name="department_id" value="<?php echo $e($departmentId); ?>">
                <?php endif; ?>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdFpCourse">Course</label>
                <select id="sdFpCourse" name="course_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $e($c['course_id'] ?? ''); ?>"
                            <?php echo $courseId === (string) ($c['course_id'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo $e($c['course_name'] ?? $c['course_id'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdFpMode">Course mode</label>
                <select id="sdFpMode" name="course_mode" class="form-select">
                    <option value="">All</option>
                    <option value="Full" <?php echo $isFullMode ? 'selected' : ''; ?>>Full Time</option>
                    <option value="Part" <?php echo $isPartMode ? 'selected' : ''; ?>>Part Time</option>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdFpYear">Academic year</label>
                <select id="sdFpYear" name="academic_year" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($academicYears as $y): ?>
                        <option value="<?php echo $e($y); ?>" <?php echo $academicYear === (string) $y ? 'selected' : ''; ?>>
                            <?php echo $e($y); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdFpGroup">Group / Batch</label>
                <select id="sdFpGroup" name="group_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($groups as $g): ?>
                        <?php
                        $gid = (string) ($g['id'] ?? $g['group_id'] ?? '');
                        $glabel = (string) ($g['name'] ?? $g['group_name'] ?? $gid);
                        ?>
                        <option value="<?php echo $e($gid); ?>" <?php echo $groupId === $gid ? 'selected' : ''; ?>>
                            <?php echo $e($glabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field sd-excel-actions">
                <label class="form-label">&nbsp;</label>
                <div class="sd-excel-actions-row">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
                    <button type="button" class="btn btn-success" id="sdFpExportBtn">
                        <i class="fas fa-download me-1"></i>Download Excel
                    </button>
                </div>
            </div>
        </div>
    </form>

    <?php if (!$run): ?>
        <div class="sd-excel-empty">
            Choose any combination of filters (or leave All), then click <strong>Apply</strong> or <strong>Download Excel</strong>.
        </div>
    <?php else: ?>
        <div class="sd-excel-summary">
            <div class="sd-excel-summary-left">
                <span class="sd-excel-count"><?php echo number_format(count($students)); ?></span>
                <span class="sd-excel-count-label">active student(s)</span>
                <?php if ($courseMode !== ''): ?>
                    <span class="sd-excel-chip"><?php echo $isFullMode ? 'Full Time' : ($isPartMode ? 'Part Time' : $e($courseMode)); ?></span>
                <?php endif; ?>
            </div>
            <?php if (count($students) > 0): ?>
            <a class="btn btn-success" href="<?php echo $e($exportUrl); ?>">
                <i class="fas fa-file-excel me-1"></i>Download Excel
            </a>
            <?php endif; ?>
        </div>

        <div class="card sd-card sd-excel-table-card">
            <div class="table-responsive sd-table-wrap">
                <table class="table table-hover sd-events-table sd-excel-table mb-0">
                    <thead class="sticky-top">
                    <tr>
                        <th>Person ID</th>
                        <th>Organization</th>
                        <th>Person Name</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Student ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($students === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No matching students for your filters / permissions.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td class="col-emp"><?php echo $e($row['person_id'] ?? ''); ?></td>
                                <td>SLGTI</td>
                                <td class="col-name"><?php echo $e($row['person_name'] ?? ''); ?></td>
                                <td class="text-center"><?php echo $e($row['gender_code'] ?? ''); ?></td>
                                <td><?php echo $e($row['contact'] ?? ''); ?></td>
                                <td><?php echo $e($row['email'] ?? ''); ?></td>
                                <td><?php echo $e($row['student_id'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
<script>
(function () {
    var form = document.getElementById('sdStudentExcelForm');
    var dept = document.getElementById('sdFpDept');
    var course = document.getElementById('sdFpCourse');
    var year = document.getElementById('sdFpYear');
    var group = document.getElementById('sdFpGroup');
    var mode = document.getElementById('sdFpMode');
    var exportBtn = document.getElementById('sdFpExportBtn');
    var exportBase = <?php echo json_encode($urls['export_fingerprint_import'] ?? '', JSON_UNESCAPED_SLASHES); ?>;
    var isHod = <?php echo $isHodScoped ? 'true' : 'false'; ?>;
    if (!form) return;

    if (dept && !isHod) {
        dept.addEventListener('change', function () {
            if (course) course.value = '';
            if (group) group.value = '';
            form.submit();
        });
    }
    if (course) {
        course.addEventListener('change', function () {
            if (group) group.value = '';
            form.submit();
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var q = new URLSearchParams();
            var deptVal = isHod
                ? ((form.querySelector('input[name="department_id"]') || {}).value || '')
                : (dept ? dept.value : '');
            if (deptVal) q.set('department_id', deptVal);
            if (course && course.value) q.set('course_id', course.value);
            if (year && year.value) q.set('academic_year', year.value);
            if (group && group.value) q.set('group_id', group.value);
            if (mode && mode.value) q.set('course_mode', mode.value);
            var qs = q.toString();
            window.location.href = exportBase + (qs ? ('?' + qs) : '');
        });
    }
})();
</script>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
