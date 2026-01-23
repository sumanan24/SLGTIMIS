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
    
    .chart-stat-item {
        background: #f5f5f5;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
        border-left: 3px solid #001f3f;
    }
    
    .chart-stat-label {
        color: #2c3e50;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .chart-stat-badge {
        background: #001f3f;
        color: #ffffff;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .department-grid-item {
        background: #f5f5f5;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .department-grid-item:hover {
        background: #e8e8e8;
        border-color: #001f3f;
    }
    
    .department-grid-item strong {
        color: #2c3e50;
        font-size: 0.9rem;
        display: block;
        margin-bottom: 0.5rem;
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
                    <p class="mb-0">Here's a comprehensive overview of your system</p>
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
                            <div class="stats-card-subtitle">Active Students</div>
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
                            <div class="stats-card-subtitle">Active Staff Members</div>
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
    
    <!-- Course Enrollment by Department -->
    <?php if (!empty($courseEnrollmentByDepartment)): ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-book-reader me-2"></i>Course Enrollment by Department
                </div>
                <div class="card-body p-0">
                    <div class="accordion accordion-custom" id="departmentAccordion">
                        <?php $deptIndex = 0; foreach ($courseEnrollmentByDepartment as $dept): ?>
                        <div class="accordion-item border-0 border-bottom">
                            <h2 class="accordion-header" id="deptHeading<?php echo $dept['department_id']; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#deptCollapse<?php echo $dept['department_id']; ?>" 
                                        aria-expanded="false" 
                                        aria-controls="deptCollapse<?php echo $dept['department_id']; ?>"
                                        style="background: linear-gradient(135deg, #001f3f 0%, #003366 100%); color: white; font-weight: 600; border: none; padding: 1.25rem 1.5rem;">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building me-3" style="font-size: 1.25rem;"></i>
                                            <span style="font-size: 1.1rem;"><?php echo htmlspecialchars($dept['department_name']); ?></span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="text-end">
                                                <div style="font-size: 1.5rem; font-weight: 700; line-height: 1;">
                                                    <?php echo number_format($dept['total_enrollment'] ?? 0); ?>
                                                </div>
                                                <small style="opacity: 0.9; font-size: 0.75rem;">Total Students</small>
                                            </div>
                                            <i class="fas fa-chevron-down accordion-icon" style="transition: transform 0.3s ease;"></i>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="deptCollapse<?php echo $dept['department_id']; ?>" 
                                 class="accordion-collapse collapse" 
                                 aria-labelledby="deptHeading<?php echo $dept['department_id']; ?>" 
                                 data-bs-parent="#departmentAccordion">
                                <div class="accordion-body" style="background: #f8f9fa; padding: 1.5rem;">
                                    <div class="row g-3">
                                        <?php foreach ($dept['courses'] as $course): ?>
                                        <div class="col-md-12">
                                            <div class="card" style="border-left: 4px solid #003366; background: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
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
                        <?php $deptIndex++; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .accordion-custom .accordion-button {
            transition: all 0.3s ease;
        }
        
        .accordion-custom .accordion-button:not(.collapsed) .accordion-icon {
            transform: rotate(180deg);
        }
        
        .accordion-custom .accordion-button:focus {
            box-shadow: none;
            border-color: transparent;
        }
        
        .accordion-custom .accordion-button::after {
            display: none;
        }
        
        .accordion-custom .accordion-item:first-of-type {
            border-top: none;
        }
        
        .accordion-custom .accordion-item:last-of-type {
            border-bottom: 1px solid #dee2e6 !important;
        }
        
        .accordion-custom .accordion-button:hover {
            background: linear-gradient(135deg, #003366 0%, #004d99 100%) !important;
        }
        
        .accordion-custom .accordion-collapse {
            transition: height 0.35s ease;
        }
    </style>
    
    <script>
        // Handle accordion behavior - ensure only one department is open at a time
        document.addEventListener('DOMContentLoaded', function() {
            const accordionButtons = document.querySelectorAll('#departmentAccordion .accordion-button');
            
            accordionButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-bs-target');
                    const targetCollapse = document.querySelector(targetId);
                    
                    // Bootstrap's accordion will handle the collapse/expand automatically
                    // But we can add custom behavior here if needed
                    
                    // Close all other accordions (Bootstrap handles this with data-bs-parent)
                    // But we ensure smooth animation
                    setTimeout(function() {
                        const allCollapses = document.querySelectorAll('#departmentAccordion .accordion-collapse');
                        allCollapses.forEach(function(collapse) {
                            if (collapse !== targetCollapse && collapse.classList.contains('show')) {
                                // This will be handled by Bootstrap
                            }
                        });
                    }, 100);
                });
            });
            
            // Handle accordion collapse events for icon rotation
            const accordionCollapses = document.querySelectorAll('#departmentAccordion .accordion-collapse');
            accordionCollapses.forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function() {
                    const button = document.querySelector('[data-bs-target="#' + this.id + '"]');
                    if (button) {
                        button.querySelector('.accordion-icon')?.style.setProperty('transform', 'rotate(180deg)');
                    }
                });
                
                collapse.addEventListener('hide.bs.collapse', function() {
                    const button = document.querySelector('[data-bs-target="#' + this.id + '"]');
                    if (button) {
                        button.querySelector('.accordion-icon')?.style.setProperty('transform', 'rotate(0deg)');
                    }
                });
            });
        });
    </script>
    <?php endif; ?>

    <!-- NVQ Level by Department -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-graduation-cap me-2"></i>NVQ Level by Department
                </div>
                <div class="card-body">
                    <?php if (!empty($nvqStatsByDepartment)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Department</th>
                                        <th class="text-center">NVQ Level 04</th>
                                        <th class="text-center">NVQ Level 05</th>
                                        <th class="text-center">NVQ Level 06</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nvqStatsByDepartment as $dept): 
                                        $dept04 = $dept['levels']['04'] ?? 0;
                                        $dept05 = $dept['levels']['05'] ?? 0;
                                        $dept06 = $dept['levels']['06'] ?? 0;
                                        $deptTotal = $dept04 + $dept05 + $dept06;
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($dept['department_name']); ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge" style="background: #0066cc; color: white;">
                                                    <?php echo number_format($dept04); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" style="background: #003366; color: white;">
                                                    <?php echo number_format($dept05); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" style="background: #004c99; color: white;">
                                                    <?php echo number_format($dept06); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <strong><?php echo number_format($deptTotal); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-center"><?php 
                                            $total04 = 0;
                                            foreach ($nvqStatsByDepartment as $dept) {
                                                $total04 += $dept['levels']['04'] ?? 0;
                                            }
                                            echo number_format($total04);
                                        ?></th>
                                        <th class="text-center"><?php 
                                            $total05 = 0;
                                            foreach ($nvqStatsByDepartment as $dept) {
                                                $total05 += $dept['levels']['05'] ?? 0;
                                            }
                                            echo number_format($total05);
                                        ?></th>
                                        <th class="text-center"><?php 
                                            $total06 = 0;
                                            foreach ($nvqStatsByDepartment as $dept) {
                                                $total06 += $dept['levels']['06'] ?? 0;
                                            }
                                            echo number_format($total06);
                                        ?></th>
                                        <th class="text-center"><?php echo number_format($totalStudents); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>
                            No NVQ Level data available by department
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Gender and Religion Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-venus-mars me-2"></i>Students by Gender
                </div>
                <div class="chart-card-body">
                    <canvas id="genderChart" style="max-height: 280px;"></canvas>
                    <div class="mt-4">
                        <?php foreach ($genderStats as $gender => $count): ?>
                        <div class="chart-stat-item d-flex justify-content-between align-items-center">
                            <span class="chart-stat-label"><?php echo htmlspecialchars($gender); ?>:</span>
                            <span class="chart-stat-badge"><?php echo number_format($count); ?> Students</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
                    <div class="mt-4">
                        <?php 
                        $topReligions = array_slice($religionStats, 0, 5, true);
                        foreach ($topReligions as $religion => $count): 
                        ?>
                        <div class="chart-stat-item d-flex justify-content-between align-items-center">
                            <span class="chart-stat-label"><?php echo htmlspecialchars($religion); ?>:</span>
                            <span class="chart-stat-badge"><?php echo number_format($count); ?> Students</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
                    <div class="row mt-4 g-3">
                        <?php foreach ($departmentStats as $dept): ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="department-grid-item">
                                <strong><?php echo htmlspecialchars($dept['name'] ?? $dept['department_name'] ?? 'Unknown'); ?></strong>
                                <span class="chart-stat-badge"><?php echo number_format($dept['count']); ?> Students</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
                    <div class="mt-4">
                        <?php 
                        $topDistricts = array_slice($districtStats, 0, 5, true);
                        foreach ($topDistricts as $district => $count): 
                        ?>
                        <div class="chart-stat-item d-flex justify-content-between align-items-center">
                            <span class="chart-stat-label"><?php echo htmlspecialchars($district); ?>:</span>
                            <span class="chart-stat-badge" style="background: #003366;"><?php echo number_format($count); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
                    <div class="mt-4">
                        <?php foreach ($provinceStats as $province => $count): ?>
                        <div class="chart-stat-item d-flex justify-content-between align-items-center">
                            <span class="chart-stat-label"><?php echo htmlspecialchars($province); ?>:</span>
                            <span class="chart-stat-badge" style="background: #1e3a5f;"><?php echo number_format($count); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Navy Blue color palette for charts
const navyChartColors = {
    primary: '#001f3f',
    secondary: '#003366',
    accent: '#1e3a5f',
    light: '#003d7a',
    shades: ['#001f3f', '#003366', '#1e3a5f', '#003d7a', '#004c8c'],
    soft: ['#e8e8e8', '#d3d3d3', '#b8b8b8', '#9d9d9d', '#808080']
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
            backgroundColor: navyChartColors.shades,
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
            backgroundColor: navyChartColors.primary,
            borderColor: navyChartColors.secondary,
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
            backgroundColor: navyChartColors.secondary,
            borderColor: navyChartColors.primary,
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
            backgroundColor: navyChartColors.accent,
            borderColor: navyChartColors.primary,
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
const provinceLabels = [<?php echo !empty($provinceStats) ? implode(',', array_map(function($p) { return "'" . htmlspecialchars($p, ENT_QUOTES) . "'"; }, array_keys($provinceStats))) : ''; ?>];
const provinceData = [<?php echo !empty($provinceStats) ? implode(',', array_values($provinceStats)) : '0'; ?>];
new Chart(provinceCtx, {
    type: 'pie',
    data: {
        labels: provinceLabels.length > 0 ? provinceLabels : ['No Data'],
        datasets: [{
            data: provinceData.length > 0 ? provinceData : [0],
            backgroundColor: navyChartColors.shades,
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