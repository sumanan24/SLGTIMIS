<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$aaNavActive = 'candidates';
$levelFilter = (string) ($levelFilter ?? '');
$rosterBase = rtrim(APP_URL, '/') . '/application-admission/candidates';
?>
<div class="container-fluid px-3 px-md-4 aa-cand-page">
    <div class="aa-cand-header">
        <div>
            <h1>Selected candidates</h1>
            <p>Attended, absent, and selected interview students, each in its own list.</p>
        </div>
    </div>
    <style>
    .aa-cand-page { padding-bottom: 1.5rem; }
    .aa-cand-header { margin-bottom: 1rem; }
    .aa-cand-header h1 { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.25rem; line-height: 1.3; }
    .aa-cand-header p { margin: 0; font-size: 0.8125rem; color: #6c757d; }
    </style>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2"><?php echo $e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2"><?php echo $e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php require BASE_PATH . '/views/application_admission/_module_nav.php'; ?>
    <?php include BASE_PATH . '/views/application_admission/_interview_roster.php'; ?>
</div>
