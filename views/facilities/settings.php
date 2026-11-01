<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header"><h5 class="fw-bold mb-0">Maintenance settings</h5></div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p class="fac-help">Choose who receives each category. Leave a person blank to use the head of the matching department, or the Facility Officer when no head is found.</p>
            <form method="post" action="<?php echo $base; ?>/settings/save">
                <div class="fac-section">
                    <h6>Category routing</h6>
                    <div class="fac-table-wrap">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Category</th><th>Routes to</th><th>Specific officer</th></tr></thead>
                            <tbody>
                            <?php foreach ($routes as $route): ?>
                                <tr>
                                    <td><?php echo $h($route['category_name']); ?></td>
                                    <td><?php echo $h($labels['routes'][$route['route_key']] ?? $route['route_key']); ?></td>
                                    <td>
                                        <select class="form-select form-select-sm" name="route_staff[<?php echo (int) $route['category_id']; ?>]">
                                            <option value="">Automatic</option>
                                            <?php foreach ($staff_options as $staff): ?>
                                                <option value="<?php echo $h($staff['staff_id']); ?>" <?php echo ((string) ($route['staff_id'] ?? '') === (string) $staff['staff_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $h($staff['staff_name'] . ' (' . $staff['staff_id'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="fac-section">
                    <h6>Expected completion (days)</h6>
                    <div class="row g-2">
                        <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $key => $label): ?>
                        <div class="col-md-3">
                            <label class="form-label"><?php echo $h($label); ?></label>
                            <input class="form-control form-control-sm" type="number" min="1" max="60" name="sla_<?php echo $h($key); ?>" value="<?php echo (int) ($sla[$key] ?? 7); ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="fac-section">
                    <h6>Escalation</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Hours before an unassigned request is escalated</label>
                            <input class="form-control form-control-sm" type="number" min="1" name="unassigned_hours" value="<?php echo (int) $settings['unassigned_hours']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Extra days overdue before the Facility Officer is notified</label>
                            <input class="form-control form-control-sm" type="number" min="1" name="facility_escalate_days" value="<?php echo (int) $settings['facility_escalate_days']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Extra days overdue before Director Administration is notified</label>
                            <input class="form-control form-control-sm" type="number" min="1" name="director_escalate_days" value="<?php echo (int) $settings['director_escalate_days']; ?>">
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Save settings</button>
            </form>
        </div>
    </div>
</div>
