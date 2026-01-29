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
 * Form for marking users as parents.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Mark users as parents form class.
 */
class local_parentmanager_mark_parent_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB;
        
        $mform = $this->_form;

        // Get all non-parent users.
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email
                FROM {user} u
                WHERE u.deleted = 0
                AND u.id > 2
                -- Exclude parent users
                AND u.id NOT IN (
                    SELECT userid
                    FROM {local_parentmanager_parents}
                )
                -- Exclude users assigned as children
                AND u.id NOT IN (
                    SELECT childid
                    FROM {local_parentmanager_rel}
                )
                ORDER BY u.lastname, u.firstname";
        
        $users = $DB->get_records_sql($sql);
        
        // Build options array for autocomplete.
        $options = [];
        foreach ($users as $user) {
            $options[$user->id] = fullname($user) . ' (' . $user->email . ')';
        }
        
        // Autocomplete with pre-loaded options.
        $attributes = [
            'multiple' => true,
        ];
        
        $mform->addElement('autocomplete', 'userids', get_string('selectuserstomarkasparent', 'local_parentmanager'), $options, $attributes);
        $mform->addRule('userids', get_string('required'), 'required', null, 'client');
        
        // Add buttons.
        $this->add_action_buttons(false, get_string('markasparent', 'local_parentmanager'));
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
