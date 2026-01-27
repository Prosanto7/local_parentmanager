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
- **Automatic role assignment in child's user context**
- Full AJAX-based interactions using Moodle's modal factory
- Privacy API compliant
- Moodle coding standards compliant

## Requirements

- Moodle 3.9 or higher
- A custom user profile field named `is_parent` with dropdown values "Yes" and "No"
- A custom role configured to be assigned at the user context level (for parent role assignment)

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
5. **The configured parent role will be automatically assigned in each child's user context**

### Viewing Children

1. Click "View Assigned Children" from the parent's action menu
2. A modal will display all children with their details
3. Use the "Remove" button to unassign a child from the parent
4. **The parent role will be automatically unassigned from the child's user context**

### Removing Parent Status

1. Click "Remove Parent" from the parent's action menu
2. Confirm the action in the confirmation dialog
3. The user's parent status will be set to "No" and all child assignments will be removed
4. **All parent role assignments will be automatically cleaned up**

## Automatic Role Assignment

When a child is assigned to a parent, this plugin can automatically assign a parent role in the child's user context, allowing the parent to access their child's information based on the role's capabilities.

### Setup for Automatic Role Assignment

1. **Create a Custom Role**
   - Go to Site administration → Users → Permissions → Define roles
   - Click "Add a new role"
   - Set the role name (e.g., "Parent")
   - Under "Context types where this role may be assigned", **check "User"**
   - Configure appropriate capabilities:
     - `moodle/user:viewdetails` - View user profiles
     - `moodle/user:viewalldetails` - View all user profile details
     - Add other capabilities as needed for your use case
   - Save the role

2. **Configure the Plugin**
   - Go to Site administration → Plugins → Local plugins → Parent Manager
   - Under "Parent role", select the role you created (only roles assignable at user context are shown)
   - Ensure "Enable automatic role assignment" is checked
   - Save changes

3. **How It Works**
   - When you assign a child to a parent, the plugin automatically calls `role_assign($roleid, $parentid, $childcontext->id, 'local_parentmanager')`
   - The role is assigned in the child's user context (accessed via URL: `/admin/roles/assign.php?contextid={childcontextid}`)
   - When you remove the relationship, the plugin calls `role_unassign()` with the component identifier
   - This ensures only roles assigned by this plugin are removed

4. **Database Storage**
   - Role assignments are stored in Moodle's standard `role_assignments` table:
     - `roleid`: The parent role ID
     - `userid`: The parent user ID
     - `contextid`: The child user's context ID
     - `component`: 'local_parentmanager' (identifies this plugin as the source)
   - You can view these assignments at: `/admin/roles/assign.php?contextid={childcontextid}`

### Example Use Case

1. Create a "Parent" role with permission to view user details at the user context
2. Configure the plugin to use this role
3. Assign student John (userid=13) to parent Mary (userid=25)
4. Mary automatically receives the "Parent" role in John's user context (contextid=100)
5. Mary can now view John's profile and information based on the role's capabilities
6. The role assignment can be verified at: `/admin/roles/assign.php?contextid=100&roleid=11`

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
3. **When a child is assigned, the parent role is automatically assigned using Moodle's `role_assign()` function**
4. **Role assignments are tracked with component='local_parentmanager' in the `role_assignments` table**
5. The main page displays all parent users with their assigned child counts
6. AJAX calls handle all interactions (viewing, assigning, removing)
7. Bootstrap modals provide the user interface for interactions
8. All actions require the `local/parentmanager:manage` capability
9. **When relationships are removed, only roles assigned by this plugin (component='local_parentmanager') are unassigned**

### Key Functions

- `local_parentmanager_assign_children($parentid, $childids)` - Assigns children and automatically assigns the parent role
- `local_parentmanager_remove_child($relationid)` - Removes a child and unassigns the parent role
- `local_parentmanager_assign_parent_role($parentid, $childid)` - Assigns the configured role in the child's user context
- `local_parentmanager_unassign_parent_role($parentid, $childid)` - Unassigns the role from the child's user context

## Future Enhancements

Possible future features could include:
- Parent dashboard for parents to view their children
- Email notifications when children are assigned/removed
- Reports on parent-child relationships
- Bulk assignment tools
- Integration with course enrollment
- Custom permissions for parent role capabilities
