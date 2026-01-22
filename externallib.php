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
 * Parent Manager external API.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/lib.php');

/**
 * Parent Manager external API class.
 */
class local_parentmanager_external extends external_api {

    /**
     * Returns description of get_children parameters.
     *
     * @return external_function_parameters
     */
    public static function get_children_parameters() {
        return new external_function_parameters([
            'parentid' => new external_value(PARAM_INT, 'Parent user ID'),
        ]);
    }

    /**
     * Get children assigned to a parent.
     *
     * @param int $parentid Parent user ID
     * @return array
     */
    public static function get_children($parentid) {
        global $PAGE;

        $params = self::validate_parameters(self::get_children_parameters(), ['parentid' => $parentid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/parentmanager:manage', $context);

        $children = local_parentmanager_get_children($params['parentid']);

        $result = [];
        foreach ($children as $child) {
            $result[] = [
                'id' => $child->id,
                'relationid' => $child->relationid,
                'fullname' => fullname($child),
                'email' => $child->email,
                'profileurl' => (new moodle_url('/user/profile.php', ['id' => $child->id]))->out(false),
            ];
        }

        return ['children' => $result];
    }

    /**
     * Returns description of get_children return value.
     *
     * @return external_single_structure
     */
    public static function get_children_returns() {
        return new external_single_structure([
            'children' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'relationid' => new external_value(PARAM_INT, 'Relation ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'email' => new external_value(PARAM_TEXT, 'Email'),
                    'profileurl' => new external_value(PARAM_URL, 'Profile URL'),
                ])
            ),
        ]);
    }

    /**
     * Returns description of get_unassigned_users parameters.
     *
     * @return external_function_parameters
     */
    public static function get_unassigned_users_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get all users not assigned to any parent.
     *
     * @return array
     */
    public static function get_unassigned_users() {
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/parentmanager:manage', $context);

        $users = local_parentmanager_get_unassigned_users();

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'fullname' => fullname($user),
                'email' => $user->email,
            ];
        }

        return ['users' => $result];
    }

    /**
     * Returns description of get_unassigned_users return value.
     *
     * @return external_single_structure
     */
    public static function get_unassigned_users_returns() {
        return new external_single_structure([
            'users' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'email' => new external_value(PARAM_TEXT, 'Email'),
                ])
            ),
        ]);
    }

    /**
     * Returns description of assign_children parameters.
     *
     * @return external_function_parameters
     */
    public static function assign_children_parameters() {
        return new external_function_parameters([
            'parentid' => new external_value(PARAM_INT, 'Parent user ID'),
            'childids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Child user ID')
            ),
        ]);
    }

    /**
     * Assign children to a parent.
     *
     * @param int $parentid Parent user ID
     * @param array $childids Array of child user IDs
     * @return array
     */
    public static function assign_children($parentid, $childids) {
        $params = self::validate_parameters(self::assign_children_parameters(), [
            'parentid' => $parentid,
            'childids' => $childids,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/parentmanager:manage', $context);

        $success = local_parentmanager_assign_children($params['parentid'], $params['childids']);

        return ['success' => $success];
    }

    /**
     * Returns description of assign_children return value.
     *
     * @return external_single_structure
     */
    public static function assign_children_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }

    /**
     * Returns description of remove_child parameters.
     *
     * @return external_function_parameters
     */
    public static function remove_child_parameters() {
        return new external_function_parameters([
            'relationid' => new external_value(PARAM_INT, 'Relation ID'),
        ]);
    }

    /**
     * Remove a child from a parent.
     *
     * @param int $relationid Relation ID
     * @return array
     */
    public static function remove_child($relationid) {
        $params = self::validate_parameters(self::remove_child_parameters(), ['relationid' => $relationid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/parentmanager:manage', $context);

        $success = local_parentmanager_remove_child($params['relationid']);

        return ['success' => $success];
    }

    /**
     * Returns description of remove_child return value.
     *
     * @return external_single_structure
     */
    public static function remove_child_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }

    /**
     * Returns description of remove_parent parameters.
     *
     * @return external_function_parameters
     */
    public static function remove_parent_parameters() {
        return new external_function_parameters([
            'parentid' => new external_value(PARAM_INT, 'Parent user ID'),
        ]);
    }

    /**
     * Remove parent status.
     *
     * @param int $parentid Parent user ID
     * @return array
     */
    public static function remove_parent($parentid) {
        $params = self::validate_parameters(self::remove_parent_parameters(), ['parentid' => $parentid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/parentmanager:manage', $context);

        $success = local_parentmanager_remove_parent($params['parentid']);

        return ['success' => $success];
    }

    /**
     * Returns description of remove_parent return value.
     *
     * @return external_single_structure
     */
    public static function remove_parent_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }
}
