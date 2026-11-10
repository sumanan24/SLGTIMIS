<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$types = TrainingProgrammeModel::types();
$isList = ($pageKey ?? '') === 'tp-schedule';
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-people-carry-box me-2 text-primary"></i><?php echo att_e($title); ?></h1>
      <p class="att-sub">Training for groups or individual students. Programme attendance uses biometric records and is kept separate from class attendance.</p>
    </div>
    <?php if (!empty($canManage)): ?>
      <a class="btn btn-primary" href="<?php echo att_e($app . '/training-programmes/create'); ?>"><i class="fas fa-plus me-1"></i>Schedule Programme</a>
    <?php endif; ?>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>
  <?php include __DIR__ . '/_nav.php'; ?>

  <?php if ($isList): ?>
  <form class="att-filters" method="get" action="<?php echo att_e($app . '/training-programmes'); ?>">
    <?php if (!empty($departments)): ?>
      <div><label>Department</label><select class="form-select" name="department_id"><option value="">All</option>
        <?php foreach ($departments as $d): ?><option value="<?php echo att_e($d['department_id']); ?>" <?php echo (string) $filters['department_id'] === (string) $d['department_id'] ? 'selected' : ''; ?>><?php echo att_e($d['department_name']); ?></option><?php endforeach; ?>
      </select></div>
    <?php endif; ?>
    <div><label>Status</label><select class="form-select" name="status"><option value="">All</option>
      <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'cancelled' => 'Cancelled'] as $k => $v): ?><option value="<?php echo $k; ?>" <?php echo $filters['status'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option><?php endforeach; ?>
    </select></div>
    <div><label>From</label><input type="date" class="form-control" name="from" value="<?php echo att_e($filters['date_from']); ?>"></div>
    <div><label>To</label><input type="date" class="form-control" name="to" value="<?php echo att_e($filters['date_to']); ?>"></div>
    <div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></div>
  </form>
  <?php endif; ?>

  <div class="att-card"><div class="att-table-wrap">
  <?php if ($rows): ?>
    <table class="att-table"><thead><tr><th>Programme</th><th>Type</th><th>Department</th><th>Instructor</th><th>Dates</th><th>Sessions</th><th>Participants</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td class="fw-semibold"><?php echo att_e($r['title']); ?></td>
        <td><?php echo att_e($types[$r['training_type']] ?? $r['training_type']); ?></td>
        <td><?php echo att_e($r['department_name']); ?></td>
        <td><?php echo att_e($r['instructor_name'] ?: '—'); ?></td>
        <td class="text-nowrap"><?php echo att_e($r['first_date']); ?><?php echo $r['last_date'] !== $r['first_date'] ? ' → ' . att_e($r['last_date']) : ''; ?></td>
        <td><?php echo (int) $r['session_count']; ?></td>
        <td><?php echo (int) $r['participant_count']; ?></td>
        <td><?php echo att_badge(ucfirst($r['status'])); ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo att_e($app . '/training-programmes/show?id=' . (int) $r['id']); ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php else: ?><div class="att-empty">No training programmes found.</div><?php endif; ?>
  </div></div>
</div>
