<?php
$e = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$dash = static function (?string $s): string {
    $s = trim((string) $s);
    return $s === '' ? '—' : htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};
$recentPayments = $recentPayments ?? [];
$busSeasonPayments = $busSeasonPayments ?? [];
$currentGroup = $currentGroup ?? null;
$student = $student ?? [];
$currentEnrollment = $currentEnrollment ?? null;
$hostelAllocation = $hostelAllocation ?? null;
$roommates = $roommates ?? [];
$hasAcceptedConduct = !empty($hasAcceptedConduct);

require_once BASE_PATH . '/models/StudentModel.php';
$studentModelHelper = new StudentModel();
$profileImageUrl = $studentModelHelper->getProfileImagePath($student);

$modeRaw = (string) ($currentEnrollment['course_mode'] ?? '');
$modeLabel = $modeRaw === 'Part' || strcasecmp($modeRaw, 'Part Time') === 0
    ? 'Part Time'
    : ($modeRaw === 'Full' || strcasecmp($modeRaw, 'Full Time') === 0 ? 'Full Time' : ($modeRaw !== '' ? $modeRaw : '—'));
$status = (string) ($student['student_status'] ?? 'Active');
$statusClass = strcasecmp($status, 'Active') === 0 ? 'success' : 'warning';
$enrollStatus = (string) ($currentEnrollment['student_enroll_status'] ?? '');
$phone = $student['student_phone'] ?? '';
$phone = ($phone === '' || $phone === '0') ? '' : (string) $phone;
$addressParts = array_filter([
    trim((string) ($student['student_address'] ?? '')),
    trim((string) ($student['student_district'] ?? '')),
    trim((string) ($student['student_provice'] ?? '')),
]);
$address = $addressParts !== [] ? implode(', ', $addressParts) : '';
?>
<style>
.sd-page { width: 100%; max-width: 100%; overflow-x: hidden; }
.sd-id {
    background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
    color: #fff;
    border-radius: 14px;
    padding: 1rem 1.15rem;
}
.sd-id-row { display: flex; align-items: center; gap: .85rem; min-width: 0; }
.sd-photo {
    width: 64px; height: 64px; object-fit: cover; border-radius: 50%;
    border: 2px solid rgba(255,255,255,.85); background: rgba(255,255,255,.15); flex-shrink: 0;
}
.sd-photo-fallback { display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.sd-id h1 { font-size: 1.05rem; font-weight: 700; margin: 0 0 .15rem; overflow-wrap: anywhere; line-height: 1.25; }
.sd-id .sd-idno { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .8rem; opacity: .9; overflow-wrap: anywhere; }
.sd-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .65rem; }
.sd-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
    border-radius: 999px; padding: .2rem .6rem; font-size: .7rem; max-width: 100%;
}
.sd-notice {
    display: flex; gap: .75rem; align-items: flex-start;
    background: #fff; border: 1px solid #e6eaf0; border-radius: 12px; padding: .85rem 1rem;
}
.sd-notice .ic { color: #001f3f; font-size: 1.1rem; margin-top: .1rem; flex-shrink: 0; }
.sd-notice p { margin: 0; font-size: .8rem; color: #5c6570; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.sd-card {
    background: #fff; border: 1px solid #e6eaf0; border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,31,63,.06); height: 100%;
}
.sd-card h2 {
    font-size: .9rem; font-weight: 700; color: #001f3f; margin: 0 0 .75rem;
    padding-bottom: .5rem; border-bottom: 1px solid #eef1f4;
}
.sd-dl { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem 1.25rem; align-items: start; }
.sd-dl dt { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; margin: 0 0 .1rem; font-weight: 600; }
.sd-dl dd { margin: 0; font-size: .88rem; font-weight: 600; color: #212529; overflow-wrap: anywhere; word-break: break-word; }
.sd-stat { text-align: center; padding: .85rem .5rem; display: flex; flex-direction: column; justify-content: center; }
.sd-stat .n { font-size: 1.45rem; font-weight: 700; color: #001f3f; line-height: 1.1; }
.sd-stat .l { font-size: .65rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; margin-top: .3rem; }
.sd-dock {
    display: flex; flex-wrap: wrap; gap: .5rem;
}
.sd-dock a {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .35rem; padding: .7rem .3rem; min-height: 76px;
    flex: 1 1 calc(25% - .5rem);
    border: 1px solid #e6eaf0; border-radius: 12px; text-decoration: none;
    color: #212529; background: #fff;
}
.sd-dock a:hover, .sd-dock a:focus-visible { border-color: #001f3f; color: #001f3f; }
.sd-dock .ic {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    background: #001f3f; color: #fff; font-size: .95rem; flex-shrink: 0;
}
.sd-dock a > span:not(.ic) { font-size: .7rem; font-weight: 600; text-align: center; line-height: 1.2; }
.sd-dl .grid-span { grid-column: 1 / -1; }
.sd-cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; width: 100%; }
.sd-cal-hd { text-align: center; font-size: .7rem; font-weight: 700; color: #6c757d; padding-bottom: .25rem; }
.attendance-calendar-day {
    width: 100%; aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    border-radius: 8px; font-weight: 600; font-size: clamp(.65rem, 2.6vw, .85rem); min-height: 0;
}
.sd-pay td { vertical-align: middle; white-space: nowrap; }
.sd-pay td:nth-child(2) { white-space: normal; word-break: break-word; }
.attendance-present { background: #d4edda; color: #155724; }
.attendance-absent { background: #f8d7da; color: #721c24; }
.attendance-holiday { background: #fff3cd; color: #856404; }
.attendance-empty { background: #e9ecef; color: #6c757d; }
.attendance-weekend { background: #f8f9fa; color: #adb5bd; }
@media (min-width: 768px) {
    .sd-id {
        display: flex; align-items: center; justify-content: space-between; gap: 1.25rem;
        padding: 1.25rem 1.5rem;
    }
    .sd-id-row { flex: 1 1 auto; }
    .sd-chips { margin-top: 0; justify-content: flex-end; max-width: 52%; }
    .sd-photo { width: 84px; height: 84px; }
    .sd-id h1 { font-size: 1.35rem; }
    .sd-dock a { flex: 1 1 calc(14.28% - .5rem); min-height: 92px; }
}
@media (max-width: 767.98px) {
    .sd-dl { grid-template-columns: 1fr; gap: 0; }
    .sd-dl .grid-span { grid-column: auto; }
    .sd-dl > div {
        display: grid; grid-template-columns: 38% minmax(0, 1fr); gap: .5rem; align-items: center;
        padding: .5rem 0; border-bottom: 1px solid #f1f3f6;
    }
    .sd-dl > div:last-child { border-bottom: 0; }
    .sd-dl dt { margin: 0; }
    .sd-dl dd { font-size: .82rem; }
    .sd-card:not(.sd-stat) { padding: .9rem !important; }
    .sd-cal .attendance-calendar-day i { display: none; }
    .sd-stat .n { font-size: 1.25rem; }
    .sd-dock a { min-width: calc(25% - .5rem); }
}
@media (max-width: 575.98px) {
    .sd-id { padding: .9rem 1rem; border-radius: 12px; }
    .sd-photo { width: 56px; height: 56px; }
    .sd-chip { font-size: .65rem; }
}
</style>

<div class="sd-page">
    <div class="sd-id mb-3">
        <div class="sd-id-row">
            <?php if ($profileImageUrl): ?>
                <img src="<?php echo $e($profileImageUrl); ?>" alt="Student photo" class="sd-photo">
            <?php else: ?>
                <div class="sd-photo sd-photo-fallback"><i class="fas fa-user-graduate"></i></div>
            <?php endif; ?>
            <div class="min-w-0">
                <h1><?php echo $dash($student['student_fullname'] ?? ''); ?></h1>
                <div class="sd-idno"><?php echo $dash($student['student_id'] ?? ''); ?></div>
            </div>
        </div>
        <div class="sd-chips">
            <span class="sd-chip"><?php echo $dash($status); ?></span>
            <span class="sd-chip">NIC <?php echo $dash($student['student_nic'] ?? ''); ?></span>
            <?php if ($currentEnrollment): ?>
                <span class="sd-chip d-none d-sm-inline-flex"><?php echo $dash($currentEnrollment['course_name'] ?? $currentEnrollment['course_id'] ?? ''); ?></span>
                <span class="sd-chip"><?php echo $dash($currentEnrollment['academic_year'] ?? ''); ?></span>
                <span class="sd-chip"><?php echo $e($modeLabel); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-3 align-items-stretch">
        <div class="col-lg-6">
            <div class="sd-card p-3 p-md-4">
                <h2><i class="fas fa-id-badge me-2"></i>Student information</h2>
                <dl class="sd-dl mb-0">
                    <div>
                        <dt>Full name</dt>
                        <dd><?php echo $dash($student['student_fullname'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Student ID</dt>
                        <dd><?php echo $dash($student['student_id'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>NIC</dt>
                        <dd><?php echo $dash($student['student_nic'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $dash($status); ?></span></dd>
                    </div>
                    <div>
                        <dt>Gender</dt>
                        <dd><?php echo $dash($student['student_gender'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Date of birth</dt>
                        <dd><?php echo $dash($student['student_dob'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><?php echo $dash($student['student_email'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd><?php echo $dash($phone); ?></dd>
                    </div>
                    <div>
                        <dt>WhatsApp</dt>
                        <dd><?php echo $dash($student['student_whatsapp'] ?? ''); ?></dd>
                    </div>
                    <div class="grid-span">
                        <dt>Address</dt>
                        <dd><?php echo $dash($address); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="sd-card p-3 p-md-4">
                <h2><i class="fas fa-university me-2"></i>Academic enrollment</h2>
                <?php if ($currentEnrollment): ?>
                <dl class="sd-dl mb-0">
                    <div>
                        <dt>Course / programme</dt>
                        <dd><?php echo $dash($currentEnrollment['course_name'] ?? $currentEnrollment['course_id'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Department</dt>
                        <dd><?php echo $dash($currentEnrollment['department_name'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Academic year</dt>
                        <dd><?php echo $dash($currentEnrollment['academic_year'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Shift / mode</dt>
                        <dd><?php echo $e($modeLabel); ?></dd>
                    </div>
                    <div>
                        <dt>Group / batch</dt>
                        <dd><?php echo $dash($currentGroup['name'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>Enrollment status</dt>
                        <dd><?php echo $dash($enrollStatus); ?></dd>
                    </div>
                    <div>
                        <dt>Enrolled on</dt>
                        <dd><?php echo $dash($currentEnrollment['student_enroll_date'] ?? ''); ?></dd>
                    </div>
                    <div>
                        <dt>NVQ level</dt>
                        <dd><?php echo $dash($currentEnrollment['course_nvq_level'] ?? ''); ?></dd>
                    </div>
                </dl>
                <?php else: ?>
                    <p class="text-muted mb-0">No current enrollment is linked to this account.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="sd-dock mb-3" aria-label="Student shortcuts">
        <a href="<?php echo APP_URL; ?>/student/profile"><span class="ic"><i class="fas fa-user"></i></span><span>Profile</span></a>
        <a href="<?php echo APP_URL; ?>/student/attendance"><span class="ic"><i class="fas fa-calendar-check"></i></span><span>Attendance</span></a>
        <a href="<?php echo APP_URL; ?>/student/payments"><span class="ic"><i class="fas fa-money-bill-wave"></i></span><span>Payments</span></a>
        <a href="<?php echo APP_URL; ?>/student/notices"><span class="ic"><i class="fas fa-bullhorn"></i></span><span>Notices</span></a>
        <a href="<?php echo APP_URL; ?>/student/documents-pdf" target="_blank" rel="noopener"><span class="ic"><i class="fas fa-file-pdf"></i></span><span>Documents</span></a>
        <a href="<?php echo APP_URL; ?>/student/forms"><span class="ic"><i class="fas fa-file-alt"></i></span><span>Forms</span></a>
        <a href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><span class="ic"><i class="fas fa-key"></i></span><span>Password</span></a>
    </nav>

    <?php $studentNotices = is_array($studentNotices ?? null) ? $studentNotices : []; $firstNotice = $studentNotices[0] ?? null; ?>
    <?php if ($firstNotice): ?>
    <a href="<?php echo APP_URL; ?>/student/notices" class="sd-notice mb-3 text-decoration-none">
        <span class="ic"><i class="fas fa-bullhorn"></i></span>
        <div class="min-w-0">
            <div class="fw-semibold text-dark small mb-1"><?php echo $e($firstNotice['title'] ?? 'SLGTI notice'); ?></div>
            <p><?php echo $e($firstNotice['body'] ?? ''); ?></p>
            <div class="small mt-1" style="color:#001f3f;">View notices, code of conduct &amp; rules</div>
        </div>
    </a>
    <?php endif; ?>

    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        <div class="col-6 col-md-3"><div class="sd-card sd-stat"><div class="n"><?php echo (int) ($attendancePercentage ?? 0); ?>%</div><div class="l">Attendance this month</div></div></div>
        <div class="col-6 col-md-3"><div class="sd-card sd-stat"><div class="n text-success"><?php echo (int) ($presentDays ?? 0); ?></div><div class="l">Present / <?php echo (int) ($totalDays ?? 0); ?> days</div></div></div>
        <div class="col-6 col-md-3"><div class="sd-card sd-stat"><div class="n text-danger"><?php echo (int) ($absentDays ?? 0); ?></div><div class="l">Absent days</div></div></div>
        <div class="col-6 col-md-3"><div class="sd-card sd-stat"><div class="n text-warning"><?php echo (int) ($holidayDays ?? 0); ?></div><div class="l">Holidays</div></div></div>
    </div>

    <div class="row g-3 mb-3 mb-md-4 align-items-stretch">
        <div class="col-lg-6">
            <div class="sd-card p-3 p-md-4">
                <h2><i class="fas fa-bed me-2"></i>Hostel</h2>
                <?php if ($hostelAllocation): ?>
                    <dl class="sd-dl mb-3">
                        <div><dt>Hostel</dt><dd><?php echo $dash($hostelAllocation['hostel_name'] ?? ''); ?></dd></div>
                        <div><dt>Room</dt><dd><?php echo $dash($hostelAllocation['room_no'] ?? ''); ?></dd></div>
                    </dl>
                    <div class="small text-muted mb-1">Roommates</div>
                    <?php if (!empty($roommates)): ?>
                        <ul class="list-unstyled mb-0 small">
                            <?php foreach ($roommates as $rm): ?>
                                <li class="mb-1"><?php echo $dash($rm['student_fullname'] ?? ''); ?> <span class="text-muted"><?php echo $dash($rm['student_id'] ?? ''); ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No other active roommates.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No hostel allocation.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="sd-card p-3 p-md-4">
                <h2><i class="fas fa-money-bill-wave me-2"></i>Recent payments</h2>
                <h3 class="h6 text-muted">Bus season</h3>
                <?php if (!empty($busSeasonPayments)): ?>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm mb-0 sd-pay">
                            <tbody>
                            <?php foreach (array_slice($busSeasonPayments, 0, 4) as $p): ?>
                                <tr>
                                    <td><?php echo $e(!empty($p['payment_date']) ? date('d M Y', strtotime($p['payment_date'])) : '—'); ?></td>
                                    <td>Rs. <?php echo number_format($p['paid_amount'] ?? 0, 2); ?></td>
                                    <td><?php echo $dash($p['status'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted small">No bus season payments.</p>
                <?php endif; ?>
                <h3 class="h6 text-muted">Other / hostel</h3>
                <?php if (!empty($recentPayments)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 sd-pay">
                            <tbody>
                            <?php foreach ($recentPayments as $p): ?>
                                <tr>
                                    <td><?php echo $e(!empty($p['pays_date']) ? date('d M Y', strtotime($p['pays_date'])) : '—'); ?></td>
                                    <td><?php echo $dash($p['payment_reason'] ?? ''); ?></td>
                                    <td>Rs. <?php echo number_format($p['pays_amount'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">No other payments recorded.</p>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>/student/payments" class="small">View all payments</a>
            </div>
        </div>
    </div>

    <div class="sd-card p-3 p-md-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
            <h2 class="mb-0 border-0 pb-0"><i class="fas fa-calendar-check me-2"></i>Attendance — <?php echo date('F Y', strtotime(($currentMonth ?? date('Y-m')) . '-01')); ?></h2>
            <a href="<?php echo APP_URL; ?>/student/attendance" class="btn btn-sm btn-outline-primary">Full calendar</a>
        </div>
        <div class="calendar-container">
            <?php
            $currentMonth = $currentMonth ?? date('Y-m');
            $attendanceRecords = $attendanceRecords ?? [];
            $firstDay = strtotime($currentMonth . '-01');
            $daysInMonth = (int) date('t', $firstDay);
            $firstDayOfWeek = (int) date('w', $firstDay);
            $weekDays = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
            $weekDaysFull = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            ?>
            <div class="sd-cal">
                <?php foreach ($weekDaysFull as $i => $day): ?>
                    <div class="sd-cal-hd"><span class="d-none d-sm-inline"><?php echo $day; ?></span><span class="d-sm-none"><?php echo $weekDays[$i]; ?></span></div>
                <?php endforeach; ?>
                <?php
                for ($i = 0; $i < $firstDayOfWeek; $i++) {
                    echo '<div></div>';
                }
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = $currentMonth . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                    $dayOfWeek = (int) date('w', strtotime($date));
                    $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
                    $st = $attendanceRecords[$date] ?? null;
                    $class = 'attendance-empty';
                    $icon = '';
                    if ($isWeekend) {
                        $class = 'attendance-weekend';
                    } elseif ($st === 1) {
                        $class = 'attendance-present';
                        $icon = '<i class="fas fa-check"></i> ';
                    } elseif ($st === 0) {
                        $class = 'attendance-absent';
                        $icon = '<i class="fas fa-times"></i> ';
                    } elseif ($st === -1) {
                        $class = 'attendance-holiday';
                        $icon = '<i class="fas fa-star"></i> ';
                    }
                    echo '<div class="attendance-calendar-day ' . $class . '" title="' . $e(date('l, F j, Y', strtotime($date))) . '">' . $icon . $day . '</div>';
                }
                ?>
            </div>
        </div>
        <div class="mt-3 d-flex flex-wrap gap-2 gap-md-3 justify-content-center small text-muted">
            <span class="d-flex align-items-center gap-1"><span class="attendance-calendar-day attendance-present" style="width:22px;height:22px;aspect-ratio:auto;"><i class="fas fa-check"></i></span> Present</span>
            <span class="d-flex align-items-center gap-1"><span class="attendance-calendar-day attendance-absent" style="width:22px;height:22px;aspect-ratio:auto;"><i class="fas fa-times"></i></span> Absent</span>
            <span class="d-flex align-items-center gap-1"><span class="attendance-calendar-day attendance-holiday" style="width:22px;height:22px;aspect-ratio:auto;"><i class="fas fa-star"></i></span> Holiday</span>
            <span class="d-flex align-items-center gap-1"><span class="attendance-calendar-day attendance-weekend" style="width:22px;height:22px;aspect-ratio:auto;"></span> Weekend</span>
        </div>
    </div>
</div>

<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo APP_URL; ?>/student/change-password" id="changePasswordForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-key me-2 text-danger"></i>Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="old_password" class="form-label fw-semibold">Old Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="old_password" name="old_password" required autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="old_password" aria-label="Show password" title="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="new_password" aria-label="Show password" title="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="form-text">Minimum 8 characters, must include 1 capital, 1 small, and 1 number.</div>
                    </div>
                    <div class="mb-0">
                        <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary st-pw-toggle" data-target="confirm_password" aria-label="Show password" title="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save me-1"></i>Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$hasAcceptedConduct): ?>
<div class="modal fade" id="conductModal" tabindex="-1" aria-labelledby="conductModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="conductModalLabel"><i class="fas fa-file-contract me-2"></i>SLGTI STUDENT CODE OF CONDUCT</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>Important:</strong> Please read the following declaration carefully before accepting.</div>
                <div class="border rounded p-4 mb-3" style="background-color: #f8f9fa; max-height: 400px; overflow-y: auto;">
                    <h6 class="fw-bold mb-3">SLGTI Student Code of Conduct and Honor</h6>
                    <p>I hereby confirm that I have read, understood, and agreed to comply with the SLGTI Student Code of Conduct and Honor, including all rules, regulations, policies, and procedures of the Sri Lanka–German Training Institute (SLGTI). I acknowledge my responsibility to maintain discipline, academic integrity, professional conduct, and respect for all members of the SLGTI community and its property. I understand that this Code applies to my conduct on campus, off campus, and during all SLGTI-authorized activities, including industrial training and On-the-Job Training (OJT). I further understand that any violation of this Code may result in disciplinary action in accordance with SLGTI regulations, including warnings, suspension, or expulsion. By submitting this declaration electronically, I confirm that this acceptance is legally binding and equivalent to my handwritten signature.</p>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="agreeCheckbox" required>
                    <label class="form-check-label" for="agreeCheckbox"><strong>I agree to the SLGTI Student Code of Conduct and Honor</strong></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="acceptConductBtn" disabled><i class="fas fa-check me-2"></i>Accept &amp; Continue</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (!$hasAcceptedConduct): ?>
document.addEventListener('DOMContentLoaded', function() {
    const conductModal = new bootstrap.Modal(document.getElementById('conductModal'));
    conductModal.show();
    const agreeCheckbox = document.getElementById('agreeCheckbox');
    const acceptBtn = document.getElementById('acceptConductBtn');
    agreeCheckbox.addEventListener('change', function() { acceptBtn.disabled = !this.checked; });
    acceptBtn.addEventListener('click', function() {
        if (!agreeCheckbox.checked) { alert('Please check the agreement box to continue.'); return; }
        acceptBtn.disabled = true;
        const originalText = acceptBtn.innerHTML;
        acceptBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        fetch('<?php echo rtrim(APP_URL, '/'); ?>/student/accept-conduct', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: JSON.stringify({})
        })
        .then(async response => {
            const text = await response.text();
            let data;
            try { data = text ? JSON.parse(text) : {}; } catch (e) { throw new Error('Server returned an invalid response. Please refresh and try again.'); }
            if (!response.ok && !data.error) { data.error = 'Request failed (' + response.status + ').'; }
            return data;
        })
        .then(data => {
            if (data.success) {
                conductModal.hide();
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || 'Code of conduct accepted successfully!') + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                const page = document.querySelector('.sd-page');
                if (page) page.insertBefore(alertDiv, page.firstChild);
            } else {
                alert('Error: ' + (data.error || 'Failed to accept code of conduct. Please try again.'));
                acceptBtn.disabled = false;
                acceptBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            alert(error.message || 'An error occurred. Please try again.');
            acceptBtn.disabled = false;
            acceptBtn.innerHTML = originalText;
        });
    });
});
<?php endif; ?>

document.querySelectorAll('.st-pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-target'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        }
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.setAttribute('title', show ? 'Hide password' : 'Show password');
    });
});

(function() {
    const form = document.getElementById('changePasswordForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        const newPass = document.getElementById('new_password');
        const confirmPass = document.getElementById('confirm_password');
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        if (newPass && confirmPass && newPass.value !== confirmPass.value) {
            e.preventDefault();
            e.stopPropagation();
            confirmPass.setCustomValidity('Passwords do not match');
            confirmPass.classList.add('is-invalid');
        } else if (confirmPass) {
            confirmPass.setCustomValidity('');
        }
        form.classList.add('was-validated');
    });
})();
</script>
