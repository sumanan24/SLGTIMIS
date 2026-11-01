<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$base = APP_URL . '/services';
$nav = [
    'dashboard' => ['Dashboard', $base . '/dashboard', 'fa-chart-pie'],
    'security' => ['Security', $base . '/area?area=security', 'fa-shield-alt'],
    'ac' => ['Air Conditioning', $base . '/area?area=ac', 'fa-snowflake'],
    'cleaning' => ['Cleaning', $base . '/area?area=cleaning', 'fa-broom'],
    'canteen' => ['Canteen', $base . '/area?area=canteen', 'fa-utensils'],
    'contracts' => ['Contracts', $base . '/area?area=contracts', 'fa-file-contract'],
    'reports' => ['Reports', $base . '/reports', 'fa-file-alt'],
    'notifications' => ['Reminders', $base . '/notifications', 'fa-bell'],
];
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/services.css?v=<?php echo time(); ?>">
<div class="container-fluid px-0 svc-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center gap-3">
            <div>
                <p class="svc-kicker mb-0">Service providers</p>
                <h5 class="fw-bold mb-0"><?php echo $h($title ?? 'Services'); ?></h5>
            </div>
            <a class="btn btn-sm btn-light" href="<?php echo $base; ?>/dashboard">Overview</a>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $h($message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $h($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <nav class="svc-nav" aria-label="Service sections">
                <?php foreach ($nav as $key => $item):
                    if ($key !== 'dashboard' && $key !== 'reports' && $key !== 'notifications' && !in_array($key, $actor['areas'] ?? [], true)) {
                        continue;
                    } ?>
                    <a class="<?php echo (isset($section) && $section === $key) ? 'is-on' : ''; ?>" href="<?php echo $h($item[1]); ?>"><i class="fas <?php echo $h($item[2]); ?>"></i><?php echo $h($item[0]); ?></a>
                <?php endforeach; ?>
            </nav>
