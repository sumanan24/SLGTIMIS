<?php
/**
 * Forgot password, OTP verification, and password reset.
 * Uses the existing user table and password_hash / password_verify login path.
 */
class PasswordResetController extends Controller {
    private const OTP_TTL = 300;
    private const RESET_AUTH_TTL = 600;
    private const MAX_ATTEMPTS = 5;
    private const MAX_SENDS = 3;
    private const WINDOW_MINUTES = 15;
    private const IP_SEND_LIMIT = 10;
    private const IP_VERIFY_LIMIT = 20;

    private const GENERIC_LOOKUP = 'If this username or NIC matches an active account with a registered mobile number, a verification code has been sent. Check that phone for the SMS.';

    public function requestForm() {
        $this->boot();
        $error = '';
        $info = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrfOk()) {
                $error = 'The form expired. Please try again.';
            } else {
                $outcome = $this->sendOtp(trim((string) $this->post('identifier', '')));
                $error = $outcome['error'];
                $info = $outcome['info'];
                if ($outcome['redirect']) {
                    $this->redirect('forgot-password/verify');
                    return;
                }
            }
        }

        return $this->view('auth/forgot-password', $this->pageData('Forgot password', [
            'error' => $error,
            'info' => $info,
        ]));
    }

    public function verifyForm() {
        $this->boot();
        $otpId = (int) ($_SESSION['pwd_reset_otp_id'] ?? 0);
        $userId = (int) ($_SESSION['pwd_reset_user_id'] ?? 0);
        if ($otpId < 1 || $userId < 1) {
            $this->redirect('forgot-password');
            return;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrfOk()) {
                $error = 'The form expired. Please try again.';
            } elseif (isset($_POST['resend'])) {
                $outcome = $this->resendOtp($userId);
                $error = $outcome['error'];
                if ($outcome['redirect']) {
                    $this->redirect('forgot-password/verify');
                    return;
                }
            } else {
                $code = preg_replace('/\D+/', '', (string) $this->post('otp', '')) ?? '';
                $result = $this->verifyOtp($otpId, $userId, $code);
                $error = $result['error'];
                if ($result['redirect']) {
                    $this->redirect('forgot-password/reset');
                    return;
                }
            }
        }

        $row = $this->otpModel()->findById($otpId);
        $expiresAt = $row ? strtotime((string) $row['expires_at']) : time();

        return $this->view('auth/verify-otp', $this->pageData('Verify OTP', [
            'error' => $error,
            'masked_mobile' => (string) ($_SESSION['pwd_reset_masked'] ?? ''),
            'expires_at' => (int) $expiresAt,
        ]));
    }

    public function resetForm() {
        $this->boot();
        if (!$this->resetAuthorized()) {
            $this->redirect('forgot-password');
            return;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrfOk()) {
                $error = 'The form expired. Please try again.';
            } else {
                $saved = $this->savePassword(
                    (int) $_SESSION['pwd_reset_user_id'],
                    (string) $this->post('new_password', ''),
                    (string) $this->post('confirm_password', '')
                );
                if ($saved === '') {
                    $notice = 'Your password has been updated. Please log in with the new password.';
                    $_SESSION = [];
                    session_regenerate_id(true);
                    $_SESSION['login_notice'] = $notice;
                    $this->redirect('login');
                    return;
                }
                $error = $saved;
            }
        }

        return $this->view('auth/reset-password', $this->pageData('Reset password', [
            'error' => $error,
        ]));
    }

    private function boot(): void {
        require_once BASE_PATH . '/models/PasswordResetOtpModel.php';
        require_once BASE_PATH . '/services/MobitelSmsService.php';
        $model = new PasswordResetOtpModel();
        $model->ensureSchema();
        $model->cleanup();
        if (empty($_SESSION['pwd_reset_csrf'])) {
            $_SESSION['pwd_reset_csrf'] = bin2hex(random_bytes(32));
        }
    }

    private function otpModel(): PasswordResetOtpModel {
        return new PasswordResetOtpModel();
    }

    /**
     * @return array{error:string,info:string,redirect:bool}
     */
    private function sendOtp(string $identifier): array {
        $ip = $this->clientIp();
        $model = $this->otpModel();
        $model->recordIpEvent($ip, 'send');
        if ($model->countIpEvents($ip, 'send', self::WINDOW_MINUTES) > self::IP_SEND_LIMIT) {
            $this->logReset('rate_limited_ip', 0);
            return ['error' => 'Too many requests from this network. Please wait 15 minutes and try again.', 'info' => '', 'redirect' => false];
        }
        if ($identifier === '' || strlen($identifier) > 64) {
            return ['error' => 'Enter your username or NIC number.', 'info' => '', 'redirect' => false];
        }

        $sms = new MobitelSmsService();
        if (!$sms->isConfigured()) {
            $this->logReset('sms_not_configured', 0);
            return ['error' => $this->smsNotConfiguredMessage(), 'info' => '', 'redirect' => false];
        }

        try {
            $account = $model->findAccount($identifier);
        } catch (Throwable $e) {
            $this->logReset('database_error', 0);
            error_log('[PasswordReset] lookup failed: ' . $e->getMessage());
            return ['error' => 'Something went wrong. Please try again.', 'info' => '', 'redirect' => false];
        }

        if (!$account['ok']) {
            $this->logReset('lookup_' . $account['reason'], (int) $account['user_id']);
            return ['error' => '', 'info' => self::GENERIC_LOOKUP, 'redirect' => false];
        }

        if ($model->countUserSends((int) $account['user_id'], self::WINDOW_MINUTES) >= self::MAX_SENDS) {
            $this->logReset('resend_limit', (int) $account['user_id']);
            return ['error' => 'You have reached the maximum number of verification messages. Please wait 15 minutes and try again.', 'info' => '', 'redirect' => false];
        }

        return $this->issueOtp((int) $account['user_id'], (string) $account['mobile'], $ip, $sms);
    }

    /**
     * @return array{error:string,redirect:bool}
     */
    private function resendOtp(int $userId): array {
        $ip = $this->clientIp();
        $model = $this->otpModel();
        $model->recordIpEvent($ip, 'send');
        if ($model->countIpEvents($ip, 'send', self::WINDOW_MINUTES) > self::IP_SEND_LIMIT) {
            return ['error' => 'Too many requests from this network. Please wait 15 minutes and try again.', 'redirect' => false];
        }
        if ($model->countUserSends($userId, self::WINDOW_MINUTES) >= self::MAX_SENDS) {
            $this->logReset('resend_limit', $userId);
            return ['error' => 'You have reached the maximum number of verification messages. Please wait 15 minutes and try again.', 'redirect' => false];
        }

        $sms = new MobitelSmsService();
        if (!$sms->isConfigured()) {
            return ['error' => $this->smsNotConfiguredMessage(), 'redirect' => false];
        }

        $mobile = (string) $model->mobileForUser($userId);
        if (PasswordResetOtpModel::normalizeLocal($mobile) === null) {
            return ['error' => 'Request a new verification code from the forgot password page.', 'redirect' => false];
        }
        $issued = $this->issueOtp($userId, $mobile, $ip, $sms);
        return ['error' => $issued['error'], 'redirect' => $issued['redirect']];
    }

    /**
     * @return array{error:string,info:string,redirect:bool}
     */
    private function issueOtp(int $userId, string $mobile, string $ip, MobitelSmsService $sms): array {
        $model = $this->otpModel();
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($otp, PASSWORD_DEFAULT);
        if ($hash === false) {
            return ['error' => 'Something went wrong. Please try again.', 'info' => '', 'redirect' => false];
        }

        try {
            $model->invalidateActive($userId);
            $otpId = $model->createPending($userId, $hash, $ip, self::OTP_TTL);
        } catch (Throwable $e) {
            error_log('[PasswordReset] store failed: ' . $e->getMessage());
            return ['error' => 'Something went wrong. Please try again.', 'info' => '', 'redirect' => false];
        }

        $message = "Dear SLGTI User, Your password reset OTP is {$otp}. This OTP is valid for 5 minutes. Do not share this code with anyone. - SLGTI MIS";
        try {
            $result = $sms->sendSms($mobile, $message);
        } catch (Throwable $e) {
            $model->markSmsResult($otpId, null, 'exception', true);
            error_log('[PasswordReset] sms exception: ' . $e->getMessage());
            return ['error' => 'The SMS service could not be reached. Please try again shortly.', 'info' => '', 'redirect' => false];
        }
        unset($otp, $message);

        $model->markSmsResult($otpId, $result['campaign_key'], (string) $result['result_code'], !$result['ok']);
        if (!$result['ok']) {
            $this->logReset('sms_' . (string) $result['error'], $userId);
            return ['error' => $this->smsErrorMessage((string) $result['error']), 'info' => '', 'redirect' => false];
        }

        session_regenerate_id(true);
        $_SESSION['pwd_reset_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['pwd_reset_otp_id'] = $otpId;
        $_SESSION['pwd_reset_user_id'] = $userId;
        $_SESSION['pwd_reset_masked'] = PasswordResetOtpModel::maskMobile($mobile);
        $_SESSION['pwd_reset_verified'] = false;
        unset($_SESSION['pwd_reset_verified_until']);
        $this->logReset('otp_submitted', $userId);
        return ['error' => '', 'info' => '', 'redirect' => true];
    }

    /**
     * @return array{error:string,redirect:bool}
     */
    private function verifyOtp(int $otpId, int $userId, string $code): array {
        $ip = $this->clientIp();
        $model = $this->otpModel();
        $model->recordIpEvent($ip, 'verify');
        if ($model->countIpEvents($ip, 'verify', self::WINDOW_MINUTES) > self::IP_VERIFY_LIMIT) {
            return ['error' => 'Too many verification attempts from this network. Please wait 15 minutes.', 'redirect' => false];
        }
        if (!preg_match('/^\d{6}$/', $code)) {
            return ['error' => 'Enter the 6-digit verification code.', 'redirect' => false];
        }

        $row = $model->findById($otpId);
        if (!$row || (int) $row['user_id'] !== $userId) {
            return ['error' => 'This verification code is no longer valid. Request a new one.', 'redirect' => false];
        }
        if (!empty($row['verified_at'])) {
            return ['error' => 'This verification code has already been used. Request a new one.', 'redirect' => false];
        }
        if (!empty($row['invalidated_at'])) {
            return ['error' => 'This verification code is no longer valid. Request a new one.', 'redirect' => false];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            $model->markExpiredAttempts($otpId);
            return ['error' => 'This verification code has expired. Request a new one.', 'redirect' => false];
        }
        if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            $model->markExpiredAttempts($otpId);
            return ['error' => 'Too many incorrect attempts. Request a new verification code.', 'redirect' => false];
        }

        $valid = password_verify($code, (string) $row['otp_hash']);
        if (!$valid) {
            $attempts = $model->incrementAttempts($otpId);
            $this->logReset('otp_rejected', $userId);
            if ($attempts >= self::MAX_ATTEMPTS) {
                $model->markExpiredAttempts($otpId);
                unset($_SESSION['pwd_reset_otp_id']);
                return ['error' => 'Too many incorrect attempts. Request a new verification code.', 'redirect' => false];
            }
            $left = self::MAX_ATTEMPTS - $attempts;
            return ['error' => 'The verification code is incorrect. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' remaining.', 'redirect' => false];
        }

        $model->markVerified($otpId);
        session_regenerate_id(true);
        $_SESSION['pwd_reset_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['pwd_reset_verified'] = true;
        $_SESSION['pwd_reset_verified_until'] = time() + self::RESET_AUTH_TTL;
        $_SESSION['pwd_reset_user_id'] = $userId;
        unset($_SESSION['pwd_reset_otp_id']);
        $this->logReset('otp_verified', $userId);
        return ['error' => '', 'redirect' => true];
    }

    private function savePassword(int $userId, string $newPassword, string $confirm): string {
        if ($newPassword === '' || $confirm === '') {
            return 'Enter and confirm your new password.';
        }
        if ($newPassword !== $confirm) {
            return 'The passwords do not match.';
        }
        if (!$this->isStrongPassword($newPassword)) {
            return 'Use at least 8 characters with one uppercase letter, one lowercase letter, and one number.';
        }

        $db = Database::getInstance();
        try {
            $db->begin_transaction();
            $stmt = $db->prepare("SELECT `user_password_hash` FROM `user` WHERE `user_id` = ? AND `user_active` = 1 LIMIT 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            if (!$user || empty($user['user_password_hash'])) {
                $db->rollback();
                return 'This account cannot be updated. Please contact the system administrator.';
            }
            if ($this->matchesCurrentPassword($newPassword, (string) $user['user_password_hash'])) {
                $db->rollback();
                return 'Choose a password that is different from your current password.';
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($hash === false) {
                $db->rollback();
                return 'The password could not be updated. Please try again.';
            }
            $update = $db->prepare("UPDATE `user` SET `user_password_hash` = ?, `must_change_password` = 0 WHERE `user_id` = ? AND `user_active` = 1");
            $update->bind_param('si', $hash, $userId);
            if (!$update->execute() || $update->affected_rows < 1) {
                $db->rollback();
                return 'The password could not be updated. Please try again.';
            }

            $this->otpModel()->invalidateAllForUser($userId);
            $db->commit();
            require_once BASE_PATH . '/helpers/SisLdap.php';
            SisLdap::syncPasswordForUserId((int) $userId, $newPassword);
        } catch (Throwable $e) {
            try {
                $db->rollback();
            } catch (Throwable $ignored) {
            }
            error_log('[PasswordReset] update failed: ' . $e->getMessage());
            return 'The password could not be updated. Please try again.';
        }

        $this->logReset('password_updated', $userId);
        return '';
    }

    private function matchesCurrentPassword(string $password, string $currentHash): bool {
        $isSha256 = strlen($currentHash) === 64 && ctype_xdigit($currentHash);
        if ($isSha256) {
            return hash_equals($currentHash, hash('sha256', $password));
        }
        return password_verify($password, $currentHash);
    }

    private function isStrongPassword(string $password): bool {
        return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
    }

    private function resetAuthorized(): bool {
        if (empty($_SESSION['pwd_reset_verified']) || empty($_SESSION['pwd_reset_user_id'])) {
            return false;
        }
        $until = (int) ($_SESSION['pwd_reset_verified_until'] ?? 0);
        if ($until < time()) {
            unset($_SESSION['pwd_reset_verified'], $_SESSION['pwd_reset_verified_until'], $_SESSION['pwd_reset_user_id']);
            return false;
        }
        return true;
    }

    private function smsNotConfiguredMessage(): string {
        $sms = new MobitelSmsService();
        $missing = $sms->missingSettings();
        if ($missing === []) {
            return 'Password reset SMS is not configured.';
        }
        return 'Password reset SMS is not configured. Set ' . implode(', ', $missing) . ' in the server .env file. MOBITEL_SMS_ALIAS is the sender mask approved in the Mobitel portal.';
    }

    private function smsErrorMessage(string $error): string {
        switch ($error) {
            case 'timeout':
                return 'The SMS service did not respond in time. Please try again shortly.';
            case 'connection':
                return 'The SMS service could not be reached. Please try again shortly.';
            case 'auth_failed':
                return 'Mobitel rejected the API password. On the mSMS Enterprise login page, choose Forgot Password, select API, and set a new API password. Put that password in MOBITEL_SMS_PASSWORD in the server .env file. The website login password is not accepted by the SMS API.';
            case 'invalid_sender':
                return 'Mobitel rejected the sender alias. Set MOBITEL_SMS_ALIAS to the mask approved on the portal.';
            case 'session_busy':
                return 'The SMS session is busy. Please try again in a moment.';
            case 'balance':
                return 'The SMS account does not have enough credit to send this message.';
            case 'invalid_number':
                return 'The registered mobile number cannot receive this SMS. Please contact the system administrator.';
            default:
                return 'The verification SMS could not be sent. Please try again later.';
        }
    }

    private function csrfOk(): bool {
        $sent = (string) $this->post('csrf_token', '');
        $known = (string) ($_SESSION['pwd_reset_csrf'] ?? '');
        $ok = $sent !== '' && $known !== '' && hash_equals($known, $sent);
        if ($ok) {
            $_SESSION['pwd_reset_csrf'] = bin2hex(random_bytes(32));
        }
        return $ok;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function pageData(string $title, array $data): array {
        return array_merge($data, [
            'title' => $title,
            'page' => 'forgot-password',
            'csrf_token' => (string) $_SESSION['pwd_reset_csrf'],
            'seo_robots' => 'noindex, nofollow',
        ]);
    }

    private function clientIp(): string {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function logReset(string $event, int $userId): void {
        error_log('[PasswordReset] event=' . $event . ' user_id=' . $userId);
    }
}
