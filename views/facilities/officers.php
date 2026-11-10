<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <?php $facHeader('Assign Officers', 'Choose who receives each ticket category.'); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p class="fac-help">Choose who receives each ticket category. Leave a person on Automatic to use the head of the matching department, or the Facility Officer when no head is found.</p>
            <form method="post" action="<?php echo $base; ?>/officers/save">
                <div class="fac-section">
                    <h6>Category routing</h6>
                    <div class="fac-table-wrap">
                        <table class="table table-sm mb-0 fac-officers-table">
                            <thead><tr><th>Category</th><th>Routes to</th><th>Specific officer</th></tr></thead>
                            <tbody>
                            <?php foreach ($routes as $route): ?>
                                <tr>
                                    <td data-label="Category" class="fac-officers-cat"><?php echo $h($route['category_name']); ?></td>
                                    <td data-label="Routes to"><?php echo $h($labels['routes'][$route['route_key']] ?? $route['route_key']); ?></td>
                                    <td data-label="Specific officer">
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
                <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>Save assignments</button>
            </form>
        </div>
    </div>
</div>