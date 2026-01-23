<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* Dashboard Styles - Navy Blue, White, Soft Gray Theme */
    .dashboard-container {
        background-color: #f5f5f5;
        min-height: calc(100vh - 60px);
        padding: 2rem 1.5rem;
    }
    
    .dashboard-welcome-card {
        background: #ffffff;
        border-radius: 16px;
        border: 2px solid #001f3f;
        box-shadow: 0 4px 12px rgba(0, 31, 63, 0.2);
        margin-bottom: 2rem;
    }
    
    .dashboard-welcome-card .card-body {
        color: #001f3f;
        padding: 2rem;
    }
    
    .dashboard-welcome-card h1 {
        color: #001f3f;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .dashboard-welcome-card p {
        color: #6c757d;
        font-size: 0.95rem;
    }
    
    .dashboard-welcome-card select {
        background-color: #ffffff;
        border: 1px solid #001f3f;
        color: #001f3f;
        border-radius: 8px;
    }
    
    .dashboard-welcome-card select option {
        background-color: #ffffff;
        color: #001f3f;
    }
    
    .dashboard-welcome-card label {
        color: #001f3f;
    }
    
    .dashboard-welcome-card small {
        color: #6c757d;
    }
    
    .dashboard-welcome-card .border-top {
        border-color: #e8e8e8 !important;
    }
    
    .dashboard-stats-card {
        background: #ffffff;
        border-radius: 12px;
        border: none;
        border-left: 4px solid #001f3f;
        box-shadow: 0 2px 8px rgba(0, 31, 63, 0.1);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .dashboard-stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 16px rgba(0, 31, 63, 0.15);
    }
    
    .dashboard-stats-card:nth-child(2) {
        border-left-color: #003366;
    }
    
    .dashboard-stats-card:nth-child(3) {
        border-left-color: #1e3a5f;
    }
    
    .stats-card-label {
        color: #6c757d;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    
    .stats-card-value {
        color: #001f3f;
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stats-card-subtitle {
        color: #6c757d;
        font-size: 0.85rem;
    }
    
    .stats-card-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        background: rgba(0, 31, 63, 0.1);
        color: #001f3f;
    }
    
    .dashboard-chart-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e8e8e8;
        box-shadow: 0 2px 8px rgba(0, 31, 63, 0.08);
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .dashboard-chart-card:hover {
        box-shadow: 0 4px 16px rgba(0, 31, 63, 0.12);
    }
    
    .chart-card-header {
        background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
        color: #ffffff;
        padding: 1rem 1.25rem;
        font-weight: 600;
        border-radius: 12px 12px 0 0;
        border: none;
    }
    
    .chart-card-body {
        padding: 1.5rem;
        color: #2c3e50;
    }
    
    .department-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
        color: white;
        border-radius: 50px;
        font-weight: 600;
        margin-bottom: 1rem;
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
    }
</style>

<div class="dashboard-container">
    <!-- Welcome Card with Department Info and Academic Year Filter -->
    <div class="card dashboard-welcome-card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div class="flex-grow-1">
                    <h1>Welcome, <?php echo htmlspecialchars($user_name ?? 'User'); ?>!</h1>
                    <p class="mb-0">Here's an overview of your department</p>
                    <div class="mt-2">
                        <span class="department-badge">
                            <i class="fas fa-building me-2"></i>
                            <?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?>
                        </span>
                    </div>
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
        <div class="col-md-4">
            <div class="card dashboard-stats-card" style="border-left-color: #001f3f;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="stats-card-label">Total Students</div>
                            <div class="stats-card-value"><?php echo number_format($totalStudents); ?></div>
                            <div class="stats-card-subtitle">Active Students in Department</div>
                        </div>
                        <div class="stats-card-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card dashboard-stats-card" style="border-left-color: #1e3a5f;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <div class="stats-card-label">Total Staff</div>
                            <div class="stats-card-value" style="color: #003366;"><?php echo number_format($totalStaff); ?></div>
                            <div class="stats-card-subtitle">Department Staff Members</div>
                        </div>
                        <div class="stats-card-icon" style="background: rgba(0, 51, 102, 0.1); color: #003366;">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
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
    
    <!-- Course Enrollment -->
    <?php if (!empty($courseEnrollmentByDepartment) && isset($courseEnrollmentByDepartment[$department_id])): ?>
    <?php $dept = $courseEnrollmentByDepartment[$department_id]; ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-book-reader me-2"></i>Course Enrollment - <?php echo htmlspecialchars($dept['department_name']); ?>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-muted">
                            <i class="fas fa-users me-2"></i>
                            Total Enrollment: <strong class="text-primary"><?php echo number_format($dept['total_enrollment'] ?? 0); ?></strong> Students
                        </h5>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($dept['courses'] as $course): ?>
                        <div class="col-md-12">
                            <div class="card" style="border-left: 4px solid #003366; background: #f8f9fa; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
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
                                                <?php echo number_format($course['enrollment_count']); ?>
                                            </div>
                                            <small class="text-muted">Students</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- NVQ Level Statistics -->
    <?php if (!empty($nvqStatsByDepartment) && isset($nvqStatsByDepartment[$department_id])): ?>
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
    
    <!-- Gender Statistics -->
    <?php if (!empty($genderStats)): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-venus-mars me-2"></i>Gender Distribution
                </div>
                <div class="card-body">
                    <canvas id="genderChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Students -->
    <?php if (!empty($recentStudents)): ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-users me-2"></i>Recent Students in Department
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
    <?php endif; ?>
</div>

<script>
// Academic Year Filter
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
// Gender Chart
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

