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
 * JavaScript for Parent Manager plugin actions.
 *
 * @module     local_parentmanager/actions
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/modal', 'core/modal_save_cancel', 'core/modal_events', 'core/str'],
    function($, Ajax, Notification, Modal, ModalSaveCancel, ModalEvents, Str) {

    /**
     * Initialize the module.
     */
    var init = function() {
        // Handle "View Children" action.
        $(document).on('click', '[data-action="view-children"]', function(e) {
            e.preventDefault();
            var parentId = $(this).data('parentid');
            var parentName = $(this).data('parentname');
            viewChildren(parentId, parentName);
        });

        // Handle "Assign Child" action.
        $(document).on('click', '[data-action="assign-child"]', function(e) {
            e.preventDefault();
            var parentId = $(this).data('parentid');
            var parentName = $(this).data('parentname');
            assignChild(parentId, parentName);
        });

        // Handle "Remove Parent" action.
        $(document).on('click', '[data-action="remove-parent"]', function(e) {
            e.preventDefault();
            var parentId = $(this).data('parentid');
            var parentName = $(this).data('parentname');
            removeParent(parentId, parentName);
        });
    };

    /**
     * View children assigned to a parent.
     *
     * @param {int} parentId Parent user ID
     * @param {string} parentName Parent name
     */
    var viewChildren = function(parentId, parentName) {
        Str.get_strings([
            {key: 'assignedchildrenforparent', component: 'local_parentmanager'},
            {key: 'nochildren', component: 'local_parentmanager'},
            {key: 'remove', component: 'core'},
            {key: 'close', component: 'core'}
        ]).done(function(strings) {
            Ajax.call([{
                methodname: 'local_parentmanager_get_children',
                args: {parentid: parentId}
            }])[0].done(function(response) {
                var body = '<div class="parent-children-list">';

                if (response.children.length === 0) {
                    body += '<p>' + strings[1] + '</p>';
                } else {
                    body += '<table class="table table-striped">';
                    body += '<thead><tr><th>Name</th><th>Email</th><th>Actions</th></tr></thead>';
                    body += '<tbody>';

                    response.children.forEach(function(child) {
                        body += '<tr>';
                        body += '<td><a href="' + child.profileurl + '" target="_blank">' + child.fullname + '</a></td>';
                        body += '<td>' + child.email + '</td>';
                        body += '<td><button class="btn btn-sm btn-danger" data-action="remove-child" ' +
                                'data-relationid="' + child.relationid + '">' + strings[2] + '</button></td>';
                        body += '</tr>';
                    });

                    body += '</tbody></table>';
                }

                body += '</div>';

                Modal.create({
                    title: strings[0] + ': ' + parentName,
                    body: body,
                    show: true,
                    removeOnClose: true,
                    large: true,
                }).then(function(modal) {
                    // Handle remove child within modal.
                    modal.getRoot().on('click', '[data-action="remove-child"]', function(e) {
                        e.preventDefault();
                        var relationId = $(this).data('relationid');
                        removeChild(relationId, modal);
                    });
                });
            }).fail(Notification.exception);
        });
    };

    /**
     * Remove a child from a parent.
     *
     * @param {int} relationId Relation ID
     */
    var removeChild = function(relationId) {
        Str.get_strings([
            {key: 'confirm', component: 'core'},
            {key: 'confirmremovechild', component: 'local_parentmanager'},
            {key: 'yes', component: 'core'},
            {key: 'no', component: 'core'},
            {key: 'childremoved', component: 'local_parentmanager'}
        ]).done(function(strings) {
            Notification.confirm(strings[0], strings[1], strings[2], strings[3], function() {
                Ajax.call([{
                    methodname: 'local_parentmanager_remove_child',
                    args: {relationid: relationId}
                }])[0].done(function(response) {
                    if (response.success) {
                        window.location.reload();
                    }
                }).fail(Notification.exception);
            });
        });
    };

    /**
     * Assign children to a parent.
     *
     * @param {int} parentId Parent user ID
     * @param {string} parentName Parent name
     */
    var assignChild = function(parentId, parentName) {
        require(['core/fragment'], function(Fragment) {
            Str.get_strings([
                {key: 'assignchild', component: 'local_parentmanager'},
                {key: 'assign', component: 'local_parentmanager'},
                {key: 'childrenassigned', component: 'local_parentmanager'}
            ]).done(function(strings) {
                var formPromise = Fragment.loadFragment(
                    'local_parentmanager',
                    'assign_form',
                    1,
                    {parentid: parentId}
                );

                // Create modal with the form as body.
                ModalSaveCancel.create({
                    title: strings[0] + ': ' + parentName,
                    body: formPromise,
                    show: true,
                    removeOnClose: true,
                    large: true,
                }).then(function(modal) {
                    modal.setSaveButtonText(strings[1]);

                    // Handle form submission.
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();

                        // Get form data.
                        var form = modal.getRoot().find('form');
                        var selectedIds = form.find('[name="childuserids[]"]').val();

                        if (!selectedIds || selectedIds.length === 0) {
                            Str.get_string('noselectederror', 'local_parentmanager').done(function(errorMsg) {
                                Notification.addNotification({
                                    message: errorMsg,
                                    type: 'warning'
                                });
                            });
                            return;
                        }

                        // Convert to integers.
                        selectedIds = selectedIds.map(function(id) {
                            return parseInt(id);
                        });

                        // Submit via AJAX.
                        Ajax.call([{
                            methodname: 'local_parentmanager_assign_children',
                            args: {
                                parentid: parentId,
                                childids: selectedIds
                            }
                        }])[0].done(function(response) {
                            if (response.success) {
                                modal.hide();
                                // Reload the page.
                                window.location.reload();
                            }
                        }).fail(Notification.exception);
                    });
                });
            });
        });
    };

    /**
     * Remove parent status.
     *
     * @param {int} parentId Parent user ID
     * @param {string} parentName Parent name
     */
    var removeParent = function(parentId, parentName) {
        Str.get_strings([
            {key: 'confirm', component: 'core'},
            {key: 'confirmremoveparent', component: 'local_parentmanager'},
            {key: 'yes', component: 'core'},
            {key: 'no', component: 'core'},
            {key: 'parentremoved', component: 'local_parentmanager'}
        ]).done(function(strings) {
            var confirmMessage = strings[1].replace('{$a}', parentName);
            Notification.confirm(strings[0], confirmMessage, strings[2], strings[3], function() {
                Ajax.call([{
                    methodname: 'local_parentmanager_remove_parent',
                    args: {parentid: parentId}
                }])[0].done(function(response) {
                    if (response.success) {
                        // Reload the page.
                        window.location.reload();
                    }
                }).fail(Notification.exception);
            });
        });
    };

    return {
        init: init
    };
});
