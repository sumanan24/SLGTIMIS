<?php require __DIR__ . '/partials/shell.php';
$studentId = $student_id ?? '';
$record = $record ?? [];
$returnTo = 'records';
$cancelUrl = APP_URL . '/lifecycle/records?kind=' . urlencode($kind);
if (($tab ?? '') !== '') {
    $returnTo = '';
    $cancelUrl = APP_URL . '/lifecycle/profile?id=' . urlencode($studentId) . '&tab=' . urlencode($tab);
}
?>
<div class="lc-block">
    <h3><?php echo $h($title); ?></h3>
    <?php if (!empty($spec['needs_student'])): ?>
        <form method="get" action="<?php echo APP_URL; ?>/lifecycle/record/form" class="lc-filters">
            <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
            <?php if (!empty($record['id'])): ?><input type="hidden" name="record_id" value="<?php echo (int) $record['id']; ?>"><?php endif; ?>
            <label>
                <span>Student registration number *</span>
                <input class="form-control" name="student_id" value="<?php echo $h($studentId); ?>" required <?php echo !empty($record['id']) ? 'readonly' : ''; ?>>
            </label>
            <?php if (empty($record['id'])): ?>
                <div class="lc-filter-actions"><button class="btn btn-outline-primary" type="submit">Load student</button></div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
    <?php if (empty($spec['needs_student']) || $studentId !== ''):
        $fields = $spec['fields'];
        $lockStudent = true;
        $return_to = 'records';
        $cancel_url = APP_URL . '/lifecycle/records?kind=' . urlencode($kind);
        if (!empty($tab) && $studentId !== '') {
            $return_to = '';
            $cancel_url = APP_URL . '/lifecycle/profile?id=' . urlencode($studentId) . '&tab=' . urlencode($tab);
        }
        require __DIR__ . '/partials/form.php';
    else: ?>
        <p class="lc-note">Enter the registration number and load the student before saving.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/close.php'; ?>
