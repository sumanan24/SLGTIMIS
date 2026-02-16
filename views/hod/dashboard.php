<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* ============================================
       DASHBOARD CONTAINER - Proper Sidebar Alignment
       ============================================ */
    .dashboard-container {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: calc(100vh - 60px);
        padding: 2rem 1.5rem;
        max-width: 100%;
        overflow-x: hidden;
        width: 100%;
        box-sizing: border-box;
        margin: 0;
    }
    
    /* ============================================
       ROW AND COLUMN ALIGNMENT
       ============================================ */
    .dashboard-container .row {
        display: flex;
        flex-wrap: wrap;
        margin-left: 0;
        margin-right: 0;
        width: 100%;
    }
    
    .dashboard-container .row > [class*="col-"] {
        display: flex;
        flex-direction: column;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .row.g-4 {
        margin-left: -0.75rem;
        margin-right: -0.75rem;
        display: flex;
        flex-wrap: wrap;
    }
    
    .row.g-4 > [class*="col-"] {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .row.g-4 > [class*="col-md-4"],
    .row.g-4 > [class*="col-md-6"],
    .row.g-4 > [class*="col-12"] {
        display: flex;
        flex-direction: column;
    }
    
    .row.g-4 > [class*="col-md-4"] > .card,
    .row.g-4 > [class*="col-md-6"] > .card,
    .row.g-4 > [class*="col-12"] > .card {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
    }
    
    /* ============================================
       WELCOME CARD
       ============================================ */
    .dashboard-welcome-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
        margin-bottom: 2rem;
        width: 100%;
    }
    
    .dashboard-welcome-card .card-body {
        color: #1e293b;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        width: 100%;
        box-sizing: border-box;
    }
    
    .dashboard-welcome-card h1 {
        color: #0f172a;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }
    
    .dashboard-welcome-card p {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
        line-height: 1.5;
    }
    
    .dashboard-welcome-card select {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        border-radius: 8px;
        transition: all 0.2s ease;
        padding: 0.375rem 0.75rem;
    }
    
    .dashboard-welcome-card select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }
    
    .dashboard-welcome-card select option {
        background-color: #ffffff;
        color: #1e293b;
    }
    
    .dashboard-welcome-card label {
        color: #475569;
        font-weight: 500;
        margin-bottom: 0;
    }
    
    .dashboard-welcome-card small {
        color: #64748b;
        display: block;
    }
    
    .dashboard-welcome-card .border-top {
        border-color: #e2e8f0 !important;
        margin-top: 1rem;
        padding-top: 1rem;
    }
    
    .department-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
        color: white;
        border-radius: 50px;
        font-weight: 600;
        margin-top: 0.5rem;
        box-shadow: 0 2px 8px rgba(0, 31, 63, 0.25);
        font-size: 0.9rem;
    }
    
    .department-info-box {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-left: 4px solid #001f3f;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    
    /* ============================================
       STATS CARDS
       ============================================ */
    .dashboard-stats-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #6366f1;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .dashboard-stats-card .card-body {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        min-height: 180px;
        padding: 1.5rem;
    }
    
    .dashboard-stats-card .card-body > .d-flex {
        height: 100%;
        width: 100%;
    }
    
    .dashboard-stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        border-left-color: #4f46e5;
    }
    
    .dashboard-stats-card[style*="border-left-color: #001f3f"] {
        border-left-color: #001f3f !important;
    }
    
    .dashboard-stats-card[style*="border-left-color: #001f3f"]:hover {
        border-left-color: #001a33 !important;
    }
    
    .dashboard-stats-card[style*="border-left-color: #1e3a5f"] {
        border-left-color: #1e3a5f !important;
    }
    
    .dashboard-stats-card[style*="border-left-color: #1e3a5f"]:hover {
        border-left-color: #152a4a !important;
    }
    
    .stats-card-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 0.75rem;
        line-height: 1.2;
    }
    
    .stats-card-value {
        color: #0f172a;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #0f172a 0%, #475569 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }
    
    .stats-card-value[style*="color: #003366"] {
        color: #003366 !important;
        -webkit-text-fill-color: #003366 !important;
        background: none !important;
    }
    
    .stats-card-value[style*="color: #1e3a5f"] {
        color: #1e3a5f !important;
        -webkit-text-fill-color: #1e3a5f !important;
        background: none !important;
    }
    
    .stats-card-subtitle {
        color: #64748b;
        font-size: 0.85rem;
        line-height: 1.4;
        margin-top: 0.5rem;
    }
    
    .stats-card-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        flex-shrink: 0;
        margin-left: 1rem;
    }
    
    /* ============================================
       CHART CARDS
       ============================================ */
    .dashboard-chart-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .dashboard-chart-card:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        border-color: #cbd5e1;
    }
    
    .chart-card-header {
        background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
        color: #ffffff;
        padding: 1rem 1.25rem;
        font-weight: 600;
        border-radius: 14px 14px 0 0;
        border: none;
        box-shadow: 0 2px 8px rgba(0, 31, 63, 0.2);
        width: 100%;
        box-sizing: border-box;
    }
    
    .chart-card-body {
        padding: 1.5rem;
        color: #2c3e50;
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 280px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .chart-card-body canvas {
        max-width: 100%;
        height: auto !important;
    }
    
    /* ============================================
       COURSE ENROLLMENT CARDS
       ============================================ */
    .course-enrollment-item {
        border-left: 4px solid #003366;
        background: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 8px;
        margin-bottom: 0.75rem;
        transition: all 0.2s ease;
    }
    
    .course-enrollment-item:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transform: translateX(2px);
    }
    
    .course-enrollment-item .card-body {
        padding: 1rem;
    }
    
    /* ============================================
       TABLE STYLES
       ============================================ */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 31, 63, 0.05);
    }
    
    /* ============================================
       MOBILE RESPONSIVE STYLES
       ============================================ */
    @media (max-width: 992px) {
        .dashboard-container {
            padding: 1.5rem 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .dashboard-welcome-card .card-body {
            padding: 1.5rem 1rem;
        }
        
        .dashboard-welcome-card h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .dashboard-welcome-card .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .dashboard-welcome-card .d-flex > div {
            width: 100%;
        }
        
        .dashboard-welcome-card .d-flex > div:first-child {
            margin-bottom: 1rem;
        }
        
        .dashboard-welcome-card select {
            width: 100%;
            min-width: 100%;
        }
        
        .stats-card-value {
            font-size: 2rem;
        }
        
        .stats-card-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            margin-left: 0 !important;
            margin-top: 0.5rem;
        }
        
        .dashboard-stats-card .card-body {
            padding: 1.5rem;
            min-height: auto;
        }
        
        .dashboard-stats-card .card-body > .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .chart-card-body {
            padding: 1rem;
            min-height: 250px;
        }
        
        .dashboard-chart-card {
            margin-bottom: 1.5rem;
        }
        
        .row.g-4 {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        
        .row.g-4 > * {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .row.g-4 > .col-md-4 {
            margin-bottom: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .dashboard-container {
            padding: 0.75rem;
        }
        
        .dashboard-welcome-card .card-body {
            padding: 1rem;
        }
        
        .dashboard-welcome-card h1 {
            font-size: 1.25rem;
        }
        
        .stats-card-value {
            font-size: 1.75rem;
        }
        
        .stats-card-icon {
            width: 45px;
            height: 45px;
            font-size: 1.25rem;
            margin-left: 0 !important;
        }
        
        .dashboard-stats-card .card-body {
            padding: 1rem;
            min-height: auto;
        }
        
        .dashboard-stats-card .card-body > .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .chart-card-body {
            padding: 0.75rem;
            min-height: 200px;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Card with Department Info and Academic Year Filter -->
    <div class="card dashboard-welcome-card">
        <div class="card-body">
            <div class="d-flex align-items-start align-items-md-center justify-content-between flex-wrap gap-3">
                <div class="flex-grow-1">
                    <h1 class="mb-2">Welcome, <?php echo htmlspecialchars($user_name ?? 'User'); ?>!</h1>
                    <p class="mb-2">Department Dashboard - Viewing your department's statistics</p>
                    <?php if (!empty($department)): ?>
                    <div class="mt-2">
                        <span class="department-badge">
                            <i class="fas fa-building me-2"></i>
                            <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?>
                        </span>
                    </div>
                    <?php if (!empty($department['department_id'])): ?>
                    <div class="department-info-box">
                        <small class="text-muted d-block mb-1">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Department ID:</strong> <?php echo htmlspecialchars($department['department_id']); ?>
                        </small>
                        <?php if (!empty($department['department_description'])): ?>
                        <small class="text-muted d-block">
                            <?php echo htmlspecialchars($department['department_description']); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="mt-2">
                        <span class="badge bg-secondary">
                            <i class="fas fa-info-circle me-2"></i>
                            Administrative View - All Departments
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <label for="academicYearFilter" class="mb-0 fw-semibold" style="font-size: 0.9rem; white-space: nowrap;">Academic Year:</label>
                        <select id="academicYearFilter" class="form-select form-select-sm" style="min-width: 180px;">
                            <option value="">All Years</option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selectedAcademicYear === $year) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-none d-md-block flex-shrink-0">
                        <i class="fas fa-chart-line fa-3x opacity-30"></i>
                    </div>
                </div>
            </div>
            <?php if (!empty($selectedAcademicYear)): ?>
                <div class="mt-3 pt-3 border-top">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        Showing statistics for Academic Year: <strong><?php echo htmlspecialchars($selectedAcademicYear); ?></strong>
                        <?php if (!empty($totalStudentsByYear)): ?>
                            (<?php echo number_format($totalStudentsByYear); ?> students in your department)
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Stats Cards - Department Only (if user has department) -->
    <?php if (!empty($department)): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card dashboard-stats-card" style="border-left-color: #001f3f;">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between flex-grow-1">
                        <div class="flex-grow-1">
                            <div class="stats-card-label">Total Students</div>
                            <div class="stats-card-value"><?php echo number_format($totalStudents); ?></div>
                            <div class="stats-card-subtitle">Active Students in <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?></div>
                        </div>
                        <div class="stats-card-icon flex-shrink-0 ms-3">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card dashboard-stats-card" style="border-left-color: #1e3a5f;">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between flex-grow-1">
                        <div class="flex-grow-1">
                            <div class="stats-card-label">Total Staff</div>
                            <div class="stats-card-value" style="color: #003366;"><?php echo number_format($totalStaff ?? 0); ?></div>
                            <div class="stats-card-subtitle">Staff Members in <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?></div>
                        </div>
                        <div class="stats-card-icon flex-shrink-0 ms-3" style="background: rgba(0, 51, 102, 0.1); color: #003366;">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card dashboard-stats-card" style="border-left-color: #1e3a5f;">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between flex-grow-1">
                        <div class="flex-grow-1">
                            <div class="stats-card-label">Total Courses</div>
                            <div class="stats-card-value" style="color: #1e3a5f;"><?php echo number_format($totalCourses ?? 0); ?></div>
                            <div class="stats-card-subtitle">Courses in <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?></div>
                        </div>
                        <div class="stats-card-icon flex-shrink-0 ms-3" style="background: rgba(30, 58, 95, 0.1); color: #1e3a5f;">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Course Enrollment - Department Only -->
    <?php if (!empty($department_id) && !empty($courseEnrollmentByDepartment) && isset($courseEnrollmentByDepartment[$department_id])): ?>
    <?php $dept = $courseEnrollmentByDepartment[$department_id]; ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-book-reader me-2"></i>Course Enrollment - <?php echo htmlspecialchars($dept['department_name']); ?>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-muted mb-0">
                            <i class="fas fa-users me-2"></i>
                            Total Enrollment: <strong class="text-primary"><?php echo number_format($dept['total_enrollment'] ?? 0); ?></strong> Students
                        </h5>
                    </div>
                    <?php if (!empty($dept['courses'])): ?>
                    <div class="row g-3 mt-2">
                        <?php foreach ($dept['courses'] as $course): ?>
                        <div class="col-md-12">
                            <div class="card course-enrollment-item">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1" style="color: #001f3f; font-weight: 600;">
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                                <?php if (!empty($course['course_nvq_level'])): ?>
                                                    <span class="badge ms-2" style="background: #003366; color: white;">
                                                        NVQ Level <?php echo htmlspecialchars($course['course_nvq_level']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">Course ID: <?php echo htmlspecialchars($course['course_id']); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="h4 mb-0" style="color: #003366; font-weight: 700;">
                                                <?php echo number_format($course['enrollment_count'] ?? 0); ?>
                                            </div>
                                            <small class="text-muted">Students</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No courses found in this department.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- NVQ Level Statistics - Department Only -->
    <?php if (!empty($department_id) && !empty($nvqStatsByDepartment) && isset($nvqStatsByDepartment[$department_id])): ?>
    <?php $dept = $nvqStatsByDepartment[$department_id]; ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-graduation-cap me-2"></i>NVQ Level Statistics - <?php echo htmlspecialchars($dept['department_name']); ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>NVQ Level</th>
                                    <th class="text-center">Student Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $dept04 = $dept['levels']['04'] ?? 0;
                                $dept05 = $dept['levels']['05'] ?? 0;
                                $dept06 = $dept['levels']['06'] ?? 0;
                                $deptTotal = $dept04 + $dept05 + $dept06;
                                ?>
                                <tr>
                                    <td><strong>NVQ Level 04</strong></td>
                                    <td class="text-center">
                                        <span class="badge" style="background: #0066cc; color: white; font-size: 0.9rem; padding: 0.5rem 0.75rem;">
                                            <?php echo number_format($dept04); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>NVQ Level 05</strong></td>
                                    <td class="text-center">
                                        <span class="badge" style="background: #003366; color: white; font-size: 0.9rem; padding: 0.5rem 0.75rem;">
                                            <?php echo number_format($dept05); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>NVQ Level 06</strong></td>
                                    <td class="text-center">
                                        <span class="badge" style="background: #004c99; color: white; font-size: 0.9rem; padding: 0.5rem 0.75rem;">
                                            <?php echo number_format($dept06); ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-center">
                                        <strong><?php echo number_format($deptTotal); ?></strong>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Gender Statistics - Department Only -->
    <?php if (!empty($department_id) && !empty($genderStats)): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-venus-mars me-2"></i>Gender Distribution - <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?>
                </div>
                <div class="chart-card-body">
                    <canvas id="genderChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Students - Department Only -->
    <?php if (!empty($department_id)): ?>
        <?php if (!empty($recentStudents)): ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card dashboard-chart-card">
                    <div class="chart-card-header">
                        <i class="fas fa-users me-2"></i>Recent Students in <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentStudents as $student): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo APP_URL; ?>/students/view?id=<?php echo urlencode($student['student_id']); ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($student['student_id']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['student_fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($student['student_email']); ?></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars($student['student_status'] ?? 'Active'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card dashboard-chart-card">
                    <div class="chart-card-header">
                        <i class="fas fa-users me-2"></i>Recent Students in <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-4">
                            <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent students found in this department.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Hostel Information - Visible to HOD, IN1, IN2, IN3, FIN, ACC, DIR, REG -->
    <?php if (!empty($hostelStats)): ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-bed me-2"></i>Hostel Information
                </div>
                <div class="card-body">
                    <!-- Hostel Summary Statistics -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #0ea5e9;">
                                <div class="card-body text-center p-3">
                                    <div class="h4 mb-1" style="color: #0ea5e9; font-weight: 700;"><?php echo number_format($hostelStats['total_hostels'] ?? 0); ?></div>
                                    <div class="small text-muted">Total Hostels</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-left: 4px solid #22c55e;">
                                <div class="card-body text-center p-3">
                                    <div class="h4 mb-1" style="color: #22c55e; font-weight: 700;"><?php echo number_format($hostelStats['total_rooms'] ?? 0); ?></div>
                                    <div class="small text-muted">Total Rooms</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b;">
                                <div class="card-body text-center p-3">
                                    <div class="h4 mb-1" style="color: #f59e0b; font-weight: 700;"><?php echo number_format($hostelStats['total_capacity'] ?? 0); ?></div>
                                    <div class="small text-muted">Total Capacity</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card" style="background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-left: 4px solid #ec4899;">
                                <div class="card-body text-center p-3">
                                    <div class="h4 mb-1" style="color: #ec4899; font-weight: 700;"><?php echo number_format($hostelStats['total_occupied'] ?? 0); ?></div>
                                    <div class="small text-muted">Occupied Beds</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($department_id)): ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Department Students in Hostels:</strong> <?php echo number_format($hostelStats['department_students'] ?? 0); ?> students from <?php echo htmlspecialchars($department['department_name'] ?? 'your department'); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Hostel Details Table -->
                    <?php if (!empty($hostelStats['hostels'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Hostel Name</th>
                                    <th>Location</th>
                                    <th>Gender</th>
                                    <th class="text-center">Rooms</th>
                                    <th class="text-center">Capacity</th>
                                    <th class="text-center">Occupied</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-center">Occupancy Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hostelStats['hostels'] as $hostel): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($hostel['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($hostel['location']); ?></td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $hostel['gender'] === 'Male' ? '#3b82f6' : ($hostel['gender'] === 'Female' ? '#ec4899' : '#6b7280'); ?>;">
                                            <?php echo htmlspecialchars($hostel['gender']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?php echo number_format($hostel['room_count']); ?></td>
                                    <td class="text-center"><?php echo number_format($hostel['capacity']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?php echo number_format($hostel['occupied']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo number_format($hostel['available']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $hostel['occupancy_rate'] >= 90 ? 'bg-danger' : ($hostel['occupancy_rate'] >= 70 ? 'bg-warning' : 'bg-success'); ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $hostel['occupancy_rate']; ?>%"
                                                 aria-valuenow="<?php echo $hostel['occupancy_rate']; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo $hostel['occupancy_rate']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3">Total</th>
                                    <th class="text-center"><?php echo number_format($hostelStats['total_rooms']); ?></th>
                                    <th class="text-center"><?php echo number_format($hostelStats['total_capacity']); ?></th>
                                    <th class="text-center"><?php echo number_format($hostelStats['total_occupied']); ?></th>
                                    <th class="text-center"><?php echo number_format($hostelStats['total_available']); ?></th>
                                    <th class="text-center">
                                        <?php 
                                        $overallOccupancy = $hostelStats['total_capacity'] > 0 ? round(($hostelStats['total_occupied'] / $hostelStats['total_capacity']) * 100, 1) : 0;
                                        echo $overallOccupancy . '%';
                                        ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hostel information available.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Academic Year Filter Handler
document.getElementById('academicYearFilter').addEventListener('change', function() {
    const selectedYear = this.value;
    const currentUrl = new URL(window.location.href);
    
    if (selectedYear) {
        currentUrl.searchParams.set('academic_year', selectedYear);
    } else {
        currentUrl.searchParams.delete('academic_year');
    }
    
    window.location.href = currentUrl.toString();
});

<?php if (!empty($genderStats)): ?>
// Gender Distribution Chart - Department Only
const genderCtx = document.getElementById('genderChart').getContext('2d');
const genderLabels = [<?php echo !empty($genderStats) ? implode(',', array_map(function($k) { return "'" . htmlspecialchars($k, ENT_QUOTES) . "'"; }, array_keys($genderStats))) : ''; ?>];
const genderData = [<?php echo !empty($genderStats) ? implode(',', array_values($genderStats)) : ''; ?>];

new Chart(genderCtx, {
    type: 'doughnut',
    data: {
        labels: genderLabels.length > 0 ? genderLabels : ['No Data'],
        datasets: [{
            label: 'Students',
            data: genderData.length > 0 ? genderData : [0],
            backgroundColor: [
                'rgba(0, 31, 63, 0.8)',
                'rgba(0, 51, 102, 0.8)',
                'rgba(30, 58, 95, 0.8)'
            ],
            borderColor: [
                '#001f3f',
                '#003366',
                '#1e3a5f'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed + ' students';
                        return label;
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>
