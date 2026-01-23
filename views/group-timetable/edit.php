<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i>Edit Timetable Entry</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/group-timetable/edit?id=<?php echo urlencode($timetable['timetable_id']); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="weekday" class="form-label fw-semibold">
                                    Weekday <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="weekday" name="weekday" required>
                                    <option value="">Select Weekday</option>
                                    <?php foreach ($weekdays as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" 
                                                <?php echo ($timetable['weekday'] === $key) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="period" class="form-label fw-semibold">
                                    Period <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="period" name="period" required>
                                    <option value="">Select Period</option>
                                    <?php foreach ($periods as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" 
                                                <?php echo ($timetable['period'] === $key) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="module_id" class="form-label fw-semibold">Module ID</label>
                                <select class="form-select" id="module_id" name="module_id">
                                    <option value="">Select Module</option>
                                    <?php if (!empty($modules)): ?>
                                        <?php foreach ($modules as $moduleId): ?>
                                            <option value="<?php echo htmlspecialchars($moduleId); ?>" 
                                                    <?php echo (isset($timetable['module_id']) && $timetable['module_id'] === $moduleId) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($moduleId); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="staff_id" class="form-label fw-semibold">Staff</label>
                                <select class="form-select" id="staff_id" name="staff_id">
                                    <option value="">Select Staff</option>
                                    <?php foreach ($staff as $staffMember): ?>
                                        <option value="<?php echo htmlspecialchars($staffMember['staff_id']); ?>" 
                                                <?php echo ($timetable['staff_id'] === $staffMember['staff_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($staffMember['staff_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="classroom" class="form-label fw-semibold">Classroom</label>
                            <input type="text" class="form-control" id="classroom" name="classroom" 
                                   value="<?php echo htmlspecialchars($timetable['classroom'] ?? ''); ?>"
                                   placeholder="Enter classroom">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label fw-semibold">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo htmlspecialchars($timetable['start_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label fw-semibold">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo htmlspecialchars($timetable['end_date'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="active" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="active" name="active">
                                <option value="1" <?php echo ($timetable['active'] == 1) ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo ($timetable['active'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Entry
                            </button>
                            <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($timetable['group_id']); ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

