<?php
namespace local_slgtiacademic;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only academic queries plus manual department overrides.
 * Enrolment changes go through Moodle's enrol plugins, not direct table writes.
 */
class service {
    /** @var array|null */
    private static $cache = null;

    public static function reset(): void {
        self::$cache = null;
    }

    /**
     * What the signed-in person is allowed to see.
     *
     * @return array{level:string,departments:array,courseids:?array,manage:bool,reports:bool,scopes:bool}
     */
    public static function viewer(): array {
        global $DB, $USER;
        $none = [
            'level' => 'none', 'departments' => [], 'courseids' => [],
            'manage' => false, 'reports' => false, 'scopes' => false,
        ];
        if (!isloggedin() || isguestuser()) {
            return $none;
        }
        $system = \context_system::instance();
        $all = is_siteadmin() || has_capability('local/slgtiacademic:viewall', $system);
        $manage = $all || has_capability('local/slgtiacademic:manage', $system);
        $reports = $all || has_capability('local/slgtiacademic:viewreports', $system);
        $scopes = [];
        if ($DB->get_manager()->table_exists('local_slgtiacademic_scope')) {
            $scopes = $DB->get_fieldset_select(
                'local_slgtiacademic_scope',
                'department',
                'userid = ?',
                [$USER->id]
            );
        }
        if ($all) {
            return [
                'level' => 'all',
                'departments' => array_keys(departments::all()),
                'courseids' => null,
                'manage' => true,
                'reports' => true,
                'scopes' => true,
            ];
        }
        if ($scopes && has_capability('local/slgtiacademic:view', $system)) {
            return [
                'level' => 'department',
                'departments' => array_values(array_unique($scopes)),
                'courseids' => null,
                'manage' => $manage,
                'reports' => $reports,
                'scopes' => false,
            ];
        }
        if (has_capability('local/slgtiacademic:view', $system)) {
            $courseids = self::teaching_course_ids((int) $USER->id);
            if ($courseids) {
                return [
                    'level' => 'course',
                    'departments' => [],
                    'courseids' => $courseids,
                    'manage' => false,
                    'reports' => false,
                    'scopes' => false,
                ];
            }
        }
        return $none;
    }

    /**
     * @return int[]
     */
    public static function teaching_course_ids(int $userid): array {
        global $DB;
        $ids = $DB->get_fieldset_sql(
            "SELECT DISTINCT ctx.instanceid
               FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
               JOIN {role} r ON r.id = ra.roleid
              WHERE ra.userid = :userid
                AND r.shortname IN ('editingteacher', 'teacher')",
            ['level' => CONTEXT_COURSE, 'userid' => $userid]
        );
        return array_map('intval', $ids);
    }

    /**
     * @return array
     */
    private static function data(): array {
        global $DB;
        if (self::$cache !== null) {
            return self::$cache;
        }
        $categories = $DB->get_records('course_categories', null, '', 'id,name,parent,visible');
        $parent = [];
        foreach ($categories as $cat) {
            $parent[(int) $cat->id] = (int) $cat->parent;
        }
        $rootof = [];
        foreach (departments::all() as $code => $unused) {
            $id = (int) get_config('local_slgtiacademic', 'cat_' . $code);
            if ($id > 0) {
                $rootof[$id] = $code;
            }
        }
        $courses = $DB->get_records_select(
            'course',
            'id > 1',
            null,
            'fullname ASC',
            'id,shortname,fullname,visible,category,startdate,enddate'
        );
        $coursedept = [];
        foreach ($courses as $course) {
            $coursedept[(int) $course->id] = self::course_code($course, $parent, $rootof);
        }
        $maps = [];
        if ($DB->get_manager()->table_exists('local_slgtiacademic_map')) {
            $rows = $DB->get_records('local_slgtiacademic_map', null, '', 'userid,department');
            foreach ($rows as $row) {
                $maps[(int) $row->userid] = (string) $row->department;
            }
        }
        $enrolments = $DB->get_records_sql(
            "SELECT ue.id, ue.userid, ue.status, ue.timestart, ue.timeend, ue.timecreated,
                    e.enrol, e.courseid
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
               JOIN {course} c ON c.id = e.courseid
              WHERE c.id > 1"
        );
        $users = $DB->get_records_select(
            'user',
            'deleted = 0 AND id > 1',
            null,
            'lastname ASC, firstname ASC',
            'id,username,firstname,lastname,email,suspended,auth,lastaccess'
        );
        $teachers = $DB->get_records_sql(
            "SELECT ra.userid, ctx.instanceid AS courseid, r.shortname
               FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
               JOIN {role} r ON r.id = ra.roleid
              WHERE r.shortname IN ('editingteacher', 'teacher')",
            ['level' => CONTEXT_COURSE]
        );
        $studentrole = [];
        $roleusers = $DB->get_fieldset_sql(
            "SELECT DISTINCT ra.userid
               FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :level
               JOIN {role} r ON r.id = ra.roleid
              WHERE r.shortname = 'student'",
            ['level' => CONTEXT_COURSE]
        );
        foreach ($roleusers as $id) {
            $studentrole[(int) $id] = true;
        }
        $completed = $DB->get_records_select(
            'course_completions',
            'timecompleted > 0',
            null,
            '',
            'id, userid, course, timecompleted'
        );
        $capacity = [];
        $selfs = $DB->get_records_sql(
            "SELECT courseid, customint3 FROM {enrol} WHERE enrol = 'self' AND customint3 > 0"
        );
        foreach ($selfs as $row) {
            $capacity[(int) $row->courseid] = (int) $row->customint3;
        }

        self::$cache = compact(
            'categories', 'parent', 'courses', 'coursedept', 'maps', 'enrolments',
            'users', 'teachers', 'studentrole', 'completed', 'capacity'
        );
        return self::$cache;
    }

    private static function course_code(\stdClass $course, array $parent, array $rootof): string {
        $id = (int) $course->category;
        $guard = 0;
        while ($id > 0 && $guard < 30) {
            if (isset($rootof[$id])) {
                return $rootof[$id];
            }
            $id = $parent[$id] ?? 0;
            $guard++;
        }
        $text = \core_text::strtolower($course->fullname . ' ' . $course->shortname);
        $needles = [
            'AUT' => ['automobile', 'automotive', 'agriculture'],
            'FDT' => ['food technology', 'food '],
            'MEC' => ['mechanical', 'machinist', 'welder'],
            'COT' => ['construction', 'drafting'],
            'EET' => ['electrical', 'mechatronic', 'renewable'],
            'ICT' => ['information and communication', 'computer', 'software'],
        ];
        foreach ($needles as $code => $words) {
            foreach ($words as $word) {
                if (strpos($text, $word) !== false) {
                    return $code;
                }
            }
        }
        return '';
    }

    public static function parsed_username(string $username): array {
        $username = \core_text::strtolower(trim($username));
        $out = ['year' => '', 'department' => '', 'nvq' => '', 'studentid' => $username];
        if (preg_match('/^((?:19|20)\d{2})(aut|cot|eet|fdt|ict|mec)([4-6])?/', $username, $match)) {
            $out['year'] = $match[1];
            $out['department'] = strtoupper($match[2]);
            $out['nvq'] = $match[3] ?? '';
        }
        return $out;
    }

    /**
     * Resolved department for one user. Manual override wins.
     */
    public static function user_department(int $userid): string {
        $data = self::data();
        if (array_key_exists($userid, $data['maps'])) {
            return (string) $data['maps'][$userid];
        }
        $user = $data['users'][$userid] ?? null;
        if (!$user) {
            return '';
        }
        $parsed = self::parsed_username($user->username);
        if ($parsed['department'] !== '') {
            return $parsed['department'];
        }
        $counts = [];
        foreach ($data['enrolments'] as $enrol) {
            if ((int) $enrol->userid !== $userid || (int) $enrol->status !== 0) {
                continue;
            }
            $code = $data['coursedept'][(int) $enrol->courseid] ?? '';
            if ($code === '') {
                continue;
            }
            $counts[$code] = ($counts[$code] ?? 0) + 1;
        }
        if (!$counts) {
            return '';
        }
        arsort($counts);
        return (string) array_key_first($counts);
    }

    public static function can_see_department(string $code): bool {
        $viewer = self::viewer();
        if ($viewer['level'] === 'all') {
            return true;
        }
        if ($viewer['level'] === 'department') {
            return in_array($code, $viewer['departments'], true);
        }
        return false;
    }

    public static function course_allowed(int $courseid): bool {
        $viewer = self::viewer();
        if ($viewer['level'] === 'none') {
            return false;
        }
        if ($viewer['level'] === 'course') {
            return in_array($courseid, $viewer['courseids'], true);
        }
        $data = self::data();
        $code = $data['coursedept'][$courseid] ?? '';
        if ($viewer['level'] === 'all') {
            return true;
        }
        return $code !== '' && in_array($code, $viewer['departments'], true);
    }

    public static function user_allowed(int $userid): bool {
        $viewer = self::viewer();
        if ($viewer['level'] === 'all') {
            return isset(self::data()['users'][$userid]);
        }
        if ($viewer['level'] === 'department') {
            return in_array(self::user_department($userid), $viewer['departments'], true);
        }
        if ($viewer['level'] === 'course') {
            foreach (self::data()['enrolments'] as $enrol) {
                if ((int) $enrol->userid === $userid && in_array((int) $enrol->courseid, $viewer['courseids'], true)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array $filters
     * @return array{0:array,1:int}
     */
    public static function students(array $filters, int $page, int $perpage): array {
        $rows = self::student_rows($filters);
        $total = count($rows);
        return [array_slice($rows, $page * $perpage, $perpage), $total];
    }

    public static function student_rows(array $filters): array {
        $data = self::data();
        $viewer = self::viewer();
        $q = \core_text::strtolower(trim($filters['q'] ?? ''));
        $dept = strtoupper(trim($filters['department'] ?? ''));
        $courseid = (int) ($filters['courseid'] ?? 0);
        $year = trim($filters['year'] ?? '');
        $account = $filters['account'] ?? '';
        $enrolstate = $filters['enrolstate'] ?? '';
        $rows = [];
        foreach ($data['users'] as $user) {
            $isstudent = !empty($data['studentrole'][(int) $user->id]) || $user->auth === 'sis';
            if (!$isstudent) {
                continue;
            }
            if (!self::user_allowed((int) $user->id) && $viewer['level'] !== 'all') {
                if ($viewer['level'] !== 'department' || !in_array(self::user_department((int) $user->id), $viewer['departments'], true)) {
                    continue;
                }
            }
            if ($viewer['level'] === 'course' && !self::user_allowed((int) $user->id)) {
                continue;
            }
            if ($viewer['level'] === 'department' && !in_array(self::user_department((int) $user->id), $viewer['departments'], true)) {
                continue;
            }
            $code = self::user_department((int) $user->id);
            if ($dept === 'NONE' && $code !== '') {
                continue;
            }
            if ($dept !== '' && $dept !== 'NONE' && $code !== $dept) {
                continue;
            }
            $parsed = self::parsed_username($user->username);
            if ($year !== '' && $parsed['year'] !== $year) {
                continue;
            }
            if ($account === 'active' && (int) $user->suspended === 1) {
                continue;
            }
            if ($account === 'suspended' && (int) $user->suspended !== 1) {
                continue;
            }
            $usercourses = self::user_course_ids((int) $user->id);
            if ($courseid && !in_array($courseid, $usercourses, true)) {
                continue;
            }
            $enrolled = count($usercourses) > 0;
            if ($enrolstate === 'enrolled' && !$enrolled) {
                continue;
            }
            if ($enrolstate === 'notenrolled' && $enrolled) {
                continue;
            }
            $haystack = \core_text::strtolower($user->firstname . ' ' . $user->lastname . ' ' . $user->username . ' ' . $user->email);
            if ($q !== '' && strpos($haystack, $q) === false) {
                continue;
            }
            $rows[] = self::student_record($user, $code, $parsed, $usercourses);
        }
        return $rows;
    }

    private static function user_course_ids(int $userid): array {
        $ids = [];
        foreach (self::data()['enrolments'] as $enrol) {
            if ((int) $enrol->userid === $userid && (int) $enrol->status === 0) {
                $ids[] = (int) $enrol->courseid;
            }
        }
        return array_values(array_unique($ids));
    }

    private static function student_record(\stdClass $user, string $code, array $parsed, array $courseids): array {
        $data = self::data();
        $names = [];
        foreach ($courseids as $id) {
            if (isset($data['courses'][$id])) {
                $names[] = $data['courses'][$id]->fullname;
            }
        }
        $done = 0;
        foreach ($data['completed'] as $row) {
            if ((int) $row->userid === (int) $user->id) {
                $done++;
            }
        }
        return [
            'id' => (int) $user->id,
            'fullname' => fullname($user),
            'username' => $user->username,
            'email' => $user->email,
            'department' => $code,
            'departmentname' => departments::name($code),
            'year' => $parsed['year'],
            'nvq' => $parsed['nvq'],
            'qualification' => $names ? implode('; ', $names) : get_string('unassigned', 'local_slgtiacademic'),
            'courses' => count($courseids),
            'completed' => $done,
            'suspended' => (int) $user->suspended,
            'status' => (int) $user->suspended ? get_string('suspended', 'local_slgtiacademic') : get_string('active', 'local_slgtiacademic'),
            'lastaccess' => (int) $user->lastaccess,
            'auth' => $user->auth,
            'manual' => array_key_exists((int) $user->id, $data['maps']),
        ];
    }

    public static function student_profile(int $userid): ?array {
        if (!self::user_allowed($userid)) {
            return null;
        }
        $data = self::data();
        $user = $data['users'][$userid] ?? null;
        if (!$user) {
            return null;
        }
        $code = self::user_department($userid);
        $record = self::student_record($user, $code, self::parsed_username($user->username), self::user_course_ids($userid));
        $record['enrolments'] = [];
        foreach ($data['enrolments'] as $enrol) {
            if ((int) $enrol->userid !== $userid) {
                continue;
            }
            if (!self::course_allowed((int) $enrol->courseid) && self::viewer()['level'] !== 'all') {
                if (self::viewer()['level'] === 'department' && !self::course_allowed((int) $enrol->courseid)) {
                    continue;
                }
                if (self::viewer()['level'] === 'course') {
                    continue;
                }
            }
            $course = $data['courses'][(int) $enrol->courseid] ?? null;
            if (!$course) {
                continue;
            }
            $record['enrolments'][] = [
                'course' => $course->fullname,
                'courseid' => (int) $course->id,
                'departmentname' => departments::name($data['coursedept'][(int) $course->id] ?? ''),
                'method' => $enrol->enrol,
                'status' => (int) $enrol->status === 0 ? get_string('active', 'local_slgtiacademic') : get_string('suspended', 'local_slgtiacademic'),
                'timestart' => (int) $enrol->timestart,
                'timeend' => (int) $enrol->timeend,
            ];
        }
        return $record;
    }

    public static function courses(array $filters): array {
        $data = self::data();
        $viewer = self::viewer();
        $dept = strtoupper(trim($filters['department'] ?? ''));
        $q = \core_text::strtolower(trim($filters['q'] ?? ''));
        $visible = $filters['visible'] ?? '';
        $counts = [];
        foreach ($data['enrolments'] as $enrol) {
            if ((int) $enrol->status !== 0) {
                continue;
            }
            $counts[(int) $enrol->courseid] = ($counts[(int) $enrol->courseid] ?? 0) + 1;
        }
        $rows = [];
        foreach ($data['courses'] as $course) {
            $id = (int) $course->id;
            if (!self::course_allowed($id)) {
                continue;
            }
            $code = $data['coursedept'][$id] ?? '';
            if ($viewer['level'] === 'department' && !in_array($code, $viewer['departments'], true)) {
                continue;
            }
            if ($dept !== '' && $code !== $dept) {
                continue;
            }
            if ($visible === '1' && (int) $course->visible !== 1) {
                continue;
            }
            if ($visible === '0' && (int) $course->visible === 1) {
                continue;
            }
            $hay = \core_text::strtolower($course->fullname . ' ' . $course->shortname);
            if ($q !== '' && strpos($hay, $q) === false) {
                continue;
            }
            $rows[] = [
                'id' => $id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'department' => $code,
                'departmentname' => departments::name($code),
                'visible' => (int) $course->visible,
                'enrolled' => $counts[$id] ?? 0,
                'capacity' => $data['capacity'][$id] ?? 0,
                'startdate' => (int) $course->startdate,
                'enddate' => (int) $course->enddate,
                'category' => $data['categories'][(int) $course->category]->name ?? '',
            ];
        }
        return $rows;
    }

    public static function enrolments(array $filters): array {
        $data = self::data();
        $dept = strtoupper(trim($filters['department'] ?? ''));
        $courseid = (int) ($filters['courseid'] ?? 0);
        $q = \core_text::strtolower(trim($filters['q'] ?? ''));
        $method = trim($filters['method'] ?? '');
        $status = $filters['status'] ?? '';
        $year = trim($filters['year'] ?? '');
        $from = (int) ($filters['from'] ?? 0);
        $to = (int) ($filters['to'] ?? 0);
        $rows = [];
        foreach ($data['enrolments'] as $enrol) {
            $cid = (int) $enrol->courseid;
            if (!self::course_allowed($cid)) {
                continue;
            }
            $code = $data['coursedept'][$cid] ?? '';
            if ($dept !== '' && $code !== $dept) {
                continue;
            }
            if ($courseid && $cid !== $courseid) {
                continue;
            }
            if ($method !== '' && $enrol->enrol !== $method) {
                continue;
            }
            if ($status === 'active' && (int) $enrol->status !== 0) {
                continue;
            }
            if ($status === 'suspended' && (int) $enrol->status === 0) {
                continue;
            }
            $start = (int) ($enrol->timestart ?: $enrol->timecreated);
            if ($year !== '' && date('Y', $start) !== $year) {
                continue;
            }
            if ($from && $start < $from) {
                continue;
            }
            if ($to && $start > $to) {
                continue;
            }
            $user = $data['users'][(int) $enrol->userid] ?? null;
            if (!$user) {
                continue;
            }
            $course = $data['courses'][$cid];
            $hay = \core_text::strtolower(fullname($user) . ' ' . $user->username . ' ' . $course->fullname);
            if ($q !== '' && strpos($hay, $q) === false) {
                continue;
            }
            $rows[] = [
                'userid' => (int) $user->id,
                'fullname' => fullname($user),
                'username' => $user->username,
                'departmentname' => departments::name($code),
                'course' => $course->fullname,
                'courseid' => $cid,
                'method' => $enrol->enrol,
                'status' => (int) $enrol->status === 0 ? get_string('active', 'local_slgtiacademic') : get_string('suspended', 'local_slgtiacademic'),
                'timestart' => (int) $enrol->timestart,
                'timeend' => (int) $enrol->timeend,
                'coursestart' => (int) $course->startdate,
                'courseend' => (int) $course->enddate,
                'year' => $start ? date('Y', $start) : '',
            ];
        }
        return $rows;
    }

    public static function instructors(array $filters): array {
        $data = self::data();
        $viewer = self::viewer();
        $dept = strtoupper(trim($filters['department'] ?? ''));
        $q = \core_text::strtolower(trim($filters['q'] ?? ''));
        $byuser = [];
        foreach ($data['teachers'] as $row) {
            $cid = (int) $row->courseid;
            if (!self::course_allowed($cid)) {
                continue;
            }
            $code = $data['coursedept'][$cid] ?? '';
            if ($viewer['level'] === 'department' && $code !== '' && !in_array($code, $viewer['departments'], true)) {
                continue;
            }
            if ($dept !== '' && $code !== $dept) {
                continue;
            }
            $uid = (int) $row->userid;
            if (!isset($byuser[$uid])) {
                $user = $data['users'][$uid] ?? null;
                if (!$user) {
                    continue;
                }
                $byuser[$uid] = [
                    'id' => $uid,
                    'fullname' => fullname($user),
                    'username' => $user->username,
                    'email' => $user->email,
                    'courses' => [],
                    'departments' => [],
                    'students' => 0,
                ];
            }
            $byuser[$uid]['courses'][$cid] = $data['courses'][$cid]->fullname ?? '';
            if ($code !== '') {
                $byuser[$uid]['departments'][$code] = departments::name($code);
            }
        }
        $enrolled = [];
        foreach ($data['enrolments'] as $enrol) {
            if ((int) $enrol->status === 0) {
                $enrolled[(int) $enrol->courseid] = ($enrolled[(int) $enrol->courseid] ?? 0) + 1;
            }
        }
        $rows = [];
        foreach ($byuser as $row) {
            $hay = \core_text::strtolower($row['fullname'] . ' ' . $row['username']);
            if ($q !== '' && strpos($hay, $q) === false) {
                continue;
            }
            $students = 0;
            foreach (array_keys($row['courses']) as $cid) {
                $students += $enrolled[$cid] ?? 0;
            }
            $row['coursecount'] = count($row['courses']);
            $row['coursenames'] = implode('; ', $row['courses']);
            $row['departmentname'] = $row['departments'] ? implode(', ', $row['departments']) : departments::name('');
            $row['students'] = $students;
            unset($row['courses'], $row['departments']);
            $rows[] = $row;
        }
        return $rows;
    }

    public static function stats(): array {
        $data = self::data();
        $viewer = self::viewer();
        $students = self::student_rows(['department' => '', 'q' => '', 'year' => '', 'account' => '', 'enrolstate' => '']);
        $courses = self::courses([]);
        $enrolments = self::enrolments([]);
        $now = time();
        $activestudents = 0;
        $suspended = 0;
        $inactive = 0;
        $bydept = array_fill_keys(array_keys(departments::all()), 0);
        $bydept[''] = 0;
        foreach ($students as $student) {
            if ($student['suspended']) {
                $suspended++;
            } else {
                $activestudents++;
            }
            $seen = $student['lastaccess'] > 0 && $student['lastaccess'] >= ($now - (90 * DAYSECS));
            if (!$seen) {
                $inactive++;
            }
            $key = $student['department'];
            if (!isset($bydept[$key])) {
                $bydept[$key] = 0;
            }
            $bydept[$key]++;
        }
        $courseby = array_fill_keys(array_keys(departments::all()), 0);
        $courseby[''] = 0;
        $activecourses = 0;
        foreach ($courses as $course) {
            if ($course['visible']) {
                $activecourses++;
            }
            $key = $course['department'];
            if (!isset($courseby[$key])) {
                $courseby[$key] = 0;
            }
            $courseby[$key]++;
        }
        $enrolby = array_fill_keys(array_keys(departments::all()), 0);
        $enrolby[''] = 0;
        $pending = 0;
        $trend = [];
        foreach ($enrolments as $enrol) {
            if ($enrol['status'] === get_string('active', 'local_slgtiacademic') && $enrol['timestart'] > $now) {
                $pending++;
            }
            $code = '';
            foreach (departments::all() as $c => $name) {
                if ($enrol['departmentname'] === $name) {
                    $code = $c;
                }
            }
            if (!isset($enrolby[$code])) {
                $enrolby[$code] = 0;
            }
            $enrolby[$code]++;
            $year = $enrol['year'] ?: 'Unknown';
            $trend[$year] = ($trend[$year] ?? 0) + 1;
        }
        ksort($trend);
        $completed = 0;
        $seenpairs = [];
        foreach ($data['completed'] as $row) {
            $cid = (int) $row->course;
            if (!self::course_allowed($cid)) {
                continue;
            }
            $completed++;
            $seenpairs[(int) $row->userid . '-' . $cid] = true;
        }
        $deptcount = 0;
        foreach (departments::all() as $code => $name) {
            if ($viewer['level'] === 'all' || in_array($code, $viewer['departments'], true) || $viewer['level'] === 'course') {
                $deptcount++;
            }
        }
        if ($viewer['level'] === 'course') {
            $deptcount = count(array_unique(array_map(function($id) use ($data) {
                return $data['coursedept'][$id] ?? '';
            }, $viewer['courseids'])));
        }
        return [
            'students' => count($students),
            'staff' => count(self::instructors([])),
            'departments' => $deptcount,
            'courses' => count($courses),
            'activecourses' => $activecourses,
            'enrolments' => count($enrolments),
            'activestudents' => $activestudents,
            'suspended' => $suspended,
            'pending' => $pending,
            'completed' => $completed,
            'inactive' => $inactive,
            'seen' => max(0, count($students) - $inactive),
            'studentsby' => $bydept,
            'coursesby' => $courseby,
            'enrolby' => $enrolby,
            'trend' => $trend,
        ];
    }

    public static function chart(string $title, array $labels, array $values, string $type = 'bar'): \core\chart_base {
        if ($type === 'line') {
            $chart = new \core\chart_line();
        } else if ($type === 'pie') {
            $chart = new \core\chart_pie();
        } else {
            $chart = new \core\chart_bar();
        }
        $chart->set_title($title);
        $series = new \core\chart_series($title, array_map('intval', array_values($values)));
        $chart->add_series($series);
        $chart->set_labels(array_values($labels));
        return $chart;
    }

    public static function save_mapping(int $userid, string $department): void {
        global $DB, $USER;
        $viewer = self::viewer();
        if (!$viewer['manage']) {
            throw new \required_capability_exception(\context_system::instance(), 'local/slgtiacademic:manage', 'nopermissions', '');
        }
        $department = strtoupper(trim($department));
        if ($department !== '' && !departments::valid($department)) {
            throw new \moodle_exception('invalidparameter');
        }
        $current = self::user_department($userid);
        if ($viewer['level'] !== 'all') {
            if (!in_array($department, $viewer['departments'], true) || $current !== '') {
                throw new \moodle_exception('nopermissions', 'error', '', 'department');
            }
        }
        $existing = $DB->get_record('local_slgtiacademic_map', ['userid' => $userid]);
        if ($existing) {
            $existing->department = $department;
            $existing->timemodified = time();
            $DB->update_record('local_slgtiacademic_map', $existing);
        } else {
            $DB->insert_record('local_slgtiacademic_map', (object) [
                'userid' => $userid,
                'department' => $department,
                'timemodified' => time(),
            ]);
        }
        self::reset();
    }

    public static function clear_mapping(int $userid): void {
        global $DB;
        if (self::viewer()['level'] !== 'all') {
            throw new \moodle_exception('nopermissions', 'error', '', 'department');
        }
        $DB->delete_records('local_slgtiacademic_map', ['userid' => $userid]);
        self::reset();
    }

    public static function assign_scope(int $userid, string $department): void {
        global $DB, $CFG;
        if (self::viewer()['level'] !== 'all') {
            throw new \moodle_exception('nopermissions', 'error', '', 'department');
        }
        if ($userid < 2 || is_siteadmin($userid) || isguestuser($userid)) {
            throw new \moodle_exception('invalidparameter');
        }
        $department = strtoupper($department);
        if (!departments::valid($department)) {
            throw new \moodle_exception('invalidparameter');
        }
        if (!$DB->record_exists('local_slgtiacademic_scope', ['userid' => $userid, 'department' => $department])) {
            $DB->insert_record('local_slgtiacademic_scope', (object) [
                'userid' => $userid,
                'department' => $department,
                'timecreated' => time(),
            ]);
        }
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'slgti_deptadmin']);
        if ($roleid) {
            role_assign($roleid, $userid, \context_system::instance()->id);
        }
        self::reset();
    }

    public static function remove_scope(int $scopeid): void {
        global $DB;
        if (self::viewer()['level'] !== 'all') {
            throw new \moodle_exception('nopermissions', 'error', '', 'department');
        }
        $row = $DB->get_record('local_slgtiacademic_scope', ['id' => $scopeid], '*', MUST_EXIST);
        $DB->delete_records('local_slgtiacademic_scope', ['id' => $scopeid]);
        if (!$DB->record_exists('local_slgtiacademic_scope', ['userid' => $row->userid])) {
            $roleid = $DB->get_field('role', 'id', ['shortname' => 'slgti_deptadmin']);
            if ($roleid) {
                role_unassign($roleid, $row->userid, \context_system::instance()->id);
            }
        }
        self::reset();
    }

    /**
     * Enrol through the manual enrolment plugin. Existing enrolments are left as they are.
     *
     * @param int[] $userids
     * @return array{ok:int,skipped:int,failed:array}
     */
    public static function bulk_enrol(int $courseid, array $userids, string $rolename): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/enrol/manual/locallib.php');
        $viewer = self::viewer();
        if (!$viewer['manage'] || !self::course_allowed($courseid)) {
            throw new \moodle_exception('nopermissions', 'error', '', 'enrol');
        }
        if (!in_array($rolename, ['student', 'editingteacher', 'teacher'], true)) {
            throw new \moodle_exception('invalidparameter');
        }
        $plugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual', 'status' => ENROL_INSTANCE_ENABLED]);
        if (!$plugin || !$instance) {
            return ['ok' => 0, 'skipped' => 0, 'failed' => [get_string('nomanual', 'local_slgtiacademic')]];
        }
        $roleid = $DB->get_field('role', 'id', ['shortname' => $rolename], MUST_EXIST);
        $context = \context_course::instance($courseid);
        $result = ['ok' => 0, 'skipped' => 0, 'failed' => []];
        foreach ($userids as $userid) {
            $userid = (int) $userid;
            if ($userid < 2 || !self::user_allowed($userid)) {
                $result['failed'][] = $userid . ' ' . get_string('outside', 'local_slgtiacademic');
                continue;
            }
            if (is_enrolled($context, $userid, '', true)) {
                $result['skipped']++;
                continue;
            }
            $plugin->enrol_user($instance, $userid, $roleid);
            $result['ok']++;
        }
        self::reset();
        return $result;
    }

    public static function scoped_admins(): array {
        global $DB;
        $rows = $DB->get_records('local_slgtiacademic_scope', null, 'department, userid');
        $out = [];
        foreach ($rows as $row) {
            $user = self::data()['users'][(int) $row->userid] ?? $DB->get_record('user', ['id' => $row->userid], 'id,firstname,lastname,username,email,suspended,auth,lastaccess,deleted');
            if (!$user || !empty($user->deleted)) {
                continue;
            }
            $out[] = [
                'id' => (int) $row->id,
                'userid' => (int) $row->userid,
                'fullname' => fullname($user),
                'username' => $user->username,
                'departmentname' => departments::name($row->department),
            ];
        }
        return $out;
    }

    public static function selectable_courses(): array {
        $rows = [];
        foreach (self::courses([]) as $course) {
            $rows[$course['id']] = $course['departmentname'] . ' — ' . $course['fullname'];
        }
        return $rows;
    }

    /**
     * Summary for a known user id. Callers must already have decided the viewer may see it.
     */
    public static function summary(int $userid): array {
        $data = self::data();
        $user = $data['users'][$userid] ?? null;
        if (!$user) {
            return [];
        }
        return self::student_record($user, self::user_department($userid), self::parsed_username($user->username), self::user_course_ids($userid));
    }

    public static function selectable_departments(): array {
        $viewer = self::viewer();
        $rows = [];
        foreach (departments::all() as $code => $name) {
            if ($viewer['level'] === 'all' || in_array($code, $viewer['departments'], true)) {
                $rows[$code] = $name;
            }
        }
        if ($viewer['level'] === 'all') {
            $rows['NONE'] = get_string('unassigned', 'local_slgtiacademic');
        }
        return $rows;
    }
}
