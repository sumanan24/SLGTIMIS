<?php
$e = $e ?? static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$aaNavActive = $aaNavActive ?? 'entrance';
$entranceUrl = rtrim(APP_URL, '/') . '/application-admission';
$interviewUrl = rtrim(APP_URL, '/') . '/application-admission/interviews';
$cutoffUrl = rtrim(APP_URL, '/') . '/application-admission/cutoffs';
$secondOptionUrl = rtrim(APP_URL, '/') . '/application-admission/second-option';
?>
<style>
.aa-module-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-bottom: 1rem;
    padding: 0.35rem;
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
}
.aa-module-nav a {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.9rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    text-decoration: none;
    border: 1px solid transparent;
}
.aa-module-nav a:hover {
    background: #fff;
    color: #0d6efd;
}
.aa-module-nav a.is-active {
    background: #fff;
    color: #0d6efd;
    border-color: #cfe2ff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
</style>
<nav class="aa-module-nav" aria-label="Admission schedules">
    <a class="<?php echo $aaNavActive === 'entrance' ? 'is-active' : ''; ?>" href="<?php echo $e($entranceUrl); ?>">
        <i class="fas fa-clipboard-list"></i> Entrance exams
    </a>
    <a class="<?php echo $aaNavActive === 'interview' ? 'is-active' : ''; ?>" href="<?php echo $e($interviewUrl); ?>">
        <i class="fas fa-comments"></i> Interviews
    </a>
    <a class="<?php echo $aaNavActive === 'cutoff' ? 'is-active' : ''; ?>" href="<?php echo $e($cutoffUrl); ?>">
        <i class="fas fa-filter"></i> Cutoff marks
    </a>
    <a class="<?php echo $aaNavActive === 'second-option' ? 'is-active' : ''; ?>" href="<?php echo $e($secondOptionUrl); ?>">
        <i class="fas fa-user-check"></i> 2nd option
    </a>
</nav>
