<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$qs = http_build_query(array_filter(['from' => $filters['from'], 'to' => $filters['to'], 'search' => $filters['search'], 'department_id' => $filters['department_id']]));
$counted = 0; $ignored = 0;
foreach ($rows as $r) { if ($r['counted']) { $counted++; } else { $ignored++; } }
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-fingerprint me-2 text-primary"></i>Biometric Records</h1>
      <p class="att-sub">Original fingerprint punches, shown exactly as received (read only). The classification shows how the attendance rules treat each punch.</p>
    </div>
    <div class="att-actions"><a class="btn btn-outline-success btn-sm" href="<?php echo att_e($app . '/attendance-management/biometric?' . $qs . '&export=xlsx'); ?>"><i class="fas fa-file-excel me-1"></i>Excel</a></div>
  </div>
  <div class="att-note">
    Morning window <strong><?php echo att_e($rules['morning_start'] . '–' . $rules['morning_end']); ?></strong> (earliest valid punch = IN) ·
    Evening OUT after <strong><?php echo att_e($rules['evening_start']); ?></strong> (latest punch = OUT) ·
    Valid punch range <?php echo att_e($rules['valid_from'] . '–' . $rules['valid_to']); ?> · Duplicate scans within <?php echo (int) $rules['duplicate_seconds']; ?>s are ignored.
    The reader does not report IN/OUT direction, so direction is decided by these time windows.
  </div>
  <form class="att-filters" method="get" action="<?php echo att_e($app . '/attendance-management/biometric'); ?>">
    <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['from']); ?>"></div>
    <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['to']); ?>" max="<?php echo att_e(date('Y-m-d')); ?>"></div>
    <?php if (!$ctx['scoped']): ?>
    <div><label>Department</label><select class="form-select" name="department_id"><option value="">All</option>
      <?php foreach ($departments as $d): ?><option value="<?php echo att_e($d['department_id']); ?>" <?php echo (string) $filters['department_id'] === (string) $d['department_id'] ? 'selected' : ''; ?>><?php echo att_e($d['department_name']); ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <div><label>Search</label><input class="form-control" name="search" value="<?php echo att_e($filters['search']); ?>" placeholder="Reg no or name"></div>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>
  <div class="att-stats">
    <div class="att-stat"><div class="n"><?php echo count($rows); ?></div><div class="l">Punches shown</div></div>
    <div class="att-stat ok"><div class="n"><?php echo $counted; ?></div><div class="l">Counted</div></div>
    <div class="att-stat warn"><div class="n"><?php echo $ignored; ?></div><div class="l">Ignored (duplicate / outside range)</div></div>
  </div>
  <div class="att-card"><div class="att-table-wrap">
    <?php if ($rows): ?>
    <table class="att-table">
      <thead><tr><th>Date</th><th>Time</th><th>Reg No</th><th>Name</th><th>Device</th><th>Event</th><th>Rule classification</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr style="<?php echo $r['counted'] ? '' : 'opacity:.6'; ?>">
          <td class="text-nowrap"><?php echo att_e($r['attendance_date']); ?></td><td class="att-mono"><?php echo att_e(substr($r['attendance_time'], 0, 8)); ?></td>
          <td class="att-mono"><?php echo att_e($r['student_id']); ?></td><td><?php echo att_e($r['name']); ?></td>
          <td class="att-mono"><?php echo att_e($r['machine_id']); ?></td><td class="att-mono small"><?php echo att_e($r['event_id']); ?></td>
          <td><?php echo att_e($r['classification']); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (count($rows) >= 800): ?><div class="att-note m-2">Showing the first 800 punches. Narrow the dates or use a search, or export from Reports.</div><?php endif; ?>
    <?php else: ?><div class="att-empty">No fingerprint records for these filters.</div><?php endif; ?>
  </div></div>

  <?php if (!$ctx['scoped']): ?>
  <div class="att-card">
    <div class="att-card-head"><h2>Unmatched punches (no student linked)</h2><span class="att-muted small">Latest 50 — kept for investigation, never counted</span></div>
    <div class="att-table-wrap">
      <?php if ($unmatched): ?>
      <table class="att-table"><thead><tr><th>Date</th><th>Time</th><th>Person ID</th><th>Device</th><th>Note</th></tr></thead><tbody>
        <?php foreach ($unmatched as $u): ?><tr><td><?php echo att_e($u['attendance_date']); ?></td><td class="att-mono"><?php echo att_e(substr($u['attendance_time'], 0, 8)); ?></td><td class="att-mono"><?php echo att_e($u['person_id']); ?></td><td><?php echo att_e($u['machine_name'] ?: $u['machine_id']); ?></td><td class="att-muted small"><?php echo att_e($u['note']); ?></td></tr><?php endforeach; ?>
      </tbody></table>
      <?php else: ?><div class="att-empty">No unmatched punches.</div><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
