<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --student-primary: #001f3f;
            --student-secondary: #003366;
            --student-dark: #000d1a;
            --student-accent: #007bff;
            --student-light: #f8f9fa;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            background-color: var(--student-light);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        /* Navigation Bar */
        .student-navbar {
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-dark) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            padding: 0.75rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .student-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.25rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .student-navbar .navbar-toggler {
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.25rem 0.5rem;
        }
        
        .student-navbar .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }
        
        .student-navbar .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .student-navbar .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            border-radius: 5px;
            margin: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .student-navbar .nav-link:hover,
        .student-navbar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white !important;
        }
        
        .student-profile-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0;
        }
        
        .student-profile-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
        }
        
        .student-main-content {
            min-height: calc(100vh - 70px);
            padding: 1.5rem 1rem;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .student-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .student-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .student-welcome-card {
            background: linear-gradient(135deg, var(--student-primary) 0%, var(--student-secondary) 100%);
            color: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .student-navbar .navbar-brand {
                font-size: 1rem;
            }
            
            .student-navbar .navbar-brand .fa-graduation-cap {
                display: none;
            }
            
            .student-profile-section {
                width: 100%;
                justify-content: center;
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .student-profile-section span {
                font-size: 0.9rem;
            }
            
            .student-profile-section .btn {
                width: auto;
                justify-content: center;
            }
            
            .student-main-content {
                padding: 1rem 0.75rem;
            }
            
            .student-welcome-card {
                padding: 1.25rem;
                border-radius: 12px;
            }
            
            .student-welcome-card h2 {
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .student-navbar {
                padding: 0.5rem 0.75rem;
            }
            
            .student-navbar .navbar-brand {
                font-size: 0.9rem;
            }
            
            .student-main-content {
                padding: 0.75rem 0.5rem;
            }
            
            .student-welcome-card {
                padding: 1rem;
            }
            
            .student-welcome-card h2 {
                font-size: 1.25rem;
            }
            
            .stat-value {
                font-size: 1.75rem !important;
            }
        }
        
        /* Container adjustments for mobile */
        @media (max-width: 1200px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Student Navigation Bar -->
        <nav class="navbar navbar-expand-lg student-navbar">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo APP_URL; ?>/student/dashboard">
                    <i class="fas fa-graduation-cap me-2"></i>SLGTI - Student Portal
                </a>
                
                <div class="student-profile-section ms-auto">
                        <?php
                        // Get student profile image
                        $profileImageUrl = null;
                        if (isset($_SESSION['user_name'])) {
                            require_once BASE_PATH . '/models/StudentModel.php';
                            $studentModelHelper = new StudentModel();
                            $student = $studentModelHelper->find($_SESSION['user_name']);
                            if ($student) {
                                $profileImageUrl = $studentModelHelper->getProfileImagePath($student);
                            }
                        }
                        ?>
                        <?php if ($profileImageUrl): ?>
                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                                 alt="Profile" 
                                 class="student-profile-avatar">
                        <?php else: ?>
                            <div class="student-profile-avatar d-flex align-items-center justify-content-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                        <?php endif; ?>
                        <span class="text-white fw-semibold d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Student'); ?></span>
                        <a href="<?php echo APP_URL; ?>/logout" class="btn btn-outline-light btn-sm ms-0 ms-md-2">
                            <i class="fas fa-sign-out-alt me-1"></i><span class="d-none d-sm-inline">Logout</span><span class="d-sm-none">Exit</span>
                        </a>
                    </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="student-main-content">
            <div class="container-fluid">
                <?php echo $content; ?>
            </div>
        </main>
    <?php else: ?>
        <!-- Not Logged In -->
        <main class="student-main-content">
            <div class="container-fluid">
                <?php echo $content; ?>
            </div>
        </main>
    <?php endif; ?>
    
    <script>
        // Session Timeout Warning
        <?php if (isset($_SESSION['user_id'])): ?>
        (function() {
            const SESSION_TIMEOUT = 1800000; // 30 minutes
            const WARNING_TIME = 300000; // 5 minutes
            let warningShown = false;
            let lastActivity = <?php echo isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] * 1000 : 'Date.now()'; ?>;
            
            function updateActivity() {
                lastActivity = Date.now();
                warningShown = false;
            }
            
            function checkTimeout() {
                const timeSinceLastActivity = Date.now() - lastActivity;
                if (timeSinceLastActivity >= SESSION_TIMEOUT - WARNING_TIME && !warningShown) {
                    warningShown = true;
                    const response = confirm('Your session will expire in 5 minutes. Do you want to extend it?');
                    if (response) {
                        fetch('<?php echo APP_URL; ?>/student/dashboard', { method: 'HEAD', cache: 'no-cache' })
                            .then(() => updateActivity())
                            .catch(() => updateActivity());
                    }
                }
                if (timeSinceLastActivity >= SESSION_TIMEOUT) {
                    alert('Your session has expired. You will be redirected to login.');
                    window.location.href = '<?php echo APP_URL; ?>/login?timeout=1';
                }
            }
            
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
                document.addEventListener(event, updateActivity, true);
            });
            
            setInterval(checkTimeout, 60000);
            updateActivity();
        })();
        <?php endif; ?>
    </script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>

