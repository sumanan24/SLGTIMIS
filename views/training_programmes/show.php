<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$types = TrainingProgrammeModel::types();
$sch = $att['schedules'];
$parts = $att['participants'];
$matrix = $att['matrix'];
$isDraft = $p['status'] === 'draft';
$isCancelled = $p['status'] === 'cancelled';
$labels = ['present' => ['P', 'att-ok', 'Present'], 'partial' => ['½', 'att-warn', 'Partial'], 'absent' => ['A', 'att-bad', 'Absent'], 'upcoming' => ['·', 'att-muted-chip', 'Upcoming']];
$form = static function ($action, $label, $cls, $extra = '') use ($app, $p, $csrf) {
    return '<form method="post" action="' . att_e($app . '/training-programmes/status') . '" class="d-inline">'
        . '<input type="hidden" name="csrf_token" value="' . att_e($csrf) . '"><input type="hidden" name="id" value="' . (int) $p['id'] . '">'
        . '<input type="hidden" name="action" value="' . $action . '">' . $extra . '<button class="btn btn-sm ' . $cls . '">' . $label . '</button></form>';
};
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><?php echo att_e($p['title']); ?> <?php echo att_badge(ucfirst($p['status'])); ?></h1>
      <p class="att-sub"><?php echo att_e($types[$p['training_type']] ?? $p['training_type']); ?> · <?php echo att_e($p['department_name']); ?>
        <?php echo $p['course_name'] ? ' · ' . att_e($p['course_name']) : ''; ?><?php echo $p['batch'] ? ' · Batch ' . att_e($p['batch']) : ''; ?>
        · Instructor: <?php echo att_e($p['instructor_name'] ?: 'Not assigned'); ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap att-actions">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo att_e($app . '/training-programmes'); ?>">Back</a>
      <?php if (!$isDraft): ?><a class="btn btn-outline-success btn-sm" href="<?php echo att_e($app . '/training-programmes/show?id=' . (int) $p['id'] . '&export=xlsx'); ?>"><i class="fas fa-file-excel me-1"></i>Excel</a><?php endif; ?>
      <?php if (!empty($canManage) && $isDraft): ?><?php echo $form('publish', '<i class="fas fa-bullhorn me-1"></i>Publish', 'btn-primary'); ?><?php endif; ?>
      <?php if (!empty($canManage) && !$isCancelled): ?>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel programme</button>
      <?php endif; ?>
    </div>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>
  <?php if ($isCancelled): ?><div class="alert alert-danger">Cancelled<?php echo $p['cancelled_at'] ? ' on ' . att_e(date('d M Y', strtotime($p['cancelled_at']))) : ''; ?>. Reason: <?php echo att_e($p['cancel_reason']); ?></div><?php endif; ?>
  <?php if ($isDraft): ?><div class="att-note">Draft. Students and attendance reports do not see this programme until it is published.</div><?php endif; ?>
  <?php if ($p['description'] || $p['learning_outcomes']): ?>
    <div class="att-card"><div class="p-3 small">
      <?php if ($p['description']): ?><div class="mb-2"><strong>Description:</strong> <?php echo nl2br(att_e($p['description'])); ?></div><?php endif; ?>
      <?php if ($p['learning_outcomes']): ?><div><strong>Learning outcomes:</strong> <?php echo nl2br(att_e($p['learning_outcomes'])); ?></div><?php endif; ?>
    </div></div>
  <?php endif; ?>

  <div class="att-card">
    <div class="att-card-head"><h2>Schedule and attendance</h2>
      <span class="small att-muted">P present · ½ partial · A absent. Based on biometric records for the session day.</span></div>
    <div class="att-table-wrap">
    <table class="att-table">
      <thead><tr><th>Reg No</th><th>Name</th><th>Group</th>
        <?php foreach ($sch as $s): ?><th class="text-center"><?php echo att_e(date('d M', strtotime($s['session_date']))); ?><div style="font-weight:400"><?php echo att_e(att_time($s['start_time']) . '-' . att_time($s['end_time'])); ?></div></th><?php endforeach; ?>
        <?php if (!empty($canManage) && !$isCancelled): ?><th></th><?php endif; ?></tr></thead>
      <tbody>
      <?php if (!$parts): ?><tr><td colspan="<?php echo 4 + count($sch); ?>" class="att-empty">No participants yet.</td></tr><?php endif; ?>
      <?php foreach ($parts as $pt): ?>
        <tr>
          <td class="att-mono"><?php echo att_e($pt['student_id']); ?></td>
          <td><?php echo att_e($pt['student_name']); ?></td>
          <td><?php echo att_e($pt['group_name'] ?: 'Individual'); ?></td>
          <?php foreach ($sch as $s): $st = $matrix[$pt['student_id']][$s['id']] ?? null; $l = $st ? $labels[$st] : ['—', 'att-muted-chip', '']; ?>
            <td class="text-center"><span class="att-chip <?php echo $l[1]; ?>" title="<?php echo att_e($l[2]); ?>"><?php echo $l[0]; ?></span></td>
          <?php endforeach; ?>
          <?php if (!empty($canManage) && !$isCancelled): ?>
          <td class="text-end"><form method="post" action="<?php echo att_e($app . '/training-programmes/participants'); ?>" onsubmit="return confirm('Remove this participant?');">
            <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
            <input type="hidden" name="action" value="remove"><input type="hidden" name="student_id" value="<?php echo att_e($pt['student_id']); ?>">
            <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="fas fa-user-minus"></i></button></form></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <?php if (!$isDraft && $parts): ?>
      <tfoot><tr><td colspan="3" class="fw-bold">Present / Partial / Absent</td>
        <?php foreach ($sch as $s): $t = $att['totals'][$s['id']] ?? null; ?>
          <td class="text-center small"><?php echo $t ? ((int) $t['upcoming'] > 0 ? 'Upcoming' : (int) $t['present'] . ' / ' . (int) $t['partial'] . ' / ' . (int) $t['absent']) : '—'; ?></td>
        <?php endforeach; ?><?php if (!empty($canManage) && !$isCancelled): ?><td></td><?php endif; ?></tr></tfoot>
      <?php endif; ?>
    </table>
    </div>
  </div>

  <div class="att-card">
    <div class="att-card-head"><h2>Session locations</h2></div>
    <div class="att-table-wrap"><table class="att-table"><thead><tr><th>Date</th><th>Time</th><th>Location</th></tr></thead><tbody>
      <?php foreach ($sch as $s): ?><tr><td><?php echo att_e(date('D, d M Y', strtotime($s['session_date']))); ?></td><td><?php echo att_e(att_time($s['start_time']) . ' – ' . att_time($s['end_time'])); ?></td><td><?php echo att_e($s['location'] ?: '—'); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>

  <?php if (!empty($canManage) && !$isCancelled): ?>
  <div class="att-card">
    <div class="att-card-head"><h2>Add participants</h2></div>
    <form method="post" action="<?php echo att_e($app . '/training-programmes/participants'); ?>" class="p-3 row g-3">
      <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
      <div class="col-md-6"><label class="form-label small fw-bold">Groups</label><select class="form-select" name="group_ids[]" multiple size="5">
        <?php foreach ($groups as $g): ?><option value="<?php echo (int) $g['id']; ?>"><?php echo att_e($g['name']); ?></option><?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label small fw-bold">Registration numbers</label><textarea class="form-control" name="student_ids" rows="5" placeholder="One per line or separated by commas"></textarea></div>
      <div class="col-12"><button class="btn btn-primary btn-sm"><i class="fas fa-user-plus me-1"></i>Add</button></div>
    </form>
  </div>

  <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="<?php echo att_e($app . '/training-programmes/status'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>"><input type="hidden" name="action" value="cancel">
    <div class="modal-header"><h5 class="modal-title">Cancel programme</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label small fw-bold">Reason *</label><textarea class="form-control" name="reason" rows="3" required minlength="5" maxlength="255"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button><button class="btn btn-danger">Cancel programme</button></div>
  </form></div></div>
  <?php endif; ?>
</div>
