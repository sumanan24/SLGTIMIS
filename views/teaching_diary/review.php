<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-clipboard-check me-2 text-primary"></i>Diary Review</h1>
      <p class="att-sub">Review the teaching records your instructors have submitted. Approve them, or return them with remarks.</p>
    </div>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>

  <form class="att-filters" method="get" action="<?php echo att_e($app . '/teaching-diary/review'); ?>">
    <div><label>Instructor</label>
      <select class="form-select" name="staff_id"><option value="">All</option>
        <?php foreach ($staffList as $s): ?><option value="<?php echo att_e($s['staff_id']); ?>" <?php echo $filters['staff_id'] === $s['staff_id'] ? 'selected' : ''; ?>><?php echo att_e($s['staff_name'] . ' (' . $s['staff_id'] . ')'); ?></option><?php endforeach; ?>
      </select></div>
    <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['from']); ?>"></div>
    <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['to']); ?>"></div>
    <div><label>Status</label>
      <select class="form-select" name="status">
        <?php foreach (['Submitted' => 'Awaiting review', 'Reviewed by HOD' => 'Reviewed', 'Pending Submission' => 'Pending submission', 'Not Conducted' => 'Not conducted', 'all' => 'All'] as $k => $v): ?>
          <option value="<?php echo att_e($k); ?>" <?php echo $filters['status'] === $k ? 'selected' : ''; ?>><?php echo att_e($v); ?></option>
        <?php endforeach; ?>
      </select></div>
    <?php if (!$ctx['scoped']): ?>
    <div><label>Department ID</label><input class="form-control" name="department_id" value="<?php echo att_e($filters['department_id']); ?>" placeholder="All"></div>
    <?php endif; ?>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>

  <div class="att-card"><div class="att-table-wrap">
    <?php if ($entries): ?>
    <table class="att-table">
      <thead><tr><th>Date</th><th>Instructor</th><th>Group</th><th>Module</th><th>Type</th><th>Sched. / actual h</th><th>Topic &amp; methods</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($entries as $r): $mod = trim((string) $r['module_name']) !== '' ? $r['module_name'] : ($r['subject'] ?: $r['module_id']); ?>
        <tr>
          <td class="text-nowrap"><?php echo att_e(date('d M Y', strtotime($r['session_date']))); ?><div class="att-muted small att-mono"><?php echo att_e(att_time($r['start_time']) . '–' . att_time($r['end_time'])); ?></div></td>
          <td><?php echo att_e($r['staff_name'] ?: $r['staff_id']); ?></td>
          <td><?php echo att_e($r['group_name']); ?></td>
          <td><?php echo att_e($mod); ?></td>
          <td><?php echo att_e($r['session_type']); ?></td>
          <td><?php echo att_e(number_format((float) $r['scheduled_hours'], 2) . ' / ' . ($r['actual_hours'] === null ? '—' : number_format((float) $r['actual_hours'], 2))); ?></td>
          <td style="max-width:320px">
            <?php if ($r['diary_status'] === 'Not Conducted'): ?><strong>Not conducted:</strong> <?php echo att_e($r['not_conducted_reason']); ?>
            <?php else: ?><strong><?php echo att_e($r['topic_covered']); ?></strong>
              <?php if ($r['teaching_methods']): ?><div class="att-muted small">Methods: <?php echo att_e($r['teaching_methods']); ?></div><?php endif; ?>
              <?php if ($r['learning_outcomes']): ?><div class="att-muted small">Outcomes: <?php echo att_e($r['learning_outcomes']); ?></div><?php endif; ?>
              <?php if ($r['activities_completed']): ?><div class="att-muted small">Activities: <?php echo att_e($r['activities_completed']); ?></div><?php endif; ?>
            <?php endif; ?>
            <?php if ($r['review_remarks']): ?><div class="small text-primary">HOD: <?php echo att_e($r['review_remarks']); ?></div><?php endif; ?>
          </td>
          <td><?php echo att_badge($r['display_status']); ?></td>
          <td class="text-nowrap">
            <?php if ($r['diary_status'] === 'Submitted' && $r['staff_id'] !== $ctx['staffId']): ?>
              <button class="btn btn-sm btn-success att-review" data-id="<?php echo (int) $r['instructor_diary_id']; ?>" data-decision="approve">Approve</button>
              <button class="btn btn-sm btn-outline-danger att-review" data-id="<?php echo (int) $r['instructor_diary_id']; ?>" data-decision="return">Return</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="att-empty">No diary entries match these filters.</div><?php endif; ?>
  </div></div>
</div>

<div class="modal fade att-page" id="reviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="<?php echo att_e($app . '/teaching-diary/review-action'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>">
    <input type="hidden" name="diary_id" id="rvId"><input type="hidden" name="decision" id="rvDecision">
    <div class="modal-header"><h5 class="modal-title" id="rvTitle">Review</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label fw-semibold" id="rvLabel">Remarks</label><textarea class="form-control" name="remarks" rows="3" maxlength="500"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="rvSubmit">Confirm</button></div>
  </form></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.att-review').forEach(function (b) {
    b.addEventListener('click', function () {
      var approve = b.dataset.decision === 'approve';
      document.getElementById('rvId').value = b.dataset.id;
      document.getElementById('rvDecision').value = b.dataset.decision;
      document.getElementById('rvTitle').textContent = approve ? 'Approve entry' : 'Return entry to instructor';
      document.getElementById('rvLabel').textContent = approve ? 'Remarks (optional)' : 'Remarks (required)';
      document.querySelector('#reviewModal textarea').required = !approve;
      new bootstrap.Modal(document.getElementById('reviewModal')).show();
    });
  });
});
</script>
