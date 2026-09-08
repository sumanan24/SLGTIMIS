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
$minMarks = (int) ($min_marks ?? ApplicationAdmissionCutoffModel::MARKS_MIN_SECOND_OPTION);
$groups = is_array($groups ?? null) ? $groups : [];
?>
<table class="head-row">
<tr>
<td style="text-align:left;border:none;">
<div class="inst">Sri Lanka German Training Institute</div>
<div class="title">2nd option list — NVQ Level <?php echo $e($level); ?></div>
<div class="sub">Course-wise students with marks from <?php echo (int) $minMarks; ?> up to (below) the 1st-choice cutoff. Restricted 2nd-option courses (<?php echo $e(ApplicationAdmissionCutoffModel::restrictedSecondOptionLabel($level)); ?>) are skipped — use 3rd instead unless 3rd is the same. Green cell = course to consider.<?php
if (!empty($filter_summary)) {
    echo '<br>' . $e((string) $filter_summary);
}
?></div>
</td>
<td style="text-align:right;border:none;">
<?php if (!empty($logo_src)): ?><img class="logo-img" src="<?php echo $e($logo_src); ?>" alt="SLGTI"><?php endif; ?>
</td>
</tr>
</table>

<?php if ($groups === []): ?>
<p class="muted">No students in this marks band.</p>
<?php endif; ?>
<?php foreach ($groups as $g):
    $list = is_array($g['students'] ?? null) ? $g['students'] : [];
    if ($list === []) {
        continue;
    }
?>
<div class="title" style="margin:14px 0 4px;"><?php echo $e($g['course_name'] ?? ''); ?></div>
<div class="sub"><?php echo $e($g['department_name'] ?? ''); ?> · <?php echo count($list); ?> student(s) · not selected for 1st choice</div>
<table class="grid">
<thead>
<tr>
<th>No</th><th>Roll / Index</th><th>Name</th><th>NIC</th><th>Marks</th><th>Cutoff</th><th>2nd option</th><th>3rd option</th><th>Province</th><th>Region</th>
</tr>
</thead>
<tbody>
<?php $n = 0; foreach ($list as $row): $n++;
$roll = trim((string) ($row['roll_number'] ?? ''));
$secondName = trim((string) ($row['second_course_name'] ?? ''));
$thirdName = trim((string) ($row['third_course_name'] ?? ''));
$consider = (int) ($row['consider_choice'] ?? 0);
$pick = 'background:#d1e7dd;font-weight:bold;';
?>
<tr>
<td><?php echo $n; ?></td>
<td><?php echo $e($roll !== '' ? $roll : '—'); ?></td>
<td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
<td><?php echo $e($row['student_nic'] ?? ''); ?></td>
<td><?php echo $e($fmt($row['marks_num'] ?? '')); ?></td>
<td><?php echo $e($fmt($row['first_cutoff'] ?? null)); ?></td>
<td<?php echo $consider === 2 ? ' style="' . $pick . '"' : ''; ?>><?php echo $e($secondName !== '' ? $secondName : '—'); ?></td>
<td<?php echo $consider === 3 ? ' style="' . $pick . '"' : ''; ?>><?php echo $e($thirdName !== '' ? $thirdName : '—'); ?></td>
<td><?php echo $e($row['student_province'] ?? '—'); ?></td>
<td><?php echo $e($row['region'] ?? ''); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>
