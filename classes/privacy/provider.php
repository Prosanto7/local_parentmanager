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
 * Privacy provider for Parent Manager plugin.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_parentmanager\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for Parent Manager plugin.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_parentmanager_rel',
            [
                'parentid' => 'privacy:metadata:local_parentmanager_rel:parentid',
                'childid' => 'privacy:metadata:local_parentmanager_rel:childid',
                'timecreated' => 'privacy:metadata:local_parentmanager_rel:timecreated',
            ],
            'privacy:metadata:local_parentmanager_rel'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Parent-child relationships are stored at system context level.
        $sql = "SELECT ctx.id
                FROM {context} ctx
                JOIN {local_parentmanager_rel} pr ON pr.parentid = :parentid OR pr.childid = :childid
                WHERE ctx.contextlevel = :contextlevel";

        $params = [
            'parentid' => $userid,
            'childid' => $userid,
            'contextlevel' => CONTEXT_SYSTEM,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        $sql = "SELECT DISTINCT parentid as userid FROM {local_parentmanager_rel}
                UNION
                SELECT DISTINCT childid as userid FROM {local_parentmanager_rel}";

        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            // Export data where user is a parent.
            $children = $DB->get_records('local_parentmanager_rel', ['parentid' => $userid]);
            if (!empty($children)) {
                $data = [];
                foreach ($children as $child) {
                    $childuser = \core_user::get_user($child->childid);
                    $data[] = [
                        'child' => fullname($childuser),
                        'timecreated' => \core_privacy\local\request\transform::datetime($child->timecreated),
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_parentmanager'), 'children'],
                    (object)['relationships' => $data]
                );
            }

            // Export data where user is a child.
            $parents = $DB->get_records('local_parentmanager_rel', ['childid' => $userid]);
            if (!empty($parents)) {
                $data = [];
                foreach ($parents as $parent) {
                    $parentuser = \core_user::get_user($parent->parentid);
                    $data[] = [
                        'parent' => fullname($parentuser),
                        'timecreated' => \core_privacy\local\request\transform::datetime($parent->timecreated),
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_parentmanager'), 'parents'],
                    (object)['relationships' => $data]
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records('local_parentmanager_rel');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            $DB->delete_records('local_parentmanager_rel', ['parentid' => $userid]);
            $DB->delete_records('local_parentmanager_rel', ['childid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            $DB->delete_records('local_parentmanager_rel', ['parentid' => $userid]);
            $DB->delete_records('local_parentmanager_rel', ['childid' => $userid]);
        }
    }
}
