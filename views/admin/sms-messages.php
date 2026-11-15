<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$tab = ($tab ?? 'sent') === 'replies' ? 'replies' : 'sent';
$rows = is_array($rows ?? null) ? $rows : [];
$total = (int) ($total ?? 0);
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$search = (string) ($search ?? '');
$baseQuery = http_build_query(array_filter([
    'tab' => $tab,
    'q' => $search !== '' ? $search : null,
], static fn ($v) => $v !== null && $v !== ''));
?>
<style>
.sms-log-page { max-width: 1100px; }
.sms-log-page .sms-tabs { display:flex; gap:.35rem; flex-wrap:wrap; margin-bottom:1rem; }
.sms-log-page .sms-tabs a { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .9rem; border-radius:.45rem; border:1px solid #dbe4f0; text-decoration:none; color:#0b2545; font-weight:600; font-size:.86rem; background:#fff; }
.sms-log-page .sms-tabs a.active { background:#0b2545; color:#fff; border-color:#0b2545; }
.sms-log-page .sms-msg { max-width:28rem; white-space:pre-wrap; word-break:break-word; font-size:.84rem; }
.sms-log-page .badge-soft { font-size:.72rem; font-weight:700; padding:.2rem .45rem; border-radius:.3rem; }
.sms-log-page .badge-ok { background:#dcfce7; color:#166534; }
.sms-log-page .badge-fail { background:#fee2e2; color:#991b1b; }
.sms-log-page .badge-muted { background:#eef2f7; color:#475569; }
</style>

<div class="container-fluid px-3 px-md-4 py-3 sms-log-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">SMS messages</h1>
            <p class="text-muted small mb-0">Sent messages logged by the system, plus replies fetched from Mobitel when a long number or short code is configured.</p>
        </div>
        <a href="<?php echo APP_URL; ?>/admin/sms-gateway" class="btn btn-sm btn-outline-secondary">Gateway test</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success py-2"><?php echo $e($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2"><?php echo $e($error); ?></div>
    <?php endif; ?>

    <div class="sms-tabs">
        <a class="<?php echo $tab === 'sent' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/sms-messages?tab=sent">
            <i class="fas fa-paper-plane"></i> Sent
        </a>
        <a class="<?php echo $tab === 'replies' ? 'active' : ''; ?>" href="<?php echo APP_URL; ?>/admin/sms-messages?tab=replies">
            <i class="fas fa-inbox"></i> Replies
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <?php if ($tab === 'sent'): ?>
            <form method="post" action="<?php echo APP_URL; ?>/admin/sms-messages" class="row g-2 align-items-end mb-3">
                <input type="hidden" name="csrf_token" value="<?php echo $e($csrf_token ?? ''); ?>">
                <input type="hidden" name="action" value="sync_sent">
                <div class="col-auto">
                    <label class="form-label small mb-0" for="start_date">From</label>
                    <input type="date" class="form-control form-control-sm" id="start_date" name="start_date"
                           value="<?php echo $e(date('Y-m-d', strtotime('-7 days'))); ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0" for="end_date">To</label>
                    <input type="date" class="form-control form-control-sm" id="end_date" name="end_date"
                           value="<?php echo $e(date('Y-m-d')); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-sync-alt me-1"></i>Sync from Mobitel
                    </button>
                </div>
                <div class="col-12">
                    <div class="form-text">Alias: <?php echo $e(($alias ?? '') !== '' ? $alias : 'not set'); ?>. Sync also pulls Mobitel sent reports into this list.</div>
                </div>
            </form>
            <?php else: ?>
            <form method="post" action="<?php echo APP_URL; ?>/admin/sms-messages" class="mb-3">
                <input type="hidden" name="csrf_token" value="<?php echo $e($csrf_token ?? ''); ?>">
                <input type="hidden" name="action" value="sync_replies">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-sync-alt me-1"></i>Fetch replies from Mobitel
                </button>
                <div class="form-text mt-2">
                    <?php if (empty($hasLongNumber) && empty($hasShortCode)): ?>
                        Set <code>MOBITEL_SMS_LONG_NUMBER</code> and/or <code>MOBITEL_SMS_SHORTCODE</code> in the server <code>.env</code> to enable reply fetch.
                    <?php else: ?>
                        Long number: <?php echo !empty($hasLongNumber) ? 'configured' : 'not set'; ?>
                        · Short code: <?php echo !empty($hasShortCode) ? 'configured' : 'not set'; ?>
                    <?php endif; ?>
                </div>
            </form>
            <?php endif; ?>

            <form method="get" action="<?php echo APP_URL; ?>/admin/sms-messages" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="<?php echo $e($tab); ?>">
                <div class="col-md-6">
                    <label class="form-label small mb-0" for="q">Search</label>
                    <input type="text" class="form-control form-control-sm" id="q" name="q"
                           value="<?php echo $e($search); ?>" placeholder="Mobile, message, sender…">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <?php if ($tab === 'sent'): ?>
                        <tr>
                            <th>When</th>
                            <th>To</th>
                            <th>Message</th>
                            <th>Source</th>
                            <th>By</th>
                            <th>Status</th>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <th>Received</th>
                            <th>From</th>
                            <th>Message</th>
                            <th>Channel</th>
                        </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No messages yet.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <?php if ($tab === 'sent'):
                                    $st = strtolower((string) ($row['status'] ?? ''));
                                    $badge = $st === 'accepted' || $st === 'delivered' ? 'badge-ok'
                                        : ($st === 'failed' || $st === 'rejected' ? 'badge-fail' : 'badge-muted');
                                    $when = $row['time_sent'] ?? $row['created_at'] ?? '';
                                ?>
                                <tr>
                                    <td class="text-nowrap small"><?php echo $e((string) $when); ?></td>
                                    <td class="text-nowrap"><code><?php echo $e((string) ($row['mobile'] ?? '')); ?></code></td>
                                    <td><div class="sms-msg"><?php echo $e((string) ($row['message'] ?? '')); ?></div></td>
                                    <td class="small"><?php echo $e((string) ($row['source'] ?? '')); ?></td>
                                    <td class="small"><?php echo $e((string) ($row['sent_by'] ?? '—')); ?></td>
                                    <td>
                                        <span class="badge-soft <?php echo $badge; ?>">
                                            <?php echo $e((string) ($row['status'] ?? '')); ?>
                                            <?php if (!empty($row['result_code'])): ?>
                                                (<?php echo $e((string) $row['result_code']); ?>)
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php else:
                                    $when = $row['received_at'] ?? $row['fetched_at'] ?? '';
                                ?>
                                <tr>
                                    <td class="text-nowrap small"><?php echo $e((string) $when); ?></td>
                                    <td class="text-nowrap"><code><?php echo $e((string) ($row['mobile'] ?? '')); ?></code></td>
                                    <td><div class="sms-msg"><?php echo $e((string) ($row['message'] ?? '')); ?></div></td>
                                    <td class="small"><?php echo $e((string) ($row['channel'] ?? '—')); ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="small text-muted"><?php echo number_format($total); ?> message(s)</span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo APP_URL; ?>/admin/sms-messages?<?php echo $e($baseQuery); ?>&amp;page=<?php echo $currentPage - 1; ?>">Prev</a>
                    </li>
                    <?php endif; ?>
                    <li class="page-item disabled"><span class="page-link"><?php echo $currentPage; ?> / <?php echo $totalPages; ?></span></li>
                    <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo APP_URL; ?>/admin/sms-messages?<?php echo $e($baseQuery); ?>&amp;page=<?php echo $currentPage + 1; ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
