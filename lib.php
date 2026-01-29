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
 * Get all parent users based on parent table.
 *
 * @return array Array of parent users
 */
function local_parentmanager_get_parents() {
    global $DB;

    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.lastaccess,
                   (SELECT COUNT(*) FROM {local_parentmanager_rel} pr WHERE pr.parentid = u.id) as childcount
            FROM {user} u
            JOIN {local_parentmanager_parents} p ON u.id = p.userid
            WHERE u.deleted = 0
            ORDER BY u.lastname, u.firstname";

    return $DB->get_records_sql($sql);
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
            WHERE u.deleted = 0
            AND u.id NOT IN (SELECT childid FROM {local_parentmanager_rel})
            AND u.id NOT IN (SELECT userid FROM {local_parentmanager_parents})
            ORDER BY u.lastname, u.firstname";

    return $DB->get_records_sql($sql);
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
 * Update user's parent status in the parent table.
 *
 * @param int $userid User ID
 * @param bool $isparent True to mark as parent, false to remove parent status
 * @return bool Success status
 */
function local_parentmanager_update_parent_status($userid, $isparent) {
    global $DB;

    $exists = $DB->record_exists('local_parentmanager_parents', ['userid' => $userid]);

    if ($isparent) {
        // Add user as parent if not already exists.
        if (!$exists) {
            $record = new stdClass();
            $record->userid = $userid;
            $record->timecreated = time();
            $record->timemodified = time();
            return $DB->insert_record('local_parentmanager_parents', $record) ? true : false;
        }
        return true; // Already a parent.
    } else {
        // Remove parent status.
        if ($exists) {
            return $DB->delete_records('local_parentmanager_parents', ['userid' => $userid]);
        }
        return true; // Already not a parent.
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

    // Remove from parent table.
    return local_parentmanager_update_parent_status($parentid, false);
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
 * Mark users as parents by adding them to the parent table.
 *
 * @param array $userids Array of user IDs to mark as parents
 * @return bool Success status
 */
function local_parentmanager_mark_as_parents($userids) {
    $success = true;
    foreach ($userids as $userid) {
        $result = local_parentmanager_update_parent_status($userid, true);
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


/**
 * Check if a user is marked as a parent.
 *
 * @param int $userid User ID
 * @return bool True if user is a parent, false otherwise
 */
function local_parentmanager_is_parent($userid) {
    global $DB;

    return $DB->record_exists('local_parentmanager_parents', ['userid' => $userid]);
}