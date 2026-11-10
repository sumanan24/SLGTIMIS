<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$time = static function ($name, $label, $help = '') use ($cfg) {
    echo '<div class="col-md-4"><label class="form-label fw-semibold">' . att_e($label) . '</label><input type="time" class="form-control" name="' . att_e($name) . '" value="' . att_e($cfg[$name]) . '" required>'
        . ($help !== '' ? '<div class="form-text">' . att_e($help) . '</div>' : '') . '</div>';
};
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-sliders-h me-2 text-primary"></i>Attendance Rules</h1>
      <p class="att-sub">Biometric attendance windows and default period times (Asia/Colombo). Changes apply to open months; closed months keep their stored results.</p>
    </div>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>
  <form method="post" action="<?php echo att_e($app . '/attendance-management/settings'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>">
    <div class="att-card"><div class="att-card-head"><h2>Daily attendance windows</h2></div><div class="p-3"><div class="row g-3">
      <?php $time('morning_start', 'Morning window starts', 'Earliest punch that counts as IN'); ?>
      <?php $time('morning_end', 'Morning window ends', 'Punches after this are not morning IN'); ?>
      <?php $time('evening_start', 'Evening OUT after', 'Latest punch after this time is OUT'); ?>
      <?php $time('valid_from', 'Valid punches from', 'Earlier punches are ignored'); ?>
      <?php $time('valid_to', 'Valid punches until', 'Later punches are ignored'); ?>
      <div class="col-md-4"><label class="form-label fw-semibold">Duplicate scan interval (seconds)</label><input type="number" min="0" max="3600" class="form-control" name="duplicate_seconds" value="<?php echo att_e($cfg['duplicate_seconds']); ?>" required><div class="form-text">Repeated scans within this interval count once</div></div>
    </div>
    <div class="att-note mt-3 mb-0">Full day (1.0) = morning IN and evening OUT. Half day (0.5) = only one of them, or punches that do not fit the windows. Absent (0) = no valid punch.</div></div></div>
    <div class="att-card"><div class="att-card-head"><h2>Default period times (timetable)</h2></div><div class="p-3"><div class="row g-3">
      <?php foreach (['P1', 'P2', 'P3', 'P4'] as $p): ?>
        <?php $time('period_' . $p . '_start', 'Period ' . substr($p, 1) . ' starts'); ?>
        <?php $time('period_' . $p . '_end', 'Period ' . substr($p, 1) . ' ends'); ?>
        <div class="col-md-4 d-none d-md-block"></div>
      <?php endforeach; ?>
    </div></div></div>
    <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Save rules</button>
  </form>
</div>
