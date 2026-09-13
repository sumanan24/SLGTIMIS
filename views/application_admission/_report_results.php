<?php
if (!isset($e) || !is_callable($e)) {
    $e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
}
if (!isset($fmt) || !is_callable($fmt)) {
    $fmt = static function ($n): string {
        if ($n === null || $n === '') {
            return '—';
        }
        if (is_numeric($n) && abs((float) $n - round((float) $n)) < 0.00001) {
            return (string) (int) round((float) $n);
        }

        return is_numeric($n) ? rtrim(rtrim(sprintf('%.2f', (float) $n), '0'), '.') : (string) $n;
    };
}
if (!isset($fmtCut) || !is_callable($fmtCut)) {
    $fmtCut = static function (array $card) use ($fmt): string {
        $parts = is_array($card['cutoff_parts'] ?? null) ? $card['cutoff_parts'] : [];
        if ($parts === []) {
            return '—';
        }
        $bits = [];
        foreach ($parts as $p) {
            $pair = 'N ' . $fmt($p['cutoff_northern'] ?? null) . ' / O ' . $fmt($p['cutoff_other'] ?? null);
            $label = trim((string) ($p['label'] ?? ''));
            $bits[] = ($label !== '' && strcasecmp($label, 'All languages') !== 0) ? ($label . ': ' . $pair) : $pair;
        }

        return implode(' · ', $bits);
    };
}
$level = trim((string) ($level ?? ''));
$departmentId = trim((string) ($department_id ?? $departmentId ?? ''));
$courseId = trim((string) ($course_id ?? $courseId ?? ''));
$groups = is_array($groups ?? null) ? $groups : [];
$failGroups = is_array($fail_groups ?? $failGroups ?? null) ? ($fail_groups ?? $failGroups) : [];
$filterQuery = (string) ($filter_query ?? $filterQuery ?? '');
$total = (int) ($total_students ?? $total ?? 0);
$totalFail = (int) ($total_fail ?? $totalFail ?? 0);
$choiceCounts = is_array($choice_counts ?? $choiceCounts ?? null) ? ($choice_counts ?? $choiceCounts) : [];
$failCounts = is_array($fail_counts ?? $failCounts ?? null) ? ($fail_counts ?? $failCounts) : [];
$minMarks = (int) ($min_marks ?? $minMarks ?? 30);
$overview = is_array($overview ?? null) ? $overview : [];
$ovTotals = is_array($overview['totals'] ?? null) ? $overview['totals'] : [];
$ovDepartments = is_array($overview['departments'] ?? null) ? $overview['departments'] : [];
$ovCourses = is_array($overview['courses'] ?? null) ? $overview['courses'] : [];
$hasFilter = !empty($has_filter);
$reportBase = rtrim(APP_URL, '/') . '/application-admission/report';
$dashScope = $hasFilter
    ? 'Counts for the applied filter. Open the student lists below, or clear the filter to see all NVQ 04 and 05 students.'
    : 'All NVQ 04 and 05 students. Apply a level, department, or course filter only when you need a subset.';
$reportScope = $hasFilter
    ? 'Selected and failed student lists for the filters above. Cutoff is shown with exam marks, applied course, and selected course.'
    : 'Selected and failed student lists for all NVQ 04 and 05 students. Cutoff is shown with exam marks, applied course, and selected course.';
?>

<div class="aa-part-jump">
    <a href="#aa-exam-dashboard"><i class="fas fa-table"></i> Selection summary</a>
    <a href="#aa-selection-report"><i class="fas fa-file-alt"></i> Selection report</a>
</div>

<div class="aa-part aa-part-dashboard" id="aa-exam-dashboard">
    <div class="aa-part-head">
        <div>
            <h2>Selection summary</h2>
            <p><?php echo $e($dashScope); ?></p>
        </div>
        <a href="<?php echo APP_URL; ?>/application-admission/export-report?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-outline-success">
            <i class="fas fa-file-excel me-1"></i> Download Excel
        </a>
    </div>

    <?php
        $fmtCutFull = static function (array $card) use ($fmt): string {
            $parts = is_array($card['cutoff_parts'] ?? null) ? $card['cutoff_parts'] : [];
            if ($parts === []) {
                if (empty($card['has_cutoff'])) {
                    return 'Not set';
                }

                return 'Northern ' . $fmt($card['cutoff_northern'] ?? null)
                    . ' / Other ' . $fmt($card['cutoff_other'] ?? null);
            }
            $bits = [];
            foreach ($parts as $p) {
                $pair = 'Northern ' . $fmt($p['cutoff_northern'] ?? null)
                    . ' / Other ' . $fmt($p['cutoff_other'] ?? null);
                $label = trim((string) ($p['label'] ?? ''));
                $bits[] = ($label !== '' && strcasecmp($label, 'All languages') !== 0)
                    ? ($label . ': ' . $pair)
                    : $pair;
            }

            return implode(' · ', $bits);
        };
    ?>
    <div class="table-responsive aa-sum-wrap">
        <table class="aa-sum-table">
            <thead>
                <tr>
                    <th class="aa-col-no">No</th>
                    <th>NVQ</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Medium</th>
                    <th class="aa-num">Applied students</th>
                    <th class="aa-num">Exam students</th>
                    <th class="aa-num">Met cutoff</th>
                    <th class="aa-num">Below cutoff (not failed)</th>
                    <th class="aa-num">Selected students</th>
                    <th class="aa-num">1st option</th>
                    <th class="aa-num">2nd option</th>
                    <th class="aa-num">3rd option</th>
                    <th class="aa-num">Selected other course</th>
                    <th>Cutoff (Northern / Other)</th>
                    <th class="aa-num">Failed students (below 30)</th>
                    <th class="aa-num">Absent students</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($ovCourses === []): ?>
                <tr><td colspan="17" class="aa-empty">No course counts yet. Enter entrance exam marks and set cutoffs first.</td></tr>
            <?php else: ?>
                <?php $n = 0; foreach ($ovCourses as $c):
                    $n++;
                    $cid = trim((string) ($c['course_id'] ?? ''));
                    $cname = trim((string) ($c['course_name'] ?? ''));
                    $cDid = trim((string) ($c['department_id'] ?? ''));
                    $dname = trim((string) ($c['department_name'] ?? ''));
                    $medium = trim((string) ($c['medium_label'] ?? ''));
                    $rowLevel = trim((string) ($c['application_level'] ?? $level));
                    $rowActive = $cid !== '' && strcasecmp($courseId, $cid) === 0;
                    $qs = [];
                    if ($rowLevel !== '') {
                        $qs['level'] = $rowLevel;
                    }
                    if ($cDid !== '') {
                        $qs['department_id'] = $cDid;
                    }
                    $deptHref = $reportBase . ($qs !== [] ? ('?' . http_build_query($qs)) : '') . '#aa-selection-report';
                    if ($cid !== '') {
                        $qs['course_id'] = $cid;
                    }
                    $courseHref = $reportBase . ($qs !== [] ? ('?' . http_build_query($qs)) : '') . '#aa-selection-report';
                ?>
                <tr class="<?php echo $rowActive ? 'is-active' : ''; ?>">
                    <td class="aa-col-no"><?php echo (int) $n; ?></td>
                    <td><?php echo $e($rowLevel !== '' ? $rowLevel : '—'); ?></td>
                    <td>
                        <a class="aa-dept-link" href="<?php echo $e($deptHref); ?>"
                           data-aa-dept="<?php echo $e($cDid); ?>" data-aa-course=""><?php echo $e($dname !== '' ? $dname : '—'); ?></a>
                    </td>
                    <td>
                        <a class="aa-course-row" href="<?php echo $e($courseHref); ?>"
                           data-aa-dept="<?php echo $e($cDid); ?>" data-aa-course="<?php echo $e($cid); ?>" data-aa-level="<?php echo $e($rowLevel); ?>">
                            <?php echo $e($cname !== '' ? $cname : $cid); ?>
                        </a>
                    </td>
                    <td><?php echo $e($medium !== '' ? $medium : 'All languages'); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['applied'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['sat'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['met_cutoff'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['below_cutoff'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_1st'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_2nd'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_3rd'] ?? 0); ?></td>
                    <td class="aa-num"><?php echo (int) ($c['selected_other'] ?? 0); ?></td>
                    <td class="aa-course-cut"><?php echo $e($fmtCutFull($c)); ?></td>
                    <td class="aa-num is-fail"><?php echo (int) ($c['failed'] ?? 0); ?></td>
                    <td class="aa-num is-fail"><?php echo (int) ($c['absent'] ?? 0); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if ($ovCourses !== []): ?>
            <tfoot>
                <tr>
                    <th colspan="5">Total</th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['applied'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['sat'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['met_cutoff'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['below_cutoff'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_1st'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_2nd'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_3rd'] ?? 0); ?></th>
                    <th class="aa-num"><?php echo (int) ($ovTotals['selected_other'] ?? 0); ?></th>
                    <th></th>
                    <th class="aa-num is-fail"><?php echo (int) ($ovTotals['failed'] ?? 0); ?></th>
                    <th class="aa-num is-fail"><?php echo (int) ($ovTotals['absent'] ?? 0); ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="aa-part aa-part-report" id="aa-selection-report">
    <div class="aa-part-head">
        <div>
            <h2>Selection report</h2>
            <p><?php echo $e($reportScope); ?></p>
        </div>
        <a href="<?php echo APP_URL; ?>/application-admission/pdf-report?<?php echo $e($filterQuery); ?>" class="btn btn-sm btn-outline-dark">
            <i class="fas fa-file-pdf me-1"></i> PDF
        </a>
    </div>

    <div class="aa-stats">
        <div class="aa-stat"><strong><?php echo (int) $total; ?></strong><span>Selected students</span></div>
        <div class="aa-stat"><strong><?php echo count($groups); ?></strong><span>Selected courses</span></div>
        <div class="aa-stat"><strong><?php echo (int) ($choiceCounts['1st'] ?? 0); ?></strong><span>1st choice</span></div>
        <div class="aa-stat"><strong><?php echo (int) ($choiceCounts['2nd'] ?? 0); ?></strong><span>2nd option</span></div>
        <div class="aa-stat"><strong><?php echo (int) ($choiceCounts['3rd'] ?? 0); ?></strong><span>3rd option</span></div>
        <div class="aa-stat aa-stat-fail"><strong><?php echo (int) $totalFail; ?></strong><span>Failed students (below 30)</span></div>
        <div class="aa-stat aa-stat-fail"><strong><?php echo (int) ($failCounts['below_min'] ?? 0); ?></strong><span>Below <?php echo (int) $minMarks; ?></span></div>
        <div class="aa-stat aa-stat-fail"><strong><?php echo (int) ($failCounts['absent'] ?? 0); ?></strong><span>Absent</span></div>
    </div>

    <?php if ($groups === [] && $failGroups === []): ?>
    <div class="aa-card">
        <p class="aa-empty mb-0">No selected or failed students yet. Enter entrance exam marks and set cutoffs first.</p>
    </div>
    <?php else: ?>
    <?php if ($groups === []): ?>
    <div class="aa-card">
        <p class="aa-empty mb-0">No selected students for this filter.</p>
    </div>
    <?php else: ?>
    <h2 class="aa-section-title">Selected students</h2>
    <?php
        $lastDept = null;
        foreach ($groups as $g):
        $list = is_array($g['students'] ?? null) ? $g['students'] : [];
        $deptName = trim((string) ($g['department_name'] ?? ''));
        if ($courseId === '' && $deptName !== '' && $deptName !== $lastDept) {
            $lastDept = $deptName;
            echo '<div class="aa-dept-label">' . $e($deptName) . '</div>';
        }
    ?>
    <div class="aa-card">
        <div class="aa-card-head">
            <div>
                <h2><?php echo $e($g['course_name'] ?? ''); ?></h2>
                <div class="aa-meta">
                    <?php
                        $gLv = trim((string) ($g['application_level'] ?? ''));
                        echo $e($g['department_name'] ?? '');
                        echo $gLv !== '' ? $e(' · NVQ ' . $gLv) : '';
                    ?>
                    <?php if (trim((string) ($g['medium_label'] ?? '')) !== ''): ?>
                        · Medium: <?php echo $e((string) $g['medium_label']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="aa-meta"><?php echo count($list); ?> student(s)</div>
        </div>
        <div class="table-responsive">
            <table class="table aa-table mb-0">
                <thead>
                    <tr>
                        <th class="aa-col-no">No</th>
                        <th>Roll No.</th>
                        <th>Name</th>
                        <th>Course applied</th>
                        <th class="text-center">Exam marks</th>
                        <th class="text-center">Cutoff</th>
                        <th>Selected course</th>
                        <th class="text-center">Choice</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($list === []): ?>
                    <tr><td colspan="8" class="aa-empty">No students.</td></tr>
                <?php else: ?>
                    <?php $n = 0; foreach ($list as $row): $n++;
                        $applied = trim((string) ($row['applied_course'] ?? ''));
                        $selected = trim((string) ($row['selected_course'] ?? ''));
                        $diff = $applied !== '' && $selected !== '' && strcasecmp($applied, $selected) !== 0;
                        $roll = trim((string) ($row['roll_number'] ?? ''));
                    ?>
                    <tr>
                        <td class="aa-col-no"><?php echo $n; ?></td>
                        <td class="aa-col-roll"><?php echo $roll !== '' ? $e($roll) : '—'; ?></td>
                        <td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td><?php echo $e($applied !== '' ? $applied : '—'); ?></td>
                        <td class="aa-col-marks"><?php echo $e($fmt($row['exam_marks'] ?? '')); ?></td>
                        <td class="aa-col-marks"><?php echo $e($fmt($row['cutoff_applied'] ?? '')); ?></td>
                        <td class="<?php echo $diff ? 'aa-diff' : ''; ?>"><?php echo $e($selected !== '' ? $selected : '—'); ?></td>
                        <td class="aa-col-choice"><?php echo $e((string) ($row['choice_label'] ?? '—')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($failGroups !== []): ?>
    <h2 class="aa-section-title">Failed students</h2>
    <p class="aa-sub mb-3">Students who sat the entrance exam and were not selected. Course applied is 1st choice. Selected course is Not selected unless a 2nd/3rd option was considered. Cutoff is the 1st-choice minimum.</p>
    <?php
        $lastFailDept = null;
        foreach ($failGroups as $g):
        $list = is_array($g['students'] ?? null) ? $g['students'] : [];
        $deptName = trim((string) ($g['department_name'] ?? ''));
        if ($courseId === '' && $deptName !== '' && $deptName !== $lastFailDept) {
            $lastFailDept = $deptName;
            echo '<div class="aa-dept-label">' . $e($deptName) . '</div>';
        }
    ?>
    <div class="aa-card">
        <div class="aa-card-head aa-fail-head">
            <div>
                <h2><?php echo $e($g['course_name'] ?? ''); ?></h2>
                <div class="aa-meta">
                    <?php
                        $gLv = trim((string) ($g['application_level'] ?? ''));
                        echo $e($g['department_name'] ?? '');
                        echo $gLv !== '' ? $e(' · NVQ ' . $gLv) : '';
                    ?>
                    <?php if (trim((string) ($g['medium_label'] ?? '')) !== ''): ?>
                        · Medium: <?php echo $e((string) $g['medium_label']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="aa-meta"><?php echo count($list); ?> student(s)</div>
        </div>
        <div class="table-responsive">
            <table class="table aa-table mb-0">
                <thead>
                    <tr>
                        <th class="aa-col-no">No</th>
                        <th>Roll No.</th>
                        <th>Name</th>
                        <th>Course applied</th>
                        <th class="text-center">Exam marks</th>
                        <th class="text-center">Cutoff</th>
                        <th>Selected course</th>
                        <th>2nd choice</th>
                        <th>3rd choice</th>
                        <th class="text-center">Reason</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($list === []): ?>
                    <tr><td colspan="10" class="aa-empty">No students.</td></tr>
                <?php else: ?>
                    <?php $n = 0; foreach ($list as $row): $n++;
                        $applied = trim((string) ($row['applied_course'] ?? ''));
                        $selected = trim((string) ($row['selected_course'] ?? ''));
                        $second = trim((string) ($row['second_course'] ?? ''));
                        $third = trim((string) ($row['third_course'] ?? ''));
                        $roll = trim((string) ($row['roll_number'] ?? ''));
                        $reason = trim((string) ($row['fail_reason'] ?? ''));
                        $notSelected = $selected === '' || strcasecmp($selected, 'Not selected') === 0;
                    ?>
                    <tr>
                        <td class="aa-col-no"><?php echo $n; ?></td>
                        <td class="aa-col-roll"><?php echo $roll !== '' ? $e($roll) : '—'; ?></td>
                        <td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
                        <td><?php echo $e($applied !== '' ? $applied : '—'); ?></td>
                        <td class="aa-col-marks"><?php echo $e($fmt($row['exam_marks'] ?? '')); ?></td>
                        <td class="aa-col-marks"><?php echo $e($fmt($row['cutoff_applied'] ?? '')); ?></td>
                        <td class="<?php echo $notSelected ? 'aa-fail-reason' : 'aa-diff'; ?>"><?php echo $e($notSelected ? 'Not selected' : $selected); ?></td>
                        <td><?php echo $e($second !== '' ? $second : '—'); ?></td>
                        <td><?php echo $e($third !== '' ? $third : '—'); ?></td>
                        <td class="aa-fail-reason"><?php echo $e($reason !== '' ? $reason : 'Fail'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; ?>
</div>
