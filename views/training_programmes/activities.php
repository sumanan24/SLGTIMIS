<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$pageKey = 'tp-activities';
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-list-check me-2 text-primary"></i>Training Activities</h1>
      <p class="att-sub">Published training sessions in date order.</p>
    </div>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>
  <?php include __DIR__ . '/_nav.php'; ?>
  <form class="att-filters" method="get" action="<?php echo att_e($app . '/training-programmes/activities'); ?>">
    <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['from']); ?>"></div>
    <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['to']); ?>"></div>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>
  <div class="att-card"><div class="att-table-wrap">
  <?php if ($sessions): ?>
    <table class="att-table"><thead><tr><th>Date</th><th>Time</th><th>Programme</th><th>Type</th><th>Location</th><th>Instructor</th><th>Participants</th><th></th></tr></thead><tbody>
    <?php foreach ($sessions as $s): $p = $s['programme']; $sc = $s['schedule']; ?>
      <tr>
        <td class="text-nowrap"><?php echo att_e(date('D, d M Y', strtotime($sc['session_date']))); ?></td>
        <td class="text-nowrap"><?php echo att_e(att_time($sc['start_time']) . ' – ' . att_time($sc['end_time'])); ?></td>
        <td class="fw-semibold"><?php echo att_e($p['title']); ?></td>
        <td><?php echo att_e($p['training_type']); ?></td>
        <td><?php echo att_e($sc['location'] ?: '—'); ?></td>
        <td><?php echo att_e($p['instructor_name'] ?: '—'); ?></td>
        <td><?php echo (int) $p['participant_count']; ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo att_e($app . '/training-programmes/show?id=' . (int) $p['id']); ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php else: ?><div class="att-empty">No published sessions in this period.</div><?php endif; ?>
  </div></div>
</div>
