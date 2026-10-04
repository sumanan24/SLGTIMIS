<?php
declare(strict_types=1);
/** @var array $urls */
/** @var array $filters */
/** @var array $rows */
/** @var int $total */
/** @var int $pageNum */
/** @var int $perPage */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$total = (int) ($total ?? 0);
$pageNum = max(1, (int) ($pageNum ?? 1));
$perPage = max(1, (int) ($perPage ?? 50));
$totalPages = max(1, (int) ceil($total / $perPage));
$rows = $rows ?? [];
$filters = $filters ?? [];

$filterParams = array_filter([
    'person_id' => $filters['person_id'] ?? null,
    'student_name' => $filters['student_name'] ?? null,
    'date' => $filters['date'] ?? null,
    'date_from' => $filters['date_from'] ?? null,
    'date_to' => $filters['date_to'] ?? null,
], static function ($v) {
    return $v !== null && $v !== '';
});
$hasFilters = $filterParams !== [];
$queryBase = $urls['events'];
if ($filterParams !== []) {
    $queryBase .= '?' . http_build_query($filterParams);
}
$pageHref = static function (int $p) use ($queryBase, $e): string {
    $sep = strpos($queryBase, '?') !== false ? '&' : '?';
    return $e($queryBase . $sep . 'page=' . $p);
};

$fromRow = $total === 0 ? 0 : (($pageNum - 1) * $perPage) + 1;
$toRow = min($total, $pageNum * $perPage);

$window = 2;
$startPage = max(1, $pageNum - $window);
$endPage = min($totalPages, $pageNum + $window);
if (($endPage - $startPage) < ($window * 2)) {
    $startPage = max(1, $endPage - ($window * 2));
    $endPage = min($totalPages, $startPage + ($window * 2));
}

$studentDeviceSection = 'events';
$pageTitle = 'Attendance';
$pageSubtitle = 'One row per student per day — In (first), Out (last), Others (middle punches). Auto quick-sync pulls today from each machine in chunks.';
$exportQs = $filterParams ? '?' . http_build_query($filterParams) : '';
?>
<div class="student-device-page sd-fullpage">
    <?php include __DIR__ . '/partials/styles.php'; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/partials/nav.php'; ?>

    <div class="sd-fullpage-body">
        <div class="sd-page-head">
            <div class="sd-page-head-text">
                <h1 class="sd-page-title"><?php echo $e($pageTitle); ?></h1>
                <p class="sd-page-lead"><?php echo $e($pageSubtitle); ?></p>
            </div>
            <div class="sd-header-actions">
                <button type="button" class="btn btn-primary" id="sdQuickSyncBtn" title="Pull today's punches from all machines (one device per request)">
                    <i class="fas fa-bolt me-1"></i>Quick sync
                </button>
                <a class="btn btn-outline-success" href="<?php echo $e($urls['month']); ?>">
                    <i class="fas fa-calendar-alt me-1"></i>Month report
                </a>
                <a class="btn btn-outline-success" href="<?php echo $e($urls['export_excel'] . $exportQs); ?>">
                    <i class="fas fa-file-excel me-1"></i>Excel
                </a>
                <a class="btn btn-outline-success" href="<?php echo $e($urls['export_csv'] . $exportQs); ?>">
                    <i class="fas fa-file-csv me-1"></i>CSV
                </a>
            </div>
        </div>

        <?php
        $rangePresets = $rangePresets ?? [];
        $rangeDefault = $rangePresets[0] ?? ['from' => date('Y-m-d', strtotime('-6 days')), 'to' => date('Y-m-d')];
        $rangeSyncReport = $rangeSyncReport ?? null;
        ?>
        <?php if (is_array($rangeSyncReport) && !empty($rangeSyncReport['text'])): ?>
            <pre class="sd-range-sync-report"><?php echo $e($rangeSyncReport['text']); ?></pre>
        <?php endif; ?>
        <div class="card sd-card mb-3">
            <div class="card-header">
                <div class="fw-semibold">Sync attendance for selected dates</div>
                <div class="small text-muted">Pull every finger and face punch from Reader 1 (172.16.0.29), Reader 2 (172.16.0.28) and Reader 3 (172.16.0.27).</div>
            </div>
            <div class="card-body">
                <div class="sd-range-sync-actions">
                    <?php foreach ($rangePresets as $preset): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm sd-range-sync-btn"
                                data-from="<?php echo $e($preset['from'] ?? ''); ?>"
                                data-to="<?php echo $e($preset['to'] ?? ''); ?>"
                                data-label="<?php echo $e($preset['label'] ?? 'Sync'); ?>">
                            <?php echo $e($preset['label'] ?? 'Sync'); ?>
                        </button>
                    <?php endforeach; ?>
                    <div class="sd-field">
                        <label class="form-label" for="sdRangeFrom">Start date</label>
                        <input type="date" id="sdRangeFrom" class="form-control form-control-sm"
                               value="<?php echo $e($rangeDefault['from'] ?? ''); ?>">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdRangeTo">End date</label>
                        <input type="date" id="sdRangeTo" class="form-control form-control-sm"
                               value="<?php echo $e($rangeDefault['to'] ?? ''); ?>">
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="sdRangeCustomBtn">
                        <i class="fas fa-fingerprint me-1"></i>Sync attendance
                    </button>
                </div>
                <p class="small text-muted mb-0 mt-2">Uses the dates you select, from 00:00:00 through the next day 00:00:00 (Asia/Colombo), including morning and evening punches. Each reader is read in full, page by page. Running the same dates again does not duplicate records.</p>
            </div>
        </div>

        <div id="sdQuickSyncBar" class="alert alert-info d-none mb-3" role="status" aria-live="polite">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="spinner-border spinner-border-sm text-primary" role="presentation" id="sdQuickSyncSpin"></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold" id="sdQuickSyncTitle">Quick sync</div>
                    <div class="small mb-1" id="sdQuickSyncMsg">Starting…</div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar" id="sdQuickSyncProgress" role="progressbar" style="width:0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <form method="get" action="<?php echo $e($urls['events']); ?>" class="sd-toolbar card sd-card">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-between gap-2">
                <span><i class="fas fa-filter me-2 text-primary"></i>Filter</span>
                <?php if ($hasFilters): ?>
                    <a href="<?php echo $e($urls['events']); ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="sd-filter-grid">
                    <div class="sd-field">
                        <label class="form-label" for="sdPersonId">Student ID / Emp No</label>
                        <input type="text" id="sdPersonId" name="person_id" class="form-control"
                               value="<?php echo $e($filters['person_id'] ?? ''); ?>"
                               placeholder="2025/ICT/… or 254TE001" autocomplete="off">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdStudentName">Student name</label>
                        <input type="text" id="sdStudentName" name="student_name" class="form-control"
                               value="<?php echo $e($filters['student_name'] ?? ''); ?>" autocomplete="off">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdDate">Date</label>
                        <input type="date" id="sdDate" name="date" class="form-control"
                               value="<?php echo $e($filters['date'] ?? ''); ?>">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdDateFrom">From</label>
                        <input type="date" id="sdDateFrom" name="date_from" class="form-control"
                               value="<?php echo $e($filters['date_from'] ?? ''); ?>">
                    </div>
                    <div class="sd-field">
                        <label class="form-label" for="sdDateTo">To</label>
                        <input type="date" id="sdDateTo" name="date_to" class="form-control"
                               value="<?php echo $e($filters['date_to'] ?? ''); ?>">
                    </div>
                    <div class="sd-field sd-field-actions">
                        <label class="form-label d-none d-lg-block invisible">Apply</label>
                        <div class="sd-filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Apply
                            </button>
                            <a href="<?php echo $e($urls['events']); ?>" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card sd-card sd-events-panel">
            <div class="card-header">
                <div class="sd-panel-head">
                    <div>
                        <div class="fw-semibold">Daily attendance</div>
                        <div class="sd-legend mt-1">
                            <span><i class="dot in"></i>In — first</span>
                            <span><i class="dot out"></i>Out — last</span>
                            <span><i class="dot other"></i>Others — middle</span>
                        </div>
                    </div>
                    <div class="sd-summary-chip">
                        <?php if ($total > 0): ?>
                            Showing <strong><?php echo number_format($fromRow); ?>–<?php echo number_format($toRow); ?></strong>
                            of <strong><?php echo number_format($total); ?></strong>
                        <?php else: ?>
                            <strong>0</strong> day-rows
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($rows === []): ?>
                <div class="sd-empty">
                    <i class="fas fa-clock"></i>
                    <p class="mb-0">No attendance records for this filter.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive sd-table-wrap d-none d-lg-block">
                    <table class="table table-hover sd-events-table mb-0">
                        <colgroup>
                            <col class="col-id">
                            <col class="col-emp">
                            <col class="col-name">
                            <col class="col-date">
                            <col class="col-time">
                            <col class="col-time">
                            <col class="col-others">
                            <col class="col-machine">
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="col-id">Student ID</th>
                            <th class="col-emp">Employee No</th>
                            <th class="col-name">Student Name</th>
                            <th class="col-date">Date</th>
                            <th class="col-time text-center">In</th>
                            <th class="col-time text-center">Out</th>
                            <th class="col-others">Others</th>
                            <th class="col-machine">Machine</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="col-id"><?php echo $e($row['student_id'] ?? ''); ?></td>
                                <td class="col-emp"><?php echo $e($row['employee_no'] ?? ''); ?></td>
                                <td class="col-name"><?php echo $e($row['student_name'] ?? ''); ?></td>
                                <td class="col-date"><?php echo $e($row['attendance_date'] ?? ''); ?></td>
                                <td class="col-time text-center">
                                    <?php if (!empty($row['time_in'])): ?>
                                        <span class="sd-time-in"><?php echo $e($row['time_in']); ?></span>
                                    <?php else: ?>
                                        <span class="sd-time-empty">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-time text-center">
                                    <?php if (!empty($row['time_out'])): ?>
                                        <span class="sd-time-out"><?php echo $e($row['time_out']); ?></span>
                                    <?php else: ?>
                                        <span class="sd-time-empty">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-others"><?php echo ($row['time_others'] ?? '') !== '' ? $e($row['time_others']) : '—'; ?></td>
                                <td class="col-machine"><?php echo $e($row['machine_id'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="sd-card-list d-lg-none">
                    <?php foreach ($rows as $row): ?>
                        <article class="sd-day-card">
                            <div class="sd-day-card-top">
                                <div class="min-w-0">
                                    <div class="sd-day-name"><?php echo $e($row['student_name'] ?? '—'); ?></div>
                                    <div class="sd-day-id"><?php echo $e($row['student_id'] ?? ''); ?></div>
                                </div>
                                <div class="sd-day-date"><?php echo $e($row['attendance_date'] ?? ''); ?></div>
                            </div>
                            <div class="sd-day-times">
                                <div>
                                    <span class="sd-mini-label">In</span>
                                    <?php if (!empty($row['time_in'])): ?>
                                        <span class="sd-time-in"><?php echo $e($row['time_in']); ?></span>
                                    <?php else: ?>
                                        <span class="sd-time-empty">—</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <span class="sd-mini-label">Out</span>
                                    <?php if (!empty($row['time_out'])): ?>
                                        <span class="sd-time-out"><?php echo $e($row['time_out']); ?></span>
                                    <?php else: ?>
                                        <span class="sd-time-empty">—</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (($row['time_others'] ?? '') !== '' || ($row['employee_no'] ?? '') !== '' || ($row['machine_id'] ?? '') !== ''): ?>
                                <div class="sd-day-meta">
                                    <?php if (($row['employee_no'] ?? '') !== ''): ?>
                                        <span>Emp <?php echo $e($row['employee_no']); ?></span>
                                    <?php endif; ?>
                                    <?php if (($row['time_others'] ?? '') !== ''): ?>
                                        <span>Others <?php echo $e($row['time_others']); ?></span>
                                    <?php endif; ?>
                                    <?php if (($row['machine_id'] ?? '') !== ''): ?>
                                        <span><?php echo $e($row['machine_id']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="card-footer sd-pager">
                    <div class="small text-muted">Page <?php echo (int) $pageNum; ?> of <?php echo (int) $totalPages; ?></div>
                    <nav aria-label="Attendance pagination">
                        <ul class="pagination pagination-sm mb-0 flex-wrap justify-content-end">
                            <li class="page-item <?php echo $pageNum <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $pageNum <= 1 ? '#' : $pageHref($pageNum - 1); ?>">Prev</a>
                            </li>
                            <?php if ($startPage > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?php echo $pageHref(1); ?>">1</a></li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                <li class="page-item <?php echo $p === $pageNum ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $pageHref($p); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="<?php echo $pageHref($totalPages); ?>"><?php echo $totalPages; ?></a></li>
                            <?php endif; ?>
                            <li class="page-item <?php echo $pageNum >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $pageNum >= $totalPages ? '#' : $pageHref($pageNum + 1); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$autoQuickSync = !empty($autoQuickSync);
$quickSyncUrl = (string) ($quickSyncUrl ?? ($urls['quick_sync_chunk'] ?? ''));
$rangeSyncUrl = (string) ($rangeSyncUrl ?? ($urls['range_sync_chunk'] ?? ''));
?>
<script>
(function () {
    var autoRun = <?php echo $autoQuickSync ? 'true' : 'false'; ?>;
    var baseUrl = <?php echo json_encode($quickSyncUrl, JSON_UNESCAPED_SLASHES); ?>;
    var rangeUrl = <?php echo json_encode($rangeSyncUrl, JSON_UNESCAPED_SLASHES); ?>;
    if (!baseUrl && !rangeUrl) return;

    var bar = document.getElementById('sdQuickSyncBar');
    var titleEl = document.getElementById('sdQuickSyncTitle');
    var msgEl = document.getElementById('sdQuickSyncMsg');
    var prog = document.getElementById('sdQuickSyncProgress');
    var spin = document.getElementById('sdQuickSyncSpin');
    var btn = document.getElementById('sdQuickSyncBtn');
    var rangeBtns = Array.prototype.slice.call(document.querySelectorAll('.sd-range-sync-btn'));
    var customBtn = document.getElementById('sdRangeCustomBtn');
    var fromInput = document.getElementById('sdRangeFrom');
    var toInput = document.getElementById('sdRangeTo');
    var running = false;
    var savedTotal = 0;

    function setBusy(on) {
        running = on;
        window.sdDeviceSyncLock = on;
        if (btn) btn.disabled = on;
        rangeBtns.forEach(function (b) { b.disabled = on; });
        if (customBtn) customBtn.disabled = on;
        if (fromInput) fromInput.disabled = on;
        if (toInput) toInput.disabled = on;
    }

    function setProgress(chunk, total) {
        var pct = total > 0 ? Math.round(((chunk + 1) / total) * 100) : 0;
        if (prog) {
            prog.style.width = pct + '%';
            prog.setAttribute('aria-valuenow', String(pct));
        }
    }

    function showBar(title) {
        if (bar) {
            bar.classList.remove('d-none', 'alert-success', 'alert-danger');
            bar.classList.add('alert-info');
        }
        if (spin) spin.classList.remove('d-none');
        if (titleEl) titleEl.textContent = title;
        if (prog) prog.style.width = '0%';
    }

    function finish(ok, summary, reload, titles) {
        setBusy(false);
        if (spin) spin.classList.add('d-none');
        if (bar) {
            bar.classList.remove('alert-info', 'alert-danger', 'alert-success');
            bar.classList.add(ok ? 'alert-success' : 'alert-danger');
        }
        if (titleEl) titleEl.textContent = ok ? titles.ok : titles.bad;
        if (msgEl) msgEl.textContent = summary || (ok ? 'Done' : 'Failed');
        if (typeof reload === 'function' && ok) {
            setTimeout(reload, 800);
        } else if (reload === true && savedTotal > 0) {
            setTimeout(function () {
                var u = new URL(window.location.href);
                u.searchParams.set('nosync', '1');
                window.location.href = u.toString();
            }, 700);
        }
    }

    function fetchJson(url) {
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.text().then(function (text) {
                var j = null;
                try { j = JSON.parse(text); } catch (e) { j = null; }
                if (!j || j.success === undefined) {
                    throw new Error((j && j.message) || ('HTTP ' + r.status));
                }
                return j;
            });
        });
    }

    function runQuickSync() {
        if (running || window.sdDeviceSyncLock || !baseUrl) return;
        setBusy(true);
        savedTotal = 0;
        showBar('Quick sync (today)');
        if (msgEl) msgEl.textContent = 'Syncing machines one by one…';

        function step(chunk) {
            if (msgEl) msgEl.textContent = 'Machine ' + (chunk + 1) + '…';
            var url = baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'chunk=' + encodeURIComponent(String(chunk));
            return fetchJson(url).then(function (data) {
                if (!data || data.success === false) {
                    throw new Error((data && data.message) || 'Chunk failed');
                }
                var total = parseInt(data.total, 10) || 0;
                var label = data.label || data.host || ('#' + (chunk + 1));
                savedTotal += parseInt(data.saved, 10) || 0;
                setProgress(parseInt(data.chunk, 10) || chunk, total);
                if (msgEl) {
                    msgEl.textContent = label + ': ' + (data.message || (data.ok ? 'OK' : 'Failed'))
                        + (total ? (' (' + (chunk + 1) + '/' + total + ')') : '');
                }
                if (data.done) {
                    finish(true, data.summary || ('Saved ' + savedTotal + ' new punch(es)'), true, {
                        ok: 'Quick sync complete',
                        bad: 'Quick sync issue'
                    });
                    return;
                }
                return step(parseInt(data.next_chunk, 10) || (chunk + 1));
            });
        }

        step(0).catch(function (err) {
            finish(false, (err && err.message) ? err.message : 'Quick sync failed', false, {
                ok: 'Quick sync complete',
                bad: 'Quick sync issue'
            });
        });
    }

    function dayCount(from, to) {
        var a = new Date(from + 'T00:00:00');
        var b = new Date(to + 'T00:00:00');
        if (isNaN(a.getTime()) || isNaN(b.getTime())) return -1;
        return Math.round((b.getTime() - a.getTime()) / 86400000) + 1;
    }

    function runRangeSync(from, to, label) {
        if (!rangeUrl) return;
        if (running || window.sdDeviceSyncLock) {
            alert('A sync is already running.');
            return;
        }
        if (!/^\d{4}-\d{2}-\d{2}$/.test(from) || !/^\d{4}-\d{2}-\d{2}$/.test(to)) {
            alert('Select a start date and an end date.');
            return;
        }
        if (from > to) {
            alert('Start date must be on or before the end date.');
            return;
        }
        var days = dayCount(from, to);
        if (days > 62) {
            alert('That range is ' + days + ' days. Sync up to 2 months (62 days) at a time.');
            return;
        }
        var title = label || (from + ' to ' + to);
        if (!confirm('Sync student finger and face attendance from all machines for ' + title + ' (' + from + ' to ' + to + ')?')) {
            return;
        }
        setBusy(true);
        savedTotal = 0;
        showBar(title);
        if (msgEl) msgEl.textContent = 'Reading punches week by week…';

        function step(chunk) {
            var url = rangeUrl + (rangeUrl.indexOf('?') >= 0 ? '&' : '?')
                + 'chunk=' + encodeURIComponent(String(chunk))
                + '&date_from=' + encodeURIComponent(from)
                + '&date_to=' + encodeURIComponent(to);
            return fetchJson(url).then(function (data) {
                if (!data || data.success === false) {
                    throw new Error((data && data.message) || 'Chunk failed');
                }
                var readersTotal = parseInt(data.readers_total, 10) || 3;
                var readersDone = parseInt(data.readers_finished, 10) || 0;
                savedTotal += parseInt(data.saved, 10) || 0;
                if (prog && readersTotal > 0) {
                    var pct = data.done ? 100 : Math.max(8, Math.round((readersDone / readersTotal) * 100));
                    prog.style.width = pct + '%';
                    prog.setAttribute('aria-valuenow', String(pct));
                }
                if (msgEl) {
                    var shown = Math.min(readersTotal, readersDone + (data.done ? 0 : 1));
                    msgEl.textContent = (data.message || (data.ok ? 'OK' : 'Failed'))
                        + ' (' + shown + '/' + readersTotal + ' readers)';
                }
                if (data.done) {
                    finish(true, data.summary || ('Saved ' + savedTotal + ' new punch(es)'), function () {
                        var u = new URL(window.location.href);
                        u.searchParams.set('nosync', '1');
                        u.searchParams.set('date_from', from);
                        u.searchParams.set('date_to', to);
                        u.searchParams.delete('date');
                        u.searchParams.delete('page');
                        window.location.href = u.toString();
                    }, {
                        ok: 'Attendance sync complete',
                        bad: 'Attendance sync issue'
                    });
                    return;
                }
                return step(parseInt(data.next_chunk, 10) || (chunk + 1));
            });
        }

        step(0).catch(function (err) {
            finish(false, (err && err.message) ? err.message : 'Attendance sync failed', false, {
                ok: 'Attendance sync complete',
                bad: 'Attendance sync issue'
            });
        });
    }

    rangeBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            var from = b.getAttribute('data-from') || '';
            var to = b.getAttribute('data-to') || '';
            if (fromInput) fromInput.value = from;
            if (toInput) toInput.value = to;
            runRangeSync(from, to, b.getAttribute('data-label') || '');
        });
    });
    if (customBtn) {
        customBtn.addEventListener('click', function () {
            runRangeSync(
                fromInput ? fromInput.value : '',
                toInput ? toInput.value : '',
                'Selected dates'
            );
        });
    }

    if (btn) {
        btn.addEventListener('click', function () {
            runQuickSync();
        });
    }
    if (autoRun && baseUrl) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runQuickSync);
        } else {
            runQuickSync();
        }
    }
})();
</script>
