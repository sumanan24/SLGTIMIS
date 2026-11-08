<?php
/**
 * Dashboard section: working staff by employment type, by category (position) and by department.
 * Uses $h, $n, $jsonAttr, $palette from views/dashboard/index.php.
 */
$staffTypes = $staffBreakdown['types'];
$staffTotals = $staffBreakdown['totals'];
$staffTabs = [
    'category' => ['label' => 'By category', 'icon' => 'fa-id-badge', 'first' => 'Category', 'rows' => $staffBreakdown['by_category']],
    'department' => ['label' => 'By department', 'icon' => 'fa-building', 'first' => 'Department', 'rows' => $staffBreakdown['by_department']],
];
$typeColorMap = ['Permanent' => '#2563eb', 'On Contract' => '#f59e0b', 'Visiting Lecturer' => '#7c3aed'];
$typeColors = [];
foreach ($staffTypes as $i => $type) {
    $typeColors[$type] = $typeColorMap[$type] ?? $palette[($i + 3) % count($palette)];
}
$typeLabel = function ($type) {
    $short = ['On Contract' => 'Contract', 'Visiting Lecturer' => 'Visiting'];
    return $short[$type] ?? $type;
};
$typeIcon = function ($type) {
    $icons = ['Permanent' => 'fa-user-check', 'On Contract' => 'fa-file-signature', 'Visiting Lecturer' => 'fa-person-chalkboard'];
    return $icons[$type] ?? 'fa-user';
};
$staffValues = [];
foreach ($staffTypes as $type) {
    $staffValues[] = (int) ($staffTotals[$type] ?? 0);
}
$dash = '<span class="dh-muted">–</span>';
?>
<section class="dh-section tone-violet" id="sec-staff">
    <div class="dh-sec-head">
        <span class="dh-sec-icon"><i class="fas fa-chalkboard-user"></i></span>
        <div>
            <h2>Staff</h2>
            <p>Working staff by category and employment type</p>
        </div>
        <a class="dh-link" href="<?php echo APP_URL; ?>/staff">Open staff list <i class="fas fa-arrow-right ms-1"></i></a>
    </div>

    <div class="dh-grid-2 wide-left">
        <div class="dh-card">
            <div class="dh-card-head"><h3>Employment type</h3><span><?php echo $n($staffTotals['all'] ?? 0); ?> working staff</span></div>
            <div class="dh-card-body">
                <div class="dh-donut-wrap">
                    <div class="dh-chart" style="height:210px"><canvas role="img" aria-label="Staff by employment type" data-chart="<?php echo $jsonAttr(['type' => 'doughnut', 'labels' => array_map($typeLabel, $staffTypes), 'values' => $staffValues, 'colors' => array_values($typeColors)]); ?>"></canvas></div>
                    <ul class="dh-legend">
                        <?php foreach ($staffTypes as $type): ?>
                            <li>
                                <span class="dot" style="background:<?php echo $h($typeColors[$type]); ?>"></span>
                                <span class="name"><i class="fas <?php echo $typeIcon($type); ?> me-1" style="color:<?php echo $h($typeColors[$type]); ?>"></i><?php echo $h($typeLabel($type)); ?></span>
                                <span class="val"><?php echo $n($staffTotals[$type] ?? 0); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="dh-card">
            <div class="dh-tabs" role="tablist">
                <?php $first = true; foreach ($staffTabs as $key => $tab): ?>
                    <button type="button" role="tab" class="<?php echo $first ? 'active' : ''; ?>" id="staffTab-<?php echo $key; ?>" data-staff-tab="<?php echo $key; ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" aria-controls="staffPane-<?php echo $key; ?>"><i class="fas <?php echo $tab['icon']; ?> me-1"></i><?php echo $h($tab['label']); ?></button>
                <?php $first = false; endforeach; ?>
            </div>
            <?php $first = true; foreach ($staffTabs as $key => $tab): ?>
                <div id="staffPane-<?php echo $key; ?>" role="tabpanel" aria-labelledby="staffTab-<?php echo $key; ?>" <?php echo $first ? '' : 'hidden'; ?>>
                    <?php if (empty($tab['rows'])): ?>
                        <p class="dh-empty"><i class="fas fa-users"></i>No working staff found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="dh-table">
                                <thead>
                                    <tr>
                                        <th><?php echo $h($tab['first']); ?></th>
                                        <?php foreach ($staffTypes as $type): ?>
                                            <th class="num" title="<?php echo $h($type); ?>"><span class="th-dot" style="background:<?php echo $h($typeColors[$type]); ?>"></span><?php echo $h($typeLabel($type)); ?></th>
                                        <?php endforeach; ?>
                                        <th class="num">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tab['rows'] as $row): ?>
                                        <tr>
                                            <td><?php echo $h($row['label']); ?></td>
                                            <?php foreach ($staffTypes as $type): ?>
                                                <?php $c = (int) ($row['counts'][$type] ?? 0); ?>
                                                <td class="num"><?php echo $c > 0 ? $n($c) : $dash; ?></td>
                                            <?php endforeach; ?>
                                            <td class="num"><span class="dh-pill tone-violet"><?php echo $n($row['total']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <?php foreach ($staffTypes as $type): ?>
                                            <td class="num"><?php echo $n($staffTotals[$type] ?? 0); ?></td>
                                        <?php endforeach; ?>
                                        <td class="num"><?php echo $n($staffTotals['all'] ?? 0); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php $first = false; endforeach; ?>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.querySelectorAll('[data-staff-tab]');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                document.getElementById('staffPane-' + t.getAttribute('data-staff-tab')).hidden = !on;
            });
        });
    });
});
</script>
