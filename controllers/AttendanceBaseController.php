<?php
/**
 * Shared access rules for Attendance Management, Training Programmes and the teaching diary.
 * Reuses the existing roles and department scoping (HOD, IN1-IN3, LE1/LE2/SLE, ADM ...). LE3 and TIN are
 * accepted as teaching roles if they are created later; no new user types are introduced.
 */
require_once dirname(__DIR__) . '/helpers/AttendanceRules.php';
require_once dirname(__DIR__) . '/models/DailyAttendanceModel.php';
require_once dirname(__DIR__) . '/models/TrainingProgrammeModel.php';

abstract class AttendanceBaseController extends Controller {
    const VIEW_ROLES = ['HOD', 'IN1', 'IN2', 'IN3', 'LE1', 'LE2', 'LE3', 'SLE', 'TIN', 'ADM', 'DIR', 'DPI', 'DPA'];
    /** Teaching roles that submit correction requests (cannot correct directly). */
    const REQUEST_ROLES = ['IN1', 'IN2', 'IN3', 'LE3', 'TIN'];
    /** Higher authority that decides exceptional (post month-end) corrections. */
    const HIGHER_ROLES = ['ADM', 'DIR', 'DPI', 'DPA'];
    const TEACHING_ROLES = ['IN1', 'IN2', 'IN3', 'LE1', 'LE2', 'LE3', 'SLE', 'TIN'];

    private $ctxCache = null;

    /**
     * @return array<string,mixed>|null null when the visitor is not a staff user with access (a redirect is sent)
     */
    protected function requireAttendanceAccess($allowRoles = null) {
        if (!isset($_SESSION['user_id']) || ($_SESSION['user_table'] ?? '') === 'student') {
            $this->redirect('login');
            return null;
        }
        $ctx = $this->attContext();
        $allowed = $allowRoles ?? self::VIEW_ROLES;
        if (!$ctx['isAdmin'] && !in_array($ctx['role'], $allowed, true) && !$ctx['isDeptManager']) {
            $_SESSION['error'] = 'Access denied. You do not have permission to open this page.';
            $this->redirect('dashboard');
            return null;
        }
        if ($ctx['scoped'] && $ctx['departmentId'] === '') {
            $_SESSION['error'] = 'Your account has no department assigned. Please contact the administrator.';
            $this->redirect('dashboard');
            return null;
        }
        return $ctx;
    }

    /**
     * @return array<string,mixed>
     */
    protected function attContext() {
        if ($this->ctxCache !== null) {
            return $this->ctxCache;
        }
        require_once BASE_PATH . '/models/UserModel.php';
        $um = new UserModel();
        $uid = (int) $_SESSION['user_id'];
        $role = (string) $um->getUserRole($uid);
        $isAdmin = (bool) $um->isAdmin($uid);
        $higher = $isAdmin || in_array($role, self::HIGHER_ROLES, true);
        $dept = $this->getUserDepartment();
        $scoped = !$higher && !in_array($role, self::INSTITUTE_WIDE_ROLES, true);
        $manager = $this->isDepartmentManager();
        $this->ctxCache = [
            'userId' => $uid,
            'userName' => (string) ($_SESSION['user_name'] ?? ''),
            'role' => $role,
            'isAdmin' => $isAdmin,
            'isHod' => $role === 'HOD',
            'isDeptManager' => $manager,
            'departmentId' => $dept !== null ? (string) $dept : '',
            'scoped' => $scoped,
            'isHigher' => $higher,
            'isTeaching' => in_array($role, self::TEACHING_ROLES, true) || $role === 'HOD',
            'canCorrect' => $manager || $role === 'ADM' || $isAdmin,
            'canRequest' => in_array($role, self::REQUEST_ROLES, true) && !$manager,
            'canApproveExceptional' => $higher,
            'canConfigure' => $role === 'ADM' || $isAdmin,
            'staffId' => (string) ($_SESSION['user_name'] ?? ''),
        ];
        return $this->ctxCache;
    }

    /**
     * Department for filters: scoped users are fixed to their own, others may pick one (empty = all).
     */
    protected function scopeDepartment(array $ctx) {
        if ($ctx['scoped']) {
            return $ctx['departmentId'];
        }
        return trim((string) $this->get('department_id', ''));
    }

    protected function csrfToken() {
        if (empty($_SESSION['att_csrf'])) {
            $_SESSION['att_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['att_csrf'];
    }

    protected function verifyCsrf() {
        return hash_equals((string) ($_SESSION['att_csrf'] ?? ''), (string) $this->post('csrf_token', ''));
    }

    protected function isoDate($value, $default = '') {
        $value = trim((string) $value);
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return ($dt && $dt->format('Y-m-d') === $value) ? $value : $default;
    }

    /**
     * Whether the current user may act on a student's attendance (same department for scoped users).
     */
    protected function canTouchStudent(array $ctx, $studentId) {
        if (!$ctx['scoped']) {
            return true;
        }
        require_once BASE_PATH . '/models/AttendanceCorrectionModel.php';
        return (new AttendanceCorrectionModel())->studentInDepartment($studentId, $ctx['departmentId']);
    }

    /**
     * Groups visible to the user (for filters).
     *
     * @return list<array<string,mixed>>
     */
    protected function visibleGroups($departmentId) {
        require_once BASE_PATH . '/models/GroupModel.php';
        return (new GroupModel())->getAllWithDetails($departmentId !== '' ? $departmentId : null);
    }

    protected function flashPull() {
        $f = ['message' => $_SESSION['message'] ?? null, 'error' => $_SESSION['error'] ?? null];
        unset($_SESSION['message'], $_SESSION['error']);
        return $f;
    }
}
