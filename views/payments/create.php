<div class="container-fluid px-4 py-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>Create New Payment</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo APP_URL; ?>/payments/create">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="student_search" class="form-label fw-semibold">
                                    Student <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="student_search" 
                                       placeholder="Search by Student ID or Name..." 
                                       autocomplete="off">
                                <input type="hidden" id="student_id" name="student_id" required>
                                <div id="student_search_results" class="position-relative" style="display: none;">
                                    <div class="list-group position-absolute w-100" style="z-index: 1000; max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; margin-top: 0.25rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);">
                                        <!-- Student list will be populated here -->
                                    </div>
                                </div>
                                <div id="selected_student" class="mt-2" style="display: none;">
                                    <div class="alert alert-info mb-0 py-2">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Selected:</strong> <span id="selected_student_text"></span>
                                        <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="clearStudentSelection()">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                    </div>
                                </div>
                                <div class="invalid-feedback">Please select a student.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label fw-semibold">
                                    Payment Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_type" class="form-label fw-semibold">
                                    Payment Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="payment_type" name="payment_type" required>
                                    <option value="">Select Payment Type</option>
                                    <?php
                                    $types = [];
                                    if (isset($paymentReasons) && !empty($paymentReasons)) {
                                        foreach ($paymentReasons as $reason) {
                                            if (!in_array($reason['payment_type'], $types)) {
                                                $types[] = $reason['payment_type'];
                                            }
                                        }
                                        foreach ($types as $type) {
                                            echo '<option value="' . htmlspecialchars($type) . '">' . htmlspecialchars($type) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_reason" class="form-label fw-semibold">
                                    Payment Reason <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="payment_reason" name="payment_reason" required>
                                    <option value="">Select Payment Type First</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_amount" class="form-label fw-semibold">
                                    Payment Amount (Rs.) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                       step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="pays_qty" class="form-label fw-semibold">Quantity</label>
                                <input type="number" class="form-control" id="pays_qty" name="pays_qty" 
                                       value="1" min="1" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label fw-semibold">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">Select Method</option>
                                    <option value="CASH">Cash</option>
                                    <option value="BANK">Bank Transfer</option>
                                    <option value="CHEQUE">Cheque</option>
                                    <option value="ONLINE">Online Payment</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="reference_no" class="form-label fw-semibold">Reference Number</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no" 
                                       placeholder="Optional reference number">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="approved" class="form-label fw-semibold">Approval Status</label>
                                <select class="form-select" id="approved" name="approved">
                                    <option value="0">Pending</option>
                                    <option value="1">Approved</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-4">
                                <label for="payment_notes" class="form-label fw-semibold">Notes</label>
                                <textarea class="form-control" id="payment_notes" name="payment_notes" 
                                          rows="3" placeholder="Additional notes about this payment"></textarea>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Create Payment
                            </button>
                            <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-secondary">
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
    const paymentTypeSelect = document.getElementById('payment_type');
    const paymentReasonSelect = document.getElementById('payment_reason');
    
    const paymentReasons = <?php echo json_encode($paymentReasons ?? []); ?>;
    
    paymentTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        paymentReasonSelect.innerHTML = '<option value="">Select Payment Reason</option>';
        
        if (selectedType) {
            const filteredReasons = paymentReasons.filter(r => r.payment_type === selectedType);
            filteredReasons.forEach(function(reason) {
                const option = document.createElement('option');
                option.value = reason.payment_reason;
                option.textContent = reason.payment_reason;
                paymentReasonSelect.appendChild(option);
            });
        }
    });
    
    // Student search functionality
    const students = <?php echo json_encode($students ?? []); ?>;
    const studentSearchInput = document.getElementById('student_search');
    const studentIdInput = document.getElementById('student_id');
    const studentSearchResults = document.getElementById('student_search_results');
    const selectedStudentDiv = document.getElementById('selected_student');
    const selectedStudentText = document.getElementById('selected_student_text');
    
    let searchTimeout;
    
    studentSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length === 0) {
            studentSearchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(function() {
            filterStudents(searchTerm);
        }, 200);
    });
    
    function filterStudents(searchTerm) {
        const filtered = students.filter(function(student) {
            const studentId = (student.student_id || '').toLowerCase();
            const studentName = (student.student_fullname || '').toLowerCase();
            return studentId.includes(searchTerm) || studentName.includes(searchTerm);
        });
        
        displayResults(filtered);
    }
    
    function displayResults(filteredStudents) {
        const resultsContainer = studentSearchResults.querySelector('.list-group');
        resultsContainer.innerHTML = '';
        
        if (filteredStudents.length === 0) {
            resultsContainer.innerHTML = '<div class="list-group-item text-muted">No students found</div>';
            studentSearchResults.style.display = 'block';
            return;
        }
        
        // Limit to 50 results for performance
        const limitedResults = filteredStudents.slice(0, 50);
        
        limitedResults.forEach(function(student) {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = '<div class="fw-semibold">' + escapeHtml(student.student_id) + '</div>' +
                           '<div class="small text-muted">' + escapeHtml(student.student_fullname || '') + '</div>';
            
            item.addEventListener('click', function(e) {
                e.preventDefault();
                selectStudent(student);
            });
            
            resultsContainer.appendChild(item);
        });
        
        if (filteredStudents.length > 50) {
            const moreItem = document.createElement('div');
            moreItem.className = 'list-group-item text-muted small';
            moreItem.textContent = '... and ' + (filteredStudents.length - 50) + ' more. Please refine your search.';
            resultsContainer.appendChild(moreItem);
        }
        
        studentSearchResults.style.display = 'block';
    }
    
    function selectStudent(student) {
        studentIdInput.value = student.student_id;
        selectedStudentText.textContent = student.student_id + ' - ' + (student.student_fullname || '');
        selectedStudentDiv.style.display = 'block';
        studentSearchResults.style.display = 'none';
        studentSearchInput.value = '';
        studentIdInput.classList.remove('is-invalid');
        studentIdInput.classList.add('is-valid');
    }
    
    function clearStudentSelection() {
        studentIdInput.value = '';
        selectedStudentDiv.style.display = 'none';
        studentSearchInput.value = '';
        studentSearchResults.style.display = 'none';
        studentIdInput.classList.remove('is-valid', 'is-invalid');
        studentSearchInput.focus();
    }
    
    // Make clearStudentSelection available globally
    window.clearStudentSelection = clearStudentSelection;
    
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!studentSearchInput.contains(e.target) && !studentSearchResults.contains(e.target)) {
            studentSearchResults.style.display = 'none';
        }
    });
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return (text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!studentIdInput.value) {
                e.preventDefault();
                studentIdInput.classList.add('is-invalid');
                studentSearchInput.focus();
            }
        });
    }
});
</script>
