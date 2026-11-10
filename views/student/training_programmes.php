<?php
$e = static function ($v) { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8'); };
?>
<div class="container-fluid px-3 px-md-4 py-3">
  <h1 class="h5 fw-bold mb-3" style="color:#0b2545"><i class="fas fa-people-carry-box me-2 text-primary"></i>Training Programmes</h1>
  <?php if (!$items): ?>
    <div class="alert alert-info">You have no published training programmes.</div>
  <?php endif; ?>
  <div class="row g-3">
  <?php foreach ($items as $it): $p = $it['programme']; ?>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between"><strong><?php echo $e($p['title']); ?></strong><span class="badge bg-primary"><?php echo $e($p['training_type']); ?></span></div>
        <div class="card-body">
          <div class="small text-muted mb-2"><?php echo $e($p['department_name']); ?> · Instructor: <?php echo $e($p['instructor_name'] ?: 'Not assigned'); ?></div>
          <?php if ($p['description']): ?><p class="small"><?php echo nl2br($e($p['description'])); ?></p><?php endif; ?>
          <table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Time</th><th>Location</th></tr></thead><tbody>
            <?php foreach ($it['schedules'] as $s): ?>
              <tr class="<?php echo $s['session_date'] < $today ? 'text-muted' : ''; ?>"><td><?php echo $e(date('D, d M Y', strtotime($s['session_date']))); ?></td>
                <td><?php echo $e(substr($s['start_time'], 0, 5) . '–' . substr($s['end_time'], 0, 5)); ?></td><td><?php echo $e($s['location'] ?: '—'); ?></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
