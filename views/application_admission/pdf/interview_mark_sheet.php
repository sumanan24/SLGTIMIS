<?php
require_once BASE_PATH . '/models/ApplicationAdmissionCutoffModel.php';
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
$fmtMarks = static function ($n): string {
    return ApplicationAdmissionCutoffModel::formatMarksValue($n);
};
$filterNote = trim((string) ($province_filter_label ?? ''));
$title = trim((string) ($schedule['title'] ?? ''));
$level = trim((string) ($schedule['application_level'] ?? ''));
$course = trim((string) ($schedule['course_name'] ?? ''));
$examLine = $course !== '' ? $course : $title;
if ($level !== '') {
    $examLine .= ($examLine !== '' ? '  ·  ' : '') . 'NVQ Level ' . $level;
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
<table class="ms-banner">
<tr>
<td>
<?php if (!empty($logo_src)): ?>
<img class="ms-logo" src="<?php echo $e($logo_src); ?>" alt="SLGTI">
<?php endif; ?>
<div class="ms-inst">Sri Lanka German Training Institute</div>
<div class="ms-addr">Ariviyal Nagar, Kilinochchi</div>
<div class="ms-title">Interview Result Sheet</div>
<?php if ($examLine !== ''): ?>
<div class="ms-course"><?php echo $e($examLine); ?></div>
<?php endif; ?>
</td>
</tr>
</table>
<table class="ms-meta">
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
<p class="ms-scale">
    <strong>Entrance Marks 50</strong>
    &nbsp;+&nbsp; Education Qualification 15
    &nbsp;+&nbsp; Other Qualification 5
    &nbsp;+&nbsp; Question–Aptitude 20
    &nbsp;+&nbsp; Performance at Interview 10
    &nbsp;=&nbsp; <strong>Total 100</strong>
</p>
<table class="grid ms-grid">
<thead>
<tr>
<th style="width:4%;">No</th>
<th style="width:12%;">NIC</th>
<th style="width:21%;">Name of candidate</th>
<th style="width:8%;">Entrance Marks<span class="ms-max">50</span></th>
<th style="width:9%;">Education Qualification<span class="ms-max">15</span></th>
<th style="width:8%;">Other Qualification<span class="ms-max">5</span></th>
<th style="width:9%;">Question–Aptitude<span class="ms-max">20</span></th>
<th style="width:10%;">Performance at Interview<span class="ms-max">10</span></th>
<th style="width:7%;">Total<span class="ms-max">100</span></th>
<th style="width:12%;">Remarks</th>
</tr>
</thead>
<tbody>
<?php if (empty($entries)): ?>
<tr><td colspan="10" class="muted" style="text-align:center;">No applicants listed.</td></tr>
<?php else: ?>
<?php $n = 0; foreach ($entries as $row): $n++;
    $alt = ($n % 2) === 0 ? ' ms-alt' : '';
    $name = mb_strtoupper(trim((string) ($row['student_full_name'] ?? '')), 'UTF-8');
    $nic = strtoupper(trim((string) ($row['student_nic'] ?? '')));
    $absent = !empty($row['entrance_absent']);
    $out50 = $row['entrance_out_of_50'] ?? null;
    if ($absent) {
        $entranceCell = 'Ab';
    } elseif ($out50 !== null && $out50 !== '') {
        $entranceCell = $fmtMarks($out50);
    } else {
        $entranceCell = '—';
    }
?>
<tr class="<?php echo trim($alt); ?>">
<td class="ms-no"><?php echo $n; ?></td>
<td class="ms-nic"><?php echo $e($nic); ?></td>
<td class="ms-name"><?php echo $e($name); ?></td>
<td class="ms-mark"><?php echo $e($entranceCell); ?></td>
<td class="ms-blank">&nbsp;</td>
<td class="ms-blank">&nbsp;</td>
<td class="ms-blank">&nbsp;</td>
<td class="ms-blank">&nbsp;</td>
<td class="ms-blank ms-total">&nbsp;</td>
<td class="ms-note">&nbsp;</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
<table class="ms-sign">
<tr>
<td style="width:50%;">&nbsp;</td>
<td>
<div style="height:34px;">&nbsp;</div>
<div class="ms-sign-line">Head of Department</div>
</td>
<td>
<div style="height:34px;">&nbsp;</div>
<div class="ms-sign-line">Date</div>
</td>
</tr>
</table>
