<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require BASE_PATH . '/views/partials/seo_head.php'; ?>
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
                            $canStaffDeviceAttendanceMenu = false;
                            $canViewStudentApplications = false;
                            $canViewApplicationAdmissionSchedules = false;
                            $canViewComplaintLetters = false;
                            $canAccessExamsModule = false;
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
                                // Blank month attendance Excel (weekdays, 4 slots): ADM, HOD, IN1–IN3, Admin
                                $hasMonthAttendanceSheetAccess = in_array($userRole, ['ADM', 'HOD', 'IN1', 'IN2', 'IN3'], true) || $isAdmin;
                                // Month-range % summary: HOD, ADM, Admin
                                $hasAttendanceRangeSummaryAccess = in_array($userRole, ['HOD', 'ADM'], true) || $isAdmin;
                                // Groups access: HOD, IN1, IN2, IN3, ADM, and Admin
                                $hasGroupAccess = in_array($userRole, ['HOD', 'IN1', 'IN2', 'IN3', 'ADM']) || $isAdmin;
                                // Instructor diary menu: teaching staff, HOD, ADM, Admin only (not DIR/DPA/DPI/REG/SAO)
                                $hasInstructorDiaryAccess = !$isSAO && (in_array($userRole, ['HOD', 'IN1', 'IN2', 'IN3', 'LE1', 'LE2', 'SLE', 'ADM'], true) || $isAdmin);
                                // Staff device (Hikvision) menu: ADM/Admin/HRO full module; DIR, REG, FIN, ACC, HOD see dashboard + month only
                                $canStaffDeviceAttendanceMenu = !$isSAO && $userModel->canViewStaffDeviceDashboardMonth($_SESSION['user_id']);
                                $canViewStudentApplications = $userModel->canViewOnlineStudentApplications($_SESSION['user_id']);
                                $canViewApplicationAdmissionSchedules = $userModel->canViewApplicationAdmissionSchedules($_SESSION['user_id']);
                                $canViewComplaintLetters = $userModel->canViewComplaintLetters($_SESSION['user_id']);
                                $canAccessExamsModule = $userModel->canAccessExamsModule($_SESSION['user_id']);
                                $canViewDevices = $userModel->canViewDevices($_SESSION['user_id']);
                                $canManageDevices = $userModel->canManageDevices($_SESSION['user_id']);
                                // Bus Season processing & payments: SAO, DIR (view), ADM, Admin
                                $canProcessBusSeasonMenu = $userModel->canViewBusSeasonOperations($_SESSION['user_id']);
                                $canViewPaymentsMenu = $userModel->canViewPaymentsList($_SESSION['user_id']);
                            }
                            $isADMRole = ($userRole === 'ADM');
                            if (!isset($canProcessBusSeasonMenu)) {
                                $canProcessBusSeasonMenu = false;
                            }
                            if (!isset($canViewPaymentsMenu)) {
                                $canViewPaymentsMenu = false;
                            }
                            if (!isset($canViewDevices)) {
                                $canViewDevices = false;
                            }
                            if (!isset($canManageDevices)) {
                                $canManageDevices = false;
                            }
                            if (!isset($canViewComplaintLetters)) {
                                $canViewComplaintLetters = false;
                            }
                            $canStudentFingerprintAttendance = false;
                            $canStudentAttendanceSaoDashboard = false;
                            if (isset($_SESSION['user_id'], $userModel) && is_object($userModel)) {
                                $canStudentFingerprintAttendance = $userModel->canManageStudentFingerprintAttendance((int) $_SESSION['user_id']);
                                $canStudentAttendanceSaoDashboard = $userModel->canViewStudentAttendanceSaoDashboard((int) $_SESSION['user_id']);
                            }
                            $devicePages = ['devices'];
                            $busSeasonPages = ['bus-season-requests-sao', 'bus-season-payments'];
                            $paymentsPages = ['payments', 'payments-create', 'payments-edit', 'payments-delete'];
                            $hasStaffApprovalAccess = $isADMRole;
                            ?>
                            
                            <ul class="sidebar-menu<?php echo $isADMRole ? ' sidebar-menu-adm' : ''; ?>">
                            <li data-nav="dashboard">
                                <a href="<?php echo APP_URL; ?>/<?php echo ($isHOD) ? 'hod/dashboard' : 'dashboard'; ?>" class="<?php echo (isset($page) && $page === 'dashboard') ? 'active' : ''; ?>">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>

                            <?php if (!empty($canViewDevices)): ?>
                            <li data-nav="devices" class="menu-item-has-children <?php echo (isset($page) && $page === 'devices') ? 'active' : ''; ?>">
                                <a href="<?php echo APP_URL; ?>/devices" class="menu-toggle">
                                    <i class="fas fa-laptop"></i>
                                    <span>Asset Management</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && $page === 'devices') ? 'display: block;' : ''; ?>">
                                    <li><a href="<?php echo APP_URL; ?>/devices" class="<?php echo (isset($deviceSection) && $deviceSection === 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></li>
                                    <li><a href="<?php echo APP_URL; ?>/devices/list"><i class="fas fa-list"></i><span>All Devices</span></a></li>
                                    <?php if (!empty($canManageDevices)): ?>
                                    <li><a href="<?php echo APP_URL; ?>/devices/create"><i class="fas fa-plus"></i><span>Add Device</span></a></li>
                                    <li><a href="<?php echo APP_URL; ?>/devices/assignments"><i class="fas fa-user-check"></i><span>Assignments</span></a></li>
                                    <li><a href="<?php echo APP_URL; ?>/devices/maintenance"><i class="fas fa-tools"></i><span>Maintenance</span></a></li>
                                    <?php endif; ?>
                                    <li><a href="<?php echo APP_URL; ?>/devices/warranty"><i class="fas fa-shield-alt"></i><span>Warranty</span></a></li>
                                    <li><a href="<?php echo APP_URL; ?>/devices/scan"><i class="fas fa-qrcode"></i><span>QR Scanner</span></a></li>
                                    <?php if (!empty($canViewDevices)): ?>
                                    <li><a href="<?php echo APP_URL; ?>/devices/export"><i class="fas fa-file-export"></i><span>Reports / Export</span></a></li>
                                    <li><a href="<?php echo APP_URL; ?>/devices/audit"><i class="fas fa-history"></i><span>Audit History</span></a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>

                            <?php if (!empty($canAccessExamsModule)): ?>
                            <li data-nav="exams">
                                <a href="<?php echo APP_URL; ?>/exams" class="<?php echo (isset($page) && in_array($page, ['exams', 'exams-marks'], true)) ? 'active' : ''; ?>">
                                    <i class="fas fa-file-signature"></i>
                                    <span>Exams</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (!$isSAO): ?>
                            <!-- Deputy Principal Education Branch - Hidden for SAO -->
                            <li data-nav="management" class="menu-item-has-children <?php 
$educationPages = ['departments', 'courses', 'modules', 'staff', 'academic-years'];
                            if ($isAdminOrADM) {
                                $educationPages[] = 'staff-roles';
                            }
                            // HOD staff module enrollment page
                            if ($isHOD) {
                                $educationPages[] = 'hod-staff-module-enroll';
                            }
                            echo (isset($page) && in_array($page, $educationPages)) ? 'active' : ''; 
                            ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Management</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php 
                                    $educationPages = ['departments', 'courses', 'modules', 'staff', 'academic-years'];
                                    if ($isAdminOrADM) {
                                        $educationPages[] = 'staff-roles';
                                    }
                                    if ($isHOD) {
                                        $educationPages[] = 'hod-staff-module-enroll';
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
                                        <a href="<?php echo APP_URL; ?>/academic-years" class="<?php echo (isset($page) && $page === 'academic-years') ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Academic Years</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/courses" class="<?php echo (isset($page) && $page === 'courses') ? 'active' : ''; ?>">
                                            <i class="fas fa-book"></i>
                                            <span>Courses</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/modules" class="<?php echo (isset($page) && $page === 'modules') ? 'active' : ''; ?>">
                                            <i class="fas fa-cubes"></i>
                                            <span>Modules</span>
                                        </a>
                                    </li>
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
                                    <?php if ($isHOD): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/hod/staff-module-enroll" class="<?php echo (isset($page) && $page === 'hod-staff-module-enroll') ? 'active' : ''; ?>">
                                            <i class="fas fa-user-cog"></i>
                                            <span>Staff Module Enrollment</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <!-- Registrar (Student Affairs) Branch - Visible for all, especially SAO -->
                            <?php 
                            // Build student affairs pages array
                            $studentAffairsPages = ['students'];
                            
                            // Check if user can view hostel information (FIN, ACC, DIR, REG, HOD, IN1, IN2, IN3, SAO, ADM, Admin)
                            $canViewHostelInfo = false;
                            if (isset($_SESSION['user_id'])) {
                                $allowedHostelViewRoles = ['FIN', 'ACC', 'DIR', 'REG', 'HOD', 'IN1', 'IN2', 'IN3', 'SAO', 'ADM'];
                                $canViewHostelInfo = in_array($userRole, $allowedHostelViewRoles) || $isAdmin;
                            }
                            
                            // Check if user can view room allocations (SAO, ADM, FIN, DIR, Admin)
                            $canViewRoomAllocations = false;
                            if (isset($_SESSION['user_id'])) {
                                $canViewRoomAllocations = $userModel->canViewRoomAllocations($_SESSION['user_id']);
                            }
                            
                            $showHostelsRoomsMenu = $isAdminOrADM && !$isHOD;
                            $showRoomAllocationsMenu = !$isHOD && ($canManageRoomAllocations || $canViewRoomAllocations);
                            $hasHostelMenu = $showHostelsRoomsMenu || $showRoomAllocationsMenu;
                            $hostelPages = [];
                            if ($showHostelsRoomsMenu) {
                                $hostelPages = array_merge($hostelPages, ['hostels', 'rooms']);
                            }
                            if ($showRoomAllocationsMenu) {
                                $hostelPages[] = 'room-allocations';
                            }
                            if ($canViewHostelInfo) {
                                $studentAffairsPages[] = 'students'; // Ensure students page is in array
                            }
                            if (!empty($canViewComplaintLetters)) {
                                $studentAffairsPages[] = 'complaint-letters';
                            }
                            if ($hasGroupAccess) {
                                $studentAffairsPages[] = 'groups';
                            }
                            $studentApplicationsPages = [];
                            if (!empty($canViewStudentApplications)) {
                                $studentApplicationsPages[] = 'student-applications';
                            }
                            if (!empty($canViewApplicationAdmissionSchedules)) {
                                $studentApplicationsPages[] = 'application-admission';
                                $studentApplicationsPages[] = 'application-admission-entrance';
                                $studentApplicationsPages[] = 'application-admission-interview';
                            }
                            $showStudentApplicationsMenu = $studentApplicationsPages !== [];
                            $showStudentBusSeasonMenu = !empty($canProcessBusSeasonMenu);
                            if ($showStudentApplicationsMenu) {
                                $studentAffairsPages = array_merge($studentAffairsPages, $studentApplicationsPages);
                            }
                            if ($showStudentBusSeasonMenu) {
                                $studentAffairsPages = array_merge($studentAffairsPages, $busSeasonPages);
                            }
                            $studentInfoOpen = isset($page) && in_array($page, $studentAffairsPages, true);
                            $studentAppsOpen = isset($page) && in_array($page, $studentApplicationsPages, true);
                            $studentBusOpen = isset($page) && in_array($page, $busSeasonPages, true);
                            ?>
                            <li data-nav="student-info" class="menu-item-has-children <?php echo $studentInfoOpen ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-user-graduate"></i>
                                    <span>Student Info</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo $studentInfoOpen ? 'display: block;' : ''; ?>">
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/students" class="<?php echo (isset($page) && $page === 'students') ? 'active' : ''; ?>">
                                            <i class="fas fa-user-graduate"></i>
                                            <span>Students</span>
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
                                    <?php if (!empty($isAdminOrADM) && ($userRole === 'ADM')): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/students/id-card-select" class="<?php echo (isset($page) && $page === 'students-id-card') ? 'active' : ''; ?>">
                                            <i class="fas fa-id-card"></i>
                                            <span>ID Card Print</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (!empty($canViewComplaintLetters)): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/complaint-letters" class="<?php echo (isset($page) && $page === 'complaint-letters') ? 'active' : ''; ?>">
                                            <i class="fas fa-envelope-open-text"></i>
                                            <span>Student Letters</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($showStudentApplicationsMenu): ?>
                                    <li data-nav="student-applications" class="menu-item-has-children <?php echo $studentAppsOpen ? 'active' : ''; ?>">
                                        <a href="#" class="menu-toggle">
                                            <i class="fas fa-file-signature"></i>
                                            <span>Student Applications</span>
                                            <i class="fas fa-chevron-down menu-arrow"></i>
                                        </a>
                                        <ul class="submenu" style="<?php echo $studentAppsOpen ? 'display: block;' : ''; ?>">
                                            <?php if (!empty($canViewStudentApplications)): ?>
                                            <li>
                                                <a href="<?php echo APP_URL; ?>/student-applications" class="<?php echo (isset($page) && $page === 'student-applications') ? 'active' : ''; ?>">
                                                    <i class="fas fa-file-alt"></i>
                                                    <span>Online applications</span>
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                            <?php if (!empty($canViewApplicationAdmissionSchedules)): ?>
                                            <li>
                                                <a href="<?php echo APP_URL; ?>/application-admission" class="<?php echo (isset($page) && in_array($page, ['application-admission', 'application-admission-entrance'], true)) ? 'active' : ''; ?>">
                                                    <i class="fas fa-clipboard-list"></i>
                                                    <span>Entrance exams</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?php echo APP_URL; ?>/application-admission/interviews" class="<?php echo (isset($page) && $page === 'application-admission-interview') ? 'active' : ''; ?>">
                                                    <i class="fas fa-comments"></i>
                                                    <span>Interviews</span>
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($showStudentBusSeasonMenu): ?>
                                    <li data-nav="student-bus-season" class="menu-item-has-children <?php echo $studentBusOpen ? 'active' : ''; ?>">
                                        <a href="#" class="menu-toggle">
                                            <i class="fas fa-bus"></i>
                                            <span>Student Bus Season</span>
                                            <i class="fas fa-chevron-down menu-arrow"></i>
                                        </a>
                                        <ul class="submenu" style="<?php echo $studentBusOpen ? 'display: block;' : ''; ?>">
                                            <li>
                                                <a href="<?php echo APP_URL; ?>/bus-season-requests/sao-process" class="<?php echo (isset($page) && $page === 'bus-season-requests-sao') ? 'active' : ''; ?>">
                                                    <i class="fas fa-tasks"></i>
                                                    <span>Process Bus Season</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?php echo APP_URL; ?>/bus-season-requests/payment-collections" class="<?php echo (isset($page) && $page === 'bus-season-payments') ? 'active' : ''; ?>">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <span>Payment Collections</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <?php if (!empty($hasHostelMenu)): ?>
                            <li data-nav="hostel" class="menu-item-has-children <?php echo (isset($page) && in_array($page, $hostelPages, true)) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-hotel"></i>
                                    <span>Hostel</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, $hostelPages, true)) ? 'display: block;' : ''; ?>">
                                    <?php if ($showHostelsRoomsMenu): ?>
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
                                    <?php if ($showRoomAllocationsMenu): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/room-allocations" class="<?php echo (isset($page) && $page === 'room-allocations') ? 'active' : ''; ?>">
                                            <i class="fas fa-user-check"></i>
                                            <span>Room Allocations</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>

                            <?php if (!empty($userRole) && $userRole === 'ADM'): ?>
                            <!-- ID Card Print (ADM) - Main menu shortcut -->
                            <li data-nav="id-card-print">
                                <a href="<?php echo APP_URL; ?>/students/id-card-select" class="<?php echo (isset($page) && $page === 'students-id-card') ? 'active' : ''; ?>">
                                    <i class="fas fa-id-card"></i>
                                    <span>ID Card Print</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            $staffDevicePages = ['staff-attendance-device', 'staff-attendance-device-daily', 'staff-attendance-device-month', 'staff-attendance-device-sync'];
                            $studentAttendancePages = [];
                            if ($hasAttendanceAccess) {
                                $studentAttendancePages[] = 'attendance';
                            }
                            if ($hasAttendanceReportAccess) {
                                $studentAttendancePages[] = 'attendance-report';
                            }
                            if (!empty($hasAttendanceRangeSummaryAccess)) {
                                $studentAttendancePages[] = 'attendance-range-summary';
                            }
                            if (!empty($hasMonthAttendanceSheetAccess)) {
                                $studentAttendancePages[] = 'attendance-month-sheet';
                            }
                            $studentDevicePages = ['student-device-attendance', 'student-device-attendance-events', 'student-device-attendance-month', 'student-device-attendance-holidays', 'student-device-attendance-users', 'student-device-attendance-logs', 'student-device-attendance-sao', 'student-device-attendance-fingerprint-import'];
                            $canStudentFingerprintMenu = !empty($canStudentFingerprintAttendance) || !empty($canStudentAttendanceSaoDashboard);
                            if ($canStudentFingerprintMenu) {
                                $studentAttendancePages = array_merge($studentAttendancePages, $studentDevicePages);
                            }
                            $hasStudentAttendanceMenu = !empty($studentAttendancePages);
                            ?>
                            <?php if ($hasStudentAttendanceMenu): ?>
                            <li data-nav="student-attendance" class="menu-item-has-children <?php echo (isset($page) && in_array($page, $studentAttendancePages, true)) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Student Attendance</span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, $studentAttendancePages, true)) ? 'display: block;' : ''; ?>">
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
                                    <?php if (!empty($hasAttendanceRangeSummaryAccess)): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/attendance/range-summary" class="<?php echo (isset($page) && $page === 'attendance-range-summary') ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-week"></i>
                                            <span>Month Summary</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if (!empty($hasMonthAttendanceSheetAccess)): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/attendance/month-sheet" class="<?php echo (isset($page) && $page === 'attendance-month-sheet') ? 'active' : ''; ?>">
                                            <i class="fas fa-file-excel"></i>
                                            <span>Month Register</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($canStudentFingerprintMenu): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/attendance/student-device<?php echo empty($canStudentFingerprintAttendance) ? '/sao-dashboard' : ''; ?>" class="<?php echo (isset($page) && in_array($page, $studentDevicePages, true) && $page !== 'student-device-attendance-fingerprint-import') ? 'active' : ''; ?>">
                                            <i class="fas fa-fingerprint"></i>
                                            <span><?php echo !empty($canStudentFingerprintAttendance) ? 'Student Fingerprint' : 'SAO Attendance'; ?></span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/attendance/student-device/fingerprint-import" class="<?php echo (isset($page) && $page === 'student-device-attendance-fingerprint-import') ? 'active' : ''; ?>">
                                            <i class="fas fa-file-excel"></i>
                                            <span>Student Excel Export</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>

                            <?php if (!empty($canStaffDeviceAttendanceMenu)): ?>
                            <li data-nav="staff-attendance">
                                <a href="<?php echo APP_URL; ?>/attendance/staff-device" class="<?php echo (isset($page) && in_array($page, $staffDevicePages, true)) ? 'active' : ''; ?>">
                                    <i class="fas fa-id-card"></i>
                                    <span>Staff Attendance</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (!empty($canViewPaymentsMenu)): ?>
                            <li data-nav="payments" class="menu-item-has-children <?php echo (isset($page) && in_array($page, $paymentsPages, true)) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span><?php echo $isADMRole ? 'Payment' : 'Payments'; ?></span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, $paymentsPages, true)) ? 'display: block;' : ''; ?>">
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/payments" class="<?php echo (isset($page) && $page === 'payments') ? 'active' : ''; ?>">
                                            <i class="fas fa-list"></i>
                                            <span>All Payments</span>
                                        </a>
                                    </li>
                                    <?php if (!empty($hasFinanceAccess)): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/payments/create" class="<?php echo (isset($page) && $page === 'payments-create') ? 'active' : ''; ?>">
                                            <i class="fas fa-plus-circle"></i>
                                            <span>Add Payment</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            // Build Students Approval menu items based on user role
                            $studentsApprovalPages = [];
                            $hasStudentsApprovalAccess = false;
                            
                            if (isset($_SESSION['user_id'])) {
                                require_once BASE_PATH . '/models/UserModel.php';
                                $userModel = new UserModel();
                                $userRole = $userModel->getUserRole($_SESSION['user_id']);
                                $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                                
                                // HOD can approve first level (on-peak and bus season)
                                if ($isHOD) {
                                    $studentsApprovalPages[] = 'on-peak-requests-hod';
                                    $studentsApprovalPages[] = 'bus-season-requests-hod';
                                    $hasStudentsApprovalAccess = true;
                                }
                                
                                // DIR, DPA, DPI, REG can approve second level (on-peak)
                                if (in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG']) || $isAdmin) {
                                    $studentsApprovalPages[] = 'on-peak-requests-final';
                                    $hasStudentsApprovalAccess = true;
                                }
                                
                                // ADM, HOD, WAR can approve second level (but not if already DIR/DPA/DPI/REG)
                                if ((in_array($userRole, ['ADM', 'HOD', 'WAR']) || $isAdmin) && !in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG'])) {
                                    $studentsApprovalPages[] = 'on-peak-requests-final';
                                    $hasStudentsApprovalAccess = true;
                                }
                            }
                            ?>
                            
                            <?php if ($hasStudentsApprovalAccess): ?>
                            <!-- Students Approval - Consolidated menu for all student approvals -->
                            <li data-nav="student-approval" class="menu-item-has-children <?php echo (isset($page) && in_array($page, $studentsApprovalPages)) ? 'active' : ''; ?>">
                                <a href="#" class="menu-toggle">
                                    <i class="fas fa-user-check"></i>
                                    <span><?php echo $isADMRole ? 'Student Approval' : 'Students Approval'; ?></span>
                                    <i class="fas fa-chevron-down menu-arrow"></i>
                                </a>
                                <ul class="submenu" style="<?php echo (isset($page) && in_array($page, $studentsApprovalPages)) ? 'display: block;' : ''; ?>">
                                    <?php if ($isHOD): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/on-peak-requests/hod-approval" class="<?php echo (isset($page) && $page === 'on-peak-requests-hod') ? 'active' : ''; ?>">
                                            <i class="fas fa-calendar-check"></i>
                                            <span>On-Peak First Approval</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Show On-Peak Second Approval for DIR, DPA, DPI, REG, ADM, HOD, WAR, Admin
                                    $canApproveOnPeakSecond = false;
                                    if (isset($_SESSION['user_id'])) {
                                        require_once BASE_PATH . '/models/UserModel.php';
                                        $userModel = new UserModel();
                                        $userRole = $userModel->getUserRole($_SESSION['user_id']);
                                        $isAdmin = $userModel->isAdmin($_SESSION['user_id']);
                                        $canApproveOnPeakSecond = in_array($userRole, ['DIR', 'DPA', 'DPI', 'REG', 'ADM', 'HOD', 'WAR']) || $isAdmin;
                                    }
                                    ?>
                                    <?php if ($canApproveOnPeakSecond): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/on-peak-requests/final-approval" class="<?php echo (isset($page) && $page === 'on-peak-requests-final') ? 'active' : ''; ?>">
                                            <i class="fas fa-check-double"></i>
                                            <span>On-Peak Second Approval</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($isHOD): ?>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/bus-season-requests/hod-approval" class="<?php echo (isset($page) && $page === 'bus-season-requests-hod') ? 'active' : ''; ?>">
                                            <i class="fas fa-bus"></i>
                                            <span>Bus Season First Approval</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            // Check if user is not a student (staff only)
                            $isStudent = false;
                            if (isset($_SESSION['user_table']) && $_SESSION['user_table'] === 'student') {
                                $isStudent = true;
                            }
                            ?>
                            <?php if (!empty($hasStaffApprovalAccess)): ?>
                            <li data-nav="staff-approval">
                                <a href="<?php echo APP_URL; ?>/circuit-program/approval" class="<?php echo (isset($page) && $page === 'circuit-program-approval') ? 'active' : ''; ?>">
                                    <i class="fas fa-user-check"></i>
                                    <span>Staff Approval</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if (!$isStudent): ?>
                            <!-- Circuit Program - Staff Only (not students) -->
                            <li data-nav="circuit-program">
                                <a href="<?php echo APP_URL; ?>/circuit-program" class="<?php echo (isset($page) && in_array($page, ['circuit-program', 'circuit-program-create', 'circuit-program-view'])) ? 'active' : ''; ?>">
                                    <i class="fas fa-route"></i>
                                    <span>Circuit Program</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isset($hasInstructorDiaryAccess) && $hasInstructorDiaryAccess): ?>
                            <!-- Instructor Diary (Teaching Staff + HOD) -->
                            <li data-nav="instructor-diary">
                                <a href="<?php echo APP_URL; ?>/instructor-diary" class="<?php echo (isset($page) && $page === 'instructor-diary') ? 'active' : ''; ?>">
                                    <i class="fas fa-book-open"></i>
                                    <span>Instructor Diary</span>
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
                                        $adminPages[] = 'admin-deleted-students';
                                    }
                                    if ($isAdminUser) {
                                        $adminPages = array_merge($adminPages, ['admin-users', 'admin-locked-accounts', 'admin-activity-logs', 'admin-backup-db', 'admin-backup-files']);
                                    }
                            ?>
                            <!-- Administration Branch -->
                            <li data-nav="admin-divider" class="menu-divider"></li>
                            <li data-nav="administration" class="menu-item-has-children <?php echo (isset($page) && in_array($page, $adminPages)) ? 'active' : ''; ?>">
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
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/admin/deleted-students" class="<?php echo (isset($page) && $page === 'admin-deleted-students') ? 'active' : ''; ?>">
                                            <i class="fas fa-user-slash"></i>
                                            <span>Deleted Students</span>
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
                                        <a href="<?php echo APP_URL; ?>/admin/backup-db" class="<?php echo (isset($page) && $page === 'admin-backup-db') ? 'active' : ''; ?>" download>
                                            <i class="fas fa-database"></i>
                                            <span>SQL Backup (PC)</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo APP_URL; ?>/admin/backup-files" class="<?php echo (isset($page) && $page === 'admin-backup-files') ? 'active' : ''; ?>" download>
                                            <i class="fas fa-file-archive"></i>
                                            <span>Files ZIP (PC)</span>
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
                    const href = (this.getAttribute('href') || '').trim();
                    const isRealLink = href !== '' && href !== '#';
                    const clickedArrow = e.target.closest('.menu-arrow');
                    if (isRealLink && !clickedArrow) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    const parent = this.parentElement;
                    const submenu = parent.querySelector('.submenu');
                    
                    // Close sibling menus at the same level only (keep ancestors open for nested menus)
                    const parentList = parent.parentElement;
                    if (parentList) {
                        parentList.querySelectorAll(':scope > .menu-item-has-children').forEach(function(item) {
                            if (item !== parent) {
                                item.classList.remove('active');
                                const otherSubmenu = item.querySelector(':scope > .submenu');
                                if (otherSubmenu) {
                                    otherSubmenu.style.display = 'none';
                                }
                            }
                        });
                    }
                    
                    // Toggle current submenu
                    const directSubmenu = parent.querySelector(':scope > .submenu') || submenu;
                    if (parent.classList.contains('active')) {
                        parent.classList.remove('active');
                        if (directSubmenu) {
                            directSubmenu.style.display = 'none';
                        }
                    } else {
                        parent.classList.add('active');
                        if (directSubmenu) {
                            directSubmenu.style.display = 'block';
                        }
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


