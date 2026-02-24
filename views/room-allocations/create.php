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
                                <label for="student_search" class="form-label fw-semibold">
                                    Student <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="text"
                                           class="form-control"
                                           id="student_search"
                                           placeholder="Type to search by name or student ID..."
                                           autocomplete="off">
                                    <input type="hidden" id="student_id" name="student_id" required>
                                    <div id="studentDropdown" class="dropdown-menu w-100" style="display: none; max-height: 260px; overflow-y: auto;">
                                        <?php if (!empty($students ?? [])): ?>
                                            <?php foreach ($students as $student): ?>
                                                <a class="dropdown-item student-option"
                                                   href="#"
                                                   data-student-id="<?php echo htmlspecialchars($student['student_id']); ?>"
                                                   data-student-name="<?php echo htmlspecialchars($student['student_fullname'] ?? ''); ?>"
                                                   data-student-gender="<?php echo htmlspecialchars($student['student_gender'] ?? ''); ?>">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($student['student_fullname'] ?? 'N/A'); ?></div>
                                                    <small class="text-muted">
                                                        ID: <?php echo htmlspecialchars($student['student_id']); ?>
                                                        <?php if (!empty($student['student_gender'])): ?>
                                                            &nbsp;|&nbsp; Gender: <?php echo htmlspecialchars($student['student_gender']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="dropdown-item text-muted">No students found</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small class="text-muted">Start typing to search and select a student. Hostels will be filtered by student gender.</small>
                                <div id="selectedStudentInfo" class="mt-2"></div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="hostel_id" class="form-label fw-semibold">Hostel</label>
                                <select class="form-select" id="hostel_id" name="hostel_id" onchange="loadAvailableRooms(this.value)" disabled>
                                    <option value="">Select Hostel</option>
                                    <?php foreach ($hostels as $hostel): ?>
                                        <option value="<?php echo htmlspecialchars($hostel['id']); ?>" 
                                                data-gender="<?php echo htmlspecialchars($hostel['gender'] ?? ''); ?>"
                                                <?php echo ($hostel_id ?? '') == $hostel['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($hostel['name']); ?>
                                            <?php if (!empty($hostel['gender'])): ?>
                                                (<?php echo htmlspecialchars($hostel['gender']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Only hostels matching the selected student's gender will be available.</div>
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
    
    fetch('<?php echo APP_URL; ?>/room-allocations/get-available-rooms?hostel_id=' + encodeURIComponent(hostelId))
        .then(response => response.json())
        .then(data => {
            roomSelect.innerHTML = '<option value="">Select Room</option>';
            if (!Array.isArray(data) || data.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No rooms with available beds in this hostel';
                roomSelect.appendChild(option);
                return;
            }
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

document.addEventListener('DOMContentLoaded', function() {
    const studentSearchInput = document.getElementById('student_search');
    const studentIdInput = document.getElementById('student_id');
    const studentDropdown = document.getElementById('studentDropdown');
    const selectedStudentInfo = document.getElementById('selectedStudentInfo');
    const hostelSelect = document.getElementById('hostel_id');
    
    // Student search/type-ahead (filter preloaded list)
    if (studentSearchInput && studentDropdown) {
        studentSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const options = studentDropdown.querySelectorAll('.student-option');
            let hasVisibleOptions = false;
            
            options.forEach(option => {
                const studentName = (option.dataset.studentName || '').toLowerCase();
                const studentId = (option.dataset.studentId || '').toLowerCase();
                const matches = !searchTerm || studentName.includes(searchTerm) || studentId.includes(searchTerm);
                
                option.style.display = matches ? '' : 'none';
                if (matches) {
                    hasVisibleOptions = true;
                }
            });
            
            studentDropdown.style.display = hasVisibleOptions ? 'block' : 'none';
        });
        
        studentSearchInput.addEventListener('focus', function() {
            const options = studentDropdown.querySelectorAll('.student-option');
            let hasVisibleOptions = false;
            options.forEach(option => {
                if (option.style.display !== 'none') {
                    hasVisibleOptions = true;
                }
            });
            studentDropdown.style.display = hasVisibleOptions ? 'block' : 'none';
        });
    }
    
    // When a student is selected, set hidden ID, show info, and filter hostels by gender
    document.querySelectorAll('.student-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.dataset.studentId || '';
            const studentName = this.dataset.studentName || '';
            const studentGender = (this.dataset.studentGender || '').toLowerCase();
            
            studentIdInput.value = studentId;
            studentSearchInput.value = studentName && studentId
                ? studentName + ' (' + studentId + ')'
                : studentId;
            studentDropdown.style.display = 'none';
            
            if (selectedStudentInfo) {
                selectedStudentInfo.innerHTML = `
                    <div class="alert alert-success py-2 small mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Selected:</strong> ${studentName || 'N/A'} (ID: ${studentId}) 
                        ${studentGender ? ' | Gender: ' + studentGender.charAt(0).toUpperCase() + studentGender.slice(1) : ''}
                    </div>
                `;
            }
            
            // Enable hostel select and filter options by gender
            if (hostelSelect) {
                hostelSelect.disabled = false;
                const options = hostelSelect.querySelectorAll('option');
                let hasMatch = false;
                
                options.forEach(opt => {
                    const value = opt.value;
                    if (!value) {
                        // Always keep placeholder
                        opt.hidden = false;
                        return;
                    }
                    
                    const hostelGender = (opt.getAttribute('data-gender') || '').toLowerCase();
                    
                    // If hostel has no gender set, keep it visible for any student
                    if (!hostelGender) {
                        opt.hidden = false;
                        hasMatch = true;
                        return;
                    }
                    
                    const isFemaleHostel = hostelGender.indexOf('female') !== -1;
                    const isMaleHostel = hostelGender.indexOf('male') !== -1;
                    
                    let show = true;
                    if (isFemaleHostel && studentGender === 'male') {
                        show = false;
                    } else if (isMaleHostel && studentGender === 'female') {
                        show = false;
                    }
                    
                    opt.hidden = !show;
                    if (show) {
                        hasMatch = true;
                    }
                });
                
                // Reset selection after filtering
                hostelSelect.value = '';
                
                if (!hasMatch) {
                    alert('No hostels are available for the selected student gender. Please check hostel configuration.');
                }
            }
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (studentSearchInput && studentDropdown &&
            !studentSearchInput.contains(e.target) &&
            !studentDropdown.contains(e.target)) {
            studentDropdown.style.display = 'none';
        }
    });
});
</script>

