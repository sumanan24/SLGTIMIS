<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) ($title ?? 'SLGTI MIS'), ENT_QUOTES, 'UTF-8'); ?> - SLGTI MIS</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f1f5f9; min-height: 100vh; }
        .bare-top { background: #fff; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <header class="bare-top">
        <div class="container d-flex align-items-center justify-content-between py-2">
            <strong>SLGTI - MIS</strong>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo APP_URL; ?>/logout" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            <?php endif; ?>
        </div>
    </header>
    <main>
        <?php echo $content; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
