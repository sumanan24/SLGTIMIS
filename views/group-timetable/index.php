<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i>Timetable: <?php echo htmlspecialchars($group['name']); ?></h5>
                <div class="d-flex gap-2">
                    <a href="<?php echo APP_URL; ?>/group-timetable/create?group_id=<?php echo urlencode($group['id']); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i>Add Entry
                    </a>
                    <a href="<?php echo APP_URL; ?>/groups/show?id=<?php echo urlencode($group['id']); ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Group
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Course:</strong> <?php echo htmlspecialchars($group['course_name'] ?? 'N/A'); ?></p>
                    <p class="mb-0"><strong>Department:</strong> <?php echo htmlspecialchars($group['department_name'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Academic Year:</strong> <?php echo htmlspecialchars($group['academic_year'] ?? 'N/A'); ?></p>
                </div>
            </div>
            
            <?php if (!empty($timetables)): ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle mb-0 table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold text-nowrap">Weekday</th>
                                <th class="fw-bold text-nowrap">Time Slot</th>
                                <th class="fw-bold text-nowrap">Module ID</th>
                                <th class="fw-bold text-nowrap">Staff</th>
                                <th class="fw-bold text-nowrap">Classroom</th>
                                <th class="fw-bold text-nowrap">Start Date</th>
                                <th class="fw-bold text-nowrap">End Date</th>
                                <th class="fw-bold text-nowrap text-center">Status</th>
                                <th class="fw-bold text-nowrap text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Weekday to number mapping (0 = Monday, 1 = Tuesday, etc.)
                            $weekdayMap = [
                                'Monday' => 0,
                                'Tuesday' => 1,
                                'Wednesday' => 2,
                                'Thursday' => 3,
                                'Friday' => 4,
                                'Saturday' => 5,
                                'Sunday' => 6
                            ];
                            
                            foreach ($timetables as $timetable): 
                                $weekdayName = $timetable['weekday'] ?? '';
                                $weekdayNumber = isset($weekdayMap[$weekdayName]) ? $weekdayMap[$weekdayName] : $weekdayName;
                                
                                // Get time slot from time_slot column first, fallback to period
                                $timeSlot = $timetable['time_slot'] ?? $timetable['period'] ?? '';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($weekdayNumber); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($timeSlot)) {
                                            // Format time slot display - if it's already formatted, use it; otherwise format it
                                            if (strpos($timeSlot, '-') !== false && strpos($timeSlot, ':') !== false) {
                                                // Format like "08:30-10:00" to "08:30 - 10:00"
                                                $periodParts = explode('-', $timeSlot);
                                                if (count($periodParts) == 2) {
                                                    echo htmlspecialchars(trim($periodParts[0]) . ' - ' . trim($periodParts[1]));
                                                } else {
                                                    echo htmlspecialchars($timeSlot);
                                                }
                                            } else {
                                                echo htmlspecialchars($timeSlot);
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($timetable['module_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($timetable['staff_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($timetable['classroom'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($timetable['start_date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($timetable['end_date'] ?? 'N/A'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo ($timetable['active'] == 1) ? 'success' : 'secondary'; ?> rounded-pill px-3">
                                            <?php echo ($timetable['active'] == 1) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>/group-timetable/edit?id=<?php echo urlencode($timetable['timetable_id']); ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/group-timetable/delete?id=<?php echo urlencode($timetable['timetable_id']); ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this timetable entry?');"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No timetable entries found for this group.</p>
                    <a href="<?php echo APP_URL; ?>/group-timetable/create?group_id=<?php echo urlencode($group['id']); ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add First Entry
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

