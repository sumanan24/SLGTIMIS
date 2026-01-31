<style>
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }
    
    .status-badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
    }
    
    .modal-header {
        background: #198754;
        color: white;
    }
    
    .payment-breakdown {
        background: #f8f9fa;
        border: 2px solid #198754;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    
    .payment-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .payment-item:last-child {
        border-bottom: none;
    }
    
    .payment-item.total {
        font-weight: bold;
        font-size: 1.1rem;
        color: #198754;
        border-top: 2px solid #198754;
        padding-top: 1rem;
        margin-top: 0.5rem;
    }
    
    /* Student Search Dropdown */
    #studentDropdown {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        margin-top: 2px;
        border: 1px solid #dee2e6;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    #studentDropdown .student-option {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    #studentDropdown .student-option:hover {
        background-color: #f8f9fa;
    }
    
    #studentDropdown .student-option:active {
        background-color: #e9ecef;
    }
    
    #selectedStudentInfo {
        min-height: 40px;
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-money-bill-wave me-2 text-success"></i>
                Bus Season Payment Collection
            </h2>
            <p class="text-muted mb-0">
                Process bus season requests and collect payments
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                <i class="fas fa-plus me-2"></i>Create Request
            </button>
            <a href="<?php echo APP_URL; ?>/bus-season-requests/payment-collections" class="btn btn-outline-primary">
                <i class="fas fa-list me-2"></i>View All Payments
            </a>
        </div>
    </div>
    
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
    
    <!-- Filter -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Show</label>
                    <select class="form-select" name="payment_filter">
                        <option value="needs_payment" <?php echo (($filters['payment_filter'] ?? 'needs_payment') === 'needs_payment') ? 'selected' : ''; ?>>
                            First payment only (no payments yet)
                        </option>
                        <option value="all" <?php echo (($filters['payment_filter'] ?? '') === 'all') ? 'selected' : ''; ?>>
                            All (collect or add monthly payment)
                        </option>
                        <option value="issued" <?php echo (($filters['payment_filter'] ?? '') === 'issued') ? 'selected' : ''; ?>>
                            Issued Only
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Student ID</label>
                    <input type="text" class="form-control" name="student_id" 
                           value="<?php echo htmlspecialchars($filters['student_id'] ?? ''); ?>" 
                           placeholder="e.g. 2025/ICT/4TE001">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Request ID</label>
                    <input type="text" class="form-control" name="request_id" 
                           value="<?php echo htmlspecialchars($filters['request_id'] ?? ''); ?>" 
                           placeholder="e.g. 123">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">
                <?php 
                $filterLabel = 'Needs Payment';
                if (($filters['payment_filter'] ?? '') === 'issued') $filterLabel = 'Issued';
                elseif (($filters['payment_filter'] ?? '') === 'all') $filterLabel = 'All';
                ?>
                <i class="fas fa-list me-1"></i> <?php echo count($requests); ?> request(s) - <?php echo $filterLabel; ?>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="requestsTable">
                <thead class="table-light">
                    <tr>
                        <th>Student Details</th>
                        <th>Route Information</th>
                        <th>Season</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle me-2"></i>No requests found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($request['student_id']); ?></small>
                                    <div class="small text-muted"><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <div>
                                        <i class="fas fa-map-marker-alt text-danger me-1 small"></i>
                                        <?php echo htmlspecialchars($request['route_from']); ?> 
                                        <i class="fas fa-arrow-right mx-1 small text-muted"></i> 
                                        <?php echo htmlspecialchars($request['route_to']); ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo number_format($request['distance_km'], 2); ?> km
                                        <?php if (!empty($request['change_point'])): ?>
                                            | Via: <?php echo htmlspecialchars($request['change_point']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-primary"><?php echo htmlspecialchars($request['season_year']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($request['season_name'] ?? 'Bus Season'); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $status = $request['status'] ?? 'pending';
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'hod_approved' => 'info',
                                        'approved' => 'primary',
                                        'paid' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pending HOD',
                                        'hod_approved' => 'HOD Approved',
                                        'approved' => 'Final Approved',
                                        'paid' => 'Paid',
                                        'rejected' => 'Rejected'
                                    ];
                                    $color = $statusColors[$status] ?? 'secondary';
                                    $label = $statusLabels[$status] ?? ucfirst($status);
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?> status-badge"><?php echo $label; ?></span>
                                    
                                    <?php if (isset($request['has_payment']) && $request['has_payment'] > 0): ?>
                                        <div class="mt-1 small text-success fw-bold">
                                            <i class="fas fa-check-circle me-1"></i><?php echo $request['has_payment']; ?> payment(s)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <?php if ($status !== 'rejected'): ?>
                                            <button type="button" 
                                                    class="btn btn-success btn-sm px-3" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal_<?php echo $request['id']; ?>">
                                                <i class="fas fa-cash-register me-1"></i> <?php echo (isset($request['has_payment']) && $request['has_payment'] > 0) ? 'Add Payment' : 'Collect Payment'; ?>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Payment Modal -->
                                    <div class="modal fade text-start" id="paymentModal_<?php echo $request['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-cash-register me-2"></i>
                                                        Collect Payment - <?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="alert alert-info py-2 small">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Processing request for <strong><?php echo htmlspecialchars($request['season_year']); ?></strong> season.
                                                        Route: <?php echo htmlspecialchars($request['route_from']); ?> to <?php echo htmlspecialchars($request['route_to']); ?>.
                                                    </div>
                                                    
                                                    <form action="<?php echo APP_URL; ?>/bus-season-requests/sao-process-save" method="POST" id="paymentForm_<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Student Payment Amount <span class="text-danger">*</span></label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">Rs.</span>
                                                                    <input type="number" 
                                                                           class="form-control" 
                                                                           name="student_payment_amount" 
                                                                           step="0.01" min="0" required
                                                                           placeholder="Enter amount paid">
                                                                </div>
                                                                <small class="text-muted">Initial payment to start processing</small>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                                                <select class="form-select" name="payment_method" required>
                                                                    <option value="cash">Cash</option>
                                                                    <option value="bank_transfer">Bank Transfer</option>
                                                                    <option value="cheque">Cheque</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label fw-bold">Reference No.</label>
                                                                <input type="text" class="form-control" name="payment_reference" placeholder="Receipt or Txn ID">
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label fw-bold">Remarks/Notes</label>
                                                                <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-4 text-end">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success px-4">
                                                                <i class="fas fa-save me-2"></i>Record Payment
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Create Request Modal -->
    <div class="modal fade" id="createRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Create Bus Season Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Create a bus season request for a student. Season Year: <strong>2026</strong>
                    </div>
                    
                    <form id="createRequestForm" method="POST" action="<?php echo APP_URL; ?>/bus-season-requests/sao-create-request">
                        <?php 
                        require_once BASE_PATH . '/core/SeasonRequestHelper.php';
                        $csrfToken = SeasonRequestHelper::generateCSRFToken();
                        ?>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="student_search" class="form-label fw-bold">
                                    <i class="fas fa-user-graduate me-2"></i>Select Student <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="text" 
                                           class="form-control" 
                                           id="student_search" 
                                           placeholder="Search by name or student ID..."
                                           autocomplete="off">
                                    <input type="hidden" id="student_id" name="student_id" required>
                                    <div id="studentDropdown" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto; display: none;">
                                        <?php if (!empty($students)): ?>
                                            <?php foreach ($students as $student): ?>
                                                <a class="dropdown-item student-option" 
                                                   href="#" 
                                                   data-student-id="<?php echo htmlspecialchars($student['student_id']); ?>"
                                                   data-student-name="<?php echo htmlspecialchars($student['student_fullname'] ?? ''); ?>">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($student['student_fullname'] ?? 'N/A'); ?></div>
                                                    <small class="text-muted">ID: <?php echo htmlspecialchars($student['student_id']); ?></small>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="dropdown-item text-muted">No students found</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small class="text-muted">Type to search and select a student</small>
                                <div id="selectedStudentInfo" class="mt-2"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="sao_route_from" class="form-label fw-bold">
                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>Route From <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="sao_route_from" 
                                       name="route_from" 
                                       placeholder="Enter starting point"
                                       required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="sao_route_to" class="form-label fw-bold">
                                    <i class="fas fa-map-marker-alt text-success me-2"></i>Route To <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="sao_route_to" 
                                       name="route_to" 
                                       placeholder="Enter destination"
                                       required>
                            </div>
                            
                            <div class="col-12">
                                <div id="createRequestError" class="alert alert-danger d-none" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <span id="createRequestErrorText"></span>
                                </div>
                                <div id="createRequestSuccess" class="alert alert-success d-none" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <span id="createRequestSuccessText"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" id="createRequestBtn">
                                <i class="fas fa-save me-2"></i>Create Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createRequestForm = document.getElementById('createRequestForm');
    const studentSearchInput = document.getElementById('student_search');
    const studentIdInput = document.getElementById('student_id');
    const studentDropdown = document.getElementById('studentDropdown');
    const selectedStudentInfo = document.getElementById('selectedStudentInfo');
    const createRequestError = document.getElementById('createRequestError');
    const createRequestErrorText = document.getElementById('createRequestErrorText');
    const createRequestSuccess = document.getElementById('createRequestSuccess');
    const createRequestSuccessText = document.getElementById('createRequestSuccessText');
    const createRequestBtn = document.getElementById('createRequestBtn');
    const studentOptions = document.querySelectorAll('.student-option');
    
    // Student search and filter
    studentSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const options = studentDropdown.querySelectorAll('.student-option');
        let hasVisibleOptions = false;
        
        if (searchTerm === '') {
            // Show all options when search is empty
            options.forEach(option => {
                option.style.display = '';
                hasVisibleOptions = true;
            });
            studentDropdown.style.display = hasVisibleOptions ? 'block' : 'none';
        } else {
            // Filter options
            options.forEach(option => {
                const studentName = (option.dataset.studentName || '').toLowerCase();
                const studentId = (option.dataset.studentId || '').toLowerCase();
                const matches = studentName.includes(searchTerm) || studentId.includes(searchTerm);
                
                option.style.display = matches ? '' : 'none';
                if (matches) hasVisibleOptions = true;
            });
            studentDropdown.style.display = hasVisibleOptions ? 'block' : 'none';
        }
    });
    
    // Handle student selection
    studentOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;
            
            studentIdInput.value = studentId;
            studentSearchInput.value = studentName + ' (' + studentId + ')';
            studentDropdown.style.display = 'none';
            
            // Show selected student info
            selectedStudentInfo.innerHTML = `
                <div class="alert alert-success py-2 small mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Selected:</strong> ${studentName} (ID: ${studentId})
                </div>
            `;
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!studentSearchInput.contains(e.target) && !studentDropdown.contains(e.target)) {
            studentDropdown.style.display = 'none';
        }
    });
    
    // Show dropdown on focus
    studentSearchInput.addEventListener('focus', function() {
        if (this.value.trim() === '') {
            studentDropdown.style.display = 'block';
        }
    });
    
    // Form submission
    createRequestForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Hide previous messages
        createRequestError.classList.add('d-none');
        createRequestSuccess.classList.add('d-none');
        
        // Disable button
        createRequestBtn.disabled = true;
        createRequestBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
        
        const formData = new FormData(createRequestForm);
        
        fetch(createRequestForm.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }
            return response.text().then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        createRequestSuccessText.textContent = data.message || 'Request created successfully!';
                        createRequestSuccess.classList.remove('d-none');
                        
                        // Reset form
                        createRequestForm.reset();
                        studentInfo.innerHTML = '';
                        
                        // Reload page after 1.5 seconds
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        createRequestErrorText.textContent = data.error || 'Failed to create request.';
                        createRequestError.classList.remove('d-none');
                        createRequestBtn.disabled = false;
                        createRequestBtn.innerHTML = '<i class="fas fa-save me-2"></i>Create Request';
                    }
                } catch (e) {
                    // Not JSON, might be HTML redirect
                    if (text.includes('successfully') || text.includes('created')) {
                        window.location.reload();
                    } else {
                        createRequestErrorText.textContent = 'An error occurred. Please try again.';
                        createRequestError.classList.remove('d-none');
                        createRequestBtn.disabled = false;
                        createRequestBtn.innerHTML = '<i class="fas fa-save me-2"></i>Create Request';
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            createRequestErrorText.textContent = 'An error occurred. Please try again.';
            createRequestError.classList.remove('d-none');
            createRequestBtn.disabled = false;
            createRequestBtn.innerHTML = '<i class="fas fa-save me-2"></i>Create Request';
        });
    });
});
</script>
