<?php
/** @var string $title */
/** @var bool $invalidRequest */
/** @var string|null $message */
/** @var array<string,mixed>|null $student */
/** @var array<string,mixed>|null $enrollment */
/** @var string|null $profileImageUrl */
$invalidRequest = $invalidRequest ?? false;
$message = $message ?? null;
$student = $student ?? null;
$enrollment = $enrollment ?? null;
$profileImageUrl = $profileImageUrl ?? null;
$pageTitle = htmlspecialchars($title ?? 'Student verification', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> — Sri Lanka German Training Institute</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        :root {
            --v-brand: #0c4a6e;
            --v-brand-mid: #075985;
            --v-brand-soft: #e0f2fe;
            --v-accent: #d97706;
            --v-text: #0f172a;
            --v-muted: #64748b;
            --v-surface: #ffffff;
            --v-border: rgba(15, 23, 42, 0.08);
            --v-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.06), 0 12px 40px -12px rgba(12, 74, 110, 0.15);
            --v-radius: 20px;
        }

        * { box-sizing: border-box; }

        body.verify-page {
            margin: 0;
            min-height: 100vh;
            font-family: "DM Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--v-text);
            background: linear-gradient(165deg, #f0f9ff 0%, #e2e8f0 45%, #f8fafc 100%);
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
        }

        /* —— Site header —— */
        .verify-site-header {
            background: linear-gradient(135deg, var(--v-brand) 0%, var(--v-brand-mid) 55%, #0a3d5c 100%);
            color: #fff;
            box-shadow: 0 4px 24px rgba(12, 74, 110, 0.35);
            position: relative;
            overflow: hidden;
        }

        .verify-site-header::before {
            content: "";
            position: absolute;
            top: -40%;
            right: -8%;
            width: min(420px, 55vw);
            height: min(420px, 55vw);
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            pointer-events: none;
        }

        .verify-site-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--v-accent), transparent);
            opacity: 0.85;
        }

        .verify-header-inner {
            position: relative;
            z-index: 1;
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.1rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .verify-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .verify-brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .verify-brand-text h1 {
            margin: 0;
            font-size: clamp(1.05rem, 2.5vw, 1.25rem);
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }

        .verify-brand-text p {
            margin: 0.15rem 0 0;
            font-size: 0.75rem;
            font-weight: 500;
            opacity: 0.88;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .verify-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .verify-header-badge i { opacity: 0.95; }

        /* —— Main —— */
        .verify-main {
            flex: 1;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            padding: 2rem 1.25rem 3rem;
        }

        .verify-page-title {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .verify-page-title h2 {
            font-size: clamp(1.15rem, 3vw, 1.4rem);
            font-weight: 700;
            color: var(--v-brand);
            margin: 0 0 0.35rem;
        }

        .verify-page-title span {
            font-size: 0.875rem;
            color: var(--v-muted);
        }

        /* Portfolio card */
        .verify-portfolio {
            background: var(--v-surface);
            border-radius: var(--v-radius);
            box-shadow: var(--v-shadow);
            border: 1px solid var(--v-border);
            overflow: hidden;
        }

        .verify-portfolio-hero {
            background: linear-gradient(145deg, var(--v-brand) 0%, #0e5a82 50%, var(--v-brand-mid) 100%);
            color: #fff;
            padding: 2rem 1.75rem 2.25rem;
            text-align: center;
            position: relative;
        }

        .verify-portfolio-hero::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 32px;
            background: linear-gradient(to bottom, transparent, var(--v-surface));
            pointer-events: none;
        }

        .verify-photo-wrap {
            position: relative;
            z-index: 1;
            margin-bottom: 1rem;
        }

        .verify-photo {
            width: 132px;
            height: 132px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .verify-placeholder {
            width: 132px;
            height: 132px;
            border-radius: 50%;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.18);
            border: 4px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.75rem;
            color: rgba(255, 255, 255, 0.85);
        }

        .verify-portfolio-hero h3 {
            position: relative;
            z-index: 1;
            margin: 0;
            font-size: clamp(1.25rem, 4vw, 1.55rem);
            font-weight: 700;
            letter-spacing: 0.01em;
            line-height: 1.25;
        }

        .verify-portfolio-hero .verify-reg-id {
            position: relative;
            z-index: 1;
            margin: 0.5rem 0 0;
            font-family: ui-monospace, "Cascadia Code", monospace;
            font-size: 0.9rem;
            opacity: 0.92;
            letter-spacing: 0.03em;
        }

        .verify-status-pill {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 1rem;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            background: rgba(34, 197, 94, 0.25);
            border: 1px solid rgba(134, 239, 172, 0.45);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .verify-portfolio-body {
            padding: 1.75rem 1.5rem 1.5rem;
            position: relative;
            z-index: 2;
        }

        .verify-portfolio-hero + .verify-portfolio-body {
            padding-top: 1.5rem;
            margin-top: -12px;
        }

        @media (min-width: 576px) {
            .verify-portfolio-body { padding: 2rem 2rem 1.75rem; }
        }

        .verify-section-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--v-muted);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--v-brand-soft);
        }

        .verify-info-grid {
            display: grid;
            gap: 0;
        }

        .verify-info-row {
            display: grid;
            grid-template-columns: minmax(100px, 34%) 1fr;
            gap: 0.75rem 1rem;
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--v-border);
            align-items: start;
        }

        .verify-info-row:last-of-type { border-bottom: none; }

        .verify-info-row dt {
            margin: 0;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--v-muted);
        }

        .verify-info-row dd {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--v-text);
        }

        .verify-alert-box {
            border-radius: 14px;
            padding: 1.25rem 1.35rem;
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .verify-alert-box i {
            margin-top: 0.15rem;
            flex-shrink: 0;
        }

        .verify-alert-warn {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .verify-alert-info {
            background: var(--v-brand-soft);
            border: 1px solid #bae6fd;
            color: #0c4a6e;
        }

        /* —— Footer —— */
        .verify-site-footer {
            margin-top: auto;
            background: var(--v-text);
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.8rem;
            text-align: center;
            padding: 1.1rem 1.25rem;
        }

        .verify-site-footer strong {
            color: #fff;
            font-weight: 600;
        }
    </style>
</head>
<body class="verify-page">
    <header class="verify-site-header" role="banner">
        <div class="verify-header-inner">
            <div class="verify-brand">
                <div class="verify-brand-mark" aria-hidden="true">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="verify-brand-text">
                    <h1>Sri Lanka German Training Institute</h1>
                    <p>Student Information System</p>
                </div>
            </div>
            <div class="verify-header-badge">
                <i class="fas fa-shield-halved"></i>
                <span>Official verification</span>
            </div>
        </div>
    </header>

    <main class="verify-main" role="main">
        <div class="verify-page-title">
            <h2>Student confirmation</h2>
            <span>Digital record matched to your ID card QR</span>
        </div>

        <div class="verify-portfolio">
            <?php if (!empty($invalidRequest)): ?>
                <div class="verify-portfolio-body">
                    <div class="verify-alert-box verify-alert-warn">
                        <i class="fas fa-triangle-exclamation fa-lg"></i>
                        <div><?php echo htmlspecialchars($message ?? 'Invalid link.', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            <?php elseif (!empty($message)): ?>
                <div class="verify-portfolio-body">
                    <div class="verify-alert-box verify-alert-info">
                        <i class="fas fa-circle-info fa-lg"></i>
                        <div><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            <?php elseif ($student): ?>
                <div class="verify-portfolio-hero">
                    <div class="verify-photo-wrap">
                        <?php if ($profileImageUrl): ?>
                            <img src="<?php echo htmlspecialchars($profileImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="verify-photo" width="132" height="132">
                        <?php else: ?>
                            <div class="verify-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars((string)($student['student_fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="verify-reg-id"><?php echo htmlspecialchars((string)($student['student_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <span class="verify-status-pill"><i class="fas fa-circle-check"></i> Registered student</span>
                </div>
                <div class="verify-portfolio-body">
                    <div class="verify-section-label">Academic profile</div>
                    <dl class="verify-info-grid">
                        <?php if (!empty($enrollment['department_name'])): ?>
                        <div class="verify-info-row">
                            <dt>Department</dt>
                            <dd><?php echo htmlspecialchars((string)$enrollment['department_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($enrollment['course_name'])): ?>
                        <div class="verify-info-row">
                            <dt>Course</dt>
                            <dd><?php echo htmlspecialchars((string)$enrollment['course_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($enrollment['academic_year'])): ?>
                        <div class="verify-info-row">
                            <dt>Academic year</dt>
                            <dd><?php echo htmlspecialchars((string)$enrollment['academic_year'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($enrollment['course_mode'])): ?>
                        <div class="verify-info-row">
                            <dt>Mode</dt>
                            <dd><?php echo htmlspecialchars((string)$enrollment['course_mode'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                        <?php endif; ?>
                        <div class="verify-info-row">
                            <dt>Status</dt>
                            <dd><?php echo htmlspecialchars((string)($student['student_status'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></dd>
                        </div>
                    </dl>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="verify-site-footer" role="contentinfo">
        <strong>SLGTI</strong> · Sri Lanka German Training Institute · &copy; <?php echo (int)date('Y'); ?>
    </footer>
</body>
</html>
