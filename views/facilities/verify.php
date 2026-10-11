<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$t = $ticket;
$id = (int) $t['id'];
?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header"><h5 class="fw-bold mb-0">Check completed work</h5></div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p><?php echo $badgeStatus($t['status']); ?> <?php echo $badgePriority($t['current_priority']); ?></p>
            <h6 class="fw-bold"><?php echo $h($t['title']); ?></h6>
            <p><?php echo nl2br($h($t['description'])); ?></p>
            <p class="fac-note">Assigned to <?php echo $h($t['responsible_name'] ?? '—'); ?> · Deadline <?php echo $h($t['deadline'] ?? '—'); ?></p>

            <div class="fac-section">
                <h6>What they did</h6>
                <?php if (!$progress_rows): ?>
                    <div class="fac-empty">No updates.</div>
                <?php else: ?>
                <ul class="fac-timeline mb-0">
                    <?php foreach ($progress_rows as $p): ?>
                        <li><strong><?php echo (int) $p['progress_percent']; ?>% — <?php echo $h($p['work_update']); ?></strong><span><?php echo $h($p['created_at']); ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <div class="fac-evidence-grid mb-3">
                <?php foreach (['before' => 'Before', 'during' => 'During', 'after' => 'After'] as $ek => $el): ?>
                <div class="fac-ev-box">
                    <h4><?php echo $h($el); ?></h4>
                    <?php $set = array_merge($evidence[$ek] ?? [], $ek === 'after' ? ($evidence['completion'] ?? []) : []); ?>
                    <?php if (!$set): ?><div class="text-muted small">None</div>
                    <?php else: foreach ($set as $ev): ?>
                        <div><a href="<?php echo $base; ?>/download?id=<?php echo $id; ?>&file=<?php echo (int) $ev['id']; ?>" target="_blank"><?php echo $h($ev['original_name']); ?></a></div>
                    <?php endforeach; endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?php echo $base; ?>/verify-save" class="fac-section">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <h6>Your decision</h6>
                <label class="form-label">Remarks</label>
                <textarea class="form-control mb-3" name="remarks" placeholder="Optional"></textarea>
                <details class="mb-3">
                    <summary class="fac-note">If the work is not good enough</summary>
                    <label class="form-label mt-2">What must be corrected?</label>
                    <textarea class="form-control mb-2" name="required_correction"></textarea>
                    <label class="form-label">New deadline</label>
                    <input type="date" class="form-control" name="new_deadline" style="max-width:200px">
                </details>
                <button class="btn btn-success" name="decision" value="approve">Approve</button>
                <button class="btn btn-outline-danger" name="decision" value="return">Send back</button>
                <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/view?id=<?php echo $id; ?>">Back</a>
            </form>
        </div>
    </div>
</div>
