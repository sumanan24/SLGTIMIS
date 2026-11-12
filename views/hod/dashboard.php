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
       MOBILE RESPONSIVE - Text & card proper sizing
       ============================================ */
    @media (max-width: 992px) {
        .dashboard-container {
            padding: 1.25rem 1rem;
            font-size: 1rem;
        }
        .dashboard-welcome-card .card-body {
            padding: 1.5rem 1rem;
        }
        .chart-card-header {
            padding: 0.875rem 1rem;
            font-size: clamp(0.9rem, 2.2vw, 0.95rem);
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 0; /* no side space on mobile */
            min-height: calc(100vh - 56px);
            font-size: 1rem;
        }
        
        .dashboard-container .row {
            margin-left: 0;
            margin-right: 0;
        }
        
        .dashboard-container .row > [class*="col-"] {
            padding-left: 0;
            padding-right: 0;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-welcome-card {
            margin-bottom: 0.75rem;
            border-radius: 12px;
        }
        
        .dashboard-welcome-card .card-body {
            padding: 0.875rem 0.625rem;
            font-size: 1rem;
        }
        
        .dashboard-welcome-card h1 {
            font-size: clamp(1.2rem, 4.5vw, 1.35rem);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        
        .dashboard-welcome-card p {
            font-size: 0.9375rem; /* 15px - readable */
        }
        
        .dashboard-welcome-card small {
            font-size: 0.875rem;
        }
        
        .dashboard-welcome-card .d-flex.align-items-start.justify-content-between,
        .dashboard-welcome-card .d-flex.align-items-md-center {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 1rem !important;
        }
        
        .dashboard-welcome-card .d-flex > div.flex-grow-1 {
            width: 100%;
        }
        
        .dashboard-welcome-card .d-flex.align-items-center.gap-3 {
            width: 100%;
            flex-direction: column !important;
            align-items: stretch !important;
        }
        
        .dashboard-welcome-card .d-flex.align-items-center.gap-2 {
            width: 100%;
            flex-direction: column !important;
            align-items: stretch !important;
        }
        
        .dashboard-welcome-card label[for="academicYearFilter"] {
            margin-bottom: 0.25rem;
            font-size: 0.9375rem;
        }
        
        .dashboard-welcome-card select#academicYearFilter {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100%;
            font-size: 1rem; /* 16px avoids iOS zoom on focus */
            min-height: 44px;
        }
        
        .department-badge {
            font-size: 0.875rem;
            padding: 0.5rem 0.875rem;
        }
        
        .department-info-box {
            padding: 0.875rem;
            margin-top: 0.75rem;
            font-size: 0.875rem;
        }
        
        .dashboard-welcome-card .border-top {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
        }
        
        /* Stats cards - stack and align, proper text size */
        .row.g-4 > .col-md-4 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .dashboard-stats-card {
            margin-bottom: 0;
            border-radius: 12px;
        }
        
        .dashboard-stats-card .card-body {
            padding: 0.75rem 0.625rem;
            min-height: auto;
            font-size: 1rem;
        }
        
        .dashboard-stats-card .card-body > .d-flex {
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between;
            gap: 0.75rem;
        }
        
        .stats-card-label {
            font-size: 0.75rem;
        }
        
        .stats-card-value {
            font-size: clamp(1.65rem, 6vw, 1.85rem);
            line-height: 1.2;
        }
        
        .stats-card-subtitle {
            font-size: 0.8125rem; /* 13px - readable */
            line-height: 1.35;
        }
        
        .stats-card-icon {
            width: 48px;
            height: 48px;
            font-size: 1.35rem;
            margin-left: 0 !important;
            flex-shrink: 0;
        }
        
        .chart-card-body {
            padding: 0.625rem 0.5rem;
            min-height: 240px;
        }
        
        .chart-card-body canvas {
            max-height: 240px;
        }
        
        .dashboard-chart-card {
            margin-bottom: 0.5rem;
            border-radius: 12px;
        }
        
        .dashboard-chart-card .card-body {
            padding: 0.625rem 0.5rem;
        }
        
        .chart-card-header {
            padding: 0.625rem 0.5rem;
        }
        
        .dashboard-chart-card .card-body {
            font-size: 1rem;
        }
        
        .dashboard-chart-card .card-body h5 {
            font-size: 0.9375rem;
        }
        
        /* Course enrollment - stack on mobile, tight to border */
        .course-enrollment-item .card-body {
            padding: 0.625rem 0.5rem !important;
            font-size: 1rem;
        }
        
        .course-enrollment-item .card-body .d-flex {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.5rem;
        }
        
        .course-enrollment-item .text-end {
            text-align: left !important;
        }
        
        .course-enrollment-item h6 {
            font-size: 0.9375rem;
            line-height: 1.4;
        }
        
        .course-enrollment-item small {
            font-size: 0.8125rem;
        }
        
        .course-enrollment-item .h4 {
            font-size: 1.25rem;
        }
        
        /* Tables - horizontal scroll, tight to card edge */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-left: -0.5rem;
            margin-right: -0.5rem;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .table-responsive .table {
            margin-bottom: 0;
            font-size: 0.9375rem;
        }
        
        .table-responsive .table th,
        .table-responsive .table td {
            white-space: nowrap;
            padding: 0.5rem 0.4rem;
            min-width: 44px;
        }
        
        /* Hostel summary - 2x2 grid, tight padding */
        .row.g-3.mb-4 .col-6 .card-body {
            padding: 0.5rem 0.375rem !important;
            font-size: 1rem;
        }
        
        .row.g-3.mb-4 {
            margin-left: -0.25rem;
            margin-right: -0.25rem;
        }
        
        .row.g-3.mb-4 > [class*="col-"] {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
        
        .row.g-3.mb-4 .col-6 .h4 {
            font-size: clamp(1.15rem, 5vw, 1.25rem);
        }
        
        .row.g-3.mb-4 .col-6 .small {
            font-size: 0.8125rem;
        }
        
        .alert {
            font-size: 0.9375rem;
            padding: 0.5rem 0.625rem;
        }
        
        .row.g-4 {
            margin-left: -0.25rem;
            margin-right: -0.25rem;
        }
        
        .row.g-4 > [class*="col-"] {
            padding-left: 0.25rem;
            padding-right: 0.25rem;
        }
    }
    
    @media (max-width: 576px) {
        .dashboard-container {
            padding: 0;
            font-size: 1rem;
        }
        
        .dashboard-container .row > [class*="col-"] {
            padding-left: 0;
            padding-right: 0;
            margin-bottom: 0.375rem;
        }
        
        .dashboard-welcome-card {
            margin-bottom: 0.5rem;
        }
        
        .dashboard-welcome-card .card-body {
            padding: 0.75rem 0.5rem;
        }
        
        .dashboard-welcome-card h1 {
            font-size: clamp(1.1rem, 5vw, 1.25rem);
        }
        
        .stats-card-value {
            font-size: clamp(1.5rem, 7vw, 1.65rem);
        }
        
        .stats-card-subtitle {
            font-size: 0.8125rem;
        }
        
        .stats-card-icon {
            width: 44px;
            height: 44px;
            font-size: 1.2rem;
        }
        
        .dashboard-stats-card .card-body {
            padding: 0.625rem 0.5rem;
        }
        
        .chart-card-header {
            padding: 0.5rem 0.5rem;
            font-size: clamp(0.875rem, 2.5vw, 0.9375rem);
        }
        
        .chart-card-body {
            padding: 0.5rem 0.375rem;
            min-height: 200px;
        }
        
        .chart-card-body canvas {
            max-height: 200px;
        }
        
        .dashboard-chart-card .card-body {
            padding: 0.5rem 0.375rem;
        }
        
        /* Hostel summary - single column on very small */
        .row.g-3.mb-4 .col-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .course-enrollment-item .card-body {
            padding: 0.5rem 0.375rem !important;
        }
        
        .course-enrollment-item h6 {
            font-size: 0.9375rem;
        }
        
        .table-responsive .table {
            font-size: 0.875rem;
        }
        
        .table-responsive .table th,
        .table-responsive .table td {
            padding: 0.5rem 0.4rem;
        }
    }
    
    @media (max-width: 380px) {
        .dashboard-container {
            padding: 0;
        }

        .dashboard-welcome-card h1 {
            font-size: 1.1rem;
        }
        
        .dashboard-welcome-card .card-body {
            padding: 0.625rem 0.375rem;
        }
        
        .stats-card-value {
            font-size: 1.4rem;
        }
        
        .stats-card-subtitle {
            font-size: 0.75rem;
        }
        
        .department-badge {
            font-size: 0.8125rem;
            padding: 0.4rem 0.625rem;
        }
        
        .dashboard-stats-card .card-body {
            padding: 0.5rem 0.375rem;
        }
        
        .chart-card-header,
        .chart-card-body {
            padding: 0.5rem 0.375rem;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Department and academic year selector -->
    <?php
    require_once BASE_PATH . '/models/UserModel.php';
    // Assigned heads get the detailed "Department assignment" card below; this line is
    // only the fallback for a HOD post without an explicit assignment.
    $dashHeaded = empty($assignments) ? (new UserModel())->getHeadedDepartments((int) ($_SESSION['user_id'] ?? 0)) : [];
    ?>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <?php if (!empty($department)): ?>
                <span class="department-badge">
                    <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($department['department_name'] ?? 'Department'); ?>
                </span>
            <?php else: ?>
                <span class="badge bg-secondary"><i class="fas fa-info-circle me-2"></i>All Departments</span>
            <?php endif; ?>
            <?php foreach ($dashHeaded as $dh): ?>
                <div class="mt-2 small text-muted"><i class="fas fa-user-tie me-2"></i>Head of Department &mdash; <?php echo htmlspecialchars($dh['department_name']); ?><?php echo $dh['head_type_label'] !== '' ? ' (' . htmlspecialchars($dh['head_type_label']) . ')' : ''; ?></div>
            <?php endforeach; ?>
        </div>
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
    </div>
    
    <?php
    $e = static function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
    $typeBadge = ['permanent' => '#0f766e', 'acting' => '#b45309', 'cover_up' => '#7c3aed'];
    $dashUrl = APP_URL . '/hod/dashboard';
    $yearQs = !empty($selectedAcademicYear) ? '&academic_year=' . urlencode($selectedAcademicYear) : '';
    ?>
    <style>
        .assign-card { border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 2px 10px rgba(15,23,42,.06); margin-bottom: 1.5rem; width: 100%; }
        .assign-card .assign-head { background: linear-gradient(135deg, #001f3f 0%, #003366 100%); color: #fff; padding: 1rem 1.25rem; font-weight: 600; border-radius: 14px 14px 0 0; }
        .assign-card .assign-body { padding: 1.25rem; }
        .assign-item { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .85rem 1rem; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: .6rem; background: #f8fafc; }
        .assign-item.is-active { border-color: #003366; background: #eef4fb; box-shadow: inset 4px 0 0 #003366; }
        .assign-type { display: inline-block; padding: .2rem .65rem; border-radius: 999px; color: #fff; font-size: .75rem; font-weight: 600; margin-left: .4rem; }
        .assign-meta { color: #64748b; font-size: .85rem; }
        .dept-actions { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
        .dept-actions .btn { border-radius: 8px; }
        .cm-table th, .cm-table td { vertical-align: middle; }
        @media (max-width: 768px) { .assign-card .assign-body { padding: .75rem .6rem; } .assign-item { padding: .7rem .6rem; } }
    </style>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $e($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($department)): ?>
    <!-- Department assignment (Permanent / Acting / Cover-up) + department information shortcuts -->
    <div class="assign-card">
        <div class="assign-head"><i class="fas fa-user-tie me-2"></i>Department Assignment &amp; Information</div>
        <div class="assign-body">
            <?php if (!empty($assignments) || !empty($ownDepartment)): ?>
                <?php foreach ($assignments as $a): ?>
                <div class="assign-item <?php echo !empty($a['active']) ? 'is-active' : ''; ?>">
                    <div>
                        <strong><?php echo $e($a['department_name']); ?></strong>
                        <?php if ($a['head_type_label'] !== ''): ?>
                            <span class="assign-type" style="background: <?php echo $typeBadge[$a['head_type']] ?? '#475569'; ?>;"><?php echo $e($a['head_type_label']); ?> Head</span>
                        <?php endif; ?>
                        <div class="assign-meta">
                            <?php echo $e($a['department_id']); ?>
                            <?php if (!empty($a['start_date'])): ?> &middot; Since <?php echo $e(date('d M Y', strtotime($a['start_date']))); ?><?php endif; ?>
                            <?php if (!empty($a['remarks'])): ?> &middot; <?php echo $e($a['remarks']); ?><?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($a['active'])): ?>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Viewing</span>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo $dashUrl . '?department_id=' . urlencode($a['department_id']) . $yearQs; ?>">View this department</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($ownDepartment)): ?>
                <div class="assign-item <?php echo !empty($ownDepartment['active']) ? 'is-active' : ''; ?>">
                    <div>
                        <strong><?php echo $e($ownDepartment['department_name']); ?></strong>
                        <span class="assign-type" style="background:#475569;">My department</span>
                        <div class="assign-meta"><?php echo $e($ownDepartment['department_id']); ?> &middot; Department on your staff record</div>
                    </div>
                    <?php if (!empty($ownDepartment['active'])): ?>
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Viewing</span>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo $dashUrl . '?department_id=' . urlencode($ownDepartment['department_id']) . $yearQs; ?>">View this department</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="dept-actions">
                <a class="btn btn-primary btn-sm" href="<?php echo APP_URL; ?>/departments/view?id=<?php echo urlencode($department['department_id']); ?>">
                    <i class="fas fa-info-circle me-1"></i>Department Information
                </a>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo APP_URL; ?>/courses?department_id=<?php echo urlencode($department['department_id']); ?>">
                    <i class="fas fa-book me-1"></i>Courses
                </a>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo APP_URL; ?>/modules?department_id=<?php echo urlencode($department['department_id']); ?>">
                    <i class="fas fa-cubes me-1"></i>Course Modules
                </a>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo APP_URL; ?>/groups">
                    <i class="fas fa-users me-1"></i>Student Groups
                </a>
                <?php if (!empty($canManageCourses)): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/modules/create">
                    <i class="fas fa-plus me-1"></i>Add Module
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo APP_URL; ?>/hod/staff-module-enroll">
                    <i class="fas fa-user-cog me-1"></i>Staff Module Enrollment
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($department) && (!empty($canMarkAttendance) || !empty($canViewAttendanceReport))): ?>
    <?php
        $attMonth = (string) ($attendanceMonth ?? date('Y-m'));
        $attMonthLabel = date('F Y', strtotime($attMonth . '-01'));
        $attLocked = !empty($attendanceMonthLocked);
        $attDeptQs = 'department_id=' . urlencode((string) ($department['department_id'] ?? ''));
        $attYearQs = !empty($selectedAcademicYear) ? '&academic_year=' . urlencode((string) $selectedAcademicYear) : '';
        $attMonthQs = '&month=' . urlencode($attMonth);
        $markUrl = APP_URL . '/attendance?' . $attDeptQs . $attYearQs . $attMonthQs;
        $reportUrl = APP_URL . '/attendance/report?' . $attDeptQs . $attYearQs . $attMonthQs . '&view=summary';
    ?>
    <style>
        .hod-att-card { border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 2px 10px rgba(15,23,42,.06); margin-bottom: 1.5rem; overflow: hidden; }
        .hod-att-head { background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: #fff; padding: 1rem 1.25rem; font-weight: 600; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
        .hod-att-body { padding: 1.15rem 1.25rem; }
        .hod-att-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
        .hod-att-action { display: flex; flex-direction: column; gap: .55rem; min-width: 0; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; background: #f8fafc; height: 100%; }
        .hod-att-action h3 { margin: 0; font-size: .95rem; font-weight: 700; color: #0f172a; }
        .hod-att-action p { margin: 0; font-size: .84rem; color: #64748b; line-height: 1.4; flex: 1; }
        .hod-att-meta { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; margin-bottom: .85rem; }
        .hod-att-meta .badge { font-weight: 600; }
        @media (max-width: 767.98px) {
            .hod-att-grid { grid-template-columns: 1fr; }
            .hod-att-body { padding: .85rem .75rem; }
            .hod-att-action .btn { width: 100%; }
        }
    </style>
    <div class="hod-att-card">
        <div class="hod-att-head">
            <span><i class="fas fa-user-check me-2"></i>Student Attendance</span>
            <span class="small opacity-90"><?php echo $e($attMonthLabel); ?></span>
        </div>
        <div class="hod-att-body">
            <div class="hod-att-meta">
                <?php if ($attLocked): ?>
                    <span class="badge bg-danger"><i class="fas fa-lock me-1"></i>Month locked</span>
                    <?php if (!empty($attendanceLockStatus['locked_by_name'])): ?>
                        <span class="small text-muted">by <?php echo $e($attendanceLockStatus['locked_by_name']); ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-success"><i class="fas fa-unlock me-1"></i>Open for marking</span>
                <?php endif; ?>
                <span class="small text-muted"><?php echo $e($department['department_name'] ?? ''); ?></span>
            </div>
            <div class="hod-att-grid">
                <?php if (!empty($canMarkAttendance)): ?>
                <div class="hod-att-action">
                    <h3><i class="fas fa-clipboard-check me-1 text-teal" style="color:#0f766e;"></i>Mark attendance</h3>
                    <p>Mark daily student attendance for your department courses and groups<?php echo $attLocked ? '. This month is locked — open only if admin unlocks.' : '.'; ?></p>
                    <a class="btn btn-success btn-sm <?php echo $attLocked ? 'disabled' : ''; ?>"
                       href="<?php echo $attLocked ? '#' : $e($markUrl); ?>"
                       <?php echo $attLocked ? 'aria-disabled="true" tabindex="-1"' : ''; ?>>
                        <i class="fas fa-pen me-1"></i><?php echo $attLocked ? 'Marking locked' : 'Open mark sheet'; ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($canViewAttendanceReport)): ?>
                <div class="hod-att-action">
                    <h3><i class="fas fa-chart-bar me-1" style="color:#003366;"></i>Report &amp; lock</h3>
                    <p>View the department attendance report, export when locked, and lock the month after review.</p>
                    <a class="btn btn-primary btn-sm" href="<?php echo $e($reportUrl); ?>">
                        <i class="fas fa-<?php echo $attLocked ? 'lock' : 'file-alt'; ?> me-1"></i>
                        <?php echo $attLocked ? 'View locked report' : 'Open report / lock month'; ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Stats Cards - Department Only (if user has department) -->
    <?php if (!empty($department)): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
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
        
        <div class="col-md-6">
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
    
    <!-- Student status counts + my work (modules, timetable, programmes, tickets) -->
    <?php
    $my = $my ?? ['staff_id' => '', 'modules' => [], 'timetable' => [], 'programs' => [], 'tickets' => [], 'ticket_total' => 0];
    $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    $today = (int) date('N');
    $humanize = static function ($v) { return ucwords(str_replace('_', ' ', (string) $v)); };
    $prioClass = ['critical' => 'danger', 'urgent' => 'danger', 'high' => 'warning text-dark', 'medium' => 'info text-dark', 'low' => 'secondary'];
    ?>
    <?php if (!empty($department) && !empty($studentStatusCounts)): ?>
    <style>
        .enr-card { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 1rem 1.1rem; height: 100%; box-shadow: 0 2px 8px rgba(15,23,42,.05); }
        .enr-card .enr-label { color: #64748b; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .enr-card .enr-total { color: #001f3f; font-size: 1.9rem; font-weight: 700; line-height: 1.2; }
        .enr-card .enr-modes { display: flex; gap: .5rem; margin-top: .6rem; }
        .enr-card .enr-mode { flex: 1; text-align: center; background: #f8fafc; border-radius: 8px; padding: .35rem .25rem; font-size: .75rem; color: #64748b; }
        .enr-card .enr-mode strong { display: block; color: #0f172a; font-size: 1rem; }
    </style>
    <div class="row g-3 mb-4">
        <?php foreach ([
            'total' => ['Total Students (Following + Internship)', '#001f3f'],
            'following' => ['Following', '#0f766e'],
            'internship' => ['Internship', '#b45309'],
            'completed' => ['Completed', '#475569'],
        ] as $k => [$lbl, $clr]): ?>
        <?php $c = $studentStatusCounts[$k] ?? ['total' => 0, 'full' => 0, 'part' => 0]; ?>
        <div class="col-6 col-lg-3">
            <div class="enr-card" style="border-top: 3px solid <?php echo $clr; ?>;">
                <div class="enr-label"><?php echo $e($lbl); ?></div>
                <div class="enr-total" style="color: <?php echo $clr; ?>;"><?php echo number_format($c['total']); ?></div>
                <div class="enr-modes">
                    <div class="enr-mode"><strong><?php echo number_format($c['full']); ?></strong>Full time</div>
                    <div class="enr-mode"><strong><?php echo number_format($c['part']); ?></strong>Part time</div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($my['staff_id'])): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card dashboard-chart-card h-100">
                <div class="chart-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-cubes me-2"></i>My Assigned Modules<?php echo !empty($selectedAcademicYear) ? ' &middot; ' . $e($selectedAcademicYear) : ''; ?></span>
                    <span class="badge bg-light text-dark"><?php echo count($my['modules']); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($my['modules'])): ?>
                        <p class="text-muted text-center py-4 mb-0">No modules assigned.</p>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height: 320px;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Module</th><th>Course</th></tr></thead>
                            <tbody>
                                <?php foreach ($my['modules'] as $mm): ?>
                                <tr>
                                    <td><strong><?php echo $e($mm['module_name'] ?? $mm['module_id']); ?></strong><div class="text-muted small"><?php echo $e($mm['module_id']); ?></div></td>
                                    <td><?php echo $e($mm['course_name'] ?? $mm['course_id']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card dashboard-chart-card h-100">
                <div class="chart-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-calendar-week me-2"></i>My Time Table</span>
                    <span class="badge bg-light text-dark"><?php echo count($my['timetable']); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($my['timetable'])): ?>
                        <p class="text-muted text-center py-4 mb-0">No classes scheduled.</p>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height: 320px;">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Day</th><th>Period</th><th>Group</th><th>Subject</th><th>Room</th></tr></thead>
                            <tbody>
                                <?php foreach ($my['timetable'] as $tt): ?>
                                <tr class="<?php echo (int) $tt['weekday'] === $today ? 'table-primary' : ''; ?>">
                                    <td><?php echo $e($dayNames[(int) $tt['weekday']] ?? ''); ?><?php echo (int) $tt['weekday'] === $today ? ' <span class="badge bg-primary">Today</span>' : ''; ?></td>
                                    <td><?php echo $e(str_replace('P', '', (string) $tt['period'])); ?></td>
                                    <td><a href="<?php echo APP_URL; ?>/groups/timetable?id=<?php echo (int) $tt['group_id']; ?>"><?php echo $e($tt['group_name'] ?? '-'); ?></a></td>
                                    <td><?php echo $e(($tt['module_name'] ?? '') !== '' ? $tt['module_name'] : ($tt['subject'] ?? '-')); ?></td>
                                    <td><?php echo $e($tt['classroom'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card dashboard-chart-card h-100">
                <div class="chart-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-route me-2"></i>Upcoming Programmes</span>
                    <a class="btn btn-sm btn-light" href="<?php echo APP_URL; ?>/circuit-program">View all</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($my['programs'])): ?>
                        <p class="text-muted text-center py-4 mb-0">No upcoming programmes.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($my['programs'] as $pg): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <a href="<?php echo APP_URL; ?>/circuit-program/view?id=<?php echo (int) $pg['program_id']; ?>"><strong><?php echo $e($pg['destination']); ?></strong></a>
                                <?php if (!empty($pg['purpose'])): ?><div class="text-muted small"><?php echo $e($pg['purpose']); ?></div><?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="small fw-semibold"><?php echo $e(date('d M Y', strtotime($pg['date']))); ?></div>
                                <span class="badge bg-<?php echo ($pg['status'] ?? '') === 'approved' ? 'success' : 'warning text-dark'; ?>"><?php echo $e($humanize($pg['status'] ?? 'pending')); ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card dashboard-chart-card h-100">
                <div class="chart-card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-ticket-alt me-2"></i>Assigned Tickets<?php echo $my['ticket_total'] > 0 ? ' (' . (int) $my['ticket_total'] . ')' : ''; ?></span>
                    <a class="btn btn-sm btn-light" href="<?php echo APP_URL; ?>/facilities/tasks">View all</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($my['tickets'])): ?>
                        <p class="text-muted text-center py-4 mb-0">No tickets assigned.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($my['tickets'] as $tk): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <a href="<?php echo APP_URL; ?>/facilities/view?id=<?php echo (int) $tk['id']; ?>"><strong><?php echo $e($tk['ticket_number']); ?></strong></a>
                                <span class="ms-1"><?php echo $e($tk['title']); ?></span>
                                <div class="text-muted small"><?php echo $e($humanize($tk['status'])); ?><?php echo !empty($tk['deadline']) ? ' &middot; Due ' . $e($tk['deadline']) : ''; ?></div>
                            </div>
                            <span class="badge bg-<?php echo $prioClass[$tk['current_priority'] ?? ''] ?? 'secondary'; ?>"><?php echo $e($humanize($tk['current_priority'] ?? '')); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Courses & Modules - Department Only -->
    <?php if (!empty($department)): ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card dashboard-chart-card">
                <div class="chart-card-header">
                    <i class="fas fa-cubes me-2"></i>Courses &amp; Modules - <?php echo $e($department['department_name'] ?? 'Department'); ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($courseModuleRows)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm cm-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Course</th>
                                    <th>NVQ</th>
                                    <th>Status</th>
                                    <th class="text-center">Modules</th>
                                    <th class="text-center">Groups</th>
                                    <th class="text-end">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courseModuleRows as $cr): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $e($cr['course_name']); ?></strong>
                                        <div class="text-muted small"><?php echo $e($cr['course_id']); ?></div>
                                    </td>
                                    <td><?php echo $cr['course_nvq_level'] !== '' ? 'Level ' . $e($cr['course_nvq_level']) : '-'; ?></td>
                                    <td><span class="badge bg-<?php echo $cr['course_status'] === 'active' ? 'success' : ($cr['course_status'] === 'draft' ? 'warning text-dark' : 'secondary'); ?>"><?php echo $e(ucfirst($cr['course_status'])); ?></span></td>
                                    <td class="text-center"><span class="badge" style="background:#003366;"><?php echo (int) $cr['modules']; ?></span></td>
                                    <td class="text-center"><?php echo (int) $cr['groups']; ?></td>
                                    <td class="text-end" style="white-space: nowrap;">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo APP_URL; ?>/modules?course_id=<?php echo urlencode($cr['course_id']); ?>" title="View the modules of this course"><i class="fas fa-cubes"></i> Modules</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo APP_URL; ?>/courses/manage?id=<?php echo urlencode($cr['course_id']); ?>" title="Course workspace"><i class="fas fa-sliders-h"></i> Manage</a>
                                        <?php if (!empty($canManageCourses)): ?>
                                        <a class="btn btn-sm btn-outline-success" href="<?php echo APP_URL; ?>/modules/create?course_id=<?php echo urlencode($cr['course_id']); ?>" title="Add a module"><i class="fas fa-plus"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No courses found in this department.</p>
                    </div>
                    <?php endif; ?>
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
