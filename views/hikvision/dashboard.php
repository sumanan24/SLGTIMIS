<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $cards */
/** @var string|null $flash_success */
/** @var string|null $flash_error */
/** @var string $test_all_url */
/** @var string $main_ip */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$statusClass = static function (string $status): string {
    $s = strtoupper($status);
    if ($s === 'ONLINE') {
        return 'hk-on';
    }
    if ($s === 'AUTH ERROR') {
        return 'hk-auth';
    }
    if ($s === 'TIMEOUT') {
        return 'hk-timeout';
    }
    if ($s === 'UNKNOWN') {
        return 'hk-unknown';
    }
    return 'hk-off';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $e($title ?? 'Hikvision LAN'); ?></title>
    <link rel="stylesheet" href="<?php echo $e(rtrim(APP_URL, '/') . '/assets/css/bootstrap.min.css'); ?>">
    <style>
        body { background: #f4f6f8; font-family: Segoe UI, system-ui, sans-serif; }
        .hk-wrap { max-width: 1100px; margin: 1.5rem auto; padding: 0 1rem; }
        .hk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }
        .hk-card { background: #fff; border: 1px solid #dde3ea; border-radius: 10px; padding: 1rem 1.1rem; }
        .hk-card h2 { font-size: 1rem; margin: 0 0 .25rem; letter-spacing: .02em; }
        .hk-ip { font-family: ui-monospace, Consolas, monospace; color: #334; }
        .hk-badge { display: inline-block; font-size: .75rem; font-weight: 700; padding: .2rem .55rem; border-radius: 999px; }
        .hk-on { background: #dcfce7; color: #166534; }
        .hk-off { background: #fee2e2; color: #991b1b; }
        .hk-auth { background: #fef3c7; color: #92400e; }
        .hk-timeout { background: #e0e7ff; color: #3730a3; }
        .hk-unknown { background: #e5e7eb; color: #374151; }
        .hk-meta { font-size: .8rem; color: #64748b; margin-top: .55rem; line-height: 1.45; }
        .hk-err { color: #b91c1c; font-size: .8rem; margin-top: .4rem; word-break: break-word; }
        .hk-actions { margin-top: .75rem; }
        .hk-diag { font-size: .75rem; color: #64748b; margin-top: .35rem; }
    </style>
</head>
<body>
<div class="hk-wrap">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Hikvision LAN Devices</h1>
            <div class="text-muted small">LAN only · Main <?php echo $e($main_ip ?? ''); ?> · no Internet checks</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $e(rtrim(APP_URL, '/') . '/attendance/student-device'); ?>">Student attendance</a>
            <a class="btn btn-sm btn-primary" href="<?php echo $e($test_all_url ?? '#'); ?>">Test all</a>
        </div>
    </div>

    <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success"><?php echo $e($flash_success); unset($_SESSION['hikvision_flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger"><?php echo $e($flash_error); unset($_SESSION['hikvision_flash_error']); ?></div>
    <?php endif; ?>

    <div class="alert alert-light border small mb-3">
        Tests run from the <strong>SIS PHP server</strong>, not your browser PC.
        If your PC can ping readers but this page shows OFFLINE, fix routing:
        SIS host → <code>172.16.0.26–29</code> TCP/80.
    </div>

    <div class="hk-grid">
        <?php foreach (($cards ?? []) as $c): ?>
            <?php
            $st = (string) ($c['status'] ?? 'UNKNOWN');
            $cls = $statusClass($st);
            ?>
            <article class="hk-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h2><?php echo $e($c['label'] ?? ''); ?></h2>
                        <div class="hk-ip"><?php echo $e($c['ip'] ?? ''); ?></div>
                    </div>
                    <span class="hk-badge <?php echo $e($cls); ?>"><?php echo $e($st); ?></span>
                </div>
                <div class="hk-meta">
                    <div>Device: <?php echo $e(($c['device_name'] ?? '') !== '' ? $c['device_name'] : '—'); ?></div>
                    <div>Last seen: <?php echo $e($c['last_seen'] ?? '—'); ?></div>
                    <div>Checked: <?php echo $e($c['checked_at'] ?? '—'); ?></div>
                </div>
                <div class="hk-diag">
                    Ping <?php echo !empty($c['ping_ok']) ? 'OK' : '—'; ?>
                    · TCP <?php echo !empty($c['tcp_ok']) ? 'OK' : '—'; ?>
                    · HTTP <?php echo !empty($c['http_ok']) ? 'OK' : '—'; ?>
                    · Auth <?php echo !empty($c['auth_ok']) ? 'OK' : '—'; ?>
                </div>
                <?php if (!empty($c['last_error'])): ?>
                    <div class="hk-err"><?php echo $e($c['last_error']); ?></div>
                <?php endif; ?>
                <div class="hk-actions">
                    <a class="btn btn-sm btn-outline-primary w-100" href="<?php echo $e($c['test_url'] ?? '#'); ?>">Test connection</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
