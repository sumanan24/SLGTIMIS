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
$langMediums = ApplicationAdmissionCutoffModel::MEDIUMS;
$allMedium = ApplicationAdmissionCutoffModel::MEDIUM_ALL;
$autoGroups = ApplicationAdmissionCutoffModel::automobileCutoffGroups();
?>
<table class="head-row">
<tr>
<td style="text-align:left;border:none;">
<div class="inst">Sri Lanka German Training Institute</div>
<div class="title">Entrance exam cutoff list — NVQ Level <?php echo $e($level); ?></div>
<div class="sub">Automobile Level 04: Tamil is separate; Sinhala and English share one cutoff. Other courses use one Northern cutoff and one other-province cutoff.<?php
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
<?php
$shown = 0;
foreach ($groups as $g):
    if (empty($g['has_cutoff'])) {
        continue;
    }
    $shown++;
    $byMedium = is_array($g['by_medium'] ?? null) ? $g['by_medium'] : [];
    $usesLanguage = !empty($g['uses_language']);
    $blocks = $usesLanguage
        ? array_values(array_map(static fn (array $grp): string => (string) ($grp['key'] ?? ''), $autoGroups))
        : [$allMedium];
?>
<div class="title" style="margin:14px 0 4px;"><?php echo $e($g['course_name'] ?? ''); ?></div>
<div class="sub"><?php echo $e($g['department_name'] ?? ''); ?>
 · <?php echo (int) ($g['qualify_count'] ?? 0); ?> qualified
</div>
<?php foreach ($blocks as $medium):
    $block = $byMedium[$medium] ?? null;
    if (!is_array($block) || empty($block['has_cutoff'])) {
        continue;
    }
    $students = is_array($block['students'] ?? null) ? $block['students'] : [];
?>
<div class="sub" style="margin-top:8px;"><strong><?php echo $e($block['label'] ?? ($usesLanguage ? $medium : 'All languages')); ?></strong>
 · Northern min <?php echo $e($fmt($block['cutoff_northern'] ?? null)); ?>
 · Other min <?php echo $e($fmt($block['cutoff_other'] ?? null)); ?>
 · <?php echo (int) ($block['qualify_count'] ?? 0); ?> qualified
</div>
<table class="grid">
<thead>
<tr>
<th>No</th><th>Roll / Index</th><th>Name</th><th>NIC</th><th>2nd choice</th><th>Province</th><th>Region</th><?php if ($usesLanguage): ?><th>Medium</th><?php endif; ?><th>Marks</th><th>Min</th>
</tr>
</thead>
<tbody>
<?php if ($students === []): ?>
<tr><td colspan="<?php echo $usesLanguage ? 10 : 9; ?>" class="muted">No students met this cutoff.</td></tr>
<?php else: ?>
<?php $n = 0; foreach ($students as $row): $n++;
$roll = trim((string) ($row['roll_number'] ?? ''));
$second = trim((string) ($row['course_priority_2'] ?? ''));
?>
<tr>
<td><?php echo $n; ?></td>
<td><?php echo $e($roll !== '' ? $roll : '—'); ?></td>
<td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
<td><?php echo $e($row['student_nic'] ?? ''); ?></td>
<td><?php echo $e($second !== '' ? $second : '—'); ?></td>
<td><?php echo $e($row['student_province'] ?? '—'); ?></td>
<td><?php echo $e($row['region'] ?? ''); ?></td>
<?php if ($usesLanguage): ?><td><?php echo $e($row['medium'] ?? $medium); ?></td><?php endif; ?>
<td><?php echo $e($fmt($row['marks_num'] ?? $row['exam_marks'] ?? '')); ?></td>
<td><?php echo $e($fmt($row['cutoff_applied'] ?? null)); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
<?php endforeach; ?>
<?php endforeach; ?>
<?php if ($shown === 0): ?>
<p class="muted">No course cutoffs have been set for this NVQ level.</p>
<?php endif; ?>
