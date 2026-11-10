<?php
require_once BASE_PATH . '/views/attendance_management/_ui.php';
$app = rtrim((string) APP_URL, '/');
$pageKey = 'tp-progress';
?>
<div class="container-fluid px-4 py-3 att-page">
  <div class="att-head">
    <div>
      <h1 class="att-title"><i class="fas fa-chart-line me-2 text-primary"></i>Training Progress</h1>
      <p class="att-sub">Attendance progress of participants in published programmes. Percentage = (present + 0.5 × partial) ÷ sessions held.</p>
    </div>
  </div>
  <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo att_e($message); ?></div><?php endif; ?>
  <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo att_e($error); ?></div><?php endif; ?>
  <?php include __DIR__ . '/_nav.php'; ?>
  <?php if (!$summary): ?>
    <div class="att-card"><div class="att-empty">No published programmes yet.</div></div>
  <?php endif; ?>
  <?php foreach ($summary as $s): $p = $s['programme']; ?>
  <div class="att-card">
    <div class="att-card-head">
      <h2><?php echo att_e($p['title']); ?> <span class="att-muted fw-normal">· <?php echo att_e($p['department_name']); ?></span></h2>
      <span class="small att-muted"><?php echo (int) $s['participants']; ?> participants · average <?php echo att_e($s['average']); ?>%
        · <a href="<?php echo att_e($app . '/training-programmes/show?id=' . (int) $p['id']); ?>">Open</a></span>
    </div>
    <div class="att-table-wrap">
      <table class="att-table"><thead><tr><th>Reg No</th><th>Name</th><th>Present</th><th>Partial</th><th>Sessions held</th><th>Total sessions</th><th style="min-width:160px">Progress</th></tr></thead><tbody>
      <?php foreach ($s['rows'] as $r): $pc = min(100, (float) $r['percent']); ?>
        <tr>
          <td class="att-mono"><?php echo att_e($r['student_id']); ?></td><td><?php echo att_e($r['student_name']); ?></td>
          <td><?php echo (int) $r['present']; ?></td><td><?php echo (int) $r['partial']; ?></td>
          <td><?php echo (int) $r['sessions_held']; ?></td><td><?php echo (int) $r['total_sessions']; ?></td>
          <td><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:8px"><div class="progress-bar <?php echo $pc >= 80 ? 'bg-success' : ($pc >= 50 ? 'bg-warning' : 'bg-danger'); ?>" style="width:<?php echo $pc; ?>%"></div></div><span class="small"><?php echo att_e($r['percent']); ?>%</span></div></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  </div>
  <?php endforeach; ?>
</div>
