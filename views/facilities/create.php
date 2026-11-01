<?php require BASE_PATH . '/views/facilities/partials/helpers.php';
$deptName = (string) ($actor['department_id'] ?? '');
foreach ($departments ?? [] as $dept) {
    if ((string) ($dept['department_id'] ?? '') === (string) ($actor['department_id'] ?? '')) {
        $deptName = (string) ($dept['department_name'] ?? $deptName);
        break;
    }
}
if ($deptName === '') {
    $deptName = 'Not on file';
}
$typeHelp = [
    'maintenance' => 'Something is broken and needs repair.',
    'complaint' => 'Report a problem with a service or place.',
    'service' => 'Ask for help, such as setup or transport.',
    'technical' => 'Computer, network, printer, or phone support.',
];
$priorityHelp = [
    'low' => 'Can wait',
    'medium' => 'Normal',
    'high' => 'Needed soon',
    'critical' => 'Work or safety is stopped',
];
$priorityOrder = ['low', 'medium', 'high', 'critical'];
?>
<div class="container-fluid px-0 fac-page fac-create">
    <div class="card border-0">
        <div class="card-header">
            <h5 class="fw-bold mb-0">New request</h5>
            <p>Tell us what you need. Your reference number is created when you submit.</p>
        </div>
        <div class="card-body">
            <?php $facNav(); $facFlash(); ?>

            <div class="fac-who">
                <div><span>Your name</span><strong><?php echo $h($actor['name'] ?? ''); ?></strong></div>
                <div><span>Employee ID</span><strong><?php echo $h($actor['staff_id'] ?? ''); ?></strong></div>
                <div><span>Department</span><strong><?php echo $h($deptName); ?></strong></div>
            </div>

            <form method="post" action="<?php echo $base; ?>/store" enctype="multipart/form-data" id="facCreateForm">
                <div class="fac-step">
                    <div class="fac-step-no">1</div>
                    <div>
                        <h6>What do you need?</h6>
                        <p class="fac-help">Choose the closest type. The category sends the request to the right officer.</p>
                        <div class="fac-choices" id="facTypes">
                            <?php foreach (($labels['types'] ?? []) as $k => $lab): ?>
                                <label class="fac-choice<?php echo $k === 'maintenance' ? ' is-on' : ''; ?>">
                                    <input type="radio" name="request_type" value="<?php echo $h($k); ?>" <?php echo $k === 'maintenance' ? 'checked' : ''; ?> required>
                                    <strong><?php echo $h($lab); ?></strong>
                                    <span><?php echo $h($typeHelp[$k] ?? ''); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label" for="facTitle">Subject <span class="text-danger">*</span></label>
                                <input class="form-control" id="facTitle" name="title" required maxlength="200" placeholder="Example: Ceiling fan not working in Lab 2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="facCat">Category <span class="text-danger">*</span></label>
                                <select class="form-select" name="category_id" id="facCat" required>
                                    <option value="">Choose a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int) $cat['id']; ?>"><?php echo $h($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="facSub">More detail</label>
                                <select class="form-select" name="subcategory_id" id="facSub">
                                    <option value="">Choose a category first</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="facDetails">Describe the problem <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="facDetails" name="description" rows="4" required placeholder="What happened, where it affects you, and what should be fixed."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="facRemarks">Anything else</label>
                                <input class="form-control" id="facRemarks" name="remarks" placeholder="Optional note">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fac-step">
                    <div class="fac-step-no">2</div>
                    <div>
                        <h6>How urgent is it?</h6>
                        <p class="fac-help">This sets the expected completion date. You can still add a date you need it by.</p>
                        <div class="fac-priorities" id="facPriority">
                            <?php foreach ($priorityOrder as $k): ?>
                                <?php if (!isset($prLabel[$k])) { continue; } ?>
                                <label class="fac-choice<?php echo $k === 'medium' ? ' is-on' : ''; ?>">
                                    <input type="radio" name="priority" value="<?php echo $h($k); ?>" <?php echo $k === 'medium' ? 'checked' : ''; ?> required>
                                    <strong><?php echo $h($prLabel[$k]); ?></strong>
                                    <span><?php echo $h($priorityHelp[$k]); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label" for="facNeed">Needed by</label>
                                <input type="date" class="form-control" id="facNeed" name="required_date">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fac-step">
                    <div class="fac-step-no">3</div>
                    <div>
                        <h6>Where should we come?</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="facLocation">Place <span class="text-danger">*</span></label>
                                <input class="form-control" id="facLocation" name="location" required placeholder="Example: Main workshop">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="facBuilding">Building</label>
                                <input class="form-control" id="facBuilding" name="building" placeholder="Example: ICT block">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="facFloor">Floor</label>
                                <input class="form-control" id="facFloor" name="floor" placeholder="Example: 1">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="facRoom">Room</label>
                                <input class="form-control" id="facRoom" name="room_area" placeholder="Example: Lab 2">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="fac-step">
                    <div class="fac-step-no">4</div>
                    <div>
                        <h6>How can we reach you?</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="facContact">Contact number <span class="text-danger">*</span></label>
                                <input class="form-control" id="facContact" name="contact" type="tel" inputmode="numeric" autocomplete="tel" required maxlength="12" pattern="0[0-9]{9}" value="<?php echo $h($actor['contact'] ?? ''); ?>" placeholder="07XXXXXXXX" title="Enter your own phone number. A 9-digit number starting with 7 is completed with a leading 0.">
                            </div>
                            <div class="col-12">
                                <label class="fac-drop" for="facFiles">
                                    <strong>Add photos or documents</strong>
                                    <span>Optional. JPG, PNG, or PDF. Up to 5 MB each.</span>
                                    <input type="file" id="facFiles" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx">
                                </label>
                                <ul class="fac-files" id="facFileList" hidden></ul>
                            </div>
                        </div>
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
    function markChoices(group) {
        if (!group) return;
        group.querySelectorAll('.fac-choice').forEach(function (label) {
            var input = label.querySelector('input');
            label.classList.toggle('is-on', !!(input && input.checked));
        });
    }
    ['facTypes', 'facPriority'].forEach(function (id) {
        var group = document.getElementById(id);
        if (!group) return;
        group.addEventListener('change', function () { markChoices(group); });
        markChoices(group);
    });

    var cat = document.getElementById('facCat');
    var sub = document.getElementById('facSub');
    if (cat && sub) {
        cat.addEventListener('change', function () {
            sub.innerHTML = '<option value="">Loading…</option>';
            if (!cat.value) {
                sub.innerHTML = '<option value="">Choose a category first</option>';
                return;
            }
            fetch('<?php echo $base; ?>/subcategories?category_id=' + encodeURIComponent(cat.value))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    sub.innerHTML = '<option value="">Optional</option>';
                    (d.rows || []).forEach(function (row) {
                        var option = document.createElement('option');
                        option.value = row.id;
                        option.textContent = row.name;
                        sub.appendChild(option);
                    });
                })
                .catch(function () {
                    sub.innerHTML = '<option value="">Could not load details</option>';
                });
        });
    }

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

    var files = document.getElementById('facFiles');
    var list = document.getElementById('facFileList');
    if (files && list) {
        files.addEventListener('change', function () {
            list.innerHTML = '';
            if (!files.files.length) {
                list.hidden = true;
                return;
            }
            Array.prototype.forEach.call(files.files, function (file) {
                var item = document.createElement('li');
                item.textContent = file.name;
                list.appendChild(item);
            });
            list.hidden = false;
        });
    }
})();
</script>
