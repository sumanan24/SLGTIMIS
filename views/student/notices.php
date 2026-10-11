<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$notices = is_array($notices ?? null) ? $notices : [];
$code = is_array($codeOfConduct ?? null) ? $codeOfConduct : [];
$rules = is_array($commonRules ?? null) ? $commonRules : [];
$tab = strtolower(trim((string) ($_GET['tab'] ?? 'notices')));
if (!in_array($tab, ['notices', 'conduct', 'rules'], true)) {
    $tab = 'notices';
}
?>
<style>
.sn-page { max-width: 960px; }
.sn-hero {
    background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
    color: #fff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
}
.sn-card { background: #fff; border: 1px solid #e6eaf0; border-radius: 12px; }
.sn-nav { flex-wrap: wrap; }
.sn-nav .nav-link { color: #001f3f; font-weight: 600; font-size: .875rem; }
.sn-nav .nav-link.active { background: #001f3f; color: #fff; }
@media (max-width: 576px) {
    .sn-hero { padding: 1rem; }
    .sn-hero h1 { font-size: 1.1rem; }
    .sn-nav { display: grid; grid-template-columns: 1fr; gap: .4rem; }
    .sn-nav .nav-item, .sn-nav .nav-link { width: 100%; text-align: center; }
    .sn-nav .nav-link { padding: .55rem .7rem; }
}
.sn-notice { border-bottom: 1px solid #eef1f4; padding: 1rem 0; }
.sn-notice:last-child { border-bottom: 0; }
</style>
<div class="sn-page">
    <div class="sn-hero mb-3">
        <div class="small opacity-75">Sri Lanka–German Training Institute</div>
        <h1 class="h4 mb-1">Notices, Code of Conduct &amp; Common Rules</h1>
        <p class="mb-0 small opacity-75">One place for incoming institute notices and the rules every student must follow.</p>
    </div>

    <ul class="nav nav-pills sn-nav gap-2 mb-3">
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'notices' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/student/notices?tab=notices">Coming notices</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'conduct' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/student/notices?tab=conduct">Code of conduct</a></li>
        <li class="nav-item"><a class="nav-link <?php echo $tab === 'rules' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/student/notices?tab=rules">Common rules</a></li>
    </ul>

    <div class="sn-card p-3 p-md-4 mb-4">
        <?php if ($tab === 'notices'): ?>
            <h2 class="h5 mb-3">SLGTI coming notices</h2>
            <?php if ($notices === []): ?>
                <p class="text-muted mb-0">No notices at this time.</p>
            <?php else: ?>
                <?php foreach ($notices as $n): ?>
                    <article class="sn-notice">
                        <div class="small text-muted"><?php echo $e($n['date'] ?? ''); ?></div>
                        <h3 class="h6 mb-1"><?php echo $e($n['title'] ?? ''); ?></h3>
                        <p class="mb-0"><?php echo $e($n['body'] ?? ''); ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php elseif ($tab === 'conduct'): ?>
            <h2 class="h5 mb-2"><?php echo $e($code['title'] ?? 'Student Code of Conduct'); ?></h2>
            <p><?php echo $e($code['intro'] ?? ''); ?></p>
            <?php if (!empty($code['points']) && is_array($code['points'])): ?>
                <ul>
                    <?php foreach ($code['points'] as $p): ?>
                        <li class="mb-1"><?php echo $e($p); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="alert alert-secondary small mb-0"><?php echo $e($code['declaration'] ?? ''); ?></div>
        <?php else: ?>
            <h2 class="h5 mb-2"><?php echo $e($rules['title'] ?? 'Common rules of SLGTI'); ?></h2>
            <p><?php echo $e($rules['intro'] ?? ''); ?></p>
            <?php foreach (($rules['sections'] ?? []) as $sec): ?>
                <h3 class="h6 mt-3"><?php echo $e($sec['heading'] ?? ''); ?></h3>
                <ul>
                    <?php foreach (($sec['items'] ?? []) as $item): ?>
                        <li class="mb-1"><?php echo $e($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <a href="<?php echo APP_URL; ?>/student/dashboard" class="small">Back to dashboard</a>
</div>
