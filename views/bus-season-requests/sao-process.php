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
    
    <div class="table-container">
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
                                            <i class="fas fa-check-circle me-1"></i>Collected
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <?php if (!(isset($request['has_payment']) && $request['has_payment'] > 0) && $status !== 'paid'): ?>
                                            <button type="button" 
                                                    class="btn btn-success btn-sm px-3" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal_<?php echo $request['id']; ?>">
                                                <i class="fas fa-cash-register me-1"></i> Collect Payment
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Basic initialization if needed
});
</script>
