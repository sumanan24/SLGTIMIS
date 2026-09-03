<?php
$group = $group ?? [];
$students = $students ?? [];
$availableStudents = $availableStudents ?? [];
$studentCount = count($students);
$isActive = (($group['status'] ?? 'active') === 'active');
$verLabel = GroupModel::versionLabel($group['course_version'] ?? 0);
$e = static function ($s): string {
    return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
};
$app = rtrim((string) APP_URL, '/');
$formatWhen = static function ($value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '' || strcasecmp($value, 'N/A') === 0) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date('d M Y, H:i', $ts) : $value;
};
$meta = [
    ['Course', $group['course_name'] ?? 'N/A'],
    ['Version', $verLabel],
    ['Department', $group['department_name'] ?? 'N/A'],
    ['Academic year', $group['academic_year'] ?? 'N/A'],
];
?>
<style>
.group-show-wrap .group-show-head { margin-bottom: 1.25rem; }
.group-show-wrap .group-show-title {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.3;
    margin: 0 0 .25rem;
    color: #1e293b;
}
.group-show-wrap .group-show-lead { font-size: .875rem; color: #64748b; margin: 0; }
.group-show-wrap .group-show-actions .btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.group-show-wrap .group-meta {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.group-show-wrap .group-meta-item {
    min-width: 0;
    padding: .9rem 1.1rem;
    border-right: 1px solid #e2e8f0;
}
.group-show-wrap .group-meta-item:last-child { border-right: none; }
.group-show-wrap .group-meta-label {
    display: block;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: .35rem;
}
.group-show-wrap .group-meta-value {
    display: block;
    font-size: .95rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    word-break: break-word;
}
.group-show-wrap .group-meta-item.is-count .group-meta-value { color: #0d6efd; font-size: 1.15rem; }

.group-show-wrap .group-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    overflow: hidden;
    padding: 0 !important;
    margin: 0 !important;
    box-shadow: none !important;
}
.group-show-wrap .group-panel-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .85rem 1.1rem;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
    margin: 0;
}
.group-show-wrap .group-panel-title {
    font-size: .95rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.group-show-wrap .group-panel-tools {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
}
.group-show-wrap .group-panel-search {
    width: 220px;
    max-width: 100%;
}

.group-show-wrap .group-table-wrap { overflow-x: auto; }
.group-show-wrap .group-table {
    width: 100%;
    margin: 0;
    table-layout: fixed;
    border-collapse: collapse;
    background: #fff;
    box-shadow: none !important;
    border-radius: 0 !important;
    overflow: visible;
}
.group-show-wrap .group-table thead {
    background: #f8fafc !important;
    color: #475569 !important;
}
.group-show-wrap .group-table th {
    padding: .7rem 1rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    vertical-align: middle;
}
.group-show-wrap .group-table td {
    padding: .7rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-size: .9rem;
}
.group-show-wrap .group-table tbody tr:last-child td { border-bottom: none; }
.group-show-wrap .group-table tbody tr:hover td { background: #f8fafc; }
.group-show-wrap .group-table .col-id { width: 18%; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; }
.group-show-wrap .group-table .col-name { width: 28%; font-weight: 600; color: #0f172a; }
.group-show-wrap .group-table .col-email { width: 26%; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.group-show-wrap .group-table .col-when { width: 16%; color: #64748b; white-space: nowrap; }
.group-show-wrap .group-table .col-status { width: 8%; }
.group-show-wrap .group-table .col-actions { width: 6%; text-align: right; }

.group-show-wrap .group-empty {
    text-align: center;
    padding: 2.75rem 1.25rem;
    color: #64748b;
}
.group-show-wrap .group-empty i { font-size: 2rem; color: #94a3b8; margin-bottom: .75rem; display: block; }

.group-show-wrap .modal-content { border: 1px solid #e2e8f0; border-radius: .5rem; }
.group-show-wrap .modal-header,
.group-show-wrap .modal-footer {
    padding: .9rem 1.15rem;
    border-color: #e2e8f0;
}
.group-show-wrap .modal-title { font-size: 1rem; font-weight: 700; }
.group-show-wrap .add-table { margin: 0; box-shadow: none !important; border-radius: 0 !important; }
.group-show-wrap .add-table thead { background: #f8fafc !important; color: #475569 !important; }
.group-show-wrap .add-table th,
.group-show-wrap .add-table td { padding: .6rem .75rem; vertical-align: middle; }
.group-show-wrap .add-table .col-check { width: 2.5rem; text-align: center; }
.group-show-wrap .add-scroll { max-height: 360px; overflow: auto; border: 1px solid #e2e8f0; border-radius: .375rem; }

@media (max-width: 1199px) {
    .group-show-wrap .group-meta { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .group-show-wrap .group-meta-item:nth-child(3n) { border-right: none; }
    .group-show-wrap .group-meta-item:nth-child(n+4) { border-top: 1px solid #e2e8f0; }
}
@media (max-width: 767px) {
    .group-show-wrap.container-fluid { padding-left: .75rem !important; padding-right: .75rem !important; }
    .group-show-wrap .group-show-actions { width: 100%; }
    .group-show-wrap .group-show-actions .btn { flex: 1 1 auto; }
    .group-show-wrap .group-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .group-show-wrap .group-meta-item { border-right: 1px solid #e2e8f0; border-top: 0; }
    .group-show-wrap .group-meta-item:nth-child(2n) { border-right: none; }
    .group-show-wrap .group-meta-item:nth-child(n+3) { border-top: 1px solid #e2e8f0; }
    .group-show-wrap .group-panel-search { width: 100%; }
    .group-show-wrap .group-panel-tools { width: 100%; }
    .group-show-wrap .group-panel-tools .btn { width: 100%; }
    .group-show-wrap .group-table { table-layout: auto; }
    .group-show-wrap .group-table .col-email,
    .group-show-wrap .group-table .col-when { white-space: nowrap; }
}
</style>

<div class="container-fluid px-4 py-3 group-show-wrap">
    <div class="group-show-head d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="min-w-0">
            <h1 class="group-show-title"><i class="fas fa-users text-primary me-2"></i><?php echo $e($group['name'] ?? 'Group'); ?></h1>
            <p class="group-show-lead">
                <?php echo $e($group['course_name'] ?? 'N/A'); ?>
                · <?php echo $e($verLabel); ?>
                · <?php echo $e($group['academic_year'] ?? 'N/A'); ?>
            </p>
        </div>
        <div class="group-show-actions d-flex flex-wrap gap-2">
            <a href="<?php echo $e($app . '/groups'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <a href="<?php echo $e($app . '/groups/edit?id=' . urlencode((string) ($group['id'] ?? ''))); ?>" class="btn btn-outline-primary">
                <i class="fas fa-pen me-1"></i>Edit
            </a>
        </div>
    </div>

    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <div><?php echo $e($message); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <div><?php echo $e($error); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="group-meta">
        <?php foreach ($meta as $item): ?>
            <div class="group-meta-item">
                <span class="group-meta-label"><?php echo $e($item[0]); ?></span>
                <span class="group-meta-value"><?php echo $e($item[1]); ?></span>
            </div>
        <?php endforeach; ?>
        <div class="group-meta-item">
            <span class="group-meta-label">Status</span>
            <span class="group-meta-value">
                <span class="badge rounded-pill <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
                    <?php echo $e(ucfirst((string) ($group['status'] ?? 'active'))); ?>
                </span>
            </span>
        </div>
        <div class="group-meta-item is-count">
            <span class="group-meta-label">Students</span>
            <span class="group-meta-value"><?php echo (int) $studentCount; ?></span>
        </div>
    </div>

    <div class="group-panel">
        <div class="group-panel-head">
            <h2 class="group-panel-title">
                Students
                <span class="badge bg-primary rounded-pill"><?php echo (int) $studentCount; ?></span>
            </h2>
            <div class="group-panel-tools">
                <?php if (!empty($students)): ?>
                    <input type="search" class="form-control form-control-sm group-panel-search" id="groupStudentFilter" placeholder="Search students…">
                <?php endif; ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentsModal">
                    <i class="fas fa-plus me-1"></i>Add students
                </button>
            </div>
        </div>

        <?php if (!empty($students)): ?>
            <div class="group-table-wrap">
                <table class="group-table">
                    <thead>
                        <tr>
                            <th class="col-id">Student ID</th>
                            <th class="col-name">Full name</th>
                            <th class="col-email">Email</th>
                            <th class="col-when">Added</th>
                            <th class="col-status">Status</th>
                            <th class="col-actions"> </th>
                        </tr>
                    </thead>
                    <tbody id="groupStudentsBody">
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="col-id"><?php echo $e($student['student_id'] ?? ''); ?></td>
                                <td class="col-name"><?php echo $e($student['student_fullname'] ?? ''); ?></td>
                                <td class="col-email" title="<?php echo $e($student['student_email'] ?? ''); ?>"><?php echo $e($student['student_email'] ?? '—'); ?></td>
                                <td class="col-when"><?php echo $e($formatWhen($student['enrolled_at'] ?? '')); ?></td>
                                <td class="col-status">
                                    <span class="badge rounded-pill <?php echo (($student['status'] ?? '') === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $e(ucfirst((string) ($student['status'] ?? 'active'))); ?>
                                    </span>
                                </td>
                                <td class="col-actions">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-student"
                                            data-student-id="<?php echo $e($student['student_id'] ?? ''); ?>"
                                            data-group-id="<?php echo $e($group['id'] ?? ''); ?>"
                                            title="Remove student">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="group-empty">
                <i class="fas fa-user-graduate"></i>
                <p class="mb-3">No students in this group yet.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentsModal">
                    <i class="fas fa-plus me-1"></i>Add students
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="addStudentsModal" tabindex="-1" aria-labelledby="addStudentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentsModalLabel"><i class="fas fa-user-plus me-2 text-primary"></i>Add students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="search" class="form-control" id="filter_student" placeholder="Search by ID, name, or email" <?php echo empty($availableStudents) ? 'disabled' : ''; ?>>
                    </div>
                    <div class="add-scroll">
                        <table class="table add-table mb-0">
                            <thead class="sticky-top">
                                <tr>
                                    <th class="col-check">
                                        <input type="checkbox" id="selectAll" class="form-check-input" <?php echo empty($availableStudents) ? 'disabled' : ''; ?> title="Select all">
                                    </th>
                                    <th>Student ID</th>
                                    <th>Full name</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody id="availableStudentsTable">
                                <?php if (!empty($availableStudents)): ?>
                                    <?php foreach ($availableStudents as $student): ?>
                                        <tr>
                                            <td class="col-check">
                                                <input type="checkbox" class="form-check-input student-checkbox" value="<?php echo $e($student['student_id'] ?? ''); ?>">
                                            </td>
                                            <td class="font-monospace"><?php echo $e($student['student_id'] ?? ''); ?></td>
                                            <td><?php echo $e($student['student_fullname'] ?? ''); ?></td>
                                            <td class="text-muted"><?php echo $e($student['student_email'] ?? '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No available students to add for this course, year, and version.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="small text-muted" id="addSelectedCount">0 selected</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="addStudentsBtn" <?php echo empty($availableStudents) ? 'disabled' : ''; ?>>
                            <i class="fas fa-plus me-1"></i>Add selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const groupId = <?php echo json_encode((string) ($group['id'] ?? '')); ?>;

    const listFilter = document.getElementById('groupStudentFilter');
    if (listFilter) {
        listFilter.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#groupStudentsBody tr').forEach(function(row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    const filterInput = document.getElementById('filter_student');
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('#availableStudentsTable tr').forEach(function(row) {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    }

    function updateSelectedCount() {
        const n = document.querySelectorAll('.student-checkbox:checked').length;
        const el = document.getElementById('addSelectedCount');
        if (el) el.textContent = n + ' selected';
    }

    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.student-checkbox').forEach(function(cb) {
                const row = cb.closest('tr');
                if (row && row.style.display === 'none') return;
                cb.checked = selectAll.checked;
            });
            updateSelectedCount();
        });
    }
    document.querySelectorAll('.student-checkbox').forEach(function(cb) {
        cb.addEventListener('change', updateSelectedCount);
    });

    const addStudentsBtn = document.getElementById('addStudentsBtn');
    if (addStudentsBtn) {
        addStudentsBtn.addEventListener('click', function() {
            const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(function(cb) { return cb.value; });
            if (selected.length === 0) {
                alert('Please select at least one student');
                return;
            }

            fetch(<?php echo json_encode($app . '/groups/add-students'); ?>, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'group_id=' + encodeURIComponent(groupId) + '&student_ids[]=' + selected.map(function(id) { return encodeURIComponent(id); }).join('&student_ids[]=')
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to add students');
                }
            })
            .catch(function() { alert('An error occurred'); });
        });
    }

    document.querySelectorAll('.remove-student').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Remove this student from the group?')) return;
            const studentId = this.getAttribute('data-student-id');
            fetch(<?php echo json_encode($app . '/groups/remove-student'); ?>, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'group_id=' + encodeURIComponent(groupId) + '&student_id=' + encodeURIComponent(studentId)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to remove student');
                }
            })
            .catch(function() { alert('An error occurred'); });
        });
    });
});
</script>
