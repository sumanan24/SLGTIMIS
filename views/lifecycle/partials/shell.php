<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$base = APP_URL . '/lifecycle';
$nav = [
    'dashboard' => ['Dashboard', $base . '/dashboard'],
    'students' => ['Students', $base . '/students'],
    'records' => ['Registers', $base . '/records'],
    'reports' => ['Reports', $base . '/reports'],
    'import' => ['Historical import', $base . '/import'],
];
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/lifecycle.css?v=<?php echo time(); ?>">
<div class="container-fluid px-0 lc-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center gap-3">
            <div>
                <p class="lc-kicker mb-0">Student lifecycle</p>
                <h5 class="fw-bold mb-0"><?php echo $h($title ?? 'Students'); ?></h5>
            </div>
            <a class="btn btn-sm btn-light" href="<?php echo $base; ?>/dashboard">Overview</a>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $h($message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if (!empty($error)): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $h($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <nav class="lc-nav" aria-label="Lifecycle sections">
                <?php foreach ($nav as $key => $item):
                    if ($key === 'import' && empty($actor['office'])) {
                        continue;
                    } ?>
                    <a class="<?php echo (isset($section) && $section === $key) ? 'is-on' : ''; ?>" href="<?php echo $h($item[1]); ?>"><?php echo $h($item[0]); ?></a>
                <?php endforeach; ?>
                <a href="<?php echo APP_URL; ?>/verify/certificate">Certificate check</a>
            </nav>
