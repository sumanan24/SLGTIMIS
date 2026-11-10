<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$back = 'teaching-diary/' . ($mode === 'lessons' ? 'lessons' : ($mode === 'practical' ? 'practical' : 'daily'));
$counts = ['Scheduled' => 0, 'Pending Submission' => 0, 'Completed' => 0, 'Submitted' => 0, 'Reviewed by HOD' => 0, 'Not Conducted' => 0, 'In Progress' => 0];
$hoursSched = 0.0;
$hoursActual = 0.0;
foreach ($entries as $r) {
    $counts[$r['display_status']] = ($counts[$r['display_status']] ?? 0) + 1;
    $hoursSched += (float) $r['scheduled_hours'];
    $hoursActual += (float) $r['actual_hours'];
}
$statusOptions = ['Scheduled', 'In Progress', 'Completed', 'Pending Submission', 'Submitted', 'Reviewed by HOD', 'Not Conducted'];
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-book-open me-2 text-primary"></i><?php echo att_e($title); ?></h1>
      <p class="att-sub">Generated from your published timetable. Record what was actually taught, then submit it for HOD review.</p>
    </div>
    <div class="att-actions d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo att_e($app . '/instructor-diary'); ?>"><i class="fas fa-pen me-1"></i>Manual diary entries</a>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo att_e($app . '/teaching-diary/progress'); ?>"><i class="fas fa-chart-line me-1"></i>Teaching progress</a>
    </div>
  </div>

  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>

  <form class="att-filters" method="get" action="<?php echo att_e($app . '/' . $back); ?>">
    <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['from']); ?>" max="<?php echo att_e(date('Y-m-d')); ?>"></div>
    <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['to']); ?>" max="<?php echo att_e(date('Y-m-d')); ?>"></div>
    <div><label>Status</label>
      <select class="form-select" name="status">
        <option value="">All</option>
        <?php foreach ($statusOptions as $s): ?><option value="<?php echo att_e($s); ?>" <?php echo $filters['status'] === $s ? 'selected' : ''; ?>><?php echo att_e($s); ?></option><?php endforeach; ?>
      </select>
    </div>
    <div><button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>

  <div class="att-stats">
    <div class="att-stat"><div class="n"><?php echo count($entries); ?></div><div class="l">Sessions</div></div>
    <div class="att-stat pending"><div class="n"><?php echo (int) $counts['Pending Submission'] + (int) $counts['Scheduled'] + (int) $counts['In Progress']; ?></div><div class="l">To record / submit</div></div>
    <div class="att-stat ok"><div class="n"><?php echo (int) $counts['Submitted'] + (int) $counts['Reviewed by HOD'] + (int) $counts['Completed']; ?></div><div class="l">Recorded</div></div>
    <div class="att-stat bad"><div class="n"><?php echo (int) $counts['Not Conducted']; ?></div><div class="l">Not conducted</div></div>
    <div class="att-stat"><div class="n"><?php echo number_format($hoursActual, 1); ?> / <?php echo number_format($hoursSched, 1); ?></div><div class="l">Actual / scheduled hours</div></div>
  </div>

  <div class="att-card">
    <div class="att-table-wrap">
      <?php if ($entries): ?>
      <table class="att-table">
        <thead><tr><th>Date</th><th>Time</th><th>Group</th><th>Module / class</th><th>Type</th><th>Sched. h</th><th>Actual h</th><th>Status</th><th>Topic</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($entries as $r):
            $st = $r['diary_status'];
            $title2 = trim((string) $r['module_name']) !== '' ? $r['module_name'] : (trim((string) $r['subject']) !== '' ? $r['subject'] : ($r['module_id'] ?: 'Class'));
            $editable = in_array($st, ['Scheduled', 'In Progress', 'Completed', 'Not Conducted'], true);
        ?>
          <tr>
            <td class="text-nowrap"><?php echo att_e(date('D d M Y', strtotime($r['session_date']))); ?></td>
            <td class="text-nowrap att-mono"><?php echo att_e(att_time($r['start_time']) . '–' . att_time($r['end_time'])); ?></td>
            <td><?php echo att_e($r['group_name']); ?></td>
            <td><?php echo att_e($title2); ?></td>
            <td><?php echo att_e($r['session_type']); ?></td>
            <td><?php echo att_e(number_format((float) $r['scheduled_hours'], 2)); ?></td>
            <td><?php echo $r['actual_hours'] === null ? '—' : att_e(number_format((float) $r['actual_hours'], 2)); ?></td>
            <td><?php echo att_badge($r['display_status']); ?>
              <?php if ($r['review_status'] === 'Returned' && $editable): ?><div class="att-muted small" title="<?php echo att_e($r['review_remarks']); ?>">Returned: <?php echo att_e($r['review_remarks']); ?></div><?php endif; ?>
            </td>
            <td style="max-width:240px"><?php echo att_e($st === 'Not Conducted' ? $r['not_conducted_reason'] : $r['topic_covered']); ?></td>
            <td class="text-nowrap">
              <?php if ($editable): ?>
                <?php if ($st === 'Scheduled'): ?>
                  <form method="post" action="<?php echo att_e($app . '/teaching-diary/action'); ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="back" value="<?php echo att_e($back); ?>">
                    <input type="hidden" name="diary_id" value="<?php echo (int) $r['instructor_diary_id']; ?>"><input type="hidden" name="do" value="start">
                    <button class="btn btn-sm btn-outline-primary">Start</button>
                  </form>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-primary att-record"
                  data-id="<?php echo (int) $r['instructor_diary_id']; ?>"
                  data-title="<?php echo att_e($title2 . ' · ' . $r['group_name'] . ' · ' . $r['session_date']); ?>"
                  data-topic="<?php echo att_e($r['topic_covered']); ?>" data-methods="<?php echo att_e($r['teaching_methods']); ?>"
                  data-outcomes="<?php echo att_e($r['learning_outcomes']); ?>" data-activities="<?php echo att_e($r['activities_completed']); ?>"
                  data-hours="<?php echo att_e($r['actual_hours'] !== null && (float) $r['actual_hours'] > 0 ? $r['actual_hours'] : $r['scheduled_hours']); ?>">Record</button>
                <button type="button" class="btn btn-sm btn-outline-danger att-nc" data-id="<?php echo (int) $r['instructor_diary_id']; ?>">Not conducted</button>
                <?php if (in_array($st, ['Completed', 'Not Conducted'], true)): ?>
                  <form method="post" action="<?php echo att_e($app . '/teaching-diary/action'); ?>" class="d-inline" onsubmit="return confirm('Submit this entry to the HOD? You cannot edit it afterwards.');">
                    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="back" value="<?php echo att_e($back); ?>">
                    <input type="hidden" name="diary_id" value="<?php echo (int) $r['instructor_diary_id']; ?>"><input type="hidden" name="do" value="submit">
                    <button class="btn btn-sm btn-success">Submit</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <span class="att-lock"><i class="fas fa-lock me-1"></i>Locked</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="att-empty"><i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>No sessions in this period. Only published timetable classes appear here.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal fade att-page" id="recordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="post" action="<?php echo att_e($app . '/teaching-diary/action'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="back" value="<?php echo att_e($back); ?>">
    <input type="hidden" name="diary_id" id="recId"><input type="hidden" name="do" value="complete">
    <div class="modal-header"><h5 class="modal-title">Record session</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <p class="att-muted small" id="recTitle"></p>
      <div class="row g-3">
        <div class="col-md-9"><label class="form-label fw-semibold">Topic covered *</label><input class="form-control" name="topic_covered" id="recTopic" maxlength="255" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Actual hours *</label><input class="form-control" type="number" step="0.25" min="0.25" max="12" name="actual_hours" id="recHours" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Teaching methods *</label><textarea class="form-control" rows="3" name="teaching_methods" id="recMethods" required placeholder="e.g. Lecture, demonstration, group work"></textarea></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Learning outcomes</label><textarea class="form-control" rows="3" name="learning_outcomes" id="recOutcomes"></textarea></div>
        <div class="col-12"><label class="form-label fw-semibold">Activities completed</label><textarea class="form-control" rows="3" name="activities_completed" id="recActivities" placeholder="Practical tasks, assignments, assessments"></textarea></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save record</button></div>
  </form></div>
</div>

<div class="modal fade att-page" id="ncModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="<?php echo att_e($app . '/teaching-diary/action'); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>"><input type="hidden" name="back" value="<?php echo att_e($back); ?>">
    <input type="hidden" name="diary_id" id="ncId"><input type="hidden" name="do" value="not_conducted">
    <div class="modal-header"><h5 class="modal-title">Session not conducted</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label fw-semibold">Reason *</label><textarea class="form-control" name="reason" rows="3" required minlength="5" maxlength="255"></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Mark not conducted</button></div>
  </form></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.att-record').forEach(function (b) {
    b.addEventListener('click', function () {
      document.getElementById('recId').value = b.dataset.id;
      document.getElementById('recTitle').textContent = b.dataset.title;
      document.getElementById('recTopic').value = b.dataset.topic || '';
      document.getElementById('recMethods').value = b.dataset.methods || '';
      document.getElementById('recOutcomes').value = b.dataset.outcomes || '';
      document.getElementById('recActivities').value = b.dataset.activities || '';
      document.getElementById('recHours').value = b.dataset.hours || '';
      new bootstrap.Modal(document.getElementById('recordModal')).show();
    });
  });
  document.querySelectorAll('.att-nc').forEach(function (b) {
    b.addEventListener('click', function () {
      document.getElementById('ncId').value = b.dataset.id;
      new bootstrap.Modal(document.getElementById('ncModal')).show();
    });
  });
});
</script>
