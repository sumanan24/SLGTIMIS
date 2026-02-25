<?php
$group_id = $group_id ?? '';
$group = $group ?? null;
$entry = $entry ?? null;
$days = $days ?? ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$sessionTypes = $sessionTypes ?? ['Theory' => 'Theory', 'Practical' => 'Practical'];
$timeSlots = $timeSlots ?? [];
$modules = $modules ?? [];
$staff = $staff ?? [];
$defaultDay = $defaultDay ?? '';
$defaultTimeSlot = $defaultTimeSlot ?? '';
?>
<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create Timetable Entry<?php echo $group ? ' - ' . htmlspecialchars($group['name']) : ''; ?></h5>
                <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($group_id); ?>" class="btn btn-light btn-sm">Back to Timetable</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error); ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($group_id === '' || !$group): ?>
                <p class="text-muted">Open this page with <strong>group_id</strong> in URL, e.g. <code>group-timetable/create?group_id=8</code></p>
            <?php else: ?>
            <form method="post" action="<?php echo APP_URL; ?>/group-timetable/create?group_id=<?php echo urlencode($group_id); ?>">
                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Day</label>
                        <select class="form-select" name="day" required>
                            <option value="">Select day</option>
                            <?php foreach ($days as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($defaultDay === $d) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Time Slot</label>
                        <select class="form-select" name="time_slot" required>
                            <option value="">Select time slot</option>
                            <?php foreach ($timeSlots as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($defaultTimeSlot === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Module</label>
                        <select class="form-select" name="module_id">
                            <option value="">Select Module</option>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?php echo htmlspecialchars((string)($m['module_id'] ?? '')); ?>"><?php echo htmlspecialchars($m['module_name'] ?? $m['module_id'] ?? '—'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Lecturer (Staff)</label>
                        <select class="form-select" name="staff_id">
                            <option value="">Select Lecturer</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?php echo htmlspecialchars((string)($s['staff_id'] ?? '')); ?>"><?php echo htmlspecialchars($s['staff_name'] ?? $s['staff_id'] ?? '—'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subject (optional)</label>
                        <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars($entry['subject'] ?? ''); ?>" placeholder="Subject name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Session Type</label>
                        <select class="form-select" name="session_type">
                            <?php foreach ($sessionTypes as $k => $v): ?>
                                <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Room</label>
                        <input type="text" class="form-control" name="room" value="<?php echo htmlspecialchars($entry['room'] ?? ''); ?>" placeholder="Room / Lab">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button>
                    <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($group_id); ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
