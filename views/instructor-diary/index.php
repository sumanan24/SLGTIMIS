<?php
$staffId = $staffId ?? '';
$enrollments = $enrollments ?? [];
$entries = $entries ?? [];
$filters = $filters ?? ['from_date' => '', 'to_date' => '', 'module_id' => ''];
$editingEntry = $editingEntry ?? null;
$isEditMode = !empty($editingEntry);
?>

<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-book-open me-2"></i>Instructor Diary
                </h5>
                <span class="small">Staff ID: <?php echo htmlspecialchars($staffId); ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row g-3">
                <div class="col-12 col-lg-5">
                    <div class="border rounded p-3 h-100">
                        <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-pen me-1 text-primary"></i>
                                <?php echo $isEditMode ? 'Edit Diary Entry' : 'New Diary Entry'; ?>
                            </span>
                            <?php if ($isEditMode): ?>
                                <a href="<?php echo APP_URL; ?>/instructor-diary" class="btn btn-sm btn-outline-secondary">
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </h6>
                        <form action="<?php echo APP_URL; ?>/instructor-diary/create" method="post" class="row g-2">
                            <input type="hidden" name="action" value="<?php echo $isEditMode ? 'update' : 'create'; ?>">
                            <?php if ($isEditMode): ?>
                                <input type="hidden" name="diary_id" value="<?php echo htmlspecialchars($editingEntry['instructor_diary_id']); ?>">
                            <?php endif; ?>
                            <div class="col-12">
                                <label class="form-label">Module</label>
                                <select name="staff_module_enrollment_id" class="form-select" required>
                                    <option value="">-- Select Module --</option>
                                    <?php foreach ($enrollments as $e): ?>
                                        <option value="<?php echo htmlspecialchars($e['staff_module_enrollment_id']); ?>"
                                            <?php
                                                if ($isEditMode && (int)$editingEntry['staff_module_enrollment_id'] === (int)$e['staff_module_enrollment_id']) {
                                                    echo 'selected';
                                                }
                                            ?>>
                                            <?php echo htmlspecialchars(($e['module_name'] ?? '') . ' (' . ($e['course_name'] ?? '') . ', ' . ($e['academic_year'] ?? '') . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Date</label>
                                <input type="date" name="diary_date" class="form-control"
                                       value="<?php echo htmlspecialchars($isEditMode ? $editingEntry['diary_date'] : date('Y-m-d')); ?>" required>
                            </div>
                            <div class="col-3">
                                <label class="form-label">From</label>
                                <input type="time" name="start_time" class="form-control"
                                       value="<?php echo htmlspecialchars($isEditMode ? substr($editingEntry['start_time'], 0, 5) : ''); ?>" required>
                            </div>
                            <div class="col-3">
                                <label class="form-label">To</label>
                                <input type="time" name="end_time" class="form-control"
                                       value="<?php echo htmlspecialchars($isEditMode ? substr($editingEntry['end_time'], 0, 5) : ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Topic Covered</label>
                                <textarea name="topic_covered" rows="3" class="form-control" required><?php echo htmlspecialchars($isEditMode ? $editingEntry['topic_covered'] : ''); ?></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>
                                    <?php echo $isEditMode ? 'Update Entry' : 'Save Entry'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-12 col-lg-7">
                    <div class="border rounded p-3 h-100">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-list me-1 text-primary"></i>Recent Entries
                        </h6>
                        
                        <form method="get" class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label">From</label>
                                <input type="date" name="from_date" value="<?php echo htmlspecialchars($filters['from_date'] ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-4">
                                <label class="form-label">To</label>
                                <input type="date" name="to_date" value="<?php echo htmlspecialchars($filters['to_date'] ?? ''); ?>" class="form-control">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Module</label>
                                <select name="module_id" class="form-select">
                                    <option value="">All</option>
                                    <?php foreach ($enrollments as $e): ?>
                                        <option value="<?php echo htmlspecialchars($e['module_id']); ?>"
                                            <?php echo (($filters['module_id'] ?? '') === $e['module_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($e['module_name'] ?? $e['module_id']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                                <a href="<?php echo APP_URL; ?>/instructor-diary" class="btn btn-sm btn-outline-secondary">
                                    Clear
                                </a>
                            </div>
                        </form>
                        
                        <?php if (!empty($entries)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Module</th>
                                            <th>Topic</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($entries as $row): ?>
                                            <tr>
                                                <td class="small"><?php echo htmlspecialchars($row['diary_date']); ?></td>
                                                <td class="small">
                                                    <?php echo htmlspecialchars(substr($row['start_time'], 0, 5)); ?>
                                                    &ndash;
                                                    <?php echo htmlspecialchars(substr($row['end_time'], 0, 5)); ?>
                                                </td>
                                                <td class="small text-truncate" style="max-width: 160px;" title="<?php echo htmlspecialchars($row['module_name'] ?? $row['module_id']); ?>">
                                                    <?php echo htmlspecialchars($row['module_name'] ?? $row['module_id']); ?>
                                                </td>
                                                <td class="small text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($row['topic_covered']); ?>">
                                                    <?php echo htmlspecialchars($row['topic_covered']); ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="<?php echo APP_URL; ?>/instructor-diary?edit_id=<?php echo htmlspecialchars($row['instructor_diary_id']); ?>"
                                                           class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="<?php echo APP_URL; ?>/instructor-diary/create" method="post" onsubmit="return confirm('Delete this entry?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="diary_id" value="<?php echo htmlspecialchars($row['instructor_diary_id']); ?>">
                                                            <button type="submit" class="btn btn-outline-danger">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">No diary entries found for the selected filters.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

