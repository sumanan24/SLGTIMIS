<?php
$fields = $fields ?? [];
$options = $options ?? [];
$record = $record ?? [];
$recordId = (int) ($record['id'] ?? ($record_id ?? 0));
$lockStudent = !isset($lockStudent) || $lockStudent;
$returnTo = $return_to ?? '';
$cancelUrl = $cancel_url ?? '';
?>
<form method="post" action="<?php echo APP_URL; ?>/lifecycle/save" class="lc-fields" <?php echo !empty($multipart) ? 'enctype="multipart/form-data"' : ''; ?> onsubmit="return confirm('Save this record?');">
    <input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
    <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
    <?php if ($lockStudent): ?>
        <input type="hidden" name="student_id" value="<?php echo $h($studentId ?? ''); ?>">
    <?php endif; ?>
    <input type="hidden" name="tab" value="<?php echo $h($tab ?? ''); ?>">
    <?php if ($recordId > 0): ?><input type="hidden" name="record_id" value="<?php echo $recordId; ?>"><?php endif; ?>
    <?php if ($returnTo !== ''): ?><input type="hidden" name="return_to" value="<?php echo $h($returnTo); ?>"><?php endif; ?>
    <?php foreach ($fields as $field):
        $name = $field['name'];
        $type = $field['type'] ?? 'text';
        $current = isset($record[$name]) ? (string) $record[$name] : '';
        $list = $field['options'] ?? [];
        if (!empty($field['options_key'])) {
            $list = $options[$field['options_key']] ?? [];
        }
        ?>
        <label>
            <span><?php echo $h($field['label']); ?><?php echo !empty($field['required']) ? ' *' : ''; ?></span>
            <?php if ($type === 'select'): ?>
                <select class="form-select" name="<?php echo $h($name); ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                    <option value="">Select</option>
                    <?php foreach ($list as $value => $label): ?>
                        <option value="<?php echo $h($value); ?>" <?php echo (string) $value === $current ? 'selected' : ''; ?>><?php echo $h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($type === 'textarea'): ?>
                <textarea class="form-control" name="<?php echo $h($name); ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>><?php echo $h($current); ?></textarea>
            <?php else: ?>
                <input class="form-control" type="<?php echo $type === 'number' ? 'number' : ($type === 'date' ? 'date' : ($type === 'email' ? 'email' : 'text')); ?>" name="<?php echo $h($name); ?>" value="<?php echo $h($current); ?>" <?php echo $type === 'number' ? 'step="0.01"' : ''; ?> <?php echo !empty($field['required']) ? 'required' : ''; ?>>
            <?php endif; ?>
        </label>
    <?php endforeach; ?>
    <?php if (!empty($multipart)): ?>
        <label><span>Scanned copy (PDF, JPG or PNG, up to 5 MB)</span><input class="form-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"></label>
    <?php endif; ?>
    <div class="lc-filter-actions">
        <button class="btn btn-primary" type="submit"><?php echo $recordId > 0 ? 'Update' : 'Save'; ?></button>
        <?php if ($cancelUrl !== ''): ?><a class="btn btn-outline-secondary" href="<?php echo $h($cancelUrl); ?>">Cancel</a><?php endif; ?>
    </div>
</form>
<?php $multipart = false; ?>
