<?php
$e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$cssUrl = rtrim(APP_URL, '/') . '/assets/css/student-profile-photo.css';
$jsUrl = rtrim(APP_URL, '/') . '/assets/js/student-profile-photo.js';
$cssVer = (int) @filemtime(BASE_PATH . '/assets/css/student-profile-photo.css');
$jsVer = (int) @filemtime(BASE_PATH . '/assets/js/student-profile-photo.js');
?>
<link rel="stylesheet" href="<?php echo $e($cssUrl); ?>?v=<?php echo $cssVer; ?>">

<div class="pp-page" id="profilePhotoDesk"
     data-search-url="<?php echo $e(rtrim(APP_URL, '/') . '/students/profile-photo/search'); ?>"
     data-save-url="<?php echo $e(rtrim(APP_URL, '/') . '/students/profile-photo/save'); ?>">
    <header class="pp-head">
        <h1><i class="fas fa-camera" aria-hidden="true"></i> Profile photo</h1>
        <p>Search by registration number or NIC, choose the student, then take or upload the ID photo.</p>
        <div class="pp-steps" aria-hidden="true">
            <span class="pp-step"><b>1</b> Search</span>
            <span class="pp-step"><b>2</b> Select</span>
            <span class="pp-step"><b>3</b> Photo</span>
        </div>
    </header>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo $e($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $e($error); ?></div>
    <?php endif; ?>

    <section class="pp-card pp-search" aria-label="Find a student">
        <label for="photoSearch" class="pp-label">Registration number or NIC</label>
        <div class="pp-field">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="text" id="photoSearch" inputmode="search" enterkeyhint="search"
                   placeholder="e.g. 2026/AUT/4AT001 or NIC"
                   autocomplete="off" autocapitalize="off" spellcheck="false"
                   aria-autocomplete="list" aria-controls="photoResults" aria-expanded="false">
            <button type="button" class="pp-clear" id="photoClear" hidden aria-label="Clear search">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <p class="pp-hint">Type at least 2 characters. Use the arrow keys and Enter to choose a student.</p>
        <div id="photoResults" class="pp-results" role="listbox" aria-label="Matching students" hidden></div>
    </section>

    <section class="pp-card pp-work" id="photoStudentCard" hidden>
        <div class="pp-photo-wrap">
            <div class="pp-photo" id="photoFrame"><i class="fas fa-user" aria-hidden="true"></i></div>
            <p class="pp-photo-caption">ID size 35 × 45 mm</p>
        </div>
        <div class="pp-panel">
            <h2 id="photoName">Student</h2>
            <p class="pp-idline" id="photoMeta"></p>
            <p class="pp-course" id="photoCourse"></p>
            <p id="photoStatus" class="pp-status" role="status" hidden></p>
            <button type="button" class="pp-btn pp-btn-success pp-next" id="btnNext" hidden>
                <i class="fas fa-arrow-right" aria-hidden="true"></i> Next student
            </button>
            <div class="pp-actions">
                <button type="button" class="pp-btn pp-btn-primary" id="btnFront">
                    <i class="fas fa-user" aria-hidden="true"></i> Front camera
                </button>
                <button type="button" class="pp-btn pp-btn-ghost" id="btnBack">
                    <i class="fas fa-camera" aria-hidden="true"></i> Back camera
                </button>
                <label class="pp-file">
                    <span class="pp-btn pp-btn-ghost">
                        <i class="fas fa-upload" aria-hidden="true"></i> Upload photo
                    </span>
                    <input type="file" id="photoFile" accept="image/jpeg,image/png,image/gif">
                </label>
            </div>
            <div id="photoCam" class="pp-cam" hidden>
                <div class="pp-cam-stage">
                    <video id="photoVideo" autoplay playsinline muted></video>
                    <div class="pp-cam-guide" aria-hidden="true"></div>
                </div>
                <canvas id="photoCapture" hidden></canvas>
                <div class="pp-cam-actions">
                    <button type="button" class="pp-btn pp-btn-primary" id="btnCapture">
                        <i class="fas fa-camera" aria-hidden="true"></i> Capture and update
                    </button>
                    <button type="button" class="pp-btn pp-btn-ghost" id="btnCamCancel">Cancel</button>
                </div>
                <p class="pp-cam-note">Keep the face inside the oval. Front camera is mirrored on screen only.</p>
            </div>
        </div>
    </section>
</div>
<script src="<?php echo $e($jsUrl); ?>?v=<?php echo $jsVer; ?>"></script>
