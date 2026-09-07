<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$isEntranceResults = !empty($isEntranceResults);
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
<th>No</th><th>Roll / Index</th><th>Name</th><th>NIC</th><th>Course</th><th>Marks</th>
</tr>
</thead>
<tbody>
<?php $n = 0; foreach ($entries as $row): $n++;
$marks = trim((string) ($row['exam_marks'] ?? ''));
$roll = trim((string) ($row['roll_number'] ?? ''));
?>
<tr>
<td><?php echo $n; ?></td>
<td><?php echo $e($roll !== '' ? $roll : '—'); ?></td>
<td><?php echo $e($row['student_full_name'] ?? ''); ?></td>
<td><?php echo $e($row['student_nic'] ?? ''); ?></td>
<td><?php echo $e($row['course_priority_1'] ?? ''); ?></td>
<td><?php echo $e($marks !== '' ? $marks : '—'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
