<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$isEntranceResults = !empty($isEntranceResults);
$labels = [
    'scheduled' => $isEntranceResults ? 'Pending' : 'Scheduled',
    'selected' => 'Selected',
    'not_selected' => 'Not selected',
    'waitlist' => 'Waitlist',
];
?>
<table class="head-row">
<tr>
<td style="text-align:left;border:none;">
<div class="inst">Sri Lanka German Training Institute</div>
<div class="title"><?php echo $isEntranceResults ? 'Entrance examination results' : 'Interview selection list'; ?></div>
<div class="sub"><?php echo $e($schedule['title'] ?? ''); ?> · NVQ <?php echo $e($schedule['application_level'] ?? ''); ?></div>
</td>
<td style="text-align:right;border:none;">
<?php if (!empty($logo_src)): ?><img class="logo-img" src="<?php echo $e($logo_src); ?>" alt="SLGTI"><?php endif; ?>
</td>
</tr>
</table>
<table class="grid">
<thead>
<tr>
<th>No</th><th>Name</th><th>NIC</th><th>Course</th><th><?php echo $isEntranceResults ? 'Exam result' : 'Selection result'; ?></th>
</tr>
</thead>
<tbody>
<?php $n = 0; foreach ($entries as $row): $n++;
$st = $row['selection_status'] ?? 'scheduled';
?>
<tr>
<td><?php echo $n; ?></td>
<td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
<td><?php echo $e($row['student_nic'] ?? ''); ?></td>
<td><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
<td><?php echo $e($labels[$st] ?? $st); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
