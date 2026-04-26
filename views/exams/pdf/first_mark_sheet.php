<?php
/**
 * @var array $exam
 * @var list<array<string,mixed>> $rows
 * @var string $moduleLine
 */
include __DIR__ . '/_styles.php';
$m = static function (array $row, string $col, ?string $leg = null): string {
    $v = $row[$col] ?? null;
    if (($v === null || $v === '') && $leg !== null) {
        $v = $row[$leg] ?? null;
    }
    return ($v !== null && $v !== '') ? htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') : '';
};
?>
<h1>First Marking Sheet</h1>
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
      <th>Name</th>
      <?php for ($q = 1; $q <= 7; $q++): ?>
        <th class="q">Q<?php echo $q; ?></th>
      <?php endfor; ?>
      <th class="fin">Final</th>
    </tr>
  </thead>
  <tbody>
    <?php $n = 0; foreach ($rows as $row): $n++; ?>
    <tr>
      <td class="num"><?php echo (int) $n; ?></td>
      <td><?php echo htmlspecialchars((string) ($row['student_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
      <td><?php echo htmlspecialchars((string) ($row['student_fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
      <?php for ($q = 1; $q <= 7; $q++): ?>
        <?php $cell = $m($row, 'marks_q' . $q); ?>
        <td class="q"><?php echo $cell === '' ? '&nbsp;' : $cell; ?></td>
      <?php endfor; ?>
      <?php $cellF = $m($row, 'marks_final', 'marks'); ?>
      <td class="fin"><?php echo $cellF === '' ? '&nbsp;' : $cellF; ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<p class="muted">First marking: Q1–Q7 and final as entered in the system.</p>
