<?php
$h = static function ($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$stLabel = $labels['statuses'] ?? [];
$prLabel = $labels['priorities'] ?? [];
$badgeStatus = static function ($status) use ($h, $stLabel) {
    $status = (string) $status;
    return '<span class="fac-badge fac-s-' . $h($status) . '">' . $h($stLabel[$status] ?? $status) . '</span>';
};
$badgePriority = static function ($priority) use ($h, $prLabel) {
    $priority = (string) $priority;
    return '<span class="fac-badge fac-p-' . $h($priority) . '">' . $h($prLabel[$priority] ?? $priority) . '</span>';
};
$dueLabel = static function ($row) {
    if (($row['due_state'] ?? '') === 'overdue') {
        return '<span class="fac-due fac-due-overdue">Overdue by ' . (int) $row['overdue_days'] . ' day(s)</span>';
    }
    if (($row['due_state'] ?? '') === 'due_soon') {
        return '<span class="fac-due fac-due-due_soon">Due soon</span>';
    }
    if (($row['due_state'] ?? '') === 'on_time') {
        return '<span class="fac-due fac-due-on_time">On time</span>';
    }
    return '';
};
$base = APP_URL . '/facilities';
$section = $section ?? '';
$actor = $actor ?? [];
$unread = (int) ($unread ?? 0);
$message = $message ?? null;
$error = $error ?? null;
$facNav = static function () use ($base, $section, $unread, $actor, $h) {
    $items = [
        'dashboard' => ['Dashboard', $base . '/dashboard'],
        'list' => ['Tickets', $base],
        'create' => ['New ticket', $base . '/create'],
        'notifications' => ['Notices' . ($unread > 0 ? ' (' . $unread . ')' : ''), $base . '/notifications'],
    ];
    if (!empty($actor['is_officer']) || !empty($actor['is_monitor']) || !empty($actor['is_hod'])) {
        $items['reports'] = ['Reports', $base . '/reports'];
    }
    if (!empty($actor['is_officer'])) {
        $items['categories'] = ['Categories', $base . '/categories'];
    }
    echo '<nav class="fac-nav">';
    foreach ($items as $key => $item) {
        $on = $section === $key ? ' is-on' : '';
        echo '<a class="fac-nav-a' . $on . '" href="' . $h($item[1]) . '">' . $h($item[0]) . '</a>';
    }
    echo '</nav>';
};
$facFlash = static function () use ($message, $error, $h) {
    if (!empty($message)) {
        echo '<div class="fac-flash fac-flash-ok">' . $h($message) . '</div>';
    }
    if (!empty($error)) {
        echo '<div class="fac-flash fac-flash-err">' . $h($error) . '</div>';
    }
};
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/facilities.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/facilities.css'); ?>">
<script src="<?php echo APP_URL; ?>/assets/js/facilities.js?v=<?php echo is_file(BASE_PATH . '/assets/js/facilities.js') ? filemtime(BASE_PATH . '/assets/js/facilities.js') : time(); ?>" defer></script>
