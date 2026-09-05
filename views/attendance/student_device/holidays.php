<?php
declare(strict_types=1);
/** @var array $urls */
/** @var array $rows */
/** @var array $departments */
/** @var array $courses */
/** @var string $departmentId */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$rows = $rows ?? [];
$departments = $departments ?? [];
$courses = $courses ?? [];
$departmentId = (string) ($departmentId ?? '');

$studentDeviceSection = 'holidays';
$pageTitle = 'Holidays & special leave';
$pageSubtitle = 'SAO / ADM can mark holiday or special leave days. Those dates (and weekends) are excluded from absent counts.';

ob_start();
?>
<div class="sd-header-actions">
    <a class="btn btn-outline-secondary" href="<?php echo $e($urls['month']); ?>">
        <i class="fas fa-calendar-alt me-1"></i>Month report
    </a>
</div>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<form method="post" action="<?php echo $e($urls['holidays']); ?>" class="card sd-card">
    <div class="card-header fw-semibold">
        <i class="fas fa-plus-circle me-2 text-primary"></i>Add holiday or special leave
    </div>
    <div class="card-body">
        <input type="hidden" name="action" value="create">
        <div class="sd-status-filter-grid">
            <div class="sd-field">
                <label class="form-label" for="leave_date">Date</label>
                <input type="date" id="leave_date" name="leave_date" class="form-control" required>
            </div>
            <div class="sd-field">
                <label class="form-label" for="leave_type">Type</label>
                <select id="leave_type" name="leave_type" class="form-select" required>
                    <option value="holiday">Holiday</option>
                    <option value="special_leave">Special leave</option>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control" maxlength="150"
                       placeholder="e.g. Poya day, Institute holiday">
            </div>
            <div class="sd-field">
                <label class="form-label" for="department_id">Department (optional)</label>
                <select id="department_id" name="department_id" class="form-select">
                    <option value="">All departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $e($d['department_id'] ?? ''); ?>"
                            <?php echo $departmentId === ($d['department_id'] ?? '') ? 'selected' : ''; ?>>
                            <?php echo $e($d['department_name'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sd-field">
                <label class="form-label" for="course_id">Course (optional)</label>
                <select id="course_id" name="course_id" class="form-select">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $e($c['course_id'] ?? ''); ?>">
                            <?php echo $e(($c['course_name'] ?? '') . ' (' . ($c['course_id'] ?? '') . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="sd-field mt-3">
            <label class="form-label" for="notes">Notes</label>
            <input type="text" id="notes" name="notes" class="form-control" maxlength="255"
                   placeholder="Optional note">
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end mt-3 pt-3 border-top">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Save leave day
            </button>
        </div>
        <div class="small text-muted mt-2">
            Leave department and course empty to apply to <strong>all students</strong>.
            Public holidays from the gazette are already excluded automatically.
        </div>
    </div>
</form>

<div class="card sd-card sd-events-panel">
    <div class="card-header fw-semibold">Saved holidays & special leave</div>
    <?php if ($rows === []): ?>
        <div class="sd-empty">
            <i class="fas fa-umbrella-beach"></i>
            <p class="mb-0">No custom leave days yet.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive sd-table-wrap d-none d-md-block">
            <table class="table table-hover sd-events-table mb-0">
                <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Scope</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $type = (string) ($row['leave_type'] ?? 'holiday');
                    $scope = 'All students';
                    if (!empty($row['course_name']) || !empty($row['course_id'])) {
                        $scope = trim((string) (($row['department_name'] ?? '') . ' · ' . ($row['course_name'] ?? $row['course_id'])));
                    } elseif (!empty($row['department_name']) || !empty($row['department_id'])) {
                        $scope = (string) ($row['department_name'] ?? $row['department_id']);
                    }
                    ?>
                    <tr>
                        <td class="col-date"><?php echo $e($row['leave_date'] ?? ''); ?></td>
                        <td>
                            <span class="sd-status-badge <?php echo $type === 'special_leave' ? 'sd-badge-leave' : 'sd-badge-holiday'; ?>">
                                <?php echo $type === 'special_leave' ? 'Special leave' : 'Holiday'; ?>
                            </span>
                        </td>
                        <td><?php echo $e($row['title'] ?? ''); ?></td>
                        <td><?php echo $e($scope); ?></td>
                        <td class="small text-muted"><?php echo $e($row['notes'] ?? ''); ?></td>
                        <td class="text-end">
                            <form method="post" action="<?php echo $e($urls['holidays']); ?>" class="d-inline"
                                  onsubmit="return confirm('Remove this leave day?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) ($row['id'] ?? 0); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="sd-card-list d-md-none">
            <?php foreach ($rows as $row): ?>
                <?php $type = (string) ($row['leave_type'] ?? 'holiday'); ?>
                <article class="sd-day-card">
                    <div class="sd-day-card-top">
                        <div>
                            <div class="sd-day-name"><?php echo $e($row['title'] ?? ''); ?></div>
                            <div class="sd-day-id"><?php echo $e($row['leave_date'] ?? ''); ?></div>
                        </div>
                        <span class="sd-status-badge <?php echo $type === 'special_leave' ? 'sd-badge-leave' : 'sd-badge-holiday'; ?>">
                            <?php echo $type === 'special_leave' ? 'Special leave' : 'Holiday'; ?>
                        </span>
                    </div>
                    <form method="post" action="<?php echo $e($urls['holidays']); ?>"
                          onsubmit="return confirm('Remove this leave day?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) ($row['id'] ?? 0); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('department_id')?.addEventListener('change', function () {
    var dept = this.value;
    window.location.href = <?php echo json_encode(rtrim((string) ($urls['holidays'] ?? ''), '/'), JSON_UNESCAPED_SLASHES); ?> + (dept ? ('?department_id=' + encodeURIComponent(dept)) : '');
});
</script>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
