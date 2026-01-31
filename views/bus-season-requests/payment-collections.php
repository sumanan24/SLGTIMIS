<style>
    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-paid { background-color: #cff4fc; color: #055160; }
    .status-processing { background-color: #fff3cd; color: #856404; }
    .status-issued { background-color: #d1e7dd; color: #0f5132; }
    
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
        padding: 1rem;
        margin-bottom: 2rem;
    }

    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 600;
        border: none;
        padding: 1rem 1.5rem;
    }

    .nav-tabs .nav-link.active {
        color: #198754;
        border-bottom: 3px solid #198754;
        background: none;
    }
</style>

<div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-2">
                <i class="fas fa-money-bill-wave me-2 text-success"></i>
                Bus Season Payment Tracking
            </h2>
            <p class="text-muted mb-0">Manage and process student bus season tickets</p>
        </div>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-file-excel me-2"></i>Export Excel
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/bus-season-requests/export-payments?<?php echo http_build_query(array_merge($filters, ['status' => 'paid'])); ?>">Export Paid Seasons</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/bus-season-requests/export-payments?<?php echo http_build_query(array_merge($filters, ['status' => 'processing'])); ?>">Export Processing Seasons</a></li>
                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/bus-season-requests/export-payments?<?php echo http_build_query(array_merge($filters, ['status' => 'issued'])); ?>">Export Issued Seasons</a></li>
                </ul>
            </div>
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
    
    <div class="filter-card">
        <form method="GET" action="<?php echo APP_URL; ?>/bus-season-requests/payment-collections" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">Season Year</label>
                <select class="form-select" name="season_year">
                    <option value="">All Years</option>
                    <?php foreach ($academicYears as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo ($filters['season_year'] == $year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Month</label>
                <input type="month" class="form-control" name="month" value="<?php echo htmlspecialchars($filters['month'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Student ID</label>
                <input type="text" class="form-control" name="student_id" value="<?php echo htmlspecialchars($filters['student_id']); ?>" placeholder="Enter ID">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search & Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Tabs for different statuses -->
    <ul class="nav nav-tabs mb-4" id="paymentTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="paid-tab" data-bs-toggle="tab" href="#paid" role="tab">
                Paid
                <span class="badge bg-info ms-1"><?php echo count(array_filter($collections, fn($c) => strtolower($c['payment_status'] ?? '') === 'paid')); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="processing-tab" data-bs-toggle="tab" href="#processing" role="tab">
                Processing
                <span class="badge bg-warning ms-1"><?php echo count(array_filter($collections, fn($c) => strtolower($c['payment_status'] ?? '') === 'processing')); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="issued-tab" data-bs-toggle="tab" href="#issued" role="tab">
                Issued
                <span class="badge bg-success ms-1"><?php echo count(array_filter($collections, fn($c) => strtolower($c['payment_status'] ?? '') === 'issued')); ?></span>
            </a>
        </li>
    </ul>

    <div class="tab-content" id="paymentTabsContent">
        <!-- Paid Table -->
        <div class="tab-pane fade show active" id="paid" role="tabpanel">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Paid Seasons</h5>
                    <button onclick="bulkUpdate('processing')" id="btnBulkProcess" class="btn btn-warning btn-sm" disabled>
                        <i class="fas fa-spinner fa-spin me-1"></i> Mark Selected as Processing
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40"><input type="checkbox" id="checkAllPaid"></th>
                                <th>Student Details</th>
                                <th>Route</th>
                                <th>NIC</th>
                                <th>Paid Amount</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $paidItems = array_filter($collections, fn($c) => strtolower($c['payment_status'] ?? '') === 'paid');
                            if (empty($paidItems)): ?>
                                <tr><td colspan="7" class="text-center py-4">No records found</td></tr>
                            <?php else: foreach ($paidItems as $c): ?>
                                <tr>
                                    <td><input type="checkbox" class="paid-check" value="<?php echo $c['payment_id']; ?>"></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($c['student_fullname'] ?? 'N/A'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($c['payment_student_id'] ?? $c['profile_student_id'] ?? $c['request_student_id'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($c['route_from'] ?? 'N/A'); ?> → <?php echo htmlspecialchars($c['route_to'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['student_nic'] ?? '-'); ?></td>
                                    <td class="fw-bold text-success">Rs. <?php echo number_format($c['paid_amount'] ?? 0, 2); ?></td>
                                    <td><?php echo !empty($c['payment_date']) ? date('M d, Y', strtotime($c['payment_date'])) : 'N/A'; ?></td>
                                    <td class="text-end">
                                        <button onclick="updateStatus(<?php echo $c['payment_id']; ?>, 'processing')" class="btn btn-sm btn-outline-warning">
                                            Process
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Processing Table -->
        <div class="tab-pane fade" id="processing" role="tabpanel">
            <div class="table-container">
                <h5 class="fw-bold mb-3">Currently Processing</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student Details</th>
                                <th>Route</th>
                                <th>NIC</th>
                                <th>Paid Amount</th>
                                <th>Processing Started</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $procItems = array_filter($collections, fn($c) => strtolower($c['payment_status'] ?? '') === 'processing');
                            if (empty($procItems)): ?>
                                <tr><td colspan="6" class="text-center py-4">No records found</td></tr>
                            <?php else: foreach ($procItems as $c): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($c['student_fullname'] ?? 'N/A'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($c['payment_student_id'] ?? $c['profile_student_id'] ?? $c['request_student_id'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($c['route_from'] ?? 'N/A'); ?> → <?php echo htmlspecialchars($c['route_to'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['student_nic'] ?? '-'); ?></td>
                                    <td class="fw-bold text-success">Rs. <?php echo number_format($c['paid_amount'] ?? 0, 2); ?></td>
                                    <td><?php echo !empty($c['updated_at']) ? date('M d, Y', strtotime($c['updated_at'])) : 'N/A'; ?></td>
                                    <td class="text-end">
                                        <button onclick="updateStatus(<?php echo $c['payment_id']; ?>, 'issued')" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#issueModal_<?php echo $c['payment_id']; ?>">
                                            Issue Season
                                        </button>

                                        <!-- Issue Modal -->
                                        <div class="modal fade" id="issueModal_<?php echo $c['payment_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-start">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title">Issue Season Ticket</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="mb-3">Confirm the final season ticket details for <strong><?php echo htmlspecialchars($c['student_fullname'] ?? 'N/A'); ?></strong>.</p>
                                                        
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Student Portion (30%)</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">Rs.</span>
                                                                    <input type="number" step="0.01" class="form-control student-portion-input" 
                                                                           id="student_portion_<?php echo $c['payment_id']; ?>" 
                                                                           value="<?php echo number_format($c['paid_amount'], 2, '.', ''); ?>"
                                                                           data-paid-amount="<?php echo $c['paid_amount']; ?>"
                                                                           data-payment-id="<?php echo $c['payment_id']; ?>">
                                                                </div>
                                                                <small class="text-muted">Initially paid: Rs. <?php echo number_format($c['paid_amount'], 2); ?></small>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Actual Total Price (100%)</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">Rs.</span>
                                                                    <input type="number" step="0.01" class="form-control total-price-input" 
                                                                           id="actual_price_<?php echo $c['payment_id']; ?>" 
                                                                           value="<?php echo !empty($c['paid_amount']) ? number_format($c['paid_amount'] / 0.30, 2, '.', '') : ''; ?>"
                                                                           data-payment-id="<?php echo $c['payment_id']; ?>">
                                                                </div>
                                                            </div>

                                                            <div class="col-12">
                                                                <label class="form-label fw-bold">Reference Number (Optional)</label>
                                                                <input type="text" class="form-control" id="reference_<?php echo $c['payment_id']; ?>" 
                                                                       value="<?php echo htmlspecialchars($c['payment_reference'] ?? ''); ?>" 
                                                                       placeholder="e.g. Receipt #, Bank Ref">
                                                            </div>
                                                        </div>

                                                        <div class="mt-3 p-3 bg-light rounded border">
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span class="fw-bold">Balance to Pay:</span>
                                                                <span class="fw-bold text-danger" id="calc_balance_<?php echo $c['payment_id']; ?>">Rs. 0.00</span>
                                                            </div>
                                                            <hr class="my-2">
                                                            <div class="d-flex justify-content-between small mb-1">
                                                                <span>SLGTI Contribution (35%):</span>
                                                                <span id="calc_slgti_<?php echo $c['payment_id']; ?>">Rs. 0.00</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between small">
                                                                <span>CTB Contribution (35%):</span>
                                                                <span id="calc_ctb_<?php echo $c['payment_id']; ?>">Rs. 0.00</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button onclick="submitIssue(<?php echo $c['payment_id']; ?>)" class="btn btn-success px-4">Complete Issuance</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Issued Table -->
        <div class="tab-pane fade" id="issued" role="tabpanel">
            <div class="table-container">
                <h5 class="fw-bold mb-3">Issued Season Tickets</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student Details</th>
                                <th>NIC</th>
                                <th>Route</th>
                                <th>Student Paid (30%)</th>
                                <th>Total Value</th>
                                <th>SLGTI (35%)</th>
                                <th>CTB (35%)</th>
                                <th>Issued Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $issuedItems = array_filter($collections, fn($c) => strtolower($c['payment_status'] ?? '') === 'issued');
                            if (empty($issuedItems)): ?>
                                <tr><td colspan="9" class="text-center py-4">No records found</td></tr>
                            <?php else: foreach ($issuedItems as $c): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($c['student_fullname'] ?? 'N/A'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($c['payment_student_id'] ?? $c['profile_student_id'] ?? $c['request_student_id'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['student_nic'] ?? '-'); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($c['route_from'] ?? 'N/A'); ?> → <?php echo htmlspecialchars($c['route_to'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td class="fw-bold text-success">Rs. <?php echo number_format($c['student_paid'] ?? 0, 2); ?></td>
                                    <td class="fw-bold text-primary">Rs. <?php echo number_format($c['total_amount'] ?? 0, 2); ?></td>
                                    <td class="small">Rs. <?php echo number_format($c['slgti_paid'] ?? 0, 2); ?></td>
                                    <td class="small">Rs. <?php echo number_format($c['ctb_paid'] ?? 0, 2); ?></td>
                                    <td><?php echo !empty($c['issued_at']) ? date('M d, Y', strtotime($c['issued_at'])) : 'N/A'; ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/view?id=<?php echo $c['request_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox handling for Paid tab
    const checkAllPaid = document.getElementById('checkAllPaid');
    const paidChecks = document.querySelectorAll('.paid-check');
    const btnBulkProcess = document.getElementById('btnBulkProcess');

    if (checkAllPaid) {
        checkAllPaid.addEventListener('change', function() {
            paidChecks.forEach(cb => cb.checked = this.checked);
            toggleBulkBtn(paidChecks, btnBulkProcess);
        });
    }

    paidChecks.forEach(cb => {
        cb.addEventListener('change', () => toggleBulkBtn(paidChecks, btnBulkProcess));
    });
});

function toggleBulkBtn(checks, btn) {
    const anyChecked = Array.from(checks).some(cb => cb.checked);
    btn.disabled = !anyChecked;
}

function updateStatus(paymentId, status) {
    if (status === 'issued') return; // Handled by separate modal/function
    
    let confirmMsg = 'Move to processing?';
    if (!confirm(confirmMsg)) return;
    
    const formData = new FormData();
    formData.append('payment_id', paymentId);
    formData.append('status', status);
    
    fetch('<?php echo APP_URL; ?>/bus-season-requests/update-payment-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function submitIssue(paymentId) {
    const actualPrice = document.getElementById('actual_price_' + paymentId).value;
    const studentPortion = document.getElementById('student_portion_' + paymentId).value;
    const reference = document.getElementById('reference_' + paymentId).value;
    
    if (!actualPrice || actualPrice <= 0) {
        alert('Please enter a valid actual price');
        return;
    }
    
    const formData = new FormData();
    formData.append('payment_id', paymentId);
    formData.append('status', 'issued');
    formData.append('actual_price', actualPrice);
    formData.append('student_portion', studentPortion);
    formData.append('payment_reference', reference);
    
    fetch('<?php echo APP_URL; ?>/bus-season-requests/update-payment-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error updating status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('student-portion-input')) {
        const paymentId = e.target.getAttribute('data-payment-id');
        const initialPaid = parseFloat(e.target.getAttribute('data-paid-amount')) || 0;
        const studentPortion = parseFloat(e.target.value) || 0;
        
        // Calculate Total from Student Portion (Student Portion is 30%)
        const totalAmount = studentPortion / 0.30;
        document.getElementById('actual_price_' + paymentId).value = totalAmount.toFixed(2);
        
        updateCalculations(paymentId, totalAmount, studentPortion, initialPaid);
    }
    
    if (e.target.classList.contains('total-price-input')) {
        const paymentId = e.target.getAttribute('data-payment-id');
        const studentPortionInput = document.getElementById('student_portion_' + paymentId);
        const initialPaid = parseFloat(studentPortionInput.getAttribute('data-paid-amount')) || 0;
        const totalAmount = parseFloat(e.target.value) || 0;
        
        // Calculate Student Portion from Total (30%)
        const studentPortion = totalAmount * 0.30;
        studentPortionInput.value = studentPortion.toFixed(2);
        
        updateCalculations(paymentId, totalAmount, studentPortion, initialPaid);
    }
});

function updateCalculations(paymentId, total, studentPortion, initialPaid) {
    const slgti = total * 0.35;
    const ctb = total * 0.35;
    const balance = studentPortion - initialPaid;
    
    document.getElementById('calc_slgti_' + paymentId).textContent = 'Rs. ' + slgti.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('calc_ctb_' + paymentId).textContent = 'Rs. ' + ctb.toLocaleString(undefined, {minimumFractionDigits: 2});
    
    const balanceEl = document.getElementById('calc_balance_' + paymentId);
    balanceEl.textContent = 'Rs. ' + balance.toLocaleString(undefined, {minimumFractionDigits: 2});
    
    if (balance > 0) {
        balanceEl.className = 'fw-bold text-danger';
    } else if (balance < 0) {
        balanceEl.className = 'fw-bold text-primary';
        balanceEl.textContent = 'Credit: ' + balanceEl.textContent;
    } else {
        balanceEl.className = 'fw-bold text-success';
    }
}

// Trigger initial calculation for modals when they open
document.addEventListener('shown.bs.modal', function (e) {
    const modal = e.target;
    const input = modal.querySelector('.student-portion-input');
    if (input) {
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }
});

function bulkUpdate(status) {
    const checks = status === 'processing' ? document.querySelectorAll('.paid-check:checked') : document.querySelectorAll('.proc-check:checked');
    const ids = Array.from(checks).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    let confirmMsg = `Are you sure you want to update ${ids.length} records to ${status}?`;
    if (!confirm(confirmMsg)) return;
    
    const formData = new FormData();
    ids.forEach(id => formData.append('payment_ids[]', id));
    formData.append('status', status);
    
    fetch('<?php echo APP_URL; ?>/bus-season-requests/bulk-update-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Error performing bulk update');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
