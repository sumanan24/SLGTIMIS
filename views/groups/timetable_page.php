<?php
$group = $group ?? [];
$e = static function ($s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$app = rtrim((string) APP_URL, '/');
$verLabel = GroupModel::versionLabel($group['course_version'] ?? 0);
?>
<div class="tt-page">
    <div class="tt-top">
        <div class="min-w-0">
            <h1><?php echo $e($group['name'] ?? 'Group'); ?></h1>
            <p>
                Timetable
                · <?php echo $e($group['course_name'] ?? 'N/A'); ?>
                · <?php echo $e($verLabel); ?>
                · <?php echo $e($group['academic_year'] ?? 'N/A'); ?>
            </p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($app . '/groups/show?id=' . urlencode((string) ($group['id'] ?? ''))); ?>">
            <i class="fas fa-arrow-left me-1"></i>Back to group
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $e($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php require BASE_PATH . '/views/groups/timetable.php'; ?>
</div>
