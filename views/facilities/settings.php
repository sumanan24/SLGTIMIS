<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader('Ticket Settings', 'Completion time for each priority and escalation rules.'); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p class="fac-help">Set how long each priority is given to finish, and when unassigned or overdue tickets are escalated. To choose who receives each category, use <a href="<?php echo $base; ?>/officers">Assign Officers</a>.</p>
            <form method="post" action="<?php echo $base; ?>/settings/save">
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
