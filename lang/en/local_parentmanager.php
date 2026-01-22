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
 * English language strings for Parent Manager plugin.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Parent Manager';
$string['manageparents'] = 'Manage Parents';
$string['parentmanager:manage'] = 'Manage parent-child relationships';
$string['assignedchildrenforparent'] = 'List of assigned children for parent';

// Table headers.
$string['assignedchildren'] = 'Number of Assigned Children';

// Actions.
$string['viewchildren'] = 'View Assigned Children';
$string['assign'] = 'Assign';
$string['assignchild'] = 'Assign Child';
$string['removeparent'] = 'Remove Parent';

// Messages.
$string['noparents'] = 'No parent users found. Users must have the "is_parent" custom profile field set to "Yes".';
$string['nochildren'] = 'No children assigned to this parent.';
$string['nousersavailable'] = 'No users available to assign. All users are either already assigned to a parent or marked as parents themselves.';
$string['noselectederror'] = 'Please select at least one user to assign.';

// Confirmations.
$string['confirmremovechild'] = 'Are you sure you want to remove this child from the parent?';
$string['confirmremoveparent'] = 'Are you sure you want to remove parent status from {$a}? This will also remove all child assignments.';

// Success messages.
$string['childremoved'] = 'Child successfully removed from parent.';
$string['childrenassigned'] = 'Children successfully assigned to parent.';
$string['parentremoved'] = 'Parent status removed successfully.';

// Privacy.
$string['privacy:metadata:local_parentmanager_rel'] = 'Information about parent-child relationships';
$string['privacy:metadata:local_parentmanager_rel:parentid'] = 'The ID of the parent user';
$string['privacy:metadata:local_parentmanager_rel:childid'] = 'The ID of the child user';
$string['privacy:metadata:local_parentmanager_rel:timecreated'] = 'The time when the relationship was created';
