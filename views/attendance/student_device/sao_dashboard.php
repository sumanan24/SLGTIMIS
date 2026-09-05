<?php
declare(strict_types=1);
/** @var array $urls */
/** @var bool $canManageDevice */
/** @var bool $isHodScoped */
/** @var string $reportMonth */
/** @var string $departmentId */
/** @var string $groupId */
/** @var string $studentId */
/** @var string $statusFilter */
/** @var array $departments */
/** @var array $groups */
/** @var array $studentsForFilter */
/** @var array $dashboard */
/** @var string $monthDisplay */
/** @var bool $reportRun */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$canManageDevice = !empty($canManageDevice);
$isHodScoped = !empty($isHodScoped);
$reportMonth = (string) ($reportMonth ?? date('Y-m'));
$departmentId = (string) ($departmentId ?? '');
$groupId = (string) ($groupId ?? '');
$studentId = (string) ($studentId ?? '');
$statusFilter = (string) ($statusFilter ?? 'flagged');
$departments = $departments ?? [];
$groups = $groups ?? [];
$studentsForFilter = $studentsForFilter ?? [];
$dashboard = $dashboard ?? ['flagged' => [], 'summary' => []];
$summary = $dashboard['summary'] ?? [];
$flagged = $dashboard['flagged'] ?? [];
$threshold = (int) ($dashboard['consecutive_threshold'] ?? 3);
$monthDisplay = (string) ($monthDisplay ?? '');
$reportRun = !empty($reportRun);

$studentDeviceSection = 'sao';
$pageTitle = 'SAO dashboard';
$pageSubtitle = '';

ob_start();
?>
<div class="sd-header-actions">
    <a class="btn btn-outline-secondary" href="<?php echo $e($urls['month']); ?>">
        <i class="fas fa-calendar-alt me-1"></i>Month report
    </a>
    <?php if ($canManageDevice): ?>
        <a class="btn btn-outline-secondary" href="<?php echo $e($urls['holidays']); ?>">
            <i class="fas fa-umbrella-beach me-1"></i>Leave
        </a>
    <?php endif; ?>
</div>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<section class="sd-sao-layout">
    <form method="get" action="<?php echo $e($urls['sao']); ?>" class="sd-sao-filters" id="sdSaoDashForm">
        <input type="hidden" name="run" value="1">
        <div class="sd-sao-filters-grid">
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
                <label class="form-label" for="sdGroup">Group</label>
                <select id="sdGroup" name="group_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?php echo $e($g['id'] ?? ''); ?>"
                            <?php echo (string) $groupId === (string) ($g['id'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo $e($g['name'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdStudent">Student</label>
                <select id="sdStudent" name="student_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($studentsForFilter as $st): ?>
                        <option value="<?php echo $e($st['student_id'] ?? ''); ?>"
                            <?php echo $studentId === ($st['student_id'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo $e(($st['student_id'] ?? '') . ' — ' . ($st['student_name'] ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="sdStatus">Status</label>
                <select id="sdStatus" name="status" class="form-select">
                    <option value="flagged" <?php echo $statusFilter === 'flagged' ? 'selected' : ''; ?>>Flagged (≥<?php echo $threshold; ?>)</option>
                    <option value="low" <?php echo $statusFilter === 'low' ? 'selected' : ''; ?>>Low &lt;80%</option>
                    <option value="ok" <?php echo $statusFilter === 'ok' ? 'selected' : ''; ?>>OK</option>
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
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
        <div class="sd-sao-empty">Select filters and click Apply.</div>
    <?php else: ?>
        <div class="sd-sao-summary">
            <div class="sd-sao-summary-main">
                <strong><?php echo $e($monthDisplay !== '' ? $monthDisplay : $reportMonth); ?></strong>
                <span class="sd-sao-dot">·</span>
                <span>Alert ≥<?php echo (int) $threshold; ?> consecutive absences</span>
            </div>
            <div class="sd-sao-kpis">
                <div class="sd-sao-kpi">
                    <span class="sd-sao-kpi-label">Students</span>
                    <span class="sd-sao-kpi-value"><?php echo number_format((int) ($summary['students'] ?? 0)); ?></span>
                </div>
                <div class="sd-sao-kpi">
                    <span class="sd-sao-kpi-label">Days</span>
                    <span class="sd-sao-kpi-value"><?php echo number_format((int) ($summary['working_days'] ?? 0)); ?></span>
                </div>
                <div class="sd-sao-kpi is-ok">
                    <span class="sd-sao-kpi-label">Present</span>
                    <span class="sd-sao-kpi-value"><?php echo number_format((int) ($summary['present'] ?? 0)); ?></span>
                </div>
                <div class="sd-sao-kpi is-bad">
                    <span class="sd-sao-kpi-label">Absent</span>
                    <span class="sd-sao-kpi-value"><?php echo number_format((int) ($summary['absent'] ?? 0)); ?></span>
                </div>
                <div class="sd-sao-kpi is-warn">
                    <span class="sd-sao-kpi-label">Flagged</span>
                    <span class="sd-sao-kpi-value"><?php echo number_format((int) ($summary['flagged'] ?? 0)); ?></span>
                </div>
                <div class="sd-sao-kpi">
                    <span class="sd-sao-kpi-label">Avg %</span>
                    <span class="sd-sao-kpi-value"><?php echo $e(number_format((float) ($summary['avg_attendance_pct'] ?? 0), 1)); ?></span>
                </div>
            </div>
        </div>

        <div class="sd-sao-table-card">
            <div class="sd-sao-table-head">
                <strong>Students</strong>
                <span><?php echo number_format(count($flagged)); ?></span>
            </div>

            <?php if ($flagged === []): ?>
                <div class="sd-sao-empty sd-sao-empty-in">No matching students.</div>
            <?php else: ?>
                <div class="sd-sao-table-wrap d-none d-lg-block">
                    <table class="sd-sao-table">
                        <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Group</th>
                            <th>Leave dates</th>
                            <th class="text-center">Days</th>
                            <th class="text-center">%</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($flagged as $row): ?>
                            <?php
                            $warnQs = http_build_query([
                                'student_id' => $row['student_id'] ?? '',
                                'report_month' => $reportMonth,
                                'format' => 'preview',
                            ]);
                            $pct = (float) ($row['attendance_pct'] ?? 0);
                            $pctClass = $pct >= 90 ? 'sd-pct-high' : ($pct >= 75 ? 'sd-pct-mid' : 'sd-pct-low');
                            ?>
                            <tr>
                                <td class="sd-sao-id"><?php echo $e($row['student_id'] ?? ''); ?></td>
                                <td class="sd-sao-name"><?php echo $e($row['student_name'] ?? ''); ?></td>
                                <td><?php echo $e($row['department_name'] ?? '—'); ?></td>
                                <td><?php echo $e(($row['group_name'] ?? '') !== '' && ($row['group_name'] ?? '') !== '—' ? $row['group_name'] : '—'); ?></td>
                                <td class="sd-sao-dates"><?php echo $e($row['leave_dates_label'] ?? '—'); ?></td>
                                <td class="text-center fw-semibold"><?php echo (int) ($row['leave_days'] ?? 0); ?></td>
                                <td class="text-center <?php echo $pctClass; ?>"><?php echo $e(number_format($pct, 1)); ?></td>
                                <td>
                                    <?php if (!empty($row['flagged'])): ?>
                                        <span class="sd-sao-tag is-flag">Flagged</span>
                                    <?php else: ?>
                                        <span class="sd-sao-tag is-other"><?php echo $e($row['status_label'] ?? ''); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($urls['warning'] . '?' . $warnQs); ?>">Letter</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="sd-sao-mobile d-lg-none">
                    <?php foreach ($flagged as $row): ?>
                        <?php
                        $warnQs = http_build_query([
                            'student_id' => $row['student_id'] ?? '',
                            'report_month' => $reportMonth,
                            'format' => 'preview',
                        ]);
                        ?>
                        <article class="sd-sao-mobile-card">
                            <div class="sd-sao-mobile-top">
                                <div class="min-w-0">
                                    <div class="sd-sao-name"><?php echo $e($row['student_name'] ?? ''); ?></div>
                                    <div class="sd-sao-id"><?php echo $e($row['student_id'] ?? ''); ?></div>
                                </div>
                                <span class="sd-sao-tag is-flag"><?php echo (int) ($row['leave_days'] ?? 0); ?>d</span>
                            </div>
                            <div class="sd-sao-mobile-meta">
                                <?php echo $e($row['department_name'] ?? ''); ?>
                                · <?php echo $e(number_format((float) ($row['attendance_pct'] ?? 0), 1)); ?>%
                            </div>
                            <div class="sd-sao-dates"><?php echo $e($row['leave_dates_label'] ?? ''); ?></div>
                            <a class="btn btn-sm btn-outline-primary mt-2" href="<?php echo $e($urls['warning'] . '?' . $warnQs); ?>">Letter</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<script>
document.getElementById('sdDept')?.addEventListener('change', function () {
    var g = document.getElementById('sdGroup');
    var s = document.getElementById('sdStudent');
    if (g) g.value = '';
    if (s) s.value = '';
    var run = document.querySelector('#sdSaoDashForm input[name="run"]');
    if (run) run.value = '0';
    document.getElementById('sdSaoDashForm')?.submit();
});
</script>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
