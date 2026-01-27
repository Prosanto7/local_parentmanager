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
 * Parent Manager plugin library functions.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get all parent users based on custom profile field.
 *
 * @return array Array of parent users
 */
function local_parentmanager_get_parents() {
    global $DB;

    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.lastaccess,
                   (SELECT COUNT(*) FROM {local_parentmanager_rel} pr WHERE pr.parentid = u.id) as childcount
            FROM {user} u
            JOIN {user_info_data} uid ON u.id = uid.userid
            JOIN {user_info_field} uif ON uid.fieldid = uif.id
            WHERE uif.shortname = :shortname
            AND uid.data = :isparent
            AND u.deleted = 0
            ORDER BY u.lastname, u.firstname";

    return $DB->get_records_sql($sql, ['shortname' => 'is_parent', 'isparent' => 'Yes']);
}

/**
 * Get children assigned to a parent.
 *
 * @param int $parentid Parent user ID
 * @return array Array of child users
 */
function local_parentmanager_get_children($parentid) {
    global $DB;

    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, pr.id as relationid
            FROM {user} u
            JOIN {local_parentmanager_rel} pr ON u.id = pr.childid
            WHERE pr.parentid = :parentid
            AND u.deleted = 0
            ORDER BY u.lastname, u.firstname";

    return $DB->get_records_sql($sql, ['parentid' => $parentid]);
}

/**
 * Get all users who are not assigned to any parent.
 *
 * @return array Array of unassigned users
 */
function local_parentmanager_get_unassigned_users() {
    global $DB;

    $sql = "SELECT u.id, u.firstname, u.lastname, u.email
            FROM {user} u
            LEFT JOIN {user_info_data} uid ON u.id = uid.userid
            LEFT JOIN {user_info_field} uif ON uid.fieldid = uif.id AND uif.shortname = :shortname
            WHERE u.deleted = 0
            AND u.id NOT IN (SELECT childid FROM {local_parentmanager_rel})
            AND (uid.data IS NULL OR uid.data != :isparent OR uif.id IS NULL)
            ORDER BY u.lastname, u.firstname";

    return $DB->get_records_sql($sql, ['shortname' => 'is_parent', 'isparent' => 'Yes']);
}

/**
 * Assign children to a parent.
 *
 * @param int $parentid Parent user ID
 * @param array $childids Array of child user IDs
 * @return bool Success status
 */
function local_parentmanager_assign_children($parentid, $childids) {
    global $DB;

    $success = true;
    foreach ($childids as $childid) {
        // Check if relationship already exists.
        if (!$DB->record_exists('local_parentmanager_rel', ['parentid' => $parentid, 'childid' => $childid])) {
            $record = new stdClass();
            $record->parentid = $parentid;
            $record->childid = $childid;
            $record->timecreated = time();

            try {
                $DB->insert_record('local_parentmanager_rel', $record);
                
                // Assign parent role in child's user context if auto-assign is enabled.
                local_parentmanager_assign_parent_role($parentid, $childid);
            } catch (Exception $e) {
                $success = false;
            }
        }
    }

    return $success;
}

/**
 * Remove a child from a parent.
 *
 * @param int $relationid Relationship ID
 * @return bool Success status
 */
function local_parentmanager_remove_child($relationid) {
    global $DB;
    
    // Get the relationship details before deleting.
    $relation = $DB->get_record('local_parentmanager_rel', ['id' => $relationid]);
    if ($relation) {
        // Unassign parent role from child's user context.
        local_parentmanager_unassign_parent_role($relation->parentid, $relation->childid);
    }
    
    return $DB->delete_records('local_parentmanager_rel', ['id' => $relationid]);
}

/**
 * Update user's is_parent profile field.
 *
 * @param int $userid User ID
 * @param string $value New value (Yes/No)
 * @return bool Success status
 */
function local_parentmanager_update_parent_status($userid, $value) {
    global $DB;

    // Get the custom field ID.
    $field = $DB->get_record('user_info_field', ['shortname' => 'is_parent']);
    if (!$field) {
        return false;
    }

    // Check if data exists.
    $datarecord = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);

    if ($datarecord) {
        $datarecord->data = $value;
        return $DB->update_record('user_info_data', $datarecord);
    } else {
        $datarecord = new stdClass();
        $datarecord->userid = $userid;
        $datarecord->fieldid = $field->id;
        $datarecord->data = $value;
        $datarecord->dataformat = 0;
        return $DB->insert_record('user_info_data', $datarecord);
    }
}

/**
 * Remove parent status and all relationships.
 *
 * @param int $parentid Parent user ID
 * @return bool Success status
 */
function local_parentmanager_remove_parent($parentid) {
    global $DB;

    // Get all children for this parent before removing relationships.
    $children = $DB->get_records('local_parentmanager_rel', ['parentid' => $parentid]);
    
    // Unassign parent role from all children's user contexts.
    foreach ($children as $child) {
        local_parentmanager_unassign_parent_role($parentid, $child->childid);
    }

    // Remove all relationships.
    $DB->delete_records('local_parentmanager_rel', ['parentid' => $parentid]);

    // Update profile field.
    return local_parentmanager_update_parent_status($parentid, 'No');
}

/**
 * Serves the form fragments for modal display.
 *
 * @param array $args Arguments passed to the fragment
 * @return string HTML output
 */
function local_parentmanager_output_fragment_assign_form($args) {
    global $CFG;
    
    require_once($CFG->dirroot . '/local/parentmanager/classes/assign_form.php');
    
    $parentid = isset($args['parentid']) ? (int)$args['parentid'] : 0;
    
    $customdata = [
        'parentid' => $parentid,
    ];
    
    $form = new local_parentmanager_assign_form(null, $customdata);
    
    return $form->render();
}

/**
 * Mark users as parents by updating their profile field.
 *
 * @param array $userids Array of user IDs to mark as parents
 * @return bool Success status
 */
function local_parentmanager_mark_as_parents($userids) {
    $success = true;
    foreach ($userids as $userid) {
        $result = local_parentmanager_update_parent_status($userid, 'Yes');
        if (!$result) {
            $success = false;
        }
    }
    return $success;
}

/**
 * Assign parent role to a user in child's user context.
 *
 * @param int $parentid Parent user ID
 * @param int $childid Child user ID
 * @return bool Success status
 */
function local_parentmanager_assign_parent_role($parentid, $childid) {
    // Check if auto role assignment is enabled.
    $autoroleassign = get_config('local_parentmanager', 'autoroleassign');
    if (!$autoroleassign) {
        return true; // Feature disabled, return success.
    }

    // Get the configured parent role.
    $roleid = get_config('local_parentmanager', 'parentrole');
    if (empty($roleid)) {
        return true; // No role configured, return success.
    }

    // Get child's user context.
    $context = context_user::instance($childid);

    try {
        // Check if role assignment already exists.
        if (!user_has_role_assignment($parentid, $roleid, $context->id)) {
            // Assign the role.
            role_assign($roleid, $parentid, $context->id, 'local_parentmanager');
        }
        return true;
    } catch (Exception $e) {
        debugging('Failed to assign parent role: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Unassign parent role from child's user context.
 *
 * @param int $parentid Parent user ID
 * @param int $childid Child user ID
 * @return bool Success status
 */
function local_parentmanager_unassign_parent_role($parentid, $childid) {
    // Get the configured parent role.
    $roleid = get_config('local_parentmanager', 'parentrole');
    if (empty($roleid)) {
        return true; // No role configured.
    }

    // Get child's user context.
    $context = context_user::instance($childid);

    try {
        // Unassign the role (only those assigned by this plugin).
        role_unassign($roleid, $parentid, $context->id, 'local_parentmanager');
        return true;
    } catch (Exception $e) {
        debugging('Failed to unassign parent role: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}