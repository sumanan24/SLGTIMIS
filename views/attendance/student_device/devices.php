<?php
declare(strict_types=1);
/** @var array $urls */
/** @var list<array<string,mixed>> $deviceRows */
/** @var list<array<string,mixed>> $jobs */
/** @var list<array<string,mixed>> $logs */
/** @var bool $curlMissing */
/** @var string $mainHost */
/** @var string $deviceTab */
/** @var array $kpi */
/** @var string $testedAt */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$studentDeviceSection = 'devices';
$pageTitle = 'Device sync';
$pageSubtitle = 'MAIN enrolls once · readers receive the same Employee No';
$deviceTab = (string) ($deviceTab ?? 'overview');
$deviceRows = $deviceRows ?? [];
$jobs = $jobs ?? [];
$logs = $logs ?? [];
$curlMissing = !empty($curlMissing);
$mainHost = (string) ($mainHost ?? '');
$kpi = $kpi ?? ['devices' => 0, 'online' => 0, 'missing' => 0, 'pending' => 0, 'failed' => 0];
$testedAt = (string) ($testedAt ?? '');

$presenceRows = $presenceRows ?? [];
$presenceHosts = $presenceHosts ?? [];
$presenceDeviceMeta = $presenceDeviceMeta ?? [];
$presenceFilter = (string) ($presenceFilter ?? 'missing');
$presenceSummary = $presenceSummary ?? ['total' => 0, 'missing' => 0, 'complete' => 0];
$presenceQ = (string) ($presenceQ ?? '');
$presencePage = max(1, (int) ($presencePage ?? 1));
$presencePages = max(1, (int) ($presencePages ?? 1));
$presenceTotal = (int) ($presenceTotal ?? 0);

$queueStatus = (string) ($queueStatus ?? 'all');
$queueTotal = (int) ($queueTotal ?? 0);
$logsPage = max(1, (int) ($logsPage ?? 1));
$logsPages = max(1, (int) ($logsPages ?? 1));
$logsTotal = (int) ($logsTotal ?? 0);

$devicesAction = (string) ($urls['devices'] ?? '#');
$tabUrl = static function (string $tab, array $extra = []) use ($devicesAction): string {
    $q = array_merge(['tab' => $tab], $extra);
    return $devicesAction . '?' . http_build_query(array_filter($q, static fn ($v) => $v !== null && $v !== ''));
};

$pagerLinks = static function (int $page, int $pages, callable $urlFor) use ($e): void {
    if ($pages <= 1) {
        return;
    }
    echo '<nav class="sd-pager" aria-label="Pagination"><ul class="pagination mb-0">';
    $prev = max(1, $page - 1);
    $next = min($pages, $page + 1);
    echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . $e($urlFor($prev)) . '">Prev</a></li>';
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $e($urlFor($i)) . '">' . $i . '</a></li>';
    }
    echo '<li class="page-item' . ($page >= $pages ? ' disabled' : '') . '"><a class="page-link" href="' . $e($urlFor($next)) . '">Next</a></li>';
    echo '</ul></nav>';
};

$tabs = [
    'overview' => ['label' => 'Overview', 'icon' => 'fa-server', 'badge' => (int) ($kpi['devices'] ?? 0)],
    'presence' => ['label' => 'Persons', 'icon' => 'fa-id-card', 'badge' => (int) ($kpi['missing'] ?? 0)],
    'tools' => ['label' => 'Sync / Delete', 'icon' => 'fa-wrench', 'badge' => null],
    'queue' => ['label' => 'Queue', 'icon' => 'fa-stream', 'badge' => (int) ($kpi['pending'] ?? 0)],
    'logs' => ['label' => 'Logs', 'icon' => 'fa-history', 'badge' => null],
];

ob_start();
?>
<span class="sd-summary-chip"><?php echo (int) ($kpi['online'] ?? 0); ?>/<?php echo (int) ($kpi['devices'] ?? 0); ?> online</span>
<?php if ((int) ($kpi['missing'] ?? 0) > 0): ?>
    <span class="sd-summary-chip sd-summary-chip-muted"><?php echo (int) $kpi['missing']; ?> missing</span>
<?php endif; ?>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<section class="sd-devices-layout">
    <?php if ($curlMissing): ?>
        <div class="alert alert-warning mb-3" role="alert">
            PHP cURL is missing — install <code>php-curl</code> before testing or syncing devices.
        </div>
    <?php endif; ?>

    <nav class="sd-subnav" aria-label="Device sync sections">
        <?php foreach ($tabs as $key => $t): ?>
            <a class="sd-subnav-link <?php echo $deviceTab === $key ? 'is-active' : ''; ?>"
               href="<?php echo $e($tabUrl($key, $key === 'presence' ? ['pf' => $presenceFilter, 'q' => $presenceQ] : ($key === 'queue' ? ['qs' => $queueStatus] : []))); ?>">
                <i class="fas <?php echo $e($t['icon']); ?>"></i>
                <span><?php echo $e($t['label']); ?></span>
                <?php if ($t['badge'] !== null && (int) $t['badge'] > 0): ?>
                    <em class="sd-subnav-badge"><?php echo (int) $t['badge']; ?></em>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($deviceTab === 'overview'): ?>
    <?php
    require_once BASE_PATH . '/config/hikvision.php';
    $passOk = function_exists('hikvision_pass_configured') && hikvision_pass_configured();
    ?>
    <?php if (!$passOk): ?>
        <div class="alert alert-danger mb-3 small">
            <strong>HIKVISION_PASS missing in config/hikvision.php</strong> on this server. Deploy the latest code, then click Test all.
        </div>
    <?php endif; ?>

        <div class="sd-devices-kpi">
            <div class="sd-kpi"><span class="sd-kpi-label">Devices</span><strong><?php echo (int) $kpi['devices']; ?></strong></div>
            <div class="sd-kpi"><span class="sd-kpi-label">Online</span><strong><?php echo (int) $kpi['online']; ?></strong></div>
            <div class="sd-kpi"><span class="sd-kpi-label">Missing persons</span><strong><?php echo (int) $kpi['missing']; ?></strong></div>
            <div class="sd-kpi"><span class="sd-kpi-label">Queue pending</span><strong><?php echo (int) $kpi['pending']; ?></strong></div>
            <div class="sd-kpi"><span class="sd-kpi-label">Queue failed</span><strong><?php echo (int) $kpi['failed']; ?></strong></div>
        </div>

        <div class="sd-devices-toolbar">
            <form method="post" action="<?php echo $e($devicesAction); ?>">
                <input type="hidden" name="return_tab" value="overview">
                <button type="submit" name="action" value="test_all" class="btn btn-primary btn-sm">
                    <i class="fas fa-plug me-1"></i>Test all
                </button>
            </form>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($tabUrl('presence', ['pf' => 'missing'])); ?>">View missing persons</a>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo $e($tabUrl('queue')); ?>">Open queue</a>
            <?php if ($testedAt !== ''): ?>
                <span class="sd-toolbar-meta">Last test: <?php echo $e($testedAt); ?></span>
            <?php endif; ?>
        </div>

        <div class="sd-devices-grid">
            <?php if ($deviceRows === []): ?>
                <div class="sd-devices-empty">No devices configured in <code>.env</code>.</div>
            <?php else: ?>
                <?php foreach ($deviceRows as $row): ?>
                    <?php
                    $online = $row['online'] ?? null;
                    $status = strtolower((string) ($row['status'] ?? ''));
                    if ($status === 'online' || $online === true) {
                        $pill = 'is-on';
                        $label = 'ONLINE';
                    } elseif ($status === 'auth_error') {
                        $pill = 'is-auth';
                        $label = 'AUTH ERROR';
                    } elseif ($status === 'invalid_config') {
                        $pill = 'is-unknown';
                        $label = 'CONFIG';
                    } elseif ($online === false || $status === 'offline') {
                        $pill = 'is-off';
                        $label = 'OFFLINE';
                    } else {
                        $pill = 'is-unknown';
                        $label = 'UNKNOWN';
                    }
                    ?>
                    <article class="sd-dev-card">
                        <div class="sd-dev-card-top">
                            <div>
                                <h3 class="sd-dev-card-title"><?php echo $e($row['label'] ?? ''); ?></h3>
                                <code><?php echo $e($row['host'] ?? ''); ?></code>
                            </div>
                            <span class="sd-dev-status <?php echo $e($pill); ?>"><?php echo $e($label); ?></span>
                        </div>
                        <div class="sd-dev-card-meta">
                            <span><?php echo $e(strtoupper((string) ($row['role'] ?? ''))); ?></span>
                            <span><?php echo (int) ($row['users_on_device'] ?? 0); ?> users</span>
                            <span><?php echo (int) ($row['pending'] ?? 0) + (int) ($row['syncing'] ?? 0); ?> pending</span>
                        </div>
                        <?php if (!empty($row['reason']) && $label !== 'ONLINE'): ?>
                            <p class="sd-dev-card-reason"><strong><?php echo $e($row['reason']); ?></strong>
                                <?php if (!empty($row['category'])): ?>
                                    <span class="text-muted"> · <?php echo $e($row['category']); ?></span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($row['message'])): ?>
                            <p class="sd-dev-card-msg"><?php echo $e($row['message']); ?></p>
                        <?php endif; ?>
                        <div class="sd-dev-card-checks small text-muted">
                            TCP <?php echo !empty($row['tcp_ok']) ? 'OK' : '—'; ?>
                            · HTTP <?php echo !empty($row['http_ok']) ? 'OK' : '—'; ?>
                            · Auth <?php echo !empty($row['auth_ok']) ? 'OK' : '—'; ?>
                        </div>
                        <form method="post" action="<?php echo $e($devicesAction); ?>" class="sd-dev-card-action">
                            <input type="hidden" name="return_tab" value="overview">
                            <input type="hidden" name="device_host" value="<?php echo $e($row['host'] ?? ''); ?>">
                            <button type="submit" name="action" value="test_one" class="btn btn-outline-primary btn-sm">Test connection</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php elseif ($deviceTab === 'presence'): ?>
        <div class="sd-devices-toolbar">
            <form method="get" action="<?php echo $e($devicesAction); ?>" class="sd-devices-filter">
                <input type="hidden" name="tab" value="presence">
                <div class="sd-field">
                    <label class="form-label" for="sdPf">Status</label>
                    <select id="sdPf" name="pf" class="form-select form-select-sm">
                        <option value="missing" <?php echo $presenceFilter === 'missing' ? 'selected' : ''; ?>>Missing on some</option>
                        <option value="complete" <?php echo $presenceFilter === 'complete' ? 'selected' : ''; ?>>On all machines</option>
                        <option value="all" <?php echo $presenceFilter === 'all' ? 'selected' : ''; ?>>All persons</option>
                    </select>
                </div>
                <div class="sd-field sd-field-grow">
                    <label class="form-label" for="sdPq">Search</label>
                    <input type="search" id="sdPq" name="q" class="form-control form-control-sm"
                           value="<?php echo $e($presenceQ); ?>" placeholder="Employee No or name">
                </div>
                <div class="sd-field sd-field-actions">
                    <label class="form-label" aria-hidden="true">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>
            <form method="post" action="<?php echo $e($devicesAction); ?>">
                <input type="hidden" name="return_tab" value="presence">
                <button type="submit" name="action" value="refresh_presence" class="btn btn-outline-success btn-sm"
                        onclick="return confirm('Pull person lists from MAIN + readers now?');">
                    <i class="fas fa-sync-alt me-1"></i>Refresh from machines
                </button>
            </form>
        </div>

        <div class="sd-filter-summary">
            Showing <strong><?php echo (int) $presenceTotal; ?></strong>
            · Missing <?php echo (int) ($presenceSummary['missing'] ?? 0); ?>
            · Complete <?php echo (int) ($presenceSummary['complete'] ?? 0); ?>
            · Page <?php echo (int) $presencePage; ?>/<?php echo (int) $presencePages; ?>
        </div>

        <div class="sd-devices-panel">
            <div class="table-responsive">
                <table class="table table-sm table-hover sd-presence-table mb-0">
                    <thead>
                    <tr>
                        <th>Employee No</th>
                        <th>Name</th>
                        <?php foreach ($presenceHosts as $ph): ?>
                            <?php $meta = $presenceDeviceMeta[$ph] ?? []; ?>
                            <th class="text-center">
                                <div><?php echo $e($meta['label'] ?? $ph); ?></div>
                                <code class="sd-presence-host"><?php echo $e($ph); ?></code>
                            </th>
                        <?php endforeach; ?>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($presenceRows === []): ?>
                        <tr>
                            <td colspan="<?php echo 3 + count($presenceHosts); ?>" class="text-center text-muted py-4">
                                <?php echo (int) ($presenceSummary['total'] ?? 0) === 0
                                    ? 'No cached persons yet. Click Refresh from machines.'
                                    : 'No rows match this filter.'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($presenceRows as $pr): ?>
                            <?php
                            $eno = (string) ($pr['employee_no'] ?? '');
                            $missingHosts = $pr['missing_hosts'] ?? [];
                            ?>
                            <tr>
                                <td><code><?php echo $e($eno); ?></code></td>
                                <td><?php echo $e($pr['name'] ?? '') !== '' ? $e($pr['name']) : '—'; ?></td>
                                <?php foreach ($presenceHosts as $ph): ?>
                                    <?php $yes = !empty($pr['devices'][$ph]['present']); ?>
                                    <td class="text-center">
                                        <span class="sd-presence-pill <?php echo $yes ? 'is-yes' : 'is-no'; ?>">
                                            <?php echo $yes ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-nowrap">
                                    <?php if ($missingHosts !== []): ?>
                                        <form method="post" action="<?php echo $e($devicesAction); ?>" class="d-inline">
                                            <input type="hidden" name="return_tab" value="presence">
                                            <input type="hidden" name="pf" value="<?php echo $e($presenceFilter); ?>">
                                            <input type="hidden" name="q" value="<?php echo $e($presenceQ); ?>">
                                            <input type="hidden" name="employee_no" value="<?php echo $e($eno); ?>">
                                            <button type="submit" name="action" value="sync_user" class="btn btn-outline-primary btn-sm">Sync</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="sd-devices-panel-foot">
                <?php
                $pagerLinks($presencePage, $presencePages, static function (int $p) use ($tabUrl, $presenceFilter, $presenceQ): string {
                    return $tabUrl('presence', ['pf' => $presenceFilter, 'q' => $presenceQ, 'page' => (string) $p]);
                });
                ?>
            </div>
        </div>

    <?php elseif ($deviceTab === 'tools'): ?>
        <?php
        $toolsRows = $toolsRows ?? [];
        $toolsQ = (string) ($toolsQ ?? '');
        $toolsPage = max(1, (int) ($toolsPage ?? 1));
        $toolsPages = max(1, (int) ($toolsPages ?? 1));
        $toolsTotal = (int) ($toolsTotal ?? 0);
        $toolsScope = (string) ($toolsScope ?? 'synced');
        $readerOptions = [];
        foreach ($deviceRows as $row) {
            if (($row['role'] ?? '') === 'reader') {
                $readerOptions[] = $row;
            }
        }
        ?>
        <div class="sd-devices-toolbar">
            <form method="get" action="<?php echo $e($devicesAction); ?>" class="sd-devices-filter">
                <input type="hidden" name="tab" value="tools">
                <div class="sd-field">
                    <label class="form-label" for="sdTs">Show</label>
                    <select id="sdTs" name="ts" class="form-select form-select-sm">
                        <option value="synced" <?php echo $toolsScope === 'synced' ? 'selected' : ''; ?>>On any machine</option>
                        <option value="readers" <?php echo $toolsScope === 'readers' ? 'selected' : ''; ?>>On readers</option>
                        <option value="all" <?php echo $toolsScope === 'all' ? 'selected' : ''; ?>>All cached</option>
                    </select>
                </div>
                <div class="sd-field sd-field-grow">
                    <label class="form-label" for="sdTq">Search</label>
                    <input type="search" id="sdTq" name="tq" class="form-control form-control-sm"
                           value="<?php echo $e($toolsQ); ?>" placeholder="Employee No or name">
                </div>
                <div class="sd-field sd-field-actions">
                    <label class="form-label" aria-hidden="true">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>
            <form method="post" action="<?php echo $e($devicesAction); ?>">
                <input type="hidden" name="return_tab" value="tools">
                <button type="submit" name="action" value="refresh_presence" class="btn btn-outline-success btn-sm"
                        onclick="return confirm('Refresh person lists from all 4 machines?');">
                    <i class="fas fa-sync-alt me-1"></i>Refresh from machines
                </button>
            </form>
        </div>

        <form method="post" action="<?php echo $e($devicesAction); ?>" id="sdToolsDeleteForm"
              onsubmit="return window.sdConfirmToolsDelete && window.sdConfirmToolsDelete();">
            <input type="hidden" name="return_tab" value="tools">
            <input type="hidden" name="tq" value="<?php echo $e($toolsQ); ?>">
            <input type="hidden" name="tpage" value="<?php echo (int) $toolsPage; ?>">
            <input type="hidden" name="action" id="sdToolsAction" value="delete_users_selected">

            <div class="sd-devices-toolbar sd-tools-delete-bar">
                <div class="sd-field">
                    <label class="form-label" for="sdToolsTarget">Delete selected from</label>
                    <select id="sdToolsTarget" name="device_host" class="form-select form-select-sm">
                        <option value="">All readers</option>
                        <?php foreach ($readerOptions as $row): ?>
                            <option value="<?php echo $e($row['host'] ?? ''); ?>">
                                <?php echo $e(($row['label'] ?? 'Reader') . ' — ' . ($row['host'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check sd-tools-main-check">
                    <input class="form-check-input" type="checkbox" value="1" id="sdToolsIncludeMain" name="include_main">
                    <label class="form-check-label" for="sdToolsIncludeMain">Also MAIN</label>
                </div>
                <button type="submit" class="btn btn-outline-danger btn-sm" id="sdToolsDeleteBtn">
                    <i class="fas fa-user-minus me-1"></i>Delete selected
                </button>
                <span class="sd-toolbar-meta"><?php echo (int) $toolsTotal; ?> person(s) · Page <?php echo (int) $toolsPage; ?>/<?php echo (int) $toolsPages; ?></span>
            </div>

            <div class="sd-devices-panel">
                <div class="sd-devices-panel-head">Synced persons on MAIN + readers</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover sd-presence-table mb-0">
                        <thead>
                        <tr>
                            <th class="sd-tools-check-col">
                                <input type="checkbox" id="sdToolsCheckAll" title="Select all on this page" aria-label="Select all">
                            </th>
                            <th>Employee No</th>
                            <th>Name</th>
                            <?php foreach ($presenceHosts as $ph): ?>
                                <?php $meta = $presenceDeviceMeta[$ph] ?? []; ?>
                                <th class="text-center">
                                    <div><?php echo $e($meta['label'] ?? $ph); ?></div>
                                    <code class="sd-presence-host"><?php echo $e($ph); ?></code>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-center">On</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($toolsRows === []): ?>
                            <tr>
                                <td colspan="<?php echo 5 + count($presenceHosts); ?>" class="text-center text-muted py-4">
                                    No synced persons in cache. Click <strong>Refresh from machines</strong> first.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($toolsRows as $pr): ?>
                                <?php
                                $eno = (string) ($pr['employee_no'] ?? '');
                                $presentCount = (int) ($pr['present_count'] ?? 0);
                                $hostCount = max(1, count($presenceHosts));
                                ?>
                                <tr>
                                    <td class="sd-tools-check-col">
                                        <input type="checkbox" class="sd-tools-row-check" name="employee_nos[]"
                                               value="<?php echo $e($eno); ?>" aria-label="Select <?php echo $e($eno); ?>">
                                    </td>
                                    <td><code><?php echo $e($eno); ?></code></td>
                                    <td><?php echo $e($pr['name'] ?? '') !== '' ? $e($pr['name']) : '—'; ?></td>
                                    <?php foreach ($presenceHosts as $ph): ?>
                                        <?php $yes = !empty($pr['devices'][$ph]['present']); ?>
                                        <td class="text-center">
                                            <span class="sd-presence-pill <?php echo $yes ? 'is-yes' : 'is-no'; ?>">
                                                <?php echo $yes ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="text-center"><?php echo $presentCount; ?>/<?php echo $hostCount; ?></td>
                                    <td class="text-nowrap">
                                        <button type="submit"
                                                class="btn btn-outline-danger btn-sm sd-tools-row-del"
                                                data-eno="<?php echo $e($eno); ?>"
                                                formaction="<?php echo $e($devicesAction); ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sd-devices-panel-foot">
                    <?php
                    $pagerLinks($toolsPage, $toolsPages, static function (int $p) use ($tabUrl, $toolsScope, $toolsQ): string {
                        return $tabUrl('tools', ['ts' => $toolsScope, 'tq' => $toolsQ, 'tpage' => (string) $p]);
                    });
                    ?>
                </div>
            </div>
        </form>

        <div class="sd-tools-grid mt-3">
            <div class="sd-devices-panel">
                <div class="sd-devices-panel-head">Sync one person</div>
                <form method="post" action="<?php echo $e($devicesAction); ?>" class="sd-tools-form">
                    <input type="hidden" name="return_tab" value="tools">
                    <div class="sd-field">
                        <label class="form-label" for="sdSyncEmp">Employee No</label>
                        <input type="text" id="sdSyncEmp" name="employee_no" class="form-control" placeholder="254TE039" required>
                    </div>
                    <button type="submit" name="action" value="sync_user" class="btn btn-success btn-sm">
                        <i class="fas fa-user-check me-1"></i>Sync to readers
                    </button>
                </form>
                <p class="sd-tools-help">Push fingerprints / Face ID from MAIN to readers.</p>
            </div>
        </div>

        <script>
        (function () {
            var form = document.getElementById('sdToolsDeleteForm');
            var checkAll = document.getElementById('sdToolsCheckAll');
            var actionInput = document.getElementById('sdToolsAction');
            var target = document.getElementById('sdToolsTarget');
            var includeMain = document.getElementById('sdToolsIncludeMain');
            if (!form) return;

            var rowChecks = function () {
                return Array.prototype.slice.call(form.querySelectorAll('.sd-tools-row-check'));
            };

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    rowChecks().forEach(function (c) { c.checked = checkAll.checked; });
                });
            }

            form.querySelectorAll('.sd-tools-row-del').forEach(function (btn) {
                btn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    var eno = btn.getAttribute('data-eno') || '';
                    if (!eno) return;
                    rowChecks().forEach(function (c) { c.checked = c.value === eno; });
                    if (checkAll) checkAll.checked = false;
                    if (actionInput) actionInput.value = 'delete_users_selected';
                    if (window.sdConfirmToolsDelete && window.sdConfirmToolsDelete()) {
                        form.submit();
                    }
                });
            });

            window.sdConfirmToolsDelete = function () {
                var selected = rowChecks().filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
                if (!selected.length) {
                    alert('Select at least one person.');
                    return false;
                }
                var where = (target && target.value) ? target.value : 'all readers';
                if (includeMain && includeMain.checked) {
                    where = (target && target.value) ? (target.value + ' + MAIN') : 'MAIN + all readers';
                }
                return confirm('Delete ' + selected.length + ' person(s) from ' + where + '?\n\n' + selected.slice(0, 8).join(', ') + (selected.length > 8 ? '…' : '') + '\n\nAttendance history is kept.');
            };
        })();
        </script>

    <?php elseif ($deviceTab === 'queue'): ?>
        <div class="sd-devices-toolbar">
            <form method="get" action="<?php echo $e($devicesAction); ?>" class="sd-devices-filter">
                <input type="hidden" name="tab" value="queue">
                <div class="sd-field">
                    <label class="form-label" for="sdQs">Status</label>
                    <select id="sdQs" name="qs" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach (['all' => 'All', 'pending' => 'Pending', 'syncing' => 'Syncing', 'failed' => 'Failed', 'success' => 'Success'] as $k => $lab): ?>
                            <option value="<?php echo $e($k); ?>" <?php echo $queueStatus === $k ? 'selected' : ''; ?>><?php echo $e($lab); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <form method="post" action="<?php echo $e($devicesAction); ?>">
                <input type="hidden" name="return_tab" value="queue">
                <button type="submit" name="action" value="process_pending" class="btn btn-primary btn-sm"
                        onclick="return confirm('Process pending jobs now?');">Process pending</button>
            </form>
            <form method="post" action="<?php echo $e($devicesAction); ?>">
                <input type="hidden" name="return_tab" value="queue">
                <button type="submit" name="action" value="retry_failed" class="btn btn-outline-warning btn-sm"
                        onclick="return confirm('Requeue failed jobs?');">Retry failed</button>
            </form>
            <form method="post" action="<?php echo $e($devicesAction); ?>">
                <input type="hidden" name="return_tab" value="queue">
                <button type="submit" name="action" value="sync_all" class="btn btn-outline-primary btn-sm"
                        onclick="return confirm('Queue all known users for reader sync?');">Sync all users</button>
            </form>
        </div>

        <div class="sd-filter-summary">
            <?php echo (int) $queueTotal; ?> job(s)
        </div>

        <div class="sd-devices-panel">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Employee No</th>
                        <th>Device</th>
                        <th>Status</th>
                        <th>Fingers</th>
                        <th>Face</th>
                        <th>Attempts</th>
                        <th>Error</th>
                        <th>Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($jobs === []): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Queue is empty.</td></tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $j): ?>
                            <?php $st = strtolower((string) ($j['status'] ?? '')); ?>
                            <tr>
                                <td><code><?php echo $e($j['employee_no'] ?? ''); ?></code></td>
                                <td><?php echo $e($j['device_host'] ?? ''); ?></td>
                                <td><span class="sd-sync-pill is-<?php echo $e($st); ?>"><?php echo $e($st); ?></span></td>
                                <td><?php echo $e($j['finger_slots'] ?? ''); ?></td>
                                <td><?php echo !empty($j['include_face']) ? 'Yes' : '—'; ?></td>
                                <td><?php echo (int) ($j['attempt_count'] ?? 0); ?></td>
                                <td class="sd-devices-err"><?php echo $e($j['last_error'] ?? ''); ?></td>
                                <td><?php echo $e($j['updated_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: /* logs */ ?>
        <div class="sd-filter-summary">
            <?php echo (int) $logsTotal; ?> log(s) · Page <?php echo (int) $logsPage; ?>/<?php echo (int) $logsPages; ?>
        </div>
        <div class="sd-devices-panel">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Time</th>
                        <th>Employee No</th>
                        <th>Device</th>
                        <th>Operation</th>
                        <th>Result</th>
                        <th>Message</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($logs === []): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No logs yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $e($log['created_at'] ?? ''); ?></td>
                                <td><code><?php echo $e($log['employee_no'] ?? ''); ?></code></td>
                                <td><?php echo $e($log['device_host'] ?? ''); ?></td>
                                <td><?php echo $e($log['operation'] ?? ''); ?></td>
                                <td><?php echo !empty($log['success']) ? 'Success' : 'Failed'; ?></td>
                                <td class="sd-devices-err"><?php echo $e($log['message'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="sd-devices-panel-foot">
                <?php
                $pagerLinks($logsPage, $logsPages, static function (int $p) use ($tabUrl): string {
                    return $tabUrl('logs', ['lpage' => (string) $p]);
                });
                ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
