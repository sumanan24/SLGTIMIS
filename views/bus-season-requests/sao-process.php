<style>
    .page-header {
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .filter-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 1.5rem;
        overflow-x: auto;
    }
    
    .status-badge {
        font-weight: 500;
        padding: 0.5em 0.8em;
        font-size: 0.875rem;
    }
    
    .student-card {
        background: #f8f9fa;
        border-left: 4px solid #198754;
        padding: 0.75rem;
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }
    
    .route-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .payment-count-badge {
        background: #d1e7dd;
        color: #0f5132;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .modal-header {
        background: #198754;
        color: white;
    }
    
    .payment-type-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .payment-type-initial {
        background: #cfe2ff;
        color: #084298;
    }
    
    .payment-type-monthly {
        background: #fff3cd;
        color: #856404;
    }
    
    #studentDropdown {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        margin-top: 2px;
        border: 1px solid #dee2e6;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        max-height: 300px;
        overflow-y: auto;
    }
    
    #studentDropdown .student-option {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    #studentDropdown .student-option:hover {
        background-color: #f8f9fa;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }
        
        .action-buttons .btn {
            width: 100%;
        }
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h2 class="fw-bold mb-2">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Bus Season Payment Collection
                </h2>
                <p class="mb-0 opacity-90">
                    Manage and collect payments for bus season requests
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                    <i class="fas fa-plus me-2"></i>Create Request
                </button>
                <a href="<?php echo APP_URL; ?>/bus-season-requests/payment-collections" class="btn btn-outline-light">
                    <i class="fas fa-list me-2"></i>View All Payments
                </a>
            </div>
        </div>
    </div>
    
    <!-- Alert Messages -->
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
    <div class="filter-card">
        <form method="GET" action="<?php echo APP_URL; ?>/bus-season-requests/sao-process" id="filterForm" class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-bold">
                    <i class="fas fa-search me-2 text-primary"></i>Search (Name, NIC, or Student ID)
                </label>
                <input type="text" 
                       class="form-control" 
                       name="search" 
                       id="searchInput"
                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                       placeholder="Search by name, NIC, or student ID...">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Filter
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-redo me-2"></i>Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Results Summary -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">
                <i class="fas fa-list me-2 text-primary"></i>
                <?php echo count($requests); ?> Request(s)
            </h5>
        </div>
    </div>
    
    <!-- Requests Table -->
    <div class="table-container">
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5 class="mt-3">No Requests Found</h5>
                <p class="text-muted">No bus season requests match your current filters.</p>
                <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="btn btn-primary mt-2">
                    <i class="fas fa-redo me-2"></i>Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Student Information</th>
                            <th style="width: 25%;">Route Details</th>
                            <th style="width: 15%;">Season</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 20%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <?php 
                            $isIssued = isset($request['has_issued_payment']) && $request['has_issued_payment'] > 0;
                            $isMonthlyPayment = $isIssued; // Monthly payment if request has been issued
                            $status = $request['status'] ?? 'pending';
                            $statusColors = [
                                'pending' => 'warning',
                                'hod_approved' => 'info',
                                'approved' => 'primary',
                                'paid' => 'success',
                                'issued' => 'success',
                                'rejected' => 'danger'
                            ];
                            $statusLabels = [
                                'pending' => 'Pending HOD',
                                'hod_approved' => 'HOD Approved',
                                'approved' => 'Final Approved',
                                'paid' => 'Paid',
                                'issued' => 'Issued',
                                'rejected' => 'Rejected'
                            ];
                            $color = $statusColors[$status] ?? 'secondary';
                            $label = $statusLabels[$status] ?? ucfirst($status);
                            ?>
                            <tr>
                                <td>
                                    <div class="student-card">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?></div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($request['student_id']); ?>
                                        </small>
                                        <?php if (!empty($request['department_name'])): ?>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($request['department_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (isset($request['total_requests_for_student']) && $request['total_requests_for_student'] > 1): ?>
                                            <small class="text-info d-block mt-1">
                                                <i class="fas fa-list me-1"></i><?php echo $request['total_requests_for_student']; ?> total request(s) for this season
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($request['created_at'])): ?>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-calendar me-1"></i>Requested: <?php echo date('M Y', strtotime($request['created_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="route-info">
                                        <i class="fas fa-map-marker-alt text-danger"></i>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($request['route_from']); ?></span>
                                        <i class="fas fa-arrow-right text-muted"></i>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($request['route_to']); ?></span>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-route me-1"></i><?php echo number_format($request['distance_km'] ?? 0, 2); ?> km
                                            <?php if (!empty($request['change_point'])): ?>
                                                <span class="ms-2">
                                                    <i class="fas fa-exchange-alt me-1"></i>Via: <?php echo htmlspecialchars($request['change_point']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary"><?php echo htmlspecialchars($request['season_year']); ?></div>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($request['season_name'] ?? 'Bus Season'); ?></small>
                                    <?php if (!empty($request['id'])): ?>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-hashtag me-1"></i>Request ID: <?php echo $request['id']; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $color; ?> status-badge">
                                        <?php echo $label; ?>
                                    </span>
                                    <?php if (isset($request['has_payment']) && $request['has_payment'] > 0): ?>
                                        <div class="mt-2">
                                            <span class="payment-count-badge">
                                                <i class="fas fa-check-circle me-1"></i><?php echo $request['has_payment']; ?> payment(s)
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <?php if ($status !== 'rejected'): ?>
                                            <button type="button" 
                                                    class="btn btn-success btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal_<?php echo $request['id']; ?>">
                                                <i class="fas fa-cash-register me-1"></i>
                                                <?php 
                                                if ($isMonthlyPayment) {
                                                    echo 'Monthly';
                                                } elseif (isset($request['has_payment']) && $request['has_payment'] > 0) {
                                                    echo 'Add';
                                                } else {
                                                    echo 'Collect';
                                                }
                                                ?>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Payment Modal -->
                                    <div class="modal fade" id="paymentModal_<?php echo $request['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-cash-register me-2"></i>
                                                        <?php echo $isMonthlyPayment ? 'Collect Monthly Payment' : 'Collect Payment'; ?>
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <!-- Payment Type Indicator -->
                                                    <div class="payment-type-indicator payment-type-<?php echo $isMonthlyPayment ? 'monthly' : 'initial'; ?> mb-3">
                                                        <i class="fas fa-<?php echo $isMonthlyPayment ? 'calendar-alt' : 'info-circle'; ?>"></i>
                                                        <?php if ($isMonthlyPayment): ?>
                                                            <span><strong>Monthly Payment:</strong> This student has been issued a season pass. Collecting payment for next month.</span>
                                                        <?php else: ?>
                                                            <span><strong>Initial Payment:</strong> Processing request for <?php echo htmlspecialchars($request['season_year']); ?> season.</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Student Info -->
                                                    <div class="card bg-light mb-3">
                                                        <div class="card-body py-2">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <small class="text-muted d-block">Student</small>
                                                                    <strong><?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?></strong>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <small class="text-muted d-block">Route</small>
                                                                    <strong><?php echo htmlspecialchars($request['route_from']); ?> → <?php echo htmlspecialchars($request['route_to']); ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Payment Form -->
                                                    <form action="<?php echo APP_URL; ?>/bus-season-requests/sao-process-save" method="POST">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">
                                                                    Payment Amount <span class="text-danger">*</span>
                                                                </label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">Rs.</span>
                                                                    <input type="number" 
                                                                           class="form-control" 
                                                                           name="student_payment_amount" 
                                                                           step="0.01" 
                                                                           min="0" 
                                                                           required
                                                                           placeholder="0.00">
                                                                </div>
                                                                <small class="text-muted">
                                                                    <?php echo $isMonthlyPayment ? 'Enter monthly payment amount' : 'Enter initial payment amount'; ?>
                                                                </small>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">
                                                                    Payment Method <span class="text-danger">*</span>
                                                                </label>
                                                                <select class="form-select" name="payment_method" required>
                                                                    <option value="cash">Cash</option>
                                                                    <option value="bank_transfer">Bank Transfer</option>
                                                                    <option value="cheque">Cheque</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label fw-bold">Reference Number</label>
                                                                <input type="text" 
                                                                       class="form-control" 
                                                                       name="payment_reference" 
                                                                       placeholder="Receipt number or transaction ID">
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label fw-bold">Notes</label>
                                                                <textarea class="form-control" 
                                                                          name="notes" 
                                                                          rows="3" 
                                                                          placeholder="<?php echo $isMonthlyPayment ? 'e.g. Payment for January 2026' : 'Optional notes...'; ?>"><?php echo $isMonthlyPayment ? 'Monthly payment collection' : ''; ?></textarea>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-4 d-flex justify-content-end gap-2">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                                                Cancel
                                                            </button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="fas fa-save me-2"></i>
                                                                <?php echo $isMonthlyPayment ? 'Record Monthly Payment' : 'Record Payment'; ?>
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
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Create a new bus season request for a student. Season Year: <strong>2026</strong>
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
                                    <div id="studentDropdown" class="dropdown-menu w-100" style="display: none;">
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
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="createRequestBtn">
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
    
    // Student search functionality
    if (studentSearchInput) {
        studentSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const options = studentDropdown.querySelectorAll('.student-option');
            let hasVisibleOptions = false;
            
            if (searchTerm === '') {
                options.forEach(option => {
                    option.style.display = '';
                    hasVisibleOptions = true;
                });
                studentDropdown.style.display = hasVisibleOptions ? 'block' : 'none';
            } else {
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
        
        studentSearchInput.addEventListener('focus', function() {
            if (this.value.trim() === '') {
                studentDropdown.style.display = 'block';
            }
        });
    }
    
    // Handle student selection
    document.querySelectorAll('.student-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.dataset.studentId;
            const studentName = this.dataset.studentName;
            
            studentIdInput.value = studentId;
            studentSearchInput.value = studentName + ' (' + studentId + ')';
            studentDropdown.style.display = 'none';
            
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
        if (studentSearchInput && studentDropdown && 
            !studentSearchInput.contains(e.target) && 
            !studentDropdown.contains(e.target)) {
            studentDropdown.style.display = 'none';
        }
    });
    
    // Form submission
    if (createRequestForm) {
        createRequestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            createRequestError.classList.add('d-none');
            createRequestSuccess.classList.add('d-none');
            
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
                            
                            createRequestForm.reset();
                            selectedStudentInfo.innerHTML = '';
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            // Prefer backend message; fall back to generic error
                            createRequestErrorText.textContent = data.message || data.error || 'Failed to create request.';
                            createRequestError.classList.remove('d-none');
                            createRequestBtn.disabled = false;
                            createRequestBtn.innerHTML = '<i class="fas fa-save me-2"></i>Create Request';
                        }
                    } catch (e) {
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
    }
    
    // Auto-update filter on input change (debounced)
    const searchInput = document.getElementById('searchInput');
    const filterForm = document.getElementById('filterForm');
    let searchTimeout;
    
    function autoUpdateFilter() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            // Auto-submit form when user stops typing (after 500ms delay)
            if (searchInput.value.trim().length >= 2 || searchInput.value.trim().length === 0) {
                filterForm.submit();
            }
        }, 500);
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', autoUpdateFilter);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                filterForm.submit();
            }
        });
    }
});
</script>
