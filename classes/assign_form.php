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
 * Form for assigning children to a parent.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Assign children form class.
 */
class local_parentmanager_assign_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $parentid = $this->_customdata['parentid'];

        $mform->addElement('hidden', 'parentid');
        $mform->setType('parentid', PARAM_INT);
        $mform->setDefault('parentid', $parentid);

        // Get all available users (not parents, not already assigned to this parent)
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email
                FROM {user} u
                LEFT JOIN {user_info_data} uid ON u.id = uid.userid
                LEFT JOIN {user_info_field} uif ON uid.fieldid = uif.id AND uif.shortname = :shortname
                WHERE u.deleted = 0
                AND u.id != :parentid
                AND u.id NOT IN (
                    SELECT childid FROM {local_parentmanager_rel} WHERE parentid = :parentid2
                )
                AND (uid.data IS NULL OR uid.data != :isparent OR uif.id IS NULL)
                ORDER BY u.lastname, u.firstname";
        
        $params = [
            'shortname' => 'is_parent',
            'isparent' => 'Yes',
            'parentid' => $parentid,
            'parentid2' => $parentid
        ];
        
        $users = $DB->get_records_sql($sql, $params);
        
        // Build options array for autocomplete
        $options = [];
        foreach ($users as $user) {
            $options[$user->id] = fullname($user) . ' (' . $user->email . ')';
        }
        
        // Simple autocomplete with pre-loaded options (no AJAX)
        $attributes = [
            'multiple' => true,
        ];
        
        $mform->addElement('autocomplete', 'userids', get_string('selectuserstoassign', 'local_parentmanager'), $options, $attributes);
        $mform->addRule('userids', get_string('required'), 'required', null, 'client');
    }

    /**
     * Validation.
     *
     * @param array $data Data to validate
     * @param array $files Files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['userids']) || !is_array($data['userids'])) {
            $errors['userids'] = get_string('noselectederror', 'local_parentmanager');
        }

        return $errors;
    }
}
