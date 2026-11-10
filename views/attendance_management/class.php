<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$gid = $group ? (int) $group['id'] : 0;
$back = 'attendance-management/class?' . http_build_query(array_filter(['date' => $date, 'group_id' => $gid, 'department_id' => $filters['department_id'] ?? '']));
$canAct = $ctx['canCorrect'] || $ctx['canRequest'];
$blocks = $result['blocks'];
if ($timetableId > 0) {
    $blocks = array_values(array_filter($blocks, static function ($b) use ($timetableId) { return (int) $b['session']['timetable_id'] === $timetableId; }));
}
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Class Attendance</h1>
      <p class="att-sub">Each published timetable session of the group, with every student's attendance for that day.</p>
    </div>
    <?php if ($group): ?><div class="att-actions"><a class="btn btn-outline-success btn-sm" href="<?php echo att_e($app . '/attendance-management/class?date=' . urlencode($date) . '&group_id=' . $gid . '&export=xlsx'); ?>"><i class="fas fa-file-excel me-1"></i>Excel</a></div><?php endif; ?>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>

  <form class="att-filters" method="get" action="<?php echo att_e($app . '/attendance-management/class'); ?>">
    <div><label>Date</label><input type="date" class="form-control" name="date" value="<?php echo att_e($date); ?>" max="<?php echo att_e(date('Y-m-d')); ?>"></div>
    <?php if (!$ctx['scoped']): ?>
    <div><label>Department</label><select class="form-select" name="department_id" onchange="this.form.submit()"><option value="">All</option>
      <?php foreach ($departments as $d): ?><option value="<?php echo att_e($d['department_id']); ?>" <?php echo (string) ($filters['department_id'] ?? '') === (string) $d['department_id'] ? 'selected' : ''; ?>><?php echo att_e($d['department_name']); ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <div><label>Group</label><select class="form-select" name="group_id" required><option value="">Select group</option>
      <?php foreach ($groups as $g): ?><option value="<?php echo (int) $g['id']; ?>" <?php echo $gid === (int) $g['id'] ? 'selected' : ''; ?>><?php echo att_e($g['name'] . ' (' . $g['academic_year'] . ')'); ?></option><?php endforeach; ?>
    </select></div>
    <?php if ($result['sessions']): ?>
    <div><label>Session</label><select class="form-select" name="timetable_id"><option value="0">All sessions</option>
      <?php foreach ($result['sessions'] as $s): ?><option value="<?php echo (int) $s['timetable_id']; ?>" <?php echo $timetableId === (int) $s['timetable_id'] ? 'selected' : ''; ?>><?php echo att_e(att_time($s['start_clock']) . ' ' . ($s['module_name'] ?: ($s['subject'] ?: 'Class'))); ?></option><?php endforeach; ?>
    </select></div>
    <?php endif; ?>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>

  <?php if (!$group): ?>
    <div class="att-note">Choose a group and a date.</div>
  <?php elseif (!$result['sessions']): ?>
    <div class="att-card"><div class="att-empty"><i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>No published timetable session for <strong><?php echo att_e($group['name']); ?></strong> on <?php echo att_e(date('l d M Y', strtotime($date))); ?>.<br><span class="small">Create and publish classes on the group timetable.</span></div></div>
  <?php else: ?>
    <?php if (!$editState['editable']): ?><div class="att-note"><i class="fas fa-lock me-1"></i><?php echo att_e($editState['reason']); ?></div><?php endif; ?>
    <?php foreach ($blocks as $b): $s = $b['session']; $label = $s['module_name'] ?: ($s['subject'] ?: 'Class'); ?>
      <div class="att-card">
        <div class="att-card-head">
          <h2><?php echo att_e(att_time($s['start_clock']) . '–' . att_time($s['end_clock']) . '  ' . $label); ?>
            <span class="att-chip att-info ms-2"><?php echo att_e($s['session_type']); ?></span></h2>
          <span class="att-muted small"><?php echo att_e($s['staff_name'] ?: $s['staff_id']); ?><?php echo $s['classroom'] ? ' · ' . att_e($s['classroom']) : ''; ?> · <?php echo count($b['rows']); ?> students</span>
        </div>
        <div class="att-table-wrap"><table class="att-table">
          <thead><tr><th>Reg No</th><th>Name</th><th>Group</th><th>IN</th><th>OUT</th><th>Biometric</th><th>Class attendance</th><th>Type</th><th>Source</th><th>Correction</th><th>Indicator</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($b['rows'] as $r):
              $mode = $editState['editable'] ? ($ctx['canCorrect'] ? 'direct' : ($ctx['canRequest'] ? 'request' : '')) : ($ctx['canCorrect'] ? 'exceptional' : '');
          ?>
            <tr>
              <td class="att-mono"><?php echo att_e($r['student_id']); ?></td><td><?php echo att_e($r['student_name']); ?></td><td><?php echo att_e($group['name']); ?></td>
              <td class="att-mono"><?php echo att_e(att_time($r['first_in'])); ?></td><td class="att-mono"><?php echo att_e(att_time($r['last_out'])); ?></td>
              <td><?php echo att_e(att_status_label($r['calc_status'])); ?></td>
              <td><strong><?php echo att_e(att_status_label($r['final_status'])); ?></strong></td>
              <td><?php echo att_e($s['session_type']); ?></td><td><?php echo att_e(ucfirst($r['source'])); ?></td>
              <td><?php echo $r['correction_status'] ? att_badge(ucfirst($r['correction_status'])) : '—'; ?></td>
              <td><?php echo att_badge($r['indicator']); ?></td>
              <td class="att-actions text-nowrap">
                <?php if ($mode !== '' && $canAct): ?>
                  <button type="button" class="btn btn-sm btn-outline-primary att-correct" data-mode="<?php echo att_e($mode); ?>" data-student="<?php echo att_e($r['student_id']); ?>"
                    data-name="<?php echo att_e($r['student_name']); ?>" data-date="<?php echo att_e($date); ?>" data-current="<?php echo att_e($r['final_status']); ?>">
                    <?php echo $mode === 'direct' ? 'Correct' : ($mode === 'request' ? 'Request' : 'Exceptional'); ?></button>
                <?php elseif (!$editState['editable']): ?><span class="att-lock"><i class="fas fa-lock"></i></span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      </div>
    <?php endforeach; ?>
    <?php if ($canAct) { include BASE_PATH . '/views/attendance_management/_correction_modal.php'; } ?>
  <?php endif; ?>
</div>
