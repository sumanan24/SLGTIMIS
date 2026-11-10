<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$q = http_build_query(array_filter(['from' => $filters['from'], 'to' => $filters['to'], 'staff_id' => $filters['staff_id'], 'department_id' => $filters['department_id']]));
$totS = 0.0; $totA = 0.0; $totSessions = 0; $totDone = 0;
foreach ($rows as $r) { $totS += (float) $r['scheduled_hours']; $totA += (float) $r['actual_hours']; $totSessions += (int) $r['sessions']; $totDone += (int) $r['completed']; }
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-chart-line me-2 text-primary"></i>Teaching Progress</h1>
      <p class="att-sub">Scheduled hours from the published timetable against the hours actually taught.</p>
    </div>
    <div class="att-actions d-flex gap-2">
      <a class="btn btn-outline-success btn-sm" href="<?php echo att_e($app . '/teaching-diary/progress?' . $q . '&export=xlsx'); ?>"><i class="fas fa-file-excel me-1"></i>Excel</a>
      <a class="btn btn-outline-danger btn-sm" href="<?php echo att_e($app . '/teaching-diary/progress?' . $q . '&export=pdf'); ?>"><i class="fas fa-file-pdf me-1"></i>PDF</a>
    </div>
  </div>
  <form class="att-filters" method="get" action="<?php echo att_e($app . '/teaching-diary/progress'); ?>">
    <?php if ($viewAll): ?>
    <div><label>Instructor</label><select class="form-select" name="staff_id"><option value="">All</option>
      <?php foreach ($staffList as $s): ?><option value="<?php echo att_e($s['staff_id']); ?>" <?php echo $filters['staff_id'] === $s['staff_id'] ? 'selected' : ''; ?>><?php echo att_e($s['staff_name']); ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['from']); ?>"></div>
    <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['to']); ?>"></div>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>
  <div class="att-stats">
    <div class="att-stat"><div class="n"><?php echo $totSessions; ?></div><div class="l">Sessions</div></div>
    <div class="att-stat ok"><div class="n"><?php echo $totDone; ?></div><div class="l">Recorded</div></div>
    <div class="att-stat"><div class="n"><?php echo number_format($totS, 1); ?></div><div class="l">Scheduled hours</div></div>
    <div class="att-stat ok"><div class="n"><?php echo number_format($totA, 1); ?></div><div class="l">Actual hours</div></div>
  </div>
  <div class="att-card"><div class="att-table-wrap">
    <?php if ($rows): ?>
    <table class="att-table">
      <thead><tr><th>Instructor</th><th>Module</th><th>Group</th><th>Sessions</th><th>Recorded</th><th>Not conducted</th><th>Outstanding</th><th>Scheduled h</th><th>Actual h</th><th>Progress</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $pct = (float) $r['scheduled_hours'] > 0 ? min(100, round((float) $r['actual_hours'] * 100 / (float) $r['scheduled_hours'])) : 0; ?>
        <tr>
          <td><?php echo att_e($r['staff_name'] ?: $r['staff_id']); ?></td><td><?php echo att_e($r['module_name']); ?></td><td><?php echo att_e($r['group_name']); ?></td>
          <td><?php echo (int) $r['sessions']; ?></td><td><?php echo (int) $r['completed']; ?></td><td><?php echo (int) $r['not_conducted']; ?></td><td><?php echo (int) $r['outstanding']; ?></td>
          <td><?php echo number_format((float) $r['scheduled_hours'], 2); ?></td><td><?php echo number_format((float) $r['actual_hours'], 2); ?></td>
          <td style="min-width:140px"><div class="progress" style="height:8px"><div class="progress-bar" style="width:<?php echo (int) $pct; ?>%"></div></div><small class="att-muted"><?php echo (int) $pct; ?>%</small></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="att-empty">No timetable-based teaching records in this period yet.</div><?php endif; ?>
  </div></div>
</div>
