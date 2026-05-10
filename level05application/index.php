<?php
/**
 * Level 05 — Multi-step wizard (procedural PHP, Bootstrap 5).
 * Posts to student_applications via insert.php / update.php (multipart).
 */
declare(strict_types=1);

/** @var array<string, list<string>> $slProv */
$slProv = require dirname(__DIR__) . '/config/sl_provinces_districts.php';
/** @var array<string, string> $slDistrictPostal */
$slDistrictPostal = require dirname(__DIR__) . '/config/sl_district_postal_codes.php';

/** Web path to this folder, always ends with / (fixes relative fetch when URL omits trailing slash). */
$l05ApiBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
/** Parent folder web path (main SIS entry) for /student-application/api/* JSON. */
$l05ScriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
$l05MainAppBasePath = dirname(dirname($l05ScriptPath));
if ($l05MainAppBasePath === '/' || $l05MainAppBasePath === '\\' || $l05MainAppBasePath === '.') {
    $l05MainAppBasePath = '';
} else {
    $l05MainAppBasePath = rtrim($l05MainAppBasePath, '/');
}
/** Official institute website (header Home / brand). */
$l05OfficialSiteUrl = 'https://slgti.ac.lk/';
$l05StudentAppCssHref = ($l05MainAppBasePath === '' ? '' : $l05MainAppBasePath) . '/assets/css/student-application.css?v=14';
$l05AlStreams = require dirname(__DIR__) . '/config/al_streams_sri_lanka.php';
$l05AlSubjects = require dirname(__DIR__) . '/config/al_subjects_common.php';
/** A/L subject results — same allowed letters as O/L (A, B, C, S, W). */
$l05GradeLetters = ['A', 'B', 'C', 'S', 'W'];
$l05Today = new DateTimeImmutable('today');
$l05DobMax = $l05Today->modify('-16 years')->format('Y-m-d');
$l05DobMin = $l05Today->modify('-120 years')->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLGTI NVQ Level 05 application — 2026 — Sri Lanka German Training Institute</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($l05StudentAppCssHref, ENT_QUOTES, 'UTF-8'); ?>">
    <script>window.L05_API_BASE = <?php echo json_encode($l05ApiBase, JSON_UNESCAPED_UNICODE); ?>;</script>
    <script>window.L05_MAIN_APP_BASE = <?php echo json_encode($l05MainAppBasePath, JSON_UNESCAPED_UNICODE); ?>;</script>
    <script>window.L05_DISTRICT_ZIP = <?php echo json_encode($slDistrictPostal, JSON_UNESCAPED_UNICODE); ?>;</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        :root { --wiz-brand: #0c4a6e; --wiz-accent: #0369a1; }
        .l05-site-header { position: relative; z-index: 2; }
        .wiz-card { border-radius: 16px; border: none; box-shadow: 0 12px 40px rgba(12, 74, 110, 0.12); }
        .wiz-header { border-bottom: 3px solid var(--wiz-accent); padding-bottom: 1rem; }
        .step-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: center; margin-bottom: 1.5rem; }
        .step-pill {
            display: flex; flex-direction: column; align-items: center; padding: 0.4rem 0.5rem; border-radius: 10px;
            font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
            color: var(--wiz-brand); opacity: 0.4; transition: opacity 0.2s, background 0.2s; min-width: 3.6rem;
        }
        .step-pill.active { opacity: 1; background: #e0f2fe; }
        .step-pill.done { opacity: 0.75; }
        .step-num {
            width: 1.55rem; height: 1.55rem; border-radius: 50%; background: var(--wiz-brand); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 0.75rem; margin-bottom: 0.12rem;
        }
        .step-pill.active .step-num { background: var(--wiz-accent); }
        .wiz-pane { display: none; animation: fadeIn 0.25s ease; }
        .wiz-pane.show { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .review-dl dt { font-weight: 600; color: #475569; }
        .subhead { font-size: 0.9rem; color: #64748b; margin: 1rem 0 0.75rem; font-weight: 600; }
        .l05-exam-card { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; }
        .l05-exam-card .card-header { border-bottom: 1px solid #e2e8f0; background: #fff; }
        .l05-exam-card.ol .card-header { background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%); }
        .l05-exam-card.al .card-header { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
        .l05-exam-card .card-header .l05-exam-title { color: #0f172a; font-weight: 600; font-size: 1.05rem; }
        .l05-exam-card .card-header .l05-exam-sub { font-size: 0.8rem; color: #64748b; }
        .l05-subject-slot { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 1rem; height: 100%; }
        .l05-subject-slot .l05-slot-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; }
        .l05-section-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #475569; margin-bottom: 0.75rem; }
        .l05-course-pref-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; }
        .l05-course-pref-card .l05-pref-badge { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #64748b; margin-bottom: 0.75rem; }
    </style>
</head>
<body class="public-app">
<div class="app-form-bg" aria-hidden="true"></div>
<div class="public-app-shell">
<header class="l05-site-header border-bottom bg-white shadow-sm">
    <div class="container-fluid px-3 px-lg-4 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <a class="text-decoration-none text-dark fw-semibold d-inline-flex align-items-center" href="<?php echo htmlspecialchars($l05OfficialSiteUrl, ENT_QUOTES, 'UTF-8'); ?>" rel="noopener noreferrer">
            <i class="fas fa-graduation-cap text-primary me-2" aria-hidden="true"></i><span class="d-none d-sm-inline">Sri Lanka German Training Institute</span><span class="d-inline d-sm-none">SLGTI</span>
        </a>
        <nav class="d-flex align-items-center gap-2 small" aria-label="Site">
            <a href="<?php echo htmlspecialchars($l05OfficialSiteUrl, ENT_QUOTES, 'UTF-8'); ?>" class="text-muted text-decoration-none" rel="noopener noreferrer">Home</a>
        </nav>
    </div>
</header>
<main class="container-fluid px-2 px-sm-3 px-lg-4 app-form-main pt-3 pt-md-4 pb-2 l05-app-main">
<div class="app-form-page">
<div class="container py-3 py-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card wiz-card">
                <div class="card-body p-4 p-md-5">
                    <div class="wiz-header mb-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="min-w-0">
                                <div class="small text-muted fw-semibold">Sri Lanka German Training Institute · Apply online 2026</div>
                                <h1 class="h3 mb-0 text-dark">SLGTI NVQ Level 05 application — 2026</h1>
                            </div>
                            <span class="badge text-bg-primary bg-opacity-75">Level 05</span>
                        </div>
                    </div>

                    <div id="globalAlert" class="alert d-none" role="alert"></div>
                    <div id="successBanner" class="alert alert-success d-none" role="status">
                        <div id="successBannerText" class="mb-3 fw-semibold"></div>
                        <p class="small text-success-emphasis mb-2">Keep a copy for your records.</p>
                        <button type="button" class="btn btn-dark btn-sm" id="btnDownloadApplicationPdfSuccess">
                            <i class="fas fa-file-pdf me-1"></i> Download application (PDF)
                        </button>
                    </div>

                    <div class="step-pills" id="stepPills" aria-label="Progress">
                        <?php
                        $labels = ['NIC', 'Personal', 'Contact', 'School', 'NVQ', 'Courses', 'Documents', 'Review'];
                        for ($i = 0; $i < 8; $i++) :
                        ?>
                        <div class="step-pill" data-step="<?php echo $i + 1; ?>">
                            <span class="step-num"><?php echo $i + 1; ?></span>
                            <span><?php echo $labels[$i]; ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <form id="wizardForm" method="post" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="application_id" id="application_id" value="">

                        <div id="nicContextBarFixed" class="alert py-2 px-3 small mb-3 d-none" role="status"></div>

                        <!-- Step 1 -->
                        <div class="wiz-pane show" data-step="1">
                            <h2 class="h5 mb-3">Step 1</h2>
                            <p class="text-muted small">Enter your National Identity Card number and click <strong>Next</strong>. If you already applied, your details will load so you can review or edit and submit again.</p>
                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label for="student_nic" class="form-label">NIC <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="student_nic" name="student_nic" maxlength="20" required autocomplete="off" placeholder="e.g. 123456789V or 12 digits">
                                    <div class="invalid-feedback">Enter a valid NIC.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 Personal -->
                        <div class="wiz-pane" data-step="2">
                            <h2 class="h5 mb-3">Step 2 — Personal</h2>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="student_title">Title <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_title" name="student_title" required>
                                        <option value="">Choose…</option>
                                        <?php foreach (['Mr', 'Miss', 'Mrs'] as $t) : ?>
                                        <option value="<?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label" for="student_full_name">Full name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_full_name" name="student_full_name" maxlength="255" required>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="student_initial_name">Name with initials <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_initial_name" name="student_initial_name" maxlength="255" required>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="student_gender">Gender <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_gender" name="student_gender" required>
                                        <option value="">Choose…</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="student_civil_status">Civil status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_civil_status" name="student_civil_status" required>
                                        <option value="">Choose…</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="student_dob">Date of birth <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="student_dob" name="student_dob" required
                                        min="<?php echo htmlspecialchars($l05DobMin, ENT_QUOTES, 'UTF-8'); ?>"
                                        max="<?php echo htmlspecialchars($l05DobMax, ENT_QUOTES, 'UTF-8'); ?>"
                                        title="You must be at least 16 years old.">
                                    <div class="form-text">Applicants must be at least 16 years old.</div>
                                    <div class="invalid-feedback">Enter your date of birth (you must be at least 16).</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="student_language">Language <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_language" name="student_language" required>
                                        <option value="">Choose…</option>
                                        <option value="Sinhala">Sinhala</option>
                                        <option value="Tamil">Tamil</option>
                                        <option value="English">English</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="student_religion">Religion <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_religion" name="student_religion" required>
                                        <option value="">Choose…</option>
                                        <option value="Buddhism">Buddhism</option>
                                        <option value="Hinduism">Hinduism</option>
                                        <option value="Islam">Islam</option>
                                        <option value="Christianity">Christianity</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 Contact -->
                        <div class="wiz-pane" data-step="3">
                            <h2 class="h5 mb-3">Step 3 — Contact &amp; address</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="student_email">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="student_email" name="student_email" maxlength="255" required>
                                    <div class="invalid-feedback">Valid email required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="student_phone">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_phone" name="student_phone" maxlength="30" inputmode="numeric" required>
                                    <div class="invalid-feedback">Invalid phone.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="student_whatsapp">WhatsApp <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_whatsapp" name="student_whatsapp" maxlength="30" inputmode="numeric" required>
                                    <div class="invalid-feedback">Invalid number.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="student_address">Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="student_address" name="student_address" rows="3" required></textarea>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="student_province">Province <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_province" name="student_province" required>
                                        <option value="">Choose…</option>
                                        <?php foreach (array_keys($slProv) as $prov) : ?>
                                        <option value="<?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($prov, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="student_district">District <span class="text-danger">*</span></label>
                                    <select class="form-select" id="student_district" name="student_district" required>
                                        <option value="">Choose province first…</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="student_zip_code">Postal / ZIP code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="student_zip_code" name="student_zip_code" maxlength="20" required>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 O/L A/L -->
                        <div class="wiz-pane" data-step="4">
                            <h2 class="h5 mb-2">Step 4 — School qualifications</h2>
                            <p class="text-muted small mb-4"><strong>A/L</strong> can qualify you without NVQ when fully completed. O/L is optional but must be finished or cleared if you start it. Do not leave A/L partly filled; rules are checked in step&nbsp;5.</p>

                            <div class="card l05-exam-card ol shadow-sm mb-4">
                                <div class="card-header py-3 d-flex align-items-start gap-3">
                                    <span class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:2.5rem;height:2.5rem;">
                                        <i class="fas fa-book-open" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <div class="l05-exam-title">G.C.E. Ordinary Level (O/L)</div>
                                        <p class="l05-exam-sub mb-0">Optional — complete only if you apply with school qualifications</p>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="l05-section-label">Examination details</div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-secondary" for="ol_index_number">Index number</label>
                                            <input type="text" class="form-control" id="ol_index_number" name="ol_index_number" maxlength="50" placeholder="O/L index">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold text-secondary" for="ol_exam_year">Year of examination</label>
                                            <input type="text" class="form-control" id="ol_exam_year" name="ol_exam_year" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" placeholder="e.g. 2019" title="4-digit year">
                                        </div>
                                    </div>
                                    <div class="l05-section-label">Subjects &amp; results</div>
                                    <div class="row g-3">
                                        <?php
                                        $old = [];
                                        for ($i = 1; $i <= 9; $i++) :
                                            $variant = 'l05';
                                            $extraAttr = '';
                                            $slotReqHtml = '';
                                            require dirname(__DIR__) . '/views/student_application/_ol_subject_slot.php';
                                        endfor;
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card l05-exam-card al shadow-sm">
                                <div class="card-header py-3 d-flex align-items-start gap-3">
                                    <span class="rounded-3 bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:2.5rem;height:2.5rem;">
                                        <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <div class="l05-exam-title">G.C.E. Advanced Level (A/L)</div>
                                        <p class="l05-exam-sub mb-0">Optional if you complete NVQ next — otherwise fill every field here</p>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="l05-section-label">Examination details</div>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold text-secondary" for="al_index_number">Index number</label>
                                            <input type="text" class="form-control" id="al_index_number" name="al_index_number" maxlength="50" placeholder="A/L index">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold text-secondary" for="al_exam_year">Year of examination</label>
                                            <input type="text" class="form-control" id="al_exam_year" name="al_exam_year" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" autocomplete="off" placeholder="e.g. 2022" title="4-digit year">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold text-secondary" for="al_stream">G.C.E. A/L stream (Sri Lanka)</label>
                                            <select class="form-select" id="al_stream" name="al_stream" aria-label="G.C.E. Advanced Level stream — Sri Lanka national categories">
                                                <option value="">Choose stream…</option>
                                                <?php foreach ($l05AlStreams as $st) : ?>
                                                <option value="<?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="l05-section-label">Subjects &amp; results</div>
                                    <p class="small text-muted mb-3">Choose each subject and result (<strong>A</strong>, <strong>B</strong>, <strong>C</strong>, <strong>S</strong>, or <strong>W</strong>).</p>
                                    <div class="row g-3">
                                        <?php for ($i = 1; $i <= 3; $i++) : $s = sprintf('%02d', $i); ?>
                                        <div class="col-md-4">
                                            <div class="l05-subject-slot">
                                                <div class="l05-slot-label">A/L subject <?php echo $i; ?></div>
                                                <label class="form-label visually-hidden" for="al_subject_name_<?php echo $s; ?>">A/L subject <?php echo $i; ?> name</label>
                                                <select class="form-select form-select-sm mb-2" id="al_subject_name_<?php echo $s; ?>" name="al_subject_name_<?php echo $s; ?>">
                                                    <option value="">Choose subject…</option>
                                                    <?php foreach ($l05AlSubjects as $asub) : ?>
                                                    <option value="<?php echo htmlspecialchars($asub, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($asub, ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label class="form-label visually-hidden" for="al_subject_<?php echo $s; ?>_marks">A/L subject <?php echo $i; ?> result</label>
                                                <select class="form-select form-select-sm" id="al_subject_<?php echo $s; ?>_marks" name="al_subject_<?php echo $s; ?>_marks">
                                                    <option value="">Choose result…</option>
                                                    <?php foreach ($l05GradeLetters as $g) : ?>
                                                    <option value="<?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 NVQ -->
                        <div class="wiz-pane" data-step="5">
                            <h2 class="h5 mb-3">Step 5 — NVQ</h2>
                            <p class="text-muted small">If you already have <strong>NVQ Level 4</strong>, enter it here. If you <strong>completed G.C.E. A/L</strong> in the step above, choose <strong>None</strong> — you do not need an NVQ for this application.</p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="nvq_level">Your NVQ qualification</label>
                                    <select class="form-select" id="nvq_level" name="nvq_level">
                                        <option value="">None — not declaring NVQ (e.g. qualified with G.C.E. A/L)</option>
                                        <option value="4">NVQ Level 4</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label" for="nvq_course_name">Course / qualification name</label>
                                    <input type="text" class="form-control" id="nvq_course_name" name="nvq_course_name" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="nvq_institute_name">Institute</label>
                                    <input type="text" class="form-control" id="nvq_institute_name" name="nvq_institute_name" maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="nvq_year_completed">Year completed</label>
                                    <input type="number" class="form-control" id="nvq_year_completed" name="nvq_year_completed" min="1900" max="2100" step="1">
                                </div>
                            </div>
                        </div>

                        <!-- Step 6 Courses -->
                        <div class="wiz-pane" data-step="6">
                            <h2 class="h5 mb-2">Step 6 — Course choices</h2>
                            <p class="text-muted small mb-4">Choose a <strong>department</strong> first; the <strong>course</strong> list loads NVQ Level 05 courses for that department. First choice is required; second and third are optional.</p>
                            <?php foreach ([1 => 'First', 2 => 'Second', 3 => 'Third'] as $prefNum => $prefLabel) :
                                $reqFirst = ((int) $prefNum === 1);
                            ?>
                            <div class="card l05-course-pref-card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="l05-pref-badge"><?php echo htmlspecialchars($prefLabel, ENT_QUOTES, 'UTF-8'); ?> choice <?php echo $reqFirst ? '<span class="text-danger">*</span>' : '<span class="text-muted fw-normal">(optional)</span>'; ?></div>
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold" for="dept_pref_<?php echo (int) $prefNum; ?>">Department <?php echo $reqFirst ? '<span class="text-danger">*</span>' : ''; ?></label>
                                            <select class="form-select" id="dept_pref_<?php echo (int) $prefNum; ?>" name="dept_pref_<?php echo (int) $prefNum; ?>" data-l05-pref="<?php echo (int) $prefNum; ?>">
                                                <option value="">Loading departments…</option>
                                            </select>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label small fw-semibold" for="course_priority_<?php echo (int) $prefNum; ?>">Course (NVQ 05) <?php echo $reqFirst ? '<span class="text-danger">*</span>' : ''; ?></label>
                                            <select class="form-select" id="course_priority_<?php echo (int) $prefNum; ?>" name="course_priority_<?php echo (int) $prefNum; ?>"<?php echo $reqFirst ? ' required' : ''; ?>>
                                                <option value="">Choose department first…</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <p class="small text-muted mb-0"><i class="fas fa-info-circle me-1"></i>Lists come from the main application catalogue. If loading fails, check you open this page from the same site as the SLGTI portal.</p>
                        </div>

                        <!-- Step 7 Documents -->
                        <div class="wiz-pane" data-step="7">
                            <h2 class="h5 mb-3">Step 7 — Documents</h2>
                            <p class="text-muted small" id="docHelpNew">Upload required documents (PDF, JPG, or PNG, max 5 MB each). JPG/PNG scans are automatically compressed to about 100 KB and stored as JPEG; PDFs are kept as uploaded.</p>
                            <p class="text-muted small d-none" id="docHelpUpdate">Updating: leave a file empty to keep the existing upload. Replace by choosing a new file.</p>
                            <div class="row g-3">
                                <?php
                                $docs = [
                                    'nic_document' => 'NIC copy',
                                    'birth_certificate' => 'Birth certificate',
                                    'ol_certificate' => 'O/L certificate',
                                    'al_certificate' => 'A/L certificate',
                                    'nvq_certificate' => 'NVQ certificate',
                                    'bank_receipt' => 'Bank receipt',
                                ];
                                $requiredDocs = ['nic_document' => true, 'birth_certificate' => true, 'bank_receipt' => true];
                                $docPathKeys = [
                                    'nic_document' => 'nic_document_path',
                                    'birth_certificate' => 'birth_certificate_path',
                                    'ol_certificate' => 'ol_certificate_path',
                                    'al_certificate' => 'al_certificate_path',
                                    'nvq_certificate' => 'nvq_certificate_path',
                                    'bank_receipt' => 'bank_receipt_path',
                                ];
                                foreach ($docs as $fname => $label) :
                                    $pk = $docPathKeys[$fname];
                                    $isReq = !empty($requiredDocs[$fname]);
                                ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?php echo htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?><?php echo $isReq ? ' <span class="text-danger doc-req">*</span>' : ''; ?></label>
                                    <input type="file" class="form-control" id="<?php echo htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                                    <div class="form-text existing-hint" data-path-key="<?php echo htmlspecialchars($pk, ENT_QUOTES, 'UTF-8'); ?>"></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 8 Review -->
                        <div class="wiz-pane" data-step="8">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h2 class="h5 mb-1">Step 8 — Review</h2>
                                    <p class="text-muted small mb-0">Use <strong>Previous</strong> to edit. Submit sends your application to the server.</p>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" id="btnDownloadApplicationPdfReview" title="Download a formatted PDF summary of the form">
                                    <i class="fas fa-file-pdf me-1"></i> Download application (PDF)
                                </button>
                            </div>
                            <dl class="row small" id="reviewDl"></dl>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary" id="btnPrev" disabled>
                                <i class="fas fa-arrow-left me-1"></i> Previous
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="btnNext">
                                    Next <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                                <button type="button" class="btn btn-success d-none" id="btnSubmit">
                                    <i class="fas fa-check me-1"></i> Submit
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
</main>
<footer class="container-fluid px-2 px-sm-3 px-lg-4 app-form-footer text-center">
    <div class="app-form-footer-inner py-3 py-md-4">
        <p class="mb-1 app-form-footer-brand">Sri Lanka German Training Institute</p>
        <p class="mb-0 app-form-footer-meta">Apply online 2026 · &copy; <?php echo date('Y'); ?></p>
    </div>
</footer>
</div>

<script id="slProvJson" type="application/json"><?php echo json_encode($slProv, JSON_UNESCAPED_UNICODE); ?></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js" crossorigin="anonymous"></script>
<script>
(function () {
  var totalSteps = 8;
  var currentStep = 1;
  /** True if this NIC already had a row before this visit (check_nic status === exists). */
  var recordFromDb = false;
  /** True if any document path was stored for this application (re-upload optional). */
  var hadUploadedDocs = false;
  var nicChecked = false;
  /** True after NIC check when application is shown read-only (documents on file / submitted). */
  var l05ReviewOnlyMode = false;

  var slProv = {};
  try {
    slProv = JSON.parse(document.getElementById('slProvJson').textContent || '{}');
  } catch (e) { slProv = {}; }

  var PATH_KEYS = {
    nic_document: 'nic_document_path',
    birth_certificate: 'birth_certificate_path',
    ol_certificate: 'ol_certificate_path',
    al_certificate: 'al_certificate_path',
    nvq_certificate: 'nvq_certificate_path',
    bank_receipt: 'bank_receipt_path'
  };
  var DOC_PDF_LABELS = {
    nic_document: 'NIC copy',
    birth_certificate: 'Birth certificate',
    ol_certificate: 'O/L certificate',
    al_certificate: 'A/L certificate',
    nvq_certificate: 'NVQ certificate',
    bank_receipt: 'Bank receipt'
  };

  function $(id) { return document.getElementById(id); }

  /** Absolute URL to endpoints in this directory (avoids wrong path when page URL is …/level05application without trailing slash). */
  function apiUrl(file) {
    var b = window.L05_API_BASE || '';
    if (b.charAt(0) !== '/') b = '/' + b;
    if (b.charAt(b.length - 1) !== '/') b += '/';
    file = String(file || '').replace(/^\//, '');
    return window.location.origin + b + file;
  }

  /** Base path to main SIS (parent of /level05application/) for public course API. */
  function mainAppApiUrl(path) {
    var base = (window.L05_MAIN_APP_BASE || '').replace(/\/$/, '');
    path = String(path || '').replace(/^\//, '');
    return window.location.origin + base + '/' + path;
  }

  var L05_NVQ = '5';
  var l05DeptsCache = null;
  var l05CourseStepListenersWired = false;
  var l05CourseStepInitPromise = null;

  function l05FillDeptSelect(sel, departments) {
    if (!sel) return;
    sel.innerHTML = '';
    var z = document.createElement('option');
    z.value = '';
    z.textContent = 'Choose department…';
    sel.appendChild(z);
    if (!departments || departments.length === 0) {
      var n = document.createElement('option');
      n.value = '';
      n.textContent = 'No departments for NVQ 05';
      sel.appendChild(n);
      return;
    }
    departments.forEach(function (d) {
      var o = document.createElement('option');
      o.value = d.department_id || '';
      o.textContent = (d.department_name || '').trim();
      sel.appendChild(o);
    });
  }

  /** Match public student_application form: store course name only; support old "id — name" saves. */
  function l05CourseNameFromLegacyStored(stored) {
    if (!stored) return '';
    var s = String(stored).trim();
    var em = '\u2014';
    var sep = ' ' + em + ' ';
    var i = s.indexOf(sep);
    if (i !== -1) return s.substring(i + sep.length).trim();
    i = s.indexOf(' — ');
    if (i !== -1) return s.substring(i + 3).trim();
    return s;
  }

  function l05CourseOptionValue(c) {
    return ((c.course_name || '').trim()).substring(0, 150);
  }

  function l05FillCourseSelect(sel, courses, selectedValue) {
    if (!sel) return;
    sel.innerHTML = '';
    var z = document.createElement('option');
    z.value = '';
    z.textContent = 'Choose course…';
    sel.appendChild(z);
    var wantValue = selectedValue ? String(selectedValue).trim() : '';
    if (wantValue) {
      var legacy = l05CourseNameFromLegacyStored(wantValue);
      if (legacy && legacy !== wantValue) wantValue = legacy.substring(0, 150);
    }
    (courses || []).forEach(function (c) {
      var o = document.createElement('option');
      o.value = l05CourseOptionValue(c);
      o.textContent = (c.course_name || '').trim();
      if (wantValue && wantValue === o.value) o.selected = true;
      sel.appendChild(o);
    });
    if (selectedValue && sel.value !== selectedValue) {
      var fb = document.createElement('option');
      fb.value = selectedValue;
      fb.textContent = selectedValue.length > 80 ? selectedValue.substring(0, 77) + '…' : selectedValue;
      fb.selected = true;
      sel.appendChild(fb);
    }
  }

  function l05FetchDepartments() {
    var qs = new URLSearchParams({ nvq_level: L05_NVQ });
    return fetch(mainAppApiUrl('student-application/api/departments?' + qs.toString()), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        return (j && j.success && Array.isArray(j.departments)) ? j.departments : [];
      })
      .catch(function () { return []; });
  }

  var l05CoursesByDept = {};
  function l05LoadCoursesForDept(deptId) {
    if (!deptId) return Promise.resolve([]);
    if (l05CoursesByDept[deptId]) return Promise.resolve(l05CoursesByDept[deptId]);
    var qs = new URLSearchParams({ department_id: deptId, nvq_level: L05_NVQ });
    return fetch(mainAppApiUrl('student-application/api/courses?' + qs.toString()), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var list = (j && j.success && Array.isArray(j.courses)) ? j.courses : [];
        l05CoursesByDept[deptId] = list;
        return list;
      })
      .catch(function () { return []; });
  }

  function l05WireCourseStepListeners() {
    if (l05CourseStepListenersWired) return;
    l05CourseStepListenersWired = true;
    [1, 2, 3].forEach(function (n) {
      var deptSel = $('dept_pref_' + n);
      var courseSel = $('course_priority_' + n);
      if (!deptSel || !courseSel) return;
      deptSel.addEventListener('change', function () {
        var id = (deptSel.value || '').trim();
        if (!id) {
          l05FillCourseSelect(courseSel, [], '');
          return;
        }
        courseSel.innerHTML = '<option value="">Loading courses…</option>';
        l05LoadCoursesForDept(id).then(function (courses) {
          l05FillCourseSelect(courseSel, courses, '');
        });
      });
    });
  }

  function l05InitCourseStep() {
    if (l05CourseStepInitPromise) return l05CourseStepInitPromise;
    l05WireCourseStepListeners();
    l05CourseStepInitPromise = l05FetchDepartments().then(function (depts) {
      l05DeptsCache = depts;
      [1, 2, 3].forEach(function (n) {
        var deptSel = $('dept_pref_' + n);
        if (deptSel) l05FillDeptSelect(deptSel, depts);
      });
    }).catch(function () {
      [1, 2, 3].forEach(function (n) {
        var deptSel = $('dept_pref_' + n);
        if (deptSel) {
          deptSel.innerHTML = '<option value="">Could not load departments</option>';
        }
      });
    });
    return l05CourseStepInitPromise;
  }

  function l05ResetCoursePriorityFields() {
    [1, 2, 3].forEach(function (n) {
      var d = $('dept_pref_' + n);
      var c = $('course_priority_' + n);
      if (d) d.value = '';
      if (c) l05FillCourseSelect(c, [], '');
    });
    if (l05DeptsCache) {
      [1, 2, 3].forEach(function (n) {
        var deptSel = $('dept_pref_' + n);
        if (deptSel) l05FillDeptSelect(deptSel, l05DeptsCache);
      });
    }
  }

  function l05FindDeptForCourseValue(want, depts) {
    if (!want || !depts || depts.length === 0) return Promise.resolve(null);
    want = l05CourseNameFromLegacyStored(want) || want;
    var tasks = depts.map(function (d) {
      return l05LoadCoursesForDept(d.department_id).then(function (courses) {
        return { d: d, courses: courses };
      });
    });
    return Promise.all(tasks).then(function (rows) {
      for (var i = 0; i < rows.length; i++) {
        var courses = rows[i].courses;
        for (var j = 0; j < courses.length; j++) {
          if (l05CourseOptionValue(courses[j]) === want) return rows[i].d;
        }
      }
      var prefix = want.split(/[\s—\-]/)[0].trim();
      if (prefix) {
        for (var a = 0; a < rows.length; a++) {
          var crs = rows[a].courses;
          for (var b = 0; b < crs.length; b++) {
            if ((crs[b].course_id || '') === prefix) return rows[a].d;
          }
        }
      }
      return null;
    });
  }

  function l05RestoreCoursePreferences(data) {
    if (!data) return Promise.resolve();
    return l05InitCourseStep().then(function () {
      var depts = l05DeptsCache || [];
      var tasks = [1, 2, 3].map(function (n) {
        var want = String(data['course_priority_' + n] || '').trim();
        var deptSel = $('dept_pref_' + n);
        var courseSel = $('course_priority_' + n);
        if (!want) {
          if (deptSel) deptSel.value = '';
          if (courseSel) l05FillCourseSelect(courseSel, [], '');
          return Promise.resolve();
        }
        return l05FindDeptForCourseValue(want, depts).then(function (d) {
          if (!d || !deptSel || !courseSel) {
            if (deptSel) deptSel.value = '';
            l05FillCourseSelect(courseSel, [], '');
            var o = document.createElement('option');
            o.value = want;
            o.textContent = want.length > 70 ? want.substring(0, 67) + '…' : want;
            o.selected = true;
            courseSel.appendChild(o);
            return;
          }
          deptSel.value = d.department_id;
          return l05LoadCoursesForDept(d.department_id).then(function (courses) {
            l05FillCourseSelect(courseSel, courses, want);
          });
        });
      });
      return Promise.all(tasks);
    });
  }

  var form = $('wizardForm');

  function showAlert(msg, kind) {
    var el = $('globalAlert');
    el.className = 'alert alert-' + (kind || 'danger');
    el.textContent = msg;
    el.classList.remove('d-none');
    $('successBanner').classList.add('d-none');
  }
  function hideAlert() {
    $('globalAlert').classList.add('d-none');
  }

  function normalizeNic(s) {
    return String(s || '').toUpperCase().trim().replace(/[\s\-_]+/g, '');
  }
  function isValidNic(n) {
    return /^(\d{9}[VX]|\d{12})$/.test(normalizeNic(n));
  }

  function digitsOnly(s) {
    return String(s || '').replace(/\D/g, '');
  }
  function phoneOk(v) {
    var d = digitsOnly(v);
    if (d.indexOf('94') === 0 && d.length > 2) d = d.slice(2);
    else if (d.indexOf('0') === 0 && d.length > 1) d = d.slice(1);
    return d.length === 9 && /^[1-9]\d{8}$/.test(d);
  }

  /** G.C.E. O/L and A/L (Level 05): A, B, C, S, or W — matches result dropdowns. */
  function olResultOk(raw) {
    var m = String(raw || '').trim().toUpperCase();
    return m === 'A' || m === 'B' || m === 'C' || m === 'S' || m === 'W';
  }

  function escapeHtml(t) {
    var d = document.createElement('div');
    d.textContent = t;
    return d.innerHTML;
  }

  function l05PdfCell(s) {
    var t = String(s == null ? '' : s).replace(/\u0000/g, '');
    if (t.length > 650) t = t.substring(0, 647) + '…';
    return t;
  }

  /** Visible text for PDF (selects use chosen option label, not internal value). */
  function l05FieldText(id) {
    var el = $(id);
    if (!el) return '';
    if (el.tagName === 'SELECT') {
      var i = el.selectedIndex;
      if (i < 0 || !el.options[i]) return String(el.value || '').trim();
      var tx = String(el.options[i].text || '').trim();
      if (/^(Choose|Loading|No departments)/i.test(tx)) return '';
      return tx;
    }
    if (el.tagName === 'INPUT' && el.type === 'hidden') {
      return String(el.value || '').trim();
    }
    if (el.tagName === 'TEXTAREA' || (el.tagName === 'INPUT' && el.type !== 'file')) {
      return String(el.value || '').trim();
    }
    return '';
  }

  function l05DownloadApplicationPdf() {
    if (typeof window.jspdf === 'undefined') {
      window.alert('PDF tools did not load. Please check your connection and refresh the page.');
      return;
    }
    var jsPDF = window.jspdf.jsPDF;
    var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    var pageW = doc.internal.pageSize.getWidth();
    var marginX = 14;
    var y = 14;
    doc.setTextColor(12, 74, 110);
    doc.setFontSize(15);
    doc.setFont('helvetica', 'bold');
    doc.text('Sri Lanka German Training Institute', pageW / 2, y, { align: 'center' });
    y += 7;
    doc.setFontSize(11);
    doc.text('NVQ Level 05 Application — 2026', pageW / 2, y, { align: 'center' });
    y += 6;
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(71, 85, 105);
    doc.setFontSize(9);
    doc.text('Application summary (copy for your records)', pageW / 2, y, { align: 'center' });
    y += 5;
    var nic = normalizeNic(l05FieldText('student_nic'));
    var appId = getApplicationId();
    doc.text('NIC: ' + (nic || '—') + '    Reference: ' + (appId ? '#' + appId : '—'), pageW / 2, y, { align: 'center' });
    y += 4;
    doc.text('Generated: ' + new Date().toLocaleString(), pageW / 2, y, { align: 'center' });
    y += 9;
    doc.setTextColor(33, 37, 41);

    function addSection(title, pairs) {
      var body = [];
      for (var i = 0; i < pairs.length; i++) {
        var val = pairs[i][1];
        if (val === '' || val === undefined || val === null) val = '—';
        body.push([l05PdfCell(pairs[i][0]), l05PdfCell(val)]);
      }
      if (body.length === 0) return;
      var pageH = doc.internal.pageSize.getHeight();
      var estH = 14 + body.length * 6;
      if (y + estH > pageH - 10) {
        doc.addPage();
        y = 14;
      }
      doc.autoTable({
        startY: y,
        margin: { left: marginX, right: marginX, bottom: 14 },
        head: [[{ content: title, colSpan: 2 }]],
        body: body,
        theme: 'striped',
        styles: {
          fontSize: 8.5,
          cellPadding: 1.6,
          textColor: [33, 37, 41],
          lineColor: [200, 208, 216],
          lineWidth: 0.15,
          minCellHeight: 5
        },
        headStyles: {
          fillColor: [12, 74, 110],
          textColor: [255, 255, 255],
          fontStyle: 'bold',
          fontSize: 10,
          halign: 'left',
          valign: 'middle'
        },
        columnStyles: {
          0: { cellWidth: 48, fontStyle: 'bold', textColor: [51, 65, 85] },
          1: { cellWidth: pageW - marginX * 2 - 48 }
        }
      });
      y = doc.lastAutoTable.finalY + 6;
    }

    addSection('1. Personal information', [
      ['Title', l05FieldText('student_title')],
      ['Full name', l05FieldText('student_full_name')],
      ['Name with initials', l05FieldText('student_initial_name')],
      ['Gender', l05FieldText('student_gender')],
      ['Civil status', l05FieldText('student_civil_status')],
      ['Date of birth', l05FieldText('student_dob')],
      ['Language', l05FieldText('student_language')],
      ['Religion', l05FieldText('student_religion')]
    ]);

    addSection('2. Contact and address', [
      ['Email', l05FieldText('student_email')],
      ['Phone', l05FieldText('student_phone')],
      ['WhatsApp', l05FieldText('student_whatsapp')],
      ['Address', l05FieldText('student_address')],
      ['Postal / ZIP code', l05FieldText('student_zip_code')],
      ['Province', l05FieldText('student_province')],
      ['District', l05FieldText('student_district')]
    ]);

    var olRows = [
      ['O/L index number', l05FieldText('ol_index_number')],
      ['O/L examination year', l05FieldText('ol_exam_year')]
    ];
    for (var oi = 1; oi <= 9; oi++) {
      var os = (oi < 10 ? '0' : '') + oi;
      olRows.push(['O/L subject ' + oi + ' — name', l05FieldText('ol_subject_name_' + os)]);
      olRows.push(['O/L subject ' + oi + ' — result', l05FieldText('ol_subject_' + os + '_marks')]);
    }
    addSection('3. G.C.E. Ordinary Level (O/L)', olRows);

    var alRows = [
      ['A/L index number', l05FieldText('al_index_number')],
      ['A/L examination year', l05FieldText('al_exam_year')],
      ['G.C.E. A/L stream (Sri Lanka)', l05FieldText('al_stream')]
    ];
    for (var aj = 1; aj <= 3; aj++) {
      var as = '0' + aj;
      alRows.push(['A/L subject ' + aj + ' — name', l05FieldText('al_subject_name_' + as)]);
      alRows.push(['A/L subject ' + aj + ' — result', l05FieldText('al_subject_' + as + '_marks')]);
    }
    addSection('4. G.C.E. Advanced Level (A/L)', alRows);

    addSection('5. NVQ qualification', [
      ['NVQ level', l05FieldText('nvq_level')],
      ['Course / qualification name', l05FieldText('nvq_course_name')],
      ['Institute', l05FieldText('nvq_institute_name')],
      ['Year completed', l05FieldText('nvq_year_completed')]
    ]);

    addSection('6. Course choices', [
      ['First choice — department', l05FieldText('dept_pref_1')],
      ['First choice — course', l05FieldText('course_priority_1')],
      ['Second choice — department', l05FieldText('dept_pref_2')],
      ['Second choice — course', l05FieldText('course_priority_2')],
      ['Third choice — department', l05FieldText('dept_pref_3')],
      ['Third choice — course', l05FieldText('course_priority_3')]
    ]);

    var docRows = [];
    Object.keys(PATH_KEYS).forEach(function (fk) {
      var lbl = DOC_PDF_LABELS[fk] || fk;
      var inp = $(fk);
      var val = '';
      if (inp && inp.files && inp.files.length) {
        val = inp.files[0].name;
      } else {
        var pk = PATH_KEYS[fk];
        var hint = document.querySelector('.existing-hint[data-path-key="' + pk + '"]');
        if (hint && hint.textContent) {
          val = hint.textContent.replace(/^Current file:\s*/i, '').trim();
        }
        if (!val && recordFromDb && hadUploadedDocs) {
          val = 'Previously uploaded (unchanged)';
        }
      }
      docRows.push([lbl, val || '—']);
    });
    addSection('7. Documents', docRows);

    doc.setPage(doc.getNumberOfPages());
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(100, 116, 139);
    doc.text('SLGTI · NVQ Level 05 · PDF generated from your application data. Keep this file with your reference number.', marginX, doc.internal.pageSize.getHeight() - 7);

    var fn = 'SLGTI-L05-application';
    if (nic) fn += '-' + nic.replace(/[^0-9A-Z]/gi, '');
    fn += '-' + new Date().toISOString().slice(0, 10) + '.pdf';
    doc.save(fn);
  }

  function getApplicationId() {
    return parseInt($('application_id').value, 10) || 0;
  }

  function applyPathHintsFromResponse(j) {
    if (!j || !j.paths) return;
    var any = false;
    Object.keys(PATH_KEYS).forEach(function (field) {
      var pk = PATH_KEYS[field];
      var pv = j.paths[pk];
      if (pv && String(pv).trim() !== '') {
        any = true;
        var hint = document.querySelector('.existing-hint[data-path-key="' + pk + '"]');
        if (hint) {
          var parts = String(pv).split(/[/\\\\]/);
          hint.textContent = 'Current file: ' + parts[parts.length - 1];
        }
      }
    });
    if (any) hadUploadedDocs = true;
  }

  /** Ensure application_id and NIC are always sent (readonly fields + large multipart can omit or reorder parts). */
  function augmentFormDataIdentity(fd) {
    var id = getApplicationId();
    var nicVal = normalizeNic(($('student_nic') && $('student_nic').value) || '');
    if (fd.set) {
      fd.set('application_id', String(id));
      fd.set('student_nic', nicVal);
    } else {
      try {
        if (fd.delete) {
          fd.delete('application_id');
          fd.delete('student_nic');
        }
      } catch (e) {}
      fd.append('application_id', String(id));
      fd.append('student_nic', nicVal);
    }
  }

  var savingProgress = false;
  function saveWizardProgress(done) {
    var id = getApplicationId();
    if (id < 1) {
      if (typeof done === 'function') done(true);
      return;
    }
    if (savingProgress) {
      if (typeof done === 'function') done(false);
      return;
    }
    savingProgress = true;
    var bn = $('btnNext');
    var bp = $('btnPrev');
    if (bn) bn.disabled = true;
    if (bp) bp.disabled = true;
    // Build FormData but do NOT upload large files unless the user actually picked new ones.
    // This keeps Next/Previous fast (especially on Step 7).
    var fd = new FormData(form);
    try {
      Object.keys(PATH_KEYS).forEach(function (field) {
        var inp = $(field);
        if (!inp || !inp.files) return;
        if (inp.files.length === 0) {
          // No new selection → don't send this field at all.
          if (fd.delete) fd.delete(field);
        }
      });
    } catch (e) {}
    augmentFormDataIdentity(fd);
    fetch(apiUrl('wizard_save_progress.php'), { method: 'POST', body: fd })
      .then(function (r) {
        return r.text().then(function (t) { return { ok: r.ok, t: t }; });
      })
      .then(function (res) {
        savingProgress = false;
        if (bn) bn.disabled = false;
        if (bp) bp.disabled = currentStep <= 1;
        var j;
        try {
          j = JSON.parse(res.t);
        } catch (e) {
          showAlert('Could not save progress (invalid response).');
          if (typeof done === 'function') done(false);
          return;
        }
        if (!res.ok || !j.success) {
          showAlert(j.message || 'Could not save progress.');
          if (typeof done === 'function') done(false);
          return;
        }
        applyPathHintsFromResponse(j);
        if (typeof done === 'function') done(true);
      })
      .catch(function () {
        savingProgress = false;
        if (bn) bn.disabled = false;
        if (bp) bp.disabled = currentStep <= 1;
        showAlert('Network error while saving.');
        if (typeof done === 'function') done(false);
      });
  }

  function syncDistricts(preserve) {
    var prov = ($('student_province') && $('student_province').value) || '';
    var distSel = $('student_district');
    if (!distSel) return;
    var cur = preserve ? distSel.value : '';
    distSel.innerHTML = '';
    var opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = prov ? 'Choose…' : 'Choose province first…';
    distSel.appendChild(opt0);
    if (prov && slProv[prov]) {
      slProv[prov].forEach(function (d) {
        var o = document.createElement('option');
        o.value = d;
        o.textContent = d;
        distSel.appendChild(o);
      });
    }
    if (cur && distSel.querySelector('option[value="' + cur.replace(/"/g, '\\"') + '"]')) {
      distSel.value = cur;
    }
  }

  function syncZipFromDistrict(force) {
    var dist = ($('student_district') && $('student_district').value) || '';
    var zipEl = $('student_zip_code');
    if (!zipEl) return;
    var m = window.L05_DISTRICT_ZIP || {};
    var z = dist && m[dist] ? String(m[dist]) : '';
    if (!z) return;
    if (force || String(zipEl.value || '').trim() === '') {
      zipEl.value = z;
    }
  }

  if ($('student_province')) {
    $('student_province').addEventListener('change', function () {
      syncDistricts(false);
      // Province changed; clear ZIP so district selection can fill a new default.
      var zipEl = $('student_zip_code');
      if (zipEl) zipEl.value = '';
    });
  }
  if ($('student_district')) {
    $('student_district').addEventListener('change', function () { syncZipFromDistrict(true); });
  }

  function syncNicContextBar() {
    var bar = $('nicContextBarFixed');
    if (!bar) return;
    if (currentStep < 2 || !nicChecked) {
      bar.classList.add('d-none');
      bar.innerHTML = '';
      return;
    }
    // Read-only / submitted: do not show "continue" or "edit" hints (user request).
    if (l05ReviewOnlyMode) {
      bar.classList.add('d-none');
      bar.innerHTML = '';
      return;
    }
    bar.classList.remove('d-none', 'alert-info', 'alert-warning', 'alert-secondary');
    if (recordFromDb) {
      bar.classList.add('alert-warning');
      bar.innerHTML =
        '<i class="fas fa-rotate me-2"></i><strong>Continue your application.</strong> This NIC already has a Level 05 record. '
        + 'Change fields as needed, then use <strong>Next</strong> until you submit.';
    } else {
      bar.classList.add('alert-info');
      bar.innerHTML =
        '<i class="fas fa-user-plus me-2"></i><strong>New application.</strong> Your NIC is saved. Complete all steps and upload every document before submitting.';
    }
  }

  // When an existing record loads without completed uploads, fields stay editable until submit.
  var l05EditEnabled = true;
  function setFormEditable(editable) {
    l05EditEnabled = !!editable;
    if (!form) return;
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (!el || !el.name) return;
      if (el.name === 'student_nic' || el.type === 'hidden') return;
      // File inputs: disabling prevents selecting unless in edit mode.
      if (el.type === 'file') {
        el.disabled = !l05EditEnabled;
        return;
      }
      // Use readOnly when possible; fall back to disabled for selects.
      if (el.tagName === 'SELECT') {
        el.disabled = !l05EditEnabled;
      } else {
        el.readOnly = !l05EditEnabled;
      }
      el.classList.toggle('bg-light', !l05EditEnabled);
    });
  }

  function updatePills() {
    document.querySelectorAll('.step-pill').forEach(function (pill) {
      var n = parseInt(pill.getAttribute('data-step'), 10);
      pill.classList.toggle('active', n === currentStep);
      pill.classList.toggle('done', n < currentStep);
    });
  }

  function showStep(n) {
    document.querySelectorAll('.wiz-pane').forEach(function (pane) {
      var sn = parseInt(pane.getAttribute('data-step'), 10);
      pane.classList.toggle('show', sn === n);
    });
    currentStep = n;
    $('btnPrev').disabled = n === 1;
    $('btnNext').classList.toggle('d-none', n === totalSteps);
    $('btnSubmit').classList.toggle('d-none', n !== totalSteps);
    updatePills();
    hideAlert();
    syncNicContextBar();
    if (n === 7) syncDocUi();
    if (n === totalSteps) buildReview();
  }

  // When an application already exists and looks fully submitted, show review-only mode.
  function setReviewOnlyMode(on) {
    l05ReviewOnlyMode = !!on;
    var pills = $('stepPills');
    var bn = $('btnNext');
    var bp = $('btnPrev');
    var bs = $('btnSubmit');
    if (pills) pills.classList.toggle('d-none', !!on);
    if (bn) bn.classList.toggle('d-none', !!on);
    if (bp) bp.classList.toggle('d-none', !!on);
    if (bs) bs.classList.add('d-none');
  }

  function syncDocUi() {
    var newH = $('docHelpNew');
    var upH = $('docHelpUpdate');
    if (recordFromDb && hadUploadedDocs) {
      newH.classList.add('d-none');
      upH.classList.remove('d-none');
      document.querySelectorAll('.doc-req').forEach(function (s) { s.classList.add('d-none'); });
    } else {
      newH.classList.remove('d-none');
      upH.classList.add('d-none');
      document.querySelectorAll('.doc-req').forEach(function (s) { s.classList.remove('d-none'); });
    }
  }

  function olComplete() {
    var ids = ['ol_index_number', 'ol_exam_year'];
    for (var i = 1; i <= 9; i++) {
      var s = (i < 10 ? '0' : '') + i;
      ids.push('ol_subject_name_' + s, 'ol_subject_' + s + '_marks');
    }
    for (var k = 0; k < ids.length; k++) {
      var el = $(ids[k]);
      if (!el || String(el.value || '').trim() === '') return false;
    }
    for (var oi = 1; oi <= 9; oi++) {
      var os = (oi < 10 ? '0' : '') + oi;
      if (!olResultOk($('ol_subject_' + os + '_marks').value)) return false;
    }
    var yo = parseInt($('ol_exam_year').value, 10);
    return !isNaN(yo) && yo >= 1990 && yo <= 2100;
  }

  /** True when no user-entered O/L data (fixed slots 1–6 always post hidden subject names). */
  function olKeysEmpty() {
    var idx = $('ol_index_number');
    var yr = $('ol_exam_year');
    if (idx && String(idx.value || '').trim() !== '') return false;
    if (yr && String(yr.value || '').trim() !== '') return false;
    var i;
    for (i = 1; i <= 9; i++) {
      var s = (i < 10 ? '0' : '') + i;
      var mk = $('ol_subject_' + s + '_marks');
      if (mk && String(mk.value || '').trim() !== '') return false;
    }
    for (var j = 7; j <= 9; j++) {
      var bs = '0' + j;
      var sn = $('ol_subject_name_' + bs);
      if (sn && String(sn.value || '').trim() !== '') return false;
    }
    return true;
  }

  function alKeysEmpty() {
    var ids = ['al_index_number', 'al_exam_year', 'al_stream'];
    for (var j = 1; j <= 3; j++) {
      var t = '0' + j;
      ids.push('al_subject_name_' + t, 'al_subject_' + t + '_marks');
    }
    for (var k = 0; k < ids.length; k++) {
      var el = $(ids[k]);
      if (el && String(el.value || '').trim() !== '') return false;
    }
    return true;
  }

  function alComplete() {
    if (alKeysEmpty()) return false;
    var ids = ['al_index_number', 'al_exam_year', 'al_stream'];
    for (var j = 1; j <= 3; j++) {
      var t = '0' + j;
      ids.push('al_subject_name_' + t, 'al_subject_' + t + '_marks');
    }
    for (var k = 0; k < ids.length; k++) {
      var el = $(ids[k]);
      if (!el || String(el.value || '').trim() === '') return false;
    }
    for (var ai = 1; ai <= 3; ai++) {
      var as = '0' + ai;
      if (!olResultOk($('al_subject_' + as + '_marks').value)) return false;
    }
    var ya = parseInt($('al_exam_year').value, 10);
    return !isNaN(ya) && ya >= 1990 && ya <= 2100;
  }

  function nvqKeysEmpty() {
    return ['nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'].every(function (id) {
      var el = $(id);
      return !el || String(el.value || '').trim() === '';
    });
  }

  function nvqComplete() {
    if (nvqKeysEmpty()) return false;
    var lvl = String($('nvq_level').value || '').trim();
    if (lvl !== '4') return false;
    var ok = ['nvq_level', 'nvq_course_name', 'nvq_institute_name', 'nvq_year_completed'].every(function (id) {
      var el = $(id);
      return el && String(el.value || '').trim() !== '';
    });
    if (!ok) return false;
    var yn = parseInt($('nvq_year_completed').value, 10);
    return !isNaN(yn) && yn >= 1900 && yn <= 2100;
  }

  /** Birth date on/before “today minus 16 years” (matches HTML max and server validation). */
  function l05DobAtLeast16(ymd) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(ymd)) return false;
    var p = ymd.split('-');
    var birth = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    var now = new Date();
    var cut = new Date(now.getFullYear() - 16, now.getMonth(), now.getDate());
    return birth.getTime() <= cut.getTime();
  }

  function validateStep(step) {
    hideAlert();
    if (step === 1) {
      var nicEl = $('student_nic');
      var ok = isValidNic(nicEl.value);
      nicEl.classList.toggle('is-invalid', !ok);
      return ok;
    }
    if (step === 2) {
      var ok = true;
      ['student_title', 'student_full_name', 'student_initial_name', 'student_gender', 'student_civil_status', 'student_dob', 'student_language', 'student_religion'].forEach(function (id) {
        var el = $(id);
        var v = (el.value || '').trim();
        var fieldOk = !!v;
        if (id === 'student_dob' && v) {
          fieldOk = l05DobAtLeast16(v);
        }
        el.classList.toggle('is-invalid', !fieldOk);
        if (!fieldOk) ok = false;
      });
      return ok;
    }
    if (step === 3) {
      var ok = true;
      ['student_email', 'student_address', 'student_zip_code', 'student_province', 'student_district'].forEach(function (id) {
        var el = $(id);
        var v = (el.value || '').trim();
        el.classList.toggle('is-invalid', !v);
        if (!v) ok = false;
      });
      var em = $('student_email');
      var ev = (em.value || '').trim();
      em.classList.toggle('is-invalid', !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(ev));
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(ev)) ok = false;
      $('student_phone').classList.toggle('is-invalid', !phoneOk($('student_phone').value));
      if (!phoneOk($('student_phone').value)) ok = false;
      $('student_whatsapp').classList.toggle('is-invalid', !phoneOk($('student_whatsapp').value));
      if (!phoneOk($('student_whatsapp').value)) ok = false;
      if ($('student_province').value && slProv[$('student_province').value]) {
        var allowed = slProv[$('student_province').value];
        var d = $('student_district').value;
        if (allowed.indexOf(d) === -1) {
          $('student_district').classList.add('is-invalid');
          ok = false;
        }
      }
      return ok;
    }
    if (step === 4) {
      // Do not validate partial O/L here — browser number spinners can set a year without intent; NVQ-only users leave O/L blank.
      // Partial O/L / A/L vs NVQ is enforced in step 5 (and on submit).
      if (!alKeysEmpty() && !alComplete()) {
        showAlert('Either complete all A/L fields (index, year, Sri Lanka G.C.E. A/L stream, three subjects and results) or clear every A/L field if you will use NVQ instead.');
        return false;
      }
      return true;
    }
    if (step === 5) {
      // Partial O/L matters only if A/L is not complete (full A/L satisfies school path without NVQ).
      if (!olKeysEmpty() && !olComplete() && !alComplete()) {
        showAlert('Either complete all O/L fields (index, year, all subject results, basket subjects) or clear index, year, results, and basket subjects if you will use NVQ instead.');
        return false;
      }
      if (!alKeysEmpty() && !alComplete()) {
        showAlert('Either complete all A/L fields (index, year, Sri Lanka G.C.E. A/L stream, three subjects and results) or clear every A/L field if you will use NVQ instead.');
        return false;
      }
      if (!nvqKeysEmpty() && !nvqComplete()) {
        showAlert('Either choose NVQ Level 4 and complete course, institute, and year, or clear all NVQ fields (None) if you completed G.C.E. A/L.');
        return false;
      }
      // Submit rule: full NVQ Level 4, or full G.C.E. A/L (O/L optional). NVQ not required when A/L is complete.
      var schoolOk = alComplete();
      if (!nvqComplete() && !schoolOk) {
        showAlert('Complete G.C.E. A/L in full, or provide full NVQ Level 4 (course, institute, year). NVQ is not required when A/L is complete.');
        return false;
      }
      return true;
    }
    if (step === 6) {
      var d1 = $('dept_pref_1');
      var c1 = $('course_priority_1');
      var okD = d1 && String(d1.value || '').trim() !== '';
      var okC = c1 && String(c1.value || '').trim() !== '';
      if (d1) d1.classList.toggle('is-invalid', !okD);
      if (c1) c1.classList.toggle('is-invalid', !okC);
      for (var pn = 2; pn <= 3; pn++) {
        var dn = $('dept_pref_' + pn);
        var cn = $('course_priority_' + pn);
        var dv = dn ? String(dn.value || '').trim() : '';
        var cv = cn ? String(cn.value || '').trim() : '';
        var partial = (dv && !cv) || (!dv && cv);
        if (dn) dn.classList.toggle('is-invalid', partial);
        if (cn) cn.classList.toggle('is-invalid', partial);
        if (partial) {
          showAlert('For each optional choice, pick both department and course, or leave both empty.');
          return false;
        }
      }
      if (!okD || !okC) {
        showAlert('Choose a department and a course for your first preference.');
        return false;
      }
      return true;
    }
    if (step === 7) {
      // New application: require NIC, Birth certificate, Bank receipt.
      // Updating an existing application: documents are optional (keep existing uploads).
      if (recordFromDb) return true;
      var miss = false;
      ['nic_document', 'birth_certificate', 'bank_receipt'].forEach(function (field) {
        var inp = $(field);
        if (!inp || !inp.files || inp.files.length === 0) miss = true;
      });
      if (miss) showAlert('Please choose NIC copy, Birth certificate, and Bank receipt.');
      return !miss;
    }
    return true;
  }

  function lockNic() {
    var nicEl = $('student_nic');
    nicEl.readOnly = true;
    nicEl.classList.add('bg-light');
  }

  function clearFileHints() {
    document.querySelectorAll('.existing-hint').forEach(function (h) { h.textContent = ''; });
  }

  function clearFormExceptNic() {
    if (!form) return;
    recordFromDb = false;
    hadUploadedDocs = false;
    l05ReviewOnlyMode = false;
    form.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (el.name === 'student_nic' || el.type === 'hidden') return;
      if (el.type === 'file') { el.value = ''; return; }
      el.value = '';
    });
    form.querySelectorAll('input[type="hidden"][data-fixed-subject]').forEach(function (el) {
      el.value = el.getAttribute('data-fixed-subject') || '';
    });
    $('application_id').value = '';
    clearFileHints();
    syncDistricts(false);
    l05ResetCoursePriorityFields();
  }

  function l05EnsureSelectHasValue(selId, val) {
    if (val == null) return;
    var el = $(selId);
    if (!el) return;
    if (el.tagName === 'INPUT' && el.type === 'hidden') {
      el.value = String(val).trim();
      return;
    }
    if (el.tagName !== 'SELECT') return;
    var s = String(val).trim();
    if (s === '') return;
    if (/_marks$/.test(selId)) {
      if (/^(ol|al)_subject_\d{2}_marks$/i.test(selId)) {
        if (!/^[ABCSW]$/i.test(s)) return;
      } else if (!/^[A-FSW][+-]?$/i.test(s)) {
        return;
      }
    }
    var i;
    for (i = 0; i < el.options.length; i++) {
      if (el.options[i].value === s) {
        el.selectedIndex = i;
        return;
      }
    }
    var su = s.toUpperCase();
    if (su !== s) {
      for (i = 0; i < el.options.length; i++) {
        if (el.options[i].value === su) {
          el.selectedIndex = i;
          return;
        }
      }
    }
    var opt = document.createElement('option');
    opt.value = s;
    opt.textContent = s;
    el.appendChild(opt);
    el.value = s;
  }

  function applyPrefill(data) {
    if (!data || typeof data !== 'object') return;
    Object.keys(data).forEach(function (k) {
      if (k.indexOf('_path') !== -1 || k === 'created_at') return;
      if (/^course_priority_[123]$/.test(k) || /^dept_pref_[123]$/.test(k)) return;
      var el = form.elements.namedItem(k);
      if (!el || el.type === 'file') return;
      el.value = data[k] == null ? '' : String(data[k]);
    });
    // If the draft placeholder is still stored, show it as empty so the applicant must enter their real name.
    // (The backend uses '(Pending)' for draft rows created at NIC step.)
    try {
      var fn = $('student_full_name');
      if (fn && String(fn.value || '').trim() === '(Pending)') {
        fn.value = '';
      }
    } catch (e) {}
    if (data.student_province) {
      syncDistricts(true);
      var dEl = $('student_district');
      if (dEl && data.student_district) dEl.value = data.student_district;
    }
    $('application_id').value = data.application_id ? String(data.application_id) : '';
    clearFileHints();
    hadUploadedDocs = false;
    Object.keys(PATH_KEYS).forEach(function (field) {
      var pk = PATH_KEYS[field];
      if (data[pk] && String(data[pk]).trim() !== '') {
        hadUploadedDocs = true;
      }
      var hint = document.querySelector('.existing-hint[data-path-key="' + pk + '"]');
      if (hint && data[pk]) {
        var parts = String(data[pk]).split(/[/\\\\]/);
        hint.textContent = 'Current file: ' + parts[parts.length - 1];
      }
    });
    l05RestoreCoursePreferences(data);

    for (var pi = 1; pi <= 9; pi++) {
      var ps = (pi < 10 ? '0' : '') + pi;
      if (data['ol_subject_name_' + ps] != null) {
        l05EnsureSelectHasValue('ol_subject_name_' + ps, data['ol_subject_name_' + ps]);
      }
      if (data['ol_subject_' + ps + '_marks'] != null) {
        l05EnsureSelectHasValue('ol_subject_' + ps + '_marks', data['ol_subject_' + ps + '_marks']);
      }
    }
    if (data.al_stream != null) l05EnsureSelectHasValue('al_stream', data.al_stream);
    for (var aj = 1; aj <= 3; aj++) {
      var at = '0' + aj;
      if (data['al_subject_name_' + at] != null) {
        l05EnsureSelectHasValue('al_subject_name_' + at, data['al_subject_name_' + at]);
      }
      if (data['al_subject_' + at + '_marks'] != null) {
        l05EnsureSelectHasValue('al_subject_' + at + '_marks', data['al_subject_' + at + '_marks']);
      }
    }
    if (data.nvq_level != null) l05EnsureSelectHasValue('nvq_level', data.nvq_level);
  }

  function buildReview() {
    var lines = [
      ['NIC', 'student_nic'],
      ['Full name', 'student_full_name'],
      ['Initials name', 'student_initial_name'],
      ['Email', 'student_email'],
      ['Phone', 'student_phone'],
      ['WhatsApp', 'student_whatsapp'],
      ['Address', 'student_address'],
      ['ZIP', 'student_zip_code'],
      ['Province', 'student_province'],
      ['District', 'student_district'],
      ['1st department', 'dept_pref_1'],
      ['1st course', 'course_priority_1'],
      ['2nd department', 'dept_pref_2'],
      ['2nd course', 'course_priority_2'],
      ['3rd department', 'dept_pref_3'],
      ['3rd course', 'course_priority_3']
    ];
    var html = '';
    lines.forEach(function (pair) {
      var el = $(pair[1]);
      var v = '';
      if (el) {
        if (el.tagName === 'SELECT') {
          var sv = String(el.value || '').trim();
          v = sv && el.options[el.selectedIndex] ? (el.options[el.selectedIndex].text || sv) : '';
        } else {
          v = el.value || '';
        }
      }
      if (pair[1] === 'student_nic') v = normalizeNic($('student_nic').value);
      html += '<dt class="col-sm-4">' + escapeHtml(pair[0]) + '</dt><dd class="col-sm-8">' + escapeHtml(v) + '</dd>';
    });
    html += '<dt class="col-sm-4">Documents</dt><dd class="col-sm-8">';
    Object.keys(PATH_KEYS).forEach(function (f) {
      var inp = $(f);
      var name = f.replace(/_/g, ' ');
      if (inp && inp.files && inp.files.length) {
        html += escapeHtml(name) + ': ' + escapeHtml(inp.files[0].name) + '<br>';
      } else if (recordFromDb && hadUploadedDocs) {
        var h = document.querySelector('.existing-hint[data-path-key="' + PATH_KEYS[f] + '"]');
        html += escapeHtml(name) + ': ' + escapeHtml(h && h.textContent ? h.textContent : '(unchanged)') + '<br>';
      }
    });
    html += '</dd>';
    $('reviewDl').innerHTML = html;
  }

  $('btnNext').addEventListener('click', function () {
    if (currentStep === 1) {
      if (!validateStep(1)) {
        showAlert('Enter a valid NIC, then click Next.');
        return;
      }
      if (!nicChecked) {
        var nic = normalizeNic($('student_nic').value);
        fetch(apiUrl('check_nic.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ nic: nic })
        })
          .then(function (r) {
            return r.text().then(function (t) { return { ok: r.ok, status: r.status, t: t }; });
          })
          .then(function (res) {
            var j;
            try {
              j = JSON.parse(res.t);
            } catch (e) {
              showAlert('Server did not return JSON (HTTP ' + res.status + '). Open ' + apiUrl('check_nic.php') + ' in a new tab or check the PHP error log.');
              return;
            }
            if (j.status === 'error') {
              showAlert(j.message || 'NIC check failed.');
              return;
            }
            if (j.status === 'exists' && j.data) {
              recordFromDb = true;
              applyPrefill(j.data);
              nicChecked = true;
              lockNic();
              // If documents are already uploaded, treat it as a submitted application:
              // jump straight to Review (Step 8) and hide the rest.
              if (hadUploadedDocs) {
                setFormEditable(false);
                showStep(totalSteps);
                setReviewOnlyMode(true);
                showAlert('This NIC already has a submitted Level 05 application. You can download the PDF from Review.', 'info');
              } else {
                setReviewOnlyMode(false);
                setFormEditable(true);
                showStep(2);
              }
              return;
            }
            clearFormExceptNic();
            var fdDraft = new FormData();
            fdDraft.append('student_nic', nic);
            fetch(apiUrl('insert_draft.php'), { method: 'POST', body: fdDraft })
              .then(function (r2) {
                return r2.text().then(function (t2) { return { ok: r2.ok, status: r2.status, t: t2 }; });
              })
              .then(function (res2) {
                var j2;
                try {
                  j2 = JSON.parse(res2.t);
                } catch (e2) {
                  showAlert('Could not save NIC (HTTP ' + res2.status + ').');
                  return;
                }
                if (!res2.ok || !j2.success) {
                  showAlert(j2.message || 'Could not save NIC.');
                  return;
                }
                $('application_id').value = String(j2.application_id);
                recordFromDb = false;
                hadUploadedDocs = false;
                l05ReviewOnlyMode = false;
                nicChecked = true;
                lockNic();
                showStep(2);
              })
              .catch(function () {
                showAlert('Network error. Try again.');
              });
          })
          .catch(function () {
            showAlert('Network error. Try again.');
          });
        return;
      }
      showStep(2);
      return;
    }

    if (!validateStep(currentStep)) {
      if (currentStep !== 4 && currentStep !== 5 && currentStep !== 7) {
        showAlert('Please correct the highlighted fields.');
      }
      return;
    }
    if (currentStep < totalSteps) {
      var nextStep = currentStep + 1;
      if (currentStep >= 2 && getApplicationId() >= 1) {
        saveWizardProgress(function (ok) {
          if (ok) showStep(nextStep);
        });
      } else {
        showStep(nextStep);
      }
    }
  });

  $('btnPrev').addEventListener('click', function () {
    if (currentStep <= 1) return;
    var prevStep = currentStep - 1;
    if (getApplicationId() >= 1) {
      saveWizardProgress(function (ok) {
        if (ok) showStep(prevStep);
      });
    } else {
      showStep(prevStep);
    }
  });

  $('btnSubmit').addEventListener('click', function () {
    for (var s = 2; s <= 7; s++) {
      if (!validateStep(s)) {
        showAlert('Some steps have errors. Use Previous to go back.');
        return;
      }
    }
    var appId = parseInt($('application_id').value, 10) || 0;
    if (appId < 1) {
      showAlert('Application is missing. Go back to step 1 and verify your NIC again.');
      return;
    }
    var btn = $('btnSubmit');
    btn.disabled = true;
    var fd = new FormData(form);
    augmentFormDataIdentity(fd);
    fetch(apiUrl('update.php'), { method: 'POST', body: fd })
      .then(function (r) {
        return r.text().then(function (t) {
          var j = {};
          try {
            j = t ? JSON.parse(t) : {};
          } catch (e) {
            j = { success: false, message: 'Invalid server response (HTTP ' + r.status + ').' };
          }
          return { ok: r.ok, j: j };
        });
      })
      .then(function (res) {
        btn.disabled = false;
        if (res.ok && res.j.success) {
          l05ReviewOnlyMode = true;
          $('globalAlert').classList.add('d-none');
          $('nicContextBarFixed').classList.add('d-none');
          var sb = $('successBanner');
          var sbt = $('successBannerText');
          if (sbt) sbt.textContent = res.j.message || 'Application updated successfully.';
          sb.classList.remove('d-none');
          document.querySelectorAll('.wiz-pane').forEach(function (p) { p.classList.remove('show'); });
          $('btnPrev').classList.add('d-none');
          $('btnNext').classList.add('d-none');
          $('btnSubmit').classList.add('d-none');
          window.scrollTo({ top: 0, behavior: 'smooth' });
          return;
        }
        showAlert(res.j.message || 'Save failed.');
      })
      .catch(function () {
        btn.disabled = false;
        showAlert('Network error.');
      });
  });

  syncDistricts(false);
  l05InitCourseStep();
  var btnPdfReview = $('btnDownloadApplicationPdfReview');
  if (btnPdfReview) btnPdfReview.addEventListener('click', function () { l05DownloadApplicationPdf(); });
  var btnPdfSuccess = $('btnDownloadApplicationPdfSuccess');
  if (btnPdfSuccess) btnPdfSuccess.addEventListener('click', function () { l05DownloadApplicationPdf(); });

  showStep(1);
})();
</script>
</body>
</html>
