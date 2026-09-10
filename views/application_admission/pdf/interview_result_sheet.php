<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$level = trim((string) ($level ?? ''));
$rows = is_array($rows ?? null) ? $rows : [];
$levelLine = $level !== '' ? ('NVQ Level ' . $level) : 'NVQ Level 04 and 05';
$printed = date('d M Y');
?>
<table class="rs-banner">
<tr>
<td>
<?php if (!empty($logo_src)): ?>
<img class="rs-logo" src="<?php echo $e($logo_src); ?>" alt="SLGTI">
<?php endif; ?>
<div class="rs-inst">Sri Lanka German Training Institute</div>
<div class="rs-title">Interview common result sheet</div>
<div class="rs-meta">
    <?php echo $e($levelLine); ?>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <?php echo (int) count($rows); ?> student(s)
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Printed: <?php echo $e($printed); ?>
</div>
</td>
</tr>
</table>
<table class="grid rs-grid">
<thead>
<tr>
<th style="width:6%;">No</th>
<th style="width:14%;">Roll No.</th>
<th style="width:28%;">Name</th>
<th style="width:26%;">Course applied</th>
<th style="width:26%;">Selected course</th>
</tr>
</thead>
<tbody>
<?php if ($rows === []): ?>
<tr>
<td colspan="5" class="muted" style="text-align:center;padding:12px 8px;">
    No students are listed on interview schedules<?php echo $level !== '' ? ' for this NVQ level' : ''; ?>.
</td>
</tr>
<?php else: ?>
<?php $n = 0; foreach ($rows as $row): $n++;
    $roll = trim((string) ($row['roll_number'] ?? ''));
    $name = trim((string) ($row['student_full_name'] ?? ''));
    $applied = trim((string) ($row['applied_course'] ?? ''));
    $selected = trim((string) ($row['selected_course'] ?? ''));
    $diff = $applied !== '' && $selected !== '' && strcasecmp($applied, $selected) !== 0;
    $alt = ($n % 2) === 0 ? ' rs-alt' : '';
    $diffClass = $diff ? ' rs-diff' : '';
?>
<tr class="<?php echo trim($alt . $diffClass); ?>">
<td class="rs-no"><?php echo $n; ?></td>
<td class="rs-roll"><?php echo $e($roll !== '' ? $roll : '—'); ?></td>
<td class="rs-name"><?php echo $e($name !== '' ? mb_strtoupper($name, 'UTF-8') : ''); ?></td>
<td class="rs-course"><?php echo $e($applied !== '' ? $applied : '—'); ?></td>
<td class="rs-selected"><?php echo $e($selected !== '' ? $selected : '—'); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
<p class="rs-foot">Course applied is the student&apos;s 1st-choice course. Selected course is the interview course. A green selected-course cell means the student was selected for a course other than 1st choice. Being listed does not guarantee admission.</p>
