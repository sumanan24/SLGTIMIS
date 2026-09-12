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
$level = (string) ($level ?? '04');
$departmentId = trim((string) ($department_id ?? $departmentId ?? ''));
$courseId = trim((string) ($course_id ?? $courseId ?? ''));
$groups = is_array($groups ?? null) ? $groups : [];
$failGroups = is_array($fail_groups ?? $failGroups ?? null) ? ($fail_groups ?? $failGroups) : [];
$filterQuery = (string) ($filter_query ?? $filterQuery ?? ('level=' . rawurlencode($level)));
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
$dashScope = $courseId !== ''
    ? 'Counts and cutoff for the selected course. Open the selection report below for student lists.'
    : ($departmentId !== ''
        ? 'Counts and cutoff for this department. Use the course filter to narrow further.'
        : 'Counts and cutoff for all departments. Use the filter or open a department/course card to narrow.');
$reportScope = $hasFilter
    ? 'Selected and failed student lists for the filters above. Cutoff is shown with exam marks, applied course, and selected course.'
    : 'Selected and failed student lists for all departments. Cutoff is shown with exam marks, applied course, and selected course.';
?>

<div class="aa-part-jump">
    <a href="#aa-exam-dashboard"><i class="fas fa-chart-bar"></i> Exam result dashboard</a>
    <a href="#aa-selection-report"><i class="fas fa-file-alt"></i> Selection report</a>
</div>

<div class="aa-part aa-part-dashboard" id="aa-exam-dashboard">
    <div class="aa-part-head">
        <div>
            <h2>Exam result dashboard</h2>
            <p><?php echo $e($dashScope); ?></p>
        </div>
    </div>

    <div class="aa-ov-kpis">
    <a class="aa-ov-kpi" href="<?php echo APP_URL; ?>/application-admission?level=<?php echo $e($level); ?>">
        <strong><?php echo (int) ($ovTotals['sat'] ?? 0); ?></strong>
        <span>Sat the examination</span>
        <em>Entrance exams</em>
    </a>
    <a class="aa-ov-kpi" href="<?php echo APP_URL; ?>/application-admission/cutoffs?level=<?php echo $e($level); ?>">
        <strong><?php echo (int) ($ovTotals['cutoff_courses'] ?? 0); ?></strong>
        <span>Courses with cutoff</span>
        <em>Cutoff marks</em>
    </a>
    <a class="aa-ov-kpi" href="<?php echo APP_URL; ?>/application-admission/second-option?level=<?php echo $e($level); ?>">
        <strong><?php echo (int) ($ovTotals['second_option'] ?? 0); ?></strong>
        <span>2nd option</span>
        <em>2nd option list</em>
    </a>
    <a class="aa-ov-kpi" href="<?php echo APP_URL; ?>/application-admission/interviews?level=<?php echo $e($level); ?>">
        <strong><?php echo (int) ($ovTotals['interview'] ?? 0); ?></strong>
        <span>Interview listed</span>
        <em>Interviews</em>
    </a>
    <a class="aa-ov-kpi" href="<?php echo $e($reportBase); ?>?<?php echo $e($filterQuery); ?>#aa-selection-report">
        <strong><?php echo (int) ($ovTotals['selected'] ?? 0); ?></strong>
        <span>Selected</span>
        <em>Selection report</em>
    </a>
    <a class="aa-ov-kpi is-fail" href="<?php echo $e($reportBase); ?>?<?php echo $e($filterQuery); ?>#aa-selection-report">
        <strong><?php echo (int) ($ovTotals['failed'] ?? 0); ?></strong>
        <span>Failed</span>
        <em>Selection report</em>
    </a>
    </div>

    <?php
        $deptKey = static function (array $row): string {
            $did = strtolower(trim((string) ($row['department_id'] ?? '')));
            if ($did !== '') {
                return 'id:' . $did;
            }
            $dname = trim((string) ($row['department_name'] ?? ''));
            $dname = function_exists('mb_strtolower') ? mb_strtolower($dname, 'UTF-8') : strtolower($dname);

            return 'n:' . ($dname !== '' ? $dname : 'other');
        };
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
        $deptCards = [];
        foreach ($ovDepartments as $d) {
            $key = $deptKey($d);
            $deptCards[$key] = $d;
            $deptCards[$key]['course_list'] = [];
        }
        foreach ($ovCourses as $cRow) {
            $key = $deptKey($cRow);
            if (!isset($deptCards[$key])) {
                $deptCards[$key] = [
                    'department_id' => trim((string) ($cRow['department_id'] ?? '')),
                    'department_name' => trim((string) ($cRow['department_name'] ?? '')) !== ''
                        ? trim((string) $cRow['department_name'])
                        : 'Department',
                    'sat' => 0,
                    'selected' => 0,
                    'failed' => 0,
                    'second_option' => 0,
                    'interview' => 0,
                    'courses' => 0,
                    'cutoff_courses' => 0,
                    'course_list' => [],
                ];
            }
            $deptCards[$key]['course_list'][] = $cRow;
        }
    ?>
    <?php if ($deptCards !== []): ?>
    <h2 class="aa-section-title">Departments</h2>
    <p class="aa-sub mb-3">One card per department. Each card lists the full course names with students who sat the examination, cutoff, selected, failed, second option, and interview counts.</p>
    <div class="aa-dept-stack">
        <?php foreach ($deptCards as $d):
            $did = trim((string) ($d['department_id'] ?? ''));
            $dname = trim((string) ($d['department_name'] ?? ''));
            $deptCourses = is_array($d['course_list'] ?? null) ? $d['course_list'] : [];
            $deptActive = $did !== '' && strcasecmp($departmentId, $did) === 0;
            $deptHref = $reportBase . '?level=' . rawurlencode($level)
                . ($did !== '' ? '&department_id=' . rawurlencode($did) : '')
                . '#aa-selection-report';
            $courseCount = count($deptCourses);
            $cutoffCount = (int) ($d['cutoff_courses'] ?? 0);
        ?>
        <article class="aa-dept-card<?php echo $deptActive ? ' is-active' : ''; ?>">
            <div class="aa-dept-card-head">
                <a class="aa-dept-link" href="<?php echo $e($deptHref); ?>"
                   data-aa-dept="<?php echo $e($did); ?>" data-aa-course="">
                    <h3>
                        <?php echo $e($dname !== '' ? $dname : 'Department'); ?>
                        <?php if ($did !== '' && strcasecmp($did, $dname) !== 0): ?>
                            <span class="aa-dept-code"><?php echo $e($did); ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="aa-dept-sub">
                        <?php echo (int) $courseCount; ?> course<?php echo $courseCount === 1 ? '' : 's'; ?>
                        · cutoff set on <?php echo (int) $cutoffCount; ?>
                    </p>
                </a>
                <div class="aa-dept-totals">
                    <span><strong><?php echo (int) ($d['sat'] ?? 0); ?></strong><em>Sat the examination</em></span>
                    <span><strong><?php echo (int) ($d['selected'] ?? 0); ?></strong><em>Selected</em></span>
                    <span class="is-fail"><strong><?php echo (int) ($d['failed'] ?? 0); ?></strong><em>Failed</em></span>
                    <span><strong><?php echo (int) ($d['second_option'] ?? 0); ?></strong><em>Second option</em></span>
                    <span><strong><?php echo (int) ($d['interview'] ?? 0); ?></strong><em>Interview</em></span>
                </div>
            </div>
            <div class="aa-course-wrap">
                <table class="aa-course-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Medium</th>
                            <th>Cutoff (Northern / Other)</th>
                            <th class="aa-num">Sat the examination</th>
                            <th class="aa-num">Selected</th>
                            <th class="aa-num">Failed</th>
                            <th class="aa-num">Second option</th>
                            <th class="aa-num">Interview</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($deptCourses === []): ?>
                        <tr><td colspan="8" class="aa-empty">No courses for this department.</td></tr>
                    <?php else: ?>
                        <?php foreach ($deptCourses as $c):
                            $cid = trim((string) ($c['course_id'] ?? ''));
                            $cname = trim((string) ($c['course_name'] ?? ''));
                            $cDid = trim((string) ($c['department_id'] ?? $did));
                            $rowActive = $cid !== '' && strcasecmp($courseId, $cid) === 0;
                            $courseHref = $reportBase . '?level=' . rawurlencode($level);
                            if ($cDid !== '') {
                                $courseHref .= '&department_id=' . rawurlencode($cDid);
                            }
                            if ($cid !== '') {
                                $courseHref .= '&course_id=' . rawurlencode($cid);
                            }
                            $courseHref .= '#aa-selection-report';
                        ?>
                        <tr class="<?php echo $rowActive ? 'is-active' : ''; ?>">
                            <td>
                                <a class="aa-course-row" href="<?php echo $e($courseHref); ?>"
                                   data-aa-dept="<?php echo $e($cDid); ?>" data-aa-course="<?php echo $e($cid); ?>">
                                    <?php echo $e($cname !== '' ? $cname : $cid); ?>
                                </a>
                            </td>
                            <td><?php echo $e(trim((string) ($c['medium_label'] ?? '')) !== '' ? (string) $c['medium_label'] : 'All languages'); ?></td>
                            <td class="aa-course-cut"><?php echo $e($fmtCutFull($c)); ?></td>
                            <td class="aa-num"><?php echo (int) ($c['sat'] ?? 0); ?></td>
                            <td class="aa-num"><?php echo (int) ($c['selected'] ?? 0); ?></td>
                            <td class="aa-num is-fail"><?php echo (int) ($c['failed'] ?? 0); ?></td>
                            <td class="aa-num"><?php echo (int) ($c['second_option'] ?? 0); ?></td>
                            <td class="aa-num"><?php echo (int) ($c['interview'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
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
        <div class="aa-stat aa-stat-fail"><strong><?php echo (int) $totalFail; ?></strong><span>Failed students</span></div>
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
                    <?php echo $e($g['department_name'] ?? ''); ?>
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
                    <?php echo $e($g['department_name'] ?? ''); ?>
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
