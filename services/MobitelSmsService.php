<?php
/**
 * Mobitel mSMSEnterprise client.
 * Uses the official PHP SDK: createSession, isSession, sendMessages, closeSession.
 * Response 200 means the gateway accepted the message, not that the handset received it.
 */
class MobitelSmsService {
    /** Codes from the Mobitel Enterprise SMS PHP API. Only 200 is acceptance. */
    private const ERROR_CODES = [
        '151' => 'auth_failed',
        '152' => 'session_busy',
        '155' => 'gateway_exception',
        '156' => 'gateway_exception',
        '157' => 'gateway_exception',
        '159' => 'balance',
        '160' => 'rejected',
        '161' => 'invalid_content',
        '162' => 'invalid_content',
        '165' => 'invalid_number',
        '166' => 'invalid_number',
        '167' => 'invalid_number',
        '168' => 'invalid_sender',
        '169' => 'invalid_sender',
        '170' => 'invalid_number',
        '171' => 'invalid_number',
    ];

    public function __construct() {
        $this->loadEnv();
        require_once BASE_PATH . '/services/mobitel/ESMSWS.php';
    }

    /**
     * @return array{ok:bool,campaign_key:?string,result_code:string,error:?string,http_status:int}
     */
    public function sendSms(string $mobileNumber, string $message): array {
        $configError = $this->configurationError();
        if ($configError !== null) {
            $this->log('config_missing', $mobileNumber, $configError);
            return $this->failure($configError, 'config');
        }

        $mobile = $this->toGatewayMsisdn($mobileNumber);
        if ($mobile === null) {
            $this->log('invalid_number', $mobileNumber, 'local_validation');
            return $this->failure('invalid_number', 'invalid_number');
        }

        return $this->sendWithAlias($mobile, $message, $this->alias(), 0);
    }

    /**
     * Send one message to many recipients over a single gateway session, in batches.
     * Numbers are normalised and de-duplicated. Messages with non-GSM characters
     * (for example Tamil or Sinhala) go through the Unicode call automatically.
     * A gateway answer applies to a whole batch, so counts are per recipient of that batch.
     *
     * @param string[] $mobileNumbers
     * @return array{ok:bool,accepted:int,failed:int,invalid:int,codes:array<string,int>,error:?string}
     */
    public function sendBulk(array $mobileNumbers, string $message, int $batchSize = 50): array {
        $result = ['ok' => false, 'accepted' => 0, 'failed' => 0, 'invalid' => 0, 'codes' => [], 'error' => null];

        $configError = $this->configurationError();
        if ($configError !== null) {
            $result['error'] = 'config:' . $configError;
            return $result;
        }

        $valid = [];
        foreach ($mobileNumbers as $number) {
            $msisdn = $this->toGatewayMsisdn((string) $number);
            if ($msisdn === null) {
                $result['invalid']++;
                continue;
            }
            $valid[$msisdn] = $msisdn;
        }
        $valid = array_values($valid);
        if ($valid === []) {
            $result['error'] = 'no_valid_numbers';
            return $result;
        }

        $unicode = preg_match('/[^\x00-\x7F]/', $message) === 1;
        $batchSize = max(1, min(100, $batchSize));
        $alias = $this->alias();
        $session = null;
        try {
            $session = createSession('', $this->username(), $this->password(), '');
            if (!$this->sessionIsUsable($session)) {
                $this->log('auth_failed', '', 'no_session');
                $result['error'] = 'auth_failed';
                $result['failed'] = count($valid);
                return $result;
            }
            foreach (array_chunk($valid, $batchSize) as $batch) {
                if (function_exists('set_time_limit')) {
                    @set_time_limit(120);
                }
                try {
                    $code = $unicode
                        ? sendMessagesMultiLang($session, $alias, $message, $batch, 0)
                        : sendMessages($session, $alias, $message, $batch, 0);
                    $parsed = $this->validateResponse($code);
                } catch (Throwable $e) {
                    $this->log('connection', '', $this->safeFault($e));
                    $parsed = $this->failure('soap', 'connection');
                }
                $this->log($parsed['ok'] ? 'bulk_accepted' : 'bulk_rejected', '', $parsed['result_code'] . ' x' . count($batch));
                if ($parsed['ok']) {
                    $result['accepted'] += count($batch);
                } else {
                    $result['failed'] += count($batch);
                    $key = (string) $parsed['result_code'];
                    $result['codes'][$key] = ($result['codes'][$key] ?? 0) + count($batch);
                }
            }
        } catch (Throwable $e) {
            $this->log('connection', '', $this->safeFault($e));
            $result['error'] = 'connection';
            $result['failed'] = count($valid) - $result['accepted'];
        } finally {
            $this->closeSession($session);
        }

        $result['ok'] = $result['accepted'] > 0 && $result['failed'] === 0;
        return $result;
    }

    /**
     * Official serviceTest. This checks that the SOAP service answers.
     * It does not prove the API password is accepted.
     *
     * @return array{ok:bool,detail:string}
     */
    public function testConnection(): array {
        if (!extension_loaded('soap')) {
            return ['ok' => false, 'detail' => 'The PHP SOAP extension is not enabled.'];
        }
        if (!$this->isConfigured()) {
            return ['ok' => false, 'detail' => 'Missing ' . implode(', ', $this->missingSettings()) . '.'];
        }
        try {
            $result = serviceTest('', $this->username(), $this->password(), '');
            $text = '';
            if (is_object($result) && isset($result->return)) {
                $text = trim((string) $result->return);
            } elseif (is_scalar($result)) {
                $text = trim((string) $result);
            }
            $ok = strcasecmp($text, 'SUCCESS') === 0;
            return ['ok' => $ok, 'detail' => $ok ? 'serviceTest returned SUCCESS.' : 'serviceTest did not return SUCCESS.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'detail' => 'SOAP error: ' . $this->safeFault($e)];
        }
    }

    /**
     * Create a session with the official createSession call and check isSession.
     * The session is closed before this method returns.
     */
    public function validateSession(): bool {
        $session = null;
        try {
            if (!$this->isConfigured()) {
                return false;
            }
            $session = createSession('', $this->username(), $this->password(), '');
            if (!$this->sessionIsUsable($session)) {
                return false;
            }
            $valid = isSession($session);
            return $valid === true || $valid === 1 || $valid === '1';
        } catch (Throwable $e) {
            $this->log('connection', '', $this->safeFault($e));
            return false;
        } finally {
            $this->closeSession($session);
        }
    }

    public function normalizeSriLankanMobile(string $mobileNumber): ?string {
        $digits = preg_replace('/\D+/', '', $mobileNumber) ?? '';
        if (strlen($digits) === 11 && strpos($digits, '94') === 0) {
            $local = '0' . substr($digits, 2);
        } elseif (strlen($digits) === 10 && strpos($digits, '07') === 0) {
            $local = $digits;
        } elseif (strlen($digits) === 9 && isset($digits[0]) && $digits[0] === '7') {
            $local = '0' . $digits;
        } else {
            return null;
        }
        if (!preg_match('/^07\d{8}$/', $local)) {
            return null;
        }
        return '94' . substr($local, 1);
    }

    public function closeSession($session): void {
        if (!$this->sessionIsUsable($session)) {
            return;
        }
        try {
            \closeSession($session);
        } catch (Throwable $e) {
            $this->log('close_failed', '', $this->safeFault($e));
        }
    }

    /**
     * @return array{ok:bool,campaign_key:?string,result_code:string,error:?string,http_status:int}
     */
    public function validateResponse($response): array {
        $raw = trim((string) $response);
        if ($raw === '200') {
            return [
                'ok' => true,
                'campaign_key' => null,
                'result_code' => '200',
                'error' => null,
                'http_status' => 0,
            ];
        }
        if ($raw === '') {
            return $this->failure('empty_response', 'rejected');
        }
        if (isset(self::ERROR_CODES[$raw])) {
            return $this->failure($raw, self::ERROR_CODES[$raw]);
        }
        return $this->failure($raw, 'rejected');
    }

    /**
     * The official sendMessages call returns a status code, not a message id.
     * Delivery reports are listed by alias, so a single-message lookup is not available.
     *
     * @return array{ok:bool,result_code:string,error:?string,detail:?string}
     */
    public function getDeliveryStatus(string $transactionId): array {
        return [
            'ok' => false,
            'result_code' => trim($transactionId),
            'error' => 'unsupported',
            'detail' => null,
        ];
    }

    public function isConfigured(): bool {
        return $this->missingSettings() === [];
    }

    /**
     * @return string[]
     */
    public function missingSettings(): array {
        $this->loadEnv();
        $missing = [];
        foreach (['MOBITEL_SMS_USERNAME', 'MOBITEL_SMS_PASSWORD', 'MOBITEL_SMS_ALIAS', 'MOBITEL_SMS_WSDL'] as $key) {
            if (trim((string) EnvLoader::get($key, '')) === '') {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /**
     * Administrator diagnostics. Does not return credentials.
     *
     * @return array<int, array{name:string,ok:bool,detail:string}>
     */
    public function runAdminTests(string $testMobile = '', bool $sendTestSms = false): array {
        $results = $this->configurationStatus();
        if (!$this->isConfigured() || !extension_loaded('soap')) {
            return $results;
        }

        $connection = $this->testConnection();
        $results[] = [
            'name' => 'Mobitel service test',
            'ok' => $connection['ok'],
            'detail' => $connection['detail'],
        ];

        $session = null;
        try {
            $session = createSession('', $this->username(), $this->password(), '');
            $opened = $this->sessionIsUsable($session);
            $results[] = [
                'name' => 'Mobitel session authentication',
                'ok' => $opened,
                'detail' => $opened ? 'createSession returned a session.' : 'createSession did not return a session. The value in MOBITEL_SMS_PASSWORD was rejected. Set the API password from the portal Forgot Password page (choose API). The website login password is not accepted here.',
            ];
            if ($opened) {
                $valid = isSession($session);
                $results[] = [
                    'name' => 'Session validation',
                    'ok' => $valid === true || $valid === 1 || $valid === '1',
                    'detail' => 'isSession returned ' . (is_scalar($valid) ? (string) $valid : gettype($valid)) . '.',
                ];
            }
        } catch (Throwable $e) {
            $results[] = [
                'name' => 'Mobitel authentication',
                'ok' => false,
                'detail' => 'SOAP error: ' . $this->safeFault($e),
            ];
        } finally {
            $hadSession = $this->sessionIsUsable($session);
            $this->closeSession($session);
            if ($hadSession) {
                $results[] = ['name' => 'Session closure', 'ok' => true, 'detail' => 'closeSession was called.'];
            }
        }

        try {
            $bad = createSession('', $this->username(), 'invalid-password', '');
            $results[] = [
                'name' => 'Invalid credentials',
                'ok' => !$this->sessionIsUsable($bad),
                'detail' => $this->sessionIsUsable($bad) ? 'A session was created with a wrong password.' : 'Wrong password did not open a session.',
            ];
            $this->closeSession($bad);
        } catch (Throwable $e) {
            $results[] = ['name' => 'Invalid credentials', 'ok' => true, 'detail' => 'Gateway rejected the bad password.'];
        }

        $mobile = $this->toGatewayMsisdn($testMobile);
        if ($testMobile !== '' && $mobile === null) {
            $results[] = ['name' => 'Invalid recipient number', 'ok' => true, 'detail' => 'The test number is not a Sri Lankan mobile number, so it was not sent.'];
        }

        if ($sendTestSms && $mobile !== null) {
            $badAlias = $this->sendWithAlias($mobile, 'SLGTI MIS alias check', 'NOT-A-REAL-ALIAS', 0);
            $results[] = [
                'name' => 'Invalid sender alias',
                'ok' => !$badAlias['ok'] && $badAlias['result_code'] === '169',
                'detail' => 'Gateway code ' . $badAlias['result_code'] . '.',
            ];
            $sent = $this->sendWithAlias($mobile, 'SLGTI MIS Mobitel SMS Gateway Test. SMS integration is working.', $this->alias(), 0);
            $results[] = [
                'name' => 'SMS sending',
                'ok' => $sent['ok'],
                'detail' => $sent['ok']
                    ? 'Gateway accepted the test SMS with code 200. Handset delivery is not confirmed by this code.'
                    : 'Gateway code ' . $sent['result_code'] . ' (' . (string) $sent['error'] . ').',
                'code' => (string) $sent['result_code'],
            ];
            $results[] = [
                'name' => 'Last SMS response code',
                'ok' => $sent['ok'],
                'detail' => (string) $sent['result_code'],
                'code' => (string) $sent['result_code'],
            ];
            if ($sent['result_code'] === '159') {
                $results[] = ['name' => 'Insufficient SMS credit', 'ok' => false, 'detail' => 'The gateway returned 159, failed credit check.'];
            }
        }

        return $results;
    }

    /**
     * @return array{ok:bool,campaign_key:?string,result_code:string,error:?string,http_status:int}
     */
    private function sendWithAlias(string $mobile, string $message, string $alias, int $messageType): array {
        $session = null;
        try {
            $session = createSession('', $this->username(), $this->password(), '');
            if (!$this->sessionIsUsable($session)) {
                $this->log('auth_failed', $mobile, 'no_session');
                return $this->failure('no_session', 'auth_failed');
            }
            $valid = isSession($session);
            if ($valid !== true && $valid !== 1 && $valid !== '1') {
                $this->log('auth_failed', $mobile, '151');
                return $this->failure('151', 'auth_failed');
            }
            $code = sendMessages($session, $alias, $message, [$mobile], $messageType);
            $parsed = $this->validateResponse($code);
            $this->log($parsed['ok'] ? 'accepted' : 'rejected', $mobile, $parsed['result_code']);
            return $parsed;
        } catch (Throwable $e) {
            $this->log('connection', $mobile, $this->safeFault($e));
            return $this->failure('soap', 'connection');
        } finally {
            $this->closeSession($session);
        }
    }

    /**
     * Status rows that do not reveal the password.
     *
     * @return array<int, array{name:string,ok:bool,detail:string}>
     */
    public function configurationStatus(): array {
        $username = $this->username() !== '';
        $password = $this->password() !== '';
        $alias = $this->alias() !== '';
        $wsdl = trim((string) EnvLoader::get('MOBITEL_SMS_WSDL', '')) !== '';
        $soap = extension_loaded('soap');
        $internet = $this->wsdlReachable();
        return [
            ['name' => 'Username configured', 'ok' => $username, 'detail' => $username ? 'MOBITEL_SMS_USERNAME is set.' : 'Set MOBITEL_SMS_USERNAME in the server .env file.'],
            ['name' => 'Password configured', 'ok' => $password, 'detail' => $password ? 'MOBITEL_SMS_PASSWORD is set on the server and is not shown here.' : 'Set MOBITEL_SMS_PASSWORD in the server .env file.'],
            ['name' => 'Sender alias configured', 'ok' => $alias, 'detail' => $alias ? 'MOBITEL_SMS_ALIAS is set.' : 'Set MOBITEL_SMS_ALIAS to the mask approved on the Mobitel portal.'],
            ['name' => 'WSDL configured', 'ok' => $wsdl, 'detail' => $wsdl ? 'MOBITEL_SMS_WSDL is set.' : 'Set MOBITEL_SMS_WSDL to the official Mobitel WSDL.'],
            ['name' => 'SOAP extension', 'ok' => $soap, 'detail' => $soap ? 'The PHP SOAP extension is enabled.' : 'Enable the PHP SOAP extension.'],
            ['name' => 'Internet connection', 'ok' => $internet, 'detail' => $internet ? 'The Mobitel WSDL host answered.' : 'The Mobitel WSDL host could not be reached.'],
        ];
    }

    private function wsdlReachable(): bool {
        $wsdl = trim((string) EnvLoader::get('MOBITEL_SMS_WSDL', ''));
        if ($wsdl === '' || !function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init($wsdl);
        if ($ch === false) {
            return false;
        }
        $options = [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
        ];
        $bundledCa = BASE_PATH . '/services/mobitel/cacert.pem';
        if (is_file($bundledCa)) {
            $options[CURLOPT_CAINFO] = $bundledCa;
        }
        if (defined('CURLSSLOPT_NATIVE_CA')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $http >= 200 && $http < 400;
    }

    private function sessionIsUsable($session): bool {
        if ($session === null || $session === false || $session === '') {
            return false;
        }
        if (is_object($session)) {
            $id = trim((string) ($session->sessionId ?? ''));
            return $id !== '';
        }
        return true;
    }

    private function username(): string {
        $this->loadEnv();
        return trim((string) EnvLoader::get('MOBITEL_SMS_USERNAME', ''));
    }

    private function password(): string {
        $this->loadEnv();
        return (string) EnvLoader::get('MOBITEL_SMS_PASSWORD', '');
    }

    private function alias(): string {
        $this->loadEnv();
        $alias = trim((string) EnvLoader::get('MOBITEL_SMS_ALIAS', ''));
        if ($alias === '') {
            $alias = trim((string) EnvLoader::get('MOBITEL_SENDER_ID', ''));
        }
        return $alias;
    }

    private function configurationError(): ?string {
        $missing = $this->missingSettings();
        return $missing[0] ?? null;
    }

    private function toGatewayMsisdn(string $mobileNumber): ?string {
        return $this->normalizeSriLankanMobile($mobileNumber);
    }

    private function loadEnv(): void {
        if (!class_exists('EnvLoader', false)) {
            require_once BASE_PATH . '/core/EnvLoader.php';
        }
        EnvLoader::load();
    }

    /**
     * @return array{ok:bool,campaign_key:?string,result_code:string,error:?string,http_status:int}
     */
    private function failure(string $resultCode, string $error): array {
        return [
            'ok' => false,
            'campaign_key' => null,
            'result_code' => $resultCode,
            'error' => $error,
            'http_status' => 0,
        ];
    }

    private function safeFault(Throwable $e): string {
        $text = preg_replace('/password\s*[:=]\s*\S+/i', 'password=***', $e->getMessage()) ?? $e->getMessage();
        return substr($text, 0, 300);
    }

    private function log(string $status, string $mobile, string $resultCode): void {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        $masked = strlen($digits) >= 6 ? substr($digits, 0, 3) . '*****' . substr($digits, -3) : 'unknown';
        error_log('[MobitelSMS] status=' . $status . ' mobile=' . $masked . ' result=' . $resultCode);
    }
}
