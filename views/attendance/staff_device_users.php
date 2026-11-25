<?php
declare(strict_types=1);
/** @var array $urls */
/** @var string $host */
/** @var bool $connected */
/** @var string $connectionMessage */
/** @var string $deviceName */
/** @var string $loadError */
/** @var string $q */
/** @var list<array<string,mixed>> $cards */
/** @var list<array<string,string>> $staffRows */
/** @var int $machineCount */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$staffDeviceSection = 'users';
$cssFile = BASE_PATH . '/assets/css/staff-machine-users.css';
$cssVer = is_file($cssFile) ? (string) filemtime($cssFile) : '1';
$action = (string) ($urls['users'] ?? '#');
?>
<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff-machine-users.css?v=<?php echo $e($cssVer); ?>">
<div class="container-fluid px-3 px-sm-4 py-3 smu-page">
    <?php include BASE_PATH . '/staff_attendance/partials/staff_device_embed_styles.php'; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $e($_SESSION['flash_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12 col-lg-2">
            <?php include BASE_PATH . '/staff_attendance/partials/staff_device_nav.php'; ?>
        </div>
        <div class="col-12 col-lg-10 min-w-0">
            <div class="smu-status <?php echo $connected ? '' : 'is-off'; ?>" role="status">
                <span class="smu-dot" aria-hidden="true"></span>
                <div class="smu-status-text">
                    <strong><?php echo $connected ? 'Machine connected' : 'Machine not connected'; ?></strong>
                    <div class="small text-muted">
                        <?php echo $e($host); ?>
                        <?php if ($deviceName !== ''): ?> · <?php echo $e($deviceName); ?><?php endif; ?>
                        <?php if ($connectionMessage !== ''): ?> · <?php echo $e($connectionMessage); ?><?php endif; ?>
                    </div>
                </div>
                <a class="btn btn-outline-primary" href="<?php echo $e($action); ?>">Connect again</a>
            </div>

            <section class="smu-panel" id="add-person" aria-label="Add a new person">
                <h2>Add new person</h2>
                <form method="post" action="<?php echo $e($action); ?>" id="smuAddForm">
                    <input type="hidden" name="action" value="add_user">
                    <div class="smu-addgrid">
                        <div class="smu-span">
                            <details class="smu-link">
                                <summary>Link a staff record (optional)</summary>
                                <label for="smuStaff">Staff record</label>
                                <select id="smuStaff" name="staff_id" class="form-select">
                                    <option value="">New person, not linked</option>
                                    <?php foreach ($staffRows as $staff): ?>
                                        <option value="<?php echo $e($staff['staff_id']); ?>"
                                            data-name="<?php echo $e($staff['staff_name']); ?>"
                                            data-no="<?php echo $e($staff['finger_machine_no']); ?>">
                                            <?php echo $e($staff['staff_name'] !== '' ? $staff['staff_name'] : $staff['staff_id']); ?>
                                            <?php if ($staff['finger_machine_no'] !== ''): ?> (<?php echo $e($staff['finger_machine_no']); ?>)<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </details>
                        </div>
                        <div>
                            <label for="smuNo">Employee number</label>
                            <input id="smuNo" name="employee_no" class="form-control" maxlength="32" required autocomplete="off" placeholder="53">
                        </div>
                        <div>
                            <label for="smuName">Name on machine</label>
                            <input id="smuName" name="name" class="form-control" maxlength="64" required autocomplete="name" placeholder="Full name">
                        </div>
                        <div>
                            <label class="d-none d-md-block" aria-hidden="true">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Add person</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="smu-panel" id="face-photo" aria-label="Take a face photo">
                <h2>Take photo</h2>
                <p class="smu-cam-who" id="smuCamWho">Choose a person below, then open the machine camera.</p>
                <div class="smu-cam-wrap">
                    <div class="smu-cam" id="smuCamBox">
                        <img id="smuCamLive" class="smu-cam-live" alt="" hidden>
                        <img id="smuCamSaved" class="smu-cam-saved" alt="" hidden>
                        <button type="button" class="smu-cam-open" id="smuCamOpen">Open camera</button>
                    </div>
                    <form method="post" action="<?php echo $e($action); ?>" id="smuCamForm">
                        <input type="hidden" name="action" value="face_device">
                        <input type="hidden" name="employee_no" id="smuCamNo" value="">
                        <input type="hidden" name="name" id="smuCamName" value="">
                        <button type="submit" class="btn btn-success" id="smuCamShot" disabled>Take photo</button>
                    </form>
                </div>
            </section>

            <section class="smu-panel" aria-label="People already on the machine">
                <div class="smu-head">
                    <div>
                        <h2>People on the machine</h2>
                        <p class="smu-count"><span id="smuShown"><?php echo count($cards); ?></span> of <?php echo (int) $machineCount; ?></p>
                    </div>
                    <div class="smu-find">
                        <label for="smuFind">Find number or name</label>
                        <input id="smuFind" type="search" class="form-control" value="<?php echo $e($q); ?>" placeholder="53" autocomplete="off">
                    </div>
                </div>
                <?php if ($loadError !== ''): ?>
                    <div class="alert alert-warning mb-0"><?php echo $e($loadError); ?></div>
                <?php endif; ?>
                <?php if ($cards === []): ?>
                    <div class="smu-empty">No people were returned from the machine.</div>
                <?php else: ?>
                    <div class="smu-list" id="smuList">
                        <?php foreach ($cards as $card): ?>
                            <?php
                            $eno = (string) $card['employee_no'];
                            $name = (string) $card['name'];
                            $fingers = (int) $card['finger_count'];
                            $faces = (int) $card['face_count'];
                            $photoUrl = (string) ($card['photo_url'] ?? '');
                            $hay = strtolower($eno . ' ' . $name . ' ' . ($card['staff_name'] ?? ''));
                            ?>
                            <article class="smu-row" data-smu="<?php echo $e($hay); ?>">
                                <div class="smu-id"><?php echo $e($eno); ?></div>
                                <div class="smu-top">
                                    <?php if ($photoUrl !== ''): ?>
                                        <img class="smu-face" src="<?php echo $e($photoUrl); ?>" alt="" loading="lazy" onerror="this.remove()">
                                    <?php endif; ?>
                                    <div class="smu-who">
                                        <strong><?php echo $e($name !== '' ? $name : 'No name'); ?></strong>
                                        <?php if (($card['staff_name'] ?? '') !== ''): ?>
                                            <span>SIS <?php echo $e($card['staff_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="smu-pills">
                                        <span class="smu-pill <?php echo $fingers > 0 ? 'is-on' : ''; ?>">Finger <?php echo $fingers; ?></span>
                                        <span class="smu-pill <?php echo $faces > 0 ? 'is-on' : ''; ?>">Face <?php echo $faces > 0 ? 'Yes' : 'No'; ?></span>
                                    </div>
                                </div>
                                <div class="smu-tools">
                                    <form method="post" action="<?php echo $e($action); ?>">
                                        <input type="hidden" name="action" value="finger">
                                        <input type="hidden" name="employee_no" value="<?php echo $e($eno); ?>">
                                        <input type="hidden" name="name" value="<?php echo $e($name); ?>">
                                        <input type="hidden" name="finger_no" value="1">
                                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Place your finger on the scanner and hold it still until the page finishes.');">Finger 1</button>
                                    </form>
                                    <form method="post" action="<?php echo $e($action); ?>">
                                        <input type="hidden" name="action" value="finger">
                                        <input type="hidden" name="employee_no" value="<?php echo $e($eno); ?>">
                                        <input type="hidden" name="name" value="<?php echo $e($name); ?>">
                                        <input type="hidden" name="finger_no" value="2">
                                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Place your finger on the scanner and hold it still until the page finishes.');">Finger 2</button>
                                    </form>
                                    <button type="button" class="btn btn-outline-success smu-use-cam"
                                        data-no="<?php echo $e($eno); ?>"
                                        data-name="<?php echo $e($name); ?>"
                                        data-photo="<?php echo $e($photoUrl); ?>"><?php echo $faces > 0 ? 'Retake photo' : 'Take photo'; ?></button>
                                    <?php if ($faces > 0): ?>
                                    <form method="post" action="<?php echo $e($action); ?>">
                                        <input type="hidden" name="action" value="remove_face">
                                        <input type="hidden" name="employee_no" value="<?php echo $e($eno); ?>">
                                        <input type="hidden" name="name" value="<?php echo $e($name); ?>">
                                        <button type="submit" class="btn btn-outline-secondary">Remove face</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?php echo $e($action); ?>">
                                        <input type="hidden" name="action" value="remove_finger">
                                        <input type="hidden" name="employee_no" value="<?php echo $e($eno); ?>">
                                        <input type="hidden" name="name" value="<?php echo $e($name); ?>">
                                        <input type="hidden" name="finger_no" value="0">
                                        <button type="submit" class="btn btn-outline-secondary">Clear fingers</button>
                                    </form>
                                </div>
                                <form method="post" action="<?php echo $e($action); ?>" class="smu-rename">
                                    <input type="hidden" name="action" value="save_name">
                                    <input type="hidden" name="employee_no" value="<?php echo $e($eno); ?>">
                                    <input name="name" class="form-control" maxlength="64" value="<?php echo $e($name); ?>" aria-label="Name for <?php echo $e($eno); ?>">
                                    <button type="submit" class="btn btn-outline-dark">Save name</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="smu-empty d-none" id="smuNone">No person matches that search.</div>
                <?php endif; ?>
                <p class="smu-note">Finger uses the machine scanner. Take photo uses the face camera on the terminal. Stand close and look at the machine screen until the photo is saved. The wide camera view cannot be saved as a face.</p>
            </section>
        </div>
    </div>
</div>
<script>
(function () {
    var select = document.getElementById('smuStaff');
    var no = document.getElementById('smuNo');
    var person = document.getElementById('smuName');
    if (select && no && person) {
        select.addEventListener('change', function () {
            var opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) return;
            if (opt.getAttribute('data-no')) no.value = opt.getAttribute('data-no');
            if (opt.getAttribute('data-name')) person.value = opt.getAttribute('data-name');
        });
    }
    var find = document.getElementById('smuFind');
    var shown = document.getElementById('smuShown');
    var none = document.getElementById('smuNone');
    var rows = document.querySelectorAll('.smu-row[data-smu]');
    if (!find) return;
    function apply() {
        var q = find.value.trim().toLowerCase();
        var n = 0;
        rows.forEach(function (el) {
            var ok = q === '' || (el.getAttribute('data-smu') || '').indexOf(q) !== -1;
            el.classList.toggle('is-hide', !ok);
            if (ok) n++;
        });
        if (shown) shown.textContent = String(n);
        if (none) none.classList.toggle('d-none', n !== 0);
        var url = new URL(window.location.href);
        if (q === '') url.searchParams.delete('q');
        else url.searchParams.set('q', find.value.trim());
        window.history.replaceState({}, '', url.pathname + url.search);
        document.querySelectorAll('.smu-page form').forEach(function (form) {
            var action = new URL(form.getAttribute('action') || window.location.pathname, window.location.origin);
            if (q === '') action.searchParams.delete('q');
            else action.searchParams.set('q', find.value.trim());
            form.setAttribute('action', action.pathname + action.search);
        });
    }
    find.addEventListener('input', apply);
    if (find.value) apply();
})();
(function () {
    var box = document.getElementById('smuCamBox');
    var live = document.getElementById('smuCamLive');
    var openBtn = document.getElementById('smuCamOpen');
    var shot = document.getElementById('smuCamShot');
    var form = document.getElementById('smuCamForm');
    var noInput = document.getElementById('smuCamNo');
    var nameInput = document.getElementById('smuCamName');
    var who = document.getElementById('smuCamWho');
    var saved = document.getElementById('smuCamSaved');
    var liveUrl = <?php echo json_encode(rtrim(APP_URL, '/') . '/attendance/staff-device/camera'); ?>;
    if (!box || !live || !openBtn || !shot || !form || !noInput || !nameInput) return;
    var timer = null;
    var seenFrame = false;
    var fails = 0;

    function setPerson(no, name, photo) {
        noInput.value = no || '';
        nameInput.value = name || '';
        who.textContent = no
            ? ('Photo for ' + no + (name ? ' · ' + name : '') + '. Press Take photo, then look at the machine screen until it finishes.')
            : 'Choose a person below, then open the machine camera.';
        shot.disabled = !seenFrame || !noInput.value;
        if (saved && !box.classList.contains('is-live')) {
            if (photo) {
                saved.src = photo;
                saved.hidden = false;
                saved.alt = 'Saved face photo for ' + no;
                box.classList.add('has-photo');
            } else {
                saved.hidden = true;
                saved.removeAttribute('src');
                box.classList.remove('has-photo');
            }
        }
    }

    function stopCamera() {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    }

    function tick() {
        if (!box.classList.contains('is-live')) return;
        live.src = liveUrl + '?t=' + Date.now();
    }

    function openCamera() {
        if (box.classList.contains('is-live')) {
            shot.disabled = !seenFrame || !noInput.value;
            return;
        }
        seenFrame = false;
        fails = 0;
        live.hidden = false;
        live.alt = 'Machine camera';
        box.classList.add('is-live');
        box.classList.remove('has-photo');
        if (saved) saved.hidden = true;
        openBtn.hidden = true;
        who.textContent = noInput.value
            ? ('Face camera for ' + noInput.value + '. Look at the machine screen, then press Take photo and stay there.')
            : 'Machine camera is on. Choose a person below, then press Take photo.';
        tick();
    }

    live.addEventListener('load', function () {
        if (!box.classList.contains('is-live')) return;
        fails = 0;
        seenFrame = true;
        shot.disabled = !noInput.value;
        timer = setTimeout(tick, 400);
    });
    live.addEventListener('error', function () {
        if (!box.classList.contains('is-live')) return;
        fails += 1;
        if (fails < 3) {
            timer = setTimeout(tick, 700);
            return;
        }
        stopCamera();
        seenFrame = false;
        box.classList.remove('is-live');
        live.hidden = true;
        openBtn.hidden = false;
        shot.disabled = true;
        who.textContent = 'The machine camera did not open. Check the terminal is on, then press Open camera again.';
    });

    openBtn.addEventListener('click', openCamera);
    document.querySelectorAll('.smu-use-cam').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setPerson(btn.getAttribute('data-no') || '', btn.getAttribute('data-name') || '', btn.getAttribute('data-photo') || '');
            var panel = document.getElementById('face-photo');
            if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            openCamera();
        });
    });
    form.addEventListener('submit', function (event) {
        if (!noInput.value) {
            event.preventDefault();
            who.textContent = 'Choose a person below first.';
            return;
        }
        if (!seenFrame) {
            event.preventDefault();
            openCamera();
            return;
        }
        shot.disabled = true;
        stopCamera();
    });
})();
</script>
