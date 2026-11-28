<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_slgtiacademic_upgrade($oldversion) {
    if ($oldversion < 2026112802) {
        require_once(__DIR__ . '/install.php');
        xmldb_local_slgtiacademic_install();
        upgrade_plugin_savepoint(true, 2026112802, 'local', 'slgtiacademic');
    }
    return true;
}
