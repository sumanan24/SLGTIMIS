<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* Dashboard Styles - Professional Modern Theme */
    .dashboard-container {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: calc(100vh - 60px);
        padding: 2rem 1.5rem;
    }
    
    .dashboard-welcome-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
        margin-bottom: 2rem;
    }
    
    .dashboard-welcome-card .card-body {
        color: #1e293b;
        padding: 2rem;
    }
    
    .dashboard-welcome-card h1 {
        color: #0f172a;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .dashboard-welcome-card p {
        color: #64748b;
        font-size: 0.95rem;
    }
    
    .dashboard-welcome-card select {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .dashboard-welcome-card select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .dashboard-welcome-card select option {
        background-color: #ffffff;
        color: #1e293b;
    }
    
    .dashboard-welcome-card label {
        color: #475569;
        font-weight: 500;
    }
    
    .dashboard-welcome-card small {
        color: #64748b;
    }
    
    .dashboard-welcome-card .border-top {
        border-color: #e2e8f0 !important;
    }
    
    .dashboard-stats-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #6366f1;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .dashboard-stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        border-left-color: #4f46e5;
    }
    
    .dashboard-stats-card:nth-child(2) {
        border-left-color: #10b981;
    }
    
    .dashboard-stats-card:nth-child(2):hover {
        border-left-color: #059669;
    }
    
    .dashboard-stats-card:nth-child(3) {
        border-left-color: #f59e0b;
    }
    
    .dashboard-stats-card:nth-child(3):hover {
        border-left-color: #d97706;
    }
    
    .stats-card-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 1rem;
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
    }
    
    .stats-card-subtitle {
        color: #64748b;
        font-size: 0.85rem;
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
    }
    
    .dashboard-stats-card:nth-child(2) .stats-card-icon {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .dashboard-stats-card:nth-child(3) .stats-card-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }
    
    .dashboard-chart-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
        transition: all 0.3s ease;
        height: 100%;
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
    }
    
    .chart-card-body {
        padding: 1.5rem;
        color: #2c3e50;
    }
    
    .chart-stat-item {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        border-left: 3px solid #6366f1;
        transition: all 0.2s ease;
    }
    
    .chart-stat-item:hover {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-left-color: #4f46e5;
    }
    
    .chart-stat-label {
        color: #1e293b;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .chart-stat-badge {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #ffffff;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(99, 102, 241, 0.3);
    }
    
    /* Stat List Styles for Direct Value Display */
    .stat-list-container {
        padding: 0.5rem 0;
    }
    
    .stat-list-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .stat-list-item:hover {
        padding-left: 0.5rem;
    }
    
    .stat-list-item:last-child {
        border-bottom: none;
    }
    
    .stat-list-label {
        color: #475569;
        font-weight: 500;
        font-size: 0.95rem;
    }
    
    .stat-list-badge {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        min-width: 100px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
    }
    
    .department-grid-item {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .department-grid-item:hover {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-color: #6366f1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    }
    
    .department-grid-item strong {
        color: #1e293b;
        font-size: 0.9rem;
        display: block;
        margin-bottom: 0.5rem;
    }
    
    /* Course Enrollment Card Styles */
    .course-enrollment-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }
    
    .course-enrollment-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
        border-color: #cbd5e1;
    }
    
    .course-enrollment-header {
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .course-name {
        font-size: 0.95rem;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }
    
    .course-badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .badge-nvq {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #ffffff;
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
    }
    
    .badge-mode {
        color: #ffffff;
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .course-enrollment-body {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .dept-name {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
    }
    
    .enrollment-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }
    
    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        flex: 1;
    }
    
    .stat-item i {
        font-size: 1rem;
    }
    
    .stat-item .stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
    }
    
    .stat-item.stat-total {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #ffffff;
        padding: 0.5rem;
        border-radius: 10px;
        min-width: 60px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .stat-item.stat-total i {
        color: #ffffff;
        font-size: 0.9rem;
    }
    
    .stat-item.stat-total .stat-value {
        color: #ffffff;
        font-size: 1.2rem;
    }
    
    /* Department Section Styles */
    .department-section {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    
    .department-header h5 {
        color: #0f172a;
        font-weight: 700;
        font-size: 1.25rem;
    }
    
    .department-header hr {
        border-top: 2px solid #6366f1;
        margin: 0.75rem 0;
        opacity: 1;
        box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
    }
    
    /* Mobile Responsive Styles */
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
        }
        
        .dashboard-stats-card .card-body {
            padding: 1.5rem;
        }
        
        .chart-card-body {
            padding: 1rem;
        }
        
        .dashboard-chart-card {
            margin-bottom: 1.5rem;
        }
        
        .row.g-4 {
            margin-left: -0.75rem;
            margin-right: -0.75rem;
        }
        
        .row.g-4 > * {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        .course-enrollment-card {
            margin-bottom: 1rem;
        }
        
        .enrollment-stats {
            flex-wrap: wrap;
        }
        
        .stat-item {
            min-width: 70px;
        }
        
        .department-section {
            padding: 1rem;
        }
        
        .department-header h5 {
            font-size: 1.1rem;
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
        }
        
        .dashboard-stats-card .card-body {
            padding: 1rem;
        }
        
        .chart-card-body {
            padding: 0.75rem;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Card with Academic Year Filter -->
    <div class="card dashboard-welcome-card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div class="flex-grow-1">
                    <h1>Welcome, <?php echo htmlspecialchars($user_name ?? 'User'); ?>!</h1>
                    <p class="mb-0">SLGTI Management Information System</p>
                </div>
                <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
                    <div class="d-flex align-items-center gap-2">
                        <label for="academicYearFilter" class="mb-0 fw-semibold" style="font-size: 0.9rem;">Academic Year:</label>
                        <select id="academicYearFilter" class="form-select form-select-sm" style="min-width: 180px;">
                            <option value="">All Years</option>
                            <?php foreach ($academicYears as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selectedAcademicYear === $year) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-none d-md-block">
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
                            (<?php echo number_format($totalStudentsByYear); ?> students)
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card dashboard-stats-card" style="border-left-color: #001f3f;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="stats-card-label">Total Students</div>
                            <div class="stats-card-value"><?php echo number_format($totalStudents); ?></div>
                            <div class="stats-card-subtitle">
                                <?php 
                                $maleCount = $genderStats['Male'] ?? 0;
                                $femaleCount = $genderStats['Female'] ?? 0;
                                ?>
                                <span class="me-2">
                                    <i class="fas fa-mars text-primary"></i> Male: <strong><?php echo number_format($maleCount); ?></strong>
                                </span>
                                <span>
                                    <i class="fas fa-venus text-danger"></i> Female: <strong><?php echo number_format($femaleCount); ?></strong>
                                </span>
                            </div>
                            <div class="mt-2" style="font-size: 0.75rem; color: #6c757d;">
                                Active & Following Only
                            </div>
                        </div>
                        <div class="stats-card-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card dashboard-stats-card" style="border-left-color: #1e3a5f;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="stats-card-label">Total Courses</div>
                            <div class="stats-card-value" style="color: #1e3a5f;"><?php echo number_format($totalCourses); ?></div>
                            <div class="stats-card-subtitle">Available Courses</div>
                        </div>
                        <div class="stats-card-icon" style="background: rgba(30, 58, 95, 0.1); color: #1e3a5f;">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Course Enrollment by Department -->
    <?php if (!empty($courseEnrollmentByDepartment)): ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-book-reader me-2"></i>Course Enrollment by Department
                </div>
                <div class="card-body p-4">
                    <?php foreach ($courseEnrollmentByDepartment as $dept): ?>
                        <?php if (!empty($dept['nvq_levels'])): ?>
                            <!-- Department Section -->
                            <div class="department-section mb-4">
                                <div class="department-header mb-3">
                                    <h5 class="mb-0">
                                        <i class="fas fa-building me-2" style="color: #001f3f;"></i>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </h5>
                                    <hr style="border-top: 2px solid #dc3545; margin: 0.5rem 0;">
                                </div>
                                
                                <!-- Courses Grid -->
                                <div class="row g-3">
                                    <?php foreach ($dept['nvq_levels'] as $nvqLevel => $courses): ?>
                                        <?php foreach ($courses as $course): 
                                            $femaleCount = $course['female_count'] ?? 0;
                                            $maleCount = $course['male_count'] ?? 0;
                                            $totalCount = $course['total_count'] ?? 0;
                                            $courseMode = $course['course_mode'] ?? 'Full';
                                            $modeDisplay = ($courseMode === 'Full') ? 'FT' : (($courseMode === 'Part') ? 'PT' : $courseMode);
                                            $modeColor = ($courseMode === 'Full') ? '#28a745' : '#ffc107';
                                        ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="course-enrollment-card">
                                                    <div class="course-enrollment-header">
                                                        <div class="course-name"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                                        <div class="course-badges">
                                                            <span class="badge badge-nvq">NVQ <?php echo htmlspecialchars($nvqLevel); ?></span>
                                                            <span class="badge badge-mode" style="background-color: <?php echo $modeColor; ?>;"><?php echo $modeDisplay; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="course-enrollment-body">
                                                        <div class="enrollment-stats">
                                                            <div class="stat-item">
                                                                <i class="fas fa-venus text-danger"></i>
                                                                <span class="stat-value"><?php echo number_format($femaleCount); ?></span>
                                                            </div>
                                                            <div class="stat-item">
                                                                <i class="fas fa-mars text-primary"></i>
                                                                <span class="stat-value"><?php echo number_format($maleCount); ?></span>
                                                            </div>
                                                            <div class="stat-item stat-total">
                                                                <i class="fas fa-users"></i>
                                                                <span class="stat-value"><?php echo number_format($totalCount); ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php if ($dept !== end($courseEnrollmentByDepartment)): ?>
                                <hr style="border-top: 2px solid #e8e8e8; margin: 2rem 0;">
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Gender and Religion Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-venus-mars me-2"></i>Students by Gender
                </div>
                <div class="chart-card-body">
                    <canvas id="genderChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-praying-hands me-2"></i>Students by Religion
                </div>
                <div class="chart-card-body">
                    <canvas id="religionChart" style="max-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-building me-2"></i>Students by Department
                </div>
                <div class="chart-card-body">
                    <canvas id="departmentChart" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- District and Province Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-map-marker-alt me-2"></i>Top Districts
                </div>
                <div class="chart-card-body">
                    <canvas id="districtChart" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-globe me-2"></i>Students by Province
                </div>
                <div class="chart-card-body">
                    <canvas id="provinceChart" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Professional Modern color palette for charts
const professionalChartColors = {
    primary: '#6366f1',
    secondary: '#8b5cf6',
    accent: '#10b981',
    light: '#3b82f6',
    emerald: '#10b981',
    amber: '#f59e0b',
    shades: ['#6366f1', '#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6', '#06b6d4'],
    soft: ['#f8fafc', '#f1f5f9', '#e2e8f0', '#cbd5e1', '#94a3b8']
};

// NVQ Level Chart removed - now displayed as department-wise table

// Gender Chart
const genderCtx = document.getElementById('genderChart').getContext('2d');
const genderLabels = [<?php echo !empty($genderStats) ? implode(',', array_map(function($g) { return "'" . htmlspecialchars($g, ENT_QUOTES) . "'"; }, array_keys($genderStats))) : ''; ?>];
const genderData = [<?php echo !empty($genderStats) ? implode(',', array_values($genderStats)) : '0'; ?>];
new Chart(genderCtx, {
    type: 'pie',
    data: {
        labels: genderLabels.length > 0 ? genderLabels : ['No Data'],
        datasets: [{
            data: genderData.length > 0 ? genderData : [0],
            backgroundColor: professionalChartColors.shades,
            borderWidth: 3,
            borderColor: '#ffffff'
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
                        size: 12,
                        weight: '600'
                    },
                    color: '#2c3e50'
                }
            }
        }
    }
});

// Religion Chart
const religionCtx = document.getElementById('religionChart').getContext('2d');
const religionLabels = [<?php echo !empty($religionStats) ? implode(',', array_map(function($r) { return "'" . htmlspecialchars($r, ENT_QUOTES) . "'"; }, array_keys($religionStats))) : ''; ?>];
const religionData = [<?php echo !empty($religionStats) ? implode(',', array_values($religionStats)) : '0'; ?>];
new Chart(religionCtx, {
    type: 'bar',
    data: {
        labels: religionLabels.length > 0 ? religionLabels : ['No Data'],
        datasets: [{
            label: 'Students',
            data: religionData.length > 0 ? religionData : [0],
            backgroundColor: professionalChartColors.primary,
            borderColor: professionalChartColors.secondary,
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6c757d'
                },
                grid: {
                    color: '#e8e8e8'
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6c757d'
                },
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Department Chart
const deptCtx = document.getElementById('departmentChart').getContext('2d');
const deptLabels = [<?php echo !empty($departmentStats) ? implode(',', array_map(function($d) { $name = $d['name'] ?? $d['department_name'] ?? 'Unknown'; return "'" . htmlspecialchars($name, ENT_QUOTES) . "'"; }, $departmentStats)) : ''; ?>];
const deptData = [<?php echo !empty($departmentStats) ? implode(',', array_map(function($d) { return $d['count']; }, $departmentStats)) : '0'; ?>];
new Chart(deptCtx, {
    type: 'bar',
    data: {
        labels: deptLabels.length > 0 ? deptLabels : ['No Data'],
        datasets: [{
            label: 'Students',
            data: deptData.length > 0 ? deptData : [0],
            backgroundColor: professionalChartColors.secondary,
            borderColor: professionalChartColors.primary,
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6c757d'
                },
                grid: {
                    color: '#e8e8e8'
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6c757d'
                },
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// District Chart
const districtCtx = document.getElementById('districtChart').getContext('2d');
const districtData = <?php echo json_encode(!empty($districtStats) ? array_slice($districtStats, 0, 10, true) : []); ?>;
const districtLabels = Object.keys(districtData).length > 0 ? Object.keys(districtData).map(d => d) : ['No Data'];
const districtValues = Object.keys(districtData).length > 0 ? Object.values(districtData) : [0];
new Chart(districtCtx, {
    type: 'bar',
    data: {
        labels: districtLabels,
        datasets: [{
            label: 'Students',
            data: districtValues,
            backgroundColor: professionalChartColors.accent,
            borderColor: professionalChartColors.primary,
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6c757d'
                },
                grid: {
                    color: '#e8e8e8'
                }
            },
            y: {
                ticks: {
                    font: {
                        size: 11
                    },
                    color: '#6c757d'
                },
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Province Chart
const provinceCtx = document.getElementById('provinceChart').getContext('2d');
<?php 
// Limit to top 9 provinces, sorted by count (descending)
$topProvinces = [];
if (!empty($provinceStats)) {
    // Sort by count in descending order
    arsort($provinceStats);
    // Take only the top 9 provinces
    $topProvinces = array_slice($provinceStats, 0, 9, true);
}
?>
const provinceLabels = [<?php echo !empty($topProvinces) ? implode(',', array_map(function($p) { return "'" . htmlspecialchars($p, ENT_QUOTES) . "'"; }, array_keys($topProvinces))) : ''; ?>];
const provinceData = [<?php echo !empty($topProvinces) ? implode(',', array_values($topProvinces)) : '0'; ?>];
new Chart(provinceCtx, {
    type: 'pie',
    data: {
        labels: provinceLabels.length > 0 ? provinceLabels : ['No Data'],
        datasets: [{
            data: provinceData.length > 0 ? provinceData : [0],
            backgroundColor: professionalChartColors.shades,
            borderWidth: 3,
            borderColor: '#ffffff'
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
                        size: 12,
                        weight: '600'
                    },
                    color: '#2c3e50'
                }
            }
        }
    }
});

// Academic Year Filter Change Handler
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
</script>