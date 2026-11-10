<?php
if (!class_exists('GroupTimetableModel')) {
    require_once BASE_PATH . '/models/GroupTimetableModel.php';
}
$timetableSlots = $timetableSlots ?? [];
$timetableModules = $timetableModules ?? [];
$timetableStaff = $timetableStaff ?? [];
$days = GroupTimetableModel::dayNames();
$periods = GroupTimetableModel::periods();
$visibleDays = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
foreach ($timetableSlots as $slot) {
    $dayNo = (int) ($slot['weekday'] ?? 0);
    if (isset($days[$dayNo])) {
        $visibleDays[$dayNo] = $days[$dayNo];
    }
}
ksort($visibleDays);
$byPeriod = [];
foreach ($timetableSlots as $slot) {
    $byPeriod[(string) ($slot['period'] ?? '')][(int) ($slot['weekday'] ?? 0)][] = $slot;
}
$draftCount = 0;
foreach ($timetableSlots as $slot) {
    if (($slot['publish_status'] ?? 'published') !== 'published') {
        $draftCount++;
    }
}
$classTitle = static function ($slot) {
    $name = trim((string) ($slot['module_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    $subject = trim((string) ($slot['subject'] ?? ''));
    if ($subject !== '') {
        return $subject;
    }
    $moduleId = trim((string) ($slot['module_id'] ?? ''));
    return $moduleId !== '' ? $moduleId : 'Class';
};
$formatDate = static function ($value) {
    $ts = strtotime((string) $value);
    return $ts ? date('d M Y', $ts) : (string) $value;
};
?>
<style>
.tt-page { color: #1e293b; }
.tt-page .tt-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: .75rem;
}
.tt-page .tt-top h1 { margin: 0; font-size: 1.05rem; font-weight: 700; line-height: 1.3; color: #0f172a; }
.tt-page .tt-top p { margin: .1rem 0 0; font-size: .8rem; color: #64748b; }
.tt-page .tt-top .btn { min-height: 32px; display: inline-flex; align-items: center; }
.tt-page .tt-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
}
.tt-page .tt-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    min-height: 42px;
    padding: .4rem .75rem;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}
.tt-page .tt-head h2 { margin: 0; font-size: .9rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; }
.tt-page .tt-head .btn { min-height: 30px; }
.tt-page .tt-scroll { overflow-x: auto; }
.tt-page .tt-grid { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 720px; }
.tt-page .tt-grid th,
.tt-page .tt-grid td {
    border-bottom: 1px solid #e2e8f0;
    border-right: 1px solid #eef2f6;
    padding: .4rem .45rem;
    vertical-align: top;
}
.tt-page .tt-grid th {
    background: #f8fafc;
    color: #64748b;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    text-align: center;
    vertical-align: middle;
    height: 32px;
}
.tt-page .tt-grid th:first-child,
.tt-page .tt-grid td.tt-time { width: 5.5rem; text-align: left; }
.tt-page .tt-grid th:last-child,
.tt-page .tt-grid td:last-child { border-right: none; }
.tt-page .tt-grid tr:last-child td { border-bottom: none; }
.tt-page .tt-time { color: #0f172a; font-weight: 700; font-size: .75rem; white-space: nowrap; }
.tt-page .tt-class { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #0b2545; border-radius: .3rem; padding: .35rem .45rem; }
.tt-page .tt-class + .tt-class { margin-top: .3rem; }
.tt-page .tt-class strong { display: block; color: #0f172a; font-size: .8rem; line-height: 1.3; }
.tt-page .tt-class span { display: block; color: #475569; font-size: .72rem; line-height: 1.35; }
.tt-page .tt-class-tools { display: flex; align-items: center; gap: .3rem; margin-top: .35rem; }
.tt-page .tt-class-tools form { margin: 0; display: flex; }
.tt-page .tt-class-tools .btn { min-height: 24px; padding: 0 .45rem; font-size: .7rem; line-height: 1; }
.tt-page .tt-form {
    display: grid;
    grid-template-columns: minmax(9.5rem, 1fr) minmax(9.5rem, 1fr) minmax(11rem, 1.35fr) minmax(10rem, 1.15fr);
    gap: .55rem .7rem;
    align-items: end;
    padding: .7rem .75rem .15rem;
    border-top: 1px solid #e2e8f0;
}
.tt-page .tt-field { min-width: 0; }
.tt-page .tt-field label,
.tt-page .tt-label {
    display: block;
    height: 1rem;
    margin: 0 0 .2rem;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
    color: #64748b;
    line-height: 1rem;
}
.tt-page .tt-label { visibility: hidden; }
.tt-page .tt-form .form-control,
.tt-page .tt-form .form-select {
    width: 100%;
    height: 34px;
    min-height: 34px;
    padding: 0 .55rem;
    font-size: .84rem;
    line-height: 32px;
    border-radius: 6px;
}
.tt-page .tt-form .form-select { padding-right: 1.7rem; background-position: right .45rem center; }
.tt-page .btn:hover,
.tt-page .btn:active { transform: none; box-shadow: none; }
.tt-page .tt-btns { display: flex; align-items: center; gap: .4rem; height: 34px; }
.tt-page .tt-btns .btn { height: 34px; min-width: 6rem; font-size: .84rem; padding: 0 .75rem; display: inline-flex; align-items: center; justify-content: center; }
.tt-page .tt-help { margin: 0; padding: .35rem .75rem .65rem; color: #64748b; font-size: .75rem; }
@media (max-width: 900px) {
    .tt-page .tt-form { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 560px) {
    .tt-page .tt-top { align-items: flex-start; }
    .tt-page .tt-form { grid-template-columns: 1fr; }
    .tt-page .tt-btns .btn { flex: 1; }
}
@media print {
    .sidebar, .top-navbar, .footer, .tt-top .btn, .tt-form, .tt-help, .tt-class-tools, .tt-head .btn, .alert { display: none !important; }
    .tt-page .tt-panel { border: none; }
}
</style>
<div class="tt-panel" id="groupTimetable">
    <div class="tt-head">
        <h2><i class="fas fa-calendar-alt text-primary me-2"></i>Class timetable
            <?php if ($draftCount > 0): ?><span class="badge bg-warning text-dark ms-2"><?php echo (int) $draftCount; ?> draft</span><?php endif; ?>
        </h2>
        <div class="d-flex gap-2">
            <?php if ($draftCount > 0): ?>
            <form method="post" action="<?php echo $e($app . '/groups/timetable-publish'); ?>" class="d-inline" onsubmit="return confirm('Publish all draft classes for this group?');">
                <input type="hidden" name="group_id" value="<?php echo (int) ($group['id'] ?? 0); ?>">
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-bullhorn me-1"></i>Publish timetable</button>
            </form>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="tt-scroll">
        <table class="tt-grid">
            <thead>
                <tr>
                    <th>Period</th>
                    <?php foreach ($visibleDays as $dayName): ?>
                        <th><?php echo $e($dayName); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periods as $periodCode => $periodLabel): ?>
                    <tr>
                        <td class="tt-time"><?php echo $e($periodLabel); ?></td>
                        <?php foreach ($visibleDays as $dayNo => $dayName): ?>
                            <td>
                                <?php foreach ($byPeriod[$periodCode][$dayNo] ?? [] as $slot): ?>
                                    <?php
                                    list($slotFrom, $slotTo) = GroupTimetableModel::effectiveTimes($slot);
                                    $isDraft = (($slot['publish_status'] ?? 'published') !== 'published');
                                    ?>
                                    <div class="tt-class" <?php echo $isDraft ? 'style="border-left-color:#f59e0b;background:#fffbeb;"' : ''; ?>>
                                        <strong><?php echo $e($classTitle($slot)); ?></strong>
                                        <span><?php echo $e(substr($slotFrom, 0, 5) . ' – ' . substr($slotTo, 0, 5)); ?>
                                            · <?php echo $e($slot['session_type'] ?? 'Theory'); ?>
                                            <?php if ($isDraft): ?><span class="badge bg-warning text-dark ms-1" style="display:inline-block">Draft</span><?php endif; ?></span>
                                        <span><?php echo $e($slot['staff_name'] ?? $slot['staff_id'] ?? ''); ?></span>
                                        <?php if (trim((string) ($slot['classroom'] ?? '')) !== ''): ?>
                                            <span><?php echo $e($slot['classroom']); ?></span>
                                        <?php endif; ?>
                                        <span><?php echo $e($formatDate($slot['start_date'] ?? '') . ' – ' . $formatDate($slot['end_date'] ?? '')); ?></span>
                                        <div class="tt-class-tools">
                                            <button type="button" class="btn btn-outline-primary btn-sm tt-edit"
                                                data-id="<?php echo (int) $slot['timetable_id']; ?>"
                                                data-day="<?php echo (int) $slot['weekday']; ?>"
                                                data-period="<?php echo $e($slot['period'] ?? ''); ?>"
                                                data-module="<?php echo $e($slot['module_id'] ?? ''); ?>"
                                                data-subject="<?php echo $e($slot['subject'] ?? ''); ?>"
                                                data-staff="<?php echo $e($slot['staff_id'] ?? ''); ?>"
                                                data-room="<?php echo $e($slot['classroom'] ?? ''); ?>"
                                                data-start="<?php echo $e($slot['start_date'] ?? ''); ?>"
                                                data-end="<?php echo $e($slot['end_date'] ?? ''); ?>"
                                                data-type="<?php echo $e($slot['session_type'] ?? 'Theory'); ?>"
                                                data-venue="<?php echo $e($slot['venue_type'] ?? ''); ?>"
                                                data-from="<?php echo $e(($slot['start_time'] ?? '') !== '' && $slot['start_time'] !== null ? substr((string) $slot['start_time'], 0, 5) : ''); ?>"
                                                data-to="<?php echo $e(($slot['end_time'] ?? '') !== '' && $slot['end_time'] !== null ? substr((string) $slot['end_time'], 0, 5) : ''); ?>">Change</button>
                                            <?php if (!$isDraft): ?>
                                            <form method="post" action="<?php echo $e($app . '/groups/timetable-publish'); ?>" class="d-inline" onsubmit="return confirm('Move this class back to draft? It will no longer appear for attendance, diary or students.');">
                                                <input type="hidden" name="group_id" value="<?php echo (int) ($group['id'] ?? 0); ?>">
                                                <input type="hidden" name="slot_id" value="<?php echo (int) $slot['timetable_id']; ?>">
                                                <input type="hidden" name="action" value="unpublish">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">Unpublish</button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="post" action="<?php echo $e($app . '/groups/timetable-delete'); ?>" class="d-inline" onsubmit="return confirm('Remove this class from the timetable?');">
                                                <input type="hidden" name="group_id" value="<?php echo (int) ($group['id'] ?? 0); ?>">
                                                <input type="hidden" name="slot_id" value="<?php echo (int) $slot['timetable_id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" action="<?php echo $e($app . '/groups/timetable-save'); ?>" class="tt-form" id="ttForm">
        <input type="hidden" name="group_id" value="<?php echo (int) ($group['id'] ?? 0); ?>">
        <input type="hidden" name="slot_id" id="ttSlotId" value="">
        <div class="tt-field">
            <label for="ttDay">Day</label>
            <select class="form-select" id="ttDay" name="weekday" required>
                <?php foreach ($days as $dayNo => $dayName): ?>
                    <option value="<?php echo (int) $dayNo; ?>"><?php echo $e($dayName); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tt-field">
            <label for="ttPeriod">Period</label>
            <select class="form-select" id="ttPeriod" name="period" required>
                <?php foreach ($periods as $periodCode => $periodLabel): ?>
                    <option value="<?php echo $e($periodCode); ?>"><?php echo $e($periodLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tt-field">
            <label for="ttSubject">Class</label>
            <?php if (!empty($timetableModules)): ?>
                <select class="form-select mb-1" id="ttModule" name="module_id">
                    <option value="">No module</option>
                    <?php foreach ($timetableModules as $module): ?>
                        <option value="<?php echo $e($module['module_id'] ?? ''); ?>">
                            <?php echo $e(($module['module_name'] ?? '') . ((string) ($module['semester'] ?? '') !== '' ? ' · Sem ' . $module['semester'] : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="hidden" name="module_id" id="ttModule" value="">
            <?php endif; ?>
            <input class="form-control" id="ttSubject" name="subject" maxlength="120" placeholder="<?php echo empty($timetableModules) ? 'Class name' : 'Class name, if no module'; ?>">
        </div>
        <div class="tt-field">
            <label for="ttStaff">Lecturer</label>
            <select class="form-select" id="ttStaff" name="staff_id" required>
                <option value="">Choose</option>
                <?php foreach ($timetableStaff as $staff): ?>
                    <option value="<?php echo $e($staff['staff_id'] ?? ''); ?>"><?php echo $e($staff['staff_name'] ?? $staff['staff_id'] ?? ''); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tt-field">
            <label for="ttRoom">Room</label>
            <input class="form-control" id="ttRoom" name="classroom" maxlength="64" placeholder="Optional">
        </div>
        <div class="tt-field">
            <label for="ttStart">From</label>
            <input class="form-control" type="date" id="ttStart" name="start_date" required>
        </div>
        <div class="tt-field">
            <label for="ttEnd">To</label>
            <input class="form-control" type="date" id="ttEnd" name="end_date" required>
        </div>
        <div class="tt-field">
            <label for="ttType">Session type</label>
            <select class="form-select" id="ttType" name="session_type">
                <?php foreach (GroupTimetableModel::sessionTypes() as $t): ?>
                    <option value="<?php echo $e($t); ?>"><?php echo $e($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tt-field">
            <label for="ttVenue">Venue type</label>
            <select class="form-select" id="ttVenue" name="venue_type">
                <option value="">Not specified</option>
                <?php foreach (GroupTimetableModel::venueTypes() as $t): ?>
                    <option value="<?php echo $e($t); ?>"><?php echo $e($t); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="tt-field">
            <label for="ttFrom">Start time (optional)</label>
            <input class="form-control" type="time" id="ttFrom" name="start_time">
        </div>
        <div class="tt-field">
            <label for="ttTo">End time (optional)</label>
            <input class="form-control" type="time" id="ttTo" name="end_time">
        </div>
        <div class="tt-field tt-submit">
            <span class="tt-label">Action</span>
            <div class="tt-btns">
                <button class="btn btn-primary" type="submit" id="ttSaveBtn">Add class</button>
                <button class="btn btn-outline-secondary d-none" type="button" id="ttCancelBtn">Cancel</button>
            </div>
        </div>
    </form>
    <p class="tt-help">Period times default to the institute settings; set a start and end time only for a non-standard session. New classes are saved as drafts and only published classes are used for attendance, the instructor diary and the student timetable. A class cannot overlap another class of the same group, instructor or classroom.</p>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('ttForm');
    if (!form) return;
    var slot = document.getElementById('ttSlotId');
    var saveBtn = document.getElementById('ttSaveBtn');
    var cancelBtn = document.getElementById('ttCancelBtn');
    function fill(data) {
        slot.value = data.id || '';
        document.getElementById('ttDay').value = data.day || '1';
        document.getElementById('ttPeriod').value = data.period || 'P1';
        var moduleField = document.getElementById('ttModule');
        if (moduleField) moduleField.value = data.module || '';
        document.getElementById('ttSubject').value = data.subject || '';
        document.getElementById('ttStaff').value = data.staff || '';
        document.getElementById('ttRoom').value = data.room || '';
        document.getElementById('ttStart').value = data.start || '';
        document.getElementById('ttEnd').value = data.end || '';
        document.getElementById('ttType').value = data.type || 'Theory';
        document.getElementById('ttVenue').value = data.venue || '';
        document.getElementById('ttFrom').value = data.from || '';
        document.getElementById('ttTo').value = data.to || '';
        saveBtn.textContent = data.id ? 'Save class' : 'Add class';
        cancelBtn.classList.toggle('d-none', !data.id);
    }
    document.querySelectorAll('.tt-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fill({
                id: btn.getAttribute('data-id'),
                day: btn.getAttribute('data-day'),
                period: btn.getAttribute('data-period'),
                module: btn.getAttribute('data-module'),
                subject: btn.getAttribute('data-subject'),
                staff: btn.getAttribute('data-staff'),
                room: btn.getAttribute('data-room'),
                start: btn.getAttribute('data-start'),
                end: btn.getAttribute('data-end'),
                type: btn.getAttribute('data-type'),
                venue: btn.getAttribute('data-venue'),
                from: btn.getAttribute('data-from'),
                to: btn.getAttribute('data-to')
            });
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () { fill({}); });
    }
    form.addEventListener('submit', function (event) {
        var moduleField = document.getElementById('ttModule');
        var subject = document.getElementById('ttSubject').value.trim();
        var moduleId = moduleField ? moduleField.value : '';
        if (!moduleId && !subject) {
            event.preventDefault();
            alert('Type the class name, or choose a module.');
        }
    });
});
</script>
