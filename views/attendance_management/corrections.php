<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$tabUrl = static function ($t) use ($app) { return att_e($app . '/attendance-management/corrections?tab=' . $t); };
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-user-edit me-2 text-primary"></i>Attendance Corrections</h1>
      <p class="att-sub">Corrections of the current month are applied by the Head of Department or approved by them. Closed months need an exceptional request approved by the higher authority.</p>
    </div>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>
  <div class="att-tabs">
    <a href="<?php echo $tabUrl('pending'); ?>" class="<?php echo $tab === 'pending' ? 'active' : ''; ?>">Pending approval</a>
    <a href="<?php echo $tabUrl('history'); ?>" class="<?php echo $tab === 'history' ? 'active' : ''; ?>">All corrections</a>
    <a href="<?php echo $tabUrl('audit'); ?>" class="<?php echo $tab === 'audit' ? 'active' : ''; ?>">Audit trail</a>
  </div>

  <?php if ($tab !== 'pending'): ?>
  <form class="att-filters" method="get" action="<?php echo att_e($app . '/attendance-management/corrections'); ?>">
    <input type="hidden" name="tab" value="<?php echo att_e($tab); ?>">
    <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['from']); ?>"></div>
    <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['to']); ?>"></div>
    <?php if ($tab === 'audit'): ?>
      <div><label>Reg No</label><input class="form-control" name="student_id" value="<?php echo att_e($filters['student_id']); ?>"></div>
      <div><label>Attendance date</label><input type="date" class="form-control" name="date" value="<?php echo att_e($filters['date']); ?>"></div>
    <?php endif; ?>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>
  <?php endif; ?>

  <?php if ($tab === 'audit'): ?>
    <div class="att-card"><div class="att-table-wrap">
      <?php if ($audit): ?>
      <table class="att-table"><thead><tr><th>When</th><th>Student</th><th>Attendance date</th><th>Action</th><th>Previous</th><th>New</th><th>By</th><th>Reason</th><th>Approval info</th></tr></thead><tbody>
      <?php foreach ($audit as $a): ?>
        <tr><td class="text-nowrap"><?php echo att_e(date('d M Y H:i', strtotime($a['performed_at']))); ?></td><td class="att-mono"><?php echo att_e($a['student_id']); ?></td><td><?php echo att_e($a['attendance_date']); ?></td>
          <td><?php echo att_e(ucwords(str_replace('_', ' ', $a['action']))); ?></td>
          <td><?php echo att_e($a['prev_status'] ? att_status_label($a['prev_status']) : '—'); ?></td><td><?php echo att_e($a['new_status'] ? att_status_label($a['new_status']) : '—'); ?></td>
          <td><?php echo att_e($a['performed_by']); ?></td><td class="small" style="max-width:260px"><?php echo att_e($a['reason']); ?></td><td class="small att-muted"><?php echo att_e($a['approval_info']); ?></td></tr>
      <?php endforeach; ?></tbody></table>
      <?php else: ?><div class="att-empty">No audit records.</div><?php endif; ?>
    </div></div>
  <?php else: $list = $tab === 'pending' ? $pending : $history; ?>
    <div class="att-card"><div class="att-table-wrap">
      <?php if ($list): ?>
      <table class="att-table"><thead><tr><th>Requested</th><th>Student</th><th>Attendance date</th><th>Change</th><th>Reason</th><th>Requested by</th><th>Type</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($list as $c):
          $exc = (int) $c['exceptional'] === 1;
          $mayDecide = $c['approval_status'] === 'pending' && ($exc ? $ctx['canApproveExceptional'] : $ctx['canCorrect']) && (int) $c['requested_by_user'] !== (int) $ctx['userId'];
      ?>
        <tr>
          <td class="text-nowrap"><?php echo att_e(date('d M Y H:i', strtotime($c['requested_at']))); ?></td>
          <td><span class="att-mono"><?php echo att_e($c['student_id']); ?></span><div class="small att-muted"><?php echo att_e($c['student_name']); ?></div></td>
          <td><?php echo att_e($c['attendance_date']); ?></td>
          <td class="text-nowrap"><?php echo att_e(att_status_label($c['prev_status']) . ' → ' . att_status_label($c['new_status'])); ?></td>
          <td class="small" style="max-width:260px"><?php echo att_e($c['reason']); ?><?php if ($c['approval_remarks']): ?><div class="att-muted">Decision: <?php echo att_e($c['approval_remarks']); ?></div><?php endif; ?></td>
          <td><?php echo att_e($c['requested_by']); ?></td>
          <td><?php echo $exc ? '<span class="att-chip att-pending">Exceptional</span>' : 'Normal'; ?></td>
          <td><?php echo att_badge($c['approval_status']); ?><?php if ($c['approved_by']): ?><div class="small att-muted"><?php echo att_e($c['approved_by']); ?></div><?php endif; ?></td>
          <td class="text-nowrap att-actions">
            <?php if ($mayDecide): ?>
              <button class="btn btn-sm btn-success att-decide" data-id="<?php echo (int) $c['id']; ?>" data-decision="approve">Approve</button>
              <button class="btn btn-sm btn-outline-danger att-decide" data-id="<?php echo (int) $c['id']; ?>" data-decision="reject">Reject</button>
            <?php elseif ($c['approval_status'] === 'pending'): ?><span class="att-muted small"><?php echo $exc ? 'Awaiting higher authority' : 'Awaiting HOD'; ?></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?></tbody></table>
      <?php else: ?><div class="att-empty"><?php echo $tab === 'pending' ? 'No requests are waiting for approval.' : 'No corrections found.'; ?></div><?php endif; ?>
    </div></div>
  <?php endif; ?>
</div>

<div class="modal fade att-page" id="decideModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="<?php echo att_e($app . '/attendance-management/decide'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>">
    <input type="hidden" name="id" id="dcId"><input type="hidden" name="decision" id="dcDecision">
    <div class="modal-header"><h5 class="modal-title" id="dcTitle">Decision</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label fw-semibold" id="dcLabel">Remarks</label><textarea class="form-control" name="remarks" rows="3" maxlength="255"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Confirm</button></div>
  </form></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.att-decide').forEach(function (b) {
    b.addEventListener('click', function () {
      var ap = b.dataset.decision === 'approve';
      document.getElementById('dcId').value = b.dataset.id;
      document.getElementById('dcDecision').value = b.dataset.decision;
      document.getElementById('dcTitle').textContent = ap ? 'Approve correction' : 'Reject correction';
      document.getElementById('dcLabel').textContent = ap ? 'Remarks (optional)' : 'Remarks (required)';
      document.querySelector('#decideModal textarea').required = !ap;
      new bootstrap.Modal(document.getElementById('decideModal')).show();
    });
  });
});
</script>
