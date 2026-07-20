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
$labels = ['scheduled' => 'Scheduled', 'selected' => 'Selected', 'not_selected' => 'Not selected', 'waitlist' => 'Waitlist'];
$st = $entry['selection_status'] ?? 'scheduled';
?>
<table class="head-row">
<tr>
<td style="text-align:left;border:none;">
<div class="inst">Sri Lanka German Training Institute</div>
<div class="title">Interview — personal schedule</div>
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
<tr><th>Course (1st preference)</th><td><?php echo $e($entry['course_priority_1'] ?? ''); ?></td></tr>
<tr><th>Interview date</th><td><?php echo $e($fmtDate($schedule['schedule_date'] ?? null)); ?></td></tr>
<tr><th>Time</th><td><?php echo $e($fmtTime($schedule['start_time'] ?? null)); ?></td></tr>
<tr><th>Venue</th><td><?php echo $e($schedule['venue'] ?? ''); ?></td></tr>
<tr><th>Panel / room</th><td><?php echo $e($entry['room_or_panel'] ?? '—'); ?></td></tr>
<tr><th>Selection status</th><td><?php echo $e($labels[$st] ?? $st); ?></td></tr>
</table>
