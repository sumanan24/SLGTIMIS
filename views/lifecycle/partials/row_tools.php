<?php
$rowKind = $row_kind ?? '';
$rowRecord = $row_record ?? [];
$rowStudent = $row_student ?? ($rowRecord['student_id'] ?? '');
$rowTab = $row_tab ?? '';
?>
<div class="lc-row-tools">
    <?php if (!empty($actor['office']) || !empty($actor['hod'])): ?>
        <a href="<?php echo APP_URL; ?>/lifecycle/record/form?kind=<?php echo urlencode($rowKind); ?>&record_id=<?php echo (int) ($rowRecord['id'] ?? 0); ?>&student_id=<?php echo urlencode((string) $rowStudent); ?>&tab=<?php echo urlencode($rowTab); ?>">Edit</a>
    <?php endif; ?>
    <?php if (!empty($actor['can_delete'])): ?>
        <form method="post" action="<?php echo APP_URL; ?>/lifecycle/delete" onsubmit="return confirm('Archive this record? It can be restored from the register.');">
            <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
            <input type="hidden" name="kind" value="<?php echo $h($rowKind); ?>">
            <input type="hidden" name="student_id" value="<?php echo $h($rowStudent); ?>">
            <input type="hidden" name="tab" value="<?php echo $h($rowTab); ?>">
            <input type="hidden" name="record_id" value="<?php echo (int) ($rowRecord['id'] ?? 0); ?>">
            <input class="form-control form-control-sm" name="reason" required minlength="3" placeholder="Reason">
            <button class="btn btn-sm btn-outline-danger" type="submit">Archive</button>
        </form>
    <?php endif; ?>
</div>
