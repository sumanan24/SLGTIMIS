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
$docTitle = $isInterview ? 'INTERVIEW ATTENDANCE SHEET' : 'ENTRANCE EXAMINATION ATTENDANCE SHEET';
$filterNote = trim((string) ($province_filter_label ?? ''));

$title = trim((string) ($schedule['title'] ?? ''));
$level = trim((string) ($schedule['application_level'] ?? ''));
$course = trim((string) ($schedule['course_name'] ?? ''));
$examLine = $title;
if ($level !== '' && stripos($title, 'NVQ') === false && stripos($title, 'Level ' . $level) === false) {
    $examLine .= ($examLine !== '' ? ' — ' : '') . 'NVQ Level ' . $level;
}
if ($course !== '' && ($title === '' || stripos($title, $course) === false)) {
    $examLine .= ($examLine !== '' ? ' — ' : '') . $course;
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
?>
<table class="head-row" style="margin-bottom:10px;">
<tr>
<td style="border:none;text-align:center;vertical-align:top;">
<?php if (!empty($logo_src)): ?>
<img class="logo-img" src="<?php echo $e($logo_src); ?>" alt="SLGTI" style="display:block;margin:0 auto 6px auto;">
<?php endif; ?>
<div class="inst" style="text-align:center;text-transform:uppercase;letter-spacing:0.04em;">Sri Lanka German Training Institute</div>
<div class="title" style="text-align:center;margin-top:6px;text-transform:uppercase;letter-spacing:0.03em;"><?php echo $e($docTitle); ?></div>
<?php if ($examLine !== ''): ?>
<div class="sub" style="text-align:center;margin-top:8px;color:#0f172a;font-size:10.5px;font-weight:700;"><?php echo $e($examLine); ?></div>
<?php endif; ?>
<div class="sub" style="text-align:center;margin-top:6px;line-height:1.55;">
<strong>Date:</strong> <?php echo $e($dateLine); ?>
&nbsp;&nbsp;|&nbsp;&nbsp;
<strong>Time:</strong> <?php echo $e($timeLine); ?>
<br>
<strong>Venue:</strong> <?php echo $e($venueLine); ?>
<?php if ($filterNote !== ''): ?>
<br><strong>Province:</strong> <?php echo $e($filterNote); ?>
<?php endif; ?>
</div>
</td>
</tr>
</table>
<table class="grid">
<thead>
<tr>
<th style="width:5%;text-align:center;">No</th>
<?php if (!$isInterview): ?>
<th style="width:14%;text-align:center;">Roll / Index</th>
<?php endif; ?>
<th style="width:<?php echo $isInterview ? '40%' : '30%'; ?>;text-align:center;">Name</th>
<th style="width:15%;text-align:center;">NIC</th>
<th style="width:<?php echo $isInterview ? '20%' : '18%'; ?>;text-align:center;">Candidate signature</th>
<th style="width:<?php echo $isInterview ? '20%' : '18%'; ?>;text-align:center;"><?php echo $isInterview ? 'Panel signature' : 'Invigilator signature'; ?></th>
</tr>
</thead>
<tbody>
<?php if (empty($entries)): ?>
<tr><td colspan="<?php echo $isInterview ? 5 : 6; ?>" class="muted">No applicants listed.</td></tr>
<?php else: ?>
<?php $n = 0; foreach ($entries as $row): $n++; ?>
<tr>
<td style="text-align:center;"><?php echo $n; ?></td>
<?php if (!$isInterview): ?>
<td style="text-align:center;"><?php echo $e($row['roll_number'] ?? '—'); ?></td>
<?php endif; ?>
<td><?php echo $e(mb_strtoupper(trim((string) ($row['student_full_name'] ?? '')), 'UTF-8')); ?></td>
<td style="text-align:center;"><?php echo $e($row['student_nic'] ?? ''); ?></td>
<td class="sig">&nbsp;</td>
<td class="sig">&nbsp;</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
<p class="muted" style="margin:10px 0 0 0;">Total candidates: <?php echo $total; ?>. This sheet is for manual attendance use only.</p>
<table style="width:100%;border-collapse:collapse;margin-top:28px;">
<tr>
<td style="width:34%;border:none;vertical-align:bottom;padding:0 12px 0 0;">
<div style="height:36px;">&nbsp;</div>
<div style="border-top:1px solid #111;padding-top:6px;font-size:9px;text-align:center;">Supervisor&apos;s name</div>
</td>
<td style="width:33%;border:none;vertical-align:bottom;padding:0 12px;">
<div style="height:36px;">&nbsp;</div>
<div style="border-top:1px solid #111;padding-top:6px;font-size:9px;text-align:center;">Supervisor&apos;s signature</div>
</td>
<td style="width:33%;border:none;vertical-align:bottom;padding:0 0 0 12px;">
<div style="height:36px;">&nbsp;</div>
<div style="border-top:1px solid #111;padding-top:6px;font-size:9px;text-align:center;">Date</div>
</td>
</tr>
</table>
