<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$back = 'attendance-management/daily?' . http_build_query(array_filter(['date' => $date, 'department_id' => $filters['department_id'] ?? '', 'group_id' => $filters['group_id'], 'indicator' => $filters['indicator'], 'search' => $filters['search']]));
$qs = http_build_query(array_filter(['date' => $date, 'department_id' => $filters['department_id'] ?? '', 'group_id' => $filters['group_id'], 'indicator' => $filters['indicator'], 'search' => $filters['search']]));
$canAct = $ctx['canCorrect'] || $ctx['canRequest'];
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-calendar-day me-2 text-primary"></i>Daily Attendance</h1>
      <p class="att-sub">Calculated from the fingerprint records. Morning IN <?php echo att_e($rules['morning_start'] . '–' . $rules['morning_end']); ?> and evening OUT after <?php echo att_e($rules['evening_start']); ?> = full day; one of them = half day.</p>
    </div>
    <div class="att-actions d-flex gap-2">
      <?php if (!$needScope): ?><a class="btn btn-outline-success btn-sm" href="<?php echo att_e($app . '/attendance-management/daily?' . $qs . '&export=xlsx'); ?>"><i class="fas fa-file-excel me-1"></i>Excel</a><?php endif; ?>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo att_e($app . '/attendance-management/reports?type=daily&date=' . urlencode($date) . '&department_id=' . urlencode((string) ($filters['department_id'] ?? '')) . '&group_id=' . urlencode((string) $filters['group_id'])); ?>"><i class="fas fa-file-pdf me-1"></i>Reports</a>
    </div>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>

  <form class="att-filters" method="get" action="<?php echo att_e($app . '/attendance-management/daily'); ?>">
    <div><label>Date</label><input type="date" class="form-control" name="date" value="<?php echo att_e($date); ?>" max="<?php echo att_e(date('Y-m-d')); ?>"></div>
    <?php if (!$ctx['scoped']): ?>
    <div><label>Department</label><select class="form-select" name="department_id"><option value="">Select</option>
      <?php foreach ($departments as $d): ?><option value="<?php echo att_e($d['department_id']); ?>" <?php echo (string) $filters['department_id'] === (string) $d['department_id'] ? 'selected' : ''; ?>><?php echo att_e($d['department_name']); ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <div><label>Group</label><select class="form-select" name="group_id"><option value="">All groups</option>
      <?php foreach ($groups as $g): ?><option value="<?php echo (int) $g['id']; ?>" <?php echo (string) $filters['group_id'] === (string) $g['id'] ? 'selected' : ''; ?>><?php echo att_e($g['name'] . ' (' . $g['academic_year'] . ')'); ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Indicator</label><select class="form-select" name="indicator"><option value="">All</option>
      <?php foreach (['Present', 'Half Day', 'Absent', 'Pending Verification', 'Manually Updated'] as $i): ?><option <?php echo $filters['indicator'] === $i ? 'selected' : ''; ?>><?php echo att_e($i); ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Search</label><input class="form-control" name="search" value="<?php echo att_e($filters['search']); ?>" placeholder="Reg no or name"></div>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>

  <?php if ($needScope): ?>
    <div class="att-note">Select a department or a group to view the attendance.</div>
  <?php else: ?>
    <?php if (!$editState['editable']): ?>
      <div class="att-note"><i class="fas fa-lock me-1"></i><?php echo att_e($editState['reason']); ?> Records are view-only<?php echo $ctx['canCorrect'] ? '; an exceptional correction can be requested for higher approval' : ''; ?>.</div>
    <?php else: ?>
      <div class="att-note"><i class="fas fa-unlock me-1"></i>Records for this date can be corrected until the end of the month (locked from <?php echo att_e(date('d M Y', strtotime($editState['locks_on']))); ?>).</div>
    <?php endif; ?>

    <div class="att-stats">
      <div class="att-stat"><div class="n"><?php echo (int) $summary['total']; ?></div><div class="l">Students</div></div>
      <div class="att-stat ok"><div class="n"><?php echo (int) $summary['full']; ?></div><div class="l">Full day</div></div>
      <div class="att-stat warn"><div class="n"><?php echo (int) $summary['half']; ?></div><div class="l">Half day</div></div>
      <div class="att-stat bad"><div class="n"><?php echo (int) $summary['absent']; ?></div><div class="l">Absent</div></div>
      <div class="att-stat pending"><div class="n"><?php echo (int) $summary['pending']; ?></div><div class="l">Pending verification</div></div>
      <div class="att-stat"><div class="n"><?php echo (int) $summary['manual']; ?></div><div class="l">Manually updated</div></div>
    </div>

    <div class="att-card"><div class="att-table-wrap">
      <?php if ($rows): ?>
      <table class="att-table">
        <thead><tr><th>Reg No</th><th>Name</th><th>Group</th><th>IN</th><th>OUT</th><th>Biometric</th><th>Attendance</th><th>Source</th><th>Indicator</th><th>Reason</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $mode = $r['editable'] ? ($ctx['canCorrect'] ? 'direct' : ($ctx['canRequest'] ? 'request' : '')) : ($ctx['canCorrect'] ? 'exceptional' : '');
        ?>
          <tr>
            <td class="att-mono"><?php echo att_e($r['student_id']); ?></td>
            <td><?php echo att_e($r['student_name']); ?></td>
            <td><?php echo att_e($r['group_name'] ?: '—'); ?></td>
            <td class="att-mono"><?php echo att_e(att_time($r['first_in'])); ?></td>
            <td class="att-mono"><?php echo att_e(att_time($r['last_out'])); ?></td>
            <td><?php echo att_e(att_status_label($r['calc_status'])); ?></td>
            <td><strong><?php echo att_e(att_status_label($r['final_status'])); ?></strong></td>
            <td><?php echo att_e(ucfirst($r['source'])); ?></td>
            <td><?php echo att_badge($r['indicator']); ?></td>
            <td class="small att-muted" style="max-width:260px" title="<?php echo att_e(implode(', ', array_map('att_time', $r['punch_times']))); ?>"><?php echo att_e($r['calc_reason']); ?></td>
            <td class="text-nowrap att-actions">
              <?php if ($mode !== ''): ?>
                <button type="button" class="btn btn-sm btn-outline-primary att-correct" data-mode="<?php echo att_e($mode); ?>" data-student="<?php echo att_e($r['student_id']); ?>"
                  data-name="<?php echo att_e($r['student_name']); ?>" data-date="<?php echo att_e($date); ?>" data-current="<?php echo att_e($r['final_status']); ?>">
                  <?php echo $mode === 'direct' ? 'Correct' : ($mode === 'request' ? 'Request' : 'Exceptional'); ?></button>
              <?php elseif (!$r['editable']): ?><span class="att-lock" title="<?php echo att_e($editState['reason']); ?>"><i class="fas fa-lock"></i></span>
              <?php endif; ?>
              <?php if ($r['verification_status'] === 'pending' && $ctx['canCorrect'] && $r['editable']): ?>
                <form method="post" action="<?php echo att_e($app . '/attendance-management/verify'); ?>" class="d-inline" onsubmit="return confirm('Confirm this record as verified?');">
                  <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="back" value="<?php echo att_e($back); ?>">
                  <input type="hidden" name="student_id" value="<?php echo att_e($r['student_id']); ?>"><input type="hidden" name="date" value="<?php echo att_e($date); ?>">
                  <button class="btn btn-sm btn-outline-secondary">Verify</button>
                </form>
              <?php endif; ?>
              <?php if (!empty($r['manually_updated'])): ?>
                <a class="btn btn-sm btn-link" href="<?php echo att_e($app . '/attendance-management/corrections?tab=audit&student_id=' . urlencode($r['student_id']) . '&date=' . urlencode($date)); ?>">History</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><div class="att-empty">No students match these filters.</div><?php endif; ?>
    </div></div>
    <?php if ($canAct) { include BASE_PATH . '/views/attendance_management/_correction_modal.php'; } ?>
  <?php endif; ?>
</div>
