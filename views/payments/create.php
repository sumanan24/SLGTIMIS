<?php
$paymentReasons = $paymentReasons ?? [];
$paymentMethods = $paymentMethods ?? [];
$cashierName = $cashierName ?? ($_SESSION['user_name'] ?? 'Staff');
$typeGroups = [];
foreach ($paymentReasons as $row) {
    $typeName = (string) ($row['payment_type'] ?? '');
    $reasonName = (string) ($row['payment_reason'] ?? '');
    if ($typeName === '' || $reasonName === '') {
        continue;
    }
    $typeGroups[$typeName][] = $reasonName;
}
?>
<style>
.pos-counter {
    --pos-navy: #0c2340;
    --pos-navy-2: #16365c;
    --pos-line: #d7dee7;
    --pos-bg: #f3f5f8;
    background: var(--pos-bg);
    margin: -0.25rem -0.5rem 0;
    padding: 1rem 1rem 5.5rem;
    min-height: calc(100vh - 70px);
}
.pos-header {
    background: var(--pos-navy);
    color: #fff;
    border-radius: 0.75rem;
    padding: 0.9rem 1.1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}
.pos-header h1 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}
.pos-header .pos-meta {
    font-size: 0.85rem;
    opacity: 0.9;
}
.pos-panel {
    background: #fff;
    border: 1px solid var(--pos-line);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.85rem;
}
.pos-panel h2 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--pos-navy);
    margin: 0 0 0.75rem;
}
.pos-search-wrap { position: relative; }
.pos-search-wrap .form-control {
    height: 3rem;
    font-size: 1.05rem;
    padding-left: 2.5rem;
}
.pos-search-wrap .pos-search-icon {
    position: absolute;
    left: 0.85rem;
    top: 0.85rem;
    color: #6c757d;
}
.pos-results {
    position: absolute;
    z-index: 30;
    left: 0;
    right: 0;
    top: calc(100% + 0.25rem);
    background: #fff;
    border: 1px solid var(--pos-line);
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1.25rem rgba(12, 35, 64, 0.12);
    max-height: 320px;
    overflow: auto;
}
.pos-result {
    display: flex;
    width: 100%;
    text-align: left;
    border: 0;
    background: #fff;
    padding: 0.65rem 0.85rem;
    border-bottom: 1px solid #eef1f4;
}
.pos-result:hover, .pos-result.is-active { background: #eef4fb; }
.pos-student {
    display: flex;
    gap: 0.9rem;
    align-items: center;
}
.pos-photo, .pos-photo-fallback {
    width: 72px;
    height: 72px;
    border-radius: 0.6rem;
    object-fit: cover;
    background: #e8eef5;
    flex: 0 0 72px;
}
.pos-photo-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: var(--pos-navy);
    font-size: 1.25rem;
}
.pos-choice-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.pos-choice {
    border: 1px solid var(--pos-line);
    background: #fff;
    color: var(--pos-navy);
    border-radius: 0.6rem;
    padding: 0.65rem 0.85rem;
    font-weight: 600;
    min-height: 2.75rem;
}
.pos-choice.is-selected {
    background: var(--pos-navy);
    border-color: var(--pos-navy);
    color: #fff;
}
.pos-choice:disabled { opacity: 0.45; }
.pos-amount-input {
    font-size: 2rem;
    font-weight: 700;
    height: auto;
    text-align: right;
    color: var(--pos-navy);
}
.pos-keypad {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.4rem;
}
.pos-keypad button {
    height: 3rem;
    font-size: 1.15rem;
    font-weight: 700;
    border: 1px solid var(--pos-line);
    background: #f8fafc;
    border-radius: 0.5rem;
    color: var(--pos-navy);
}
.pos-keypad button:hover { background: #e7eef6; }
.pos-method {
    min-width: 8.5rem;
    text-align: left;
}
.pos-summary {
    position: sticky;
    top: 0.75rem;
}
.pos-total {
    background: var(--pos-navy);
    color: #fff;
    border-radius: 0.75rem;
    padding: 0.9rem 1rem;
    margin-bottom: 0.85rem;
}
.pos-total span { display: block; font-size: 0.8rem; opacity: 0.8; }
.pos-total strong { font-size: 2rem; line-height: 1.1; }
.pos-summary-line {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.35rem 0;
    border-bottom: 1px solid #eef1f4;
    font-size: 0.92rem;
}
.pos-summary-line b { text-align: right; }
.pos-bill-item, .pos-cart-item {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: flex-start;
    padding: 0.55rem 0;
    border-bottom: 1px solid #eef1f4;
}
.pos-cart-item button {
    border: 0;
    background: transparent;
    color: #9b1c1c;
    padding: 0 0.15rem;
}
.pos-mobile-bar {
    display: none;
}
@media (max-width: 991.98px) {
    .pos-summary { position: static; }
    .pos-mobile-bar {
        display: flex;
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 40;
        background: #fff;
        border-top: 1px solid var(--pos-line);
        padding: 0.65rem 0.85rem;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .pos-mobile-bar strong { color: var(--pos-navy); font-size: 1.25rem; }
}
</style>

<div class="pos-counter">
    <header class="pos-header">
        <div>
            <h1><i class="fas fa-cash-register me-2"></i>SLGTI – Student Payment Counter</h1>
            <div class="pos-meta">
                <span id="posClock"><?php echo date('d M Y, h:i A'); ?></span>
                <span class="mx-2">·</span>
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($cashierName); ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo APP_URL; ?>/payment-types" class="btn btn-outline-light btn-sm">
                <i class="fas fa-tags me-1"></i>Payment Types
            </a>
            <a href="<?php echo APP_URL; ?>/payments" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Payments
            </a>
        </div>
    </header>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($typeGroups)): ?>
        <div class="alert alert-warning">
            No payment types are configured.
            <a href="<?php echo APP_URL; ?>/payment-types/create">Add a payment type and reason</a>
            before collecting a payment.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>/payments/create" id="posPaymentForm" autocomplete="off">
        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($formToken ?? ''); ?>">
        <input type="hidden" name="student_id" id="student_id" value="">
        <input type="hidden" name="payment_type" id="payment_type" value="">
        <input type="hidden" name="payment_reason" id="payment_reason" value="">
        <input type="hidden" name="payment_method" id="payment_method" value="">
        <input type="hidden" name="approved" id="approved" value="0">

        <div class="row g-3">
            <div class="col-lg-8">
                <section class="pos-panel">
                    <h2><i class="fas fa-search me-2"></i>Find student</h2>
                    <div class="pos-search-wrap">
                        <i class="fas fa-search pos-search-icon"></i>
                        <input type="search" class="form-control" id="student_search"
                               placeholder="Student ID, name, or NIC" aria-label="Search student"
                               aria-autocomplete="list" aria-controls="posResults">
                        <div id="posResults" class="pos-results d-none" role="listbox"></div>
                    </div>
                    <div id="studentError" class="text-danger small mt-2 d-none">Select a student before collecting the payment.</div>
                    <div id="selectedStudent" class="pos-student mt-3 d-none">
                        <img id="studentPhoto" class="pos-photo d-none" alt="">
                        <div id="studentInitials" class="pos-photo-fallback">?</div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" id="studentName">—</div>
                            <div class="small text-muted" id="studentMeta">—</div>
                            <div class="small" id="studentCourse">—</div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="changeStudent">
                            Change
                        </button>
                    </div>
                </section>

                <section class="pos-panel">
                    <h2><i class="fas fa-tags me-2"></i>Payment type</h2>
                    <div class="pos-choice-grid" id="typeChoices">
                        <?php foreach ($typeGroups as $typeName => $reasons): ?>
                            <button type="button" class="pos-choice" data-type="<?php echo htmlspecialchars($typeName); ?>">
                                <?php echo htmlspecialchars($typeName); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div id="typeError" class="text-danger small mt-2 d-none">Select a payment type and reason.</div>
                    <h2 class="mt-3"><i class="fas fa-list me-2"></i>Reason</h2>
                    <div class="pos-choice-grid" id="reasonChoices">
                        <span class="text-muted small">Select a payment type first.</span>
                    </div>
                </section>

                <section class="pos-panel">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <h2><i class="fas fa-coins me-2"></i>Item amount (Rs.)</h2>
                            <div class="input-group input-group-lg mb-2">
                                <span class="input-group-text">Rs.</span>
                                <input type="text" inputmode="decimal" class="form-control pos-amount-input" id="payment_amount"
                                       value="" placeholder="0.00">
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">This item</span>
                                <strong id="lineTotal">Rs. 0.00</strong>
                            </div>
                            <div id="amountError" class="text-danger small mb-2 d-none">Enter an amount greater than zero.</div>
                            <div class="pos-keypad" id="posKeypad" aria-label="Amount keypad">
                                <?php foreach (['1','2','3','4','5','6','7','8','9','00','0','.'] as $key): ?>
                                    <button type="button" data-key="<?php echo $key; ?>"><?php echo $key; ?></button>
                                <?php endforeach; ?>
                                <button type="button" data-key="back" aria-label="Backspace"><i class="fas fa-delete-left"></i></button>
                                <button type="button" data-key="clear" class="fw-semibold" style="grid-column: span 2;">Clear</button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h2>Quantity</h2>
                            <div class="input-group mb-3">
                                <button class="btn btn-outline-secondary" type="button" id="qtyMinus" aria-label="Decrease quantity">−</button>
                                <input type="number" class="form-control text-center" id="pays_qty" name="pays_qty" value="1" min="1" max="999" required>
                                <button class="btn btn-outline-secondary" type="button" id="qtyPlus" aria-label="Increase quantity">+</button>
                            </div>
                            <label for="payment_date" class="form-label fw-semibold">Payment date</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                            <button type="button" class="btn w-100 mt-3 text-white" id="addItemBtn" style="background:#16365c;">
                                <i class="fas fa-plus me-1"></i>Add item
                            </button>
                        </div>
                    </div>
                    <div id="itemError" class="text-danger small mt-2 d-none">Add at least one payment item.</div>
                    <div id="itemList" class="mt-3"></div>
                    <div id="itemFields"></div>
                </section>

                <section class="pos-panel">
                    <h2><i class="fas fa-wallet me-2"></i>Payment method</h2>
                    <div class="pos-choice-grid" id="methodChoices">
                        <?php foreach ($paymentMethods as $code => $method): ?>
                            <button type="button" class="pos-choice pos-method" data-method="<?php echo htmlspecialchars($code); ?>">
                                <i class="fas <?php echo htmlspecialchars($method['icon']); ?> me-1"></i>
                                <?php echo htmlspecialchars($method['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div id="referenceWrap" class="mt-3 d-none">
                        <label for="reference_no" class="form-label fw-semibold">Reference number <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" class="form-control" id="reference_no" name="reference_no" placeholder="Bank slip, cheque, or transfer reference">
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-link px-0" type="button" data-bs-toggle="collapse" data-bs-target="#notesCollapse">
                            <i class="fas fa-sticky-note me-1"></i>Notes
                        </button>
                        <div class="collapse" id="notesCollapse">
                            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2" placeholder="Optional note for this receipt"></textarea>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-lg-4">
                <aside class="pos-summary">
                    <div class="pos-total">
                        <span>Total to collect</span>
                        <strong id="summaryTotal">Rs. 0.00</strong>
                    </div>
                    <section class="pos-panel">
                        <h2>Payment summary</h2>
                        <div class="pos-summary-line"><span>Student</span><b id="sumStudent">—</b></div>
                        <div class="pos-summary-line"><span>Items</span><b id="sumCount">0</b></div>
                        <div id="sumItems"></div>
                        <div class="pos-summary-line"><span>Method</span><b id="sumMethod">—</b></div>
                        <div class="mt-3">
                            <div class="form-label fw-semibold mb-2">Approval status</div>
                            <div class="pos-choice-grid">
                                <button type="button" class="pos-choice is-selected" data-approved="0" id="approvePending">Pending</button>
                                <button type="button" class="pos-choice" data-approved="1" id="approveYes">Approved</button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-lg w-100 mt-3 text-white" id="collectBtn" style="background:#0c2340;">
                            <i class="fas fa-check me-1"></i>Collect Payment
                        </button>
                        <a href="<?php echo APP_URL; ?>/payments" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                    </section>
                </aside>
            </div>
        </div>
    </form>
</div>

<div class="pos-mobile-bar">
    <div>
        <div class="small text-muted">Total</div>
        <strong id="mobileTotal">Rs. 0.00</strong>
    </div>
    <button type="submit" form="posPaymentForm" class="btn text-white" style="background:#0c2340;">Collect Payment</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var reasonsByType = <?php echo json_encode($typeGroups, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var methodLabels = <?php echo json_encode(array_map(function ($method) {
        return $method['label'];
    }, $paymentMethods), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var searchUrl = <?php echo json_encode(rtrim(APP_URL, '/') . '/payments/search-students'); ?>;

    var form = document.getElementById('posPaymentForm');
    var searchInput = document.getElementById('student_search');
    var resultsBox = document.getElementById('posResults');
    var studentIdInput = document.getElementById('student_id');
    var selectedBox = document.getElementById('selectedStudent');
    var typeInput = document.getElementById('payment_type');
    var reasonInput = document.getElementById('payment_reason');
    var methodInput = document.getElementById('payment_method');
    var amountInput = document.getElementById('payment_amount');
    var qtyInput = document.getElementById('pays_qty');
    var approvedInput = document.getElementById('approved');
    var reasonChoices = document.getElementById('reasonChoices');
    var referenceWrap = document.getElementById('referenceWrap');
    var collectBtn = document.getElementById('collectBtn');
    var itemList = document.getElementById('itemList');
    var itemFields = document.getElementById('itemFields');
    var cart = [];
    var submitting = false;
    var searchTimer = null;
    var searchAbort = null;
    var activeResult = -1;
    var selectedStudent = null;

    function money(value) {
        return 'Rs. ' + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function unitAmount() {
        var raw = String(amountInput.value || '').replace(/,/g, '');
        var amount = parseFloat(raw);
        return isNaN(amount) ? 0 : amount;
    }

    function quantity() {
        var qty = parseInt(qtyInput.value, 10);
        if (isNaN(qty) || qty < 1) {
            return 1;
        }
        return Math.min(qty, 999);
    }

    function lineAmount() {
        return Math.round(unitAmount() * quantity() * 100) / 100;
    }

    function cartTotal() {
        return cart.reduce(function (sum, item) {
            return sum + (item.amount * item.qty);
        }, 0);
    }

    function draftState() {
        var hasType = typeInput.value !== '';
        var hasReason = reasonInput.value !== '';
        var hasAmount = String(amountInput.value || '').trim() !== '';
        if (!hasType && !hasReason && !hasAmount) {
            return 'empty';
        }
        if (hasType && hasReason && unitAmount() > 0) {
            return 'ready';
        }
        return 'partial';
    }

    function renderCart() {
        if (!cart.length) {
            itemList.innerHTML = '<div class="text-muted small">No items added yet. Add one or more payments for this student.</div>';
        } else {
            itemList.innerHTML = cart.map(function (item, index) {
                var total = item.amount * item.qty;
                return '<div class="pos-cart-item">' +
                    '<div><div class="fw-semibold">' + escapeHtml(item.reason) + '</div>' +
                    '<div class="small text-muted">' + escapeHtml(item.type) + ' · ' + item.qty + ' x ' + money(item.amount) + '</div></div>' +
                    '<div class="text-end"><div class="fw-bold">' + money(total) + '</div>' +
                    '<button type="button" data-remove="' + index + '" aria-label="Remove item">Remove</button></div></div>';
            }).join('');
        }
        itemFields.innerHTML = cart.map(function (item, index) {
            return '<input type="hidden" name="items[' + index + '][payment_type]" value="' + escapeHtml(item.type) + '">' +
                '<input type="hidden" name="items[' + index + '][payment_reason]" value="' + escapeHtml(item.reason) + '">' +
                '<input type="hidden" name="items[' + index + '][payment_amount]" value="' + item.amount.toFixed(2) + '">' +
                '<input type="hidden" name="items[' + index + '][pays_qty]" value="' + item.qty + '">';
        }).join('');
        document.getElementById('sumCount').textContent = String(cart.length);
        document.getElementById('sumItems').innerHTML = cart.map(function (item) {
            return '<div class="pos-bill-item"><span>' + escapeHtml(item.reason) + '</span><b>' + money(item.amount * item.qty) + '</b></div>';
        }).join('');
    }

    function clearDraft() {
        typeInput.value = '';
        reasonInput.value = '';
        amountInput.value = '';
        qtyInput.value = '1';
        document.querySelectorAll('#typeChoices .pos-choice, #reasonChoices .pos-choice').forEach(function (item) {
            item.classList.remove('is-selected');
        });
        reasonChoices.innerHTML = '<span class="text-muted small">Select a payment type first.</span>';
    }

    function addDraftItem() {
        if (cart.length >= 20) {
            document.getElementById('itemError').textContent = 'A single collection can include up to 20 items.';
            document.getElementById('itemError').classList.remove('d-none');
            return false;
        }
        if (!typeInput.value || !reasonInput.value) {
            document.getElementById('typeError').classList.remove('d-none');
            return false;
        }
        if (!(unitAmount() > 0)) {
            document.getElementById('amountError').classList.remove('d-none');
            amountInput.focus();
            return false;
        }
        cart.push({
            type: typeInput.value,
            reason: reasonInput.value,
            amount: unitAmount(),
            qty: quantity()
        });
        clearDraft();
        document.getElementById('itemError').classList.add('d-none');
        document.getElementById('typeError').classList.add('d-none');
        document.getElementById('amountError').classList.add('d-none');
        refreshSummary();
        return true;
    }

    function refreshSummary() {
        var label = money(cartTotal());
        document.getElementById('summaryTotal').textContent = label;
        document.getElementById('mobileTotal').textContent = label;
        document.getElementById('lineTotal').textContent = money(lineAmount());
        document.getElementById('sumMethod').textContent = methodLabels[methodInput.value] || '—';
        if (selectedStudent) {
            document.getElementById('sumStudent').textContent = selectedStudent.student_id + ' · ' + (selectedStudent.student_fullname || '');
        } else {
            document.getElementById('sumStudent').textContent = '—';
        }
        var needsReference = methodInput.value !== '' && methodInput.value !== 'CASH';
        referenceWrap.classList.toggle('d-none', !needsReference);
        renderCart();
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
        });
    }

    function initials(name) {
        var parts = String(name || '').trim().split(/\s+/).slice(0, 2);
        return parts.map(function (part) { return part.charAt(0).toUpperCase(); }).join('') || '?';
    }

    function selectStudent(student) {
        selectedStudent = student;
        studentIdInput.value = student.student_id;
        document.getElementById('studentName').textContent = student.student_fullname || 'Student';
        document.getElementById('studentMeta').textContent = student.student_id + (student.student_nic ? ' · NIC ' + student.student_nic : '');
        var courseBits = [student.course_name, student.department_name, student.academic_year ? 'Intake ' + student.academic_year : ''].filter(Boolean);
        document.getElementById('studentCourse').textContent = courseBits.join(' · ') || 'No current enrollment on file';
        var photo = document.getElementById('studentPhoto');
        var fallback = document.getElementById('studentInitials');
        if (student.photo_url) {
            photo.src = student.photo_url;
            photo.alt = student.student_fullname || 'Student photo';
            photo.classList.remove('d-none');
            fallback.classList.add('d-none');
        } else {
            photo.classList.add('d-none');
            fallback.classList.remove('d-none');
            fallback.textContent = initials(student.student_fullname);
        }
        selectedBox.classList.remove('d-none');
        resultsBox.classList.add('d-none');
        searchInput.value = '';
        document.getElementById('studentError').classList.add('d-none');
        refreshSummary();
        var firstType = document.querySelector('#typeChoices .pos-choice');
        if (firstType) {
            firstType.focus();
        }
    }

    function renderResults(students) {
        activeResult = -1;
        if (!students.length) {
            resultsBox.innerHTML = '<div class="p-3 text-muted">No matching students.</div>';
            resultsBox.classList.remove('d-none');
            return;
        }
        resultsBox.innerHTML = students.map(function (student, index) {
            return '<button type="button" class="pos-result" role="option" data-index="' + index + '">' +
                '<span><strong>' + escapeHtml(student.student_id) + '</strong> · ' + escapeHtml(student.student_fullname) +
                '<br><span class="small text-muted">' + escapeHtml(student.student_nic || '') +
                (student.course_name ? ' · ' + escapeHtml(student.course_name) : '') + '</span></span></button>';
        }).join('');
        resultsBox.classList.remove('d-none');
        resultsBox._students = students;
    }

    function runSearch(term) {
        if (searchAbort) {
            searchAbort.abort();
        }
        searchAbort = new AbortController();
        resultsBox.innerHTML = '<div class="p-3 text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Searching…</div>';
        resultsBox.classList.remove('d-none');
        fetch(searchUrl + '?q=' + encodeURIComponent(term), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: searchAbort.signal
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            renderResults((data && data.students) || []);
        }).catch(function (error) {
            if (error.name === 'AbortError') {
                return;
            }
            resultsBox.innerHTML = '<div class="p-3 text-danger">Student search failed. Try again.</div>';
        });
    }

    searchInput.addEventListener('input', function () {
        var term = searchInput.value.trim();
        clearTimeout(searchTimer);
        if (term.length < 2) {
            resultsBox.classList.add('d-none');
            return;
        }
        searchTimer = setTimeout(function () { runSearch(term); }, 250);
    });

    searchInput.addEventListener('keydown', function (event) {
        var buttons = resultsBox.querySelectorAll('.pos-result');
        if (!buttons.length || resultsBox.classList.contains('d-none')) {
            return;
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            activeResult += event.key === 'ArrowDown' ? 1 : -1;
            if (activeResult < 0) activeResult = buttons.length - 1;
            if (activeResult >= buttons.length) activeResult = 0;
            buttons.forEach(function (button, index) {
                button.classList.toggle('is-active', index === activeResult);
            });
        } else if (event.key === 'Enter' && activeResult >= 0) {
            event.preventDefault();
            selectStudent(resultsBox._students[activeResult]);
        } else if (event.key === 'Escape') {
            resultsBox.classList.add('d-none');
        }
    });

    resultsBox.addEventListener('click', function (event) {
        var button = event.target.closest('.pos-result');
        if (!button || !resultsBox._students) {
            return;
        }
        selectStudent(resultsBox._students[parseInt(button.getAttribute('data-index'), 10)]);
    });

    document.getElementById('changeStudent').addEventListener('click', function () {
        selectedStudent = null;
        studentIdInput.value = '';
        selectedBox.classList.add('d-none');
        refreshSummary();
        searchInput.focus();
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.pos-search-wrap')) {
            resultsBox.classList.add('d-none');
        }
    });

    document.getElementById('typeChoices').addEventListener('click', function (event) {
        var button = event.target.closest('[data-type]');
        if (!button) {
            return;
        }
        document.querySelectorAll('#typeChoices .pos-choice').forEach(function (item) {
            item.classList.toggle('is-selected', item === button);
        });
        typeInput.value = button.getAttribute('data-type');
        reasonInput.value = '';
        var reasons = reasonsByType[typeInput.value] || [];
        if (!reasons.length) {
            reasonChoices.innerHTML = '<span class="text-muted small">No reasons configured for this type.</span>';
        } else {
            reasonChoices.innerHTML = reasons.map(function (reason) {
                return '<button type="button" class="pos-choice" data-reason="' + escapeHtml(reason) + '">' + escapeHtml(reason) + '</button>';
            }).join('');
            if (reasons.length === 1) {
                reasonChoices.querySelector('[data-reason]').click();
            }
        }
        document.getElementById('typeError').classList.add('d-none');
        refreshSummary();
    });

    reasonChoices.addEventListener('click', function (event) {
        var button = event.target.closest('[data-reason]');
        if (!button) {
            return;
        }
        reasonChoices.querySelectorAll('.pos-choice').forEach(function (item) {
            item.classList.toggle('is-selected', item === button);
        });
        reasonInput.value = button.getAttribute('data-reason');
        document.getElementById('typeError').classList.add('d-none');
        refreshSummary();
        amountInput.focus();
    });

    document.getElementById('methodChoices').addEventListener('click', function (event) {
        var button = event.target.closest('[data-method]');
        if (!button) {
            return;
        }
        document.querySelectorAll('#methodChoices .pos-choice').forEach(function (item) {
            item.classList.toggle('is-selected', item === button);
        });
        methodInput.value = button.getAttribute('data-method');
        refreshSummary();
    });

    document.getElementById('posKeypad').addEventListener('click', function (event) {
        var button = event.target.closest('[data-key]');
        if (!button) {
            return;
        }
        var key = button.getAttribute('data-key');
        var current = amountInput.value || '';
        if (key === 'clear') {
            amountInput.value = '';
        } else if (key === 'back') {
            amountInput.value = current.slice(0, -1);
        } else if (key === '.') {
            if (current.indexOf('.') === -1) {
                amountInput.value = (current === '' ? '0' : current) + '.';
            }
        } else {
            var next = current + key;
            var parts = next.split('.');
            if (parts.length < 2 || parts[1].length <= 2) {
                amountInput.value = next.replace(/^0+(\d)/, '$1');
            }
        }
        document.getElementById('amountError').classList.add('d-none');
        refreshSummary();
    });

    amountInput.addEventListener('input', function () {
        amountInput.value = amountInput.value.replace(/[^\d.]/g, '');
        refreshSummary();
    });
    qtyInput.addEventListener('input', refreshSummary);
    document.getElementById('qtyMinus').addEventListener('click', function () {
        qtyInput.value = String(Math.max(1, quantity() - 1));
        refreshSummary();
    });
    document.getElementById('qtyPlus').addEventListener('click', function () {
        qtyInput.value = String(Math.min(999, quantity() + 1));
        refreshSummary();
    });

    document.getElementById('addItemBtn').addEventListener('click', function () {
        if (addDraftItem()) {
            var firstType = document.querySelector('#typeChoices .pos-choice');
            if (firstType) {
                firstType.focus();
            }
        }
    });

    itemList.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove]');
        if (!button) {
            return;
        }
        cart.splice(parseInt(button.getAttribute('data-remove'), 10), 1);
        refreshSummary();
    });

    document.getElementById('approvePending').addEventListener('click', function () {
        approvedInput.value = '0';
        document.getElementById('approvePending').classList.add('is-selected');
        document.getElementById('approveYes').classList.remove('is-selected');
    });
    document.getElementById('approveYes').addEventListener('click', function () {
        approvedInput.value = '1';
        document.getElementById('approveYes').classList.add('is-selected');
        document.getElementById('approvePending').classList.remove('is-selected');
    });

    function showError(id) {
        document.getElementById(id).classList.remove('d-none');
    }

    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }
        var valid = true;
        document.getElementById('studentError').classList.add('d-none');
        document.getElementById('typeError').classList.add('d-none');
        document.getElementById('amountError').classList.add('d-none');
        document.getElementById('itemError').classList.add('d-none');
        if (!studentIdInput.value) {
            showError('studentError');
            searchInput.focus();
            valid = false;
        }
        var draft = draftState();
        if (draft === 'partial') {
            document.getElementById('itemError').textContent = 'Finish the current item, or clear it, before collecting.';
            showError('itemError');
            if (!typeInput.value || !reasonInput.value) {
                showError('typeError');
            }
            if (!(unitAmount() > 0)) {
                showError('amountError');
            }
            valid = false;
        } else if (draft === 'ready' && !addDraftItem()) {
            valid = false;
        }
        if (valid && cart.length === 0) {
            document.getElementById('itemError').textContent = 'Add at least one payment item.';
            showError('itemError');
            valid = false;
        }
        if (!valid) {
            event.preventDefault();
            return;
        }
        refreshSummary();
        submitting = true;
        collectBtn.disabled = true;
        collectBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Collecting…';
    });

    function tickClock() {
        var now = new Date();
        document.getElementById('posClock').textContent = now.toLocaleString(undefined, {
            day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
    }
    tickClock();
    setInterval(tickClock, 30000);
    searchInput.focus();
    refreshSummary();
});
</script>
