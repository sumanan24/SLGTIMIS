<?php
/** @var array|null $item */
/** @var array $parents */
/** @var string $formAction */
/** @var array|null $staffList */
/** @var array|null $assignments */
/** @var array|null $assignedStaffIds */
$isEdit = !empty($item);
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pageKeysStr = implode(', ', $item['page_keys'] ?? []);
$staffList = $staffList ?? [];
$assignments = $assignments ?? [];
$assignedStaffIds = $assignedStaffIds ?? [];
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-bold"><i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus'; ?> me-2"></i><?php echo $isEdit ? 'Edit' : 'Add'; ?> Navbar Option</h5>
            <a href="<?php echo APP_URL; ?>/admin/nav-menu/staff-privileges" class="btn btn-light btn-sm">
                <i class="fas fa-user-shield me-1"></i>Staff privileges
            </a>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo $h($formAction); ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="label">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="label" name="label" required value="<?php echo $h($item['label'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="parent_id">Parent menu (optional)</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">— Top level —</option>
                            <?php foreach ($parents as $p): ?>
                                <option value="<?php echo (int) $p['nav_id']; ?>" <?php echo (isset($item['parent_id']) && (int) $item['parent_id'] === (int) $p['nav_id']) ? 'selected' : ''; ?>>
                                    <?php echo $h($p['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Leave route empty on parent items to create a submenu group.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="route_path">Route path</label>
                        <input type="text" class="form-control" id="route_path" name="route_path" placeholder="e.g. departments" value="<?php echo $h($item['route_path'] ?? ''); ?>">
                        <div class="form-text">Relative path without leading slash. Empty for parent-only items.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="icon_class">Icon class</label>
                        <input type="text" class="form-control" id="icon_class" name="icon_class" value="<?php echo $h($item['icon_class'] ?? 'fas fa-circle'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="page_key">Page key (active)</label>
                        <input type="text" class="form-control" id="page_key" name="page_key" value="<?php echo $h($item['page_key'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="page_keys">Page keys (parent active)</label>
                        <input type="text" class="form-control" id="page_keys" name="page_keys" placeholder="departments, courses" value="<?php echo $h($pageKeysStr); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="sort_order">Sort order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo (int) ($item['sort_order'] ?? 0); ?>">
                    </div>
                </div>

                <?php include BASE_PATH . '/views/nav_menu/partials/staff_assign.php'; ?>

                <hr class="my-4">
                <h6 class="fw-bold">Options</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?php echo (!$isEdit || !empty($item['is_active'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_divider" value="1" id="is_divider" <?php echo !empty($item['is_divider']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_divider">Divider line</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="hide_for_sao" value="1" id="hide_for_sao" <?php echo !empty($item['hide_for_sao']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hide_for_sao">Hide for SAO</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="require_adm" value="1" id="require_adm" <?php echo !empty($item['require_adm']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="require_adm">ADM only</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="require_admin" value="1" id="require_admin" <?php echo !empty($item['require_admin']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="require_admin">System admin only</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                    <a href="<?php echo APP_URL; ?>/admin/nav-menu" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
