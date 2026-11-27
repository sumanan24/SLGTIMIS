<?php
/**
 * Use the LDAP password for existing Moodle accounts with the same username.
 * Does not create or delete Moodle users and does not print passwords.
 */
define('CLI_SCRIPT', true);
require '/var/www/moodle/config.php';

$mode = $argv[1] ?? 'report';
if (!in_array($mode, ['report', 'apply', 'diag', 'sis-report', 'sis-apply'], true)) {
    fwrite(STDERR, "use report, apply, diag, sis-report, or sis-apply\n");
    exit(2);
}

if ($mode === 'diag') {
    $needle = strtolower(trim((string) ($argv[2] ?? '')));
    echo 'auth_plugins=' . implode(',', get_enabled_auth_plugins()) . PHP_EOL;
    echo 'prevent_creation=' . (string) get_config('core', 'authpreventaccountcreation') . PHP_EOL;
    echo 'login_via_email=' . (string) get_config('core', 'authloginviaemail') . PHP_EOL;
    $dbcfg = get_config('auth_db');
    echo 'db_type=' . (string) ($dbcfg->type ?? '') . PHP_EOL;
    echo 'db_host=' . (string) ($dbcfg->host ?? '') . PHP_EOL;
    echo 'db_name=' . (string) ($dbcfg->name ?? '') . PHP_EOL;
    echo 'db_table=' . (string) ($dbcfg->table ?? '') . PHP_EOL;
    $counts = $DB->get_records_sql("SELECT auth, COUNT(1) AS total FROM {user} WHERE deleted = 0 GROUP BY auth");
    foreach ($counts as $row) {
        echo 'auth_count_' . $row->auth . '=' . $row->total . PHP_EOL;
    }
    if ($needle !== '') {
        $like = '%' . $DB->sql_like_escape($needle) . '%';
        $rows = $DB->get_records_sql(
            "SELECT id, username, auth, suspended
               FROM {user}
              WHERE deleted = 0
                AND (" . $DB->sql_like('username', ':username', false) . "
                  OR " . $DB->sql_like('email', ':email', false) . "
                  OR " . $DB->sql_like('firstname', ':firstname', false) . "
                  OR " . $DB->sql_like('lastname', ':lastname', false) . ")
           ORDER BY id",
            ['username' => $like, 'email' => $like, 'firstname' => $like, 'lastname' => $like]
        );
        echo 'matches=' . count($rows) . PHP_EOL;
        foreach ($rows as $row) {
            echo 'user=' . $row->id . ' username=' . $row->username . ' auth=' . $row->auth . ' suspended=' . $row->suspended . PHP_EOL;
        }
    }
    exit(0);
}

if ($mode === 'sis-report' || $mode === 'sis-apply') {
    $cfgpath = '/usr/local/etc/slgti-sis-auth.php';
    if (!is_file($cfgpath)) {
        fwrite(STDERR, "sis auth settings are missing\n");
        exit(1);
    }
    $sicfg = require $cfgpath;
    $extended = !empty($CFG->extendedusernamechars) ? '1' : '0';
    $ch = curl_init((string) $sicfg['url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['action' => 'map', 'extended' => $extended]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-SLGTI-Auth: ' . $sicfg['token'],
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($decoded) || empty($decoded['ok']) || !isset($decoded['users'])) {
        $bytes = is_string($raw) ? strlen($raw) : 0;
        fwrite(STDERR, "sis map request failed errno={$errno} http={$http} bytes={$bytes}\n");
        exit(1);
    }
    $byEmail = [];
    foreach ($decoded['users'] as $row) {
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $username = strtolower(trim((string) ($row['username'] ?? '')));
        if ($email === '' || $username === '' || (int) ($row['active'] ?? 0) !== 1) {
            continue;
        }
        $byEmail[$email] = $username;
    }
    $protected = ['admin' => true, 'guest' => true];
    $siteadmins = array_filter(array_map('intval', explode(',', (string) get_config('core', 'siteadmins'))));
    $users = $DB->get_records_select('user', 'deleted = 0', null, 'id ASC', 'id, username, auth, email, suspended');
    $switch = 0;
    $same = 0;
    $skipped = 0;
    $sampleOld = '';
    $sampleNew = '';
    foreach ($users as $user) {
        $name = strtolower(trim((string) $user->username));
        $email = strtolower(trim((string) $user->email));
        if ($name === '' || isset($protected[$name]) || in_array((int) $user->id, $siteadmins, true)) {
            continue;
        }
        if ($email === '' || !isset($byEmail[$email])) {
            $skipped++;
            continue;
        }
        $target = $byEmail[$email];
        if ($target === $name && $user->auth === 'sis') {
            $same++;
            continue;
        }
        $taken = $DB->get_records_select('user', 'deleted = 0 AND id <> :id AND LOWER(username) = :username', [
            'id' => $user->id,
            'username' => $target,
        ], '', 'id');
        if ($taken) {
            $skipped++;
            continue;
        }
        $switch++;
        if ($sampleOld === '') {
            $sampleOld = $name;
            $sampleNew = $target;
        }
        if ($name === 'keerththeejan' || $target === '2022ict5te100' || $target === '2022/ict/5te100') {
            echo 'known_old=' . $name . PHP_EOL;
            echo 'known_new=' . $target . PHP_EOL;
        }
        if ($mode === 'sis-apply') {
            $user->username = $target;
            $user->auth = 'sis';
            $user->password = 'not cached';
            $DB->update_record('user', $user);
        }
    }
    $auths = array_filter(array_map('trim', explode(',', (string) ($CFG->auth ?? ''))));
    if (!in_array('sis', $auths, true)) {
        $auths[] = 'sis';
    }
    if ($mode === 'sis-apply') {
        set_config('auth', implode(',', $auths));
        purge_all_caches();
    }
    echo 'mode=' . $mode . PHP_EOL;
    echo 'extended_usernames=' . $extended . PHP_EOL;
    echo 'sis_accounts=' . count($byEmail) . PHP_EOL;
    echo 'moodle_to_sis=' . $switch . PHP_EOL;
    echo 'already_sis=' . $same . PHP_EOL;
    echo 'left_unchanged=' . $skipped . PHP_EOL;
    echo 'sample_old=' . $sampleOld . PHP_EOL;
    echo 'sample_new=' . $sampleNew . PHP_EOL;
    exit(0);
}

$host = (string) get_config('auth_ldap', 'host_url');
$binddn = (string) get_config('auth_ldap', 'bind_dn');
$bindpw = (string) get_config('auth_ldap', 'bind_pw');
$contexts = (string) get_config('auth_ldap', 'contexts');
$attr = (string) get_config('auth_ldap', 'user_attribute');
if ($attr === '') {
    $attr = 'uid';
}
if ($host === '' || $binddn === '' || $bindpw === '' || $contexts === '') {
    fwrite(STDERR, "moodle ldap settings are incomplete\n");
    exit(1);
}

$link = ldap_connect($host);
if ($link === false) {
    fwrite(STDERR, "ldap connect failed\n");
    exit(1);
}
ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($link, LDAP_OPT_REFERRALS, 0);
ldap_set_option($link, LDAP_OPT_NETWORK_TIMEOUT, 8);
if (!@ldap_bind($link, $binddn, $bindpw)) {
    fwrite(STDERR, "ldap bind failed\n");
    exit(1);
}

$withPassword = [];
$uidByMail = [];
$ambiguousMail = [];
foreach (preg_split('/\s*;\s*/', $contexts) as $base) {
    $base = trim($base);
    if ($base === '') {
        continue;
    }
    $cookie = '';
    do {
        $controls = [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'iscritical' => true, 'value' => ['size' => 500, 'cookie' => $cookie]]];
        $search = @ldap_search($link, $base, '(' . $attr . '=*)', [$attr, 'userPassword', 'mail'], 0, 0, 0, LDAP_DEREF_NEVER, $controls);
        if ($search === false) {
            fwrite(STDERR, "ldap search failed\n");
            exit(1);
        }
        $entries = ldap_get_entries($link, $search);
        $count = (int) ($entries['count'] ?? 0);
        for ($i = 0; $i < $count; $i++) {
            $uid = strtolower((string) ($entries[$i][strtolower($attr)][0] ?? ''));
            if ($uid === '' || empty($entries[$i]['userpassword'][0])) {
                continue;
            }
            $withPassword[$uid] = true;
            $mail = strtolower(trim((string) ($entries[$i]['mail'][0] ?? '')));
            if ($mail === '' || isset($ambiguousMail[$mail])) {
                continue;
            }
            if (isset($uidByMail[$mail]) && $uidByMail[$mail] !== $uid) {
                unset($uidByMail[$mail]);
                $ambiguousMail[$mail] = true;
                continue;
            }
            $uidByMail[$mail] = $uid;
        }
        $cookie = '';
        $errcode = 0;
        $matcheddn = '';
        $errmsg = '';
        $referrals = [];
        $serverControls = [];
        if (function_exists('ldap_parse_result')) {
            ldap_parse_result($link, $search, $errcode, $matcheddn, $errmsg, $referrals, $serverControls);
            if (isset($serverControls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                $cookie = (string) $serverControls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
            }
        }
    } while ($cookie !== '');
}
ldap_unbind($link);

$protected = ['admin' => true, 'guest' => true];
$siteadmins = array_filter(array_map('intval', explode(',', (string) get_config('core', 'siteadmins'))));
$users = $DB->get_records_select('user', "deleted = 0 AND auth IN ('manual','db')", null, 'id ASC', 'id, username, auth, idnumber, email');
$switch = 0;
$skipped = 0;
$adminSkipped = 0;
$byUsername = 0;
$byIdnumber = 0;
$byMail = 0;
$byEmail = 0;
$shapes = [];
foreach ($users as $user) {
    $name = strtolower(trim((string) $user->username));
    $shape = preg_replace('/[0-9]/', '#', preg_replace('/[a-z]/', 'A', $name));
    $shapes[$shape] = ($shapes[$shape] ?? 0) + 1;
    if ($name === '' || isset($protected[$name]) || in_array((int) $user->id, $siteadmins, true)) {
        $adminSkipped++;
        continue;
    }
    $mail = strtolower(trim((string) $user->email));
    $local = strtolower(trim(strtok($mail, '@')));
    $idnumber = strtolower(trim((string) $user->idnumber));
    $ldapName = '';
    $how = '';
    if (isset($withPassword[$name])) {
        $ldapName = $name;
        $how = 'username';
    } elseif ($idnumber !== '' && isset($withPassword[$idnumber])) {
        $ldapName = $idnumber;
        $how = 'idnumber';
    } elseif ($mail !== '' && isset($uidByMail[$mail])) {
        $ldapName = $uidByMail[$mail];
        $how = 'mail';
    } elseif ($local !== '' && isset($withPassword[$local])) {
        $ldapName = $local;
        $how = 'email';
    }
    if ($ldapName === '' || !isset($withPassword[$ldapName])) {
        $skipped++;
        continue;
    }
    if ($how === 'username') {
        $byUsername++;
    } elseif ($how === 'idnumber') {
        $byIdnumber++;
    } elseif ($how === 'mail') {
        $byMail++;
    } else {
        $byEmail++;
    }
    $switch++;
    if ($mode === 'apply') {
        $taken = $DB->get_records_select('user', 'deleted = 0 AND id <> :id AND LOWER(username) = :username', [
            'id' => $user->id,
            'username' => $ldapName,
        ], '', 'id');
        if ($taken) {
            $switch--;
            $skipped++;
            continue;
        }
        $user->username = $ldapName;
        $user->auth = 'ldap';
        $user->password = 'not cached';
        $DB->update_record('user', $user);
    }
}

echo 'mode=' . $mode . PHP_EOL;
echo 'ldap_accounts_with_password=' . count($withPassword) . PHP_EOL;
echo 'moodle_manual_to_ldap=' . $switch . PHP_EOL;
echo 'ldap_mail_matches=' . count($uidByMail) . PHP_EOL;
echo 'matched_username=' . $byUsername . PHP_EOL;
echo 'matched_idnumber=' . $byIdnumber . PHP_EOL;
echo 'matched_mail=' . $byMail . PHP_EOL;
echo 'matched_email=' . $byEmail . PHP_EOL;
echo 'left_on_manual=' . $skipped . PHP_EOL;
echo 'admins_left_unchanged=' . $adminSkipped . PHP_EOL;
foreach ($shapes as $shape => $count) {
    if ($count >= 5) {
        echo 'shape_' . $count . '=' . $shape . PHP_EOL;
    }
}
exit(0);
