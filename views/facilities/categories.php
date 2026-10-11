<?php require BASE_PATH . '/views/facilities/partials/helpers.php'; ?>
<div class="container-fluid px-0 fac-page">
    <div class="card border-0">
        <div class="card-header"><h5 class="fw-bold mb-0">Categories</h5></div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>
            <p class="fac-help">Add a category or a subcategory. These appear on the new-ticket form.</p>
            <form method="post" action="<?php echo $base; ?>/categories/save" class="fac-section">
                <input type="hidden" name="kind" value="category">
                <h6>New category</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <input class="form-control" name="name" placeholder="e.g. Safety" required style="max-width:280px">
                    <button class="btn btn-primary">Add</button>
                </div>
            </form>
            <form method="post" action="<?php echo $base; ?>/categories/save" class="fac-section">
                <input type="hidden" name="kind" value="subcategory">
                <h6>New subcategory</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <select class="form-select" name="category_id" required style="max-width:220px">
                        <?php foreach ($categories as $cat): ?><option value="<?php echo (int) $cat['id']; ?>"><?php echo $h($cat['name']); ?></option><?php endforeach; ?>
                    </select>
                    <input class="form-control" name="name" placeholder="e.g. Fire extinguisher" required style="max-width:260px">
                    <button class="btn btn-outline-primary">Add</button>
                </div>
            </form>
            <div class="fac-table-wrap">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Category</th><th>Subcategories</th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?php echo $h($cat['name']); ?></td>
                            <td>
                                <?php foreach ($subcategories as $sub): if ((int) $sub['category_id'] === (int) $cat['id']): ?>
                                    <span class="fac-badge fac-s-new"><?php echo $h($sub['name']); ?></span>
                                <?php endif; endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
