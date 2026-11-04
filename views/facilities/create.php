<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$deptName = (string) ($actor['department_id'] ?? '');
foreach ($departments ?? [] as $dept) {
    if ((string) ($dept['department_id'] ?? '') === (string) ($actor['department_id'] ?? '')) {
        $deptName = (string) ($dept['department_name'] ?? $deptName);
        break;
    }
}
$priorityLabels = [
    'low' => 'Can wait',
    'medium' => 'Normal',
    'high' => 'Needed soon',
    'critical' => 'Urgent — work has stopped',
];
?>
<div class="container-fluid px-0 fac-page fac-create">
    <div class="card border-0">
        <div class="card-header">
            <h5 class="fw-bold mb-0">New request</h5>
            <p>Fill in the boxes below. We will give you a reference number when you submit.</p>
        </div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <form method="post" action="<?php echo $base; ?>/store" enctype="multipart/form-data" class="fac-simple" id="facCreateForm">
                <input type="hidden" name="subcategory_id" value="">
                <input type="hidden" name="building" value="">
                <input type="hidden" name="floor" value="">
                <input type="hidden" name="room_area" value="">
                <input type="hidden" name="request_type" value="maintenance">
                <input type="hidden" name="required_date" value="">

                <div class="fac-who-line">
                    <span>From</span>
                    <strong><?php echo $h($actor['name'] ?? ''); ?></strong>
                    <?php if ($deptName !== ''): ?><em><?php echo $h($deptName); ?></em><?php endif; ?>
                </div>

                <div class="fac-grid">
                    <div class="fac-span">
                        <label class="form-label" for="facTitle">What is the problem? <span class="text-danger">*</span></label>
                        <input class="form-control" id="facTitle" name="title" required maxlength="200" placeholder="Example: Ceiling fan not working">
                    </div>

                    <div>
                        <label class="form-label" for="facNeed">What do you need? <span class="text-danger">*</span></label>
                        <input class="form-control" id="facNeed" name="remarks" required maxlength="200" placeholder="Example: Repair the ceiling fan">
                    </div>

                    <div>
                        <label class="form-label" for="facCat">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" id="facCat" required>
                            <option value="">Choose one</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int) $cat['id']; ?>"><?php echo $h($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="facLocation">Where is it? <span class="text-danger">*</span></label>
                        <input class="form-control" id="facLocation" name="location" required placeholder="Example: ICT block, Lab 2">
                    </div>

                    <div>
                        <label class="form-label" for="facPriority">How soon? <span class="text-danger">*</span></label>
                        <select class="form-select" id="facPriority" name="priority" required>
                            <?php foreach ($priorityLabels as $k => $lab): ?>
                                <option value="<?php echo $h($k); ?>" <?php echo $k === 'medium' ? 'selected' : ''; ?>><?php echo $h($lab); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fac-span">
                        <label class="form-label" for="facDetails">Tell us more <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="facDetails" name="description" rows="4" required placeholder="What happened, and what should be fixed."></textarea>
                    </div>

                    <div>
                        <label class="form-label" for="facContact">Your phone number <span class="text-danger">*</span></label>
                        <input class="form-control" id="facContact" name="contact" type="tel" inputmode="numeric" autocomplete="tel" required maxlength="12" pattern="0[0-9]{9}" value="<?php echo $h($actor['contact'] ?? ''); ?>" placeholder="07XXXXXXXX" title="Enter a 10-digit phone number starting with 0.">
                    </div>

                    <div>
                        <label class="form-label" for="facFiles">Photo <span class="fac-optional">Optional</span></label>
                        <input class="form-control" type="file" id="facFiles" name="evidence[]" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        <div class="form-text">JPG, PNG, or PDF. Up to 5 MB.</div>
                    </div>
                </div>

                <div class="fac-submit">
                    <button class="btn btn-primary" type="submit">Submit request</button>
                    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var phone = document.getElementById('facContact');
    if (!phone) return;
    phone.addEventListener('input', function () {
        var digits = phone.value.replace(/\D/g, '');
        if (digits.indexOf('94') === 0 && digits.length >= 11) {
            digits = '0' + digits.slice(2, 11);
        } else if (digits.charAt(0) === '7' && digits.length === 9) {
            digits = '0' + digits;
        }
        phone.value = digits.slice(0, 10);
    });
})();
</script>
