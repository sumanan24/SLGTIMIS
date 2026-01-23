<div class="container-fluid px-4 py-3">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i><?php echo htmlspecialchars($group['name']); ?></h5>
                        <div class="d-flex gap-2">
                            <a href="<?php echo APP_URL; ?>/groups/edit?id=<?php echo urlencode($group['id']); ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-edit me-1"></i>Edit Group
                            </a>
                            <a href="<?php echo APP_URL; ?>/groups" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Groups
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
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Course</h6>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($group['course_name'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Department</h6>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($group['department_name'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Academic Year</h6>
                                    <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($group['academic_year'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Status</h6>
                                    <p class="mb-0">
                                        <span class="badge bg-<?php echo ($group['status'] === 'active') ? 'success' : 'secondary'; ?> rounded-pill px-3">
                                            <?php echo htmlspecialchars(ucfirst($group['status'] ?? 'active')); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Total Students</h6>
                                    <p class="mb-0 fw-bold fs-4 text-primary"><?php echo count($students); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-info text-white">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2"></i>Group Timetable</h5>
                        <?php
                        require_once BASE_PATH . '/models/UserModel.php';
                        $userModel = new UserModel();
                        $userRole = $userModel->getUserRole($_SESSION['user_id']);
                        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                        $canManageTimetable = in_array($userRole, ['HOD', 'ADM']) || $isAdmin;
                        ?>
                        <?php if ($canManageTimetable): ?>
                        <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($group['id']); ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-cog me-1"></i>Manage Timetable
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center py-3">
                        <p class="mb-2">View and manage the timetable for this group</p>
                        <a href="<?php echo APP_URL; ?>/group-timetable/index?group_id=<?php echo urlencode($group['id']); ?>" class="btn btn-info">
                            <i class="fas fa-calendar-alt me-1"></i>View Timetable
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-user-graduate me-2"></i>Students in Group</h5>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentsModal">
                            <i class="fas fa-plus me-1"></i>Add Students
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($students)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-bold">Student ID</th>
                                        <th class="fw-bold">Full Name</th>
                                        <th class="fw-bold">Email</th>
                                        <th class="fw-bold">Enrolled At</th>
                                        <th class="fw-bold">Status</th>
                                        <th class="fw-bold text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_email'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($student['enrolled_at'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($student['status'] === 'active') ? 'success' : 'secondary'; ?> rounded-pill px-3">
                                                    <?php echo htmlspecialchars(ucfirst($student['status'] ?? 'active')); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-student" 
                                                        data-student-id="<?php echo htmlspecialchars($student['student_id']); ?>"
                                                        data-group-id="<?php echo htmlspecialchars($group['id']); ?>"
                                                        title="Remove Student">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-3">No students in this group yet.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentsModal">
                                <i class="fas fa-plus me-1"></i>Add Students
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Students Modal -->
<div class="modal fade" id="addStudentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Students to Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="filter_student" class="form-label">Search Students</label>
                    <input type="text" class="form-control" id="filter_student" placeholder="Type to search...">
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody id="availableStudentsTable">
                            <?php if (!empty($availableStudents)): ?>
                                <?php foreach ($availableStudents as $student): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="student-checkbox" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['student_fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($student['student_email'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No available students to add</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addStudentsBtn">
                    <i class="fas fa-plus me-1"></i>Add Selected Students
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupId = <?php echo htmlspecialchars($group['id']); ?>;
    
    // Filter students
    const filterInput = document.getElementById('filter_student');
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#availableStudentsTable tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
    
    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }
    
    // Add students
    const addStudentsBtn = document.getElementById('addStudentsBtn');
    if (addStudentsBtn) {
        addStudentsBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Please select at least one student');
                return;
            }
            
            fetch('<?php echo APP_URL; ?>/groups/add-students', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'group_id=' + encodeURIComponent(groupId) + '&student_ids[]=' + selected.map(id => encodeURIComponent(id)).join('&student_ids[]=')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to add students');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });
    }
    
    // Remove student
    document.querySelectorAll('.remove-student').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Are you sure you want to remove this student from the group?')) {
                return;
            }
            
            const studentId = this.getAttribute('data-student-id');
            
            fetch('<?php echo APP_URL; ?>/groups/remove-student', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'group_id=' + encodeURIComponent(groupId) + '&student_id=' + encodeURIComponent(studentId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to remove student');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        });
    });
});
</script>

