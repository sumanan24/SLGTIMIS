<?php
$app = rtrim((string) APP_URL, '/');
$e = static function ($v) { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); };
$label = ['full' => ['Full Day', 'success'], 'half' => ['Half Day', 'warning'], 'absent' => ['Absent', 'danger']];
?>
<div class="container-fluid px-3 px-md-4 py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h5 fw-bold mb-0" style="color:#0b2545"><i class="fas fa-fingerprint me-2 text-primary"></i>Biometric Attendance</h1>
      <div class="small text-muted">Read only. Full day = 1, half day = 0.5, absent = 0. Contact your Head of Department for corrections.</div>
    </div>
    <div class="btn-group">
      <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($app . '/student/my-attendance?month=' . $prevMonth); ?>"><i class="fas fa-chevron-left"></i></a>
      <span class="btn btn-sm btn-light disabled"><?php echo $e(date('F Y', strtotime($month . '-01'))); ?></span>
      <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($app . '/student/my-attendance?month=' . $nextMonth); ?>"><i class="fas fa-chevron-right"></i></a>
    </div>
  </div>
  <?php if ($summary): ?>
  <div class="row g-2 mb-3">
    <?php foreach ([['Working days', $summary['working_days'], 'primary'], ['Full days', $summary['full_days'], 'success'], ['Half days', $summary['half_days'], 'warning'], ['Absent', $summary['absent_days'], 'danger'], ['Attendance', $summary['percentage'] . '%', 'primary']] as $c): ?>
      <div class="col-6 col-md"><div class="card text-center"><div class="card-body py-2"><div class="fs-4 fw-bold text-<?php echo $c[2]; ?>"><?php echo $e($c[1]); ?></div><div class="small text-muted"><?php echo $e($c[0]); ?></div></div></div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card"><div class="card-header fw-semibold">Daily attendance</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
          <thead><tr><th>Date</th><th>Result</th></tr></thead><tbody>
          <?php foreach (array_reverse($dayMap, true) as $d => $st): $l = $label[$st] ?? ['—', 'secondary']; ?>
            <tr><td><?php echo $e(date('D, d M', strtotime($d))); ?></td><td><span class="badge bg-<?php echo $l[1]; ?>"><?php echo $e($l[0]); ?></span></td></tr>
          <?php endforeach; ?>
          <?php if (!$dayMap): ?><tr><td colspan="2" class="text-muted text-center py-3">No teaching days recorded yet this month.</td></tr><?php endif; ?>
          </tbody></table></div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card"><div class="card-header fw-semibold">Class attendance</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
          <thead><tr><th>Date</th><th>Time</th><th>Module</th><th>Type</th><th>Result</th></tr></thead><tbody>
          <?php foreach ($sessions as $x): $s = $x['session']; $l = $label[$x['status']] ?? ['—', 'secondary']; ?>
            <tr><td class="text-nowrap"><?php echo $e(date('d M', strtotime($x['date']))); ?></td>
              <td class="text-nowrap"><?php echo $e(substr($s['start_clock'], 0, 5) . '–' . substr($s['end_clock'], 0, 5)); ?></td>
              <td><?php echo $e($s['module_name'] ?: $s['subject']); ?></td><td><?php echo $e($s['session_type'] ?? ''); ?></td>
              <td><span class="badge bg-<?php echo $l[1]; ?>"><?php echo $e($l[0]); ?></span></td></tr>
          <?php endforeach; ?>
          <?php if (!$sessions): ?><tr><td colspan="5" class="text-muted text-center py-3">No class sessions to show.</td></tr><?php endif; ?>
          </tbody></table></div>
      </div>
    </div>
  </div>
</div>
