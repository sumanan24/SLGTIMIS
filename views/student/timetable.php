<?php
$app = rtrim((string) APP_URL, '/');
$e = static function ($v) { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); };
?>
<div class="container-fluid px-3 px-md-4 py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h5 fw-bold mb-0" style="color:#0b2545"><i class="fas fa-calendar-week me-2 text-primary"></i>My Timetable</h1>
      <div class="small text-muted"><?php echo $group ? $e($group['name']) : 'No active group'; ?> · Published sessions only</div>
    </div>
    <div class="btn-group">
      <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($app . '/student/my-timetable?week=' . $prevWeek); ?>"><i class="fas fa-chevron-left"></i></a>
      <span class="btn btn-sm btn-light disabled"><?php echo $e(date('d M', strtotime($weekStart)) . ' – ' . date('d M Y', strtotime($weekStart . ' +5 day'))); ?></span>
      <a class="btn btn-sm btn-outline-primary" href="<?php echo $e($app . '/student/my-timetable?week=' . $nextWeek); ?>"><i class="fas fa-chevron-right"></i></a>
    </div>
  </div>
  <?php if (!$group): ?>
    <div class="alert alert-info">You are not assigned to a group yet. Your timetable will appear here once you are.</div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($days as $date => $list): $isToday = $date === date('Y-m-d'); ?>
    <div class="col-md-6 col-xl-4">
      <div class="card h-100" style="border-color:<?php echo $isToday ? '#1d4ed8' : '#dbe4f0'; ?>">
        <div class="card-header fw-semibold" style="background:<?php echo $isToday ? '#1d4ed8' : '#f5f8fd'; ?>;color:<?php echo $isToday ? '#fff' : '#0b2545'; ?>">
          <?php echo $e(date('l, d M', strtotime($date))); ?>
        </div>
        <div class="list-group list-group-flush">
          <?php if (!$list): ?><div class="list-group-item text-muted small">No sessions</div><?php endif; ?>
          <?php foreach ($list as $s): ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between"><strong><?php echo $e($s['module_name'] ?: $s['subject']); ?></strong>
                <span class="small text-nowrap"><?php echo $e(substr($s['start_clock'], 0, 5) . '–' . substr($s['end_clock'], 0, 5)); ?></span></div>
              <div class="small text-muted"><?php echo $e($s['session_type'] ?? ''); ?><?php echo !empty($s['classroom']) ? ' · ' . $e($s['classroom']) : ''; ?><?php echo !empty($s['staff_name']) ? ' · ' . $e($s['staff_name']) : ''; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
