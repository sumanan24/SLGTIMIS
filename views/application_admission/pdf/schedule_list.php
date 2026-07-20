<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$fmtDate = static function (?string $d): string {
    if ($d === null || trim($d) === '') return '—';
    $ts = strtotime($d);
    return $ts ? date('d M Y', $ts) : $d;
};
$fmtTime = static function (?string $t): string {
    if ($t === null || trim($t) === '') return '';
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
};
$isInterview = ($schedule['schedule_type'] ?? '') === 'interview';
$docTitle = $isInterview ? 'Interview Schedule' : 'Entrance Examination Schedule';
?>
<table class="head-row">
<tr>
<td class="head-left" style="text-align:left;">
<div class="inst">Sri Lanka German Training Institute</div>
<div class="title"><?php echo $e($docTitle); ?></div>
<div class="sub"><?php echo $e($schedule['title'] ?? ''); ?> — NVQ Level <?php echo $e($schedule['application_level'] ?? ''); ?><?php if (!empty($schedule['course_name'])): ?> — <?php echo $e($schedule['course_name']); ?><?php endif; ?></div>
<div class="sub"><?php echo $e($fmtDate($schedule['schedule_date'] ?? null)); ?>
<?php
$st = $fmtTime($schedule['start_time'] ?? null);
$et = $fmtTime($schedule['end_time'] ?? null);
if ($st !== '') echo ' · ' . $e($st) . ($et !== '' ? ' – ' . $e($et) : '');
?> · <?php echo $e($schedule['venue'] ?? ''); ?></div>
</td>
<td class="head-right" style="text-align:right;">
<?php if (!empty($logo_src)): ?><img class="logo-img" src="<?php echo $e($logo_src); ?>" alt="SLGTI"><?php endif; ?>
</td>
</tr>
</table>
<?php if (!empty($schedule['instructions'])): ?>
<p class="muted"><?php echo nl2br($e($schedule['instructions'])); ?></p>
<?php endif; ?>
<table class="grid">
<thead>
<tr>
<th style="width:5%;">No</th>
<th style="width:12%;">Roll / Index</th>
<th style="width:28%;">Name</th>
<th style="width:14%;">NIC</th>
<th style="width:22%;">Course (1st preference)</th>
<th style="width:10%;"><?php echo $isInterview ? 'Panel' : 'Hall'; ?></th>
<?php if ($isInterview): ?><th style="width:9%;">Status</th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php if (empty($entries)): ?>
<tr><td colspan="<?php echo $isInterview ? 7 : 6; ?>" class="muted">No applicants listed.</td></tr>
<?php else: ?>
<?php $n = 0; foreach ($entries as $row): $n++; ?>
<tr>
<td><?php echo $n; ?></td>
<td><?php echo $e($row['roll_number'] ?? '—'); ?></td>
<td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
<td><?php echo $e($row['student_nic'] ?? ''); ?></td>
<td><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
<td><?php echo $e($row['room_or_panel'] ?? '—'); ?></td>
<?php if ($isInterview): ?><td><?php echo $e($row['selection_status'] ?? ''); ?></td><?php endif; ?>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
