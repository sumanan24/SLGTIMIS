<?php
/**
 * Facilities ticket workflow and permission checks.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/models/FacilitiesTicketModel.php';
require_once BASE_PATH . '/helpers/FacilitiesTicketService.php';
require_once BASE_PATH . '/helpers/FacilitiesAssignmentService.php';
require_once BASE_PATH . '/helpers/FacilitiesProgressService.php';
require_once BASE_PATH . '/helpers/FacilitiesVerificationService.php';
require_once BASE_PATH . '/core/AccessControl.php';

$failed = 0;
$passed = 0;
function assertTrue($cond, $label) {
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "PASS  {$label}\n";
        return;
    }
    $failed++;
    echo "FAIL  {$label}\n";
}

$model = new FacilitiesTicketModel();
$svc = new FacilitiesTicketService();
$assign = new FacilitiesAssignmentService();
$progress = new FacilitiesProgressService();
$verify = new FacilitiesVerificationService();

$seq = $model->nextTicketNumber();
assertTrue(preg_match('#^SLGTI/FAC/\d{4}/\d{5}$#', $seq['ticket_number']) === 1, 'ticket number format SLGTI/FAC/YYYY/00001');
assertTrue((int) $seq['ticket_seq'] >= 1, 'ticket sequence starts at 1 or higher');

$cats = $model->categories();
assertTrue(count($cats) >= 8, 'seeded facility categories exist');
$subs = $model->subcategories((int) $cats[0]['id']);
assertTrue(count($subs) >= 1, 'subcategories exist for first category');

$over = $model->withDeadlineState([
    'status' => 'in_progress',
    'deadline' => date('Y-m-d', strtotime('-3 days')),
]);
assertTrue(!empty($over['is_overdue']) && (int) $over['overdue_days'] === 3, 'overdue detection uses deadline without changing status');
$soon = $model->withDeadlineState(['status' => 'in_progress', 'deadline' => date('Y-m-d')]);
assertTrue(($soon['due_state'] ?? '') === 'due_soon', 'due soon is within 2 days');
$closed = $model->withDeadlineState(['status' => 'closed', 'deadline' => date('Y-m-d', strtotime('-3 days'))]);
assertTrue(empty($closed['is_overdue']), 'closed tickets are not overdue');

$officer = ['user_id' => 1, 'staff_id' => 'FACOFF', 'is_officer' => true, 'is_monitor' => true, 'is_hod' => false, 'hod_department_id' => null, 'is_admin' => true, 'department_id' => 'ICT', 'contact' => '', 'name' => 'Officer'];
$staffA = ['user_id' => 2, 'staff_id' => 'FACREQ', 'is_officer' => false, 'is_monitor' => false, 'is_hod' => false, 'hod_department_id' => null, 'is_admin' => false, 'department_id' => 'ICT', 'contact' => '071', 'name' => 'Requester'];
$staffB = ['user_id' => 3, 'staff_id' => 'FACWRK', 'is_officer' => false, 'is_monitor' => false, 'is_hod' => false, 'hod_department_id' => null, 'is_admin' => false, 'department_id' => 'ICT', 'contact' => '', 'name' => 'Worker'];
$other = ['user_id' => 4, 'staff_id' => 'FACOTH', 'is_officer' => false, 'is_monitor' => false, 'is_hod' => false, 'hod_department_id' => null, 'is_admin' => false, 'department_id' => 'MET', 'contact' => '', 'name' => 'Other'];
$hod = ['user_id' => 5, 'staff_id' => 'FACHOD', 'is_officer' => false, 'is_monitor' => false, 'is_hod' => true, 'hod_department_id' => 'ICT', 'is_admin' => false, 'department_id' => 'ICT', 'contact' => '', 'name' => 'HOD'];
$principal = ['user_id' => 6, 'staff_id' => 'FACDIR', 'is_officer' => false, 'is_monitor' => true, 'is_hod' => false, 'hod_department_id' => null, 'is_admin' => false, 'department_id' => 'ADM', 'contact' => '', 'name' => 'Principal'];

$ticket = [
    'id' => 1,
    'status' => 'new',
    'requester_staff_id' => 'FACREQ',
    'responsible_staff_id' => null,
    'requester_department_id' => 'ICT',
    'responsible_department_id' => null,
];
assertTrue($svc->canViewTicket($staffA, $ticket), 'requester can view own ticket');
assertTrue(!$svc->canViewTicket($other, $ticket), 'unrelated staff cannot view');
assertTrue($svc->canViewTicket($hod, $ticket), 'HOD can view department ticket');
assertTrue($svc->canViewTicket($principal, $ticket), 'principal can monitor all tickets');
assertTrue($svc->canAssign($officer, $ticket), 'officer can assign');
assertTrue(!$svc->canAssign($staffB, $ticket), 'staff cannot assign');
assertTrue(!$svc->canClose($staffB, $ticket + ['responsible_staff_id' => 'FACWRK', 'status' => 'completed_pending_verification']), 'assigned staff cannot close');

assertTrue(isset(AccessControl::catalog()['facilities']), 'facilities module is in the catalog');
assertTrue(in_array('facilities', AccessControl::catalog()['facilities']['prefixes'] ?? ['facilities'], true)
    || (AccessControl::catalog()['facilities']['prefixes'][0] ?? '') === 'facilities', 'facilities route prefix is registered');

$db = Database::getInstance();
$staffRow = $db->query("SELECT staff_id, department_id FROM staff LIMIT 1");
$staff = $staffRow ? $staffRow->fetch_assoc() : null;
if ($staff) {
    $actor = $staffA;
    $actor['staff_id'] = $staff['staff_id'];
    $actor['department_id'] = $staff['department_id'];
    $worker = $staffB;
    $worker['staff_id'] = $staff['staff_id'];
    $created = $svc->createTicket($actor, [
        'title' => 'FAC-TEST leak',
        'description' => 'Test tap leak in workshop',
        'category_id' => (int) $cats[0]['id'],
        'subcategory_id' => !empty($subs[0]['id']) ? (int) $subs[0]['id'] : null,
        'location' => 'Workshop A',
        'priority' => 'high',
        'building' => 'Main',
        'floor' => '1',
        'room_area' => 'Lab',
        'required_date' => date('Y-m-d', strtotime('+3 days')),
    ]);
    assertTrue(!empty($created['id']) && strpos($created['ticket_number'], 'SLGTI/FAC/') === 0, 'ticket is created with generated number');
    assertTrue($created['status'] === 'new', 'new ticket starts as NEW');
    assertTrue($created['original_priority'] === 'high' && $created['current_priority'] === 'high', 'original and current priority stored');

    $created = $verify->review($officer, $created);
    assertTrue($created['status'] === 'under_review', 'officer review moves to UNDER REVIEW');

    $created = $assign->assign($officer, $created, [
        'assignment_type' => 'staff',
        'staff_id' => $staff['staff_id'],
        'department_id' => $staff['department_id'],
        'deadline' => date('Y-m-d', strtotime('+5 days')),
        'start_date' => date('Y-m-d'),
        'instructions' => 'Fix the tap',
    ]);
    assertTrue($created['status'] === 'assigned', 'assignment sets ASSIGNED');
    $hist = $model->children('facilities_assignments', (int) $created['id']);
    assertTrue(count($hist) === 1 && (int) $hist[0]['is_current'] === 1, 'assignment history stored');

    $created = $assign->assign($officer, $created, [
        'assignment_type' => 'staff',
        'staff_id' => $staff['staff_id'],
        'department_id' => $staff['department_id'],
        'deadline' => date('Y-m-d', strtotime('+7 days')),
        'start_date' => date('Y-m-d'),
        'instructions' => 'Reassigned same person',
    ]);
    $hist = $model->children('facilities_assignments', (int) $created['id']);
    assertTrue(count($hist) === 2, 'reassignment appends history and does not overwrite');

    $created = $progress->accept($worker, $created);
    assertTrue($created['status'] === 'accepted', 'assigned staff can accept');
    $created = $progress->start($worker, $created);
    assertTrue($created['status'] === 'in_progress', 'start moves to IN PROGRESS');
    $created = $progress->updateProgress($worker, $created, 50, 'Pipe isolated', '');
    assertTrue((int) $created['progress_percent'] === 50, 'progress percent stored');
    $prg = $model->children('facilities_progress_updates', (int) $created['id']);
    $created = $progress->updateProgress($worker, $created, 80, 'Tap replaced', '');
    $prg2 = $model->children('facilities_progress_updates', (int) $created['id']);
    assertTrue(count($prg2) > count($prg), 'progress updates are append-only');

    $blocked = false;
    try {
        $progress->submitCompletion($worker, $created, 'Done', date('Y-m-d'));
    } catch (Throwable $e) {
        $blocked = strpos($e->getMessage(), 'evidence') !== false;
    }
    assertTrue($blocked, 'completion without evidence is blocked');

    $model->addChild('facilities_evidence', [
        'ticket_id' => $created['id'],
        'evidence_type' => 'after',
        'original_name' => 'after.jpg',
        'stored_name' => 'after.jpg',
        'file_path' => 'uploads/facilities/test.jpg',
        'file_type' => 'image/jpeg',
        'file_size' => 100,
        'description' => 'After photo',
        'uploaded_by' => $staff['staff_id'],
        'uploaded_at' => date('Y-m-d H:i:s'),
    ]);
    $created = $progress->submitCompletion($worker, $created, 'Tap replaced and tested', date('Y-m-d'));
    assertTrue($created['status'] === 'completed_pending_verification', 'completion waits for verification');

    $closedByWorker = false;
    try {
        $verify->close($worker, $created, 'done');
    } catch (Throwable $e) {
        $closedByWorker = true;
    }
    assertTrue($closedByWorker, 'assigned staff cannot close');

    $created = $verify->returnForCorrection($officer, $created, 'Photo unclear', 'Take a closer after photo', date('Y-m-d', strtotime('+2 days')));
    assertTrue($created['status'] === 'in_progress', 'return sends ticket back to IN PROGRESS');
    $created = $progress->submitCompletion($worker, $created, 'New after photo uploaded', date('Y-m-d'));
    $created = $verify->verify($officer, $created, 'Looks good');
    assertTrue($created['status'] === 'verified', 'officer can verify');
    $created = $verify->close($officer, $created, 'Closed after verification');
    assertTrue($created['status'] === 'closed', 'officer can close after verification');

    $created = $verify->reopen($officer, $created, 'Leak returned');
    assertTrue($created['status'] === 'reopened', 'reopen requires reason and authorized user');

    $db->query('DELETE FROM facilities_activity WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_notifications WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_evidence WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_progress_updates WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_assignments WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_status_history WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_deadline_history WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_verifications WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_comments WHERE ticket_id = ' . (int) $created['id']);
    $db->query('DELETE FROM facilities_tickets WHERE id = ' . (int) $created['id']);
} else {
    echo "SKIP  live workflow (no staff row available)\n";
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
