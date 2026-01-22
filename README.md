# Parent Manager Plugin for Moodle

A local Moodle plugin for managing parent-child relationships, allowing administrators to assign students to parent/guardian users for monitoring their progress.

## Features

- Manage parent-child relationships through an intuitive interface
- View all parent users using Moodle's flexible table
- Built-in search functionality with real-time filtering
- Sortable columns for easy navigation
- Pagination for large datasets
- Assign multiple children to a parent user via Moodle modal dialogs
- View all children assigned to a parent
- Remove children from parents
- Remove parent status from users
- Full AJAX-based interactions using Moodle's modal factory
- Privacy API compliant
- Moodle coding standards compliant

## Requirements

- Moodle 3.9 or higher
- A custom user profile field named `is_parent` with dropdown values "Yes" and "No"

## Installation

1. Copy the `parentmanager` folder to your Moodle's `/local/` directory
2. Visit Site administration → Notifications to install the plugin
3. Create a custom user profile field:
   - Go to Site administration → Users → User profile fields
   - Add a new "Dropdown menu" field
   - Short name: `is_parent`
   - Name: `Is Parent`
   - Options (one per line):
     ```
     Yes
     No
     ```
4. Mark users as parents by setting the "Is Parent" field to "Yes" in their profile

## Usage

### For Administrators

1. Navigate to Site administration → Local plugins → Parent Manager
2. View the list of all parent users
3. Use the three-dot menu for each parent to:
   - **View Assigned Children**: See all children currently assigned to the parent
   - **Assign Child**: Add new children to the parent (select multiple users from modal)
   - **Remove Parent**: Remove parent status and all child assignments

### Assigning Children

1. Click "Assign Child" from the parent's action menu
2. A modal will open showing all available users (not assigned to any parent)
3. Select one or more users using checkboxes
4. Click "Save" to assign them to the parent

### Viewing Children

1. Click "View Assigned Children" from the parent's action menu
2. A modal will display all children with their details
3. Use the "Remove" button to unassign a child from the parent

### Removing Parent Status

1. Click "Remove Parent" from the parent's action menu
2. Confirm the action in the confirmation dialog
3. The user's parent status will be set to "No" and all child assignments will be removed

## Capabilities

- `local/parentmanager:manage` - Manage parent-child relationships (assigned to managers by default)

## Database Tables

- `local_parentmanager_rel` - Stores parent-child relationships
  - `id` - Primary key
  - `parentid` - ID of the parent user
  - `childid` - ID of the child user
  - `timecreated` - Timestamp when relationship was created

## Web Services

The plugin provides the following AJAX-enabled web services:

- `local_parentmanager_get_children` - Get children assigned to a parent
- `local_parentmanager_get_unassigned_users` - Get users not assigned to any parent
- `local_parentmanager_assign_children` - Assign children to a parent
- `local_parentmanager_remove_child` - Remove a child from a parent
- `local_parentmanager_remove_parent` - Remove parent status

## Privacy

The plugin is compliant with Moodle's Privacy API and includes:
- Data export for parent-child relationships
- Data deletion for individual users
- Data deletion for all users in context

## License

GPL v3 or later

## Copyright

2026

## Support

For issues, questions, or feature requests, please contact your system administrator.

## Technical Details

### File Structure

```
local/parentmanager/
├── amd/
│   └── src/
│       └── actions.js          # JavaScript module for AJAX interactions
├── classes/
│   └── privacy/
│       └── provider.php        # Privacy API implementation
├── db/
│   ├── access.php              # Capabilities definition
│   ├── install.xml             # Database schema
│   ├── services.php            # Web services definition
│   └── upgrade.php             # Upgrade script
├── lang/
│   └── en/
│       └── local_parentmanager.php  # English language strings
├── templates/
│   └── parentlist.mustache     # Parent list template
├── externallib.php             # External API functions
├── index.php                   # Main page
├── lib.php                     # Library functions
├── README.md                   # This file
├── settings.php                # Plugin settings
├── styles.css                  # Plugin styles
└── version.php                 # Plugin version information
```

### How It Works

1. The plugin reads the `is_parent` custom profile field to identify parent users
2. Parent-child relationships are stored in the `local_parentmanager_rel` table
3. The main page displays all parent users with their assigned child counts
4. AJAX calls handle all interactions (viewing, assigning, removing)
5. Bootstrap modals provide the user interface for interactions
6. All actions require the `local/parentmanager:manage` capability

## Future Enhancements

Possible future features could include:
- Parent dashboard for parents to view their children
- Email notifications when children are assigned/removed
- Reports on parent-child relationships
- Bulk assignment tools
- Integration with course enrollment
- Custom permissions for parent role capabilities
