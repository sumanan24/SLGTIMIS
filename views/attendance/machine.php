<?php
/**
 * Machine Attendance Records View
 * Displays attendance records directly from Hikvision device
 */

if (!isset($title)) {
    $title = 'Machine Attendance Records';
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-fingerprint me-2"></i>Machine Attendance Records
                        </h5>
                        <a href="<?php echo APP_URL; ?>/attendance/staff" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Staff Attendance
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($message) && $message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error) && $error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Device Information Card -->
                    <div class="card border mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>Device Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Device IP:</strong> <?php echo htmlspecialchars($hikvisionConfig['host'] ?? 'N/A'); ?></p>
                                    <p class="mb-1"><strong>Port:</strong> <?php echo htmlspecialchars($hikvisionConfig['port'] ?? 'N/A'); ?> (<?php echo ($hikvisionConfig['ssl'] ?? false) ? 'HTTPS' : 'HTTP'; ?>)</p>
                                    <p class="mb-1"><strong>Connection Status:</strong>
                                        <?php if ($deviceStatus === 'connected'): ?>
                                            <span class="badge bg-success">Connected</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Disconnected</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($deviceInfo && is_array($deviceInfo)): ?>
                                        <?php if (isset($deviceInfo['model'])): ?>
                                            <p class="mb-1"><strong>Model:</strong> <?php echo htmlspecialchars($deviceInfo['model']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($deviceInfo['firmwareVersion'])): ?>
                                            <p class="mb-1"><strong>Firmware:</strong> <?php echo htmlspecialchars($deviceInfo['firmwareVersion']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($deviceInfo['serialNumber'])): ?>
                                            <p class="mb-1"><strong>Serial:</strong> <?php echo htmlspecialchars($deviceInfo['serialNumber']); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Device Users Card -->
                    <?php if ($deviceStatus === 'connected' && !empty($deviceUsers)): ?>
                        <div class="card border mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fas fa-users me-2"></i>Registered Users in Device (<?php echo count($deviceUsers); ?>)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Employee No.</th>
                                                <th>Name</th>
                                                <th>Card No.</th>
                                                <th>User Type</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($deviceUsers as $user): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($user['employee_no'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['card_no'] ?? '-'); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo htmlspecialchars($user['user_type'] ?? 'normal'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (isset($user['valid']) && $user['valid']): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($deviceStatus === 'connected' && empty($deviceUsers)): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Connected to device, but no users found. Please check the device configuration.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Filter Form -->
                    <div class="card border mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2"></i>Filter Records</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="<?php echo APP_URL; ?>/attendance/machine" class="row g-3">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo htmlspecialchars($startDate ?? date('Y-m-d', strtotime('-7 days'))); ?>" 
                                           max="<?php echo date('Y-m-d'); ?>"
                                           required>
                                    <small class="text-muted">Select a date in the past or today</small>
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo htmlspecialchars($endDate ?? date('Y-m-d')); ?>" 
                                           max="<?php echo date('Y-m-d'); ?>"
                                           required>
                                    <small class="text-muted">Select a date in the past or today</small>
                                </div>
                                <div class="col-md-4">
                                    <label for="employee_id" class="form-label">Employee ID (Optional)</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                           value="<?php echo htmlspecialchars($employeeId ?? ''); ?>" 
                                           placeholder="Leave empty for all employees">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>Search
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Attendance Records Table -->
                    <?php if (!empty($machineRecords)): ?>
                        <div class="card border">
                            <div class="card-header bg-info text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fas fa-list me-2"></i>Attendance Records 
                                        (<?php echo count($machineRecords); ?> records found)
                                    </h6>
                                    <a href="<?php echo APP_URL; ?>/attendance/export-machine?start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?><?php echo !empty($employeeId) ? '&employee_id=' . urlencode($employeeId) : ''; ?>" 
                                       class="btn btn-light btn-sm">
                                        <i class="fas fa-download me-1"></i>Export CSV
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Employee ID</th>
                                                <th>Card No.</th>
                                                <th>Staff Name</th>
                                                <th>Type</th>
                                                <th>Device Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($machineRecords as $record): ?>
                                                <?php 
                                                $employeeId = $record['employee_id'] ?? $record['employee_no'] ?? '';
                                                $staffInfo = null;
                                                if (!empty($employeeId)) {
                                                    $staffInfo = $staffMap[$employeeId] ?? null;
                                                    // Also try matching by staff_nic
                                                    if (!$staffInfo) {
                                                        foreach ($staffMap as $key => $staff) {
                                                            if (is_array($staff) && isset($staff['staff_nic']) && $staff['staff_nic'] == $employeeId) {
                                                                $staffInfo = $staff;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                                $recordDate = $record['date'] ?? '';
                                                $recordTime = $record['time'] ?? '';
                                                // Extract just time part if it's a datetime
                                                if (preg_match('/(\d{2}:\d{2}:\d{2})/', $recordTime, $matches)) {
                                                    $recordTime = $matches[1];
                                                }
                                                $recordType = $record['type'] ?? $record['event_description'] ?? '';
                                                $typeLabel = '';
                                                $typeBadge = '';
                                                if ($recordType == '1' || $recordType == 1 || strtolower($recordType) == 'check-in' || stripos($recordType, 'check-in') !== false) {
                                                    $typeLabel = 'Check-In';
                                                    $typeBadge = 'success';
                                                } elseif ($recordType == '2' || $recordType == 2 || strtolower($recordType) == 'check-out' || stripos($recordType, 'check-out') !== false) {
                                                    $typeLabel = 'Check-Out';
                                                    $typeBadge = 'warning';
                                                } else {
                                                    $typeLabel = ucfirst($recordType ?: 'Unknown');
                                                    $typeBadge = 'secondary';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($recordDate); ?></td>
                                                    <td><?php echo htmlspecialchars($recordTime); ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($employeeId); ?></td>
                                                    <td><?php echo htmlspecialchars($record['card_no'] ?? '-'); ?></td>
                                                    <td>
                                                        <?php if ($staffInfo && is_array($staffInfo)): ?>
                                                            <?php echo htmlspecialchars($staffInfo['staff_name'] ?? 'N/A'); ?>
                                                            <?php if (!empty($staffInfo['department_name'])): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($staffInfo['department_name']); ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not found in system</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $typeBadge; ?>"><?php echo htmlspecialchars($typeLabel); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($record['device_name'] ?? $record['device_id'] ?? '-'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($deviceStatus === 'connected'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No attendance records found for the specified date range and filters.
                        </div>
                        <?php if (isset($debugInfo) && !empty($debugInfo)): ?>
                            <div class="alert alert-secondary mt-3">
                                <h6 class="fw-bold">Debug Information:</h6>
                                <div class="small">
                                    <?php 
                                    $today = date('Y-m-d');
                                    $hasFutureDates = ($startDate > $today || $endDate > $today);
                                    ?>
                                    <strong>Date Range:</strong> <?php echo htmlspecialchars($startDate ?? ''); ?> to <?php echo htmlspecialchars($endDate ?? ''); ?>
                                    <?php if ($hasFutureDates): ?>
                                        <span class="badge bg-warning ms-2">⚠️ Future dates - no records exist for future dates</span>
                                    <?php endif; ?>
                                    <br>
                                    <?php if (isset($debugInfo['endpoints_tried'])): ?>
                                        <strong>Endpoints Tried:</strong> <?php echo htmlspecialchars(implode(', ', $debugInfo['endpoints_tried'])); ?><br>
                                    <?php endif; ?>
                                    <?php if (isset($debugInfo['date_range'])): ?>
                                        <strong>API Request Times:</strong> 
                                        <?php echo htmlspecialchars($debugInfo['date_range']['start_time'] ?? ''); ?> to 
                                        <?php echo htmlspecialchars($debugInfo['date_range']['end_time'] ?? ''); ?><br>
                                    <?php endif; ?>
                                    <?php if (isset($debugInfo['error'])): ?>
                                        <strong>Error:</strong> <?php echo htmlspecialchars($debugInfo['error']); ?><br>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <strong>Tips:</strong>
                                        <ul class="mb-0">
                                            <?php if ($hasFutureDates): ?>
                                                <li><strong class="text-danger">⚠️ The selected dates are in the future. Please select dates in the past or today (max: <?php echo $today; ?>)</strong></li>
                                            <?php endif; ?>
                                            <li>Ensure dates are in the past or today (attendance records are only created after events occur)</li>
                                            <li>Check your PHP error logs for detailed API request/response information</li>
                                            <li>Verify the device has records for this date range using the device's web interface</li>
                                            <li>Try a wider date range (e.g., last 30 days) to see if any records are returned</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Cannot fetch attendance records. Device is not connected.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

