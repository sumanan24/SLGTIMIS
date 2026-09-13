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
$groups = is_array($groups ?? null) ? $groups : [];
$failGroups = is_array($fail_groups ?? null) ? $fail_groups : [];
$total = (int) ($total_students ?? 0);
$totalFail = (int) ($total_fail ?? 0);
$minMarks = (int) ($min_marks ?? 30);
$overview = is_array($overview ?? null) ? $overview : [];
$ovTotals = is_array($overview['totals'] ?? null) ? $overview['totals'] : [];
$ovDepartments = is_array($overview['departments'] ?? null) ? $overview['departments'] : [];
$ovCourses = is_array($overview['courses'] ?? null) ? $overview['courses'] : [];
$printed = date('d M Y');
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
?>
<table class="rs-banner">
<tr>
<td>
<?php if (!empty($logo_src)): ?>
<img class="rs-logo" src="<?php echo $e($logo_src); ?>" alt="SLGTI">
<?php endif; ?>
<div class="rs-inst">Sri Lanka German Training Institute</div>
<div class="rs-title">Admission selection report</div>
<div class="rs-meta">
    NVQ Level <?php echo $e($level); ?>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <?php echo (int) $total; ?> selected
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <?php echo (int) $totalFail; ?> failed
    <?php if (!empty($filter_summary)): ?>
    &nbsp;&nbsp;|&nbsp;&nbsp; <?php echo $e((string) $filter_summary); ?>
    <?php endif; ?>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Printed: <?php echo $e($printed); ?>
</div>
</td>
</tr>
</table>

<?php if ($ovDepartments !== [] || $ovCourses !== []): ?>
<div class="rs-section">
    <div class="rs-lang">Overview — sat, cutoff, selected, failed, 2nd option, interview</div>
    <table class="grid rs-grid rs-ov">
    <thead>
    <tr>
    <th>Sat the examination</th><th>Cutoff courses</th><th>2nd option</th><th>Interview</th><th>Selected</th><th>Failed (below 30)</th>
    </tr>
    </thead>
    <tbody>
    <tr>
    <td class="rs-marks"><?php echo (int) ($ovTotals['sat'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($ovTotals['cutoff_courses'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($ovTotals['second_option'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($ovTotals['interview'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($ovTotals['selected'] ?? 0); ?></td>
    <td class="rs-reason"><?php echo (int) ($ovTotals['failed'] ?? 0); ?></td>
    </tr>
    </tbody>
    </table>
</div>
<?php if ($ovDepartments !== []): ?>
<div class="rs-section">
    <div class="rs-lang">Department wise</div>
    <table class="grid rs-grid rs-ov">
    <thead>
    <tr>
    <th>Department</th><th>Courses</th><th>Sat the examination</th><th>Cutoff set</th><th>Selected</th><th>Failed (below 30)</th><th>2nd option</th><th>Interview</th>
    </tr>
    </thead>
    <tbody>
    <?php $n = 0; foreach ($ovDepartments as $d): $n++; $alt = ($n % 2) === 0 ? ' rs-alt' : ''; ?>
    <tr class="<?php echo trim($alt); ?>">
    <td class="rs-name"><?php echo $e((string) ($d['department_name'] ?? '')); ?></td>
    <td class="rs-marks"><?php echo (int) ($d['courses'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($d['sat'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($d['cutoff_courses'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($d['selected'] ?? 0); ?></td>
    <td class="rs-reason"><?php echo (int) ($d['failed'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($d['second_option'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($d['interview'] ?? 0); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
<?php endif; ?>
<?php if ($ovCourses !== []): ?>
<div class="rs-section">
    <div class="rs-lang">Course wise</div>
    <table class="grid rs-grid rs-ov">
    <thead>
    <tr>
    <th>Department</th><th>Course</th><th>Cutoff</th><th>Sat the examination</th><th>Selected</th><th>Failed (below 30)</th><th>2nd option</th><th>Interview</th>
    </tr>
    </thead>
    <tbody>
    <?php $n = 0; foreach ($ovCourses as $c): $n++; $alt = ($n % 2) === 0 ? ' rs-alt' : ''; ?>
    <tr class="<?php echo trim($alt); ?>">
    <td class="rs-course"><?php echo $e((string) ($c['department_name'] ?? '')); ?></td>
    <td class="rs-name"><?php echo $e((string) ($c['course_name'] ?? '')); ?></td>
    <td class="rs-cut"><?php echo $e($fmtCut($c)); ?></td>
    <td class="rs-marks"><?php echo (int) ($c['sat'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($c['selected'] ?? 0); ?></td>
    <td class="rs-reason"><?php echo (int) ($c['failed'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($c['second_option'] ?? 0); ?></td>
    <td class="rs-marks"><?php echo (int) ($c['interview'] ?? 0); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>

<?php if ($groups === [] && $failGroups === []): ?>
<table class="grid rs-grid">
<thead>
<tr>
<th>No</th><th>Roll No.</th><th>Name</th><th>Course applied</th><th>Exam marks</th><th>Cutoff</th><th>Selected course</th><th>Choice</th>
</tr>
</thead>
<tbody>
<tr><td colspan="8" class="muted" style="text-align:center;padding:12px 8px;">No selected or failed students for this filter.</td></tr>
</tbody>
</table>
<?php else: ?>
<?php if ($groups !== []): ?>
<?php $g = 0; foreach ($groups as $group): $g++;
    $list = is_array($group['students'] ?? null) ? $group['students'] : [];
    $head = trim((string) ($group['course_name'] ?? 'Course'));
    $dept = trim((string) ($group['department_name'] ?? ''));
    $medium = trim((string) ($group['medium_label'] ?? ''));
    if ($dept !== '') {
        $head .= '  —  ' . $dept;
    }
    if ($medium !== '') {
        $head .= '  —  Medium: ' . $medium;
    }
?>
<div class="rs-section<?php echo $g > 1 ? ' rs-section-break' : ''; ?>">
    <div class="rs-lang">
        <?php echo $e($head); ?>
        <span class="rs-lang-count"> — <?php echo count($list); ?> selected student(s)</span>
    </div>
    <table class="grid rs-grid">
    <thead>
    <tr>
    <th style="width:4%;">No</th>
    <th style="width:11%;">Roll No.</th>
    <th style="width:18%;">Name</th>
    <th style="width:16%;">Course applied</th>
    <th style="width:8%;">Exam marks</th>
    <th style="width:8%;">Cutoff</th>
    <th style="width:16%;">Selected course</th>
    <th style="width:9%;">Choice</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($list === []): ?>
    <tr><td colspan="8" class="muted" style="text-align:center;">No students.</td></tr>
    <?php else: ?>
    <?php $n = 0; foreach ($list as $row): $n++;
        $applied = trim((string) ($row['applied_course'] ?? ''));
        $selected = trim((string) ($row['selected_course'] ?? ''));
        $diff = $applied !== '' && $selected !== '' && strcasecmp($applied, $selected) !== 0;
        $alt = ($n % 2) === 0 ? ' rs-alt' : '';
        $diffClass = $diff ? ' rs-diff' : '';
        $roll = trim((string) ($row['roll_number'] ?? ''));
        $name = trim((string) ($row['student_full_name'] ?? ''));
    ?>
    <tr class="<?php echo trim($alt . $diffClass); ?>">
    <td class="rs-no"><?php echo $n; ?></td>
    <td class="rs-roll"><?php echo $e($roll !== '' ? $roll : '—'); ?></td>
    <td class="rs-name"><?php echo $e($name !== '' ? mb_strtoupper($name, 'UTF-8') : ''); ?></td>
    <td class="rs-course"><?php echo $e($applied !== '' ? $applied : '—'); ?></td>
    <td class="rs-marks"><?php echo $e($fmt($row['exam_marks'] ?? '')); ?></td>
    <td class="rs-cut"><?php echo $e($fmt($row['cutoff_applied'] ?? '')); ?></td>
    <td class="rs-selected"><?php echo $e($selected !== '' ? $selected : '—'); ?></td>
    <td class="rs-choice"><?php echo $e((string) ($row['choice_label'] ?? '—')); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($failGroups !== []): ?>
<?php $fg = 0; foreach ($failGroups as $group): $fg++;
    $list = is_array($group['students'] ?? null) ? $group['students'] : [];
    $head = trim((string) ($group['course_name'] ?? 'Course'));
    $dept = trim((string) ($group['department_name'] ?? ''));
    $medium = trim((string) ($group['medium_label'] ?? ''));
    if ($dept !== '') {
        $head .= '  —  ' . $dept;
    }
    if ($medium !== '') {
        $head .= '  —  Medium: ' . $medium;
    }
    $break = ($groups !== [] || $fg > 1) ? ' rs-section-break' : '';
?>
<div class="rs-section<?php echo $break; ?>">
    <div class="rs-lang rs-lang-fail">
        Failed students — <?php echo $e($head); ?>
        <span class="rs-lang-count"> — <?php echo count($list); ?> student(s)</span>
    </div>
    <table class="grid rs-grid">
    <thead>
    <tr>
    <th style="width:4%;">No</th>
    <th style="width:10%;">Roll No.</th>
    <th style="width:16%;">Name</th>
    <th style="width:13%;">Course applied</th>
    <th style="width:8%;">Exam marks</th>
    <th style="width:7%;">Cutoff</th>
    <th style="width:13%;">Selected course</th>
    <th style="width:12%;">2nd choice</th>
    <th style="width:12%;">3rd choice</th>
    <th style="width:5%;">Reason</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($list === []): ?>
    <tr><td colspan="10" class="muted" style="text-align:center;">No students.</td></tr>
    <?php else: ?>
    <?php $n = 0; foreach ($list as $row): $n++;
        $applied = trim((string) ($row['applied_course'] ?? ''));
        $selected = trim((string) ($row['selected_course'] ?? ''));
        $second = trim((string) ($row['second_course'] ?? ''));
        $third = trim((string) ($row['third_course'] ?? ''));
        $alt = ($n % 2) === 0 ? ' rs-alt' : '';
        $roll = trim((string) ($row['roll_number'] ?? ''));
        $name = trim((string) ($row['student_full_name'] ?? ''));
        $reason = trim((string) ($row['fail_reason'] ?? ''));
        $notSelected = $selected === '' || strcasecmp($selected, 'Not selected') === 0;
    ?>
    <tr class="<?php echo trim($alt); ?>">
    <td class="rs-no"><?php echo $n; ?></td>
    <td class="rs-roll"><?php echo $e($roll !== '' ? $roll : '—'); ?></td>
    <td class="rs-name"><?php echo $e($name !== '' ? mb_strtoupper($name, 'UTF-8') : ''); ?></td>
    <td class="rs-course"><?php echo $e($applied !== '' ? $applied : '—'); ?></td>
    <td class="rs-marks"><?php echo $e($fmt($row['exam_marks'] ?? '')); ?></td>
    <td class="rs-cut"><?php echo $e($fmt($row['cutoff_applied'] ?? '')); ?></td>
    <td class="rs-selected<?php echo $notSelected ? ' rs-reason' : ''; ?>"><?php echo $e($notSelected ? 'Not selected' : $selected); ?></td>
    <td class="rs-course"><?php echo $e($second !== '' ? $second : '—'); ?></td>
    <td class="rs-course"><?php echo $e($third !== '' ? $third : '—'); ?></td>
    <td class="rs-reason"><?php echo $e($reason !== '' ? $reason : 'Fail'); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
<p class="rs-foot">Course applied is the student&apos;s 1st-choice course. Exam marks are from the entrance examination. Selected-list cutoff is the minimum for the selected course. Failed-list cutoff is the 1st-choice minimum. Failed selected course is Not selected unless a 2nd/3rd option was considered. Marks below <?php echo (int) $minMarks; ?> or absent (ab) are a fail. Green selected-course cells are 2nd/3rd option. Being listed as selected does not guarantee admission.</p>
