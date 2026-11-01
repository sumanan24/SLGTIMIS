<?php require __DIR__ . '/partials/shell.php';
require_once BASE_PATH . '/models/LifecycleModel.php';
$qs = function ($page) use ($filters) {
    $q = $filters;
    $q['page'] = $page;
    return APP_URL . '/lifecycle/students?' . http_build_query($q);
};
?>
<form class="lc-filters" method="get" action="<?php echo APP_URL; ?>/lifecycle/students">
    <?php $showSearch = true; $showOrigin = true; require __DIR__ . '/partials/filter_fields.php'; ?>
    <div class="lc-filter-actions">
        <button class="btn btn-primary" type="submit">Search</button>
    </div>
</form>
<div class="lc-table-wrap table-responsive">
    <table class="table lc-table align-middle">
        <thead><tr><th>Registration</th><th>Name</th><th>NIC</th><th>Status</th><th>Course</th><th>Year</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($result['rows'])): ?>
            <tr><td colspan="7">No students match this search.</td></tr>
        <?php else: foreach ($result['rows'] as $row): ?>
            <tr>
                <td><?php echo $h($row['student_id']); ?><?php if (($row['record_origin'] ?? '') === 'historical'): ?> <span class="lc-badge warn">Historical</span><?php endif; ?></td>
                <td><?php echo $h($row['student_fullname']); ?></td>
                <td><?php echo $h($row['student_nic']); ?></td>
                <td><span class="lc-badge"><?php echo $h(LifecycleModel::label($row)); ?></span></td>
                <td><?php echo $h($row['course_name'] ?? ''); ?></td>
                <td><?php echo $h($row['academic_year'] ?? ''); ?></td>
                <td><a href="<?php echo APP_URL; ?>/lifecycle/profile?id=<?php echo urlencode($row['student_id']); ?>">Open</a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php if (($result['pages'] ?? 1) > 1):
    $current = (int) $result['page'];
    $start = max(1, $current - 3);
    $end = min((int) $result['pages'], $current + 3);
?>
    <nav class="lc-nav">
        <?php if ($start > 1): ?><a href="<?php echo $h($qs(1)); ?>">1</a><?php endif; ?>
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <a class="<?php echo $i === $current ? 'is-on' : ''; ?>" href="<?php echo $h($qs($i)); ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($end < (int) $result['pages']): ?><a href="<?php echo $h($qs($result['pages'])); ?>"><?php echo (int) $result['pages']; ?></a><?php endif; ?>
    </nav>
<?php endif; ?>
<?php require __DIR__ . '/partials/close.php'; ?>
