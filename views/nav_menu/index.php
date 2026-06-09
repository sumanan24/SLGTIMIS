<?php
/** @var array $items */
/** @var bool $dynamicEnabled */
/** @var int $activeCount */
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-bold"><i class="fas fa-bars me-2"></i>Navbar Access Settings</h5>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo APP_URL; ?>/admin/nav-menu/create" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i>Add Option
                </a>
                <a href="<?php echo APP_URL; ?>/admin/nav-menu/staff-privileges" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-user-shield me-1"></i>Staff privileges
                </a>
                <a href="<?php echo APP_URL; ?>/admin/nav-menu/assignments" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-users me-1"></i>All assignments
                </a>
                <a href="<?php echo APP_URL; ?>/admin/nav-menu?seed=1" class="btn btn-outline-light btn-sm" onclick="return confirm('Import default menu items only if the list is empty. Continue?');">
                    <i class="fas fa-download me-1"></i>Seed defaults
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo $h($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo $h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo APP_URL; ?>/admin/nav-menu" class="card border mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Use database navbar for staff</h6>
                    <p class="text-muted small mb-3">When enabled, the sidebar is built from the options below. Each staff member only sees menu items assigned to them by name (approved). When disabled, the built-in system menu is used.</p>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="dynamic_enabled" value="1" id="dynamicEnabled" <?php echo $dynamicEnabled ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="dynamicEnabled">Enable dynamic navbar (<?php echo (int) $activeCount; ?> active items)</label>
                    </div>
                    <input type="hidden" name="action" value="toggle_dynamic">
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Save mode</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Label</th>
                            <th>Route</th>
                            <th>Parent</th>
                            <th>Staff assigned</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No navbar options yet. Add one or seed defaults.</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $row): ?>
                                <tr class="<?php echo empty($row['is_active']) ? 'table-secondary' : ''; ?>">
                                    <td><?php echo (int) $row['sort_order']; ?></td>
                                    <td>
                                        <?php if (!empty($row['parent_id'])): ?><span class="text-muted">â†³</span> <?php endif; ?>
                                        <i class="<?php echo $h($row['icon_class']); ?> me-1 text-primary"></i>
                                        <?php echo $h($row['label']); ?>
                                        <?php if (!empty($row['is_divider'])): ?><span class="badge bg-secondary">Divider</span><?php endif; ?>
                                        <?php if (!empty($row['require_adm'])): ?><span class="badge bg-dark">ADM</span><?php endif; ?>
                                        <?php if (!empty($row['hide_for_sao'])): ?><span class="badge bg-warning text-dark">Hide SAO</span><?php endif; ?>
                                    </td>
                                    <td><code class="small"><?php echo $row['route_path'] !== '' ? $h($row['route_path']) : 'â€”'; ?></code></td>
                                    <td class="small"><?php echo $h($row['parent_label'] ?? 'â€”'); ?></td>
                                    <td class="small">
                                        <?php
                                        $approved = (int) ($row['approved_staff_count'] ?? 0);
                                        $total = (int) ($row['assignment_count'] ?? 0);
                                        echo $approved . ' approved';
                                        if ($total > $approved) {
                                            echo ' <span class="text-muted">(' . ($total - $approved) . ' pending)</span>';
                                        }
                                        if ($total === 0) {
                                            echo ' <span class="text-warning">— assign staff</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['is_active'])): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="<?php echo APP_URL; ?>/admin/nav-menu/edit?id=<?php echo (int) $row['nav_id']; ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/admin/nav-menu/delete?id=<?php echo (int) $row['nav_id']; ?>" class="btn btn-outline-danger btn-sm" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

