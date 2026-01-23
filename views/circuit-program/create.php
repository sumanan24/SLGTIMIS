<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create Circuit Program</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($staff)): ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This circuit program will be created for your own record.
                    </div>
                    
                    <div class="card border mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Employee Information</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small">Name of Employee</label>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($staff['staff_name']); ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small">Designation</label>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($staff['staff_position'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-muted small">Department</label>
                                    <div class="fw-semibold">
                                        <?php
                                        require_once BASE_PATH . '/models/DepartmentModel.php';
                                        $deptModel = new DepartmentModel();
                                        $dept = $deptModel->find($staff['department_id'] ?? '');
                                        echo htmlspecialchars($dept['department_name'] ?? $staff['department_id'] ?? 'N/A');
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/circuit-program/create" id="circuitProgramForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="mode_of_travel" class="form-label fw-semibold">
                                    Mode of Travel <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="mode_of_travel" name="mode_of_travel" 
                                       required placeholder="e.g., Vehicle, Bus, Train">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Program Details</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addProgramRow">
                                    <i class="fas fa-plus me-1"></i>Add Row
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered" id="programDetailsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Date <span class="text-danger">*</span></th>
                                            <th style="width: 35%;">Destination <span class="text-danger">*</span></th>
                                            <th style="width: 30%;">Purpose</th>
                                            <th style="width: 5%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="programDetailsBody">
                                        <tr>
                                            <td>
                                                <?php
                                                // Calculate minimum allowed date (3 days before today)
                                                $minDate = date('Y-m-d', strtotime('-3 days'));
                                                ?>
                                                <input type="date" class="form-control form-control-sm" name="program_date[]" 
                                                       min="<?php echo $minDate; ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="program_destination[]" required placeholder="Enter destination">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="program_purpose[]" placeholder="Enter purpose">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger removeRow" disabled>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Circuit Program
                            </button>
                            <a href="<?php echo APP_URL; ?>/circuit-program" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('addProgramRow');
    const tbody = document.getElementById('programDetailsBody');
    const removeButtons = document.querySelectorAll('.removeRow');
    
    // Update remove button states
    function updateRemoveButtons() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            const btn = row.querySelector('.removeRow');
            if (btn) {
                btn.disabled = rows.length <= 1;
            }
        });
    }
    
    // Add new row
    addBtn.addEventListener('click', function() {
        const minDate = '<?php echo date('Y-m-d', strtotime('-3 days')); ?>';
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="date" class="form-control form-control-sm" name="program_date[]" 
                       min="${minDate}" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="program_destination[]" required placeholder="Enter destination">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="program_purpose[]" placeholder="Enter purpose">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger removeRow">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
        updateRemoveButtons();
        
        // Add event listener to new remove button
        newRow.querySelector('.removeRow').addEventListener('click', function() {
            newRow.remove();
            updateRemoveButtons();
        });
    });
    
    // Remove row
    removeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('tr').remove();
            updateRemoveButtons();
        });
    });
    
    updateRemoveButtons();
});
</script>

