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
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #eef3f8;
            color: #102033;
        }
        .bare-top { background: #001f3f; box-shadow: 0 2px 10px rgba(0, 31, 63, .25); }
        .bare-top .container { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-top: .65rem; padding-bottom: .65rem; }
        .bare-brand { display: flex; align-items: center; gap: .65rem; color: #fff; font-weight: 700; letter-spacing: .02em; text-decoration: none; }
        .bare-brand img { height: 34px; width: auto; background: #fff; border-radius: 6px; padding: 2px 4px; }
        .bare-user { display: flex; align-items: center; gap: .75rem; color: #cbd5e1; font-size: .875rem; }
        .bare-user .bare-user-name { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bare-logout { color: #fff; border: 1px solid rgba(255, 255, 255, .45); border-radius: 8px; padding: .3rem .7rem; font-size: .85rem; text-decoration: none; }
        .bare-logout:hover { background: rgba(255, 255, 255, .12); color: #fff; }
        .bare-main { flex: 1 0 auto; display: flex; align-items: center; justify-content: center; }
        .bare-main > * { width: 100%; }
        .bare-footer { flex-shrink: 0; text-align: center; color: #64748b; font-size: .8rem; padding: 1rem; }
        @media (max-width: 575.98px) { .bare-user .bare-user-name { display: none; } }
    </style>
</head>
<body>
    <header class="bare-top">
        <div class="container">
            <a class="bare-brand" href="<?php echo APP_URL; ?>/">
                <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="SLGTI" onerror="this.style.display='none'">
                <span>SLGTI - MIS</span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="bare-user">
                    <span class="bare-user-name"><i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars((string) ($_SESSION['user_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="<?php echo APP_URL; ?>/logout" class="bare-logout"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="bare-main">
        <?php echo $content; ?>
    </main>
    <footer class="bare-footer">&copy; <?php echo date('Y'); ?> Sri Lanka German Training Institute &middot; SLGTI MIS</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
