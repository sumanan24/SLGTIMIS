<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Database.php';

EnvLoader::load(BASE_PATH . '/.env');
$token = (string) EnvLoader::get('LMS_AUTH_TOKEN', '');
$db = Database::getInstance();
$result = $db->query(
    "SELECT u.user_name, s.student_nic
       FROM user u
       INNER JOIN student s ON s.student_id = u.user_name
      WHERE u.user_active = 1
        AND s.student_nic IS NOT NULL
        AND s.student_nic <> ''
      LIMIT 1"
);
$row = $result ? $result->fetch_assoc() : null;
if (!$row) {
    echo "sample=none\n";
    exit(1);
}

function call_api(string $token, string $username): array
{
    $ch = curl_init('http://127.0.0.1/SLGTIMIS/api/lms-auth.php');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'action' => 'resolve',
            'username' => $username,
            'extended' => '0',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-SLGTI-Auth: ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = is_string($raw) ? json_decode($raw, true) : null;
    return [$code, is_array($json) ? $json : []];
}

$clean = strtolower(trim((string) $row['student_nic']));
$clean = (string) preg_replace('/[^-\.@_a-z0-9]/', '', $clean);
echo 'nic_key_length=' . strlen($clean) . PHP_EOL;
$all = $db->query(
    "SELECT u.user_name, s.student_id, s.student_nic, st.staff_nic
       FROM user u
       LEFT JOIN student s ON u.user_table = 'student'
         AND (s.student_id = u.user_name OR s.student_nic = u.user_name)
       LEFT JOIN staff st ON u.user_table = 'staff' AND st.staff_id = u.user_name"
);
$matchCount = 0;
if ($all) {
    while ($item = $all->fetch_assoc()) {
        $keys = [];
        foreach (['user_name', 'student_id', 'student_nic', 'staff_nic'] as $field) {
            $value = strtolower(trim((string) ($item[$field] ?? '')));
            $value = (string) preg_replace('/[^-\.@_a-z0-9]/', '', $value);
            if ($value !== '') {
                $keys[$value] = true;
            }
        }
        if (isset($keys[$clean])) {
            $matchCount++;
        }
    }
}
echo 'local_matches=' . $matchCount . PHP_EOL;
[$idCode, $byId] = call_api($token, (string) $row['user_name']);
[$nicCode, $byNic] = call_api($token, (string) $row['student_nic']);
$same = (($byId['username'] ?? '') !== '' && ($byId['username'] ?? '') === ($byNic['username'] ?? ''));
echo 'id_http=' . $idCode . ' id_found=' . (int) ($byId['found'] ?? 0) . ' id_rev=' . (int) ($byId['rev'] ?? 0) . PHP_EOL;
echo 'nic_http=' . $nicCode
    . ' nic_found=' . (int) ($byNic['found'] ?? 0)
    . ' rows=' . (int) ($byNic['rows'] ?? -1)
    . ' with_nic=' . (int) ($byNic['with_nic'] ?? -1)
    . PHP_EOL;
echo 'same_account=' . ($same ? 'yes' : 'no') . PHP_EOL;
echo 'active=' . (int) ($byNic['active'] ?? 0) . PHP_EOL;
