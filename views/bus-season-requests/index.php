<style>
    .request-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--student-primary);
    }
    
    .request-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-hod_approved {
        background-color: #cfe2ff;
        color: #084298;
    }
    
    .status-approved {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    .status-rejected {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
        border-left: 4px solid #198754;
    }
    
    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }
    
    @media (max-width: 768px) {
        .form-card {
            padding: 1.5rem;
        }
        
        .form-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
    
    .payment-info {
        background: #e7f3ff;
        border-left: 4px solid #0d6efd;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-bus me-2" style="color: #198754;"></i>
                Bus Season Request
            </h2>
            <p class="text-muted mb-0">Apply for Bus Season Ticket</p>
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
    
    <?php if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student'): ?>
        <?php if ($hasExistingRequest): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                You already have a bus season request for the season year <strong><?php echo htmlspecialchars($seasonYear ?? ''); ?></strong>. Only one request per year is allowed.
            </div>
        <?php else: ?>
            <!-- Request Form -->
            <div class="form-card">
                <div class="form-header">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-bus me-2 text-success"></i>New Bus Season Request
                    </h4>
                </div>
                
                <div class="alert alert-info">
                    <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-2"></i>Request Information</h6>
                    <p class="mb-2">Submit a bus season request for approval. Payment collection will be handled separately by the Student Affairs Office (SAO) after your request is approved.</p>
                    <p class="mb-0"><small><strong>Payment Structure:</strong> Student pays 30%, SLGTI pays 35%, CTB pays 35%</small></p>
                </div>
                
                <form method="POST" action="<?php echo APP_URL; ?>/bus-season-requests/create" id="busSeasonForm" novalidate>
                    <?php 
                    // Generate CSRF token for nginx compatibility
                    if (!isset($_SESSION['csrf_token'])) {
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="form_submitted" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="route_from" class="form-label fw-semibold">Route From <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="route_from" name="route_from" 
                                   placeholder="Starting point" required maxlength="255"
                                   value="<?php echo htmlspecialchars($_POST['route_from'] ?? ''); ?>">
                            <div class="invalid-feedback">Please provide a valid route from location.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="route_to" class="form-label fw-semibold">Route To <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="route_to" name="route_to" 
                                   placeholder="Destination" required maxlength="255"
                                   value="<?php echo htmlspecialchars($_POST['route_to'] ?? ''); ?>">
                            <div class="invalid-feedback">Please provide a valid route to location.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="change_point" class="form-label fw-semibold">Change Point</label>
                            <input type="text" class="form-control" id="change_point" name="change_point" 
                                   placeholder="Transfer point if any" maxlength="255"
                                   value="<?php echo htmlspecialchars($_POST['change_point'] ?? ''); ?>">
                            <small class="text-muted">Optional: Enter if you need to change buses</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="distance_km" class="form-label fw-semibold">Distance (KM) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="distance_km" name="distance_km" 
                                   step="0.1" min="0.1" max="9999.9" placeholder="0.0" required
                                   value="<?php echo htmlspecialchars($_POST['distance_km'] ?? ''); ?>">
                            <div class="invalid-feedback">Please enter a valid distance greater than 0.</div>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Only one bus season request per season year is allowed. Season year (<?php echo htmlspecialchars($seasonYear ?? ''); ?>) is automatically set from your current enrollment. Once approved, payment collection will be handled by SAO.
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div id="formError" class="alert alert-danger d-none" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <span id="formErrorText"></span>
                            </div>
                            <div id="formSuccess" class="alert alert-success d-none" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <span id="formSuccessText"></span>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="fas fa-paper-plane me-2"></i><span id="submitBtnText">Submit Request</span>
                            </button>
                            <a href="<?php echo APP_URL; ?>/student/dashboard" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </form>
                
                <script>
                (function() {
                    'use strict';
                    
                    const form = document.getElementById('busSeasonForm');
                    const submitBtn = document.getElementById('submitBtn');
                    const submitBtnText = document.getElementById('submitBtnText');
                    const formError = document.getElementById('formError');
                    const formErrorText = document.getElementById('formErrorText');
                    const formSuccess = document.getElementById('formSuccess');
                    const formSuccessText = document.getElementById('formSuccessText');
                    
                    let isSubmitting = false;
                    
                    // Hide alerts on page load
                    formError.classList.add('d-none');
                    formSuccess.classList.add('d-none');
                    
                    // Client-side validation
                    function validateForm() {
                        const routeFrom = document.getElementById('route_from').value.trim();
                        const routeTo = document.getElementById('route_to').value.trim();
                        const distanceKm = parseFloat(document.getElementById('distance_km').value);
                        
                        if (!routeFrom) {
                            showError('Please enter the route from location.');
                            document.getElementById('route_from').focus();
                            return false;
                        }
                        
                        if (!routeTo) {
                            showError('Please enter the route to location.');
                            document.getElementById('route_to').focus();
                            return false;
                        }
                        
                        if (!distanceKm || distanceKm <= 0 || isNaN(distanceKm)) {
                            showError('Please enter a valid distance greater than 0.');
                            document.getElementById('distance_km').focus();
                            return false;
                        }
                        
                        if (distanceKm > 9999.9) {
                            showError('Distance cannot exceed 9999.9 KM.');
                            document.getElementById('distance_km').focus();
                            return false;
                        }
                        
                        return true;
                    }
                    
                    function showError(message) {
                        formErrorText.textContent = message;
                        formError.classList.remove('d-none');
                        formSuccess.classList.add('d-none');
                    }
                    
                    function showSuccess(message) {
                        formSuccessText.textContent = message;
                        formSuccess.classList.remove('d-none');
                        formError.classList.add('d-none');
                    }
                    
                    function setSubmitting(state) {
                        isSubmitting = state;
                        submitBtn.disabled = state;
                        if (state) {
                            submitBtnText.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';
                            submitBtn.classList.add('disabled');
                        } else {
                            submitBtnText.textContent = 'Submit Request';
                            submitBtn.classList.remove('disabled');
                        }
                    }
                    
                    // Bootstrap validation
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (isSubmitting) {
                            return false;
                        }
                        
                        // Hide previous messages
                        formError.classList.add('d-none');
                        formSuccess.classList.add('d-none');
                        
                        // Validate form
                        if (!validateForm()) {
                            form.classList.add('was-validated');
                            return false;
                        }
                        
                        // Check if form is valid
                        if (!form.checkValidity()) {
                            form.classList.add('was-validated');
                            return false;
                        }
                        
                        // Try AJAX submission first (nginx compatible)
                        setSubmitting(true);
                        
                        const formData = new FormData(form);
                        
                        fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            // Check if response is a redirect
                            if (response.redirected) {
                                window.location.href = response.url;
                                return;
                            }
                            
                            return response.text().then(text => {
                                // Try to parse as JSON if possible
                                try {
                                    const data = JSON.parse(text);
                                    if (data.success) {
                                        showSuccess(data.message || 'Request submitted successfully!');
                                        setTimeout(() => {
                                            window.location.href = '<?php echo APP_URL; ?>/bus-season-requests';
                                        }, 1500);
                                    } else {
                                        showError(data.error || 'Failed to submit request. Please try again.');
                                        setSubmitting(false);
                                    }
                                } catch (e) {
                                    // If not JSON, check if it's HTML (likely a redirect page)
                                    if (text.includes('bus-season-requests') || text.includes('successfully')) {
                                        // Redirect to form page (session message will be shown)
                                        window.location.href = '<?php echo APP_URL; ?>/bus-season-requests';
                                    } else {
                                        // Fallback to regular form submission
                                        form.submit();
                                    }
                                }
                            });
                        })
                        .catch(error => {
                            console.error('AJAX submission error:', error);
                            // Fallback to regular form submission for nginx compatibility
                            showError('Switching to standard submission...');
                            setTimeout(() => {
                                form.submit();
                            }, 500);
                        });
                        
                        return false;
                    });
                    
                    // Real-time validation
                    const inputs = form.querySelectorAll('input[required]');
                    inputs.forEach(input => {
                        input.addEventListener('blur', function() {
                            if (this.value.trim() === '' && this.hasAttribute('required')) {
                                this.classList.add('is-invalid');
                            } else {
                                this.classList.remove('is-invalid');
                                this.classList.add('is-valid');
                            }
                        });
                        
                        input.addEventListener('input', function() {
                            if (this.classList.contains('is-invalid') && this.value.trim() !== '') {
                                this.classList.remove('is-invalid');
                                this.classList.add('is-valid');
                            }
                        });
                    });
                    
                    // Distance validation
                    const distanceInput = document.getElementById('distance_km');
                    distanceInput.addEventListener('input', function() {
                        const value = parseFloat(this.value);
                        if (value <= 0 || isNaN(value)) {
                            this.classList.add('is-invalid');
                            this.classList.remove('is-valid');
                        } else if (value > 9999.9) {
                            this.classList.add('is-invalid');
                            this.classList.remove('is-valid');
                        } else {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        }
                    });
                })();
                </script>
            </div>
        <?php endif; ?>
        
        <!-- Existing Requests (Student View) -->
        <?php if (!empty($requests)): ?>
            <div class="form-card">
                <h4 class="fw-bold mb-3">
                    <i class="fas fa-list me-2"></i>My Bus Season Requests
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Academic Year</th>
                                <th>Payment Status</th>
                                <th>Payment Period</th>
                                <th>Student Payment</th>
                                <th>Total Value</th>
                                <th>Request Status</th>
                                <th>Request Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['season_year'] ?? ''); ?></td>
                                    <td>
                                        <?php if (isset($request['payment'])): ?>
                                            <span class="badge bg-success">Payment Collected</span>
                                            <br><small class="text-muted"><?php echo $request['payment']['payment_date'] ? date('M d, Y', strtotime($request['payment']['payment_date'])) : ''; ?></small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending Collection</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($request['payment'])): ?>
                                            <?php echo htmlspecialchars($request['payment']['payment_method'] ?? 'N/A'); ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($request['payment'])): ?>
                                            Rs. <?php echo number_format($request['payment']['student_paid'] ?? 0, 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not Collected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($request['payment'])): ?>
                                            Rs. <?php echo number_format($request['payment']['total_amount'] ?? 0, 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Pending Collection</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $request['status'] ?? 'pending';
                                        $statusClass = 'status-' . strtolower($status);
                                        $statusLabels = [
                                            'pending' => 'Pending HOD Approval',
                                            'hod_approved' => 'HOD Approved - Pending Second Approval',
                                            'approved' => 'Approved',
                                            'paid' => 'Paid',
                                            'rejected' => 'Rejected'
                                        ];
                                        $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $request['created_at'] ? date('M d, Y', strtotime($request['created_at'])) : 'N/A'; ?></td>
                                    <td>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Admin/Staff View (All Requests) -->
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">
                    <i class="fas fa-list me-2"></i>All Bus Season Requests
                </h4>
                <div class="btn-group">
                    <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="btn btn-success btn-sm">
                        <i class="fas fa-cash-register me-1"></i> Payment Collection
                    </a>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Student Details</th>
                            <th>Route Information</th>
                            <th>Season</th>
                            <th>Status</th>
                            <th>Actions</th>
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
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $label; ?></span>
                                        
                                        <?php if (isset($request['has_payment']) && $request['has_payment'] > 0): ?>
                                            <div class="mt-1 small text-success fw-bold">
                                                <i class="fas fa-check-circle me-1"></i>Paid
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($status === 'approved' && !(isset($request['has_payment']) && $request['has_payment'] > 0)): ?>
                                                <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-cash-register"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>


