<style>
    .request-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
        border-left: 4px solid #198754;
    }
    
    .request-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .student-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .payment-form {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .payment-breakdown {
        background: white;
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
    
    .badge-collected {
        background: #198754;
        color: white;
    }
    
    @media (max-width: 768px) {
        .request-card {
            padding: 1rem !important;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .student-info {
            padding: 0.75rem;
        }
        
        .payment-form {
            padding: 1rem;
        }
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
                Process approved bus season requests and collect payments
            </p>
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
    
    <?php if (empty($requests)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No requests available for payment collection.
        </div>
    <?php else: ?>
        <?php foreach ($requests as $request): ?>
            <div class="request-card p-4">
                <div class="student-info">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <small class="text-muted">Student ID:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['student_id']); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Student Name:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['student_fullname'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Season Year:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['season_year'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Department:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6 class="fw-bold mb-3">Route Information</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Route From:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['route_from'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Route To:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['route_to'] ?? 'N/A'); ?></div>
                        </div>
                        <?php if (!empty($request['change_point'])): ?>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Change Point:</small>
                            <div class="fw-semibold"><?php echo htmlspecialchars($request['change_point']); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <small class="text-muted d-block mb-1">Distance (KM):</small>
                            <div class="fw-semibold"><?php echo number_format($request['distance_km'] ?? 0, 2); ?> km</div>
                        </div>
                    </div>
                </div>
                
                <!-- Request Status -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Request Status:</small>
                            <span class="badge bg-<?php 
                                $status = $request['status'] ?? 'pending';
                                $statusColors = [
                                    'pending' => 'warning',
                                    'hod_approved' => 'info',
                                    'approved' => 'success',
                                    'rejected' => 'danger'
                                ];
                                echo $statusColors[$status] ?? 'secondary';
                            ?> ms-2">
                                <?php 
                                $statusLabels = [
                                    'pending' => 'Pending HOD Approval',
                                    'hod_approved' => 'HOD Approved - Needs Second Approval',
                                    'approved' => 'Approved - Ready for Payment',
                                    'rejected' => 'Rejected'
                                ];
                                echo $statusLabels[$status] ?? ucfirst($status);
                                ?>
                            </span>
                        </div>
                        <?php if (isset($request['has_payment']) && $request['has_payment'] > 0): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Payment Collected
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (isset($request['has_payment']) && $request['has_payment'] > 0): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Payment Already Collected</strong> - This request has already been processed. 
                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="alert-link">View details</a>
                    </div>
                <?php elseif ($request['status'] === 'approved'): ?>
                    <div class="payment-form">
                        <h6 class="fw-bold mb-3">Payment Collection Form</h6>
                        
                        <form action="<?php echo APP_URL; ?>/bus-season-requests/sao-process-save" method="POST" id="paymentForm_<?php echo $request['id']; ?>">
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="student_payment_amount_<?php echo $request['id']; ?>" class="form-label fw-semibold">
                                        Student Payment Amount (30%) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" 
                                               class="form-control student-payment-input" 
                                               id="student_payment_amount_<?php echo $request['id']; ?>" 
                                               name="student_payment_amount" 
                                               step="0.01" 
                                               min="0" 
                                               required
                                               data-request-id="<?php echo $request['id']; ?>"
                                               placeholder="Enter student payment (30%)">
                                    </div>
                                    <small class="text-muted">This is 30% of the total season value</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="season_rate_<?php echo $request['id']; ?>" class="form-label fw-semibold">
                                        Season Rate (Optional)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" 
                                               class="form-control season-rate-input" 
                                               id="season_rate_<?php echo $request['id']; ?>" 
                                               name="season_rate" 
                                               step="0.01" 
                                               min="0"
                                               data-request-id="<?php echo $request['id']; ?>"
                                               placeholder="Auto-calculated if not provided">
                                    </div>
                                    <small class="text-muted">Leave empty to auto-calculate from student payment</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="payment_method_<?php echo $request['id']; ?>" class="form-label fw-semibold">
                                        Payment Method <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" 
                                            id="payment_method_<?php echo $request['id']; ?>" 
                                            name="payment_method" 
                                            required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="payment_reference_<?php echo $request['id']; ?>" class="form-label fw-semibold">
                                        Payment Reference
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="payment_reference_<?php echo $request['id']; ?>" 
                                           name="payment_reference" 
                                           placeholder="Receipt number, transaction ID, etc.">
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="notes_<?php echo $request['id']; ?>" class="form-label fw-semibold">
                                        Notes
                                    </label>
                                    <textarea class="form-control" 
                                              id="notes_<?php echo $request['id']; ?>" 
                                              name="notes" 
                                              rows="2" 
                                              placeholder="Any additional notes..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Payment Breakdown (Auto-calculated) -->
                            <div class="payment-breakdown" id="paymentBreakdown_<?php echo $request['id']; ?>" style="display: none;">
                                <h6 class="fw-bold mb-3">Payment Breakdown</h6>
                                <div class="payment-item">
                                    <span>Student Payment (30%):</span>
                                    <span class="text-success" id="displayStudentPayment_<?php echo $request['id']; ?>">Rs. 0.00</span>
                                </div>
                                <div class="payment-item">
                                    <span>SLGTI Payment (35%):</span>
                                    <span id="displaySLGTIPayment_<?php echo $request['id']; ?>">Rs. 0.00</span>
                                </div>
                                <div class="payment-item">
                                    <span>CTB Payment (35%):</span>
                                    <span id="displayCTBPayment_<?php echo $request['id']; ?>">Rs. 0.00</span>
                                </div>
                                <div class="payment-item total">
                                    <span>Total Season Value (100%):</span>
                                    <span id="displayTotalValue_<?php echo $request['id']; ?>">Rs. 0.00</span>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Collect Payment
                                </button>
                                <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                            </div>
                        </form>
                    </div>
                <?php elseif ($request['status'] === 'hod_approved'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Waiting for Second Approval</strong> - This request has been approved by HOD but is waiting for second approval (DIR/DPA/DPI/REG) before payment collection can proceed.
                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="alert-link">View details</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Request Pending</strong> - This request is still pending HOD approval. Payment collection will be available after approval.
                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $request['id']; ?>" class="alert-link">View details</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle student payment input for all forms
    document.querySelectorAll('.student-payment-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const requestId = this.getAttribute('data-request-id');
            const studentPayment = parseFloat(this.value) || 0;
            
            if (studentPayment > 0) {
                // Calculate total: Student pays 30%, so total = student_payment / 0.30
                const totalValue = studentPayment / 0.30;
                
                // Calculate other payments
                const slgtiPayment = totalValue * 0.35;
                const ctbPayment = totalValue * 0.35;
                
                // Update display
                document.getElementById('displayStudentPayment_' + requestId).textContent = 'Rs. ' + studentPayment.toFixed(2);
                document.getElementById('displaySLGTIPayment_' + requestId).textContent = 'Rs. ' + slgtiPayment.toFixed(2);
                document.getElementById('displayCTBPayment_' + requestId).textContent = 'Rs. ' + ctbPayment.toFixed(2);
                document.getElementById('displayTotalValue_' + requestId).textContent = 'Rs. ' + totalValue.toFixed(2);
                
                // Auto-fill season rate if empty
                const seasonRateInput = document.getElementById('season_rate_' + requestId);
                if (!seasonRateInput.value || seasonRateInput.value == '0') {
                    seasonRateInput.value = totalValue.toFixed(2);
                }
                
                // Show breakdown
                document.getElementById('paymentBreakdown_' + requestId).style.display = 'block';
            } else {
                document.getElementById('paymentBreakdown_' + requestId).style.display = 'none';
            }
        });
    });
    
    // Handle season rate input (optional - for manual entry)
    document.querySelectorAll('.season-rate-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const requestId = this.getAttribute('data-request-id');
            const seasonRate = parseFloat(this.value) || 0;
            const studentPaymentInput = document.getElementById('student_payment_amount_' + requestId);
            const studentPayment = parseFloat(studentPaymentInput.value) || 0;
            
            if (seasonRate > 0 && studentPayment > 0) {
                // Recalculate if season rate is manually set
                const slgtiPayment = seasonRate * 0.35;
                const ctbPayment = seasonRate * 0.35;
                
                // Update display
                document.getElementById('displayStudentPayment_' + requestId).textContent = 'Rs. ' + studentPayment.toFixed(2);
                document.getElementById('displaySLGTIPayment_' + requestId).textContent = 'Rs. ' + slgtiPayment.toFixed(2);
                document.getElementById('displayCTBPayment_' + requestId).textContent = 'Rs. ' + ctbPayment.toFixed(2);
                document.getElementById('displayTotalValue_' + requestId).textContent = 'Rs. ' + seasonRate.toFixed(2);
                
                // Show breakdown
                document.getElementById('paymentBreakdown_' + requestId).style.display = 'block';
            }
        });
    });
});
</script>

