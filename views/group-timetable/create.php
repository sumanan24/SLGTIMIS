<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create Timetable - <?php echo htmlspecialchars($group['name']); ?></h5>
                <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($group['id']); ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Timetable
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="mb-4">
                <p class="mb-1"><strong>Group:</strong> <?php echo htmlspecialchars($group['name']); ?></p>
                <p class="mb-0"><strong>Course:</strong> <?php echo htmlspecialchars($group['course_name'] ?? 'N/A'); ?> - <strong>Department:</strong> <?php echo htmlspecialchars($group['department_name'] ?? 'N/A'); ?></p>
            </div>
            
            <form method="POST" action="<?php echo APP_URL; ?>/group-timetable/create?group_id=<?php echo urlencode($group['id']); ?>" id="timetableForm">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" style="min-width: 1200px;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center fw-bold" style="width: 150px;">Time Slot</th>
                                <?php 
                                $weekdaysToShow = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                                foreach ($weekdaysToShow as $day): 
                                ?>
                                    <th class="text-center fw-bold"><?php echo htmlspecialchars($day); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
            <?php 
            // Initialize existingDataMap if not set
            $existingDataMap = $existingDataMap ?? [];
            
            $timeSlots = [
                '08:30-10:00' => '08:30 - 10:00',
                '10:30-12:00' => '10:30 - 12:00',
                '13:00-14:30' => '13:00 - 14:30',
                '14:45-16:15' => '14:45 - 16:15'
            ];
            foreach ($timeSlots as $slotKey => $slotLabel):
                            ?>
                                <tr>
                                    <td class="fw-bold bg-light text-center"><?php echo htmlspecialchars($slotLabel); ?></td>
                                    <?php foreach ($weekdaysToShow as $day): 
                                        // Check if there's existing data for this weekday and period
                                        $dataKey = $day . '_' . $slotKey;
                                        $existingEntry = $existingDataMap[$dataKey] ?? null;
                                    ?>
                                        <td class="p-2" style="vertical-align: top;">
                                            <div class="timetable-cell">
                                                <input type="hidden" name="timetable[<?php echo htmlspecialchars($day); ?>][<?php echo htmlspecialchars($slotKey); ?>][weekday]" value="<?php echo htmlspecialchars($day); ?>">
                                                <input type="hidden" name="timetable[<?php echo htmlspecialchars($day); ?>][<?php echo htmlspecialchars($slotKey); ?>][period]" value="<?php echo htmlspecialchars($slotKey); ?>">
                                                
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1 fw-semibold">Module ID</label>
                                                    <select class="form-select form-select-sm" 
                                                            name="timetable[<?php echo htmlspecialchars($day); ?>][<?php echo htmlspecialchars($slotKey); ?>][module_id]">
                                                        <option value="">Select Module</option>
                                                        <?php if (!empty($modules)): ?>
                                                            <?php foreach ($modules as $moduleId): ?>
                                                                <option value="<?php echo htmlspecialchars($moduleId); ?>" 
                                                                        <?php echo ($existingEntry && isset($existingEntry['module_id']) && $existingEntry['module_id'] === $moduleId) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($moduleId); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1 fw-semibold">Staff</label>
                                                    <select class="form-select form-select-sm" 
                                                            name="timetable[<?php echo htmlspecialchars($day); ?>][<?php echo htmlspecialchars($slotKey); ?>][staff_id]">
                                                        <option value="">Select Staff</option>
                                                        <?php foreach ($staff as $staffMember): ?>
                                                            <option value="<?php echo htmlspecialchars($staffMember['staff_id']); ?>"
                                                                    <?php echo ($existingEntry && isset($existingEntry['staff_id']) && $existingEntry['staff_id'] === $staffMember['staff_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($staffMember['staff_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <label class="form-label small mb-1 fw-semibold">Classroom</label>
                                                    <input type="text" 
                                                           class="form-control form-control-sm" 
                                                           name="timetable[<?php echo htmlspecialchars($day); ?>][<?php echo htmlspecialchars($slotKey); ?>][classroom]" 
                                                           value="<?php echo ($existingEntry && isset($existingEntry['classroom'])) ? htmlspecialchars($existingEntry['classroom']) : ''; ?>"
                                                           placeholder="Room">
                                                </div>
                                                
                                                <?php if ($existingEntry): ?>
                                                    <div class="mt-1">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle"></i> Existing entry
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Timetable
                    </button>
                    <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($group['id']); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.timetable-cell {
    min-height: 180px;
    background-color: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
}

.timetable-cell .form-control-sm,
.timetable-cell .form-select-sm {
    font-size: 0.875rem;
}

.timetable-cell .form-label {
    font-size: 0.75rem;
    color: #495057;
}

.table th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
}
</style>
