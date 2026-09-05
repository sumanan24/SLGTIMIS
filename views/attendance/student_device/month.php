<?php
declare(strict_types=1);
/** @var array $urls */
/** @var string $reportMonth */
/** @var string $departmentId */
/** @var string $courseId */
/** @var string $academicYear */
/** @var string $studentId */
/** @var string $courseMode */
/** @var bool $eligibleOnly */
/** @var array $departments */
/** @var array $courses */
/** @var array $academicYears */
/** @var array $report */
/** @var string $monthDisplay */
/** @var string $scopeLabel */
/** @var bool $reportRun */
/** @var int $reportPage */
/** @var int $reportTotalPages */
/** @var int $reportTotalStudents */
/** @var string $exportMonthUrl */
/** @var bool $isHodScoped */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$reportMonth = (string) ($reportMonth ?? date('Y-m'));
$departmentId = (string) ($departmentId ?? '');
$courseId = (string) ($courseId ?? '');
$academicYear = (string) ($academicYear ?? '');
$studentId = (string) ($studentId ?? '');
$courseMode = (string) ($courseMode ?? '');
$eligibleOnly = !empty($eligibleOnly);
$departments = $departments ?? [];
$courses = $courses ?? [];
$academicYears = $academicYears ?? [];
$report = $report ?? [];
$students = $report['students'] ?? [];
$columns = $report['columns'] ?? [];
$summary = $report['summary'] ?? [];
$monthDisplay = (string) ($monthDisplay ?? '');
$scopeLabel = (string) ($scopeLabel ?? 'All students');
$reportRun = !empty($reportRun);
$reportPage = max(1, (int) ($reportPage ?? 1));
$reportTotalPages = max(0, (int) ($reportTotalPages ?? 0));
$reportTotalStudents = max(0, (int) ($reportTotalStudents ?? 0));
$exportMonthUrl = (string) ($exportMonthUrl ?? '#');
$isHodScoped = !empty($isHodScoped);

$queryBase = [
    'run' => '1',
    'report_month' => $reportMonth,
    'department_id' => $departmentId,
    'course_id' => $courseId,
    'academic_year' => $academicYear,
    'student_id' => $studentId,
    'course_mode' => $courseMode,
    'eligible_only' => $eligibleOnly ? '1' : '0',
];
$pageUrl = static function (int $p) use ($urls, $queryBase, $e): string {
    $q = $queryBase;
    $q['page'] = (string) $p;
    return $e($urls['month'] . '?' . http_build_query($q));
};

$modeShort = static function (string $label): string {
    $n = strtolower(trim($label));
    if (str_contains($n, 'full')) {
        return 'FT';
    }
    if (str_contains($n, 'part')) {
        return 'PT';
    }
    return $label !== '' ? $label : '—';
};

$studentDeviceSection = 'month';
$pageTitle = 'Month report';
$pageSubtitle = '';

ob_start();
?>
<div class="sd-header-actions">
    <?php if ($reportRun && $reportTotalStudents > 0): ?>
        <a class="btn btn-success" href="<?php echo $e($exportMonthUrl); ?>">
            <i class="fas fa-file-excel me-1"></i>Export
        </a>
    <?php endif; ?>
</div>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<section class="sd-month-layout">
    <form method="get" action="<?php echo $e($urls['month']); ?>" class="sd-month-filters" id="sdMonthReportForm">
        <input type="hidden" name="run" value="1">
        <div class="sd-month-filters-grid">
            <div class="sd-field">
                <label class="form-label" for="sdReportMonth">Month</label>
                <input type="month" id="sdReportMonth" name="report_month" class="form-control"
                       value="<?php echo $e($reportMonth); ?>" required>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdDept">Department</label>
                <select id="sdDept" name="department_id" class="form-select" <?php echo $isHodScoped ? 'disabled' : ''; ?>>
                    <?php if (!$isHodScoped): ?>
                        <option value="">All</option>
                    <?php endif; ?>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $e($d['department_id'] ?? ''); ?>"
                            <?php echo $departmentId === ($d['department_id'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo $e($d['department_name'] ?? $d['department_id'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isHodScoped): ?>
                    <input type="hidden" name="department_id" value="<?php echo $e($departmentId); ?>">
                <?php endif; ?>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdCourse">Course</label>
                <select id="sdCourse" name="course_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $e($c['course_id'] ?? ''); ?>"
                            <?php echo $courseId === ($c['course_id'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo $e($c['course_name'] ?? $c['course_id'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdCourseMode">Mode</label>
                <select id="sdCourseMode" name="course_mode" class="form-select">
                    <option value="">All</option>
                    <option value="Full" <?php echo in_array($courseMode, ['Full', 'full', 'Full Time'], true) ? 'selected' : ''; ?>>Full Time</option>
                    <option value="Part" <?php echo in_array($courseMode, ['Part', 'part', 'Part Time'], true) ? 'selected' : ''; ?>>Part Time</option>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdYear">Year</label>
                <select id="sdYear" name="academic_year" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($academicYears as $y): ?>
                        <option value="<?php echo $e($y); ?>" <?php echo $academicYear === $y ? 'selected' : ''; ?>>
                            <?php echo $e($y); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdEligible">Allowance</label>
                <select id="sdEligible" name="eligible_only" class="form-select">
                    <option value="0" <?php echo !$eligibleOnly ? 'selected' : ''; ?>>All active</option>
                    <option value="1" <?php echo $eligibleOnly ? 'selected' : ''; ?>>Eligible</option>
                </select>
            </div>
            <div class="sd-field sd-field-actions">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i>Apply
                </button>
            </div>
        </div>
    </form>

    <?php if (!$reportRun): ?>
        <div class="sd-month-empty">Select filters and click Apply.</div>
    <?php else: ?>
        <div class="sd-month-summary">
            <div class="sd-month-summary-main">
                <strong><?php echo $e($monthDisplay !== '' ? $monthDisplay : $reportMonth); ?></strong>
                <span class="sd-month-dot">·</span>
                <span><?php echo $e($scopeLabel); ?></span>
            </div>
            <div class="sd-month-summary-stats">
                <span><b><?php echo number_format($reportTotalStudents); ?></b> students</span>
                <span><b><?php echo number_format((int) ($summary['above_90'] ?? 0)); ?></b> ≥90%</span>
                <span><b><?php echo number_format((int) ($summary['above_75'] ?? 0)); ?></b> ≥75%</span>
                <span class="sd-month-total">Rs <?php echo number_format((float) ($summary['total_allowance'] ?? 0), 0); ?></span>
            </div>
            <div class="sd-month-legend">
                <span class="sd-mx-cell present">1</span> Present
                <span class="sd-mx-cell absent">0</span> Absent
                <span class="sd-mx-cell holiday">H</span> Holiday
            </div>
        </div>

        <?php if ($reportTotalPages > 1): ?>
            <div class="sd-month-pager">
                <span>Page <?php echo (int) $reportPage; ?> / <?php echo (int) $reportTotalPages; ?></span>
                <div class="sd-month-pager-actions">
                    <?php if ($reportPage > 1): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo $pageUrl($reportPage - 1); ?>">Prev</a>
                    <?php endif; ?>
                    <?php if ($reportPage < $reportTotalPages): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo $pageUrl($reportPage + 1); ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($students === []): ?>
            <div class="sd-month-empty">No active students for this filter.</div>
        <?php else: ?>
            <div class="sd-month-table-card">
                <div class="sd-matrix-wrap">
                    <table class="sd-matrix-table">
                        <thead>
                        <tr>
                            <th class="sd-mx-sticky sd-mx-id">Student ID</th>
                            <th class="sd-mx-sticky-2 sd-mx-name">Name</th>
                            <th class="sd-mx-mode">Mode</th>
                            <th class="sd-mx-bank">Bank</th>
                            <th class="sd-mx-acc">Account</th>
                            <th class="sd-mx-branch">Branch</th>
                            <?php foreach ($columns as $col): ?>
                                <th class="sd-mx-day" title="<?php echo $e(($col['date'] ?? '') . ' · ' . ($col['day_name'] ?? '')); ?>">
                                    <span class="sd-mx-day-num"><?php echo $e($col['day'] ?? ''); ?></span>
                                    <span class="sd-mx-dow"><?php echo $e($col['day_name'] ?? ''); ?></span>
                                </th>
                            <?php endforeach; ?>
                            <th class="sd-mx-num">Days</th>
                            <th class="sd-mx-num">P</th>
                            <th class="sd-mx-num">%</th>
                            <th class="sd-mx-allow">Allowance</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($students as $st): ?>
                            <?php
                            $pct = (float) ($st['attendance_percentage'] ?? 0);
                            $pctClass = $pct >= 90 ? 'sd-pct-high' : ($pct >= 75 ? 'sd-pct-mid' : 'sd-pct-low');
                            ?>
                            <tr>
                                <td class="sd-mx-sticky sd-mx-id"><?php echo $e($st['student_id'] ?? ''); ?></td>
                                <td class="sd-mx-sticky-2 sd-mx-name"><?php echo $e($st['student_name'] ?? ''); ?></td>
                                <td class="sd-mx-mode"><?php echo $e($modeShort((string) ($st['course_mode_label'] ?? ''))); ?></td>
                                <td class="sd-mx-bank"><?php echo $e(($st['bank_name'] ?? '') !== '' ? $st['bank_name'] : '—'); ?></td>
                                <td class="sd-mx-acc"><?php echo $e(($st['bank_account_no'] ?? '') !== '' ? $st['bank_account_no'] : '—'); ?></td>
                                <td class="sd-mx-branch"><?php echo $e(($st['bank_branch'] ?? '') !== '' ? $st['bank_branch'] : '—'); ?></td>
                                <?php foreach ($columns as $col): ?>
                                    <?php
                                    $ymd = (string) ($col['date'] ?? '');
                                    $cell = (string) (($st['day_by_day'][$ymd] ?? ''));
                                    if ($cell === '1') {
                                        $cls = 'present';
                                        $label = '1';
                                    } elseif ($cell === '0') {
                                        $cls = 'absent';
                                        $label = '0';
                                    } elseif ($cell === 'H') {
                                        $cls = 'holiday';
                                        $label = 'H';
                                    } else {
                                        $cls = 'empty';
                                        $label = '';
                                    }
                                    ?>
                                    <td class="sd-mx-day-cell"><span class="sd-mx-cell <?php echo $cls; ?>"><?php echo $e($label); ?></span></td>
                                <?php endforeach; ?>
                                <td class="sd-mx-num"><?php echo (int) ($st['effective_working_days'] ?? 0); ?></td>
                                <td class="sd-mx-num"><?php echo (int) ($st['present_days'] ?? 0); ?></td>
                                <td class="sd-mx-num <?php echo $pctClass; ?>"><?php echo $e(number_format($pct, 1)); ?></td>
                                <td class="sd-mx-allow"><?php echo number_format((float) ($st['allowance'] ?? 0), 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="sd-mx-total">
                            <td class="sd-mx-sticky" colspan="6">Total</td>
                            <?php foreach ($columns as $_): ?>
                                <td></td>
                            <?php endforeach; ?>
                            <td class="sd-mx-num"><?php echo (int) ($summary['effective_working_days'] ?? 0); ?></td>
                            <td class="sd-mx-num"><?php echo number_format((int) ($summary['present'] ?? 0)); ?></td>
                            <td></td>
                            <td class="sd-mx-allow sd-mx-allow-total"><?php echo number_format((float) ($summary['total_allowance'] ?? 0), 0); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script>
document.getElementById('sdDept')?.addEventListener('change', function () {
    var course = document.getElementById('sdCourse');
    if (course) course.value = '';
    var run = document.querySelector('#sdMonthReportForm input[name="run"]');
    if (run) run.value = '0';
    document.getElementById('sdMonthReportForm')?.submit();
});
</script>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
