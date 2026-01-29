<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Parent Manager plugin settings.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    // Create settings page.
    $settings = new admin_settingpage('local_parentmanager_settings',
        get_string('pluginname', 'local_parentmanager'));

    if ($ADMIN->fulltree) {
        // Get all roles that can be assigned at user context.
        $roles = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);
        $roleoptions = [0 => get_string('none')];
        $systemroleoptions = [0 => get_string('none')];
        
        foreach ($roles as $role) {
            // Check if this role can be assigned at user context.
            $rolecontexts = get_role_contextlevels($role->id);
            if (in_array(CONTEXT_USER, $rolecontexts)) {
                $roleoptions[$role->id] = $role->localname;
            }
            // Check if this role can be assigned at system context.
            if (in_array(CONTEXT_SYSTEM, $rolecontexts)) {
                $systemroleoptions[$role->id] = $role->localname;
            }
        }

        // Setting for parent role at user context.
        $settings->add(new admin_setting_configselect(
            'local_parentmanager/parentrole',
            get_string('parentrole', 'local_parentmanager'),
            get_string('parentrole_desc', 'local_parentmanager'),
            0,
            $roleoptions
        ));

        // Setting for parent role at system context.
        $settings->add(new admin_setting_configselect(
            'local_parentmanager/parentsystemrole',
            get_string('parentsystemrole', 'local_parentmanager'),
            get_string('parentsystemrole_desc', 'local_parentmanager'),
            0,
            $systemroleoptions
        ));

        // Enable auto role assignment.
        $settings->add(new admin_setting_configcheckbox(
            'local_parentmanager/autoroleassign',
            get_string('autoroleassign', 'local_parentmanager'),
            get_string('autoroleassign_desc', 'local_parentmanager'),
            1
        ));
    }

    $ADMIN->add('localplugins', $settings);

    // Add the external page for managing parents.
    $ADMIN->add('accounts', new admin_externalpage(
        'local_parentmanager',
        get_string('manageparents', 'local_parentmanager'),
        new moodle_url('/local/parentmanager/index.php'),
        'local/parentmanager:manage'
    ));
}
