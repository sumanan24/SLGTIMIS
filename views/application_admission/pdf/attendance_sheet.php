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
$docTitle = $isInterview ? 'Interview Attendance Sheet' : 'Entrance Examination Attendance Sheet';
$filterNote = trim((string) ($province_filter_label ?? ''));
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
<?php if ($filterNote !== ''): ?>
<div class="sub">Province filter: <?php echo $e($filterNote); ?></div>
<?php endif; ?>
</td>
<td class="head-right" style="text-align:right;">
<?php if (!empty($logo_src)): ?><img class="logo-img" src="<?php echo $e($logo_src); ?>" alt="SLGTI"><?php endif; ?>
</td>
</tr>
</table>
<table class="grid">
<thead>
<tr>
<th style="width:5%;text-align:center;">No</th>
<th style="width:14%;text-align:center;">Roll / Index</th>
<th style="width:30%;text-align:center;">Name</th>
<th style="width:15%;text-align:center;">NIC</th>
<th style="width:18%;text-align:center;">Candidate signature</th>
<th style="width:18%;text-align:center;">Invigilator signature</th>
</tr>
</thead>
<tbody>
<?php if (empty($entries)): ?>
<tr><td colspan="6" class="muted">No applicants listed.</td></tr>
<?php else: ?>
<?php $n = 0; foreach ($entries as $row): $n++; ?>
<tr>
<td><?php echo $n; ?></td>
<td><?php echo $e($row['roll_number'] ?? '—'); ?></td>
<td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
<td><?php echo $e($row['student_nic'] ?? ''); ?></td>
<td class="sig">&nbsp;</td>
<td class="sig">&nbsp;</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
<p class="muted">Attendance is not stored in the system. This sheet is for manual use only. Total candidates: <?php echo (int) count($entries ?? []); ?>.</p>
