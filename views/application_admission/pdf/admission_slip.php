<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$fmtDate = static function (?string $d): string {
    $ts = $d ? strtotime($d) : false;
    return $ts ? date('d M Y', $ts) : '—';
};
$fmtTime = static function (?string $t): string {
    if (!$t || trim($t) === '') return '—';
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
};
?>
<table class="head-row">
<tr>
<td style="text-align:left;border:none;">
<div class="inst">Sri Lanka German Training Institute</div>
<div class="title">Entrance examination — admission slip</div>
<div class="sub"><?php echo $e($schedule['title'] ?? ''); ?></div>
</td>
<td style="text-align:right;border:none;">
<?php if (!empty($logo_src)): ?><img class="logo-img" src="<?php echo $e($logo_src); ?>" alt="SLGTI"><?php endif; ?>
</td>
</tr>
</table>
<table class="grid">
<tr><th style="width:35%;">Candidate name</th><td><?php echo $e($entry['student_full_name'] ?? ''); ?></td></tr>
<tr><th>NIC</th><td><?php echo $e($entry['student_nic'] ?? ''); ?></td></tr>
<tr><th>Roll / Index number</th><td><?php echo $e($entry['roll_number'] ?? '—'); ?></td></tr>
<tr><th>NVQ Level</th><td><?php echo $e($schedule['application_level'] ?? ''); ?></td></tr>
<tr><th>Course (1st preference)</th><td><?php echo $e($entry['course_priority_1'] ?? ''); ?></td></tr>
<tr><th>Examination date</th><td><?php echo $e($fmtDate($schedule['schedule_date'] ?? null)); ?></td></tr>
<tr><th>Time</th><td><?php echo $e($fmtTime($schedule['start_time'] ?? null)); ?><?php if (!empty($schedule['end_time'])): ?> – <?php echo $e($fmtTime($schedule['end_time'])); ?><?php endif; ?></td></tr>
<tr><th>Venue / Hall</th><td><?php echo $e($schedule['venue'] ?? ''); ?><?php if (!empty($entry['room_or_panel'])): ?> — <?php echo $e($entry['room_or_panel']); ?><?php endif; ?></td></tr>
</table>
<?php if (!empty($schedule['instructions'])): ?>
<p class="muted" style="margin-top:12px;"><?php echo nl2br($e($schedule['instructions'])); ?></p>
<?php endif; ?>
<p style="margin-top:16px;font-size:9px;font-style:italic;">This candidate is scheduled to sit the entrance examination. Bring this slip and a valid ID to the examination centre.</p>
