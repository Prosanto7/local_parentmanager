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
 * Parent Manager plugin external services.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_parentmanager_get_children' => [
        'classname'   => 'local_parentmanager_external',
        'methodname'  => 'get_children',
        'classpath'   => 'local/parentmanager/externallib.php',
        'description' => 'Get children assigned to a parent',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_parentmanager_get_unassigned_users' => [
        'classname'   => 'local_parentmanager_external',
        'methodname'  => 'get_unassigned_users',
        'classpath'   => 'local/parentmanager/externallib.php',
        'description' => 'Get all users not assigned to any parent',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_parentmanager_assign_children' => [
        'classname'   => 'local_parentmanager_external',
        'methodname'  => 'assign_children',
        'classpath'   => 'local/parentmanager/externallib.php',
        'description' => 'Assign children to a parent',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_parentmanager_remove_child' => [
        'classname'   => 'local_parentmanager_external',
        'methodname'  => 'remove_child',
        'classpath'   => 'local/parentmanager/externallib.php',
        'description' => 'Remove a child from a parent',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_parentmanager_remove_parent' => [
        'classname'   => 'local_parentmanager_external',
        'methodname'  => 'remove_parent',
        'classpath'   => 'local/parentmanager/externallib.php',
        'description' => 'Remove parent status',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
