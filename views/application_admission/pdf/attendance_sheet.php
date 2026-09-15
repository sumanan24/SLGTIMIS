<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$fmtDate = static function (?string $d): string {
    if ($d === null || trim($d) === '') {
        return '—';
    }
    $ts = strtotime($d);
    return $ts ? date('d M Y', $ts) : $d;
};
$fmtTime = static function (?string $t): string {
    if ($t === null || trim($t) === '') {
        return '';
    }
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
};
$isInterview = ($schedule['schedule_type'] ?? '') === 'interview';
$docTitle = $isInterview ? 'Interview Attendance Sheet' : 'Entrance Examination Attendance Sheet';
$filterNote = trim((string) ($province_filter_label ?? ''));

$title = trim((string) ($schedule['title'] ?? ''));
$level = trim((string) ($schedule['application_level'] ?? ''));
$course = trim((string) ($schedule['course_name'] ?? ''));
$examLine = $course !== '' ? $course : $title;
if ($level !== '') {
    $examLine .= ($examLine !== '' ? '  ·  ' : '') . 'NVQ Level ' . $level;
}
if ($title !== '' && $course !== '' && strcasecmp($title, $course) !== 0) {
    $examLine .= '  ·  ' . $title;
}

$dateLine = $fmtDate($schedule['schedule_date'] ?? null);
$st = $fmtTime($schedule['start_time'] ?? null);
$et = $fmtTime($schedule['end_time'] ?? null);
$timeLine = $st !== '' ? ($st . ($et !== '' ? ' – ' . $et : '')) : '—';
$venueLine = trim((string) ($schedule['venue'] ?? ''));
if ($venueLine === '') {
    $venueLine = '—';
}
$total = (int) count($entries ?? []);
$colCount = $isInterview ? 6 : 6;
?>
<table class="at-banner">
<tr>
<td>
<?php if (!empty($logo_src)): ?>
<img class="at-logo" src="<?php echo $e($logo_src); ?>" alt="SLGTI">
<?php endif; ?>
<div class="at-inst">Sri Lanka German Training Institute</div>
<div class="at-addr">Ariviyal Nagar, Kilinochchi</div>
<div class="at-title"><?php echo $e($docTitle); ?></div>
<?php if ($examLine !== ''): ?>
<div class="at-course"><?php echo $e($examLine); ?></div>
<?php endif; ?>
</td>
</tr>
</table>
<table class="at-meta">
<tr>
<th>Date</th>
<td><?php echo $e($dateLine); ?></td>
<th>Time</th>
<td><?php echo $e($timeLine); ?></td>
<th>Venue</th>
<td><?php echo $e($venueLine); ?></td>
<th>Candidates</th>
<td><?php echo $total; ?></td>
</tr>
<?php if ($filterNote !== ''): ?>
<tr>
<th>Province</th>
<td colspan="7" style="text-align:left;"><?php echo $e($filterNote); ?></td>
</tr>
<?php endif; ?>
</table>
<table class="grid at-grid">
<thead>
<tr>
<th style="width:6%;">No</th>
<?php if (!$isInterview): ?>
<th style="width:13%;">Index No.</th>
<?php endif; ?>
<th style="width:14%;">NIC</th>
<th><?php echo $isInterview ? 'Full name of candidate' : 'Full name'; ?></th>
<th style="width:16%;">Signature of candidate</th>
<th style="width:<?php echo $isInterview ? '10%' : '16%'; ?>;"><?php echo $isInterview ? 'Time' : 'Invigilator'; ?></th>
<?php if ($isInterview): ?>
<th style="width:12%;">Remarks</th>
<?php endif; ?>
</tr>
</thead>
<tbody>
<?php if (empty($entries)): ?>
<tr><td colspan="<?php echo (int) $colCount; ?>" class="muted" style="text-align:center;">No applicants listed.</td></tr>
<?php else: ?>
<?php $n = 0; foreach ($entries as $row): $n++;
    $alt = ($n % 2) === 0 ? ' at-alt' : '';
    $name = mb_strtoupper(trim((string) ($row['student_full_name'] ?? '')), 'UTF-8');
    $nic = strtoupper(trim((string) ($row['student_nic'] ?? '')));
?>
<tr class="<?php echo trim($alt); ?>">
<td class="at-no"><?php echo $n; ?></td>
<?php if (!$isInterview): ?>
<td class="at-roll"><?php echo $e(trim((string) ($row['roll_number'] ?? '')) !== '' ? (string) $row['roll_number'] : '—'); ?></td>
<?php endif; ?>
<td class="at-nic"><?php echo $e($nic); ?></td>
<td class="at-name"><?php echo $e($name); ?></td>
<td class="at-sig">&nbsp;</td>
<?php if ($isInterview): ?>
<td class="at-time">&nbsp;</td>
<td class="at-note">&nbsp;</td>
<?php else: ?>
<td class="at-sig">&nbsp;</td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
<table class="at-tally">
<tr>
<th style="width:16%;">Present</th>
<td style="width:17%;">&nbsp;</td>
<th style="width:16%;">Absent</th>
<td style="width:17%;">&nbsp;</td>
<th style="width:16%;">Total listed</th>
<td style="width:18%;"><?php echo $total; ?></td>
</tr>
</table>
<table class="at-sign">
<tr>
<td>
<div style="height:34px;">&nbsp;</div>
<div class="at-sign-line"><?php echo $isInterview ? 'Interviewer' : 'Invigilator'; ?></div>
</td>
<td>
<div style="height:34px;">&nbsp;</div>
<div class="at-sign-line"><?php echo $isInterview ? 'Panel member' : 'Supervisor'; ?></div>
</td>
<td>
<div style="height:34px;">&nbsp;</div>
<div class="at-sign-line">Head of Department</div>
</td>
<td>
<div style="height:34px;">&nbsp;</div>
<div class="at-sign-line">Date</div>
</td>
</tr>
</table>
<p class="at-foot">This sheet is for hall / interview attendance only. Candidates must sign against their own name and NIC. Being listed does not guarantee admission.</p>
