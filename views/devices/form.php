<?php
$e = static function (?string $s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$d = $device ?? [];
$isEdit = !empty($isEdit);
$locked = (string) ($lockedDepartmentId ?? '');
$action = APP_URL . ($isEdit ? '/devices/update?id=' . (int) ($d['id'] ?? 0) : '/devices/store');
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="asset-title"><?php echo $isEdit ? 'Edit Asset' : 'Add New Asset'; ?></h1>
    <p class="asset-help"><?php echo $isEdit ? 'Change the details, then save.' : 'Fill in the boxes. The asset code and QR code are created for you.'; ?></p>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['import_errors']) && is_array($_SESSION['import_errors'])): ?>
    <div class="alert alert-warning">
        <strong>Some rows were not saved.</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($_SESSION['import_errors'] as $importError): ?>
            <li><?php echo $e((string) $importError); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['import_errors']); endif; ?>

    <?php if (!$isEdit): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5">Add many assets from Excel</h2>
            <p class="asset-help mb-3">Download the file and type one asset on each row. Do not put the department in the Excel file. Choose the department here, then upload. Delete the example row first.</p>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a class="btn btn-outline-primary btn-lg" href="<?php echo APP_URL; ?>/devices/import-template"><i class="fas fa-file-excel"></i> Download Excel file</a>
            </div>
            <form method="post" enctype="multipart/form-data" action="<?php echo APP_URL; ?>/devices/import" onsubmit="return confirm('Save every asset in this Excel file?');">
                <div class="mb-3">
                    <label class="form-label" for="importDepartment">Department</label>
                    <?php if ($locked !== ''): ?>
                    <input type="hidden" name="department_id" value="<?php echo $e($locked); ?>">
                    <input id="importDepartment" class="form-control form-control-lg" readonly value="<?php
                        $importDeptName = $locked;
                        foreach ($departments ?? [] as $dep) {
                            if (($dep['department_id'] ?? '') === $locked) {
                                $importDeptName = (string) ($dep['department_name'] ?? $locked);
                            }
                        }
                        echo $e($importDeptName);
                    ?>">
                    <?php else: ?>
                    <select id="importDepartment" name="department_id" class="form-select form-select-lg" required>
                        <option value="">Choose a department</option>
                        <?php foreach ($departments ?? [] as $dep): ?>
                        <option value="<?php echo $e($dep['department_id'] ?? ''); ?>"><?php echo $e($dep['department_name'] ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <label class="form-label" for="importFile">Excel file</label>
                <input id="importFile" type="file" name="import_file" class="form-control form-control-lg mb-3" accept=".xlsx,.xls,.csv" required>
                <button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-upload"></i> Upload and save</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="<?php echo $action; ?>" class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Asset Name</label>
                    <input name="asset_name" class="form-control form-control-lg" required value="<?php echo $e($d['asset_name'] ?? ''); ?>" placeholder="Example: Office laptop">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select form-select-lg" required>
                        <option value="">Choose a category</option>
                        <?php foreach ($categories ?? [] as $cat): ?>
                        <option value="<?php echo $e($cat); ?>" <?php echo ($d['category'] ?? $d['device_type'] ?? '') === $cat ? 'selected' : ''; ?>><?php echo $e($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand</label>
                    <input name="brand" class="form-control form-control-lg" value="<?php echo $e($d['brand'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Model</label>
                    <input name="model" class="form-control form-control-lg" value="<?php echo $e($d['model'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Serial Number</label>
                    <input name="serial_number" class="form-control form-control-lg" value="<?php echo $e($d['serial_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control form-control-lg" value="<?php echo $e($d['purchase_date'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Price</label>
                    <input type="number" min="0" step="0.01" name="purchase_cost" class="form-control form-control-lg" value="<?php echo $e((string) ($d['purchase_cost'] ?? '')); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantity</label>
                    <input type="number" min="1" name="quantity" class="form-control form-control-lg" value="<?php echo (int) ($d['quantity'] ?? 1); ?>">
                    <div class="form-text">Use more than 1 only when you have several of the same item.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <?php if ($locked !== ''): ?>
                    <input type="hidden" name="department_id" value="<?php echo $e($locked); ?>">
                    <input class="form-control form-control-lg" readonly value="<?php
                        $deptName = $locked;
                        foreach ($departments ?? [] as $dep) {
                            if (($dep['department_id'] ?? '') === $locked) {
                                $deptName = (string) ($dep['department_name'] ?? $locked);
                            }
                        }
                        echo $e($deptName);
                    ?>">
                    <?php else: ?>
                    <select name="department_id" class="form-select form-select-lg" required>
                        <option value="">Choose a department</option>
                        <?php foreach ($departments ?? [] as $dep): ?>
                        <option value="<?php echo $e($dep['department_id'] ?? ''); ?>" <?php echo ($d['department_id'] ?? '') === ($dep['department_id'] ?? '') ? 'selected' : ''; ?>><?php echo $e($dep['department_name'] ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Room / Location</label>
                    <input name="room_location" class="form-control form-control-lg" value="<?php echo $e($d['room_location'] ?? ''); ?>" placeholder="Example: Lab 2">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Asset Condition</label>
                    <select name="asset_condition" class="form-select form-select-lg">
                        <?php foreach ($conditions ?? [] as $cond): ?>
                        <option value="<?php echo $e($cond); ?>" <?php echo ($d['asset_condition'] ?? 'Good') === $cond ? 'selected' : ''; ?>><?php echo $e($cond); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload Photo</label>
                    <input type="file" name="photo" class="form-control form-control-lg" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($d['photo_path'])): ?><img src="<?php echo APP_URL . '/' . $e($d['photo_path']); ?>" alt="" class="asset-photo mt-2"><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"><?php echo $e($d['remarks'] ?? ''); ?></textarea>
                </div>
            </div>

            <?php if ($isEdit): ?>
            <input type="hidden" name="asset_id" value="<?php echo $e($d['asset_id'] ?? ''); ?>">
            <?php endif; ?>
            <?php
            $keep = ['asset_tag_no','product_number','service_tag','computer_name','processor','ram','storage_type','storage_capacity','display_size','operating_system','supplier','warranty_expiry','lan_mac_address','wifi_mac_address','charger_serial_no','battery_serial_no'];
            foreach ($keep as $field):
            ?>
            <input type="hidden" name="<?php echo $field; ?>" value="<?php echo $e((string) ($d[$field] ?? '')); ?>">
            <?php endforeach; ?>

            <details class="mt-4">
                <summary>Computer details (optional)</summary>
                <div class="row g-3 mt-1">
                    <div class="col-md-4"><label class="form-label">Computer Name</label><input name="computer_name" class="form-control" value="<?php echo $e($d['computer_name'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Processor</label><input name="processor" class="form-control" value="<?php echo $e($d['processor'] ?? ''); ?>"></div>
                    <div class="col-md-4"><label class="form-label">RAM</label><input name="ram" class="form-control" value="<?php echo $e($d['ram'] ?? ''); ?>"></div>
                </div>
            </details>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg"><?php echo $isEdit ? 'Save Changes' : 'Save Asset'; ?></button>
                <a href="<?php echo APP_URL; ?>/devices/list" class="btn btn-outline-secondary btn-lg">Cancel</a>
                <a href="<?php echo APP_URL; ?>/devices/list" class="btn btn-link btn-lg">Back</a>
            </div>
        </div>
    </form>
</div>
