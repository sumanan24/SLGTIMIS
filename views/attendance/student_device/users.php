<?php
declare(strict_types=1);
/** @var array $urls */
/** @var list<array<string,mixed>> $cards */
/** @var string $search */
/** @var string $machineHost */
/** @var array $departments */
/** @var array $courses */
/** @var array $academicYears */
/** @var string $departmentId */
/** @var string $courseId */
/** @var string $academicYear */
/** @var string $courseMode */

$e = static function ($v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};

$studentDeviceSection = 'users';
$pageTitle = 'Student fingerprints';
$pageSubtitle = 'Filter or search, then enroll Finger 01 / 02 and Face ID on the machine';
$cards = $cards ?? [];
$search = (string) ($search ?? '');
$machineHost = (string) ($machineHost ?? '');
$departments = $departments ?? [];
$courses = $courses ?? [];
$academicYears = $academicYears ?? [];
$departmentId = (string) ($departmentId ?? '');
$courseId = (string) ($courseId ?? '');
$academicYear = (string) ($academicYear ?? '');
$courseMode = (string) ($courseMode ?? '');
$isFullMode = in_array($courseMode, ['Full', 'full', 'Full Time'], true);
$isPartMode = in_array($courseMode, ['Part', 'part', 'Part Time'], true);
$usersAction = (string) ($urls['users'] ?? '#');
$hasFilters = $departmentId !== '' || $courseId !== '' || $academicYear !== '' || $courseMode !== '';
$queryParams = array_filter([
    'q' => $search,
    'department_id' => $departmentId,
    'course_id' => $courseId,
    'academic_year' => $academicYear,
    'course_mode' => $courseMode,
], static fn ($v) => $v !== null && $v !== '');
$postBase = $usersAction . ($queryParams !== [] ? ('?' . http_build_query($queryParams)) : '');

$filterHiddens = static function () use ($e, $departmentId, $courseId, $academicYear, $courseMode): void {
    if ($departmentId !== '') {
        echo '<input type="hidden" name="department_id" value="' . $e($departmentId) . '">';
    }
    if ($courseId !== '') {
        echo '<input type="hidden" name="course_id" value="' . $e($courseId) . '">';
    }
    if ($academicYear !== '') {
        echo '<input type="hidden" name="academic_year" value="' . $e($academicYear) . '">';
    }
    if ($courseMode !== '') {
        echo '<input type="hidden" name="course_mode" value="' . $e($courseMode) . '">';
    }
};

ob_start();
?>
<div class="sd-page-head-actions">
    <span class="sd-summary-chip"><?php echo count($cards); ?> result<?php echo count($cards) === 1 ? '' : 's'; ?></span>
    <?php if ($machineHost !== ''): ?>
        <span class="sd-summary-chip sd-summary-chip-muted"><i class="fas fa-server me-1"></i><?php echo $e($machineHost); ?></span>
    <?php endif; ?>
</div>
<?php
$headerActions = ob_get_clean();

ob_start();
?>
<section class="sd-users-layout">
    <?php if (!empty($curlMissing)): ?>
        <div class="alert alert-warning" role="alert">
            <strong>PHP cURL is not installed on this server.</strong>
            Machine sync, fingerprint enroll, and Face ID will not work until you install
            <code>php-curl</code> and restart PHP-FPM/Apache
            (e.g. <code>sudo apt install php-curl &amp;&amp; sudo systemctl restart php8.2-fpm</code>).
            The student list below still loads from the database.
        </div>
    <?php endif; ?>
    <div class="sd-users-panel">
        <form method="get" action="<?php echo $e($usersAction); ?>" class="sd-users-search" id="sdUsersSearchForm">
            <div class="sd-users-filters-grid">
                <div class="sd-field">
                    <label class="form-label" for="sdUsersDept">Department</label>
                    <select id="sdUsersDept" name="department_id" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $e($d['department_id'] ?? ''); ?>"
                                <?php echo $departmentId === (string) ($d['department_id'] ?? '') ? 'selected' : ''; ?>>
                                <?php echo $e($d['department_name'] ?? $d['department_id'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sd-field">
                    <label class="form-label" for="sdUsersCourse">Course</label>
                    <select id="sdUsersCourse" name="course_id" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?php echo $e($c['course_id'] ?? ''); ?>"
                                <?php echo $courseId === (string) ($c['course_id'] ?? '') ? 'selected' : ''; ?>>
                                <?php echo $e($c['course_name'] ?? $c['course_id'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sd-field">
                    <label class="form-label" for="sdUsersMode">Course mode</label>
                    <select id="sdUsersMode" name="course_mode" class="form-select">
                        <option value="">All</option>
                        <option value="Full" <?php echo $isFullMode ? 'selected' : ''; ?>>Full Time</option>
                        <option value="Part" <?php echo $isPartMode ? 'selected' : ''; ?>>Part Time</option>
                    </select>
                </div>
                <div class="sd-field">
                    <label class="form-label" for="sdUsersYear">Academic year</label>
                    <select id="sdUsersYear" name="academic_year" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($academicYears as $y): ?>
                            <option value="<?php echo $e($y); ?>" <?php echo $academicYear === (string) $y ? 'selected' : ''; ?>>
                                <?php echo $e($y); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sd-field sd-users-search-field">
                    <label class="form-label" for="sdEmpSearch">Employee number</label>
                    <input type="search" id="sdEmpSearch" name="q" class="form-control"
                           value="<?php echo $e($search); ?>"
                           placeholder="254TE039 or 4TE039"
                           autocomplete="off">
                </div>
                <div class="sd-field sd-users-search-btns">
                    <label class="form-label sd-users-btns-label" aria-hidden="true">&nbsp;</label>
                    <div class="sd-users-btn-row">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <?php if ($search !== '' || $hasFilters): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo $e($usersAction); ?>">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>

        <div class="sd-users-toolbar">
            <form method="post" action="<?php echo $e($postBase); ?>" class="sd-users-toolbar-form">
                <input type="hidden" name="q" value="<?php echo $e($search); ?>">
                <?php $filterHiddens(); ?>
                <button type="submit" name="action" value="refresh" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-sync-alt me-1"></i>Refresh from machine
                </button>
            </form>
            <p class="sd-users-toolbar-hint mb-0">Stand at the terminal when capturing fingerprints or Face ID.</p>
        </div>
    </div>

    <?php if ($cards === []): ?>
        <div class="sd-users-empty">
            <?php if ($search !== '' || $hasFilters): ?>
                No match for your filters<?php echo $search !== '' ? ' / <code>' . $e($search) . '</code>' : ''; ?>.
                Try Person ID, Student ID, or widen the filters.
            <?php else: ?>
                Choose filters or search by employee number to begin.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="sd-users-cards">
            <?php foreach ($cards as $card): ?>
                <?php
                $eno = (string) ($card['employee_no'] ?? '');
                $onMachine = !empty($card['on_machine']);
                $sid = (string) ($card['student_id'] ?? '');
                $displayName = (string) ($card['student_name'] ?? $card['machine_name'] ?? '');
                $photoUrl = (string) ($card['profile_photo_url'] ?? '');
                $has01 = !empty($card['has_finger_01']);
                $has02 = !empty($card['has_finger_02']);
                $hasFace = !empty($card['has_face']) || (int) ($card['face_count'] ?? 0) > 0;
                $facePhotoBase = (string) ($urls['face_photo'] ?? '');
                $machineFaceUrl = ($hasFace && $facePhotoBase !== '' && $eno !== '')
                    ? ($facePhotoBase . '?employee_no=' . rawurlencode($eno))
                    : '';
                $hiddenCommon = static function () use ($e, $search, $eno, $displayName, $sid, $filterHiddens): void {
                    echo '<input type="hidden" name="q" value="' . $e($search) . '">';
                    echo '<input type="hidden" name="employee_no" value="' . $e($eno) . '">';
                    echo '<input type="hidden" name="name" value="' . $e($displayName) . '">';
                    echo '<input type="hidden" name="student_id" value="' . $e($sid) . '">';
                    $filterHiddens();
                };
                ?>
                <article class="sd-stu-card <?php echo $onMachine ? 'is-on' : 'is-off'; ?>">
                    <div class="sd-stu-photos">
                        <figure class="sd-stu-photo">
                            <?php if ($photoUrl !== ''): ?>
                                <img src="<?php echo $e($photoUrl); ?>" alt="Profile" loading="lazy"
                                     onerror="this.style.display='none';this.nextElementSibling.hidden=false;">
                                <span class="sd-stu-photo-fallback" hidden aria-hidden="true"><i class="fas fa-user"></i></span>
                            <?php else: ?>
                                <span class="sd-stu-photo-fallback" aria-hidden="true"><i class="fas fa-user"></i></span>
                            <?php endif; ?>
                            <figcaption>Profile</figcaption>
                        </figure>
                        <figure class="sd-stu-photo <?php echo $machineFaceUrl !== '' ? 'has-face' : ''; ?>">
                            <?php if ($machineFaceUrl !== ''): ?>
                                <img src="<?php echo $e($machineFaceUrl); ?>" alt="Machine face" loading="lazy"
                                     onerror="this.style.display='none';this.nextElementSibling.hidden=false;">
                                <span class="sd-stu-photo-fallback" hidden aria-hidden="true"><i class="fas fa-camera"></i></span>
                            <?php else: ?>
                                <span class="sd-stu-photo-fallback" aria-hidden="true"><i class="fas fa-camera"></i></span>
                            <?php endif; ?>
                            <figcaption>Machine</figcaption>
                        </figure>
                    </div>

                    <div class="sd-stu-body">
                        <h2 class="sd-stu-name"><?php echo $e($displayName !== '' ? $displayName : '—'); ?></h2>
                        <div class="sd-stu-meta">
                            <div class="sd-stu-meta-row">
                                <span class="sd-stu-label">Employee No</span>
                                <code class="sd-stu-emp"><?php echo $e($eno); ?></code>
                            </div>
                            <div class="sd-stu-meta-row">
                                <span class="sd-stu-label">Student ID</span>
                                <span class="sd-stu-sid"><?php echo $sid !== '' ? $e($sid) : '—'; ?></span>
                            </div>
                        </div>

                        <div class="sd-stu-actions">
                            <?php if (!$onMachine): ?>
                                <form method="post" action="<?php echo $e($postBase); ?>" class="sd-stu-action-full">
                                    <?php $hiddenCommon(); ?>
                                    <button type="submit" name="action" value="add_user" class="btn btn-primary btn-sm"
                                            onclick="return confirm('Add this student on the fingerprint machine as <?php echo $e($eno); ?>?');">
                                        <i class="fas fa-user-plus me-1"></i>Add on machine
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="sd-stu-action">
                                    <span class="sd-stu-action-label">Finger 01</span>
                                    <div class="sd-stu-btns">
                                        <form method="post" action="<?php echo $e($postBase); ?>">
                                            <?php $hiddenCommon(); ?>
                                            <input type="hidden" name="finger_no" value="1">
                                            <button type="submit" name="action" value="add_finger"
                                                    class="btn btn-sm <?php echo $has01 ? 'btn-outline-primary' : 'btn-primary'; ?>"
                                                    onclick="return confirm('Capture Finger 01 now?');">
                                                <?php echo $has01 ? 'Replace' : 'Add'; ?>
                                            </button>
                                        </form>
                                        <?php if ($has01): ?>
                                            <form method="post" action="<?php echo $e($postBase); ?>">
                                                <?php $hiddenCommon(); ?>
                                                <input type="hidden" name="finger_no" value="1">
                                                <button type="submit" name="action" value="remove_finger"
                                                        class="btn btn-outline-danger btn-sm" title="Remove Finger 01"
                                                        onclick="return confirm('Remove Finger 01 for <?php echo $e($eno); ?>?');">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="sd-stu-action">
                                    <span class="sd-stu-action-label">Finger 02</span>
                                    <div class="sd-stu-btns">
                                        <form method="post" action="<?php echo $e($postBase); ?>">
                                            <?php $hiddenCommon(); ?>
                                            <input type="hidden" name="finger_no" value="2">
                                            <button type="submit" name="action" value="add_finger"
                                                    class="btn btn-sm <?php echo $has02 ? 'btn-outline-primary' : 'btn-primary'; ?>"
                                                    onclick="return confirm('Capture Finger 02 now?');">
                                                <?php echo $has02 ? 'Replace' : 'Add'; ?>
                                            </button>
                                        </form>
                                        <?php if ($has02): ?>
                                            <form method="post" action="<?php echo $e($postBase); ?>">
                                                <?php $hiddenCommon(); ?>
                                                <input type="hidden" name="finger_no" value="2">
                                                <button type="submit" name="action" value="remove_finger"
                                                        class="btn btn-outline-danger btn-sm" title="Remove Finger 02"
                                                        onclick="return confirm('Remove Finger 02 for <?php echo $e($eno); ?>?');">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="sd-stu-action">
                                    <span class="sd-stu-action-label">Face ID</span>
                                    <div class="sd-stu-btns">
                                        <form method="post" action="<?php echo $e($postBase); ?>">
                                            <?php $hiddenCommon(); ?>
                                            <?php if ($hasFace): ?>
                                                <input type="hidden" name="replace_face" value="1">
                                            <?php endif; ?>
                                            <button type="submit" name="action" value="add_face"
                                                    class="btn btn-sm <?php echo $hasFace ? 'btn-outline-success' : 'btn-success'; ?>"
                                                    onclick="return confirm('<?php echo $hasFace ? ($photoUrl !== '' ? 'Replace Face ID using student profile photo?' : 'Replace Face ID on the terminal camera. Student must stand at the device.') : ($photoUrl !== '' ? 'Upload Face ID from student profile photo?' : 'No profile photo — enroll Face ID on the terminal camera. Student must stand at the device.'); ?>');">
                                                <?php echo $hasFace ? 'Replace' : 'Add'; ?>
                                            </button>
                                        </form>
                                        <?php if ($hasFace): ?>
                                            <form method="post" action="<?php echo $e($postBase); ?>">
                                                <?php $hiddenCommon(); ?>
                                                <button type="submit" name="action" value="remove_face"
                                                        class="btn btn-outline-danger btn-sm" title="Remove Face ID"
                                                        onclick="return confirm('Remove Face ID for <?php echo $e($eno); ?>?');">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<script>
(function () {
    var form = document.getElementById('sdUsersSearchForm');
    var dept = document.getElementById('sdUsersDept');
    var course = document.getElementById('sdUsersCourse');
    if (!form || !dept) return;
    dept.addEventListener('change', function () {
        if (course) course.value = '';
        form.submit();
    });
})();
</script>
<?php
$contentHtml = ob_get_clean();
$contentPartial = null;
include __DIR__ . '/partials/shell.php';
