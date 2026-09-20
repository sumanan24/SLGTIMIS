<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff.css?v=<?php echo time(); ?>">
<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$roles = $roles ?? [];
$departments = $departments ?? [];
?>

<div class="container-fluid px-0 st-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="fw-bold"><i class="fas fa-plus-circle me-2"></i>Create Staff</h5>
            <a href="<?php echo APP_URL; ?>/staff" class="btn btn-sm btn-light">Back</a>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <?php echo $h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/staff/create" id="stStaffForm">
                <div class="st-grid-3">
                    <div>
                        <label class="form-label" for="staff_id">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="staff_id" name="staff_id" maxlength="64" required placeholder="Alfred">
                    </div>
                    <div>
                        <label class="form-label" for="staff_name">Staff Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="staff_name" name="staff_name" maxlength="100" required>
                    </div>
                    <div>
                        <label class="form-label" for="staff_ininame">Name with Initials</label>
                        <input type="text" class="form-control" id="staff_ininame" name="staff_ininame" maxlength="100" placeholder="e.g. A. B. Alfred">
                    </div>
                </div>

                <div class="st-grid-3">
                    <div>
                        <label class="form-label" for="staff_email">Email</label>
                        <input type="email" class="form-control" id="staff_email" name="staff_email" maxlength="100">
                    </div>
                    <div>
                        <label class="form-label" for="staff_nic">NIC <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="staff_nic" name="staff_nic" maxlength="20" required>
                    </div>
                    <div>
                        <label class="form-label" for="staff_pno">Phone</label>
                        <input type="tel" class="form-control" id="staff_pno" name="staff_pno" maxlength="20" pattern="[0-9]{9,10}">
                    </div>
                </div>

                <div class="st-grid-3">
                    <div>
                        <label class="form-label" for="staff_gender">Gender</label>
                        <select class="form-select" id="staff_gender" name="staff_gender">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Transgender">Transgender</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="staff_epf">EPF Number</label>
                        <input type="text" class="form-control" id="staff_epf" name="staff_epf" maxlength="20">
                    </div>
                    <div>
                        <label class="form-label" for="finger_machine_no">Finger Machine Number</label>
                        <input type="text" class="form-control" id="finger_machine_no" name="finger_machine_no" maxlength="50" placeholder="e.g. 101">
                    </div>
                </div>

                <div class="st-grid-3">
                    <div>
                        <label class="form-label" for="staff_date_of_join">Date of Join</label>
                        <input type="date" class="form-control" id="staff_date_of_join" name="staff_date_of_join">
                    </div>
                    <div>
                        <label class="form-label" for="department_id">Department <span class="text-danger">*</span></label>
                        <select class="form-select" id="department_id" name="department_id" required>
                            <option value="">Select department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $h($dept['department_id']); ?>">
                                    <?php echo $h($dept['department_name']); ?> (<?php echo $h($dept['department_id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="staff_status">Status</label>
                        <select class="form-select" id="staff_status" name="staff_status">
                            <option value="Working" selected>Working</option>
                            <option value="Terminated">Terminated</option>
                            <option value="Resigned">Resigned</option>
                        </select>
                    </div>
                </div>

                <div class="st-section-label">Permanent appointment</div>
                <p class="st-perm-note mb-2">A staff member may hold only one permanent position.</p>
                <div class="st-grid-3">
                    <div>
                        <label class="form-label" for="staff_position">Position <span class="text-danger">*</span></label>
                        <select class="form-select" id="staff_position" name="staff_position" required>
                            <option value="">Select position</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $h($role['staff_position_type_id']); ?>">
                                    <?php echo $h($role['staff_position_type_name']); ?> (<?php echo $h($role['staff_position_type_id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="staff_type">Staff Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="staff_type" name="staff_type" required>
                            <option value="">Select</option>
                            <option value="Permanent">Permanent</option>
                            <option value="On Contract">On Contract</option>
                            <option value="Visiting Lecturer">Visiting Lecturer</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="salary_grade">Salary Grade</label>
                        <select class="form-select" id="salary_grade" name="salary_grade">
                            <option value="">Not assigned</option>
                            <option value="3">Grade 3</option>
                            <option value="2">Grade 2</option>
                            <option value="1">Grade 1</option>
                        </select>
                    </div>
                </div>

                <div class="st-form-actions">
                    <a href="<?php echo APP_URL; ?>/staff" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
