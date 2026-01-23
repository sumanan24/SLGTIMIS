<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus me-2"></i>Allocate Room</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/room-allocations/create">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label fw-semibold">
                                    Student ID <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       required placeholder="Enter Student ID">
                                <div class="form-text">Enter the student ID to allocate a room</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="hostel_id" class="form-label fw-semibold">Hostel</label>
                                <select class="form-select" id="hostel_id" name="hostel_id" onchange="loadAvailableRooms(this.value)">
                                    <option value="">Select Hostel</option>
                                    <?php foreach ($hostels as $hostel): ?>
                                        <option value="<?php echo htmlspecialchars($hostel['id']); ?>" 
                                                <?php echo ($hostel_id ?? '') == $hostel['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($hostel['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="room_id" class="form-label fw-semibold">
                                Room <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="room_id" name="room_id" required>
                                <option value="">Select Room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo htmlspecialchars($room['id']); ?>" 
                                            data-available="<?php echo $room['available_beds'] ?? 0; ?>">
                                        <?php echo htmlspecialchars($room['room_no'] ?? 'N/A'); ?> - 
                                        <?php echo htmlspecialchars($room['block_name'] ?? 'N/A'); ?> 
                                        (Available: <?php echo $room['available_beds'] ?? 0; ?> beds)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Only rooms with available beds are shown</div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="<?php echo APP_URL; ?>/room-allocations" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Allocate Room
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadAvailableRooms(hostelId) {
    const roomSelect = document.getElementById('room_id');
    roomSelect.innerHTML = '<option value="">Loading...</option>';
    
    if (!hostelId) {
        roomSelect.innerHTML = '<option value="">Select Room</option>';
        return;
    }
    
    fetch('<?php echo APP_URL; ?>/room-allocations/get-available-rooms?hostel_id=' + hostelId)
        .then(response => response.json())
        .then(data => {
            roomSelect.innerHTML = '<option value="">Select Room</option>';
            data.forEach(room => {
                const option = document.createElement('option');
                option.value = room.id;
                option.textContent = (room.room_no || 'N/A') + ' - ' + (room.block_name || 'N/A') + 
                                   ' (Available: ' + (room.available_beds || 0) + ' beds)';
                option.setAttribute('data-available', room.available_beds || 0);
                roomSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
        });
}
</script>

