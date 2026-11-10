<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$deptName = (string) ($actor['department_id'] ?? '');
foreach ($departments ?? [] as $dept) {
    if ((string) ($dept['department_id'] ?? '') === (string) ($actor['department_id'] ?? '')) {
        $deptName = (string) ($dept['department_name'] ?? $deptName);
        break;
    }
}
$priorityLabels = [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
];
?>
<div class="container-fluid px-0 fac-page fac-create">
    <div class="card border-0">
        <?php $facHeader('Create New Ticket', 'Describe the issue clearly. A ticket number is issued once you submit.'); ?>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <form method="post" action="<?php echo $base; ?>/store" enctype="multipart/form-data" class="fac-simple" id="facCreateForm">
                <input type="hidden" name="subcategory_id" value="">
                <input type="hidden" name="remarks" value="">
                <input type="hidden" name="building" value="">
                <input type="hidden" name="floor" value="">
                <input type="hidden" name="room_area" value="">
                <input type="hidden" name="request_type" value="maintenance">
                <input type="hidden" name="required_date" value="">

                <div class="fac-who-line">
                    <span>Requested by</span>
                    <strong><?php echo $h($actor['name'] ?? ''); ?></strong>
                    <?php if ($deptName !== ''): ?><em><?php echo $h($deptName); ?></em><?php endif; ?>
                </div>

                <p class="fac-required-note"><span class="text-danger">*</span> Required field</p>

                <div class="fac-grid">
                    <h6 class="fac-form-group fac-span">Issue details</h6>

                    <div class="fac-span">
                        <label class="form-label" for="facCat">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" id="facCat" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int) $cat['id']; ?>"><?php echo $h($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fac-span">
                        <label class="form-label" for="facDetails">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="facDetails" name="description" rows="4" required autocapitalize="sentences" enterkeyhint="next" placeholder="Explain what happened and what needs to be fixed."></textarea>
                    </div>

                    <h6 class="fac-form-group fac-span">Location and priority</h6>

                    <div>
                        <label class="form-label" for="facLocation">Location <span class="text-danger">*</span></label>
                        <input class="form-control" id="facLocation" name="location" required autocomplete="off" autocapitalize="words" enterkeyhint="next" maxlength="150" placeholder="e.g. ICT Block, Lab 2">
                    </div>

                    <div>
                        <span class="form-label d-block">Priority <span class="text-danger">*</span></span>
                        <div class="fac-priority-radios" role="radiogroup" aria-label="Priority" aria-required="true">
                            <?php foreach ($priorityLabels as $k => $lab): ?>
                                <label class="fac-priority-opt fac-priority-<?php echo $h($k); ?>">
                                    <input type="radio" name="priority" value="<?php echo $h($k); ?>" <?php echo $k === 'medium' ? 'checked' : ''; ?> required>
                                    <span><?php echo $h($lab); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <h6 class="fac-form-group fac-span">Contact and attachment</h6>

                    <div>
                        <label class="form-label" for="facContact">Contact number <span class="text-danger">*</span></label>
                        <input class="form-control" id="facContact" name="contact" type="tel" inputmode="numeric" autocomplete="tel" enterkeyhint="done" required maxlength="12" pattern="0[0-9]{9}" value="<?php echo $h($actor['contact'] ?? ''); ?>" placeholder="07XXXXXXXX" title="Enter a 10-digit phone number starting with 0.">
                        <div class="form-text">10 digits, starting with 0.</div>
                    </div>

                    <div>
                        <label class="form-label" for="facFiles">Attachment <span class="fac-optional">(optional)</span></label>
                        <input class="form-control" type="file" id="facFiles" name="evidence[]" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        <div class="form-text" id="facFileHelp">Photo or PDF (JPG, PNG, WEBP, PDF), up to 5 MB.</div>
                        <div class="form-text fac-file-info" id="facFileInfo" aria-live="polite" hidden></div>
                    </div>
                </div>

                <div class="fac-submit">
                    <button class="btn btn-primary" type="submit" id="facSubmitBtn"><i class="fas fa-paper-plane me-1"></i>Submit Ticket</button>
                    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div><script>
(function () {
    // Phone: accept +94 / 7XXXXXXXX forms and keep a clean 10-digit local number
    var phone = document.getElementById('facContact');
    if (phone) {
        phone.addEventListener('input', function () {
            var digits = phone.value.replace(/\D/g, '');
            if (digits.indexOf('94') === 0 && digits.length >= 11) {
                digits = '0' + digits.slice(2, 11);
            } else if (digits.charAt(0) === '7' && digits.length === 9) {
                digits = '0' + digits;
            }
            phone.value = digits.slice(0, 10);
        });
    }

    // Attachment: check type and size on the device before uploading (helps slow mobile networks)
    var file = document.getElementById('facFiles');
    var info = document.getElementById('facFileInfo');
    var MAX = 5 * 1024 * 1024;
    var OK = /\.(jpe?g|png|webp|pdf)$/i;
    if (file && info) {
        file.addEventListener('change', function () {
            var f = file.files && file.files[0];
            file.setCustomValidity('');
            info.hidden = true;
            info.classList.remove('is-error');
            if (!f) { return; }
            var msg = '';
            if (!OK.test(f.name)) {
                msg = 'This file type is not allowed. Use JPG, PNG, WEBP or PDF.';
            } else if (f.size > MAX) {
                msg = 'This file is ' + (f.size / 1048576).toFixed(1) + ' MB. The limit is 5 MB.';
            }
            if (msg) {
                file.setCustomValidity(msg);
                info.classList.add('is-error');
                info.textContent = msg;
                file.value = '';
            } else {
                info.textContent = f.name + ' (' + (f.size >= 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB') + ')';
            }
            info.hidden = false;
        });
    }

    // One submit only: stops double taps on slow connections
    var form = document.getElementById('facCreateForm');
    var btn = document.getElementById('facSubmitBtn');
    if (form && btn) {
        var label = btn.innerHTML;
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) { return; }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';
        });
        // Back button / bfcache: make the form usable again
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { btn.disabled = false; btn.innerHTML = label; }
        });
    }
})();
</script>
