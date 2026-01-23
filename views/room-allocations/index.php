<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0 fw-bold"><i class="fas fa-user-check me-2"></i>Room Allocations</h5>
                <a href="<?php echo APP_URL; ?>/room-allocations/create" class="btn btn-light btn-sm mt-2 mt-md-0">
                    <i class="fas fa-plus me-1"></i>Allocate Room
                </a>
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
            
            <!-- Filters -->
            <div class="card border mb-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="<?php echo APP_URL; ?>/room-allocations" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Hostel</label>
                            <select name="hostel_id" id="filter_hostel_id" class="form-select" onchange="loadRoomsForFilter(this.value)">
                                <option value="">All Hostels</option>
                                <?php foreach ($hostels as $hostel): ?>
                                    <option value="<?php echo htmlspecialchars($hostel['id']); ?>" 
                                            <?php echo ($hostel_id ?? '') == $hostel['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hostel['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Room</label>
                            <select name="room_id" id="filter_room_id" class="form-select">
                                <option value="">All Rooms</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room['id']); ?>" 
                                            <?php echo ($room_id ?? '') == $room['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($room['room_no'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($room['block_name'] ?? 'N/A'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by student name or ID..." 
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <?php if (!empty($search) || !empty($hostel_id) || !empty($room_id) || !empty($status)): ?>
                            <div class="col-12">
                                <a href="<?php echo APP_URL; ?>/room-allocations" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (isset($roomWiseView) && $roomWiseView): ?>
                <!-- Room-wise Card View -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing rooms for selected hostel
                    </div>
                </div>

                <?php if (!empty($roomAllocations)): ?>
                    <div class="row g-4">
                        <?php foreach ($roomAllocations as $roomData): 
                            $room = $roomData['room'];
                            $roomAllocs = $roomData['allocations'];
                            $occupied = count(array_filter($roomAllocs, function($a) { return ($a['status'] ?? '') === 'active'; }));
                            $capacity = $room['capacity'] ?? 0;
                            $available = $capacity - $occupied;
                            $occupancyPercent = $capacity > 0 ? ($occupied / $capacity) * 100 : 0;
                        ?>
                            <div class="col-6">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-header bg-primary text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-bold">
                                                    <i class="fas fa-door-open me-2"></i>
                                                    Room <?php echo htmlspecialchars($room['room_no'] ?? 'N/A'); ?>
                                                </h6>
                                                <small class="opacity-75">
                                                    <?php echo htmlspecialchars($room['block_name'] ?? 'N/A'); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-light text-dark">
                                                <?php echo $occupied; ?>/<?php echo $capacity; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small">Occupancy</span>
                                                <span class="fw-bold small">
                                                    <span class="text-warning"><?php echo $occupied; ?></span> / 
                                                    <span class="text-success"><?php echo $available; ?></span>
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar 
                                                    <?php echo $occupancyPercent >= 100 ? 'bg-danger' : ($occupancyPercent >= 80 ? 'bg-warning' : 'bg-success'); ?>" 
                                                    role="progressbar" 
                                                    style="width: <?php echo min($occupancyPercent, 100); ?>%"
                                                    aria-valuenow="<?php echo $occupancyPercent; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border-top pt-3">
                                            <h6 class="fw-bold mb-3">
                                                <i class="fas fa-users me-2"></i>
                                                Students (<?php echo count($roomAllocs); ?>)
                                            </h6>
                                            
                                            <?php if (!empty($roomAllocs)): ?>
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($roomAllocs as $alloc): 
                                                        if (!empty($status) && ($alloc['status'] ?? '') !== $status) continue;
                                                    ?>
                                                        <div class="list-group-item px-0 py-2 border-bottom">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div class="flex-grow-1">
                                                                    <div class="mb-1">
                                                                        <label class="form-label small text-muted mb-0">Student Name</label>
                                                                        <div class="fw-semibold text-primary">
                                                                            <?php echo htmlspecialchars($alloc['student_fullname'] ?? 'N/A'); ?>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <label class="form-label small text-muted mb-0">Student ID</label>
                                                                        <div class="small">
                                                                            <?php echo htmlspecialchars($alloc['student_id'] ?? 'N/A'); ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="ms-3 d-flex align-items-center gap-2">
                                                                    <span class="badge <?php echo (($alloc['status'] ?? 'active') === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                                                        <?php echo htmlspecialchars(ucfirst($alloc['status'] ?? 'active')); ?>
                                                                    </span>
                                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#studentModal<?php echo $alloc['id'] ?? ''; ?>"
                                                                            title="View Full Details">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Student Details Modal -->
                                                        <div class="modal fade" id="studentModal<?php echo $alloc['id'] ?? ''; ?>" tabindex="-1" aria-labelledby="studentModalLabel<?php echo $alloc['id'] ?? ''; ?>" aria-hidden="true">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-primary text-white">
                                                                        <h5 class="modal-title" id="studentModalLabel<?php echo $alloc['id'] ?? ''; ?>">
                                                                            <i class="fas fa-user me-2"></i>Student Details
                                                                        </h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="row g-3">
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Student Name</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <?php echo htmlspecialchars($alloc['student_fullname'] ?? 'N/A'); ?>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Student ID</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <?php echo htmlspecialchars($alloc['student_id'] ?? 'N/A'); ?>
                                                                                </div>
                                                                            </div>
                                                                            <?php if (!empty($alloc['student_email'])): ?>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Email</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <i class="fas fa-envelope me-1 text-muted"></i>
                                                                                    <?php echo htmlspecialchars($alloc['student_email']); ?>
                                                                                </div>
                                                                            </div>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($alloc['student_nic'])): ?>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">NIC</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <i class="fas fa-id-card me-1 text-muted"></i>
                                                                                    <?php echo htmlspecialchars($alloc['student_nic']); ?>
                                                                                </div>
                                                                            </div>
                                                                            <?php endif; ?>
                                                                            <?php if (!empty($alloc['student_gender'])): ?>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Gender</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <?php echo htmlspecialchars(ucfirst($alloc['student_gender'])); ?>
                                                                                </div>
                                                                            </div>
                                                                            <?php endif; ?>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Hostel</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <?php echo htmlspecialchars($alloc['hostel_name'] ?? 'N/A'); ?>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Block</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <?php echo htmlspecialchars($alloc['block_name'] ?? 'N/A'); ?>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Room Number</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <span class="badge bg-info bg-opacity-10 text-info">
                                                                                        <?php echo htmlspecialchars($alloc['room_no'] ?? 'N/A'); ?>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Status</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <span class="badge <?php echo (($alloc['status'] ?? 'active') === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                                                                        <?php echo htmlspecialchars(ucfirst($alloc['status'] ?? 'active')); ?>
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                            <?php if (!empty($alloc['allocated_at'])): ?>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label fw-bold text-muted">Allocated Date</label>
                                                                                <div class="form-control-plaintext">
                                                                                    <i class="fas fa-calendar me-1 text-muted"></i>
                                                                                    <?php 
                                                                                    $allocDate = $alloc['allocated_at'];
                                                                                    if (is_numeric($allocDate)) {
                                                                                        echo date('Y-m-d', (int)$allocDate);
                                                                                    } else {
                                                                                        $date = strtotime($allocDate);
                                                                                        echo $date ? date('Y-m-d', $date) : htmlspecialchars($allocDate);
                                                                                    }
                                                                                    ?>
                                                                                </div>
                                                                            </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center py-3 text-muted">
                                                    <i class="fas fa-user-slash fa-2x mb-2"></i>
                                                    <p class="mb-0 small">No students allocated</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No rooms found for this hostel.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Regular Table View -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <strong><?php echo count($allocations ?? []); ?></strong> of <strong><?php echo number_format($total ?? 0); ?></strong> allocations
                    </div>
                </div>

                <?php if (!empty($allocations)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Student</th>
                                <th class="fw-bold">Student ID</th>
                                <th class="fw-bold">Hostel</th>
                                <th class="fw-bold">Block</th>
                                <th class="fw-bold">Room</th>
                                <th class="fw-bold">Allocated Date</th>
                                <th class="fw-bold">Status</th>
                                <th class="fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocations as $allocation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($allocation['student_fullname'] ?? 'N/A'); ?></td>
                                    <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($allocation['student_id'] ?? 'N/A'); ?></span></td>
                                    <td><?php echo htmlspecialchars($allocation['hostel_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($allocation['block_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo htmlspecialchars($allocation['room_no'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($allocation['allocated_at'])) {
                                            // Check if it's a timestamp (integer) or date string
                                            if (is_numeric($allocation['allocated_at'])) {
                                                echo date('Y-m-d', (int)$allocation['allocated_at']);
                                            } else {
                                                // Already a date string, just format it
                                                $date = strtotime($allocation['allocated_at']);
                                                echo $date ? date('Y-m-d', $date) : htmlspecialchars($allocation['allocated_at']);
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo (($allocation['status'] ?? 'active') === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo htmlspecialchars(ucfirst($allocation['status'] ?? 'active')); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo APP_URL; ?>/room-allocations/edit?id=<?php echo urlencode($allocation['id'] ?? ''); ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (($allocation['status'] ?? '') === 'active'): ?>
                                                <a href="<?php echo APP_URL; ?>/room-allocations/deallocate?id=<?php echo urlencode($allocation['id'] ?? ''); ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Deallocate" 
                                                   onclick="return confirm('Are you sure you want to deallocate this room?');">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo APP_URL; ?>/room-allocations/delete?id=<?php echo urlencode($allocation['id'] ?? ''); ?>" 
                                               class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($hostel_id) ? '&hostel_id=' . urlencode($hostel_id) : ''; ?><?php echo !empty($room_id) ? '&room_id=' . urlencode($room_id) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($hostel_id) ? '&hostel_id=' . urlencode($hostel_id) : ''; ?><?php echo !empty($room_id) ? '&room_id=' . urlencode($room_id) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($hostel_id) ? '&hostel_id=' . urlencode($hostel_id) : ''; ?><?php echo !empty($room_id) ? '&room_id=' . urlencode($room_id) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No room allocations found.</p>
                        <a href="<?php echo APP_URL; ?>/room-allocations/create" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create First Allocation
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function loadRoomsForFilter(hostelId) {
    const roomSelect = document.getElementById('filter_room_id');
    roomSelect.innerHTML = '<option value="">Loading...</option>';
    
    if (!hostelId) {
        roomSelect.innerHTML = '<option value="">All Rooms</option>';
        return;
    }
    
    fetch('<?php echo APP_URL; ?>/room-allocations/get-available-rooms?hostel_id=' + hostelId)
        .then(response => response.json())
        .then(data => {
            roomSelect.innerHTML = '<option value="">All Rooms</option>';
            data.forEach(room => {
                const option = document.createElement('option');
                option.value = room.id;
                option.textContent = (room.room_no || 'N/A') + ' - ' + (room.block_name || 'N/A');
                roomSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
        });
}

// Load rooms on page load if hostel is selected
document.addEventListener('DOMContentLoaded', function() {
    const hostelSelect = document.getElementById('filter_hostel_id');
    if (hostelSelect && hostelSelect.value) {
        loadRoomsForFilter(hostelSelect.value);
    }
});
</script>

