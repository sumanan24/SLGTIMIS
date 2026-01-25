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
</head>
<body class="<?php echo isset($_SESSION['user_id']) ? 'logged-in' : ''; ?>">
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Logged In Layout with Sidebar -->
        <div class="app-wrapper">
            <!-- Top Navigation Bar -->
            <nav class="top-navbar">
                <div class="navbar-content">
                    <div class="nav-left">
                        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="nav-brand">
                            <span class="brand-text">SLGTI - MIS</span>
                        </div>
                    </div>
                    <div class="nav-right">
                        <div class="user-menu">
                            <a href="<?php echo APP_URL; ?>/profile" class="profile-btn">
                                <i class="fas fa-user"></i> <span class="profile-text">Profile</span>
                            </a>
                            <a href="<?php echo APP_URL; ?>/logout" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="app-container">
                <!-- Sidebar Overlay - Outside sidebar for proper z-index -->
                <div class="sidebar-overlay" id="sidebarOverlay"></div>
                <!-- Sidebar Menu -->
                <aside class="sidebar" id="sidebar">
                    <div class="sidebar-content">
                        <div class="sidebar-header">
                        <span class="user-name">
                                <i class="fas fa-user-circle"></i> 
                                <span class="user-name-text"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                            </span>
                            <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <nav class="sidebar-nav">
                        <ul class="sidebar-menu">
                            <?php
                            // Get user role to determine menu access
                            $userRole = null;
                            $isSAO = false;
                            $isHOD = false;
                            $hasFinanceAccess = false;
                            $isAdminOrADM = false;
                            $canManageRoomAllocations = false;
                            $hasAttendanceAccess = false;
                            $hasAttendanceReportAccess = false;
                            $hasGroupAccess = false;
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModel = new UserModel();
                                $userRole = $userModel->getUserRole($_SESSION['user_id']);
                                $isSAO = $userModel->isSAO($_SESSION['user_id']);
                                $isHOD = $userModel->isHOD($_SESSION['user_id']);
                                $hasFinanceAccess = $userModel->hasFinanceAccess($_SESSION['user_id']);
                                $isAdminOrADM = $userModel->isAdminOrADM($_SESSION['user_id']);
                                $canManageRoomAllocations = $userModel->canManageRoomAllocations($_SESSION['user_id']);
                                $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                                // Attendance access: HOD, IN1, IN2, IN3, and Admin
                                $hasAttendanceAccess = in_array($userRole, ['HOD', 'IN1', 'IN2', 'IN3']) || $isAdmin;
                                // Attendance report access: DIR, DPI, DPA, REG, FIN, ACC, SAO, HOD, IN1, IN2, IN3, ADM, and Admin
                                $hasAttendanceReportAccess = in_array($userRole, ['DIR', 'DPI', 'DPA', 'REG', 'FIN', 'ACC', 'SAO', 'HOD', 'IN1', 'IN2', 'IN3', 'ADM']) || $isAdmin;
                                // Groups access: HOD, IN1, IN2, IN3, ADM, and Admin
                                $hasGroupAccess = in_array($userRole, ['HOD', 'IN1', 'IN2', 'IN3', 'ADM']) || $isAdmin;
                                // Timetable access: HOD, ADM, and Admin (for managing timetables)
                                $hasTimetableAccess = in_array($userRole, ['HOD', 'ADM']) || $isAdmin;
                            }
                            ?>
                            
                            <li>
                                <a href="<?php echo APP_URL; ?>/<?php echo ($isHOD) ? 'hod/dashboard' : 'dashboard'; ?>" class="<?php echo (isset($page) && $page === 'dashboard') ? 'active' : ''; ?>">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            
                            <?php if (!$isSAO): ?>
                            <!-- Deputy Principal Education Branch - Hidden for SAO -->
                            <li class="menu-item-has-children <?php 
                                $educationPages = ['departments', 'courses', 'staff'];
                                if ($hasGroupAccess) {
                                    $educationPages[] = 'groups';
                                }
                                if ($hasTimetableAccess) {
                                    $educationPages[] = 'group-timetable';
                                }
                                if ($isAdminOrADM) {
                                    $educationPages[] = 'staff-roles';
                                }
                                echo (isset($page) && in_array($page, $educationPages)) ? 'active' : ''; 
                            ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Education</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php 
                                    $educationPages = ['departments', 'courses', 'staff'];
                                    if ($hasGroupAccess) {
                                        $educationPages[] = 'groups';
                                    }
                                    if ($hasTimetableAccess) {
                                        $educationPages[] = 'group-timetable';
                                    }
                                    if ($isAdminOrADM) {
                                        $educationPages[] = 'staff-roles';
                                    }
                                    echo (isset($page) && in_array($page, $educationPages)) ? 'display: block;' : ''; 
                                ?>">
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/departments" class="<?php echo (isset($page) && $page === 'departments') ? 'active' : ''; ?>">
                                            <i class="fas fa-building"></i>
                                            <span>Departments</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/courses" class="<?php echo (isset($page) && $page === 'courses') ? 'active' : ''; ?>">
                                            <i class="fas fa-book"></i>
                                            <span>Courses</span>
                                        </a>
                                    </li>
                                    <?php if ($hasGroupAccess): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/groups" class="<?php echo (isset($page) && $page === 'groups') ? 'active' : ''; ?>">
                                            <i class="fas fa-users"></i>
                                            <span>Groups</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($hasTimetableAccess): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/groups" class="<?php echo (isset($page) && in_array($page, ['group-timetable', 'groups'])) ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Timetables</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($isAdminOrADM): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/staff-roles" class="<?php echo (isset($page) && $page === 'staff-roles') ? 'active' : ''; ?>">
                                            <i class="fas fa-user-tag"></i>
                                            <span>Staff Roles</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/staff" class="<?php echo (isset($page) && $page === 'staff') ? 'active' : ''; ?>">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <span>Staff</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <!-- Registrar (Student Affairs) Branch - Visible for all, especially SAO -->
                            <?php 
                            // Build student affairs pages array
                            $studentAffairsPages = ['students'];
                            if ($isAdminOrADM && !$isHOD) {
                                $studentAffairsPages = array_merge($studentAffairsPages, ['hostels', 'rooms']);
                            }
                            if ($canManageRoomAllocations && !$isHOD) {
                                $studentAffairsPages[] = 'room-allocations';
                            }
                            ?>
                            <li class="menu-item-has-children <?php echo (isset($page) && in_array($page, $studentAffairsPages)) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>Student Affairs</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, $studentAffairsPages)) ? 'display: block;' : ''; ?>">
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/students" class="<?php echo (isset($page) && $page === 'students') ? 'active' : ''; ?>">
                                            <i class="fas fa-user-graduate"></i>
                                            <span>Students</span>
                                        </a>
                                    </li>
                                    <?php if ($isAdminOrADM && !$isHOD): ?>
                                    <li class="menu-divider-submenu"></li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/hostels" class="<?php echo (isset($page) && $page === 'hostels') ? 'active' : ''; ?>">
                                            <i class="fas fa-building"></i>
                                            <span>Hostels</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/rooms" class="<?php echo (isset($page) && $page === 'rooms') ? 'active' : ''; ?>">
                                            <i class="fas fa-door-open"></i>
                                            <span>Rooms</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($canManageRoomAllocations && !$isHOD): ?>
                                    <li class="menu-divider-submenu"></li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/room-allocations" class="<?php echo (isset($page) && $page === 'room-allocations') ? 'active' : ''; ?>">
                                            <i class="fas fa-user-check"></i>
                                            <span>Room Allocations</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php
                                    // Check if user can process bus season requests (SAO, ADM, Admin)
                                    $canProcessBusSeasonMenu = false;
                                    if (isset($_SESSION['user_id'])) {
                                        require_once BASE_PATH . '/models/UserModel.php';
                                        $userModelMenu = new UserModel();
                                        $userRoleMenu = $userModelMenu->getUserRole($_SESSION['user_id']);
                                        $isSAOMenu = $userModelMenu->isSAO($_SESSION['user_id']);
                                        $isADMMenu = ($userRoleMenu === 'ADM');
                                        $isAdminMenu = $userModelMenu->isAdmin($_SESSION['user_id']);
                                        $canProcessBusSeasonMenu = $isSAOMenu || $isADMMenu || $isAdminMenu;
                                    }
                                    ?>
                                    <?php if ($canProcessBusSeasonMenu): ?>
                                    <li class="menu-divider-submenu"></li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="<?php echo (isset($page) && $page === 'bus-season-requests-sao') ? 'active' : ''; ?>">
                                            <i class="fas fa-bus"></i>
                                            <span>Process Bus Season Tickets</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/payment-collections" class="<?php echo (isset($page) && $page === 'bus-season-payments') ? 'active' : ''; ?>">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Payment Collections</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            
                            <!-- Attendance Management - Show if user has attendance access or report access -->
                            <?php if ($hasAttendanceAccess || $hasAttendanceReportAccess): ?>
                            <li class="menu-item-has-children <?php echo (isset($page) && in_array($page, ['attendance', 'attendance-report', 'staff-attendance'])) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Attendance</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, ['attendance', 'attendance-report', 'staff-attendance'])) ? 'display: block;' : ''; ?>">
                                    <?php if ($hasAttendanceAccess): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/attendance" class="<?php echo (isset($page) && $page === 'attendance') ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Student Attendance</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($hasAttendanceReportAccess): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/attendance/report" class="<?php echo (isset($page) && $page === 'attendance-report') ? 'active' : ''; ?>">
                                            <i class="fas fa-chart-line"></i>
                                            <span>Attendance Report</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (!$isSAO && $hasAttendanceAccess): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/attendance/staff" class="<?php echo (isset($page) && $page === 'staff-attendance') ? 'active' : ''; ?>">
                                            <i class="fas fa-fingerprint"></i>
                                            <span>Staff Attendance</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($hasFinanceAccess): ?>
                            <!-- Payments - Only for FIN, ACC, ADM -->
                            <li>
                                <a href="<?php echo APP_URL; ?>/payments" class="<?php echo (isset($page) && $page === 'payments') ? 'active' : ''; ?>">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            
                            <?php if ($isHOD): ?>
                            <!-- Student Approval - Only for HOD -->
                            <li class="menu-item-has-children <?php echo (isset($page) && in_array($page, ['on-peak-requests-hod', 'bus-season-requests-hod'])) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-user-check"></i>
                                    <span>Student Approval</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, ['on-peak-requests-hod', 'bus-season-requests-hod'])) ? 'display: block;' : ''; ?>">
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/on-peak-requests/hod-approval" class="<?php echo (isset($page) && $page === 'on-peak-requests-hod') ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-check"></i>
                                            <span>On-Peak Approval</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/hod-approval" class="<?php echo (isset($page) && $page === 'bus-season-requests-hod') ? 'active' : ''; ?>">
                                            <i class="fas fa-bus"></i>
                                            <span>Bus Season Approval</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            // Check if user can approve final requests
                            $canFinalApprove = false;
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModel = new UserModel();
                                $userRole = $userModel->getUserRole($_SESSION['user_id']);
                                $canFinalApprove = in_array($userRole, ['DPR', 'RSA', 'DPA', 'DPI', 'WAR', 'ADM']) || $userModel->isAdmin($_SESSION['user_id']);
                            }
                            ?>
                            <?php
                            // Check if user is DIR, DPA, DPI, or REG for approvals submenu
                            $isDIRApprover = false;
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModel = new UserModel();
                                $userRole = $userModel->getUserRole($_SESSION['user_id']);
                                $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                                $isDIRApprover = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG']) || $isAdmin;
                            }
                            ?>
                            <?php if ($isDIRApprover): ?>
                            <!-- Approvals - For DIR, DPA, DPI, REG -->
                            <li class="menu-item-has-children <?php echo (isset($page) && in_array($page, ['on-peak-requests-final', 'bus-season-requests-second', 'circuit-program-approval'])) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Approvals</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, ['on-peak-requests-final', 'bus-season-requests-second', 'circuit-program-approval'])) ? 'display: block;' : ''; ?>">
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/on-peak-requests/final-approval" class="<?php echo (isset($page) && $page === 'on-peak-requests-final') ? 'active' : ''; ?>">
                                            <i class="fas fa-check-double"></i>
                                            <span>On-Peak Approval</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/second-approval" class="<?php echo (isset($page) && $page === 'bus-season-requests-second') ? 'active' : ''; ?>">
                                            <i class="fas fa-bus"></i>
                                            <span>Bus Season Approval</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/circuit-program/approval" class="<?php echo (isset($page) && $page === 'circuit-program-approval') ? 'active' : ''; ?>">
                                            <i class="fas fa-route"></i>
                                            <span>Circuit Program Approval</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <?php else: ?>
                            <?php
                            // Check if user can approve second approval (ADM, HOD, WAR - but not DIR/DPA/DPI/REG)
                            $canSecondApprove = false;
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModel = new UserModel();
                                $userRole = $userModel->getUserRole($_SESSION['user_id']);
                                $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                                // Only show if not DIR/DPA/DPI/REG (those are handled above)
                                $canSecondApprove = (in_array($userRole, ['ADM', 'HOD', 'WAR']) || $isAdmin) && !in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG']);
                            }
                            ?>
                            <?php if ($canSecondApprove): ?>
                            <!-- Second Request Approvals - For ADM, HOD, WAR (hostel students) -->
                            <li>
                                <a href="<?php echo APP_URL; ?>/on-peak-requests/final-approval" class="<?php echo (isset($page) && $page === 'on-peak-requests-final') ? 'active' : ''; ?>">
                                    <i class="fas fa-check-double"></i>
                                    <span>Second Request Approvals</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php
                            // Check if user is SAO, ADM, or Admin for bus season processing
                            $canProcessBusSeason = false;
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModel = new UserModel();
                                $userRole = $userModel->getUserRole($_SESSION['user_id']);
                                $isSAO = $userModel->isSAO($_SESSION['user_id']);
                                $isADM = ($userRole === 'ADM');
                                $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                                $canProcessBusSeason = $isSAO || $isADM || $isAdmin;
                            }
                            ?>
                            <?php if ($isHOD): ?>
                            <!-- Bus Season HOD Approval - For HOD -->
                            <li>
                                <a href="<?php echo APP_URL; ?>/bus-season-requests/hod-approval" class="<?php echo (isset($page) && $page === 'bus-season-requests-hod') ? 'active' : ''; ?>">
                                    <i class="fas fa-bus"></i>
                                    <span>Bus Season Requests</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($canProcessBusSeason): ?>
                            <!-- Bus Season Processing - For SAO, ADM, Admin -->
                            <li>
                                <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="<?php echo (isset($page) && $page === 'bus-season-requests-sao') ? 'active' : ''; ?>">
                                    <i class="fas fa-ticket-alt"></i>
                                    <span>Process Bus Season Tickets</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            // Check if user is not a student (staff only)
                            $isStudent = false;
                            if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
                                $isStudent = true;
                            }
                            ?>
                            <?php if (!$isStudent): ?>
                            <!-- Circuit Program - Staff Only (not students) -->
                            <li>
                                <a href="<?php echo APP_URL; ?>/circuit-program" class="<?php echo (isset($page) && in_array($page, ['circuit-program', 'circuit-program-create', 'circuit-program-view'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-route"></i>
                                    <span>Circuit Program</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            
                            <?php
                            // Check if user is admin or ADM and show security menu
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModel = new UserModel();
                                $isAdminUser = $userModel->isAdmin($_SESSION['user_id']);
                                $isADM = false;
                                $role = $userModel->getUserRole($_SESSION['user_id']);
                                if ($role === 'ADM') {
                                    $isADM = true;
                                }
                                
                                if ($isAdminUser || $isADM):
                                    // Build admin pages array
                                    $adminPages = [];
                                    if ($isADM) {
                                        $adminPages[] = 'admin-import-images';
                                    }
                                    if ($isAdminUser) {
                                        $adminPages = array_merge($adminPages, ['admin-users', 'admin-locked-accounts', 'admin-activity-logs', 'admin-backup-db']);
                                    }
                            ?>
                            <!-- Administration Branch -->
                            <li class="menu-divider"></li>
                            <li class="menu-item-has-children <?php echo (isset($page) && in_array($page, $adminPages)) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-cog"></i>
                                    <span>Administration</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, $adminPages)) ? 'display: block;' : ''; ?>">
                                    <?php if ($isADM): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/students/upload-images" class="<?php echo (isset($page) && $page === 'admin-import-images') ? 'active' : ''; ?>">
                                            <i class="fas fa-images"></i>
                                            <span>Upload Student Images</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($isAdminUser): ?>
                                    <?php if ($isADM): ?>
                                    <li class="menu-divider-submenu"></li>
                                    <?php endif; ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/admin/users" class="<?php echo (isset($page) && $page === 'admin-users') ? 'active' : ''; ?>">
                                            <i class="fas fa-list"></i>
                                            <span>List</span>
                                        </a>
                                    </li>
                                    <li class="menu-divider-submenu"></li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/admin/locked-accounts" class="<?php echo (isset($page) && $page === 'admin-locked-accounts') ? 'active' : ''; ?>">
                                            <i class="fas fa-lock"></i>
                                            <span>Locked Accounts</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/admin/activity-logs" class="<?php echo (isset($page) && $page === 'admin-activity-logs') ? 'active' : ''; ?>">
                                            <i class="fas fa-history"></i>
                                            <span>User Activities</span>
                                        </a>
                                    </li>
                                    <li class="menu-divider-submenu"></li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/admin/backup-db" class="<?php echo (isset($page) && $page === 'admin-backup-db') ? 'active' : ''; ?>">
                                            <i class="fas fa-database"></i>
                                            <span>SQL Backup</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php
                                endif;
                            }
                            ?>
                        </ul>
                        </nav>
                    </div>
                </aside>

                <!-- Main Content Area -->
                <main class="main-content-wrapper">
                    <div class="main-content">
                        <?php echo $content; ?>
                    </div>
                </main>
            </div>
        </div>
    <?php else: ?>
        <!-- Not Logged In - Simple Layout with White Theme -->
        <style>
            body:not(.logged-in) {
                background-color: #ffffff !important;
                color: #000000;
                overflow-x: hidden;
                overflow-y: hidden;
                margin: 0;
                padding: 0;
                height: 100vh;
            }
            body:not(.logged-in) .main-content {
                background-color: #ffffff !important;
                height: calc(100vh - 50px);
                padding: 0;
                margin: 0 auto;
                display: flex;
                flex-direction: column;
                position: relative;
                width: 100%;
                max-width: 100%;
                overflow: hidden;
            }
            body:not(.logged-in) .footer {
                background-color: #ffffff !important;
                color: #000000;
                border-top: 1px solid #e0e0e0;
                padding: 0.75rem 0;
                margin: 0;
                width: 100%;
                position: relative;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            body:not(.logged-in) .footer p {
                color: #666666;
                margin: 0;
                font-size: 0.875rem;
            }
        </style>
        <main class="main-content" style="background-color: #ffffff; flex: 1;">
            <?php echo $content; ?>
        </main>

        <footer class="footer" style="background-color: #ffffff; color: #000000; border-top: 1px solid #e0e0e0; padding: 0.75rem 0; margin: 0 auto; width: 100%; height: 50px; display: flex; align-items: center; justify-content: center;">
            <div class="container text-center" style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
                <p style="color: #666666; margin: 0; font-size: 0.8rem;">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </footer>
    <?php endif; ?>
    
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebarClose = document.getElementById('sidebarClose');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.add('sidebar-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.add('sidebar-open');
                    }
                    document.body.style.overflow = 'hidden';
                });
            }
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', function() {
                    sidebar.classList.remove('sidebar-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('sidebar-open');
                    }
                    document.body.style.overflow = '';
                });
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function(e) {
                    // Only close if clicking on the overlay itself, not if event bubbles from sidebar
                    if (e.target === sidebarOverlay) {
                        sidebar.classList.remove('sidebar-open');
                        sidebarOverlay.classList.remove('sidebar-open');
                        document.body.style.overflow = '';
                    }
                });
            }
            
            // Close sidebar when clicking on a menu item (mobile)
            // But NOT on menu-toggle items (they toggle submenus)
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            sidebarLinks.forEach(function(link) {
                // Only close sidebar for actual navigation links, not toggle links
                if (!link.classList.contains('menu-toggle')) {
                    link.addEventListener('click', function(e) {
                        // Allow the link to navigate normally
                        if (window.innerWidth <= 768) {
                            // Close sidebar after a small delay to allow navigation
                            setTimeout(function() {
                                sidebar.classList.remove('sidebar-open');
                                if (sidebarOverlay) {
                                    sidebarOverlay.classList.remove('sidebar-open');
                                }
                                document.body.style.overflow = '';
                            }, 100);
                        }
                    });
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('sidebar-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('sidebar-open');
                    }
                    document.body.style.overflow = '';
                }
            });
            
            // Submenu toggle functionality
            const menuToggles = document.querySelectorAll('.menu-toggle');
            menuToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const parent = this.parentElement;
                    const submenu = parent.querySelector('.submenu');
                    
                    // Close other submenus
                    document.querySelectorAll('.menu-item-has-children').forEach(function(item) {
                        if (item !== parent) {
                            item.classList.remove('active');
                            const otherSubmenu = item.querySelector('.submenu');
                            if (otherSubmenu) {
                                otherSubmenu.style.display = 'none';
                            }
                        }
                    });
                    
                    // Toggle current submenu
                    if (parent.classList.contains('active')) {
                        parent.classList.remove('active');
                        submenu.style.display = 'none';
                    } else {
                        parent.classList.add('active');
                        submenu.style.display = 'block';
                    }
                });
            });
        });
        
        // Session Timeout Warning (30 minutes = 1800 seconds)
        <?php if (isset($_SESSION['user_id'])): ?>
        (function() {
            const SESSION_TIMEOUT = 1800000; // 30 minutes in milliseconds
            const WARNING_TIME = 300000; // 5 minutes before timeout (in milliseconds)
            let warningShown = false;
            let lastActivity = <?php echo isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] * 1000 : 'Date.now()'; ?>;
            let warningTimer = null;
            let timeoutTimer = null;
            
            function updateActivity() {
                lastActivity = Date.now();
                warningShown = false;
                
                // Clear existing timers
                if (warningTimer) clearTimeout(warningTimer);
                if (timeoutTimer) clearTimeout(timeoutTimer);
                
                // Set warning timer (25 minutes from now)
                warningTimer = setTimeout(function() {
                    if (!warningShown) {
                        warningShown = true;
                        const response = confirm('Your session will expire in 5 minutes due to inactivity. Do you want to extend your session?');
                        if (response) {
                            // Extend session by making a request to update last activity
                            fetch('<?php echo APP_URL; ?>/dashboard', { method: 'HEAD', cache: 'no-cache' })
                                .then(() => {
                                    updateActivity(); // Reset timers
                                })
                                .catch(() => {
                                    updateActivity(); // Reset anyway
                                });
                        }
                    }
                }, SESSION_TIMEOUT - WARNING_TIME);
                
                // Set timeout timer (30 minutes from now)
                timeoutTimer = setTimeout(function() {
                    alert('Your session has expired due to inactivity. You will be redirected to the login page.');
                    window.location.href = '<?php echo APP_URL; ?>/login?timeout=1';
                }, SESSION_TIMEOUT);
            }
            
            // Track user activity
            ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function(event) {
                document.addEventListener(event, updateActivity, true);
            });
            
            // Initialize
            updateActivity();
            
            // Check periodically (every minute)
            setInterval(function() {
                const timeSinceLastActivity = Date.now() - lastActivity;
                if (timeSinceLastActivity >= SESSION_TIMEOUT) {
                    alert('Your session has expired due to inactivity. You will be redirected to the login page.');
                    window.location.href = '<?php echo APP_URL; ?>/login?timeout=1';
                }
            }, 60000); // Check every minute
        })();
        <?php endif; ?>
    </script>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>


