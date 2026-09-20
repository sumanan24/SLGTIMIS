<link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/staff.css?v=<?php echo time(); ?>">
<?php
$h = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$staff = $staff ?? [];
$sid = $staff['staff_id'] ?? '';
$base = APP_URL . '/staff/edit?id=' . urlencode($sid);
$fileBase = APP_URL . '/staff/file';
$activeTab = $activeTab ?? 'personal';
$canEditFile = !empty($canEditFile);
$canAddFile = !empty($canAddFile);
$canDeleteFile = !empty($canDeleteFile);
$canUploadFile = !empty($canUploadFile);
$canDownloadFile = !empty($canDownloadFile);
$canApproveFile = !empty($canApproveFile);
$canEditStaffAccess = !empty($canEditStaffAccess);
$fileLabels = $fileLabels ?? [];
$photoUrl = !empty($staff['staff_photo_path'])
    ? APP_URL . '/staff/file/photo?id=' . urlencode($sid)
    : '';

$tabs = [
    'personal' => ['Personal', 'fa-user', 'Personal information'],
    'contact' => ['Contact', 'fa-address-book', 'Contact information'],
    'employment' => ['Employment', 'fa-briefcase', 'Employment information'],
    'qualifications' => ['Education', 'fa-graduation-cap', 'Education and qualifications'],
    'training' => ['Training', 'fa-chalkboard-teacher', 'Training and professional development'],
    'service' => ['Service', 'fa-history', 'Service history'],
    'attendance' => ['Attendance', 'fa-calendar-check', 'Attendance and leave'],
    'documents' => ['Documents', 'fa-folder-open', 'Personal file documents'],
    'payroll' => ['Payroll', 'fa-university', 'Bank and payroll'],
    'family' => ['Family', 'fa-users', 'Family and nominee'],
    'administrative' => ['Admin', 'fa-gavel', 'Administrative records'],
    'access' => ['Access', 'fa-shield-alt', 'Menu and module permissions'],
];

$opt = static function ($map, $selected) use ($h) {
    $html = '';
    foreach ($map as $value => $label) {
        $sel = ((string) $selected === (string) $value) ? ' selected' : '';
        $html .= '<option value="' . $h($value) . '"' . $sel . '>' . $h($label) . '</option>';
    }
    return $html;
};
$labelOf = static function ($group, $key) use ($fileLabels) {
    return $fileLabels[$group][$key] ?? $key;
};
?>

<div class="container-fluid px-0 st-page st-file-page">
    <div class="card border-0">
        <div class="card-header d-flex justify-content-between align-items-center gap-3">
            <div class="st-file-heading">
                <h5 class="fw-bold mb-0"><i class="fas fa-folder-open me-2"></i>Staff Personal File</h5>
                <p class="st-file-sub mb-0">
                    <?php echo $h($staff['staff_name'] ?? $sid); ?>
                    <span><?php echo $h($sid); ?></span>
                </p>
            </div>
            <a href="<?php echo APP_URL; ?>/staff" class="btn btn-sm btn-light flex-shrink-0">Back</a>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2"><?php echo $h($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2"><?php echo $h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <nav class="st-file-nav" aria-label="Personal file sections">
                <?php foreach ($tabs as $key => $meta):
                    if ($key === 'access' && !$canEditStaffAccess) {
                        continue;
                    }
                    $isActive = $activeTab === $key; ?>
                    <a class="st-file-nav-item <?php echo $isActive ? 'active' : ''; ?>"
                       href="<?php echo $base; ?>&tab=<?php echo $key; ?>"
                       title="<?php echo $h($meta[2]); ?>"
                       <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                        <i class="fas <?php echo $meta[1]; ?>" aria-hidden="true"></i>
                        <span><?php echo $h($meta[0]); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="st-file-panel">
            <?php if ($activeTab !== 'attendance'): ?>
            <div class="st-file-panel-head">
                <?php echo $h($tabs[$activeTab][2] ?? $tabs['personal'][2]); ?>
            </div>
            <?php endif; ?>

            <?php if ($activeTab === 'personal'): ?>
                <div class="st-file-grid">
                    <div class="st-photo-card">
                        <?php if ($photoUrl): ?>
                            <img src="<?php echo $h($photoUrl); ?>" alt="Profile photo">
                        <?php else: ?>
                            <div class="st-photo-empty"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        <?php if ($canUploadFile): ?>
                            <form method="POST" action="<?php echo APP_URL; ?>/staff/file/photo?id=<?php echo urlencode($sid); ?>" enctype="multipart/form-data" class="mt-2">
                                <input type="file" name="photo" accept="image/*" class="form-control form-control-sm mb-2" required>
                                <button class="btn btn-outline-primary btn-sm w-100" type="submit">Upload Photo</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?php echo $h($base); ?>" class="st-file-form">
                        <input type="hidden" name="update_section" value="personal">
                        <div class="st-grid-2">
                            <div>
                                <label class="form-label" for="staff_id">Staff ID / Employee No. <span class="text-danger">*</span></label>
                                <input class="form-control" id="staff_id" name="staff_id" maxlength="64" required value="<?php echo $h($sid); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                            </div>
                            <div>
                                <label class="form-label" for="finger_machine_no">Finger Machine Number</label>
                                <input class="form-control" id="finger_machine_no" name="finger_machine_no" maxlength="50" value="<?php echo $h($staff['finger_machine_no'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                            </div>
                        </div>
                        <div class="st-grid-2">
                            <div>
                                <label class="form-label" for="staff_name">Full Name <span class="text-danger">*</span></label>
                                <input class="form-control" id="staff_name" name="staff_name" maxlength="100" required value="<?php echo $h($staff['staff_name'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                            </div>
                            <div>
                                <label class="form-label" for="staff_ininame">Name with Initials</label>
                                <input class="form-control" id="staff_ininame" name="staff_ininame" maxlength="100" value="<?php echo $h($staff['staff_ininame'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                            </div>
                        </div>
                        <div class="st-grid-3">
                            <div>
                                <label class="form-label" for="staff_dob">Date of Birth</label>
                                <input type="date" class="form-control" id="staff_dob" name="staff_dob" value="<?php echo $h($staff['staff_dob'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                            </div>
                            <div>
                                <label class="form-label" for="staff_nic">NIC No. <span class="text-danger">*</span></label>
                                <input class="form-control" id="staff_nic" name="staff_nic" maxlength="20" required value="<?php echo $h($staff['staff_nic'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                            </div>
                            <div>
                                <label class="form-label" for="staff_gender">Gender</label>
                                <select class="form-select" id="staff_gender" name="staff_gender" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                    <option value="">Select</option>
                                    <?php echo $opt(['Male' => 'Male', 'Female' => 'Female', 'Transgender' => 'Transgender'], $staff['staff_gender'] ?? ''); ?>
                                </select>
                            </div>
                        </div>
                        <div class="st-grid-2">
                            <div>
                                <label class="form-label" for="staff_civil_status">Civil Status</label>
                                <select class="form-select" id="staff_civil_status" name="staff_civil_status" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                    <option value="">Select</option>
                                    <?php echo $opt(['Single' => 'Single', 'Married' => 'Married', 'Widowed' => 'Widowed', 'Divorced' => 'Divorced'], $staff['staff_civil_status'] ?? ''); ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="staff_nationality">Nationality</label>
                                <input class="form-control" id="staff_nationality" name="staff_nationality" maxlength="50" value="<?php echo $h($staff['staff_nationality'] ?? 'Sri Lankan'); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                            </div>
                        </div>
                        <?php if ($canEditFile): ?>
                            <div class="st-form-actions"><button class="btn btn-primary" type="submit">Save Personal Information</button></div>
                        <?php endif; ?>
                    </form>
                </div>

            <?php elseif ($activeTab === 'contact'): ?>
                <form method="POST" action="<?php echo $h($base); ?>">
                    <input type="hidden" name="update_section" value="contact">
                    <div class="st-grid-2">
                        <div>
                            <label class="form-label" for="staff_address">Permanent Address</label>
                            <textarea class="form-control" id="staff_address" name="staff_address" rows="2" maxlength="255" <?php echo $canEditFile ? '' : 'disabled'; ?>><?php echo $h($staff['staff_address'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label class="form-label" for="staff_current_address">Current Address</label>
                            <textarea class="form-control" id="staff_current_address" name="staff_current_address" rows="2" maxlength="255" <?php echo $canEditFile ? '' : 'disabled'; ?>><?php echo $h($staff['staff_current_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="st-grid-2">
                        <div>
                            <label class="form-label" for="staff_pno">Mobile</label>
                            <input class="form-control" id="staff_pno" name="staff_pno" maxlength="20" value="<?php echo $h($staff['staff_pno'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                        <div>
                            <label class="form-label" for="staff_email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="staff_email" name="staff_email" required value="<?php echo $h($staff['staff_email'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                    </div>
                    <div class="st-grid-3">
                        <div>
                            <label class="form-label" for="staff_emergency_name">Emergency Contact Name</label>
                            <input class="form-control" id="staff_emergency_name" name="staff_emergency_name" maxlength="100" value="<?php echo $h($staff['staff_emergency_name'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                        <div>
                            <label class="form-label" for="staff_emergency_phone">Emergency Contact Number</label>
                            <input class="form-control" id="staff_emergency_phone" name="staff_emergency_phone" maxlength="20" value="<?php echo $h($staff['staff_emergency_phone'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                        <div>
                            <label class="form-label" for="staff_emergency_relation">Relationship</label>
                            <input class="form-control" id="staff_emergency_relation" name="staff_emergency_relation" maxlength="50" value="<?php echo $h($staff['staff_emergency_relation'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                    </div>
                    <?php if ($canEditFile): ?>
                        <div class="st-form-actions"><button class="btn btn-primary" type="submit">Save Contact Information</button></div>
                    <?php endif; ?>
                </form>

            <?php elseif ($activeTab === 'employment'): ?>
                <form method="POST" action="<?php echo $h($base); ?>" id="stStaffForm">
                    <input type="hidden" name="update_section" value="employment">
                    <div class="st-grid-3">
                        <div>
                            <label class="form-label" for="staff_date_of_join">Appointment Date</label>
                            <input type="date" class="form-control" id="staff_date_of_join" name="staff_date_of_join" value="<?php echo $h($staff['staff_date_of_join'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                        <div>
                            <label class="form-label" for="finger_machine_no">Employee Number</label>
                            <input class="form-control" id="finger_machine_no" name="finger_machine_no" maxlength="50" value="<?php echo $h($staff['finger_machine_no'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                        <div>
                            <label class="form-label" for="staff_designation">Designation</label>
                            <input class="form-control" id="staff_designation" name="staff_designation" maxlength="100" value="<?php echo $h($staff['staff_designation'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                    </div>
                    <div class="st-grid-3">
                        <div>
                            <label class="form-label" for="staff_position">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="staff_position" name="staff_position" required <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <option value="">Select role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $h($role['staff_position_type_id']); ?>" <?php echo ($staff['staff_position'] ?? '') === $role['staff_position_type_id'] ? 'selected' : ''; ?>>
                                        <?php echo $h($role['staff_position_type_name']); ?> (<?php echo $h($role['staff_position_type_id']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="salary_grade">Grade</label>
                            <select class="form-select" id="salary_grade" name="salary_grade" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <option value="">Not assigned</option>
                                <?php echo $opt(['3' => 'Grade 3', '2' => 'Grade 2', '1' => 'Grade 1'], (string) ($staff['salary_grade'] ?? '')); ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="department_id">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="department_id" name="department_id" required <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <option value="">Select department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $h($dept['department_id']); ?>" <?php echo ($staff['department_id'] ?? '') === $dept['department_id'] ? 'selected' : ''; ?>>
                                        <?php echo $h($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="st-grid-3">
                        <div>
                            <label class="form-label" for="staff_type">Employment Type</label>
                            <select class="form-select" id="staff_type" name="staff_type" required <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <?php echo $opt(['Permanent' => 'Permanent', 'On Contract' => 'On Contract', 'Visiting Lecturer' => 'Visiting Lecturer'], $staff['staff_type'] ?? ''); ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="staff_confirmation_date">Confirmation Date</label>
                            <input type="date" class="form-control" id="staff_confirmation_date" name="staff_confirmation_date" value="<?php echo $h($staff['staff_confirmation_date'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                        <div>
                            <label class="form-label" for="staff_status">Current Employment Status</label>
                            <select class="form-select" id="staff_status" name="staff_status" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <?php echo $opt(['Working' => 'Working', 'Terminated' => 'Terminated', 'Resigned' => 'Resigned'], $staff['staff_status'] ?? 'Working'); ?>
                            </select>
                        </div>
                    </div>
                    <div class="st-grid-3">
                        <div>
                            <label class="form-label" for="staff_retirement_date">Retirement Date</label>
                            <input type="date" class="form-control" id="staff_retirement_date" name="staff_retirement_date" value="<?php echo $h($staff['staff_retirement_date'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                        </div>
                    </div>
                    <div class="st-section-label">Another appointment</div>
                    <p class="st-perm-note mb-2">One permanent post per person. Cover-up / Acting is for Permanent staff only.</p>
                    <?php $appointType = strtolower((string) ($staff['appoint_type'] ?? 'none')); ?>
                    <div class="st-grid-3 st-appoint" id="stAppointBlock">
                        <div>
                            <label class="form-label" for="appoint_type">Appointment Type</label>
                            <select class="form-select" id="appoint_type" name="appoint_type" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <?php echo $opt(['none' => 'None', 'cover_up' => 'Cover-up', 'acting' => 'Acting'], $appointType); ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="appoint_position">Position</label>
                            <select class="form-select" id="appoint_position" name="appoint_position" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <option value="">Select position</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $h($role['staff_position_type_id']); ?>" <?php echo (($staff['appoint_position'] ?? '') === $role['staff_position_type_id']) ? 'selected' : ''; ?>>
                                        <?php echo $h($role['staff_position_type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="appoint_department_id">Department</label>
                            <select class="form-select" id="appoint_department_id" name="appoint_department_id" <?php echo $canEditFile ? '' : 'disabled'; ?>>
                                <option value="">Same as primary</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $h($dept['department_id']); ?>" <?php echo (($staff['appoint_department_id'] ?? '') === $dept['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo $h($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($canEditFile): ?>
                        <div class="st-form-actions"><button class="btn btn-primary" type="submit">Save Employment Information</button></div>
                    <?php endif; ?>
                </form>

            <?php elseif (in_array($activeTab, ['qualifications', 'training', 'service', 'family', 'administrative'], true)):
                $kindMap = [
                    'qualifications' => ['qualification', 'qual_type', $qualifications ?? [], ['Type', 'Title', 'Institute', 'Year', 'Result']],
                    'training' => ['training', 'train_type', $trainings ?? [], ['Type', 'Title', 'Organizer', 'Start', 'End']],
                    'service' => ['service', 'event_type', $serviceHistory ?? [], ['Type', 'Title', 'From', 'To', 'Date']],
                    'family' => ['family', 'member_type', $familyMembers ?? [], ['Type', 'Name', 'Relationship', 'NIC', 'Phone']],
                    'administrative' => ['admin', 'record_type', $adminRecords ?? [], ['Type', 'Title', 'Date', 'Notes']],
                ];
                [$kind, $typeKey, $rows, $heads] = $kindMap[$activeTab];
                $typeOptions = $fileLabels[$typeKey] ?? [];
                ?>
                <?php if ($canAddFile): ?>
                <form method="POST" action="<?php echo $fileBase; ?>/save?id=<?php echo urlencode($sid); ?>" class="st-add-box mb-3">
                    <input type="hidden" name="kind" value="<?php echo $h($kind); ?>">
                    <div class="st-grid-3">
                        <div>
                            <label class="form-label">Type</label>
                            <select class="form-select" name="<?php echo $h($typeKey); ?>" required><?php echo $opt($typeOptions, ''); ?></select>
                        </div>
                        <?php if ($kind === 'family'): ?>
                            <div><label class="form-label">Full Name</label><input class="form-control" name="full_name" required></div>
                            <div><label class="form-label">Relationship</label><input class="form-control" name="relationship"></div>
                            <div><label class="form-label">NIC</label><input class="form-control" name="nic"></div>
                            <div><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
                            <div><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="dob"></div>
                        <?php elseif ($kind === 'admin'): ?>
                            <div><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                            <div><label class="form-label">Date</label><input type="date" class="form-control" name="record_date"></div>
                            <div class="st-span-3"><label class="form-label">Notes</label><input class="form-control" name="notes" maxlength="500"></div>
                        <?php elseif ($kind === 'training'): ?>
                            <div><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                            <div><label class="form-label">Organizer</label><input class="form-control" name="organizer"></div>
                            <div><label class="form-label">Start</label><input type="date" class="form-control" name="start_date"></div>
                            <div><label class="form-label">End</label><input type="date" class="form-control" name="end_date"></div>
                            <div><label class="form-label">Notes</label><input class="form-control" name="notes"></div>
                        <?php elseif ($kind === 'service'): ?>
                            <div><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                            <div><label class="form-label">From</label><input class="form-control" name="from_value"></div>
                            <div><label class="form-label">To</label><input class="form-control" name="to_value"></div>
                            <div><label class="form-label">Effective Date</label><input type="date" class="form-control" name="effective_date"></div>
                            <div><label class="form-label">Notes</label><input class="form-control" name="notes"></div>
                        <?php else: ?>
                            <div><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                            <div><label class="form-label">Institute</label><input class="form-control" name="institute"></div>
                            <div><label class="form-label">Year</label><input class="form-control" name="year_completed" maxlength="10"></div>
                            <div><label class="form-label">Result</label><input class="form-control" name="result"></div>
                            <div><label class="form-label">Notes</label><input class="form-control" name="notes"></div>
                        <?php endif; ?>
                    </div>
                    <div class="st-form-actions"><button class="btn btn-primary" type="submit">Add Record</button></div>
                </form>
                <?php endif; ?>
                <div class="st-table-wrap">
                    <table class="table st-table mb-0">
                        <thead><tr><?php foreach ($heads as $head): ?><th><?php echo $h($head); ?></th><?php endforeach; ?><?php if ($canDeleteFile): ?><th></th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" class="text-muted">No records yet.</td></tr>
                        <?php else: foreach ($rows as $row): ?>
                            <tr>
                                <?php if ($kind === 'family'): ?>
                                    <td><?php echo $h($labelOf('member_type', $row['member_type'] ?? '')); ?></td>
                                    <td><?php echo $h($row['full_name'] ?? ''); ?></td>
                                    <td><?php echo $h($row['relationship'] ?? ''); ?></td>
                                    <td><?php echo $h($row['nic'] ?? ''); ?></td>
                                    <td><?php echo $h($row['phone'] ?? ''); ?></td>
                                <?php elseif ($kind === 'admin'): ?>
                                    <td><?php echo $h($labelOf('record_type', $row['record_type'] ?? '')); ?></td>
                                    <td><?php echo $h($row['title'] ?? ''); ?></td>
                                    <td><?php echo $h($row['record_date'] ?? ''); ?></td>
                                    <td><?php echo $h($row['notes'] ?? ''); ?></td>
                                <?php elseif ($kind === 'training'): ?>
                                    <td><?php echo $h($labelOf('train_type', $row['train_type'] ?? '')); ?></td>
                                    <td><?php echo $h($row['title'] ?? ''); ?></td>
                                    <td><?php echo $h($row['organizer'] ?? ''); ?></td>
                                    <td><?php echo $h($row['start_date'] ?? ''); ?></td>
                                    <td><?php echo $h($row['end_date'] ?? ''); ?></td>
                                <?php elseif ($kind === 'service'): ?>
                                    <td><?php echo $h($labelOf('event_type', $row['event_type'] ?? '')); ?></td>
                                    <td><?php echo $h($row['title'] ?? ''); ?></td>
                                    <td><?php echo $h($row['from_value'] ?? ''); ?></td>
                                    <td><?php echo $h($row['to_value'] ?? ''); ?></td>
                                    <td><?php echo $h($row['effective_date'] ?? ''); ?></td>
                                <?php else: ?>
                                    <td><?php echo $h($labelOf('qual_type', $row['qual_type'] ?? '')); ?></td>
                                    <td><?php echo $h($row['title'] ?? ''); ?></td>
                                    <td><?php echo $h($row['institute'] ?? ''); ?></td>
                                    <td><?php echo $h($row['year_completed'] ?? ''); ?></td>
                                    <td><?php echo $h($row['result'] ?? ''); ?></td>
                                <?php endif; ?>
                                <?php if ($canDeleteFile): ?>
                                    <td><a class="btn btn-outline-danger btn-sm" href="<?php echo $fileBase; ?>/delete?id=<?php echo urlencode($sid); ?>&kind=<?php echo urlencode($kind); ?>&record=<?php echo (int) $row['id']; ?>" onclick="return confirm('Delete this record?');">Delete</a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($activeTab === 'service' && !empty($salaryHistory)): ?>
                    <div class="st-section-label">Salary revisions & increment history</div>
                    <div class="st-table-wrap">
                        <table class="table st-table mb-0">
                            <thead><tr><th>Effective</th><th>Basic</th><th>Gross</th><th>Note</th></tr></thead>
                            <tbody>
                            <?php foreach ($salaryHistory as $hist): ?>
                                <tr>
                                    <td><?php echo $h($hist['effective_date'] ?? ''); ?></td>
                                    <td><?php echo $h($hist['basic_salary'] ?? $hist['basic'] ?? ''); ?></td>
                                    <td><?php echo $h($hist['gross_salary'] ?? $hist['gross'] ?? ''); ?></td>
                                    <td><?php echo $h($hist['note'] ?? $hist['remarks'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php elseif ($activeTab === 'attendance'):
                $device = $deviceAttendance ?? ['finger_no' => '', 'month_label' => '', 'present' => 0, 'late' => 0, 'early' => 0, 'absent' => 0, 'holiday' => 0, 'rows' => [], 'dbError' => null];
                $leaveRows = $leaveRecords ?? [];
                $leaveMonthDays = $leaveMonthDays ?? 0;
                $attendanceMonth = $attendanceMonth ?? date('Y-m');
                $balances = $leaveBalances ?? ['year' => date('Y'), 'items' => []];
                $fingerNo = trim((string) ($device['finger_no'] ?? $staff['finger_machine_no'] ?? ''));
                $fmtN = static function ($n) {
                    $n = (float) $n;
                    return abs($n - round($n)) < 0.05 ? (string) (int) round($n) : number_format($n, 1);
                };
                $fmtD = static function ($d) {
                    $t = strtotime((string) $d);
                    return $t ? date('d M Y', $t) : (string) $d;
                };
                ?>
                <section class="st-sheet">
                    <header class="st-sheet-head">
                        <div class="st-sheet-title">
                            <h2><?php echo $h($device['month_label'] !== '' ? $device['month_label'] : date('F Y', strtotime($attendanceMonth . '-01'))); ?></h2>
                            <p>Official hours 08:40 – 16:15</p>
                        </div>
                        <form method="get" action="<?php echo APP_URL; ?>/staff/edit" class="st-sheet-month">
                            <input type="hidden" name="id" value="<?php echo $h($sid); ?>">
                            <input type="hidden" name="tab" value="attendance">
                            <label class="visually-hidden" for="stAttMonth">Month</label>
                            <input type="month" class="form-control" id="stAttMonth" name="month" value="<?php echo $h($attendanceMonth); ?>">
                            <button class="btn btn-primary" type="submit">Show</button>
                        </form>
                    </header>

                    <div class="st-sheet-card">
                        <div class="st-sheet-card-head"><h3>Leave balance — <?php echo $h((string) ($balances['year'] ?? date('Y'))); ?></h3></div>
                        <div class="st-table-wrap mb-0">
                            <table class="table st-table st-sheet-table st-bal-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Leave</th>
                                        <th class="text-end">Entitled</th>
                                        <th class="text-end">Availed</th>
                                        <th class="text-end">Pending</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($balances['items'])): ?>
                                    <tr><td colspan="5" class="text-muted">No leave entitlements.</td></tr>
                                <?php else: foreach ($balances['items'] as $bal): ?>
                                    <tr>
                                        <td><?php echo $h($bal['code']); ?> · <?php echo $h($bal['title']); ?><?php echo ($bal['key'] ?? '') === 'short' ? ' <span class="text-muted">(month)</span>' : ''; ?></td>
                                        <td class="text-end"><?php echo $h($fmtN($bal['entitled'])); ?></td>
                                        <td class="text-end"><?php echo $h($fmtN($bal['availed'])); ?></td>
                                        <td class="text-end"><?php echo $h($fmtN($bal['pending'])); ?></td>
                                        <td class="text-end st-bal-now"><?php echo $h($fmtN($bal['balance'])); ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="st-sheet-metrics">
                        <div><span>Present</span><strong><?php echo (int) ($device['present'] ?? 0); ?></strong></div>
                        <div><span>Late</span><strong><?php echo (int) ($device['late'] ?? 0); ?></strong></div>
                        <div><span>Leave</span><strong><?php echo $h($fmtN($leaveMonthDays)); ?></strong></div>
                        <div><span>Holiday</span><strong><?php echo (int) ($device['holiday'] ?? 0); ?></strong></div>
                        <div><span>Absent</span><strong><?php echo (int) ($device['absent'] ?? 0); ?></strong></div>
                    </div>

                    <?php if ($fingerNo === ''): ?>
                        <p class="st-perm-note mb-0">Set Finger Machine Number on Personal to load device attendance.</p>
                    <?php elseif (!empty($device['dbError'])): ?>
                        <div class="alert alert-danger py-2 mb-0"><?php echo $h($device['dbError']); ?></div>
                    <?php endif; ?>

                    <div class="st-sheet-card">
                        <div class="st-sheet-card-head"><h3>Attendance register</h3></div>
                        <div class="st-table-wrap mb-0">
                            <table class="table st-table st-sheet-table st-att-register mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Arrival</th>
                                        <th>Departure</th>
                                        <th>Late</th>
                                        <th>Early</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($fingerNo === ''): ?>
                                    <tr><td colspan="7" class="text-muted">No Finger Machine Number.</td></tr>
                                <?php elseif (empty($device['rows'])): ?>
                                    <tr><td colspan="7" class="text-muted">No records for this month.</td></tr>
                                <?php else: foreach ($device['rows'] as $row):
                                    $st = (string) ($row['status'] ?? ''); ?>
                                    <tr class="st-att-<?php echo $h($st); ?>">
                                        <td><?php echo $h($fmtD($row['date'] ?? '')); ?></td>
                                        <td><?php echo $h($row['day'] ?? ''); ?></td>
                                        <td><?php echo $h($row['in'] ?? '—'); ?></td>
                                        <td><?php echo $h($row['out'] ?? '—'); ?></td>
                                        <td><?php echo $h($row['late'] ?? '—'); ?></td>
                                        <td><?php echo $h($row['early'] ?? '—'); ?></td>
                                        <td><span class="st-pill st-pill-<?php echo $h($st !== '' ? $st : 'none'); ?>"><?php echo $h($row['remark'] ?? ''); ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="st-sheet-card" id="stLeaveSheet">
                        <div class="st-sheet-card-head"><h3>Leave sheet</h3></div>
                        <?php if ($canAddFile): ?>
                        <form method="POST" action="<?php echo $fileBase; ?>/save?id=<?php echo urlencode($sid); ?>" class="st-leave-form">
                            <input type="hidden" name="kind" value="leave">
                            <input type="hidden" name="return_month" value="<?php echo $h($attendanceMonth); ?>">
                            <?php if (empty($canApproveFile)): ?>
                                <input type="hidden" name="status" value="Recommended">
                            <?php endif; ?>
                            <div class="st-leave-grid<?php echo !empty($canApproveFile) ? ' st-leave-grid-approve' : ''; ?>">
                                <div>
                                    <label class="form-label" for="stLeaveType">Nature of leave</label>
                                    <select class="form-select" id="stLeaveType" name="leave_type" required><?php echo $opt($fileLabels['leave_type'] ?? [], 'casual'); ?></select>
                                </div>
                                <div>
                                    <label class="form-label" for="stLeaveFrom">From</label>
                                    <input type="date" class="form-control" id="stLeaveFrom" name="start_date" required>
                                </div>
                                <div>
                                    <label class="form-label" for="stLeaveTo">To</label>
                                    <input type="date" class="form-control" id="stLeaveTo" name="end_date" required>
                                </div>
                                <div>
                                    <label class="form-label" for="stLeaveDays">Days</label>
                                    <input class="form-control" id="stLeaveDays" name="days" required>
                                </div>
                                <div class="st-leave-reason">
                                    <label class="form-label" for="stLeaveReason">Reason</label>
                                    <input class="form-control" id="stLeaveReason" name="reason" maxlength="255">
                                </div>
                                <?php if (!empty($canApproveFile)): ?>
                                <div class="st-leave-status">
                                    <label class="form-label" for="stLeaveStatus">Status</label>
                                    <select class="form-select" id="stLeaveStatus" name="status"><?php echo $opt($fileLabels['leave_status'] ?? [], 'Recommended'); ?></select>
                                </div>
                                <?php endif; ?>
                                <div class="st-leave-submit">
                                    <button class="btn btn-primary w-100" type="submit">Submit</button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                        <div class="st-table-wrap mb-0 st-leave-list">
                            <table class="table st-table st-sheet-table st-leave-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Leave</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th class="text-end">Days</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <?php if ($canDeleteFile): ?><th class="st-col-action"></th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($leaveRows)): ?>
                                    <tr><td colspan="<?php echo $canDeleteFile ? '7' : '6'; ?>" class="text-muted">No leave submitted.</td></tr>
                                <?php else: foreach ($leaveRows as $row):
                                    $ls = strtolower((string) ($row['status'] ?? 'recorded')); ?>
                                    <tr>
                                        <td><?php echo $h($labelOf('leave_type', $row['leave_type'] ?? '')); ?></td>
                                        <td><?php echo $h($fmtD($row['start_date'] ?? '')); ?></td>
                                        <td><?php echo $h($fmtD($row['end_date'] ?? '')); ?></td>
                                        <td class="text-end"><?php echo $h($row['days'] ?? ''); ?></td>
                                        <td><?php echo $h($row['reason'] ?? ''); ?></td>
                                        <td><span class="st-pill st-pill-<?php echo $h(preg_replace('/[^a-z]/', '', $ls)); ?>"><?php echo $h($row['status'] ?? ''); ?></span></td>
                                        <?php if ($canDeleteFile): ?>
                                            <td class="text-end"><a class="btn btn-outline-danger btn-sm" href="<?php echo $fileBase; ?>/delete?id=<?php echo urlencode($sid); ?>&kind=leave&record=<?php echo (int) $row['id']; ?>" onclick="return confirm('Delete this leave sheet?');">Delete</a></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

            <?php elseif ($activeTab === 'documents'):
                $docs = $documents ?? []; ?>
                <?php if ($canUploadFile): ?>
                <form method="POST" action="<?php echo $fileBase; ?>/document/upload?id=<?php echo urlencode($sid); ?>" enctype="multipart/form-data" class="st-add-box mb-3">
                    <div class="st-grid-3">
                        <div><label class="form-label">Document Type</label><select class="form-select" name="doc_type" required><?php echo $opt($fileLabels['doc_type'] ?? [], ''); ?></select></div>
                        <div><label class="form-label">Title</label><input class="form-control" name="title" required></div>
                        <div><label class="form-label">File</label><input type="file" class="form-control" name="document" required></div>
                    </div>
                    <div class="st-form-actions"><button class="btn btn-primary" type="submit">Upload Document</button></div>
                </form>
                <?php endif; ?>
                <div class="st-table-wrap">
                    <table class="table st-table mb-0">
                        <thead><tr><th>Type</th><th>Title</th><th>Uploaded</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($docs)): ?>
                            <tr><td colspan="5" class="text-muted">No documents in this personal file.</td></tr>
                        <?php else: foreach ($docs as $doc): ?>
                            <tr>
                                <td><?php echo $h($labelOf('doc_type', $doc['doc_type'] ?? '')); ?></td>
                                <td><?php echo $h($doc['title'] ?? ''); ?></td>
                                <td><?php echo $h($doc['created_at'] ?? ''); ?></td>
                                <td><?php echo !empty($doc['approved']) ? 'Approved' : 'Pending'; ?></td>
                                <td class="st-actions">
                                    <?php if ($canDownloadFile): ?>
                                        <a class="btn btn-outline-primary btn-sm" href="<?php echo $fileBase; ?>/document/download?id=<?php echo urlencode($sid); ?>&doc=<?php echo (int) $doc['id']; ?>">View</a>
                                    <?php endif; ?>
                                    <?php if ($canApproveFile && empty($doc['approved'])): ?>
                                        <a class="btn btn-outline-success btn-sm" href="<?php echo $fileBase; ?>/document/approve?id=<?php echo urlencode($sid); ?>&doc=<?php echo (int) $doc['id']; ?>">Approve</a>
                                    <?php endif; ?>
                                    <?php if ($canDeleteFile): ?>
                                        <a class="btn btn-outline-danger btn-sm" href="<?php echo $fileBase; ?>/document/delete?id=<?php echo urlencode($sid); ?>&doc=<?php echo (int) $doc['id']; ?>" onclick="return confirm('Delete this document?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($activeTab === 'payroll'): ?>
                <form method="POST" action="<?php echo $h($base); ?>">
                    <input type="hidden" name="update_section" value="payroll">
                    <div class="st-grid-3">
                        <div><label class="form-label" for="staff_bank_name">Bank</label>
                            <input class="form-control" id="staff_bank_name" name="staff_bank_name" value="<?php echo $h($staff['staff_bank_name'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>></div>
                        <div><label class="form-label" for="staff_bank_branch">Branch</label>
                            <input class="form-control" id="staff_bank_branch" name="staff_bank_branch" value="<?php echo $h($staff['staff_bank_branch'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>></div>
                        <div><label class="form-label" for="staff_bank_account">Account Number</label>
                            <input class="form-control" id="staff_bank_account" name="staff_bank_account" value="<?php echo $h($staff['staff_bank_account'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>></div>
                    </div>
                    <div class="st-grid-2">
                        <div><label class="form-label" for="staff_epf">EPF No. <span class="text-danger">*</span></label>
                            <input class="form-control" id="staff_epf" name="staff_epf" required value="<?php echo $h($staff['staff_epf'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>></div>
                        <div><label class="form-label" for="staff_etf">ETF No.</label>
                            <input class="form-control" id="staff_etf" name="staff_etf" value="<?php echo $h($staff['staff_etf'] ?? ''); ?>" <?php echo $canEditFile ? '' : 'disabled'; ?>></div>
                    </div>
                    <?php if (!empty($salaryCalc['has_scale'])): ?>
                        <p class="st-perm-note">Current scale basic <?php echo $h(number_format((float) $salaryCalc['basic'], 2)); ?>, gross <?php echo $h(number_format((float) $salaryCalc['gross'], 2)); ?>.</p>
                    <?php endif; ?>
                    <?php if ($canEditFile): ?>
                        <div class="st-form-actions"><button class="btn btn-primary" type="submit">Save Payroll Information</button></div>
                    <?php endif; ?>
                </form>

            <?php elseif ($activeTab === 'access' && $canEditStaffAccess):
                $permissionMatrix = $permissionMatrix ?? [];
                $actions = ['view', 'add', 'edit', 'delete', 'upload', 'download', 'approve'];
                ?>
                <p class="st-perm-note mb-3">Staff Member → Module → Action. Grant, Deny, and Custom apply only to <strong><?php echo $h($staff['staff_name'] ?? $sid); ?></strong>.</p>
                <form method="POST" action="<?php echo APP_URL; ?>/staff/permissions/save?id=<?php echo urlencode($sid); ?>" id="stPermForm">
                    <div class="st-perm-toolbar">
                        <button type="button" class="btn btn-outline-secondary" data-perm-bulk="inherit">Inherit Role Permissions</button>
                        <button type="button" class="btn btn-outline-primary" data-perm-bulk="custom">Custom Staff Permissions</button>
                        <button type="button" class="btn btn-outline-success" data-perm-bulk="grant">Grant</button>
                        <button type="button" class="btn btn-outline-danger" data-perm-bulk="deny">Deny</button>
                        <button type="submit" name="perm_action" value="reset" class="btn btn-outline-secondary" onclick="return confirm('Reset all modules to role defaults?');">Reset to Role Defaults</button>
                        <button type="submit" name="perm_action" value="save" class="btn btn-primary">Save Permissions</button>
                    </div>
                    <div class="st-table-wrap st-perm-wrap">
                        <table class="table st-table st-perm-table mb-0">
                            <thead><tr><th>Module</th><th>Access</th><?php foreach ($actions as $act): ?><th class="text-center"><?php echo ucfirst($act); ?></th><?php endforeach; ?></tr></thead>
                            <tbody>
                            <?php foreach ($permissionMatrix as $mod):
                                $key = $mod['key'];
                                $mode = $mod['access_mode'] ?? 'inherit';
                                $role = $mod['role'] ?? [];
                                $shown = ($mode === 'custom') ? ($mod['custom'] ?? []) : ($mod['effective'] ?? []);
                                ?>
                                <tr class="st-perm-row"
                                    data-role-view="<?php echo !empty($role['can_view']) ? '1' : '0'; ?>"
                                    data-role-add="<?php echo !empty($role['can_add']) ? '1' : '0'; ?>"
                                    data-role-edit="<?php echo !empty($role['can_edit']) ? '1' : '0'; ?>"
                                    data-role-delete="<?php echo !empty($role['can_delete']) ? '1' : '0'; ?>"
                                    data-role-upload="<?php echo !empty($role['can_upload']) ? '1' : '0'; ?>"
                                    data-role-download="<?php echo !empty($role['can_download']) ? '1' : '0'; ?>"
                                    data-role-approve="<?php echo !empty($role['can_approve']) ? '1' : '0'; ?>">
                                    <td><strong><?php echo $h($mod['label']); ?></strong></td>
                                    <td>
                                        <select class="form-select form-select-sm st-perm-mode" name="modules[<?php echo $h($key); ?>][access_mode]">
                                            <option value="inherit" <?php echo $mode === 'inherit' ? 'selected' : ''; ?>>Inherit Role Permissions</option>
                                            <option value="custom" <?php echo $mode === 'custom' ? 'selected' : ''; ?>>Custom Staff Permissions</option>
                                            <option value="grant" <?php echo $mode === 'grant' ? 'selected' : ''; ?>>Grant</option>
                                            <option value="deny" <?php echo $mode === 'deny' ? 'selected' : ''; ?>>Deny</option>
                                        </select>
                                    </td>
                                    <?php foreach ($actions as $act): ?>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input st-perm-flag" name="modules[<?php echo $h($key); ?>][can_<?php echo $act; ?>]" value="1" data-action="<?php echo $act; ?>" <?php echo !empty($shown['can_' . $act]) ? 'checked' : ''; ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var form = document.getElementById('stStaffForm');
    if (form) {
        var staffType = form.querySelector('#staff_type');
        var appointType = form.querySelector('#appoint_type');
        var appointPosition = form.querySelector('#appoint_position');
        var appointDept = form.querySelector('#appoint_department_id');
        function syncAppoint() {
            if (!staffType || !appointType) return;
            var isPermanent = staffType.value === 'Permanent';
            var hasOther = appointType.value === 'cover_up' || appointType.value === 'acting';
            if (!isPermanent && hasOther) { appointType.value = 'none'; hasOther = false; }
            appointType.disabled = !isPermanent || staffType.disabled;
            if (appointPosition) appointPosition.disabled = !isPermanent || !hasOther || staffType.disabled;
            if (appointDept) appointDept.disabled = !isPermanent || !hasOther || staffType.disabled;
        }
        if (staffType) staffType.addEventListener('change', syncAppoint);
        if (appointType) appointType.addEventListener('change', syncAppoint);
        form.addEventListener('submit', function () {
            if (appointType) appointType.disabled = false;
            if (appointPosition) appointPosition.disabled = false;
            if (appointDept) appointDept.disabled = false;
        });
        syncAppoint();
    }
    var leaveFrom = document.getElementById('stLeaveFrom');
    var leaveTo = document.getElementById('stLeaveTo');
    var leaveDays = document.getElementById('stLeaveDays');
    function fillLeaveDays() {
        if (!leaveFrom || !leaveTo || !leaveDays) return;
        if (!leaveFrom.value || !leaveTo.value) return;
        var a = new Date(leaveFrom.value + 'T12:00:00');
        var b = new Date(leaveTo.value + 'T12:00:00');
        if (isNaN(a.getTime()) || isNaN(b.getTime()) || b < a) return;
        var days = Math.round((b - a) / 86400000) + 1;
        if (!leaveDays.value || leaveDays.dataset.auto === '1') {
            leaveDays.value = String(days);
            leaveDays.dataset.auto = '1';
        }
    }
    if (leaveFrom && leaveTo && leaveDays) {
        leaveDays.addEventListener('input', function () { leaveDays.dataset.auto = '0'; });
        leaveFrom.addEventListener('change', fillLeaveDays);
        leaveTo.addEventListener('change', fillLeaveDays);
    }
    var perm = document.getElementById('stPermForm');
    if (!perm) return;
    function applyRow(row, mode) {
        row.querySelector('.st-perm-mode').value = mode;
        row.querySelectorAll('.st-perm-flag').forEach(function (box) {
            var action = box.getAttribute('data-action');
            if (mode === 'grant') { box.checked = true; box.disabled = true; }
            else if (mode === 'deny') { box.checked = false; box.disabled = true; }
            else if (mode === 'inherit') { box.checked = row.getAttribute('data-role-' + action) === '1'; box.disabled = true; }
            else { box.disabled = false; }
        });
    }
    perm.querySelectorAll('.st-perm-row').forEach(function (row) {
        applyRow(row, row.querySelector('.st-perm-mode').value);
        row.querySelector('.st-perm-mode').addEventListener('change', function () { applyRow(row, this.value); });
    });
    perm.querySelectorAll('[data-perm-bulk]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            perm.querySelectorAll('.st-perm-row').forEach(function (row) { applyRow(row, btn.getAttribute('data-perm-bulk')); });
        });
    });
    perm.addEventListener('submit', function () {
        perm.querySelectorAll('.st-perm-flag').forEach(function (box) { box.disabled = false; });
    });
})();
</script>
