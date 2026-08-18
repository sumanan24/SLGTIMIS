<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$d = $device ?? [];
$id = (int) ($d['id'] ?? 0);
?>
<div class="container-fluid px-3 px-md-4 devices-page-wrap">
    <?php require BASE_PATH . '/views/devices/_styles.php'; ?>
    <h1 class="h4 mb-3">Assign Device — <?php echo $e($d['asset_id'] ?? ''); ?></h1>
    <?php require BASE_PATH . '/views/devices/_nav.php'; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <form method="post" action="<?php echo APP_URL; ?>/devices/assign-save" class="card border-0 shadow-sm" style="max-width:640px;">
        <div class="card-body p-4">
            <input type="hidden" name="device_id" value="<?php echo $id; ?>">
            <div class="mb-3">
                <label class="form-label">Employee / Lecturer <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Select staff…</option>
                    <?php foreach ($staffList ?? [] as $s): ?>
                    <option value="<?php echo $e($s['staff_id'] ?? ''); ?>"><?php echo $e(($s['staff_name'] ?? '') . ' — ' . ($s['staff_id'] ?? '') . ' (' . ($s['department_name'] ?? '') . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Issue Date <span class="text-danger">*</span></label><input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
            <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Assign</button>
                <a href="<?php echo APP_URL; ?>/devices/view?id=<?php echo $id; ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
