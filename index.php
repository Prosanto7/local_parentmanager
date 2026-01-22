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
 * Parent Manager main page.
 *
 * @package    local_parentmanager
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/mark_parent_form.php');

admin_externalpage_setup('local_parentmanager');

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$sort = optional_param('tsort', 'lastname', PARAM_ALPHA);
$dir = optional_param('tdir', SORT_ASC, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/parentmanager/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_parentmanager'));
$PAGE->set_heading(get_string('manageparents', 'local_parentmanager'));

require_login();
require_capability('local/parentmanager:manage', context_system::instance());

// Handle form submission for marking users as parents.
$markform = new local_parentmanager_mark_parent_form();

if ($markform->is_cancelled()) {
    redirect(new moodle_url('/local/parentmanager/index.php'));
} else if ($data = $markform->get_data()) {
    if (!empty($data->userids)) {
        $success = local_parentmanager_mark_as_parents($data->userids);
        if ($success) {
            redirect(new moodle_url('/local/parentmanager/index.php'),
                get_string('usersmarkedasparents', 'local_parentmanager'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('/local/parentmanager/index.php'),
                get_string('error'),
                null,
                \core\output\notification::NOTIFY_ERROR);
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageparents', 'local_parentmanager'));

echo html_writer::empty_tag('hr');

// Section 1: Add Parent.
echo $OUTPUT->heading(get_string('addparent', 'local_parentmanager'), 3);
echo html_writer::start_div('mb-4');
$markform->display();
echo html_writer::end_div();

echo html_writer::empty_tag('hr');

// Section 2: Parent List.
echo $OUTPUT->heading(get_string('parentlist', 'local_parentmanager'), 3);

// Build SQL query for parents with search.
$params = ['shortname' => 'is_parent', 'isparent' => 'Yes'];
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.lastaccess,
               (SELECT COUNT(*) FROM {local_parentmanager_rel} pr WHERE pr.parentid = u.id) as childcount
        FROM {user} u
        JOIN {user_info_data} uid ON u.id = uid.userid
        JOIN {user_info_field} uif ON uid.fieldid = uif.id
        WHERE uif.shortname = :shortname
        AND uid.data = :isparent
        AND u.deleted = 0";

if (!empty($search)) {
    $sql .= " AND (" . $DB->sql_like('u.firstname', ':search1', false) . " OR " .
                       $DB->sql_like('u.lastname', ':search2', false) . " OR " .
                       $DB->sql_like('u.email', ':search3', false) . ")";
    $params['search1'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
}

// Determine sorting.
$allowedsort = ['firstname', 'lastname', 'email', 'childcount', 'lastaccess'];
if (!in_array($sort, $allowedsort)) {
    $sort = 'lastname';
}
$direction = ($dir == SORT_ASC) ? 'ASC' : 'DESC';
$sql .= " ORDER BY u.$sort $direction";

// Get parent records.
$parents = $DB->get_records_sql($sql, $params, $perpage * $page, $perpage);

// Count total parents.
$countsql = "SELECT COUNT(u.id)
             FROM {user} u
             JOIN {user_info_data} uid ON u.id = uid.userid
             JOIN {user_info_field} uif ON uid.fieldid = uif.id
             WHERE uif.shortname = :shortname
             AND uid.data = :isparent
             AND u.deleted = 0";

$countparams = ['shortname' => 'is_parent', 'isparent' => 'Yes'];
if (!empty($search)) {
    $countsql .= " AND (" . $DB->sql_like('u.firstname', ':search1', false) . " OR " .
                            $DB->sql_like('u.lastname', ':search2', false) . " OR " .
                            $DB->sql_like('u.email', ':search3', false) . ")";
    $countparams['search1'] = "%$search%";
    $countparams['search2'] = "%$search%";
    $countparams['search3'] = "%$search%";
}
$totalparents = $DB->count_records_sql($countsql, $countparams);

// Set the base URL for pagination and sorting.
$baseurl = new moodle_url('/local/parentmanager/index.php', [
    'perpage' => $perpage,
    'search' => $search,
]);

// Render search box.
$searchdata = [
    'action' => $baseurl,
    'inputname' => 'search',
    'searchstring' => get_string('search'),
    'query' => $search,
];
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
echo html_writer::start_div('search-box');
echo $OUTPUT->render_from_template('core/search_input', $searchdata);
echo html_writer::end_div();
echo html_writer::end_div();

if (empty($parents)) {
    echo $OUTPUT->notification(get_string('noparents', 'local_parentmanager'), 'info');
} else {
    // Create flexible table.
    $table = new flexible_table('local_parentmanager_parents');
    $table->define_columns(['fullname', 'email', 'childcount', 'lastaccess', 'actions']);
    $table->define_headers([
        get_string('fullname'),
        get_string('email'),
        get_string('assignedchildren', 'local_parentmanager'),
        get_string('lastaccess'),
        get_string('actions'),
    ]);
    
    $table->define_baseurl($baseurl);
    $table->set_attribute('class', 'generaltable');
    $table->set_attribute('id', 'local_parentmanager_parents_table');
    $table->sortable(true, $sort, $dir);
    $table->no_sorting('actions');
    $table->pageable(true);
    $table->setup();
    
    foreach ($parents as $parent) {
        $row = [];
        
        // Full name with profile link.
        $profileurl = new moodle_url('/user/profile.php', ['id' => $parent->id]);
        $row[] = html_writer::link($profileurl, fullname($parent));
        
        // Email.
        $row[] = $parent->email;
        
        // Child count.
        $row[] = $parent->childcount;
        
        // Last access.
        $row[] = $parent->lastaccess ? userdate($parent->lastaccess) : get_string('never');
        
        // Actions menu.
        $actionmenu = new action_menu();
        $actionmenu->set_kebab_trigger(get_string('actions'));
        
        // View children action.
        $viewaction = new action_menu_link_primary(
            new moodle_url('#'),
            new pix_icon('i/preview', '', 'moodle'),
            get_string('viewchildren', 'local_parentmanager'),
            [
                'data-action' => 'view-children',
                'data-parentid' => $parent->id,
                'data-parentname' => fullname($parent),
            ]
        );
        $actionmenu->add($viewaction);
        
        // Assign child action.
        $assignaction = new action_menu_link_primary(
            new moodle_url('#'),
            new pix_icon('i/user', '', 'moodle'),
            get_string('assignchild', 'local_parentmanager'),
            [
                'data-action' => 'assign-child',
                'data-parentid' => $parent->id,
                'data-parentname' => fullname($parent),
            ]
        );
        $actionmenu->add($assignaction);
        
        // Remove parent action.
        $removeaction = new action_menu_link_primary(
            new moodle_url('#'),
            new pix_icon('i/delete', '', 'moodle'),
            get_string('removeparent', 'local_parentmanager'),
            [
                'data-action' => 'remove-parent',
                'data-parentid' => $parent->id,
                'data-parentname' => fullname($parent),
                'class' => 'text-danger',
            ]
        );
        $actionmenu->add($removeaction);
        
        $row[] = $OUTPUT->render($actionmenu);
        
        $table->add_data($row);
    }
    
    $table->finish_output();
    
    // Render pagination.
    echo $OUTPUT->paging_bar($totalparents, $page, $perpage, $baseurl);
}

// Add JS module for actions (view children, assign child, remove parent).
$PAGE->requires->js_call_amd('local_parentmanager/actions', 'init');

echo $OUTPUT->footer();
