<?php
/**
 * @var array $exam
 * @var list<array<string,mixed>> $students
 * @var string $moduleLine
 */
include __DIR__ . '/_styles.php';
?>
<h1>Examination Attendance Sheet</h1>
<div class="sub">
  <strong><?php echo htmlspecialchars($exam['course_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong><br>
  Date: <?php echo htmlspecialchars((string) ($exam['exam_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
  &nbsp;|&nbsp; Time: <?php echo htmlspecialchars((string) ($exam['exam_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
  &nbsp;|&nbsp; Venue: <?php echo htmlspecialchars((string) ($exam['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
  <br>Module: <?php echo htmlspecialchars($moduleLine ?? '', ENT_QUOTES, 'UTF-8'); ?>
</div>
<table>
  <thead>
    <tr>
      <th class="num">#</th>
      <th>Reg. no.</th>
      <th>Name (initials)</th>
      <th class="sig">Signature</th>
    </tr>
  </thead>
  <tbody>
    <?php $n = 0; foreach ($students as $row): $n++; ?>
    <tr>
      <td class="num"><?php echo (int) $n; ?></td>
      <td><?php echo htmlspecialchars((string) ($row['student_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?php echo htmlspecialchars((string) ($row['display_name'] ?? $row['student_ininame'] ?? $row['student_fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
      <td class="sig">&nbsp;</td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<p class="muted">Attendance is not stored in the system. This sheet is for manual use only.</p>
