<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-fingerprint me-2"></i>Staff Attendance (Hikvision)
                </h5>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/attendance/machine" class="btn btn-info btn-sm">
                        <i class="fas fa-server me-1"></i>View Machine Records
                    </a>
                    <button type="button" class="btn btn-light btn-sm" id="testConnectionBtn">
                        <i class="fas fa-plug me-1"></i>Test Connection
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="syncBtn">
                        <i class="fas fa-sync me-1"></i>Sync from Device
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Device Information Card -->
            <div class="card border mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-server me-2"></i>Hikvision Device Information
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (isset($hikvisionConfig)): ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Device IP:</strong> <?php echo htmlspecialchars($hikvisionConfig['host'] ?? 'N/A'); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Port:</strong> <?php echo htmlspecialchars($hikvisionConfig['port'] ?? 'N/A'); ?>
                                (<?php echo isset($hikvisionConfig['ssl']) && $hikvisionConfig['ssl'] ? 'HTTPS' : 'HTTP'; ?>)
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Connection Status:</strong>
                                <?php if ($deviceStatus === 'connected'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Connected
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle me-1"></i>Disconnected
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Username:</strong> <?php echo htmlspecialchars($hikvisionConfig['username'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <?php if ($deviceStatus === 'connected' && !empty($deviceInfo)): ?>
                            <hr>
                            <h6 class="fw-bold mb-3">Device Details:</h6>
                            <div class="row">
                                <?php if (is_array($deviceInfo)): ?>
                                    <!-- Basic Information -->
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary fw-bold mb-2"><i class="fas fa-info-circle me-1"></i>Basic Information</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="ps-0" style="width: 45%;"><strong>Device Name:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['deviceName'] ?? $deviceInfo['DeviceName'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Device ID:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['deviceID'] ?? $deviceInfo['DeviceID'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Model:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['model'] ?? $deviceInfo['Model'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Serial Number:</strong></td>
                                                <td><code><?php echo htmlspecialchars($deviceInfo['serialNumber'] ?? $deviceInfo['SerialNumber'] ?? 'N/A'); ?></code></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>MAC Address:</strong></td>
                                                <td><code><?php echo htmlspecialchars($deviceInfo['macAddress'] ?? $deviceInfo['MacAddress'] ?? 'N/A'); ?></code></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Device Type:</strong></td>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($deviceInfo['deviceType'] ?? $deviceInfo['DeviceType'] ?? 'N/A'); ?></span></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Sub Device Type:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['subDeviceType'] ?? $deviceInfo['SubDeviceType'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Manufacturer:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['manufacturer'] ?? $deviceInfo['Manufacturer'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Production Date:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['productionDate'] ?? $deviceInfo['ProductionDate'] ?? 'N/A'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <!-- Firmware & Version Information -->
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary fw-bold mb-2"><i class="fas fa-code-branch me-1"></i>Firmware & Version</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="ps-0" style="width: 45%;"><strong>Firmware Version:</strong></td>
                                                <td><span class="badge bg-success"><?php echo htmlspecialchars($deviceInfo['firmwareVersion'] ?? $deviceInfo['FirmwareVersion'] ?? 'N/A'); ?></span></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Firmware Release:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['firmwareReleasedDate'] ?? $deviceInfo['FirmwareReleasedDate'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Encoder Version:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['encoderVersion'] ?? $deviceInfo['EncoderVersion'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Encoder Release:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['encoderReleasedDate'] ?? $deviceInfo['EncoderReleasedDate'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>BSP Version:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['bspVersion'] ?? $deviceInfo['BspVersion'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>DSP Version:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['dspVersion'] ?? $deviceInfo['DspVersion'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>OEM Code:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['oemCode'] ?? $deviceInfo['OEMCode'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Market Type:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['marketType'] ?? $deviceInfo['MarketType'] ?? 'N/A'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <!-- Hardware Configuration -->
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary fw-bold mb-2"><i class="fas fa-cog me-1"></i>Hardware Configuration</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="ps-0" style="width: 45%;"><strong>Telecontrol ID:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['telecontrolID'] ?? $deviceInfo['TelecontrolID'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Local Zone Num:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['localZoneNum'] ?? $deviceInfo['LocalZoneNum'] ?? '0'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Alarm Out Num:</strong></td>
                                                <td><?php echo htmlspecialchars($deviceInfo['alarmOutNum'] ?? $deviceInfo['AlarmOutNum'] ?? '0'); ?></td>
                                            </tr>
                                            <tr>
                                                <td class="ps-0"><strong>Electro Lock Num:</strong></td>
                                                <td><span class="badge bg-warning"><?php echo htmlspecialchars($deviceInfo['electroLockNum'] ?? $deviceInfo['ElectroLockNum'] ?? '0'); ?></span></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <!-- Display any other fields not covered above -->
                                    <?php
                                    $displayedFields = [
                                        'deviceName', 'DeviceName', 'deviceID', 'DeviceID', 'model', 'Model',
                                        'serialNumber', 'SerialNumber', 'macAddress', 'MacAddress', 'deviceType', 'DeviceType',
                                        'subDeviceType', 'SubDeviceType', 'manufacturer', 'Manufacturer', 'productionDate', 'ProductionDate',
                                        'firmwareVersion', 'FirmwareVersion', 'firmwareReleasedDate', 'FirmwareReleasedDate',
                                        'encoderVersion', 'EncoderVersion', 'encoderReleasedDate', 'EncoderReleasedDate',
                                        'bspVersion', 'BspVersion', 'dspVersion', 'DspVersion', 'oemCode', 'OEMCode',
                                        'marketType', 'MarketType', 'telecontrolID', 'TelecontrolID',
                                        'localZoneNum', 'LocalZoneNum', 'alarmOutNum', 'AlarmOutNum',
                                        'electroLockNum', 'ElectroLockNum'
                                    ];
                                    $otherFields = [];
                                    foreach ($deviceInfo as $key => $value) {
                                        if (!is_array($value) && !is_object($value) && !in_array($key, $displayedFields)) {
                                            $otherFields[$key] = $value;
                                        }
                                    }
                                    if (!empty($otherFields)):
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary fw-bold mb-2"><i class="fas fa-list me-1"></i>Additional Information</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <?php foreach ($otherFields as $key => $value): ?>
                                                <tr>
                                                    <td class="ps-0" style="width: 45%;"><strong><?php echo htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', $key))); ?>:</strong></td>
                                                    <td><?php echo htmlspecialchars($value); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars(json_encode($deviceInfo, JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Device information not available. Click "Test Connection" to connect and retrieve device details.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Hikvision configuration not found. Please configure the device settings first.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="dateFilter" class="form-label">Select Date</label>
                    <input type="date" class="form-control" id="dateFilter" value="<?php echo htmlspecialchars($selectedDate ?? date('Y-m-d')); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" onclick="filterByDate()">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </div>
            
            <!-- Machine Users with Attendance -->
            <?php if ($deviceStatus === 'connected' && !empty($usersWithAttendance)): ?>
                <div class="card border">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0 fw-bold">
                            <i class="fas fa-users me-2"></i>Machine Users & Attendance for <?php echo htmlspecialchars(date('F d, Y', strtotime($selectedDate))); ?>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee No.</th>
                                        <th>Name</th>
                                        <th>Card No.</th>
                                        <th>User Type</th>
                                        <th>Status</th>
                                        <th>Check-In Time</th>
                                        <th>Check-Out Time</th>
                                        <th>Attendance Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usersWithAttendance as $user): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($user['employee_no']); ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['card_no'] ?: '-'); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($user['user_type'] ?? 'normal'); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($user['valid']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($user['has_attendance']): ?>
                                                <td><?php echo htmlspecialchars($user['check_in_time'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($user['check_out_time'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge bg-success">Present</span>
                                                </td>
                                            <?php else: ?>
                                                <td class="text-muted">-</td>
                                                <td class="text-muted">-</td>
                                                <td>
                                                    <span class="badge bg-warning">No Record</span>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">
                            Total Users: <?php echo count($usersWithAttendance); ?> | 
                            With Attendance: <?php echo count(array_filter($usersWithAttendance, function($u) { return $u['has_attendance']; })); ?> | 
                            Without Attendance: <?php echo count(array_filter($usersWithAttendance, function($u) { return !$u['has_attendance']; })); ?>
                        </small>
                    </div>
                </div>
            <?php elseif ($deviceStatus === 'connected' && empty($deviceUsers)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Device is connected but no users found. Please check the device configuration.
                </div>
            <?php elseif ($deviceStatus === 'disconnected'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    Device is not connected. Please check the device connection and configuration.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No users found in the device.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Sync Modal -->
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sync from Hikvision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="syncStartDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="syncStartDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="mb-3">
                    <label for="syncEndDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="syncEndDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div id="syncStatus" class="alert" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSyncBtn">
                    <i class="fas fa-sync me-1"></i>Sync Now
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function filterByDate() {
    const date = document.getElementById('dateFilter').value;
    if (date) {
        window.location.href = '<?php echo APP_URL; ?>/attendance/staff?date=' + date;
    }
}

// Test Connection
document.getElementById('testConnectionBtn').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
    
    fetch('<?php echo APP_URL; ?>/attendance/test-hikvision', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection successful!\n\nDevice: ' + (data.device_info?.deviceName || data.device_info?.model || 'Connected'));
            // Reload page to show updated device info
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Connection failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

// Sync Button
document.getElementById('syncBtn').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('syncModal'));
    modal.show();
});

// Confirm Sync
document.getElementById('confirmSyncBtn').addEventListener('click', function() {
    const btn = this;
    const statusDiv = document.getElementById('syncStatus');
    const startDate = document.getElementById('syncStartDate').value;
    const endDate = document.getElementById('syncEndDate').value;
    
    if (!startDate || !endDate) {
        statusDiv.className = 'alert alert-danger';
        statusDiv.innerHTML = 'Please select both start and end dates.';
        statusDiv.style.display = 'block';
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing...';
    statusDiv.style.display = 'none';
    
    fetch('<?php echo APP_URL; ?>/attendance/sync-hikvision', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate)
    })
    .then(response => response.json())
    .then(data => {
        statusDiv.style.display = 'block';
        if (data.success) {
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.error || 'Sync failed');
        }
    })
    .catch(error => {
        statusDiv.style.display = 'block';
        statusDiv.className = 'alert alert-danger';
        statusDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error: ' + error.message;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync me-1"></i>Sync Now';
    });
});
</script>

