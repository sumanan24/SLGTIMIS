<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Create the department-administrator role and attach existing department categories.
 * Does not move courses, users, or enrolments.
 */
function xmldb_local_slgtiacademic_install() {
    global $DB, $CFG;
    require_once($CFG->libdir . '/accesslib.php');
    require_once($CFG->dirroot . '/course/lib.php');
    update_capabilities('local_slgtiacademic');

    $roleid = $DB->get_field('role', 'id', ['shortname' => 'slgti_deptadmin']);
    if (!$roleid) {
        $roleid = create_role(
            'Department administrator',
            'slgti_deptadmin',
            'Manages one SLGTI department in the LMS. This role has no site administration access.',
            'user'
        );
    }
    $sys = context_system::instance();
    foreach (['local/slgtiacademic:view', 'local/slgtiacademic:manage', 'local/slgtiacademic:viewreports'] as $cap) {
        assign_capability($cap, CAP_ALLOW, $roleid, $sys->id, true);
    }

    $roots = $DB->get_records('course_categories', ['parent' => 0], 'sortorder');
    $found = [];
    foreach ($roots as $cat) {
        $name = \core_text::strtolower($cat->name);
        if (strpos($name, 'information') !== false && strpos($name, 'communication') !== false) {
            $found['ICT'] = $cat;
        } else if (strpos($name, 'construction') !== false) {
            $found['COT'] = $cat;
        } else if (strpos($name, 'electrical') !== false) {
            $found['EET'] = $cat;
        } else if (strpos($name, 'automotive') !== false || strpos($name, 'automobile') !== false) {
            $found['AUT'] = $cat;
        } else if (strpos($name, 'food') !== false) {
            $found['FDT'] = $cat;
        } else if (strpos($name, 'mechanical') !== false) {
            $found['MEC'] = $cat;
        }
    }

    $labels = \local_slgtiacademic\departments::all();
    foreach ($labels as $code => $label) {
        if (empty($found[$code])) {
            $created = \core_course_category::create([
                'name' => 'Department of ' . $label,
                'idnumber' => 'slgti-' . strtolower($code),
                'description' => 'SLGTI department category. Existing courses were not moved.',
                'parent' => 0,
            ]);
            $found[$code] = $created;
        } else if (empty($found[$code]->idnumber)) {
            $DB->set_field('course_categories', 'idnumber', 'slgti-' . strtolower($code), ['id' => $found[$code]->id]);
        }
        set_config('cat_' . $code, (int) $found[$code]->id, 'local_slgtiacademic');
    }
}
