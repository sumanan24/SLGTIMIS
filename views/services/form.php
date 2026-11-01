<?php require BASE_PATH . '/views/services/partials/shell.php';
$row = $row ?? [];
?>
<form class="svc-form" method="post" action="<?php echo APP_URL; ?>/services/save" enctype="multipart/form-data">
    <input type="hidden" name="area" value="<?php echo $h($area); ?>">
    <input type="hidden" name="resource" value="<?php echo $h($resource_key); ?>">
    <input type="hidden" name="id" value="<?php echo (int) ($row['id'] ?? 0); ?>">
    <div class="svc-fields">
        <?php foreach ($resource['fields'] as $name => $field):
            $value = $row[$name] ?? '';
            if ($field['type'] === 'datetime' && $value) {
                $value = str_replace(' ', 'T', substr((string) $value, 0, 16));
            } ?>
            <label>
                <span><?php echo $h($field['label']); ?><?php if (!empty($field['required'])): ?> <b>*</b><?php endif; ?></span>
                <?php if ($field['type'] === 'textarea'): ?>
                    <textarea class="form-control" name="<?php echo $h($name); ?>" rows="3"><?php echo $h($value); ?></textarea>
                <?php elseif ($field['type'] === 'select'): ?>
                    <select class="form-select" name="<?php echo $h($name); ?>">
                        <option value="">Select</option>
                        <?php foreach ($field['options'] as $opt => $label): ?>
                            <option value="<?php echo $h($opt); ?>" <?php echo (string) $value === (string) $opt ? 'selected' : ''; ?>><?php echo $h($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($field['type'] === 'category'): ?>
                    <select class="form-select" name="category_id">
                        <option value="">Select</option>
                        <?php foreach ($options['categories'] as $cat): ?>
                            <option value="<?php echo (int) $cat['id']; ?>" <?php echo (string) $value === (string) $cat['id'] ? 'selected' : ''; ?>><?php echo $h($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (in_array($field['type'], ['staff', 'provider', 'unit', 'inventory', 'contract'], true)):
                    $bucket = ['staff' => 'staff', 'provider' => 'providers', 'unit' => 'units', 'inventory' => 'inventory', 'contract' => 'contracts'][$field['type']]; ?>
                    <select class="form-select" name="<?php echo $h($name); ?>">
                        <option value="">Select</option>
                        <?php foreach ($options[$bucket] as $opt):
                            $text = $opt['full_name'] ?? $opt['provider_name'] ?? $opt['unit_code'] ?? $opt['item_name'] ?? $opt['contract_code'] ?? $opt['id'];
                            if (!empty($opt['staff_code'])) { $text .= ' (' . $opt['staff_code'] . ')'; }
                            if (!empty($opt['room'])) { $text .= ' - ' . $opt['room']; } ?>
                            <option value="<?php echo (int) $opt['id']; ?>" <?php echo (string) $value === (string) $opt['id'] ? 'selected' : ''; ?>><?php echo $h($text); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($field['type'] === 'file'): ?>
                    <input class="form-control" type="file" name="<?php echo $h($name); ?>" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <?php if (!empty($value)): ?><a href="<?php echo APP_URL; ?>/services/download?file=<?php echo urlencode($value); ?>">Current file</a><?php endif; ?>
                <?php else:
                    $type = $field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : ($field['type'] === 'datetime' ? 'datetime-local' : ($field['type'] === 'month' ? 'month' : 'text')));
                    $step = $field['type'] === 'number' ? ' step="0.01"' : ''; ?>
                    <input class="form-control" type="<?php echo $h($type); ?>" name="<?php echo $h($name); ?>" value="<?php echo $h($value); ?>"<?php echo $step; ?><?php echo $name === 'finger_machine_no' ? ' inputmode="numeric" maxlength="50" placeholder="Example: 101"' : ''; ?>>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="svc-actions">
        <button class="btn btn-primary" type="submit">Save</button>
        <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/services/records?area=<?php echo $h($area); ?>&resource=<?php echo $h($resource_key); ?>">Cancel</a>
    </div>
</form>
</div></div></div>
