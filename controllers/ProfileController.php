<?php
/**
 * Profile Controller
 *
 * Every signed-in user can update their own account. Staff can also update their own
 * personal and contact details. Employment data (department, position, staff type,
 * status, EPF, finger number, date of join) is managed by HR / admin and is read-only here.
 */

class ProfileController extends Controller {

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM `user` WHERE `user_id` = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                $_SESSION['error'] = 'User not found.';
                $this->redirect('dashboard');
                return;
            }

            // Staff details (link via user_name = staff_id)
            $staff = null;
            $staffDepartment = null;
            if (($user['user_table'] ?? '') === 'staff') {
                try {
                    $this->model('StaffPersonalFileModel'); // makes sure the optional staff columns exist
                    $staff = $this->model('StaffModel')->getById($user['user_name']);

                    if ($staff && !empty($staff['department_id'])) {
                        $staffDepartment = $this->model('DepartmentModel')->getById($staff['department_id']);
                    }
                } catch (Throwable $e) {
                    error_log("ProfileController::index - Failed to load staff details: " . $e->getMessage());
                }
            }

            $data = [
                'title' => 'My Profile',
                'page' => 'profile',
                'user' => $user,
                'staff' => $staff,
                'staffDepartment' => $staffDepartment,
                'headedDepartments' => (function () {
                    require_once BASE_PATH . '/models/UserModel.php';
                    return (new UserModel())->getHeadedDepartments((int) $_SESSION['user_id']);
                })(),
                'message' => $_SESSION['message'] ?? null,
                'error' => $_SESSION['error'] ?? null,
            ];

            unset($_SESSION['message'], $_SESSION['error']);
            return $this->view('profile/index', $data);
        } catch (Throwable $e) {
            return $this->view('profile/index', [
                'title' => 'My Profile',
                'page' => 'profile',
                'error' => 'Error loading profile: ' . $e->getMessage(),
            ]);
        }
    }

    private function fail($message) {
        $_SESSION['error'] = $message;
        $this->redirect('profile');
    }

    private function emptyToNull($value) {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    public function update() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('profile');
            return;
        }

        try {
            $db = Database::getInstance();
            $userId = (int) $_SESSION['user_id'];

            $stmt = $db->prepare("SELECT * FROM `user` WHERE `user_id` = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $currentUser = $stmt->get_result()->fetch_assoc();
            if (!$currentUser) {
                $this->fail('User not found.');
                return;
            }

            // Username is not editable here.
            $userName = $currentUser['user_name'];
            $userEmail = trim($this->post('user_email', ''));
            $currentPassword = (string) $this->post('current_password', '');
            $newPassword = (string) $this->post('new_password', '');
            $confirmPassword = (string) $this->post('confirm_password', '');

            // ---- Account validation ----
            if ($userEmail === '') {
                $this->fail('Email is required.');
                return;
            }
            if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
                $this->fail('Invalid email format.');
                return;
            }
            $check = $db->prepare("SELECT user_id FROM `user` WHERE `user_email` = ? AND `user_id` != ?");
            $check->bind_param("si", $userEmail, $userId);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $this->fail('That email address is already used by another account.');
                return;
            }

            // ---- Password validation (before anything is saved) ----
            $newPasswordHash = null;
            if ($newPassword !== '') {
                if ($currentPassword === '') {
                    $this->fail('Current password is required to change password.');
                    return;
                }
                if ($newPassword !== $confirmPassword) {
                    $this->fail('New password and confirm password do not match.');
                    return;
                }
                if (strlen($newPassword) < 6) {
                    $this->fail('New password must be at least 6 characters long.');
                    return;
                }

                $hash = (string) $currentUser['user_password_hash'];
                if (strlen($hash) === 64 && ctype_xdigit($hash)) {
                    $verified = hash_equals($hash, hash('sha256', $currentPassword));
                } else {
                    $verified = password_verify($currentPassword, $hash);
                }
                if (!$verified) {
                    $this->fail('Current password is incorrect.');
                    return;
                }
                if ($newPassword === $currentPassword) {
                    $this->fail('New password must be different from the current password.');
                    return;
                }
                $newPasswordHash = hash('sha256', $newPassword);
            }

            // ---- Staff details validation ----
            $isStaffUser = ($currentUser['user_table'] ?? '') === 'staff';
            $staffId = $currentUser['user_name']; // staff_id equals user_name for staff accounts
            $staffData = null;
            if ($isStaffUser) {
                $this->model('StaffPersonalFileModel'); // make sure the optional columns exist
                $staffModel = $this->model('StaffModel');
                if (!$staffModel->getById($staffId)) {
                    $this->fail('Your staff record could not be found. Please contact the administrator.');
                    return;
                }

                $staffName = trim($this->post('staff_name', ''));
                $staffIni = trim($this->post('staff_ininame', ''));
                $staffNic = strtoupper(trim($this->post('staff_nic', '')));
                $staffDob = trim($this->post('staff_dob', ''));
                $staffGender = trim($this->post('staff_gender', ''));
                $staffPhone = preg_replace('/[^0-9+]/', '', (string) $this->post('staff_pno', ''));

                if ($staffName === '' || $staffNic === '') {
                    $this->fail('Full name and NIC are required.');
                    return;
                }
                if (mb_strlen($staffName) > 100 || mb_strlen($staffIni) > 100) {
                    $this->fail('Names can be at most 100 characters.');
                    return;
                }
                if (strlen($staffNic) > 20) {
                    $this->fail('NIC can be at most 20 characters.');
                    return;
                }
                if ($staffDob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $staffDob)) {
                    $this->fail('Date of birth is not valid.');
                    return;
                }
                if ($staffGender !== '' && !in_array($staffGender, ['Male', 'Female', 'Transgender'], true)) {
                    $this->fail('Select a valid gender.');
                    return;
                }
                if ($staffPhone !== '' && !preg_match('/^\+?[0-9]{9,15}$/', $staffPhone)) {
                    $this->fail('Enter a valid phone number (9 to 15 digits).');
                    return;
                }

                // NIC and email must be unique across staff.
                $dup = $db->prepare("SELECT `staff_id` FROM `staff` WHERE `staff_nic` = ? AND `staff_id` <> ? LIMIT 1");
                $dup->bind_param('ss', $staffNic, $staffId);
                $dup->execute();
                if ($dup->get_result()->fetch_assoc()) {
                    $this->fail('That NIC is already registered to another staff member.');
                    return;
                }
                $dup = $db->prepare("SELECT `staff_id` FROM `staff` WHERE `staff_email` = ? AND `staff_id` <> ? LIMIT 1");
                $dup->bind_param('ss', $userEmail, $staffId);
                $dup->execute();
                if ($dup->get_result()->fetch_assoc()) {
                    $this->fail('That email address is already used by another staff member.');
                    return;
                }

                $staffData = [
                    'staff_name' => $staffName,
                    'staff_ininame' => $staffIni !== '' ? $staffIni : null,
                    'staff_nic' => $staffNic,
                    'staff_email' => $userEmail, // keep in sync with the login email
                    'staff_address' => $this->emptyToNull($this->post('staff_address', '')) ?? '-',
                    'staff_current_address' => $this->emptyToNull($this->post('staff_current_address', '')),
                    'staff_emergency_name' => $this->emptyToNull($this->post('staff_emergency_name', '')),
                    'staff_emergency_phone' => $this->emptyToNull($this->post('staff_emergency_phone', '')),
                    'staff_emergency_relation' => $this->emptyToNull($this->post('staff_emergency_relation', '')),
                ];
                if ($staffDob !== '') {
                    $staffData['staff_dob'] = $staffDob;
                }
                if ($staffGender !== '') {
                    $staffData['staff_gender'] = $staffGender;
                }
                if ($staffPhone !== '') {
                    $staffData['staff_pno'] = $staffPhone;
                }
            }

            // ---- Save ----
            if ($staffData !== null) {
                // Updating the staff row also syncs the login email through the database trigger.
                if (!$staffModel->updateStaff($staffId, $staffData)) {
                    $this->fail('Failed to update your staff details. Please try again.');
                    return;
                }
            }

            if ($newPasswordHash !== null) {
                $update = $db->prepare("UPDATE `user` SET `user_email` = ?, `user_password_hash` = ?, `must_change_password` = 0 WHERE `user_id` = ?");
                $update->bind_param("ssi", $userEmail, $newPasswordHash, $userId);
            } else {
                $update = $db->prepare("UPDATE `user` SET `user_email` = ? WHERE `user_id` = ?");
                $update->bind_param("si", $userEmail, $userId);
            }
            if (!$update->execute()) {
                $this->fail('Failed to update your account. Please try again.');
                return;
            }

            $_SESSION['user_name'] = $userName;
            $_SESSION['user_email'] = $userEmail;

            $this->logActivity('UPDATE', 'profile', (string) $userId, 'Profile updated by the user ('
                . $userName . ')' . ($newPasswordHash !== null ? ', password changed' : ''), null, null);

            $_SESSION['message'] = 'Your profile was updated'
                . ($staffData !== null ? ' (including your personal details)' : '')
                . ($newPasswordHash !== null ? ' and your password was changed' : '')
                . '.';
            $this->redirect('profile');
        } catch (Throwable $e) {
            error_log('ProfileController::update - ' . $e->getMessage());
            $this->fail('Error updating profile: ' . $e->getMessage());
        }
    }
}
