<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$qs = http_build_query(array_filter(['type' => $type, 'department_id' => $filters['department_id'], 'group_id' => $filters['group_id'], 'date' => $filters['date'], 'month' => $filters['month'], 'from' => $filters['from'], 'to' => $filters['to']]));
$rowLimit = 500;
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-file-alt me-2 text-primary"></i>Attendance Reports</h1>
      <p class="att-sub">Daily and monthly attendance, biometric records, training programme attendance and instructor teaching reports. Export to Excel or PDF.</p>
    </div>
    <?php if (!$needScope): ?>
    <div class="att-actions d-flex gap-2">
      <a class="btn btn-outline-success btn-sm" href="<?php echo att_e($app . '/attendance-management/reports?' . $qs . '&export=xlsx'); ?>"><i class="fas fa-file-excel me-1"></i>Excel</a>
      <a class="btn btn-outline-danger btn-sm" href="<?php echo att_e($app . '/attendance-management/reports?' . $qs . '&export=pdf'); ?>"><i class="fas fa-file-pdf me-1"></i>PDF</a>
    </div>
    <?php endif; ?>
  </div>
  <form class="att-filters" method="get" action="<?php echo att_e($app . '/attendance-management/reports'); ?>">
    <div><label>Report</label><select class="form-select" name="type" onchange="this.form.submit()">
      <?php foreach ($types as $k => $v): ?><option value="<?php echo att_e($k); ?>" <?php echo $type === $k ? 'selected' : ''; ?>><?php echo att_e($v); ?></option><?php endforeach; ?>
    </select></div>
    <?php if (!$ctx['scoped']): ?>
    <div><label>Department</label><select class="form-select" name="department_id"><option value="">All</option>
      <?php foreach ($departments as $d): ?><option value="<?php echo att_e($d['department_id']); ?>" <?php echo (string) $filters['department_id'] === (string) $d['department_id'] ? 'selected' : ''; ?>><?php echo att_e($d['department_name']); ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <?php if (in_array($type, ['daily', 'monthly'], true)): ?>
    <div><label>Group</label><select class="form-select" name="group_id"><option value="">All groups</option>
      <?php foreach ($groups as $g): ?><option value="<?php echo (int) $g['id']; ?>" <?php echo (string) $filters['group_id'] === (string) $g['id'] ? 'selected' : ''; ?>><?php echo att_e($g['name'] . ' (' . $g['academic_year'] . ')'); ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <?php if ($type === 'daily'): ?>
      <div><label>Date</label><input type="date" class="form-control" name="date" value="<?php echo att_e($filters['date']); ?>" max="<?php echo att_e(date('Y-m-d')); ?>"></div>
    <?php elseif ($type === 'monthly'): ?>
      <div><label>Month</label><input type="month" class="form-control" name="month" value="<?php echo att_e($filters['month']); ?>" max="<?php echo att_e(date('Y-m')); ?>"></div>
    <?php else: ?>
      <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['from']); ?>"></div>
      <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['to']); ?>" max="<?php echo att_e(date('Y-m-d')); ?>"></div>
    <?php endif; ?>
    <div><button class="btn btn-primary"><i class="fas fa-play me-1"></i>Generate</button></div>
  </form>

  <?php if ($needScope): ?>
    <div class="att-note">Select a department or a group to generate this report.</div>
  <?php else: ?>
    <div class="att-card">
      <div class="att-card-head"><h2><?php echo att_e($report['title']); ?></h2><span class="att-muted small"><?php echo count($report['rows']); ?> row(s)</span></div>
      <?php foreach ($report['summary'] as $line): ?><div class="px-3 pt-2 att-muted small"><?php echo att_e($line); ?></div><?php endforeach; ?>
      <div class="att-table-wrap mt-2">
        <?php if ($report['rows']): ?>
        <table class="att-table"><thead><tr><?php foreach ($report['headers'] as $h): ?><th><?php echo att_e($h); ?></th><?php endforeach; ?></tr></thead><tbody>
          <?php foreach (array_slice($report['rows'], 0, $rowLimit) as $r): ?><tr><?php foreach ($r as $v): ?><td><?php echo att_e($v); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table>
        <?php if (count($report['rows']) > $rowLimit): ?><div class="att-note m-2">Showing the first <?php echo $rowLimit; ?> rows. Export to Excel or PDF for the full report.</div><?php endif; ?>
        <?php else: ?><div class="att-empty">No records for this report.</div><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
