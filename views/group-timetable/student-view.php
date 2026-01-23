<div class="container-fluid px-4 py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i>My Timetable</h5>
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
            
            <?php if (!empty($timetables)): ?>
                <?php
                // Group timetables by group name
                $groupedTimetables = [];
                foreach ($timetables as $timetable) {
                    $groupName = $timetable['group_name'] ?? 'Unknown Group';
                    if (!isset($groupedTimetables[$groupName])) {
                        $groupedTimetables[$groupName] = [];
                    }
                    $groupedTimetables[$groupName][] = $timetable;
                }
                
                // Weekday order
                $weekdayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                ?>
                
                <?php foreach ($groupedTimetables as $groupName => $groupTimetables): ?>
                    <div class="card border mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($groupName); ?></h6>
                        </div>
                        <div class="card-body">
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Sort by weekday and period
                                        usort($groupTimetables, function($a, $b) use ($weekdayOrder) {
                                            $weekdayA = array_search($a['weekday'], $weekdayOrder);
                                            $weekdayB = array_search($b['weekday'], $weekdayOrder);
                                            if ($weekdayA === false) $weekdayA = 999;
                                            if ($weekdayB === false) $weekdayB = 999;
                                            
                                            if ($weekdayA != $weekdayB) {
                                                return $weekdayA - $weekdayB;
                                            }
                                            return strcmp($a['period'] ?? '', $b['period'] ?? '');
                                        });
                                        ?>
                                        <?php foreach ($groupTimetables as $timetable): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($timetable['weekday']); ?></td>
                                                <td>
                                                    <?php 
                                                    $period = $timetable['period'] ?? '';
                                                    // Format period display - if it's already formatted, use it; otherwise format it
                                                    if (strpos($period, '-') !== false && strpos($period, ':') !== false) {
                                                        // Format like "08:30-10:00" to "08:30 - 10:00"
                                                        $periodParts = explode('-', $period);
                                                        if (count($periodParts) == 2) {
                                                            echo htmlspecialchars(trim($periodParts[0]) . ' - ' . trim($periodParts[1]));
                                                        } else {
                                                            echo htmlspecialchars($period);
                                                        }
                                                    } else {
                                                        echo htmlspecialchars($period);
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($timetable['module_id'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($timetable['staff_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($timetable['classroom'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($timetable['start_date'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($timetable['end_date'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">No timetable entries found for your groups.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

