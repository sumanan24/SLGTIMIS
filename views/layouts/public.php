<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seo_robots = $seo_robots ?? 'index, follow';
    require BASE_PATH . '/views/partials/seo_head.php';
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(rtrim(APP_URL, '/') . '/assets/css/student-application.css', ENT_QUOTES, 'UTF-8'); ?>?v=14">
</head>
<body class="public-app">
    <div class="app-form-bg" aria-hidden="true"></div>
    <div class="public-app-shell">
    <main class="container-fluid px-2 px-sm-3 px-lg-4 app-form-main pt-3 pt-md-4 pb-2">
        <div class="app-form-page">
        <?php echo $content; ?>
        </div>
    </main>
    <footer class="container-fluid px-2 px-sm-3 px-lg-4 app-form-footer text-center">
        <div class="app-form-footer-inner py-3 py-md-4">
            <p class="mb-1 app-form-footer-brand">SLGTI — Sri Lanka German Training Institute</p>
            <p class="mb-0 app-form-footer-meta">SLGTI apply online 2026 · &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
