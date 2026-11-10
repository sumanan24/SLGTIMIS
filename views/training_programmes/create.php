<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$types = TrainingProgrammeModel::types();
$old = is_array($old ?? null) ? $old : [];
$o = static function ($k, $d = '') use ($old) { return $old[$k] ?? $d; };
$oldGroups = array_map('strval', (array) ($old['group_ids'] ?? []));
$dates = (array) ($old['session_date'] ?? ['']);
$starts = (array) ($old['start_time'] ?? ['']);
$ends = (array) ($old['end_time'] ?? ['']);
$locs = (array) ($old['location'] ?? ['']);
unset($_SESSION['tp_old']);
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-calendar-plus me-2 text-primary"></i>Schedule Training Programme</h1>
      <p class="att-sub">The programme is saved as a draft. Publish it after checking the schedule and participants.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo att_e($app . '/training-programmes'); ?>">Back</a>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>

  <?php if (!empty($departments)): ?>
  <form class="att-filters" method="get" action="<?php echo att_e($app . '/training-programmes/create'); ?>">
    <div><label>Department</label><select class="form-select" name="department_id" onchange="this.form.submit()"><option value="">Select department</option>
      <?php foreach ($departments as $d): ?><option value="<?php echo att_e($d['department_id']); ?>" <?php echo (string) $department_id === (string) $d['department_id'] ? 'selected' : ''; ?>><?php echo att_e($d['department_name']); ?></option><?php endforeach; ?>
    </select></div>
  </form>
  <?php endif; ?>

  <?php if ($department_id === ''): ?>
    <div class="att-card"><div class="att-empty">Select a department to continue.</div></div>
  <?php else: ?>
  <form method="post" action="<?php echo att_e($app . '/training-programmes/create'); ?>" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?php echo att_e($csrf); ?>">
    <input type="hidden" name="department_id" value="<?php echo att_e($department_id); ?>">

    <div class="att-card">
      <div class="att-card-head"><h2>Programme details</h2></div>
      <div class="p-3 row g-3">
        <div class="col-md-6"><label class="form-label small fw-bold">Title *</label><input class="form-control" name="title" maxlength="200" required value="<?php echo att_e($o('title')); ?>"></div>
        <div class="col-md-3"><label class="form-label small fw-bold">Type *</label><select class="form-select" name="training_type">
          <?php foreach ($types as $k => $v): ?><option value="<?php echo att_e($k); ?>" <?php echo $o('training_type', 'Practical') === $k ? 'selected' : ''; ?>><?php echo att_e($v); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label small fw-bold">Instructor</label><select class="form-select" name="instructor_staff_id"><option value="">Not assigned</option>
          <?php foreach ($staff as $s): ?><option value="<?php echo att_e($s['staff_id']); ?>" <?php echo (string) $o('instructor_staff_id') === (string) $s['staff_id'] ? 'selected' : ''; ?>><?php echo att_e($s['staff_name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label small fw-bold">Course</label><select class="form-select" name="course_id"><option value="">Any</option>
          <?php foreach ($courses as $c): ?><option value="<?php echo att_e($c['course_id']); ?>" <?php echo (string) $o('course_id') === (string) $c['course_id'] ? 'selected' : ''; ?>><?php echo att_e($c['course_name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label small fw-bold">Batch</label><input class="form-control" name="batch" maxlength="50" value="<?php echo att_e($o('batch')); ?>"></div>
        <div class="col-md-4"><label class="form-label small fw-bold">Academic year</label><input class="form-control" name="academic_year" maxlength="20" value="<?php echo att_e($o('academic_year')); ?>"></div>
        <div class="col-12"><label class="form-label small fw-bold">Description</label><textarea class="form-control" name="description" rows="2"><?php echo att_e($o('description')); ?></textarea></div>
        <div class="col-12"><label class="form-label small fw-bold">Learning outcomes</label><textarea class="form-control" name="learning_outcomes" rows="2"><?php echo att_e($o('learning_outcomes')); ?></textarea></div>
      </div>
    </div>

    <div class="att-card">
      <div class="att-card-head"><h2>Schedule</h2><button type="button" class="btn btn-sm btn-outline-primary" id="addSession"><i class="fas fa-plus me-1"></i>Add session</button></div>
      <div class="p-3" id="sessionRows">
        <?php foreach ($dates as $i => $d): ?>
        <div class="row g-2 mb-2 session-row">
          <div class="col-md-3"><input type="date" class="form-control" name="session_date[]" value="<?php echo att_e($d); ?>"></div>
          <div class="col-md-2"><input type="time" class="form-control" name="start_time[]" value="<?php echo att_e(substr((string) ($starts[$i] ?? ''), 0, 5)); ?>"></div>
          <div class="col-md-2"><input type="time" class="form-control" name="end_time[]" value="<?php echo att_e(substr((string) ($ends[$i] ?? ''), 0, 5)); ?>"></div>
          <div class="col-md-4"><input class="form-control" name="location[]" maxlength="120" placeholder="Location / workshop / company" value="<?php echo att_e($locs[$i] ?? ''); ?>"></div>
          <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 rm-session" title="Remove"><i class="fas fa-times"></i></button></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="att-card">
      <div class="att-card-head"><h2>Participants</h2></div>
      <div class="p-3 row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-bold">Groups (all active members are added)</label>
          <select class="form-select" name="group_ids[]" multiple size="8">
            <?php foreach ($groups as $g): ?><option value="<?php echo (int) $g['id']; ?>" <?php echo in_array((string) $g['id'], $oldGroups, true) ? 'selected' : ''; ?>><?php echo att_e(($g['group_code'] ?? '') !== '' && isset($g['group_code']) ? $g['group_code'] . ' · ' . $g['name'] : $g['name']); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Individual students (registration numbers)</label>
          <textarea class="form-control" name="student_ids" rows="6" placeholder="One per line or separated by commas"><?php echo att_e($o('student_ids')); ?></textarea>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mb-4">
      <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Save as draft</button>
      <a class="btn btn-outline-secondary" href="<?php echo att_e($app . '/training-programmes'); ?>">Cancel</a>
    </div>
  </form>
  <script>
  (function () {
    var box = document.getElementById('sessionRows');
    document.getElementById('addSession').addEventListener('click', function () {
      var first = box.querySelector('.session-row');
      var c = first.cloneNode(true);
      c.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      box.appendChild(c);
    });
    box.addEventListener('click', function (e) {
      var b = e.target.closest('.rm-session');
      if (b && box.querySelectorAll('.session-row').length > 1) { b.closest('.session-row').remove(); }
    });
  })();
  </script>
  <?php endif; ?>
</div>
